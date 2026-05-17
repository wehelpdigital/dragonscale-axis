<?php

namespace App\Http\Controllers\aniSensoAdmin\ScheduleManager;

use App\Models\AsScheduleIrrigation;
use App\Models\AsScheduleWorker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class IrrigationController extends BaseScheduleController
{
    public function store(Request $request)
    {
        return $this->save($request, null);
    }

    public function update(Request $request)
    {
        return $this->save($request, $this->queryId($request));
    }

    public function destroy(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);
        $irrigation = AsScheduleIrrigation::active()->where('croppingScheduleId', $schedule->id)->where('id', $id)->first();
        if (!$irrigation) return $this->jsonFail('Irrigation entry not found.', 404);

        $irrigation->update(['deleteStatus' => 0]);
        return $this->jsonOk('Irrigation entry deleted.');
    }

    private function save(Request $request, $id = null)
    {
        $schedule = $this->scheduleFromRequest($request);

        $validator = Validator::make($request->all(), [
            'irrigationTitle'   => 'required|string|max:255',
            'description'       => 'nullable|string|max:2000',
            'startDay'          => 'required|integer',
            'endDay'            => 'required|integer|gte:startDay',
            'assignedWorkerId'  => 'nullable|integer',
            'timeRequired'      => 'nullable|in:half,whole',
        ]);

        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        if ($request->filled('assignedWorkerId')) {
            $ok = AsScheduleWorker::active()
                ->where('croppingScheduleId', $schedule->id)
                ->where('id', $request->assignedWorkerId)
                ->exists();
            if (!$ok) return $this->jsonFail('Selected worker does not belong to this schedule.', 422);
        }

        $payload = [
            'croppingScheduleId' => $schedule->id,
            'irrigationTitle'    => $request->irrigationTitle,
            'description'        => $request->description,
            'startDay'           => $request->startDay,
            'endDay'             => $request->endDay,
            'assignedWorkerId'   => $request->assignedWorkerId,
            // Field removed from the irrigation form — kept on the column so the
            // calendar generator (which feeds it into event.timeOfDay) keeps
            // working. Default to 'half' for any newly-created entry.
            'timeRequired'       => $request->input('timeRequired') ?: 'half',
            'deleteStatus'       => 1,
        ];

        if ($id) {
            $irrigation = AsScheduleIrrigation::active()->where('croppingScheduleId', $schedule->id)->where('id', $id)->first();
            if (!$irrigation) return $this->jsonFail('Irrigation entry not found.', 404);
            $irrigation->update($payload);
        } else {
            $irrigation = AsScheduleIrrigation::create($payload);
        }

        return $this->jsonOk($id ? 'Irrigation entry updated.' : 'Irrigation entry added.', [
            'data' => $irrigation->fresh('assignedWorker'),
        ]);
    }
}
