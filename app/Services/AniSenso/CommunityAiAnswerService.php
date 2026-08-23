<?php

namespace App\Services\AniSenso;

use App\Models\AnisystemUser;
use App\Models\AsAiSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Generates community answers with the configured AniSenso AI, and resolves the
 * "AI Technician" persona user that authors the posted replies.
 *
 * Uses the shared AsAiSetting (provider / model / system prompt / assistant
 * name) and the shared, secret-encrypted key.
 */
class CommunityAiAnswerService
{
    /** Sentinel account that represents the AI Technician in the community. */
    public const PERSONA_EMAIL = 'ai-technician@anisenso.system';

    public function settings(): AsAiSetting
    {
        return AsAiSetting::current();
    }

    public function isUsable(): bool
    {
        return $this->settings()->isUsable();
    }

    /**
     * Find (or create) the AnisystemUser that authors AI replies. Kept in sync
     * with the configured assistant name. Never deletes; only upserts.
     */
    public function personaUser(): AnisystemUser
    {
        $settings = $this->settings();
        $name = trim((string) ($settings->assistantName ?? '')) ?: 'AI Technician';

        $user = AnisystemUser::where('email', self::PERSONA_EMAIL)->first();
        if (! $user) {
            $user = new AnisystemUser();
            $user->email = self::PERSONA_EMAIL;
            $user->password = bcrypt(Str::random(40));
            $user->clientId = 0;
            $user->status = 'active';
            $user->deleteStatus = 1;
        }
        // Keep the display name aligned with the configured persona — and the
        // face with it, or a discussion shows initials where the chat shows a
        // portrait of the same assistant.
        $user->firstName = $name;
        $user->lastName = '';
        if (filled($settings->avatarPath)) {
            $user->avatarPath = $settings->avatarPath;
        }
        $user->save();

        return $user;
    }

    /**
     * How an answer has to be written to survive the screen it lands on.
     *
     * The community renders a reply as escaped plain text with line breaks
     * kept — no markdown at all — so `**bold**` arrives as four literal
     * asterisks and a "## heading" as two hashes. Emoji, on the other hand,
     * are just characters and land exactly as written, which makes them the
     * only formatting this surface actually has.
     */
    private const HOUSE_STYLE = <<<'STYLE'

HOW TO WRITE THE ANSWER (the community shows plain text, never markdown):
- Never use markdown. No **bold**, no ## headings, no [links](...), no tables.
  Those characters are shown literally and make the answer look broken.
- Open with one warm line that answers the question directly.
- Then short blocks, one idea each, separated by a blank line.
- Where a block is a step or an option, start the line with a fitting emoji
  followed by a space — for example 🌱 planting, 💧 water, 🧪 inputs and
  chemicals, 🐛 pests, 💰 cost, ⚠️ a warning, ✅ what to do,
  📅 timing, 🌾 harvest. Two or three per answer, not a wall of them.
- Numbers matter to a farmer: give rates, days and prices as ranges in the
  units they use (kilo/ha, bags/ha, pesos), never vague adjectives.
- Filipino or Taglish if the question was asked that way; English if it was.
- Keep it under about 220 words, and close with one short line inviting them
  to say what their field is doing.
STYLE;

    /**
     * Ask the configured AI a single question and return its answer text.
     * Throws \RuntimeException on any provider/transport failure.
     */
    public function answer(string $question, string $context = ''): string
    {
        $settings = $this->settings();
        if (! $settings->isUsable()) {
            throw new \RuntimeException('The AniSenso AI is not configured (no provider/key).');
        }
        $key = $settings->plainApiKey();
        if (! $key) {
            throw new \RuntimeException('The AI key could not be read on this server.');
        }

        $system = trim((string) $settings->systemPrompt) ?: 'You are a helpful crop-farming technician. Answer community questions clearly and practically.';
        if ($context !== '') {
            $system .= "\n\n" . $context;
        }
        $system .= self::HOUSE_STYLE;
        $model = $settings->effectiveModel();
        $maxTokens = (int) ($settings->maxOutputTokens ?: 900);
        $temperature = (float) ($settings->temperature ?: 0.4);

        $text = match ($settings->provider) {
            'openai' => $this->askOpenAi($key, $model, $system, $question, $maxTokens, $temperature),
            'gemini' => $this->askGemini($key, $model, $system, $question, $maxTokens, $temperature),
            default  => $this->askClaude($key, $model, $system, $question, $maxTokens, $temperature),
        };

        return self::tidy($text);
    }

