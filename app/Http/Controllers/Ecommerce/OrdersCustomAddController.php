<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\EcomOrder;
use App\Models\EcomProduct;
use App\Models\EcomProductVariant;
use App\Models\EcomProductVariantImage;
use App\Models\EcomProductVariantVideo;
use App\Models\EcomProductStore;
use App\Models\EcomProductDiscount;
use App\Models\EcomProductDiscountRestriction;
use App\Models\ClientAllDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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

        // Get active store names (only stores with isActive = 1)
        $activeStoreNames = EcomProductStore::active()->enabled()->pluck('storeName')->toArray();

        $query = EcomProduct::active()
            ->where('isActive', 1)
            ->where('deleteStatus', 1)
            ->whereIn('productStore', $activeStoreNames)
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

        // Add product information to each variant
        $variantsData = $variants->items();
        foreach ($variantsData as $variant) {
            if ($variant->product) {
                $variant->productName = $variant->product->productName ?? 'Unknown Product';
                $variant->productStore = $variant->product->productStore ?? 'Unknown Store';
                $variant->productType = $variant->product->productType ?? 'Unknown';
                $variant->shipCoverage = $variant->product->shipCoverage ?? 'n/a';
            } else {
                $variant->productName = 'Unknown Product';
                $variant->productStore = 'Unknown Store';
                $variant->productType = 'Unknown';
                $variant->shipCoverage = 'n/a';
            }
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
                ->enabled()
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

    /**
     * Get Philippine provinces
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPhilippineProvinces()
    {
        try {
            $provinces = [
                'Abra', 'Agusan del Norte', 'Agusan del Sur', 'Aklan', 'Albay', 'Antique', 'Apayao',
                'Aurora', 'Basilan', 'Bataan', 'Batanes', 'Batangas', 'Benguet', 'Biliran', 'Bohol',
                'Bukidnon', 'Bulacan', 'Cagayan', 'Camarines Norte', 'Camarines Sur', 'Camiguin',
                'Capiz', 'Catanduanes', 'Cavite', 'Cebu', 'Cotabato', 'Davao de Oro', 'Davao del Norte',
                'Davao del Sur', 'Davao Occidental', 'Davao Oriental', 'Dinagat Islands', 'Eastern Samar',
                'Guimaras', 'Ifugao', 'Ilocos Norte', 'Ilocos Sur', 'Iloilo', 'Isabela', 'Kalinga',
                'Laguna', 'Lanao del Norte', 'Lanao del Sur', 'La Union', 'Leyte', 'Maguindanao',
                'Marinduque', 'Masbate', 'Metro Manila', 'Misamis Occidental', 'Misamis Oriental', 'Mountain Province',
                'Negros Occidental', 'Negros Oriental', 'Northern Samar', 'Nueva Ecija', 'Nueva Vizcaya',
                'Occidental Mindoro', 'Oriental Mindoro', 'Palawan', 'Pampanga', 'Pangasinan',
                'Quezon', 'Quirino', 'Rizal', 'Romblon', 'Samar', 'Sarangani', 'Siquijor',
                'Sorsogon', 'South Cotabato', 'Southern Leyte', 'Sultan Kudarat', 'Sulu',
                'Surigao del Norte', 'Surigao del Sur', 'Tarlac', 'Tawi-Tawi', 'Zambales',
                'Zamboanga del Norte', 'Zamboanga del Sur', 'Zamboanga Sibugay'
            ];

            sort($provinces);

            return response()->json([
                'success' => true,
                'data' => $provinces
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching provinces: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get municipalities/cities for a specific province
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPhilippineMunicipalities(Request $request)
    {
        $province = $request->get('province');

        if (!$province) {
            return response()->json([
                'success' => false,
                'message' => 'Province is required'
            ], 400);
        }

        try {
            // Sample municipalities data - in a real application, this would come from a database
            $municipalities = $this->getMunicipalitiesByProvince($province);

            if (empty($municipalities)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No municipalities found for the selected province'
                ], 404);
            }

            sort($municipalities);

            return response()->json([
                'success' => true,
                'data' => $municipalities
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching municipalities: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get municipalities for a specific province
     * This is a simplified implementation - in production, use a proper Philippine location database
     *
     * @param string $province
     * @return array
     */
    private function getMunicipalitiesByProvince($province)
    {
        // Complete data for all Philippine provinces with their municipalities and cities
        $municipalitiesData = [
            'Metro Manila' => [
                'Caloocan', 'Las Piñas', 'Makati', 'Malabon', 'Mandaluyong', 'Manila', 'Marikina',
                'Muntinlupa', 'Navotas', 'Parañaque', 'Pasay', 'Pasig', 'Pateros', 'Quezon City',
                'San Juan', 'Taguig', 'Valenzuela'
            ],
            'Cebu' => [
                'Bogo', 'Carcar', 'Cebu City', 'Danao', 'Lapu-Lapu City', 'Mandaue', 'Naga',
                'Talisay', 'Toledo', 'Argao', 'Asturias', 'Badian', 'Balamban', 'Bantayan',
                'Barili', 'Boljoon', 'Borbon', 'Carmen', 'Catmon', 'Compostela', 'Consolacion',
                'Cordova', 'Daanbantayan', 'Dalaguete', 'Dumanjug', 'Ginatilan', 'Liloan',
                'Madridejos', 'Malabuyoc', 'Medellin', 'Minglanilla', 'Moalboal', 'Oslob',
                'Pilar', 'Pinamungajan', 'Poro', 'Ronda', 'Samboan', 'San Fernando', 'San Francisco',
                'San Remigio', 'Santa Fe', 'Santander', 'Sibonga', 'Sogod', 'Tabogon', 'Tabuelan',
                'Tuburan', 'Tudela'
            ],
            'Laguna' => [
                'Biñan', 'Cabuyao', 'Calamba', 'San Pablo', 'Santa Rosa', 'Alaminos', 'Bay',
                'Calauan', 'Cavinti', 'Famy', 'Kalayaan', 'Liliw', 'Los Baños', 'Luisiana',
                'Lumban', 'Mabitac', 'Magdalena', 'Majayjay', 'Nagcarlan', 'Paete', 'Pagsanjan',
                'Pakil', 'Pangil', 'Pila', 'Rizal', 'San Pedro', 'Santa Cruz', 'Santa Maria',
                'Siniloan', 'Victoria'
            ],
            'Batangas' => [
                'Batangas City', 'Lipa', 'Tanauan', 'Bauan', 'Calaca', 'Lemery', 'Nasugbu',
                'Balayan', 'Calatagan', 'Cuenca', 'Ibaan', 'Laurel', 'Lian', 'Lobo', 'Mabini',
                'Malvar', 'Mataasnakahoy', 'Padre Garcia', 'Rosario', 'San Jose', 'San Juan',
                'San Luis', 'San Nicolas', 'San Pascual', 'Santo Tomas', 'Taal', 'Taysan',
                'Tingloy', 'Tuy'
            ],
            'Cavite' => [
                'Bacoor', 'Cavite City', 'Dasmariñas', 'Imus', 'Tagaytay', 'Trece Martires',
                'Alfonso', 'Amadeo', 'Carmona', 'General Emilio Aguinaldo', 'General Mariano Alvarez',
                'General Trias', 'Indang', 'Kawit', 'Magallanes', 'Maragondon', 'Mendez',
                'Naic', 'Noveleta', 'Rosario', 'Silang', 'Tanza', 'Ternate'
            ],
            'Bulacan' => [
                'Angat', 'Balagtas', 'Baliuag', 'Bocaue', 'Bulacan', 'Bustos', 'Calumpit',
                'Doña Remedios Trinidad', 'Guiguinto', 'Hagonoy', 'Malolos', 'Marilao',
                'Meycauayan', 'Norzagaray', 'Obando', 'Pandi', 'Paombong', 'Plaridel',
                'Pulilan', 'San Ildefonso', 'San Jose del Monte', 'San Miguel', 'San Rafael',
                'Santa Maria'
            ],
            'Pampanga' => [
                'Angeles', 'Apalit', 'Arayat', 'Bacolor', 'Candaba', 'Floridablanca',
                'Guagua', 'Lubao', 'Mabalacat', 'Macabebe', 'Magalang', 'Masantol',
                'Mexico', 'Minalin', 'Porac', 'San Fernando', 'San Luis', 'San Simon',
                'Santa Ana', 'Santa Rita', 'Santo Tomas', 'Sasmuan'
            ],
            'Rizal' => [
                'Angono', 'Antipolo', 'Baras', 'Binangonan', 'Cainta', 'Cardona',
                'Jala-Jala', 'Morong', 'Pililla', 'Rodriguez', 'San Mateo', 'Tanay',
                'Taytay', 'Teresa'
            ],
            'Quezon' => [
                'Agdangan', 'Alabat', 'Atimonan', 'Buenavista', 'Burdeos', 'Calauag',
                'Candelaria', 'Catanauan', 'Dolores', 'General Luna', 'General Nakar',
                'Guinayangan', 'Gumaca', 'Infanta', 'Jomalig', 'Lopez', 'Lucban',
                'Lucena', 'Macalelon', 'Mauban', 'Mulanay', 'Padre Burgos', 'Pagbilao',
                'Panukulan', 'Patnanungan', 'Perez', 'Pitogo', 'Plaridel', 'Polillo',
                'Quezon', 'Real', 'Sampaloc', 'San Andres', 'San Antonio', 'San Francisco',
                'San Narciso', 'Sariaya', 'Tagkawayan', 'Tayabas', 'Tiaong', 'Unisan'
            ],
            'Nueva Ecija' => [
                'Aliaga', 'Bongabon', 'Cabanatuan', 'Cabiao', 'Carranglan', 'Cuyapo',
                'Gabaldon', 'General Mamerto Natividad', 'General Tinio', 'Guimba',
                'Jaen', 'Laur', 'Licab', 'Llanera', 'Lupao', 'Muñoz', 'Nampicuan',
                'Palayan', 'Pantabangan', 'Peñaranda', 'Quezon', 'Rizal', 'San Antonio',
                'San Isidro', 'San Jose', 'San Leonardo', 'Santa Rosa', 'Santo Domingo',
                'Talavera', 'Talugtug', 'Zaragoza'
            ],
            'Tarlac' => [
                'Anao', 'Bamban', 'Camiling', 'Capas', 'Concepcion', 'Gerona',
                'La Paz', 'Mayantoc', 'Moncada', 'Paniqui', 'Pura', 'Ramos',
                'San Clemente', 'San Jose', 'San Manuel', 'Santa Ignacia', 'Tarlac City', 'Victoria'
            ],
            'Zambales' => [
                'Botolan', 'Cabangan', 'Candelaria', 'Castillejos', 'Iba', 'Masinloc',
                'Olongapo', 'Palauig', 'San Antonio', 'San Felipe', 'San Marcelino',
                'San Narciso', 'Santa Cruz', 'Subic'
            ],
            'Bataan' => [
                'Abucay', 'Bagac', 'Balanga', 'Dinalupihan', 'Hermosa', 'Limay',
                'Mariveles', 'Morong', 'Orani', 'Orion', 'Pilar', 'Samal'
            ],
            'Iloilo' => [
                'Ajuy', 'Alimodian', 'Anilao', 'Badiangan', 'Balasan', 'Banate',
                'Barotac Nuevo', 'Barotac Viejo', 'Batad', 'Bingawan', 'Cabatuan',
                'Calinog', 'Carles', 'Concepcion', 'Dingle', 'Dueñas', 'Dumangas',
                'Estancia', 'Guimbal', 'Igbaras', 'Janiuay', 'Lambunao', 'Leganes',
                'Lemery', 'Leon', 'Maasin', 'Miagao', 'Mina', 'New Lucena', 'Oton',
                'Passi', 'Pavia', 'Pototan', 'San Dionisio', 'San Enrique', 'San Joaquin',
                'San Miguel', 'San Rafael', 'Santa Barbara', 'Sara', 'Tigbauan',
                'Tubungan', 'Zarraga', 'Iloilo City'
            ],
            'Negros Occidental' => [
                'Bacolod', 'Bago', 'Binalbagan', 'Cadiz', 'Calatrava', 'Candoni',
                'Cauayan', 'Enrique B. Magalona', 'Escalante', 'Himamaylan', 'Hinigaran',
                'Hinoba-an', 'Ilog', 'Isabela', 'Kabankalan', 'La Carlota', 'La Castellana',
                'Manapla', 'Moises Padilla', 'Murcia', 'Pontevedra', 'Pulupandan',
                'Sagay', 'Salvador Benedicto', 'San Carlos', 'San Enrique', 'Silay',
                'Sipalay', 'Talisay', 'Toboso', 'Valladolid', 'Victorias'
            ],
            'Negros Oriental' => [
                'Amlan', 'Ayungon', 'Bacong', 'Basay', 'Bayawan', 'Bindoy',
                'Canlaon', 'Dauin', 'Dumaguete', 'Guihulngan', 'Jimalalud', 'La Libertad',
                'Mabinay', 'Manjuyod', 'Pamplona', 'San Jose', 'Santa Catalina',
                'Siaton', 'Sibulan', 'Tanjay', 'Tayasan', 'Valencia', 'Vallehermoso',
                'Zamboanguita'
            ],
            'Aklan' => [
                'Altavas', 'Balete', 'Banga', 'Batan', 'Buruanga', 'Ibajay',
                'Kalibo', 'Lezo', 'Libacao', 'Madalag', 'Makato', 'Malay',
                'Malinao', 'Nabas', 'New Washington', 'Numancia', 'Tangalan'
            ],
            'Antique' => [
                'Anini-y', 'Barbaza', 'Belison', 'Bugasong', 'Caluya', 'Culasi',
                'Hamtic', 'Laua-an', 'Libertad', 'Pandan', 'Patnongon', 'San Jose',
                'San Remigio', 'Sebaste', 'Sibalom', 'Tibiao', 'Tobias Fornier', 'Valderrama'
            ],
            'Capiz' => [
                'Cuartero', 'Dao', 'Dumalag', 'Dumarao', 'Ivisan', 'Jamindan',
                'Maayon', 'Mambusao', 'Panay', 'Panitan', 'Pilar', 'Pontevedra',
                'President Roxas', 'Roxas City', 'Sapian', 'Sigma', 'Tapaz'
            ],
            'Guimaras' => [
                'Buenavista', 'Jordan', 'Nueva Valencia', 'San Lorenzo', 'Sibunag'
            ],
            'Bohol' => [
                'Alburquerque', 'Alicia', 'Anda', 'Antequera', 'Baclayon', 'Balilihan',
                'Batuan', 'Bien Unido', 'Bilar', 'Buenavista', 'Calape', 'Candijay',
                'Carmen', 'Catigbian', 'Clarin', 'Corella', 'Cortes', 'Dagohoy',
                'Danao', 'Dauis', 'Dimiao', 'Duero', 'Garcia Hernandez', 'Getafe',
                'Guindulman', 'Inabanga', 'Jagna', 'Lila', 'Loay', 'Loboc',
                'Loon', 'Mabini', 'Maribojoc', 'Panglao', 'Pilar', 'Pres. Carlos P. Garcia',
                'Sagbayan', 'San Isidro', 'San Miguel', 'Sevilla', 'Sierra Bullones',
                'Sikatuna', 'Tagbilaran', 'Talibon', 'Trinidad', 'Tubigon', 'Ubay', 'Valencia'
            ],
            'Abra' => [
                'Bangued', 'Boliney', 'Bucay', 'Bucloc', 'Daguioman', 'Danglas',
                'Dolores', 'La Paz', 'Lacub', 'Lagangilang', 'Lagayan', 'Langiden',
                'Licuan-Baay', 'Luba', 'Malibcong', 'Manabo', 'Peñarrubia', 'Pidigan',
                'Pilar', 'Sallapadan', 'San Isidro', 'San Juan', 'San Quintin', 'Tayum',
                'Tineg', 'Tubo', 'Villaviciosa'
            ],
            'Agusan del Norte' => [
                'Butuan', 'Cabadbaran', 'Buenavista', 'Carmen', 'Jabonga', 'Kitcharao',
                'Las Nieves', 'Magallanes', 'Nasipit', 'Remedios T. Romualdez', 'Santiago', 'Tubay'
            ],
            'Agusan del Sur' => [
                'Bayugan', 'Bunawan', 'Esperanza', 'La Paz', 'Loreto', 'Prosperidad',
                'Rosario', 'San Francisco', 'San Luis', 'Santa Josefa', 'Sibagat',
                'Talacogon', 'Trento', 'Veruela'
            ],
            'Albay' => [
                'Legazpi', 'Ligao', 'Tabaco', 'Bacacay', 'Camalig', 'Daraga',
                'Guinobatan', 'Jovellar', 'Libon', 'Malilipot', 'Malinao', 'Manito',
                'Oas', 'Pio Duran', 'Polangui', 'Rapu-Rapu', 'Santo Domingo', 'Tiwi'
            ],
            'Apayao' => [
                'Calanasan', 'Conner', 'Flora', 'Kabugao', 'Luna', 'Pudtol', 'Santa Marcela'
            ],
            'Aurora' => [
                'Baler', 'Casiguran', 'Dilasag', 'Dinalungan', 'Dingalan', 'Dipaculao',
                'Maria Aurora', 'San Luis'
            ],
            'Basilan' => [
                'Isabela City', 'Lamitan', 'Akbar', 'Al-Barka', 'Hadji Mohammad Ajul',
                'Hadji Muhtamad', 'Lantawan', 'Maluso', 'Sumisip', 'Tabuan-Lasa',
                'Tipo-Tipo', 'Tuburan', 'Ungkaya Pukan'
            ],
            'Batanes' => [
                'Basco', 'Itbayat', 'Ivana', 'Mahatao', 'Sabtang', 'Uyugan'
            ],
            'Benguet' => [
                'Baguio', 'Atok', 'Bakun', 'Bokod', 'Buguias', 'Itogon',
                'Kabayan', 'Kapangan', 'Kibungan', 'La Trinidad', 'Mankayan', 'Sablan',
                'Tuba', 'Tublay'
            ],
            'Biliran' => [
                'Almeria', 'Biliran', 'Cabucgayan', 'Caibiran', 'Culaba', 'Kawayan',
                'Maripipi', 'Naval'
            ],
            'Bukidnon' => [
                'Malaybalay', 'Valencia', 'Baungon', 'Cabanglasan', 'Damulog', 'Dangcagan',
                'Don Carlos', 'Impasugong', 'Kadingilan', 'Kalilangan', 'Kibawe', 'Kitaotao',
                'Lantapan', 'Libona', 'Malitbog', 'Manolo Fortich', 'Maramag', 'Pangantucan',
                'Quezon', 'San Fernando', 'Sumilao', 'Talakag'
            ],
            'Cagayan' => [
                'Tuguegarao', 'Abulug', 'Alcala', 'Allacapan', 'Amulung', 'Aparri',
                'Baggao', 'Ballesteros', 'Buguey', 'Calayan', 'Camalaniugan', 'Claveria',
                'Enrile', 'Gattaran', 'Gonzaga', 'Iguig', 'Lal-lo', 'Lasam',
                'Pamplona', 'Peñablanca', 'Piat', 'Rizal', 'Sanchez-Mira', 'Santa Ana',
                'Santa Praxedes', 'Santa Teresita', 'Santo Niño', 'Solana', 'Tuao'
            ],
            'Camarines Norte' => [
                'Basud', 'Capalonga', 'Daet', 'Jose Panganiban', 'Labo', 'Mercedes',
                'Paracale', 'San Lorenzo Ruiz', 'San Vicente', 'Santa Elena', 'Talisay', 'Vinzons'
            ],
            'Camarines Sur' => [
                'Iriga', 'Naga', 'Baao', 'Balatan', 'Bato', 'Bombon',
                'Buhi', 'Bula', 'Cabusao', 'Calabanga', 'Camaligan', 'Canaman',
                'Caramoan', 'Del Gallego', 'Gainza', 'Garchitorena', 'Goa', 'Lagonoy',
                'Libmanan', 'Lupi', 'Magarao', 'Milaor', 'Minalabac', 'Nabua',
                'Ocampo', 'Pamplona', 'Pasacao', 'Pili', 'Presentacion', 'Ragay',
                'Sagñay', 'San Fernando', 'San Jose', 'Sipocot', 'Siruma', 'Tigaon', 'Tinambac'
            ],
            'Camiguin' => [
                'Catarman', 'Guinsiliban', 'Mahinog', 'Mambajao', 'Sagay'
            ],
            'Catanduanes' => [
                'Virac', 'Bagamanoc', 'Baras', 'Bato', 'Caramoran', 'Gigmoto',
                'Pandan', 'Panganiban', 'San Andres', 'San Miguel', 'Viga'
            ],
            'Cotabato' => [
                'Alamada', 'Aleosan', 'Antipas', 'Arakan', 'Banisilan', 'Carmen',
                'Kabacan', 'Kidapawan', 'Libungan', 'M\'lang', 'Magpet', 'Makilala',
                'Matalam', 'Midsayap', 'Pigcawayan', 'Pikit', 'President Roxas', 'Tulunan'
            ],
            'Davao de Oro' => [
                'Nabunturan', 'Compostela', 'Laak', 'Mabini', 'Maco', 'Maragusan',
                'Mawab', 'Monkayo', 'Montevista', 'New Bataan', 'Pantukan'
            ],
            'Davao del Norte' => [
                'Tagum', 'Asuncion', 'Braulio E. Dujali', 'Carmen', 'Kapalong', 'New Corella',
                'Panabo', 'Samal', 'San Isidro', 'Santo Tomas', 'Talaingod'
            ],
            'Davao del Sur' => [
                'Davao City', 'Digos', 'Bansalan', 'Hagonoy', 'Kiblawan', 'Magsaysay',
                'Malalag', 'Matanao', 'Padada', 'Santa Cruz', 'Sulop'
            ],
            'Davao Occidental' => [
                'Malita', 'Don Marcelino', 'Jose Abad Santos', 'Santa Maria', 'Sarangani'
            ],
            'Davao Oriental' => [
                'Mati', 'Baganga', 'Banaybanay', 'Boston', 'Caraga', 'Cateel',
                'Governor Generoso', 'Lupon', 'Manay', 'San Isidro', 'Tarragona'
            ],
            'Dinagat Islands' => [
                'Basilisa', 'Cagdianao', 'Dinagat', 'Libjo', 'Loreto', 'San Jose', 'Tubajon'
            ],
            'Eastern Samar' => [
                'Borongan', 'Arteche', 'Balangiga', 'Balangkayan', 'Can-avid', 'Dolores',
                'General MacArthur', 'Giporlos', 'Guiuan', 'Hernani', 'Jipapad', 'Lawaan',
                'Llorente', 'Maslog', 'Maydolong', 'Mercedes', 'Oras', 'Quinapondan',
                'Salcedo', 'San Julian', 'San Policarpo', 'Sulat', 'Taft'
            ],
            'Ifugao' => [
                'Aguinaldo', 'Alfonso Lista', 'Asipulo', 'Banaue', 'Hingyon', 'Hungduan',
                'Kiangan', 'Lagawe', 'Lamut', 'Mayoyao', 'Tinoc'
            ],
            'Ilocos Norte' => [
                'Batac', 'Laoag', 'Adams', 'Bacarra', 'Badoc', 'Bangui',
                'Banna', 'Burgos', 'Carasi', 'Currimao', 'Dingras', 'Dumalneg',
                'Marcos', 'Nueva Era', 'Pagudpud', 'Paoay', 'Pasuquin', 'Piddig',
                'Pinili', 'San Nicolas', 'Sarrat', 'Solsona', 'Vintar'
            ],
            'Ilocos Sur' => [
                'Candon', 'Vigan', 'Alilem', 'Banayoyo', 'Bantay', 'Burgos',
                'Cabugao', 'Caoayan', 'Cervantes', 'Galimuyod', 'Gregorio del Pilar', 'Lidlidda',
                'Magsingal', 'Nagbukel', 'Narvacan', 'Quirino', 'Salcedo', 'San Emilio',
                'San Esteban', 'San Ildefonso', 'San Juan', 'San Vicente', 'Santa', 'Santa Catalina',
                'Santa Cruz', 'Santa Lucia', 'Santa Maria', 'Santiago', 'Santo Domingo',
                'Sigay', 'Sinait', 'Sugpon', 'Suyo', 'Tagudin'
            ],
            'Isabela' => [
                'Cauayan', 'Ilagan', 'Santiago', 'Alicia', 'Angadanan', 'Aurora',
                'Benito Soliven', 'Burgos', 'Cabagan', 'Cabatuan', 'Cordon', 'Delfin Albano',
                'Dinapigue', 'Divilacan', 'Echague', 'Gamu', 'Jones', 'Luna',
                'Maconacon', 'Mallig', 'Naguilian', 'Palanan', 'Quezon', 'Quirino',
                'Ramon', 'Reina Mercedes', 'Roxas', 'San Agustin', 'San Guillermo', 'San Isidro',
                'San Manuel', 'San Mariano', 'San Mateo', 'San Pablo', 'Santa Maria', 'Santo Tomas', 'Tumauini'
            ],
            'Kalinga' => [
                'Tabuk', 'Balbalan', 'Lubuagan', 'Pasil', 'Pinukpuk', 'Rizal',
                'Tanudan', 'Tinglayan'
            ],
            'La Union' => [
                'San Fernando', 'Agoo', 'Aringay', 'Bacnotan', 'Bagulin', 'Balaoan',
                'Bangar', 'Bauang', 'Burgos', 'Caba', 'Luna', 'Naguilian',
                'Pugo', 'Rosario', 'San Gabriel', 'San Juan', 'Santo Tomas', 'Santol',
                'Sudipen', 'Tubao'
            ],
            'Lanao del Norte' => [
                'Iligan', 'Bacolod', 'Baloi', 'Baroy', 'Kapatagan', 'Kauswagan',
                'Kolambugan', 'Lala', 'Linamon', 'Magsaysay', 'Maigo', 'Matungao',
                'Munai', 'Nunungan', 'Pantao Ragat', 'Pantar', 'Poona Piagapo', 'Salvador',
                'Sapad', 'Sultan Naga Dimaporo', 'Tagoloan', 'Tangcal', 'Tubod'
            ],
            'Lanao del Sur' => [
                'Marawi', 'Bacolod-Kalawi', 'Balabagan', 'Balindong', 'Bayang', 'Binidayan',
                'Buadiposo-Buntong', 'Bubong', 'Butig', 'Calanogas', 'Ditsaan-Ramain', 'Ganassi',
                'Kapai', 'Kapatagan', 'Lumba-Bayabao', 'Lumbaca-Unayan', 'Lumbatan', 'Lumbayanague',
                'Madalum', 'Madamba', 'Malabang', 'Marantao', 'Marogong', 'Masiu',
                'Mulondo', 'Pagayawan', 'Piagapo', 'Picong', 'Poona Bayabao', 'Pualas',
                'Saguiaran', 'Sultan Dumalondong', 'Tagoloan II', 'Tamparan', 'Taraka', 'Tubaran',
                'Tugaya', 'Wao'
            ],
            'Leyte' => [
                'Ormoc', 'Tacloban', 'Abuyog', 'Alangalang', 'Albuera', 'Babatngon',
                'Barugo', 'Bato', 'Baybay', 'Burauen', 'Calubian', 'Capoocan',
                'Carigara', 'Dagami', 'Dulag', 'Hilongos', 'Hindang', 'Inopacan',
                'Isabel', 'Jaro', 'Javier', 'Julita', 'Kananga', 'La Paz',
                'Leyte', 'MacArthur', 'Mahaplag', 'Matag-ob', 'Matalom', 'Mayorga',
                'Merida', 'Palo', 'Palompon', 'Pastrana', 'San Isidro', 'San Miguel',
                'Santa Fe', 'Tabango', 'Tabontabon', 'Tanauan', 'Tolosa', 'Tunga', 'Villaba'
            ],
            'Maguindanao' => [
                'Ampatuan', 'Barira', 'Buldon', 'Buluan', 'Cotabato City', 'Datu Abdullah Sangki',
                'Datu Anggal Midtimbang', 'Datu Blah T. Sinsuat', 'Datu Hoffer Ampatuan', 'Datu Montawal',
                'Datu Odin Sinsuat', 'Datu Paglas', 'Datu Piang', 'Datu Salibo', 'Datu Saudi-Ampatuan',
                'Datu Unsay', 'General Salipada K. Pendatun', 'Guindulungan', 'Kabuntalan', 'Mamasapano',
                'Mangudadatu', 'Matanog', 'Northern Kabuntalan', 'Pagalungan', 'Paglat',
                'Pandag', 'Parang', 'Rajah Buayan', 'Shariff Aguak', 'Shariff Saydona Mustapha',
                'South Upi', 'Sultan Kudarat', 'Sultan Mastura', 'Sultan sa Barongis', 'Talayan',
                'Talitay', 'Upi'
            ],
            'Marinduque' => [
                'Boac', 'Buenavista', 'Gasan', 'Mogpog', 'Santa Cruz', 'Torrijos'
            ],
            'Masbate' => [
                'Masbate City', 'Aroroy', 'Baleno', 'Balud', 'Batuan', 'Cataingan',
                'Cawayan', 'Claveria', 'Dimasalang', 'Esperanza', 'Mandaon', 'Milagros',
                'Mobo', 'Monreal', 'Palanas', 'Pio V. Corpuz', 'Placer', 'San Fernando',
                'San Jacinto', 'San Pascual', 'Uson'
            ],
            'Misamis Occidental' => [
                'Oroquieta', 'Ozamiz', 'Tangub', 'Aloran', 'Baliangao', 'Bonifacio',
                'Calamba', 'Clarin', 'Concepcion', 'Don Victoriano Chiongbian', 'Jimenez', 'Lopez Jaena',
                'Panaon', 'Plaridel', 'Sapang Dalaga', 'Sinacaban', 'Tudela'
            ],
            'Misamis Oriental' => [
                'Cagayan de Oro', 'Gingoog', 'Alubijid', 'Balingasag', 'Balingoan', 'Binuangan',
                'Claveria', 'El Salvador', 'Gitagum', 'Initao', 'Jasaan', 'Kinoguitan',
                'Lagonglong', 'Laguindingan', 'Libertad', 'Lugait', 'Magsaysay', 'Manticao',
                'Medina', 'Naawan', 'Opol', 'Salay', 'Sugbongcogon', 'Tagoloan', 'Talisayan', 'Villanueva'
            ],
            'Mountain Province' => [
                'Barlig', 'Bauko', 'Besao', 'Bontoc', 'Natonin', 'Paracelis',
                'Sabangan', 'Sadanga', 'Sagada', 'Tadian'
            ],
            'Northern Samar' => [
                'Allen', 'Biri', 'Bobon', 'Capul', 'Catarman', 'Catubig',
                'Gamay', 'Laoang', 'Lapinig', 'Las Navas', 'Lavezares', 'Lope de Vega',
                'Mapanas', 'Mondragon', 'Palapag', 'Pambujan', 'Rosario', 'San Antonio',
                'San Isidro', 'San Jose', 'San Roque', 'San Vicente', 'Silvino Lobos', 'Victoria'
            ],
            'Nueva Vizcaya' => [
                'Bayombong', 'Alfonso Castañeda', 'Ambaguio', 'Aritao', 'Bagabag', 'Bambang',
                'Diadi', 'Dupax del Norte', 'Dupax del Sur', 'Kasibu', 'Kayapa', 'Quezon',
                'Santa Fe', 'Solano', 'Villaverde'
            ],
            'Occidental Mindoro' => [
                'Abra de Ilog', 'Calintaan', 'Looc', 'Lubang', 'Magsaysay', 'Mamburao',
                'Paluan', 'Rizal', 'Sablayan', 'San Jose', 'Santa Cruz'
            ],
            'Oriental Mindoro' => [
                'Calapan', 'Baco', 'Bansud', 'Bongabong', 'Bulalacao', 'Gloria',
                'Mansalay', 'Naujan', 'Pinamalayan', 'Pola', 'Puerto Galera', 'Roxas',
                'San Teodoro', 'Socorro', 'Victoria'
            ],
            'Palawan' => [
                'Puerto Princesa', 'Aborlan', 'Agutaya', 'Araceli', 'Balabac', 'Bataraza',
                'Brooke\'s Point', 'Busuanga', 'Cagayancillo', 'Coron', 'Culion', 'Cuyo',
                'Dumaran', 'El Nido', 'Kalayaan', 'Linapacan', 'Magsaysay', 'Narra',
                'Quezon', 'Rizal', 'Roxas', 'San Vicente', 'Sofronio Española', 'Taytay'
            ],
            'Pangasinan' => [
                'Alaminos', 'Dagupan', 'San Carlos', 'Urdaneta', 'Agno', 'Aguilar',
                'Alcala', 'Anda', 'Asingan', 'Balungao', 'Bani', 'Basista',
                'Bautista', 'Bayambang', 'Binalonan', 'Binmaley', 'Bolinao', 'Bugallon',
                'Burgos', 'Calasiao', 'Dasol', 'Infanta', 'Labrador', 'Laoac',
                'Lingayen', 'Mabini', 'Malasiqui', 'Manaoag', 'Mangaldan', 'Mangatarem',
                'Mapandan', 'Natividad', 'Pozorrubio', 'Rosales', 'San Fabian', 'San Jacinto',
                'San Manuel', 'San Nicolas', 'San Quintin', 'Santa Barbara', 'Santa Maria', 'Santo Tomas',
                'Sison', 'Sual', 'Tayug', 'Umingan', 'Urbiztondo', 'Villasis'
            ],
            'Quirino' => [
                'Aglipay', 'Cabarroguis', 'Diffun', 'Maddela', 'Nagtipunan', 'Saguday'
            ],
            'Romblon' => [
                'Alcantara', 'Banton', 'Cajidiocan', 'Calatrava', 'Concepcion', 'Corcuera',
                'Ferrol', 'Looc', 'Magdiwang', 'Odiongan', 'Romblon', 'San Agustin',
                'San Andres', 'San Fernando', 'San Jose', 'Santa Fe', 'Santa Maria'
            ],
            'Samar' => [
                'Catbalogan', 'Calbayog', 'Almagro', 'Basey', 'Calbiga', 'Daram',
                'Gandara', 'Hinabangan', 'Jiabong', 'Marabut', 'Matuguinao', 'Motiong',
                'Pagsanghan', 'Paranas', 'Pinabacdao', 'San Jorge', 'San Jose de Buan', 'San Sebastian',
                'Santa Margarita', 'Santa Rita', 'Santo Niño', 'Tagapul-an', 'Talalora', 'Tarangnan',
                'Villareal', 'Zumarraga'
            ],
            'Sarangani' => [
                'Alabel', 'Glan', 'Kiamba', 'Maasim', 'Maitum', 'Malapatan', 'Malungon'
            ],
            'Siquijor' => [
                'Enrique Villanueva', 'Larena', 'Lazi', 'Maria', 'San Juan', 'Siquijor'
            ],
            'Sorsogon' => [
                'Sorsogon City', 'Barcelona', 'Bulan', 'Bulusan', 'Casiguran', 'Castilla',
                'Donsol', 'Gubat', 'Irosin', 'Juban', 'Magallanes', 'Matnog',
                'Pilar', 'Prieto Diaz', 'Santa Magdalena'
            ],
            'South Cotabato' => [
                'General Santos', 'Koronadal', 'Banga', 'Lake Sebu', 'Norala', 'Polomolok',
                'Santo Niño', 'Surallah', 'T\'Boli', 'Tampakan', 'Tantangan', 'Tupi'
            ],
            'Southern Leyte' => [
                'Maasin', 'Anahawan', 'Bontoc', 'Hinunangan', 'Hinundayan', 'Libagon',
                'Liloan', 'Limasawa', 'Macrohon', 'Malitbog', 'Padre Burgos', 'Pintuyan',
                'Saint Bernard', 'San Francisco', 'San Juan', 'San Ricardo', 'Silago', 'Sogod', 'Tomas Oppus'
            ],
            'Sultan Kudarat' => [
                'Tacurong', 'Bagumbayan', 'Columbio', 'Esperanza', 'Isulan', 'Kalamansig',
                'Lambayong', 'Lebak', 'Lutayan', 'Palimbang', 'President Quirino', 'Senator Ninoy Aquino'
            ],
            'Sulu' => [
                'Jolo', 'Hadji Panglima Tahil', 'Indanan', 'Kalingalan Caluang', 'Lugus', 'Luuk',
                'Maimbung', 'Old Panamao', 'Omar', 'Pandami', 'Panglima Estino', 'Pangutaran',
                'Parang', 'Pata', 'Patikul', 'Siasi', 'Talipao', 'Tapul', 'Tongkil'
            ],
            'Surigao del Norte' => [
                'Surigao City', 'Alegria', 'Bacuag', 'Burgos', 'Claver', 'Dapa',
                'Del Carmen', 'General Luna', 'Gigaquit', 'Mainit', 'Malimono', 'Pilar',
                'Placer', 'San Benito', 'San Francisco', 'San Isidro', 'Santa Monica', 'Sison',
                'Socorro', 'Tagana-an', 'Tubod'
            ],
            'Surigao del Sur' => [
                'Bislig', 'Tandag', 'Barobo', 'Bayabas', 'Cagwait', 'Cantilan',
                'Carmen', 'Carrascal', 'Cortes', 'Hinatuan', 'Lanuza', 'Lianga',
                'Lingig', 'Madrid', 'Marihatag', 'San Agustin', 'San Miguel', 'Tagbina', 'Tago'
            ],
            'Tawi-Tawi' => [
                'Bongao', 'Languyan', 'Mapun', 'Panglima Sugala', 'Sapa-Sapa', 'Sibutu',
                'Simunul', 'Sitangkai', 'South Ubian', 'Tandubas', 'Turtle Islands'
            ],
            'Zamboanga del Norte' => [
                'Dapitan', 'Dipolog', 'Baliguian', 'Dapitan', 'Godod', 'Gutalac',
                'Jose Dalman', 'Kalawit', 'Katipunan', 'La Libertad', 'Labason', 'Liloy',
                'Manukan', 'Mutia', 'Piñan', 'Polanco', 'President Manuel A. Roxas', 'Rizal',
                'Salug', 'Sergio Osmeña Sr.', 'Siayan', 'Sibuco', 'Sibutad', 'Sindangan',
                'Siocon', 'Sirawai', 'Tampilisan'
            ],
            'Zamboanga del Sur' => [
                'Pagadian', 'Zamboanga City', 'Aurora', 'Bayog', 'Dimataling', 'Dinas',
                'Dumalinao', 'Dumingag', 'Guipos', 'Josefina', 'Kumalarang', 'Labangan',
                'Lakewood', 'Lapuyan', 'Mahayag', 'Margosatubig', 'Midsalip', 'Molave',
                'Pitogo', 'Ramon Magsaysay', 'San Miguel', 'San Pablo', 'Sominot', 'Tabina',
                'Tambulig', 'Tigbao', 'Tukuran', 'Vincenzo A. Sagun'
            ],
            'Zamboanga Sibugay' => [
                'Alicia', 'Buug', 'Diplahan', 'Imelda', 'Ipil', 'Kabasalan',
                'Mabuhay', 'Malangas', 'Naga', 'Olutanga', 'Payao', 'Roseller Lim',
                'Siay', 'Talusan', 'Titay', 'Tungawan'
            ]
        ];

        // Return the municipalities for the province, or empty array if not found
        return $municipalitiesData[$province] ?? [];
    }

    /**
     * Calculate shipping costs for selected products
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function calculateShipping(Request $request)
    {
        try {
            $selectedProducts = $request->input('selectedProducts', []);
            $province = $request->input('province');

            // Province is optional - we can calculate access products without it
            // Only required if there are ship products

            if (empty($selectedProducts)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No products selected'
                ], 400);
            }

            // Separate ship and access products
            $shipProducts = array_filter($selectedProducts, function($product) {
                return isset($product['productType']) &&
                       (strtolower($product['productType']) === 'ship');
            });

            $accessProducts = array_filter($selectedProducts, function($product) {
                return isset($product['productType']) &&
                       (strtolower($product['productType']) === 'access');
            });

            // Debug: Log product types found
            \Log::info('Product type detection', [
                'total_products' => count($selectedProducts),
                'ship_products_count' => count($shipProducts),
                'access_products_count' => count($accessProducts),
                'selected_products' => $selectedProducts,
                'province' => $province
            ]);

            // Check if province is required (only if there are ship products)
            if (count($shipProducts) > 0 && empty($province)) {
                // Show warning but still calculate access products
                \Log::info('Province not provided but ship products exist');
            }

            // Validate ship coverage for ship products
            $shipCoverageErrors = [];
            foreach ($shipProducts as $product) {
                $shipCoverage = $product['shipCoverage'] ?? 'n/a';
                $productName = $product['productName'] ?? 'Unknown Product';

                if (strtolower($shipCoverage) === 'province') {
                    if (empty($province)) {
                        $shipCoverageErrors[] = "Province shipping location is required for product: {$productName}";
                    } elseif (strtolower($province) !== 'pangasinan') {
                        $shipCoverageErrors[] = "Product '{$productName}' has Province shipping coverage only and can only be shipped to Pangasinan.";
                    }
                }
            }

            // Return validation errors if any
            if (!empty($shipCoverageErrors)) {
                return response()->json([
                    'success' => false,
                    'message' => implode(' ', $shipCoverageErrors)
                ], 400);
            }

            // Calculate total subtotal for all products
            $totalSubtotal = 0;
            foreach ($selectedProducts as $product) {
                $quantity = intval($product['quantity'] ?? 1);
                $price = floatval($product['price'] ?? 0);
                $totalSubtotal += $quantity * $price;
            }

            $shippingBreakdown = [];
            $totalShipping = 0;

            // Process ship products for shipping calculation
            foreach ($shipProducts as $product) {
                $variantId = $product['variantId'] ?? null;
                $quantity = intval($product['quantity'] ?? 1);
                $price = floatval($product['price'] ?? 0);

                if (!$variantId) continue;

                $subtotal = $quantity * $price;

                // Get shipping options for this variant
                $shippingOptions = DB::table('ecom_products_variants_shipping as evs')
                    ->join('ecom_products_shipping as es', 'evs.ecomShippingId', '=', 'es.id')
                    ->leftJoin('ecom_products_shipping_options as eso', function($join) use ($province) {
                        $join->on('es.id', '=', 'eso.shippingId')
                             ->where('eso.provinceTarget', '=', $province)
                             ->where('eso.isActive', '=', 1)
                             ->where('eso.deleteStatus', '=', 1);
                    })
                    ->where('evs.ecomVariantId', $variantId)
                    ->where('es.isActive', 1)
                    ->where('es.deleteStatus', 1)
                    ->select(
                        'es.id as shippingId',
                        'es.defaultMaxQuantity',
                        'es.defaultPrice',
                        'eso.maxQuantity',
                        'eso.shippingPrice',
                        'eso.provinceTarget'
                    )
                    ->get();

                // Debug: Log the query results
                \Log::info('Shipping Options Query Result for Variant ' . $variantId . ' and Province ' . $province, [
                    'shipping_options' => $shippingOptions->toArray()
                ]);

                if ($shippingOptions->isEmpty()) {
                    // No shipping options found for this variant
                    continue;
                }

                // Find the cheapest shipping option
                $cheapestShipping = null;
                $cheapestPrice = PHP_FLOAT_MAX;

                foreach ($shippingOptions as $option) {
                    $shippingPrice = 0;
                    $isProvinceSpecific = false;

                    // Use province-specific pricing if available
                    if ($option->shippingPrice !== null && $option->maxQuantity !== null && $option->provinceTarget) {
                        $maxQty = intval($option->maxQuantity);
                        $pricePerBatch = floatval($option->shippingPrice);
                        $isProvinceSpecific = true;

                        // Calculate how many batches needed
                        $batches = ceil($quantity / $maxQty);
                        $shippingPrice = $batches * $pricePerBatch;

                        \Log::info('Using province-specific pricing', [
                            'province' => $option->provinceTarget,
                            'maxQuantity' => $maxQty,
                            'pricePerBatch' => $pricePerBatch,
                            'quantity' => $quantity,
                            'batches' => $batches,
                            'totalPrice' => $shippingPrice
                        ]);
                    } else {
                        // Use default pricing
                        $maxQty = intval($option->defaultMaxQuantity);
                        $pricePerBatch = floatval($option->defaultPrice);

                        // Calculate how many batches needed
                        $batches = ceil($quantity / $maxQty);
                        $shippingPrice = $batches * $pricePerBatch;

                        \Log::info('Using default pricing', [
                            'defaultMaxQuantity' => $maxQty,
                            'defaultPrice' => $pricePerBatch,
                            'quantity' => $quantity,
                            'batches' => $batches,
                            'totalPrice' => $shippingPrice
                        ]);
                    }

                    if ($shippingPrice < $cheapestPrice) {
                        $cheapestPrice = $shippingPrice;
                        $cheapestShipping = $option;
                        $cheapestShipping->isProvinceSpecific = $isProvinceSpecific;
                    }
                }

                if ($cheapestShipping) {
                    $totalShipping += $cheapestPrice;

                    // Get shipping name from the shipping options query
                    $shippingName = 'Default Shipping';
                    if ($cheapestShipping->shippingId) {
                        $shippingInfo = DB::table('ecom_products_shipping')
                            ->where('id', $cheapestShipping->shippingId)
                            ->select('shippingName')
                            ->first();
                        if ($shippingInfo) {
                            $shippingName = $shippingInfo->shippingName ?? 'Default Shipping';
                        }
                    }

                    $shippingBreakdown[] = [
                        'productName' => $product['productName'] ?? 'Unknown Product',
                        'variantName' => $product['variantName'] ?? 'Default',
                        'productStore' => $product['productStore'] ?? 'Unknown Store',
                        'quantity' => $quantity,
                        'subtotal' => $subtotal,
                        'shippingPrice' => $cheapestPrice,
                        'shippingDetails' => [
                            'shippingId' => intval($cheapestShipping->shippingId),
                            'shippingName' => $shippingName,
                            'maxQuantity' => intval($cheapestShipping->maxQuantity ?? $cheapestShipping->defaultMaxQuantity),
                            'pricePerBatch' => floatval($cheapestShipping->shippingPrice ?? $cheapestShipping->defaultPrice),
                            'batches' => ceil($quantity / intval($cheapestShipping->maxQuantity ?? $cheapestShipping->defaultMaxQuantity)),
                            'province' => $province,
                            'isProvinceSpecific' => $cheapestShipping->isProvinceSpecific ?? false,
                            'pricingType' => ($cheapestShipping->isProvinceSpecific ?? false) ? 'Province-Specific' : 'Default'
                        ]
                    ];
                }
            }

            // Create complete breakdown including access products
            $completeBreakdown = [];

            // Add ship products with shipping details
            foreach ($shippingBreakdown as $item) {
                $completeBreakdown[] = [
                    'productName' => $item['productName'],
                    'variantName' => $item['variantName'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['subtotal'],
                    'productType' => 'ship',
                    'shippingPrice' => $item['shippingPrice'],
                    'shippingDetails' => $item['shippingDetails']
                ];
            }

            // Add access products (no shipping)
            foreach ($accessProducts as $product) {
                $quantity = intval($product['quantity'] ?? 1);
                $price = floatval($product['price'] ?? 0);
                $subtotal = $quantity * $price;

                $completeBreakdown[] = [
                    'productName' => $product['productName'] ?? 'Unknown Product',
                    'variantName' => $product['variantName'] ?? 'Default',
                    'productStore' => $product['productStore'] ?? 'Unknown Store',
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                    'productType' => 'access',
                    'shippingPrice' => 0,
                    'shippingDetails' => null
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'completeBreakdown' => $completeBreakdown,
                    'shippingBreakdown' => $shippingBreakdown,
                    'subtotal' => $totalSubtotal,
                    'totalShipping' => $totalShipping,
                    'total' => $totalSubtotal + $totalShipping,
                    'province' => $province
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error calculating shipping: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if a discount is applicable to the given cart items based on its restrictions.
     *
     * @param  EcomProductDiscount  $discount
     * @param  array  $cartItems  Array of cart items with productId and productStore
     * @return bool
     */
    private function isDiscountApplicableToCart(EcomProductDiscount $discount, array $cartItems): bool
    {
        // If no cart items, discount is not applicable
        if (empty($cartItems)) {
            return false;
        }

        $restrictionType = $discount->restrictionType ?? 'all';

        // If restriction type is 'all', the discount applies to everything
        if ($restrictionType === 'all') {
            return true;
        }

        // Get active restrictions for this discount
        $restrictions = EcomProductDiscountRestriction::where('discountId', $discount->id)
            ->where('deleteStatus', 1)
            ->get();

        // If no restrictions are defined but type is not 'all', discount is not applicable
        if ($restrictions->isEmpty()) {
            return false;
        }

        if ($restrictionType === 'stores') {
            // Get the store IDs from restrictions
            $allowedStoreIds = $restrictions->pluck('storeId')->filter()->toArray();

            if (empty($allowedStoreIds)) {
                return false;
            }

            // Get store names for the allowed store IDs
            $allowedStoreNames = EcomProductStore::whereIn('id', $allowedStoreIds)
                ->where('deleteStatus', 1)
                ->pluck('storeName')
                ->toArray();

            // Check if at least one cart item's store matches the allowed stores
            foreach ($cartItems as $item) {
                $itemStore = $item['productStore'] ?? '';
                if (in_array($itemStore, $allowedStoreNames)) {
                    return true;
                }
            }

            return false;

        } elseif ($restrictionType === 'products') {
            // Get the product IDs from restrictions
            $allowedProductIds = $restrictions->pluck('productId')->filter()->toArray();

            if (empty($allowedProductIds)) {
                return false;
            }

            // Check if at least one cart item's product ID matches the allowed products
            foreach ($cartItems as $item) {
                $itemProductId = $item['productId'] ?? null;
                if ($itemProductId && in_array((int)$itemProductId, $allowedProductIds)) {
                    return true;
                }
            }

            return false;
        }

        return false;
    }

    /**
     * Get all active auto-apply discounts.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAutoApplyDiscounts(Request $request)
    {
        try {
            // Get cart items from request (JSON encoded array)
            $cartItems = $request->get('cartItems', []);
            if (is_string($cartItems)) {
                $cartItems = json_decode($cartItems, true) ?? [];
            }

            $discounts = EcomProductDiscount::active()
                ->enabled()
                ->autoApply()
                ->get()
                ->filter(function ($discount) use ($cartItems) {
                    // Filter out expired discounts
                    if ($discount->isExpired()) {
                        return false;
                    }

                    // Check if discount is applicable based on restrictions
                    return $this->isDiscountApplicableToCart($discount, $cartItems);
                })
                ->map(function ($discount) {
                    return [
                        'id' => $discount->id,
                        'discountName' => $discount->discountName,
                        'discountDescription' => $discount->discountDescription,
                        'discountType' => $discount->discountType,
                        'amountType' => $discount->amountType,
                        'valuePercent' => $discount->valuePercent,
                        'valueAmount' => $discount->valueAmount,
                        'valueReplacement' => $discount->valueReplacement,
                        'discountCapType' => $discount->discountCapType,
                        'discountCapValue' => $discount->discountCapValue,
                        'displayValue' => $discount->getDisplayValue(),
                        'expirationType' => $discount->expirationType,
                        'dateTimeExpiration' => $discount->dateTimeExpiration,
                        'restrictionType' => $discount->restrictionType ?? 'all',
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,
                'data' => $discounts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching discounts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate a discount code and return its details.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateDiscountCode(Request $request)
    {
        $code = $request->get('code');

        if (empty($code)) {
            return response()->json([
                'success' => false,
                'message' => 'Discount code is required'
            ], 400);
        }

        // Get cart items from request (JSON encoded array)
        $cartItems = $request->get('cartItems', []);
        if (is_string($cartItems)) {
            $cartItems = json_decode($cartItems, true) ?? [];
        }

        try {
            $discount = EcomProductDiscount::active()
                ->enabled()
                ->codeBased()
                ->where('discountCode', $code)
                ->first();

            if (!$discount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid discount code'
                ], 404);
            }

            // Check if expired
            if ($discount->isExpired()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This discount code has expired'
                ], 400);
            }

            // Check if discount is applicable based on restrictions
            if (!$this->isDiscountApplicableToCart($discount, $cartItems)) {
                $restrictionType = $discount->restrictionType ?? 'all';
                $message = 'This discount code is not applicable to the products in your cart.';

                if ($restrictionType === 'stores') {
                    $message = 'This discount code is only valid for products from specific stores not in your cart.';
                } elseif ($restrictionType === 'products') {
                    $message = 'This discount code is only valid for specific products not in your cart.';
                }

                return response()->json([
                    'success' => false,
                    'message' => $message
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Discount code applied successfully',
                'data' => [
                    'id' => $discount->id,
                    'discountName' => $discount->discountName,
                    'discountDescription' => $discount->discountDescription,
                    'discountType' => $discount->discountType,
                    'discountCode' => $discount->discountCode,
                    'amountType' => $discount->amountType,
                    'valuePercent' => $discount->valuePercent,
                    'valueAmount' => $discount->valueAmount,
                    'valueReplacement' => $discount->valueReplacement,
                    'discountCapType' => $discount->discountCapType,
                    'discountCapValue' => $discount->discountCapValue,
                    'displayValue' => $discount->getDisplayValue(),
                    'expirationType' => $discount->expirationType,
                    'dateTimeExpiration' => $discount->dateTimeExpiration,
                    'restrictionType' => $discount->restrictionType ?? 'all',
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error validating discount code: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate order total with discounts applied.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function calculateWithDiscounts(Request $request)
    {
        try {
            $subtotal = floatval($request->input('subtotal', 0));
            $shippingTotal = floatval($request->input('shippingTotal', 0));
            $appliedDiscounts = $request->input('appliedDiscounts', []);

            $discountBreakdown = [];
            $totalDiscount = 0;

            foreach ($appliedDiscounts as $discountData) {
                $discountId = $discountData['id'] ?? null;
                if (!$discountId) continue;

                $discount = EcomProductDiscount::active()->enabled()->find($discountId);
                if (!$discount || $discount->isExpired()) continue;

                $discountAmount = 0;

                // Calculate based on amount type
                if ($discount->amountType === 'Percentage') {
                    $discountAmount = ($subtotal * $discount->valuePercent) / 100;
                } elseif ($discount->amountType === 'Specific Amount') {
                    $discountAmount = $discount->valueAmount;
                } elseif ($discount->amountType === 'Price Replacement') {
                    // Price replacement is handled differently - it replaces the total
                    $discountAmount = max(0, $subtotal - $discount->valueReplacement);
                }

                // Apply discount cap if set
                if ($discount->discountCapType !== 'None' && $discount->discountCapValue !== null) {
                    $discountAmount = min($discountAmount, $discount->discountCapValue);
                }

                // Ensure discount doesn't exceed subtotal
                $discountAmount = min($discountAmount, $subtotal - $totalDiscount);
                $discountAmount = max(0, $discountAmount);

                $totalDiscount += $discountAmount;

                $discountBreakdown[] = [
                    'id' => $discount->id,
                    'name' => $discount->discountName,
                    'type' => $discount->amountType,
                    'displayValue' => $discount->getDisplayValue(),
                    'calculatedAmount' => $discountAmount,
                    'trigger' => $discount->discountTrigger,
                    'code' => $discount->discountCode,
                ];
            }

            $grandTotal = max(0, $subtotal - $totalDiscount + $shippingTotal);

            return response()->json([
                'success' => true,
                'data' => [
                    'subtotal' => $subtotal,
                    'shippingTotal' => $shippingTotal,
                    'totalDiscount' => $totalDiscount,
                    'grandTotal' => $grandTotal,
                    'discountBreakdown' => $discountBreakdown,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error calculating discounts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate product prices and availability against current database values.
     * Called before moving from Step 1 to Step 2.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateProductPrices(Request $request)
    {
        try {
            $cartItems = $request->get('cartItems', []);
            if (is_string($cartItems)) {
                $cartItems = json_decode($cartItems, true) ?? [];
            }

            if (empty($cartItems)) {
                return response()->json([
                    'success' => true,
                    'hasChanges' => false,
                    'changes' => []
                ]);
            }

            $changes = [];
            $updatedItems = [];

            foreach ($cartItems as $item) {
                $variantId = $item['variantId'] ?? null;
                $currentPrice = floatval($item['price'] ?? 0);
                $currentQuantity = intval($item['quantity'] ?? 1);

                if (!$variantId) continue;

                // Get fresh variant data from database
                $variant = EcomProductVariant::with('product')
                    ->where('id', $variantId)
                    ->where('deleteStatus', 1)
                    ->first();

                if (!$variant) {
                    // Variant has been deleted
                    $changes[] = [
                        'type' => 'removed',
                        'variantId' => $variantId,
                        'variantName' => $item['variantName'] ?? 'Unknown',
                        'productName' => $item['productName'] ?? 'Unknown',
                        'message' => "Product variant '{$item['variantName']}' is no longer available and will be removed from your cart."
                    ];
                    continue;
                }

                // Check if variant is still active
                if (!$variant->isActive) {
                    $changes[] = [
                        'type' => 'removed',
                        'variantId' => $variantId,
                        'variantName' => $variant->ecomVariantName,
                        'productName' => $variant->product->productName ?? 'Unknown',
                        'message' => "Product variant '{$variant->ecomVariantName}' is no longer active and will be removed from your cart."
                    ];
                    continue;
                }

                // Check if product is still active
                if ($variant->product && (!$variant->product->isActive || $variant->product->deleteStatus != 1)) {
                    $changes[] = [
                        'type' => 'removed',
                        'variantId' => $variantId,
                        'variantName' => $variant->ecomVariantName,
                        'productName' => $variant->product->productName ?? 'Unknown',
                        'message' => "Product '{$variant->product->productName}' is no longer available and will be removed from your cart."
                    ];
                    continue;
                }

                $newPrice = floatval($variant->ecomVariantPrice);
                $availableStock = intval($variant->stocksAvailable);
                $maxOrder = intval($variant->maxOrderPerTransaction) ?: 999999;

                $itemUpdate = [
                    'variantId' => $variantId,
                    'variantName' => $variant->ecomVariantName,
                    'productName' => $variant->product->productName ?? $item['productName'],
                    'productStore' => $variant->product->productStore ?? $item['productStore'],
                    'productType' => $variant->product->productType ?? $item['productType'],
                    'shipCoverage' => $variant->product->shipCoverage ?? $item['shipCoverage'],
                    'price' => $newPrice,
                    'quantity' => $currentQuantity,
                    'stocksAvailable' => $availableStock,
                    'maxOrderPerTransaction' => $maxOrder
                ];

                // Check for price change
                if (abs($newPrice - $currentPrice) > 0.01) {
                    $changes[] = [
                        'type' => 'price_change',
                        'variantId' => $variantId,
                        'variantName' => $variant->ecomVariantName,
                        'productName' => $variant->product->productName ?? 'Unknown',
                        'oldPrice' => $currentPrice,
                        'newPrice' => $newPrice,
                        'message' => "Price of '{$variant->ecomVariantName}' changed from ₱" . number_format($currentPrice, 2) . " to ₱" . number_format($newPrice, 2)
                    ];
                }

                // Check for stock availability
                if ($availableStock < $currentQuantity) {
                    if ($availableStock == 0) {
                        $changes[] = [
                            'type' => 'out_of_stock',
                            'variantId' => $variantId,
                            'variantName' => $variant->ecomVariantName,
                            'productName' => $variant->product->productName ?? 'Unknown',
                            'message' => "'{$variant->ecomVariantName}' is now out of stock and will be removed from your cart."
                        ];
                        continue;
                    } else {
                        $changes[] = [
                            'type' => 'stock_reduced',
                            'variantId' => $variantId,
                            'variantName' => $variant->ecomVariantName,
                            'productName' => $variant->product->productName ?? 'Unknown',
                            'oldQuantity' => $currentQuantity,
                            'newQuantity' => $availableStock,
                            'message' => "Stock for '{$variant->ecomVariantName}' reduced. Quantity adjusted from {$currentQuantity} to {$availableStock}."
                        ];
                        $itemUpdate['quantity'] = $availableStock;
                    }
                }

                // Check max order limit
                if ($currentQuantity > $maxOrder) {
                    $changes[] = [
                        'type' => 'max_order_exceeded',
                        'variantId' => $variantId,
                        'variantName' => $variant->ecomVariantName,
                        'productName' => $variant->product->productName ?? 'Unknown',
                        'oldQuantity' => $currentQuantity,
                        'newQuantity' => $maxOrder,
                        'message' => "Maximum order limit for '{$variant->ecomVariantName}' is {$maxOrder}. Quantity adjusted."
                    ];
                    $itemUpdate['quantity'] = min($itemUpdate['quantity'], $maxOrder);
                }

                $updatedItems[] = $itemUpdate;
            }

            return response()->json([
                'success' => true,
                'hasChanges' => count($changes) > 0,
                'changes' => $changes,
                'updatedItems' => $updatedItems
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error validating products: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate shipping rates against current values.
     * Called before moving from Step 4 to Step 5.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateShippingRates(Request $request)
    {
        try {
            $cartItems = $request->get('cartItems', []);
            $province = $request->get('province', '');
            $currentShipping = floatval($request->get('currentShipping', 0));

            if (is_string($cartItems)) {
                $cartItems = json_decode($cartItems, true) ?? [];
            }

            // Filter only ship products
            $shipProducts = array_filter($cartItems, function($item) {
                $type = strtolower($item['productType'] ?? '');
                return $type === 'ship';
            });

            if (empty($shipProducts)) {
                return response()->json([
                    'success' => true,
                    'hasChanges' => false,
                    'changes' => [],
                    'newShipping' => 0
                ]);
            }

            // Recalculate shipping using the same logic as calculateShipping
            $shippingConfig = DB::table('ecom_shipping_config')
                ->where('deleteStatus', 1)
                ->first();

            if (!$shippingConfig) {
                return response()->json([
                    'success' => true,
                    'hasChanges' => false,
                    'changes' => [],
                    'newShipping' => $currentShipping
                ]);
            }

            // Determine shipping rate
            $newShippingRate = 0;
            $provinceLower = strtolower(trim($province));

            if ($provinceLower === 'pangasinan') {
                $newShippingRate = floatval($shippingConfig->provinceShipPrice ?? 0);
            } else {
                $newShippingRate = floatval($shippingConfig->nationwideShipPrice ?? 0);
            }

            // Calculate total shipping based on products
            $totalShipping = 0;
            foreach ($shipProducts as $product) {
                $quantity = intval($product['quantity'] ?? 1);
                $totalShipping += $newShippingRate * $quantity;
            }

            $changes = [];
            if (abs($totalShipping - $currentShipping) > 0.01) {
                $changes[] = [
                    'type' => 'shipping_change',
                    'oldShipping' => $currentShipping,
                    'newShipping' => $totalShipping,
                    'message' => "Shipping cost has changed from ₱" . number_format($currentShipping, 2) . " to ₱" . number_format($totalShipping, 2)
                ];
            }

            return response()->json([
                'success' => true,
                'hasChanges' => count($changes) > 0,
                'changes' => $changes,
                'newShipping' => $totalShipping
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error validating shipping: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate applied discounts are still valid and applicable.
     * Called before moving from Step 5 to Step 6.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateAppliedDiscounts(Request $request)
    {
        try {
            $appliedDiscounts = $request->get('appliedDiscounts', []);
            $cartItems = $request->get('cartItems', []);

            if (is_string($appliedDiscounts)) {
                $appliedDiscounts = json_decode($appliedDiscounts, true) ?? [];
            }
            if (is_string($cartItems)) {
                $cartItems = json_decode($cartItems, true) ?? [];
            }

            if (empty($appliedDiscounts)) {
                return response()->json([
                    'success' => true,
                    'hasChanges' => false,
                    'changes' => [],
                    'validDiscounts' => [],
                    'removedDiscounts' => []
                ]);
            }

            $changes = [];
            $validDiscounts = [];
            $removedDiscounts = [];

            foreach ($appliedDiscounts as $appliedDiscount) {
                $discountId = $appliedDiscount['id'] ?? null;
                if (!$discountId) continue;

                // Get fresh discount data from database
                $discount = EcomProductDiscount::where('id', $discountId)
                    ->where('deleteStatus', 1)
                    ->first();

                // Check if discount exists and is active
                if (!$discount) {
                    $changes[] = [
                        'type' => 'removed',
                        'discountId' => $discountId,
                        'discountName' => $appliedDiscount['discountName'] ?? 'Unknown',
                        'message' => "Discount '{$appliedDiscount['discountName']}' is no longer available."
                    ];
                    $removedDiscounts[] = $discountId;
                    continue;
                }

                if (!$discount->isActive) {
                    $changes[] = [
                        'type' => 'deactivated',
                        'discountId' => $discountId,
                        'discountName' => $discount->discountName,
                        'message' => "Discount '{$discount->discountName}' has been deactivated."
                    ];
                    $removedDiscounts[] = $discountId;
                    continue;
                }

                // Check if expired
                if ($discount->isExpired()) {
                    $changes[] = [
                        'type' => 'expired',
                        'discountId' => $discountId,
                        'discountName' => $discount->discountName,
                        'message' => "Discount '{$discount->discountName}' has expired."
                    ];
                    $removedDiscounts[] = $discountId;
                    continue;
                }

                // Check if discount is still applicable to cart based on restrictions
                if (!$this->isDiscountApplicableToCart($discount, $cartItems)) {
                    $restrictionType = $discount->restrictionType ?? 'all';
                    $message = "Discount '{$discount->discountName}' no longer applies to your cart items.";

                    if ($restrictionType === 'stores') {
                        $message = "Discount '{$discount->discountName}' is restricted to specific stores not in your cart.";
                    } elseif ($restrictionType === 'products') {
                        $message = "Discount '{$discount->discountName}' is restricted to specific products not in your cart.";
                    }

                    $changes[] = [
                        'type' => 'restriction_mismatch',
                        'discountId' => $discountId,
                        'discountName' => $discount->discountName,
                        'message' => $message
                    ];
                    $removedDiscounts[] = $discountId;
                    continue;
                }

                // Check if discount values have changed
                $oldDisplayValue = $appliedDiscount['displayValue'] ?? '';
                $newDisplayValue = $discount->getDisplayValue();

                $valueChanged = false;
                $oldPercent = floatval($appliedDiscount['valuePercent'] ?? 0);
                $oldAmount = floatval($appliedDiscount['valueAmount'] ?? 0);
                $oldReplacement = floatval($appliedDiscount['valueReplacement'] ?? 0);

                if ($discount->amountType === 'Percentage' && abs($oldPercent - floatval($discount->valuePercent)) > 0.01) {
                    $valueChanged = true;
                } elseif ($discount->amountType === 'Specific Amount' && abs($oldAmount - floatval($discount->valueAmount)) > 0.01) {
                    $valueChanged = true;
                } elseif ($discount->amountType === 'Price Replacement' && abs($oldReplacement - floatval($discount->valueReplacement)) > 0.01) {
                    $valueChanged = true;
                }

                if ($valueChanged) {
                    $changes[] = [
                        'type' => 'value_change',
                        'discountId' => $discountId,
                        'discountName' => $discount->discountName,
                        'oldValue' => $oldDisplayValue,
                        'newValue' => $newDisplayValue,
                        'message' => "Discount '{$discount->discountName}' value changed from {$oldDisplayValue} to {$newDisplayValue}."
                    ];
                }

                // Add to valid discounts with updated values
                $validDiscounts[] = [
                    'id' => $discount->id,
                    'discountName' => $discount->discountName,
                    'discountDescription' => $discount->discountDescription,
                    'discountType' => $discount->discountType,
                    'discountCode' => $discount->discountCode,
                    'amountType' => $discount->amountType,
                    'valuePercent' => $discount->valuePercent,
                    'valueAmount' => $discount->valueAmount,
                    'valueReplacement' => $discount->valueReplacement,
                    'discountCapType' => $discount->discountCapType,
                    'discountCapValue' => $discount->discountCapValue,
                    'displayValue' => $newDisplayValue,
                    'trigger' => $appliedDiscount['trigger'] ?? 'auto',
                    'restrictionType' => $discount->restrictionType ?? 'all',
                ];
            }

            return response()->json([
                'success' => true,
                'hasChanges' => count($changes) > 0,
                'changes' => $changes,
                'validDiscounts' => $validDiscounts,
                'removedDiscounts' => $removedDiscounts
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error validating discounts: ' . $e->getMessage()
            ], 500);
        }
    }
}

