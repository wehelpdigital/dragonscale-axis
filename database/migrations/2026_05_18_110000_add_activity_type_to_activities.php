<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_activities', function (Blueprint $table) {
            // String (not enum) so the catalog can evolve without DB migrations.
            // App-level validation in ActivityController enforces the allow-list.
            $table->string('activityType', 32)->nullable()->after('priority');
            $table->index('activityType');
        });
    }

    public function down(): void
    {
        Schema::table('as_schedule_activities', function (Blueprint $table) {
            $table->dropIndex(['activityType']);
            $table->dropColumn('activityType');
        });
    }
};
