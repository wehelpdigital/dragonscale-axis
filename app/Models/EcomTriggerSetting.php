<?php

namespace App\Models;

class EcomTriggerSetting extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecom_trigger_settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'settingKey',
        'settingValue',
        'settingType',
        'description',
    ];

    /**
     * Disable timestamps for settings.
     */
    public $timestamps = true;

    /**
     * Get a setting value by key.
     */
    public static function getValue($key, $default = null)
    {
        $setting = self::where('settingKey', $key)->first();

        if (!$setting) {
            return $default;
        }

        return match($setting->settingType) {
            'boolean' => (bool) $setting->settingValue,
            'integer' => (int) $setting->settingValue,
            'json' => json_decode($setting->settingValue, true),
            default => $setting->settingValue,
        };
    }

    /**
     * Set a setting value.
     */
    public static function setValue($key, $value, $type = 'string', $description = null)
    {
        if ($type === 'json' && is_array($value)) {
            $value = json_encode($value);
        } elseif ($type === 'boolean') {
            $value = $value ? '1' : '0';
        }

        return self::updateOrCreate(
            ['settingKey' => $key],
            [
                'settingValue' => $value,
                'settingType' => $type,
                'description' => $description,
            ]
        );
    }

    /**
     * Get the cron secret key.
     */
    public static function getCronSecret()
    {
        return self::getValue('cron_secret_key', '');
    }

    /**
     * Check if cron is enabled.
     */
    public static function isCronEnabled()
    {
        return self::getValue('cron_enabled', true);
    }

    /**
     * Get batch size for cron processing.
     */
    public static function getCronBatchSize()
    {
        return self::getValue('cron_batch_size', 10);
    }

    /**
     * Update last cron run time.
     */
    public static function updateLastCronRun()
    {
        self::setValue('cron_last_run', now()->format('Y-m-d H:i:s'));

        $totalRuns = self::getValue('cron_total_runs', 0);
        self::setValue('cron_total_runs', $totalRuns + 1, 'integer');
    }

    /**
     * Get last cron run time.
     */
    public static function getLastCronRun()
    {
        return self::getValue('cron_last_run');
    }

    /**
     * Get total cron runs.
     */
    public static function getTotalCronRuns()
    {
        return self::getValue('cron_total_runs', 0);
    }

    /**
     * Regenerate cron secret key.
     */
    public static function regenerateCronSecret()
    {
        $newSecret = \Illuminate\Support\Str::random(32);
        self::setValue('cron_secret_key', $newSecret);
        return $newSecret;
    }
}
