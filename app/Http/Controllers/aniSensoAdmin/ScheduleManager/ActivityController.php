<?php

namespace App\Http\Controllers\aniSensoAdmin\ScheduleManager;

use App\Models\AsScheduleActivity;
use App\Models\AsScheduleActivityItem;
use App\Models\AsScheduleActivityVersion;
use App\Models\AsScheduleDateNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ActivityController extends BaseScheduleController
{
    public function store(Request $request)
    {
        return $this->saveActivity($request, null);
    }

    public function update(Request $request)
    {
        return $this->saveActivity($request, $this->queryId($request));
    }

    public function show(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);
        $activity = AsScheduleActivity::active()
            ->where('croppingScheduleId', $schedule->id)
            ->where('id', $id)
            ->with(['items.material', 'items.service', 'lots', 'workers'])
            ->first();
        if (!$activity) return $this->jsonFail('Activity not found.', 404);

        $payload = $activity->toArray();
        $payload['lotIds'] = $activity->lots->pluck('id');
        $payload['workerIds'] = $activity->workers->pluck('id');
        // Resolved public URL alongside the stored path so the modal can
        // render the existing image immediately without re-deriving.
        $payload['imageUrl'] = $activity->imageUrl();

        return $this->jsonOk('Activity loaded.', ['data' => $payload]);
    }

    public function destroy(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);
        $activity = AsScheduleActivity::active()->where('croppingScheduleId', $schedule->id)->where('id', $id)->first();
        if (!$activity) return $this->jsonFail('Activity not found.', 404);

        // Soft-delete is reversible, so we keep the image file on disk
        // even after deletion — restoration brings it back wired up.
        $activity->update(['deleteStatus' => 0]);
        return $this->jsonOk('Activity deleted.');
    }

    /**
     * Toggle the per-activity isHidden flag. Hidden activities stay on
     * the setup timeline (dimmed, with a "Hidden" tag) so the user knows
     * they exist, but are filtered out of the worker presentation, card
     * viewer, and export schedule. Use case: skip an optional activity
     * for this print run without unscheduling it from the timeline.
     *
     * Distinct from isDraft: isDraft archives the activity entirely
     * (moves it off the timeline into a separate drafts panel). isHidden
     * is lighter-weight — quick toggle, easy to flip back.
     */
    public function toggleHidden(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);
        $activity = AsScheduleActivity::active()
            ->where('croppingScheduleId', $schedule->id)
            ->where('id', $id)
            ->first();
        if (!$activity) return $this->jsonFail('Activity not found.', 404);

        $next = !((bool) $activity->isHidden);
        $activity->update(['isHidden' => $next]);

        return $this->jsonOk($next ? 'Activity hidden from presentations.' : 'Activity restored to presentations.', [
            'data' => [
                'id'       => $activity->id,
                'isHidden' => $next,
            ],
        ]);
    }

    /**
     * Flip the "done" checkmark on an activity — the same isDone flag the
     * client app's big checkbox writes, so both apps stay in step. The client
     * app locks done activities against editing; here the admin keeps full
     * edit rights and only dragging is blocked client-side.
     */
    public function toggleDone(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);
        $activity = AsScheduleActivity::active()
            ->where('croppingScheduleId', $schedule->id)
            ->where('id', $id)
            ->first();
        if (!$activity) return $this->jsonFail('Activity not found.', 404);

        $next = !((bool) $activity->isDone);
        $activity->update(['isDone' => $next]);

        return $this->jsonOk($next ? 'Marked as done.' : 'Marked as not done.', [
            'data' => [
                'id'     => $activity->id,
                'isDone' => $next,
            ],
        ]);
    }

    /**
     * Upload a reference image for an activity. The file is written under
     * `public/storage/schedule-activities/{scheduleId}/{uuid}.{ext}` and
     * the relative path is returned to the client. The client stashes that
     * path in a hidden input so the next activity save (store or update)
     * persists it to the activity row.
     *
     * This is intentionally a separate endpoint from the activity save
     * so the upload happens immediately (instant preview) without forcing
     * the entire activity form to be submitted as multipart.
     *
     * Orphan handling: if the user uploads but never saves, the file
     * sticks around — acceptable for this workflow since save normally
     * fires within the same modal session.
     */
    public function uploadImage(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpg,jpeg,png,webp,gif|max:8192',
        ], [
            'image.required' => 'Pick an image to upload.',
            'image.image'    => 'File must be an image.',
            'image.mimes'    => 'Allowed types: JPG, PNG, WebP, GIF.',
            'image.max'      => 'Image is too large — max 8 MB.',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $file        = $request->file('image');
        $ext         = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $stem        = Str::uuid()->toString();
        $relativeDir = 'schedule-activities/' . $schedule->id;
        $relativePath = $relativeDir . '/' . $stem . '.' . $ext;

        try {
            Storage::disk('public')->putFileAs($relativeDir, $file, $stem . '.' . $ext);
        } catch (\Throwable $e) {
            return $this->jsonFail('Image upload failed: ' . $e->getMessage(), 500);
        }

        return $this->jsonOk('Image uploaded.', [
            'data' => [
                'imagePath' => $relativePath,
                'imageUrl'  => asset('storage/' . $relativePath),
            ],
        ]);
    }

    /**
     * Aggregate the labor cost for every non-draft activity on the schedule.
     *
     * Cost rule per (activity, worker, day):
     *   - timeRequired = 'n/a'   → ₱0   (no labor billed)
     *   - timeRequired = 'half'  → 1× worker.costPerHalfDay   per day in range
     *   - timeRequired = 'whole' → 2× worker.costPerHalfDay   per day in range
     *
     * Multi-day range activities bill EVERY day of the range, so a 5-day
     * whole-day activity with 3 workers at ₱500/half = 3 × 500 × 2 × 5 = ₱15,000.
     *
     * Accepts optional query filters:
     *   groupIds[]  — restrict to activities covering any lot in any of these
     *                 default-groupings (the user-friendly entry point)
     *   lotIds[]    — restrict to activities covering any of these lots
     *                 (merged into the effective lot set with groupIds)
     *   workerIds[] — restrict the costing (and per-worker breakdown) to these
     *                 workers; activities with no remaining workers are dropped
     *   dasMin / dasMax — restrict to activities whose computed DAS for any
     *                 applicable lot falls inside this inclusive integer range
     *
     * Returns the grand total plus per-worker and per-activity breakdowns so
     * the modal can present both views without further math on the client.
     */
    public function laborSummary(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $activities = $schedule->activities()
            ->with(['workers' => fn ($q) => $q->orderBy('priority', 'asc'), 'lots'])
            ->get();

        // --- Parse filters ---
        $groupIdsFilter  = array_values(array_unique(array_filter(array_map('intval', (array) $request->input('groupIds', [])))));
        $lotIdsRaw       = array_values(array_unique(array_filter(array_map('intval', (array) $request->input('lotIds', [])))));
        $workerIdsFilter = array_values(array_unique(array_filter(array_map('intval', (array) $request->input('workerIds', [])))));

        // Resolve groups → lots and merge with any explicit lotIds filter.
        $lotIdsFromGroups = [];
        if (!empty($groupIdsFilter)) {
            $lotIdsFromGroups = \App\Models\AsScheduleDefaultGroupingLot::whereIn('defaultGroupingId', $groupIdsFilter)
                ->pluck('lotId')->map(fn ($id) => (int) $id)->all();
        }
        $lotIdsFilter = array_values(array_unique(array_merge($lotIdsRaw, $lotIdsFromGroups)));

        $hasLotFilter    = !empty($lotIdsFilter);
        $hasWorkerFilter = !empty($workerIdsFilter);
        $hasDasMin = $request->filled('dasMin') && is_numeric($request->dasMin);
        $hasDasMax = $request->filled('dasMax') && is_numeric($request->dasMax);
        $hasDasFilter = $hasDasMin || $hasDasMax;
        $dasMin = $hasDasMin ? (int) $request->dasMin : PHP_INT_MIN;
        $dasMax = $hasDasMax ? (int) $request->dasMax : PHP_INT_MAX;

        // Calendar date range filter. When set, multi-day activities are
        // pro-rated to the days inside the window — a 10-day activity that
        // only overlaps 3 days of the picked range bills 3 day-rates per
        // worker, not 10. An activity that doesn't intersect the range
        // at all is excluded from the total. Either bound can be left
        // blank (acts as -∞ / +∞).
        $hasStartDate = $request->filled('startDate');
        $hasEndDate   = $request->filled('endDate');
        $hasDateFilter = $hasStartDate || $hasEndDate;
        try {
            $dateMin = $hasStartDate ? \Illuminate\Support\Carbon::parse($request->input('startDate'))->startOfDay() : null;
            $dateMax = $hasEndDate   ? \Illuminate\Support\Carbon::parse($request->input('endDate'))->endOfDay()   : null;
        } catch (\Throwable $e) {
            $dateMin = $dateMax = null;
            $hasDateFilter = false;
        }

        // --- Build effective Day 0 anchor per lot (matches the JS recompute logic). ---
        $lotDayZero = [];
        foreach ($schedule->lots as $lot) {
            if ($lot->dayZeroDate) {
                $lotDayZero[$lot->id] = \Illuminate\Support\Carbon::parse($lot->dayZeroDate);
            }
        }
        foreach ($activities as $a) {
            if (!$a->isDayZero || !$a->targetDate) continue;
            $aDate = \Illuminate\Support\Carbon::parse($a->targetDate);
            foreach ($a->lots as $lot) {
                if (!isset($lotDayZero[$lot->id]) || $aDate->lt($lotDayZero[$lot->id])) {
                    $lotDayZero[$lot->id] = $aDate->copy();
                }
            }
        }

        $perWorker = [];
        $perActivity = [];
        $grandTotal = 0.0;
        $totals = ['halfDays' => 0, 'wholeDays' => 0, 'naCount' => 0, 'totalAssignments' => 0];

        // Phase buckets — split into "land preparation" (DAS < 0) and "main
        // cropping season" (DAS >= 0). Activities with no resolvable DAS land
        // in 'unanchored' so the user knows they're missing a Day 0 anchor.
        $phaseTemplate = ['count' => 0, 'cost' => 0.0, 'assignments' => 0, 'halfDays' => 0, 'wholeDays' => 0, 'naCount' => 0];
        $phases = [
            'preDayZero' => $phaseTemplate,
            'cropping'   => $phaseTemplate,
            'unanchored' => $phaseTemplate,
        ];

        $unitsFor = function ($timeRequired) {
            return match ($timeRequired) {
                'whole' => 2,
                'half'  => 1,
                default => 0,
            };
        };

        foreach ($activities as $activity) {
            $activityLotIds = $activity->lots->pluck('id')->all();

            // --- Lot filter: activity must cover at least one selected lot ---
            if ($hasLotFilter && empty(array_intersect($activityLotIds, $lotIdsFilter))) {
                continue;
            }

            // --- DAS filter: at least one of the activity's relevant lots must yield a DAS in range ---
            $activityDas = null;
            if ($activity->targetDate) {
                $aDate = \Illuminate\Support\Carbon::parse($activity->targetDate);
                $consideredLotIds = $hasLotFilter
                    ? array_values(array_intersect($activityLotIds, $lotIdsFilter))
                    : $activityLotIds;
                $candidateDeltas = [];
                foreach ($consideredLotIds as $lotId) {
                    if (!isset($lotDayZero[$lotId])) continue;
                    $delta = (int) $lotDayZero[$lotId]->diffInDays($aDate, false);
                    $candidateDeltas[] = $delta;
                }
                if (!empty($candidateDeltas)) {
                    $activityDas = min($candidateDeltas);
                }
            }
            if ($hasDasFilter) {
                if ($activityDas === null) continue;
                $inRange = false;
                foreach ($candidateDeltas ?? [] as $delta) {
                    if ($delta >= $dasMin && $delta <= $dasMax) { $inRange = true; break; }
                }
                if (!$inRange) continue;
            }

            // --- Worker filter: keep only the workers the user wants to see costs for ---
            $effectiveWorkers = $hasWorkerFilter
                ? $activity->workers->whereIn('id', $workerIdsFilter)
                : $activity->workers;
            if ($hasWorkerFilter && $effectiveWorkers->count() === 0) {
                continue;
            }

            $units = $unitsFor($activity->timeRequired);

            // Multi-day range: each day in [targetDate, targetEndDate] bills
            // the workers' day-rate. Single-day or no end date → 1 day.
            $actStart = $activity->targetDate;
            $actEnd   = $activity->targetEndDate ?: $activity->targetDate;
            $rangeDays = 1;
            if ($actStart && $actEnd) {
                $rangeDays = (int) $actStart->diffInDays($actEnd) + 1;
                if ($rangeDays < 1) $rangeDays = 1;
            }

            // Apply the calendar-date filter by intersecting the activity's
            // [start, end] window with the requested [dateMin, dateMax].
            // Activities that fall fully outside the window are skipped;
            // partial overlaps are pro-rated to the intersecting day count.
            if ($hasDateFilter) {
                if (!$actStart || !$actEnd) continue; // can't compare without dates
                $clipStart = $dateMin && $actStart->lt($dateMin) ? $dateMin->copy() : $actStart->copy();
                $clipEnd   = $dateMax && $actEnd->gt($dateMax)   ? $dateMax->copy() : $actEnd->copy();
                if ($clipStart->gt($clipEnd)) continue; // no overlap
                $rangeDays = (int) $clipStart->copy()->startOfDay()->diffInDays($clipEnd->copy()->startOfDay()) + 1;
                if ($rangeDays < 1) continue;
            }

            // Phase: split land-prep (DAS < 0) from cropping (DAS >= 0). An
            // activity with no DAS at all (no anchored lot) lands in
            // 'unanchored' so the user notices.
            $phaseKey = 'unanchored';
            if ($activityDas !== null) {
                $phaseKey = $activityDas < 0 ? 'preDayZero' : 'cropping';
            }

            $activityCost = 0.0;
            $workerRateSum = 0.0; // sum of half-day rates across workers — used for audit display

            foreach ($effectiveWorkers as $worker) {
                $rate = (float) $worker->costPerHalfDay;
                $workerRateSum += $rate;
                $costPerWorkerPerDay = $rate * $units;
                $cost = $costPerWorkerPerDay * $rangeDays;
                $activityCost += $cost;
                $totals['totalAssignments']++;
                $phases[$phaseKey]['assignments']++;

                if (!isset($perWorker[$worker->id])) {
                    $perWorker[$worker->id] = [
                        'id'                => $worker->id,
                        'name'              => $worker->workerName,
                        'priority'          => (int) $worker->priority,
                        'costPerHalfDay'    => $rate,
                        'halfDays'          => 0,
                        'wholeDays'         => 0,
                        'naCount'           => 0,
                        'assignmentCount'   => 0,
                        'total'             => 0.0,
                        'preDayZeroTotal'   => 0.0,
                        'croppingTotal'     => 0.0,
                        'unanchoredTotal'   => 0.0,
                    ];
                }
                $perWorker[$worker->id]['assignmentCount']++;
                $perWorker[$worker->id]['total'] += $cost;
                if ($phaseKey === 'preDayZero')      $perWorker[$worker->id]['preDayZeroTotal'] += $cost;
                elseif ($phaseKey === 'cropping')    $perWorker[$worker->id]['croppingTotal']   += $cost;
                else                                 $perWorker[$worker->id]['unanchoredTotal'] += $cost;

                if ($activity->timeRequired === 'half') {
                    $perWorker[$worker->id]['halfDays'] += $rangeDays;
                    $totals['halfDays']               += $rangeDays;
                    $phases[$phaseKey]['halfDays']    += $rangeDays;
                } elseif ($activity->timeRequired === 'whole') {
                    $perWorker[$worker->id]['wholeDays'] += $rangeDays;
                    $totals['wholeDays']                 += $rangeDays;
                    $phases[$phaseKey]['wholeDays']      += $rangeDays;
                } else {
                    $perWorker[$worker->id]['naCount'] += $rangeDays;
                    $totals['naCount']                 += $rangeDays;
                    $phases[$phaseKey]['naCount']      += $rangeDays;
                }
            }

            $grandTotal += $activityCost;
            $phases[$phaseKey]['count']++;
            $phases[$phaseKey]['cost'] += $activityCost;

            $perActivity[] = [
                'id'             => $activity->id,
                'activityTitle'  => $activity->activityTitle,
                'targetDate'     => $activity->targetDate ? $activity->targetDate->format('Y-m-d') : null,
                'targetEndDate'  => $activity->targetEndDate ? $activity->targetEndDate->format('Y-m-d') : null,
                'rangeDays'      => $rangeDays,
                'timeRequired'   => $activity->timeRequired,
                'unitsPerDay'    => $units,
                'workerCount'    => $effectiveWorkers->count(),
                'workerRateSum'  => round($workerRateSum, 2),
                'das'            => $activityDas,
                'phase'          => $phaseKey,
                'cost'           => round($activityCost, 2),
            ];
        }

        foreach ($phases as &$p) { $p['cost'] = round($p['cost'], 2); } unset($p);

        foreach ($perWorker as &$w) {
            $w['total']           = round($w['total'], 2);
            $w['preDayZeroTotal'] = round($w['preDayZeroTotal'], 2);
            $w['croppingTotal']   = round($w['croppingTotal'], 2);
            $w['unanchoredTotal'] = round($w['unanchoredTotal'], 2);
        }
        unset($w);

        usort($perWorker, function ($a, $b) {
            if ($a['priority'] !== $b['priority']) return $a['priority'] <=> $b['priority'];
            return strcmp($a['name'], $b['name']);
        });

        // Filter echo so the front-end can render the active filter pills.
        $filtersEcho = [
            'groupIds'  => $groupIdsFilter,
            'lotIds'    => $lotIdsFilter,
            'workerIds' => $workerIdsFilter,
            'dasMin'    => $hasDasMin ? $dasMin : null,
            'dasMax'    => $hasDasMax ? $dasMax : null,
            'startDate' => $dateMin ? $dateMin->format('Y-m-d') : null,
            'endDate'   => $dateMax ? $dateMax->format('Y-m-d') : null,
        ];

        return $this->jsonOk('Labor summary computed.', [
            'data' => [
                'grandTotal'      => round($grandTotal, 2),
                'totalActivities' => count($perActivity),
                'totals'          => $totals,
                'phases'          => $phases,
                'perWorker'       => array_values($perWorker),
                'perActivity'     => $perActivity,
                'filters'         => $filtersEcho,
                'dayType'         => $schedule->dayType,
                'scheduleTitle'   => $schedule->title,
            ],
        ]);
    }

    /**
     * Move an active activity into the Drafts bin (isDraft = 1). Drafts are
     * hidden from the activity panel, the export view, the readiness check,
     * and the calendar generator — but stay queryable in the Drafts modal so
     * the user can pull them back later without losing any data.
     */
    public function toDraft(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);

        $activity = AsScheduleActivity::active()
            ->where('croppingScheduleId', $schedule->id)
            ->where('id', $id)
            ->first();
        if (!$activity) return $this->jsonFail('Activity not found.', 404);
        if ((int) $activity->isDraft === 1) {
            return $this->jsonFail('Activity is already a draft.', 422);
        }

        $activity->update(['isDraft' => 1]);
        return $this->jsonOk('Moved to drafts.');
    }

    /**
     * Pull a draft back into the active activity panel (isDraft = 0). Returns
     * the full activity payload so the caller can render it back onto the
     * timeline without a follow-up fetch.
     */
    public function fromDraft(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);

        $activity = AsScheduleActivity::active()
            ->where('croppingScheduleId', $schedule->id)
            ->where('id', $id)
            ->first();
        if (!$activity) return $this->jsonFail('Activity not found.', 404);
        if ((int) $activity->isDraft !== 1) {
            return $this->jsonFail('Activity is not a draft.', 422);
        }

        $activity->update(['isDraft' => 0]);

        $fresh = $activity->fresh(['items.material', 'items.service', 'lots', 'workers']);
        $data = $fresh->toArray();
        $data['lotIds'] = $fresh->lots->pluck('id');
        $data['workerIds'] = $fresh->workers->pluck('id');

        return $this->jsonOk('Restored from drafts.', ['data' => $data]);
    }

    /**
     * Server-side PDF generation for the Worker Presentation.
     *
     * Uses headless Chrome via `--print-to-pdf` so the output is pixel-
     * identical to the browser preview (CSS Grid, @page rules, and the
     * landscape calendar section all render correctly — none of which work
     * properly with dompdf / mpdf).
     *
     * The Chrome binary path is configurable via the CHROME_PATH env so the
     * same code works across machines.
     */
    public function workerPresentationPdf(Request $request)
    {
        // Render the same view to a self-contained HTML string. The view has
        // inline <style> + <script>, no external assets, so it works as a
        // standalone file:// document for Chrome.
        $view = $this->workerPresentation($request);
        $html = $view->render();

        // Hide the on-screen action bar inside the PDF + force the print
        // media zoom reset by injecting a small print rule. The view already
        // does this via @media print but we belt-and-suspenders it here.
        $html = str_replace('</head>', '<style>@media print{.no-print{display:none!important}.sheet{zoom:1!important;margin:0!important;padding:0!important;border:none!important;box-shadow:none!important}}</style></head>', $html);

        // Stage temp files
        $tmpDir = storage_path('app/tmp-pdf');
        if (!is_dir($tmpDir)) {
            @mkdir($tmpDir, 0775, true);
        }
        $token    = uniqid('wp_', true);
        $htmlPath = $tmpDir . DIRECTORY_SEPARATOR . $token . '.html';
        $pdfPath  = $tmpDir . DIRECTORY_SEPARATOR . $token . '.pdf';
        file_put_contents($htmlPath, $html);

        // Locate Chrome. Default to the common Windows install path; can be
        // overridden in .env if Chrome lives elsewhere (or on Mac/Linux).
        $chrome = env('CHROME_PATH', 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe');
        if (!file_exists($chrome)) {
            // Try a few well-known fallbacks before giving up.
            $candidates = [
                'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
                'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
                'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
                'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
                '/usr/bin/google-chrome',
                '/usr/bin/chromium-browser',
                '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
            ];
            $chrome = null;
            foreach ($candidates as $c) {
                if (file_exists($c)) {
                    $chrome = $c;
                    break;
                }
            }
        }
        if (!$chrome || !file_exists($chrome)) {
            @unlink($htmlPath);
            return response()->json([
                'success' => false,
                'message' => 'Chrome/Edge binary not found. Set CHROME_PATH in .env or install Google Chrome.',
            ], 500);
        }

        // file:// URL — Chrome on Windows wants forward slashes after file:///
        $fileUrl = 'file:///' . str_replace('\\', '/', $htmlPath);

        // Build the headless command. --virtual-time-budget gives the page
        // time for layout to settle before printing; --no-pdf-header-footer
        // strips Chrome's default header/footer so we're left with just the
        // document's own footer.
        $args = [
            escapeshellarg($chrome),
            '--headless=new',
            '--disable-gpu',
            '--no-sandbox',
            '--no-pdf-header-footer',
            '--virtual-time-budget=8000',
            '--print-to-pdf=' . escapeshellarg($pdfPath),
            escapeshellarg($fileUrl),
        ];
        $cmd = implode(' ', $args) . ' 2>&1';

        $output = [];
        $exit = 0;
        exec($cmd, $output, $exit);

        // If the new headless flag failed, retry with the legacy flag.
        if (!file_exists($pdfPath)) {
            $args[1] = '--headless';
            $cmd = implode(' ', $args) . ' 2>&1';
            $output = [];
            exec($cmd, $output, $exit);
        }

        if (!file_exists($pdfPath) || filesize($pdfPath) === 0) {
            @unlink($htmlPath);
            @unlink($pdfPath);
            return response()->json([
                'success' => false,
                'message' => 'Chrome failed to generate the PDF.',
                'details' => $output,
                'cmd'     => $cmd,
            ], 500);
        }

        $pdf = file_get_contents($pdfPath);
        @unlink($htmlPath);
        @unlink($pdfPath);

        $schedule = $view->getData()['schedule'] ?? null;
        $title = $schedule ? $schedule->title : 'schedule';
        $safe = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $title);
        $filename = $safe . '_worker_presentation_' . now()->format('Ymd-His') . '.pdf';

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length'      => strlen($pdf),
            'Cache-Control'       => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /**
     * Comprehensive "Worker Presentation" — a printable, full-page report
     * meant to be opened in a new tab and saved as PDF.
     *
     * Sections:
     *   1. Intro: groups, workers, lots
     *   2. Activities (date-grouped, with a weather-disclaimer note)
     *   3. Monthly labor count across all workers (counts only — no peso)
     *   4. Per-worker pages (one worker per page, forced page-break)
     *   5. Irrigation schedules
     *   6. Calendar view (monthly grid with activity + irrigation chips)
     */
    public function workerPresentation(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $schedule->load([
            'lots',
            'workers' => fn ($q) => $q->orderBy('priority', 'asc'),
            // Skip activities the user toggled as hidden — they stay
            // visible on the setup timeline (dimmed) but are excluded
            // from the worker briefing.
            'activities' => fn ($q) => $q->where('isHidden', false)->orderBy('targetDate', 'asc'),
            'activities.workers',
            'activities.lots',
            'activities.items.material',
            'activities.items.service',
            'irrigations' => fn ($q) => $q->orderBy('startDay', 'asc'),
            'irrigations.assignedWorker',
            'irrigations.workers',
            'irrigations.lots',
            'defaultGroupings.lots',
            'dateNotes',
            'versions',
            'attachments',
            'criticalRules',
        ]);

        // ---- Effective Day 0 anchor per lot (manual + activity flags) ----
        $lotDayZero = [];
        foreach ($schedule->lots as $lot) {
            if ($lot->dayZeroDate) {
                $lotDayZero[$lot->id] = \Illuminate\Support\Carbon::parse($lot->dayZeroDate);
            }
        }
        foreach ($schedule->activities as $a) {
            if (!$a->isDayZero || !$a->targetDate) continue;
            $aDate = $a->targetDate;
            foreach ($a->lots as $lot) {
                if (!isset($lotDayZero[$lot->id]) || $aDate->lt($lotDayZero[$lot->id])) {
                    $lotDayZero[$lot->id] = $aDate->copy();
                }
            }
        }

        // ---- Per-worker stats (work-day list, monthly counts, earnings) ----
        $workerStats = [];
        foreach ($schedule->workers as $worker) {
            $workDays = [];
            $byMonth = [];
            $halfCount = 0; $wholeCount = 0; $naCount = 0;
            foreach ($schedule->activities as $activity) {
                if (!$activity->workers->contains('id', $worker->id)) continue;
                $start = $activity->targetDate;
                if (!$start) continue;
                $end = $activity->targetEndDate ? $activity->targetEndDate : $start;
                for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                    $monthKey = $d->format('Y-m');
                    $byMonth[$monthKey] = ($byMonth[$monthKey] ?? 0) + 1;
                    $workDays[] = [
                        'date'         => $d->copy(),
                        'timeRequired' => $activity->timeRequired,
                    ];
                    if ($activity->timeRequired === 'whole') {
                        $wholeCount++;
                    } elseif ($activity->timeRequired === 'half') {
                        $halfCount++;
                    } else {
                        $naCount++;
                    }
                }
            }
            usort($workDays, function ($x, $y) {
                if ($x['date']->equalTo($y['date'])) return 0;
                return $x['date']->lt($y['date']) ? -1 : 1;
            });
            ksort($byMonth);
            $units = ($wholeCount * 2) + ($halfCount * 1);
            $earnings = $units * (float) $worker->costPerHalfDay;
            $workerStats[] = [
                'worker'     => $worker,
                'workDays'   => $workDays,
                'totalDays'  => count($workDays),
                'byMonth'    => $byMonth,
                'halfCount'  => $halfCount,
                'wholeCount' => $wholeCount,
                'naCount'    => $naCount,
                'units'      => $units,
                'earnings'   => round($earnings, 2),
            ];
        }

        // ---- Aggregate monthly labor counts across all workers ----
        // Counts the number of DISTINCT calendar days per month that have at
        // least one scheduled activity. NOT multiplied by the number of
        // workers — if 3 workers all work the same day, that's 1 day, not 3.
        $aggregateMonthly = [];
        $monthDates = []; // monthKey → set of date keys
        foreach ($schedule->activities as $activity) {
            if (!$activity->targetDate) continue;
            $start = $activity->targetDate;
            $end = $activity->targetEndDate ? $activity->targetEndDate : $start;
            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                $monthKey = $d->format('Y-m');
                $dateKey  = $d->format('Y-m-d');
                $monthDates[$monthKey][$dateKey] = true;
            }
        }
        foreach ($monthDates as $monthKey => $dates) {
            $aggregateMonthly[$monthKey] = count($dates);
        }
        ksort($aggregateMonthly);

        // ---- Irrigation calendar mapping per group → contiguous BANDS per
        // week, so the calendar can render an irrigation cycle as a single
        // bar spanning multiple cells instead of one chip per day.
        //
        // A cycle for a group runs continuously from (groupStart + startDAS)
        // through (groupStart + endDAS). For each calendar week the cycle
        // touches, we emit one band segment with its startCol/endCol in
        // that week's Sun–Sat lane (1-based, 1 = Sunday).
        //
        // After collection, each week's bands are row-packed greedily so
        // non-overlapping segments share a row and only true overlaps stack.
        $irrigationBandsByWeek = [];
        // Band color + icon now come from the irrigation's taskType so the
        // calendar visually groups Irrigate / Maintain / Overflow / Drain
        // together regardless of cycle order. Mirrors AsScheduleIrrigation
        // catalog — change once, propagates everywhere.
        // ---- Priority-resolved band generation ----
        //
        // Step 1: collect every raw (start, end, group) window for every
        //         irrigation, tagged with its priority.
        // Step 2: per group, walk day-by-day and pick the winning window
        //         (lowest priority number; tie-break: latest id wins so
        //         newer edits override older ones).
        // Step 3: collapse contiguous days that have the same winning
        //         irrigation back into single bands — this is how a
        //         priority-1 day in the middle of a DAS 1–10 priority-5
        //         band splits the original into 1–4 and 6–10.
        // Step 4: feed the resolved bands into the per-week segmenter the
        //         calendar grid renders from.
        $rawWindows = [];
        foreach ($schedule->irrigations as $irrigation) {
            $taskMeta = \App\Models\AsScheduleIrrigation::taskTypeMeta($irrigation->taskType);
            $priority = (int) ($irrigation->priority ?? 5);

            if ($irrigation->dayMode === 'date' && $irrigation->startDate && $irrigation->endDate) {
                $rawWindows[] = [
                    'start'      => $irrigation->startDate->copy(),
                    'end'        => $irrigation->endDate->copy(),
                    'groupKey'   => '__absolute__', // date-mode is not tied to a default-grouping
                    'groupName'  => '',
                    'priority'   => $priority,
                    'irrigation' => $irrigation,
                    'taskMeta'   => $taskMeta,
                ];
            } else {
                foreach ($schedule->defaultGroupings as $group) {
                    $groupStart = $group->startDate ? \Illuminate\Support\Carbon::parse($group->startDate) : null;
                    if (!$groupStart) continue;
                    $rawWindows[] = [
                        'start'      => $groupStart->copy()->addDays((int) $irrigation->startDay),
                        'end'        => $groupStart->copy()->addDays((int) $irrigation->endDay),
                        'groupKey'   => 'g' . $group->id,
                        'groupName'  => $group->groupName,
                        'priority'   => $priority,
                        'irrigation' => $irrigation,
                        'taskMeta'   => $taskMeta,
                    ];
                }
            }
        }

        // Per-day winners, partitioned by group so that two groups can have
        // their own independent bands (a priority-1 in Group A doesn't
        // touch Group B's bands).
        $dayWinners = []; // [groupKey => [Y-m-d => window]]
        foreach ($rawWindows as $win) {
            $cursor = $win['start']->copy();
            while ($cursor->lte($win['end'])) {
                $dateKey = $cursor->format('Y-m-d');
                $existing = $dayWinners[$win['groupKey']][$dateKey] ?? null;
                $existingPriority = $existing['priority'] ?? PHP_INT_MAX;
                $beats = $win['priority'] < $existingPriority
                    || ($win['priority'] === $existingPriority
                        && (int) $win['irrigation']->id > (int) ($existing['irrigation']->id ?? -1));
                if ($beats) {
                    $dayWinners[$win['groupKey']][$dateKey] = $win;
                }
                $cursor->addDay();
            }
        }

        // Collapse contiguous same-irrigation winning days back into bands.
        $resolvedWindows = [];
        foreach ($dayWinners as $groupKey => $byDate) {
            ksort($byDate);
            $current = null;
            $bandStart = null;
            $bandEnd = null;
            foreach ($byDate as $dateKey => $winner) {
                $today = \Illuminate\Support\Carbon::parse($dateKey);
                $sameIrrig = $current && (int) $current['irrigation']->id === (int) $winner['irrigation']->id;
                $contiguous = $bandEnd && $bandEnd->copy()->addDay()->equalTo($today);
                if ($sameIrrig && $contiguous) {
                    $bandEnd = $today->copy();
                    continue;
                }
                if ($current !== null) {
                    $resolvedWindows[] = array_merge($current, [
                        'start' => $bandStart,
                        'end'   => $bandEnd,
                    ]);
                }
                $current = $winner;
                $bandStart = $today->copy();
                $bandEnd = $today->copy();
            }
            if ($current !== null) {
                $resolvedWindows[] = array_merge($current, [
                    'start' => $bandStart,
                    'end'   => $bandEnd,
                ]);
            }
        }

        // Flatten the resolved windows into a per-date lookup so the
        // activities timeline can show "what irrigation is happening on
        // this calendar day" inside each date block. Deduplicated by
        // irrigation id — if the same irrigation covers multiple default
        // groupings that all land on this calendar day, we emit ONE row
        // and list all the relevant group names inside it. Without this
        // dedupe an irrigation in 2 groups would print twice on every
        // overlapping day.
        $byDateAndId = []; // [dateKey => [irrigationId => entry]]
        foreach ($resolvedWindows as $win) {
            $cursor = $win['start']->copy();
            $irrId  = (int) $win['irrigation']->id;
            while ($cursor->lte($win['end'])) {
                $dateKey = $cursor->format('Y-m-d');
                if (!isset($byDateAndId[$dateKey][$irrId])) {
                    $byDateAndId[$dateKey][$irrId] = [
                        'irrigation' => $win['irrigation'],
                        'taskMeta'   => $win['taskMeta'],
                        'priority'   => $win['priority'],
                        'groupNames' => [],
                    ];
                }
                if (!empty($win['groupName'])
                    && !in_array($win['groupName'], $byDateAndId[$dateKey][$irrId]['groupNames'], true)) {
                    $byDateAndId[$dateKey][$irrId]['groupNames'][] = $win['groupName'];
                }
                $cursor->addDay();
            }
        }
        // Strip the inner irrigation-id keys so the view can foreach over
        // a plain numeric list. Order is by priority asc, then irrigation
        // id desc — same precedence the band-cut algorithm uses for ties.
        $irrigationsByDate = [];
        foreach ($byDateAndId as $dateKey => $entries) {
            usort($entries, function ($a, $b) {
                if ($a['priority'] !== $b['priority']) return $a['priority'] <=> $b['priority'];
                return (int) $b['irrigation']->id <=> (int) $a['irrigation']->id;
            });
            $irrigationsByDate[$dateKey] = $entries;
        }

        // Per-week segmenting on the resolved (priority-cut) bands.
        foreach ($resolvedWindows as $win) {
            $irrigation = $win['irrigation'];
            $taskMeta   = $win['taskMeta'];
            $cycleStart = $win['start'];
            $cycleEnd   = $win['end'];
            $segStart   = $cycleStart->copy();
            while ($segStart->lte($cycleEnd)) {
                $weekSunday = $segStart->copy()->startOfWeek(\Carbon\Carbon::SUNDAY);
                $weekSaturday = $weekSunday->copy()->addDays(6);
                $segEnd = $weekSaturday->lt($cycleEnd) ? $weekSaturday->copy() : $cycleEnd->copy();
                $startCol = $segStart->dayOfWeek + 1; // Sun=0 → 1, Sat=6 → 7
                $endCol   = $segEnd->dayOfWeek + 1;
                $weekKey = $weekSunday->format('Y-m-d');
                $irrigationBandsByWeek[$weekKey][] = [
                    'irrigationId' => $irrigation->id,
                    'title'        => $irrigation->irrigationTitle,
                    'groupName'    => $win['groupName'],
                    'startCol'     => $startCol,
                    'endCol'       => $endCol,
                    'startDate'    => $segStart->copy(),
                    'endDate'      => $segEnd->copy(),
                    'dasStart'     => (int) $irrigation->startDay,
                    'dasEnd'       => (int) $irrigation->endDay,
                    'priority'     => (int) ($irrigation->priority ?? 5),
                    'taskType'     => $taskMeta['slug'],
                    'taskLabel'    => $taskMeta['label'],
                    'taskIcon'     => $taskMeta['icon'],
                    'color'        => $taskMeta['color'],
                ];
                $segStart = $segEnd->copy()->addDay();
            }
        }
        // Greedy row-packing per week so non-overlapping bands share a row.
        foreach ($irrigationBandsByWeek as $weekKey => &$bands) {
            usort($bands, function ($a, $b) {
                if ($a['startCol'] !== $b['startCol']) return $a['startCol'] <=> $b['startCol'];
                return $a['endCol'] <=> $b['endCol'];
            });
            $rowEnds = []; // rowIdx → last endCol in that row
            foreach ($bands as &$band) {
                $placed = false;
                foreach ($rowEnds as $rowIdx => $endCol) {
                    if ($band['startCol'] > $endCol) {
                        $band['row'] = $rowIdx + 1;
                        $rowEnds[$rowIdx] = $band['endCol'];
                        $placed = true;
                        break;
                    }
                }
                if (!$placed) {
                    $rowEnds[] = $band['endCol'];
                    $band['row'] = count($rowEnds);
                }
            }
            unset($band);
        }
        unset($bands);

        // Span lookup: keep a date → count for span-extension (just so days
        // touched by an irrigation extend $lastDate even if no activities
        // happen on them).
        $irrigationDates = [];
        foreach ($irrigationBandsByWeek as $bands) {
            foreach ($bands as $b) {
                $cursor = $b['startDate']->copy();
                while ($cursor->lte($b['endDate'])) {
                    $irrigationDates[$cursor->format('Y-m-d')] = true;
                    $cursor->addDay();
                }
            }
        }

        // ---- Activity calendar lookup ----
        // Single-day activities live in the cells they happen on (chip inside
        // the cell). Multi-day activities are emitted as week-segmented bands
        // (just like irrigation cycles) so the date range reads as one long
        // bar above the cells it spans instead of being repeated per day.
        $activitiesByDate = [];
        $activityBandsByWeek = [];
        $priorityColors = [
            'critical' => '#8a1d1d',
            'high'     => '#c95a35',
            'medium'   => '#5b8c3a',
            'low'      => '#6b7280',
        ];
        foreach ($schedule->activities as $activity) {
            if (!$activity->targetDate) continue;
            $start = $activity->targetDate;
            $end = $activity->targetEndDate ? $activity->targetEndDate : $start;
            $isRange = $end->gt($start);

            if (!$isRange) {
                $key = $start->format('Y-m-d');
                if (!isset($activitiesByDate[$key])) $activitiesByDate[$key] = [];
                $activitiesByDate[$key][] = $activity;
                continue;
            }

            // Multi-day → split into per-week contiguous segments.
            $segStart = $start->copy();
            $workerList = $activity->workers->pluck('workerName')->implode(', ');
            while ($segStart->lte($end)) {
                $weekSunday   = $segStart->copy()->startOfWeek(\Carbon\Carbon::SUNDAY);
                $weekSaturday = $weekSunday->copy()->addDays(6);
                $segEnd       = $weekSaturday->lt($end) ? $weekSaturday->copy() : $end->copy();
                $startCol     = $segStart->dayOfWeek + 1;
                $endCol       = $segEnd->dayOfWeek + 1;
                $weekKey      = $weekSunday->format('Y-m-d');
                $activityBandsByWeek[$weekKey][] = [
                    'activityId' => $activity->id,
                    'title'      => $activity->activityTitle,
                    'workers'    => $workerList,
                    'priority'   => $activity->priority,
                    'color'      => $priorityColors[$activity->priority] ?? '#5b8c3a',
                    'startCol'   => $startCol,
                    'endCol'     => $endCol,
                    'startDate'  => $segStart->copy(),
                    'endDate'    => $segEnd->copy(),
                    'totalStart' => $start->copy(),
                    'totalEnd'   => $end->copy(),
                ];
                $segStart = $segEnd->copy()->addDay();
            }
        }
        // Greedy row-pack per week so non-overlapping bands share a row.
        foreach ($activityBandsByWeek as $weekKey => &$bands) {
            usort($bands, function ($a, $b) {
                if ($a['startCol'] !== $b['startCol']) return $a['startCol'] <=> $b['startCol'];
                return $a['endCol'] <=> $b['endCol'];
            });
            $rowEnds = [];
            foreach ($bands as &$band) {
                $placed = false;
                foreach ($rowEnds as $rowIdx => $endCol) {
                    if ($band['startCol'] > $endCol) {
                        $band['row'] = $rowIdx + 1;
                        $rowEnds[$rowIdx] = $band['endCol'];
                        $placed = true;
                        break;
                    }
                }
                if (!$placed) {
                    $rowEnds[] = $band['endCol'];
                    $band['row'] = count($rowEnds);
                }
            }
            unset($band);
        }
        unset($bands);

        // ---- Calendar span: earliest of any activity/irrigation, latest of either ----
        $firstDate = null;
        $lastDate = null;
        foreach ($schedule->activities as $a) {
            if (!$a->targetDate) continue;
            $s = $a->targetDate;
            $e = $a->targetEndDate ? $a->targetEndDate : $s;
            if (!$firstDate || $s->lt($firstDate)) $firstDate = $s->copy();
            if (!$lastDate || $e->gt($lastDate))   $lastDate  = $e->copy();
        }
        foreach (array_keys($irrigationDates) as $dateKey) {
            $d = \Illuminate\Support\Carbon::parse($dateKey);
            if (!$firstDate || $d->lt($firstDate)) $firstDate = $d->copy();
            if (!$lastDate || $d->gt($lastDate))   $lastDate  = $d->copy();
        }

        // ---- Build the list of months that the calendar should span ----
        $calendarMonths = [];
        if ($firstDate && $lastDate) {
            $cursor = $firstDate->copy()->startOfMonth();
            $end    = $lastDate->copy()->endOfMonth();
            while ($cursor->lte($end)) {
                $calendarMonths[] = $cursor->copy();
                $cursor->addMonth();
            }
        }

        // Base64-encode attachment images so the printed PDF is fully
        // self-contained (no http:// fetches during PDF generation, which
        // would fail in a CI / cron context). Cap at ~5MB per image to
        // keep the rendered HTML reasonable.
        $attachmentsEmbedded = [];
        foreach ($schedule->attachments as $att) {
            $payload = [
                'id'          => $att->id,
                'filename'    => $att->filename,
                'mimeType'    => $att->mimeType,
                'description' => $att->description,
                'isImage'     => $att->isImage(),
                'dataUri'     => null,
                'url'         => $att->getPublicUrl(),
            ];
            $abs = $att->getAbsolutePath();
            if ($abs && $att->isImage() && filesize($abs) < 5 * 1024 * 1024) {
                $bytes = @file_get_contents($abs);
                if ($bytes !== false) {
                    $payload['dataUri'] = 'data:' . $att->mimeType . ';base64,' . base64_encode($bytes);
                }
            }
            $attachmentsEmbedded[] = $payload;
        }

        // Same treatment for activity reference images — keyed by activity
        // id so the view can do {{ $activityImages[$a->id] ?? $a->imageUrl() }}
        // and silently fall back to the public URL when the embed is too
        // large to inline. Cap at 3MB per image; the activity list can be
        // long and we want the rendered HTML to stay under ~50MB even with
        // dozens of activities each carrying an image.
        $activityImages = [];
        foreach ($schedule->activities as $a) {
            if (empty($a->imagePath)) continue;
            $abs = $a->imageAbsolutePath();
            if (!$abs || filesize($abs) >= 3 * 1024 * 1024) continue;
            $bytes = @file_get_contents($abs);
            if ($bytes === false) continue;
            $mime = function_exists('mime_content_type')
                ? (mime_content_type($abs) ?: 'image/jpeg')
                : 'image/jpeg';
            $activityImages[$a->id] = 'data:' . $mime . ';base64,' . base64_encode($bytes);
        }

        // Optional-section toggles set by the pre-generate modal in the
        // activities tab. The PDF route calls this method and inherits the
        // request, so the flags flow through to the printed PDF too.
        $showDescriptions = $request->boolean('showDesc');
        $showIrrigation   = $request->boolean('showIrrigation');
        $showCalendar     = $request->boolean('showCalendar');

        // Labor-only mode: hide intro tables, the activities timeline,
        // irrigation, and calendar. Only the per-worker pages + monthly
        // labor counts (sections 3 & 4) remain. When labor-only is on, the
        // optional toggles for irrigation/calendar are effectively forced
        // off — kept in this implementation by intersecting at view time.
        $laborOnly = $request->boolean('laborOnly');
        if ($laborOnly) {
            // Labor-only intersects with the per-section toggles: irrigation
            // and calendar are forced off so the existing @if($showXxx)
            // gates in the view handle their hiding. Section 1 (intro) and
            // Section 2 (activities timeline) are hidden via $laborOnly
            // directly in the view.
            $showIrrigation = false;
            $showCalendar   = false;
        }

        // Skip workers with zero assigned work days — typically rows the user
        // added to the schedule but hasn't assigned to any activity in the
        // protocol yet. Showing them produces empty per-worker pages with
        // "0 days, ₱0.00 earnings" which is just noise. Done BEFORE the
        // explicit workerIds filter so that filter only narrows among the
        // workers who actually have something to do.
        $workerStats = array_values(array_filter(
            $workerStats,
            fn ($row) => (int) $row['totalDays'] > 0
        ));

        // Worker filter: empty = include everyone (default). When set,
        // workerStats is filtered to only those IDs so the per-worker pages
        // section renders exactly the picked workers, in priority order.
        $workerIdsFilter = array_values(array_unique(array_filter(
            array_map('intval', (array) $request->input('workerIds', []))
        )));
        if (!empty($workerIdsFilter)) {
            $workerStats = array_values(array_filter(
                $workerStats,
                fn ($row) => in_array((int) $row['worker']->id, $workerIdsFilter, true)
            ));
        }

        return view('aniSensoAdmin.scheduleManager.worker-presentation', [
            'schedule'              => $schedule,
            'lotDayZero'            => $lotDayZero,
            'workerStats'           => $workerStats,
            'aggregateMonthly'      => $aggregateMonthly,
            'irrigationBandsByWeek' => $irrigationBandsByWeek,
            'irrigationsByDate'     => $irrigationsByDate,
            'activityBandsByWeek'   => $activityBandsByWeek,
            'activitiesByDate'      => $activitiesByDate,
            'calendarMonths'        => $calendarMonths,
            'firstDate'             => $firstDate,
            'lastDate'              => $lastDate,
            'generatedAt'           => \Illuminate\Support\Carbon::now('Asia/Manila'),
            'showDescriptions'      => $showDescriptions,
            'showIrrigation'        => $showIrrigation,
            'showCalendar'          => $showCalendar,
            'laborOnly'             => $laborOnly,
            'workerIdsFilter'       => $workerIdsFilter,
            'attachmentsEmbedded'   => $attachmentsEmbedded,
            'activityImages'        => $activityImages,
        ]);
    }

    /**
     * List every drafted activity for this schedule. Used by the Drafts
     * modal — returns a lean payload (no items/workers) since the modal only
     * needs the title, target date, lots, and priority for its cards.
     */
    public function listDrafts(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $drafts = AsScheduleActivity::active()
            ->where('croppingScheduleId', $schedule->id)
            ->where('isDraft', 1)
            ->with(['lots'])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($a) {
                return [
                    'id'             => $a->id,
                    'activityTitle'  => $a->activityTitle,
                    'targetDate'     => $a->targetDate ? $a->targetDate->format('Y-m-d') : null,
                    'targetEndDate'  => $a->targetEndDate ? $a->targetEndDate->format('Y-m-d') : null,
                    'priority'       => $a->priority,
                    'isDayZero'      => (bool) $a->isDayZero,
                    'updatedAt'      => $a->updated_at ? $a->updated_at->format('Y-m-d H:i') : null,
                    'lots'           => $a->lots->map(fn ($l) => ['id' => $l->id, 'lotName' => $l->lotName])->values(),
                ];
            });

        return $this->jsonOk('Drafts loaded.', ['data' => $drafts, 'count' => $drafts->count()]);
    }

    /**
     * Restore a soft-deleted activity (flip deleteStatus back to 1). Used by
     * the in-page undo stack on the setup screen.
     */
    public function restore(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);

        $activity = AsScheduleActivity::where('croppingScheduleId', $schedule->id)
            ->where('id', $id)
            ->first();
        if (!$activity) return $this->jsonFail('Activity not found.', 404);
        if ((int) $activity->deleteStatus === 1) {
            return $this->jsonFail('Activity is not deleted.', 422);
        }

        $activity->update(['deleteStatus' => 1]);

        $fresh = $activity->fresh(['items.material', 'items.service', 'lots', 'workers']);
        $data = $fresh->toArray();
        $data['lotIds'] = $fresh->lots->pluck('id');
        $data['workerIds'] = $fresh->workers->pluck('id');

        return $this->jsonOk('Activity restored.', ['data' => $data]);
    }

    /**
     * Render a print-friendly "document" view of all activities for the
     * schedule. The setup page loads this in an iframe inside the Export
     * modal; the same URL is what gets printed when the user clicks
     * "Download PDF".
     */
    public function export(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $schedule->load([
            'lots',
            'workers',
            'activities' => function ($q) {
                // Hidden activities are excluded from the export — same
                // policy as worker presentation and card viewer.
                $q->where('isHidden', false)
                    ->orderBy('targetDate')
                    ->orderBy('sequenceOrder')
                    ->orderBy('id');
            },
            'activities.lots',
            'activities.workers',
            'activities.items.material',
            'activities.items.service',
            'irrigations' => fn ($q) => $q->orderBy('sortOrder', 'asc')->orderBy('startDay', 'asc'),
            'irrigations.workers',
            'irrigations.lots',
            'irrigations.assignedWorker',
            'dateNotes',
            'versions',
            'defaultGroupings.lots',
            'attachments',
            'criticalRules',
        ]);

        // ---- Export view options (all optional query params) ----
        //
        // activitiesOnly  : drop every non-activity section (rules, protocol,
        //                   attachments, summary, lots, workers, irrigation)
        //                   so the document is just the activity timeline.
        // startFrom       : Y-m-d cutoff — the document begins on this date.
        // dasMax          : upper DAS/DAP bound — the document ends at this
        //                   day number.
        // lotIds[]        : restrict activities to these lots.
        // includeNa       : keep activities not tied to any lot (default yes).
        // hideWorkers     : drop worker names (roster section + per-activity).
        // hideNotes       : drop the per-date notes + the version-wide note.
        // hideCriticality : drop the per-activity priority pill.
        //
        // Filtering happens here rather than in the view so every figure the
        // view derives from $schedule->activities (totals, first/last date,
        // the date-group keys) is computed from the SAME filtered set — a
        // view-level filter would leave the Summary counting rows the reader
        // can no longer see.
        $exportActivitiesOnly = $request->boolean('activitiesOnly');

        $exportStartFrom = null;
        if ($request->filled('startFrom')) {
            try {
                $exportStartFrom = \Illuminate\Support\Carbon::parse($request->input('startFrom'))->startOfDay();
            } catch (\Throwable $e) {
                $exportStartFrom = null; // unparseable date → treat as "no cutoff"
            }
        }

        // A lot filter is "present" whenever the param was sent at all. The
        // client sends the sentinel -1 when the user unchecks every lot, so
        // "none selected" narrows to zero rows instead of silently meaning
        // "show everything" (the empty-array ambiguity).
        $exportLotIds = array_values(array_filter(array_map(
            'intval',
            (array) $request->input('lotIds', [])
        )));
        $exportHasLotFilter = $request->has('lotIds');
        $exportIncludeNa = !$request->has('includeNa') || $request->boolean('includeNa');

        $exportHideWorkers     = $request->boolean('hideWorkers');
        $exportHideNotes       = $request->boolean('hideNotes');
        $exportHideCriticality = $request->boolean('hideCriticality');

        $exportHasDasMax = $request->filled('dasMax') && is_numeric($request->input('dasMax'));
        $exportDasMax = $exportHasDasMax ? (int) $request->input('dasMax') : null;

        // Effective Day 0 anchor per lot, mirroring laborSummary() and the JS
        // recomputeLotDayZero(): the lot's manual date is the baseline, and any
        // activity flagged as Day 0 overrides it (earliest wins). Built from the
        // UNFILTERED activity set — a DAS cap has to be measured against the
        // schedule's real anchors, not against whatever survives the filter.
        $exportLotDayZero = [];
        foreach ($schedule->lots as $lot) {
            if ($lot->dayZeroDate) {
                $exportLotDayZero[$lot->id] = \Illuminate\Support\Carbon::parse($lot->dayZeroDate);
            }
        }
        foreach ($schedule->activities as $a) {
            if (!$a->isDayZero || !$a->targetDate) {
                continue;
            }
            $aDate = \Illuminate\Support\Carbon::parse($a->targetDate);
            foreach ($a->lots as $lot) {
                if (!isset($exportLotDayZero[$lot->id]) || $aDate->lt($exportLotDayZero[$lot->id])) {
                    $exportLotDayZero[$lot->id] = $aDate->copy();
                }
            }
        }

        if ($exportStartFrom || $exportHasLotFilter || $exportHasDasMax) {
            $filteredActivities = $schedule->activities->filter(
                function ($a) use (
                    $exportStartFrom,
                    $exportHasLotFilter,
                    $exportLotIds,
                    $exportIncludeNa,
                    $exportHasDasMax,
                    $exportDasMax,
                    $exportLotDayZero
                ) {
                    $aLotIds = $a->lots->pluck('id')->all();

                    if ($exportStartFrom) {
                        // Compare the END of the range, not the start: an activity
                        // that began earlier but is still running on the cutoff is
                        // work the reader still needs. Undated activities have
                        // nothing to compare, so a date-bounded document drops them.
                        $end = $a->targetEndDate ?: $a->targetDate;
                        if (!$end || \Illuminate\Support\Carbon::parse($end)->lt($exportStartFrom)) {
                            return false;
                        }
                    }

                    if ($exportHasLotFilter) {
                        // No lots at all = a general "N/A" activity; it applies to
                        // every lot, so it's governed by its own toggle.
                        if (count($aLotIds) === 0) {
                            return $exportIncludeNa;
                        }
                        if (count(array_intersect($aLotIds, $exportLotIds)) === 0) {
                            return false;
                        }
                    }

                    if ($exportHasDasMax) {
                        if (!$a->targetDate) {
                            return false;
                        }
                        $aDate = \Illuminate\Support\Carbon::parse($a->targetDate);
                        // Only measure against lots the reader is actually seeing.
                        $consideredLotIds = $exportHasLotFilter
                            ? array_values(array_intersect($aLotIds, $exportLotIds))
                            : $aLotIds;
                        $deltas = [];
                        foreach ($consideredLotIds as $lotId) {
                            if (!isset($exportLotDayZero[$lotId])) {
                                continue;
                            }
                            $deltas[] = (int) $exportLotDayZero[$lotId]->diffInDays($aDate, false);
                        }
                        // No anchor → no DAS → nothing to compare a bound against,
                        // so the activity can't satisfy it. Same rule laborSummary
                        // applies. This is what drops general N/A activities (they
                        // have no lot, hence no Day 0) once a DAS cap is set.
                        if (empty($deltas)) {
                            return false;
                        }
                        // Earliest DAS across the activity's lots — matches the
                        // "earliest DAS across its lots" convention used by the
                        // labor summary's DAS range filter.
                        if (min($deltas) > $exportDasMax) {
                            return false;
                        }
                    }

                    return true;
                }
            )->values();
            $schedule->setRelation('activities', $filteredActivities);
        }

        // Per-date notes are keyed off dates too — without this a note sitting
        // before the cutoff would still print and re-introduce a date the
        // document is supposed to start after. Dropping them outright when the
        // user asked to hide notes also stops note-only dates from rendering as
        // empty date blocks.
        if ($exportHideNotes) {
            $schedule->setRelation('dateNotes', $schedule->dateNotes->take(0));
        } elseif ($exportStartFrom) {
            $schedule->setRelation('dateNotes', $schedule->dateNotes->filter(
                fn ($n) => $n->noteDate && $n->noteDate->gte($exportStartFrom)
            )->values());
        }

        return view('aniSensoAdmin.scheduleManager.export', compact(
            'schedule',
            'exportActivitiesOnly',
            'exportStartFrom',
            'exportLotIds',
            'exportHasLotFilter',
            'exportIncludeNa',
            'exportHasDasMax',
            'exportDasMax',
            'exportHideWorkers',
            'exportHideNotes',
            'exportHideCriticality'
        ));
    }

    /**
     * Batch-update target date + manual sequence order for many activities at
     * once. Used by drag-and-drop on the setup page when the user rearranges
     * cards within a date, or moves a card between dates and we need to
     * persist the new order in both containers.
     *
     * Body: items: [{ id, targetDate, sequenceOrder }, ...]
     */
    public function reorder(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $validator = Validator::make($request->all(), [
            'items'                   => 'required|array|min:1',
            'items.*.id'              => 'required|integer',
            'items.*.targetDate'      => 'required|date',
            'items.*.targetEndDate'   => 'nullable|date|after_or_equal:items.*.targetDate',
            'items.*.sequenceOrder'   => 'required|integer|min:0',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $items = (array) $request->input('items');
        $ids = array_map(fn($it) => (int) $it['id'], $items);

        // Only allow updates to activities owned by this schedule.
        $validIds = AsScheduleActivity::active()
            ->where('croppingScheduleId', $schedule->id)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->all();
        $validSet = array_flip($validIds);

        try {
            DB::transaction(function () use ($items, $validSet) {
                foreach ($items as $it) {
                    $id = (int) $it['id'];
                    if (!isset($validSet[$id])) continue;
                    $update = [
                        'targetDate'    => $it['targetDate'],
                        'sequenceOrder' => (int) $it['sequenceOrder'],
                    ];
                    if (array_key_exists('targetEndDate', $it)) {
                        $update['targetEndDate'] = !empty($it['targetEndDate']) ? $it['targetEndDate'] : null;
                    }
                    AsScheduleActivity::where('id', $id)->update($update);
                }
            });
        } catch (\Throwable $e) {
            return $this->jsonFail('Failed to reorder: ' . $e->getMessage(), 500);
        }

        return $this->jsonOk('Order saved.', ['count' => count($items)]);
    }

    /**
     * Update only the targetDate of an activity. Used by the drag-and-drop
     * handler on the setup page so we don't have to round-trip the full form.
     */
    public function setDate(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);

        $validator = Validator::make($request->all(), [
            'targetDate' => 'required|date',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $activity = AsScheduleActivity::active()
            ->where('croppingScheduleId', $schedule->id)
            ->where('id', $id)
            ->first();
        if (!$activity) return $this->jsonFail('Activity not found.', 404);

        $activity->update(['targetDate' => $request->targetDate]);

        return $this->jsonOk('Activity moved.', [
            'data' => ['id' => $activity->id, 'targetDate' => $request->targetDate],
        ]);
    }

    /**
     * Duplicate an activity: copy fields, items, and lot links into a new row,
     * append " (copy)" to the title, then return it for the UI to render + open.
     */
    public function duplicate(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);

        $source = AsScheduleActivity::active()
            ->where('croppingScheduleId', $schedule->id)
            ->where('id', $id)
            ->with(['items', 'lots', 'workers'])
            ->first();
        if (!$source) return $this->jsonFail('Activity not found.', 404);

        $activeVersionId = $this->activeVersionIdFor($schedule->id);

        try {
            $new = DB::transaction(function () use ($source, $activeVersionId) {
                $copy = $source->replicate(['sequenceOrder']);
                $copy->activityTitle = mb_substr($source->activityTitle, 0, 240) . ' (copy)';
                // Duplicate always lands in the currently-active version so the
                // new copy belongs to whatever tab the user is looking at,
                // independent of which version the source row was forked into.
                if ($activeVersionId) {
                    $copy->versionId = $activeVersionId;
                }
                $copy->sourceActivityId = $source->id;
                $copy->save();

                // Copy active items
                foreach ($source->items as $item) {
                    if ((int) $item->deleteStatus !== 1) continue;
                    $itemCopy = $item->replicate();
                    $itemCopy->activityId = $copy->id;
                    $itemCopy->save();
                }

                // Copy lot + worker pivots
                $copy->lots()->sync($source->lots->pluck('id')->all());
                $copy->workers()->sync($source->workers->pluck('id')->all());

                return $copy;
            });
        } catch (\Throwable $e) {
            return $this->jsonFail('Failed to duplicate activity: ' . $e->getMessage(), 500);
        }

        $fresh = $new->fresh(['items.material', 'items.service', 'lots', 'workers']);
        $data = $fresh->toArray();
        $data['lotIds'] = $fresh->lots->pluck('id');
        $data['workerIds'] = $fresh->workers->pluck('id');

        return $this->jsonOk('Activity duplicated.', ['data' => $data]);
    }

    private function saveActivity(Request $request, $id = null)
    {
        $schedule = $this->scheduleFromRequest($request);

        $validator = Validator::make($request->all(), [
            'activityTitle'    => 'required|string|max:255',
            'targetDate'       => 'required|date',
            'targetEndDate'    => 'nullable|date|after_or_equal:targetDate',
            'priority'         => 'required|in:critical,high,medium,low',
            'activityType'     => ['nullable', 'string', \Illuminate\Validation\Rule::in(array_keys(AsScheduleActivity::ACTIVITY_TYPES))],
            // Irrigation activities carry a water task; service activities carry
            // a price. Both are ignored server-side unless the matching type is
            // set, so a stale value from switching tabs never persists.
            'waterTask'        => ['nullable', 'string', \Illuminate\Validation\Rule::in(array_keys(AsScheduleActivity::WATER_TASKS))],
            'servicePrice'     => 'nullable|numeric|min:0',
            'isDayZero'        => 'nullable|boolean',
            'isTransplant'     => 'nullable|boolean',
            'description'      => 'nullable|string|max:20000',
            // imagePath is a relative path under the `public` disk, set by
            // a prior /activities-image-upload call. Empty string / null
            // = no image (the current image, if any, is removed).
            'imagePath'        => 'nullable|string|max:500',
            'timeRequired'     => 'required|in:half,whole,n/a',
            // Lots can be empty when the activity is marked "N/A — not
            // lot-specific" in the modal (general work that doesn't tie
            // to a particular lot). Empty array is valid; server stores
            // zero lot pivots and the card UI renders an N/A badge.
            'lotIds'           => 'nullable|array',
            'lotIds.*'         => 'integer',
            'workerIds'        => 'nullable|array',
            'workerIds.*'      => 'integer',
            'items'                  => 'nullable|array',
            // `custom` is a line that is only a name and a price — the way
            // the farmer app writes every item now.
            'items.*.itemType'       => 'required_with:items|in:material,service,custom',
            'items.*.itemId'         => 'nullable|integer',
            'items.*.itemName'       => 'nullable|string|max:255',
            'items.*.unitPrice'      => 'nullable|numeric|min:0|max:99999999',
            'items.*.quantity'       => 'nullable|numeric|min:0',
            'items.*.unitOfMeasure'  => 'nullable|string|max:30',
            'items.*.notes'          => 'nullable|string|max:500',
        ], [
            'lotIds.required' => 'Pick at least one lot for this activity.',
            'lotIds.min'      => 'Pick at least one lot for this activity.',
        ]);

        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        // Sowing (DAS 0) and transplanting (DAT 0) are different events on
        // different dates — one activity can't be both anchors. The UI keeps
        // the two toggles mutually exclusive; this guards a tampered payload.
        if ($request->boolean('isDayZero') && $request->boolean('isTransplant')) {
            return $this->jsonFail('An activity cannot be both the sowing (Day 0) and the transplant (DAT 0) anchor.', 422);
        }

        // Lots must belong to this schedule. Empty array is allowed —
        // it means the user picked the N/A pseudo-chip ("activity is
        // general, not lot-specific"). Lots that DO arrive must still
        // be valid ids for this schedule; otherwise we ignore them.
        $validLotIds = \App\Models\AsScheduleLot::active()
            ->where('croppingScheduleId', $schedule->id)
            ->pluck('id')->all();
        $rawLotIds = collect($request->input('lotIds', []))
            ->map(fn ($v) => (int) $v)
            ->filter(fn ($v) => $v > 0); // strip 0/negative
        $submittedLotIds = $rawLotIds
            ->filter(fn ($v) => in_array($v, $validLotIds, true))
            ->unique()->values()->all();
        // If the user submitted lot ids but NONE belong to this schedule,
        // that's a real error (tampered payload). Empty submission =
        // N/A, which is fine.
        if ($rawLotIds->isNotEmpty() && empty($submittedLotIds)) {
            return $this->jsonFail('Selected lots do not belong to this schedule.', 422);
        }

        // Workers must belong to this schedule (zero is allowed = no manual labor).
        $validWorkerIds = \App\Models\AsScheduleWorker::active()
            ->where('croppingScheduleId', $schedule->id)
            ->pluck('id')->all();
        $submittedWorkerIds = collect($request->input('workerIds', []))
            ->map(fn($v) => (int) $v)
            ->filter(fn($v) => in_array($v, $validWorkerIds, true))
            ->unique()->values()->all();

        // Normalize incoming imagePath: trim, drop any leading slash, and
        // null out empties. Belt-and-suspenders security check: reject any
        // path that escapes the schedule-activities directory (no ".."
        // traversal, no absolute paths).
        $rawImage = trim((string) $request->input('imagePath', ''));
        $rawImage = ltrim($rawImage, '/\\');
        if ($rawImage !== '' && (str_contains($rawImage, '..') || !str_starts_with($rawImage, 'schedule-activities/'))) {
            return $this->jsonFail('Invalid image path.', 422);
        }
        $submittedImagePath = $rawImage !== '' ? $rawImage : null;

        $payload = [
            'croppingScheduleId' => $schedule->id,
            'activityTitle'      => $request->activityTitle,
            'targetDate'         => $request->targetDate,
            'targetEndDate'      => $request->filled('targetEndDate') ? $request->targetEndDate : null,
            'priority'           => $request->priority,
            'activityType'       => $request->filled('activityType') ? $request->activityType : null,
            // Only keep the mode-specific field that matches the chosen type,
            // so switching Task ⇄ Irrigation ⇄ Service never leaves a stale
            // water task or price behind.
            'waterTask'          => $request->activityType === 'irrigation'
                ? ($request->filled('waterTask') ? $request->waterTask : 'irrigate')
                : null,
            'servicePrice'       => $request->activityType === 'service' && $request->filled('servicePrice')
                ? $request->servicePrice
                : null,
            'isDayZero'          => $request->boolean('isDayZero'),
            'isTransplant'       => $request->boolean('isTransplant'),
            'description'        => $request->description,
            'imagePath'          => $submittedImagePath,
            'timeRequired'       => $request->timeRequired,
            'deleteStatus'       => 1,
        ];

        // New activities always land in the schedule's active version so they
        // appear inside whichever tab the user is currently viewing. On edits
        // we leave versionId alone — moving an activity between versions is
        // a separate operation, not a side-effect of saving.
        if ($id === null) {
            $activeVersionId = $this->activeVersionIdFor($schedule->id);
            if ($activeVersionId) {
                $payload['versionId'] = $activeVersionId;
            }
        }

        try {
            $activity = DB::transaction(function () use ($id, $schedule, $payload, $request, $submittedLotIds, $submittedWorkerIds, $submittedImagePath) {
                if ($id) {
                    $activity = AsScheduleActivity::active()->where('croppingScheduleId', $schedule->id)->where('id', $id)->first();
                    if (!$activity) {
                        abort(404, 'Activity not found.');
                    }
                    // Clean up the prior image file when the path changes
                    // (user uploaded a replacement OR cleared the image).
                    // Skipped when the path is identical — same file.
                    $previousImagePath = $activity->imagePath;
                    if ($previousImagePath && $previousImagePath !== $submittedImagePath) {
                        try {
                            Storage::disk('public')->delete($previousImagePath);
                        } catch (\Throwable $e) {
                            // Non-fatal — orphan files can be janitor-cleaned.
                        }
                    }
                    $activity->update($payload);
                } else {
                    $activity = AsScheduleActivity::create($payload);
                }

                // Replace only the material/service items this admin form knows
                // about — free-form "custom" items created in the client app
                // (itemName + unitPrice) are preserved untouched.
                // The form now knows about all three kinds, so it replaces
                // all three rather than tiptoeing around the one it could not
                // draw. A custom line's inventory link is carried across
                // untouched: which thing on the shelf it spends is the
                // client's answer, not this form's.
                $shelfLinks = AsScheduleActivityItem::where('activityId', $activity->id)
                    ->where('deleteStatus', 1)
                    ->whereNotNull('inventoryItemId')
                    ->pluck('inventoryItemId', 'itemName');

                AsScheduleActivityItem::where('activityId', $activity->id)
                    ->update(['deleteStatus' => 0]);

                foreach ((array) $request->input('items', []) as $item) {
                    $type = $item['itemType'];
                    $name = trim((string) ($item['itemName'] ?? ''));
                    AsScheduleActivityItem::create([
                        'activityId'    => $activity->id,
                        'itemType'      => $type,
                        'materialId'    => $type === 'material' ? ($item['itemId'] ?? null) : null,
                        'serviceId'     => $type === 'service'  ? ($item['itemId'] ?? null) : null,
                        'itemName'      => $type === 'custom' && $name !== '' ? $name : null,
                        'unitPrice'     => $type === 'custom' && isset($item['unitPrice']) && $item['unitPrice'] !== ''
                            ? (float) $item['unitPrice'] : null,
                        'inventoryItemId' => $type === 'custom' ? ($shelfLinks[$name] ?? null) : null,
                        'quantity'      => $item['quantity'] ?? 1,
                        'unitOfMeasure' => isset($item['unitOfMeasure']) && $item['unitOfMeasure'] !== '' ? $item['unitOfMeasure'] : null,
                        'notes'         => $item['notes'] ?? null,
                        'deleteStatus'  => 1,
                    ]);
                }

                // Replace pivot rows with the submitted lot + worker sets.
                $activity->lots()->sync($submittedLotIds);
                $activity->workers()->sync($submittedWorkerIds);

                return $activity;
            });
        } catch (\Throwable $e) {
            return $this->jsonFail('Failed to save activity: '.$e->getMessage(), 500);
        }

        $fresh = $activity->fresh(['items.material', 'items.service', 'lots', 'workers']);
        $data = $fresh->toArray();
        $data['lotIds'] = $fresh->lots->pluck('id');
        $data['workerIds'] = $fresh->workers->pluck('id');
        $data['imageUrl'] = $fresh->imageUrl();

        return $this->jsonOk($id ? 'Activity updated.' : 'Activity added.', [
            'data' => $data,
        ]);
    }

    /**
     * Upsert the per-date note for the schedule's active version.
     *
     * The (versionId, noteDate) pair is unique-by-app-logic: the first row
     * that matches it gets updated in place, anything else is created. We
     * deliberately don't enforce uniqueness at the DB level so soft-deleted
     * rows can coexist with a new active row for the same slot.
     *
     * An empty noteContent collapses to a soft delete so the same endpoint
     * handles both "save" and "clear" from the UI without a separate route.
     */
    public function saveDateNote(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $validator = Validator::make($request->all(), [
            'noteDate'    => 'required|date',
            'noteContent' => 'nullable|string|max:20000',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $versionId = $this->activeVersionIdFor($schedule->id);
        if (!$versionId) {
            return $this->jsonFail('No active version found for this schedule.', 422);
        }

        $noteDate = $request->input('noteDate');
        $content  = trim((string) $request->input('noteContent', ''));

        $existing = AsScheduleDateNote::active()
            ->forSchedule($schedule->id)
            ->forVersion($versionId)
            ->whereDate('noteDate', $noteDate)
            ->first();

        // Empty content → treat as "remove note for this date" so the UI
        // doesn't need a separate clear endpoint.
        if ($content === '') {
            if ($existing) {
                $existing->update(['deleteStatus' => 0]);
            }
            return $this->jsonOk('Note cleared.', ['data' => null]);
        }

        if ($existing) {
            $existing->update(['noteContent' => $content]);
            $note = $existing;
        } else {
            $note = AsScheduleDateNote::create([
                'croppingScheduleId' => $schedule->id,
                'versionId'          => $versionId,
                'noteDate'           => $noteDate,
                'noteContent'        => $content,
                'deleteStatus'       => 1,
            ]);
        }

        return $this->jsonOk($existing ? 'Note updated.' : 'Note added.', [
            'data' => [
                'id'          => $note->id,
                'noteDate'    => $note->noteDate->format('Y-m-d'),
                'noteContent' => $note->noteContent,
            ],
        ]);
    }

    /**
     * Soft-delete the per-date note for the active version. Idempotent — if
     * no note exists for the given date, returns success with a null payload
     * so the UI can call this even when it isn't sure whether one is present.
     */
    public function deleteDateNote(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $validator = Validator::make($request->all(), [
            'noteDate' => 'required|date',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $versionId = $this->activeVersionIdFor($schedule->id);
        if (!$versionId) {
            return $this->jsonFail('No active version found for this schedule.', 422);
        }

        AsScheduleDateNote::active()
            ->forSchedule($schedule->id)
            ->forVersion($versionId)
            ->whereDate('noteDate', $request->input('noteDate'))
            ->update(['deleteStatus' => 0]);

        return $this->jsonOk('Note deleted.');
    }

    /**
     * Drag-move: shift the per-date note to a different date. Any note
     * already sitting on the target date (same version) is absorbed —
     * one note per date, and the dragged note's content travels with it.
     */
    public function moveDateNote(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $validator = Validator::make($request->all(), [
            'fromDate' => 'required|date',
            'toDate'   => 'required|date|different:fromDate',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $versionId = $this->activeVersionIdFor($schedule->id);
        if (!$versionId) {
            return $this->jsonFail('No active version found for this schedule.', 422);
        }

        $note = AsScheduleDateNote::active()
            ->forSchedule($schedule->id)
            ->forVersion($versionId)
            ->whereDate('noteDate', $request->input('fromDate'))
            ->first();
        if (!$note) return $this->jsonFail('No note found on the source date.', 404);

        AsScheduleDateNote::active()
            ->forSchedule($schedule->id)
            ->forVersion($versionId)
            ->whereDate('noteDate', $request->input('toDate'))
            ->where('id', '!=', $note->id)
            ->update(['deleteStatus' => 0]);

        $note->update(['noteDate' => $request->input('toDate')]);

        return $this->jsonOk('Note moved.', [
            'data' => [
                'id'          => $note->id,
                'noteDate'    => $note->noteDate->format('Y-m-d'),
                'noteContent' => $note->noteContent,
            ],
        ]);
    }

    /**
     * Card Viewer — a PowerPoint-style per-day presentation.
     *
     * Each calendar day with any content (activity, irrigation, or note)
     * gets its own slide. A cover slide leads with the schedule title,
     * critical rules, and the active version's protocol introduction.
     *
     * Multi-day activities appear on EVERY day they span — the slide
     * card includes a "Day X of Y" indicator so the worker can tell at
     * a glance how far through a range they are.
     *
     * Per-day irrigation is priority-resolved (same algorithm the worker
     * presentation uses) so a priority-1 "No Irrigation" interrupt
     * correctly displaces lower-priority bands on overlapping days.
     */
    public function cardViewer(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $schedule->load([
            'lots',
            'workers',
            // Hidden activities are excluded from the card viewer — same
            // policy as worker presentation and export.
            'activities' => fn ($q) => $q->where('isHidden', false)->orderBy('targetDate', 'asc'),
            'activities.workers',
            'activities.lots',
            'activities.items.material',
            'activities.items.service',
            'irrigations' => fn ($q) => $q->orderBy('sortOrder', 'asc'),
            'irrigations.workers',
            'irrigations.lots',
            'irrigations.assignedWorker',
            'defaultGroupings.lots',
            'versions',
            'dateNotes',
            'criticalRules',
        ]);

        $activeVersion = $schedule->versions->firstWhere('isActive', true)
            ?? $schedule->versions->firstWhere('isOriginal', true)
            ?? $schedule->versions->first();

        $irrigationsByDate = $this->resolveIrrigationsByDate($schedule);
        $dateNotesByDate   = $schedule->dateNotes->keyBy(fn ($n) => $n->noteDate->format('Y-m-d'));

        // Collect every calendar day that needs a slide: anything spanned
        // by an activity, any day with irrigation, any day with a note.
        // Empty days are skipped — they'd be uninformative slides.
        $dateSet = [];
        foreach ($schedule->activities as $a) {
            if (!$a->targetDate) continue;
            $start = $a->targetDate;
            $end   = $a->targetEndDate ?: $start;
            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                $dateSet[$d->format('Y-m-d')] = true;
            }
        }
        foreach (array_keys($irrigationsByDate) as $dk) $dateSet[$dk] = true;
        foreach ($dateNotesByDate->keys() as $dk)       $dateSet[$dk] = true;
        ksort($dateSet);
        $dateKeys = array_keys($dateSet);

        $firstDate = !empty($dateKeys) ? \Illuminate\Support\Carbon::parse($dateKeys[0])                : null;
        $lastDate  = !empty($dateKeys) ? \Illuminate\Support\Carbon::parse($dateKeys[count($dateKeys) - 1]) : null;

        // Build per-slide data. activitiesForDay includes EVERY activity
        // whose [start, end] range covers this date — so multi-day
        // activities recur across their span.
        $slides = [];
        foreach ($dateKeys as $idx => $dateKey) {
            $dateCarbon = \Illuminate\Support\Carbon::parse($dateKey);
            $activitiesForDay = $schedule->activities->filter(function ($a) use ($dateCarbon) {
                if (!$a->targetDate) return false;
                $start = $a->targetDate;
                $end   = $a->targetEndDate ?: $start;
                return $start->lte($dateCarbon) && $dateCarbon->lte($end);
            })->values();

            $slides[] = [
                'dateKey'    => $dateKey,
                'date'       => $dateCarbon,
                'dayIndex'   => $idx + 1,
                'activities' => $activitiesForDay,
                'irrigations' => $irrigationsByDate[$dateKey] ?? [],
                'note'       => $dateNotesByDate->get($dateKey),
            ];
        }

        return view('aniSensoAdmin.scheduleManager.card-viewer', [
            'schedule'      => $schedule,
            'activeVersion' => $activeVersion,
            'slides'        => $slides,
            'firstDate'     => $firstDate,
            'lastDate'      => $lastDate,
            'criticalRules' => $schedule->criticalRules,
            'generatedAt'   => \Illuminate\Support\Carbon::now('Asia/Manila'),
        ]);
    }

    /**
     * Per-date map of priority-resolved irrigation entries. Same algorithm
     * the workerPresentation uses inline — extracted here so the card
     * viewer can reuse it without duplicating the band-cut logic.
     *
     * The map shape is: ['Y-m-d' => [
     *   ['irrigation' => Model, 'taskMeta' => array, 'priority' => int,
     *    'groupNames' => string[]],
     *   ...
     * ]]
     *
     * Deduplicated by irrigation id within each date — when the same
     * irrigation covers multiple default-groupings on the same calendar
     * day, the entry lists every affected group via groupNames[].
     */
    private function resolveIrrigationsByDate(\App\Models\AsCroppingSchedule $schedule): array
    {
        $rawWindows = [];
        foreach ($schedule->irrigations as $irrigation) {
            $taskMeta = \App\Models\AsScheduleIrrigation::taskTypeMeta($irrigation->taskType);
            $priority = (int) ($irrigation->priority ?? 5);

            if ($irrigation->dayMode === 'date' && $irrigation->startDate && $irrigation->endDate) {
                $rawWindows[] = [
                    'start'      => $irrigation->startDate->copy(),
                    'end'        => $irrigation->endDate->copy(),
                    'groupKey'   => '__absolute__',
                    'groupName'  => '',
                    'priority'   => $priority,
                    'irrigation' => $irrigation,
                    'taskMeta'   => $taskMeta,
                ];
            } else {
                foreach ($schedule->defaultGroupings as $group) {
                    $groupStart = $group->startDate ? \Illuminate\Support\Carbon::parse($group->startDate) : null;
                    if (!$groupStart) continue;
                    $rawWindows[] = [
                        'start'      => $groupStart->copy()->addDays((int) $irrigation->startDay),
                        'end'        => $groupStart->copy()->addDays((int) $irrigation->endDay),
                        'groupKey'   => 'g' . $group->id,
                        'groupName'  => $group->groupName,
                        'priority'   => $priority,
                        'irrigation' => $irrigation,
                        'taskMeta'   => $taskMeta,
                    ];
                }
            }
        }

        // Per-day winners within each group context (priority resolution).
        $dayWinners = [];
        foreach ($rawWindows as $win) {
            $cursor = $win['start']->copy();
            while ($cursor->lte($win['end'])) {
                $dateKey = $cursor->format('Y-m-d');
                $existing = $dayWinners[$win['groupKey']][$dateKey] ?? null;
                $existingPriority = $existing['priority'] ?? PHP_INT_MAX;
                $beats = $win['priority'] < $existingPriority
                    || ($win['priority'] === $existingPriority
                        && (int) $win['irrigation']->id > (int) ($existing['irrigation']->id ?? -1));
                if ($beats) {
                    $dayWinners[$win['groupKey']][$dateKey] = $win;
                }
                $cursor->addDay();
            }
        }

        // Flatten + deduplicate by irrigation id; collect groupNames.
        $byDateAndId = [];
        foreach ($dayWinners as $groupKey => $byDate) {
            foreach ($byDate as $dateKey => $winner) {
                $irrId = (int) $winner['irrigation']->id;
                if (!isset($byDateAndId[$dateKey][$irrId])) {
                    $byDateAndId[$dateKey][$irrId] = [
                        'irrigation' => $winner['irrigation'],
                        'taskMeta'   => $winner['taskMeta'],
                        'priority'   => $winner['priority'],
                        'groupNames' => [],
                    ];
                }
                if (!empty($winner['groupName'])
                    && !in_array($winner['groupName'], $byDateAndId[$dateKey][$irrId]['groupNames'], true)) {
                    $byDateAndId[$dateKey][$irrId]['groupNames'][] = $winner['groupName'];
                }
            }
        }

        $result = [];
        foreach ($byDateAndId as $dateKey => $entries) {
            usort($entries, function ($a, $b) {
                if ($a['priority'] !== $b['priority']) return $a['priority'] <=> $b['priority'];
                return (int) $b['irrigation']->id <=> (int) $a['irrigation']->id;
            });
            $result[$dateKey] = array_values($entries);
        }
        return $result;
    }

    /**
     * Resolve the active version id for a schedule, falling back to whichever
     * version exists (preferring Original) if no row is flagged isActive.
     * Returns null only for schedules that have zero versions at all, which
     * shouldn't happen post-migration but is handled defensively so save
     * actions never blow up on a misconfigured row.
     */
    private function activeVersionIdFor(int $scheduleId): ?int
    {
        $active = AsScheduleActivityVersion::active()
            ->forSchedule($scheduleId)
            ->where('isActive', 1)
            ->orderBy('id', 'asc')
            ->first();
        if ($active) return (int) $active->id;

        $fallback = AsScheduleActivityVersion::active()
            ->forSchedule($scheduleId)
            ->orderBy('isOriginal', 'desc')
            ->orderBy('id', 'asc')
            ->first();
        return $fallback ? (int) $fallback->id : null;
    }
}
