<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Expand the enum to include 'n/a' (MySQL ENUM alteration via raw SQL).
        DB::statement("ALTER TABLE as_schedule_activities MODIFY COLUMN timeRequired ENUM('half','whole','n/a') NOT NULL DEFAULT 'half'");
    }

    public function down(): void
    {
        // Roll back to the original two-value enum. Any rows currently 'n/a'
        // are normalized to 'half' so the alter doesn't fail.
        DB::table('as_schedule_activities')->where('timeRequired', 'n/a')->update(['timeRequired' => 'half']);
        DB::statement("ALTER TABLE as_schedule_activities MODIFY COLUMN timeRequired ENUM('half','whole') NOT NULL DEFAULT 'half'");
    }
};
