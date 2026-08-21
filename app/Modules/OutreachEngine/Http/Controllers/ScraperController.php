<?php

namespace App\Modules\OutreachEngine\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\OutreachEngine\Models\OutreachLead;
use App\Modules\OutreachEngine\Models\OutreachSearchGrid;
use App\Modules\OutreachEngine\Services\GeoGridService;
use App\Modules\OutreachEngine\Services\GridScrapeService;
use App\Modules\OutreachEngine\Services\SettingsResolver;
use App\Modules\OutreachEngine\Support\OutreachException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Region sweeps: lay a grid over a province, then work the cells.
 *
 * Cron (outreach:scrape-grids) is the real engine. runBatch() exists so the screen
 * still advances on a machine where the cron entry has not been installed yet - it
 * works a few cells inline, inside a wall-clock budget, and hands back exactly the
 * payload progress() would.
 */
class ScraperController extends Controller
{
    /** Cells one inline runBatch() call may work. Cron does the rest. */
    const MAX_INLINE_GRIDS = 3;

    /**
     * Wall-clock ceiling for runBatch(), in seconds. A cell can spend ~2s per page
     * token plus network time, so three of them must never be allowed to run the
     * request into a gateway timeout with nothing to show for it.
     */
    const INLINE_BUDGET_SECONDS = 45;

    /** Upper bound the start form accepts, matching the contract's validation rule. */
    const MAX_RADIUS_KM = 50.0;

    /**
     * Cell size used when a settings row carries no usable default.
     *
     * 20 km rather than 5: the cap Google enforces is 60 results per search, not
     * per square kilometre, so a sparse keyword ("resort" across Pangasinan)
     * wastes 245 near-empty searches on a 5 km grid where 27 would do. Dense
     * keywords saturate either way and the adaptive split walks them back down,
     * keeping every lead found on the way. A 5 km grid also put Palawan at 3,007
     * cells - past the 2,000-cell build cap, so part of the province was simply
     * never queued.
     */
    const DEFAULT_RADIUS_KM = 20.0;

