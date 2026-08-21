<?php

namespace App\Modules\OutreachEngine\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\OutreachEngine\Models\OutreachEmailLog;
use App\Modules\OutreachEngine\Models\OutreachLead;
use App\Modules\OutreachEngine\Models\OutreachSearchGrid;
use App\Modules\OutreachEngine\Services\LeadEnrichmentService;
use App\Modules\OutreachEngine\Services\SettingsResolver;
use App\Modules\OutreachEngine\Support\OutreachException;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

/**
 * The lead list: server-side DataTables, CSV export, and per-lead edit / delete /
 * enrich actions.
 *
 * The table and the export share applyFilters() on purpose. Two copies of the same
 * filter logic drift within a release or two, and an export that silently disagrees
 * with the table on screen is the kind of bug nobody reports until it has been wrong
 * for months.
 */
class LeadsController extends Controller
{
    /** Leads one inline enrichBatch() call may work. */
    const MAX_BATCH_LEADS = 25;

    /** Default batch size when the caller does not say. */
    const DEFAULT_BATCH_LEADS = 10;

    /**
     * Wall-clock ceiling for enrichBatch(), in seconds. Each lead can spend several
     * HTTP timeouts chasing a contact page, so the loop watches the clock as well as
     * the counter.
     */
    const INLINE_BUDGET_SECONDS = 45;

    /** Rows fetched per pass while streaming the CSV, so a big export stays flat in memory. */
    const EXPORT_CHUNK = 500;

    /** CSV column headings, in the same order the rows are written. */
    const CSV_HEADERS = [
        'ID',
        'Business Name',
        'Category',
        'Google Type',
        'Email',
        'Email Source',
        'Phone',
        'Website',
        'Facebook',
        'Address',
        'City',
        'Province',
        'Latitude',
        'Longitude',
        'Rating',
        'Reviews',
        'Enrichment Status',
        'Outreach Status',
        'Last Contacted',
        'Replied At',
        'Contact Attempts',
        'Batch',
        'Found On',
    ];

