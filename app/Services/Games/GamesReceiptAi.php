<?php

namespace App\Services\Games;

use App\Models\AsAiSetting;

/**
 * The owner's AI (AniSystem > AI Settings: the provider, model and key anee.io
 * uses), with a receipt reader's instructions instead of Anee's persona, and
 * steady settings (temperature 0, a short answer). Never saved: a copy for one
 * call through App\Services\AniSensoAiClient.
 */
class GamesReceiptAi extends AsAiSetting
{
    private string $readerInstructions = '';

    public static function from(AsAiSetting $base, string $instructions): self
    {
        $copy = new self();
        $copy->setRawAttributes($base->getAttributes(), true);
        $copy->exists = false;
        $copy->readerInstructions = $instructions;
        $copy->temperature = 0;
        $copy->maxOutputTokens = 1200;

        return $copy;
    }

    public function instructions(): string
    {
        return $this->readerInstructions;
    }

    public function save(array $options = []): bool
    {
        return false;  // A copy for one call.
    }
}
