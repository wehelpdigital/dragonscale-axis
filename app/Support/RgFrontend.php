<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Single source of truth for the Resort Guru PH frontend URL.
 *
 * Historical context: the mother app had three different config
 * paths in use (`app.frontend_url`, `services.frontend_url`,
 * `services.resort_guru.frontend_url`), only one of which (the
 * second) was actually populated from the `RG_FRONTEND_URL` env
 * var. Views and controllers used a mix of the three and most
 * silently fell back to a `localhost:8001` default that didn't
 * match the operator's actual setup. That's what made the Live
 * Editor iframe fail to connect.
 *
 * This helper consolidates the lookup into a cascade:
 *  1. rg_settings row keyed `rg_frontend_url` (admin-editable
 *     via the /resort-guru-settings page's Frontend tab)
 *  2. env `RG_FRONTEND_URL`
 *  3. hardcoded `http://localhost:8001` fallback
 *
 * On first access, if the rg_settings row is missing the helper
 * self-seeds it so the operator immediately sees a row to edit on
 * the settings page. The lookup is request-cached.
 *
 * Use `RgFrontend::url()` in code and `\App\Support\RgFrontend::url()`
 * in Blade. Always returns a value with no trailing slash.
 *
 * KEY NAMING NOTE: PHP silently converts dots and spaces in $_POST
 * keys to underscores when populating the superglobal, so a form
 * field `name="rg.frontend_url"` would land in $request as
 * `rg_frontend_url`. The settings update controller iterates the
 * request keys and runs `where('key', $key)->update(...)`, which
 * would then silently no-op against a dot-keyed row. Stick to
 * underscore-only keys to match the rest of rg_settings.
 */
class RgFrontend
{
    /** rg_settings row key. NO DOTS — see KEY NAMING NOTE on the class. */
    public const SETTING_KEY = 'rg_frontend_url';

    private static ?string $cached = null;

    /** Resolve the configured frontend base URL (no trailing slash). */
    public static function url(): string
    {
        if (self::$cached !== null) return self::$cached;

        $envDefault = rtrim((string) env('RG_FRONTEND_URL', 'http://localhost:8001'), '/');

        // The rg_settings table is owned by the frontend's migrations
        // (see CLAUDE.md). If the table isn't there yet on this
        // host, fall back gracefully.
        if (!Schema::hasTable('rg_settings')) {
            return self::$cached = $envDefault;
        }

        // Self-heal: an earlier version of this helper used a dot-keyed
        // row ('rg.frontend_url'). PHP form-encode mangled the dot to
        // underscore, so saves silently no-op'd. Migrate any leftover
        // dot row to the underscore key on first access.
        DB::table('rg_settings')
            ->where('key', 'rg.frontend_url')
            ->update(['key' => self::SETTING_KEY, 'updated_at' => now()]);

        $row = DB::table('rg_settings')->where('key', self::SETTING_KEY)->first();

        if (!$row) {
            DB::table('rg_settings')->insert([
                'key' => self::SETTING_KEY,
                'value' => $envDefault,
                'label' => 'Frontend URL',
                'type' => 'string',
                'group' => 'frontend',
                'help_text' => 'Base URL of the Resort Guru PH frontend (the public site). '
                    . 'Used everywhere the admin links to a rendered page: View Live buttons, '
                    . 'the Live Editor preview iframe, URL slug previews on the page create / '
                    . 'edit forms, schema validators, etc. Examples: http://localhost:8001, '
                    . 'http://127.0.0.1:8001, http://resortguruph.test',
                'updated_at' => now(),
                'created_at' => now(),
            ]);
            return self::$cached = $envDefault;
        }

        $url = trim((string) $row->value);
        if ($url === '') return self::$cached = $envDefault;
        return self::$cached = rtrim($url, '/');
    }

    /** Convenience: build a full URL for a slug path (slug or empty). */
    public static function urlFor(string $slug): string
    {
        $slug = ltrim($slug, '/');
        return self::url() . ($slug === '' ? '' : '/' . $slug);
    }

    /**
     * Clear the request cache. Useful right after a setting save so
     * subsequent calls in the same request reflect the new value.
     */
    public static function flushCache(): void
    {
        self::$cached = null;
    }
}
