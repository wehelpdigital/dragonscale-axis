<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\EcomOrder;
use App\Models\EcomOrderItem;
use App\Models\EcomOrderDiscount;
use App\Models\EcomOrderAffiliateCommission;
use App\Models\EcomProductStore;
use App\Models\EcomProduct;
use App\Models\EcomSalesReport;
use App\Models\EcomRefundRequest;
use App\Models\EcomRefundItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class SalesReportsController extends Controller
{
    /**
     * Get profitability data - Cost, Revenue, Margin analysis
     * Joins order items with variants to calculate profit margins
     */
    public function getProfitabilityData(Request $request)
    {
        try {
            // Get order items with variant cost prices
            $itemsData = DB::table('ecom_order_items as oi')
                ->join('ecom_orders as o', 'oi.orderId', '=', 'o.id')
                ->leftJoin('ecom_products_variants as v', 'oi.variantId', '=', 'v.id')
                ->where('o.deleteStatus', 1)
                ->whereIn('o.orderStatus', ['complete', 'refunded'])
                ->where('oi.deleteStatus', 1);

            // Apply date filters
            if ($request->filled('dateFrom')) {
                $itemsData->whereDate('o.created_at', '>=', $request->dateFrom);
            }
            if ($request->filled('dateTo')) {
                $itemsData->whereDate('o.created_at', '<=', $request->dateTo);
            }

            // Apply store filter
            if ($request->filled('storeIds')) {
                $storeIds = is_array($request->storeIds) ? $request->storeIds : [$request->storeIds];
                $itemsData->whereIn('oi.productStore', function ($sq) use ($storeIds) {
                    $sq->select('storeName')
                        ->from('ecom_product_stores')
                        ->whereIn('id', $storeIds);
                });
            }

            $items = $itemsData->select([
                DB::raw('SUM(oi.quantity) as totalQuantity'),
                DB::raw('SUM(oi.subtotal) as totalRevenue'),
                DB::raw('SUM(oi.quantity * COALESCE(v.costPrice, 0)) as totalCost'),
                DB::raw('SUM(oi.unitPrice * oi.quantity) as grossRevenue'),
                DB::raw('AVG(oi.unitPrice) as avgSellingPrice'),
                DB::raw('AVG(COALESCE(v.costPrice, 0)) as avgCostPrice')
            ])->first();

            // Get refund data for the period
            $refundQuery = EcomRefundRequest::where('deleteStatus', 1)
                ->where('status', 'processed');

            if ($request->filled('dateFrom')) {
                $refundQuery->whereDate('processedAt', '>=', $request->dateFrom);
            }
            if ($request->filled('dateTo')) {
                $refundQuery->whereDate('processedAt', '<=', $request->dateTo);
            }
            if ($request->filled('storeIds')) {
                $storeIds = is_array($request->storeIds) ? $request->storeIds : [$request->storeIds];
                $refundQuery->whereIn('storeName', function ($sq) use ($storeIds) {
                    $sq->select('storeName')
                        ->from('ecom_product_stores')
                        ->whereIn('id', $storeIds);
                });
            }

            $totalRefunds = $refundQuery->sum('approvedAmount');
            $refundCount = $refundQuery->count();

            // Calculate refunded cost (items that were refunded)
            $refundedCost = DB::table('ecom_refund_items as ri')
                ->join('ecom_refund_requests as rr', 'ri.refundRequestId', '=', 'rr.id')
                ->leftJoin('ecom_products_variants as v', 'ri.variantId', '=', 'v.id')
                ->where('rr.deleteStatus', 1)
                ->where('ri.deleteStatus', 1)
                ->where('rr.status', 'processed')
                ->when($request->filled('dateFrom'), function ($q) use ($request) {
                    $q->whereDate('rr.processedAt', '>=', $request->dateFrom);
                })
                ->when($request->filled('dateTo'), function ($q) use ($request) {
                    $q->whereDate('rr.processedAt', '<=', $request->dateTo);
                })
                ->sum(DB::raw('ri.refundQuantity * COALESCE(v.costPrice, 0)'));

            // Get discounts total
            $discountsQuery = $this->getBaseQuery($request);
            $totalDiscounts = (clone $discountsQuery)->sum('discountTotal');

            // Get commissions total
            $totalCommissions = (clone $discountsQuery)->sum('affiliateCommissionTotal');

            // Calculate metrics
            $grossRevenue = (float) ($items->grossRevenue ?? 0);
            $totalCost = (float) ($items->totalCost ?? 0);
            $netRevenue = $grossRevenue - (float) $totalDiscounts - (float) $totalRefunds;
            $adjustedCost = $totalCost - (float) $refundedCost; // Remove cost of refunded items

            $grossProfit = $netRevenue - $adjustedCost;
            $netProfit = $grossProfit - (float) $totalCommissions;

            $grossMargin = $netRevenue > 0 ? round(($grossProfit / $netRevenue) * 100, 2) : 0;
            $netMargin = $netRevenue > 0 ? round(($netProfit / $netRevenue) * 100, 2) : 0;

            // Profit by store
            $profitByStore = DB::table('ecom_order_items as oi')
                ->join('ecom_orders as o', 'oi.orderId', '=', 'o.id')
                ->leftJoin('ecom_products_variants as v', 'oi.variantId', '=', 'v.id')
                ->where('o.deleteStatus', 1)
                ->whereIn('o.orderStatus', ['complete', 'refunded'])
                ->where('oi.deleteStatus', 1)
                ->when($request->filled('dateFrom'), function ($q) use ($request) {
                    $q->whereDate('o.created_at', '>=', $request->dateFrom);
                })
                ->when($request->filled('dateTo'), function ($q) use ($request) {
                    $q->whereDate('o.created_at', '<=', $request->dateTo);
                })
                ->select([
                    'oi.productStore',
                    DB::raw('SUM(oi.subtotal) as revenue'),
                    DB::raw('SUM(oi.quantity * COALESCE(v.costPrice, 0)) as cost'),
                    DB::raw('SUM(oi.subtotal) - SUM(oi.quantity * COALESCE(v.costPrice, 0)) as profit'),
                    DB::raw('SUM(oi.quantity) as unitsSold')
                ])
                ->groupBy('oi.productStore')
                ->orderByDesc('profit')
                ->get()
                ->map(function ($store) {
                    $margin = $store->revenue > 0 ? round(($store->profit / $store->revenue) * 100, 1) : 0;
                    return [
                        'storeName' => $store->productStore ?: 'Unknown Store',
                        'revenue' => (float) $store->revenue,
                        'cost' => (float) $store->cost,
                        'profit' => (float) $store->profit,
                        'margin' => $margin,
                        'unitsSold' => (int) $store->unitsSold
                    ];
                });

            // Profit by product (top 20)
            $profitByProduct = DB::table('ecom_order_items as oi')
                ->join('ecom_orders as o', 'oi.orderId', '=', 'o.id')
                ->leftJoin('ecom_products_variants as v', 'oi.variantId', '=', 'v.id')
                ->where('o.deleteStatus', 1)
                ->whereIn('o.orderStatus', ['complete', 'refunded'])
                ->where('oi.deleteStatus', 1)
                ->when($request->filled('dateFrom'), function ($q) use ($request) {
                    $q->whereDate('o.created_at', '>=', $request->dateFrom);
                })
                ->when($request->filled('dateTo'), function ($q) use ($request) {
                    $q->whereDate('o.created_at', '<=', $request->dateTo);
                })
                ->select([
                    'oi.productId',
                    'oi.productName',
                    'oi.productStore',
                    DB::raw('SUM(oi.quantity) as unitsSold'),
                    DB::raw('AVG(oi.unitPrice) as avgSellingPrice'),
                    DB::raw('AVG(COALESCE(v.costPrice, 0)) as avgCostPrice'),
                    DB::raw('SUM(oi.subtotal) as revenue'),
                    DB::raw('SUM(oi.quantity * COALESCE(v.costPrice, 0)) as cost'),
                    DB::raw('SUM(oi.subtotal) - SUM(oi.quantity * COALESCE(v.costPrice, 0)) as profit')
                ])
                ->groupBy('oi.productId', 'oi.productName', 'oi.productStore')
                ->orderByDesc('profit')
                ->limit(20)
                ->get()
                ->map(function ($product) {
                    $margin = $product->revenue > 0 ? round(($product->profit / $product->revenue) * 100, 1) : 0;
                    $marginPerUnit = $product->unitsSold > 0 ? round($product->profit / $product->unitsSold, 2) : 0;
                    return [
                        'productId' => $product->productId,
                        'productName' => $product->productName ?: 'Unknown Product',
                        'storeName' => $product->productStore ?: 'Unknown Store',
                        'unitsSold' => (int) $product->unitsSold,
                        'avgSellingPrice' => (float) $product->avgSellingPrice,
                        'avgCostPrice' => (float) $product->avgCostPrice,
                        'revenue' => (float) $product->revenue,
                        'cost' => (float) $product->cost,
                        'profit' => (float) $product->profit,
                        'margin' => $margin,
                        'marginPerUnit' => $marginPerUnit
                    ];
                });

            return response()->json([
                'success' => true,
                'summary' => [
                    'grossRevenue' => $grossRevenue,
                    'totalCost' => $totalCost,
                    'totalDiscounts' => (float) $totalDiscounts,
                    'totalRefunds' => (float) $totalRefunds,
                    'refundedCost' => (float) $refundedCost,
                    'netRevenue' => $netRevenue,
                    'adjustedCost' => $adjustedCost,
                    'grossProfit' => $grossProfit,
                    'totalCommissions' => (float) $totalCommissions,
                    'netProfit' => $netProfit,
                    'grossMargin' => $grossMargin,
                    'netMargin' => $netMargin,
                    'avgSellingPrice' => (float) ($items->avgSellingPrice ?? 0),
                    'avgCostPrice' => (float) ($items->avgCostPrice ?? 0),
                    'totalQuantity' => (int) ($items->totalQuantity ?? 0),
                    'refundCount' => $refundCount
                ],
                'byStore' => $profitByStore,
                'byProduct' => $profitByProduct
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting profitability data: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error loading profitability data: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get detailed refunds report
     */
    public function getRefundsReport(Request $request)
    {
        try {
            // Base refund query
            $baseQuery = EcomRefundRequest::where('deleteStatus', 1);

            if ($request->filled('dateFrom')) {
                $baseQuery->whereDate('created_at', '>=', $request->dateFrom);
            }
            if ($request->filled('dateTo')) {
                $baseQuery->whereDate('created_at', '<=', $request->dateTo);
            }
            if ($request->filled('storeIds')) {
                $storeIds = is_array($request->storeIds) ? $request->storeIds : [$request->storeIds];
                $baseQuery->whereIn('storeName', function ($sq) use ($storeIds) {
                    $sq->select('storeName')
                        ->from('ecom_product_stores')
                        ->whereIn('id', $storeIds);
                });
            }

            // Summary statistics
            $allRefunds = (clone $baseQuery)->get();
            $totalRequested = $allRefunds->sum('requestedAmount');
            $totalApproved = $allRefunds->where('status', 'processed')->sum('approvedAmount');
            $totalPending = $allRefunds->where('status', 'pending')->sum('requestedAmount');
            $totalRejected = $allRefunds->where('status', 'rejected')->count();

            $byStatus = [
                'pending' => $allRefunds->where('status', 'pending')->count(),
                'approved' => $allRefunds->where('status', 'approved')->count(),
                'processed' => $allRefunds->where('status', 'processed')->count(),
                'rejected' => $allRefunds->where('status', 'rejected')->count()
            ];

            // Refunds by store
            $byStore = (clone $baseQuery)
                ->where('status', 'processed')
                ->select([
                    'storeName',
                    DB::raw('COUNT(*) as refundCount'),
                    DB::raw('SUM(approvedAmount) as totalRefunded'),
                    DB::raw('AVG(approvedAmount) as avgRefund')
                ])
                ->groupBy('storeName')
                ->orderByDesc('totalRefunded')
                ->get()
                ->map(function ($store) {
                    return [
                        'storeName' => $store->storeName ?: 'Unknown Store',
                        'refundCount' => (int) $store->refundCount,
                        'totalRefunded' => (float) $store->totalRefunded,
                        'avgRefund' => (float) $store->avgRefund
                    ];
                });

            // Top refunded products
            $refundedProducts = DB::table('ecom_refund_items as ri')
                ->join('ecom_refund_requests as rr', 'ri.refundRequestId', '=', 'rr.id')
                ->where('rr.deleteStatus', 1)
                ->where('ri.deleteStatus', 1)
                ->where('rr.status', 'processed')
                ->when($request->filled('dateFrom'), function ($q) use ($request) {
                    $q->whereDate('rr.processedAt', '>=', $request->dateFrom);
                })
                ->when($request->filled('dateTo'), function ($q) use ($request) {
                    $q->whereDate('rr.processedAt', '<=', $request->dateTo);
                })
                ->select([
                    'ri.productId',
                    'ri.productName',
                    'ri.productStore',
                    DB::raw('SUM(ri.refundQuantity) as totalQuantity'),
                    DB::raw('SUM(ri.refundAmount) as totalRefunded'),
                    DB::raw('COUNT(DISTINCT rr.id) as refundCount')
                ])
                ->groupBy('ri.productId', 'ri.productName', 'ri.productStore')
                ->orderByDesc('totalRefunded')
                ->limit(15)
                ->get()
                ->map(function ($product) {
                    return [
                        'productId' => $product->productId,
                        'productName' => $product->productName ?: 'Unknown Product',
                        'storeName' => $product->productStore ?: 'Unknown Store',
                        'totalQuantity' => (int) $product->totalQuantity,
                        'totalRefunded' => (float) $product->totalRefunded,
                        'refundCount' => (int) $product->refundCount
                    ];
                });

            // Refunds trend (by month)
            $refundTrend = (clone $baseQuery)
                ->where('status', 'processed')
                ->select([
                    DB::raw("DATE_FORMAT(processedAt, '%Y-%m') as period"),
                    DB::raw('COUNT(*) as refundCount'),
                    DB::raw('SUM(approvedAmount) as totalRefunded')
                ])
                ->groupBy('period')
                ->orderBy('period')
                ->get()
                ->map(function ($item) {
                    $label = Carbon::parse($item->period . '-01')->format('M Y');
                    return [
                        'period' => $item->period,
                        'label' => $label,
                        'refundCount' => (int) $item->refundCount,
                        'totalRefunded' => (float) $item->totalRefunded
                    ];
                });

            // Calculate refund rate (refunds vs total orders)
            $ordersQuery = $this->getBaseQuery($request);
            $totalOrders = (clone $ordersQuery)->count();
            $refundRate = $totalOrders > 0 ? round(($byStatus['processed'] / $totalOrders) * 100, 2) : 0;

            return response()->json([
                'success' => true,
                'summary' => [
                    'totalRequested' => $totalRequested,
                    'totalApproved' => $totalApproved,
                    'totalPending' => $totalPending,
                    'rejectedCount' => $totalRejected,
                    'refundRate' => $refundRate,
                    'totalOrders' => $totalOrders
                ],
                'byStatus' => $byStatus,
                'byStore' => $byStore,
                'refundedProducts' => $refundedProducts,
                'trend' => $refundTrend
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting refunds report: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error loading refunds data'], 500);
        }
    }
    /**
     * Display the sales reports page.
     */
    public function index(Request $request)
    {
        $stores = EcomProductStore::where('deleteStatus', 1)
            ->where('isActive', 1)
            ->orderBy('storeName')
            ->get();

        $savedReports = EcomSalesReport::active()
            ->forUser(Auth::id())
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('ecommerce.reports.sales', compact('stores', 'savedReports'));
    }

    /**
     * Get sales overview KPIs
     * Includes refund calculations - net sales = gross sales - refunds
     */
    public function getOverview(Request $request)
    {
        try {
            $query = $this->getBaseQuery($request);

            // Total metrics from orders
            $totals = $query->select([
                DB::raw('COUNT(*) as totalOrders'),
                DB::raw('SUM(grandTotal) as totalSales'),
                DB::raw('SUM(subtotal) as totalSubtotal'),
                DB::raw('SUM(shippingTotal) as totalShipping'),
                DB::raw('SUM(discountTotal) as totalDiscounts'),
                DB::raw('SUM(affiliateCommissionTotal) as totalCommissions'),
                DB::raw('SUM(netRevenue) as totalNetRevenue'),
                DB::raw('AVG(grandTotal) as avgOrderValue')
            ])->first();

            // Calculate total refunds for the period
            $refundQuery = EcomRefundRequest::where('deleteStatus', 1)
                ->where('status', 'processed');

            // Apply date filters to refunds based on when they were processed
            if ($request->filled('dateFrom')) {
                $refundQuery->whereDate('processedAt', '>=', $request->dateFrom);
            }
            if ($request->filled('dateTo')) {
                $refundQuery->whereDate('processedAt', '<=', $request->dateTo);
            }

            // Apply store filter to refunds
            if ($request->filled('storeIds')) {
                $storeIds = is_array($request->storeIds) ? $request->storeIds : [$request->storeIds];
                $refundQuery->whereIn('storeName', function ($sq) use ($storeIds) {
                    $sq->select('storeName')
                        ->from('ecom_product_stores')
                        ->whereIn('id', $storeIds);
                });
            }

            $totalRefunds = $refundQuery->sum('approvedAmount');
            $refundCount = $refundQuery->count();

            // Calculate items sold (subtract refunded quantities)
            $itemsSold = EcomOrderItem::whereIn('orderId', function ($q) use ($request) {
                $this->applyFiltersToSubquery($q, $request);
            })->where('deleteStatus', 1)->sum('quantity');

            // Get refunded item quantities
            $refundedItemsQuery = EcomRefundItem::where('deleteStatus', 1)
                ->whereHas('refundRequest', function ($q) use ($request) {
                    $q->where('deleteStatus', 1)
                        ->where('status', 'processed');
                    if ($request->filled('dateFrom')) {
                        $q->whereDate('processedAt', '>=', $request->dateFrom);
                    }
                    if ($request->filled('dateTo')) {
                        $q->whereDate('processedAt', '<=', $request->dateTo);
                    }
                });
            $refundedQuantity = $refundedItemsQuery->sum('refundQuantity');

            // Net items sold = total sold - refunded quantity
            $netItemsSold = $itemsSold - $refundedQuantity;

            // Previous period comparison (already includes refund calculation)
            $previousPeriod = $this->getPreviousPeriodData($request);

            // Net sales = gross sales - refunds
            $netSales = (float) $totals->totalSales - $totalRefunds;

            // Growth calculations based on net sales
            $salesGrowth = $this->calculateGrowth($netSales, $previousPeriod['totalSales']);
            $ordersGrowth = $this->calculateGrowth($totals->totalOrders, $previousPeriod['totalOrders']);

            // Adjusted net revenue (accounting for refunds)
            $adjustedNetRevenue = (float) $totals->totalNetRevenue - $totalRefunds;

            // Calculate profit metrics by joining order items with variants
            $profitData = DB::table('ecom_order_items as oi')
                ->join('ecom_orders as o', 'oi.orderId', '=', 'o.id')
                ->leftJoin('ecom_products_variants as v', 'oi.variantId', '=', 'v.id')
                ->where('o.deleteStatus', 1)
                ->whereIn('o.orderStatus', ['complete', 'refunded'])
                ->where('oi.deleteStatus', 1);

            // Apply same filters
            if ($request->filled('dateFrom')) {
                $profitData->whereDate('o.created_at', '>=', $request->dateFrom);
            }
            if ($request->filled('dateTo')) {
                $profitData->whereDate('o.created_at', '<=', $request->dateTo);
            }
            if ($request->filled('storeIds')) {
                $storeIds = is_array($request->storeIds) ? $request->storeIds : [$request->storeIds];
                $profitData->whereIn('oi.productStore', function ($sq) use ($storeIds) {
                    $sq->select('storeName')
                        ->from('ecom_product_stores')
                        ->whereIn('id', $storeIds);
                });
            }

            $costData = $profitData->select([
                DB::raw('SUM(oi.quantity * COALESCE(v.costPrice, 0)) as totalCost'),
                DB::raw('SUM(oi.subtotal) as totalProductRevenue')
            ])->first();

            $totalCost = (float) ($costData->totalCost ?? 0);
            $grossProfit = $netSales - $totalCost;
            $netProfit = $grossProfit - (float) $totals->totalCommissions;
            $grossMargin = $netSales > 0 ? round(($grossProfit / $netSales) * 100, 2) : 0;
            $netMargin = $netSales > 0 ? round(($netProfit / $netSales) * 100, 2) : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'totalOrders' => (int) $totals->totalOrders,
                    'totalSales' => $netSales, // Net sales after refunds
                    'grossSales' => (float) $totals->totalSales, // Gross sales before refunds
                    'totalRefunds' => $totalRefunds, // Total refunded amount
                    'refundCount' => $refundCount, // Number of processed refunds
                    'totalSubtotal' => (float) $totals->totalSubtotal,
                    'totalShipping' => (float) $totals->totalShipping,
                    'totalDiscounts' => (float) $totals->totalDiscounts,
                    'totalCommissions' => (float) $totals->totalCommissions,
                    'totalNetRevenue' => $adjustedNetRevenue, // Net revenue after refunds
                    'avgOrderValue' => (float) $totals->avgOrderValue,
                    'itemsSold' => (int) $netItemsSold, // Net items after refunds
                    'grossItemsSold' => (int) $itemsSold, // Gross items before refunds
                    'salesGrowth' => $salesGrowth,
                    'ordersGrowth' => $ordersGrowth,
                    // New profit metrics
                    'totalCost' => $totalCost,
                    'grossProfit' => $grossProfit,
                    'netProfit' => $netProfit,
                    'grossMargin' => $grossMargin,
                    'netMargin' => $netMargin
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting sales overview: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error loading overview data'], 500);
        }
    }

    /**
     * Get sales by store
     */
    public function getSalesByStore(Request $request)
    {
        try {
            // Get store sales from order items (productStore field contains store name)
            $storeData = EcomOrderItem::select([
                'productStore',
                DB::raw('COUNT(DISTINCT orderId) as orderCount'),
                DB::raw('SUM(quantity) as unitsSold'),
                DB::raw('SUM(subtotal) as totalSales')
            ])
            ->whereIn('orderId', function ($q) use ($request) {
                $this->applyFiltersToSubquery($q, $request);
            })
            ->where('deleteStatus', 1)
            ->groupBy('productStore')
            ->orderByDesc('totalSales')
            ->get();

            // Calculate percentages
            $grandTotal = $storeData->sum('totalSales');
            $formattedData = $storeData->map(function ($store) use ($grandTotal) {
                return [
                    'storeName' => $store->productStore ?: 'Unknown Store',
                    'orderCount' => (int) $store->orderCount,
                    'unitsSold' => (int) $store->unitsSold,
                    'totalSales' => (float) $store->totalSales,
                    'percentage' => $grandTotal > 0 ? round(($store->totalSales / $grandTotal) * 100, 1) : 0
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedData,
                'grandTotal' => $grandTotal
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting sales by store: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error loading store data'], 500);
        }
    }

    /**
     * Get sales by product with margin data
     */
    public function getSalesByProduct(Request $request)
    {
        try {
            $limit = $request->input('limit', 20);

            // Join with variants to get cost price for margin calculation
            $productData = DB::table('ecom_order_items as oi')
                ->join('ecom_orders as o', 'oi.orderId', '=', 'o.id')
                ->leftJoin('ecom_products_variants as v', 'oi.variantId', '=', 'v.id')
                ->where('o.deleteStatus', 1)
                ->whereIn('o.orderStatus', ['complete', 'refunded'])
                ->where('oi.deleteStatus', 1);

            // Apply date filters
            if ($request->filled('dateFrom')) {
                $productData->whereDate('o.created_at', '>=', $request->dateFrom);
            }
            if ($request->filled('dateTo')) {
                $productData->whereDate('o.created_at', '<=', $request->dateTo);
            }

            // Apply store filter
            if ($request->filled('storeIds')) {
                $storeIds = is_array($request->storeIds) ? $request->storeIds : [$request->storeIds];
                $productData->whereIn('oi.productStore', function ($sq) use ($storeIds) {
                    $sq->select('storeName')
                        ->from('ecom_product_stores')
                        ->whereIn('id', $storeIds);
                });
            }

            $productData = $productData->select([
                'oi.productId',
                'oi.productName',
                'oi.productStore',
                DB::raw('SUM(oi.quantity) as unitsSold'),
                DB::raw('SUM(oi.subtotal) as totalSales'),
                DB::raw('AVG(oi.unitPrice) as avgPrice'),
                DB::raw('AVG(COALESCE(v.costPrice, 0)) as avgCostPrice'),
                DB::raw('SUM(oi.quantity * COALESCE(v.costPrice, 0)) as totalCost'),
                DB::raw('SUM(oi.subtotal) - SUM(oi.quantity * COALESCE(v.costPrice, 0)) as totalProfit'),
                DB::raw('COUNT(DISTINCT oi.orderId) as orderCount')
            ])
            ->groupBy('oi.productId', 'oi.productName', 'oi.productStore')
            ->orderByDesc('totalSales')
            ->limit($limit)
            ->get();

            $grandTotal = $productData->sum('totalSales');
            $grandProfit = $productData->sum('totalProfit');

            $formattedData = $productData->map(function ($product) use ($grandTotal) {
                $margin = $product->totalSales > 0 ? round(($product->totalProfit / $product->totalSales) * 100, 1) : 0;
                $marginPerUnit = $product->unitsSold > 0 ? round($product->totalProfit / $product->unitsSold, 2) : 0;

                return [
                    'productId' => $product->productId,
                    'productName' => $product->productName ?: 'Unknown Product',
                    'storeName' => $product->productStore ?: 'Unknown Store',
                    'unitsSold' => (int) $product->unitsSold,
                    'totalSales' => (float) $product->totalSales,
                    'avgPrice' => (float) $product->avgPrice,
                    'avgCostPrice' => (float) $product->avgCostPrice,
                    'totalCost' => (float) $product->totalCost,
                    'totalProfit' => (float) $product->totalProfit,
                    'margin' => $margin,
                    'marginPerUnit' => $marginPerUnit,
                    'orderCount' => (int) $product->orderCount,
                    'percentage' => $grandTotal > 0 ? round(($product->totalSales / $grandTotal) * 100, 1) : 0
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedData,
                'grandTotal' => $grandTotal,
                'grandProfit' => $grandProfit
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting sales by product: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error loading product data'], 500);
        }
    }

    /**
     * Get sales trend over time
     */
    public function getSalesTrend(Request $request)
    {
        try {
            $groupBy = $request->input('groupBy', 'daily'); // daily, weekly, monthly

            $query = $this->getBaseQuery($request);

            // Determine date format based on grouping
            switch ($groupBy) {
                case 'weekly':
                    $dateFormat = '%Y-%u'; // Year-Week
                    $labelFormat = 'Week %u, %Y';
                    break;
                case 'monthly':
                    $dateFormat = '%Y-%m';
                    $labelFormat = '%M %Y';
                    break;
                default: // daily
                    $dateFormat = '%Y-%m-%d';
                    $labelFormat = '%b %d';
            }

            $trendData = $query->select([
                DB::raw("DATE_FORMAT(created_at, '$dateFormat') as period"),
                DB::raw('COUNT(*) as orderCount'),
                DB::raw('SUM(grandTotal) as totalSales'),
                DB::raw('SUM(netRevenue) as netRevenue'),
                DB::raw('AVG(grandTotal) as avgOrderValue')
            ])
            ->groupBy('period')
            ->orderBy('period')
            ->get();

            // Format labels
            $formattedData = $trendData->map(function ($item) use ($groupBy) {
                $label = $item->period;
                if ($groupBy === 'daily') {
                    $label = Carbon::parse($item->period)->format('M d');
                } elseif ($groupBy === 'monthly') {
                    $label = Carbon::parse($item->period . '-01')->format('M Y');
                } elseif ($groupBy === 'weekly') {
                    $parts = explode('-', $item->period);
                    $label = 'W' . $parts[1] . ' ' . $parts[0];
                }

                return [
                    'period' => $item->period,
                    'label' => $label,
                    'orderCount' => (int) $item->orderCount,
                    'totalSales' => (float) $item->totalSales,
                    'netRevenue' => (float) $item->netRevenue,
                    'avgOrderValue' => (float) $item->avgOrderValue
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedData,
                'groupBy' => $groupBy
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting sales trend: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error loading trend data'], 500);
        }
    }

    /**
     * Get discount analysis
     */
    public function getDiscountReport(Request $request)
    {
        try {
            // Get discount usage summary - include isAutoApplied for trigger type
            // Left join with source discount table to get discount code if not stored in order
            $discountData = EcomOrderDiscount::select([
                'ecom_order_discounts.discountId',
                'ecom_order_discounts.discountName',
                'ecom_order_discounts.discountCode',
                'ecom_order_discounts.discountType',
                'ecom_order_discounts.isAutoApplied',
                'ecom_products_discount.discountCode as sourceDiscountCode',
                DB::raw('COUNT(*) as usageCount'),
                DB::raw('SUM(ecom_order_discounts.calculatedAmount) as totalDiscounted'),
                DB::raw('AVG(ecom_order_discounts.calculatedAmount) as avgDiscount')
            ])
            ->leftJoin('ecom_products_discount', 'ecom_order_discounts.discountId', '=', 'ecom_products_discount.id')
            ->whereIn('ecom_order_discounts.orderId', function ($q) use ($request) {
                $this->applyFiltersToSubquery($q, $request);
            })
            ->where('ecom_order_discounts.deleteStatus', 1)
            ->groupBy(
                'ecom_order_discounts.discountId',
                'ecom_order_discounts.discountName',
                'ecom_order_discounts.discountCode',
                'ecom_order_discounts.discountType',
                'ecom_order_discounts.isAutoApplied',
                'ecom_products_discount.discountCode'
            )
            ->orderByDesc('totalDiscounted')
            ->get();

            // Orders with vs without discounts
            $baseQuery = $this->getBaseQuery($request);
            $totalOrders = (clone $baseQuery)->count();
            $ordersWithDiscount = (clone $baseQuery)->where('discountTotal', '>', 0)->count();
            $ordersWithoutDiscount = $totalOrders - $ordersWithDiscount;

            // Total discount amount
            $totalDiscountAmount = (clone $baseQuery)->sum('discountTotal');

            $formattedData = $discountData->map(function ($discount) {
                // Determine trigger type (Auto Apply vs Discount Code)
                $triggerType = $discount->isAutoApplied ? 'Auto Apply' : 'Discount Code';

                // Format amount type for display
                $amountType = $discount->discountType === 'percentage' ? 'Percentage' : 'Fixed Amount';

                // Use order discount code, or fall back to source discount code
                $discountCode = $discount->discountCode ?: $discount->sourceDiscountCode;

                return [
                    'discountName' => $discount->discountName ?: 'Unknown',
                    'discountCode' => $discountCode,
                    'discountType' => $discount->discountType,
                    'triggerType' => $triggerType,
                    'amountType' => $amountType,
                    'usageCount' => (int) $discount->usageCount,
                    'totalDiscounted' => (float) $discount->totalDiscounted,
                    'avgDiscount' => (float) $discount->avgDiscount
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedData,
                'summary' => [
                    'totalOrders' => $totalOrders,
                    'ordersWithDiscount' => $ordersWithDiscount,
                    'ordersWithoutDiscount' => $ordersWithoutDiscount,
                    'discountRate' => $totalOrders > 0 ? round(($ordersWithDiscount / $totalOrders) * 100, 1) : 0,
                    'totalDiscountAmount' => $totalDiscountAmount
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting discount report: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error loading discount data'], 500);
        }
    }

    /**
     * Get commission report
     */
    public function getCommissionReport(Request $request)
    {
        try {
            // Commission by affiliate
            $affiliateData = EcomOrderAffiliateCommission::select([
                'affiliateName',
                'affiliateEmail',
                'storeName',
                DB::raw('COUNT(*) as orderCount'),
                DB::raw('SUM(baseAmount) as totalBaseAmount'),
                DB::raw('SUM(commissionAmount) as totalCommission'),
                DB::raw('AVG(commissionPercentage) as avgCommissionRate')
            ])
            ->whereIn('orderId', function ($q) use ($request) {
                $this->applyFiltersToSubquery($q, $request);
            })
            ->where('deleteStatus', 1)
            ->groupBy('affiliateName', 'affiliateEmail', 'storeName')
            ->orderByDesc('totalCommission')
            ->get();

            // Commission by store
            $storeCommissions = EcomOrderAffiliateCommission::select([
                'storeName',
                DB::raw('COUNT(*) as orderCount'),
                DB::raw('SUM(commissionAmount) as totalCommission')
            ])
            ->whereIn('orderId', function ($q) use ($request) {
                $this->applyFiltersToSubquery($q, $request);
            })
            ->where('deleteStatus', 1)
            ->groupBy('storeName')
            ->orderByDesc('totalCommission')
            ->get();

            // Total commission
            $totalCommission = $affiliateData->sum('totalCommission');
            $totalBaseAmount = $affiliateData->sum('totalBaseAmount');

            return response()->json([
                'success' => true,
                'byAffiliate' => $affiliateData->map(function ($item) {
                    return [
                        'affiliateName' => $item->affiliateName ?: 'Unknown',
                        'affiliateEmail' => $item->affiliateEmail,
                        'storeName' => $item->storeName,
                        'orderCount' => (int) $item->orderCount,
                        'totalBaseAmount' => (float) $item->totalBaseAmount,
                        'totalCommission' => (float) $item->totalCommission,
                        'avgCommissionRate' => (float) $item->avgCommissionRate
                    ];
                }),
                'byStore' => $storeCommissions->map(function ($item) {
                    return [
                        'storeName' => $item->storeName ?: 'Unknown',
                        'orderCount' => (int) $item->orderCount,
                        'totalCommission' => (float) $item->totalCommission
                    ];
                }),
                'summary' => [
                    'totalCommission' => $totalCommission,
                    'totalBaseAmount' => $totalBaseAmount,
                    'avgCommissionRate' => $totalBaseAmount > 0 ? round(($totalCommission / $totalBaseAmount) * 100, 2) : 0
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting commission report: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error loading commission data'], 500);
        }
    }

    /**
     * Save report to database
     */
    public function saveReport(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'reportName' => 'required|string|max:255',
                'reportType' => 'required|string|in:overview,by_store,by_product,trend,discount,commission',
                'reportData' => 'required|array',
                'notes' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $report = EcomSalesReport::create([
                'usersId' => Auth::id(),
                'reportName' => $request->reportName,
                'reportType' => $request->reportType,
                'dateFrom' => $request->dateFrom,
                'dateTo' => $request->dateTo,
                'filters' => $request->filters,
                'reportData' => $request->reportData,
                'groupBy' => $request->groupBy,
                'notes' => $request->notes,
                'deleteStatus' => 1
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Report saved successfully!',
                'report' => [
                    'id' => $report->id,
                    'reportName' => $report->reportName,
                    'reportType' => $report->reportType,
                    'reportTypeLabel' => $report->reportTypeLabel,
                    'dateRange' => $report->dateRange,
                    'createdAt' => $report->formattedCreatedAt
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error saving report: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error saving report'], 500);
        }
    }

    /**
     * Get saved reports list
     */
    public function getSavedReports(Request $request)
    {
        try {
            $reports = EcomSalesReport::active()
                ->forUser(Auth::id())
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($report) {
                    return [
                        'id' => $report->id,
                        'reportName' => $report->reportName,
                        'reportType' => $report->reportType,
                        'reportTypeLabel' => $report->reportTypeLabel,
                        'dateRange' => $report->dateRange,
                        'createdAt' => $report->formattedCreatedAt,
                        'notes' => $report->notes
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $reports
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting saved reports: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error loading saved reports'], 500);
        }
    }

    /**
     * Load a saved report
     */
    public function loadReport($id)
    {
        try {
            $report = EcomSalesReport::active()
                ->forUser(Auth::id())
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'report' => [
                    'id' => $report->id,
                    'reportName' => $report->reportName,
                    'reportType' => $report->reportType,
                    'reportTypeLabel' => $report->reportTypeLabel,
                    'dateFrom' => $report->dateFrom ? $report->dateFrom->format('Y-m-d') : null,
                    'dateTo' => $report->dateTo ? $report->dateTo->format('Y-m-d') : null,
                    'filters' => $report->filters,
                    'reportData' => $report->reportData,
                    'groupBy' => $report->groupBy,
                    'notes' => $report->notes,
                    'createdAt' => $report->formattedCreatedAt
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading report: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Report not found'], 404);
        }
    }

    /**
     * Delete a saved report
     */
    public function deleteReport($id)
    {
        try {
            $report = EcomSalesReport::active()
                ->forUser(Auth::id())
                ->findOrFail($id);

            $report->update(['deleteStatus' => 0]);

            return response()->json([
                'success' => true,
                'message' => 'Report deleted successfully!'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting report: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error deleting report'], 500);
        }
    }

    /**
     * Export report to CSV/Excel format
     */
    public function exportReport(Request $request)
    {
        try {
            $reportType = $request->input('reportType', 'overview');
            $format = $request->input('format', 'csv');

            // Generate report data based on type
            $data = [];
            $filename = 'sales_report_' . $reportType . '_' . date('Y-m-d');

            switch ($reportType) {
                case 'by_store':
                    $response = $this->getSalesByStore($request);
                    $jsonData = json_decode($response->getContent(), true);
                    $data = $jsonData['data'] ?? [];
                    break;
                case 'by_product':
                    $response = $this->getSalesByProduct($request);
                    $jsonData = json_decode($response->getContent(), true);
                    $data = $jsonData['data'] ?? [];
                    break;
                case 'discount':
                    $response = $this->getDiscountReport($request);
                    $jsonData = json_decode($response->getContent(), true);
                    $data = $jsonData['data'] ?? [];
                    break;
                case 'commission':
                    $response = $this->getCommissionReport($request);
                    $jsonData = json_decode($response->getContent(), true);
                    $data = $jsonData['byAffiliate'] ?? [];
                    break;
                default:
                    $response = $this->getOverview($request);
                    $jsonData = json_decode($response->getContent(), true);
                    $data = [$jsonData['data'] ?? []];
            }

            if ($format === 'csv') {
                return $this->generateCsvResponse($data, $filename);
            }

            return response()->json([
                'success' => true,
                'data' => $data,
                'filename' => $filename
            ]);
        } catch (\Exception $e) {
            Log::error('Error exporting report: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error exporting report'], 500);
        }
    }

    /**
     * Helper: Get base query for orders
     * Include both 'complete' and 'refunded' orders for accurate reporting
     */
    private function getBaseQuery(Request $request)
    {
        $query = EcomOrder::where('deleteStatus', 1)
            ->whereIn('orderStatus', ['complete', 'refunded']);

        // Date filters
        if ($request->filled('dateFrom')) {
            $query->whereDate('created_at', '>=', $request->dateFrom);
        }
        if ($request->filled('dateTo')) {
            $query->whereDate('created_at', '<=', $request->dateTo);
        }

        // Store filter (via order items)
        if ($request->filled('storeIds')) {
            $storeIds = is_array($request->storeIds) ? $request->storeIds : [$request->storeIds];
            $query->whereIn('id', function ($q) use ($storeIds) {
                $q->select('orderId')
                    ->from('ecom_order_items')
                    ->whereIn('productStore', function ($sq) use ($storeIds) {
                        $sq->select('storeName')
                            ->from('ecom_product_stores')
                            ->whereIn('id', $storeIds);
                    });
            });
        }

        return $query;
    }

    /**
     * Helper: Apply filters to subquery
     * Include both 'complete' and 'refunded' orders
     */
    private function applyFiltersToSubquery($query, Request $request)
    {
        $query->select('id')
            ->from('ecom_orders')
            ->where('deleteStatus', 1)
            ->whereIn('orderStatus', ['complete', 'refunded']);

        if ($request->filled('dateFrom')) {
            $query->whereDate('created_at', '>=', $request->dateFrom);
        }
        if ($request->filled('dateTo')) {
            $query->whereDate('created_at', '<=', $request->dateTo);
        }
    }

    /**
     * Helper: Get previous period data for comparison
     * Include both 'complete' and 'refunded' orders
     */
    private function getPreviousPeriodData(Request $request)
    {
        $dateFrom = $request->dateFrom ? Carbon::parse($request->dateFrom) : null;
        $dateTo = $request->dateTo ? Carbon::parse($request->dateTo) : Carbon::now();

        if ($dateFrom) {
            $daysDiff = $dateFrom->diffInDays($dateTo);
            $prevDateTo = $dateFrom->copy()->subDay();
            $prevDateFrom = $prevDateTo->copy()->subDays($daysDiff);
        } else {
            // Default: compare to previous 30 days
            $prevDateTo = Carbon::now()->subDays(30);
            $prevDateFrom = $prevDateTo->copy()->subDays(30);
        }

        $prevQuery = EcomOrder::where('deleteStatus', 1)
            ->whereIn('orderStatus', ['complete', 'refunded'])
            ->whereDate('created_at', '>=', $prevDateFrom)
            ->whereDate('created_at', '<=', $prevDateTo);

        $result = $prevQuery->select([
            DB::raw('COUNT(*) as totalOrders'),
            DB::raw('SUM(grandTotal) as totalSales')
        ])->first();

        // Get refunds for previous period
        $prevRefunds = EcomRefundRequest::where('deleteStatus', 1)
            ->where('status', 'processed')
            ->whereDate('processedAt', '>=', $prevDateFrom)
            ->whereDate('processedAt', '<=', $prevDateTo)
            ->sum('approvedAmount');

        return [
            'totalOrders' => (int) ($result->totalOrders ?? 0),
            'totalSales' => (float) (($result->totalSales ?? 0) - $prevRefunds)
        ];
    }

    /**
     * Helper: Calculate growth percentage
     */
    private function calculateGrowth($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * Helper: Generate CSV response
     */
    private function generateCsvResponse($data, $filename)
    {
        if (empty($data)) {
            return response()->json(['success' => false, 'message' => 'No data to export'], 400);
        }

        $headers = array_keys((array) $data[0]);

        $callback = function () use ($data, $headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);

            foreach ($data as $row) {
                fputcsv($file, (array) $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
        ]);
    }
}
