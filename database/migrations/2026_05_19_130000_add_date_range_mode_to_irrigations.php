<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_irrigations', function (Blueprint $table) {
            // 'das' = relative day numbers (existing behavior — startDay /
            // endDay are integer offsets from each group's Day 0 anchor).
            // 'date' = absolute calendar dates (startDate / endDate are the
            // canonical source; startDay/endDay stay 0 and are ignored for
            // band rendering).
            $table->string('dayMode', 8)->default('das')->after('endDay');
            $table->date('startDate')->nullable()->after('dayMode');
            $table->date('endDate')->nullable()->after('startDate');
        });
    }

    public function down(): void
    {
        Schema::table('as_schedule_irrigations', function (Blueprint $table) {
            $table->dropColumn(['dayMode', 'startDate', 'endDate']);
        });
    }
};
