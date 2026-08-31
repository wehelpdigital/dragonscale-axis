<?php

namespace App\Http\Controllers\aniSensoAdmin\ScheduleManager;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * The client's Post-harvest module, from this side.
 *
 * What came off the field and what happened to it: the yield, the moisture,
 * who bought it and for how much, and the lessons for next season. The
 * reading of it already lived in ScheduleInsightController because the header
 * strip needed a total; this is the writing.
 *
 * Each kind of observation asks for a few extra answers of its own — a pest's
 * severity, how long a typhoon sat over the field — and those live in a JSON
 * column driven by a table of questions that belongs to the farmer app. They
 * are left exactly as the client wrote them: this side edits the columns
 * every category shares, and never quietly drops the rest.
 */
class PostHarvestController extends BaseScheduleController
{
    /** The same eight the farmer app offers, in the same order. */
    public const CATEGORIES = [
        'yield' => 'Yield',
        'quality' => 'Grain / produce quality',
        'pest' => 'Pest & disease outcome',
        'weather' => 'Weather impact',
        'storage' => 'Drying & storage',
        'market' => 'Selling & price',
        'lesson' => 'Lesson for next season',
        'other' => 'Other observation',
    ];

    public function save(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $lotIds = DB::table('as_schedule_lots')
            ->where('croppingScheduleId', $schedule->id)->where('deleteStatus', 1)
            ->pluck('id')->all();

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:191',
            'category' => ['required', Rule::in(array_keys(self::CATEGORIES))],
            'observationDate' => 'nullable|date',
            'lotId' => ['nullable', Rule::in($lotIds)],
            'yieldAmount' => 'nullable|numeric|min:0|max:99999999',
            'yieldUnit' => 'nullable|string|max:24',
            'moisturePercent' => 'nullable|numeric|min:0|max:100',
            'pricePerUnit' => 'nullable|numeric|min:0|max:99999999',
            'buyer' => 'nullable|string|max:191',
            'notes' => 'nullable|string|max:20000',
        ], [
            'lotId.in' => 'That lot does not belong to this schedule.',
        ]);

        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $d = $validator->validated();
        $payload = [
            'title' => $d['title'],
            'category' => $d['category'],
            'observationDate' => $d['observationDate'] ?? null,
            'lotId' => $d['lotId'] ?? null,
            'yieldAmount' => $d['yieldAmount'] ?? null,
            'yieldUnit' => $d['yieldUnit'] ?? null,
            'moisturePercent' => $d['moisturePercent'] ?? null,
            'pricePerUnit' => $d['pricePerUnit'] ?? null,
            'buyer' => $d['buyer'] ?? null,
            'notes' => $d['notes'] ?? null,
            'updated_at' => now(),
        ];

        $id = (int) $request->input('id', 0);
        if ($id) {
            $hit = DB::table('as_schedule_post_harvests')
                ->where('id', $id)
                ->where('croppingScheduleId', $schedule->id)
                ->where('deleteStatus', 1)
                ->update($payload);

            return $hit ? $this->jsonOk('Record saved.', ['id' => $id]) : $this->jsonFail('That record is gone.', 404);
        }

        $id = DB::table('as_schedule_post_harvests')->insertGetId($payload + [
            'croppingScheduleId' => $schedule->id,
            'sortOrder' => 0,
            'deleteStatus' => 1,
            'created_at' => now(),
        ]);

        return $this->jsonOk('Record added.', ['id' => $id]);
    }

    /** One record, with the answers the columns hold. */
    public function show(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $r = DB::table('as_schedule_post_harvests')
            ->where('id', $this->queryId($request))
            ->where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)
            ->first();

        if (! $r) {
            return $this->jsonFail('That record is gone.', 404);
        }

        return $this->jsonOk('OK', ['data' => [
            'id' => (int) $r->id,
            'title' => (string) $r->title,
            'category' => (string) $r->category,
            'observationDate' => $r->observationDate ? substr((string) $r->observationDate, 0, 10) : null,
            'lotId' => $r->lotId ? (int) $r->lotId : 0,
            'yieldAmount' => $r->yieldAmount,
            'yieldUnit' => $r->yieldUnit,
            'moisturePercent' => $r->moisturePercent,
            'pricePerUnit' => $r->pricePerUnit,
            'buyer' => $r->buyer,
            'notes' => (string) ($r->notes ?? ''),
        ]]);
    }
}
