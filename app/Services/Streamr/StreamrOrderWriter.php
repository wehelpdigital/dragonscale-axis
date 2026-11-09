<?php

namespace App\Services\Streamr;

use App\Models\ClientAllDatabase;
use App\Models\EcomOrder;
use App\Models\EcomOrderAuditLog;
use App\Models\EcomOrderItem;
use App\Models\EcomOrderPayment;
use App\Models\EcomProductStore;
use App\Models\StreamrClient;
use App\Models\StreamrOrder;
use App\Models\StreamrPaymentProof;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Writes Streamr's purchases into /ecom-orders, the way the e-commerce module
 * shows them (Streamr's mother-ecom-orders.md §6.2-6.4):
 *
 *   ecom_orders          pending, not shipped, the buyer, the totals, the payment
 *   ecom_order_items     one line under the store named STREAMR_STORE_NAME ("Streamr")
 *   ecom_order_payments  a pending payment whose picture is /streamr-proofs?order=...&n=...
 *                        and whose Verification Notes carry the AI's reading
 *   ecom_order_audit_logs  order_created, payment_details_submitted, ai_receipt_check
 *
 * plus streamr_orders (Streamr's uuid of the order) and streamr_payment_proofs
 * (the receipt bytes, never on a public disk). When Streamr approved the
 * purchase itself (its AI, or its admin while this app was not linked), the
 * payment and the order are marked verified in the same transaction.
 *
 * Its own state changes are query-builder updates: no model events, so the
 * order hook does not ring Streamr about what Streamr itself did. Rows are
 * created with the models so the payment number comes from
 * EcomOrderPayment's own generator. Everything is idempotent by Streamr's
 * uuids; the order number is unique.
 */
class StreamrOrderWriter
{
    public const PROOF_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif', 'application/pdf'];

    private const METHOD_WORDS = [
        'manual_gcash' => 'GCash QR',
        'manual_maya' => 'Maya',
        'manual_bank' => 'bank transfer',
    ];

    public static function numberPattern(): string
    {
        return '/^' . preg_quote((string) config('streamr.order_prefix', 'STR-'), '/') . '\d{8}-[23456789ABCDEFGHJKMNPQRSTUVWXYZ]{4,6}$/';
    }

    /** Validation of POST /orders `payload` (Streamr's architecture §10.2; M05 §8.1). */
    public static function createRules(): array
    {
        $money = ['required', 'string', 'regex:/^\d{1,13}\.\d{2}$/'];

        return array_merge([
            'uuid' => ['required', 'uuid'],
            'number' => ['required', 'string', 'max:50', 'regex:' . self::numberPattern()],
            'kind' => ['required', 'in:plan,credits'],
            'item' => ['required', 'array'],
            'item.key' => ['required', 'string', 'max:40'],
            'item.sku' => ['required', 'string', 'max:100', 'starts_with:streamr:'],
            'item.product_name' => ['required', 'in:Streamr Subscription,Streamr Credits'],
            'item.variant_name' => ['required', 'string', 'max:255'],
            'item.days' => ['nullable', 'integer', 'min:0'],
            'item.credits' => ['nullable', 'integer', 'min:0'],
            'amounts' => ['required', 'array'],
            'amounts.currency' => ['required', 'in:PHP'],
            'amounts.subtotal' => $money,
            'amounts.discount' => $money,
            'amounts.fee' => $money,
            'amounts.total' => $money,
            'client' => ['required', 'array'],
            'client.streamr_user_id' => ['required', 'integer', 'min:1'],
            'client.first_name' => ['nullable', 'string', 'max:100'],
            'client.last_name' => ['nullable', 'string', 'max:100'],
            'client.email' => ['required', 'email', 'max:255'],
            'client.phone' => ['nullable', 'string', 'max:20'],
            'client.store_name' => ['nullable', 'string', 'max:150'],
            'client.store_handle' => ['nullable', 'string', 'max:80'],
            'decision' => ['nullable', 'array'],
            'decision.auto_approved' => ['nullable', 'boolean'],
            'decision.decided_by' => ['nullable', 'in:ai,streamr_admin'],
            'decision.approved_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], self::paymentRules(), self::aiRules('ai'));
    }

    /** Validation of POST /orders/{number}/proofs `payload`. */
    public static function proofRules(): array
    {
        return array_merge([
            'uuid' => ['required', 'uuid'],
            'n' => ['nullable', 'integer', 'min:1', 'max:255'],
        ], self::paymentRules(), self::aiRules('ai'));
    }

    /** Validation of POST /orders/{number}/ai-check. */
    public static function aiCheckRules(): array
    {
        // The AI block is required here (array_merge: the later 'ai' rule wins).
        return array_merge(self::aiRules('ai'), [
            'proof_uuid' => ['nullable', 'uuid'],
            'ai' => ['required', 'array'],
        ]);
    }

    private static function paymentRules(): array
    {
        return [
            'payment' => ['required', 'array'],
            'payment.method' => ['required', 'in:manual_gcash,manual_maya,manual_bank'],
            'payment.payer_name' => ['nullable', 'string', 'max:255'],
            'payment.amount_sent' => ['required', 'string', 'regex:/^\d{1,13}\.\d{2}$/'],
            'payment.reference' => ['nullable', 'string', 'max:100'],
            'payment.ref_key' => ['nullable', 'string', 'max:64'],
            'payment.sender_phone' => ['nullable', 'string', 'max:20'],
            'payment.buyer_note' => ['nullable', 'string', 'max:500'],
            'payment.submitted_at' => ['nullable', 'date'],
            'payment.proof' => ['nullable', 'array'],
            'payment.proof.filename' => ['required_with:payment.proof', 'string', 'max:191'],
            'payment.proof.mime' => ['required_with:payment.proof', 'string', 'max:100'],
            'payment.proof.bytes' => ['required_with:payment.proof', 'integer', 'min:1', 'max:' . self::maxBytes()],
            'payment.proof.sha256' => ['required_with:payment.proof', 'string', 'regex:/^[a-fA-F0-9]{64}$/'],
            'payment.proof.uuid' => ['nullable', 'uuid'],
        ];
    }

    private static function aiRules(string $key): array
    {
        return [
            $key => ['nullable', 'array'],
            $key . '.verdict' => ['required_with:' . $key, 'in:pass,fail,unsure,error,skipped,running'],
            $key . '.score' => ['nullable', 'integer', 'min:0', 'max:100'],
            $key . '.summary' => ['nullable', 'string', 'max:500'],
            $key . '.checks' => ['nullable', 'array', 'max:12'],
            $key . '.checks.*' => ['array'],
            $key . '.checks.*.key' => ['nullable', 'string', 'max:20'],
            $key . '.checks.*.label' => ['nullable', 'string', 'max:191'],
            $key . '.checks.*.ok' => ['nullable', 'boolean'],
            $key . '.checks.*.hard' => ['nullable', 'boolean'],
            $key . '.checks.*.detail' => ['nullable', 'string', 'max:500'],
            $key . '.provider' => ['nullable', 'string', 'max:20'],
            $key . '.model' => ['nullable', 'string', 'max:60'],
            $key . '.checked_at' => ['nullable', 'date'],
        ];
    }

    /**
     * The AI block's words are informational: overlong text is shortened
     * rather than refused, so a long detail can never cost a buyer's order.
     */
    public static function normalizeAi($ai)
    {
        if (! is_array($ai)) {
            return $ai;
        }
        $cut = fn ($value, int $max) => is_string($value) ? mb_substr($value, 0, $max) : $value;
        foreach (['summary' => 500, 'provider' => 20, 'model' => 60] as $field => $max) {
            if (array_key_exists($field, $ai)) {
                $ai[$field] = $cut($ai[$field], $max);
            }
        }
        if (isset($ai['checks']) && is_array($ai['checks'])) {
            $ai['checks'] = array_map(function ($check) use ($cut) {
                if (! is_array($check)) {
                    return $check;
                }
                foreach (['key' => 20, 'label' => 191, 'detail' => 500] as $field => $max) {
                    if (array_key_exists($field, $check)) {
                        $check[$field] = $cut($check[$field], $max);
                    }
                }

                return $check;
            }, array_slice(array_values($ai['checks']), 0, 12));
        }

        return $ai;
    }

    public static function maxBytes(): int
    {
        return max(1, (int) config('streamr.proof_max_kb', 8192)) * 1024;
    }

    // ------------------------------------------------------------------
    // POST /orders
    // ------------------------------------------------------------------

    /** @return array{number: string, mother_order_id: int, payment_number: ?string, order_status: string, payment_verification_status: string, duplicate: bool} */
    public function create(array $payload, ?UploadedFile $proof): array
    {
        $uuid = strtolower((string) $payload['uuid']);
        $number = (string) $payload['number'];

        $existing = StreamrOrder::where('uuid', $uuid)->first();
        if ($existing) {
            return $this->answer($existing, true);
        }
        if (EcomOrder::where('orderNumber', $number)->exists()) {
            throw new StreamrApiException('duplicate_number', 'Order ' . $number . ' already exists in DS AXIS for another purchase.', 409);
        }

        $amounts = $payload['amounts'];
        if ($this->cents($amounts['subtotal']) - $this->cents($amounts['discount']) + $this->cents($amounts['fee']) !== $this->cents($amounts['total'])) {
            throw StreamrApiException::validation(['amounts.total' => 'The total must be the subtotal minus the discount plus the fee.']);
        }

        $file = $this->file($payload['payment'], $proof);
        $client = $payload['client'];
        $crmId = $this->crmClient($client);

        try {
            $streamrOrder = DB::transaction(fn () => $this->insert($payload, $uuid, $number, $file, $crmId));
        } catch (QueryException $e) {
            // Two identical calls at once: the unique keys stop the second.
            $again = StreamrOrder::where('uuid', $uuid)->first();
            if ($again) {
                return $this->answer($again, true);
            }
            if (EcomOrder::where('orderNumber', $number)->exists()) {
                throw new StreamrApiException('duplicate_number', 'Order ' . $number . ' already exists in DS AXIS for another purchase.', 409);
            }
            throw $e;
        }

        try {
            StreamrClient::where('streamrUserId', (int) $client['streamr_user_id'])->whereNull('crmClientId')
                ->update(['crmClientId' => $crmId]);
        } catch (\Throwable $e) {
            // The registry link is a convenience.
        }
        StreamrBadges::forget();

        return $this->answer($streamrOrder, false);
    }

    private function insert(array $payload, string $uuid, string $number, ?array $file, ?int $crmId): StreamrOrder
    {
        $now = StreamrTime::now();
        $client = $payload['client'];
        $item = $payload['item'];
        $amounts = $payload['amounts'];
        $pay = $payload['payment'];
        $ai = $payload['ai'] ?? null;
        $decision = is_array($payload['decision'] ?? null) ? $payload['decision'] : [];
        $method = (string) $pay['method'];
        $storeName = $this->store()->storeName;
        $first = mb_substr(trim((string) ($client['first_name'] ?? '')), 0, 100);
        $last = mb_substr(trim((string) ($client['last_name'] ?? '')), 0, 100);
        $fullName = trim($first . ' ' . $last);
        $phone = $this->phone($client['phone'] ?? null);
        $senderPhone = $this->phone($pay['sender_phone'] ?? null);
        $reference = $this->reference($pay);

        $order = EcomOrder::create([
            'usersId' => 0,
            'orderNumber' => $number,
            'orderStatus' => 'pending',
            'shippingStatus' => 'not_applicable',
            'clientId' => $crmId,
            'clientFirstName' => $first !== '' ? $first : null,
            'clientMiddleName' => '',
            'clientLastName' => $last !== '' ? $last : null,
            'clientPhone' => $phone,
            'clientEmail' => mb_substr((string) $client['email'], 0, 255),
            'subtotal' => $amounts['subtotal'],
            'shippingTotal' => 0,
            'discountTotal' => $amounts['discount'],
            'grandTotal' => $amounts['total'],
            'affiliateCommissionTotal' => 0,
            'netRevenue' => $amounts['total'],
            'orderNotes' => $this->nullIfBlank($payload['notes'] ?? null),
            'isPackage' => false,
            'paymentMethod' => $method,
            'paymentVerificationStatus' => 'pending',
            'paymentPayerName' => $this->nullIfBlank($pay['payer_name'] ?? null, 255),
            'paymentAmountSent' => $pay['amount_sent'],
            'paymentReferenceNumber' => $reference,
            'paymentPhoneNumber' => $senderPhone,
            'paymentScreenshot' => null,
            'paymentNotes' => $this->nullIfBlank($pay['buyer_note'] ?? null),
            'deleteStatus' => 1,
        ]);

        EcomOrderItem::create([
            'orderId' => $order->id,
            'productId' => null,
            'productName' => (string) $item['product_name'],
            'productStore' => $storeName,
            'productType' => 'access',
            'variantId' => null,
            'variantName' => mb_substr((string) $item['variant_name'], 0, 255),
            'variantSku' => mb_substr((string) $item['sku'], 0, 100),
            'unitPrice' => $amounts['subtotal'],
            'quantity' => 1,
            'subtotal' => $amounts['subtotal'],
            'shippingCost' => 0,
            'accessClientName' => $fullName !== '' ? mb_substr($fullName, 0, 255) : null,
            'accessClientPhone' => $phone,
            'accessClientEmail' => mb_substr((string) $client['email'], 0, 255),
            'deleteStatus' => 1,
        ]);

        $notesKind = $this->notesKind($method, $file);
        $payment = EcomOrderPayment::create([
            'orderId' => $order->id,
            'paymentMethod' => $method,
            'paymentStatus' => 'pending',
            'amountSent' => $pay['amount_sent'],
            'payerName' => $this->nullIfBlank($pay['payer_name'] ?? null, 255),
            'referenceNumber' => $reference,
            'phoneNumber' => $senderPhone,
            'screenshot' => $file ? self::proofPath($number, 1) : null,
            'verificationNotes' => StreamrAiNotes::lines($ai, $notesKind),
            'deleteStatus' => 1,
        ]);

        $streamrOrder = StreamrOrder::create([
            'uuid' => $uuid,
            'orderNumber' => $number,
            'ecomOrderId' => $order->id,
            'streamrUserId' => (int) $client['streamr_user_id'],
            'kind' => (string) $payload['kind'],
            'itemKey' => mb_substr((string) $item['key'], 0, 40),
            'sku' => mb_substr((string) $item['sku'], 0, 100),
            'variantName' => mb_substr((string) $item['variant_name'], 0, 255),
            'total' => $amounts['total'],
            'method' => $method,
            'crmClientId' => $crmId,
            'aiVerdict' => is_array($ai) ? (string) $ai['verdict'] : null,
            'aiScore' => is_array($ai) && isset($ai['score']) ? (int) $ai['score'] : null,
            'autoApproved' => false,
            'decidedBy' => null,
            'proofsCount' => 1,
            'delete_status' => 'active',
        ]);

        $this->proofRow($streamrOrder, 1, (string) ($pay['proof']['uuid'] ?? '') ?: $uuid, $pay, $file, $ai, (int) $payment->id);

        $customer = ($fullName !== '' ? $fullName : (string) $client['email']) . ' (Customer)';
        $this->audit($order, 'order_created', 'Streamr', null, null, null,
            'Order #' . $number . ' created from Streamr checkout: ' . $item['variant_name'] . ', ₱' . $this->money($amounts['total'])
            . ' (Streamr account #' . (int) $client['streamr_user_id']
            . (trim((string) ($client['store_handle'] ?? '')) !== '' ? ', ' . trim((string) $client['store_handle']) : '') . ').');
        $this->audit($order, 'payment_details_submitted', $customer, 'paymentMethod', null, $method,
            'Customer submitted payment details via ' . self::METHOD_WORDS[$method] . ' from Streamr. Amount: ₱' . $this->money($pay['amount_sent'])
            . '. Ref: ' . ($reference ?? '—'));
        if (is_array($ai)) {
            $this->audit($order, 'ai_receipt_check', 'Streamr AI', 'paymentStatus', null, (string) $ai['verdict'], StreamrAiNotes::auditSummary($ai));
        }

        if (filter_var($decision['auto_approved'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $this->markVerified($order, $payment, $streamrOrder, ($decision['decided_by'] ?? null) === 'streamr_admin' ? 'streamr_admin' : 'ai', $now);
        }

        return $streamrOrder;
    }

    /**
     * The verified state Streamr's own approval gives, as verifyOrderPayment
     * would write it, guarded so it never fights an admin's decision.
     */
    private function markVerified(EcomOrder $order, EcomOrderPayment $payment, StreamrOrder $streamrOrder, string $by, $now): void
    {
        $stamp = $now->format('Y-m-d H:i:s');
        $line = $by === 'streamr_admin'
            ? '[' . $now->format('Y-m-d H:i') . '] Verified in Streamr: approved by a Streamr admin while DS AXIS was not linked.'
            : '[' . $now->format('Y-m-d H:i') . '] Verified automatically by Streamr AI: every check passed.';
        $notes = trim((string) DB::table('ecom_order_payments')->where('id', $payment->id)->value('verificationNotes'));

        $paid = DB::table('ecom_order_payments')->where('id', $payment->id)->where('paymentStatus', 'pending')->update([
            'paymentStatus' => 'verified',
            'amountVerified' => $payment->amountSent,
            'verifiedAt' => $stamp,
            'verifiedBy' => null,
            'verificationNotes' => $notes !== '' ? $notes . "\n" . $line : $line,
            'invoiceNumber' => EcomOrderPayment::generateInvoiceNumber($order->id),
            'invoiceToken' => EcomOrderPayment::generateInvoiceToken(),
            'invoiceGeneratedAt' => $stamp,
            'updated_at' => $stamp,
        ]);
        if (! $paid) {
            return;
        }

        DB::table('ecom_orders')->where('id', $order->id)->where('paymentVerificationStatus', 'pending')->update([
            'paymentVerificationStatus' => 'verified',
            'paymentVerifiedAt' => $stamp,
            'paymentVerifiedBy' => null,
            'orderStatus' => 'complete',
            'updated_at' => $stamp,
        ]);

        $who = $by === 'streamr_admin' ? 'Streamr admin' : 'Streamr AI';
        $this->audit($order, 'payment_verified', $who, 'paymentStatus', 'pending', 'verified',
            'Payment ' . $payment->paymentNumber . ($by === 'streamr_admin'
                ? ' verified: approved in Streamr by a Streamr admin while DS AXIS was not linked.'
                : ' verified automatically by Streamr AI (every check passed).'));
        $this->audit($order, 'status_change', $who, 'orderStatus', 'pending', 'complete', 'Order status set to complete (payment fully verified)');

        DB::table('streamr_orders')->where('id', $streamrOrder->id)->update([
            'autoApproved' => true,
            'decidedBy' => $by,
            'updated_at' => $stamp,
        ]);
    }

    // ------------------------------------------------------------------
    // POST /orders/{number}/proofs
    // ------------------------------------------------------------------

    public function addProof(string $number, array $payload, ?UploadedFile $proof): array
    {
        $streamrOrder = StreamrOrder::where('orderNumber', $number)->first();
        if (! $streamrOrder) {
            throw new StreamrApiException('unknown_order', 'No Streamr order ' . $number . ' in DS AXIS.', 404);
        }

        $proofUuid = strtolower((string) $payload['uuid']);
        $known = StreamrPaymentProof::where('uuid', $proofUuid)->first(['id', 'streamrOrderId', 'ecomPaymentId']);
        if ($known) {
            if ((int) $known->streamrOrderId !== (int) $streamrOrder->id) {
                throw new StreamrApiException('conflict', 'That proof belongs to another order.', 409);
            }

            return $this->proofAnswer($streamrOrder, (int) $known->ecomPaymentId, true);
        }

        $file = $this->file($payload['payment'], $proof);
        $ai = $payload['ai'] ?? null;
        $pay = $payload['payment'];

        $paymentId = DB::transaction(function () use ($streamrOrder, $number, $proofUuid, $file, $ai, $pay) {
            $locked = StreamrOrder::where('id', $streamrOrder->id)->lockForUpdate()->first();
            $order = EcomOrder::find($locked->ecomOrderId);
            if (! $order || (int) $order->deleteStatus !== 1 || ($order->orderStatus ?? 'pending') !== 'pending'
                || $order->paymentVerificationStatus !== 'rejected') {
                throw new StreamrApiException('invalid_state', 'A new proof is taken only after the last one was rejected and while the order is still open.', 409);
            }
            $n = (int) $locked->proofsCount + 1;
            $max = max(1, (int) config('streamr.max_proofs', 3));
            if ($n > $max) {
                throw new StreamrApiException('not_applicable', 'This order already has ' . $max . ' proofs.', 409);
            }

            $now = StreamrTime::now();
            $stamp = $now->format('Y-m-d H:i:s');
            $method = (string) $pay['method'];
            $reference = $this->reference($pay);
            $senderPhone = $this->phone($pay['sender_phone'] ?? null);

            $payment = EcomOrderPayment::create([
                'orderId' => $order->id,
                'paymentMethod' => $method,
                'paymentStatus' => 'pending',
                'amountSent' => $pay['amount_sent'],
                'payerName' => $this->nullIfBlank($pay['payer_name'] ?? null, 255),
                'referenceNumber' => $reference,
                'phoneNumber' => $senderPhone,
                'screenshot' => $file ? self::proofPath($number, $n) : null,
                'verificationNotes' => StreamrAiNotes::lines($ai, $this->notesKind($method, $file)),
                'deleteStatus' => 1,
            ]);

            DB::table('ecom_orders')->where('id', $order->id)->update([
                'paymentVerificationStatus' => 'pending',
                'paymentMethod' => $method,
                'paymentPayerName' => $this->nullIfBlank($pay['payer_name'] ?? null, 255),
                'paymentAmountSent' => $pay['amount_sent'],
                'paymentReferenceNumber' => $reference,
                'paymentPhoneNumber' => $senderPhone,
                'updated_at' => $stamp,
            ]);

            $this->proofRow($locked, $n, $proofUuid, $pay, $file, $ai, (int) $payment->id);

            $this->audit($order, 'payment_details_submitted', $this->customerName($order), 'paymentVerificationStatus', 'rejected', 'pending',
                'Customer submitted a new payment proof (' . $n . ' of ' . $max . ') via ' . self::METHOD_WORDS[$method] . ' from Streamr. Amount: ₱'
                . $this->money($pay['amount_sent']) . '. Ref: ' . ($reference ?? '—'));
            if (is_array($ai)) {
                $this->audit($order, 'ai_receipt_check', 'Streamr AI', 'paymentStatus', null, (string) $ai['verdict'], StreamrAiNotes::auditSummary($ai));
            }

            DB::table('streamr_orders')->where('id', $locked->id)->update([
                'proofsCount' => $n,
                'aiVerdict' => is_array($ai) ? (string) $ai['verdict'] : null,
                'aiScore' => is_array($ai) && isset($ai['score']) ? (int) $ai['score'] : null,
                'method' => $method,
                'updated_at' => $stamp,
            ]);

            return (int) $payment->id;
        });
        StreamrBadges::forget();

        return $this->proofAnswer($streamrOrder->fresh(), $paymentId, false);
    }

    // ------------------------------------------------------------------
    // POST /orders/{number}/ai-check
    // ------------------------------------------------------------------

    public function recordAiCheck(string $number, array $payload): array
    {
        $streamrOrder = StreamrOrder::where('orderNumber', $number)->first();
        if (! $streamrOrder) {
            throw new StreamrApiException('unknown_order', 'No Streamr order ' . $number . ' in DS AXIS.', 404);
        }

        $columns = StreamrPaymentProof::LIGHT;
        $proof = null;
        if (! empty($payload['proof_uuid'])) {
            $proof = StreamrPaymentProof::where('streamrOrderId', $streamrOrder->id)->where('uuid', strtolower((string) $payload['proof_uuid']))->first($columns);
        }
        $proof ??= StreamrPaymentProof::where('streamrOrderId', $streamrOrder->id)->orderByDesc('n')->first($columns);
        if (! $proof) {
            throw new StreamrApiException('not_found', 'Order ' . $number . ' has no proof to record a check on.', 404);
        }

        $ai = $payload['ai'];
        $report = is_array($proof->aiReport) ? $proof->aiReport : [];
        $rechecks = is_array($report['rechecks'] ?? null) ? $report['rechecks'] : [];
        $last = $rechecks !== [] ? end($rechecks) : $report;
        $lastAt = StreamrTime::parse($last['checked_at'] ?? null);
        $newAt = StreamrTime::parse($ai['checked_at'] ?? null);
        if ((string) ($last['verdict'] ?? $proof->aiVerdict) === (string) $ai['verdict']
            && (($lastAt === null && $newAt === null) || ($lastAt && $newAt && $lastAt->equalTo($newAt)))) {
            return ['recorded' => false, 'duplicate' => true];
        }

        DB::transaction(function () use ($streamrOrder, $proof, $ai, $report, $rechecks) {
            $stamp = StreamrTime::stamp();
            $order = EcomOrder::find($streamrOrder->ecomOrderId);
            $rechecks[] = $ai;
            $report = $report === [] ? ['rechecks' => $rechecks] : array_merge($report, ['rechecks' => $rechecks]);

            DB::table('streamr_payment_proofs')->where('id', $proof->id)->update([
                'aiVerdict' => (string) $ai['verdict'],
                'aiScore' => isset($ai['score']) ? (int) $ai['score'] : null,
                'aiReport' => json_encode($report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'aiCheckedAt' => StreamrTime::parse($ai['checked_at'] ?? null)?->format('Y-m-d H:i:s') ?? $stamp,
                'updated_at' => $stamp,
            ]);

            if ($proof->ecomPaymentId) {
                $kind = $proof->kind === 'pdf' ? 'pdf' : ($proof->kind === 'ref' ? 'ref' : 'image');
                $lines = StreamrAiNotes::lines($ai, $streamrOrder->method === 'manual_bank' ? 'bank' : $kind, 'Streamr AI re-check');
                $notes = trim((string) DB::table('ecom_order_payments')->where('id', $proof->ecomPaymentId)->value('verificationNotes'));
                DB::table('ecom_order_payments')->where('id', $proof->ecomPaymentId)->update([
                    'verificationNotes' => $notes !== '' ? $notes . "\n" . $lines : $lines,
                    'updated_at' => $stamp,
                ]);
            }

            $latestN = (int) StreamrPaymentProof::where('streamrOrderId', $streamrOrder->id)->max('n');
            if ((int) $proof->n === $latestN) {
                DB::table('streamr_orders')->where('id', $streamrOrder->id)->update([
                    'aiVerdict' => (string) $ai['verdict'],
                    'aiScore' => isset($ai['score']) ? (int) $ai['score'] : null,
                    'updated_at' => $stamp,
                ]);
            }

            if ($order) {
                $this->audit($order, 'ai_receipt_check', 'Streamr AI', 'paymentStatus', null, (string) $ai['verdict'],
                    StreamrAiNotes::auditSummary($ai, 'Re-check of proof ' . $proof->n . ': '));
            }
        });

        return ['recorded' => true, 'duplicate' => false];
    }

    // ------------------------------------------------------------------

    /** The relative URL /ecom-orders opens with asset(): streamed to signed-in admins only. */
    public static function proofPath(string $number, int $n): string
    {
        return 'streamr-proofs?order=' . rawurlencode($number) . '&n=' . $n;
    }

    /**
     * The uploaded receipt, checked against what the payload says about it:
     * there iff `payment.proof` is, at most 8 MB, the same sha256, and by its
     * content a JPEG, PNG, WebP, HEIC/HEIF or PDF.
     *
     * @return array{bytes: string, mime: string, size: int, sha256: string, name: string, kind: string}|null
     */
    private function file(array $pay, ?UploadedFile $proof): ?array
    {
        $declared = is_array($pay['proof'] ?? null) ? $pay['proof'] : null;

        if ($declared === null) {
            if ($proof) {
                throw StreamrApiException::validation(['proof' => 'A file was sent but payment.proof is null.']);
            }

            return null;
        }
        if (! $proof) {
            throw StreamrApiException::validation(['proof' => 'payment.proof describes a file, but no file was sent.']);
        }
        if (! $proof->isValid()) {
            throw new StreamrApiException('payload_too_large', 'The proof could not be received (too large for this server, or cut off).', 413);
        }
        if ((int) $proof->getSize() > self::maxBytes()) {
            throw new StreamrApiException('payload_too_large', 'The proof is over ' . (int) config('streamr.proof_max_kb', 8192) / 1024 . ' MB.', 413);
        }

        $bytes = (string) @file_get_contents((string) $proof->getRealPath());
        if ($bytes === '') {
            throw StreamrApiException::validation(['proof' => 'The proof file is empty.']);
        }
        $sha = hash('sha256', $bytes);
        if (! hash_equals(strtolower((string) $declared['sha256']), $sha)) {
            throw StreamrApiException::validation(['proof' => 'The file does not match payment.proof.sha256.']);
        }
        $mime = strtolower((string) (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes));
        if (! in_array($mime, self::PROOF_MIMES, true)) {
            throw StreamrApiException::validation(['proof' => 'The proof must be a JPEG, PNG, WebP, HEIC picture or a PDF.']);
        }

        $name = trim((string) ($declared['filename'] ?? '')) ?: (string) $proof->getClientOriginalName();

        return [
            'bytes' => $bytes,
            'mime' => $mime,
            'size' => strlen($bytes),
            'sha256' => $sha,
            'name' => mb_substr(basename(str_replace('\\', '/', $name)), 0, 191),
            'kind' => $mime === 'application/pdf' ? 'pdf' : 'image',
        ];
    }

    private function proofRow(StreamrOrder $streamrOrder, int $n, string $uuid, array $pay, ?array $file, ?array $ai, int $paymentId): void
    {
        $reference = $this->reference($pay);

        StreamrPaymentProof::create([
            'uuid' => strtolower($uuid),
            'streamrOrderId' => $streamrOrder->id,
            'orderNumber' => $streamrOrder->orderNumber,
            'n' => $n,
            'ecomPaymentId' => $paymentId,
            'kind' => $file['kind'] ?? 'ref',
            'name' => $file['name'] ?? null,
            'mime' => $file['mime'] ?? null,
            'size' => $file['size'] ?? null,
            'sha256' => $file['sha256'] ?? null,
            'bytes' => $file['bytes'] ?? null,
            'reference' => $reference,
            'refKey' => ($key = StreamrReferenceChecker::normalize((string) ($pay['ref_key'] ?? '') ?: (string) ($pay['reference'] ?? ''))) !== '' ? mb_substr($key, 0, 64) : null,
            'aiVerdict' => is_array($ai) ? (string) $ai['verdict'] : null,
            'aiScore' => is_array($ai) && isset($ai['score']) ? (int) $ai['score'] : null,
            'aiReport' => is_array($ai) ? $ai : null,
            'aiCheckedAt' => is_array($ai) ? StreamrTime::parse($ai['checked_at'] ?? null) : null,
            'submittedAt' => StreamrTime::parse($pay['submitted_at'] ?? null) ?? StreamrTime::now(),
            'delete_status' => 'active',
        ]);
    }

    private function answer(StreamrOrder $streamrOrder, bool $duplicate): array
    {
        $order = DB::table('ecom_orders')->where('id', $streamrOrder->ecomOrderId)->first(['id', 'orderNumber', 'orderStatus', 'paymentVerificationStatus']);
        $paymentNumber = DB::table('ecom_order_payments')->where('orderId', $streamrOrder->ecomOrderId)->where('deleteStatus', 1)
            ->orderBy('id')->value('paymentNumber');

        return [
            'number' => (string) $streamrOrder->orderNumber,
            'mother_order_id' => (int) $streamrOrder->ecomOrderId,
            'payment_number' => $paymentNumber,
            'order_status' => (string) ($order->orderStatus ?? 'pending'),
            'payment_verification_status' => (string) ($order->paymentVerificationStatus ?? 'pending'),
            'duplicate' => $duplicate,
        ];
    }

    private function proofAnswer(StreamrOrder $streamrOrder, int $paymentId, bool $duplicate): array
    {
        $order = DB::table('ecom_orders')->where('id', $streamrOrder->ecomOrderId)->first(['orderStatus', 'paymentVerificationStatus']);

        return [
            'number' => (string) $streamrOrder->orderNumber,
            'payment_number' => DB::table('ecom_order_payments')->where('id', $paymentId)->value('paymentNumber'),
            'order_status' => (string) ($order->orderStatus ?? 'pending'),
            'payment_verification_status' => (string) ($order->paymentVerificationStatus ?? 'pending'),
            'duplicate' => $duplicate,
        ];
    }

    /** The store in E-commerce > Stores named STREAMR_STORE_NAME, made the first time (like Stores > Add). */
    private function store(): EcomProductStore
    {
        $name = StreamrSettings::storeName();
        $store = EcomProductStore::where('storeName', $name)->orderByDesc('deleteStatus')->first();

        return $store ?? EcomProductStore::create([
            'storeName' => $name,
            'storeDescription' => 'Streamr subscriptions and credits, logged automatically from Streamr.',
            'storeLogo' => null,
            'isActive' => 1,
            'deleteStatus' => 1,
        ]);
    }

    /** The buyer in the CRM (clients_all_database), found by email or added; null when the CRM cannot be written. */
    private function crmClient(array $client): ?int
    {
        $email = mb_strtolower(trim((string) $client['email']));
        try {
            $existing = ClientAllDatabase::where('deleteStatus', 1)
                ->whereRaw('LOWER(clientEmailAddress) = ?', [$email])
                ->orderBy('id')
                ->value('id');
            if ($existing) {
                return (int) $existing;
            }

            return (int) ClientAllDatabase::create([
                'clientFirstName' => mb_substr(trim((string) ($client['first_name'] ?? '')), 0, 100),
                'clientMiddleName' => '',
                'clientLastName' => mb_substr(trim((string) ($client['last_name'] ?? '')), 0, 100),
                'clientPhoneNumber' => (string) ($this->phone($client['phone'] ?? null) ?? ''),
                'clientEmailAddress' => $email,
                'deleteStatus' => 1,
            ])->id;
        } catch (\Throwable $e) {
            Log::warning('[Streamr] CRM client not written: ' . $e->getMessage());

            return null;
        }
    }

    private function audit(EcomOrder $order, string $type, string $userName, ?string $field, ?string $previous, ?string $new, string $description): void
    {
        EcomOrderAuditLog::create([
            'orderId' => $order->id,
            'orderNumber' => $order->orderNumber,
            'userId' => null,
            'userName' => mb_substr($userName, 0, 255),
            'actionType' => $type,
            'fieldChanged' => $field,
            'previousValue' => $previous,
            'newValue' => $new,
            'description' => $description,
            'ipAddress' => null,
            'userAgent' => 'Streamr API',
            'deleteStatus' => 1,
        ]);
    }

    private function customerName(EcomOrder $order): string
    {
        $name = trim(trim((string) $order->clientFirstName) . ' ' . trim((string) $order->clientLastName));

        return ($name !== '' ? $name : (string) $order->clientEmail) . ' (Customer)';
    }

    private function notesKind(string $method, ?array $file): string
    {
        if ($method === 'manual_bank') {
            return 'bank';
        }
        if (! $file) {
            return 'ref';
        }

        return $file['kind'];
    }

    /** The reference as typed (≤ 100), else the normalised one. */
    private function reference(array $pay): ?string
    {
        $typed = trim((string) ($pay['reference'] ?? ''));
        if ($typed !== '') {
            return mb_substr($typed, 0, 100);
        }
        $key = trim((string) ($pay['ref_key'] ?? ''));

        return $key !== '' ? mb_substr($key, 0, 100) : null;
    }

    /** 09XXXXXXXXX when it is a PH mobile, else the digits (≤ 20), else null. */
    private function phone($value): ?string
    {
        $phone = StreamrSettings::localPhone(is_scalar($value) ? (string) $value : '');

        return $phone !== '' ? mb_substr($phone, 0, 20) : null;
    }

    private function nullIfBlank($value, ?int $max = null): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        if ($value === '') {
            return null;
        }

        return $max ? mb_substr($value, 0, $max) : $value;
    }

    private function cents($value): int
    {
        return (int) round(((float) $value) * 100);
    }

    private function money($value): string
    {
        return number_format((float) $value, 2);
    }
}
