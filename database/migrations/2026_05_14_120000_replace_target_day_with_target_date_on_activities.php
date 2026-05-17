<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_activities', function (Blueprint $table) {
            $table->date('targetDate')->nullable()->after('activityTitle');
        });

        // Drop targetDay (any existing data is lost — feature is in dev)
        Schema::table('as_schedule_activities', function (Blueprint $table) {
            if (Schema::hasColumn('as_schedule_activities', 'targetDay')) {
                // Was previously indexed on targetDay only after the prior migration.
                try { $table->dropIndex(['targetDay']); } catch (\Throwable $e) {}
                $table->dropColumn('targetDay');
            }
        });

        Schema::table('as_schedule_activities', function (Blueprint $table) {
            $table->index('targetDate');
        });
    }

    public function down(): void
    {
        Schema::table('as_schedule_activities', function (Blueprint $table) {
            $table->dropIndex(['targetDate']);
            $table->dropColumn('targetDate');
            $table->integer('targetDay')->default(0)->after('activityTitle');
            $table->index('targetDay');
        });
    }
};
