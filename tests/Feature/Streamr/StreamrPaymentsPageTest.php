<?php

namespace Tests\Feature\Streamr;

use App\Services\Streamr\StreamrSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Streamr > Payment reviews: only Streamr's orders, the AI checks from the
 * receipt, the receipt streamed to signed-in admins only, Verify/Reject on
 * the e-commerce module's own endpoint, and the payment settings.
 */
class StreamrPaymentsPageTest extends StreamrTestCase
{
    public function test_the_list_holds_only_streamr_orders_by_chip(): void
    {
        $this->postOrder()->assertOk();
        $this->postOrder(['uuid' => '2a6f3a5e-6a51-4f5a-9d43-1c9b5f1d2e72', 'number' => 'STR-20261108-8KQ4', 'ai.verdict' => 'pass',
            'decision' => ['auto_approved' => true, 'decided_by' => 'ai']])->assertOk();
        $this->otherOrder('ORD-20261108-ABCD');
        // A streamr_orders row whose /ecom-orders item is another store's is not Streamr's.
        $foreign = $this->otherOrder('STR-20261108-ZZZZ', 'Other store');
        DB::table('streamr_orders')->insert(['uuid' => '9f9f9f9f-0000-4000-8000-000000000001', 'orderNumber' => 'STR-20261108-ZZZZ', 'ecomOrderId' => $foreign,
            'streamrUserId' => 1, 'kind' => 'plan', 'itemKey' => 'x', 'sku' => 'x', 'variantName' => 'x', 'total' => 500, 'method' => 'manual_gcash',
            'delete_status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        $admin = $this->admin();

        $all = $this->actingAs($admin)->getJson(route('streamr-payments.data', ['status' => 'all']))->assertOk()->json('data');
        $this->assertSame(['STR-20261108-8KQ4', 'STR-20261108-7KQ4'], array_column($all['rows'], 'number'));
        $this->assertSame(['review' => 1, 'approved' => 1, 'auto7' => 1, 'rejected' => 0, 'closed' => 0, 'all' => 2], $all['counts']);

        $review = $this->actingAs($admin)->getJson(route('streamr-payments.data'))->assertOk()->json('data.rows');
        $this->assertSame(['STR-20261108-7KQ4'], array_column($review, 'number'));
        $this->assertSame(['fail', 'AI: failed', 'bg-danger'], [$review[0]['ai']['verdict'], $review[0]['ai']['label'], $review[0]['ai']['badge']]);
        $this->assertSame(['To review', 'bg-warning text-dark'], [$review[0]['status']['label'], $review[0]['status']['badge']]);

        $auto = $this->actingAs($admin)->getJson(route('streamr-payments.data', ['status' => 'auto7']))->assertOk()->json('data.rows');
        $this->assertSame(['STR-20261108-8KQ4'], array_column($auto, 'number'));
        $this->assertTrue($auto[0]['autoApproved']);

        $found = $this->actingAs($admin)->getJson(route('streamr-payments.data', ['status' => 'all', 'q' => '1023 796']))->assertOk()->json('data.rows');
        $this->assertCount(2, $found);
        $this->assertSame([], $this->actingAs($admin)->getJson(route('streamr-payments.data', ['status' => 'all', 'q' => 'nobody@nowhere']))->json('data.rows'));
    }

    public function test_the_review_json_has_the_facts_the_ai_checks_and_the_existing_verify_endpoint(): void
    {
        $this->postOrder()->assertOk();
        $paymentId = (int) DB::table('ecom_order_payments')->value('id');
        StreamrSettings::savePay(['source' => 'own', 'gcashNumber' => '09170001111', 'gcashNameMasked' => 'JU•••N D.']);

        $one = $this->actingAs($this->admin())->getJson(route('streamr-payments.one', ['number' => 'STR-20261108-7KQ4']))->assertOk()->json('data');

        $this->assertSame(['STR-20261108-7KQ4', '0b6f3a5e-6a51-4f5a-9d43-1c9b5f1d2e77', 'GCash QR', 11000.0], [$one['number'], $one['uuid'], $one['method'], (float) $one['total']]);
        $this->assertSame(['09170001111', 'JU•••N D.'], [$one['expected']['gcash']['number'], $one['expected']['gcash']['account_name_masked']]);
        $this->assertSame('1023 796 539709', $one['facts']['reference']);
        $proof = $one['proofs'][0];
        $this->assertSame(route('streamr-proofs.show', ['order' => 'STR-20261108-7KQ4', 'n' => 1]), $proof['url']);
        $this->assertSame(['fail', 62], [$proof['ai']['verdict'], $proof['ai']['score']]);
        $this->assertSame([false, null, true], array_column($proof['ai']['checks'], 'ok'));
        $this->assertSame([true, true, $paymentId], [$one['can']['verify'], $one['can']['reject'], $one['can']['paymentId']]);
        $this->assertEquals(11000, $one['can']['amount']);
        $this->assertSame(url('/ecom-order-payments/' . $paymentId . '/verify'), $one['links']['verify']);
        $this->assertSame(self::STREAMR . '/admin/billing-orders/0b6f3a5e-6a51-4f5a-9d43-1c9b5f1d2e77', $one['links']['streamr']);
        $this->assertSame(['Order created', 'Payment details submitted', 'Ai receipt check'], array_column($one['timeline'], 'what'));

        $this->actingAs($this->admin())->getJson(route('streamr-payments.one', ['number' => 'STR-20261108-9ZZZ']))->assertNotFound();
    }

    public function test_the_page_decides_through_ecom_orders_own_verify_endpoint(): void
    {
        $this->fakeStreamr();
        $this->postOrder()->assertOk();
        $admin = $this->admin();

        $page = $this->actingAs($admin)->get(route('streamr-payments.index', ['open' => 'STR-20261108-7KQ4']))->assertOk();
        $page->assertSee('ecom-order-payments', false)->assertSee("method: 'PUT'", false)->assertSee('STR-20261108-7KQ4', false);

        // What the page's button sends.
        $paymentId = (int) DB::table('ecom_order_payments')->value('id');
        $this->actingAs($admin)->putJson('/ecom-order-payments/' . $paymentId . '/verify', ['action' => 'verify', 'amountVerified' => '11000.00'])
            ->assertOk()->assertJsonPath('success', true);
        $this->assertCount(1, $this->streamrCalls());   // the doorbell, after that response

        $one = $this->actingAs($admin)->getJson(route('streamr-payments.one', ['number' => 'STR-20261108-7KQ4']))->assertOk()->json('data');
        $this->assertSame(['approved', false, false], [$one['status']['key'], $one['can']['verify'], $one['can']['reject']]);
        $this->assertSame('Test Admin', $one['payments'][0]['verifiedBy']);
        $this->assertNotNull($one['payments'][0]['invoiceUrl']);
    }

    public function test_the_receipt_is_streamed_only_to_signed_in_admins(): void
    {
        $bytes = $this->receiptBytes();
        $this->postOrder([], $bytes)->assertOk();
        $url = route('streamr-proofs.show', ['order' => 'STR-20261108-7KQ4', 'n' => 1]);

        $this->get($url)->assertRedirect(route('login'));

        $response = $this->actingAs($this->admin())->get($url)->assertOk();
        $this->assertSame($bytes, $response->getContent());
        $response->assertHeader('Content-Type', 'image/jpeg')
            ->assertHeader('Content-Disposition', 'inline; filename="STR-20261108-7KQ4-1.jpg"')
            ->assertHeader('X-Robots-Tag', 'noindex')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $cache = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cache);
        $this->assertStringContainsString('private', $cache);

        // /ecom-orders opens the same address (asset() of the payment's screenshot).
        $this->assertSame($url, asset(DB::table('ecom_order_payments')->value('screenshot')));

        $this->actingAs($this->admin())->get(route('streamr-proofs.show', ['order' => 'STR-20261108-7KQ4', 'n' => 2]))->assertNotFound();
        $this->actingAs($this->admin())->get(route('streamr-proofs.show', ['order' => 'nope', 'n' => 1]))->assertNotFound();
    }

    public function test_payment_settings_are_validated_and_saved(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('streamr-payments.settings'), ['source' => 'own', 'gcashNumber' => '12345', 'creditsDailyCapPhp' => 2500, 'reviewHours' => 24])
            ->assertRedirect(route('streamr-payments.index', ['tab' => 'settings']))->assertSessionHasErrors('gcashNumber');
        $this->actingAs($admin)->post(route('streamr-payments.settings'), ['source' => 'own', 'creditsDailyCapPhp' => 200000, 'reviewHours' => 500])
            ->assertSessionHasErrors(['creditsDailyCapPhp', 'reviewHours']);

        $this->actingAs($admin)->post(route('streamr-payments.settings'), [
            'source' => 'own', 'gcashNumber' => '+63 917 000 1111', 'gcashNameMasked' => 'JU•••N D.', 'qrDisplayName' => 'JUAN D.',
            'bankName' => 'BPI', 'bankAccountName' => 'DSWC', 'bankAccountNumber' => '123', 'receiptGuide' => 'Green border.',
            'autoApprovePlans' => '0', 'autoApproveLifetime' => '1', 'creditsDailyCapPhp' => 1000, 'reviewHours' => 8,
        ])->assertRedirect(route('streamr-payments.index', ['tab' => 'settings']))->assertSessionHas('success');

        $pay = StreamrSettings::pay();
        $this->assertSame(['own', '09170001111', 'JU•••N D.', false, true, 1000, 8],
            [$pay['source'], $pay['gcashNumber'], $pay['gcashNameMasked'], $pay['autoApprovePlans'], $pay['autoApproveLifetime'], $pay['creditsDailyCapPhp'], $pay['reviewHours']]);
        $this->assertSame($admin->id, (int) DB::table('streamr_settings')->where('settingKey', 'pay')->value('updatedBy'));

        $config = $this->signedGet('/api/streamr/v1/config')->assertOk()->json('data.payment');
        $this->assertSame(['plans' => false, 'lifetime' => true, 'credits_daily_cap_php' => 1000], $config['auto_approve']);
        $this->assertSame('09170001111', $config['gcash']['number']);
    }

