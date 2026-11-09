<?php

namespace App\Services\Streamr;

/**
 * Streamr's AI reading of a receipt, written as plain lines for the payment's
 * Verification Notes in /ecom-orders (shown escaped in a <pre>), e.g.
 *
 *   [2026-11-08 14:03] Streamr AI receipt check: FAIL (authenticity 62/100). Something on the receipt does not match this payment.
 *     ✗ The amount is ₱11,000.00: The receipt shows ₱1,100.00.
 *     ? Paid after the checkout was opened: No date and time could be read.
 *     ✓ Sent to our account (GCash 09XX XXX XXXX): Read: J•••N D•••Z, +63 9XX •••.
 *
 * The AI only reads in Streamr; these lines record what it said.
 */
final class StreamrAiNotes
{
    public const VERDICTS = [
        'pass' => 'PASS',
        'fail' => 'FAIL',
        'unsure' => 'UNSURE',
        'error' => 'ERROR',
        'skipped' => 'NOT CHECKED',
        'running' => 'CHECKING',
    ];

    /**
     * @param  array|null  $ai  the `ai` block Streamr sent (verdict, score, summary, checks[], provider, model, checked_at)
     * @param  string  $kind  image | pdf | ref | bank
     */
    public static function lines(?array $ai, string $kind, string $label = 'Streamr AI receipt check'): string
    {
        $at = StreamrTime::parse($ai['checked_at'] ?? null) ?? StreamrTime::now();
        $stamp = '[' . $at->format('Y-m-d H:i') . '] ';
        $verdict = is_array($ai) ? (string) ($ai['verdict'] ?? '') : '';
        $out = [];

        if ($kind === 'bank') {
            $out[] = $stamp . 'Bank transfer — reviewed by a person (the AI does not read bank slips).';
            if ($ai === null || $verdict === 'skipped' || $verdict === '') {
                return implode("\n", $out);
            }
        } elseif ($kind === 'ref') {
            $out[] = $stamp . 'Reference number only — no receipt picture.';
            if ($ai === null || $verdict === 'skipped' || $verdict === '') {
                return implode("\n", $out);
            }
        }

        if ($ai === null) {
            $out[] = $stamp . $label . ': NOT CHECKED. Streamr sent no AI reading for this receipt.';
        } else {
            $word = self::VERDICTS[$verdict] ?? strtoupper($verdict ?: 'unknown');
            $score = isset($ai['score']) && is_numeric($ai['score']) ? ' (authenticity ' . (int) $ai['score'] . '/100)' : '';
            $summary = self::text($ai['summary'] ?? '');
            $out[] = $stamp . $label . ': ' . $word . $score . '.' . ($summary !== '' ? ' ' . $summary : '');

            foreach (array_slice(array_values(array_filter((array) ($ai['checks'] ?? []), 'is_array')), 0, 12) as $check) {
                $line = '  ' . self::icon($check['ok'] ?? null) . ' ' . self::text($check['label'] ?? '');
                $detail = self::text($check['detail'] ?? '');

                $out[] = rtrim($line) . ($detail !== '' ? ': ' . $detail : '');
            }
        }

        if ($kind === 'pdf') {
            $out[] = '  Proof is a PDF — open it from Streamr › Payment reviews.';
        }

        return implode("\n", $out);
    }

    /** The audit-log description: the summary plus every check that did not pass (≤ 1,000 characters). */
    public static function auditSummary(?array $ai, string $prefix = ''): string
    {
        if (! is_array($ai)) {
            return mb_substr($prefix . 'No AI reading.', 0, 1000);
        }
        $word = self::VERDICTS[(string) ($ai['verdict'] ?? '')] ?? strtoupper((string) ($ai['verdict'] ?? ''));
        $parts = [$prefix . 'Streamr AI: ' . $word . (isset($ai['score']) && is_numeric($ai['score']) ? ' (' . (int) $ai['score'] . '/100)' : '') . '.'];
        $summary = self::text($ai['summary'] ?? '');
        if ($summary !== '') {
            $parts[] = $summary;
        }
        foreach ((array) ($ai['checks'] ?? []) as $check) {
            if (is_array($check) && ($check['ok'] ?? null) !== true) {
                $parts[] = self::icon($check['ok'] ?? null) . ' ' . self::text($check['label'] ?? '')
                    . (self::text($check['detail'] ?? '') !== '' ? ': ' . self::text($check['detail'] ?? '') : '');
            }
        }

        return mb_substr(implode(' ', $parts), 0, 1000);
    }

    public static function icon($ok): string
    {
        return $ok === true ? '✓' : ($ok === false ? '✗' : '?');
    }

    /** One line of plain text: no line breaks, no control characters. */
    private static function text($value): string
    {
        $text = is_scalar($value) ? (string) $value : '';
        $text = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $text) ?? '';

        return trim(preg_replace('/\s{2,}/u', ' ', $text) ?? '');
    }
}
