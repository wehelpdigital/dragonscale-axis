<?php

namespace App\Modules\OutreachEngine\Services;

use App\Modules\OutreachEngine\Models\OutreachSetting;
use Illuminate\Support\Facades\Log;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Every outbound email the module produces goes through here - campaign sends from
 * the cron and quick replies from the inbox alike.
 *
 * Mirrors the house PHPMailer usage in
 * app/Http/Controllers/aniSensoAdmin/MailSettingsController.php: PHPMailer(true) so
 * transport problems arrive as exceptions, the same Host / Port / CharSet / auth /
 * encryption order, and $mail->ErrorInfo - not the exception message - as the
 * operator-facing reason, because ErrorInfo carries the actual SMTP dialogue while
 * the exception only carries a generic label.
 *
 * Two things this adds over that reference:
 *  - We mint the RFC 5322 Message-ID ourselves and return it. The inbox threads
 *    replies by matching a reply's In-Reply-To / References against
 *    outreach_email_logs.messageId, so letting PHPMailer invent one at header-build
 *    time would leave us nothing to store and no way to match anything later.
 *  - testConnection() is connect + authenticate only. It must never put a message on
 *    the wire; a settings page that mails somebody every time an admin clicks "Test"
 *    is how a fresh sending domain gets itself flagged.
 */
class SmtpMailerService
{
    /**
     * PHPMailer defaults to a 300 second socket timeout. That is far too long for a
     * per-minute cron or for a settings page holding a request open on a test, so a
     * dead host fails fast instead of wedging the queue.
     */
    const SMTP_TIMEOUT_SECONDS = 30;

    /** @var \App\Modules\OutreachEngine\Models\OutreachSetting */
    protected $settings;

    public function __construct(OutreachSetting $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Enough SMTP detail on file to attempt a send.
     */
    public function isConfigured(): bool
    {
        return $this->settings->smtpConfigured();
    }

    /**
     * Deliver one HTML email.
     *
     * @param  string       $toEmail    Recipient address.
     * @param  string       $toName     Recipient display name (may be empty).
     * @param  string       $subject    Already-rendered subject line.
     * @param  string       $htmlBody   Already-rendered HTML body.
     * @param  string|null  $inReplyTo  Message-ID we are replying to, if any.
     * @return array{success:bool,messageId:?string,response:?string,error:?string}
     */
    public function send(string $toEmail, string $toName, string $subject, string $htmlBody, ?string $inReplyTo = null): array
    {
        if (!$this->isConfigured()) {
            return $this->failure('SMTP is not configured. Add a host, username, password and From address in Settings.');
        }

        $toEmail = trim($toEmail);

        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return $this->failure('Recipient address is not a valid email: ' . $toEmail);
        }

        $messageId = $this->generateMessageId();
        $mail = new PHPMailer(true);

        try {
            $this->configureTransport($mail);

            $mail->setFrom(
                $this->settings->smtpFromEmail,
                $this->settings->smtpFromName ?: $this->settings->smtpFromEmail
            );
            $mail->addAddress($toEmail, $this->headerSafe($toName));

            // Replies have to land in the mailbox IMAP polls, which is the From
            // address unless the operator deliberately sends from a no-reply alias.
            $mail->addReplyTo(
                $this->settings->smtpFromEmail,
                $this->settings->smtpFromName ?: $this->settings->smtpFromEmail
            );

            // Set before send(): createHeader() only honours a custom MessageID that
            // matches RFC 5322 3.6.4, otherwise it silently substitutes its own.
            $mail->MessageID = $messageId;

            $threadRef = $this->normalizeMessageId($inReplyTo);
            if ($threadRef !== null) {
                $mail->addCustomHeader('In-Reply-To', $threadRef);
                $mail->addCustomHeader('References', $threadRef);
            }

            $mail->isHTML(true);
            $mail->Subject = $this->headerSafe($subject);
            $mail->Body = $htmlBody;
            $mail->AltBody = $this->toPlainText($htmlBody);

            $mail->send();

            // getLastMessageID() is the id that actually went out - had PHPMailer
            // rejected ours it would differ, and the log must record what was sent.
            $sentId = $mail->getLastMessageID() ?: $messageId;
            $response = $this->lastSmtpReply($mail);

            $this->closeQuietly($mail);

            return [
                'success' => true,
                'messageId' => $sentId,
                'response' => $response,
                'error' => null,
            ];
        } catch (PHPMailerException $e) {
            $reason = $mail->ErrorInfo ?: $e->getMessage();

            Log::error('[OutreachEngine] SMTP send failed', [
                'to' => $toEmail,
                'host' => $this->settings->smtpHost,
                'error' => $reason,
            ]);

            $this->closeQuietly($mail);

            return $this->failure($reason);
        } catch (\Exception $e) {
            Log::error('[OutreachEngine] SMTP send crashed: ' . $e->getMessage(), [
                'to' => $toEmail,
                'host' => $this->settings->smtpHost,
            ]);

            $this->closeQuietly($mail);

            return $this->failure($e->getMessage());
        }
    }

