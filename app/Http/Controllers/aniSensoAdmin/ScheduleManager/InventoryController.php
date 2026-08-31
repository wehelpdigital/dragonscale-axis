<?php

namespace App\Http\Controllers\aniSensoAdmin\ScheduleManager;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * The client's Inventory module, from this side.
 *
 * What the farm holds and what it cost. There is no stock column anywhere:
 * on hand is the sum of the moves, because a stored total and a log of moves
 * are two answers to one question and the day they disagree the stock figure
 * is quietly wrong. So this reads the same sum the farmer app reads and, when
 * it writes, it writes a move — never a total.
 *
 * A move made from here is not signed by a client user, because no client
 * user made it. `byUserId` is left empty rather than borrowed.
 */
class InventoryController extends BaseScheduleController
{
    /** The same ten kinds the farmer app offers, with the units each holds. */
    public const KINDS = [
        'granular' => ['label' => 'Granular fertiliser', 'icon' => '🧂'],
        'foliar' => ['label' => 'Foliar / liquid feed', 'icon' => '🧪'],
        'pesticide' => ['label' => 'Pesticide', 'icon' => '🐛'],
        'herbicide' => ['label' => 'Herbicide', 'icon' => '🌿'],
        'fungicide' => ['label' => 'Fungicide', 'icon' => '🍄'],
        'molluscicide' => ['label' => 'Molluscicide', 'icon' => '🐌'],
        'seed' => ['label' => 'Seed', 'icon' => '🌱'],
        'fuel' => ['label' => 'Fuel', 'icon' => '⛽'],
        'tool' => ['label' => 'Tool / supply', 'icon' => '🧰'],
        'other' => ['label' => 'Other', 'icon' => '📦'],
    ];

    public const UNITS = ['kg', 'g', 'L', 'ml', 'piece'];

    /** Why the stock moved. 'activity' is written by the app, never by hand. */
    public const REASONS = [
        'open' => 'Opening stock',
        'in' => 'Stock added',
        'out' => 'Used',
        'activity' => 'Used by an activity',
        'adjust' => 'Correction',
    ];

    /** The shelf, with what is on hand and the last of the movements. */
    public function data(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        // One grouped query rather than one per item: the whole shelf is
        // drawn at once and the remote database is a long way away.
        $onHand = DB::table('as_inventory_moves')
            ->where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)
            ->groupBy('itemId')
            ->selectRaw('itemId, SUM(delta) as total')
            ->pluck('total', 'itemId');

