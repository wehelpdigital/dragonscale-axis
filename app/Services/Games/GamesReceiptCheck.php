<?php

namespace App\Services\Games;

use App\Models\AsAiSetting;
use App\Models\GamePayment;
use App\Services\AniSensoAiClient;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * The AI reads a payment receipt; the code decides. Ported from anee.io's
 * App\Services\ReceiptCheck (2026-09-30), which takes payments to the same
 * GCash wallet.
 *
 * The AI is shown the uploaded picture with GamesReceipt::guide() and asked
 * only to READ it: what app, what kind of screen, the amount, the recipient's
 * name and number, the reference, the date and time, and any sign of editing.
 * It is never told what the payment expects, so it cannot "see" the number it
 * was hoping for. Then every check is made here, against the payment:
 *
 *   a receipt of GCash, Maya or a bank app, sent/successful; no editing seen;
 *   the AI is sure of it; the amount the recipient got is the payment's total
 *   to the centavo; the recipient is our account (its number, or when no
 *   number shows, our masked name); a reference never used on another payment
 *   (here or on anee.io, the same wallet) and matching the one the buyer typed;
 *   the same file never sent before; and paid after the checkout was opened.
 *
 * Only when all pass is the verdict "pass". A clear contradiction is "fail";
 * anything unread is "unsure". A fail or unsure waits for a person.
 */
class GamesReceiptCheck
{
    /** How sure the AI must say it is, 0-100, before a pass. */
    public const SURE = 80;

    public const APPS = ['gcash' => 'GCash', 'maya' => 'Maya', 'bank' => 'bank app', 'wallet' => 'wallet app'];

    public function __construct(private AniSensoAiClient $ai) {}

    /** Read the payment's receipt and judge it: the report saved on the payment. */
    public function run(GamePayment $payment): array
    {
        $bytes = $payment->proofPath ? Storage::disk('public')->get($payment->proofPath) : null;
        if (! $bytes) {
            return $this->report('skipped', 'There was no picture to read.', [], null);
        }

        $settings = AsAiSetting::current();
        if (! $settings->isUsable()) {
            return $this->report('error', 'The AI is not switched on (AniSystem > AI Settings), so this payment waits for a person.', [], null);
        }

        $result = $this->ai->ask(
            GamesReceiptAi::from($settings, 'You examine payment receipts for a small Philippine business. You read one uploaded image and report exactly what it shows, in JSON. You never guess a value that is not clearly visible; when unsure, you say so.'),
            [],
            $this->prompt(),
            ['mime' => (string) $payment->proofMime, 'data' => base64_encode($bytes)]
        );

        if (empty($result['ok'])) {
            return $this->report('error', 'The AI could not read the receipt just now (' . ($result['error'] ?? 'no answer') . '). A person will check it.', [], null);
        }
        $read = self::parse((string) $result['text']);
        if (! $read) {
            return $this->report('error', 'The AI\'s reading came back unreadable. A person will check it.', [], null);
        }

        $report = $this->judge($payment, $read);
        $report['tokens'] = ['in' => (int) ($result['tokensIn'] ?? 0), 'out' => (int) ($result['tokensOut'] ?? 0)];

        return $report;
    }

