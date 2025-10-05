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
use Illuminate\Support\Facades\DB;

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

        // Transform products to include productType
        $transformedProducts = $products->items();
        foreach ($transformedProducts as $product) {
            $product->productType = $product->productType ?? 'N/A';
        }

        return response()->json([
            'success' => true,
            'data' => $transformedProducts,
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

    /**
     * Check if phone number already exists.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    /**
     * Get access clients by store.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAccessClients(Request $request)
    {
        $productStore = $request->get('productStore');
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 20);
        $search = $request->get('search', '');

        if (!$productStore) {
            return response()->json([
                'success' => false,
                'message' => 'Product store is required'
            ], 400);
        }

        try {
            $query = DB::table('clients_access_login')
                ->where('productStore', $productStore)
                ->where('isActive', 1)
                ->where('deleteStatus', 1);

            // Add search functionality
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('clientFirstName', 'like', '%' . $search . '%')
                      ->orWhere('clientMiddleName', 'like', '%' . $search . '%')
                      ->orWhere('clientLastName', 'like', '%' . $search . '%')
                      ->orWhere('clientPhoneNumber', 'like', '%' . $search . '%')
                      ->orWhere('clientEmailAddress', 'like', '%' . $search . '%');
                });
            }

            // Get total count for pagination
            $total = $query->count();

            // Apply pagination
            $clients = $query->select('id', 'clientFirstName', 'clientMiddleName', 'clientLastName', 'clientPhoneNumber', 'clientEmailAddress')
                ->orderBy('clientFirstName', 'asc')
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();

            $lastPage = ceil($total / $perPage);

            return response()->json([
                'success' => true,
                'data' => $clients,
                'current_page' => (int)$page,
                'last_page' => $lastPage,
                'per_page' => (int)$perPage,
                'total' => $total
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching access clients: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Check if phone number already exists in clients_access_login table
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkAccessPhone(Request $request)
    {
        $phone = $request->get('phone');
        $store = $request->get('store');

        if (empty($phone) || empty($store)) {
            return response()->json([
                'success' => false,
                'exists' => false,
                'message' => 'Phone number and store are required'
            ]);
        }

        try {
            // Normalize the input phone number
            $normalizedPhone = $this->normalizePhoneNumber($phone);

            // Generate all possible formats to check against database
            $possibleFormats = $this->generatePhoneFormats($normalizedPhone);

            // Check if any of the possible formats exist in the database
            $exists = DB::table('clients_access_login')
                ->where('deleteStatus', 1)
                ->where('productStore', $store)
                ->whereIn('clientPhoneNumber', $possibleFormats)
                ->exists();

            return response()->json([
                'success' => true,
                'exists' => $exists,
                'message' => $exists ? 'Phone number already exists for this store' : 'Phone number is available'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'exists' => false,
                'message' => 'Error checking phone number: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Normalize phone number to standard format (09661123355)
     *
     * @param string $phoneNumber
     * @return string
     */
    private function normalizePhoneNumber($phoneNumber)
    {
        if (empty($phoneNumber)) return '';

        // Remove all non-digit characters except +
        $cleaned = preg_replace('/[^\d+]/', '', $phoneNumber);

        // Handle different formats and convert to 09 format
        if (str_starts_with($cleaned, '+63')) {
            // +639661123355 -> 09661123355
            return '0' . substr($cleaned, 3);
        } elseif (str_starts_with($cleaned, '63') && strlen($cleaned) === 12) {
            // 639661123355 -> 09661123355
            return '0' . substr($cleaned, 2);
        } elseif (str_starts_with($cleaned, '09') && strlen($cleaned) === 11) {
            // 09661123355 -> 09661123355 (already correct)
            return $cleaned;
        } elseif (str_starts_with($cleaned, '9') && strlen($cleaned) === 10) {
            // 9661123355 -> 09661123355
            return '0' . $cleaned;
        }

        return $cleaned;
    }

    /**
     * Generate all possible phone number formats for database comparison
     *
     * @param string $normalizedPhone (format: 09661123355)
     * @return array
     */
    private function generatePhoneFormats($normalizedPhone)
    {
        if (empty($normalizedPhone) || strlen($normalizedPhone) !== 11 || !str_starts_with($normalizedPhone, '09')) {
            return [];
        }

        $last10Digits = substr($normalizedPhone, 1); // Remove '0' prefix to get 9661123355

        return [
            $normalizedPhone,                    // 09661123355
            '63' . $last10Digits,                // 639661123355
            '+' . '63' . $last10Digits,          // +639661123355
            $last10Digits,                       // 9661123355
        ];
    }

    /**
     * Save access client to clients_access_login table
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveAccess(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phoneNumber' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'firstName' => 'required|string|max:255',
            'middleName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'password' => 'required|string|min:8',
            'store' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Generate all possible phone formats for storage
            $normalizedPhone = $this->normalizePhoneNumber($request->phoneNumber);
            $possibleFormats = $this->generatePhoneFormats($normalizedPhone);

            // Check if phone already exists (double check)
            $exists = DB::table('clients_access_login')
                ->where('deleteStatus', 1)
                ->where('productStore', $request->store)
                ->whereIn('clientPhoneNumber', $possibleFormats)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Phone number already exists for this store'
                ], 409);
            }

            // Create the access client record
            DB::table('clients_access_login')->insert([
                'clientPhoneNumber' => $normalizedPhone, // Store in normalized format
                'clientEmailAddress' => $request->email,
                'clientFirstName' => $request->firstName,
                'clientMiddleName' => $request->middleName,
                'clientLastName' => $request->lastName,
                'clientPassword' => bcrypt($request->password), // Hash the password
                'productStore' => $request->store,
                'deleteStatus' => 1, // Active status
                'isActive' => 1, // Active status
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Get the last inserted ID
            $accessClientId = DB::getPdo()->lastInsertId();

            return response()->json([
                'success' => true,
                'message' => 'Access client created successfully',
                'data' => [
                    'id' => $accessClientId,
                    'phoneNumber' => $normalizedPhone,
                    'email' => $request->email,
                    'fullName' => trim($request->firstName . ' ' . $request->middleName . ' ' . $request->lastName),
                    'store' => $request->store
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create access client: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if phone number already exists in clients_all_database table
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkClientPhone(Request $request)
    {
        $phoneNumber = $request->get('phone_number');

        if (!$phoneNumber) {
            return response()->json([
                'success' => false,
                'message' => 'Phone number is required'
            ], 400);
        }

        try {
            // Normalize the input phone number to check all possible formats
            $normalizedPhone = $this->normalizePhoneNumber($phoneNumber);
            $possibleFormats = $this->generatePhoneFormats($normalizedPhone);

            // Check if any of the possible formats exist in the database
            $exists = ClientAllDatabase::whereIn('clientPhoneNumber', $possibleFormats)->exists();

            return response()->json([
                'success' => true,
                'exists' => $exists,
                'message' => $exists ? 'Phone number already exists' : 'Phone number is available'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error checking phone number: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save a new client to the database
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveClient(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'clientFirstName' => 'required|string|max:255',
            'clientMiddleName' => 'required|string|max:255',
            'clientLastName' => 'required|string|max:255',
            'clientPhoneNumber' => 'required|string|unique:clients_all_database,clientPhoneNumber',
            'clientEmailAddress' => 'required|email|max:255|unique:clients_all_database,clientEmailAddress',
        ], [
            'clientPhoneNumber.unique' => 'This phone number already exists.',
            'clientEmailAddress.unique' => 'This email address already exists.',
        ]);

        // Custom phone number validation
        $validator->after(function ($validator) use ($request) {
            $phoneNumber = $request->clientPhoneNumber;
            if ($phoneNumber && !preg_match('/^(09\d{9}|\+63\d{9}|63\d{9})$/', $phoneNumber)) {
                $validator->errors()->add('clientPhoneNumber', 'Phone number must be in format: 09XXXXXXXXX, +63XXXXXXXXX, or 63XXXXXXXXX');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $client = ClientAllDatabase::create([
                'clientFirstName' => $request->clientFirstName,
                'clientMiddleName' => $request->clientMiddleName,
                'clientLastName' => $request->clientLastName,
                'clientPhoneNumber' => $request->clientPhoneNumber,
                'clientEmailAddress' => $request->clientEmailAddress,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Client created successfully',
                'client' => [
                    'id' => $client->id,
                    'fullName' => $client->fullName,
                    'clientFirstName' => $client->clientFirstName,
                    'clientMiddleName' => $client->clientMiddleName,
                    'clientLastName' => $client->clientLastName,
                    'clientPhoneNumber' => $client->clientPhoneNumber,
                    'clientEmailAddress' => $client->clientEmailAddress,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create client: ' . $e->getMessage()
            ], 500);
        }
    }
}

