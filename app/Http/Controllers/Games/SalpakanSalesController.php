<?php

namespace App\Http\Controllers\Games;

use App\Models\GameOrder;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

/**
 * Games > Salpakan Tactics! > Sales & orders: every real-money order (Google
 * Play, Steam), its status (refunds included) and whether it is a store test
 * purchase, with totals for whatever the filters select and a CSV export.
 * Each order links to the /ecom-orders order it was logged as.
 */
class SalpakanSalesController extends SalpakanController
{
    public function index()
    {
        $orders = GameOrder::forGame(self::GAME)->active();

        return view('games.salpakan.sales', [
            'gameName' => $this->gameName(),
            'stores' => GameOrder::STORES + (clone $orders)->distinct()->pluck('store', 'store')->all(),
            'categories' => (clone $orders)->whereNotNull('itemCategory')->distinct()->orderBy('itemCategory')->pluck('itemCategory')->all(),
            'kinds' => GameOrder::KINDS,
            'statuses' => GameOrder::STATUSES,
            'currency' => $this->currency(),
        ]);
    }

    public function data(Request $request)
    {
        return DataTables::of($this->filteredOrders($request))
            ->addColumn('storeLabel', fn (GameOrder $o) => $o->storeLabel())
            ->addColumn('kindLabel', fn (GameOrder $o) => $o->kindLabel())
            ->addColumn('statusLabel', fn (GameOrder $o) => $o->statusLabel())
            ->addColumn('priceFormatted', fn (GameOrder $o) => $o->formattedPrice())
            ->addColumn('orderedFormatted', fn (GameOrder $o) => $this->formatTime($o->orderedAt))
            ->addColumn('completedFormatted', fn (GameOrder $o) => $this->formatTime($o->completedAt))
            ->addColumn('playerUrl', fn (GameOrder $o) => $o->playerRowId ? route('games-salpakan-players.show', ['id' => $o->playerRowId]) : null)
            ->addColumn('ecomPhpFormatted', fn (GameOrder $o) => $o->ecomPhpAmount !== null ? '₱' . number_format((float) $o->ecomPhpAmount, 2) : null)
            ->escapeColumns([])
            ->make(true);
    }

    /** What the filtered orders add up to. Revenue is completed orders only, per currency. */
    public function totals(Request $request)
    {
        $rows = $this->filteredOrders($request)
            ->reorder()
            ->select('games_orders.orderStatus', 'games_orders.currency', 'games_orders.store')
            ->selectRaw('COUNT(*) as orders, SUM(games_orders.priceCents) as cents')
            ->groupBy('games_orders.orderStatus', 'games_orders.currency', 'games_orders.store')
            ->get();

        $byStatus = [];
        $revenue = [];
        $refunded = [];
        $byStore = [];
        foreach ($rows as $row) {
            $byStatus[$row->orderStatus] = ($byStatus[$row->orderStatus] ?? 0) + (int) $row->orders;
            if ($row->orderStatus === 'completed') {
                $revenue[$row->currency] = ($revenue[$row->currency] ?? 0) + (int) $row->cents;
                $byStore[$row->store][$row->currency] = ($byStore[$row->store][$row->currency] ?? 0) + (int) $row->cents;
            }
            if ($row->orderStatus === 'refunded') {
                $refunded[$row->currency] = ($refunded[$row->currency] ?? 0) + (int) $row->cents;
            }
        }

        $money = fn (array $amounts) => collect($amounts)->map(fn ($cents, $currency) => GameOrder::money($cents, $currency))->values()->all();
        $completed = $byStatus['completed'] ?? 0;
        $mainRevenue = $revenue[$this->currency()] ?? 0;

        return response()->json([
            'success' => true,
            'data' => [
                'orders' => array_sum($byStatus),
                'byStatus' => $byStatus,
                'revenue' => $money($revenue) ?: [GameOrder::money(0, $this->currency())],
                'refunded' => $money($refunded) ?: [GameOrder::money(0, $this->currency())],
                'average' => GameOrder::money($completed ? intdiv($mainRevenue, $completed) : 0, $this->currency()),
                'byStore' => collect($byStore)->map(fn ($amounts, $store) => [
                    'store' => GameOrder::STORES[$store] ?? $store,
                    'revenue' => implode(' + ', $money($amounts)),
                ])->values()->all(),
            ],
        ]);
    }

    public function export(Request $request)
    {
        return $this->csv(
            'salpakan-orders-' . now()->format('Ymd-His') . '.csv',
            ['Order ID', 'Store', 'Store order ID', 'Player ID', 'Username', 'Email', 'Country', 'Kind', 'Product ID', 'Item', 'Category',
                'Price', 'Currency', 'Status', 'Test purchase', 'Ordered', 'Completed', '/ecom-orders ID', 'Logged amount (PHP)'],
            $this->filteredOrders($request),
            fn (GameOrder $o) => [
                $o->remoteOrderId, $o->store, $o->storeOrderId, $o->remotePlayerId, $o->playerUsername, $o->playerEmail, $o->playerCountry,
                $o->orderKind, $o->productId, $o->itemName, $o->itemCategory, number_format($o->priceCents / 100, 2, '.', ''), $o->currency,
                $o->orderStatus, $o->isTestPurchase ? 'yes' : 'no', $this->formatTime($o->orderedAt, 'Y-m-d H:i:s'),
                $this->formatTime($o->completedAt, 'Y-m-d H:i:s'), $o->ecomOrderId, $o->ecomPhpAmount,
            ],
            'games_orders.id'
        );
    }
}
