<?php

namespace Tests\Feature\Games;

use App\Models\EcomOrder;
use App\Models\EcomOrderAuditLog;
use App\Models\EcomOrderItem;
use App\Models\EcomProductStore;
use App\Models\EcomRefundItem;
use App\Models\EcomRefundRequest;
use App\Models\GameOrder;
use App\Models\GameSetting;
use App\Services\Games\SalpakanSyncService;

class SalpakanEcomOrdersTest extends GamesTestCase
{
    private const ORDER_ID = '6f1c3f4e-1111-4222-8333-944455556666';

    private function sync(?array $only = null): array
    {
        return app(SalpakanSyncService::class)->run($only, 60, 'console');
    }

    public function test_a_completed_order_is_logged_once_with_its_store_line_and_customer(): void
    {
        $this->game->players = [FakeGameServer::player(12, ['username' => 'KapitanTikbalang', 'email' => 'a@b.com'])];
        $this->game->orders = [FakeGameServer::order(self::ORDER_ID, 12)];
        $this->fakeServices();

        $results = $this->sync();
        $this->assertSame('ok', $results['ecom']['status'], $results['ecom']['message']);

        $ecom = EcomOrder::where('orderNumber', 'SAL-' . self::ORDER_ID)->firstOrFail();
        $this->assertSame('complete', $ecom->orderStatus);
        $this->assertSame('not_applicable', $ecom->shippingStatus);
        $this->assertSame('online_payment', $ecom->paymentMethod);
        $this->assertSame('not_required', $ecom->paymentVerificationStatus);
        $this->assertSame('174.00', $ecom->grandTotal);          // $3.00 at 58
        $this->assertSame('174.00', $ecom->subtotal);
        $this->assertSame('174.00', $ecom->netRevenue);
        $this->assertSame('0.00', $ecom->shippingTotal);
        $this->assertSame('KapitanTikbalang', $ecom->clientFirstName);
        $this->assertSame('a@b.com', $ecom->clientEmail);
        $this->assertSame(0, $ecom->usersId);
        $this->assertSame('GPA.1234-5678-9012-34567', $ecom->paymentReferenceNumber);
        $this->assertStringContainsString('$3.00', $ecom->orderNotes);
        // Dated when it was paid (02:01 UTC is 10:01 in Manila).
        $this->assertSame('2026-10-25 10:01:00', $ecom->getRawOriginal('created_at'));

        $item = EcomOrderItem::where('orderId', $ecom->id)->sole();
        $this->assertSame('Lava Pit', $item->productName);
        $this->assertSame('Salpakan Tactics!', $item->productStore);
        $this->assertSame('access', $item->productType);
        $this->assertSame('Item', $item->variantName);
        $this->assertSame('item_tier_3', $item->variantSku);
        $this->assertSame('174.00', $item->unitPrice);
        $this->assertSame(1, $item->quantity);

        $store = EcomProductStore::where('storeName', 'Salpakan Tactics!')->sole();
        $this->assertSame(1, $store->isActive);
        $this->assertSame(1, $store->deleteStatus);
        $this->assertSame(1, EcomOrderAuditLog::where('orderId', $ecom->id)->where('actionType', 'order_created')->count());

        $order = GameOrder::forGame('salpakan')->where('remoteOrderId', self::ORDER_ID)->firstOrFail();
        $this->assertSame($ecom->id, $order->ecomOrderId);
        $this->assertSame('completed', $order->ecomStatus);
        $this->assertSame('174.00', $order->ecomPhpAmount);

        // Again and again: still one order, one line, one store, one log entry.
        $this->game->orders[0]['updated_at'] = '2026-10-26T00:00:00Z';
        $this->sync();
        $this->sync();
        $this->assertSame(1, EcomOrder::count());
        $this->assertSame(1, EcomOrderItem::count());
        $this->assertSame(1, EcomProductStore::count());
        $this->assertSame(1, EcomOrderAuditLog::count());
    }

    public function test_pending_cancelled_and_test_purchases_stay_out_until_they_are_real_sales(): void
    {
        $this->game->players = [FakeGameServer::player(1)];
        $this->game->orders = [
            FakeGameServer::order('pending-1', 1, ['status' => 'pending', 'completed_at' => null]),
            FakeGameServer::order('cancelled-1', 1, ['status' => 'cancelled', 'completed_at' => null]),
            FakeGameServer::order('test-1', 1, ['test_purchase' => true]),
        ];
        $this->fakeServices();

        $this->sync();
        $this->assertSame(0, EcomOrder::count());
        $this->assertSame(3, GameOrder::count());

        // The pending one completes: now it is a sale.
        $this->game->orders[0] = FakeGameServer::order('pending-1', 1, ['updated_at' => '2026-10-26T00:00:00Z']);
        $this->sync();

        $this->assertSame(1, EcomOrder::count());
        $this->assertSame('SAL-pending-1', EcomOrder::value('orderNumber'));
        $this->assertNull(GameOrder::where('remoteOrderId', 'test-1')->value('ecomOrderId'));
    }

