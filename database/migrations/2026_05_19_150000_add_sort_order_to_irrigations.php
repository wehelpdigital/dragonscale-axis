<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_irrigations', function (Blueprint $table) {
            $table->integer('sortOrder')->default(0)->after('endDate');
        });

        // Backfill sortOrder so existing rows have a stable initial order.
        // Done per schedule (PHP loop, not SQL ROW_NUMBER) so this works on
        // older MySQL versions without windowing-function support.
        $scheduleIds = DB::table('as_schedule_irrigations')
            ->where('deleteStatus', 1)
            ->select('croppingScheduleId')
            ->distinct()
            ->pluck('croppingScheduleId');

        foreach ($scheduleIds as $sid) {
            $rows = DB::table('as_schedule_irrigations')
                ->where('croppingScheduleId', $sid)
                ->where('deleteStatus', 1)
                ->orderBy('startDay', 'asc')
                ->orderBy('id', 'asc')
                ->pluck('id');
            $idx = 1;
            foreach ($rows as $rowId) {
                DB::table('as_schedule_irrigations')
                    ->where('id', $rowId)
                    ->update(['sortOrder' => $idx++]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('as_schedule_irrigations', function (Blueprint $table) {
            $table->dropColumn('sortOrder');
        });
    }
};
