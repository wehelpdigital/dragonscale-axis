<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_irrigations', function (Blueprint $table) {
            // String (not enum) so the catalog can evolve without DB migrations.
            // App-level validation in IrrigationController enforces the allow-list.
            $table->string('taskType', 32)->default('irrigate')->after('endDay');
            $table->index('taskType');
        });
    }

    public function down(): void
    {
        Schema::table('as_schedule_irrigations', function (Blueprint $table) {
            $table->dropIndex(['taskType']);
            $table->dropColumn('taskType');
        });
    }
};
