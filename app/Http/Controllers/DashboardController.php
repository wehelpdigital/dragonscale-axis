<?php

namespace App\Http\Controllers;

use App\Models\EcomOrder;
use App\Models\EcomOrderItem;
use App\Models\EcomProductStore;
use App\Models\EcomRefundRequest;
use App\Models\EcomAffiliate;
use App\Models\CrmLead;
use App\Models\AsCourseEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the main dashboard.
     */
    public function index()
    {
        $stores = EcomProductStore::where('deleteStatus', 1)
            ->where('isActive', 1)
            ->orderBy('storeName')
            ->get();

        return view('dashboard', compact('stores'));
    }

    /**
     * Get all dashboard data via AJAX.
     */
    public function getData(Request $request)
    {
        try {
            $dateFrom = $request->input('dateFrom', Carbon::now()->subDays(30)->format('Y-m-d'));
            $dateTo = $request->input('dateTo', Carbon::now()->format('Y-m-d'));
            $storeId = $request->input('storeId');

            // Get all dashboard sections in parallel
            $salesKPIs = $this->getSalesKPIs($dateFrom, $dateTo, $storeId);
            $salesTrend = $this->getSalesTrend($dateFrom, $dateTo, $storeId);
            $salesByStore = $this->getSalesByStore($dateFrom, $dateTo);
            $leadsData = $this->getLeadsData($dateFrom, $dateTo);
            $topProvinces = $this->getTopProvinces($dateFrom, $dateTo, $storeId);
            $topProducts = $this->getTopProducts($dateFrom, $dateTo, $storeId, 5);
            $recentOrders = $this->getRecentOrders(5);
            $affiliatesData = $this->getAffiliatesData();

            return response()->json([
                'success' => true,
                'data' => [
                    'salesKPIs' => $salesKPIs,
                    'salesTrend' => $salesTrend,
                    'salesByStore' => $salesByStore,
                    'leads' => $leadsData,
                    'topProvinces' => $topProvinces,
                    'topProducts' => $topProducts,
                    'recentOrders' => $recentOrders,
                    'affiliates' => $affiliatesData,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Dashboard getData error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading dashboard data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get sales KPIs with growth comparison.
     */
    private function getSalesKPIs($dateFrom, $dateTo, $storeId = null)
    {
        // Current period query
        $query = EcomOrder::where('deleteStatus', 1)
            ->whereIn('orderStatus', ['complete', 'refunded'])
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo);

        if ($storeId) {
            $query->whereIn('id', function ($q) use ($storeId) {
                $q->select('orderId')
                    ->from('ecom_order_items')
                    ->whereIn('productStore', function ($sq) use ($storeId) {
                        $sq->select('storeName')
                            ->from('ecom_product_stores')
                            ->where('id', $storeId);
                    });
            });
        }

        $totals = (clone $query)->select([
            DB::raw('COUNT(*) as totalOrders'),
            DB::raw('SUM(grandTotal) as grossSales'),
            DB::raw('SUM(netRevenue) as netRevenue'),
            DB::raw('AVG(grandTotal) as avgOrderValue'),
            DB::raw('SUM(discountTotal) as totalDiscounts'),
            DB::raw('SUM(affiliateCommissionTotal) as totalCommissions')
        ])->first();

        // Get refunds
        $refundQuery = EcomRefundRequest::where('deleteStatus', 1)
            ->where('status', 'processed')
            ->whereDate('processedAt', '>=', $dateFrom)
            ->whereDate('processedAt', '<=', $dateTo);

        $totalRefunds = $refundQuery->sum('approvedAmount');
        $refundCount = $refundQuery->count();

        // Calculate net sales
        $netSales = (float) $totals->grossSales - $totalRefunds;

        // Get cost data for profit calculation
        $costData = DB::table('ecom_order_items as oi')
            ->join('ecom_orders as o', 'oi.orderId', '=', 'o.id')
            ->leftJoin('ecom_products_variants as v', 'oi.variantId', '=', 'v.id')
            ->where('o.deleteStatus', 1)
            ->whereIn('o.orderStatus', ['complete', 'refunded'])
            ->where('oi.deleteStatus', 1)
            ->whereDate('o.created_at', '>=', $dateFrom)
            ->whereDate('o.created_at', '<=', $dateTo)
            ->select([
                DB::raw('SUM(oi.quantity * COALESCE(v.costPrice, 0)) as totalCost')
            ])->first();

        $totalCost = (float) ($costData->totalCost ?? 0);
        $grossProfit = $netSales - $totalCost;
        $netProfit = $grossProfit - (float) $totals->totalCommissions;
        $profitMargin = $netSales > 0 ? round(($netProfit / $netSales) * 100, 1) : 0;

        // Previous period comparison
        $daysDiff = Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo)) + 1;
        $prevDateTo = Carbon::parse($dateFrom)->subDay();
        $prevDateFrom = $prevDateTo->copy()->subDays($daysDiff - 1);

        $prevQuery = EcomOrder::where('deleteStatus', 1)
            ->whereIn('orderStatus', ['complete', 'refunded'])
            ->whereDate('created_at', '>=', $prevDateFrom)
            ->whereDate('created_at', '<=', $prevDateTo);

        $prevTotals = $prevQuery->select([
            DB::raw('COUNT(*) as totalOrders'),
            DB::raw('SUM(grandTotal) as grossSales')
        ])->first();

        $prevRefunds = EcomRefundRequest::where('deleteStatus', 1)
            ->where('status', 'processed')
            ->whereDate('processedAt', '>=', $prevDateFrom)
            ->whereDate('processedAt', '<=', $prevDateTo)
            ->sum('approvedAmount');

        $prevNetSales = (float) $prevTotals->grossSales - $prevRefunds;

        // Calculate growth
        $salesGrowth = $this->calculateGrowth($netSales, $prevNetSales);
        $ordersGrowth = $this->calculateGrowth($totals->totalOrders, $prevTotals->totalOrders);

        // Refund rate
        $refundRate = $totals->totalOrders > 0 ? round(($refundCount / $totals->totalOrders) * 100, 1) : 0;

        return [
            'totalOrders' => (int) $totals->totalOrders,
            'grossSales' => (float) $totals->grossSales,
            'netSales' => $netSales,
            'avgOrderValue' => (float) $totals->avgOrderValue,
            'totalRefunds' => $totalRefunds,
            'refundCount' => $refundCount,
            'refundRate' => $refundRate,
            'totalDiscounts' => (float) $totals->totalDiscounts,
            'totalCommissions' => (float) $totals->totalCommissions,
            'netProfit' => $netProfit,
            'profitMargin' => $profitMargin,
            'salesGrowth' => $salesGrowth,
            'ordersGrowth' => $ordersGrowth,
        ];
    }

    /**
     * Get sales trend data for chart.
     */
    private function getSalesTrend($dateFrom, $dateTo, $storeId = null)
    {
        $query = EcomOrder::where('deleteStatus', 1)
            ->whereIn('orderStatus', ['complete', 'refunded'])
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo);

        if ($storeId) {
            $query->whereIn('id', function ($q) use ($storeId) {
                $q->select('orderId')
                    ->from('ecom_order_items')
                    ->whereIn('productStore', function ($sq) use ($storeId) {
                        $sq->select('storeName')
                            ->from('ecom_product_stores')
                            ->where('id', $storeId);
                    });
            });
        }

        $trendData = $query->select([
            DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d') as period"),
            DB::raw('COUNT(*) as orderCount'),
            DB::raw('SUM(grandTotal) as totalSales'),
            DB::raw('SUM(netRevenue) as netRevenue')
        ])
        ->groupBy('period')
        ->orderBy('period')
        ->get();

        return $trendData->map(function ($item) {
            return [
                'date' => $item->period,
                'label' => Carbon::parse($item->period)->format('M d'),
                'orders' => (int) $item->orderCount,
                'sales' => (float) $item->totalSales,
                'revenue' => (float) $item->netRevenue
            ];
        });
    }

    /**
     * Get sales by store for pie chart.
     */
    private function getSalesByStore($dateFrom, $dateTo)
    {
        $storeData = EcomOrderItem::select([
            'productStore',
            DB::raw('COUNT(DISTINCT orderId) as orderCount'),
            DB::raw('SUM(subtotal) as totalSales')
        ])
        ->whereIn('orderId', function ($q) use ($dateFrom, $dateTo) {
            $q->select('id')
                ->from('ecom_orders')
                ->where('deleteStatus', 1)
                ->whereIn('orderStatus', ['complete', 'refunded'])
                ->whereDate('created_at', '>=', $dateFrom)
                ->whereDate('created_at', '<=', $dateTo);
        })
        ->where('deleteStatus', 1)
        ->groupBy('productStore')
        ->orderByDesc('totalSales')
        ->limit(6)
        ->get();

        $total = $storeData->sum('totalSales');

        return $storeData->map(function ($store) use ($total) {
            return [
                'name' => $store->productStore ?: 'Unknown',
                'orders' => (int) $store->orderCount,
                'sales' => (float) $store->totalSales,
                'percentage' => $total > 0 ? round(($store->totalSales / $total) * 100, 1) : 0
            ];
        });
    }

    /**
     * Get leads summary data.
     */
    private function getLeadsData($dateFrom, $dateTo)
    {
        // Total stats
        $total = CrmLead::active()->count();
        $newLeads = CrmLead::active()->byStatus('new')->count();
        $qualified = CrmLead::active()->byStatus('qualified')->count();
        $won = CrmLead::active()->byStatus('won')->count();
        $lost = CrmLead::active()->byStatus('lost')->count();

        // Leads in period
        $periodLeads = CrmLead::active()
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->count();

        // Conversion rate (won vs total completed: won + lost)
        $completed = $won + $lost;
        $conversionRate = $completed > 0 ? round(($won / $completed) * 100, 1) : 0;

        // By status for chart
        $byStatus = [
            ['status' => 'New', 'count' => $newLeads, 'color' => '#50a5f1'],
            ['status' => 'Qualified', 'count' => $qualified, 'color' => '#34c38f'],
            ['status' => 'Won', 'count' => $won, 'color' => '#556ee6'],
            ['status' => 'Lost', 'count' => $lost, 'color' => '#f46a6a'],
        ];

        return [
            'total' => $total,
            'new' => $newLeads,
            'qualified' => $qualified,
            'won' => $won,
            'lost' => $lost,
            'periodLeads' => $periodLeads,
            'conversionRate' => $conversionRate,
            'byStatus' => $byStatus,
        ];
    }

    /**
     * Get top provinces by orders.
     */
    private function getTopProvinces($dateFrom, $dateTo, $storeId = null)
    {
        $query = EcomOrder::where('deleteStatus', 1)
            ->whereIn('orderStatus', ['complete', 'refunded'])
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->whereNotNull('shippingProvince')
            ->where('shippingProvince', '!=', '');

        if ($storeId) {
            $query->where('storeId', $storeId);
        }

        $provinces = $query->select([
            'shippingProvince',
            DB::raw('COUNT(*) as orderCount'),
            DB::raw('SUM(grandTotal) as totalSales')
        ])
        ->groupBy('shippingProvince')
        ->orderByDesc('totalSales')
        ->limit(5)
        ->get();

        return $provinces->map(function ($p) {
            return [
                'province' => ucwords(strtolower($p->shippingProvince)),
                'orders' => (int) $p->orderCount,
                'sales' => (float) $p->totalSales
            ];
        });
    }

    /**
     * Get top products by sales.
     */
    private function getTopProducts($dateFrom, $dateTo, $storeId = null, $limit = 5)
    {
        $query = DB::table('ecom_order_items as oi')
            ->join('ecom_orders as o', 'oi.orderId', '=', 'o.id')
            ->where('o.deleteStatus', 1)
            ->whereIn('o.orderStatus', ['complete', 'refunded'])
            ->where('oi.deleteStatus', 1)
            ->whereDate('o.created_at', '>=', $dateFrom)
            ->whereDate('o.created_at', '<=', $dateTo);

        if ($storeId) {
            $query->whereIn('oi.productStore', function ($sq) use ($storeId) {
                $sq->select('storeName')
                    ->from('ecom_product_stores')
                    ->where('id', $storeId);
            });
        }

        $products = $query->select([
            'oi.productName',
            'oi.productStore',
            DB::raw('SUM(oi.quantity) as unitsSold'),
            DB::raw('SUM(oi.subtotal) as totalSales')
        ])
        ->groupBy('oi.productName', 'oi.productStore')
        ->orderByDesc('totalSales')
        ->limit($limit)
        ->get();

        return $products->map(function ($p) {
            return [
                'name' => $p->productName ?: 'Unknown',
                'store' => $p->productStore ?: 'Unknown',
                'units' => (int) $p->unitsSold,
                'sales' => (float) $p->totalSales
            ];
        });
    }

    /**
     * Get recent orders.
     */
    private function getRecentOrders($limit = 5)
    {
        $orders = EcomOrder::where('deleteStatus', 1)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get(['id', 'orderNumber', 'clientFirstName', 'clientLastName', 'grandTotal', 'orderStatus', 'created_at']);

        return $orders->map(function ($o) {
            $clientName = trim(($o->clientFirstName ?? '') . ' ' . ($o->clientLastName ?? ''));
            return [
                'id' => $o->id,
                'orderNumber' => $o->orderNumber,
                'client' => $clientName ?: 'N/A',
                'total' => (float) $o->grandTotal,
                'status' => $o->orderStatus,
                'date' => $o->created_at->format('M d, Y h:i A')
            ];
        });
    }

    /**
     * Get affiliates summary.
     */
    private function getAffiliatesData()
    {
        $total = EcomAffiliate::active()->count();
        $activeCount = EcomAffiliate::active()->where('accountStatus', 'active')->count();

        // Total commissions (from completed orders)
        $totalCommissions = DB::table('ecom_order_affiliate_commissions as c')
            ->join('ecom_orders as o', 'c.orderId', '=', 'o.id')
            ->where('o.deleteStatus', 1)
            ->whereIn('o.orderStatus', ['complete', 'refunded'])
            ->where('c.deleteStatus', 1)
            ->sum('c.commissionAmount');

        return [
            'total' => $total,
            'active' => $activeCount,
            'totalCommissions' => (float) $totalCommissions
        ];
    }

    /**
     * Calculate growth percentage.
     */
    private function calculateGrowth($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        return round((($current - $previous) / $previous) * 100, 1);
    }
}
