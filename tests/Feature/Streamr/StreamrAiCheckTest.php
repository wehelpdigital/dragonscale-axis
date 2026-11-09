<?php

namespace Tests\Feature\Streamr;

use Illuminate\Support\Facades\DB;

/**
 * POST /api/streamr/v1/orders/{number}/ai-check: a later AI reading of a
 * receipt is appended to that payment's notes and the audit log once; the
 * same verdict and time again is a duplicate.
 */
class StreamrAiCheckTest extends StreamrTestCase
{
    private function recheck(array $over = []): array
    {
        return array_replace_recursive([
            'proof_uuid' => '0b6f3a5e-6a51-4f5a-9d43-1c9b5f1d2e77',
            'ai' => ['verdict' => 'pass', 'score' => 91, 'summary' => 'Read again: every check passed.',
                'checks' => [['key' => 'amount', 'label' => 'The amount is ₱11,000.00', 'ok' => true, 'hard' => true, 'detail' => 'The receipt shows ₱11,000.00.']],
                'provider' => 'anthropic', 'model' => 'claude-opus-5-5', 'checked_at' => '2026-11-08T15:10:00+08:00'],
        ], $over);
    }

    public function test_a_recheck_is_recorded_once(): void
    {
        $this->postOrder()->assertOk();

        $this->signedJson('POST', '/api/streamr/v1/orders/STR-20261108-7KQ4/ai-check', $this->recheck())
            ->assertOk()->assertJsonPath('data', ['recorded' => true, 'duplicate' => false]);

        $notes = DB::table('ecom_order_payments')->value('verificationNotes');
        $this->assertStringContainsString("\n[2026-11-08 15:10] Streamr AI re-check: PASS (authenticity 91/100). Read again: every check passed.", $notes);
        $this->assertStringStartsWith('[2026-11-08 14:03] Streamr AI receipt check: FAIL', $notes);   // the first reading stays

        $proof = DB::table('streamr_payment_proofs')->first();
        $report = json_decode($proof->aiReport, true);
        $this->assertSame('fail', $report['verdict']);
        $this->assertCount(1, $report['rechecks']);
        $this->assertSame(['pass', 91], [$proof->aiVerdict, (int) $proof->aiScore]);
        $this->assertSame('pass', DB::table('streamr_orders')->value('aiVerdict'));
        $this->assertSame(2, DB::table('ecom_order_audit_logs')->where('actionType', 'ai_receipt_check')->count());
        $this->assertStringStartsWith('Re-check of proof 1: ', DB::table('ecom_order_audit_logs')->where('actionType', 'ai_receipt_check')->orderByDesc('id')->value('description'));

        // The same reading again changes nothing.
        $this->signedJson('POST', '/api/streamr/v1/orders/STR-20261108-7KQ4/ai-check', $this->recheck())
            ->assertOk()->assertJsonPath('data', ['recorded' => false, 'duplicate' => true]);
        $this->assertSame($notes, DB::table('ecom_order_payments')->value('verificationNotes'));
        $this->assertSame(2, DB::table('ecom_order_audit_logs')->where('actionType', 'ai_receipt_check')->count());
    }

    public function test_an_unknown_proof_uuid_falls_back_to_the_latest_proof(): void
    {
        $this->postOrder()->assertOk();

        $this->signedJson('POST', '/api/streamr/v1/orders/STR-20261108-7KQ4/ai-check', $this->recheck(['proof_uuid' => '11111111-2222-4333-8444-555555555555']))
            ->assertOk()->assertJsonPath('data.recorded', true);

        $this->assertSame('pass', DB::table('streamr_payment_proofs')->value('aiVerdict'));
    }

    public function test_unknown_orders_and_orders_without_proofs_are_404(): void
    {
        $this->signedJson('POST', '/api/streamr/v1/orders/STR-20261108-9ZZZ/ai-check', $this->recheck())
            ->assertStatus(404)->assertJsonPath('error.code', 'unknown_order');

        $this->postOrder()->assertOk();
        DB::table('streamr_payment_proofs')->delete();
        $this->signedJson('POST', '/api/streamr/v1/orders/STR-20261108-7KQ4/ai-check', $this->recheck())
            ->assertStatus(404)->assertJsonPath('error.code', 'not_found');
    }

    public function test_a_recheck_needs_an_ai_block(): void
    {
        $this->postOrder()->assertOk();

        $this->signedJson('POST', '/api/streamr/v1/orders/STR-20261108-7KQ4/ai-check', ['proof_uuid' => '0b6f3a5e-6a51-4f5a-9d43-1c9b5f1d2e77'])
            ->assertStatus(422)->assertJsonStructure(['error' => ['fields' => ['ai']]]);
    }
}
