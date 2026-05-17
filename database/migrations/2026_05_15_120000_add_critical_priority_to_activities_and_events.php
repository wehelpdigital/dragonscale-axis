<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE as_schedule_activities MODIFY COLUMN priority ENUM('critical','high','medium','low') NOT NULL DEFAULT 'medium'");
        DB::statement("ALTER TABLE as_schedule_calendar_events MODIFY COLUMN priority ENUM('critical','high','medium','low') NOT NULL DEFAULT 'medium'");
    }

    public function down(): void
    {
        // Collapse any 'critical' rows back to 'high' so the narrowed enum accepts them.
        DB::statement("UPDATE as_schedule_activities SET priority = 'high' WHERE priority = 'critical'");
        DB::statement("UPDATE as_schedule_calendar_events SET priority = 'high' WHERE priority = 'critical'");
        DB::statement("ALTER TABLE as_schedule_activities MODIFY COLUMN priority ENUM('high','medium','low') NOT NULL DEFAULT 'medium'");
        DB::statement("ALTER TABLE as_schedule_calendar_events MODIFY COLUMN priority ENUM('high','medium','low') NOT NULL DEFAULT 'medium'");
    }
};
