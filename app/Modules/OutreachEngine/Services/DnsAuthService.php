<?php

namespace App\Modules\OutreachEngine\Services;

use Illuminate\Support\Facades\Log;

/**
 * Reads the four DNS records that decide whether cold outreach reaches an inbox or a
 * spam folder: SPF, DKIM, DMARC and MX.
 *
 * Written defensively on purpose. dns_get_record() emits PHP warnings on a slow or
 * broken resolver and can hand back `false` instead of an array, and shared hosting
 * frequently blocks the lookup outright. None of that may take down the settings
 * page, so every lookup is both @-suppressed and wrapped in try/catch, and a lookup
 * that cannot run is reported as "could not check" rather than as a failure the
 * operator will waste an afternoon chasing.
 *
 * Each result is a status the UI can colour plus one sentence a non-expert can act
 * on. No RFC quoting, no jargon without an explanation attached.
 */
class DnsAuthService
{
    const STATUS_PASS = 'pass';
    const STATUS_WARN = 'warn';
    const STATUS_FAIL = 'fail';

    /**
     * Run all four checks for a sending domain.
     *
     * Accepts a bare domain, a URL or a full email address, so a caller can hand it
     * the saved smtpFromEmail without picking it apart first.
     *
     * @param  string  $domain         Domain, URL or email address to inspect.
     * @param  string  $dkimSelector   DKIM selector to test, e.g. 'google', 's1', 'default'.
     * @return array<string,array{found:bool,record:?string,status:string,message:string}>
     */
    public function check(string $domain, string $dkimSelector = 'default'): array
    {
        $host = $this->normalizeDomain($domain);
        $selector = $this->normalizeSelector($dkimSelector);

        if ($host === '') {
            $message = 'No sending domain to check. Save a From address in the SMTP tab first.';

            return [
                'spf' => $this->result(false, null, self::STATUS_FAIL, $message),
                'dkim' => $this->result(false, null, self::STATUS_FAIL, $message),
                'dmarc' => $this->result(false, null, self::STATUS_FAIL, $message),
                'mx' => $this->result(false, null, self::STATUS_FAIL, $message),
            ];
        }

        return [
            'spf' => $this->checkSpf($host),
            'dkim' => $this->checkDkim($host, $selector),
            'dmarc' => $this->checkDmarc($host),
            'mx' => $this->checkMx($host),
        ];
    }

    /**
     * Domain part of an email address, or '' when there is not one.
     * Public because the settings screen derives the domain it displays from the
     * saved From address the same way.
     */
    public function domainFromEmail(string $email): string
    {
        return $this->normalizeDomain($email);
    }

    // ==================== INDIVIDUAL CHECKS ====================

    /**
     * SPF lists the servers allowed to send as this domain.
     *
     * Two records is a hard failure, not a warning: RFC 7208 says a receiver seeing
     * more than one v=spf1 record must return permerror, which in practice means every
     * message fails SPF - worse than having none at all.
     */
    protected function checkSpf(string $host): array
    {
        $lookup = $this->lookupTxt($host);

        if ($lookup === null) {
            return $this->unreadable('SPF', $host);
        }

        $records = array_values(array_filter($lookup, function ($txt) {
            return stripos($txt, 'v=spf1') === 0;
        }));

        if (empty($records)) {
            return $this->result(false, null, self::STATUS_FAIL,
                'No SPF record found on ' . $host . '. Add a TXT record listing your mail provider, '
                . 'for example "v=spf1 include:your-provider.com ~all". Without it most inboxes treat '
                . 'your mail as unverified.');
        }

        if (count($records) > 1) {
            return $this->result(true, implode(' | ', $records), self::STATUS_FAIL,
                'There are ' . count($records) . ' SPF records on ' . $host . ', and a domain may only '
                . 'have one. Receivers reject the lot when they see duplicates - merge them into a '
                . 'single TXT record.');
        }

        $record = $records[0];

        if (stripos($record, '+all') !== false) {
            return $this->result(true, $record, self::STATUS_WARN,
                'Your SPF record ends in "+all", which authorises the entire internet to send as '
                . $host . '. Change that ending to "~all" (soft fail) or "-all" (hard fail).');
        }

        // A redirect= modifier hands the whole policy to another domain (Google Workspace
        // publishes exactly "v=spf1 redirect=_spf.google.com"), so the absence of an
        // "all" ending here is correct rather than a mistake.
        $delegated = (bool) preg_match('/\bredirect\s*=/i', $record);

        if (!$delegated && !preg_match('/[~\-?]all\b/i', $record)) {
            return $this->result(true, $record, self::STATUS_WARN,
                'An SPF record exists but has no "all" ending, so receivers get no instruction about '
                . 'unlisted servers. Append "~all" to the end of the record.');
        }

        // Each include/a/mx/ptr/exists costs one DNS lookup and the limit is 10; past
        // that SPF permerrors and every message silently loses its authentication.
        $mechanisms = preg_match_all('/\b(include|a|mx|ptr|exists|redirect)[:=]/i', $record);

        if ($mechanisms > 10) {
            return $this->result(true, $record, self::STATUS_WARN,
                'Your SPF record uses about ' . $mechanisms . ' lookups and the limit is 10. Past the '
                . 'limit SPF stops working entirely - remove providers you no longer send through.');
        }

        if ($delegated) {
            return $this->result(true, $record, self::STATUS_PASS,
                'SPF is published and hands the policy to another domain with "redirect=", which is how '
                . 'Google Workspace and similar providers set it up. Nothing to change.');
        }

        return $this->result(true, $record, self::STATUS_PASS,
            'SPF is published and tells receivers what to do with mail from servers you have not '
            . 'listed. Make sure the server you send through is included.');
    }

