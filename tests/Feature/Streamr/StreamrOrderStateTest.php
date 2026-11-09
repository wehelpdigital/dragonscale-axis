<?php

namespace Tests\Feature\Streamr;

use Illuminate\Support\Facades\DB;

/**
 * GET /api/streamr/v1/orders/{number} and the batch read, after decisions
 * made through the e-commerce module's own code (OrdersController) as a
 * signed-in admin: verify, reject with a reason, a partial verify, Status >
 * Complete and a refund.
 */
class StreamrOrderStateTest extends StreamrTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeStreamr();
    }

    private function paymentId(): int
    {
        return (int) DB::table('ecom_order_payments')->orderByDesc('id')->value('id');
    }

    public function test_a_verified_order_reports_who_decided_and_the_invoice(): void
    {
        $this->postOrder()->assertOk();
        $admin = $this->admin('Jun Reyes', 'jun@example.test');

        $this->actingAs($admin)->putJson('/ecom-order-payments/' . $this->paymentId() . '/verify', ['action' => 'verify'])->assertOk();

        $state = $this->signedGet('/api/streamr/v1/orders/STR-20261108-7KQ4')->assertOk()->json('data');
        $this->assertSame('STR-20261108-7KQ4', $state['number']);
        $this->assertSame((int) DB::table('ecom_orders')->value('id'), $state['mother_order_id']);
        $this->assertSame(['complete', 'verified', 1], [$state['order_status'], $state['payment_verification_status'], $state['delete_status']]);
        $this->assertSame('Jun Reyes', $state['decided']['by_name']);
        $this->assertStringEndsWith('+08:00', $state['decided']['at']);
        $token = DB::table('ecom_order_payments')->value('invoiceToken');
        $this->assertSame(url('/invoice/' . $token), $state['invoice_url']);
        $this->assertNull($state['reject_reason']);
        $this->assertSame([['verified', '11000.00', '11000.00', 'Jun Reyes']], array_map(fn ($p) => [$p['status'], $p['amount_sent'], $p['amount_verified'], $p['verified_by_name']], $state['payments']));
        $this->assertStringEndsWith('+08:00', $state['updated_at']);
    }

    public function test_a_rejected_order_reports_the_reason_the_admin_typed(): void
    {
        $this->postOrder()->assertOk();

        $this->actingAs($this->admin())->putJson('/ecom-order-payments/' . $this->paymentId() . '/verify', [
            'action' => 'reject', 'notes' => 'The ₱11,000.00 did not arrive in our GCash.',
        ])->assertOk();

        $state = $this->signedGet('/api/streamr/v1/orders/STR-20261108-7KQ4')->assertOk()->json('data');
        $this->assertSame(['pending', 'rejected'], [$state['order_status'], $state['payment_verification_status']]);
        $this->assertSame('The ₱11,000.00 did not arrive in our GCash.', $state['reject_reason']);
        $this->assertSame('Test Admin', $state['decided']['by_name']);
        $this->assertNull($state['invoice_url']);
    }

    public function test_a_reason_typed_on_several_lines_reaches_streamr_whole(): void
    {
        $this->postOrder()->assertOk();

        $this->actingAs($this->admin())->putJson('/ecom-order-payments/' . $this->paymentId() . '/verify', [
            'action' => 'reject', 'notes' => "The amount did not arrive.\r\nPlease send the receipt of the ₱11,000.00 transfer.",
        ])->assertOk();
        // A later note (e.g. a Streamr re-check) does not run into the reason.
        $notes = (string) DB::table('ecom_order_payments')->where('id', $this->paymentId())->value('verificationNotes');
        DB::table('ecom_order_payments')->where('id', $this->paymentId())
            ->update(['verificationNotes' => $notes . "\n[2026-11-08 16:00] Streamr AI re-check: PASS.\n  ✓ The amount is ₱11,000.00"]);

        $state = $this->signedGet('/api/streamr/v1/orders/STR-20261108-7KQ4')->assertOk()->json('data');
        $this->assertSame("The amount did not arrive.\nPlease send the receipt of the ₱11,000.00 transfer.", $state['reject_reason']);
    }

    public function test_a_partial_verification_keeps_the_order_pending(): void
    {
        $this->postOrder()->assertOk();

        $this->actingAs($this->admin())->putJson('/ecom-order-payments/' . $this->paymentId() . '/verify', ['action' => 'verify', 'amountVerified' => 5000])->assertOk();

        $state = $this->signedGet('/api/streamr/v1/orders/STR-20261108-7KQ4')->assertOk()->json('data');
        $this->assertSame(['pending', 'pending'], [$state['order_status'], $state['payment_verification_status']]);
        $this->assertSame('5000.00', $state['payments'][0]['amount_verified']);
    }

    public function test_status_complete_and_then_refunded_are_reported(): void
    {
        $this->postOrder()->assertOk();
        $orderId = (int) DB::table('ecom_orders')->value('id');
        $admin = $this->admin();

        $this->actingAs($admin)->putJson('/ecom-orders/' . $orderId . '/status', ['status' => 'complete', 'confirmationToken' => 'CONFIRM'])->assertOk();
        $state = $this->signedGet('/api/streamr/v1/orders/STR-20261108-7KQ4')->assertOk()->json('data');
        $this->assertSame(['complete', 'pending'], [$state['order_status'], $state['payment_verification_status']]);
        $this->assertSame('Test Admin', $state['decided']['by_name']);

        $this->actingAs($admin)->putJson('/ecom-orders/' . $orderId . '/status', ['status' => 'refunded', 'confirmationToken' => 'CONFIRM'])->assertOk();
        $this->assertSame('refunded', $this->signedGet('/api/streamr/v1/orders/STR-20261108-7KQ4')->assertOk()->json('data.order_status'));
    }

    public function test_the_batch_read_answers_orders_and_missing(): void
    {
        $this->postOrder()->assertOk();
        $this->postOrder(['uuid' => '2a6f3a5e-6a51-4f5a-9d43-1c9b5f1d2e72', 'number' => 'STR-20261108-8KQ4'])->assertOk();

        $data = $this->signedGet('/api/streamr/v1/orders', ['numbers' => ['STR-20261108-7KQ4', 'STR-20261108-9ZZZ', 'STR-20261108-8KQ4']])->assertOk()->json('data');

        $this->assertSame(['STR-20261108-7KQ4', 'STR-20261108-8KQ4'], array_column($data['orders'], 'number'));
        $this->assertSame(['STR-20261108-9ZZZ'], $data['missing']);

        $many = array_map(fn ($i) => sprintf('STR-20261108-%04d', $i), range(1, 51));
        $this->signedGet('/api/streamr/v1/orders', ['numbers' => $many])->assertStatus(422)->assertJsonPath('error.code', 'validation_failed');
        $this->signedGet('/api/streamr/v1/orders')->assertStatus(422);
    }

    public function test_an_order_that_is_not_streamrs_is_unknown(): void
    {
        $this->otherOrder('STR-20261108-ZZZZ', 'Dragon Scale Store');

        $this->signedGet('/api/streamr/v1/orders/STR-20261108-ZZZZ')->assertStatus(404)->assertJsonPath('error.code', 'unknown_order');
        $this->signedGet('/api/streamr/v1/orders/STR-20261108-9ZZZ')->assertStatus(404)->assertJsonPath('error.code', 'unknown_order');
    }

    public function test_an_auto_approved_order_names_streamr_ai_as_the_decider(): void
    {
        $this->postOrder(['ai.verdict' => 'pass', 'decision' => ['auto_approved' => true, 'decided_by' => 'ai']])->assertOk();

        $state = $this->signedGet('/api/streamr/v1/orders/STR-20261108-7KQ4')->assertOk()->json('data');
        $this->assertSame('Streamr AI', $state['decided']['by_name']);
        $this->assertSame('Streamr AI', $state['payments'][0]['verified_by_name']);
        $this->assertNotNull($state['invoice_url']);
    }
}
