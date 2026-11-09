<?php

namespace Tests\Feature\Streamr;

use App\Http\Controllers\Streamr\StreamrController;
use App\Services\Streamr\StreamrBadges;
use Illuminate\Support\Facades\Schema;

/**
 * The sidebar's Streamr block: after Games, five pages, the badges (payments
 * waiting + tickets with unread client messages), and zeros instead of an
 * error on a host where the Streamr tables do not exist yet. The rest of
 * the sidebar is untouched.
 */
class StreamrSidebarTest extends StreamrTestCase
{
    public function test_the_block_renders_with_its_badges_on_every_page(): void
    {
        $this->postOrder()->assertOk();   // one payment to review
        $this->signedJson('POST', '/api/streamr/v1/support/tickets', [
            'uuid' => '6f1c0d2e-3b4a-4c5d-8e9f-0a1b2c3d4e5f', 'number' => 'SR-2026-0007', 'streamr_user_id' => 42, 'subject' => 'Help',
            'message' => ['uuid' => '7a1c0d2e-3b4a-4c5d-8e9f-0a1b2c3d4e51', 'author_type' => 'client', 'body' => 'Hi'],
        ])->assertOk();
        $this->assertSame(['reviews' => 1, 'support' => 1, 'total' => 2], StreamrBadges::counts());

        // Another module's page: the block is there, after Games, before E-commerce.
        $html = $this->actingAs($this->admin())->get(route('ecom-orders'))->assertOk()->getContent();
        $this->assertStringContainsString('<span key="t-streamr">Streamr</span>', $html);
        $this->assertStringContainsString('<span class="badge rounded-pill bg-danger float-end ms-auto me-2">2</span>', $html);
        foreach (['streamr-sales.index', 'streamr-clients.index', 'streamr-payments.index', 'streamr-support.index', 'streamr-health.index'] as $route) {
            $this->assertStringContainsString('href="' . route($route) . '"', $html);
        }
        $games = strpos($html, 'key="t-games"');
        $streamr = strpos($html, 'key="t-streamr"');
        $ecommerce = strpos($html, 'key="t-ecommerce"');
        $this->assertTrue($games < $streamr && $streamr < $ecommerce, 'Streamr sits between Games and E-commerce.');
        foreach (['t-games-payments', 't-ecommerce', 'Sales dashboard', 'Payment reviews', 'Link status'] as $text) {
            $this->assertStringContainsString($text, $html);
        }

        // On its own pages the block is open.
        $this->actingAs($this->admin())->get(route('streamr-clients.index'))->assertOk()->assertSee('sub-menu mm-show', false);
    }

    public function test_the_sidebar_renders_with_zero_badges_without_the_tables(): void
    {
        foreach (array_reverse(StreamrController::TABLES) as $table) {
            Schema::drop($table);
        }
        StreamrBadges::forget();

        $this->assertSame(['reviews' => 0, 'support' => 0, 'total' => 0], StreamrBadges::counts());
        $html = $this->actingAs($this->admin())->get(route('ecom-orders'))->assertOk()->getContent();
        $this->assertStringContainsString('<span key="t-streamr">Streamr</span>', $html);
        $this->assertStringNotContainsString('badge rounded-pill bg-danger float-end', $html);
    }

    public function test_the_badges_are_cached_for_a_minute_and_refreshed_by_streamrs_writes(): void
    {
        $this->assertSame(0, StreamrBadges::counts()['total']);
        $this->postOrder()->assertOk();
        $this->assertSame(1, StreamrBadges::counts()['reviews']);   // a new order clears the cache

        // A change nobody announced waits for the minute to pass.
        \Illuminate\Support\Facades\DB::table('ecom_orders')->update(['paymentVerificationStatus' => 'verified', 'orderStatus' => 'complete']);
        $this->assertSame(1, StreamrBadges::counts()['reviews']);
        StreamrBadges::forget();
        $this->assertSame(0, StreamrBadges::counts()['reviews']);

        // A decision saved on the order clears it at once.
        \Illuminate\Support\Facades\DB::table('ecom_orders')->update(['paymentVerificationStatus' => 'pending', 'orderStatus' => 'pending']);
        $this->assertSame(0, StreamrBadges::counts()['reviews']);
        $this->fakeStreamr();
        $order = \App\Models\EcomOrder::firstOrFail();
        $order->orderStatus = 'cancelled';
        $order->save();
        $this->assertSame(0, StreamrBadges::counts()['reviews']);
        $order->orderStatus = 'pending';
        $order->save();
        $this->assertSame(1, StreamrBadges::counts()['reviews']);
    }
}