    /**
     * DKIM signs each message with a key published under {selector}._domainkey.
     *
     * A missing selector is only a warning: providers each pick their own selector
     * name (google, s1, k1, mail...) so "not found" often means "wrong selector typed",
     * not "no DKIM". An empty p= tag, on the other hand, is a published revocation.
     */
    protected function checkDkim(string $host, string $selector): array
    {
        $name = $selector . '._domainkey.' . $host;
        $lookup = $this->lookupTxt($name);

        if ($lookup === null) {
            return $this->unreadable('DKIM', $name);
        }

        $records = array_values(array_filter($lookup, function ($txt) {
            return stripos($txt, 'v=DKIM1') !== false || stripos($txt, 'p=') !== false;
        }));

        if (empty($records)) {
            return $this->result(false, null, self::STATUS_WARN,
                'No DKIM key found at ' . $name . '. Either DKIM is not set up, or your provider uses a '
                . 'different selector - check your mail provider for the exact selector name and test '
                . 'again with it.');
        }

        $record = $records[0];

        if (preg_match('/\bp=\s*(;|$)/i', $record)) {
            return $this->result(true, $record, self::STATUS_FAIL,
                'The DKIM record at ' . $name . ' has an empty public key, which publicly revokes the '
                . 'key. Re-publish the full key value from your mail provider.');
        }

        if (stripos($record, 'p=') === false) {
            return $this->result(true, $record, self::STATUS_FAIL,
                'The record at ' . $name . ' exists but carries no public key ("p=" tag). Replace it '
                . 'with the complete value your mail provider gives you.');
        }

        return $this->result(true, $record, self::STATUS_PASS,
            'A DKIM key is published for the "' . $selector . '" selector, so your provider can sign '
            . 'messages and prove they were not altered in transit.');
    }

    /**
     * DMARC ties SPF and DKIM together and tells receivers what to do on failure.
     * p=none publishes a policy but enforces nothing - useful for collecting reports,
     * not enough to protect the domain, hence a warning rather than a pass.
     */
    protected function checkDmarc(string $host): array
    {
        $name = '_dmarc.' . $host;
        $lookup = $this->lookupTxt($name);

        if ($lookup === null) {
            return $this->unreadable('DMARC', $name);
        }

        $records = array_values(array_filter($lookup, function ($txt) {
            return stripos($txt, 'v=DMARC1') === 0;
        }));

        if (empty($records)) {
            return $this->result(false, null, self::STATUS_FAIL,
                'No DMARC record found at ' . $name . '. Add a TXT record such as '
                . '"v=DMARC1; p=none; rua=mailto:you@' . $host . '" to start receiving reports, then '
                . 'tighten it once the reports look clean. Gmail and Yahoo now expect bulk senders to '
                . 'have one.');
        }

        $record = $records[0];
        $policy = 'none';

        if (preg_match('/\bp\s*=\s*([a-z]+)/i', $record, $matches)) {
            $policy = strtolower($matches[1]);
        }

        if ($policy === 'reject' || $policy === 'quarantine') {
            return $this->result(true, $record, self::STATUS_PASS,
                'DMARC is enforcing with policy "' . $policy . '", so mail that fails SPF and DKIM is '
                . 'refused or quarantined instead of landing in your customers\' inboxes as a forgery.');
        }

        return $this->result(true, $record, self::STATUS_WARN,
            'DMARC is published but the policy is "' . $policy . '", which asks receivers to take no '
            . 'action on failures. Once your reports look clean, move it to "p=quarantine" and then '
            . '"p=reject".');
    }

