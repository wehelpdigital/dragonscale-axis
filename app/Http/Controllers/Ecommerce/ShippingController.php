<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\EcomProductsShipping;
use App\Models\EcomProductsShippingOptions;
use App\Models\EcomProductShippingRestriction;
use App\Models\EcomProductStore;
use App\Models\EcomProduct;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    /**
     * Display the shipping settings page.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('ecommerce.shipping');
    }

    /**
     * Get shipping data for DataTables with pagination, search, and filtering
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function getData(Request $request)
    {
        $query = EcomProductsShipping::active();

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where('shippingName', 'LIKE', "%{$searchTerm}%");
        }

        // Get total count before pagination
        $totalRecords = $query->count();

        // Pagination
        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);
        $offset = ($page - 1) * $perPage;

        $shippingMethods = $query->offset($offset)->limit($perPage)->get();

        return response()->json([
            'data' => $shippingMethods->map(function ($shipping) {
                // Get shipping types as array
                $types = $shipping->getShippingTypesArray();

                // Build badges HTML for multiple types
                $typeBadges = array_map(function($type) use ($shipping) {
                    return [
                        'type' => $type,
                        'badgeClass' => $shipping->getBadgeClassForType($type)
                    ];
                }, $types);

                return [
                    'id' => $shipping->id,
                    'shippingName' => $shipping->shippingName,
                    'shippingDescription' => $shipping->description_excerpt,
                    'shippingType' => $shipping->formatted_shipping_types, // Comma-separated for display
                    'shippingTypes' => $types, // Array of types
                    'shippingTypeBadges' => $typeBadges, // Array of type/badge pairs for rendering
                    'shippingTypeBadgeClass' => $shipping->shipping_type_badge_class,
                    'defaultPrice' => $shipping->formatted_default_price,
                    'defaultMaxQuantity' => $shipping->defaultMaxQuantity,
                    'actions' => $shipping->id
                ];
            }),
            'total' => $totalRecords,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($totalRecords / $perPage),
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $totalRecords)
        ]);
    }

    /**
     * Store a new shipping method
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'shippingName' => 'required|string|max:255',
            'shippingDescription' => 'nullable|string',
            'shippingType' => 'required|array|min:1',
            'shippingType.*' => 'in:Regular,Cash on Delivery,Cash on Pickup',
            'defaultPrice' => 'required|numeric|min:0',
            'defaultMaxQuantity' => 'required|integer|min:1'
        ]);

        try {
            $shipping = EcomProductsShipping::create([
                'shippingName' => $request->shippingName,
                'shippingDescription' => $request->shippingDescription,
                'shippingType' => $request->shippingType, // Already an array
                'defaultPrice' => $request->defaultPrice,
                'defaultMaxQuantity' => $request->defaultMaxQuantity,
                'isActive' => 1,
                'deleteStatus' => 1
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Shipping method created successfully.',
                'data' => $shipping
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating shipping method: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a shipping method
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $shipping = EcomProductsShipping::findOrFail($id);
            $shipping->update(['deleteStatus' => 0]);

            return response()->json([
                'success' => true,
                'message' => 'Shipping method deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting shipping method: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Edit a shipping method
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $shipping = EcomProductsShipping::findOrFail($id);
        return response()->json($shipping);
    }

    /**
     * Update a shipping method
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'shippingName' => 'required|string|max:255',
            'shippingDescription' => 'nullable|string',
            'shippingType' => 'required|array|min:1',
            'shippingType.*' => 'in:Regular,Cash on Delivery,Cash on Pickup',
            'defaultPrice' => 'required|numeric|min:0',
            'defaultMaxQuantity' => 'required|integer|min:1'
        ]);

        try {
            $shipping = EcomProductsShipping::findOrFail($id);
            $shipping->update([
                'shippingName' => $request->shippingName,
                'shippingDescription' => $request->shippingDescription,
                'shippingType' => $request->shippingType, // Already an array
                'defaultPrice' => $request->defaultPrice,
                'defaultMaxQuantity' => $request->defaultMaxQuantity
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Shipping method updated successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating shipping method: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the shipping settings page for a specific shipping method
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function settings(Request $request)
    {
        $id = $request->get('id');

        if (!$id) {
            return redirect()->route('ecom-shipping')->with('error', 'Shipping method ID is required.');
        }

        try {
            $shipping = EcomProductsShipping::findOrFail($id);

            // Get provinces that are already used for this shipping method
            $usedProvinces = EcomProductsShippingOptions::active()
                ->byShippingId($id)
                ->pluck('provinceTarget')
                ->toArray();

            // Get all Philippine provinces
            $allProvinces = [
                'Abra', 'Agusan del Norte', 'Agusan del Sur', 'Aklan', 'Albay', 'Antique', 'Apayao', 'Aurora',
                'Basilan', 'Bataan', 'Batanes', 'Batangas', 'Benguet', 'Biliran', 'Bohol', 'Bukidnon',
                'Bulacan', 'Cagayan', 'Camarines Norte', 'Camarines Sur', 'Camiguin', 'Capiz', 'Catanduanes',
                'Cavite', 'Cebu', 'Cotabato', 'Davao del Norte', 'Davao del Sur', 'Davao Occidental', 'Davao Oriental',
                'Dinagat Islands', 'Eastern Samar', 'Guimaras', 'Ifugao', 'Ilocos Norte', 'Ilocos Sur', 'Iloilo',
                'Isabela', 'Kalinga', 'Laguna', 'Lanao del Norte', 'Lanao del Sur', 'La Union', 'Leyte',
                'Maguindanao', 'Marinduque', 'Masbate', 'Misamis Occidental', 'Misamis Oriental', 'Mountain Province',
                'Negros Occidental', 'Negros Oriental', 'Northern Samar', 'Nueva Ecija', 'Nueva Vizcaya',
                'Occidental Mindoro', 'Oriental Mindoro', 'Palawan', 'Pampanga', 'Pangasinan', 'Quezon', 'Quirino',
                'Rizal', 'Romblon', 'Samar', 'Sarangani', 'Siquijor', 'Sorsogon', 'South Cotabato', 'Southern Leyte',
                'Sultan Kudarat', 'Sulu', 'Surigao del Norte', 'Surigao del Sur', 'Tarlac', 'Tawi-Tawi', 'Zambales',
                'Zamboanga del Norte', 'Zamboanga del Sur', 'Zamboanga Sibugay'
            ];

            // Filter out provinces that are already used
            $availableProvinces = array_diff($allProvinces, $usedProvinces);

            return view('ecommerce.shipping-settings', compact('shipping', 'availableProvinces'));
        } catch (\Exception $e) {
            return redirect()->route('ecom-shipping')->with('error', 'Shipping method not found.');
        }
    }

    /**
     * Get shipping options data for DataTables with pagination, search, and filtering
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function getShippingOptionsData(Request $request)
    {
        $shippingId = $request->get('shipping_id');

        if (!$shippingId) {
            return response()->json([
                'data' => [],
                'total' => 0,
                'per_page' => 10,
                'current_page' => 1,
                'last_page' => 1,
                'from' => 0,
                'to' => 0
            ]);
        }

        $query = EcomProductsShippingOptions::active()->byShippingId($shippingId);

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('provinceTarget', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('maxQuantity', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('shippingPrice', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Get total count before pagination
        $totalRecords = $query->count();

        // Pagination
        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);
        $offset = ($page - 1) * $perPage;

        $shippingOptions = $query->offset($offset)->limit($perPage)->get();

        return response()->json([
            'data' => $shippingOptions->map(function ($option) {
                return [
                    'id' => $option->id,
                    'provinceTarget' => $option->provinceTarget,
                    'maxQuantity' => $option->maxQuantity,
                    'shippingPrice' => $option->formatted_shipping_price,
                    'status' => $option->status_text,
                    'statusBadgeClass' => $option->status_badge_class,
                    'isActive' => $option->isActive
                ];
            }),
            'total' => $totalRecords,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($totalRecords / $perPage),
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $totalRecords)
        ]);
    }

    /**
     * Store a new shipping option
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function storeShippingOption(Request $request)
    {
        $request->validate([
            'shippingId' => 'required|integer',
            'provinceTarget' => 'required|string|max:255',
            'maxQuantity' => 'required|integer|min:1',
            'shippingPrice' => 'required|numeric|min:0'
        ]);

        try {
            $shippingOption = EcomProductsShippingOptions::create([
                'shippingId' => $request->shippingId,
                'provinceTarget' => $request->provinceTarget,
                'maxQuantity' => $request->maxQuantity,
                'shippingPrice' => $request->shippingPrice,
                'isActive' => 1,
                'deleteStatus' => 1
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Target province saved successfully.',
                'data' => $shippingOption
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error saving target province: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available provinces for a shipping method
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function getAvailableProvinces(Request $request)
    {
        $shippingId = $request->get('shipping_id');

        if (!$shippingId) {
            return response()->json([
                'provinces' => []
            ]);
        }

        // Get provinces that are already used for this shipping method
        $usedProvinces = EcomProductsShippingOptions::active()
            ->byShippingId($shippingId)
            ->pluck('provinceTarget')
            ->toArray();

        // Get all Philippine provinces
        $allProvinces = [
            'Abra', 'Agusan del Norte', 'Agusan del Sur', 'Aklan', 'Albay', 'Antique', 'Apayao', 'Aurora',
            'Basilan', 'Bataan', 'Batanes', 'Batangas', 'Benguet', 'Biliran', 'Bohol', 'Bukidnon',
            'Bulacan', 'Cagayan', 'Camarines Norte', 'Camarines Sur', 'Camiguin', 'Capiz', 'Catanduanes',
            'Cavite', 'Cebu', 'Cotabato', 'Davao del Norte', 'Davao del Sur', 'Davao Occidental', 'Davao Oriental',
            'Dinagat Islands', 'Eastern Samar', 'Guimaras', 'Ifugao', 'Ilocos Norte', 'Ilocos Sur', 'Iloilo',
            'Isabela', 'Kalinga', 'Laguna', 'Lanao del Norte', 'Lanao del Sur', 'La Union', 'Leyte',
            'Maguindanao', 'Marinduque', 'Masbate', 'Misamis Occidental', 'Misamis Oriental', 'Mountain Province',
            'Negros Occidental', 'Negros Oriental', 'Northern Samar', 'Nueva Ecija', 'Nueva Vizcaya',
            'Occidental Mindoro', 'Oriental Mindoro', 'Palawan', 'Pampanga', 'Pangasinan', 'Quezon', 'Quirino',
            'Rizal', 'Romblon', 'Samar', 'Sarangani', 'Siquijor', 'Sorsogon', 'South Cotabato', 'Southern Leyte',
            'Sultan Kudarat', 'Sulu', 'Surigao del Norte', 'Surigao del Sur', 'Tarlac', 'Tawi-Tawi', 'Zambales',
            'Zamboanga del Norte', 'Zamboanga del Sur', 'Zamboanga Sibugay'
        ];

        // Filter out provinces that are already used
        $availableProvinces = array_diff($allProvinces, $usedProvinces);

        return response()->json([
            'provinces' => array_values($availableProvinces)
        ]);
    }

    /**
     * Get shipping option for editing
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function editShippingOption($id)
    {
        try {
            $shippingOption = EcomProductsShippingOptions::active()->findOrFail($id);

            return response()->json([
                'id' => $shippingOption->id,
                'provinceTarget' => $shippingOption->provinceTarget,
                'maxQuantity' => $shippingOption->maxQuantity,
                'shippingPrice' => $shippingOption->shippingPrice,
                'isActive' => $shippingOption->isActive
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Shipping option not found.'
            ], 404);
        }
    }

    /**
     * Update a shipping option
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function updateShippingOption(Request $request, $id)
    {
        $request->validate([
            'provinceTarget' => 'required|string|max:255',
            'maxQuantity' => 'required|integer|min:1',
            'shippingPrice' => 'required|numeric|min:0'
        ]);

        try {
            $shippingOption = EcomProductsShippingOptions::active()->findOrFail($id);

            $shippingOption->update([
                'provinceTarget' => $request->provinceTarget,
                'maxQuantity' => $request->maxQuantity,
                'shippingPrice' => $request->shippingPrice
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Target province updated successfully.',
                'data' => $shippingOption
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating target province: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update shipping option status
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function updateShippingOptionStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:0,1'
        ]);

        try {
            $shippingOption = EcomProductsShippingOptions::active()->findOrFail($id);

            $shippingOption->update([
                'isActive' => $request->status
            ]);

            $statusText = $request->status == 1 ? 'Active' : 'Inactive';

            return response()->json([
                'success' => true,
                'message' => "Status updated to {$statusText} successfully.",
                'data' => $shippingOption
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete shipping option (soft delete)
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function deleteShippingOption($id)
    {
        try {
            $shippingOption = EcomProductsShippingOptions::active()->findOrFail($id);

            $shippingOption->update([
                'deleteStatus' => 0
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Target province deleted successfully.',
                'data' => $shippingOption
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting target province: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the restrictions page for a shipping method.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function restrictions(Request $request)
    {
        $shippingId = $request->get('id');

        $shipping = EcomProductsShipping::active()->find($shippingId);

        if (!$shipping) {
            return redirect()->route('ecom-shipping')
                ->with('error', 'Shipping method not found.');
        }

        // Get existing restrictions with related data
        $existingRestrictions = EcomProductShippingRestriction::active()
            ->where('shippingId', $shippingId)
            ->with(['store', 'product'])
            ->get();

        // Get all active stores for dropdown
        $stores = EcomProductStore::where('deleteStatus', 1)
            ->where('isActive', 1)
            ->orderBy('storeName')
            ->get();

        return view('ecommerce.shipping.restrictions', compact('shipping', 'existingRestrictions', 'stores'));
    }

    /**
     * Get current restrictions for a shipping method (AJAX).
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRestrictions($id)
    {
        try {
            $shipping = EcomProductsShipping::active()->findOrFail($id);

            $restrictions = EcomProductShippingRestriction::active()
                ->where('shippingId', $id)
                ->with(['store', 'product', 'variant'])
                ->get();

            $formattedRestrictions = $restrictions->map(function($restriction) {
                $data = [
                    'id' => $restriction->id,
                    'type' => $restriction->storeId ? 'store' : 'variant'
                ];

                if ($restriction->store) {
                    $data['storeId'] = $restriction->storeId;
                    $data['storeName'] = $restriction->store->storeName;
                }

                if ($restriction->variant) {
                    $data['variantId'] = $restriction->variantId;
                    $data['variantName'] = $restriction->variant->ecomVariantName;
                    $data['productId'] = $restriction->productId;
                    if ($restriction->product) {
                        $data['productName'] = $restriction->product->productName;
                        $data['productStore'] = $restriction->product->productStore ?? '';
                    }
                }

                return $data;
            });

            return response()->json([
                'success' => true,
                'restrictionType' => $shipping->restrictionType ?? 'all',
                'restrictions' => $formattedRestrictions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading restrictions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save restrictions for a shipping method.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveRestrictions(Request $request, $id)
    {
        try {
            $shipping = EcomProductsShipping::active()->findOrFail($id);

            $restrictionType = $request->get('restrictionType', 'all');
            $storeIds = $request->get('storeIds', []);
            $variantIds = $request->get('variantIds', []);

            // Update the shipping's restriction type
            $shipping->update(['restrictionType' => $restrictionType]);

            // Soft delete all existing restrictions
            EcomProductShippingRestriction::where('shippingId', $id)
                ->update(['deleteStatus' => 0]);

            // Create new restrictions based on type
            if ($restrictionType === 'stores' && !empty($storeIds)) {
                foreach ($storeIds as $storeId) {
                    EcomProductShippingRestriction::create([
                        'shippingId' => $id,
                        'storeId' => $storeId,
                        'productId' => null,
                        'variantId' => null,
                        'deleteStatus' => 1
                    ]);
                }
            } elseif ($restrictionType === 'products' && !empty($variantIds)) {
                // Now using variantIds instead of productIds
                foreach ($variantIds as $variantId) {
                    // Get the product ID for this variant
                    $variant = \App\Models\EcomProductVariant::find($variantId);
                    $productId = $variant ? $variant->ecomProductsId : null;

                    EcomProductShippingRestriction::create([
                        'shippingId' => $id,
                        'storeId' => null,
                        'productId' => $productId,
                        'variantId' => $variantId,
                        'deleteStatus' => 1
                    ]);
                }
            }

            // Also update the ecom_products_variants_shipping table for order creation compatibility
            if ($restrictionType === 'all') {
                // For "all products", we don't add individual variant assignments
                // The order creation should check restrictions instead
            } elseif ($restrictionType === 'stores') {
                // For store restrictions, we need to add all variants from those stores
                $this->syncVariantShippingForStores($id, $storeIds);
            } elseif ($restrictionType === 'products' && !empty($variantIds)) {
                // For product/variant restrictions, sync the specific variants
                $this->syncVariantShippingForVariants($id, $variantIds);
            }

            return response()->json([
                'success' => true,
                'message' => 'Restrictions have been saved successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error saving restrictions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync variant shipping assignments for store-based restrictions.
     */
    private function syncVariantShippingForStores($shippingId, $storeIds)
    {
        // Remove existing assignments for this shipping method
        \DB::table('ecom_products_variants_shipping')
            ->where('ecomShippingId', $shippingId)
            ->delete();

        if (empty($storeIds)) {
            return;
        }

        // Get all active variants for products in these stores
        $variants = \DB::table('ecom_products_variants as v')
            ->join('ecom_products as p', 'v.ecomProductsId', '=', 'p.id')
            ->join('ecom_stores as s', 'p.productStore', '=', 's.storeName')
            ->whereIn('s.id', $storeIds)
            ->where('p.deleteStatus', 1)
            ->where('v.deleteStatus', 1)
            ->where('p.productType', 'Ship')
            ->select('v.id as variantId')
            ->get();

        // Create assignments
        foreach ($variants as $variant) {
            \DB::table('ecom_products_variants_shipping')->insert([
                'ecomVariantId' => $variant->variantId,
                'ecomShippingId' => $shippingId,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }

    /**
     * Sync variant shipping assignments for variant-based restrictions.
     */
    private function syncVariantShippingForVariants($shippingId, $variantIds)
    {
        // Remove existing assignments for this shipping method
        \DB::table('ecom_products_variants_shipping')
            ->where('ecomShippingId', $shippingId)
            ->delete();

        if (empty($variantIds)) {
            return;
        }

        // Create assignments for each variant
        foreach ($variantIds as $variantId) {
            \DB::table('ecom_products_variants_shipping')->insert([
                'ecomVariantId' => $variantId,
                'ecomShippingId' => $shippingId,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }

    /**
     * Search for stores (AJAX endpoint for dynamic search with pagination).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchStores(Request $request)
    {
        try {
            $search = $request->get('search', '');
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 20);

            // Only show active and non-deleted stores
            $query = EcomProductStore::where('deleteStatus', 1)
                ->where('isActive', 1);

            // Search by store name
            if ($search) {
                $query->where('storeName', 'LIKE', '%' . $search . '%');
            }

            $total = $query->count();
            $stores = $query->orderBy('storeName')
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();

            return response()->json([
                'success' => true,
                'stores' => $stores->map(function($store) {
                    return [
                        'id' => $store->id,
                        'name' => $store->storeName ?? 'Unknown',
                        'isActive' => $store->isActive
                    ];
                }),
                'pagination' => [
                    'current_page' => (int) $page,
                    'per_page' => (int) $perPage,
                    'total' => $total,
                    'has_more' => ($page * $perPage) < $total
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error searching stores: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search for products (AJAX endpoint for dynamic search with pagination).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchProducts(Request $request)
    {
        try {
            $search = $request->get('search', '');
            $storeId = $request->get('store_id');
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 20);

            // Only show active and non-deleted products with productType = 'ship'
            $query = EcomProduct::where('deleteStatus', 1)
                ->where('isActive', 1)
                ->where('productType', 'ship');

            // Filter by store if provided
            if ($storeId) {
                $store = EcomProductStore::find($storeId);
                if ($store) {
                    $query->where('productStore', $store->storeName);
                }
            }

            // Search by product name
            if ($search) {
                $query->where('productName', 'LIKE', '%' . $search . '%');
            }

            $total = $query->count();
            $products = $query->orderBy('productName')
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();

            return response()->json([
                'success' => true,
                'products' => $products->map(function($product) {
                    // Get active variants for price range
                    $variants = $product->variants()
                        ->where('deleteStatus', 1)
                        ->where('isActive', 1)
                        ->get();

                    $minPrice = $variants->min('ecomVariantPrice');
                    $maxPrice = $variants->max('ecomVariantPrice');
                    $variantCount = $variants->count();

                    // Format price range
                    if ($variantCount === 0) {
                        $priceRange = 'No variants';
                    } elseif ($minPrice == $maxPrice) {
                        $priceRange = '₱' . number_format($minPrice, 2);
                    } else {
                        $priceRange = '₱' . number_format($minPrice, 2) . ' - ₱' . number_format($maxPrice, 2);
                    }

                    return [
                        'id' => $product->id,
                        'name' => $product->productName ?? 'Unknown',
                        'store' => $product->productStore ?? '',
                        'price' => $priceRange,
                        'variantCount' => $variantCount
                    ];
                }),
                'pagination' => [
                    'current_page' => (int) $page,
                    'per_page' => (int) $perPage,
                    'total' => $total,
                    'has_more' => ($page * $perPage) < $total
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error searching products: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get variants for a specific product.
     *
     * @param int $productId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProductVariants($productId)
    {
        try {
            $product = EcomProduct::find($productId);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            // Get active and non-deleted variants with their first image
            $variants = $product->variants()
                ->where('deleteStatus', 1)
                ->where('isActive', 1)
                ->with('firstImage')
                ->orderBy('ecomVariantPrice')
                ->get();

            return response()->json([
                'success' => true,
                'variants' => $variants->map(function($variant) {
                    $image = $variant->firstImage;
                    return [
                        'id' => $variant->id,
                        'name' => $variant->ecomVariantName ?? 'Unknown',
                        'price' => '₱' . number_format($variant->ecomVariantPrice ?? 0, 2),
                        'stock' => $variant->stocksAvailable ?? 0,
                        'image' => $image ? asset($image->imageLink) : null
                    ];
                })
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading variants: ' . $e->getMessage()
            ], 500);
        }
    }
}
