<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\EcomOrder;
use App\Models\EcomProductStore;
use App\Models\EcomProduct;
use App\Models\EcomAffiliate;
use App\Models\EcomClientShippingAddress;
use App\Models\EcomRefundRequest;
use App\Models\CrmLead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HeatmapController extends Controller
{
    /**
     * Display the heatmap page.
     */
    public function index()
    {
        // Get stores for filter
        $stores = EcomProductStore::active()->enabled()->orderBy('storeName', 'asc')->get();

        // Get products for filter
        $products = EcomProduct::where('deleteStatus', 1)
            ->orderBy('productName', 'asc')
            ->get(['id', 'productName']);

        // Get unique provinces from various sources for the dropdown
        $provinces = $this->getAllProvinces();

        return view('ecommerce.reports.heatmap', compact('stores', 'products', 'provinces'));
    }

    /**
     * Get heatmap data via AJAX.
     */
    public function getData(Request $request)
    {
        $dataSource = $request->input('source', 'orders');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $storeId = $request->input('store_id');
        $productId = $request->input('product_id');
        $province = $request->input('province');

        $data = [];

        switch ($dataSource) {
            case 'orders':
                $data = $this->getOrdersData($startDate, $endDate, $storeId, $productId, $province);
                break;
            case 'leads':
                $data = $this->getLeadsData($startDate, $endDate, $storeId, $province);
                break;
            case 'affiliates':
                $data = $this->getAffiliatesData($startDate, $endDate, $storeId, $province);
                break;
            case 'clients':
                $data = $this->getClientsData($startDate, $endDate, $province);
                break;
            case 'refunds':
                $data = $this->getRefundsData($startDate, $endDate, $storeId, $province);
                break;
        }

        // Aggregate by province and municipality
        $aggregated = $this->aggregateLocationData($data);

        return response()->json([
            'success' => true,
            'data' => $aggregated,
            'source' => $dataSource,
            'total' => count($data),
        ]);
    }

    /**
     * Get orders data (completed orders only).
     * Note: Shipping info is stored directly in ecom_orders table.
     */
    private function getOrdersData($startDate, $endDate, $storeId, $productId, $province)
    {
        $query = EcomOrder::where('deleteStatus', 1)
            ->where('orderStatus', 'complete')
            ->whereNotNull('shippingProvince')
            ->where('shippingProvince', '!=', '');

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        if ($storeId) {
            $query->where('storeId', $storeId);
        }
        if ($productId) {
            $query->whereHas('items', function ($q) use ($productId) {
                $q->where('productId', $productId);
            });
        }
        if ($province) {
            $query->where('shippingProvince', 'like', '%' . $province . '%');
        }

        $orders = $query->get();

        $data = [];
        foreach ($orders as $order) {
            $data[] = [
                'id' => $order->id,
                'province' => $order->shippingProvince,
                'municipality' => $order->shippingMunicipality ?? '',
                'barangay' => $order->shippingZone ?? '', // Zone is used as barangay
                'date' => $order->created_at->format('Y-m-d'),
                'value' => $order->grandTotal ?? 0,
            ];
        }

        return $data;
    }

    /**
     * Get leads data (with location info).
     */
    private function getLeadsData($startDate, $endDate, $storeId, $province)
    {
        $query = CrmLead::active()
            ->whereNotNull('province')
            ->where('province', '!=', '');

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        if ($storeId) {
            $query->whereHas('targetStores', function ($q) use ($storeId) {
                $q->where('ecom_product_stores.id', $storeId);
            });
        }
        if ($province) {
            $query->where('province', 'like', '%' . $province . '%');
        }

        $leads = $query->get();

        $data = [];
        foreach ($leads as $lead) {
            $data[] = [
                'id' => $lead->id,
                'province' => $lead->province,
                'municipality' => $lead->municipality ?? '',
                'barangay' => $lead->barangay ?? '',
                'date' => $lead->created_at->format('Y-m-d'),
                'value' => 1,
            ];
        }

        return $data;
    }

    /**
     * Get affiliates data.
     */
    private function getAffiliatesData($startDate, $endDate, $storeId, $province)
    {
        $query = EcomAffiliate::active()
            ->whereNotNull('province')
            ->where('province', '!=', '');

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        if ($storeId) {
            $query->whereHas('affiliateStores', function ($q) use ($storeId) {
                $q->where('storeId', $storeId)->where('deleteStatus', 1);
            });
        }
        if ($province) {
            $query->where('province', 'like', '%' . $province . '%');
        }

        $affiliates = $query->get();

        $data = [];
        foreach ($affiliates as $affiliate) {
            $data[] = [
                'id' => $affiliate->id,
                'province' => $affiliate->province,
                'municipality' => $affiliate->municipality ?? '',
                'barangay' => $affiliate->barangay ?? '',
                'date' => $affiliate->created_at->format('Y-m-d'),
                'value' => 1,
            ];
        }

        return $data;
    }

    /**
     * Get clients data (from client shipping addresses).
     */
    private function getClientsData($startDate, $endDate, $province)
    {
        $query = EcomClientShippingAddress::where('deleteStatus', 1)
            ->whereNotNull('province')
            ->where('province', '!=', '');

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        if ($province) {
            $query->where('province', 'like', '%' . $province . '%');
        }

        $shippings = $query->get();

        $data = [];
        foreach ($shippings as $shipping) {
            $data[] = [
                'id' => $shipping->id,
                'province' => $shipping->province,
                'municipality' => $shipping->municipality ?? '',
                'barangay' => $shipping->zone ?? '',
                'date' => $shipping->created_at->format('Y-m-d'),
                'value' => 1,
            ];
        }

        return $data;
    }

    /**
     * Get refunds data (connected to order shipping).
     * Note: Shipping info is stored directly in ecom_orders table.
     */
    private function getRefundsData($startDate, $endDate, $storeId, $province)
    {
        $query = EcomRefundRequest::where('deleteStatus', 1)
            ->whereIn('refundStatus', ['approved', 'processed'])
            ->with(['order']);

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        if ($storeId) {
            $query->whereHas('order', function ($q) use ($storeId) {
                $q->where('storeId', $storeId);
            });
        }

        $refunds = $query->get();

        $data = [];
        foreach ($refunds as $refund) {
            if ($refund->order && $refund->order->shippingProvince) {
                if ($province && stripos($refund->order->shippingProvince, $province) === false) {
                    continue;
                }

                $data[] = [
                    'id' => $refund->id,
                    'province' => $refund->order->shippingProvince,
                    'municipality' => $refund->order->shippingMunicipality ?? '',
                    'barangay' => $refund->order->shippingZone ?? '',
                    'date' => $refund->created_at->format('Y-m-d'),
                    'value' => $refund->refundAmount ?? 0,
                ];
            }
        }

        return $data;
    }

    /**
     * Aggregate location data by province and municipality.
     */
    private function aggregateLocationData($data)
    {
        $byProvince = [];
        $byMunicipality = [];

        foreach ($data as $item) {
            $provinceName = ucwords(strtolower(trim($item['province'])));
            $municipalityName = ucwords(strtolower(trim($item['municipality'])));

            // Aggregate by province
            if (!isset($byProvince[$provinceName])) {
                $byProvince[$provinceName] = [
                    'name' => $provinceName,
                    'count' => 0,
                    'totalValue' => 0,
                ];
            }
            $byProvince[$provinceName]['count']++;
            $byProvince[$provinceName]['totalValue'] += $item['value'];

            // Aggregate by municipality
            if ($municipalityName) {
                $key = $provinceName . '|' . $municipalityName;
                if (!isset($byMunicipality[$key])) {
                    $byMunicipality[$key] = [
                        'province' => $provinceName,
                        'municipality' => $municipalityName,
                        'count' => 0,
                        'totalValue' => 0,
                    ];
                }
                $byMunicipality[$key]['count']++;
                $byMunicipality[$key]['totalValue'] += $item['value'];
            }
        }

        // Sort provinces by count descending
        uasort($byProvince, function ($a, $b) {
            return $b['count'] - $a['count'];
        });

        // Sort municipalities by count descending
        uasort($byMunicipality, function ($a, $b) {
            return $b['count'] - $a['count'];
        });

        return [
            'byProvince' => array_values($byProvince),
            'byMunicipality' => array_values($byMunicipality),
        ];
    }

    /**
     * Get all unique provinces from various sources.
     */
    private function getAllProvinces()
    {
        $provinces = collect();

        // From orders (shipping info stored directly in ecom_orders)
        $orderProvinces = DB::table('ecom_orders')
            ->where('deleteStatus', 1)
            ->whereNotNull('shippingProvince')
            ->where('shippingProvince', '!=', '')
            ->distinct()
            ->pluck('shippingProvince');
        $provinces = $provinces->merge($orderProvinces);

        // From leads
        $leadProvinces = DB::table('crm_leads')
            ->where('delete_status', 'active')
            ->whereNotNull('province')
            ->where('province', '!=', '')
            ->distinct()
            ->pluck('province');
        $provinces = $provinces->merge($leadProvinces);

        // From affiliates
        $affiliateProvinces = DB::table('ecom_affiliates')
            ->where('deleteStatus', 1)
            ->whereNotNull('province')
            ->where('province', '!=', '')
            ->distinct()
            ->pluck('province');
        $provinces = $provinces->merge($affiliateProvinces);

        // From client shipping addresses
        $clientProvinces = DB::table('ecom_client_shipping_addresses')
            ->where('deleteStatus', 1)
            ->whereNotNull('province')
            ->where('province', '!=', '')
            ->distinct()
            ->pluck('province');
        $provinces = $provinces->merge($clientProvinces);

        // Normalize and deduplicate
        return $provinces->map(function ($p) {
            return ucwords(strtolower(trim($p)));
        })->unique()->sort()->values()->toArray();
    }

    /**
     * Export heatmap data to CSV.
     */
    public function export(Request $request)
    {
        $dataSource = $request->input('source', 'orders');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $storeId = $request->input('store_id');
        $productId = $request->input('product_id');
        $province = $request->input('province');

        $data = [];

        switch ($dataSource) {
            case 'orders':
                $data = $this->getOrdersData($startDate, $endDate, $storeId, $productId, $province);
                break;
            case 'leads':
                $data = $this->getLeadsData($startDate, $endDate, $storeId, $province);
                break;
            case 'affiliates':
                $data = $this->getAffiliatesData($startDate, $endDate, $storeId, $province);
                break;
            case 'clients':
                $data = $this->getClientsData($startDate, $endDate, $province);
                break;
            case 'refunds':
                $data = $this->getRefundsData($startDate, $endDate, $storeId, $province);
                break;
        }

        $aggregated = $this->aggregateLocationData($data);

        $filename = 'heatmap_' . $dataSource . '_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($aggregated) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, ['Province', 'Municipality', 'Count', 'Total Value']);

            // Data rows - by municipality first
            foreach ($aggregated['byMunicipality'] as $row) {
                fputcsv($file, [
                    $row['province'],
                    $row['municipality'],
                    $row['count'],
                    number_format($row['totalValue'], 2),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
