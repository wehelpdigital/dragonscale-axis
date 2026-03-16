<?php

namespace App\Models;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiCurrencySetting extends BaseModel
{
    protected $table = 'ai_currency_settings';

    protected $fillable = [
        'usersId',
        'usdToPhpRate',
        'lastRateUpdate',
        'autoUpdate',
        'apiSource',
        'delete_status',
    ];

    protected $casts = [
        'usdToPhpRate' => 'decimal:4',
        'autoUpdate' => 'boolean',
        'lastRateUpdate' => 'datetime',
    ];

    /**
     * Default exchange rate if API fails
     */
    const DEFAULT_USD_TO_PHP = 56.0;

    /**
     * API source for exchange rates (free, no API key required)
     */
    const API_SOURCE = 'https://open.er-api.com/v6/latest/USD';

    /**
     * Scope for active records
     */
    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    /**
     * Scope for user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('usersId', $userId);
    }

    /**
     * Get or create currency setting for a user
     */
    public static function getOrCreateForUser($userId): self
    {
        $setting = self::active()
            ->forUser($userId)
            ->first();

        if (!$setting) {
            $setting = self::create([
                'usersId' => $userId,
                'usdToPhpRate' => self::DEFAULT_USD_TO_PHP,
                'autoUpdate' => true,
                'apiSource' => 'exchangerate-api',
                'delete_status' => 'active',
            ]);

            // Try to fetch current rate
            $setting->refreshRate();
        }

        return $setting;
    }

    /**
     * Get or create GLOBAL currency setting (not user-specific)
     */
    public static function getOrCreate(): self
    {
        $setting = self::active()->first();

        if (!$setting) {
            $setting = self::create([
                'usersId' => null,
                'usdToPhpRate' => self::DEFAULT_USD_TO_PHP,
                'autoUpdate' => true,
                'apiSource' => 'exchangerate-api',
                'delete_status' => 'active',
            ]);

            // Try to fetch current rate
            $setting->refreshRate();
        }

        return $setting;
    }

    /**
     * Fetch the latest exchange rate from the API
     */
    public function refreshRate(): bool
    {
        try {
            $response = Http::timeout(10)->get(self::API_SOURCE);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['rates']['PHP'])) {
                    $this->usdToPhpRate = $data['rates']['PHP'];
                    $this->lastRateUpdate = now();
                    $this->save();

                    Log::info('Currency rate updated', [
                        'userId' => $this->usersId,
                        'rate' => $this->usdToPhpRate,
                        'source' => self::API_SOURCE,
                    ]);

                    return true;
                }
            }

            Log::warning('Failed to fetch exchange rate', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 200),
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching exchange rate: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Get the current USD to PHP rate (auto-refresh if needed)
     */
    public function getRate(): float
    {
        // Auto-refresh if enabled and last update was more than 24 hours ago
        if ($this->autoUpdate && $this->shouldRefresh()) {
            $this->refreshRate();
        }

        return (float) $this->usdToPhpRate;
    }

    /**
     * Check if rate should be refreshed (older than 24 hours)
     */
    public function shouldRefresh(): bool
    {
        if (!$this->lastRateUpdate) {
            return true;
        }

        return $this->lastRateUpdate->diffInHours(now()) >= 24;
    }

    /**
     * Get formatted rate with currency symbol
     */
    public function getFormattedRateAttribute(): string
    {
        return '₱' . number_format($this->usdToPhpRate, 4);
    }

    /**
     * Get last update time in human-readable format
     */
    public function getLastUpdateAgoAttribute(): string
    {
        if (!$this->lastRateUpdate) {
            return 'Never';
        }

        return $this->lastRateUpdate->diffForHumans();
    }

    /**
     * Convert USD to PHP
     */
    public function convertUsdToPhp(float $usd): float
    {
        return $usd * $this->getRate();
    }
}
