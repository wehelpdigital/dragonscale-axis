<?php

namespace App\Http\Controllers\aniSensoAdmin\ScheduleManager;

use App\Models\AsScheduleActivity;
use App\Models\AsScheduleActivityItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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

        return $this->jsonOk('Activity loaded.', ['data' => $payload]);
    }

    public function destroy(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);
        $activity = AsScheduleActivity::active()->where('croppingScheduleId', $schedule->id)->where('id', $id)->first();
        if (!$activity) return $this->jsonFail('Activity not found.', 404);

        $activity->update(['deleteStatus' => 0]);
        return $this->jsonOk('Activity deleted.');
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
            $rangeDays = 1;
            if ($activity->targetDate && $activity->targetEndDate) {
                $rangeDays = (int) $activity->targetDate->diffInDays($activity->targetEndDate) + 1;
                if ($rangeDays < 1) $rangeDays = 1;
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
            'activities' => fn ($q) => $q->orderBy('targetDate', 'asc'),
            'activities.workers',
            'activities.lots',
            'activities.items.material',
            'activities.items.service',
            'irrigations' => fn ($q) => $q->orderBy('startDay', 'asc'),
            'irrigations.assignedWorker',
            'defaultGroupings.lots',
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
        // Monochromatic blue palette — different cycles get different shades
        // but stay clearly "blue" so the visual mapping water = blue holds.
        $palette = ['#1565c0', '#1976d2', '#1e88e5', '#0d47a1', '#1e6dbf', '#0277bd', '#2563eb'];
        foreach ($schedule->irrigations as $irrIdx => $irrigation) {
            $color = $palette[$irrIdx % count($palette)];
            foreach ($schedule->defaultGroupings as $group) {
                $groupStart = $group->startDate ? \Illuminate\Support\Carbon::parse($group->startDate) : null;
                if (!$groupStart) continue;
                $cycleStart = $groupStart->copy()->addDays((int) $irrigation->startDay);
                $cycleEnd   = $groupStart->copy()->addDays((int) $irrigation->endDay);
                $segStart = $cycleStart->copy();
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
                        'groupName'    => $group->groupName,
                        'startCol'     => $startCol,
                        'endCol'       => $endCol,
                        'startDate'    => $segStart->copy(),
                        'endDate'      => $segEnd->copy(),
                        'dasStart'     => (int) $irrigation->startDay,
                        'dasEnd'       => (int) $irrigation->endDay,
                        'color'        => $color,
                    ];
                    $segStart = $segEnd->copy()->addDay();
                }
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

        return view('aniSensoAdmin.scheduleManager.worker-presentation', [
            'schedule'              => $schedule,
            'lotDayZero'            => $lotDayZero,
            'workerStats'           => $workerStats,
            'aggregateMonthly'      => $aggregateMonthly,
            'irrigationBandsByWeek' => $irrigationBandsByWeek,
            'activityBandsByWeek'   => $activityBandsByWeek,
            'activitiesByDate'      => $activitiesByDate,
            'calendarMonths'        => $calendarMonths,
            'firstDate'             => $firstDate,
            'lastDate'              => $lastDate,
            'generatedAt'           => \Illuminate\Support\Carbon::now('Asia/Manila'),
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
                $q->orderBy('targetDate')->orderBy('sequenceOrder')->orderBy('id');
            },
            'activities.lots',
            'activities.workers',
            'activities.items.material',
            'activities.items.service',
        ]);

        return view('aniSensoAdmin.scheduleManager.export', compact('schedule'));
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

        try {
            $new = DB::transaction(function () use ($source) {
                $copy = $source->replicate(['sequenceOrder']);
                $copy->activityTitle = mb_substr($source->activityTitle, 0, 240) . ' (copy)';
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
            'isDayZero'        => 'nullable|boolean',
            'description'      => 'nullable|string|max:20000',
            'timeRequired'     => 'required|in:half,whole,n/a',
            'lotIds'           => 'required|array|min:1',
            'lotIds.*'         => 'integer',
            'workerIds'        => 'nullable|array',
            'workerIds.*'      => 'integer',
            'items'                  => 'nullable|array',
            'items.*.itemType'       => 'required_with:items|in:material,service',
            'items.*.itemId'         => 'required_with:items|integer',
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

        // Lots must belong to this schedule.
        $validLotIds = \App\Models\AsScheduleLot::active()
            ->where('croppingScheduleId', $schedule->id)
            ->pluck('id')->all();
        $submittedLotIds = collect($request->input('lotIds', []))
            ->map(fn($v) => (int) $v)
            ->filter(fn($v) => in_array($v, $validLotIds, true))
            ->unique()->values()->all();
        if (empty($submittedLotIds)) {
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

        $payload = [
            'croppingScheduleId' => $schedule->id,
            'activityTitle'      => $request->activityTitle,
            'targetDate'         => $request->targetDate,
            'targetEndDate'      => $request->filled('targetEndDate') ? $request->targetEndDate : null,
            'priority'           => $request->priority,
            'isDayZero'          => $request->boolean('isDayZero'),
            'description'        => $request->description,
            'timeRequired'       => $request->timeRequired,
            'deleteStatus'       => 1,
        ];

        try {
            $activity = DB::transaction(function () use ($id, $schedule, $payload, $request, $submittedLotIds, $submittedWorkerIds) {
                if ($id) {
                    $activity = AsScheduleActivity::active()->where('croppingScheduleId', $schedule->id)->where('id', $id)->first();
                    if (!$activity) {
                        abort(404, 'Activity not found.');
                    }
                    $activity->update($payload);
                } else {
                    $activity = AsScheduleActivity::create($payload);
                }

                AsScheduleActivityItem::where('activityId', $activity->id)->update(['deleteStatus' => 0]);

                foreach ((array) $request->input('items', []) as $item) {
                    AsScheduleActivityItem::create([
                        'activityId'    => $activity->id,
                        'itemType'      => $item['itemType'],
                        'materialId'    => $item['itemType'] === 'material' ? $item['itemId'] : null,
                        'serviceId'     => $item['itemType'] === 'service'  ? $item['itemId'] : null,
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

        return $this->jsonOk($id ? 'Activity updated.' : 'Activity added.', [
            'data' => $data,
        ]);
    }
}
