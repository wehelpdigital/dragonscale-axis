<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_activity_versions', function (Blueprint $table) {
            // One free-form note per version that renders above the whole
            // activity timeline on the setup screen, the worker presentation,
            // and the export schedule. Distinct from `description` (admin-only
            // "why I created this branch" metadata) — globalActivityNote is
            // *output content* shown to the worker / client audience.
            $table->text('globalActivityNote')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('as_schedule_activity_versions', function (Blueprint $table) {
            $table->dropColumn('globalActivityNote');
        });
    }
};
