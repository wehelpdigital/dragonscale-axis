<?php

namespace App\Http\Controllers\aniSensoAdmin\ScheduleManager;

use App\Models\AsScheduleCalendarEvent;
use App\Models\AsScheduleGeneration;
use App\Models\AsScheduleWorker;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CalendarController extends BaseScheduleController
{
    public function index(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $generation = AsScheduleGeneration::where('croppingScheduleId', $schedule->id)
            ->where('isCurrent', 1)
            ->first();

        if (!$generation) {
            return redirect()
                ->route('anisenso-schedule-manager.generate.form', ['scheduleId' => $schedule->id])
                ->with('info', 'Generate the calendar first.');
        }

        $schedule->load(['lots', 'workers', 'activities', 'irrigations']);

        return view('aniSensoAdmin.scheduleManager.calendar', compact('schedule', 'generation'));
    }

    public function events(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $generation = AsScheduleGeneration::where('croppingScheduleId', $schedule->id)
            ->where('isCurrent', 1)
            ->first();

        if (!$generation) {
            return $this->jsonOk('No generation yet.', ['data' => []]);
        }

        $events = AsScheduleCalendarEvent::where('generationId', $generation->id)
            ->where('deleteStatus', 1)
            ->orderBy('scheduledDate')
            ->orderBy('sequenceOrder')
            ->get();

        return $this->jsonOk('Events loaded.', [
            'data' => $events,
        ]);
    }

    public function updateEvent(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);

        $event = AsScheduleCalendarEvent::where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)
            ->where('id', $id)
            ->first();
        if (!$event) return $this->jsonFail('Calendar event not found.', 404);

        if ($event->status === 'completed') {
            return $this->jsonFail('Completed events cannot be edited.', 422);
        }

        $validator = Validator::make($request->all(), [
            'scheduledDate'      => 'required|date',
            'timeOfDay'          => 'required|in:half,whole',
            'priority'           => 'required|in:critical,high,medium,low',
            'assignedWorkerIds'  => 'nullable|array',
            'assignedWorkerIds.*'=> 'integer',
            'remarks'            => 'nullable|string|max:5000',
        ]);

        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $validWorkerIds = AsScheduleWorker::active()
            ->where('croppingScheduleId', $schedule->id)
            ->pluck('id')->all();
        $assigned = collect((array) $request->input('assignedWorkerIds', []))
            ->map(fn($v) => (int) $v)
            ->filter(fn($v) => in_array($v, $validWorkerIds, true))
            ->values()->all();

        $event->update([
            'scheduledDate'     => $request->scheduledDate,
            'timeOfDay'         => $request->timeOfDay,
            'priority'          => $request->priority,
            'assignedWorkerIds' => $assigned,
            'remarks'           => $request->remarks,
        ]);

        return $this->jsonOk('Event updated.', ['data' => $event->fresh()]);
    }

    public function completeEvent(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);

        $event = AsScheduleCalendarEvent::where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)
            ->where('id', $id)
            ->first();
        if (!$event) return $this->jsonFail('Calendar event not found.', 404);

        if ($event->status === 'completed') {
            return $this->jsonFail('Event is already completed.', 422);
        }

        $blocker = AsScheduleCalendarEvent::where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)
            ->where('lotId', $event->lotId)
            ->where('status', '!=', 'completed')
            ->where(function ($q) use ($event) {
                $q->where('scheduledDate', '<', $event->scheduledDate)
                  ->orWhere(function ($q2) use ($event) {
                      $q2->where('scheduledDate', $event->scheduledDate)
                         ->where('sequenceOrder', '<', $event->sequenceOrder);
                  });
            })
            ->orderBy('scheduledDate')
            ->orderBy('sequenceOrder')
            ->first();

        if ($blocker) {
            return $this->jsonFail(
                "You cannot complete this yet. The earlier activity '{$blocker->eventTitle}' on " .
                Carbon::parse($blocker->scheduledDate)->format('M j, Y') . ' must be completed first.',
                422
            );
        }

        $validator = Validator::make($request->all(), [
            'extraCost'            => 'nullable|numeric|min:0',
            'extraCostDescription' => 'nullable|string|max:2000',
            'remarks'              => 'nullable|string|max:5000',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $event->update([
            'status'               => 'completed',
            'completedAt'          => now(),
            'completedBy'          => Auth::id(),
            'extraCost'            => $request->input('extraCost', 0) ?: 0,
            'extraCostDescription' => $request->input('extraCostDescription'),
            'remarks'              => $request->input('remarks', $event->remarks),
        ]);

        return $this->jsonOk('Event marked completed.', ['data' => $event->fresh()]);
    }

    public function uncompleteEvent(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);

        $event = AsScheduleCalendarEvent::where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)
            ->where('id', $id)
            ->first();
        if (!$event) return $this->jsonFail('Calendar event not found.', 404);
        if ($event->status !== 'completed') return $this->jsonFail('Event is not completed.', 422);

        $laterCompleted = AsScheduleCalendarEvent::where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)
            ->where('lotId', $event->lotId)
            ->where('status', 'completed')
            ->where(function ($q) use ($event) {
                $q->where('scheduledDate', '>', $event->scheduledDate)
                  ->orWhere(function ($q2) use ($event) {
                      $q2->where('scheduledDate', $event->scheduledDate)
                         ->where('sequenceOrder', '>', $event->sequenceOrder);
                  });
            })
            ->exists();

        if ($laterCompleted) {
            return $this->jsonFail('Cannot un-complete: a later event in the same lot is already marked complete.', 422);
        }

        $event->update([
            'status'      => 'pending',
            'completedAt' => null,
            'completedBy' => null,
        ]);

        return $this->jsonOk('Event reverted to pending.', ['data' => $event->fresh()]);
    }
}
