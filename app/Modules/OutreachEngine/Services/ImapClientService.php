<?php

namespace App\Modules\OutreachEngine\Services;

use App\Modules\OutreachEngine\Models\OutreachSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * A minimal IMAP4rev1 client written directly on a socket.
 *
 * ext-imap is NOT installed on this server and no composer package may be added, so
 * everything below - connection, TLS, the tagged command protocol, literal handling
 * and the MIME parser - is hand rolled against RFC 3501 and RFC 2045/2047.
 *
 * Two rules shape the whole class:
 *
 *  1. It NEVER throws. The poller runs from cron every five minutes against a mailbox
 *     we do not control; a dead server must degrade to "nothing fetched", not to a
 *     fatal that kills the scheduler run. Every entry point returns [] or
 *     ['success' => false, ...] and records the cause in lastError().
 *  2. It reads with BODY.PEEK[] so the server-side \Seen flag never changes. The
 *     mailbox stays exactly as the operator left it and outreach_inbound_messages
 *     owns read state instead - which is also why every poll re-sees the same UIDs
 *     and why (usersId, messageUid) is unique.
 */
class ImapClientService
{
    /** Socket read/write timeout in seconds - also the ceiling for one literal read. */
    const SOCKET_TIMEOUT = 20;

    /** A single protocol line longer than this means a confused stream; stop reading it. */
    const MAX_LINE_BYTES = 1048576;

    /** Bytes of one message we keep. Anything larger is drained (to stay in protocol sync) and truncated. */
    const MAX_MESSAGE_BYTES = 2097152;

    /** Nested multipart depth guard - real mail never goes past three or four. */
    const MAX_MIME_DEPTH = 10;

    /** Hard ceiling on UIDs pulled in one poll, whatever the caller asks for. */
    const MAX_UIDS = 200;

    /** Response lines to read before we assume the server has stopped talking sense. */
    const MAX_RESPONSE_LINES = 20000;

    protected OutreachSetting $settings;

    /** @var resource|null The live socket, or null when disconnected. */
    private $socket = null;

    /** Monotonic command tag counter - A001, A002, ... */
    private int $tagCounter = 0;

    /** Why the last call failed, for the caller's error report. */
    private ?string $lastError = null;

    /** UIDVALIDITY of the selected mailbox, captured from the SELECT response. */
    private ?string $uidValidity = null;

    public function __construct(OutreachSetting $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Enough IMAP detail stored to attempt a login?
     */
    public function isConfigured(): bool
    {
        return $this->settings->imapConfigured();
    }

    /**
     * Why the last fetchUnseen()/testConnection() failed, or null if it did not.
     *
     * fetchUnseen() returns [] both for "mailbox is empty" and for "the server refused
     * us", so InboundProcessor reads this to tell the two apart.
     */
    public function lastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Connect, authenticate and open the folder - no message is fetched.
     *
     * @return array ['success' => bool, 'error' => ?string]
     */
    public function testConnection(): array
    {
        $this->lastError = null;

        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'IMAP is not configured. Fill in host, port, username and password first.'];
        }

