<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\EcomProductDiscount;
use App\Models\EcomProductDiscountRestriction;
use App\Models\EcomProductStore;
use App\Models\EcomProduct;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class DiscountsController extends Controller
{
    /**
     * Display the discounts page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('ecommerce.discounts.index');
    }

    /**
     * Show the form for creating a new discount.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('ecommerce.discounts.create');
    }

    /**
     * Store a newly created discount in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        try {
            // Prepare data for creation
            $discountData = [
                'discountName' => $request->discountName,
                'discountDescription' => $request->discountDescription,
                'discountType' => $request->discountType,
                'discountTrigger' => $request->discountTrigger,
                'discountCode' => $request->discountCode,
                'amountType' => $request->amountType,
                'valuePercent' => $request->valuePercent,
                'valueAmount' => $request->valueAmount,
                'valueReplacement' => $request->valueReplacement,
                'discountCapType' => $request->discountCapType,
                'discountCapValue' => $request->discountCapValue,
                'usageLimit' => $request->usageLimit,
                'expirationType' => $request->expirationType,
                'timerCountdown' => $request->countdownMinutes,
                'isActive' => 0,
                'deleteStatus' => 1,
            ];

            // Combine date and time expiration if expirationType is "Time and Date"
            if ($request->expirationType === 'Time and Date' && $request->dateExpiration && $request->timeExpiration) {
                // Parse the date and time
                $dateExpiration = \Carbon\Carbon::parse($request->dateExpiration);
                $timeExpiration = \Carbon\Carbon::parse($request->timeExpiration);

                // Combine date and time
                $dateTimeExpiration = $dateExpiration->format('Y-m-d') . ' ' . $timeExpiration->format('H:i:s');
                $discountData['dateTimeExpiration'] = $dateTimeExpiration;
            } else {
                $discountData['dateTimeExpiration'] = null;
            }

            // Create the discount
            EcomProductDiscount::create($discountData);

            return redirect()->route('ecom-discounts')
                ->with('success', 'Discount has been added successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'An error occurred while saving the discount. Please try again.');
        }
    }

    /**
     * Show the form for editing a discount.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function edit(Request $request)
    {
        $discountId = $request->get('id');

        // Get the discount details
        $discount = EcomProductDiscount::active()->find($discountId);

        if (!$discount) {
            return redirect()->route('ecom-discounts')
                ->with('error', 'Discount not found.');
        }

        return view('ecommerce.discounts.edit', compact('discount'));
    }

    /**
     * Update the specified discount in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        try {
            $discountId = $request->discountId;

            // Find the discount
            $discount = EcomProductDiscount::active()->findOrFail($discountId);

            // Prepare data for update
            $discountData = [
                'discountName' => $request->discountName,
                'discountDescription' => $request->discountDescription,
                'discountType' => $request->discountType,
                'discountTrigger' => $request->discountTrigger,
                'amountType' => $request->amountType,
                'discountCapType' => $request->discountCapType,
                'usageLimit' => $request->usageLimit,
                'expirationType' => $request->expirationType,
            ];

            // Handle conditional fields - clear if hidden
            // Discount Code (only if trigger is "Discount Code")
            if ($request->discountTrigger === 'Discount Code') {
                $discountData['discountCode'] = $request->discountCode;
            } else {
                $discountData['discountCode'] = null;
            }

            // Value fields (based on Amount Type)
            if ($request->amountType === 'Percentage') {
                $discountData['valuePercent'] = $request->valuePercent;
                $discountData['valueAmount'] = null;
                $discountData['valueReplacement'] = null;
            } elseif ($request->amountType === 'Specific Amount') {
                $discountData['valuePercent'] = null;
                $discountData['valueAmount'] = $request->valueAmount;
                $discountData['valueReplacement'] = null;
            } elseif ($request->amountType === 'Price Replacement') {
                $discountData['valuePercent'] = null;
                $discountData['valueAmount'] = null;
                $discountData['valueReplacement'] = $request->valueReplacement;
            } else {
                $discountData['valuePercent'] = null;
                $discountData['valueAmount'] = null;
                $discountData['valueReplacement'] = null;
            }

            // Discount Cap Value (only if cap type is not "None")
            if ($request->discountCapType !== 'None' && $request->discountCapType) {
                $discountData['discountCapValue'] = $request->discountCapValue;
            } else {
                $discountData['discountCapValue'] = null;
            }

            // Expiration fields (based on Expiration Type)
            if ($request->expirationType === 'Time and Date' && $request->dateExpiration && $request->timeExpiration) {
                // Parse the date and time
                $dateExpiration = \Carbon\Carbon::parse($request->dateExpiration);
                $timeExpiration = \Carbon\Carbon::parse($request->timeExpiration);

                // Combine date and time
                $dateTimeExpiration = $dateExpiration->format('Y-m-d') . ' ' . $timeExpiration->format('H:i:s');
                $discountData['dateTimeExpiration'] = $dateTimeExpiration;
                $discountData['timerCountdown'] = null;
            } elseif ($request->expirationType === 'Countdown') {
                $discountData['dateTimeExpiration'] = null;
                $discountData['timerCountdown'] = $request->countdownMinutes;
            } else {
                $discountData['dateTimeExpiration'] = null;
                $discountData['timerCountdown'] = null;
            }

            // Update the discount
            $discount->update($discountData);

            return redirect()->route('ecom-discounts')
                ->with('success', 'Discount has been updated successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'An error occurred while updating the discount. Please try again.');
        }
    }

    /**
     * Update the status of a discount.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            // Validate the request
            $request->validate([
                'isActive' => 'required|in:0,1',
            ], [
                'isActive.required' => 'Status is required.',
                'isActive.in' => 'Status must be either Active or Inactive.',
            ]);

            // Find the discount
            $discount = EcomProductDiscount::active()->findOrFail($id);

            // Update the status
            $discount->update(['isActive' => $request->isActive]);

            $statusText = $request->isActive ? 'Active' : 'Inactive';

            return response()->json([
                'success' => true,
                'message' => 'Discount status has been updated to ' . $statusText . ' successfully!',
                'status' => $request->isActive,
                'statusText' => $statusText
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the discount status. Please try again.'
            ], 500);
        }
    }

    /**
     * Get discount details.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $discount = EcomProductDiscount::active()->findOrFail($id);

            // Format the discount details
            $details = [];

            // Basic details
            if ($discount->discountName) {
                $details['Discount Name'] = $discount->discountName;
            }

            if ($discount->discountDescription) {
                $details['Description'] = $discount->discountDescription;
            }

            if ($discount->discountType) {
                $details['Discount Type'] = $discount->discountType;
            }

            if ($discount->discountTrigger) {
                $details['Discount Trigger'] = $discount->discountTrigger;
            }

            if ($discount->discountCode) {
                $details['Discount Code'] = $discount->discountCode;
            }

            if ($discount->amountType) {
                $details['Amount Type'] = $discount->amountType;
            }

            // Value fields
            if ($discount->valuePercent !== null) {
                $details['Value Percent'] = $discount->valuePercent . '%';
            }

            if ($discount->valueAmount !== null) {
                $details['Value Amount'] = 'Php ' . number_format($discount->valueAmount, 2);
            }

            if ($discount->valueReplacement !== null) {
                $details['Value Replacement'] = 'Php ' . number_format($discount->valueReplacement, 2);
            }

            if ($discount->discountCapType) {
                $details['Discount Cap Type'] = $discount->discountCapType;
            }

            if ($discount->discountCapValue !== null) {
                $details['Discount Cap Value'] = 'Php ' . number_format($discount->discountCapValue, 2);
            }

            if ($discount->usageLimit !== null) {
                $details['Usage Limit'] = $discount->usageLimit;
            }

            if ($discount->expirationType) {
                $details['Expiration Type'] = $discount->expirationType;
            }

            if ($discount->dateTimeExpiration) {
                $details['Date & Time Expiration'] = \Carbon\Carbon::parse($discount->dateTimeExpiration)->format('F j, Y g:ia');
            }

            if ($discount->timerCountdown !== null) {
                $details['Countdown Minutes'] = $discount->timerCountdown;
            }

            // Status
            $details['Status'] = $discount->isActive ? 'Active' : 'Inactive';

            // Timestamps
            if ($discount->created_at) {
                $details['Date Created'] = \Carbon\Carbon::parse($discount->created_at)->format('F j, Y g:ia');
            }

            if ($discount->updated_at) {
                $details['Last Updated'] = \Carbon\Carbon::parse($discount->updated_at)->format('F j, Y g:ia');
            }

            return response()->json([
                'success' => true,
                'discount' => $discount,
                'details' => $details
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Discount not found.'
            ], 404);
        }
    }

    /**
     * Soft delete a discount by setting deleteStatus to 0.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $discount = EcomProductDiscount::findOrFail($id);
            $discount->update(['deleteStatus' => 0]);

            return response()->json([
                'success' => true,
                'message' => 'Discount "' . $discount->discountName . '" has been deleted successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the discount. Please try again.'
            ], 500);
        }
    }

    /**
     * Get discounts data for DataTables.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getData(Request $request)
    {
        if ($request->ajax()) {
            try {
                $discounts = EcomProductDiscount::active()
                    ->orderBy('created_at', 'desc')
                    ->get();

                return DataTables::of($discounts)
                    ->addColumn('value', function($discount) {
                        if ($discount->amountType === 'Percentage' && $discount->valuePercent !== null) {
                            return '<span class="badge bg-info">' . $discount->valuePercent . '%</span>';
                        } elseif ($discount->amountType === 'Specific Amount' && $discount->valueAmount !== null) {
                            return '<span class="badge bg-success">Php ' . number_format($discount->valueAmount, 2) . '</span>';
                        } elseif ($discount->amountType === 'Price Replacement' && $discount->valueReplacement !== null) {
                            return '<span class="badge bg-warning">Php ' . number_format($discount->valueReplacement, 2) . '</span>';
                        } else {
                            return '<span class="badge bg-secondary">N/A</span>';
                        }
                    })
                    ->addColumn('active', function($discount) {
                        if ($discount->isActive) {
                            return '<span class="badge bg-success">Yes</span>';
                        } else {
                            return '<span class="badge bg-danger">No</span>';
                        }
                    })
                    ->addColumn('action', function($discount) {
                        $editUrl = route('ecom-discounts.edit', ['id' => $discount->id]);
                        $restrictionsUrl = route('ecom-discounts.restrictions', ['id' => $discount->id]);
                        $discountName = htmlspecialchars($discount->discountName ?? '', ENT_QUOTES, 'UTF-8');
                        return '
                            <div class="d-flex flex-wrap gap-1 justify-content-center">
                                <button type="button"
                                        class="btn btn-sm btn-outline-info badge-style details-btn"
                                        title="Details"
                                        data-discount-id="' . $discount->id . '"
                                        data-discount-name="' . $discountName . '">
                                    <i class="bx bx-info-circle me-1"></i>Details
                                </button>

                                <a href="' . $editUrl . '"
                                   class="btn btn-sm btn-outline-success badge-style"
                                   title="Edit">
                                    <i class="bx bx-edit me-1"></i>Edit
                                </a>

                                <a href="' . $restrictionsUrl . '"
                                   class="btn btn-sm btn-outline-warning badge-style"
                                   title="Restrictions">
                                    <i class="bx bx-filter me-1"></i>Restrictions
                                </a>

                                <button type="button"
                                        class="btn btn-sm btn-outline-primary badge-style status-btn"
                                        title="Status"
                                        data-discount-id="' . $discount->id . '"
                                        data-discount-name="' . $discountName . '"
                                        data-current-status="' . ($discount->isActive ? 1 : 0) . '">
                                    <i class="bx bx-toggle-right me-1"></i>Status
                                </button>

                                <button type="button"
                                        class="btn btn-sm btn-outline-danger badge-style delete-btn"
                                        title="Delete"
                                        data-discount-id="' . $discount->id . '"
                                        data-discount-name="' . $discountName . '">
                                    <i class="bx bx-trash me-1"></i>Delete
                                </button>
                            </div>
                        ';
                    })
                    ->rawColumns(['value', 'active', 'action'])
                    ->make(true);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('DataTables Error: ' . $e->getMessage());
                return response()->json([
                    'error' => 'An error occurred while loading data.'
                ], 500);
            }
        }
    }

    /**
     * Display the restrictions page for a discount.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function restrictions(Request $request)
    {
        $discountId = $request->get('id');

        $discount = EcomProductDiscount::active()->find($discountId);

        if (!$discount) {
            return redirect()->route('ecom-discounts')
                ->with('error', 'Discount not found.');
        }

        // Get existing restrictions with related data
        $existingRestrictions = EcomProductDiscountRestriction::active()
            ->where('discountId', $discountId)
            ->with(['store', 'product'])
            ->get();

        // Get all active stores for dropdown
        $stores = EcomProductStore::active()->enabled()->orderBy('storeName')->get();

        return view('ecommerce.discounts.restrictions', compact('discount', 'existingRestrictions', 'stores'));
    }

    /**
     * Search for stores (AJAX endpoint for dynamic search with pagination).
     *
     * @param  \Illuminate\Http\Request  $request
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchProducts(Request $request)
    {
        try {
            $search = $request->get('search', '');
            $storeId = $request->get('store_id');
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 20);

            // Only show active and non-deleted products
            $query = EcomProduct::where('deleteStatus', 1)
                ->where('isActive', 1);

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
     * @param  int  $productId
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

    /**
     * Save restrictions for a discount.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveRestrictions(Request $request, $id)
    {
        try {
            $discount = EcomProductDiscount::active()->findOrFail($id);

            $restrictionType = $request->get('restrictionType', 'all');
            $storeIds = $request->get('storeIds', []);
            $productIds = $request->get('productIds', []);

            // Update the discount's restriction type
            $discount->update(['restrictionType' => $restrictionType]);

            // Soft delete all existing restrictions
            EcomProductDiscountRestriction::where('discountId', $id)
                ->update(['deleteStatus' => 0]);

            // Create new restrictions based on type
            if ($restrictionType === 'stores' && !empty($storeIds)) {
                foreach ($storeIds as $storeId) {
                    EcomProductDiscountRestriction::create([
                        'discountId' => $id,
                        'storeId' => $storeId,
                        'productId' => null,
                        'deleteStatus' => 1
                    ]);
                }
            } elseif ($restrictionType === 'products' && !empty($productIds)) {
                foreach ($productIds as $productId) {
                    EcomProductDiscountRestriction::create([
                        'discountId' => $id,
                        'storeId' => null,
                        'productId' => $productId,
                        'deleteStatus' => 1
                    ]);
                }
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
     * Get current restrictions for a discount (AJAX).
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRestrictions($id)
    {
        try {
            $discount = EcomProductDiscount::active()->findOrFail($id);

            $restrictions = EcomProductDiscountRestriction::active()
                ->where('discountId', $id)
                ->with(['store', 'product'])
                ->get();

            $formattedRestrictions = $restrictions->map(function($restriction) {
                $data = [
                    'id' => $restriction->id,
                    'type' => $restriction->storeId ? 'store' : 'product'
                ];

                if ($restriction->store) {
                    $data['storeId'] = $restriction->storeId;
                    $data['storeName'] = $restriction->store->storeName;
                }

                if ($restriction->product) {
                    $data['productId'] = $restriction->productId;
                    $data['productName'] = $restriction->product->productName;
                    $data['productStore'] = $restriction->product->productStore ?? '';
                    $data['productPrice'] = '₱' . number_format($restriction->product->productPrice ?? 0, 2);
                }

                return $data;
            });

            return response()->json([
                'success' => true,
                'restrictionType' => $discount->restrictionType ?? 'all',
                'restrictions' => $formattedRestrictions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching restrictions: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Remove a single restriction.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeRestriction($id)
    {
        try {
            $restriction = EcomProductDiscountRestriction::findOrFail($id);
            $restriction->update(['deleteStatus' => 0]);

            return response()->json([
                'success' => true,
                'message' => 'Restriction has been removed.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error removing restriction: ' . $e->getMessage()
            ], 500);
        }
    }
}

