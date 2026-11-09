<?php

namespace Tests\Feature\Streamr;

use App\Models\EcomOrder;
use App\Services\Streamr\StreamrCallbacks;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * The order hook: a guarded observer on EcomOrder rings Streamr (a signed
 * POST /api/mother/orders/changed after the response) when one of its
 * orders is decided, by any path that saves the model; never for another
 * venture's order, never for Streamr's own writes, and nothing it does can
 * break the save.
 */
class StreamrOrderHookTest extends StreamrTestCase
{
    private function streamrOrder(): EcomOrder
    {
        $this->postOrder()->assertOk();

        return EcomOrder::where('orderNumber', 'STR-20261108-7KQ4')->firstOrFail();
    }

    public function test_verifying_through_ecom_orders_rings_streamr_once_after_the_response(): void
    {
        $this->fakeStreamr();
        $this->streamrOrder();
        $paymentId = (int) DB::table('ecom_order_payments')->value('id');
        $admin = $this->admin('Jun Reyes', 'jun@example.test');

        $this->actingAs($admin)->putJson('/ecom-order-payments/' . $paymentId . '/verify', ['action' => 'verify'])->assertOk()->assertJsonPath('success', true);

        $calls = $this->streamrCalls();
        $this->assertCount(1, $calls);
        [$method, $path, $body, $request] = $calls[0];
        $this->assertSame(['POST', '/api/mother/orders/changed'], [$method, $path]);
        $this->assertSame('ecom_order.changed', $body['event']);
        $this->assertSame('STR-20261108-7KQ4', $body['order_number']);
        $this->assertSame((int) DB::table('ecom_orders')->value('id'), $body['mother_order_id']);
        $this->assertSame(['complete', 'verified', 1], [$body['order_status'], $body['payment_verification_status'], $body['delete_status']]);
        $this->assertSame(['id' => $admin->id, 'name' => 'Jun Reyes'], $body['actor']);
        $this->assertSame('observer', $body['source']);
        $this->assertStringEndsWith('+08:00', $body['at']);
        $this->assertSignedForStreamr($request);
        $this->assertNotEmpty($request->header('Idempotency-Key')[0]);

        $logged = DB::table('streamr_api_requests')->where('direction', 'out')->first();
        $this->assertSame(['POST', '/api/mother/orders/changed', 202, 'order_changed'], [$logged->method, $logged->path, (int) $logged->status, $logged->note]);
    }

    public function test_the_doorbell_goes_out_only_after_the_response(): void
    {
        $this->fakeStreamr();
        $order = $this->streamrOrder();

        $order->orderStatus = 'cancelled';
        $order->save();
        $this->assertCount(0, $this->streamrCalls());

        $this->runAfterResponse();
        $this->assertCount(1, $this->streamrCalls());
        $this->assertSame('cancelled', $this->streamrCalls()[0][2]['order_status']);
        $this->assertSame(['id' => null, 'name' => 'System'], $this->streamrCalls()[0][2]['actor']);
    }

    public function test_reject_status_refund_revert_and_delete_ring_too(): void
    {
        $this->fakeStreamr();
        $order = $this->streamrOrder();

        foreach ([['paymentVerificationStatus', 'rejected'], ['orderStatus', 'complete'], ['orderStatus', 'refunded'], ['deleteStatus', 0]] as [$column, $value]) {
            $fresh = EcomOrder::findOrFail($order->id);
            $fresh->{$column} = $value;
            $fresh->save();
            $this->runAfterResponse();
        }

        $sent = collect($this->streamrCalls())->map(fn ($c) => [$c[2]['payment_verification_status'], $c[2]['order_status'], $c[2]['delete_status']])->unique()->values()->all();
        $this->assertSame([['rejected', 'pending', 1], ['rejected', 'complete', 1], ['rejected', 'refunded', 1], ['rejected', 'refunded', 0]], $sent);
    }

    public function test_two_saves_in_one_request_ring_once_with_the_final_state(): void
    {
        $this->fakeStreamr();
        $order = $this->streamrOrder();

        $order->orderStatus = 'complete';
        $order->save();
        $order->paymentVerificationStatus = 'verified';
        $order->save();
        $this->runAfterResponse();

        $this->assertCount(1, $this->streamrCalls());
        $this->assertSame(['complete', 'verified'], [$this->streamrCalls()[0][2]['order_status'], $this->streamrCalls()[0][2]['payment_verification_status']]);
    }

