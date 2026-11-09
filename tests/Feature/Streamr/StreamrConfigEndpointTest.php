<?php

namespace Tests\Feature\Streamr;

use App\Models\EcomProductStore;
use App\Services\Streamr\StreamrSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * GET /api/streamr/v1/config: the Streamr store's receiving details from
 * E-commerce > Stores (ecom_store_payment_settings) plus the module's own
 * settings, the QR as a PNG (base64 + sha256 + mime), and with source "own"
 * only the module's settings.
 */
class StreamrConfigEndpointTest extends StreamrTestCase
{
    private function store(): EcomProductStore
    {
        return EcomProductStore::create(['storeName' => 'Streamr', 'storeDescription' => 'x', 'isActive' => 1, 'deleteStatus' => 1]);
    }

    private function ecomSettings(int $storeId, array $over = []): void
    {
        DB::table('ecom_store_payment_settings')->insert(array_merge([
            'storeId' => $storeId,
            'gcashNumber' => '+63 917 000 1111', 'gcashAccountName' => 'Juan Dela Cruz', 'qrCodeImage' => 'build/images/logo-dark.png', 'isGcashActive' => 1,
            'mayaNumber' => null, 'mayaAccountName' => null, 'isMayaActive' => 0,
            'bankName' => 'BPI', 'bankAccountName' => 'Dragon Scale Web Company', 'bankAccountNumber' => '1234-5678-90', 'isBankActive' => 1,
            'paymentInstructions' => 'Send the exact amount.', 'isActive' => 0, 'deleteStatus' => 1,
            'created_at' => '2026-11-08 09:00:00', 'updated_at' => '2026-11-08 09:00:00',
        ], $over));
    }

    public function test_ecom_source_mirrors_the_store_payment_settings_plus_the_module_settings(): void
    {
        $store = $this->store();
        $this->ecomSettings($store->id);
        StreamrSettings::savePay([
            'gcashNameMasked' => 'JU•••N D.', 'qrDisplayName' => 'JUAN D.', 'qrAccount' => '•••• 1111', 'bankBranch' => 'Ortigas',
            'receiptGuide' => 'Our QR shows a green border.', 'autoApproveLifetime' => true, 'creditsDailyCapPhp' => 3000, 'reviewHours' => 12,
        ]);

        $response = $this->signedGet('/api/streamr/v1/config')->assertOk()->assertJsonPath('success', true);
        $data = $response->json('data');

        $this->assertSame(['name' => 'Streamr', 'id' => $store->id], $data['store']);
        $gcash = $data['payment']['gcash'];
        $this->assertTrue($gcash['enabled']);
        $this->assertSame('09170001111', $gcash['number']);
        $this->assertSame('JU•••N D.', $gcash['account_name_masked']);
        $this->assertSame('JUAN D.', $gcash['qr_display_name']);
        $this->assertSame('•••• 1111', $gcash['qr_account']);
        $png = base64_decode($gcash['qr_image_base64'], true);
        $this->assertSame("\x89PNG", substr($png, 0, 4));
        $this->assertSame(hash('sha256', $png), $gcash['qr_sha256']);
        $this->assertSame('image/png', $gcash['qr_mime']);

        $this->assertFalse($data['payment']['maya']['enabled']);
        $this->assertNull($data['payment']['maya']['qr_image_base64']);
        $this->assertSame(['enabled' => true, 'bank_name' => 'BPI', 'account_name' => 'Dragon Scale Web Company', 'account_number' => '1234-5678-90', 'branch' => 'Ortigas'],
            $data['payment']['bank']);
        $this->assertSame('Send the exact amount.', $data['payment']['instructions']);
        $this->assertSame('Our QR shows a green border.', $data['payment']['receipt_guide_extra']);
        $this->assertSame(['plans' => true, 'lifetime' => true, 'credits_daily_cap_php' => 3000], $data['payment']['auto_approve']);
        $this->assertSame(12, $data['payment']['review_hours']);
        $this->assertSame(['general', 'billing', 'live', 'store', 'technical'], array_column($data['support']['categories'], 'key'));
        $this->assertStringEndsWith('+08:00', $data['updated_at']);
        $this->assertStringNotContainsString(self::SECRET, $response->getContent());
    }

