<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\EcomOrder;
use App\Models\EcomProduct;
use App\Models\EcomProductVariant;
use App\Models\EcomProductVariantImage;
use App\Models\EcomProductVariantVideo;
use App\Models\EcomProductStore;
use App\Models\ClientAllDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrdersCustomAddController extends Controller
{
    /**
     * Display the custom add order page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('ecommerce.orders.custom-add');
    }

    /**
     * Store the order data from the wizard.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'orderNumber' => 'required|string|max:255|unique:ecom_orders,orderNumber',
            'customerFullName' => 'required|string|max:255',
            'paymentAmount' => 'required|numeric|min:0',
            'paymentDiscount' => 'nullable|numeric|min:0',
            'shippingAmount' => 'nullable|numeric|min:0',
            'totalToPay' => 'required|numeric|min:0',
            'paymentStatus' => 'required|in:pending,paid,refunded,partial',
            'shippingStatus' => 'required|in:pending,shipped,delivered,returned',
            'handledBy' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order = EcomOrder::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'order' => $order
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate a specific step of the wizard.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateStep(Request $request)
    {
        $step = $request->input('step');
        $data = $request->except(['step', '_token']);

        $rules = $this->getStepValidationRules($step);

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Additional validation for step 1 - check quantity limits
        if ($step === 1 && isset($data['selectedProducts'])) {
            $quantityValidationResult = $this->validateProductQuantities($data['selectedProducts']);
            if (!$quantityValidationResult['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quantity validation failed',
                    'errors' => $quantityValidationResult['errors']
                ], 422);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Step validation passed'
        ]);
    }

    /**
     * Get validation rules for a specific step.
     *
     * @param int $step
     * @return array
     */
    private function getStepValidationRules($step)
    {
        switch ($step) {
            case 1:
                return [
                    'selectedProducts' => 'required|array|min:1',
                    'selectedProducts.*.variantId' => 'required|integer|exists:ecom_products_variants,id',
                    'selectedProducts.*.quantity' => 'required|integer|min:1',
                ];
            case 2:
                return [
                    'selectedClient' => 'required|string',
                ];
            case 3:
                return [
                    'paymentStatus' => 'required|in:pending,paid,refunded,partial',
                    'shippingStatus' => 'required|in:pending,shipped,delivered,returned',
                    'handledBy' => 'nullable|string|max:255',
                ];
            default:
                return [];
        }
    }

    /**
     * Validate product quantities against maxOrderPerTransaction limits.
     *
     * @param array $selectedProducts
     * @return array
     */
    private function validateProductQuantities($selectedProducts)
    {
        $errors = [];

        foreach ($selectedProducts as $index => $product) {
            if (!isset($product['variantId']) || !isset($product['quantity'])) {
                continue;
            }

            // Get variant details
            $variant = EcomProductVariant::where('id', $product['variantId'])
                ->where('isActive', 1)
                ->where('deleteStatus', 1)
                ->first();

            if (!$variant) {
                $errors["selectedProducts.{$index}.variantId"] = ['Variant not found or inactive'];
                continue;
            }

            $maxOrderPerTransaction = $variant->maxOrderPerTransaction ?? 1;
            $requestedQuantity = (int) $product['quantity'];

            if ($requestedQuantity > $maxOrderPerTransaction) {
                $errors["selectedProducts.{$index}.quantity"] = [
                    "Quantity cannot exceed the maximum order limit of {$maxOrderPerTransaction} for this variant."
                ];
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Get products for the wizard.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProducts(Request $request)
    {
        $storeSearch = $request->get('store_search', '');
        $productSearch = $request->get('product_search', '');
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 15);

        $query = EcomProduct::active()
            ->where('isActive', 1)
            ->where('deleteStatus', 1)
            ->whereHas('variants', function ($variantQuery) {
                $variantQuery->where('isActive', 1)
                           ->where('deleteStatus', 1);
            });

        if ($storeSearch) {
            $query->where('productStore', 'LIKE', "%{$storeSearch}%");
        }

        if ($productSearch) {
            $query->where('productName', 'LIKE', "%{$productSearch}%");
        }

        $products = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'from' => $products->firstItem(),
                'to' => $products->lastItem(),
            ]
        ]);
    }

    /**
     * Get variants for a specific product.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProductVariants(Request $request)
    {
        $productId = $request->get('product_id');
        $search = $request->get('search', '');
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 10);

        $query = EcomProductVariant::with('product')
            ->active()
            ->where('ecomProductsId', $productId)
            ->where('isActive', 1)
            ->where('deleteStatus', 1);

        if ($search) {
            $query->where('ecomVariantName', 'LIKE', "%{$search}%");
        }

        $variants = $query->paginate($perPage, ['*'], 'page', $page);

        // Add product name to each variant
        $variantsData = $variants->items();
        foreach ($variantsData as $variant) {
            $variant->productName = $variant->product ? $variant->product->productName : 'Unknown Product';
        }

        return response()->json([
            'success' => true,
            'data' => $variantsData,
            'pagination' => [
                'current_page' => $variants->currentPage(),
                'last_page' => $variants->lastPage(),
                'per_page' => $variants->perPage(),
                'total' => $variants->total(),
                'from' => $variants->firstItem(),
                'to' => $variants->lastItem(),
            ]
        ]);
    }

    /**
     * Get stores for dropdown.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStores()
    {
        try {
            $stores = EcomProductStore::active()
                ->orderBy('storeName', 'asc')
                ->get(['id', 'storeName']);

            return response()->json([
                'success' => true,
                'data' => $stores
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching stores: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get clients for search.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getClients(Request $request)
    {
        $search = $request->get('search', '');
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 10);

        try {
            $query = ClientAllDatabase::active();

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('clientFirstName', 'LIKE', "%{$search}%")
                      ->orWhere('clientMiddleName', 'LIKE', "%{$search}%")
                      ->orWhere('clientLastName', 'LIKE', "%{$search}%")
                      ->orWhere('clientPhoneNumber', 'LIKE', "%{$search}%")
                      ->orWhere('clientEmailAddress', 'LIKE', "%{$search}%");
                });
            }

            $clients = $query->orderBy('clientFirstName', 'asc')
                ->orderBy('clientLastName', 'asc')
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => $clients->items(),
                'pagination' => [
                    'current_page' => $clients->currentPage(),
                    'last_page' => $clients->lastPage(),
                    'per_page' => $clients->perPage(),
                    'total' => $clients->total(),
                    'from' => $clients->firstItem(),
                    'to' => $clients->lastItem(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching clients: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get variant details with product info and images.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getVariantDetails(Request $request)
    {
        $variantId = $request->get('variant_id');

        try {
            // Get variant with product relationship
            $variant = EcomProductVariant::with('product')
                ->where('id', $variantId)
                ->where('isActive', 1)
                ->where('deleteStatus', 1)
                ->first();

            if (!$variant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Variant not found'
                ], 404);
            }

            // Get variant images
            $images = EcomProductVariantImage::active()
                ->where('ecomVariantsId', $variantId)
                ->where('deleteStatus', 1)
                ->orderBy('imageOrder', 'asc')
                ->get();

            // Get variant videos
            $videos = EcomProductVariantVideo::active()
                ->where('ecomVariantsId', $variantId)
                ->where('deleteStatus', 1)
                ->orderBy('videoOrder', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'variant' => $variant,
                    'product' => $variant->product,
                    'images' => $images,
                    'videos' => $videos
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching variant details: ' . $e->getMessage()
            ], 500);
        }
    }
}

