<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_activities', function (Blueprint $table) {
            $table->integer('versionId')->nullable()->after('croppingScheduleId');
            // Tracks lineage when an activity is cloned into a new version, so
            // a future diff view can match "the same logical activity" across
            // branches. Null on every original (un-forked) row.
            $table->integer('sourceActivityId')->nullable()->after('versionId');
            $table->index('versionId');
            $table->index('sourceActivityId');
        });

        // Backfill: every existing activity → its schedule's Original version.
        // The first migration in this pair already inserted one Original row
        // per schedule, so this UPDATE just wires the FK.
        DB::statement("
            UPDATE as_schedule_activities a
            INNER JOIN as_schedule_activity_versions v
                ON v.croppingScheduleId = a.croppingScheduleId
                AND v.isOriginal = 1
                AND v.deleteStatus = 1
            SET a.versionId = v.id
            WHERE a.versionId IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('as_schedule_activities', function (Blueprint $table) {
            $table->dropIndex(['versionId']);
            $table->dropIndex(['sourceActivityId']);
            $table->dropColumn(['versionId', 'sourceActivityId']);
        });
    }
};
