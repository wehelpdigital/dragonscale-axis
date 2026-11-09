<?php

namespace App\Services\Streamr;

use Illuminate\Support\Facades\DB;

/**
 * A Streamr order as /ecom-orders has it now (GET /api/streamr/v1/orders/{number}
 * and the batch GET /orders?numbers[]=...): Streamr reads it after a doorbell,
 * and every few minutes as a safety net, and applies it on its side.
 *
 *   {number, mother_order_id, order_status, payment_verification_status,
 *    delete_status, payments: [...], decided: {by_name, at} | null,
 *    reject_reason, invoice_url, updated_at}
 *
 * Only orders with a live item of the Streamr store count; anything else is
 * unknown_order. One query per table, however many numbers are asked.
 */
final class StreamrOrderState
{
    public const MAX_NUMBERS = 50;

    private const DECISION_ACTIONS = ['status_change', 'order_cancelled', 'refund_processed'];

    public function for(string $number): array
    {
        $result = $this->many([$number]);
        if ($result['orders'] === []) {
            throw new StreamrApiException('unknown_order', 'No Streamr order ' . $number . ' in DS AXIS.', 404);
        }

        return $result['orders'][0];
    }

    /** @return array{orders: list<array>, missing: list<string>} */
    public function many(array $numbers): array
    {
        $numbers = array_values(array_unique(array_filter(array_map(fn ($n) => is_scalar($n) ? trim((string) $n) : '', $numbers))));
        $numbers = array_slice($numbers, 0, self::MAX_NUMBERS);
        if ($numbers === []) {
            return ['orders' => [], 'missing' => []];
        }

        $storeName = StreamrSettings::storeName();
        $orders = DB::table('ecom_orders')->whereIn('orderNumber', $numbers)
            ->whereExists(function ($q) use ($storeName) {
                $q->select(DB::raw(1))->from('ecom_order_items')
                    ->whereColumn('ecom_order_items.orderId', 'ecom_orders.id')
                    ->where('ecom_order_items.productStore', $storeName)
                    ->where('ecom_order_items.deleteStatus', 1);
            })
            ->get(['id', 'orderNumber', 'orderStatus', 'paymentVerificationStatus', 'deleteStatus', 'updated_at']);

        $ids = $orders->pluck('id')->all();
        $payments = $ids === [] ? collect() : DB::table('ecom_order_payments')->whereIn('orderId', $ids)->where('deleteStatus', 1)
            ->orderBy('id')
            ->get(['id', 'orderId', 'paymentNumber', 'paymentStatus', 'amountSent', 'amountVerified', 'verifiedAt', 'verifiedBy',
                'verificationNotes', 'invoiceToken'])
            ->groupBy('orderId');
        $audits = $ids === [] ? collect() : DB::table('ecom_order_audit_logs')->whereIn('orderId', $ids)
            ->whereIn('actionType', self::DECISION_ACTIONS)->where('deleteStatus', 1)
            ->orderBy('id')
            ->get(['id', 'orderId', 'userName', 'created_at'])
            ->groupBy('orderId');
        $verifierIds = $payments->flatten(1)->pluck('verifiedBy')->filter()->unique()->values()->all();
        $names = $verifierIds === [] ? [] : DB::table('users')->whereIn('id', $verifierIds)->pluck('name', 'id')->all();
        $decidedBy = DB::table('streamr_orders')->whereIn('orderNumber', $orders->pluck('orderNumber')->all())->pluck('decidedBy', 'orderNumber')->all();

        $found = [];
        foreach ($orders as $order) {
            $found[$order->orderNumber] = $this->state($order, $payments->get($order->id, collect()), $audits->get($order->id, collect()),
                $names, $decidedBy[$order->orderNumber] ?? null);
        }

        $list = [];
        $missing = [];
        foreach ($numbers as $number) {
            if (isset($found[$number])) {
                $list[] = $found[$number];
            } else {
                $missing[] = $number;
            }
        }

        return ['orders' => $list, 'missing' => $missing];
    }

    private function state(object $order, $payments, $audits, array $names, ?string $decidedBy): array
    {
        $machine = $decidedBy === 'streamr_admin' ? 'Streamr admin' : 'Streamr AI';

        $rows = [];
        $decided = null;
        $rejectReason = null;
        $invoiceUrl = null;
        foreach ($payments as $p) {
            $verifiedName = $p->verifiedAt ? ($p->verifiedBy ? ($names[$p->verifiedBy] ?? 'An admin') : $machine) : null;
            $notes = (string) ($p->verificationNotes ?? '');
            $rows[] = [
                'payment_number' => $p->paymentNumber,
                'status' => $p->paymentStatus,
                'amount_sent' => $this->money($p->amountSent),
                'amount_verified' => $p->amountVerified !== null ? $this->money($p->amountVerified) : null,
                'verified_at' => StreamrTime::iso($p->verifiedAt),
                'verified_by_name' => $verifiedName,
                'notes' => $notes === '' ? null : (mb_strlen($notes) > 2000 ? mb_substr($notes, -2000) : $notes),
            ];

            if (in_array($p->paymentStatus, ['verified', 'rejected'], true) && $p->verifiedAt) {
                $at = StreamrTime::parse($p->verifiedAt);
                if ($at && (! $decided || $at->gte($decided['carbon']))) {
                    $decided = ['carbon' => $at, 'by_name' => $verifiedName];
                }
            }
            if ($p->paymentStatus === 'rejected') {
                $rejectReason = $this->rejectReason($notes);
            }
            if ($p->paymentStatus === 'verified' && $p->invoiceToken) {
                $invoiceUrl = url('/invoice/' . $p->invoiceToken);
            }
        }

        foreach ($audits as $a) {
            $at = StreamrTime::parse($a->created_at);
            if ($at && (! $decided || $at->gte($decided['carbon']))) {
                $decided = ['carbon' => $at, 'by_name' => (string) ($a->userName ?: 'System')];
            }
        }

        return [
            'number' => (string) $order->orderNumber,
            'mother_order_id' => (int) $order->id,
            'order_status' => (string) ($order->orderStatus ?? 'pending'),
            'payment_verification_status' => (string) $order->paymentVerificationStatus,
            'delete_status' => (int) $order->deleteStatus,
            'payments' => $rows,
            'decided' => $decided ? ['by_name' => $decided['by_name'], 'at' => $decided['carbon']->toIso8601String()] : null,
            'reject_reason' => $rejectReason,
            'invoice_url' => $invoiceUrl,
            'updated_at' => StreamrTime::iso($order->updated_at),
        ];
    }

    /**
     * What the admin typed when rejecting: the text after "Rejected:" on the
     * last such note, with the further lines it ran over (up to the next
     * "[Y-m-d H:i]" note), so a reason typed on several lines reaches Streamr whole.
     */
    private function rejectReason(string $notes): ?string
    {
        $notes = str_replace(["\r\n", "\r"], "\n", $notes);
        if ($notes === '' || ! preg_match_all('/\] Rejected: (.*(?:\n(?!\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}\]).*)*)/u', $notes, $matches)) {
            return null;
        }
        $reason = trim((string) end($matches[1]));

        return $reason === '' ? null : mb_substr($reason, 0, 500);
    }

    private function money($value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}
