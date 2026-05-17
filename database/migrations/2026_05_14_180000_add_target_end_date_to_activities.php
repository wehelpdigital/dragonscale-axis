<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_activities', function (Blueprint $table) {
            $table->date('targetEndDate')->nullable()->after('targetDate');
            $table->index('targetEndDate');
        });
    }

    public function down(): void
    {
        Schema::table('as_schedule_activities', function (Blueprint $table) {
            $table->dropIndex(['targetEndDate']);
            $table->dropColumn('targetEndDate');
        });
    }
};
