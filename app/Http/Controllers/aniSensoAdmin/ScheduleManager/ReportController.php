<?php

namespace App\Http\Controllers\aniSensoAdmin\ScheduleManager;

use App\Models\AsScheduleActivity;
use App\Models\AsScheduleActivityItem;
use App\Models\AsScheduleCalendarEvent;
use App\Models\AsScheduleGeneration;
use App\Models\AsScheduleMaterial;
use App\Models\AsScheduleService;
use App\Models\AsScheduleWorker;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends BaseScheduleController
{
    public function index(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $generation = AsScheduleGeneration::where('croppingScheduleId', $schedule->id)->where('isCurrent', 1)->first();

        return view('aniSensoAdmin.scheduleManager.reports', compact('schedule', 'generation'));
    }

    public function data(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $generation = AsScheduleGeneration::where('croppingScheduleId', $schedule->id)->where('isCurrent', 1)->first();

        if (!$generation) {
            return $this->jsonOk('No generation yet.', ['data' => null]);
        }

        $events = AsScheduleCalendarEvent::where('generationId', $generation->id)
            ->where('deleteStatus', 1)
            ->get();

        $workers = AsScheduleWorker::active()->where('croppingScheduleId', $schedule->id)->get()->keyBy('id');
        $materials = AsScheduleMaterial::active()->where('croppingScheduleId', $schedule->id)->get()->keyBy('id');
        $services = AsScheduleService::active()->where('croppingScheduleId', $schedule->id)->get()->keyBy('id');
        $activities = AsScheduleActivity::active()->where('croppingScheduleId', $schedule->id)->get()->keyBy('id');

        $laborByWorker = [];
        foreach ($workers as $w) {
            $laborByWorker[$w->id] = [
                'workerId'      => $w->id,
                'workerName'    => $w->workerName,
                'priority'      => $w->priority,
                'costPerHalfDay'=> (float) $w->costPerHalfDay,
                'halfDayUnits'  => 0,
                'totalCost'     => 0.0,
                'totalCostCompleted' => 0.0,
                'eventsAssignedAll'   => 0,
                'eventsAssignedDone'  => 0,
            ];
        }

        $laborCostTotalAll = 0.0;
        $laborCostTotalDone = 0.0;
        $totalEventsAll = 0;
        $totalEventsDone = 0;
        $totalExtraCost = 0.0;

        foreach ($events as $ev) {
            $units = $ev->timeOfDay === 'whole' ? 2 : 1;
            $ids = $ev->assignedWorkerIds ?? [];
            $totalEventsAll++;
            if ($ev->status === 'completed') {
                $totalEventsDone++;
                $totalExtraCost += (float) $ev->extraCost;
            }

            foreach ($ids as $wid) {
                if (!isset($laborByWorker[$wid])) continue;
                $cost = $units * (float) $laborByWorker[$wid]['costPerHalfDay'];
                $laborByWorker[$wid]['halfDayUnits'] += $units;
                $laborByWorker[$wid]['totalCost'] += $cost;
                $laborByWorker[$wid]['eventsAssignedAll']++;
                $laborCostTotalAll += $cost;

                if ($ev->status === 'completed') {
                    $laborByWorker[$wid]['totalCostCompleted'] += $cost;
                    $laborByWorker[$wid]['eventsAssignedDone']++;
                    $laborCostTotalDone += $cost;
                }
            }
        }

        $materialUsage = [];
        $serviceUsage = [];

        $itemsByActivity = AsScheduleActivityItem::whereIn('activityId', $activities->keys()->all())
            ->where('deleteStatus', 1)
            ->get()
            ->groupBy('activityId');

        foreach ($events as $ev) {
            if ($ev->eventType !== 'activity') continue;
            $aid = $ev->activityId;
            if (!isset($itemsByActivity[$aid])) continue;
            $isCompleted = $ev->status === 'completed';

            foreach ($itemsByActivity[$aid] as $item) {
                if ($item->itemType === 'material') {
                    $m = $materials[$item->materialId] ?? null;
                    if (!$m) continue;
                    $key = $item->materialId;
                    $materialUsage[$key] = $materialUsage[$key] ?? [
                        'materialId' => $m->id,
                        'materialName' => $m->materialName,
                        'materialType' => $m->materialType,
                        'unitOfMeasure' => $m->unitOfMeasure,
                        'priceAmount' => (float) $m->priceAmount,
                        'priceQuantity' => (float) $m->priceQuantity,
                        'plannedQty' => 0.0,
                        'usedQty' => 0.0,
                        'plannedCost' => 0.0,
                        'usedCost' => 0.0,
                    ];
                    $pricePerUnit = $m->priceQuantity > 0 ? ((float) $m->priceAmount) / ((float) $m->priceQuantity) : 0;
                    $qty = (float) $item->quantity;
                    $materialUsage[$key]['plannedQty'] += $qty;
                    $materialUsage[$key]['plannedCost'] += $qty * $pricePerUnit;
                    if ($isCompleted) {
                        $materialUsage[$key]['usedQty'] += $qty;
                        $materialUsage[$key]['usedCost'] += $qty * $pricePerUnit;
                    }
                } elseif ($item->itemType === 'service') {
                    $s = $services[$item->serviceId] ?? null;
                    if (!$s) continue;
                    $key = $item->serviceId;
                    $serviceUsage[$key] = $serviceUsage[$key] ?? [
                        'serviceId' => $s->id,
                        'serviceName' => $s->serviceName,
                        'serviceCost' => (float) $s->serviceCost,
                        'plannedTimes' => 0,
                        'usedTimes' => 0,
                        'plannedCost' => 0.0,
                        'usedCost' => 0.0,
                    ];
                    $serviceUsage[$key]['plannedTimes']++;
                    $serviceUsage[$key]['plannedCost'] += (float) $s->serviceCost;
                    if ($isCompleted) {
                        $serviceUsage[$key]['usedTimes']++;
                        $serviceUsage[$key]['usedCost'] += (float) $s->serviceCost;
                    }
                }
            }
        }

        $unusedWorkers = collect($laborByWorker)->filter(fn($w) => $w['eventsAssignedAll'] === 0)->values()->all();
        $usedWorkers = collect($laborByWorker)->filter(fn($w) => $w['eventsAssignedAll'] > 0)->values()->all();

        return $this->jsonOk('Report.', [
            'data' => [
                'summary' => [
                    'totalEvents' => $totalEventsAll,
                    'completedEvents' => $totalEventsDone,
                    'pendingEvents' => $totalEventsAll - $totalEventsDone,
                    'laborCostPlanned' => round($laborCostTotalAll, 2),
                    'laborCostCompleted' => round($laborCostTotalDone, 2),
                    'extraCostLogged' => round($totalExtraCost, 2),
                    'materialsCostPlanned' => round(collect($materialUsage)->sum('plannedCost'), 2),
                    'materialsCostUsed' => round(collect($materialUsage)->sum('usedCost'), 2),
                    'servicesCostPlanned' => round(collect($serviceUsage)->sum('plannedCost'), 2),
                    'servicesCostUsed' => round(collect($serviceUsage)->sum('usedCost'), 2),
                    'grandTotalPlanned' => round($laborCostTotalAll + collect($materialUsage)->sum('plannedCost') + collect($serviceUsage)->sum('plannedCost'), 2),
                    'grandTotalActual' => round($laborCostTotalDone + collect($materialUsage)->sum('usedCost') + collect($serviceUsage)->sum('usedCost') + $totalExtraCost, 2),
                ],
                'laborByWorker' => array_values($laborByWorker),
                'usedWorkers' => $usedWorkers,
                'unusedWorkers' => $unusedWorkers,
                'materialUsage' => array_values($materialUsage),
                'serviceUsage' => array_values($serviceUsage),
                'generation' => [
                    'id' => $generation->id,
                    'number' => $generation->generationNumber,
                    'seasonStartDate' => Carbon::parse($generation->seasonStartDate)->format('M j, Y'),
                    'generatedAt' => $generation->generatedAt ? Carbon::parse($generation->generatedAt)->format('M j, Y g:i A') : null,
                ],
            ],
        ]);
    }
}
