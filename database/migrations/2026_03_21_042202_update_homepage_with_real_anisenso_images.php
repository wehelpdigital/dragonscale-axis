<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Update homepage tables with real images downloaded from anisenso.test
     */
    public function up(): void
    {
        // Update award section side image with real palay image
        $awardSection = DB::table('as_homepage_sections')->where('sectionKey', 'award')->first();
        if ($awardSection) {
            $settings = json_decode($awardSection->settings, true) ?? [];
            $settings['sideImage'] = '/images/anisenso/homepage/palay-08.jpg';
            DB::table('as_homepage_sections')
                ->where('id', $awardSection->id)
                ->update(['settings' => json_encode($settings)]);
        }

        // Update service icons (IDs 39-42) with real webp icons
        $iconMappings = [
            39 => '/images/icons/icon-1.webp', // Fertilization
            40 => '/images/icons/icon-2.webp', // Biostimulants
            41 => '/images/icons/icon-3.webp', // Soil Restoration
            42 => '/images/icons/icon-4.webp', // Technician Support
        ];

        foreach ($iconMappings as $id => $path) {
            DB::table('as_homepage_items')
                ->where('id', $id)
                ->update(['image' => $path]);
        }

        // Update carousel crop images (IDs 18-20) with real images
        $cropMappings = [
            18 => '/images/anisenso/homepage/banana.png',
            19 => '/images/anisenso/homepage/palm.png',
            20 => '/images/anisenso/homepage/mango.png',
        ];

        foreach ($cropMappings as $id => $path) {
            DB::table('as_homepage_items')
                ->where('id', $id)
                ->update(['image' => $path]);
        }

        // Update comparison items (IDs 50, 51, 52) with real before/after images
        // ID 50 - Root Development
        DB::table('as_homepage_items')
            ->where('id', 50)
            ->update([
                'image' => '/images/anisenso/homepage/roots-item.png',
                'image2' => '/images/anisenso/homepage/growing-rice.png'
            ]);

        // ID 51 - Tiller Count
        DB::table('as_homepage_items')
            ->where('id', 51)
            ->update([
                'image' => '/images/anisenso/homepage/corn-before.png',
                'image2' => '/images/anisenso/homepage/corn-after.png'
            ]);

        // ID 52 - Harvest Results
        DB::table('as_homepage_items')
            ->where('id', 52)
            ->update([
                'image' => '/images/anisenso/homepage/rice-comparison.png',
                'image2' => '/images/anisenso/homepage/more-tillers-min.png'
            ]);

        // Update success story farmer images (IDs 59, 60, 61)
        $farmerMappings = [
            59 => '/images/anisenso/homepage/Anon-Pampanga.png',      // Juan
            60 => '/images/anisenso/homepage/pascual-sagum.png',      // Maria -> Pascual
            61 => '/images/anisenso/homepage/boy-listano.png',        // Pedro -> Boy
        ];

        foreach ($farmerMappings as $id => $path) {
            DB::table('as_homepage_items')
                ->where('id', $id)
                ->update(['image' => $path]);
        }

        // Use hero slide as background image if hero section background is empty/placeholder
        $heroSection = DB::table('as_homepage_sections')->where('sectionKey', 'hero')->first();
        if ($heroSection) {
            $settings = json_decode($heroSection->settings, true) ?? [];
            // Use existing hero slide as background
            $settings['backgroundImage'] = '/images/anisenso/homepage/hero_slide_1772394332_YJFIul3g.jpg';
            DB::table('as_homepage_sections')
                ->where('id', $heroSection->id)
                ->update(['settings' => json_encode($settings)]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot reverse - would need to restore placeholder paths
    }
};
