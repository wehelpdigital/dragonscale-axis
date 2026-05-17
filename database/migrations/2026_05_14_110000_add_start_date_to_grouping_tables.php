<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_default_groupings', function (Blueprint $table) {
            $table->date('startDate')->nullable()->after('staggerDays');
        });

        Schema::table('as_generation_groupings', function (Blueprint $table) {
            $table->date('startDate')->nullable()->after('staggerDays');
        });
    }

    public function down(): void
    {
        Schema::table('as_schedule_default_groupings', function (Blueprint $table) {
            $table->dropColumn('startDate');
        });
        Schema::table('as_generation_groupings', function (Blueprint $table) {
            $table->dropColumn('startDate');
        });
    }
};
