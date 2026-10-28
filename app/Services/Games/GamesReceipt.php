<?php

namespace App\Services\Games;

/**
 * What a genuine receipt of a payment to the website's GCash account looks
 * like -- the reference the AI reads every uploaded receipt against -- and
 * the matching the code does on what it read.
 *
 * Ported from anee.io's App\Support\GcashReceipt (2026-09-30, written from a
 * real "Sent via GCash" receipt, GCash's help pages and its warnings about
 * AI-made fake receipts). The same wallet takes both apps' payments, so the
 * rules are the same; anee's built-in QR (its own account number and name) is
 * not part of this one. What the owner adds in Games > Website > Settings is
 * added to GUIDE, never swapped for it.
 *
 * The AI only READS. Whether a payment is accepted is decided in
 * GamesReceiptCheck::judge(), by comparing what it read with the payment.
 */
class GamesReceipt
{
    public const GUIDE = <<<'TXT'
GENUINE RECEIPTS OF A PAYMENT TO OUR GCASH ACCOUNT (Philippines, 2024-2026)

The receiving account is a GCash wallet. Buyers send money to its mobile
number with GCash ("Express Send"), or scan its QR code (a QR Ph code that any
bank or wallet app can scan, InstaPay), so the receipt may come from the GCash
app, from Maya, or from any bank's app.

1. GCash "Express Send" / "Send via QR" receipt (the GCash app's own confirmation or
   its saved image, often named GCash-<number>-<date>.PNG):
   - A royal-blue page with a white receipt card; a round blue badge with a
     white check mark sits on the card's top edge.
   - The RECIPIENT's name in bold blue capitals, MASKED: the first one or two
     letters, dots or bullets, the last letter, e.g. "DO•••N MA•••N T.".
   - Under it, the recipient's mobile number in a pale pill, "+63 9XX XXX XXXX"
     (sometimes partly masked), then the grey words "Sent via GCash".
   - "Amount" with the figure on the right (e.g. "30,000.00", no peso sign),
     then a line, then "Total Amount Sent" with "₱" and the figure in large
     bold (the same figure when there is no fee).
   - In the lower, paler section: "Ref No." followed by a 13-digit number
     printed in groups 4-3-6, e.g. "1023 796 539709", and under it the date
     and time, e.g. "Dec 18, 2024 11:22 PM".
   - Newer receipts show a green card near the bottom: "<n>g (gCO2e) By going
     digital, you reduce your carbon footprint from transportation, paper,
     and plastic."; the card's foot is cut in a zigzag, like torn paper.
   - A plain screenshot may also show the phone's own status bar (time,
     signal, battery) above it; a saved receipt image does not.
2. Other GCash screens: the Transaction History detail ("Express Send",
   amount, date, Ref No.) and the "You have sent PHP ... to ... Ref. No. ..."
   text message. These are GCash too, but carry less to check.
3. Paid from ANOTHER app into this account (by scanning the QR, or by an
   InstaPay transfer to the mobile number): Maya, or a bank's app (BPI,
   BDO, UnionBank, Landbank, Metrobank, Security Bank, RCBC, PNB, ChinaBank,
   a rural bank and others), or another wallet. That app's own confirmation
   screen or receipt, in its own colours and fonts. It shows the amount, the
   recipient (a name, often masked, and a mobile or account number, often
   masked), often the destination as GCash or G-Xchange, often the word
   InstaPay or QR Ph, a reference number (letters and digits, of the app's own
   length; some show a trace number as well), and the date and time. Any
   transfer fee the bank or wallet charged the SENDER is shown apart from the
   amount sent; report the amount the recipient receives as amountToRecipient.