    /**
     * Display the leads screen.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $userId = (int) Auth::id();

        return view('outreach::leads', [
            'outreachStatuses' => OutreachLead::getOutreachStatusLabels(),
            'enrichmentStatuses' => OutreachLead::getEnrichmentStatusLabels(),
            'categories' => $this->usedCategories($userId),
            'cities' => $this->distinctValues($userId, 'city'),
            'provinces' => $this->distinctValues($userId, 'province'),
            'batches' => $this->batchOptions($userId),
            'totalLeads' => OutreachLead::query()->active()->forUser($userId)->count(),
        ]);
    }

    /**
     * Server-side DataTables feed.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getData(Request $request)
    {
        try {
            $query = $this->baseQuery((int) Auth::id());
            $this->applyFilters($request, $query);

            return DataTables::of($query)
                ->editColumn('businessName', function ($lead) {
                    $name = e((string) $lead->businessName);
                    $city = trim((string) $lead->city);

                    $html = '<strong class="text-dark">' . $name . '</strong>';
                    if ($city !== '') {
                        $html .= '<br><small class="text-secondary">' . e($city) . '</small>';
                    }

                    return $html;
                })
                ->addColumn('location', function ($lead) {
                    $location = $lead->display_location;

                    return $location === ''
                        ? '<span class="text-secondary">Unknown</span>'
                        : '<span class="text-dark">' . e($location) . '</span>';
                })
                ->editColumn('email', function ($lead) {
                    if (empty($lead->email)) {
                        return '<span class="text-secondary">No email yet</span>';
                    }

                    $email = e((string) $lead->email);
                    $source = trim((string) $lead->emailSource);

                    $html = '<a class="text-primary" href="mailto:' . $email . '">' . $email . '</a>';
                    if ($source !== '') {
                        $html .= '<br><small class="text-secondary">via ' . e($source) . '</small>';
                    }

                    return $html;
                })
                ->editColumn('phone', function ($lead) {
                    return empty($lead->phone)
                        ? '<span class="text-secondary">-</span>'
                        : '<span class="text-dark">' . e((string) $lead->phone) . '</span>';
                })
                ->editColumn('website', function ($lead) {
                    if (empty($lead->website)) {
                        return '<span class="text-secondary">-</span>';
                    }

                    $url = e((string) $lead->website);
                    // preg_replace returns null on failure; casting keeps a malformed
                    // URL from reaching mb_strimwidth as null and deprecating.
                    $bare = (string) preg_replace('#^https?://#i', '', (string) $lead->website);
                    $label = e(mb_strimwidth($bare, 0, 34, '...'));

                    return '<a class="text-primary" href="' . $url . '" target="_blank" rel="noopener noreferrer">' . $label . '</a>';
                })
                ->addColumn('displayCategory', function ($lead) {
                    $label = $lead->display_category;

                    if ($label === '') {
                        return '<span class="text-secondary">&mdash;</span>';
                    }

                    // The model's own answer reads as a real category; a value
                    // salvaged from Google's raw type is dimmer, so the two are
                    // never mistaken for each other at a glance.
                    $strong = trim((string) $lead->aiCategory) !== '';

                    return '<span class="' . ($strong ? 'text-dark' : 'text-secondary fst-italic') . '">'
                        . e($label) . '</span>';
                })
                ->editColumn('rating', function ($lead) {
                    $label = $lead->rating_label;

                    return $label === ''
                        ? '<span class="text-secondary">-</span>'
                        : '<span class="text-dark">' . e($label) . '</span>';
                })
                ->editColumn('enrichmentStatus', function ($lead) {
                    return $lead->enrichment_status_badge;
                })
                ->editColumn('outreachStatus', function ($lead) {
                    return $lead->outreach_status_badge;
                })
                ->editColumn('created_at', function ($lead) {
                    return $lead->created_at
                        ? '<span class="text-dark">' . $lead->created_at->format('Y-m-d H:i') . '</span>'
                        : '<span class="text-secondary">-</span>';
                })
                ->addColumn('action', function ($lead) {
                    $id = (int) $lead->id;
                    $name = htmlspecialchars((string) $lead->businessName, ENT_QUOTES, 'UTF-8');

                    return '
                        <div class="d-flex flex-wrap gap-1 justify-content-center">
                            <button type="button"
                                    class="btn btn-sm btn-outline-info badge-style lead-view-btn"
                                    title="Details"
                                    data-lead-id="' . $id . '"
                                    data-lead-name="' . $name . '">
                                <i class="bx bx-info-circle me-1"></i>Details
                            </button>

                            <button type="button"
                                    class="btn btn-sm btn-outline-success badge-style lead-edit-btn"
                                    title="Edit"
                                    data-lead-id="' . $id . '"
                                    data-lead-name="' . $name . '">
                                <i class="bx bx-edit me-1"></i>Edit
                            </button>

                            <button type="button"
                                    class="btn btn-sm btn-outline-primary badge-style lead-enrich-btn"
                                    title="Find email"
                                    data-lead-id="' . $id . '"
                                    data-lead-name="' . $name . '">
                                <i class="bx bx-search-alt me-1"></i>Enrich
                            </button>

                            <button type="button"
                                    class="btn btn-sm btn-outline-danger badge-style lead-delete-btn"
                                    title="Delete"
                                    data-lead-id="' . $id . '"
                                    data-lead-name="' . $name . '">
                                <i class="bx bx-trash me-1"></i>Delete
                            </button>
                        </div>
                    ';
                })
                // 'location' is stitched together in PHP, so DataTables needs to be told
                // which real columns to search and sort it by.
                ->filterColumn('location', function ($query, $keyword) {
                    $query->where(function ($inner) use ($keyword) {
                        $inner->where('city', 'like', '%' . $keyword . '%')
                            ->orWhere('province', 'like', '%' . $keyword . '%');
                    });
                })
                ->orderColumn('location', 'city $1, province $1')
                ->orderColumn('rating', 'rating $1, userRatingsTotal $1')
                ->rawColumns([
                    'businessName',
                    'displayCategory',
                    'location',
                    'email',
                    'phone',
                    'website',
                    'rating',
                    'enrichmentStatus',
                    'outreachStatus',
                    'created_at',
                    'action',
                ])
                ->make(true);
        } catch (\Exception $e) {
            Log::error('[OutreachEngine] Leads DataTables failed: ' . $e->getMessage());

            return response()->json([
                'error' => 'An error occurred while loading data.',
            ], 500);
        }
    }

    /**
     * Stream the filtered leads as CSV.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\JsonResponse
     */
    public function exportCsv(Request $request)
    {
        try {
            $query = $this->baseQuery((int) Auth::id());
            $this->applyFilters($request, $query);
            $query->orderBy('id');

            $filename = 'outreach-leads-' . Carbon::now('Asia/Manila')->format('Ymd-His') . '.csv';

            return response()->streamDownload(function () use ($query) {
                $handle = fopen('php://output', 'w');

                // Excel reads a BOM-less CSV as the system codepage and turns accented
                // business names ("Cafe Nino") into mojibake. The BOM is what makes it
                // pick UTF-8.
                fwrite($handle, "\xEF\xBB\xBF");
                fputcsv($handle, self::CSV_HEADERS);

                try {
                    $query->chunk(self::EXPORT_CHUNK, function ($leads) use ($handle) {
                        foreach ($leads as $lead) {
                            fputcsv($handle, $this->csvRow($lead));
                        }
                    });
                } catch (\Exception $e) {
                    // Headers are already on the wire, so there is no way back to a JSON
                    // error here - log it and let the file end where it ends.
                    Log::error('[OutreachEngine] Leads CSV stream failed: ' . $e->getMessage());
                }

                fclose($handle);
            }, $filename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
            ]);
        } catch (\Exception $e) {
            Log::error('[OutreachEngine] Leads CSV export failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while exporting leads.',
            ], 500);
        }
    }

    /**
     * One lead with its recent send history.
     *
     * The id arrives as a query parameter rather than a path segment: every URI
     * in this app is a single hyphenated segment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        try {
            $userId = (int) Auth::id();
            $id = (int) $request->input('id');
            $lead = $this->findOwnedLead($userId, $id);

            if (!$lead) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lead not found.',
                ], 404);
            }

            $logs = OutreachEmailLog::query()
                ->active()
                ->forUser($userId)
                ->where('leadId', $lead->id)
                ->orderByDesc('id')
                ->limit(10)
                ->get(['id', 'subjectUsed', 'status', 'aiRephrased', 'errorMessage', 'sentAt', 'created_at'])
                ->map(function ($log) {
                    return [
                        'id' => (int) $log->id,
                        'subject' => (string) $log->subjectUsed,
                        'status' => (string) $log->status,
                        'aiRephrased' => (bool) $log->aiRephrased,
                        'error' => $log->errorMessage,
                        'sentAt' => $log->sentAt ? $log->sentAt->format('Y-m-d H:i') : null,
                        'createdAt' => $log->created_at ? $log->created_at->format('Y-m-d H:i') : null,
                    ];
                })
                ->all();

            return response()->json([
                'success' => true,
                'message' => 'Lead loaded.',
                'data' => [
                    'lead' => $this->leadPayload($lead),
                    'emailLogs' => $logs,
                    'messageCount' => $lead->inboundMessages()->where('delete_status', 'active')->count(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[OutreachEngine] Lead show failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the lead.',
            ], 500);
        }
    }

    /**
     * Update one lead.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        try {
            $userId = (int) Auth::id();
            $id = (int) $request->input('id');
            $lead = $this->findOwnedLead($userId, $id);

            if (!$lead) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lead not found.',
                ], 404);
            }

            $outreachStatuses = implode(',', array_keys(OutreachLead::getOutreachStatusLabels()));
            $enrichmentStatuses = implode(',', array_keys(OutreachLead::getEnrichmentStatusLabels()));

            $validator = Validator::make($request->all(), [
                'businessName' => 'required|string|max:255',
                'category' => 'nullable|string|max:190',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:60',
                // Not validated as a URL: Places hands back plenty of addresses with no
                // scheme, and rejecting them would lose data we already hold.
                'website' => 'nullable|string|max:500',
                'facebookUrl' => 'nullable|string|max:500',
                'address' => 'nullable|string|max:500',
                'city' => 'nullable|string|max:190',
                'province' => 'nullable|string|max:190',
                'notes' => 'nullable|string|max:5000',
                'outreachStatus' => 'nullable|in:' . $outreachStatuses,
                'enrichmentStatus' => 'nullable|in:' . $enrichmentStatuses,
            ], [
                'businessName.required' => 'Business name is required.',
                'email.email' => 'Please enter a valid email address.',
                'outreachStatus.in' => 'That campaign status is not one we recognise.',
                'enrichmentStatus.in' => 'That enrichment status is not one we recognise.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors(),
                ], 422);
            }

            $newEmail = trim((string) $request->input('email'));
            $emailChanged = strtolower($newEmail) !== strtolower((string) $lead->email);

            $lead->businessName = trim((string) $request->input('businessName'));
            $lead->category = $this->nullableInput($request, 'category');
            $lead->email = $newEmail !== '' ? strtolower($newEmail) : null;
            $lead->phone = $this->nullableInput($request, 'phone');
            $lead->website = $this->nullableInput($request, 'website');
            $lead->facebookUrl = $this->nullableInput($request, 'facebookUrl');
            $lead->address = $this->nullableInput($request, 'address');
            $lead->city = $this->nullableInput($request, 'city');
            $lead->province = $this->nullableInput($request, 'province');
            $lead->notes = $this->nullableInput($request, 'notes');

            if ($request->filled('outreachStatus')) {
                $lead->outreachStatus = (string) $request->input('outreachStatus');
            }

            if ($request->filled('enrichmentStatus')) {
                $lead->enrichmentStatus = (string) $request->input('enrichmentStatus');
            }

            // A hand-typed address is a finished enrichment: mark it so the cron stops
            // spending API calls trying to discover what the admin just supplied.
            if ($emailChanged && $lead->email !== null && !$request->filled('enrichmentStatus')) {
                $lead->enrichmentStatus = OutreachLead::ENRICHMENT_ENRICHED;
                $lead->emailSource = OutreachLead::SOURCE_MANUAL;
                $lead->enrichedAt = Carbon::now('Asia/Manila');
                $lead->enrichmentError = null;
            }

            $lead->save();

            return response()->json([
                'success' => true,
                'message' => 'Lead updated successfully!',
                'data' => ['lead' => $this->leadPayload($lead)],
            ]);
        } catch (\Exception $e) {
            Log::error('[OutreachEngine] Lead update failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the lead.',
            ], 500);
        }
    }

    /**
     * Soft-delete one lead.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request)
    {
        try {
            $userId = (int) Auth::id();
            $id = (int) $request->input('id');
            $lead = $this->findOwnedLead($userId, $id);

            if (!$lead) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lead not found.',
                ], 404);
            }

            // Soft delete only. The row keeps its placeId, which is globally unique -
            // a hard delete would let the same business be scraped in again tomorrow.
            $lead->update(['delete_status' => 'deleted']);

            return response()->json([
                'success' => true,
                'message' => 'Lead deleted successfully!',
            ]);
        } catch (\Exception $e) {
            Log::error('[OutreachEngine] Lead delete failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the lead.',
            ], 500);
        }
    }

    /**
     * Run email discovery for one lead, right now.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function enrichNow(Request $request)
    {
        try {
            $userId = (int) Auth::id();
            $id = (int) $request->input('id');
            $lead = $this->findOwnedLead($userId, $id);

            if (!$lead) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lead not found.',
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

            if (function_exists('set_time_limit')) {
                @set_time_limit(120);
            }

            $result = (new LeadEnrichmentService($settings))->enrich($lead);
            $lead->refresh();

            $found = !empty($result['email']);

            return response()->json([
                'success' => true,
                'message' => $found
                    ? 'Found ' . $result['email'] . ' for this lead.'
                    : ($result['error'] ?: 'No email could be found for this lead.'),
                'data' => [
                    'found' => $found,
                    'email' => $result['email'] ?? null,
                    'source' => $result['source'] ?? null,
                    'error' => $result['error'] ?? null,
                    'lead' => $this->leadPayload($lead),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[OutreachEngine] Lead enrichment failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while enriching the lead.',
            ], 500);
        }
    }

    /**
     * Enrich several leads inline.
     *
     * Pass 'ids' to work an explicit selection, or leave it out and the current
     * filters decide - in which case only leads that still need an email are taken.
     * Bounded by both a count and a wall clock, because QUEUE_CONNECTION is sync and
     * this loop runs inside the admin's own request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function enrichBatch(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'nullable|array|max:' . self::MAX_BATCH_LEADS,
                'ids.*' => 'integer|min:1',
                'limit' => 'nullable|integer|min:1|max:' . self::MAX_BATCH_LEADS,
            ], [
                'ids.max' => 'Enrich at most ' . self::MAX_BATCH_LEADS . ' leads at a time.',
                'limit.max' => 'Enrich at most ' . self::MAX_BATCH_LEADS . ' leads at a time.',
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

            $limit = (int) $request->input('limit', self::DEFAULT_BATCH_LEADS);
            $limit = max(1, min(self::MAX_BATCH_LEADS, $limit));

            $query = $this->baseQuery($userId);

            if ($request->filled('ids')) {
                $ids = array_slice(array_map('intval', (array) $request->input('ids')), 0, self::MAX_BATCH_LEADS);
                $query->whereIn('id', $ids);
            } else {
                // No explicit selection: take what the screen is currently showing,
                // narrowed to the leads that still have something to discover.
                $this->applyFilters($request, $query);
                $query->needsEnrichment();
            }

            $leads = $query->orderBy('id')->limit($limit)->get();

            if ($leads->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No leads are waiting for enrichment.',
                    'data' => ['processed' => 0, 'enriched' => 0, 'failed' => 0, 'results' => []],
                ]);
            }

            if (function_exists('set_time_limit')) {
                @set_time_limit(self::INLINE_BUDGET_SECONDS + 60);
            }

            $service = new LeadEnrichmentService($settings);
            $deadline = microtime(true) + self::INLINE_BUDGET_SECONDS;

            $processed = 0;
            $enriched = 0;
            $failed = 0;
            $results = [];

            foreach ($leads as $lead) {
                if (microtime(true) >= $deadline) {
                    break;
                }

                $result = $service->enrich($lead);
                $processed++;

                if (!empty($result['email'])) {
                    $enriched++;
                } else {
                    $failed++;
                }

                $results[] = [
                    'id' => (int) $lead->id,
                    'businessName' => (string) $lead->businessName,
                    'email' => $result['email'] ?? null,
                    'source' => $result['source'] ?? null,
                    'error' => $result['error'] ?? null,
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Enriched ' . $enriched . ' of ' . $processed . ' lead(s).',
                'data' => [
                    'processed' => $processed,
                    'enriched' => $enriched,
                    'failed' => $failed,
                    // Fewer than requested means the clock ran out, not that the list
                    // is exhausted - the UI can simply call again.
                    'remaining' => max(0, $leads->count() - $processed),
                    'results' => $results,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[OutreachEngine] Batch enrichment failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while enriching leads.',
            ], 500);
        }
    }

    // ==================== INTERNALS ====================

    /**
     * The ownership-scoped starting point for every lead query in this controller.
     */
    private function baseQuery(int $userId)
    {
        return OutreachLead::query()
            ->active()
            ->forUser($userId)
            ->select([
                'id',
                'batchId',
                'gridId',
                'placeId',
                'businessName',
                'category',
                'address',
                'city',
                'province',
                'latitude',
                'longitude',
                'phone',
                'website',
                'facebookUrl',
                'email',
                'emailSource',
                'rating',
                'userRatingsTotal',
                'enrichmentStatus',
                'enrichmentAttempts',
                'enrichmentError',
                'enrichedAt',
                'outreachStatus',
                'lastContactedAt',
                'repliedAt',
                'contactAttempts',
                'notes',
                'created_at',
                'updated_at',
            ]);
    }

    /**
     * The single filter implementation behind both the table and the export.
     *
     * Date bounds accept either naming style - dateFrom/dateTo or the repo's older
     * start_date/end_date - so a view written to either convention filters the same.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    private function applyFilters(Request $request, $query)
    {
        // DataTables posts its own global search as search[value], an ARRAY. Only a
        // plain string is a filter of ours - Yajra has already dealt with its own.
        $rawSearch = $request->input('search_term');
        if (!is_string($rawSearch)) {
            $rawSearch = $request->input('search');
            $rawSearch = is_string($rawSearch) ? $rawSearch : '';
        }

        $search = trim($rawSearch);
        if ($search !== '') {
            $query->where(function ($inner) use ($search) {
                $like = '%' . $search . '%';
                $inner->where('businessName', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('category', 'like', $like)
                    ->orWhere('aiCategory', 'like', $like)
                    ->orWhere('city', 'like', $like)
                    ->orWhere('province', 'like', $like)
                    ->orWhere('address', 'like', $like)
                    ->orWhere('phone', 'like', $like);
            });
        }

        if ($request->filled('outreachStatus')) {
            $status = (string) $request->input('outreachStatus');
            if (array_key_exists($status, OutreachLead::getOutreachStatusLabels())) {
                $query->where('outreachStatus', $status);
            }
        }

        if ($request->filled('enrichmentStatus')) {
            $status = (string) $request->input('enrichmentStatus');
            if (array_key_exists($status, OutreachLead::getEnrichmentStatusLabels())) {
                $query->where('enrichmentStatus', $status);
            }
        }

        // The category filter matches the model's answer only. Google's raw type
        // lives in `category` and is not something anyone would filter by on purpose.
        if ($request->filled('aiCategory')) {
            $query->where('aiCategory', (string) $request->input('aiCategory'));
        }

        if ($request->filled('categoryStatus')) {
            $status = (string) $request->input('categoryStatus');
            if (in_array($status, OutreachLead::CATEGORY_STATUSES, true)) {
                $query->where('categoryStatus', $status);
            }
        }

        if ($request->filled('city')) {
            $query->where('city', 'like', '%' . trim((string) $request->input('city')) . '%');
        }

        if ($request->filled('province')) {
            $query->where('province', 'like', '%' . trim((string) $request->input('province')) . '%');
        }

        if ($request->filled('batchId')) {
            $query->where('batchId', (string) $request->input('batchId'));
        }

        // hasEmail is tri-state: absent means "no opinion", and '0' is a real filter,
        // which is why filled() cannot be used to detect it.
        $hasEmail = $request->input('hasEmail');
        if ($hasEmail !== null && $hasEmail !== '') {
            if (in_array($hasEmail, ['1', 1, true, 'true', 'yes'], true)) {
                $query->hasEmail();
            } elseif (in_array($hasEmail, ['0', 0, false, 'false', 'no'], true)) {
                $query->where(function ($inner) {
                    $inner->whereNull('email')->orWhere('email', '');
                });
            }
        }

        $dateFrom = $request->input('dateFrom', $request->input('start_date'));
        if (!empty($dateFrom)) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        $dateTo = $request->input('dateTo', $request->input('end_date'));
        if (!empty($dateTo)) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        return $query;
    }

    /**
     * One lead, or null when it is not this user's to see.
     */
    private function findOwnedLead(int $userId, $id): ?OutreachLead
    {
        return OutreachLead::query()
            ->active()
            ->forUser($userId)
            ->where('id', (int) $id)
            ->first();
    }

    /**
     * The lead shape the screens consume - explicit, so a new column never leaks into
     * the browser by accident.
     */
    private function leadPayload(OutreachLead $lead): array
    {
        return [
            'id' => (int) $lead->id,
            'batchId' => $lead->batchId,
            'businessName' => (string) $lead->businessName,
            'category' => $lead->category,
            'address' => $lead->address,
            'city' => $lead->city,
            'province' => $lead->province,
            'location' => $lead->display_location,
            'latitude' => $lead->latitude !== null ? (float) $lead->latitude : null,
            'longitude' => $lead->longitude !== null ? (float) $lead->longitude : null,
            'phone' => $lead->phone,
            'website' => $lead->website,
            'facebookUrl' => $lead->facebookUrl,
            'email' => $lead->email,
            'emailSource' => $lead->emailSource,
            'rating' => $lead->rating !== null ? (float) $lead->rating : null,
            'userRatingsTotal' => $lead->userRatingsTotal !== null ? (int) $lead->userRatingsTotal : null,
            'ratingLabel' => $lead->rating_label,
            'enrichmentStatus' => (string) $lead->enrichmentStatus,
            'enrichmentAttempts' => (int) $lead->enrichmentAttempts,
            'enrichmentError' => $lead->enrichmentError,
            'enrichedAt' => $lead->enrichedAt ? $lead->enrichedAt->format('Y-m-d H:i') : null,
            'outreachStatus' => (string) $lead->outreachStatus,
            'outreachStatusBadge' => $lead->outreach_status_badge,
            'enrichmentStatusBadge' => $lead->enrichment_status_badge,
            'lastContactedAt' => $lead->lastContactedAt ? $lead->lastContactedAt->format('Y-m-d H:i') : null,
            'repliedAt' => $lead->repliedAt ? $lead->repliedAt->format('Y-m-d H:i') : null,
            'contactAttempts' => (int) $lead->contactAttempts,
            'notes' => $lead->notes,
            'createdAt' => $lead->created_at ? $lead->created_at->format('Y-m-d H:i') : null,
        ];
    }

    /**
     * One CSV line, in CSV_HEADERS order.
     */
    private function csvRow(OutreachLead $lead): array
    {
        return [
            (int) $lead->id,
            (string) $lead->businessName,
            $lead->display_category,
            (string) $lead->category,
            (string) $lead->email,
            (string) $lead->emailSource,
            (string) $lead->phone,
            (string) $lead->website,
            (string) $lead->facebookUrl,
            (string) $lead->address,
            (string) $lead->city,
            (string) $lead->province,
            $lead->latitude !== null ? (string) $lead->latitude : '',
            $lead->longitude !== null ? (string) $lead->longitude : '',
            $lead->rating !== null ? (string) $lead->rating : '',
            $lead->userRatingsTotal !== null ? (string) $lead->userRatingsTotal : '',
            (string) $lead->enrichmentStatus,
            (string) $lead->outreachStatus,
            $lead->lastContactedAt ? $lead->lastContactedAt->format('Y-m-d H:i:s') : '',
            $lead->repliedAt ? $lead->repliedAt->format('Y-m-d H:i:s') : '',
            (int) $lead->contactAttempts,
            (string) $lead->batchId,
            $lead->created_at ? $lead->created_at->format('Y-m-d H:i:s') : '',
        ];
    }

    /**
     * Distinct non-empty values of one column, for a filter dropdown.
     *
     * @return string[]
     */
    private function distinctValues(int $userId, string $column): array
    {
        return OutreachLead::query()
            ->active()
            ->forUser($userId)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->map(function ($value) {
                return (string) $value;
            })
            ->all();
    }

    /**
     * Recent scrape batches as filter options, newest first.
     */
    private function batchOptions(int $userId, int $limit = 25): array
    {
        return OutreachSearchGrid::query()
            ->active()
            ->forUser($userId)
            ->selectRaw('batchId')
            ->selectRaw('MAX(businessType) as businessType')
            ->selectRaw('MAX(regionLabel) as regionLabel')
            ->selectRaw('MAX(created_at) as startedAt')
            ->groupBy('batchId')
            ->orderByRaw('MAX(created_at) DESC')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                return [
                    'batchId' => (string) $row->batchId,
                    'label' => trim($row->businessType . ' - ' . $row->regionLabel, ' -'),
                    'startedAt' => (string) $row->startedAt,
                ];
            })
            ->all();
    }

    /**
     * Trimmed input, or null when the field is blank - blank strings in nullable
     * columns make every "is it set?" check downstream ambiguous.
     */
    private function nullableInput(Request $request, string $key): ?string
    {
        $value = trim((string) $request->input($key, ''));

        return $value === '' ? null : $value;
    }

    /**
     * Categories that actually appear in this account's leads.
     *
     * The full taxonomy would offer two dozen options where most return nothing;
     * this lists only what is really there, so every choice finds rows.
     *
     * @return array<int, string>
     */
    private function usedCategories(int $userId): array
    {
        return OutreachLead::query()
            ->active()
            ->forUser($userId)
            ->whereNotNull('aiCategory')
            ->where('aiCategory', '!=', '')
            ->distinct()
            ->orderBy('aiCategory')
            ->pluck('aiCategory')
            ->all();
    }
}
