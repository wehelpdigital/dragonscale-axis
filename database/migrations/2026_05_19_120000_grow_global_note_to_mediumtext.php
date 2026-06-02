<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Promote from TEXT (max 65KB) to MEDIUMTEXT (max 16MB). TinyMCE's
        // rich-text markup inflates note sizes quickly — paragraph tags,
        // lists, tables — so long-form protocol commentary easily exceeds
        // the TEXT ceiling and would otherwise silently truncate at the DB.
        // Doctrine's schema builder can't introduce MEDIUMTEXT directly, so
        // raw SQL is used here.
        DB::statement('ALTER TABLE as_schedule_activity_versions MODIFY globalActivityNote MEDIUMTEXT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE as_schedule_activity_versions MODIFY globalActivityNote TEXT NULL');
    }
};