    /**
     * Display the scraper screen.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $userId = (int) Auth::id();
        $settings = (new SettingsResolver())->forUser($userId);

        return view('outreach::scraper', [
            'settings' => $settings,
            'regions' => (new GeoGridService())->regionLabels(),
            'recentBatches' => $this->recentBatches($userId),
            'hasPlacesKey' => $settings->hasPlacesKey(),
            'defaultRadiusKm' => (float) $settings->defaultGridRadiusKm,
            'minRadiusKm' => (float) $settings->minGridRadiusKm,
            'maxRadiusKm' => self::MAX_RADIUS_KM,
        ]);
    }

    /**
     * Tile a region into pending grid cells.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function start(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'businessType' => 'required|string|max:190',
                'regionLabel' => 'required|string|max:190',
            ], [
                'businessType.required' => 'Tell us what kind of business to look for.',
                'regionLabel.required' => 'Choose a region to search.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors(),
                ], 422);
            }

            $userId = (int) Auth::id();

            try {
                $settings = (new SettingsResolver())->requireForUser($userId);
            } catch (OutreachException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            // Fail here rather than let every queued cell fail one by one later.
            if (!$settings->hasPlacesKey()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Add your Google Places API key under Lead Finder > Settings before starting a search.',
                ], 422);
            }

            $businessType = trim((string) $request->input('businessType'));
            $regionLabel = trim((string) $request->input('regionLabel'));
            // The admin no longer picks a cell size. Every sweep starts at the
            // configured default (5 km) and covers the entire province; cells that
            // come back saturated subdivide themselves down to minGridRadiusKm, so
            // the grid adapts to density instead of asking someone to guess it.
            $radiusKm = (float) $settings->defaultGridRadiusKm;

            if ($radiusKm <= 0) {
                $radiusKm = self::DEFAULT_RADIUS_KM;
            }

            $radiusKm = min(self::MAX_RADIUS_KM, max((float) $settings->minGridRadiusKm, $radiusKm));

            try {
                $batchId = (new GridScrapeService($settings))
                    ->queueRegion($userId, $businessType, $regionLabel, $radiusKm);
            } catch (OutreachException $e) {
                // Unknown region, radius that tiles to nothing - all user-fixable.
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            $progress = $this->progressPayload($userId, $batchId);

            return response()->json([
                'success' => true,
                'message' => 'Queued ' . $progress['counts']['total'] . ' search cells for ' . $regionLabel . '.',
                'data' => [
                    'batchId' => $batchId,
                    'gridCount' => $progress['counts']['total'],
                    'businessType' => $businessType,
                    'regionLabel' => $regionLabel,
                    'radiusKm' => $radiusKm,
                    'progress' => $progress,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[OutreachEngine] Scraper start failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while starting the search.',
            ], 500);
        }
    }

    /**
     * Progress for one batch: per-status cell counts plus the newest leads found.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function progress(Request $request)
    {
        try {
            $userId = (int) Auth::id();
            $batchId = $this->resolveBatchId($request, $userId);

            if ($batchId === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'No search batch found for this account yet.',
                ], 404);
            }

            $payload = $this->progressPayload($userId, $batchId);

            if ($payload['counts']['total'] === 0) {
                // Either the id is a typo or it belongs to another admin. Same answer
                // for both, so the endpoint never confirms that a batch exists.
                return response()->json([
                    'success' => false,
                    'message' => 'That search batch could not be found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Progress loaded.',
                'data' => $payload,
            ]);
        } catch (\Exception $e) {
            Log::error('[OutreachEngine] Scraper progress failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading progress.',
            ], 500);
        }
    }

    /**
     * Work up to MAX_INLINE_GRIDS pending cells of a batch right now.
     *
     * QUEUE_CONNECTION is sync, so there is no worker to hand this to: the request
     * itself does the work, bounded by INLINE_BUDGET_SECONDS. The UI is expected to
     * call this on a timer until 'finished' comes back true.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function runBatch(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'batchId' => 'required|string|max:36',
            ], [
                'batchId.required' => 'Which search batch should we run?',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors(),
                ], 422);
            }

            $userId = (int) Auth::id();
            $batchId = (string) $request->input('batchId');

            $ownsBatch = OutreachSearchGrid::query()
                ->active()
                ->forUser($userId)
                ->forBatch($batchId)
                ->exists();

            if (!$ownsBatch) {
                return response()->json([
                    'success' => false,
                    'message' => 'That search batch could not be found.',
                ], 404);
            }

            try {
                $settings = (new SettingsResolver())->requireForUser($userId);
            } catch (OutreachException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            // The default max_execution_time is shorter than the budget below, and a
            // half-processed cell is worse than a slow response.
            if (function_exists('set_time_limit')) {
                @set_time_limit(self::INLINE_BUDGET_SECONDS + 60);
            }

            $scraper = new GridScrapeService($settings);
            $deadline = microtime(true) + self::INLINE_BUDGET_SECONDS;
            $processed = 0;
            $newLeads = 0;
            $lastError = null;

            for ($i = 0; $i < self::MAX_INLINE_GRIDS; $i++) {
                if (microtime(true) >= $deadline) {
                    break;
                }

                $grid = OutreachSearchGrid::query()
                    ->active()
                    ->forUser($userId)
                    ->forBatch($batchId)
                    ->where('status', OutreachSearchGrid::STATUS_PENDING)
                    ->where('attempts', '<', OutreachSearchGrid::MAX_ATTEMPTS)
                    ->orderBy('depth')
                    ->orderBy('id')
                    ->first();

                if (!$grid) {
                    break;
                }

                // Claim it with a conditional UPDATE before touching the network. If a
                // cron tick took the same row a millisecond earlier this affects zero
                // rows, and we simply look for another cell.
                $claimed = OutreachSearchGrid::query()
                    ->where('id', $grid->id)
                    ->where('status', OutreachSearchGrid::STATUS_PENDING)
                    ->update(['status' => OutreachSearchGrid::STATUS_PROCESSING]);

                if ($claimed !== 1) {
                    continue;
                }

                $grid->refresh();
                $result = $scraper->processGrid($grid);
                $processed++;
                $newLeads += (int) ($result['new'] ?? 0);

                if (!empty($result['error'])) {
                    $lastError = (string) $result['error'];
                }
            }

            $payload = $this->progressPayload($userId, $batchId, $processed);

            if ($lastError !== null) {
                $payload['lastError'] = $lastError;
            }

            return response()->json([
                'success' => true,
                'message' => $processed === 0
                    ? 'No pending cells were available to run.'
                    : 'Processed ' . $processed . ' cell(s), found ' . $newLeads . ' new lead(s).',
                'data' => $payload,
            ]);
        } catch (\Exception $e) {
            Log::error('[OutreachEngine] Scraper run-batch failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while running the search batch.',
            ], 500);
        }
    }

    /**
     * Stop a batch: soft-delete whatever has not started yet.
     *
     * Cells already 'processing' are left alone - they finish and report normally -
     * and every lead already found is kept.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'batchId' => 'required|string|max:36',
            ], [
                'batchId.required' => 'Which search batch should we cancel?',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors(),
                ], 422);
            }

            $userId = (int) Auth::id();
            $batchId = (string) $request->input('batchId');

            $batch = OutreachSearchGrid::query()
                ->active()
                ->forUser($userId)
                ->forBatch($batchId);

            if (!(clone $batch)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'That search batch could not be found.',
                ], 404);
            }

            $cancelled = (clone $batch)
                ->where('status', OutreachSearchGrid::STATUS_PENDING)
                ->update([
                    'delete_status' => 'deleted',
                    'lastError' => 'Cancelled by user',
                ]);

            return response()->json([
                'success' => true,
                'message' => $cancelled > 0
                    ? 'Cancelled ' . $cancelled . ' pending cell(s).'
                    : 'Nothing left to cancel - every cell has already started.',
                'data' => $this->progressPayload($userId, $batchId),
            ]);
        } catch (\Exception $e) {
            Log::error('[OutreachEngine] Scraper cancel failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while cancelling the search batch.',
            ], 500);
        }
    }

    /**
     * Region list for the dropdown, plus - when a region and radius are supplied -
     * how many cells that combination would tile into, so the admin sees the size of
     * the API bill before committing to it.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function regions(Request $request)
    {
        try {
            $userId = (int) Auth::id();
            $settings = (new SettingsResolver())->forUser($userId);
            $geo = new GeoGridService();

            $data = [
                'regions' => $geo->regionLabels(),
                'defaultRadiusKm' => (float) $settings->defaultGridRadiusKm,
                'minRadiusKm' => (float) $settings->minGridRadiusKm,
                'maxRadiusKm' => self::MAX_RADIUS_KM,
            ];

            if ($request->filled('regionLabel')) {
                $input = trim((string) $request->input('regionLabel'));
                $canonical = $geo->canonicalRegionLabel($input);
                $bounds = $canonical !== null ? $geo->boundsForRegion($canonical) : null;

                $radiusKm = (float) $request->input('radiusKm', $settings->defaultGridRadiusKm);
                $radiusKm = max((float) $settings->minGridRadiusKm, min(self::MAX_RADIUS_KM, $radiusKm));

                $estimatedCells = $bounds !== null ? $geo->estimateCellCount($bounds, $radiusKm) : 0;

                $data['match'] = [
                    'input' => $input,
                    // buildGrid() stops at MAX_GRID_CELLS. Past that the far end of
                    // the region is never queued, so the panel has to say so before
                    // anyone starts a sweep believing it covers the whole province.
                    'cellCap' => \App\Modules\OutreachEngine\Services\GeoGridService::MAX_GRID_CELLS,
                    'overCap' => $estimatedCells > \App\Modules\OutreachEngine\Services\GeoGridService::MAX_GRID_CELLS,
                    // Null means "we hold no bounding box for this" - the UI should say
                    // so instead of letting the admin queue a sweep that will be refused.
                    'canonical' => $canonical,
                    'known' => $bounds !== null,
                    'radiusKm' => round($radiusKm, 3),
                    'estimatedCells' => $estimatedCells,
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Regions loaded.',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('[OutreachEngine] Scraper regions failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the region list.',
            ], 500);
        }
    }

    // ==================== INTERNALS ====================

    /**
     * The one progress shape, shared by start(), progress(), runBatch() and cancel()
     * so the polling UI never has to branch on which call answered it.
     *
     * @param  int  $processedNow  Cells this request worked itself (runBatch only).
     */
    private function progressPayload(int $userId, string $batchId, int $processedNow = 0): array
    {
        $counts = [
            OutreachSearchGrid::STATUS_PENDING => 0,
            OutreachSearchGrid::STATUS_PROCESSING => 0,
            OutreachSearchGrid::STATUS_COMPLETED => 0,
            OutreachSearchGrid::STATUS_SPLIT => 0,
            OutreachSearchGrid::STATUS_FAILED => 0,
        ];

        $totalCells = 0;
        $resultsCount = 0;
        $newLeadsCount = 0;

        $rows = OutreachSearchGrid::query()
            ->active()
            ->forUser($userId)
            ->forBatch($batchId)
            ->selectRaw('status, COUNT(*) as cells, SUM(resultsCount) as results, SUM(newLeadsCount) as newLeads')
            ->groupBy('status')
            ->get();

        foreach ($rows as $row) {
            $cells = (int) $row->cells;
            $counts[$row->status] = $cells;
            $totalCells += $cells;
            $resultsCount += (int) $row->results;
            $newLeadsCount += (int) $row->newLeads;
        }

        $meta = OutreachSearchGrid::query()
            ->active()
            ->forUser($userId)
            ->forBatch($batchId)
            ->orderBy('id')
            ->first(['businessType', 'regionLabel', 'radiusKm', 'created_at']);

        $lastFailure = OutreachSearchGrid::query()
            ->active()
            ->forUser($userId)
            ->forBatch($batchId)
            ->where('status', OutreachSearchGrid::STATUS_FAILED)
            ->whereNotNull('lastError')
            ->orderByDesc('id')
            ->value('lastError');

        // 'split' cells are finished work even though they spawned children; the
        // children are separate pending rows and are already counted in the total.
        $settled = $counts[OutreachSearchGrid::STATUS_COMPLETED]
            + $counts[OutreachSearchGrid::STATUS_SPLIT]
            + $counts[OutreachSearchGrid::STATUS_FAILED];

        return [
            'batchId' => $batchId,
            'businessType' => $meta ? (string) $meta->businessType : '',
            'regionLabel' => $meta ? (string) $meta->regionLabel : '',
            'radiusKm' => $meta ? (float) $meta->radiusKm : 0.0,
            'startedAt' => $meta && $meta->created_at ? $meta->created_at->format('Y-m-d H:i:s') : null,
            'counts' => array_merge($counts, ['total' => $totalCells]),
            'percent' => $totalCells > 0 ? round(($settled / $totalCells) * 100, 1) : 0.0,
            'finished' => $totalCells > 0
                && $counts[OutreachSearchGrid::STATUS_PENDING] === 0
                && $counts[OutreachSearchGrid::STATUS_PROCESSING] === 0,
            'processedNow' => $processedNow,
            'resultsCount' => $resultsCount,
            'newLeadsCount' => $newLeadsCount,
            'leadsTotal' => OutreachLead::query()->active()->forUser($userId)->forBatch($batchId)->count(),
            'recentLeads' => $this->recentLeads($userId, $batchId),
            'lastError' => $lastFailure !== null ? (string) $lastFailure : null,
        ];
    }

