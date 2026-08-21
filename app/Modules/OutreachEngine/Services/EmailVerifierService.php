<?php

namespace App\Modules\OutreachEngine\Services;

use App\Modules\OutreachEngine\Models\OutreachLead;
use App\Modules\OutreachEngine\Models\OutreachSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Checks whether an address will actually accept mail, via Reoon Email Verifier.
 *
 * This runs after enrichment for one reason: a bounce is not free. It flips the
 * lead to 'bounced', and enough of them will cost the sending domain its
 * reputation, which is far more expensive to repair than a verification credit.
 *
 * Only a confirmed-deliverable address sets isEmailValid. Reoon's other verdicts
 * are recorded but not promoted:
 *
 *   catch_all     the server accepts everything, so the mailbox is unproven
 *   role_account  info@ / sales@ - real, but routinely unread and quick to
 *                 attract a spam complaint
 *   disposable    a throwaway domain
 *   disabled      the mailbox exists but is switched off
 *   inbox_full    real, but currently refusing mail
 *   spamtrap      never send - this is what burns a sending domain
 *   unknown       the server would not say
 *
 * Quick mode and power mode do not share a vocabulary. Quick answers valid /
 * invalid / disposable / spamtrap; power answers safe / invalid / disabled /
 * disposable / inbox_full / catch_all / role_account / spamtrap / unknown. The
 * success token differs - "valid" in quick, "safe" in power - which is why
 * GOOD_RESULTS holds both.
 *
 * They are kept rather than blanked so the same address is never paid for twice,
 * and so the rule can be widened later without re-verifying the whole table.
 *
 * The response parser is deliberately forgiving. Reoon returns a rich object and
 * the exact key set differs between quick and power mode, so the verdict is read
 * from `status` when it is there and reconstructed from the boolean flags when it
 * is not, rather than hard-failing on a shape change.
 */
class EmailVerifierService
{
    const ENDPOINT = 'https://emailverifier.reoon.com/api/v1/verify';

    const TIMEOUT_SECONDS = 30;

    /**
     * Verdicts that mean "send to this".
     *
     * Two tokens because Reoon uses a different vocabulary per mode, and the two
     * sets do not overlap: quick mode's success is "valid", power mode's is
     * "safe". Listing only one silently rejects every result from the other mode.
     *
     * Everything else is held back, which for power mode means catch_all,
     * role_account, disabled, inbox_full, spamtrap and unknown.
     */
    const GOOD_RESULTS = ['valid', 'safe'];

    /** Give up on an address after this many failed attempts. */
    const MAX_ATTEMPTS = 3;

    protected OutreachSetting $settings;

    public function __construct(OutreachSetting $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Is there a key to verify with, and is verification switched on?
     */
    public function isConfigured(): bool
    {
        return $this->verificationEnabled() && trim((string) $this->settings->reoonApiKey) !== '';
    }

    /**
     * Has the admin asked for verification at all?
     */
    public function verificationEnabled(): bool
    {
        return (bool) ($this->settings->verificationEnabled ?? true);
    }

    /**
     * Verify one address.
     *
     * Never throws - a verifier outage must not take down the cron tick that
     * called it.
     *
     * @return array{ok:bool,result:?string,valid:bool,raw:array,error:?string}
     */
    public function verify(string $email): array
    {
        $email = trim($email);

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // No need to spend a credit on something that cannot be an address.
            return ['ok' => true, 'result' => 'invalid', 'valid' => false, 'raw' => [], 'error' => null];
        }

        if (!$this->isConfigured()) {
            return [
                'ok' => false,
                'result' => null,
                'valid' => false,
                'raw' => [],
                'error' => 'No Reoon API key is configured.',
            ];
        }

        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)->get(self::ENDPOINT, [
                'email' => $email,
                'key' => (string) $this->settings->reoonApiKey,
                'mode' => $this->mode(),
            ]);

            if (!$response->successful()) {
                return [
                    'ok' => false,
                    'result' => null,
                    'valid' => false,
                    'raw' => [],
                    'error' => 'Verifier returned HTTP ' . $response->status(),
                ];
            }

            $body = $response->json();

            if (!is_array($body)) {
                return [
                    'ok' => false,
                    'result' => null,
                    'valid' => false,
                    'raw' => [],
                    'error' => 'Verifier returned a response that could not be read as JSON.',
                ];
            }

            // Reoon reports its own errors inside a 200 body.
            if (isset($body['error']) && $body['error']) {
                return [
                    'ok' => false,
                    'result' => null,
                    'valid' => false,
                    'raw' => $body,
                    'error' => is_string($body['error']) ? $body['error'] : 'Verifier reported an error.',
                ];
            }

            $result = $this->readVerdict($body);

