<?php

namespace App\Services\Streamr;

use App\Models\EcomProductStore;
use App\Models\StreamrSetting;
use App\Models\User;

/**
 * The module's settings (Streamr > Payment reviews > Payment settings), kept
 * on the streamr_settings shelf under the key "pay":
 *
 *   source                 ecom: the receiving details come from E-commerce >
 *                          Stores > Streamr (anything filled in here overrides
 *                          that field); own: only the details below
 *   gcashNumber, gcashNameMasked (as GCash prints it on receipts, e.g. JU•••N D.),
 *   qrDisplayName, qrAccount (as printed on QR Ph receipts), gcashQr (public disk)
 *   mayaNumber, mayaName, mayaQr
 *   bankName, bankAccountName, bankAccountNumber, bankBranch
 *   instructions, receiptGuide (more for Streamr's AI to know about receipts)
 *   autoApprovePlans, autoApproveLifetime, creditsDailyCapPhp, reviewHours
 *
 * Never secrets.
 */
final class StreamrSettings
{
    public const PAY_KEY = 'pay';

    public const PAY_DEFAULTS = [
        'source' => 'ecom',
        'gcashNumber' => '',
        'gcashNameMasked' => '',
        'qrDisplayName' => '',
        'qrAccount' => '',
        'gcashQr' => '',
        'mayaNumber' => '',
        'mayaName' => '',
        'mayaQr' => '',
        'bankName' => '',
        'bankAccountName' => '',
        'bankAccountNumber' => '',
        'bankBranch' => '',
        'instructions' => '',
        'receiptGuide' => '',
        'autoApprovePlans' => true,
        'autoApproveLifetime' => false,
        'creditsDailyCapPhp' => 2500,
        'reviewHours' => 24,
    ];

    public static function pay(): array
    {
        try {
            $stored = StreamrSetting::getJson(self::PAY_KEY);
        } catch (\Throwable $e) {
            $stored = [];
        }

        $pay = array_replace(self::PAY_DEFAULTS, array_intersect_key($stored, self::PAY_DEFAULTS));

        foreach (self::PAY_DEFAULTS as $key => $default) {
            if (is_string($default)) {
                $pay[$key] = trim((string) $pay[$key]);
            }
        }
        $pay['source'] = $pay['source'] === 'own' ? 'own' : 'ecom';
        $pay['autoApprovePlans'] = filter_var($pay['autoApprovePlans'], FILTER_VALIDATE_BOOLEAN);
        $pay['autoApproveLifetime'] = filter_var($pay['autoApproveLifetime'], FILTER_VALIDATE_BOOLEAN);
        $pay['creditsDailyCapPhp'] = max(0, min(100000, (int) $pay['creditsDailyCapPhp']));
        $pay['reviewHours'] = max(1, min(168, (int) $pay['reviewHours']));

        return $pay;
    }

    public static function savePay(array $values, ?User $by = null): void
    {
        $pay = array_intersect_key(array_replace(self::pay(), $values), self::PAY_DEFAULTS);
        StreamrSetting::setJson(self::PAY_KEY, $pay, $by?->id);
    }

    /** When the pay settings were last saved (null: never). */
    public static function payUpdatedAt(): ?string
    {
        try {
            return StreamrSetting::where('settingKey', self::PAY_KEY)->value('updated_at');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * The Streamr store in E-commerce > Stores: STREAMR_STORE_ID when set,
     * else the live row named STREAMR_STORE_NAME. Null until the first order
     * (which creates it) or until someone adds it.
     */
    public static function storeRow(): ?EcomProductStore
    {
        try {
            $id = config('streamr.store_id');
            if ($id) {
                $row = EcomProductStore::where('id', (int) $id)->where('deleteStatus', 1)->first();
                if ($row) {
                    return $row;
                }
            }

            return EcomProductStore::where('storeName', self::storeName())->where('deleteStatus', 1)->orderBy('id')->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** THE match key for Streamr's /ecom-orders items. */
    public static function storeName(): string
    {
        $name = trim((string) config('streamr.store_name', 'Streamr'));

        return $name !== '' ? $name : 'Streamr';
    }

    /** 09XXXXXXXXX from "+63 917 123 4567", "0917-123-4567", "639171234567"; other values as digits. */
    public static function localPhone(?string $phone): string
    {
        $digits = preg_replace('/\D/', '', (string) $phone) ?? '';
        if (str_starts_with($digits, '63') && strlen($digits) === 12) {
            return '0' . substr($digits, 2);
        }
        if (str_starts_with($digits, '9') && strlen($digits) === 10) {
            return '0' . $digits;
        }

        return $digits;
    }
}
