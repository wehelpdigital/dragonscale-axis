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
            'variety'        => 'nullable|string|max:255',
            // The farmer app writes these three; the admin can now set them
            // too, which is what makes growth stages and the forecast
            // manageable from this side.
            'crop'           => 'nullable|string|max:80',
            // TREE is the fourth: an orchard keeps no day count and is
            // read by the tree's age instead.
            'dayType'        => 'nullable|in:DAS,DAT,DAP,TREE',
            'daysToMaturity' => 'nullable|integer|min:1|max:2000',
            'treePlantedAt'  => 'nullable|date',
            'pinLat'         => 'nullable|numeric|between:-90,90',
            'pinLng'         => 'nullable|numeric|between:-180,180',
            'pinLabel'       => 'nullable|string|max:191',
            'locBarangay'    => 'nullable|string|max:255',
            'locZone'        => 'nullable|string|max:255',
            'locTown'        => 'nullable|string|max:255',
            'locProvince'    => 'nullable|string|max:255',
            'dayZeroDate'    => 'nullable|date',
            'transplantDate' => 'nullable|date',
            'notes'          => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $lot = AsScheduleLot::create([
            'croppingScheduleId' => $schedule->id,
            'lotName'            => $request->lotName,
            'lotSize'            => $request->lotSize,
            'lotSizeUnit'        => $request->lotSizeUnit,
            'variety'            => $request->filled('variety') ? trim($request->variety) : null,
            'crop'               => $request->filled('crop') ? trim($request->crop) : null,
            'dayType'            => $request->filled('dayType') ? $request->dayType : 'DAT',
            'locBarangay'        => $request->filled('locBarangay') ? trim($request->locBarangay) : null,
            'locZone'            => $request->filled('locZone') ? trim($request->locZone) : null,
            'locTown'            => $request->filled('locTown') ? trim($request->locTown) : null,
            'locProvince'        => $request->filled('locProvince') ? trim($request->locProvince) : null,
            'dayZeroDate'        => $request->filled('dayZeroDate') ? $request->dayZeroDate : null,
            'transplantDate'     => $request->filled('transplantDate') ? $request->transplantDate : null,
            'daysToMaturity'     => $request->filled('daysToMaturity') ? (int) $request->daysToMaturity : null,
            'treePlantedAt'      => $request->filled('treePlantedAt') ? $request->treePlantedAt : null,
            'pinLat'             => $request->filled('pinLat') ? (float) $request->pinLat : null,
            'pinLng'             => $request->filled('pinLng') ? (float) $request->pinLng : null,
            'pinLabel'           => $request->filled('pinLabel') ? trim($request->pinLabel) : null,
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
            'variety'        => 'nullable|string|max:255',
            // The farmer app writes these three; the admin can now set them
            // too, which is what makes growth stages and the forecast
            // manageable from this side.
            'crop'           => 'nullable|string|max:80',
            // TREE is the fourth: an orchard keeps no day count and is
            // read by the tree's age instead.
            'dayType'        => 'nullable|in:DAS,DAT,DAP,TREE',
            'daysToMaturity' => 'nullable|integer|min:1|max:2000',
            'treePlantedAt'  => 'nullable|date',
            'pinLat'         => 'nullable|numeric|between:-90,90',
            'pinLng'         => 'nullable|numeric|between:-180,180',
            'pinLabel'       => 'nullable|string|max:191',
            'locBarangay'    => 'nullable|string|max:255',
            'locZone'        => 'nullable|string|max:255',
            'locTown'        => 'nullable|string|max:255',
            'locProvince'    => 'nullable|string|max:255',
            'dayZeroDate'    => 'nullable|date',
            'transplantDate' => 'nullable|date',
            'notes'          => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $lot->update([
            'lotName'     => $request->lotName,
            'lotSize'     => $request->lotSize,
            'lotSizeUnit' => $request->lotSizeUnit,
            'variety'        => $request->filled('variety') ? trim($request->variety) : null,
            'crop'           => $request->filled('crop') ? trim($request->crop) : null,
            'dayType'        => $request->filled('dayType') ? $request->dayType : 'DAT',
            'locBarangay'    => $request->filled('locBarangay') ? trim($request->locBarangay) : null,
            'locZone'        => $request->filled('locZone') ? trim($request->locZone) : null,
            'locTown'        => $request->filled('locTown') ? trim($request->locTown) : null,
            'locProvince'    => $request->filled('locProvince') ? trim($request->locProvince) : null,
            'dayZeroDate'    => $request->filled('dayZeroDate') ? $request->dayZeroDate : null,
            'transplantDate' => $request->filled('transplantDate') ? $request->transplantDate : null,
            'daysToMaturity' => $request->filled('daysToMaturity') ? (int) $request->daysToMaturity : null,
            'treePlantedAt'  => $request->filled('treePlantedAt') ? $request->treePlantedAt : null,
            'pinLat'         => $request->filled('pinLat') ? (float) $request->pinLat : null,
            'pinLng'         => $request->filled('pinLng') ? (float) $request->pinLng : null,
            'pinLabel'       => $request->filled('pinLabel') ? trim($request->pinLabel) : null,
            'notes'          => $request->notes,
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