    public function test_other_changes_and_streamrs_own_writes_ring_nothing(): void
    {
        $this->fakeStreamr();
        $order = $this->streamrOrder();   // created + written by Streamr: no ring

        $order->orderNotes = 'An admin note';
        $order->trackingNumber = 'n/a';
        $order->save();
        $this->runAfterResponse();

        Http::assertNothingSent();
    }

    public function test_another_venture_order_rings_nothing_and_costs_no_query(): void
    {
        $this->fakeStreamr();
        $id = $this->otherOrder('ORD-20261108-ABCD');
        $order = EcomOrder::findOrFail($id);

        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });
        $order->orderStatus = 'complete';
        $order->paymentVerificationStatus = 'verified';
        $order->save();

        $this->assertCount(1, $queries, 'Only the UPDATE itself: ' . implode(' | ', $queries));
        $this->assertStringStartsWith('update "ecom_orders"', $queries[0]);
        $this->runAfterResponse();
        Http::assertNothingSent();
    }

    public function test_an_str_order_of_another_store_rings_nothing(): void
    {
        $this->fakeStreamr();
        $id = $this->otherOrder('STR-20261108-ZZZZ', 'Some other store');

        $order = EcomOrder::findOrFail($id);
        $order->orderStatus = 'cancelled';
        $order->save();
        $this->runAfterResponse();

        Http::assertNothingSent();
    }

    public function test_nothing_is_sent_while_streamr_is_not_linked(): void
    {
        $this->fakeStreamr();
        $order = $this->streamrOrder();
        config(['streamr.app_url' => '']);

        $order->orderStatus = 'cancelled';
        $order->save();
        $this->runAfterResponse();

        Http::assertNothingSent();
        $this->assertSame('cancelled', EcomOrder::findOrFail($order->id)->orderStatus);
    }

    public function test_streamr_down_or_failing_never_breaks_the_save(): void
    {
        Http::fake(['streamr.test/*' => fn () => throw new ConnectionException('Connection refused')]);
        $order = $this->streamrOrder();
        $paymentId = (int) DB::table('ecom_order_payments')->value('id');

        $this->actingAs($this->admin())->putJson('/ecom-order-payments/' . $paymentId . '/verify', ['action' => 'verify'])
            ->assertOk()->assertJsonPath('success', true);

        $this->assertSame('verified', EcomOrder::findOrFail($order->id)->paymentVerificationStatus);
        $logged = DB::table('streamr_api_requests')->where('direction', 'out')->first();
        $this->assertSame([null, 'unreachable'], [$logged->status, $logged->errorCode]);

        Http::fake(['streamr.test/*' => Http::response('Down for maintenance', 503)]);
        $fresh = EcomOrder::findOrFail($order->id);
        $fresh->orderStatus = 'refunded';
        $fresh->save();
        $this->runAfterResponse();
        $this->assertSame('refunded', EcomOrder::findOrFail($order->id)->orderStatus);
    }

    public function test_an_exception_inside_the_hook_is_swallowed(): void
    {
        $order = $this->streamrOrder();
        $this->app->bind(StreamrCallbacks::class, fn () => throw new \RuntimeException('boom'));

        $fresh = EcomOrder::findOrFail($order->id);
        $fresh->orderStatus = 'cancelled';
        $this->assertTrue($fresh->save());
        $this->runAfterResponse();

        $this->assertSame('cancelled', EcomOrder::findOrFail($order->id)->orderStatus);

        // Even the hook service itself failing cannot reach the save.
        $this->app->bind(\App\Services\Streamr\StreamrOrderHookService::class, fn () => throw new \RuntimeException('worse'));
        $fresh = EcomOrder::findOrFail($order->id);
        $fresh->deleteStatus = 0;
        $this->assertTrue($fresh->save());
        $this->assertSame(0, (int) EcomOrder::findOrFail($order->id)->deleteStatus);
    }
}
