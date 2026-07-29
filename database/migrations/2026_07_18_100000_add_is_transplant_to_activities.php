<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DAT (Days After Transplanting) support for transplanted rice.
 *
 * `isTransplant` marks the activity whose date becomes the lot's DAT 0
 * anchor — the transplanting event. From that date forward, activities on
 * the lot are counted in DAT instead of the schedule's base type (DAS).
 * Parallels the existing `isDayZero` flag, which anchors DAS 0 (sowing).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_activities', function (Blueprint $table) {
            $table->tinyInteger('isTransplant')->default(0)->after('isDayZero');
            $table->index('isTransplant');
        });
    }

    public function down(): void
    {
        Schema::table('as_schedule_activities', function (Blueprint $table) {
            $table->dropIndex(['isTransplant']);
            $table->dropColumn('isTransplant');
        });
    }
};
