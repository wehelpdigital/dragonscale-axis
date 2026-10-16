<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * The RSS Feeds module's settings, one row per key (axis_rss_settings).
 */
class RssSetting extends BaseModel
{
    protected $table = 'axis_rss_settings';

    protected $fillable = ['settingKey', 'settingValue'];

    public static function getValue(string $key, $default = null)
    {
        return static::where('settingKey', $key)->value('settingValue') ?? $default;
    }

    public static function setValue(string $key, $value): void
    {
        static::updateOrCreate(['settingKey' => $key], ['settingValue' => $value]);
    }

    /** The secret in the cron address, made the first time it is asked for. */
    public static function cronKey(): string
    {
        $key = (string) static::getValue('cronKey', '');
        if ($key === '') {
            $key = static::newCronKey();
        }

        return $key;
    }

    public static function newCronKey(): string
    {
        $key = Str::random(40);
        static::setValue('cronKey', $key);

        return $key;
    }

    /** Notes that the sources were just read automatically, and by what. */
    public static function ranBy(string $by): void
    {
        static::setValue('lastRunAt', now()->format('Y-m-d H:i:s'));
        static::setValue('lastRunBy', $by);
    }

    public static function lastRunAt(): ?Carbon
    {
        $at = static::getValue('lastRunAt');

        return $at ? Carbon::parse($at) : null;
    }
}