    /**
     * MX says where replies go. Outreach without a working MX means every reply the
     * campaign earns bounces straight back to the prospect.
     */
    protected function checkMx(string $host): array
    {
        $records = $this->lookupMx($host);

        if ($records === null) {
            return $this->unreadable('MX', $host);
        }

        if (empty($records)) {
            return $this->result(false, null, self::STATUS_FAIL,
                'No MX record found for ' . $host . ', so this domain cannot receive email. Replies to '
                . 'your outreach will bounce - point MX at your mail provider before sending.');
        }

        return $this->result(true, implode(', ', $records), self::STATUS_PASS,
            'Mail for ' . $host . ' is routed to ' . count($records) . ' server'
            . (count($records) === 1 ? '' : 's') . ', so replies have somewhere to land.');
    }

    // ==================== LOOKUP PLUMBING ====================

    /**
     * TXT strings for a name, or null when the lookup itself could not be performed.
     *
     * A long TXT record arrives split into 255-character chunks in 'entries'; those
     * must be joined before matching or a long SPF record never matches its own tags.
     *
     * @return array<int,string>|null
     */
    protected function lookupTxt(string $name): ?array
    {
        $records = $this->rawLookup($name, DNS_TXT);

        if ($records === null) {
            return null;
        }

        $values = [];

        foreach ($records as $record) {
            if (!empty($record['entries']) && is_array($record['entries'])) {
                $values[] = trim(implode('', $record['entries']));
            } elseif (isset($record['txt'])) {
                $values[] = trim((string) $record['txt']);
            }
        }

        return array_values(array_filter($values, function ($value) {
            return $value !== '';
        }));
    }

    /**
     * Mail exchangers sorted by preference, or null when the lookup could not run.
     *
     * @return array<int,string>|null
     */
    protected function lookupMx(string $name): ?array
    {
        $records = $this->rawLookup($name, DNS_MX);

        if ($records === null) {
            return null;
        }

        usort($records, function ($a, $b) {
            return ((int) ($a['pri'] ?? 0)) <=> ((int) ($b['pri'] ?? 0));
        });

        $hosts = [];

        foreach ($records as $record) {
            $target = trim((string) ($record['target'] ?? ''));
            if ($target !== '') {
                $hosts[] = $target;
            }
        }

        return $hosts;
    }

    /**
     * The only place dns_get_record() is called.
     *
     * @-suppressed AND caught: the function warns on a resolver timeout, returns false
     * on failure, and throws on some PHP builds when the query type is unsupported.
     * `null` means "we could not ask", which is deliberately different from `[]`,
     * which means "we asked and the record is genuinely absent".
     *
     * @return array<int,array>|null
     */
    protected function rawLookup(string $name, int $type): ?array
    {
        try {
            $records = @dns_get_record($name, $type);

            if ($records === false || !is_array($records)) {
                return null;
            }

            return $records;
        } catch (\Throwable $e) {
            Log::warning('[OutreachEngine] DNS lookup failed for ' . $name . ': ' . $e->getMessage());

            return null;
        }
    }

    // ==================== HELPERS ====================

    /**
     * Reduce whatever the caller passed - domain, URL or email address - to a bare
     * hostname suitable for a DNS query.
     */
    protected function normalizeDomain(string $value): string
    {
        $host = strtolower(trim($value));

        if ($host === '') {
            return '';
        }

        // Strip a scheme and anything after the authority, so a pasted website URL works.
        $host = preg_replace('#^[a-z][a-z0-9+.\-]*://#i', '', $host);
        $host = (string) $host;
        $host = explode('/', $host)[0];
        $host = explode('?', $host)[0];

        // An email address (or a "Name <a@b>" fragment) reduces to its domain part.
        if (strpos($host, '@') !== false) {
            $host = substr(strrchr($host, '@'), 1);
        }

        $host = trim((string) $host, " \t<>.");
        $host = explode(':', $host)[0]; // drop a :port

        // Only a plausible hostname may reach the resolver.
        if (!preg_match('/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9\-]*[a-z0-9])?)+$/i', $host)) {
            return '';
        }

        return $host;
    }

    /**
     * A DKIM selector is a DNS label; anything else would build a nonsense query name.
     */
    protected function normalizeSelector(string $selector): string
    {
        $clean = strtolower(trim($selector));
        $clean = preg_replace('/[^a-z0-9\-_.]/', '', $clean);
        $clean = trim((string) $clean, '.');

        return $clean === '' ? 'default' : $clean;
    }

    /**
     * Shared answer for "the resolver would not tell us". Reported as a warning, never
     * a failure - the records may well be perfect and the server simply unable to ask.
     */
    protected function unreadable(string $label, string $name): array
    {
        return $this->result(false, null, self::STATUS_WARN,
            'Could not read ' . $label . ' for ' . $name . '. This server\'s DNS lookups are failing or '
            . 'blocked, so the record itself may be fine - verify it with an external DNS checker.');
    }

    /**
     * @return array{found:bool,record:?string,status:string,message:string}
     */
    protected function result(bool $found, ?string $record, string $status, string $message): array
    {
        return [
            'found' => $found,
            'record' => $record,
            'status' => $status,
            'message' => $message,
        ];
    }
}
