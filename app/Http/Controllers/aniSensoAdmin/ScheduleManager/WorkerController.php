<?php

namespace App\Http\Controllers\aniSensoAdmin\ScheduleManager;

use App\Models\AsScheduleWorker;
use App\Models\AsScheduleWorkerOffDate;
use App\Models\AsScheduleWorkerOffDay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WorkerController extends BaseScheduleController
{
    public function store(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $validator = Validator::make($request->all(), [
            'workerName' => 'required|string|max:255',
            'costPerHalfDay' => 'nullable|numeric|min:0',
            'priority' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $worker = AsScheduleWorker::create([
            'croppingScheduleId' => $schedule->id,
            'workerName' => $request->workerName,
            'costPerHalfDay' => is_numeric($request->costPerHalfDay) ? $request->costPerHalfDay : 0,
            'priority' => $request->priority,
            'notes' => $request->notes,
            'deleteStatus' => 1,
        ]);

        return $this->jsonOk('Worker added.', ['data' => $worker]);
    }

    public function update(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);
        $worker = AsScheduleWorker::active()->where('croppingScheduleId', $schedule->id)->where('id', $id)->first();
        if (!$worker) return $this->jsonFail('Worker not found.', 404);

        $validator = Validator::make($request->all(), [
            'workerName' => 'required|string|max:255',
            'costPerHalfDay' => 'nullable|numeric|min:0',
            'priority' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $worker->update([
            'workerName' => $request->workerName,
            'costPerHalfDay' => is_numeric($request->costPerHalfDay) ? $request->costPerHalfDay : 0,
            'priority' => $request->priority,
            'notes' => $request->notes,
        ]);
        return $this->jsonOk('Worker updated.', ['data' => $worker]);
    }

    public function destroy(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);
        $worker = AsScheduleWorker::active()->where('croppingScheduleId', $schedule->id)->where('id', $id)->first();
        if (!$worker) return $this->jsonFail('Worker not found.', 404);

        $worker->update(['deleteStatus' => 0]);
        return $this->jsonOk('Worker deleted.');
    }

    public function rules(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);
        $worker = AsScheduleWorker::active()
            ->where('croppingScheduleId', $schedule->id)
            ->where('id', $id)
            ->with(['offDates', 'offDays'])
            ->first();

        if (!$worker) return $this->jsonFail('Worker not found.', 404);

        return $this->jsonOk('Worker rules.', [
            'data' => [
                'worker' => $worker,
                'offDates' => $worker->offDates,
                'offDays' => $worker->offDays->pluck('dayOfWeek'),
            ],
        ]);
    }

    public function saveRules(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);
        $worker = AsScheduleWorker::active()->where('croppingScheduleId', $schedule->id)->where('id', $id)->first();
        if (!$worker) return $this->jsonFail('Worker not found.', 404);

        $validator = Validator::make($request->all(), [
            'offDates'   => 'nullable|array',
            'offDates.*' => 'nullable|date',
            'offDays'    => 'nullable|array',
            'offDays.*'  => 'integer|min:0|max:6',
        ]);

        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        DB::transaction(function () use ($worker, $request) {
            AsScheduleWorkerOffDate::where('workerId', $worker->id)->delete();
            AsScheduleWorkerOffDay::where('workerId', $worker->id)->delete();

            foreach ((array) $request->input('offDates', []) as $d) {
                if (!$d) continue;
                AsScheduleWorkerOffDate::create(['workerId' => $worker->id, 'offDate' => $d]);
            }

            foreach (array_unique((array) $request->input('offDays', [])) as $dow) {
                AsScheduleWorkerOffDay::create(['workerId' => $worker->id, 'dayOfWeek' => (int) $dow]);
            }
        });

        return $this->jsonOk('Rules saved.');
    }
}
