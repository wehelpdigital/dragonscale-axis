<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Settles the starting cell size at 10 km.
 *
 * 20 km was the cheapest option measured, but it is coarse enough that a first
 * sweep of a small province is a handful of cells and every one of them comes
 * back saturated, so the useful work all happens in the split. 10 km costs
 * roughly three times a 20 km sweep and a third of a 5 km one - for a typical
 * province, single dollars either way - while starting close enough to real
 * business density that most cells resolve without splitting at all.
 *
 * Precision is not what is being traded here. Every starting radius between 5
 * and 20 km bottoms out at the same 0.625 km, because the floor is set by
 * halvings against minGridRadiusKm (0.5) rather than by the starting number.
 * maxSubdivisionDepth stays at 6, which 10 km reaches its floor well inside.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('outreach_settings')) {
            return;
        }

        DB::statement('ALTER TABLE `outreach_settings` ALTER COLUMN `defaultGridRadiusKm` SET DEFAULT 10.00');

        // Only rows still carrying the previous default move. Anyone who has
        // deliberately chosen a radius keeps it.
        DB::table('outreach_settings')
            ->where('defaultGridRadiusKm', 20.00)
            ->update(['defaultGridRadiusKm' => 10.00]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('outreach_settings')) {
            return;
        }

        DB::statement('ALTER TABLE `outreach_settings` ALTER COLUMN `defaultGridRadiusKm` SET DEFAULT 20.00');

        DB::table('outreach_settings')
            ->where('defaultGridRadiusKm', 10.00)
            ->update(['defaultGridRadiusKm' => 20.00]);
    }
};