    /**
     * Ask again, with the answer it already gave and what to do differently.
     *
     * The previous answer travels in the question rather than as a second
     * turn: every provider helper here takes one user message, and a rewrite
     * is one instruction about one text, not a conversation.
     */
    public function refine(string $question, string $previousAnswer, string $instruction, string $context = ''): string
    {
        $instruction = trim($instruction) !== ''
            ? trim($instruction)
            : 'Write it again, better: clearer, more specific, and easier to act on.';

        $ask = "A farmer asked this in the community:\n\n"
            . trim($question)
            . "\n\n---\nThe answer you gave before was:\n\n"
            . trim($previousAnswer)
            . "\n\n---\nRewrite that answer following this instruction:\n\n"
            . $instruction
            . "\n\nReturn only the new answer — no preamble, no note about what you changed.";

        return $this->answer($ask, $context);
    }

    /**
     * Take out the markdown the model reaches for anyway.
     *
     * Asking nicely gets most of the way; a stray **rate** or a "* " bullet
     * still slips through, and on a surface that escapes everything those
     * characters are what the farmer reads. Bullets become a real bullet
     * rather than vanishing, so the shape of the answer survives.
     */
    public static function tidy(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", trim($text));

        // ### Heading -> Heading
        // [ \t] rather than \s: \s eats the newline before the line it is
        // anchored to, and the blank line between two blocks is the only
        // paragraph break this surface has.
        $text = preg_replace('/^[ \t]{0,3}#{1,6}[ \t]*/m', '', $text);
        // **bold** / __bold__ -> bold
        $text = preg_replace('/\*\*(.+?)\*\*/s', '$1', $text);
        $text = preg_replace('/__(.+?)__/s', '$1', $text);
        // * bullet / - bullet -> bulleted line (a line already led by an
        // emoji keeps it; this only replaces the marker).
        $text = preg_replace('/^[ \t]{0,3}[*\-•][ \t]+/mu', '• ', $text);
        // [label](url) -> label (url), because a link is not clickable there
        $text = preg_replace('/\[([^\]]+)\]\((https?:[^)\s]+)\)/', '$1 ($2)', $text);
        $text = preg_replace('/\[([^\]]+)\]\([^)]*\)/', '$1', $text);
        // Leftover emphasis asterisks around a word.
        $text = preg_replace('/(?<!\*)\*(?!\s)([^*\n]+?)(?<!\s)\*(?!\*)/', '$1', $text);
        // Three or more blank lines read as a gap, not a pause.
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        return trim($text);
    }

    private function askClaude(string $key, string $model, string $system, string $q, int $max, float $temp): string
    {
        $res = Http::withHeaders([
            'x-api-key' => $key,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
            'model' => $model,
            'max_tokens' => $max,
            'temperature' => $temp,
            'system' => $system,
            'messages' => [['role' => 'user', 'content' => $q]],
        ]);

        if (! $res->successful()) {
            throw new \RuntimeException('Claude error: ' . $res->status() . ' ' . $res->body());
        }
        $text = data_get($res->json(), 'content.0.text');
        if (! filled($text)) {
            throw new \RuntimeException('Claude returned no text.');
        }
        return trim($text);
    }

    private function askOpenAi(string $key, string $model, string $system, string $q, int $max, float $temp): string
    {
        $res = Http::withToken($key)->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
            'model' => $model,
            'max_tokens' => $max,
            'temperature' => $temp,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $q],
            ],
        ]);

        if (! $res->successful()) {
            throw new \RuntimeException('OpenAI error: ' . $res->status() . ' ' . $res->body());
        }
        $text = data_get($res->json(), 'choices.0.message.content');
        if (! filled($text)) {
            throw new \RuntimeException('OpenAI returned no text.');
        }
        return trim($text);
    }

    private function askGemini(string $key, string $model, string $system, string $q, int $max, float $temp): string
    {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent?key=' . urlencode($key);
        $res = Http::timeout(60)->post($url, [
            'system_instruction' => ['parts' => [['text' => $system]]],
            'contents' => [['role' => 'user', 'parts' => [['text' => $q]]]],
            'generationConfig' => ['maxOutputTokens' => $max, 'temperature' => $temp],
        ]);

        if (! $res->successful()) {
            throw new \RuntimeException('Gemini error: ' . $res->status() . ' ' . $res->body());
        }
        $text = data_get($res->json(), 'candidates.0.content.parts.0.text');
        if (! filled($text)) {
            throw new \RuntimeException('Gemini returned no text.');
        }
        return trim($text);
    }
}
