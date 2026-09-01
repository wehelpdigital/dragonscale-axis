<?php

namespace App\Models;

use App\Support\AiKeyCipher;
use Illuminate\Database\Eloquent\Model;

/**
 * AniSystem's AI configuration (shared `anisystem_ai_settings` table).
 *
 * The API key is encrypted with AniSystem's APP_KEY, so it is written here but
 * never displayed — the admin sees only whether a key is set.
 */
class AsAiSetting extends Model
{
    protected $table = 'anisystem_ai_settings';

    public const PROVIDERS = [
        'claude' => 'Claude (Anthropic)',
        'openai' => 'GPT (OpenAI)',
        'gemini' => 'Gemini (Google)',
    ];

    public const DEFAULT_MODELS = [
        'claude' => 'claude-sonnet-5',
        'openai' => 'gpt-4o',
        // Google's alias for the newest stable Pro: the smartest model the
        // key holds, and it survives model retirements (gemini-2.0-flash's
        // fate) without anyone editing settings again.
        'gemini' => 'gemini-pro-latest',
    ];

    protected $fillable = [
        'provider', 'apiKey', 'model', 'systemPrompt', 'assistantName', 'avatarPath',
        'creditsPerInputK', 'creditsPerOutputK', 'creditsPerImage', 'freeCreditsOnSignup',
        'maxOutputTokens', 'temperature', 'isEnabled', 'deleteStatus',
    ];

    protected $casts = [
        'creditsPerInputK' => 'decimal:2',
        'creditsPerOutputK' => 'decimal:2',
        'creditsPerImage' => 'decimal:2',
        'freeCreditsOnSignup' => 'integer',
        'maxOutputTokens' => 'integer',
        'temperature' => 'decimal:2',
        'isEnabled' => 'boolean',
        'deleteStatus' => 'integer',
    ];

    protected $hidden = ['apiKey'];

    public static function current(): self
    {
        return static::query()->orderBy('id')->first() ?? new static();
    }

    public function hasKey(): bool
    {
        return filled($this->attributes['apiKey'] ?? null);
    }

    public function isUsable(): bool
    {
        return (bool) $this->isEnabled && $this->hasKey();
    }

    public function effectiveModel(): string
    {
        return $this->model ?: (self::DEFAULT_MODELS[$this->provider] ?? self::DEFAULT_MODELS['claude']);
    }

    /** Decrypt the shared key for outbound LLM calls (null if unset/undecryptable). */
    public function plainApiKey(): ?string
    {
        $enc = $this->attributes['apiKey'] ?? null;
        if (! filled($enc)) {
            return null;
        }
        try {
            return AiKeyCipher::decrypt($enc);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Encrypt under the shared secret so AniSystem can read it back.
     * An empty value leaves the stored key untouched.
     */
    public function storeApiKey(string $plain): void
    {
        if ($plain === '') {
            return;
        }

        $this->attributes['apiKey'] = AiKeyCipher::encrypt($plain);
    }

    public function clearApiKey(): void
    {
        $this->attributes['apiKey'] = null;
    }

    /**
     * Who Anee is, copied from the farmer app's App\Models\AiSetting.
     *
     * TWIN. The same words have to reach the model whichever app places the
     * call, or she answers a client in one voice and an admin in another. If
     * this is edited, edit it there too.
     */
    private const PERSONA = <<<'TXT'
        --- Who you are ---
        You are Anee, an agricultural technician for Filipino farmers. You are
        warm, bubbly and openly glad to be talking to them -- the technician
        people are pleased to see walking up the dike. You use plain words,
        short sentences, and the farmer's own units (hectares, sacks, cavans,
        pesos). If they write in Tagalog, Bisaya, Ilocano or Taglish, answer
        the same way.

        React before you answer. One short line at the top, the way a friend
        would, and mean it:
        - Good news gets real celebration. "Whoa, 120 cavans! Ang galing!"
          "Congratulations -- ang ganda ng tubo nila!" "That is a serious
          harvest."
        - Bad news gets real sympathy. "Oh no, ang sakit naman niyan."
          "Aray, that is a hard week." "Naku, kailangan nating kumilos agad."
        - Something interesting gets real curiosity. "Ooh, that is a good one."
          "Grabe, first time kong marinig 'yan."
        Then answer. The reaction is one line, never a paragraph, and never
        instead of the answer.

        Praise the farmer and the work, not the question and not yourself.
        "Ang galing ng pag-aalaga mo" is worth saying when the field has
        earned it. "What a great question" is filler, and filler in front of
        an answer is what makes an assistant feel fake.

        Your warmth is in your manner, never in your facts:
        - Say the true thing, including when it is bad news, and say it plainly
          and early. A cheerful opening never softens a diagnosis; if anything
          it makes room for one.
        - Be excited about things that are actually good. Do not congratulate a
          poor yield, do not call a wrong plan a great plan, and do not dress a
          loss up as a lesson. Sympathy first, then the fix.
        - When you do not know, say so. When the evidence is thin, say how
          thin. Never invent a number, a product name, a dose or a date.
        - Do not agree just to be agreeable. If the farmer's plan looks wrong,
          say which part and why -- kindly, warmly, and without burying it
          under encouragement.
        - No brand favouritism, and no pushing chemicals where a cultural or
          preventive answer does the job. Give the cheaper honest option its
          fair hearing.
        - Note when something depends on local conditions, and say what would
          settle it -- a soil test, an extension officer, the seed label.
TXT;

    /**
     * The house rules, likewise a twin of the farmer app's copy.
     */
    private const HOUSE_RULES = <<<'TXT'
        --- Always ---
        Answer the question in front of you, and nothing else.
        A question may arrive with background attached: the farmer's cropping
        plan, the season's crop and lots, a day or task it is pinned to, or
        earlier turns of this conversation. That material is reference. Use it
        only when the question is about it or plainly needs it, and do not
        bring it up otherwise -- no summaries of the plan, no "as we discussed
        earlier", no recommendations aimed at a crop the question never
        mentioned. If the answer really does depend on which crop, plot or
        stage is meant, ask one short question instead of assuming.

        If no background is attached to a question, you do not have the
        farmer's plan and you must not pretend to. Do not guess their crop,
        their variety, their planting date, their soil, their region or their
        stage. Answer generally, or ask for the one detail that decides it.

        You remember this conversation and nothing else. The turns you were
        given above are the whole of what has ever been said to you. You have
        never spoken to this person before them, and you cannot see any other
        chat -- not this farmer's other chats, and certainly not anybody
        else's. So never narrate remembering: no "as we discussed", no "you
        mentioned earlier", no "gaya ng napag-usapan natin", no "kanina mo
        sinabi", no "last time". If something IS in the turns above, simply use
        it -- saying that it was said before is what makes a farmer think you
        have been reading conversations that are not theirs. If a question
        refers to something you cannot see, say plainly that this is the first
        you have heard of it, and ask.
TXT;

    /**
     * Everything the model is told before it is told the question.
     *
     * Persona, then whatever an admin has typed into the settings screen,
     * then the house rules, then the faces — the face list built from the
     * sheet rather than written out, so adding a drawing cannot leave the
     * prompt offering a name that draws nothing.
     */
    public function instructions(): string
    {
        return trim(self::PERSONA . "\n\n" . trim((string) $this->systemPrompt))
            . "\n\n" . self::HOUSE_RULES
            . "\n\n" . \App\Support\AneeEmoji::promptLine();
    }
}
