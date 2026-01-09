<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\EcomPackage;
use App\Models\EcomPackageItem;
use App\Models\EcomProduct;
use App\Models\EcomProductVariant;
use App\Models\EcomProductStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class PackagesController extends Controller
{
    /**
     * Display the packages listing page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('ecommerce.packages.index');
    }

    /**
     * Get packages data for DataTables.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getData(Request $request)
    {
        $query = EcomPackage::with('items')
            ->active()
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('packageName')) {
            $query->where('packageName', 'like', '%' . $request->packageName . '%');
        }

        if ($request->filled('packageStatus')) {
            $query->where('packageStatus', $request->packageStatus);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('statusBadge', function ($package) {
                $badgeClass = $package->packageStatus === 'active' ? 'bg-success' : 'bg-secondary';
                return '<span class="badge ' . $badgeClass . '">' . ucfirst($package->packageStatus) . '</span>';
            })
            ->addColumn('itemCount', function ($package) {
                return $package->items->count();
            })
            ->addColumn('formatted_calculated_price', function ($package) {
                return '₱' . number_format($package->calculatedPrice, 2);
            })
            ->addColumn('formatted_package_price', function ($package) {
                return '₱' . number_format($package->packagePrice, 2);
            })
            ->addColumn('discount_info', function ($package) {
                if ($package->calculatedPrice > $package->packagePrice && $package->packagePrice > 0) {
                    $discount = $package->calculatedPrice - $package->packagePrice;
                    $percentage = round(($discount / $package->calculatedPrice) * 100, 1);
                    return '<span class="text-success">-₱' . number_format($discount, 2) . ' (' . $percentage . '%)</span>';
                }
                return '<span class="text-secondary">No discount</span>';
            })
            ->addColumn('formatted_date', function ($package) {
                return $package->created_at ? $package->created_at->format('M j, Y') : '';
            })
            ->addColumn('action', function ($package) {
                return ''; // Actions are rendered client-side
            })
            ->rawColumns(['statusBadge', 'discount_info', 'action'])
            ->make(true);
    }

    /**
     * Show the form for creating a new package.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        // Get active stores
        $stores = EcomProductStore::active()->enabled()->orderBy('storeName')->get();

        return view('ecommerce.packages.create', compact('stores'));
    }

    /**
     * Store a newly created package in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'packageName' => 'required|string|max:255',
            'packageDescription' => 'nullable|string',
            'packagePrice' => 'required|numeric|min:0',
            'packageStatus' => 'required|in:active,inactive',
            'items' => 'required|array|min:1',
            'items.*.variantId' => 'required|integer|exists:ecom_products_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
        ], [
            'packageName.required' => 'Package name is required.',
            'packagePrice.required' => 'Package price is required.',
            'items.required' => 'At least one product must be added to the package.',
            'items.min' => 'At least one product must be added to the package.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Calculate total price from items
            $calculatedPrice = 0;
            $itemsData = [];

            foreach ($request->items as $item) {
                $variant = EcomProductVariant::with('product')->find($item['variantId']);
                if (!$variant) {
                    continue;
                }

                $unitPrice = $variant->ecomVariantPrice ?? 0;
                $quantity = $item['quantity'];
                $subtotal = $unitPrice * $quantity;
                $calculatedPrice += $subtotal;

                // Get store name from product
                $storeName = $variant->product->productStore ?? null;

                $itemsData[] = [
                    'productId' => $variant->ecomProductsId,
                    'variantId' => $variant->id,
                    'productName' => $variant->product->productName ?? 'Unknown Product',
                    'variantName' => $variant->ecomVariantName ?? 'Default',
                    'variantSku' => null, // Add if available in your schema
                    'storeName' => $storeName,
                    'unitPrice' => $unitPrice,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                ];
            }

            // Create the package
            $package = EcomPackage::create([
                'packageName' => $request->packageName,
                'packageDescription' => $request->packageDescription,
                'calculatedPrice' => $calculatedPrice,
                'packagePrice' => $request->packagePrice,
                'packageStatus' => $request->packageStatus,
                'usersId' => Auth::id(),
                'deleteStatus' => 1,
            ]);

            // Create package items
            foreach ($itemsData as $itemData) {
                EcomPackageItem::create(array_merge($itemData, [
                    'packageId' => $package->id,
                    'deleteStatus' => 1,
                ]));
            }

            DB::commit();

            Log::info('Package created', [
                'package_id' => $package->id,
                'package_name' => $package->packageName,
                'created_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Package created successfully!',
                'packageId' => $package->id,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create package', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create package: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing a package.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function edit(Request $request)
    {
        $id = $request->query('id');
        if (!$id) {
            return redirect()->route('ecom-packages')->with('error', 'Package ID is required');
        }

        $package = EcomPackage::with('items')->active()->findOrFail($id);
        $stores = EcomProductStore::active()->enabled()->orderBy('storeName')->get();

        return view('ecommerce.packages.edit', compact('package', 'stores'));
    }

    /**
     * Update the specified package in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'packageName' => 'required|string|max:255',
            'packageDescription' => 'nullable|string',
            'packagePrice' => 'required|numeric|min:0',
            'packageStatus' => 'required|in:active,inactive',
            'items' => 'required|array|min:1',
            'items.*.variantId' => 'required|integer|exists:ecom_products_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
        ], [
            'packageName.required' => 'Package name is required.',
            'packagePrice.required' => 'Package price is required.',
            'items.required' => 'At least one product must be added to the package.',
            'items.min' => 'At least one product must be added to the package.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $package = EcomPackage::active()->findOrFail($id);

            DB::beginTransaction();

            // Calculate total price from items
            $calculatedPrice = 0;
            $itemsData = [];

            foreach ($request->items as $item) {
                $variant = EcomProductVariant::with('product')->find($item['variantId']);
                if (!$variant) {
                    continue;
                }

                $unitPrice = $variant->ecomVariantPrice ?? 0;
                $quantity = $item['quantity'];
                $subtotal = $unitPrice * $quantity;
                $calculatedPrice += $subtotal;

                // Get store name from product
                $storeName = $variant->product->productStore ?? null;

                $itemsData[] = [
                    'productId' => $variant->ecomProductsId,
                    'variantId' => $variant->id,
                    'productName' => $variant->product->productName ?? 'Unknown Product',
                    'variantName' => $variant->ecomVariantName ?? 'Default',
                    'variantSku' => null,
                    'storeName' => $storeName,
                    'unitPrice' => $unitPrice,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                ];
            }

            // Update the package
            $package->update([
                'packageName' => $request->packageName,
                'packageDescription' => $request->packageDescription,
                'calculatedPrice' => $calculatedPrice,
                'packagePrice' => $request->packagePrice,
                'packageStatus' => $request->packageStatus,
            ]);

            // Soft delete existing items
            EcomPackageItem::where('packageId', $package->id)
                ->update(['deleteStatus' => 0]);

            // Create new package items
            foreach ($itemsData as $itemData) {
                EcomPackageItem::create(array_merge($itemData, [
                    'packageId' => $package->id,
                    'deleteStatus' => 1,
                ]));
            }

            DB::commit();

            Log::info('Package updated', [
                'package_id' => $package->id,
                'package_name' => $package->packageName,
                'updated_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Package updated successfully!',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update package', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update package: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Soft delete a package.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $package = EcomPackage::active()->findOrFail($id);

            $package->update(['deleteStatus' => 0]);

            // Also soft delete items
            EcomPackageItem::where('packageId', $id)
                ->update(['deleteStatus' => 0]);

            Log::info('Package deleted', [
                'package_id' => $id,
                'deleted_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Package deleted successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete package', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete package'
            ], 500);
        }
    }

    /**
     * Toggle package status.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus($id)
    {
        try {
            $package = EcomPackage::active()->findOrFail($id);

            $newStatus = $package->packageStatus === 'active' ? 'inactive' : 'active';
            $package->update(['packageStatus' => $newStatus]);

            return response()->json([
                'success' => true,
                'message' => 'Package status updated to ' . ucfirst($newStatus),
                'newStatus' => $newStatus
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update package status'
            ], 500);
        }
    }

    /**
     * Get products for selection (with variants).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProducts(Request $request)
    {
        try {
            // Get active store names
            $activeStoreNames = EcomProductStore::active()->enabled()->pluck('storeName')->toArray();

            $query = EcomProduct::active()
                ->whereIn('productStore', $activeStoreNames)
                ->with(['variants' => function($q) {
                    $q->active()->where('isActive', 1)->with('firstImage');
                }]);

            // Filter by search term
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('productName', 'like', '%' . $search . '%')
                      ->orWhere('productStore', 'like', '%' . $search . '%');
                });
            }

            // Filter by store
            if ($request->filled('store')) {
                $query->where('productStore', $request->store);
            }

            $products = $query->orderBy('productName')->get();

            $result = [];
            foreach ($products as $product) {
                $productVariants = [];
                foreach ($product->variants as $variant) {
                    // Get first image if available - imageLink contains full path like /storage/uploads/variants/...
                    $image = $variant->firstImage;
                    $imageUrl = null;
                    if ($image && $image->imageLink) {
                        // imageLink already has path like /storage/uploads/variants/xxx.jpg
                        // Just need to make it a full URL
                        $imageUrl = url($image->imageLink);
                    }

                    $productVariants[] = [
                        'variantId' => $variant->id,
                        'variantName' => $variant->ecomVariantName,
                        'variantPrice' => floatval($variant->ecomVariantPrice),
                        'stocksAvailable' => $variant->stocksAvailable,
                        'imageUrl' => $imageUrl,
                        'productType' => $product->productType, // Include product type for each variant
                    ];
                }

                if (!empty($productVariants)) {
                    $result[] = [
                        'productId' => $product->id,
                        'productName' => $product->productName,
                        'productStore' => $product->productStore,
                        'productType' => $product->productType,
                        'variants' => $productVariants,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'products' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching products', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching products'
            ], 500);
        }
    }

    /**
     * Get package details.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPackageDetails($id)
    {
        try {
            $package = EcomPackage::with('items')->active()->findOrFail($id);

            // Format items with variant images
            $items = $package->items->map(function($item) {
                $variant = EcomProductVariant::with('firstImage')->find($item->variantId);
                $imageUrl = null;
                if ($variant && $variant->firstImage) {
                    $imageUrl = asset('images/products/' . $variant->firstImage->imagePath);
                }

                return [
                    'id' => $item->id,
                    'productId' => $item->productId,
                    'variantId' => $item->variantId,
                    'productName' => $item->productName,
                    'variantName' => $item->variantName,
                    'storeName' => $item->storeName,
                    'unitPrice' => floatval($item->unitPrice),
                    'quantity' => $item->quantity,
                    'subtotal' => floatval($item->subtotal),
                    'imageUrl' => $imageUrl,
                ];
            });

            return response()->json([
                'success' => true,
                'package' => [
                    'id' => $package->id,
                    'packageName' => $package->packageName,
                    'packageDescription' => $package->packageDescription,
                    'calculatedPrice' => floatval($package->calculatedPrice),
                    'packagePrice' => floatval($package->packagePrice),
                    'packageStatus' => $package->packageStatus,
                    'items' => $items,
                    'createdAt' => $package->created_at ? $package->created_at->format('M j, Y g:i A') : '',
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching package details', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching package details'
            ], 500);
        }
    }
}
