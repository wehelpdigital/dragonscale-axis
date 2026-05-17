<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_activities', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('as_schedule_activities', 'windowBeforeDays')) $cols[] = 'windowBeforeDays';
            if (Schema::hasColumn('as_schedule_activities', 'windowAfterDays'))  $cols[] = 'windowAfterDays';
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }

    public function down(): void
    {
        Schema::table('as_schedule_activities', function (Blueprint $table) {
            $table->integer('windowBeforeDays')->default(0)->after('targetDate');
            $table->integer('windowAfterDays')->default(0)->after('windowBeforeDays');
        });
    }
};
