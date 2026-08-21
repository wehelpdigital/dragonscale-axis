<?php

namespace App\Modules\OutreachEngine\Services;

use App\Modules\OutreachEngine\Models\OutreachLead;
use App\Modules\OutreachEngine\Models\OutreachSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Finds the contact email for one scraped lead.
 *
 * The ladder is ordered by cost, cheapest first, and stops at the first hit:
 *   1. no website on the lead  -> Google Custom Search for one (and a Facebook page)
 *   2. fetch the site plus /contact, /contact-us, /about on the same host
 *   3. PATTERN MATCH each page - free, and it catches the large majority of sites
 *   4. only when the pattern pass came back empty, pay for one LLM read
 *   5. validate, then write the result back to the lead row
 *
 * Every exit path - hit, miss or crash - updates the lead and has already bumped
 * enrichmentAttempts, so a site that can never be read stops costing cron time after
 * OutreachLead::MAX_ENRICHMENT_ATTEMPTS tries.
 *
 * A missing search key or a missing LLM key ends the run as 'skipped', never 'failed':
 * that is an unfinished configuration, not a dead lead, and it should become workable
 * again the moment the operator fills the field in.
 */
class LeadEnrichmentService
{
    /** Seconds per page fetch. Slow hosts are abandoned, not waited on. */
    const FETCH_TIMEOUT = 15;

    /** Hard ceiling on fetches per lead: the site itself, its 3 contact paths, its Facebook page. */
    const MAX_PAGES = 5;

    /** Stripped text kept per page before it may reach the model. */
    const MAX_TEXT_CHARS = 12000;

    /** Ceiling on the whole prompt payload when several pages are sent together. */
    const MAX_LLM_CHARS = 24000;

    /** A real browser UA - a bot string gets a 403 from most WAFs. */
    const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36';

    /** Paths probed on the primary host - where small business sites keep their address. */
    const CONTACT_PATHS = ['/contact', '/contact-us', '/about'];

    /** Local parts that mark a genuine business inbox, ranked above a random personal one. */
    const ROLE_PREFIXES = ['info', 'contact', 'hello', 'inquiry', 'inquiries'];

    /**
     * Domains that are never a customer's mailbox - placeholder copy, site-builder
     * plumbing and vendor telemetry. Matched as the domain itself or a subdomain of it.
     */
    const REJECT_DOMAINS = [
        'example.com',
        'example.org',
        'example.net',
        'example.edu',
        'domain.com',
        'yourdomain.com',
        'yoursite.com',
        'email.com',
        'sentry.io',
        'wixpress.com',
        'godaddy.com',
        'secureserver.net',
        'cloudflare.com',
        'w3.org',
        'schema.org',
    ];

    /** Substrings that condemn a domain wherever they appear (vendor CDNs, error trackers). */
    const REJECT_FRAGMENTS = [
        'sentry',
        'wixpress',
        'godaddy',
        'cloudflare',
        'gravatar',
        'sentry-cdn',
    ];

