<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Starts sweeps at 20 km cells instead of 5 km, and deepens the split limit to match.
 *
 * Google caps a Nearby Search at 60 results per call, not per square kilometre,
 * so cell size only ever mattered for two things: how many calls an empty area
 * costs, and how far a crowded one can be refined.
 *
 * At 5 km a sparse keyword paid for hundreds of near-empty searches - Pangasinan
 * tiles into 245 cells at 5 km against 27 at 20 km - and Palawan tiled into 3,007,
 * past GeoGridService::MAX_GRID_CELLS, so the far end of the province was never
 * queued at all. A crowded cell saturates at either size and the adaptive split
 * walks it back down, keeping every lead found on the way, so starting coarse
 * costs a dense sweep very little.
 *
 * maxSubdivisionDepth rises 4 -> 6 because the floor is measured in halvings from
 * the starting radius, not in kilometres: from 20 km, four splits only reach
 * 1.25 km, which is coarser than the 0.625 km a 5 km start used to manage. Six
 * splits reach the same 0.625 km floor that minGridRadiusKm (0.5) permits, so
 * city centres are refined at least as finely as before rather than less.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('outreach_settings')) {
            return;
        }

        // Raw ALTER rather than ->change(): doctrine/dbal is not installed, and a
        // default is all that is being touched - no type or nullability change.
        DB::statement('ALTER TABLE `outreach_settings` ALTER COLUMN `defaultGridRadiusKm` SET DEFAULT 20.00');
        DB::statement('ALTER TABLE `outreach_settings` ALTER COLUMN `maxSubdivisionDepth` SET DEFAULT 6');

        // Move rows still sitting on the old defaults. A row whose owner has
        // deliberately chosen some other value is left alone - this is a change
        // of default, not an override of anyone's setting.
        DB::table('outreach_settings')
            ->where('defaultGridRadiusKm', 5.00)
            ->update(['defaultGridRadiusKm' => 20.00]);

        DB::table('outreach_settings')
            ->where('maxSubdivisionDepth', 4)
            ->update(['maxSubdivisionDepth' => 6]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('outreach_settings')) {
            return;
        }

        DB::statement('ALTER TABLE `outreach_settings` ALTER COLUMN `defaultGridRadiusKm` SET DEFAULT 5.00');
        DB::statement('ALTER TABLE `outreach_settings` ALTER COLUMN `maxSubdivisionDepth` SET DEFAULT 4');

        DB::table('outreach_settings')
            ->where('defaultGridRadiusKm', 20.00)
            ->update(['defaultGridRadiusKm' => 5.00]);

        DB::table('outreach_settings')
            ->where('maxSubdivisionDepth', 6)
            ->update(['maxSubdivisionDepth' => 4]);
    }
};