    public function test_a_switched_off_method_is_not_offered_and_a_lost_qr_falls_back_to_the_uploaded_one(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('streamr/pay/gcash-qr-abc.png', $this->pngBytes());
        $store = $this->store();
        $this->ecomSettings($store->id, ['qrCodeImage' => 'images/ecommerce/payments/payment_gcash_1_gone.png', 'isBankActive' => 0]);
        StreamrSettings::savePay(['gcashQr' => 'streamr/pay/gcash-qr-abc.png']);

        $data = $this->signedGet('/api/streamr/v1/config')->assertOk()->json('data.payment');

        $this->assertFalse($data['bank']['enabled']);
        // No masked name set here: the store's account name stands in.
        $this->assertSame('Juan Dela Cruz', $data['gcash']['account_name_masked']);
        $this->assertSame("\x89PNG", substr(base64_decode($data['gcash']['qr_image_base64']), 0, 4));
    }

    public function test_a_huge_qr_picture_is_passed_on_as_it_is_never_decoded(): void
    {
        // A tiny file that claims 10,000 x 10,000 pixels: GD would need ~400 MB for it.
        $chunk = fn (string $type, string $data) => pack('N', strlen($data)) . $type . $data . pack('N', crc32($type . $data));
        $bomb = "\x89PNG\r\n\x1a\n" . $chunk('IHDR', pack('NNCCCCC', 10000, 10000, 8, 2, 0, 0, 0)) . $chunk('IDAT', (string) gzcompress('')) . $chunk('IEND', '');
        Storage::fake('public');
        Storage::disk('public')->put('streamr/pay/gcash-qr-big.png', $bomb);
        StreamrSettings::savePay(['source' => 'own', 'gcashNumber' => '09170001111', 'gcashNameMasked' => 'JU•••N D.', 'gcashQr' => 'streamr/pay/gcash-qr-big.png']);

        memory_reset_peak_usage();
        $before = memory_get_peak_usage();
        $gcash = $this->signedGet('/api/streamr/v1/config')->assertOk()->json('data.payment.gcash');

        $this->assertLessThan(64 * 1048576, memory_get_peak_usage() - $before, 'GD must not have decoded the picture.');
        $this->assertSame($bomb, base64_decode($gcash['qr_image_base64'], true));
        $this->assertSame([hash('sha256', $bomb), 'image/png'], [$gcash['qr_sha256'], $gcash['qr_mime']]);
    }

    public function test_a_qr_path_outside_public_is_never_read(): void
    {
        $store = $this->store();
        $this->ecomSettings($store->id, ['qrCodeImage' => '../.env.png']);

        $this->assertNull($this->signedGet('/api/streamr/v1/config')->assertOk()->json('data.payment.gcash.qr_image_base64'));
    }

    public function test_own_source_uses_only_the_module_settings(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('streamr/pay/maya-qr-xyz.png', $this->pngBytes());
        $store = $this->store();
        $this->ecomSettings($store->id);
        StreamrSettings::savePay([
            'source' => 'own', 'gcashNumber' => '09281112222', 'gcashNameMasked' => 'MA•••A S.',
            'mayaNumber' => '09993334444', 'mayaName' => 'MA•••A S.', 'mayaQr' => 'streamr/pay/maya-qr-xyz.png',
        ]);

        $data = $this->signedGet('/api/streamr/v1/config')->assertOk()->json('data.payment');

        $this->assertSame(['09281112222', 'MA•••A S.', true], [$data['gcash']['number'], $data['gcash']['account_name_masked'], $data['gcash']['enabled']]);
        $this->assertNull($data['gcash']['qr_image_base64']);          // the store's QR is not used
        $this->assertTrue($data['maya']['enabled']);
        $this->assertNotNull($data['maya']['qr_image_base64']);
        $this->assertFalse($data['bank']['enabled']);                    // incomplete here
        $this->assertNull($data['bank']['bank_name']);
        $this->assertNull($data['instructions']);
    }

    public function test_without_a_store_the_module_settings_alone_answer(): void
    {
        StreamrSettings::savePay(['gcashNumber' => '09170001111', 'gcashNameMasked' => 'JU•••N D.']);

        $data = $this->signedGet('/api/streamr/v1/config')->assertOk()->json('data');

        $this->assertNull($data['store']['id']);
        $this->assertTrue($data['payment']['gcash']['enabled']);
    }
}