        try {
            $opened = $this->open();
            if (!$opened['success']) {
                return ['success' => false, 'error' => $opened['error']];
            }

            $selected = $this->selectFolder();
            if (!$selected['success']) {
                return ['success' => false, 'error' => $selected['error']];
            }

            return ['success' => true, 'error' => null];
        } catch (\Throwable $e) {
            Log::error('[OutreachEngine] IMAP test connection crashed: ' . $e->getMessage());

            return ['success' => false, 'error' => 'IMAP connection failed: ' . $e->getMessage()];
        } finally {
            $this->close();
        }
    }

    /**
     * Fetch the unseen messages, oldest first.
     *
     * @return array<int,array<string,mixed>> Each entry:
     *   uid, uidValidity, messageId, inReplyTo, references, from, fromName,
     *   subject, text, html, date ('Y-m-d H:i:s' Asia/Manila), isBounce
     */
    public function fetchUnseen(int $limit = 50): array
    {
        $this->lastError = null;

        if (!$this->isConfigured()) {
            return $this->fail('IMAP is not configured.', []);
        }

        $limit = max(1, min($limit, self::MAX_UIDS));

        try {
            $opened = $this->open();
            if (!$opened['success']) {
                return $this->fail($opened['error'], []);
            }

            $selected = $this->selectFolder();
            if (!$selected['success']) {
                return $this->fail($selected['error'], []);
            }

            $uids = $this->searchUnseen();
            if (empty($uids)) {
                return [];
            }

            // Keep the newest UIDs when the backlog is larger than the caller's budget;
            // the older ones stay unread server-side and come back on the next poll.
            if (count($uids) > $limit) {
                $uids = array_slice($uids, -$limit);
            }

            $messages = [];
            foreach ($uids as $uid) {
                $raw = $this->fetchRaw($uid);
                if ($raw === null) {
                    continue;
                }

                $messages[] = $this->parseRaw($raw, $uid);
            }

            return $messages;
        } catch (\Throwable $e) {
            Log::error('[OutreachEngine] IMAP fetch crashed: ' . $e->getMessage());

            return $this->fail('IMAP fetch failed: ' . $e->getMessage(), []);
        } finally {
            $this->close();
        }
    }

    /**
     * Parse a raw RFC 5322 blob into the same shape fetchUnseen() returns.
     *
     * Public so the inbound webhook can hand a forwarded raw message straight to the
     * same parser instead of duplicating MIME handling.
     */
    public function parseRaw(string $raw, string $uid = ''): array
    {
        [$headerBlock, $bodyBlock] = $this->splitHeadersAndBody($raw);
        $headers = $this->parseHeaders($headerBlock);

        $sender = $this->parseAddress($headers['from'] ?? '');
        $subject = $this->decodeHeaderText((string) ($headers['subject'] ?? ''));

        $collected = ['text' => [], 'html' => []];
        $this->walkPart($headers, $bodyBlock, $collected, 0);

        $text = trim(implode("\n\n", array_filter($collected['text'], 'strlen')));
        $html = trim(implode("\n", array_filter($collected['html'], 'strlen')));

        return [
            'uid' => (string) $uid,
            'uidValidity' => $this->uidValidity,
            'messageId' => $this->trimAngles($headers['message-id'] ?? null),
            'inReplyTo' => $this->trimAngles($headers['in-reply-to'] ?? null),
            'references' => isset($headers['references']) ? trim((string) $headers['references']) : null,
            'from' => $sender['email'],
            'fromName' => $sender['name'],
            'subject' => $subject,
            'text' => $text !== '' ? $text : null,
            'html' => $html !== '' ? $html : null,
            'date' => $this->normalizeDate($headers['date'] ?? null),
            'isBounce' => $this->looksLikeBounce($headers, $sender['email'], $subject),
        ];
    }

    // ==================== CONNECTION ====================

    /**
     * Open the socket, greet, upgrade to TLS when asked, and LOGIN.
     *
     * @return array ['success' => bool, 'error' => ?string]
     */
    private function open(): array
    {
        $host = trim((string) $this->settings->imapHost);
        $port = (int) $this->settings->imapPort;
        $encryption = strtolower(trim((string) $this->settings->imapEncryption));

        if ($port <= 0) {
            $port = $encryption === 'ssl' ? 993 : 143;
        }

        // 'ssl' means implicit TLS from the first byte; 'tls' means connect in the
        // clear on 143 and upgrade with STARTTLS; 'none' stays plaintext.
        $transport = $encryption === 'ssl' ? 'ssl://' : 'tcp://';

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'SNI_enabled' => true,
            ],
        ]);

        $errno = 0;
        $errstr = '';

        $socket = @stream_socket_client(
            $transport . $host . ':' . $port,
            $errno,
            $errstr,
            self::SOCKET_TIMEOUT,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$socket) {
            $reason = trim($errstr) !== '' ? trim($errstr) : 'unknown socket error';

            return ['success' => false, 'error' => 'Could not reach ' . $host . ':' . $port . ' (' . $reason . ')'];
        }

        $this->socket = $socket;
        stream_set_timeout($this->socket, self::SOCKET_TIMEOUT);

        // The server speaks first: "* OK ..." or "* PREAUTH ...".
        $greeting = $this->readLine();
        if ($greeting === null || !preg_match('/^\*\s+(OK|PREAUTH)/i', trim($greeting))) {
            return ['success' => false, 'error' => 'The IMAP server refused the connection: ' . trim((string) $greeting)];
        }

        if ($encryption === 'tls') {
            $upgraded = $this->startTls();
            if (!$upgraded['success']) {
                return $upgraded;
            }
        }

        return $this->login();
    }

    /**
     * STARTTLS then hand the stream to OpenSSL. Everything after this point is encrypted.
     *
     * @return array ['success' => bool, 'error' => ?string]
     */
    private function startTls(): array
    {
        $response = $this->command('STARTTLS');
        if (!$response['ok']) {
            return ['success' => false, 'error' => 'STARTTLS refused: ' . $response['message']];
        }

        // TLS_CLIENT still allows 1.0/1.1 on some builds; prefer the modern methods
        // when the running OpenSSL exposes them.
        $method = STREAM_CRYPTO_METHOD_TLS_CLIENT;
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
            $method = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
                $method |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
            }
        }

        $enabled = @stream_socket_enable_crypto($this->socket, true, $method);
        if ($enabled !== true) {
            return ['success' => false, 'error' => 'The TLS handshake failed after STARTTLS.'];
        }

        return ['success' => true, 'error' => null];
    }

    /**
     * Authenticate with a quoted LOGIN.
     *
     * @return array ['success' => bool, 'error' => ?string]
     */
    private function login(): array
    {
        $username = (string) $this->settings->imapUsername;
        $password = (string) $this->settings->imapPassword;

        $response = $this->command('LOGIN ' . $this->quote($username) . ' ' . $this->quote($password));

        if (!$response['ok']) {
            $detail = $response['message'] !== '' ? $response['message'] : 'the server rejected the credentials';

            return ['success' => false, 'error' => 'IMAP login failed: ' . $detail];
        }

        return ['success' => true, 'error' => null];
    }

    /**
     * SELECT the configured folder and remember its UIDVALIDITY.
     *
     * @return array ['success' => bool, 'error' => ?string]
     */
    private function selectFolder(): array
    {
        $folder = trim((string) $this->settings->imapFolder);
        if ($folder === '') {
            $folder = 'INBOX';
        }

        $response = $this->command('SELECT ' . $this->quote($folder));

        if (!$response['ok']) {
            $detail = $response['message'] !== '' ? $response['message'] : 'folder not found';

            return ['success' => false, 'error' => 'Could not open the folder "' . $folder . '": ' . $detail];
        }

        foreach ($response['lines'] as $line) {
            if (preg_match('/UIDVALIDITY\s+(\d+)/i', $line, $matches)) {
                $this->uidValidity = $matches[1];
                break;
            }
        }

        return ['success' => true, 'error' => null];
    }

    /**
     * UIDs of the unseen messages, ascending.
     *
     * @return array<int,string>
     */
    private function searchUnseen(): array
    {
        $response = $this->command('UID SEARCH UNSEEN');

        if (!$response['ok']) {
            $this->fail('IMAP search failed: ' . $response['message'], null);

            return [];
        }

        $uids = [];
        foreach ($response['lines'] as $line) {
            if (!preg_match('/^\*\s+SEARCH\b(.*)$/i', trim($line), $matches)) {
                continue;
            }

            foreach (preg_split('/\s+/', trim($matches[1])) as $token) {
                // ctype_digit doubles as the injection guard: the UID is spliced
                // straight into the next FETCH command line.
                if ($token !== '' && ctype_digit($token)) {
                    $uids[] = $token;
                }
            }
        }

        $uids = array_values(array_unique($uids));
        usort($uids, static function ($a, $b) {
            return (int) $a <=> (int) $b;
        });

        return $uids;
    }

    /**
     * Pull one whole message with BODY.PEEK[] so it stays unread on the server.
     */
    private function fetchRaw(string $uid): ?string
    {
        $response = $this->command('UID FETCH ' . $uid . ' (BODY.PEEK[])');

        if (!$response['ok']) {
            Log::warning('[OutreachEngine] IMAP fetch of UID ' . $uid . ' failed: ' . $response['message']);

            return null;
        }

        if (empty($response['literals'])) {
            Log::warning('[OutreachEngine] IMAP fetch of UID ' . $uid . ' returned no message body.');

            return null;
        }

        return $response['literals'][0];
    }

    /**
     * Best-effort LOGOUT, then always close the handle.
     *
     * Called from the finally of every public entry point - a leaked socket would
     * hold an authenticated session open on the mail server until it times out.
     */
    private function close(): void
    {
        if (is_resource($this->socket)) {
            @fwrite($this->socket, $this->nextTag() . " LOGOUT\r\n");
            @fclose($this->socket);
        }

        $this->socket = null;
    }

    // ==================== PROTOCOL ====================

    /**
     * Send one tagged command and read everything up to its tagged completion.
     *
     * @return array ['ok'=>bool,'status'=>string,'message'=>string,'lines'=>string[],'literals'=>string[]]
     */
    private function command(string $command): array
    {
        $tag = $this->nextTag();

        if (!$this->write($tag . ' ' . $command . "\r\n")) {
            return ['ok' => false, 'status' => '', 'message' => 'The connection dropped while sending a command.', 'lines' => [], 'literals' => []];
        }

        return $this->readResponse($tag);
    }

    /**
     * Collect untagged lines until the line that starts with our tag.
     *
     * Literals ({N} at the end of a line) are read by byte count and returned
     * separately - that is how the raw message body arrives for a BODY.PEEK[] fetch.
     *
     * @return array ['ok'=>bool,'status'=>string,'message'=>string,'lines'=>string[],'literals'=>string[]]
     */
    private function readResponse(string $tag): array
    {
        $lines = [];
        $literals = [];
        $prefix = $tag . ' ';
        $prefixLength = strlen($prefix);
        $guard = 0;

        while (true) {
            if (++$guard > self::MAX_RESPONSE_LINES) {
                return ['ok' => false, 'status' => '', 'message' => 'The server sent an unreasonably long response.', 'lines' => $lines, 'literals' => $literals];
            }

            $line = $this->readLine();
            if ($line === null) {
                return ['ok' => false, 'status' => '', 'message' => 'The connection timed out or closed mid-response.', 'lines' => $lines, 'literals' => $literals];
            }

            // A line ending in {N} announces N raw bytes, then the rest of the line.
            // Repeat, because one response line may carry several literals.
            while (preg_match('/\{(\d+)\}\r?\n$/', $line, $matches)) {
                $literal = $this->readBytes((int) $matches[1], self::MAX_MESSAGE_BYTES);
                if ($literal === null) {
                    return ['ok' => false, 'status' => '', 'message' => 'The connection dropped while reading a message body.', 'lines' => $lines, 'literals' => $literals];
                }

                $literals[] = $literal;

                // Drop the {N} marker; the payload lives in $literals, so the line
                // stays small enough to scan for the UID and to log.
                $line = (string) preg_replace('/\{\d+\}\r?\n$/', '', $line);

                $rest = $this->readLine();
                if ($rest === null) {
                    break;
                }

                $line .= $rest;
            }

            $lines[] = $line;

            if (strncmp($line, $prefix, $prefixLength) === 0) {
                if (preg_match('/^\S+\s+(OK|NO|BAD)\s*(.*)$/i', trim($line), $done)) {
                    $status = strtoupper($done[1]);

                    return [
                        'ok' => $status === 'OK',
                        'status' => $status,
                        'message' => trim($done[2]),
                        'lines' => $lines,
                        'literals' => $literals,
                    ];
                }

                return ['ok' => false, 'status' => '', 'message' => trim($line), 'lines' => $lines, 'literals' => $literals];
            }
        }
    }

    /**
     * One CRLF-terminated line, reassembled across fgets() chunk boundaries.
     */
    private function readLine(): ?string
    {
        if (!is_resource($this->socket)) {
            return null;
        }

        $line = '';

        while (true) {
            $chunk = fgets($this->socket, 8192);

            if ($chunk === false) {
                return $line === '' ? null : $line;
            }

            $line .= $chunk;

            if (substr($chunk, -1) === "\n") {
                return $line;
            }

            $meta = stream_get_meta_data($this->socket);
            if (!empty($meta['timed_out']) || feof($this->socket)) {
                return $line === '' ? null : $line;
            }

            if (strlen($line) > self::MAX_LINE_BYTES) {
                return $line;
            }
        }
    }

    /**
     * Read exactly $length bytes. Everything is consumed to keep the stream in
     * protocol sync, but at most $keep bytes are retained so one enormous
     * attachment cannot exhaust memory.
     */
    private function readBytes(int $length, int $keep): ?string
    {
        if (!is_resource($this->socket) || $length < 0) {
            return null;
        }

        $buffer = '';
        $remaining = $length;
        $deadline = time() + self::SOCKET_TIMEOUT;

        while ($remaining > 0) {
            $chunk = fread($this->socket, (int) min(8192, $remaining));

            if ($chunk === false || $chunk === '') {
                $meta = stream_get_meta_data($this->socket);
                if (!empty($meta['timed_out']) || feof($this->socket) || time() > $deadline) {
                    return null;
                }

                continue;
            }

            $remaining -= strlen($chunk);

            if (strlen($buffer) < $keep) {
                $buffer .= $chunk;
            }

            $deadline = time() + self::SOCKET_TIMEOUT;
        }

        return strlen($buffer) > $keep ? substr($buffer, 0, $keep) : $buffer;
    }

    /**
     * Write the whole payload, tolerating partial writes.
     */
    private function write(string $payload): bool
    {
        if (!is_resource($this->socket)) {
            return false;
        }

        $total = strlen($payload);
        $sent = 0;

        while ($sent < $total) {
            $written = @fwrite($this->socket, substr($payload, $sent));

            if ($written === false || $written === 0) {
                return false;
            }

            $sent += $written;
        }

        return true;
    }

    /**
     * Next command tag: A001, A002, ...
     */
    private function nextTag(): string
    {
        $this->tagCounter++;

        return 'A' . str_pad((string) $this->tagCounter, 3, '0', STR_PAD_LEFT);
    }

    /**
     * IMAP quoted string. Backslash and double quote are escaped per RFC 3501, and
     * CR/LF are stripped outright - a newline inside a password would otherwise let
     * the rest of it be read as a fresh command.
     */
    private function quote(string $value): string
    {
        $value = str_replace(["\r", "\n"], '', $value);
        $value = str_replace(['\\', '"'], ['\\\\', '\\"'], $value);

        return '"' . $value . '"';
    }

    /**
     * Record a failure and hand back the caller's neutral value.
     *
     * @param  mixed  $return
     * @return mixed
     */
    private function fail(?string $message, $return = null)
    {
        $this->lastError = $message;

        if ($message !== null) {
            Log::warning('[OutreachEngine] ' . $message);
        }

        return $return;
    }

    // ==================== MIME PARSING ====================

    /**
     * Split a message (or a MIME part) on the first blank line.
     *
     * @return array{0:string,1:string} [headerBlock, body]
     */
    private function splitHeadersAndBody(string $raw): array
    {
        // A part that opens with a blank line carries no headers at all.
        if (strncmp($raw, "\r\n", 2) === 0) {
            return ['', substr($raw, 2)];
        }

        if (strncmp($raw, "\n", 1) === 0) {
            return ['', substr($raw, 1)];
        }

        $position = strpos($raw, "\r\n\r\n");
        if ($position !== false) {
            return [substr($raw, 0, $position), substr($raw, $position + 4)];
        }

        $position = strpos($raw, "\n\n");
        if ($position !== false) {
            return [substr($raw, 0, $position), substr($raw, $position + 2)];
        }

        return [$raw, ''];
    }

    /**
     * Header block to a lowercase-keyed map, folded lines joined back together.
     *
     * Repeats keep the first occurrence: that is the one the sender wrote, while
     * later copies are usually added by relays.
     *
     * @return array<string,string>
     */
    private function parseHeaders(string $block): array
    {
        if (trim($block) === '') {
            return [];
        }

        $block = str_replace("\r\n", "\n", $block);
        // RFC 5322 folding: a line starting with SP/HTAB continues the one above.
        $block = (string) preg_replace('/\n[ \t]+/', ' ', $block);

        $headers = [];

        foreach (explode("\n", $block) as $line) {
            if (trim($line) === '') {
                continue;
            }

            $position = strpos($line, ':');
            if ($position === false) {
                continue;
            }

            $name = strtolower(trim(substr($line, 0, $position)));
            if ($name === '' || isset($headers[$name])) {
                continue;
            }

            $headers[$name] = trim(substr($line, $position + 1));
        }

        return $headers;
    }

    /**
     * Recursively collect the readable text of a part into $collected.
     *
     * Attachments and the message/rfc822 copy a bounce report carries are skipped:
     * the first is not conversation, and the second is our own outbound copy coming
     * back, which would read in the inbox as if the lead had quoted us.
     *
     * @param  array<string,string>  $headers
     * @param  array{text:array<int,string>,html:array<int,string>}  $collected
     */
    private function walkPart(array $headers, string $body, array &$collected, int $depth): void
    {
        if ($depth > self::MAX_MIME_DEPTH || $body === '') {
            return;
        }

        $contentType = (string) ($headers['content-type'] ?? 'text/plain');
        $mime = strtolower(trim(explode(';', $contentType)[0]));
        $disposition = strtolower((string) ($headers['content-disposition'] ?? ''));

        if (strncmp($mime, 'multipart/', 10) === 0) {
            $boundary = $this->headerParam($contentType, 'boundary');
            if ($boundary === null || $boundary === '') {
                return;
            }

            foreach ($this->splitMultipart($body, $boundary) as $chunk) {
                [$partHeaderBlock, $partBody] = $this->splitHeadersAndBody($chunk);
                $this->walkPart($this->parseHeaders($partHeaderBlock), $partBody, $collected, $depth + 1);
            }

            return;
        }

        if (strncmp($disposition, 'attachment', 10) === 0) {
            return;
        }

        $decoded = $this->decodePartBody(
            $body,
            $headers['content-transfer-encoding'] ?? null,
            $this->headerParam($contentType, 'charset')
        );

        if ($decoded === '') {
            return;
        }

        if ($mime === 'text/html') {
            $collected['html'][] = $decoded;

            return;
        }

        // message/delivery-status and text/rfc822-headers are the machine-readable
        // halves of a bounce - plain text, and the only place the failure reason lives.
        if ($mime === 'text/plain' || $mime === 'message/delivery-status' || $mime === 'text/rfc822-headers') {
            $collected['text'][] = $decoded;
        }
    }

    /**
     * Cut a multipart body into its parts, dropping the preamble and the epilogue.
     *
     * @return array<int,string>
     */
    private function splitMultipart(string $body, string $boundary): array
    {
        $segments = explode('--' . $boundary, $body);
        array_shift($segments); // preamble before the first delimiter

        $parts = [];

        foreach ($segments as $segment) {
            // "--boundary--" closes the set; anything after it is the epilogue.
            if (strncmp($segment, '--', 2) === 0) {
                break;
            }

            // Drop the CRLF that ends the delimiter line and the one that opens the next.
            $segment = (string) preg_replace('/^[ \t]*\r?\n/', '', $segment, 1);
            $segment = (string) preg_replace('/\r?\n$/', '', $segment, 1);

            if (trim($segment) === '') {
                continue;
            }

            $parts[] = $segment;
        }

        return $parts;
    }

    /**
     * Undo the transfer encoding, then normalise the charset to UTF-8.
     */
    private function decodePartBody(string $body, ?string $encoding, ?string $charset): string
    {
        $encoding = strtolower(trim((string) $encoding));

        if ($encoding === 'base64') {
            $decoded = base64_decode((string) preg_replace('/\s+/', '', $body), false);
            $body = $decoded === false ? '' : $decoded;
        } elseif ($encoding === 'quoted-printable') {
            $body = quoted_printable_decode($body);
        }

        return trim($this->toUtf8($body, $charset));
    }

    /**
     * Convert a decoded part to UTF-8, never fatally.
     */
    private function toUtf8(string $text, ?string $charset): string
    {
        if ($text === '') {
            return '';
        }

        $charset = strtoupper(trim((string) $charset, " \t\"'"));

        if ($charset === '' || in_array($charset, ['UTF-8', 'UTF8', 'US-ASCII', 'ASCII', 'ANSI_X3.4-1968'], true)) {
            return $this->assumeLatin1IfBroken($text);
        }

        // Legacy single-byte labels are routinely wrong - plenty of senders declare
        // ISO-8859-1 while shipping UTF-8. Converting real UTF-8 "from" Latin-1 turns
        // every accent into mojibake, so trust the bytes when they are unambiguously
        // multi-byte UTF-8.
        if (preg_match('/^(ISO-8859-\d+|WINDOWS-125\d)$/', $charset)
            && mb_check_encoding($text, 'UTF-8')
            && preg_match('/[\xC2-\xF4][\x80-\xBF]/', $text)) {
            return $text;
        }

        try {
            $converted = mb_convert_encoding($text, 'UTF-8', $charset);

            return is_string($converted) ? $converted : $this->assumeLatin1IfBroken($text);
        } catch (\Throwable $e) {
            // PHP 8 raises a ValueError on an unknown charset label, and mail is full
            // of them. Latin-1 at least keeps the bytes legible.
            return $this->assumeLatin1IfBroken($text);
        }
    }

    /**
     * Bytes that are already valid UTF-8 pass through; anything else is treated as
     * Latin-1 so the column never receives an invalid UTF-8 sequence.
     */
    private function assumeLatin1IfBroken(string $text): string
    {
        if (mb_check_encoding($text, 'UTF-8')) {
            return $text;
        }

        $converted = mb_convert_encoding($text, 'UTF-8', 'ISO-8859-1');

        return is_string($converted) ? $converted : '';
    }

    /**
     * Decode an RFC 2047 encoded-word header ("=?UTF-8?B?...?=") to plain UTF-8.
     */
    private function decodeHeaderText(string $value): string
    {
        $value = trim((string) preg_replace('/\s+/', ' ', $value));

        if ($value === '') {
            return '';
        }

        // In a Q encoded-word an underscore stands for a space (RFC 2047 section 4.2),
        // but mb_decode_mimeheader leaves it alone. Rewrite it to the =20 escape so
        // subjects do not come through as "Re:_Your_inquiry".
        $value = (string) preg_replace_callback(
            '/=\?([^?]+)\?([QqBb])\?([^?]*)\?=/',
            static function (array $word) {
                $payload = strtoupper($word[2]) === 'Q' ? str_replace('_', '=20', $word[3]) : $word[3];

                return '=?' . $word[1] . '?' . $word[2] . '?' . $payload . '?=';
            },
            $value
        );

        $decoded = @mb_decode_mimeheader($value);

        return $this->assumeLatin1IfBroken(is_string($decoded) && $decoded !== '' ? $decoded : $value);
    }

    /**
     * Pull one parameter out of a structured header value, quoted or bare.
     */
    private function headerParam(string $headerValue, string $name): ?string
    {
        $quotedName = preg_quote($name, '/');

        if (preg_match('/;\s*' . $quotedName . '\s*=\s*"([^"]*)"/i', $headerValue, $matches)) {
            return $matches[1];
        }

        if (preg_match('/;\s*' . $quotedName . '\s*=\s*([^;\s]+)/i', $headerValue, $matches)) {
            return trim($matches[1], "\"'");
        }

        return null;
    }

    /**
     * Split a From header into a lowercase address and an optional display name.
     *
     * @return array{email:string,name:?string}
     */
    private function parseAddress(?string $value): array
    {
        $value = trim((string) $value);

        if ($value === '') {
            return ['email' => '', 'name' => null];
        }

        $name = null;
        $email = $value;

        if (preg_match('/<([^<>]+)>/', $value, $matches)) {
            $email = trim($matches[1]);
            $name = trim(str_replace($matches[0], '', $value));
        }

        $email = strtolower(trim($email, " \t<>"));

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            // Some senders write "Foo Bar foo@bar.com" with no angle brackets at all.
            $email = preg_match('/[\w.+\-]+@[\w\-]+\.[\w.\-]+/', $value, $fallback)
                ? strtolower($fallback[0])
                : '';
        }

        $name = trim((string) $name, " \t\"'");
        $name = $name !== '' ? $this->decodeHeaderText($name) : null;

        return ['email' => $email, 'name' => $name];
    }

    /**
     * Strip the angle brackets from a Message-ID style header.
     */
    private function trimAngles(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (preg_match('/<([^<>]+)>/', $value, $matches)) {
            return trim($matches[1]);
        }

        return $value;
    }

    /**
     * A Date header as 'Y-m-d H:i:s' in Asia/Manila, or null when unparseable.
     */
    private function normalizeDate(?string $raw): ?string
    {
        $raw = trim((string) $raw);

        if ($raw === '') {
            return null;
        }

        // Drop the leading day name. Senders get it wrong constantly, and PHP reads a
        // mismatched "Mon," as the relative modifier "next Monday" - which silently
        // pushes the whole date days into the future.
        $raw = trim((string) preg_replace('/^[A-Za-z]{3,9}\s*,\s*/', '', $raw));

        // Trailing "(PST)" style comments confuse the parser on some locales.
        $raw = trim((string) preg_replace('/\s*\([^)]*\)\s*$/', '', $raw));

        try {
            return Carbon::parse($raw)->timezone('Asia/Manila')->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Is this a delivery failure rather than a human reply?
     *
     * Three signals, any one of which is enough: the daemon addresses, the stock
     * subject lines every MTA uses, and the multipart/report container from RFC 3462.
     *
     * @param  array<string,string>  $headers
     */
    private function looksLikeBounce(array $headers, string $fromEmail, string $subject): bool
    {
        if ($fromEmail !== '' && preg_match('/mailer-daemon|postmaster/i', $fromEmail)) {
            return true;
        }

        if ($subject !== '' && preg_match('/undeliverable|delivery status notification|returned mail|delivery has failed/i', $subject)) {
            return true;
        }

        $contentType = strtolower((string) ($headers['content-type'] ?? ''));

        return strpos($contentType, 'multipart/report') !== false;
    }
}