    /** Compare what the AI read with the payment. Pure: no AI, no writes. */
    public function judge(GamePayment $payment, array $read): array
    {
        $pay = GamesSiteSettings::pay();
        $checks = [];
        $add = function (string $key, string $label, ?bool $ok, string $detail, bool $hard = true) use (&$checks) {
            $checks[] = ['key' => $key, 'label' => $label, 'ok' => $ok, 'detail' => $detail, 'hard' => $hard];
        };
        $txt = fn ($v) => trim((string) ($v ?? ''));

        // 1. A receipt of a sent, successful payment, from GCash, Maya or a bank's app.
        $isReceipt = (bool) ($read['isReceipt'] ?? false);
        $app = strtolower($txt($read['app'] ?? ''));
        $isGcash = $app === 'gcash';
        $known = isset(self::APPS[$app]);
        $add('receipt', 'A payment receipt (GCash, Maya or a bank app)', $isReceipt && $known ? true : ($isReceipt ? null : false),
            ! $isReceipt ? 'This does not look like a payment receipt.'
                : ($known ? 'A ' . self::APPS[$app] . ' ' . str_replace('_', ' ', $txt($read['kind'] ?? 'receipt')) . '.' : 'A receipt from ' . ($app ?: 'an app') . ' the AI does not know: checked by hand.'));
        $status = strtolower($txt($read['status'] ?? ''));
        $add('status', 'Sent successfully', in_array($status, ['success', 'successful', 'sent', 'completed'], true) ? true : ($status === '' || $status === 'unknown' ? null : false),
            $status === '' || $status === 'unknown' ? 'No status could be read.' : 'It says: ' . $status . '.');

        // 2. No sign of editing, and the AI is sure. A sign that is only about the date
        //    being in the future or the past is set aside: when is this code's question (7).
        $signs = array_values(array_filter(array_map('strval', (array) ($read['editingSigns'] ?? [])),
            fn ($s) => trim($s) !== '' && ! self::onlyAboutWhen($s)));
        $add('edits', 'No sign of editing', $signs ? false : true, $signs ? implode('; ', $signs) : 'Nothing looked altered.');
        $score = max(0, min(100, (int) ($read['authenticity'] ?? 0)));
        $add('sure', 'The AI is sure it is genuine', $score >= self::SURE ? true : null, 'It rates it ' . $score . ' out of 100 (needs ' . self::SURE . ').', false);

        // 3. The amount: the payment's total, to the centavo.
        $amount = self::money($read['amountToRecipient'] ?? $read['amount'] ?? null);
        $want = (float) $payment->totalPhp;
        $add('amount', 'The amount is ₱' . number_format($want, 2), $amount === null ? null : abs($amount - $want) < 0.01,
            $amount === null ? 'No amount could be read.' : 'The receipt shows ₱' . number_format($amount, 2) . '.');

        // 4. The recipient: our number, or (when hidden) our masked name.
        $numOk = GamesReceipt::numberMatches($read['recipientNumber'] ?? null, (string) $pay['gcashNumber']);
        $nameRead = $read['recipientName'] ?? null;
        $nameOk = GamesReceipt::nameMatches($nameRead, (string) $pay['gcashName'], $isGcash);
        $toGcash = (bool) preg_match('/g\s*cash|g\s*xchange/i', $txt($read['recipientBank'] ?? ''));
        if ($numOk === false) {
            $recipOk = false;
        } elseif ($numOk === true) {
            $recipOk = $nameOk === false ? ($isGcash ? false : null) : true;
        } else {
            $recipOk = $nameOk === true ? ($isGcash || $toGcash ? true : null) : ($nameOk === false ? false : null);
        }
        $add('recipient', 'Sent to our account (GCash ' . GamesSiteSettings::spacedNumber() . ')', $recipOk,
            'Read: ' . ($txt($nameRead) ?: 'no name') . ', ' . ($txt($read['recipientNumber'] ?? '') ?: 'no number')
            . ($txt($read['recipientBank'] ?? '') !== '' ? ', at ' . $txt($read['recipientBank']) : '') . '.');

        // 5. The reference: new (here and on anee.io), the one the buyer typed, of the app's shape.
        $ref = GamesReceipt::refKey($read['referenceNumber'] ?? null);
        $typed = GamesReceipt::refKey($payment->refNumber);
        $refUsed = $ref !== '' && $this->referenceUsed($ref, $payment->id);
        $shapeOk = $isGcash ? (strlen($ref) === 13 && ctype_digit($ref)) : (strlen($ref) >= 6 && strlen($ref) <= 32);
        $typedOff = $typed !== '' && $typed !== $ref;
        $refOk = $ref === '' ? null : ($refUsed || ! $shapeOk ? false : ($typedOff ? ($isGcash ? false : null) : true));
        $add('ref', $isGcash ? 'A new Ref No. (13 digits)' : 'A new reference number', $refOk,
            $ref === '' ? 'No reference number could be read.'
                : ($refUsed ? 'Reference ' . $ref . ' was already used on another payment.'
                    : (! $shapeOk ? 'Reference ' . $ref . ($isGcash ? ' is not 13 digits.' : ' is not the shape of a reference (6 to 32 letters and digits).')
                        : ($typedOff ? 'The typed reference (' . $typed . ') is not the one on the receipt (' . $ref . ').' : 'Reference ' . $ref . '.'))));

        // 6. The same file never sent before.
        $dupe = $payment->proofSha && GamePayments::shaSeenElsewhere($payment->proofSha, $payment->id);
        $add('file', 'A receipt not sent before', ! $dupe, $dupe ? 'This exact file was already sent with another payment.' : 'First time this file is seen.');

        // 7. Paid after the checkout was opened, and not in the future.
        $when = self::when($read['dateTime'] ?? null);
        $opened = Carbon::parse($payment->created_at)->timezone('Asia/Manila');
        $timeOk = $when === null ? null : ($when->gte($opened->copy()->subMinutes(15)) && $when->lte(Carbon::now('Asia/Manila')->addMinutes(15)));
        $add('time', 'Paid after the checkout was opened', $timeOk,
            $when === null ? 'No date and time could be read.'
                : 'Paid ' . $when->format('M j, Y g:i A') . '; the checkout was opened ' . $opened->format('M j, Y g:i A') . '.');

        $hardNo = collect($checks)->contains(fn ($c) => $c['ok'] === false && $c['hard']);
        $unsure = collect($checks)->contains(fn ($c) => $c['ok'] !== true);
        $verdict = $hardNo ? 'fail' : ($unsure ? 'unsure' : 'pass');
        $summary = match ($verdict) {
            'pass' => 'Every check passed.',
            'fail' => 'Something on the receipt does not match this payment.',
            default => 'The AI could not confirm everything, so a person will look.',
        };

        return $this->report($verdict, $summary, $checks, [
            'app' => $app, 'kind' => $txt($read['kind'] ?? ''), 'status' => $status,
            'amount' => $amount, 'recipientName' => $txt($read['recipientName'] ?? ''), 'recipientNumber' => $txt($read['recipientNumber'] ?? ''),
            'recipientBank' => $txt($read['recipientBank'] ?? ''), 'senderName' => $txt($read['senderName'] ?? ''),
            'ref' => $ref, 'dateTime' => $when?->toDateTimeString(), 'authenticity' => $score,
            'notes' => mb_substr($txt($read['notes'] ?? ''), 0, 500),
        ]);
    }