    /**
     * Open an SMTP session, authenticate, hang up. No message is composed and nothing
     * is delivered - this is purely "are these credentials good?".
     *
     * @return array{success:bool,error:?string}
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'SMTP is not configured. Add a host, username, password and From address first.',
            ];
        }

        $mail = new PHPMailer(true);

        try {
            $this->configureTransport($mail);

            // smtpConnect() runs the whole greeting -> STARTTLS -> AUTH handshake and
            // throws on the first step that fails, which is exactly the diagnosis the
            // settings page wants to show.
            $mail->smtpConnect();

            return ['success' => true, 'error' => null];
        } catch (PHPMailerException $e) {
            $reason = $mail->ErrorInfo ?: $e->getMessage();

            Log::warning('[OutreachEngine] SMTP connection test failed', [
                'host' => $this->settings->smtpHost,
                'port' => $this->settings->smtpPort,
                'error' => $reason,
            ]);

            return ['success' => false, 'error' => $reason];
        } catch (\Exception $e) {
            Log::warning('[OutreachEngine] SMTP connection test crashed: ' . $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        } finally {
            // Always hang up, including after a mid-handshake failure, so we never
            // leave a half-open session sitting against the provider.
            $this->closeQuietly($mail);
        }
    }

    // ==================== INTERNALS ====================

    /**
     * Host, port, auth and encryption - shared by send() and testConnection() so a
     * passing test can never describe a different configuration than the real send.
     */
    protected function configureTransport(PHPMailer $mail): void
    {
        $mail->isSMTP();
        $mail->Host = $this->settings->smtpHost;
        $mail->Port = (int) $this->settings->smtpPort;
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->Timeout = self::SMTP_TIMEOUT_SECONDS;
        $mail->SMTPDebug = 0;

        if ($this->settings->smtpUsername) {
            $mail->SMTPAuth = true;
            $mail->Username = $this->settings->smtpUsername;
            $mail->Password = $this->settings->smtpPassword; // decrypted by the model accessor
        }

        $encryption = $this->settings->smtpEncryption;

        if ($encryption === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($encryption === 'none') {
            // SMTPAutoTLS has to go off as well. Left on, PHPMailer upgrades the
            // session the moment the server advertises STARTTLS, so a deliberately
            // plain relay (a local Postfix, a catch-all dev mailbox) breaks on a
            // certificate the operator never asked anyone to present.
            $mail->SMTPSecure = '';
            $mail->SMTPAutoTLS = false;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }
    }

    /**
     * Build an RFC 5322 Message-ID we control: <uniqid.time@fromdomain>.
     *
     * The domain comes from the From address so the id looks native to the sending
     * domain. Anything that is not a clean dot-atom falls back to a literal, because
     * PHPMailer discards a malformed custom id and threading would break silently.
     */
    protected function generateMessageId(): string
    {
        $domain = '';
        $from = (string) $this->settings->smtpFromEmail;

        if (strpos($from, '@') !== false) {
            $domain = strtolower(trim(substr(strrchr($from, '@'), 1)));
        }

        $dotAtom = '/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9\-]*[a-z0-9])?)+$/i';

        if ($domain === '' || !preg_match($dotAtom, $domain)) {
            $domain = 'outreach.local';
        }

        return sprintf('<%s.%d@%s>', uniqid(), time(), $domain);
    }

    /**
     * Normalise a stored Message-ID for the In-Reply-To / References headers: angle
     * brackets guaranteed, line breaks and stray whitespace gone. The value came off
     * an inbound email, so it is untrusted - unfiltered CRLF here is a header
     * injection vector (and PHPMailer would throw on it anyway, killing the reply).
     */
    protected function normalizeMessageId(?string $raw): ?string
    {
        $value = trim((string) $raw);

        if ($value === '') {
            return null;
        }

        $value = preg_replace('/[\r\n\t]+/', '', $value);
        $value = trim((string) $value, " <>\t");

        if ($value === '' || strpos($value, '@') === false) {
            return null;
        }

        // Keep only characters legal inside a msg-id; anything else is a red flag.
        if (preg_match('/[^A-Za-z0-9!#$%&\'*+\/=?^_`{|}~.\-@]/', $value)) {
            return null;
        }

        return '<' . $value . '>';
    }

    /**
     * Readable plain-text alternative: block-level tags become newlines first so the
     * fallback part is not one unbroken wall of a paragraph.
     */
    protected function toPlainText(string $html): string
    {
        $text = preg_replace('/<(br|\/p|\/div|\/tr|\/h[1-6])[^>]*>/i', "\n", $html);
        $text = strip_tags((string) $text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t]+/", ' ', $text);
        $text = preg_replace("/\n{3,}/", "\n\n", (string) $text);

        return trim((string) $text);
    }

    /**
     * Strip line breaks from a value headed for a header. PHPMailer sanitises the
     * headers it owns, but a name or subject built from scraped lead data should
     * never get that far carrying a newline in the first place.
     */
    protected function headerSafe(string $value): string
    {
        return trim((string) preg_replace('/[\r\n]+/', ' ', $value));
    }

    /**
     * The server's final reply line, stored on the log row for the inevitable "did it
     * actually leave?" question. Best-effort - never let it break a good send.
     */
    protected function lastSmtpReply(PHPMailer $mail): ?string
    {
        try {
            $reply = trim((string) $mail->getSMTPInstance()->getLastReply());

            return $reply === '' ? null : $reply;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Hang up without letting the teardown itself throw.
     */
    protected function closeQuietly(PHPMailer $mail): void
    {
        try {
            $mail->smtpClose();
        } catch (\Exception $e) {
            // Nothing useful to do here - the session is going away regardless.
        }
    }

    /**
     * Uniform failure shape for send().
     *
     * @return array{success:bool,messageId:?string,response:?string,error:?string}
     */
    protected function failure(string $error): array
    {
        return [
            'success' => false,
            'messageId' => null,
            'response' => null,
            'error' => $error,
        ];
    }
}
