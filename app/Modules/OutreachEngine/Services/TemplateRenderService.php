<?php

namespace App\Modules\OutreachEngine\Services;

use App\Modules\OutreachEngine\Models\OutreachEmailTemplate;
use App\Modules\OutreachEngine\Models\OutreachLead;
use App\Modules\OutreachEngine\Models\OutreachSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Turns a stored template into the exact subject and HTML body that go out for one
 * lead, and - when the operator has AI rephrasing switched on - asks the model for a
 * light variation so a hundred sends do not arrive as a hundred identical strings.
 *
 * Two rules govern this class:
 *
 *  1. Lead values are scraped from Google and from strangers' websites. Every one of
 *     them is passed through e() before it touches the HTML body, so a business
 *     literally named "Smith & Sons <Auto>" cannot break the markup or smuggle a tag
 *     into an email we sign with our own domain.
 *  2. The model is a garnish, never a dependency. personalize() falls back to the
 *     plainly rendered template on any failure at all - no key, no response, bad
 *     JSON, missing fields, a suspiciously truncated body. Outreach must keep moving
 *     while the provider is down.
 */
class TemplateRenderService
{
    /**
     * A rephrased body shorter than this fraction of the original is treated as a
     * truncated response and thrown away. Models that hit a token ceiling mid-sentence
     * return something that reads fine and stops halfway - the length ratio is the
     * only cheap way to notice.
     */
    const MIN_REPHRASE_LENGTH_RATIO = 0.5;

    /** subjectTemplate is varchar(500); a rephrased subject must still fit the column. */
    const MAX_SUBJECT_LENGTH = 500;

    /** @var \App\Modules\OutreachEngine\Models\OutreachSetting */
    protected $settings;

