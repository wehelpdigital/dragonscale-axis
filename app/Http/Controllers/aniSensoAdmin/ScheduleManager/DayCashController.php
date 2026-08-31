<?php

namespace App\Http\Controllers\aniSensoAdmin\ScheduleManager;

use App\Models\AsScheduleActivityVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * The money a day cost and the money a day brought in.
 *
 * The farmer app puts these on the board itself — a day header carries a peso
 * figure and opens a block of "Extra expenses" under it, with the diesel for
 * the pump and the gasoline refill written as separate lines. Thirty-one
 * expenses and twenty-one incomes were sitting in the shared database with no
 * screen on this side that had ever heard of them.
 *
 * They are versioned like everything else on the board: a plan copied to a new
 * version gets its own money, and reading the wrong version's would put a cost
 * on a day that never had it. So everything here is scoped to the version the
 * Activities tab is showing.
 */
class DayCashController extends BaseScheduleController
{
    /** Every expense and income on the season, by date, for the live version. */
    public function data(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $versionId = $this->liveVersion($schedule->id);

        $expenses = DB::table('as_schedule_day_expenses')
            ->where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)
            ->when($versionId, fn ($q) => $q->where('versionId', $versionId))
            ->orderBy('expenseDate')->orderBy('sortOrder')->orderBy('id')
            ->get()
            ->map(fn ($r) => [
                'id' => (int) $r->id,
                'kind' => 'expense',
                'date' => substr((string) $r->expenseDate, 0, 10),
                'amount' => (float) $r->amount,
                'title' => '',
                'note' => (string) ($r->note ?? ''),
            ]);

        $incomes = DB::table('as_schedule_day_incomes')
            ->where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)
            ->when($versionId, fn ($q) => $q->where('versionId', $versionId))
            ->orderBy('incomeDate')->orderBy('sortOrder')->orderBy('id')
            ->get()
            ->map(fn ($r) => [
                'id' => (int) $r->id,
                'kind' => 'income',
                'date' => substr((string) $r->incomeDate, 0, 10),
                'amount' => (float) $r->amount,
                'title' => (string) ($r->title ?? ''),
                'note' => (string) ($r->note ?? ''),
            ]);

        $rows = $expenses->concat($incomes)->sortBy([['date', 'desc'], ['kind', 'asc']])->values();

        return $this->jsonOk('OK', [
            'data' => $rows,
            'versionId' => $versionId,
            'totals' => [
                'expense' => round((float) $expenses->sum('amount'), 2),
                'income' => round((float) $incomes->sum('amount'), 2),
            ],
        ]);
    }

    public function save(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $v = Validator::make($request->all(), [
            'id' => 'nullable|integer',
            'kind' => 'required|in:expense,income',
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0|max:99999999',
            // Only an income carries a name of its own; an expense IS its note.
            'title' => 'nullable|string|max:191',
            'note' => 'nullable|string|max:500',
        ]);
        if ($v->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $v->errors()]);
        }

        $versionId = $this->liveVersion($schedule->id);
        if (! $versionId) {
            return $this->jsonFail('This schedule has no active version to put it on.', 422);
        }

        $income = $request->input('kind') === 'income';
        [$table, $dateCol] = $income
            ? ['as_schedule_day_incomes', 'incomeDate']
            : ['as_schedule_day_expenses', 'expenseDate'];

        $date = substr((string) $request->input('date'), 0, 10);
        $note = trim((string) $request->input('note', ''));
        $payload = [
            $dateCol => $date,
            'amount' => round((float) $request->input('amount'), 2),
            'note' => $note !== '' ? $note : null,
            'updated_at' => now(),
        ];
        if ($income) {
            $title = trim((string) $request->input('title', ''));
            $payload['title'] = $title !== '' ? $title : null;
        }

        $id = (int) $request->input('id', 0);
        if ($id) {
            $hit = DB::table($table)
                ->where('id', $id)
                ->where('croppingScheduleId', $schedule->id)
                ->where('deleteStatus', 1)
                ->update($payload);

            return $hit ? $this->jsonOk('Saved.', ['id' => $id]) : $this->jsonFail('That entry is gone.', 404);
        }

        // New entries go to the end of that day's block, which is the order
        // the farmer app draws them in.
        $next = (int) DB::table($table)
            ->where('croppingScheduleId', $schedule->id)
            ->where('versionId', $versionId)
            ->whereDate($dateCol, $date)
            ->max('sortOrder');

        $id = DB::table($table)->insertGetId($payload + [
            'croppingScheduleId' => $schedule->id,
            'versionId' => $versionId,
            'sortOrder' => $next + 1,
            'deleteStatus' => 1,
            'created_at' => now(),
        ]);

        return $this->jsonOk($income ? 'Income added.' : 'Expense added.', ['id' => $id]);
    }

    public function destroy(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $kind = (string) $request->query('kind');
        if (! in_array($kind, ['expense', 'income'], true)) {
            return $this->jsonFail('Unknown kind.', 422);
        }
        $table = $kind === 'income' ? 'as_schedule_day_incomes' : 'as_schedule_day_expenses';

        $hit = DB::table($table)
            ->where('id', $this->queryId($request))
            ->where('croppingScheduleId', $schedule->id)
            ->update(['deleteStatus' => 0, 'updated_at' => now()]);

        return $hit ? $this->jsonOk('Removed.') : $this->jsonFail('That entry is gone.', 404);
    }

    /** The version the board is showing, which is the one the money belongs to. */
    private function liveVersion(int $scheduleId): ?int
    {
        $active = AsScheduleActivityVersion::active()
            ->forSchedule($scheduleId)
            ->where('isActive', 1)
            ->orderBy('id')
            ->first();

        if ($active) {
            return (int) $active->id;
        }

        // No version is flagged live: the first one is the plan.
        $first = AsScheduleActivityVersion::active()->forSchedule($scheduleId)->orderBy('id')->value('id');

        return $first ? (int) $first : null;
    }
}
