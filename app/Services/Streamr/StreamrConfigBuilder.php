<?php

namespace App\Services\Streamr;

use App\Models\EcomStorePaymentSetting;
use Illuminate\Support\Facades\Storage;

/**
 * What Streamr mirrors from this app (GET /api/streamr/v1/config): where buyers
 * send money (GCash, Maya, bank), the QR codes as PNG, what the AI may approve,
 * how long a person takes to check, and the support categories.
 *
 * With source "ecom" the numbers, names, on/off switches, QR files and the
 * instructions come from E-commerce > Stores > Streamr > Payment
 * (ecom_store_payment_settings), and the Streamr settings fill what that
 * table does not hold (the name as receipts print it, QR names, branch, the
 * AI guide, auto-approval, review hours) and override any field they set.
 * With source "own" only the Streamr settings count. Never a secret.
 */
final class StreamrConfigBuilder
{
    private const QR_MAX_BYTES = 4194304;

    /** GD holds every pixel in memory; a bigger picture is passed on as it is (an out-of-memory error cannot be caught). */
    private const QR_MAX_PIXELS = 25000000;

    private const IMAGE_MIMES = ['image/png', 'image/jpeg', 'image/webp', 'image/gif'];

    public function build(): array
    {
        $pay = StreamrSettings::pay();
        $store = StreamrSettings::storeRow();
        $ecom = $this->ecomRow($pay, $store);
        $details = $this->receiving($pay, $ecom);

        $gcashQr = $this->qr($ecom?->qrCodeImage, $pay['gcashQr']);
        $mayaQr = $this->qr($ecom?->mayaQrCodeImage, $pay['mayaQr']);

        return [
            'store' => ['name' => StreamrSettings::storeName(), 'id' => $store ? (int) $store->id : null],
            'payment' => [
                'gcash' => $details['gcash'] + [
                    'qr_image_base64' => $gcashQr['base64'],
                    'qr_sha256' => $gcashQr['sha256'],
                    'qr_mime' => $gcashQr['mime'],
                    'qr_display_name' => $pay['qrDisplayName'] !== '' ? $pay['qrDisplayName'] : null,
                    'qr_account' => $pay['qrAccount'] !== '' ? $pay['qrAccount'] : null,
                ],
                'maya' => $details['maya'] + [
                    'qr_image_base64' => $mayaQr['base64'],
                    'qr_sha256' => $mayaQr['sha256'],
                    'qr_mime' => $mayaQr['mime'],
                ],
                'bank' => $details['bank'],
                'instructions' => $details['instructions'],
                'receipt_guide_extra' => $pay['receiptGuide'] !== '' ? $pay['receiptGuide'] : null,
                'auto_approve' => [
                    'plans' => (bool) $pay['autoApprovePlans'],
                    'lifetime' => (bool) $pay['autoApproveLifetime'],
                    'credits_daily_cap_php' => (int) $pay['creditsDailyCapPhp'],
                ],
                'review_hours' => (int) $pay['reviewHours'],
            ],
            'support' => ['categories' => array_values((array) config('streamr.support_categories', []))],
            'updated_at' => $this->updatedAt($ecom?->updated_at, StreamrSettings::payUpdatedAt()),
        ];
    }

    /**
     * Where buyers send money, without the QR pictures (the review modal's
     * "Expected" box): {gcash, maya, bank} with enabled, numbers and names.
     */
    public function receiving(?array $pay = null, $ecom = false): array
    {
        $pay ??= StreamrSettings::pay();
        if ($ecom === false) {
            $ecom = $this->ecomRow($pay, StreamrSettings::storeRow());
        }

        $pick = fn (string $own, ?string $fromEcom) => $own !== '' ? $own : trim((string) $fromEcom);

        $gcashNumber = StreamrSettings::localPhone($pick($pay['gcashNumber'], $ecom?->gcashNumber));
        $gcashName = $pick($pay['gcashNameMasked'], $ecom?->gcashAccountName);
        $mayaNumber = StreamrSettings::localPhone($pick($pay['mayaNumber'], $ecom?->mayaNumber));
        $mayaName = $pick($pay['mayaName'], $ecom?->mayaAccountName);
        $bankName = $pick($pay['bankName'], $ecom?->bankName);
        $bankAccountName = $pick($pay['bankAccountName'], $ecom?->bankAccountName);
        $bankAccountNumber = $pick($pay['bankAccountNumber'], $ecom?->bankAccountNumber);

        $gcashComplete = $gcashNumber !== '' && $gcashName !== '';
        $mayaComplete = $mayaNumber !== '' && $mayaName !== '';
        $bankComplete = $bankName !== '' && $bankAccountName !== '' && $bankAccountNumber !== '';

        // On/off: the store's switches when it has payment settings, else "the details are complete".
        $gcashOn = $gcashComplete && ($ecom ? (bool) $ecom->isGcashActive : true);
        $mayaOn = $mayaComplete && ($ecom ? (bool) $ecom->isMayaActive : true);
        $bankOn = $bankComplete && ($ecom ? (bool) $ecom->isBankActive : true);

        $instructions = $pick($pay['instructions'], $ecom?->paymentInstructions);

        return [
            'gcash' => [
                'enabled' => $gcashOn,
                'number' => $gcashNumber !== '' ? $gcashNumber : null,
                'account_name_masked' => $gcashName !== '' ? $gcashName : null,
            ],
            'maya' => [
                'enabled' => $mayaOn,
                'number' => $mayaNumber !== '' ? $mayaNumber : null,
                'account_name_masked' => $mayaName !== '' ? $mayaName : null,
            ],
            'bank' => [
                'enabled' => $bankOn,
                'bank_name' => $bankName !== '' ? $bankName : null,
                'account_name' => $bankAccountName !== '' ? $bankAccountName : null,
                'account_number' => $bankAccountNumber !== '' ? $bankAccountNumber : null,
                'branch' => $pay['bankBranch'] !== '' ? $pay['bankBranch'] : null,
            ],
            'instructions' => $instructions !== '' ? $instructions : null,
            'source' => $pay['source'],
            'ecomRowFound' => (bool) $ecom,
        ];
    }

