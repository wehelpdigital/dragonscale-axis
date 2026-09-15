<?php

namespace App\Models;

/**
 * One of anee.io's site-wide switches: a key and its value, set here and
 * read there (its twin is anee's App\Models\AsSiteSetting). The first on
 * the shelf: seo.publicIndexable -- whether search engines may index the
 * public site at all. The app behind the login is never indexable.
 */
class AsSiteSetting extends BaseModel
{
    protected $table = 'as_site_settings';

    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            return static::query()->where('key', $key)->value('value') ?? $default;
        } catch (\Throwable $e) {
            // anee.io has not run the migration yet: the default.
            return $default;
        }
    }

    /** A yes/no switch, read leniently ("1", "true", "on", "yes"). */
    public static function yes(string $key, bool $default = false): bool
    {
        $v = self::get($key);
        if ($v === null) {
            return $default;
        }

        return filter_var($v, FILTER_VALIDATE_BOOLEAN);
    }

    public static function put(string $key, mixed $value): void
    {
        $stored = is_bool($value) ? ($value ? '1' : '0') : (string) $value;
        static::query()->updateOrCreate(['key' => $key], ['value' => $stored]);
    }
}
