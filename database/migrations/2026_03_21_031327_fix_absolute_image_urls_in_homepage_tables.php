<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Convert absolute URLs to relative URLs in homepage tables.
     * This ensures images work correctly across all environments (local, staging, production).
     */
    public function up(): void
    {
        // Domains to strip from URLs (add any other domains that might be in the database)
        $domainsToStrip = [
            'http://btc-check.test',
            'https://btc-check.test',
            'http://anisenso.test',
            'https://anisenso.test',
            'http://localhost',
            'https://localhost',
        ];

        // Fix as_homepage_items table - image, image2, icon columns
        $items = DB::table('as_homepage_items')->get();

        foreach ($items as $item) {
            $updates = [];

            // Check and fix image column
            if ($item->image) {
                $fixedImage = $this->convertToRelativeUrl($item->image, $domainsToStrip);
                if ($fixedImage !== $item->image) {
                    $updates['image'] = $fixedImage;
                }
            }

            // Check and fix image2 column
            if ($item->image2) {
                $fixedImage2 = $this->convertToRelativeUrl($item->image2, $domainsToStrip);
                if ($fixedImage2 !== $item->image2) {
                    $updates['image2'] = $fixedImage2;
                }
            }

            // Check and fix icon column
            if ($item->icon) {
                $fixedIcon = $this->convertToRelativeUrl($item->icon, $domainsToStrip);
                if ($fixedIcon !== $item->icon) {
                    $updates['icon'] = $fixedIcon;
                }
            }

            // Update the row if any changes were made
            if (!empty($updates)) {
                DB::table('as_homepage_items')
                    ->where('id', $item->id)
                    ->update($updates);
            }
        }

        // Fix as_homepage_sections table - settings JSON column
        $sections = DB::table('as_homepage_sections')->get();

        foreach ($sections as $section) {
            if ($section->settings) {
                $settings = json_decode($section->settings, true);
                if ($settings && is_array($settings)) {
                    $modified = false;

                    foreach ($settings as $key => $value) {
                        if (is_string($value) && $this->isImageUrl($value)) {
                            $fixedValue = $this->convertToRelativeUrl($value, $domainsToStrip);
                            if ($fixedValue !== $value) {
                                $settings[$key] = $fixedValue;
                                $modified = true;
                            }
                        }
                    }

                    if ($modified) {
                        DB::table('as_homepage_sections')
                            ->where('id', $section->id)
                            ->update(['settings' => json_encode($settings)]);
                    }
                }
            }
        }
    }

    /**
     * Convert an absolute URL to a relative URL by stripping known domains.
     */
    private function convertToRelativeUrl(string $url, array $domainsToStrip): string
    {
        foreach ($domainsToStrip as $domain) {
            if (str_starts_with($url, $domain)) {
                return substr($url, strlen($domain));
            }
        }

        // Handle URLs with port numbers (e.g., http://localhost:8000/images/...)
        foreach ($domainsToStrip as $domain) {
            $pattern = preg_quote($domain, '/') . '(:\d+)?';
            if (preg_match('/^' . $pattern . '(.*)$/i', $url, $matches)) {
                return $matches[2];
            }
        }

        return $url;
    }

    /**
     * Check if a string looks like an image URL.
     */
    private function isImageUrl(string $value): bool
    {
        // Check if it starts with http and contains /images/ or common image extensions
        if (str_starts_with($value, 'http')) {
            return str_contains($value, '/images/') ||
                   preg_match('/\.(jpg|jpeg|png|gif|webp|svg)$/i', $value);
        }
        return false;
    }

    /**
     * Reverse the migrations (no-op, we don't want to restore absolute URLs).
     */
    public function down(): void
    {
        // Cannot reverse this migration as we don't know the original domain
    }
};
