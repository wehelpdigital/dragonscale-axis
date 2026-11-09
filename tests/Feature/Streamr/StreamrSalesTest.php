<?php

namespace Tests\Feature\Streamr;

use Illuminate\Support\Facades\DB;

/**
 * Streamr > Sales dashboard: only the Streamr store's complete or refunded
 * orders count, processed refunds are taken off, plans and credits are
 * apart, and the CSV adds up to the same revenue.
 */
class StreamrSalesTest extends StreamrTestCase
{
    private function sale(string $uuid, string $number, string $kind, string $total, string $status): int
    {
        $item = $kind === 'credits'
            ? ['key' => 'pack:pack_1000', 'sku' => 'streamr:pack:pack_1000', 'product_name' => 'Streamr Credits', 'variant_name' => '1,000 credits pack', 'days' => null, 'credits' => 1000]
            : ['key' => 'plan:yearly', 'sku' => 'streamr:plan:yearly', 'product_name' => 'Streamr Subscription', 'variant_name' => 'Yearly (365 days)', 'days' => 365, 'credits' => 0];
        $this->postOrder(['uuid' => $uuid, 'number' => $number, 'kind' => $kind, 'item' => $item,
            'amounts' => ['currency' => 'PHP', 'subtotal' => $total, 'discount' => '0.00', 'fee' => '0.00', 'total' => $total], 'payment.amount_sent' => $total])->assertOk();
        $id = (int) DB::table('ecom_orders')->where('orderNumber', $number)->value('id');
        DB::table('ecom_orders')->where('id', $id)->update(['orderStatus' => $status]);

        return $id;
    }

    public function test_the_totals_count_streamr_sales_net_of_refunds_and_match_the_csv(): void
    {
        $this->sale('a1111111-0000-4000-8000-000000000001', 'STR-20261108-AAAA', 'plan', '11000.00', 'complete');
        $refunded = $this->sale('a1111111-0000-4000-8000-000000000002', 'STR-20261108-BBBB', 'plan', '1000.00', 'refunded');
        $this->sale('a1111111-0000-4000-8000-000000000003', 'STR-20261108-CCCC', 'credits', '500.00', 'complete');
        $this->sale('a1111111-0000-4000-8000-000000000004', 'STR-20261108-DDDD', 'credits', '250.00', 'pending');     // not a sale yet
        $this->sale('a1111111-0000-4000-8000-000000000005', 'STR-20261108-EEEE', 'plan', '35000.00', 'cancelled');   // never a sale
        DB::table('ecom_refund_requests')->insert(['orderId' => $refunded, 'refundNumber' => 'REF-1', 'requestedAt' => now(), 'status' => 'processed',
            'approvedAmount' => 1000, 'deleteStatus' => 1, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('ecom_refund_requests')->insert(['orderId' => $refunded, 'refundNumber' => 'REF-2', 'requestedAt' => now(), 'status' => 'pending',
            'approvedAmount' => 999, 'deleteStatus' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $this->otherOrder('ORD-20261108-ZZZZ', 'Dragon Scale Store', ['orderStatus' => 'complete', 'grandTotal' => 777]);   // another store

        $data = $this->actingAs($this->admin())->getJson(route('streamr-sales.data', ['range' => 'month']))->assertOk()->json('data');
        $k = $data['kpis'];

        $this->assertEquals(11500, $k['revenue']);          // 11,000 + 1,000 + 500 − 1,000 refunded
        $this->assertSame(3, $k['orders']);
        $this->assertEquals(11000, $k['plans']);
        $this->assertEquals(500, $k['credits']);
        $this->assertEquals(1000, $k['refunds']);
        $this->assertEquals(round(12500 / 3, 2), $k['average']);
        $this->assertSame(1, $k['pendingCount']);
        $this->assertEquals(250, $k['pendingTotal']);
        $this->assertSame('day', $data['series']['bucket']);
        $this->assertEquals(11000, array_sum($data['series']['plans']));
        $this->assertEquals(500, array_sum($data['series']['credits']));
        $this->assertCount(5, $data['recent']);
        $this->assertSame([['item' => 'Yearly (365 days)', 'kind' => 'plans', 'count' => 2, 'net' => 11000.0], ['item' => '1,000 credits pack', 'kind' => 'credits', 'count' => 1, 'net' => 500.0]],
            array_map(fn ($m) => ['item' => $m['item'], 'kind' => $m['kind'], 'count' => $m['count'], 'net' => (float) $m['net']], $data['mix']));

        $csv = $this->actingAs($this->admin())->get(route('streamr-sales.export', ['range' => 'month']))->assertOk()->streamedContent();
        $rows = array_map('str_getcsv', array_values(array_filter(explode("\n", trim(substr($csv, 3))))));
        $header = array_shift($rows);
        $net = array_search('Net (PHP)', $header, true);
        $this->assertCount(3, $rows);
        $this->assertEquals($k['revenue'], array_sum(array_map(fn ($r) => (float) $r[$net], $rows)));
        $this->assertStringNotContainsString('ORD-20261108-ZZZZ', $csv);
        $this->assertStringNotContainsString('STR-20261108-DDDD', $csv);
    }

    public function test_subscribers_come_from_the_registry_and_lifetime_sales_from_orders(): void
    {
        $this->signedJson('PUT', '/api/streamr/v1/clients/1', $this->snapshot(['streamr_user_id' => 1, 'subscription.plan_key' => 'monthly', 'subscription.price' => '1000.00']))->assertOk();
        $this->signedJson('PUT', '/api/streamr/v1/clients/2', $this->snapshot(['streamr_user_id' => 2, 'subscription.plan_key' => 'yearly']))->assertOk();
        $this->signedJson('PUT', '/api/streamr/v1/clients/3', $this->snapshot(['streamr_user_id' => 3, 'subscription.plan_key' => 'lifetime', 'subscription.is_lifetime' => true, 'subscription.price' => '0.00']))->assertOk();
        $this->signedJson('PUT', '/api/streamr/v1/clients/4', $this->snapshot(['streamr_user_id' => 4, 'subscription.plan_key' => 'monthly',
            'subscription.expires_at' => now('Asia/Manila')->subDay()->toIso8601String()]))->assertOk();   // lapsed
        $this->signedJson('PUT', '/api/streamr/v1/clients/5', $this->snapshot(['streamr_user_id' => 5, 'role' => 'admin']))->assertOk();   // an admin

        $this->postOrder(['item.sku' => 'streamr:plan:lifetime', 'item.key' => 'plan:lifetime', 'ai.verdict' => 'pass', 'decision' => ['auto_approved' => true, 'decided_by' => 'ai']])->assertOk();

        $subscribers = $this->actingAs($this->admin())->getJson(route('streamr-sales.data', ['range' => 'all']))->assertOk()->json('data.subscribers');

        $this->assertSame(['monthly' => 1, 'yearly' => 1, 'lifetime' => 1, 'comped' => 1, 'lifetimeSold' => 1, 'total' => 3], $subscribers);
    }

    public function test_every_range_answers(): void
    {
        $this->sale('a1111111-0000-4000-8000-000000000001', 'STR-20261108-AAAA', 'plan', '11000.00', 'complete');

        foreach (['month', '30d', '90d', 'year', 'all', 'nonsense'] as $range) {
            $data = $this->actingAs($this->admin())->getJson(route('streamr-sales.data', ['range' => $range]))->assertOk()->json('data');
            $this->assertEquals(11000, $data['kpis']['revenue'], $range);
            $this->assertSame(in_array($range, ['year', 'all'], true) ? 'month' : 'day', $data['series']['bucket'], $range);
        }
    }
}
