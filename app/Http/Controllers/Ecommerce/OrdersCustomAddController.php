<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\EcomOrder;
use App\Models\EcomProduct;
use App\Models\EcomProductVariant;
use App\Models\EcomProductVariantImage;
use App\Models\EcomProductVariantVideo;
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
                ];
            case 2:
                return [
                    'orderNumber' => 'required|string|max:255|unique:ecom_orders,orderNumber',
                    'customerFullName' => 'required|string|max:255',
                    'paymentAmount' => 'required|numeric|min:0',
                    'paymentDiscount' => 'nullable|numeric|min:0',
                    'shippingAmount' => 'nullable|numeric|min:0',
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
        $perPage = $request->get('per_page', 20);

        $query = EcomProduct::active()
            ->where('isActive', 1)
            ->where('deleteStatus', 1);

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

        $query = EcomProductVariant::active()
            ->where('ecomProductsId', $productId)
            ->where('isActive', 1)
            ->where('deleteStatus', 1);

        if ($search) {
            $query->where('ecomVariantName', 'LIKE', "%{$search}%");
        }

        $variants = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => $variants->items(),
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