    /**
     * Newest leads of a batch, flattened for the progress ticker.
     */
    private function recentLeads(int $userId, string $batchId, int $limit = 10): array
    {
        return OutreachLead::query()
            ->active()
            ->forUser($userId)
            ->forBatch($batchId)
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'businessName', 'category', 'city', 'province', 'email', 'phone', 'website', 'enrichmentStatus'])
            ->map(function ($lead) {
                return [
                    'id' => (int) $lead->id,
                    'businessName' => (string) $lead->businessName,
                    'category' => (string) $lead->category,
                    'location' => $lead->display_location,
                    'email' => $lead->email,
                    'phone' => $lead->phone,
                    'website' => $lead->website,
                    'enrichmentStatus' => (string) $lead->enrichmentStatus,
                ];
            })
            ->all();
    }

    /**
     * The user's latest batches, one row each, for the "recent searches" list.
     */
    private function recentBatches(int $userId, int $limit = 10): array
    {
        return OutreachSearchGrid::query()
            ->active()
            ->forUser($userId)
            ->selectRaw('batchId')
            ->selectRaw('MAX(businessType) as businessType')
            ->selectRaw('MAX(regionLabel) as regionLabel')
            ->selectRaw('COUNT(*) as totalCells')
            ->selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pendingCells")
            ->selectRaw('SUM(newLeadsCount) as newLeadsCount')
            ->selectRaw('MAX(created_at) as startedAt')
            ->groupBy('batchId')
            ->orderByRaw('MAX(created_at) DESC')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                return [
                    'batchId' => (string) $row->batchId,
                    'businessType' => (string) $row->businessType,
                    'regionLabel' => (string) $row->regionLabel,
                    'totalCells' => (int) $row->totalCells,
                    'pendingCells' => (int) $row->pendingCells,
                    'newLeadsCount' => (int) $row->newLeadsCount,
                    'startedAt' => (string) $row->startedAt,
                ];
            })
            ->all();
    }

    /**
     * The batch this request is about: the one asked for, or the user's most recent.
     */
    private function resolveBatchId(Request $request, int $userId): ?string
    {
        $batchId = trim((string) $request->input('batchId', ''));

        if ($batchId !== '') {
            return $batchId;
        }

        $latest = OutreachSearchGrid::query()
            ->active()
            ->forUser($userId)
            ->orderByDesc('id')
            ->value('batchId');

        return $latest !== null ? (string) $latest : null;
    }
}
