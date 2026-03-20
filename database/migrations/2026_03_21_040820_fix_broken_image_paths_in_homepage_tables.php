<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix broken image paths in homepage tables.
     * - Updates icon paths from .png to .svg
     * - Removes broken wp-content paths
     * - Sets placeholder URLs to null (they're external and unreliable)
     */
    public function up(): void
    {
        // Fix service icons: change from .png to .svg
        $iconMappings = [
            '/images/icons/fertilizer.png' => '/images/icons/fertilizer.svg',
            '/images/icons/biostimulant.png' => '/images/icons/biostimulant.svg',
            '/images/icons/soil-restoration.png' => '/images/icons/soil-restoration.svg',
            '/images/icons/technician-support.png' => '/images/icons/technician-support.svg',
        ];

        foreach ($iconMappings as $oldPath => $newPath) {
            DB::table('as_homepage_items')
                ->where('image', $oldPath)
                ->update(['image' => $newPath]);
        }

        // Remove broken wp-content paths (set to null)
        DB::table('as_homepage_items')
            ->where('image', 'like', '%/wp-content/%')
            ->update(['image' => null]);

        DB::table('as_homepage_items')
            ->where('image2', 'like', '%/wp-content/%')
            ->update(['image2' => null]);

        // Fix as_homepage_sections settings - remove broken wp-content paths
        $sections = DB::table('as_homepage_sections')->whereNotNull('settings')->get();

        foreach ($sections as $section) {
            $settings = json_decode($section->settings, true);
            if (!$settings) continue;

            $modified = false;
            foreach ($settings as $key => $value) {
                if (is_string($value) && str_contains($value, '/wp-content/')) {
                    $settings[$key] = null;
                    $modified = true;
                }
            }

            if ($modified) {
                DB::table('as_homepage_sections')
                    ->where('id', $section->id)
                    ->update(['settings' => json_encode($settings)]);
            }
        }

        // Note: placehold.co URLs are left as-is since they're external placeholder services
        // that will show placeholder images. They can be replaced by uploading real images.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot reverse as original files don't exist
    }
};
