<?php

namespace Tests\Feature\Streamr;

use App\Models\EcomOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

/**
 * Nothing the e-commerce module did before changes: another store's order is
 * verified, rejected and moved exactly as before through OrdersController,
 * with no call to Streamr and no Streamr row; /ecom-orders still renders; a
 * Streamr order's payment in /ecom-orders is its own row (no auto-migration)
 * whose picture is the private proof; and the migration only ever creates
 * (and drops) its own streamr_* tables.
 */
class StreamrEcomUnchangedTest extends StreamrTestCase
{
    public function test_another_stores_order_is_verified_exactly_as_before_and_streamr_hears_nothing(): void
    {
        Http::fake();
        $id = $this->otherOrder('ORD-20261108-ABCD');
        $paymentId = (int) DB::table('ecom_order_payments')->where('orderId', $id)->value('id');
        $admin = $this->admin();

        $this->actingAs($admin)->putJson('/ecom-order-payments/' . $paymentId . '/verify', ['action' => 'verify', 'notes' => 'Seen in GCash'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Payment verified successfully')
            ->assertJsonPath('orderStatus', 'complete');

        $payment = DB::table('ecom_order_payments')->find($paymentId);
        $this->assertSame(['verified', $admin->id], [$payment->paymentStatus, (int) $payment->verifiedBy]);
        $this->assertEquals(500, (float) $payment->amountVerified);
        $this->assertMatchesRegularExpression('/^INV-\d{6}-\d{5}-01$/', $payment->invoiceNumber);
        $this->assertMatchesRegularExpression('/\] Verified: Seen in GCash$/', $payment->verificationNotes);
        $order = DB::table('ecom_orders')->find($id);
        $this->assertSame(['complete', 'verified'], [$order->orderStatus, $order->paymentVerificationStatus]);
        $this->assertSame(['status_change', 'payment_verified'], DB::table('ecom_order_audit_logs')->where('orderId', $id)->orderBy('id')->pluck('actionType')->all());

        Http::assertNothingSent();
        foreach (\App\Http\Controllers\Streamr\StreamrController::TABLES as $table) {
            $this->assertSame(0, DB::table($table)->count(), $table);
        }
    }

    public function test_another_stores_order_is_rejected_and_moved_exactly_as_before(): void
    {
        Http::fake();
        $id = $this->otherOrder('ORD-20261108-ABCD');
        $paymentId = (int) DB::table('ecom_order_payments')->where('orderId', $id)->value('id');
        $admin = $this->admin();

        $this->actingAs($admin)->putJson('/ecom-order-payments/' . $paymentId . '/verify', ['action' => 'reject', 'notes' => 'No money'])
            ->assertOk()->assertJsonPath('message', 'Payment rejected successfully');
        $this->assertSame('rejected', DB::table('ecom_orders')->where('id', $id)->value('paymentVerificationStatus'));

        $this->actingAs($admin)->putJson('/ecom-orders/' . $id . '/status', ['status' => 'cancelled', 'confirmationToken' => 'CONFIRM'])
            ->assertOk()->assertJsonPath('order.orderStatus', 'cancelled');
        $this->actingAs($admin)->putJson('/ecom-orders/' . $id . '/status', ['status' => 'pending'])
            ->assertStatus(400)->assertJsonPath('isFinal', true);

        Http::assertNothingSent();
        $this->assertSame(0, DB::table('streamr_api_requests')->count());
    }

    public function test_the_ecom_orders_page_and_the_payments_modal_still_work(): void
    {
        $this->fakeStreamr();
        $this->postOrder()->assertOk();
        $streamrId = (int) DB::table('ecom_orders')->where('orderNumber', 'STR-20261108-7KQ4')->value('id');
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('ecom-orders'))->assertOk();

        // A Streamr order always has its own payment: opening Payments adds none.
        $payments = $this->actingAs($admin)->getJson('/ecom-orders/' . $streamrId . '/payments')->assertOk()->json();
        $this->assertCount(1, $payments['payments']);
        $this->assertSame(1, DB::table('ecom_order_payments')->where('orderId', $streamrId)->count());
        $this->assertSame(route('streamr-proofs.show', ['order' => 'STR-20261108-7KQ4', 'n' => 1]), $payments['payments'][0]['screenshotUrl']);
        $this->assertStringContainsString('Streamr AI receipt check: FAIL', $payments['payments'][0]['verificationNotes']);

        // The View modal: the item under store Streamr, an access product, the notes.
        $details = $this->actingAs($admin)->getJson('/ecom-orders/' . $streamrId . '/details')->assertOk()->json();
        $this->assertTrue($details['success']);
        $this->assertSame(['Streamr', 'access', 'streamr:plan:yearly'], [$details['order']['items'][0]['productStore'],
            $details['order']['items'][0]['productType'], $details['order']['items'][0]['variantSku']]);
        $this->assertStringContainsString('Streamr account #42', $details['order']['orderNotes']);
        $this->assertSame('GCash (Manual)', $details['order']['paymentMethodLabel']);
        $this->assertTrue($details['order']['requiresPaymentVerification']);
    }

    public function test_the_observer_is_the_only_new_listener_on_ecom_orders(): void
    {
        $dispatcher = EcomOrder::getEventDispatcher();

        $this->assertTrue($dispatcher->hasListeners('eloquent.updated: ' . EcomOrder::class));
        foreach (['created', 'creating', 'saving', 'saved', 'deleting', 'deleted', 'updating'] as $event) {
            $this->assertFalse($dispatcher->hasListeners('eloquent.' . $event . ': ' . EcomOrder::class), $event);
        }
    }

    public function test_the_migration_creates_and_drops_only_its_own_tables(): void
    {
        $migration = require base_path('database/migrations/2026_11_20_100000_create_streamr_module_tables.php');
        $before = $this->tables();

        $migration->down();
        $dropped = array_values(array_diff($before, $this->tables()));
        sort($dropped);
        $this->assertSame([
            'streamr_api_requests', 'streamr_client_actions', 'streamr_clients', 'streamr_desk_messages', 'streamr_desk_tickets',
            'streamr_orders', 'streamr_payment_proofs', 'streamr_settings',
        ], $dropped);

        $migration->up();
        $this->assertSame($before, $this->tables());

        // Run again over existing tables: nothing happens (hasTable guards).
        $migration->up();
        $this->assertSame($before, $this->tables());
    }

    private function tables(): array
    {
        $names = array_map(fn ($t) => is_array($t) ? $t['name'] : $t->name, Schema::getTables());
        sort($names);

        return $names;
    }
}
