<?php

namespace App\Services\Streamr;

use App\Models\StreamrClient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Streamr's sales, read from /ecom-orders with the Sales Report's rules: an
 * order of the Streamr store counts when it is complete or refunded, and
 * processed refunds are taken off. Plans and credits are told apart by the
 * item ("Streamr Subscription" / "Streamr Credits"). Days are Manila days.
 */
final class StreamrSales
{
    public const RANGES = [
        'month' => 'This month',
        '30d' => 'Last 30 days',
        '90d' => 'Last 90 days',
        'year' => 'This year',
        'all' => 'All time',
    ];

    public const SALE_STATUSES = ['complete', 'refunded'];

    /** @return array{0: ?Carbon, 1: Carbon} */
    public static function bounds(string $range): array
    {
        $now = StreamrTime::now();

        return match ($range) {
            '30d' => [$now->copy()->subDays(29)->startOfDay(), $now],
            '90d' => [$now->copy()->subDays(89)->startOfDay(), $now],
            'year' => [$now->copy()->startOfYear(), $now],
            'all' => [null, $now],
            default => [$now->copy()->startOfMonth(), $now],
        };
    }

    public static function range(?string $range): string
    {
        return array_key_exists((string) $range, self::RANGES) ? (string) $range : 'month';
    }

    /** Every order with a live item of the Streamr store (one row per order), with its item and refunds. */
    public function orders(?Carbon $from = null, ?Carbon $to = null)
    {
        $store = StreamrSettings::storeName();
        $item = fn (string $column) => DB::table('ecom_order_items as i')->select('i.' . $column)
            ->whereColumn('i.orderId', 'o.id')->where('i.deleteStatus', 1)->where('i.productStore', $store)
            ->orderBy('i.id')->limit(1);

        return DB::table('ecom_orders as o')
            ->where('o.deleteStatus', 1)
            ->whereExists(fn ($q) => $q->select(DB::raw(1))->from('ecom_order_items as e')
                ->whereColumn('e.orderId', 'o.id')->where('e.deleteStatus', 1)->where('e.productStore', $store))
            ->when($from, fn ($q) => $q->where('o.created_at', '>=', $from->format('Y-m-d H:i:s')))
            ->when($to, fn ($q) => $q->where('o.created_at', '<=', $to->format('Y-m-d H:i:s')))
            ->select(['o.id', 'o.orderNumber', 'o.created_at', 'o.clientFirstName', 'o.clientLastName', 'o.clientEmail',
                'o.grandTotal', 'o.orderStatus', 'o.paymentVerificationStatus', 'o.paymentMethod'])
            ->selectSub($item('productName'), 'productName')
            ->selectSub($item('variantName'), 'variantName')
            ->selectSub($item('variantSku'), 'variantSku')
            ->selectSub(DB::table('ecom_refund_requests as r')->selectRaw('COALESCE(SUM(r.approvedAmount), 0)')
                ->whereColumn('r.orderId', 'o.id')->where('r.status', 'processed')->where('r.deleteStatus', 1), 'refunded');
    }

    /** The sales (complete or refunded) of a range: the rows the totals and the CSV are made of. */
    public function sales(string $range)
    {
        [$from, $to] = self::bounds(self::range($range));

        return $this->orders($from, $to)->whereIn('o.orderStatus', self::SALE_STATUSES);
    }

    public static function kindOf(?string $productName): string
    {
        return $productName === 'Streamr Credits' ? 'credits' : 'plans';
    }

    /** @return array{kpis: array, series: array, subscribers: array, recent: array, range: array} */
    public function summary(string $range): array
    {
        $range = self::range($range);
        [$from, $to] = self::bounds($range);
        $rows = $this->sales($range)->orderBy('o.created_at')->get();

        $gross = 0.0;
        $refunds = 0.0;
        $byKind = ['plans' => 0.0, 'credits' => 0.0];
        $countByKind = ['plans' => 0, 'credits' => 0];
        $byBucket = [];
        $mix = [];
        $monthly = $range === 'year' || $range === 'all';
        foreach ($rows as $row) {
            $total = (float) $row->grandTotal;
            $refunded = min($total, (float) $row->refunded);
            $kind = self::kindOf($row->productName);
            $gross += $total;
            $refunds += $refunded;
            $byKind[$kind] += $total - $refunded;
            $countByKind[$kind]++;
            $item = (string) ($row->variantName ?: $row->productName ?: 'Other');
            $mix[$item] ??= ['item' => $item, 'kind' => $kind, 'count' => 0, 'net' => 0.0];
            $mix[$item]['count']++;
            $mix[$item]['net'] += $total - $refunded;
            $day = StreamrTime::parse($row->created_at);
            $bucket = $day ? $day->format($monthly ? 'Y-m' : 'Y-m-d') : 'unknown';
            $byBucket[$bucket][$kind] = ($byBucket[$bucket][$kind] ?? 0) + ($total - $refunded);
        }

        $pending = $this->orders()->where(fn ($q) => $q->where('o.orderStatus', 'pending')->orWhereNull('o.orderStatus'))
            ->where('o.paymentVerificationStatus', 'pending');
        $pendingCount = (clone $pending)->count();
        $pendingTotal = (float) (clone $pending)->sum('o.grandTotal');

        $orders = $rows->count();

        return [
            'range' => ['key' => $range, 'label' => self::RANGES[$range], 'from' => $from?->toDateString(), 'to' => $to->toDateString()],
            'kpis' => [
                'revenue' => round($gross - $refunds, 2),
                'gross' => round($gross, 2),
                'orders' => $orders,
                'plans' => round($byKind['plans'], 2),
                'credits' => round($byKind['credits'], 2),
                'plansCount' => $countByKind['plans'],
                'creditsCount' => $countByKind['credits'],
                'average' => $orders > 0 ? round($gross / $orders, 2) : 0.0,
                'refunds' => round($refunds, 2),
                'pendingCount' => $pendingCount,
                'pendingTotal' => round($pendingTotal, 2),
            ],
            'series' => $this->series($byBucket, $from, $to, $monthly),
            'mix' => collect($mix)->map(fn ($m) => array_merge($m, ['net' => round($m['net'], 2)]))->sortByDesc('net')->values()->all(),
            'subscribers' => $this->subscribers(),
            'recent' => $this->recent(),
        ];
    }

