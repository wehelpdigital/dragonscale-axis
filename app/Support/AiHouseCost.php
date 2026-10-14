<?php

namespace App\Support;

/**
 * What a run costs the house, in pesos, from the providers' list prices.
 *
 * Rough by design -- list prices move, and the peso does too -- but close
 * enough to read a margin by: is a 50-credit analysis earning ten times
 * its tokens or barely covering them? Since 2026-10-15 the rates, the peso
 * rate and the search fee are the pricing engine's (App\Support\AiPricingModel,
 * the twin of anee's AiPricing, edited on the Pricing engine tab), so this
 * reading and the engine never disagree. Gemini 3 bills web search per
 * query, so a searched run carries the engine's queries per search.
 */
final class AiHouseCost
{
    /** Pesos per US dollar, the default (the engine's own rate is used for the reading). */
    public const PHP_PER_USD = 58.0;

    /** @return array{0: float, 1: float} USD per million tokens, in and out */
    public static function rates(?string $provider, ?string $model): array
    {
        [$in, $out] = AiPricingModel::modelRates($provider, $model);

        return [$in, $out];
    }

    /** Pesos for one run. */
    public static function pesos(?string $provider, ?string $model, int $tokensIn, int $tokensOut, bool $searched = false): float
    {
        return AiPricingModel::runCost($provider, $model, $tokensIn, $tokensOut, $searched);
    }
}