            return [
                'ok' => true,
                'result' => $result,
                'valid' => in_array($result, self::GOOD_RESULTS, true),
                'raw' => $body,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            Log::warning('[OutreachEngine] Email verification failed: ' . $e->getMessage(), ['email' => $email]);

            return [
                'ok' => false,
                'result' => null,
                'valid' => false,
                'raw' => [],
                'error' => 'Could not reach the verifier: ' . mb_substr($e->getMessage(), 0, 200),
            ];
        }
    }

    /**
     * Verify one lead and write the outcome to it.
     *
     * A transport failure leaves the lead 'pending' so the next tick retries;
     * only a real verdict, or running out of attempts, settles it.
     *
     * @return array{ok:bool,result:?string,valid:bool,error:?string}
     */
    public function verifyLead(OutreachLead $lead): array
    {
        $lead->verificationStatus = OutreachLead::VERIFY_PROCESSING;
        $lead->verificationAttempts = (int) $lead->verificationAttempts + 1;
        $lead->save();

        $outcome = $this->verify((string) $lead->email);

        if (!$outcome['ok']) {
            $exhausted = (int) $lead->verificationAttempts >= self::MAX_ATTEMPTS;

            $lead->forceFill([
                'verificationStatus' => $exhausted ? OutreachLead::VERIFY_FAILED : OutreachLead::VERIFY_PENDING,
                'verificationError' => mb_substr((string) $outcome['error'], 0, 500),
            ])->save();

            return ['ok' => false, 'result' => null, 'valid' => false, 'error' => $outcome['error']];
        }

        $lead->forceFill([
            'verificationStatus' => OutreachLead::VERIFY_VERIFIED,
            'verificationResult' => mb_substr((string) $outcome['result'], 0, 40),
            'isEmailValid' => $outcome['valid'],
            'verifiedAt' => now('Asia/Manila'),
            'verificationError' => null,
        ])->save();

        return ['ok' => true, 'result' => $outcome['result'], 'valid' => $outcome['valid'], 'error' => null];
    }

    /**
     * Check the key without spending it on a real lead.
     *
     * Uses a syntactically valid address on a domain reserved by RFC 2606, so
     * the call exercises the key and the transport without touching anyone's
     * mail server.
     *
     * @return array{success:bool,message:string,raw:array}
     */
    public function testConnection(): array
    {
        if (trim((string) $this->settings->reoonApiKey) === '') {
            return ['success' => false, 'message' => 'Add your Reoon API key first.', 'raw' => []];
        }

        $outcome = $this->verify('test@example.com');

        if (!$outcome['ok']) {
            return ['success' => false, 'message' => (string) $outcome['error'], 'raw' => $outcome['raw']];
        }

        return [
            'success' => true,
            // The verdict itself does not matter here - a reply of any kind means
            // the key was accepted, which is the only thing being tested.
            'message' => 'Connected. The verifier answered "' . $outcome['result'] . '" for the test address.',
            'raw' => $outcome['raw'],
        ];
    }

    /**
     * Which Reoon mode to call.
     */
    public function mode(): string
    {
        $mode = strtolower(trim((string) ($this->settings->verifierMode ?? 'power')));

        return in_array($mode, ['quick', 'power'], true) ? $mode : 'power';
    }

    /**
     * Reduce Reoon's response to a single verdict word.
     *
     * `status` is the documented field, but the flags are read as a fallback so
     * a renamed or missing key degrades to a usable answer instead of throwing
     * the whole verification away.
     *
     * @param  array<string, mixed>  $body
     */
    protected function readVerdict(array $body): string
    {
        $status = strtolower(trim((string) ($body['status'] ?? '')));

        if ($status !== '') {
            // Documented values are already snake_cased; the replace only guards
            // against a hyphenated "catch-all" slipping through.
            return str_replace('-', '_', $status);
        }

        // No status field. Rebuild from the flags rather than discarding a paid
        // call - is_safe_to_send is power mode's own summary and is checked first.
        if (array_key_exists('is_safe_to_send', $body)) {
            return $body['is_safe_to_send'] ? 'safe' : 'invalid';
        }

        if (!empty($body['is_spamtrap'])) {
            return 'spamtrap';
        }

        if (!empty($body['is_disposable'])) {
            return 'disposable';
        }

        if (!empty($body['is_disabled'])) {
            return 'disabled';
        }

        if (!empty($body['has_inbox_full'])) {
            return 'inbox_full';
        }

        if (!empty($body['is_catch_all'])) {
            return 'catch_all';
        }

        if (!empty($body['is_role_account'])) {
            return 'role_account';
        }

        if (array_key_exists('is_deliverable', $body)) {
            return $body['is_deliverable'] ? 'safe' : 'invalid';
        }

        if (array_key_exists('is_valid_syntax', $body) && !$body['is_valid_syntax']) {
            return 'invalid';
        }

        return 'unknown';
    }
}
