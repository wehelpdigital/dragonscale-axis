<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_activities', function (Blueprint $table) {
            if (Schema::hasColumn('as_schedule_activities', 'lotsPerDay')) {
                $table->dropColumn('lotsPerDay');
            }
        });
    }

    public function down(): void
    {
        Schema::table('as_schedule_activities', function (Blueprint $table) {
            $table->integer('lotsPerDay')->default(1)->after('timeRequired');
        });
    }
};
