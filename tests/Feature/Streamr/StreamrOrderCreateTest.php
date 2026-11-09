<?php

namespace Tests\Feature\Streamr;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * POST /api/streamr/v1/orders: exactly the /ecom-orders rows of the contract
 * (Streamr's mother-ecom-orders.md §6.2-6.3; M05 §6.3), the verified state
 * when Streamr approved it, idempotency, the proof checks, and no doorbell
 * for what Streamr itself wrote.
 */
class StreamrOrderCreateTest extends StreamrTestCase
{
    public function test_a_pending_purchase_writes_exactly_the_contract_rows(): void
    {
        Http::fake();
        $bytes = $this->receiptBytes();
        $before = now('Asia/Manila');

        $response = $this->postOrder([], $bytes)->assertOk();

        $response->assertJsonPath('success', true)
            ->assertJsonPath('data.number', 'STR-20261108-7KQ4')
            ->assertJsonPath('data.order_status', 'pending')
            ->assertJsonPath('data.payment_verification_status', 'pending')
            ->assertJsonPath('data.duplicate', false);

        $this->assertSame(1, DB::table('ecom_orders')->count());
        $order = DB::table('ecom_orders')->first();
        $this->assertSame((int) $order->id, $response->json('data.mother_order_id'));
        $this->assertSame(0, (int) $order->usersId);
        $this->assertSame('STR-20261108-7KQ4', $order->orderNumber);
        $this->assertSame('pending', $order->orderStatus);
        $this->assertSame('not_applicable', $order->shippingStatus);
        $this->assertSame('manual_gcash', $order->paymentMethod);
        $this->assertSame('pending', $order->paymentVerificationStatus);
        $this->assertNull($order->paymentScreenshot);
        $this->assertSame(['Lia', 'Santos', '09171234567', 'lia@example.ph'], [$order->clientFirstName, $order->clientLastName, $order->clientPhone, $order->clientEmail]);
        $this->assertEquals(11000, (float) $order->grandTotal);
        $this->assertEquals(11000, (float) $order->subtotal);
        $this->assertEquals(11000, (float) $order->netRevenue);
        $this->assertEquals(0, (float) $order->discountTotal);
        $this->assertSame('1023 796 539709', $order->paymentReferenceNumber);
        $this->assertSame('09171234567', $order->paymentPhoneNumber);
        $this->assertStringContainsString('Streamr account #42', $order->orderNotes);
        $this->assertSame(1, (int) $order->deleteStatus);
        // Manila wall-clock, not UTC.
        $created = \Illuminate\Support\Carbon::parse($order->created_at, 'Asia/Manila');
        $this->assertLessThan(120, abs($created->diffInSeconds($before)));

        $this->assertSame(1, DB::table('ecom_order_items')->count());
        $item = DB::table('ecom_order_items')->first();
        $this->assertSame(['Streamr', 'access', 'streamr:plan:yearly', 'Streamr Subscription', 'Yearly (365 days)'],
            [$item->productStore, $item->productType, $item->variantSku, $item->productName, $item->variantName]);
        $this->assertEquals(11000, (float) $item->unitPrice);
        $this->assertSame(1, (int) $item->quantity);
        $this->assertSame('Lia Santos', $item->accessClientName);

        $this->assertSame(1, DB::table('ecom_order_payments')->count());
        $payment = DB::table('ecom_order_payments')->first();
        $this->assertMatchesRegularExpression('/^PAY-\d{6}-\d{5}-01$/', $payment->paymentNumber);
        $this->assertSame($payment->paymentNumber, $response->json('data.payment_number'));
        $this->assertSame('pending', $payment->paymentStatus);
        $this->assertSame('streamr-proofs?order=STR-20261108-7KQ4&n=1', $payment->screenshot);
        $this->assertStringStartsWith('[2026-11-08 14:03] Streamr AI receipt check: FAIL (authenticity 62/100). Something on the receipt does not match this payment.', $payment->verificationNotes);
        $this->assertStringContainsString("\n  ✗ The amount is ₱11,000.00: The receipt shows ₱1,100.00.", $payment->verificationNotes);
        $this->assertStringContainsString("\n  ? Paid after the checkout was opened: No date and time could be read.", $payment->verificationNotes);
        $this->assertStringContainsString("\n  ✓ Sent to our account (GCash 09XX XXX XXXX): Read: J•••N D•••Z, +63 9XX •••.", $payment->verificationNotes);

        $proof = DB::table('streamr_payment_proofs')->first();
        $this->assertSame($bytes, $proof->bytes);
        $this->assertSame([1, 'image', 'image/jpeg', hash('sha256', $bytes), '1023796539709', 'fail', 62],
            [(int) $proof->n, $proof->kind, $proof->mime, $proof->sha256, $proof->refKey, $proof->aiVerdict, (int) $proof->aiScore]);
        $this->assertSame((int) $payment->id, (int) $proof->ecomPaymentId);
        $this->assertSame('0b6f3a5e-6a51-4f5a-9d43-1c9b5f1d2e77', $proof->uuid);

        $streamrOrder = DB::table('streamr_orders')->first();
        $this->assertSame(['0b6f3a5e-6a51-4f5a-9d43-1c9b5f1d2e77', (int) $order->id, 42, 'plan', 'plan:yearly', 'manual_gcash', 'fail', 1, 0],
            [$streamrOrder->uuid, (int) $streamrOrder->ecomOrderId, (int) $streamrOrder->streamrUserId, $streamrOrder->kind, $streamrOrder->itemKey,
                $streamrOrder->method, $streamrOrder->aiVerdict, (int) $streamrOrder->proofsCount, (int) $streamrOrder->autoApproved]);

        $audits = DB::table('ecom_order_audit_logs')->orderBy('id')->get();
        $this->assertSame(['order_created', 'payment_details_submitted', 'ai_receipt_check'], $audits->pluck('actionType')->all());
        $this->assertSame('Streamr', $audits[0]->userName);
        $this->assertNull($audits[0]->userId);
        $this->assertSame('Order #STR-20261108-7KQ4 created from Streamr checkout: Yearly (365 days), ₱11,000.00 (Streamr account #42, lias-preloved).', $audits[0]->description);
        $this->assertSame(['Lia Santos (Customer)', 'paymentMethod', 'manual_gcash', 'Streamr API'],
            [$audits[1]->userName, $audits[1]->fieldChanged, $audits[1]->newValue, $audits[1]->userAgent]);
        $this->assertSame('Customer submitted payment details via GCash QR from Streamr. Amount: ₱11,000.00. Ref: 1023 796 539709', $audits[1]->description);
        $this->assertNull($audits[1]->ipAddress);
        $this->assertSame(['Streamr AI', 'paymentStatus', 'fail'], [$audits[2]->userName, $audits[2]->fieldChanged, $audits[2]->newValue]);
        $this->assertStringContainsString('✗ The amount is ₱11,000.00', $audits[2]->description);
        $this->assertStringNotContainsString('✓ Sent to our account', $audits[2]->description);   // only what did not pass

        $crm = DB::table('clients_all_database')->first();
        $this->assertSame(['Lia', '', 'Santos', '09171234567', 'lia@example.ph', 1],
            [$crm->clientFirstName, $crm->clientMiddleName, $crm->clientLastName, $crm->clientPhoneNumber, $crm->clientEmailAddress, (int) $crm->deleteStatus]);
        $this->assertSame((int) $crm->id, (int) $order->clientId);

        $store = DB::table('ecom_product_stores')->first();
        $this->assertSame(['Streamr', 1, 1], [$store->storeName, (int) $store->isActive, (int) $store->deleteStatus]);

        $this->runAfterResponse();
        Http::assertNothingSent();
    }

    public function test_an_auto_approved_purchase_is_written_verified_with_an_invoice_and_no_doorbell(): void
    {
        Http::fake();
        $response = $this->postOrder([
            'ai.verdict' => 'pass', 'ai.score' => 92, 'ai.summary' => 'Every check passed.',
            'ai.checks' => [['key' => 'amount', 'label' => 'The amount is ₱11,000.00', 'ok' => true, 'hard' => true, 'detail' => 'The receipt shows ₱11,000.00.']],
            'decision' => ['auto_approved' => true, 'decided_by' => 'ai', 'approved_at' => '2026-11-08T14:03:42+08:00'],
        ])->assertOk();

        $response->assertJsonPath('data.order_status', 'complete')->assertJsonPath('data.payment_verification_status', 'verified');

        $payment = DB::table('ecom_order_payments')->first();
        $this->assertSame('verified', $payment->paymentStatus);
        $this->assertEquals(11000, (float) $payment->amountVerified);
        $this->assertNotNull($payment->verifiedAt);
        $this->assertNull($payment->verifiedBy);
        $this->assertMatchesRegularExpression('/^INV-\d{6}-\d{5}-01$/', $payment->invoiceNumber);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $payment->invoiceToken);
        $this->assertNotNull($payment->invoiceGeneratedAt);
        $this->assertStringContainsString('Verified automatically by Streamr AI: every check passed.', $payment->verificationNotes);

        $order = DB::table('ecom_orders')->first();
        $this->assertSame(['verified', 'complete'], [$order->paymentVerificationStatus, $order->orderStatus]);
        $this->assertNotNull($order->paymentVerifiedAt);
        $this->assertNull($order->paymentVerifiedBy);

        $audits = DB::table('ecom_order_audit_logs')->orderBy('id')->get();
        $this->assertSame(['order_created', 'payment_details_submitted', 'ai_receipt_check', 'payment_verified', 'status_change'], $audits->pluck('actionType')->all());
        $this->assertSame(['Streamr AI', 'paymentStatus', 'pending', 'verified'], [$audits[3]->userName, $audits[3]->fieldChanged, $audits[3]->previousValue, $audits[3]->newValue]);
        $this->assertSame(['orderStatus', 'pending', 'complete', 'Order status set to complete (payment fully verified)'],
            [$audits[4]->fieldChanged, $audits[4]->previousValue, $audits[4]->newValue, $audits[4]->description]);

        $this->assertSame([1, 'ai'], [(int) DB::table('streamr_orders')->value('autoApproved'), DB::table('streamr_orders')->value('decidedBy')]);

        $this->runAfterResponse();
        Http::assertNothingSent();
    }

    public function test_a_streamr_admin_approval_is_recorded_as_such(): void
    {
        $this->postOrder(['decision' => ['auto_approved' => true, 'decided_by' => 'streamr_admin']])->assertOk();

        $this->assertStringContainsString('approved by a Streamr admin while DS AXIS was not linked', DB::table('ecom_order_payments')->value('verificationNotes'));
        $this->assertSame('Streamr admin', DB::table('ecom_order_audit_logs')->where('actionType', 'payment_verified')->value('userName'));
        $this->assertSame('streamr_admin', DB::table('streamr_orders')->value('decidedBy'));
    }

    public function test_the_same_purchase_twice_is_one_order_and_another_purchase_cannot_take_its_number(): void
    {
        $bytes = $this->receiptBytes();
        $first = $this->postOrder([], $bytes)->assertOk()->json('data');

        $again = $this->postOrder([], $bytes)->assertOk();
        $again->assertJsonPath('data.duplicate', true)->assertJsonPath('data.mother_order_id', $first['mother_order_id'])
            ->assertJsonPath('data.payment_number', $first['payment_number']);
        foreach (['ecom_orders', 'ecom_order_items', 'ecom_order_payments', 'streamr_orders', 'streamr_payment_proofs'] as $table) {
            $this->assertSame(1, DB::table($table)->count(), $table);
        }
        $this->assertSame(3, DB::table('ecom_order_audit_logs')->count());
        $this->assertSame(1, DB::table('clients_all_database')->count());

        $this->postOrder(['uuid' => '9a6f3a5e-6a51-4f5a-9d43-1c9b5f1d2e70'], $bytes)
            ->assertStatus(409)->assertJsonPath('success', false)->assertJsonPath('error.code', 'duplicate_number');

        // Another venture's order number is never taken over either.
        $this->otherOrder('STR-20261109-ABCD', 'Other store');
        $this->postOrder(['uuid' => '1a6f3a5e-6a51-4f5a-9d43-1c9b5f1d2e71', 'number' => 'STR-20261109-ABCD'], $bytes)
            ->assertStatus(409)->assertJsonPath('error.code', 'duplicate_number');
    }

    public function test_the_receipt_is_checked_against_the_payload(): void
    {
        $bytes = $this->receiptBytes();

        // Over 8 MB.
        $big = str_repeat('a', 8 * 1024 * 1024 + 1);
        $payload = $this->orderPayload([], $big);
        $this->signedMultipart('/api/streamr/v1/orders', $payload, $this->upload($big))
            ->assertStatus(413)->assertJsonPath('error.code', 'payload_too_large');

        // A file that is not the one described.
        $payload = $this->orderPayload(['payment.proof.sha256' => str_repeat('0', 64)], $bytes);
        $this->signedMultipart('/api/streamr/v1/orders', $payload, $this->upload($bytes))
            ->assertStatus(422)->assertJsonPath('error.code', 'validation_failed')->assertJsonStructure(['error' => ['fields' => ['proof']]]);

        // Described but not sent.
        $this->postOrder([], $bytes, false)->assertStatus(422)->assertJsonPath('error.code', 'validation_failed');

        // Not a picture or a PDF.
        $text = 'just some text, not a receipt';
        $this->signedMultipart('/api/streamr/v1/orders', $this->orderPayload([], $text), $this->upload($text, 'receipt.txt'))
            ->assertStatus(422)->assertJsonPath('error.fields.proof', 'The proof must be a JPEG, PNG, WebP, HEIC picture or a PDF.');

        $this->assertSame(0, DB::table('ecom_orders')->count());
        $this->assertSame(0, DB::table('streamr_payment_proofs')->count());
    }

    public function test_invalid_payloads_are_422_with_the_fields(): void
    {
        $cases = [
            ['payment.method', 'manual_paypal'],
            ['item.product_name', 'Something Else'],
            ['number', 'ORD-20261108-7KQ4'],
            ['amounts.total', '11000'],
            ['client.email', 'not-an-email'],
            ['uuid', 'not-a-uuid'],
            ['ai.verdict', 'maybe'],
            ['item.sku', 'plan:yearly'],
        ];
        foreach ($cases as [$field, $value]) {
            $this->postOrder([$field => $value])->assertStatus(422)
                ->assertJsonPath('error.code', 'validation_failed')
                ->assertJsonStructure(['error' => ['fields' => [$field]]]);
        }

        $this->postOrder(['amounts.fee' => '50.00'])->assertStatus(422)->assertJsonPath('error.fields', ['amounts.total' => 'The total must be the subtotal minus the discount plus the fee.']);

        // Not JSON at all.
        $server = $this->transformHeadersToServerVars($this->signedHeaders('POST', '/api/streamr/v1/orders', 'nope') + ['Content-Type' => 'application/json']);
        $this->call('POST', '/api/streamr/v1/orders', [], [], [], $server, 'nope')->assertStatus(400)->assertJsonPath('error.code', 'bad_request');

        $this->assertSame(0, DB::table('ecom_orders')->count());
    }

    public function test_long_ai_text_is_shortened_not_refused(): void
    {
        $this->postOrder(['ai.summary' => str_repeat('s', 900), 'ai.checks.0.detail' => str_repeat('d', 900)])->assertOk();

        $report = json_decode(DB::table('streamr_payment_proofs')->value('aiReport'), true);
        $this->assertSame(500, mb_strlen($report['summary']));
        $this->assertSame(500, mb_strlen($report['checks'][0]['detail']));
    }

    public function test_a_bank_transfer_without_a_file_is_sent_as_plain_json(): void
    {
        $payload = $this->orderPayload([
            'payment.method' => 'manual_bank', 'payment.reference' => null, 'payment.ref_key' => 'BPI-778899', 'ai' => null,
            'kind' => 'credits', 'item' => ['key' => 'pack:pack_1000', 'sku' => 'streamr:pack:pack_1000', 'product_name' => 'Streamr Credits',
                'variant_name' => '1,000 credits pack (1,100 credits)', 'days' => null, 'credits' => 1100],
            'amounts' => ['currency' => 'PHP', 'subtotal' => '500.00', 'discount' => '0.00', 'fee' => '0.00', 'total' => '500.00'],
            'payment.amount_sent' => '500.00',
        ]);

        $this->signedJson('POST', '/api/streamr/v1/orders', $payload)->assertOk()->assertJsonPath('data.order_status', 'pending');

        $payment = DB::table('ecom_order_payments')->first();
        $this->assertNull($payment->screenshot);
        $this->assertSame('BPI-778899', $payment->referenceNumber);
        $this->assertStringContainsString('Bank transfer — reviewed by a person (the AI does not read bank slips).', $payment->verificationNotes);
        $this->assertSame(['manual_bank', 'Streamr Credits'], [DB::table('ecom_orders')->value('paymentMethod'), DB::table('ecom_order_items')->value('productName')]);
        $this->assertSame(['ref', null], [DB::table('streamr_payment_proofs')->value('kind'), DB::table('streamr_payment_proofs')->value('bytes')]);
        $this->assertSame(1, DB::table('ecom_order_audit_logs')->where('actionType', 'payment_details_submitted')->count());
        $this->assertSame(0, DB::table('ecom_order_audit_logs')->where('actionType', 'ai_receipt_check')->count());
    }

    public function test_an_existing_crm_client_is_reused_by_email_whatever_its_case(): void
    {
        $id = DB::table('clients_all_database')->insertGetId([
            'clientFirstName' => 'Lia', 'clientMiddleName' => '', 'clientLastName' => 'Santos', 'clientPhoneNumber' => '',
            'clientEmailAddress' => 'LIA@Example.PH', 'deleteStatus' => 1,
        ]);

        $this->postOrder()->assertOk();

        $this->assertSame(1, DB::table('clients_all_database')->count());
        $this->assertSame($id, (int) DB::table('ecom_orders')->value('clientId'));
    }

    public function test_an_existing_store_row_is_used_and_never_duplicated(): void
    {
        DB::table('ecom_product_stores')->insert(['storeName' => 'Streamr', 'isActive' => 1, 'deleteStatus' => 1]);

        $this->postOrder()->assertOk();
        $this->postOrder(['uuid' => '2a6f3a5e-6a51-4f5a-9d43-1c9b5f1d2e72', 'number' => 'STR-20261108-8KQ4'])->assertOk();

        $this->assertSame(1, DB::table('ecom_product_stores')->count());
        $this->assertSame(2, DB::table('ecom_orders')->count());
    }
}