    public function test_a_qr_is_uploaded_to_the_public_disk_and_replaces_the_old_one(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('streamr-payments.qr', ['method' => 'gcash']), ['qr' => UploadedFile::fake()->createWithContent('qr.png', $this->pngBytes())])
            ->assertRedirect(route('streamr-payments.index', ['tab' => 'settings']))->assertSessionHas('success');
        $first = StreamrSettings::pay()['gcashQr'];
        $this->assertMatchesRegularExpression('#^streamr/pay/gcash-qr-[a-z0-9]{16}\.png$#', $first);
        Storage::disk('public')->assertExists($first);

        $this->actingAs($admin)->post(route('streamr-payments.qr', ['method' => 'gcash']), ['qr' => UploadedFile::fake()->createWithContent('qr2.png', $this->pngBytes(80))]);
        $second = StreamrSettings::pay()['gcashQr'];
        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);

        $this->assertNotNull($this->signedGet('/api/streamr/v1/config')->assertOk()->json('data.payment.gcash.qr_image_base64'));

        // Not a picture.
        $this->actingAs($admin)->post(route('streamr-payments.qr', ['method' => 'maya']), ['qr' => UploadedFile::fake()->createWithContent('qr.png', 'not an image')])
            ->assertSessionHasErrors('qr');
        $this->assertSame('', StreamrSettings::pay()['mayaQr']);

        $this->actingAs($admin)->post(route('streamr-payments.qr', ['method' => 'gcash']), ['remove' => 1])->assertSessionHas('success');
        $this->assertSame('', StreamrSettings::pay()['gcashQr']);
        Storage::disk('public')->assertMissing($second);
    }
}