    public function test_a_refund_follows_the_order_and_is_recorded_like_an_ecom_refund(): void
    {
        $this->game->players = [FakeGameServer::player(12)];
        $this->game->orders = [FakeGameServer::order(self::ORDER_ID, 12)];
        $this->fakeServices();
        $this->sync();

        $this->game->orders[0] = FakeGameServer::order(self::ORDER_ID, 12, ['status' => 'refunded', 'updated_at' => '2026-10-27T03:00:00Z']);
        $this->sync();

        $ecom = EcomOrder::sole();
        $this->assertSame('refunded', $ecom->orderStatus);

        $refund = EcomRefundRequest::where('orderId', $ecom->id)->sole();
        $this->assertSame('processed', $refund->status);
        $this->assertSame('full', $refund->refundType);
        $this->assertSame('174.00', $refund->approvedAmount);
        $this->assertSame('Salpakan Tactics!', $refund->storeName);
        $this->assertSame(1, EcomRefundItem::where('refundRequestId', $refund->id)->count());
        $this->assertSame(1, EcomOrderAuditLog::where('orderId', $ecom->id)->where('actionType', 'refund_processed')->count());

        $order = GameOrder::sole();
        $this->assertSame('refunded', $order->ecomStatus);
        $this->assertSame(0, \App\Models\GamePlayer::where('remotePlayerId', 12)->value('purchasesCount'));

        // Nothing doubles on later syncs.
        $this->sync();
        $this->assertSame(1, EcomRefundRequest::count());
        $this->assertSame(1, EcomOrderAuditLog::where('actionType', 'refund_processed')->count());
    }

    public function test_a_cancelled_sale_is_cancelled_in_ecom_orders(): void
    {
        $this->game->players = [FakeGameServer::player(1)];
        $this->game->orders = [FakeGameServer::order('order-x', 1)];
        $this->fakeServices();
        $this->sync();

        $this->game->orders[0] = FakeGameServer::order('order-x', 1, ['status' => 'cancelled', 'updated_at' => '2026-10-27T03:00:00Z']);
        $this->sync();

        $this->assertSame('cancelled', EcomOrder::sole()->orderStatus);
        $this->assertSame(1, EcomOrderAuditLog::where('actionType', 'status_change')->where('newValue', 'cancelled')->count());
        $this->assertSame(0, EcomRefundRequest::count());
    }

    public function test_an_order_first_seen_refunded_is_logged_with_its_refund(): void
    {
        $this->game->players = [FakeGameServer::player(1, ['email' => '765@steam.invalid', 'sign_up_method' => 'steam'])];
        $this->game->orders = [FakeGameServer::order('order-r', 1, ['status' => 'refunded', 'store' => 'steam', 'store_order_id' => null])];
        $this->fakeServices();

        $this->sync();

        $ecom = EcomOrder::sole();
        $this->assertSame('refunded', $ecom->orderStatus);
        $this->assertNull($ecom->clientEmail);            // a Steam placeholder is not an address
        $this->assertSame(1, EcomRefundRequest::where('orderId', $ecom->id)->where('status', 'processed')->count());
    }

    public function test_an_order_already_in_ecom_orders_is_found_by_its_number_not_made_again(): void
    {
        $existing = new EcomOrder();
        $existing->forceFill(['usersId' => 0, 'orderNumber' => 'SAL-' . self::ORDER_ID, 'orderStatus' => 'complete', 'shippingStatus' => 'not_applicable', 'grandTotal' => 174, 'deleteStatus' => 1])->save();

        $this->game->players = [FakeGameServer::player(12)];
        $this->game->orders = [FakeGameServer::order(self::ORDER_ID, 12)];
        $this->fakeServices();
        $this->sync();

        $this->assertSame(1, EcomOrder::count());
        $this->assertSame($existing->id, GameOrder::sole()->ecomOrderId);
    }

    public function test_the_peso_rate_set_in_settings_is_used(): void
    {
        GameSetting::setValue('salpakan', 'usdToPhpRate', '60');
        $this->game->players = [FakeGameServer::player(1)];
        $this->game->orders = [FakeGameServer::order('order-1', 1, ['price_cents' => 100])];
        $this->fakeServices();

        $this->sync();

        $this->assertSame('60.00', EcomOrder::sole()->grandTotal);
        $this->assertSame('60.0000', GameOrder::sole()->ecomRate);
    }

    public function test_an_order_in_a_currency_without_a_rate_is_kept_back_with_a_reason(): void
    {
        $this->game->players = [FakeGameServer::player(1)];
        $this->game->orders = [FakeGameServer::order('order-eur', 1, ['currency' => 'EUR'])];
        $this->fakeServices();

        $results = $this->sync();

        $this->assertSame('failed', $results['ecom']['status']);
        $this->assertSame(0, EcomOrder::count());
        $this->assertStringContainsString('EUR', GameOrder::sole()->ecomError);
        $this->assertSame('ok', $results['acumbamail']['status']);
    }
}
