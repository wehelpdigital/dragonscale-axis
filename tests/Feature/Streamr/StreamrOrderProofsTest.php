<?php

namespace Tests\Feature\Streamr;

use Illuminate\Support\Facades\DB;

/**
 * POST /api/streamr/v1/orders/{number}/proofs: a new receipt after a
 * rejection (proof n of 3) puts the order back to pending with a new pending
 * payment; refused while the order is not rejected, and after the third.
 */
class StreamrOrderProofsTest extends StreamrTestCase
{
    private function rejectLatest(): void
    {
        $payment = DB::table('ecom_order_payments')->orderByDesc('id')->first();
        $this->actingAs($this->admin())->putJson('/ecom-order-payments/' . $payment->id . '/verify', ['action' => 'reject', 'notes' => 'The amount did not arrive.'])
            ->assertOk()->assertJsonPath('success', true);
    }

    private function proofPayload(string $uuid, string $bytes, array $ai = null): array
    {
        return [
            'uuid' => $uuid,
            'n' => 2,
            'payment' => ['method' => 'manual_gcash', 'payer_name' => 'Lia Santos', 'amount_sent' => '11000.00', 'reference' => '2023 796 539709',
                'ref_key' => '2023796539709', 'sender_phone' => '09171234567', 'buyer_note' => 'Sent again', 'submitted_at' => now('Asia/Manila')->toIso8601String(),
                'proof' => ['filename' => 'receipt-2.jpg', 'mime' => 'image/jpeg', 'bytes' => strlen($bytes), 'sha256' => hash('sha256', $bytes)]],
            'ai' => $ai ?? ['verdict' => 'unsure', 'score' => 75, 'summary' => 'Could not read the date.', 'checks' => [], 'provider' => 'anthropic',
                'model' => 'claude-opus-5-5', 'checked_at' => now('Asia/Manila')->toIso8601String()],
        ];
    }

    public function test_a_new_proof_after_a_rejection_adds_a_pending_payment_and_reopens_the_order(): void
    {
        $this->fakeStreamr();
        $this->postOrder()->assertOk();
        $this->rejectLatest();
        $this->assertSame('rejected', DB::table('ecom_orders')->value('paymentVerificationStatus'));

        $bytes = $this->receiptBytes('second receipt');
        $response = $this->signedMultipart('/api/streamr/v1/orders/STR-20261108-7KQ4/proofs',
            $this->proofPayload('3d3c2a1b-0000-4000-8000-000000000002', $bytes), $this->upload($bytes))->assertOk();

        $response->assertJsonPath('data.number', 'STR-20261108-7KQ4')
            ->assertJsonPath('data.order_status', 'pending')
            ->assertJsonPath('data.payment_verification_status', 'pending')
            ->assertJsonPath('data.duplicate', false);

        $payments = DB::table('ecom_order_payments')->orderBy('id')->get();
        $this->assertCount(2, $payments);
        $this->assertSame(['rejected', 'pending'], $payments->pluck('paymentStatus')->all());
        $this->assertSame('streamr-proofs?order=STR-20261108-7KQ4&n=2', $payments[1]->screenshot);
        $this->assertSame($payments[1]->paymentNumber, $response->json('data.payment_number'));
        $this->assertStringContainsString('Streamr AI receipt check: UNSURE (authenticity 75/100)', $payments[1]->verificationNotes);

        $order = DB::table('ecom_orders')->first();
        $this->assertSame(['pending', 'pending', '2023 796 539709'], [$order->orderStatus, $order->paymentVerificationStatus, $order->paymentReferenceNumber]);
        $this->assertSame(2, (int) DB::table('streamr_orders')->value('proofsCount'));
        $this->assertSame('unsure', DB::table('streamr_orders')->value('aiVerdict'));
        $proof = DB::table('streamr_payment_proofs')->where('n', 2)->first();
        $this->assertSame([$bytes, '3d3c2a1b-0000-4000-8000-000000000002', (int) $payments[1]->id], [$proof->bytes, $proof->uuid, (int) $proof->ecomPaymentId]);
        $this->assertStringContainsString('(2 of 3)', DB::table('ecom_order_audit_logs')->where('actionType', 'payment_details_submitted')->orderByDesc('id')->value('description'));

        // The same proof again is a duplicate, not a third payment.
        $this->signedMultipart('/api/streamr/v1/orders/STR-20261108-7KQ4/proofs',
            $this->proofPayload('3d3c2a1b-0000-4000-8000-000000000002', $bytes), $this->upload($bytes))
            ->assertOk()->assertJsonPath('data.duplicate', true);
        $this->assertSame(2, DB::table('ecom_order_payments')->count());
    }

    public function test_a_proof_is_refused_unless_the_order_was_rejected(): void
    {
        $this->postOrder()->assertOk();
        $bytes = $this->receiptBytes('again');

        $this->signedMultipart('/api/streamr/v1/orders/STR-20261108-7KQ4/proofs',
            $this->proofPayload('3d3c2a1b-0000-4000-8000-000000000003', $bytes), $this->upload($bytes))
            ->assertStatus(409)->assertJsonPath('error.code', 'invalid_state');

        $this->signedMultipart('/api/streamr/v1/orders/STR-20261108-9ZZZ/proofs',
            $this->proofPayload('3d3c2a1b-0000-4000-8000-000000000004', $bytes), $this->upload($bytes))
            ->assertStatus(404)->assertJsonPath('error.code', 'unknown_order');

        $this->assertSame(1, DB::table('ecom_order_payments')->count());
    }

    public function test_a_fourth_proof_is_not_applicable(): void
    {
        $this->fakeStreamr();
        $this->postOrder()->assertOk();

        foreach ([2, 3] as $n) {
            $this->rejectLatest();
            $bytes = $this->receiptBytes('receipt ' . $n);
            $this->signedMultipart('/api/streamr/v1/orders/STR-20261108-7KQ4/proofs',
                $this->proofPayload('3d3c2a1b-0000-4000-8000-00000000001' . $n, $bytes), $this->upload($bytes))->assertOk();
        }
        $this->rejectLatest();

        $bytes = $this->receiptBytes('receipt 4');
        $this->signedMultipart('/api/streamr/v1/orders/STR-20261108-7KQ4/proofs',
            $this->proofPayload('3d3c2a1b-0000-4000-8000-000000000014', $bytes), $this->upload($bytes))
            ->assertStatus(409)->assertJsonPath('error.code', 'not_applicable');

        $this->assertSame(3, DB::table('ecom_order_payments')->count());
        $this->assertSame(3, (int) DB::table('streamr_orders')->value('proofsCount'));
    }
}
