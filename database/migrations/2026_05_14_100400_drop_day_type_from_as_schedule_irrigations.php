<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_irrigations', function (Blueprint $table) {
            if (Schema::hasColumn('as_schedule_irrigations', 'dayType')) {
                $table->dropColumn('dayType');
            }
        });
    }

    public function down(): void
    {
        Schema::table('as_schedule_irrigations', function (Blueprint $table) {
            $table->enum('dayType', ['DAP', 'DAS', 'DAT'])->default('DAS')->after('description');
        });
    }
};
