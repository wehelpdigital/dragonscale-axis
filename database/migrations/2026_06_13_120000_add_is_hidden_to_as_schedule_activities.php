<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_activities', function (Blueprint $table) {
            // Per-activity visibility flag. Distinct from isDraft (which
            // archives the activity entirely): isHidden keeps the row in
            // the setup timeline (dimmed) but excludes it from worker
            // presentation, card viewer, and export. Use case: skip an
            // optional activity for this print run without unscheduling it.
            $table->boolean('isHidden')->default(false)->after('isDraft');
        });
    }

    public function down(): void
    {
        Schema::table('as_schedule_activities', function (Blueprint $table) {
            $table->dropColumn('isHidden');
        });
    }
};