    /** An "address" ending in one of these is a filename, e.g. logo@2x.png. */
    const IMAGE_EXTENSIONS = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'bmp', 'ico', 'tif', 'tiff'];

    /**
     * Two-label public suffixes that matter here, so example.com.ph and shop.example.com.ph
     * are recognised as the same registrable domain.
     */
    const MULTI_LABEL_SUFFIXES = [
        'com.ph', 'net.ph', 'org.ph', 'gov.ph', 'edu.ph',
        'co.uk', 'org.uk', 'com.au', 'net.au', 'co.nz',
        'com.sg', 'com.my', 'co.th', 'com.hk', 'co.jp',
    ];

    /** Aggregators and social sites: worth reading, but never the preferred "official website". */
    const DIRECTORY_HOSTS = [
        'google.com', 'maps.google.com', 'goo.gl', 'yelp.com', 'tripadvisor.com',
        'foursquare.com', 'yellowpages.com', 'yellow-pages.ph', 'wikipedia.org',
        'instagram.com', 'twitter.com', 'x.com', 'linkedin.com', 'youtube.com',
        'tiktok.com', 'booking.com', 'agoda.com', 'airbnb.com', 'lazada.com.ph',
        'shopee.ph',
    ];

    protected OutreachSetting $settings;

    protected LlmService $llm;

    public function __construct(OutreachSetting $settings)
    {
        $this->settings = $settings;
        $this->llm = new LlmService($settings);
    }

    /**
     * Run the full discovery pipeline for one lead and persist the outcome.
     *
     * @return array ['email' => ?string, 'source' => ?string, 'error' => ?string]
     */
    public function enrich(OutreachLead $lead): array
    {
        // Claim the row before any network work: the counter has to move even if this
        // process is killed mid-fetch, or an unreachable site burns a slot forever.
        $lead->enrichmentStatus = OutreachLead::ENRICHMENT_PROCESSING;
        $lead->enrichmentAttempts = (int) $lead->enrichmentAttempts + 1;
        $lead->save();

        // Places sometimes hands the address over with the listing - nothing to discover.
        if ($lead->hasValidEmail()) {
            return $this->finish(
                $lead,
                strtolower(trim((string) $lead->email)),
                $lead->emailSource ?: OutreachLead::SOURCE_PLACES,
                OutreachLead::ENRICHMENT_ENRICHED,
                null
            );
        }

        try {
            return $this->runLadder($lead);
        } catch (\Throwable $e) {
            Log::error('[OutreachEngine] Lead enrichment crashed: ' . $e->getMessage(), [
                'leadId' => $lead->id,
                'usersId' => $lead->usersId,
            ]);

            return $this->finish(
                $lead,
                null,
                null,
                OutreachLead::ENRICHMENT_FAILED,
                'Enrichment error: ' . mb_substr($e->getMessage(), 0, 400)
            );
        }
    }

    /**
     * Emails found in a blob of text or HTML, best candidate first.
     *
     * Public because it is the one piece of this service worth exercising directly:
     * hand it a saved page and assert what comes back.
     *
     * @param string $text Raw HTML or already-stripped text.
     * @param string|null $preferHost Host the text came from - its own domain wins.
     * @return array<int, string> Lower-cased, validated, de-duplicated, ranked.
     */
    public function extractEmailsFromText(string $text, ?string $preferHost = null): array
    {
        if (trim($text) === '') {
            return [];
        }

        $candidates = [];

        // mailto: hrefs first - plenty of sites never print the address as visible text.
        if (preg_match_all('/mailto:\s*([^"\'<>\s\)\]]+)/i', $text, $hrefMatches)) {
            foreach ($hrefMatches[1] as $hit) {
                $candidates[] = rawurldecode($hit);
            }
        }

        $plain = $this->deobfuscate($text);

        if (preg_match_all('/[A-Za-z0-9._%+\-]+@[A-Za-z0-9](?:[A-Za-z0-9.\-]*[A-Za-z0-9])?\.[A-Za-z]{2,24}/', $plain, $plainMatches)) {
            foreach ($plainMatches[0] as $hit) {
                $candidates[] = $hit;
            }
        }

        // Keep discovery order so the ranking sort stays stable within a score band.
        $accepted = [];
        foreach ($candidates as $raw) {
            $email = $this->cleanEmail($raw);
            if ($email === null || isset($accepted[$email])) {
                continue;
            }
            $accepted[$email] = count($accepted);
        }

        if (empty($accepted)) {
            return [];
        }

        $host = $this->normalizeHost($preferHost);

        $ranked = [];
        foreach ($accepted as $email => $order) {
            $ranked[] = [
                'email' => (string) $email,
                'order' => $order,
                'score' => $this->rankEmail((string) $email, $host),
            ];
        }

        usort($ranked, function (array $a, array $b) {
            if ($a['score'] !== $b['score']) {
                return $a['score'] <=> $b['score'];
            }
            return $a['order'] <=> $b['order'];
        });

        return array_column($ranked, 'email');
    }

    // ==================== PIPELINE ====================

    /**
     * Steps 1-4 of the ladder. Split out of enrich() so every throw lands in one catch.
     */
    protected function runLadder(OutreachLead $lead): array
    {
        $website = $this->normalizeUrl((string) $lead->website);
        $facebook = $this->normalizeUrl((string) $lead->facebookUrl);

        // Columns discovered along the way, written back with the final result.
        $discovered = [];

        // ---- Step 1: no website on the lead, so go looking for one.
        if ($website === null) {
            if (!$this->settings->hasSearchKey()) {
                return $this->finish(
                    $lead,
                    null,
                    null,
                    OutreachLead::ENRICHMENT_SKIPPED,
                    'No website on this lead and no Google Custom Search key configured.'
                );
            }

            $links = $this->searchForLinks($lead);
            $website = $links['website'];
            $facebook = $facebook ?? $links['facebook'];

            if ($website !== null) {
                $discovered['website'] = $website;
            }
            if ($facebook !== null && empty($lead->facebookUrl)) {
                $discovered['facebookUrl'] = $facebook;
            }

            if ($website === null && $facebook === null) {
                return $this->finish(
                    $lead,
                    null,
                    null,
                    OutreachLead::ENRICHMENT_FAILED,
                    'Google Custom Search returned no website or Facebook page for this business.',
                    $discovered
                );
            }
        }

        // ---- Steps 2 and 3: fetch, then pattern match each page as it arrives.
        $urls = $this->candidateUrls($website, $facebook);
        $texts = [];
        $fetched = 0;

        foreach ($urls as $url) {
            $html = $this->fetchPage($url);
            if ($html === null) {
                continue;
            }
            $fetched++;

            $emails = $this->extractEmailsFromText($this->scannableHtml($html), $this->hostOf($url));
            if (!empty($emails)) {
                return $this->finish(
                    $lead,
                    $emails[0],
                    $this->isFacebookUrl($url) ? OutreachLead::SOURCE_FACEBOOK : OutreachLead::SOURCE_WEBSITE,
                    OutreachLead::ENRICHMENT_ENRICHED,
                    null,
                    $discovered
                );
            }

            $readable = $this->readableText($html);
            if ($readable !== '') {
                $texts[] = mb_substr($readable, 0, self::MAX_TEXT_CHARS);
            }
        }

        if ($fetched === 0) {
            return $this->finish(
                $lead,
                null,
                null,
                OutreachLead::ENRICHMENT_FAILED,
                'None of the candidate pages could be read (timeout, TLS failure or an error status).',
                $discovered
            );
        }

        if (empty($texts)) {
            return $this->finish(
                $lead,
                null,
                null,
                OutreachLead::ENRICHMENT_FAILED,
                'Pages loaded but carried no readable text and no email address.',
                $discovered
            );
        }

        // ---- Step 4: the paid pass, and only now.
        if (!$this->llm->isConfigured()) {
            return $this->finish(
                $lead,
                null,
                null,
                OutreachLead::ENRICHMENT_SKIPPED,
                'No email matched on the page and no LLM is configured to read it.',
                $discovered
            );
        }

        $email = $this->askLlmForEmail($lead, $texts);
        if ($email !== null) {
            return $this->finish(
                $lead,
                $email,
                OutreachLead::SOURCE_LLM,
                OutreachLead::ENRICHMENT_ENRICHED,
                null,
                $discovered
            );
        }

        return $this->finish(
            $lead,
            null,
            null,
            OutreachLead::ENRICHMENT_FAILED,
            'Read ' . $fetched . ' page(s); no business contact email found.',
            $discovered
        );
    }

    /**
     * Step 5: write the outcome to the lead and shape the return value.
     * enrichedAt is stamped only on a hit, so it never reads as "we have an email".
     */
    protected function finish(
        OutreachLead $lead,
        ?string $email,
        ?string $source,
        string $status,
        ?string $error,
        array $extra = []
    ): array {
        $payload = array_merge($extra, [
            'enrichmentStatus' => $status,
            'enrichmentError' => $error,
        ]);

        if ($email !== null) {
            $payload['email'] = $email;
            $payload['emailSource'] = $source;
            $payload['enrichedAt'] = Carbon::now('Asia/Manila');
        }

        $lead->update($payload);

        if ($email === null && $error !== null) {
            Log::warning('[OutreachEngine] Lead enrichment found nothing: ' . $error, [
                'leadId' => $lead->id,
                'usersId' => $lead->usersId,
                'status' => $status,
            ]);
        }

        return [
            'email' => $email,
            'source' => $email !== null ? $source : null,
            'error' => $error,
        ];
    }

    /**
     * Google Custom Search for the business's own site and Facebook page.
     * Looks at the top 3 results only - past that the answers are other businesses.
     *
     * @return array ['website' => ?string, 'facebook' => ?string]
     */
    protected function searchForLinks(OutreachLead $lead): array
    {
        $found = ['website' => null, 'facebook' => null];

        $key = (string) $this->settings->googleSearchApiKey;
        $engineId = trim((string) $this->settings->googleSearchEngineId);

        if ($key === '' || $engineId === '') {
            return $found;
        }

        $place = trim((string) ($lead->city ?: $lead->province));
        $query = '"' . trim((string) $lead->businessName) . '"';
        if ($place !== '') {
            $query .= ' "' . $place . '"';
        }
        $query .= ' official website OR facebook';

        try {
            $res = Http::timeout(self::FETCH_TIMEOUT)->get('https://www.googleapis.com/customsearch/v1', [
                'key' => $key,
                'cx' => $engineId,
                'q' => $query,
                'num' => 5,
            ]);

            if (!$res->successful()) {
                Log::warning('[OutreachEngine] Custom Search failed: ' . $res->status() . ' ' . mb_substr($res->body(), 0, 300), [
                    'leadId' => $lead->id,
                ]);
                return $found;
            }

            $items = (array) data_get($res->json(), 'items', []);
        } catch (\Throwable $e) {
            Log::warning('[OutreachEngine] Custom Search request failed: ' . $e->getMessage(), [
                'leadId' => $lead->id,
            ]);
            return $found;
        }

        $considered = 0;
        $fallback = null;

        foreach ($items as $item) {
            if ($considered >= 3) {
                break;
            }

            $link = $this->normalizeUrl((string) data_get($item, 'link', ''));
            if ($link === null) {
                continue;
            }
            $considered++;

            if ($this->isFacebookUrl($link)) {
                if ($found['facebook'] === null) {
                    $found['facebook'] = $link;
                }
                continue;
            }

            if ($found['website'] === null && !$this->isDirectoryUrl($link)) {
                $found['website'] = $link;
            } elseif ($fallback === null) {
                // An aggregator listing still often prints the address - better than nothing.
                $fallback = $link;
            }
        }

        if ($found['website'] === null && $found['facebook'] === null) {
            $found['website'] = $fallback;
        }

        return $found;
    }

    /**
     * The pages worth reading, in priority order, capped at MAX_PAGES.
     */
    protected function candidateUrls(?string $website, ?string $facebook): array
    {
        $urls = [];

        if ($website !== null) {
            $urls[] = $website;

            $origin = $this->originOf($website);
            if ($origin !== null) {
                foreach (self::CONTACT_PATHS as $path) {
                    $urls[] = $origin . $path;
                }
            }
        }

        if ($facebook !== null) {
            $urls[] = $facebook;
        }

        $unique = [];
        foreach ($urls as $url) {
            $keyed = strtolower(rtrim($url, '/'));
            if (!isset($unique[$keyed])) {
                $unique[$keyed] = $url;
            }
        }

        return array_slice(array_values($unique), 0, self::MAX_PAGES);
    }

    /**
     * Fetch one page. Any failure - DNS, TLS, timeout, 404, a PDF - is silent and
     * returns null: a broken site is an expected outcome here, not an incident.
     */
    protected function fetchPage(string $url): ?string
    {
        try {
            $res = Http::timeout(self::FETCH_TIMEOUT)
                ->withHeaders([
                    'User-Agent' => self::USER_AGENT,
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.9',
                    'Connection' => 'close',
                ])
                ->withOptions([
                    // Small business hosting is full of expired and mismatched certificates;
                    // we only read public marketing copy, so a bad chain must not stop us.
                    'verify' => false,
                    'allow_redirects' => [
                        'max' => 5,
                        'strict' => false,
                        'referer' => true,
                        'protocols' => ['http', 'https'],
                    ],
                    'curl' => [
                        CURLOPT_FORBID_REUSE => true,
                        CURLOPT_FRESH_CONNECT => true,
                    ],
                ])
                ->get($url);

            if (!$res->successful()) {
                return null;
            }

            $contentType = strtolower((string) $res->header('Content-Type'));
            if ($contentType !== ''
                && strpos($contentType, 'html') === false
                && strpos($contentType, 'text/plain') === false
                && strpos($contentType, 'xml') === false) {
                return null;
            }

            $body = (string) $res->body();

            return $body === '' ? null : $body;
        } catch (\Throwable $e) {
            Log::debug('[OutreachEngine] Page fetch failed for ' . $url . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Ask the model to read the page, once, after the free pass came back empty.
     */
    protected function askLlmForEmail(OutreachLead $lead, array $texts): ?string
    {
        $corpus = mb_substr(implode("\n\n---- next page ----\n\n", $texts), 0, self::MAX_LLM_CHARS);

        $system = 'You extract business contact emails. Reply with JSON only.';
        $user = 'Extract the official business contact email from this text for '
            . trim((string) $lead->businessName)
            . '. Return JSON: {"email": "string|null"}'
            . "\n\n" . $corpus;

        $answer = $this->llm->completeJson($system, $user, 200);

        $email = $answer['email'] ?? null;
        if (!is_string($email)) {
            return null;
        }

        // The model's answer goes through the same validation and junk filter as a
        // scraped one - "info@example.com" is its single favourite hallucination.
        return $this->cleanEmail($email);
    }

    // ==================== TEXT HANDLING ====================

    /**
     * HTML with the noisy blocks removed but the markup (and its mailto: hrefs) intact.
     */
    protected function scannableHtml(string $html): string
    {
        $cleaned = preg_replace('#<(script|style|noscript|svg|template)\b[^>]*>.*?</\1\s*>#is', ' ', $html);

        return $cleaned === null ? $html : $cleaned;
    }

    /**
     * HTML reduced to the text a person would see - what the model gets to read.
     */
    protected function readableText(string $html): string
    {
        $text = $this->scannableHtml($html);

        // Keep block boundaries as line breaks so an address does not fuse into a menu.
        $text = preg_replace('#<(?:br\s*/?|/p|/div|/li|/tr|/h[1-6]|/td)\s*>#i', "\n", $text) ?? $text;
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t\x{00A0}]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\n\s*\n\s*\n+/', "\n\n", $text) ?? $text;

        return trim($text);
    }

    /**
     * Turn the common hand-written obfuscations back into real addresses:
     * "name [at] domain [dot] com", "(at)", "{at}", "name at domain dot com",
     * and the numeric HTML entities for @ and . that survive in raw markup.
     */
    protected function deobfuscate(string $text): string
    {
        $out = str_ireplace(['&#64;', '&#x40;', '&commat;'], '@', $text);
        $out = str_ireplace(['&#46;', '&#x2e;', '&period;'], '.', $out);

        $rules = [
            '/\s*[\[\(\{]\s*(?:at|@)\s*[\]\)\}]\s*/i' => '@',
            '/\s*[\[\(\{]\s*(?:dot|\.)\s*[\]\)\}]\s*/i' => '.',
            // Bare word form. Both markers are required, which keeps ordinary prose
            // ("look at the dot") from being rewritten into a fake address.
            '/\b([A-Za-z0-9._%+\-]+)\s+at\s+([A-Za-z0-9\-]+(?:\s+dot\s+[A-Za-z0-9\-]+)*)\s+dot\s+([A-Za-z]{2,24})\b/i' => '$1@$2.$3',
        ];

        foreach ($rules as $pattern => $replacement) {
            $replaced = preg_replace($pattern, $replacement, $out);
            if ($replaced !== null) {
                $out = $replaced;
            }
        }

        // The middle group of the bare form may still hold " dot " separators.
        $collapsed = preg_replace('/@([A-Za-z0-9\-]+)\s+dot\s+/i', '@$1.', $out);
        if ($collapsed !== null) {
            $out = $collapsed;
        }

        return $out;
    }

    /**
     * Normalise one raw match into a storable address, or null if it is not one.
     */
    protected function cleanEmail(string $raw): ?string
    {
        $email = trim($raw);
        $email = (string) (preg_replace('/^(?:mailto:)+/i', '', $email) ?? $email);

        // Drop the querystring an href carried: mailto:info@x.com?subject=Hello
        $queryAt = strpos($email, '?');
        if ($queryAt !== false) {
            $email = substr($email, 0, $queryAt);
        }

        $email = trim($email, " \t\n\r\0\x0B<>\"'()[]{},;:");
        $email = rtrim($email, '.');
        $email = strtolower($email);

        if ($email === '' || substr_count($email, '@') !== 1) {
            return null;
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        return $this->isJunkEmail($email) ? null : $email;
    }

    /**
     * Placeholder copy, image filenames and vendor telemetry that look like addresses.
     */
    protected function isJunkEmail(string $email): bool
    {
        // "logo@2x.png" - a retina asset, never a mailbox.
        if (strpos($email, '@2x') !== false) {
            return true;
        }

        $at = strpos($email, '@');
        $local = substr($email, 0, $at);
        $domain = substr($email, $at + 1);

        foreach (self::IMAGE_EXTENSIONS as $extension) {
            if (str_ends_with($domain, '.' . $extension)) {
                return true;
            }
        }

        foreach (self::REJECT_DOMAINS as $rejected) {
            if ($domain === $rejected || str_ends_with($domain, '.' . $rejected)) {
                return true;
            }
        }

        foreach (self::REJECT_FRAGMENTS as $fragment) {
            if (strpos($domain, $fragment) !== false) {
                return true;
            }
        }

        // A long hex local part is a tracking id (Sentry DSNs, cache busters), not a person.
        if (strlen($local) >= 24 && preg_match('/^[0-9a-f]+$/', $local) === 1) {
            return true;
        }

        return false;
    }

    /**
     * Lower score wins: the site's own host, then its registrable domain, then a role
     * inbox, then everything else.
     */
    protected function rankEmail(string $email, ?string $preferHost): int
    {
        $at = strpos($email, '@');
        $local = substr($email, 0, $at);
        $domain = substr($email, $at + 1);

        if ($preferHost !== null && $preferHost !== '') {
            if ($domain === $preferHost) {
                return 0;
            }
            $registrable = $this->registrableDomain($domain);
            if ($registrable !== '' && $registrable === $this->registrableDomain($preferHost)) {
                return 1;
            }
        }

        if (in_array($local, self::ROLE_PREFIXES, true)) {
            return 2;
        }

        return 3;
    }

    // ==================== URL HELPERS ====================

    /**
     * A usable absolute http(s) URL, or null. Bare "example.com" gets https://.
     */
    protected function normalizeUrl(string $url): ?string
    {
        $url = trim($url);

        if ($url === '') {
            return null;
        }

        // Non-web schemes are never a page we can read.
        if (preg_match('/^(?:mailto|tel|sms|javascript|data|file|ftp):/i', $url) === 1) {
            return null;
        }

        if (strpos($url, '//') === 0) {
            $url = 'https:' . $url;
        } elseif (preg_match('#^https?://#i', $url) !== 1) {
            $url = 'https://' . ltrim($url, '/');
        }

        $host = parse_url($url, PHP_URL_HOST);

        // A host needs a dot; "https://localhost" style values are configuration noise.
        if (!is_string($host) || $host === '' || strpos($host, '.') === false) {
            return null;
        }

        return $url;
    }

    /**
     * scheme://host[:port] for the given URL, or null.
     */
    protected function originOf(string $url): ?string
    {
        $parts = parse_url($url);

        if (empty($parts['host'])) {
            return null;
        }

        $origin = ($parts['scheme'] ?? 'https') . '://' . $parts['host'];

        if (!empty($parts['port'])) {
            $origin .= ':' . $parts['port'];
        }

        return $origin;
    }

    /**
     * The comparable host of a URL: lower-cased, no www., no port.
     */
    protected function hostOf(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) ? $this->normalizeHost($host) : null;
    }

    /**
     * Accepts a bare host or a full URL and returns the comparable host.
     */
    protected function normalizeHost(?string $host): ?string
    {
        if ($host === null) {
            return null;
        }

        $host = trim($host);
        if ($host === '') {
            return null;
        }

        if (strpos($host, '/') !== false || strpos($host, ':') !== false) {
            $parsed = parse_url(preg_match('#^https?://#i', $host) === 1 ? $host : 'https://' . $host, PHP_URL_HOST);
            if (is_string($parsed) && $parsed !== '') {
                $host = $parsed;
            }
        }

        $host = strtolower(rtrim($host, '.'));
        $host = preg_replace('/^www\./', '', $host) ?? $host;

        return $host === '' ? null : $host;
    }

    /**
     * The registrable domain, honouring the two-label suffixes this market uses.
     */
    protected function registrableDomain(string $host): string
    {
        $host = (string) $this->normalizeHost($host);

        if ($host === '') {
            return '';
        }

        $labels = explode('.', $host);
        $count = count($labels);

        if ($count <= 2) {
            return $host;
        }

        $lastTwo = implode('.', array_slice($labels, -2));

        if (in_array($lastTwo, self::MULTI_LABEL_SUFFIXES, true)) {
            return implode('.', array_slice($labels, -3));
        }

        return $lastTwo;
    }

    /**
     * Is this a Facebook page URL?
     */
    protected function isFacebookUrl(string $url): bool
    {
        $host = $this->hostOf($url);

        if ($host === null) {
            return false;
        }

        return $host === 'facebook.com'
            || str_ends_with($host, '.facebook.com')
            || $host === 'fb.com'
            || $host === 'fb.me';
    }

    /**
     * Is this an aggregator or social profile rather than the business's own site?
     */
    protected function isDirectoryUrl(string $url): bool
    {
        $host = $this->hostOf($url);

        if ($host === null) {
            return true;
        }

        foreach (self::DIRECTORY_HOSTS as $directory) {
            if ($host === $directory || str_ends_with($host, '.' . $directory)) {
                return true;
            }
        }

        return false;
    }
}