    /** The store's live payment settings row, used only with source "ecom". */
    private function ecomRow(array $pay, $store): ?EcomStorePaymentSetting
    {
        if ($pay['source'] !== 'ecom' || ! $store) {
            return null;
        }
        try {
            return EcomStorePaymentSetting::where('storeId', $store->id)->where('deleteStatus', 1)->orderByDesc('id')->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Whether the GCash QR Streamr would show can be read (Link status). */
    public function gcashQrReadable(): bool
    {
        return $this->build()['payment']['gcash']['qr_image_base64'] !== null;
    }

    /**
     * A QR as PNG: the store's file (under public/) when it can be read, else
     * the one uploaded in Streamr's payment settings (public disk, which
     * survives deploys). Converted with GD; when GD cannot, the image as it is.
     *
     * @return array{base64: ?string, sha256: ?string, mime: ?string}
     */
    public function qr(?string $ecomPath, string $uploaded): array
    {
        foreach ([fn () => $this->readPublicFile($ecomPath), fn () => $this->readDisk($uploaded)] as $read) {
            $bytes = $read();
            if ($bytes !== null && ($image = $this->asPng($bytes)) !== null) {
                return $image;
            }
        }

        return ['base64' => null, 'sha256' => null, 'mime' => null];
    }

    private function readPublicFile(?string $path): ?string
    {
        $path = ltrim(str_replace('\\', '/', trim((string) $path)), '/');
        if ($path === '' || str_contains($path, '..') || preg_match('#^[a-z]+://#i', $path)
            || ! preg_match('/\.(png|jpe?g|webp|gif)$/i', $path)) {
            return null;
        }

        $root = realpath(public_path());
        $file = realpath(public_path($path));
        if ($root === false || $file === false || ! str_starts_with($file, $root . DIRECTORY_SEPARATOR) || ! is_file($file)) {
            return null;
        }
        if (filesize($file) > self::QR_MAX_BYTES) {
            return null;
        }
        $bytes = @file_get_contents($file);

        return is_string($bytes) && $bytes !== '' ? $bytes : null;
    }

    private function readDisk(string $path): ?string
    {
        $path = trim($path);
        if ($path === '' || str_contains($path, '..')) {
            return null;
        }
        try {
            $disk = Storage::disk('public');
            if (! $disk->exists($path) || (int) $disk->size($path) > self::QR_MAX_BYTES) {
                return null;
            }
            $bytes = $disk->get($path);

            return is_string($bytes) && $bytes !== '' ? $bytes : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** @return array{base64: string, sha256: string, mime: string}|null */
    private function asPng(string $bytes): ?array
    {
        $mime = $this->mimeOf($bytes);
        if (! in_array($mime, self::IMAGE_MIMES, true)) {
            return null;
        }

        $size = @getimagesizefromstring($bytes);
        $decodable = is_array($size) && (int) $size[0] > 0 && (int) $size[1] > 0 && (int) $size[0] * (int) $size[1] <= self::QR_MAX_PIXELS;
        if ($decodable && function_exists('imagecreatefromstring') && function_exists('imagepng')) {
            $image = @imagecreatefromstring($bytes);
            if ($image !== false) {
                ob_start();
                imagepng($image);
                $png = (string) ob_get_clean();
                imagedestroy($image);
                if ($png !== '') {
                    return ['base64' => base64_encode($png), 'sha256' => hash('sha256', $png), 'mime' => 'image/png'];
                }
            }
        }

        return ['base64' => base64_encode($bytes), 'sha256' => hash('sha256', $bytes), 'mime' => $mime];
    }

    private function mimeOf(string $bytes): ?string
    {
        try {
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);

            return is_string($mime) ? strtolower($mime) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function updatedAt($ecom, $own): ?string
    {
        $times = array_filter([StreamrTime::parse($ecom), StreamrTime::parse($own)]);
        if ($times === []) {
            return null;
        }
        usort($times, fn ($a, $b) => $b <=> $a);

        return $times[0]->toIso8601String();
    }
}