WHAT FAKES GET WRONG (GCash warns that AI-made fake receipts circulate):
fonts that differ between lines or from the app's own font; digits that are
bolder, larger or misaligned beside their neighbours; a peso sign that sits
off the baseline; on a GCash receipt, a Ref No. that is not 13 digits or not
grouped 4-3-6; a date written in a format the app does not use; blurred or
smudged patches around the amount, name or number; a card that is cropped
tightly around the figures; a status other than sent or successful; screens
from a web page or a "receipt maker". A receipt is never proof on its own:
only the receiver's own transaction history is. So read carefully, say what
is visible, and never fill in a value that cannot be read.
TXT;

    public static function guide(): string
    {
        $extra = trim((string) GamesSiteSettings::pay()['receiptGuide']);

        return self::GUIDE . ($extra !== '' ? "\n\nMORE FROM THE OWNER:\n" . $extra : '');
    }

    /** Digits only; a mask becomes '*'. '+63 956 904 ••••' -> '0956904****'. */
    public static function numberShape(?string $n): string
    {
        $n = trim((string) $n);
        if ($n === '') {
            return '';
        }
        $n = preg_replace('/[•●·∙*xX#]/u', '*', $n);
        $n = preg_replace('/[^0-9*+]/', '', $n);
        if (str_starts_with($n, '+63')) {
            $n = '0' . substr($n, 3);
        } elseif (str_starts_with($n, '63') && strlen($n) === 12) {
            $n = '0' . substr($n, 2);
        }

        return str_replace('+', '', $n);
    }

    /**
     * Does a read number agree with ours? Every digit both show must be the
     * same, and at least four must be visible. A masked reading of another
     * length (a bank's "••••••4144") is matched by the digits at each end.
     * null when nothing was read.
     */
    public static function numberMatches(?string $read, string $ours): ?bool
    {
        $a = self::numberShape($read);
        $b = self::numberShape($ours);
        if ($a === '') {
            return null;
        }
        if (strlen($a) !== strlen($b)) {
            if (! str_contains($a, '*')) {
                return false;
            }
            $head = substr($a, 0, strpos($a, '*'));
            $tail = substr($a, strrpos($a, '*') + 1);
            $shown = strlen($head) + strlen($tail);
            if ($shown > strlen($b) || ! str_starts_with($b, $head) || ($tail !== '' && ! str_ends_with($b, $tail))) {
                return false;
            }

            return $shown >= 4;
        }
        $seen = 0;
        for ($i = 0, $n = strlen($a); $i < $n; $i++) {
            if ($a[$i] === '*' || $b[$i] === '*') {
                continue;
            }
            if ($a[$i] !== $b[$i]) {
                return false;
            }
            $seen++;
        }

        return $seen >= 4;
    }

    /**
     * Do two masked names agree? Only the letters both show count: each
     * word's first letter, and its last when shown. With $sameCount false (a
     * bank prints fewer or more words), a different count is "cannot tell".
     */
    public static function nameMatches(?string $read, string $ours, bool $sameCount = true): ?bool
    {
        $words = function (string $s): array {
            $s = mb_strtoupper(preg_replace('/[•●·∙*]+/u', '*', $s));
            $s = preg_replace('/[^A-Z*\s]/u', '', $s);

            return array_values(array_filter(preg_split('/\s+/', trim($s))));
        };
        $a = $words((string) $read);
        $b = $words($ours);
        if (! $a) {
            return null;
        }
        if (count($a) !== count($b)) {
            return $sameCount ? false : null;
        }
        foreach ($a as $i => $w) {
            $v = $b[$i];
            if ($w[0] !== '*' && $v[0] !== '*' && $w[0] !== $v[0]) {
                return false;
            }
            $wl = substr($w, -1);
            $vl = substr($v, -1);
            if (strlen($w) > 1 && strlen($v) > 1 && $wl !== '*' && $vl !== '*' && $wl !== $vl) {
                return false;
            }
        }

        return true;
    }

    /**
     * Any app's reference as one comparable key: a leading label ("Ref No.",
     * "Reference ID:") dropped, then letters and digits only, in capitals.
     */
    public static function refKey(?string $ref): string
    {
        $r = trim((string) $ref);
        $r = (string) preg_replace('/^(ref(erence)?|trace|transaction|txn)\.?\s*(no\.?|number|id|#)?\s*[:.#]?\s*/i', '', $r);

        return strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $r));
    }
}