    /** Every day (or month) of the range, zeros included, oldest first. */
    private function series(array $byBucket, ?Carbon $from, Carbon $to, bool $monthly): array
    {
        if ($from === null) {
            $first = array_key_first($byBucket);
            $from = $first ? Carbon::parse($first . ($monthly ? '-01' : ''), StreamrTime::ZONE) : $to->copy()->startOfMonth();
        }

        $labels = [];
        $plans = [];
        $credits = [];
        $cursor = $monthly ? $from->copy()->startOfMonth() : $from->copy()->startOfDay();
        $guard = 0;
        while ($cursor->lte($to) && $guard++ < 1200) {
            $key = $cursor->format($monthly ? 'Y-m' : 'Y-m-d');
            $labels[] = $monthly ? $cursor->format('M Y') : $cursor->format('M j');
            $plans[] = round((float) ($byBucket[$key]['plans'] ?? 0), 2);
            $credits[] = round((float) ($byBucket[$key]['credits'] ?? 0), 2);
            $monthly ? $cursor->addMonthNoOverflow()->startOfMonth() : $cursor->addDay();
        }

        return ['labels' => $labels, 'plans' => $plans, 'credits' => $credits, 'bucket' => $monthly ? 'month' : 'day'];
    }

    /** Active subscribers per plan, from the registry (Streamr's admins left out). */
    private function subscribers(): array
    {
        $out = ['monthly' => 0, 'yearly' => 0, 'lifetime' => 0, 'comped' => 0, 'lifetimeSold' => 0, 'total' => 0];
        try {
            $now = StreamrTime::now()->format('Y-m-d H:i:s');
            $base = StreamrClient::active()->whereNotIn('role', StreamrClient::ADMIN_ROLES)
                ->where(function ($q) use ($now) {
                    $q->where(fn ($a) => $a->where('subscriptionState', 'active')
                        ->where(fn ($b) => $b->where('isLifetime', true)->orWhereNull('expiresAt')->orWhere('expiresAt', '>', $now)))
                        ->orWhere(fn ($g) => $g->where('subscriptionState', 'grace')
                            ->where(fn ($b) => $b->whereNull('graceUntil')->orWhere('graceUntil', '>', $now)));
                });
            foreach ((clone $base)->selectRaw('planKey, COUNT(*) as n')->groupBy('planKey')->pluck('n', 'planKey') as $plan => $n) {
                if (array_key_exists((string) $plan, $out) && in_array($plan, ['monthly', 'yearly', 'lifetime'], true)) {
                    $out[$plan] = (int) $n;
                }
            }
            $out['comped'] = (clone $base)->where(fn ($q) => $q->where('planPrice', 0)->orWhereNull('planPrice'))->count();
            $out['total'] = (clone $base)->count();
        } catch (\Throwable $e) {
            // The registry is optional for the sales page.
        }

        try {
            $out['lifetimeSold'] = (clone $this->orders())->where('o.orderStatus', 'complete')
                ->whereExists(fn ($q) => $q->select(DB::raw(1))->from('ecom_order_items as l')->whereColumn('l.orderId', 'o.id')
                    ->where('l.deleteStatus', 1)->where('l.variantSku', 'streamr:plan:lifetime'))
                ->count();
        } catch (\Throwable $e) {
            $out['lifetimeSold'] = 0;
        }

        return $out;
    }

    private function recent(): array
    {
        return $this->orders()->orderByDesc('o.id')->limit(25)->get()->map(fn ($o) => [
            'number' => (string) $o->orderNumber,
            'client' => trim(trim((string) $o->clientFirstName) . ' ' . trim((string) $o->clientLastName)) ?: (string) $o->clientEmail,
            'email' => (string) $o->clientEmail,
            'item' => (string) ($o->variantName ?: $o->productName),
            'kind' => self::kindOf($o->productName),
            'total' => round((float) $o->grandTotal, 2),
            'orderStatus' => (string) ($o->orderStatus ?? 'pending'),
            'paymentStatus' => (string) $o->paymentVerificationStatus,
            'date' => StreamrTime::show($o->created_at),
        ])->values()->all();
    }
}