    public function __construct(OutreachSetting $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Substitute every placeholder in a subject or body for this lead.
     *
     * Matching is case-insensitive so {Business_Name} behaves like {business_name}.
     * Unknown tokens are deliberately left alone: seeing "{buiness_name}" survive into
     * the preview is how an operator finds their typo, whereas a silently blanked
     * token just looks like a broken sentence.
     */
    public function render(string $template, OutreachLead $lead): string
    {
        return $this->applyReplacements($template, $this->replacements($lead));
    }

    /**
     * Same substitution, then wound back for a plain-text context.
     *
     * render() escapes for HTML, which is right for a body and wrong for a subject
     * line - "Smith &amp; Sons" is what a header would literally show. Use this for
     * anything that is not HTML; personalize() already does it for the subject it
     * returns.
     */
    public function renderSubject(string $template, OutreachLead $lead): string
    {
        return $this->tidySubject($this->render($template, $lead));
    }

    /**
     * Render the template and, if enabled, ask the LLM for a variation of the subject
     * line and opening sentence.
     *
     * @return array{subject:string,body:string,rephrased:bool}
     */
    public function personalize(OutreachEmailTemplate $template, OutreachLead $lead): array
    {
        $map = $this->replacements($lead);
        $subject = $this->applyReplacements((string) $template->subjectTemplate, $map);
        $body = $this->applyReplacements((string) $template->bodyTemplate, $map);

        $plain = [
            'subject' => $this->tidySubject($subject),
            'body' => $body,
            'rephrased' => false,
        ];

        if (!$this->settings->aiRephraseEnabled || !$this->settings->hasLlm()) {
            return $plain;
        }

        try {
            $llm = new LlmService($this->settings);

            if (!$llm->isConfigured()) {
                return $plain;
            }

            $result = $llm->completeJson(
                $this->rephraseSystemPrompt(),
                $this->rephraseUserPrompt($subject, $body, $lead),
                1200
            );

            if (empty($result) || !isset($result['subject'], $result['body'])) {
                Log::warning('[OutreachEngine] Rephrase returned no usable JSON; sending the template as written.', [
                    'templateId' => $template->id,
                    'leadId' => $lead->id,
                ]);

                return $plain;
            }

            $aiSubject = $this->tidySubject((string) $result['subject']);
            $aiBody = trim((string) $result['body']);

            if ($aiSubject === '' || $aiBody === '') {
                return $plain;
            }

            if (!$this->lengthLooksSane($body, $aiBody)) {
                Log::warning('[OutreachEngine] Rephrased body looks truncated; sending the template as written.', [
                    'templateId' => $template->id,
                    'leadId' => $lead->id,
                    'originalChars' => mb_strlen($this->plainLength($body)),
                    'rephrasedChars' => mb_strlen($this->plainLength($aiBody)),
                ]);

                return $plain;
            }

            // Models like to echo the placeholder tokens back verbatim. Resolving them
            // a second time means an echoed {business_name} still reaches the prospect
            // as a name rather than as a curly-braced token.
            $aiSubject = $this->tidySubject($this->applyReplacements($aiSubject, $map));
            $aiBody = $this->applyReplacements($aiBody, $map);

            return [
                'subject' => $aiSubject,
                'body' => $aiBody,
                'rephrased' => true,
            ];
        } catch (\Throwable $e) {
            // \Throwable, not \Exception: LlmService is resolved by name, so a class or
            // method problem arrives as an Error and would otherwise stop the send.
            Log::warning('[OutreachEngine] Rephrase failed, using the plain template: ' . $e->getMessage(), [
                'templateId' => $template->id,
                'leadId' => $lead->id,
            ]);

            return $plain;
        }
    }

    /**
     * Placeholder token => human label, for the templates editor's insert buttons.
     * Sourced from the model so the editor, the renderer and the docs cannot drift.
     */
    public function availablePlaceholders(): array
    {
        return OutreachEmailTemplate::PLACEHOLDERS;
    }

    /**
     * An unsaved lead carrying believable values, for previewing a template before any
     * real leads exist. Never saved - it is only ever handed to render().
     */
    public function sampleLead(): OutreachLead
    {
        return new OutreachLead([
            'businessName' => 'Sunrise Beach Resort',
            'category' => 'Resort',
            'address' => '12 Baywalk Road, Barangay Poblacion',
            'city' => 'San Juan',
            'province' => 'La Union',
            'phone' => '(072) 888 1234',
            'website' => 'https://sunrisebeachresort.example.ph',
            'email' => 'hello@sunrisebeachresort.example.ph',
        ]);
    }

    // ==================== INTERNALS ====================

    /**
     * Token => already-escaped replacement value for one lead.
     *
     * Every lead-derived value goes through e() here, at the single point where it
     * enters HTML, so no caller has to remember to do it. Blank fields fall back to a
     * neutral phrase - "businesses in ." reads like a bug, "businesses in your area"
     * reads like a sentence.
     */
    protected function replacements(OutreachLead $lead): array
    {
        $city = trim((string) $lead->city);
        $province = trim((string) $lead->province);
        $area = $city !== '' ? $city : ($province !== '' ? $province : 'your area');

        return [
            '{business_name}' => e(trim((string) $lead->businessName) ?: 'your business'),
            '{city}' => e($area),
            '{province}' => e($province !== '' ? $province : $city),
            '{address}' => e(trim((string) $lead->address)),
            '{phone}' => e(trim((string) $lead->phone)),
            '{website}' => e(trim((string) $lead->website)),
            '{category}' => e(trim((string) $lead->category) ?: 'local'),
            '{sender_name}' => e(trim((string) $this->settings->smtpFromName)),
            '{sender_email}' => e(trim((string) $this->settings->smtpFromEmail)),
            '{date}' => Carbon::now('Asia/Manila')->format('F j, Y'),
        ];
    }

    /**
     * Case-insensitive single pass over the token map.
     *
     * str_ireplace with parallel arrays replaces each token exactly once per
     * occurrence and never re-scans what it just inserted, so a business name that
     * happens to contain a token string cannot trigger a second substitution.
     */
    protected function applyReplacements(string $text, array $map): string
    {
        if ($text === '') {
            return '';
        }

        return str_ireplace(array_keys($map), array_values($map), $text);
    }

    /**
     * A subject line is a header: no markup, no line breaks, and short enough for the
     * column that stores it.
     */
    protected function tidySubject(string $subject): string
    {
        $clean = strip_tags($subject);
        $clean = html_entity_decode($clean, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $clean = preg_replace('/\s+/u', ' ', $clean);
        $clean = trim((string) $clean);

        if (mb_strlen($clean) > self::MAX_SUBJECT_LENGTH) {
            $clean = mb_substr($clean, 0, self::MAX_SUBJECT_LENGTH);
        }

        return $clean;
    }

    /**
     * Compare visible text, not markup - a model that rewrites the HTML with different
     * tags but the same words must not be rejected for it.
     */
    protected function lengthLooksSane(string $original, string $rephrased): bool
    {
        $originalLength = mb_strlen($this->plainLength($original));

        if ($originalLength === 0) {
            return true;
        }

        return mb_strlen($this->plainLength($rephrased)) >= ($originalLength * self::MIN_REPHRASE_LENGTH_RATIO);
    }

    /**
     * Visible text of an HTML fragment, whitespace collapsed.
     */
    protected function plainLength(string $html): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', strip_tags($html)));
    }

    /**
     * The rephrase contract. Stated as hard constraints because a model given a loose
     * brief will happily invent a discount we never offered.
     */
    protected function rephraseSystemPrompt(): string
    {
        return 'You rewrite cold outreach emails for a Philippine web agency. '
            . 'Keep the meaning identical. Keep every fact, name, number, link and price exactly as given - '
            . 'never invent or remove one. Keep the HTML structure, tags and attributes exactly as they are. '
            . 'Vary ONLY the subject line and the opening sentence; leave every other sentence untouched. '
            . 'Stay warm, plain and professional - no hype, no emoji, no exclamation marks. '
            . 'Reply with JSON only, no commentary and no code fences.';
    }

    /**
     * The per-lead brief. The rendered subject and body are supplied as-is so the model
     * edits the finished text rather than the template.
     */
    protected function rephraseUserPrompt(string $subject, string $body, OutreachLead $lead): string
    {
        $business = trim((string) $lead->businessName) ?: 'this business';
        $location = trim((string) $lead->display_location);
        $context = $location !== '' ? $business . ' in ' . $location : $business;

        return 'Rewrite the subject line and the opening sentence of this email for ' . $context . '. '
            . 'Everything after the opening sentence must be returned unchanged, including all HTML.' . "\n\n"
            . 'SUBJECT: ' . $subject . "\n\n"
            . 'BODY (HTML): ' . "\n" . $body . "\n\n"
            . 'Return JSON: {"subject": "string", "body": "string"}';
    }
}
