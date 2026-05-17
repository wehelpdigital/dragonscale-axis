<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_cropping_schedules', function (Blueprint $table) {
            $table->enum('dayType', ['DAP', 'DAS', 'DAT'])->default('DAS')->after('description');
            $table->integer('defaultStaggerDays')->default(0)->after('dayType');
        });

        // Backfill schedule.dayType from the most common per-activity dayType, if any exists.
        if (Schema::hasColumn('as_schedule_activities', 'activityType')) {
            $rows = DB::table('as_schedule_activities')
                ->select('croppingScheduleId', 'activityType', DB::raw('COUNT(*) as c'))
                ->where('deleteStatus', 1)
                ->groupBy('croppingScheduleId', 'activityType')
                ->orderBy('croppingScheduleId')
                ->orderByDesc('c')
                ->get();

            $seen = [];
            foreach ($rows as $r) {
                if (isset($seen[$r->croppingScheduleId])) continue;
                $seen[$r->croppingScheduleId] = true;
                DB::table('as_cropping_schedules')
                    ->where('id', $r->croppingScheduleId)
                    ->update(['dayType' => $r->activityType]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('as_cropping_schedules', function (Blueprint $table) {
            $table->dropColumn(['dayType', 'defaultStaggerDays']);
        });
    }
};
