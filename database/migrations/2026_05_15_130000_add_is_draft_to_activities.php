<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_activities', function (Blueprint $table) {
            $table->tinyInteger('isDraft')->default(0)->after('isDayZero');
            $table->index('isDraft');
        });
    }

    public function down(): void
    {
        Schema::table('as_schedule_activities', function (Blueprint $table) {
            $table->dropIndex(['isDraft']);
            $table->dropColumn('isDraft');
        });
    }
};
