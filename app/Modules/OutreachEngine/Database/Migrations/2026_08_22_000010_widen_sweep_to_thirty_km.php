<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sweeps start at 30 km, and the floor drops to 0.25 km so they can still get fine.
 *
 * Starting wide is close to a free bet, because the split is adaptive. A 30 km
 * cell that comes back under Google's 60-result ceiling is a single call and
 * that part of the province is finished; one that fills up splits into four,
 * and the 60 places it already returned are kept rather than discarded. The
 * coarse start therefore only costs anything where there is real density.
 *
 * For a niche keyword that is the entire bill: "resort" across La Union is 4
 * cells at 30 km against 24 at 10 km, and neither saturates - the same leads
 * for a sixth of the calls.
 *
 * minGridRadiusKm 0.50 -> 0.25 is the necessary counterpart, not a separate
 * idea. The subdivision floor is halvings from the starting radius, so at the
 * old minimum a 30 km sweep would have stopped at 0.9375 km - coarser than the
 * 0.625 km a 10 km sweep reached, quietly making dense areas worse. At 0.25 km
 * with depth 7 it reaches 0.469 km instead, finer than before.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('outreach_settings')) {
            return;
        }

        DB::statement('ALTER TABLE `outreach_settings` ALTER COLUMN `defaultGridRadiusKm` SET DEFAULT 30.00');
        DB::statement('ALTER TABLE `outreach_settings` ALTER COLUMN `minGridRadiusKm` SET DEFAULT 0.25');
        DB::statement('ALTER TABLE `outreach_settings` ALTER COLUMN `maxSubdivisionDepth` SET DEFAULT 7');

        // Only rows still on the previous defaults move; a deliberately chosen
        // value is left alone.
        DB::table('outreach_settings')
            ->where('defaultGridRadiusKm', 10.00)
            ->update(['defaultGridRadiusKm' => 30.00]);

        DB::table('outreach_settings')
            ->where('minGridRadiusKm', 0.50)
            ->update(['minGridRadiusKm' => 0.25]);

        DB::table('outreach_settings')
            ->where('maxSubdivisionDepth', 6)
            ->update(['maxSubdivisionDepth' => 7]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('outreach_settings')) {
            return;
        }

        DB::statement('ALTER TABLE `outreach_settings` ALTER COLUMN `defaultGridRadiusKm` SET DEFAULT 10.00');
        DB::statement('ALTER TABLE `outreach_settings` ALTER COLUMN `minGridRadiusKm` SET DEFAULT 0.50');
        DB::statement('ALTER TABLE `outreach_settings` ALTER COLUMN `maxSubdivisionDepth` SET DEFAULT 6');

        DB::table('outreach_settings')
            ->where('defaultGridRadiusKm', 30.00)
            ->update(['defaultGridRadiusKm' => 10.00]);

        DB::table('outreach_settings')
            ->where('minGridRadiusKm', 0.25)
            ->update(['minGridRadiusKm' => 0.50]);

        DB::table('outreach_settings')
            ->where('maxSubdivisionDepth', 7)
            ->update(['maxSubdivisionDepth' => 6]);
    }
};
