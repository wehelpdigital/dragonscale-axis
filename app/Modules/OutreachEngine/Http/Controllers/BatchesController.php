<?php

namespace App\Modules\OutreachEngine\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\OutreachEngine\Models\OutreachBatch;
use App\Modules\OutreachEngine\Models\OutreachLead;
use App\Modules\OutreachEngine\Services\BatchProgressService;
use App\Modules\OutreachEngine\Services\SettingsResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Batch Search: every sweep this account has run, and how far each one got.
 *
 * A sweep is named on this screen, so a uuid becomes "La Union resorts - Q3"
 * and the leads it produced can be found again six months later.
 *
 * The counters are read from the cached columns rather than recomputed per row,
 * which is the whole reason those columns exist. index() refreshes the ones
 * still in flight first, so what is drawn is current without making a finished
 * sweep pay for a recount it does not need.
 */
class BatchesController extends Controller
{
    /** Sweeps per page. */
    const PER_PAGE = 20;

    /**
     * Display the batch list.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $userId = (int) Auth::id();

        // Only unfinished sweeps are recomputed. A completed one cannot change
        // on its own, and refreshing every row would put a dozen aggregates on
        // every page load for no new information.
        try {
            $settings = (new SettingsResolver())->forUser($userId);
            (new BatchProgressService())->refreshForUser(
                $userId,
                (bool) ($settings->verificationEnabled ?? true)
            );
        } catch (\Throwable $e) {
            // A stale counter is a cosmetic problem; failing the page is not.
            Log::warning('[OutreachEngine] Batch list refresh skipped: ' . $e->getMessage());
        }

        return view('outreach::batches', [
            'statuses' => OutreachBatch::getStatusLabels(),
            'totalBatches' => OutreachBatch::query()->active()->forUser($userId)->count(),
            'completeBatches' => OutreachBatch::query()->active()->forUser($userId)->complete()->count(),
        ]);
    }

    /**
     * The batch list as JSON, for the table and its poller.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function data(Request $request)
    {
        try {
            $userId = (int) Auth::id();

            $query = OutreachBatch::query()->active()->forUser($userId);

            if ($request->filled('status')) {
                $status = (string) $request->input('status');

                if (in_array($status, OutreachBatch::STATUSES, true)) {
                    $query->where('status', $status);
                }
            }

            if ($request->filled('search')) {
                $like = '%' . trim((string) $request->input('search')) . '%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('name', 'like', $like)
                        ->orWhere('businessType', 'like', $like)
                        ->orWhere('regionLabel', 'like', $like);
                });
            }

            $batches = $query->orderByDesc('created_at')
                ->paginate(self::PER_PAGE)
                ->through(function (OutreachBatch $batch) {
                    return [
                        'id' => (int) $batch->id,
                        'batchId' => (string) $batch->batchId,
                        'name' => (string) ($batch->name ?? ''),
                        'displayName' => $batch->display_name,
                        'businessType' => (string) $batch->businessType,
                        'regionLabel' => (string) $batch->regionLabel,
                        'radiusKm' => (float) $batch->radiusKm,
                        'status' => (string) $batch->status,
                        'statusBadge' => $batch->status_badge,
                        'progress' => $batch->progress_percent,
                        'totalCells' => (int) $batch->totalCells,
                        'pendingCells' => (int) $batch->pendingCells,
                        'totalLeads' => (int) $batch->totalLeads,
                        'leadsWithEmail' => (int) $batch->leadsWithEmail,
                        'leadsVerified' => (int) $batch->leadsVerified,
                        'leadsValid' => (int) $batch->leadsValid,
                        'startedAt' => $batch->startedAt ? $batch->startedAt->format('Y-m-d H:i') : null,
                        'completedAt' => $batch->completedAt ? $batch->completedAt->format('Y-m-d H:i') : null,
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Batches loaded.',
                'data' => $batches,
            ]);
        } catch (\Exception $e) {
            Log::error('[OutreachEngine] Batch data failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Could not load the batch list.',
            ], 500);
        }
    }

    /**
     * Rename one sweep.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function rename(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|integer',
                'name' => 'nullable|string|max:190',
            ], [
                'id.required' => 'Which batch should be renamed?',
                'name.max' => 'Keep the name under 190 characters.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors(),
                ], 422);
            }

            $batch = $this->findOwned((int) $request->input('id'));

            if (!$batch) {
                return response()->json(['success' => false, 'message' => 'Batch not found.'], 404);
            }

            // An emptied field clears the custom name rather than storing '' -
            // display_name then falls back to the business type and region,
            // which is more useful than a blank row.
            $name = trim((string) $request->input('name'));
            $batch->name = $name === '' ? null : $name;
            $batch->save();

            return response()->json([
                'success' => true,
                'message' => 'Batch renamed.',
                'data' => ['name' => (string) ($batch->name ?? ''), 'displayName' => $batch->display_name],
            ]);
        } catch (\Exception $e) {
            Log::error('[OutreachEngine] Batch rename failed: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Could not rename this batch.'], 500);
        }
    }

    /**
     * One batch with a sample of the verified-good leads it produced.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        try {
            $batch = $this->findOwned((int) $request->input('id'));

            if (!$batch) {
                return response()->json(['success' => false, 'message' => 'Batch not found.'], 404);
            }

            (new BatchProgressService())->refresh($batch);

            $leads = OutreachLead::query()
                ->active()
                ->forUser((int) Auth::id())
                ->where('batchId', $batch->batchId)
                ->verifiedValid()
                ->orderBy('businessName')
                ->limit(50)
                ->get(['id', 'businessName', 'aiCategory', 'category', 'city', 'province', 'email', 'phone', 'website']);

            return response()->json([
                'success' => true,
                'message' => 'Batch loaded.',
                'data' => [
                    'batch' => [
                        'id' => (int) $batch->id,
                        'batchId' => (string) $batch->batchId,
                        'displayName' => $batch->display_name,
                        'name' => (string) ($batch->name ?? ''),
                        'businessType' => (string) $batch->businessType,
                        'regionLabel' => (string) $batch->regionLabel,
                        'status' => (string) $batch->status,
                        'statusBadge' => $batch->status_badge,
                        'progress' => $batch->progress_percent,
                        'totalCells' => (int) $batch->totalCells,
                        'totalLeads' => (int) $batch->totalLeads,
                        'leadsWithEmail' => (int) $batch->leadsWithEmail,
                        'leadsVerified' => (int) $batch->leadsVerified,
                        'leadsValid' => (int) $batch->leadsValid,
                        'completedAt' => $batch->completedAt ? $batch->completedAt->format('Y-m-d H:i') : null,
                    ],
                    'leads' => $leads->map(function (OutreachLead $lead) {
                        return [
                            'id' => (int) $lead->id,
                            'businessName' => (string) $lead->businessName,
                            'category' => $lead->display_category,
                            'location' => $lead->display_location,
                            'email' => (string) $lead->email,
                            'phone' => (string) $lead->phone,
                            'website' => (string) $lead->website,
                        ];
                    })->all(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[OutreachEngine] Batch show failed: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Could not load this batch.'], 500);
        }
    }

    /**
     * Soft-delete a batch record.
     *
     * The leads it found are deliberately left alone: they are deduplicated
     * globally by placeId, so deleting them would let a later sweep re-scrape
     * and re-enrich businesses that have already been paid for once.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request)
    {
        try {
            $batch = $this->findOwned((int) $request->input('id'));

            if (!$batch) {
                return response()->json(['success' => false, 'message' => 'Batch not found.'], 404);
            }

            $batch->update(['delete_status' => 'deleted']);

            return response()->json([
                'success' => true,
                'message' => 'Batch removed from the list. Its leads were kept.',
            ]);
        } catch (\Exception $e) {
            Log::error('[OutreachEngine] Batch delete failed: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Could not remove this batch.'], 500);
        }
    }

    /**
     * A batch belonging to the signed-in account, or null.
     */
    private function findOwned(int $id): ?OutreachBatch
    {
        if ($id <= 0) {
            return null;
        }

        return OutreachBatch::query()
            ->active()
            ->forUser((int) Auth::id())
            ->where('id', $id)
            ->first();
    }
}
