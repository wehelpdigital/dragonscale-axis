<?php

namespace App\Services\ScheduleManager;

use App\Models\AsScheduleCalendarEvent;
use App\Models\AsScheduleGeneration;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CalendarGeneratorService
{
    /**
     * Generate (or regenerate) the calendar for a given generation.
     *
     * @param  bool  $preserveCompleted  When true, completed events from prior runs are kept and
     *                                   their worker loads pre-seeded so the regeneration plans
     *                                   around them.
     */
    public function generate(AsScheduleGeneration $generation, bool $preserveCompleted = false): array
    {
        $schedule = $generation->schedule()->with([
            'activities.items',
            'activities.lots',
            'activities.workers.offDates',
            'activities.workers.offDays',
            'irrigations',
            'workers.offDates',
            'workers.offDays',
        ])->first();

        if (!$schedule) {
            throw new \RuntimeException('Cropping schedule for generation not found.');
        }

        $groupings = $generation->groupings()->with(['lots' => function ($q) {
            $q->where('as_schedule_lots.deleteStatus', 1);
        }])->get();

        if ($groupings->isEmpty()) {
            throw new \RuntimeException('At least one grouping must be defined before generating the calendar.');
        }

        $activities  = $schedule->activities()->with(['lots', 'workers.offDates', 'workers.offDays'])->orderBy('targetDate')->get();
        $irrigations = $schedule->irrigations;
        $workers     = $schedule->workers()->orderBy('priority', 'asc')->get();

        $genOffDates = $generation->offDates->pluck('offDate')->map(function ($d) {
            return Carbon::parse($d)->toDateString();
        })->all();
        $genOffDays = $generation->offDays->pluck('dayOfWeek')->map('intval')->all();

        // Worker load tracker. Whole-day = 1.0, half-day = 0.5. Anything > 1.0 means overloaded.
        $workerLoad = [];

        // Pre-seed load from any completed events if we are preserving them.
        if ($preserveCompleted) {
            $completed = AsScheduleCalendarEvent::where('generationId', $generation->id)
                ->where('deleteStatus', 1)
                ->where('status', 'completed')
                ->get();

            foreach ($completed as $ev) {
                $inc = $ev->timeOfDay === 'whole' ? 1.0 : 0.5;
                $key = $ev->scheduledDate ? Carbon::parse($ev->scheduledDate)->toDateString() : null;
                if (!$key) continue;
                foreach ((array) ($ev->assignedWorkerIds ?? []) as $wid) {
                    $workerLoad[$wid][$key] = ($workerLoad[$wid][$key] ?? 0) + $inc;
                }
            }

            // Remove only the pending events from this generation.
            AsScheduleCalendarEvent::where('generationId', $generation->id)
                ->where('status', 'pending')
                ->delete();
        } else {
            AsScheduleCalendarEvent::where('generationId', $generation->id)->delete();
        }

        $seasonStart = Carbon::parse($generation->seasonStartDate);
        $sequenceOrder = 0;
        $warnings = [];

        DB::transaction(function () use (
            $generation,
            $schedule,
            $groupings,
            $activities,
            $irrigations,
            $workers,
            $seasonStart,
            $genOffDates,
            $genOffDays,
            $preserveCompleted,
            &$workerLoad,
            &$sequenceOrder,
            &$warnings
        ) {
            // ---- Activities: fully manual. Each activity has an explicit date,
            //      explicit lots, and explicit workers. The generator just
            //      records events on that date for each lot and tallies the
            //      workers (skipping any worker that's individually off). ----
            foreach ($activities as $activity) {
                $activityLots = $activity->lots->values();
                if ($activityLots->isEmpty() || !$activity->targetDate) {
                    continue;
                }

                $startDate = Carbon::parse($activity->targetDate);
                $endDate   = $activity->targetEndDate
                    ? Carbon::parse($activity->targetEndDate)
                    : $startDate->copy();
                if ($endDate->lessThan($startDate)) {
                    $endDate = $startDate->copy();
                }

                $activityWorkers = $activity->workers->values();
                $inc = match ($activity->timeRequired) {
                    'whole' => 1.0,
                    'half'  => 0.5,
                    default => 0.0,
                };

                // Loop every day in the range. Each day x lot becomes its own event.
                $current = $startDate->copy();
                while ($current->lessThanOrEqualTo($endDate)) {
                    $dateKey = $current->toDateString();

                    // Filter workers by their own off-day rules for THIS day.
                    $assigned = [];
                    $skippedWorkerNames = [];
                    foreach ($activityWorkers as $worker) {
                        if ($worker->isAvailableOn($current)) {
                            $assigned[] = (int) $worker->id;
                        } else {
                            $skippedWorkerNames[] = $worker->workerName;
                        }
                    }

                    $isGenOff = in_array((int) $current->dayOfWeek, $genOffDays, true)
                             || in_array($dateKey, $genOffDates, true);

                    foreach ($activityLots as $lot) {
                        if ($preserveCompleted) {
                            $hasCompleted = AsScheduleCalendarEvent::where('generationId', $generation->id)
                                ->where('eventType', 'activity')
                                ->where('activityId', $activity->id)
                                ->where('lotId', $lot->id)
                                ->where('scheduledDate', $dateKey)
                                ->where('status', 'completed')
                                ->exists();
                            if ($hasCompleted) {
                                continue;
                            }
                        }

                        if ($inc > 0) {
                            foreach ($assigned as $wid) {
                                $workerLoad[$wid][$dateKey] = ($workerLoad[$wid][$dateKey] ?? 0) + $inc;
                            }
                        }

                        if (!empty($skippedWorkerNames)) {
                            $warnings[] = "Activity '{$activity->activityTitle}' on lot '{$lot->lotName}' ({$dateKey}): worker(s) unavailable — " . implode(', ', $skippedWorkerNames);
                        }
                        if ($isGenOff) {
                            $warnings[] = "Activity '{$activity->activityTitle}' on lot '{$lot->lotName}' ({$dateKey}): scheduled on a generation off-day";
                        }

                        AsScheduleCalendarEvent::create([
                            'generationId'        => $generation->id,
                            'croppingScheduleId'  => $schedule->id,
                            'groupingId'          => null,
                            'lotId'               => $lot->id,
                            'eventType'           => 'activity',
                            'activityId'          => $activity->id,
                            'eventTitle'          => $activity->activityTitle,
                            'scheduledDate'       => $dateKey,
                            'originalDate'        => $dateKey,
                            'timeOfDay'           => $activity->timeRequired,
                            'targetDayValue'      => null,
                            'priority'            => $activity->priority,
                            'status'              => 'pending',
                            'assignedWorkerIds'   => $assigned,
                            'sequenceOrder'       => $sequenceOrder++,
                            'deleteStatus'        => 1,
                        ]);
                    }

                    $current->addDay();
                }
            }

            // ---- Irrigations: still scheduled per grouping using day-range from group start ----
            foreach ($groupings as $group) {
                // Per-group start: explicit startDate wins, otherwise seasonStart + staggerDays.
                $groupStart = $group->startDate
                    ? Carbon::parse($group->startDate)
                    : $seasonStart->copy()->addDays((int) $group->staggerDays);
                $lots = $group->lots->values();

                // ---- Irrigations (one event per lot per day in range) ----
                foreach ($irrigations as $irrigation) {
                    foreach ($lots as $lot) {
                        for ($d = (int) $irrigation->startDay; $d <= (int) $irrigation->endDay; $d++) {
                            $targetDate = $groupStart->copy()->addDays($d);

                            if ($preserveCompleted) {
                                $hasCompleted = AsScheduleCalendarEvent::where('generationId', $generation->id)
                                    ->where('eventType', 'irrigation')
                                    ->where('irrigationId', $irrigation->id)
                                    ->where('lotId', $lot->id)
                                    ->where('scheduledDate', $targetDate->toDateString())
                                    ->where('status', 'completed')
                                    ->exists();
                                if ($hasCompleted) {
                                    continue;
                                }
                            }

                            // Irrigation tolerates off-days; we only shift by 1 day if absolutely off.
                            [$plannedDate, ] = $this->pickValidDate($targetDate, 1, 1, $genOffDates, $genOffDays);

                            $assigned = [];
                            if ($irrigation->assignedWorkerId) {
                                $assigned = [(int) $irrigation->assignedWorkerId];
                            }

                            AsScheduleCalendarEvent::create([
                                'generationId'        => $generation->id,
                                'croppingScheduleId'  => $schedule->id,
                                'groupingId'          => $group->id,
                                'lotId'               => $lot->id,
                                'eventType'           => 'irrigation',
                                'irrigationId'        => $irrigation->id,
                                'eventTitle'          => $irrigation->irrigationTitle . ' (D' . $d . ')',
                                'scheduledDate'       => $plannedDate->toDateString(),
                                'originalDate'        => $targetDate->toDateString(),
                                'timeOfDay'           => $irrigation->timeRequired,
                                'targetDayValue'      => $d,
                                'priority'            => 'medium',
                                'status'              => 'pending',
                                'assignedWorkerIds'   => $assigned,
                                'sequenceOrder'       => $sequenceOrder++,
                                'deleteStatus'        => 1,
                            ]);
                        }
                    }
                }
            }
        });

        // Mark schedule as generated.
        $schedule->update(['status' => 'generated']);

        return [
            'eventsCount' => AsScheduleCalendarEvent::where('generationId', $generation->id)->where('deleteStatus', 1)->count(),
            'warnings'    => $warnings,
        ];
    }

    /**
     * Find a valid date within the +/- window. Returns [date, wasShifted].
     */
    protected function pickValidDate(Carbon $target, int $windowBefore, int $windowAfter, array $offDates, array $offDays): array
    {
        if ($this->isValidDate($target, $offDates, $offDays)) {
            return [$target->copy(), false];
        }

        $maxWin = max($windowBefore, $windowAfter);
        for ($i = 1; $i <= $maxWin; $i++) {
            if ($i <= $windowAfter) {
                $cand = $target->copy()->addDays($i);
                if ($this->isValidDate($cand, $offDates, $offDays)) return [$cand, true];
            }
            if ($i <= $windowBefore) {
                $cand = $target->copy()->subDays($i);
                if ($this->isValidDate($cand, $offDates, $offDays)) return [$cand, true];
            }
        }

        return [$target->copy(), true];
    }

    protected function isValidDate(Carbon $date, array $offDates, array $offDays): bool
    {
        if (in_array((int) $date->dayOfWeek, $offDays, true)) return false;
        if (in_array($date->toDateString(), $offDates, true)) return false;
        return true;
    }

    /**
     * Assign workers honoring priority + availability + load. Returns [ids, finalDate, warning|null].
     */
    protected function assignWorkers(
        Collection $workers,
        Carbon $startDate,
        int $needed,
        string $timeRequired,
        int $windowBefore,
        int $windowAfter,
        array &$workerLoad,
        array $offDates,
        array $offDays
    ): array {
        if ($needed <= 0) {
            return [[], $startDate->copy(), null];
        }

        $req = $timeRequired === 'whole' ? 1.0 : 0.5;

        $candidates = [$startDate->copy()];
        $maxWin = max($windowBefore, $windowAfter);
        for ($i = 1; $i <= $maxWin; $i++) {
            if ($i <= $windowAfter)  $candidates[] = $startDate->copy()->addDays($i);
            if ($i <= $windowBefore) $candidates[] = $startDate->copy()->subDays($i);
        }

        foreach ($candidates as $date) {
            if (!$this->isValidDate($date, $offDates, $offDays)) continue;
            $key = $date->toDateString();

            $available = [];
            foreach ($workers as $worker) {
                if (!$worker->isAvailableOn($date)) continue;
                $load = $workerLoad[$worker->id][$key] ?? 0;
                if ($load + $req <= 1.0 + 1e-9) {
                    $available[] = (int) $worker->id;
                    if (count($available) >= $needed) break;
                }
            }

            if (count($available) >= $needed) {
                return [$available, $date->copy(), null];
            }
        }

        // Fallback: assign whatever workers are at least personally available on the start date.
        $key = $startDate->toDateString();
        $partial = [];
        foreach ($workers as $worker) {
            if (!$worker->isAvailableOn($startDate)) continue;
            $partial[] = (int) $worker->id;
            if (count($partial) >= $needed) break;
        }

        $warning = $partial
            ? 'only ' . count($partial) . ' of ' . $needed . ' workers available; overlaps may occur'
            : 'no workers available; please assign manually';

        return [$partial, $startDate->copy(), $warning];
    }
}