        $items = DB::table('as_inventory_items')
            ->where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)
            ->orderBy('name')
            ->get()
            ->map(function ($i) use ($onHand) {
                $have = (float) ($onHand[$i->id] ?? 0);

                return [
                    'id' => (int) $i->id,
                    'name' => (string) $i->name,
                    'kind' => (string) $i->kind,
                    'kindLabel' => self::KINDS[$i->kind]['label'] ?? 'Other',
                    'icon' => self::KINDS[$i->kind]['icon'] ?? '📦',
                    'unit' => (string) $i->unit,
                    'packSize' => $i->packSize !== null ? (float) $i->packSize : null,
                    'packLabel' => (string) ($i->packLabel ?? ''),
                    'lowAt' => $i->lowAt !== null ? (float) $i->lowAt : null,
                    'note' => (string) ($i->note ?? ''),
                    'onHand' => $have,
                    'isLow' => $i->lowAt !== null && $have <= (float) $i->lowAt,
                ];
            })->values();

        $moves = DB::table('as_inventory_moves as m')
            ->leftJoin('as_inventory_items as i', 'i.id', '=', 'm.itemId')
            ->where('m.croppingScheduleId', $schedule->id)
            ->where('m.deleteStatus', 1)
            ->orderByDesc('m.id')
            ->limit(200)
            ->get(['m.*', 'i.name as itemName', 'i.unit as itemUnit'])
            ->map(fn ($m) => [
                'id' => (int) $m->id,
                'itemId' => (int) $m->itemId,
                'itemName' => (string) ($m->itemName ?? 'Removed item'),
                'unit' => (string) ($m->itemUnit ?? ''),
                'delta' => (float) $m->delta,
                'qtyAfter' => $m->qtyAfter !== null ? (float) $m->qtyAfter : null,
                'reason' => (string) $m->reason,
                'reasonLabel' => self::REASONS[$m->reason] ?? 'Change',
                'fromActivity' => $m->reason === 'activity',
                'happenedOn' => $m->happenedOn ? substr((string) $m->happenedOn, 0, 10) : null,
                'note' => (string) ($m->note ?? ''),
            ])->values();

        return $this->jsonOk('OK', [
            'items' => $items,
            'moves' => $moves,
            'kinds' => self::KINDS,
            'units' => self::UNITS,
        ]);
    }

    public function itemSave(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $v = Validator::make($request->all(), [
            'name' => 'required|string|max:191',
            'kind' => ['required', Rule::in(array_keys(self::KINDS))],
            'unit' => ['required', Rule::in(self::UNITS)],
            'packSize' => 'nullable|numeric|min:0|max:9999999',
            'packLabel' => 'nullable|string|max:60',
            'lowAt' => 'nullable|numeric|min:0|max:9999999',
            'note' => 'nullable|string|max:500',
        ]);
        if ($v->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $v->errors()]);
        }

        $d = $v->validated();
        $payload = [
            'name' => $d['name'],
            'kind' => $d['kind'],
            'unit' => $d['unit'],
            'packSize' => $d['packSize'] ?? null,
            'packLabel' => $d['packLabel'] ?? null,
            'lowAt' => $d['lowAt'] ?? null,
            'note' => $d['note'] ?? null,
            'updated_at' => now(),
        ];

        $id = (int) $request->input('id', 0);
        if ($id) {
            $hit = DB::table('as_inventory_items')
                ->where('id', $id)->where('croppingScheduleId', $schedule->id)->where('deleteStatus', 1)
                ->update($payload);

            return $hit ? $this->jsonOk('Item saved.', ['id' => $id]) : $this->jsonFail('That item is gone.', 404);
        }

        $id = DB::table('as_inventory_items')->insertGetId($payload + [
            'croppingScheduleId' => $schedule->id,
            'deleteStatus' => 1,
            'created_at' => now(),
        ]);

        // An opening count, if one was given with the item. It is a move like
        // any other, so the log starts where the shed does.
        $opening = (float) $request->input('opening', 0);
        if (abs($opening) >= 0.0005) {
            $this->writeMove($schedule->id, $id, $opening, 'open', null, 'Opening stock');
        }

        return $this->jsonOk('Item added.', ['id' => $id]);
    }

    public function itemDestroy(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $hit = DB::table('as_inventory_items')
            ->where('id', $this->queryId($request))
            ->where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)
            ->update(['deleteStatus' => 0, 'updated_at' => now()]);

        return $hit ? $this->jsonOk('Removed from the inventory.') : $this->jsonFail('That item is gone.', 404);
    }

    /**
     * Move stock by hand — a delivery, a use, an opening count, a correction.
     *
     * One endpoint for all four because they are one act with a sign on it,
     * and four would be four places that each have to remember to write down
     * what the stock was before.
     */
    public function move(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $v = Validator::make($request->all(), [
            'itemId' => 'required|integer',
            'qty' => 'required|numeric|min:0.001|max:9999999',
            'direction' => 'required|in:in,out',
            'reason' => 'nullable|in:open,in,out,adjust',
            'on' => 'nullable|date',
            'note' => 'nullable|string|max:500',
        ]);
        if ($v->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $v->errors()]);
        }

        $item = DB::table('as_inventory_items')
            ->where('id', (int) $request->input('itemId'))
            ->where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)
            ->first();
        if (! $item) {
            return $this->jsonFail('That item is not on this season.', 404);
        }

        $in = $request->input('direction') === 'in';
        $qty = abs((float) $request->input('qty'));
        $reason = $request->input('reason') ?: ($in ? 'in' : 'out');

        $after = $this->writeMove(
            $schedule->id,
            (int) $item->id,
            $in ? $qty : -$qty,
            $reason,
            $request->input('on'),
            $request->input('note')
        );

        return $this->jsonOk(
            ($in ? 'Added to ' : 'Taken from ') . $item->name . ' — ' . rtrim(rtrim(number_format($after, 3), '0'), '.') . ' ' . $item->unit . ' left.'
        );
    }

    /** Undo one hand-typed move. Activity moves are undone by unticking. */
    public function moveDestroy(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $move = DB::table('as_inventory_moves')
            ->where('id', $this->queryId($request))
            ->where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)
            ->first();
        if (! $move) {
            return $this->jsonFail('That entry is gone already.', 404);
        }
        if ($move->reason === 'activity') {
            return $this->jsonFail(
                'This one came from an activity being marked done. Untick the activity to take it back.',
                422
            );
        }

        DB::table('as_inventory_moves')->where('id', $move->id)
            ->update(['deleteStatus' => 0, 'updated_at' => now()]);

        return $this->jsonOk('Entry removed.');
    }

    /**
     * Write one move and what the stock was either side of it.
     *
     * The read happens inside the transaction with the item's rows locked, so
     * two people writing at the same moment cannot both read the same
     * "before" and each claim the stock went from 84 to 72.
     *
     * @return float what is on hand afterwards
     */
    private function writeMove(int $scheduleId, int $itemId, float $delta, string $reason, ?string $on, ?string $note): float
    {
        return DB::transaction(function () use ($scheduleId, $itemId, $delta, $reason, $on, $note) {
            $before = (float) DB::table('as_inventory_moves')
                ->where('itemId', $itemId)
                ->where('deleteStatus', 1)
                ->lockForUpdate()
                ->sum('delta');

            DB::table('as_inventory_moves')->insert([
                'croppingScheduleId' => $scheduleId,
                'itemId' => $itemId,
                'delta' => $delta,
                'qtyBefore' => $before,
                'qtyAfter' => $before + $delta,
                'reason' => $reason,
                'activityId' => null,
                'happenedOn' => $on ?: now('Asia/Manila')->toDateString(),
                'note' => $note,
                // Nobody in the client's app did this, so nobody in the
                // client's app is named for it.
                'byUserId' => null,
                'deleteStatus' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $before + $delta;
        });
    }
}
