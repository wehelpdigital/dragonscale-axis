<?php

namespace App\Services\Games;

use App\Http\Controllers\aniSensoAdmin\AnisystemOrdersController;
use App\Models\AsSiteSetting;
use App\Models\GameSetting;
use Illuminate\Support\Facades\Storage;

/**
 * The studio website's settings (Games > Website > Settings), stored in
 * games_settings under gameKey "site":
 *
 *   site  what the website calls itself and how people reach the studio
 *   pay   how the web checkout is paid: the GCash account buyers send money
 *         to, the fee added for the AI that reads every receipt, whether the
 *         AI may approve a payment on its own, and more for the AI to know
 *         about genuine receipts
 *
 * The GCash account starts as anee.io's (the same owner's wallet, set in
 * AniSystem > Orders); anything saved here is the website's own.
 */
class GamesSiteSettings
{
    public const GAME = 'site';

    public const SITE_DEFAULTS = [
        'siteName' => 'Dragon Scale Games',
        'tagline' => 'Games made to be played together.',
        'contactEmail' => '',
        'announcement' => '',
    ];

    public const PAY_DEFAULTS = [
        'gcashNumber' => '',
        'gcashName' => '',
        // A path on the public disk (uploaded here), or blank: no QR, the number only.
        'gcashQr' => '',
        // Added to a GCash payment: the AI reads every receipt.
        'gcashFee' => 5,
        // Read receipts with the AI at all (off: every payment waits for a person).
        'aiCheck' => true,
        // May the AI approve a payment on its own when every check passes?
        'aiAutoApprove' => true,
        // Said to the buyer while a payment waits for a person.
        'reviewHours' => 24,
        // More for the AI to know about genuine receipts, added to the built-in guide.
        'receiptGuide' => '',
        // PayPal is coming: shown as "soon" until it is switched on.
        'paypalEnabled' => false,
    ];

    public static function site(): array
    {
        $s = array_replace(self::SITE_DEFAULTS, array_intersect_key(GameSetting::getJson(self::GAME, 'site'), self::SITE_DEFAULTS));

        return array_map(fn ($v) => trim((string) $v), $s);
    }

    public static function saveSite(array $values): void
    {
        GameSetting::setJson(self::GAME, 'site', array_intersect_key(array_replace(self::site(), $values), self::SITE_DEFAULTS));
    }

    public static function pay(): array
    {
        $stored = GameSetting::getJson(self::GAME, 'pay');
        $s = array_replace(self::PAY_DEFAULTS, self::aneeAccount(), array_intersect_key($stored, self::PAY_DEFAULTS));
        $s['gcashNumber'] = preg_replace('/\D/', '', (string) $s['gcashNumber']);
        $s['gcashName'] = trim((string) $s['gcashName']);
        $s['gcashFee'] = max(0, round((float) $s['gcashFee'], 2));
        $s['aiCheck'] = filter_var($s['aiCheck'], FILTER_VALIDATE_BOOLEAN);
        $s['aiAutoApprove'] = filter_var($s['aiAutoApprove'], FILTER_VALIDATE_BOOLEAN);
        $s['paypalEnabled'] = filter_var($s['paypalEnabled'], FILTER_VALIDATE_BOOLEAN);
        $s['reviewHours'] = max(1, (int) $s['reviewHours']);

        return $s;
    }

    public static function savePay(array $values): void
    {
        GameSetting::setJson(self::GAME, 'pay', array_intersect_key(array_replace(self::pay(), $values), self::PAY_DEFAULTS));
    }

    /** GCash can be paid: a number and the name as GCash shows it. */
    public static function gcashReady(): bool
    {
        $s = self::pay();

        return strlen($s['gcashNumber']) === 11 && $s['gcashName'] !== '';
    }

    public static function qrUrl(): ?string
    {
        $qr = trim((string) self::pay()['gcashQr']);
        if ($qr === '') {
            return null;
        }
        if (preg_match('#^https?://#i', $qr)) {
            return $qr;
        }

        return Storage::disk('public')->url($qr);
    }

    /** '09569044144' -> '0956 904 4144'. */
    public static function spacedNumber(?string $n = null): string
    {
        $d = preg_replace('/\D/', '', (string) ($n ?? self::pay()['gcashNumber']));

        return strlen($d) === 11 ? substr($d, 0, 4) . ' ' . substr($d, 4, 3) . ' ' . substr($d, 7) : $d;
    }

    /** anee.io's GCash account (AniSystem > Orders), the starting point for the website's. */
    private static function aneeAccount(): array
    {
        $stored = json_decode((string) AsSiteSetting::get(AnisystemOrdersController::PAY_KEY, ''), true);
        $pay = array_replace(AnisystemOrdersController::PAY_DEFAULTS, is_array($stored) ? $stored : []);

        return ['gcashNumber' => (string) $pay['gcashNumber'], 'gcashName' => (string) $pay['gcashName']];
    }
}
