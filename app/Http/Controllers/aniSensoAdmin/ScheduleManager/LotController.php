<?php

namespace App\Http\Controllers\aniSensoAdmin\ScheduleManager;

use App\Models\AsScheduleLot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LotController extends BaseScheduleController
{
    public function store(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $validator = Validator::make($request->all(), [
            'lotName'     => 'required|string|max:255',
            'lotSize'     => 'required|numeric|min:0',
            'lotSizeUnit' => 'required|string|max:50',
            'dayZeroDate' => 'nullable|date',
            'notes'       => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $lot = AsScheduleLot::create([
            'croppingScheduleId' => $schedule->id,
            'lotName'            => $request->lotName,
            'lotSize'            => $request->lotSize,
            'lotSizeUnit'        => $request->lotSizeUnit,
            'dayZeroDate'        => $request->filled('dayZeroDate') ? $request->dayZeroDate : null,
            'notes'              => $request->notes,
            'deleteStatus'       => 1,
        ]);

        return $this->jsonOk('Lot added.', ['data' => $lot]);
    }

    public function update(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);
        $lot = AsScheduleLot::active()->where('croppingScheduleId', $schedule->id)->where('id', $id)->first();
        if (!$lot) return $this->jsonFail('Lot not found.', 404);

        $validator = Validator::make($request->all(), [
            'lotName'     => 'required|string|max:255',
            'lotSize'     => 'required|numeric|min:0',
            'lotSizeUnit' => 'required|string|max:50',
            'dayZeroDate' => 'nullable|date',
            'notes'       => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $lot->update([
            'lotName'     => $request->lotName,
            'lotSize'     => $request->lotSize,
            'lotSizeUnit' => $request->lotSizeUnit,
            'dayZeroDate' => $request->filled('dayZeroDate') ? $request->dayZeroDate : null,
            'notes'       => $request->notes,
        ]);
        return $this->jsonOk('Lot updated.', ['data' => $lot]);
    }

    public function destroy(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);
        $lot = AsScheduleLot::active()->where('croppingScheduleId', $schedule->id)->where('id', $id)->first();
        if (!$lot) return $this->jsonFail('Lot not found.', 404);

        $lot->update(['deleteStatus' => 0]);
        return $this->jsonOk('Lot deleted.');
    }
}