    /** Whether a reference was already used on another web payment, or on anee.io (the same wallet). */
    private function referenceUsed(string $ref, int $paymentId): bool
    {
        $norm = "UPPER(REPLACE(REPLACE(REPLACE(refNumber, ' ', ''), '-', ''), '.', ''))";
        $here = DB::table('games_payments')->where('id', '!=', $paymentId)->where('status', '!=', GamePayment::EXPIRED)
            ->where(fn ($q) => $q->whereRaw("{$norm} = ?", [$ref])
                ->orWhere('aiReport', 'like', '%"ref":"' . $ref . '"%'))
            ->exists();
        if ($here) {
            return true;
        }
        try {
            return Schema::hasTable('as_orders')
                && DB::table('as_orders')->where('status', '!=', 'cancelled')->whereRaw("{$norm} = ?", [$ref])->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Whether an "editing sign" is only doubt about WHEN rather than something on the picture. */
    public static function onlyAboutWhen(string $sign): bool
    {
        $s = mb_strtolower($sign);
        $aboutWhen = (bool) preg_match('/\b(future|not yet (happened|occurred)|has(n\'t| not) happened|upcoming|too old|outdated|in the past)\b/u', $s);
        $aboutLooks = (bool) preg_match('/\b(font|bold|align|misalign|blur|smudg|pixel|cropp|colou?r|spacing|kerning|edited|photoshop|overlay|mismatch)/u', $s);

        return $aboutWhen && ! $aboutLooks;
    }

    private function prompt(): string
    {
        $now = Carbon::now('Asia/Manila');
        $today = "\n\nTODAY\nIt is " . $now->format('l, F j, Y, g:i A') . ' in the Philippines (Asia/Manila, UTC+8). '
            . 'Your own sense of the current date may be out of date: trust this one. Read the receipt\'s date and time exactly as printed. '
            . 'Whether that date is right for this payment is checked by our system, so never list a date or a year as a sign of editing '
            . 'because it seems to be in the future or the past, and do not lower your authenticity score for it.';

        return GamesReceipt::guide() . $today . <<<'TXT'


THE TASK
Read the attached image. Report ONLY what is visible. Do not correct or
complete anything. Reply with one JSON object and nothing else:

{
  "isReceipt": true or false,
  "app": "gcash" | "maya" | "bank" | "wallet" | "other" | "unknown",
  "kind": "express_send" | "qr_payment" | "instapay_transfer" | "bank_transfer" | "transaction_detail" | "sms" | "other",
  "status": "success" | "pending" | "failed" | "unknown",
  "amountToRecipient": number or null,
  "amount": number or null,
  "currency": "PHP" | "other",
  "recipientName": "as printed, keeping its masking" or null,
  "recipientNumber": "as printed, keeping its masking" or null,
  "recipientBank": "the destination bank or wallet as printed" or null,
  "senderName": "as printed" or null,
  "referenceNumber": "exactly as printed" or null,
  "dateTime": "YYYY-MM-DD HH:MM (24-hour, as printed, Philippine time)" or null,
  "editingSigns": ["each concrete sign of alteration you can point at"],
  "authenticity": 0-100,
  "notes": "one short sentence"
}
TXT;
    }

    /** The first JSON object in the answer, fences and chatter stripped. */
    public static function parse(string $text): ?array
    {
        $text = preg_replace('/^```(?:json)?|```$/m', '', trim($text));
        $a = strpos($text, '{');
        $b = strrpos($text, '}');
        if ($a === false || $b === false || $b <= $a) {
            return null;
        }
        $json = preg_replace('#(?<![:"\w])//[^\n]*#', '', substr($text, $a, $b - $a + 1));
        $data = json_decode($json, true);

        return is_array($data) ? $data : null;
    }

    private static function money(mixed $v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }
        $n = (float) preg_replace('/[^0-9.]/', '', (string) $v);

        return $n > 0 ? round($n, 2) : null;
    }

    private static function when(mixed $v): ?Carbon
    {
        $v = trim((string) ($v ?? ''));
        if ($v === '') {
            return null;
        }
        try {
            return Carbon::parse($v, 'Asia/Manila');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function report(string $verdict, string $summary, array $checks, ?array $read): array
    {
        return array_filter([
            'verdict' => $verdict,
            'summary' => $summary,
            'checks' => $checks,
            'read' => $read,
            'at' => now()->toDateTimeString(),
        ], fn ($v) => $v !== null);
    }
}
