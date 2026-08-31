<?php

namespace App\Http\Controllers\aniSensoAdmin\ScheduleManager;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The reports the CLIENT saved, as against the ones this app computes.
 *
 * The farmer app's Reports module holds two: a labor report, which this page
 * has had its own screen for since the beginning, and a post-harvest report —
 * a saved copy of what the season yielded against what it cost. Those copies
 * are rows they made on purpose.
 *
 * Nothing here recomputes them. A saved report is a record of a moment, and
 * doing the arithmetic again on today's numbers would quietly change what
 * they saw when they pressed save.
 *
 * Named for what it holds rather than for the module, because ReportController
 * next door is this app's own generation-costing screen and two files called
 * the same thing is how one of them gets edited by mistake.
 */
class SavedReportController extends BaseScheduleController
{
    public function data(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $rows = DB::table('as_schedule_revenue_reports')
            ->where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn ($r) => [
                'id' => (int) $r->id,
                'title' => (string) ($r->title ?: 'Untitled report'),
                'yieldAmount' => $r->yieldAmount !== null ? (float) $r->yieldAmount : null,
                'yieldUnit' => (string) ($r->yieldUnit ?? ''),
                'pricePerUnit' => $r->pricePerUnit !== null ? (float) $r->pricePerUnit : null,
                'grossRevenue' => (float) ($r->grossRevenue ?? 0),
                'materialsCost' => (float) ($r->materialsCost ?? 0),
                'servicesCost' => (float) ($r->servicesCost ?? 0),
                'laborCost' => (float) ($r->laborCost ?? 0),
                'expensesCost' => (float) ($r->expensesCost ?? 0),
                'totalCost' => (float) ($r->totalCost ?? 0),
                'netProfit' => (float) ($r->netProfit ?? 0),
                'notes' => (string) ($r->notes ?? ''),
                'when' => (string) ($r->created_at ?? ''),
            ])->values();

        return $this->jsonOk('OK', ['data' => $rows]);
    }

    public function destroy(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $hit = DB::table('as_schedule_revenue_reports')
            ->where('id', $this->queryId($request))
            ->where('croppingScheduleId', $schedule->id)
            ->update(['deleteStatus' => 0, 'updated_at' => now()]);

        return $hit ? $this->jsonOk('Report removed.') : $this->jsonFail('That report is gone.', 404);
    }
}
