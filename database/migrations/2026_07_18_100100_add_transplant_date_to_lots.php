<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Manual DAT 0 (transplant) anchor per lot — the twin of `dayZeroDate`
 * (DAS 0 / sowing). Lets a lot carry its transplanting date directly, for
 * schedules where transplanting isn't captured as an explicit activity.
 * An activity flagged `isTransplant` still overrides this (earliest wins),
 * exactly as an `isDayZero` activity overrides `dayZeroDate`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_lots', function (Blueprint $table) {
            $table->date('transplantDate')->nullable()->after('dayZeroDate');
        });
    }

    public function down(): void
    {
        Schema::table('as_schedule_lots', function (Blueprint $table) {
            $table->dropColumn('transplantDate');
        });
    }
};
