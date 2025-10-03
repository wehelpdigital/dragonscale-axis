<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\EcomProductsShipping;
use App\Models\EcomProductsShippingOptions;
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
                return [
                    'id' => $shipping->id,
                    'shippingName' => $shipping->shippingName,
                    'shippingDescription' => $shipping->description_excerpt,
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
            'defaultPrice' => 'required|numeric|min:0',
            'defaultMaxQuantity' => 'required|integer|min:1'
        ]);

        try {
            $shipping = EcomProductsShipping::create([
                'shippingName' => $request->shippingName,
                'shippingDescription' => $request->shippingDescription,
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
            'defaultPrice' => 'required|numeric|min:0',
            'defaultMaxQuantity' => 'required|integer|min:1'
        ]);

        try {
            $shipping = EcomProductsShipping::findOrFail($id);
            $shipping->update($request->only(['shippingName', 'shippingDescription', 'defaultPrice', 'defaultMaxQuantity']));

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
}
