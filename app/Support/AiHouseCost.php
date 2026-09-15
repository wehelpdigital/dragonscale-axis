<?php

namespace App\Support;

/**
 * What a run costs the house, in pesos, from the providers' list prices.
 *
 * Rough by design -- list prices move, and the peso does too -- but close
 * enough to read a margin by: is a 50-credit analysis earning ten times
 * its tokens or barely covering them? The table is matched on the model
 * name loosely (any "flash" Gemini, any "sonnet" Claude) so a newer
 * version of the same family reads at the same rate.
 */
final class AiHouseCost
{
    /** Pesos per US dollar, for the reading. */
    public const PHP_PER_USD = 58.0;

    /** USD per one million tokens, [input, output], by provider and family. */
    private const RATES = [
        'gemini' => [
            'flash-lite' => [0.10, 0.40],
            'flash' => [0.30, 2.50],
            'pro' => [1.25, 10.00],
            '*' => [1.25, 10.00],
        ],
        'claude' => [
            'opus' => [15.00, 75.00],
            'haiku' => [1.00, 5.00],
            'sonnet' => [3.00, 15.00],
            '*' => [3.00, 15.00],
        ],
        'openai' => [
            'nano' => [0.10, 0.40],
            'mini' => [0.40, 1.60],
            'gpt-5' => [1.25, 10.00],
            'gpt-4.1' => [2.00, 8.00],
            'gpt-4o' => [2.50, 10.00],
            '*' => [2.50, 10.00],
        ],
    ];

    /** USD per web-searched request, on top of the tokens. */
    private const SEARCH = ['gemini' => 0.035, 'claude' => 0.01, 'openai' => 0.025];

    /** @return array{0: float, 1: float} USD per million tokens, in and out */
    public static function rates(?string $provider, ?string $model): array
    {
        $table = self::RATES[$provider] ?? self::RATES['gemini'];
        $m = strtolower((string) $model);
        foreach ($table as $family => $rate) {
            if ($family !== '*' && str_contains($m, $family)) {
                return $rate;
            }
        }

        return $table['*'];
    }

    /** Pesos for one run. */
    public static function pesos(?string $provider, ?string $model, int $tokensIn, int $tokensOut, bool $searched = false): float
    {
        [$in, $out] = self::rates($provider, $model);
        $usd = ($tokensIn / 1_000_000) * $in + ($tokensOut / 1_000_000) * $out
            + ($searched ? (self::SEARCH[$provider] ?? 0.03) : 0);

        return round($usd * self::PHP_PER_USD, 2);
    }
}
