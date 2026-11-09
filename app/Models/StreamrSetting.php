<?php

namespace App\Models;

/**
 * The Streamr module's settings shelf (streamr_settings), one row per key.
 * Never secrets: those live only in the environment.
 *
 *   pay   how Streamr is paid: the source of the receiving details, the GCash
 *         name as receipts print it, QR names, auto-approval, review hours
 */
class StreamrSetting extends BaseModel
{
    protected $table = 'streamr_settings';

    protected $fillable = ['settingKey', 'settingValue', 'updatedBy'];

    protected $casts = [
        'updatedBy' => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public static function getValue(string $key, $default = null)
    {
        return static::where('settingKey', $key)->value('settingValue') ?? $default;
    }

    public static function setValue(string $key, $value, ?int $by = null): void
    {
        static::updateOrCreate(
            ['settingKey' => $key],
            ['settingValue' => $value === null ? null : (string) $value, 'updatedBy' => $by]
        );
    }

    public static function getJson(string $key, array $default = []): array
    {
        $decoded = json_decode((string) static::getValue($key, ''), true);

        return is_array($decoded) ? $decoded : $default;
    }

    public static function setJson(string $key, array $value, ?int $by = null): void
    {
        static::setValue($key, json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $by);
    }
}
