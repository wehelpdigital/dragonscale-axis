<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Update homepage tables to use local placeholder images instead of external URLs.
     */
    public function up(): void
    {
        // Update hero section background image
        $heroSection = DB::table('as_homepage_sections')->where('sectionKey', 'hero')->first();
        if ($heroSection) {
            $settings = json_decode($heroSection->settings, true) ?? [];
            $settings['backgroundImage'] = '/images/anisenso/homepage/placeholder-hero-bg.svg';
            DB::table('as_homepage_sections')
                ->where('id', $heroSection->id)
                ->update(['settings' => json_encode($settings)]);
        }

        // Update award section side image
        $awardSection = DB::table('as_homepage_sections')->where('sectionKey', 'award')->first();
        if ($awardSection) {
            $settings = json_decode($awardSection->settings, true) ?? [];
            $settings['sideImage'] = '/images/anisenso/homepage/placeholder-palay.svg';
            DB::table('as_homepage_sections')
                ->where('id', $awardSection->id)
                ->update(['settings' => json_encode($settings)]);
        }

        // Update partner logos (IDs 43-48) with local placeholder
        DB::table('as_homepage_items')
            ->whereIn('id', [43, 44, 45, 46, 47, 48])
            ->where('itemType', 'logo')
            ->update(['image' => '/images/anisenso/homepage/placeholder-partner.svg']);

        // Update comparison items (IDs 50, 51, 52) with local before/after placeholders
        DB::table('as_homepage_items')
            ->whereIn('id', [50, 51, 52])
            ->where('itemType', 'comparison')
            ->update([
                'image' => '/images/anisenso/homepage/placeholder-before.svg',
                'image2' => '/images/anisenso/homepage/placeholder-after.svg'
            ]);

        // Update success story farmer images (IDs 59, 60, 61)
        DB::table('as_homepage_items')
            ->whereIn('id', [59, 60, 61])
            ->where('itemType', 'story')
            ->update(['image' => '/images/anisenso/homepage/placeholder-farmer.svg']);

        // Update carousel items (IDs 18, 19, 20) - seasonal crops
        // Create simple crop placeholder SVGs
        $cropPlaceholders = [
            18 => '/images/anisenso/homepage/placeholder-crop-banana.svg',
            19 => '/images/anisenso/homepage/placeholder-crop-palm.svg',
            20 => '/images/anisenso/homepage/placeholder-crop-mango.svg',
        ];

        foreach ($cropPlaceholders as $id => $path) {
            DB::table('as_homepage_items')
                ->where('id', $id)
                ->update(['image' => $path]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot reverse - original external URLs should not be restored
    }
};
