<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\EcomProduct;
use App\Models\EcomProductVariant;
use App\Models\EcomProductStore;
use App\Models\EcomProductsShipping;
use App\Models\EcomProductsShippingOptions;
use App\Models\EcomProductsVariantsShipping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductsController extends Controller
{
    /**
     * Display the products page.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Get active store names (only stores with isActive = 1)
        $activeStoreNames = EcomProductStore::active()->enabled()->pluck('storeName')->toArray();

        $query = EcomProduct::active()
            ->whereIn('productStore', $activeStoreNames);

        // Apply filters
        if ($request->filled('name')) {
            $query->filterByName($request->name);
        }

        if ($request->filled('store')) {
            $query->filterByStore($request->store);
        }

        if ($request->filled('productType')) {
            $query->filterByProductType($request->productType);
        }

        // Get paginated results
        $products = $query->orderBy('created_at', 'desc')->paginate(10);

        // Get unique stores for filter dropdown (only from active stores)
        $stores = EcomProduct::active()
            ->whereIn('productStore', $activeStoreNames)
            ->distinct()
            ->pluck('productStore')
            ->filter();

        // Get unique product types for filter dropdown
        $productTypes = EcomProduct::active()
            ->whereIn('productStore', $activeStoreNames)
            ->distinct()
            ->pluck('productType')
            ->filter();

        return view('ecommerce.products.index', compact('products', 'stores', 'productTypes'));
    }

    /**
     * Show the form for creating a new product.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        // Get active and enabled stores for the dropdown
        $stores = EcomProductStore::active()->enabled()->orderBy('storeName')->get();

        return view('ecommerce.products.create', compact('stores'));
    }

    /**
     * Store a newly created product in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Validate the request
        $validationRules = [
            'productName' => 'required|string|max:255',
            'productStore' => 'required|string|max:255',
            'productType' => 'required|string|in:access,ship',
            'productDescription' => 'required|string',
        ];

        // Only validate shipCoverage if productType is 'ship'
        if ($request->productType === 'ship') {
            $validationRules['shipCoverage'] = 'required|string|in:Town,Province,Region,National';
        }

        $request->validate($validationRules, [
            'productName.required' => 'Product name is required.',
            'productStore.required' => 'Product store is required.',
            'productType.required' => 'Product type is required.',
            'productType.in' => 'Product type must be either access or ship.',
            'productDescription.required' => 'Product description is required.',
            'shipCoverage.required' => 'Shipping coverage is required for ship products.',
            'shipCoverage.in' => 'Shipping coverage must be one of: Town, Province, Region, National.',
        ]);

        try {
            // Prepare data for creation
            $productData = [
                'productName' => $request->productName,
                'productStore' => $request->productStore,
                'productType' => $request->productType,
                'productDescription' => $request->productDescription,
                'isActive' => 1,
                'deleteStatus' => 1,
            ];

            // Add shipCoverage only if product type is 'ship', otherwise set to 'n/a'
            if ($request->productType === 'ship') {
                $productData['shipCoverage'] = $request->shipCoverage;
            } else {
                $productData['shipCoverage'] = 'n/a';
            }

            // Create the product
            EcomProduct::create($productData);

            return redirect()->route('ecom-products')
                ->with('success', 'Product has been added successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'An error occurred while saving the product. Please try again.');
        }
    }

    /**
     * Soft delete a product by setting deleteStatus to 0.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $product = EcomProduct::findOrFail($id);
            $product->update(['deleteStatus' => 0]);

            return response()->json([
                'success' => true,
                'message' => 'Product "' . $product->productName . '" has been deleted successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the product. Please try again.'
            ], 500);
        }
    }

    /**
     * Display the variants page for a specific product.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function variants(Request $request)
    {
        $productId = $request->get('id');

        // Get the product details
        $product = EcomProduct::active()->find($productId);

        if (!$product) {
            return redirect()->route('ecom-products')
                ->with('error', 'Product not found.');
        }

        // Get variants for this product
        $variants = EcomProductVariant::active()
            ->byProduct($productId)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('ecommerce.products.variants', compact('product', 'variants'));
    }

    /**
     * Show the form for creating a new variant.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function createVariant(Request $request)
    {
        $productId = $request->get('id');

        // Get the product details
        $product = EcomProduct::active()->find($productId);

        if (!$product) {
            return redirect()->route('ecom-products')
                ->with('error', 'Product not found.');
        }

        return view('ecommerce.products.variants.create', compact('product'));
    }

    /**
     * Store a newly created variant in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeVariant(Request $request)
    {
        // Validate the request
        $request->validate([
            'ecomProductsId' => 'required|exists:ecom_products,id',
            'ecomVariantName' => 'required|string|max:255',
            'ecomVariantDescription' => 'required|string|max:1000',
            'ecomVariantPrice' => 'required|numeric|min:0',
            'costPrice' => 'nullable|numeric|min:0',
            'affiliatePrice' => 'required|numeric|min:0',
            'stocksAvailable' => 'required|integer|min:0',
            'maxOrderPerTransaction' => 'required|integer|min:1',
        ], [
            'ecomProductsId.required' => 'Product ID is required.',
            'ecomProductsId.exists' => 'Selected product does not exist.',
            'ecomVariantName.required' => 'Variant name is required.',
            'ecomVariantDescription.required' => 'Variant description is required.',
            'ecomVariantPrice.required' => 'Variant price is required.',
            'ecomVariantPrice.numeric' => 'Variant price must be a valid number.',
            'ecomVariantPrice.min' => 'Variant price must be greater than or equal to 0.',
            'costPrice.numeric' => 'Cost price must be a valid number.',
            'costPrice.min' => 'Cost price must be greater than or equal to 0.',
            'affiliatePrice.required' => 'Affiliate price is required.',
            'affiliatePrice.numeric' => 'Affiliate price must be a valid number.',
            'affiliatePrice.min' => 'Affiliate price must be greater than or equal to 0.',
            'stocksAvailable.required' => 'Stocks available is required.',
            'stocksAvailable.integer' => 'Stocks available must be a whole number.',
            'stocksAvailable.min' => 'Stocks available must be greater than or equal to 0.',
            'maxOrderPerTransaction.required' => 'Maximum order per transaction is required.',
            'maxOrderPerTransaction.integer' => 'Maximum order per transaction must be a whole number.',
            'maxOrderPerTransaction.min' => 'Maximum order per transaction must be at least 1.',
        ]);

        try {
            // Create the variant with specified field mappings
            EcomProductVariant::create([
                'ecomProductsId' => $request->ecomProductsId,
                'ecomVariantName' => $request->ecomVariantName,
                'ecomVariantDescription' => $request->ecomVariantDescription,
                'ecomVariantPrice' => $request->ecomVariantPrice,
                'costPrice' => $request->costPrice ?? 0,
                'affiliatePrice' => $request->affiliatePrice,
                'stocksAvailable' => $request->stocksAvailable,
                'maxOrderPerTransaction' => $request->maxOrderPerTransaction,
                'isActive' => 0,
                'deleteStatus' => 1,
            ]);

            return redirect()->route('ecom-products.variants', ['id' => $request->ecomProductsId])
                ->with('success', 'Variant has been added successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'An error occurred while saving the variant. Please try again.');
        }
    }

    /**
     * Show the form for editing a variant.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function editVariant(Request $request)
    {
        $variantId = $request->get('id');

        // Get the variant details
        $variant = EcomProductVariant::active()->find($variantId);

        if (!$variant) {
            return redirect()->route('ecom-products')
                ->with('error', 'Variant not found.');
        }

        // Get the product details
        $product = EcomProduct::active()->find($variant->ecomProductsId);

        if (!$product) {
            return redirect()->route('ecom-products')
                ->with('error', 'Product not found.');
        }

        return view('ecommerce.products.variants.edit', compact('variant', 'product'));
    }

    /**
     * Update a variant in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateVariant(Request $request)
    {
        // Validate the request
        $request->validate([
            'variantId' => 'required|exists:ecom_products_variants,id',
            'ecomVariantName' => 'required|string|max:255',
            'ecomVariantDescription' => 'required|string|max:1000',
            'ecomVariantPrice' => 'required|numeric|min:0',
            'rawPrice' => 'required|numeric|min:0',
            'costPrice' => 'nullable|numeric|min:0',
            'affiliatePrice' => 'required|numeric|min:0',
            'stocksAvailable' => 'required|integer|min:0',
            'maxOrderPerTransaction' => 'required|integer|min:1',
        ], [
            'variantId.required' => 'Variant ID is required.',
            'variantId.exists' => 'Selected variant does not exist.',
            'ecomVariantName.required' => 'Variant name is required.',
            'ecomVariantDescription.required' => 'Variant description is required.',
            'ecomVariantPrice.required' => 'Variant price is required.',
            'ecomVariantPrice.numeric' => 'Variant price must be a valid number.',
            'ecomVariantPrice.min' => 'Variant price must be greater than or equal to 0.',
            'rawPrice.required' => 'Raw price is required.',
            'rawPrice.numeric' => 'Raw price must be a valid number.',
            'rawPrice.min' => 'Raw price must be greater than or equal to 0.',
            'costPrice.numeric' => 'Cost price must be a valid number.',
            'costPrice.min' => 'Cost price must be greater than or equal to 0.',
            'affiliatePrice.required' => 'Affiliate price is required.',
            'affiliatePrice.numeric' => 'Affiliate price must be a valid number.',
            'affiliatePrice.min' => 'Affiliate price must be greater than or equal to 0.',
            'stocksAvailable.required' => 'Stocks available is required.',
            'stocksAvailable.integer' => 'Stocks available must be a whole number.',
            'stocksAvailable.min' => 'Stocks available must be greater than or equal to 0.',
            'maxOrderPerTransaction.required' => 'Maximum order per transaction is required.',
            'maxOrderPerTransaction.integer' => 'Maximum order per transaction must be a whole number.',
            'maxOrderPerTransaction.min' => 'Maximum order per transaction must be at least 1.',
        ]);

        try {
            // Find the variant
            $variant = EcomProductVariant::active()->findOrFail($request->variantId);

            // Update the variant
            $variant->update([
                'ecomVariantName' => $request->ecomVariantName,
                'ecomVariantDescription' => $request->ecomVariantDescription,
                'ecomVariantPrice' => $request->ecomVariantPrice,
                'ecomRawVariantPrice' => $request->rawPrice,
                'costPrice' => $request->costPrice ?? 0,
                'affiliatePrice' => $request->affiliatePrice,
                'stocksAvailable' => $request->stocksAvailable,
                'maxOrderPerTransaction' => $request->maxOrderPerTransaction,
            ]);

            return redirect()->route('ecom-products.variants', ['id' => $variant->ecomProductsId])
                ->with('success', 'Variant has been updated successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'An error occurred while updating the variant. Please try again.');
        }
    }

    /**
     * Show the photos page for a variant.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function variantPhotos(Request $request)
    {
        $variantId = $request->get('id');

        // Get the variant details
        $variant = EcomProductVariant::active()->find($variantId);

        if (!$variant) {
            return redirect()->route('ecom-products')
                ->with('error', 'Variant not found.');
        }

        // Get the product details
        $product = EcomProduct::active()->find($variant->ecomProductsId);

        if (!$product) {
            return redirect()->route('ecom-products')
                ->with('error', 'Product not found.');
        }

        // Get the variant images
        $images = \App\Models\EcomProductVariantImage::active()
            ->byVariant($variantId)
            ->orderBy('imageOrder', 'ASC')
            ->get();

        // Fix any existing images with full URLs (temporary fix)
        foreach ($images as $image) {
            if (strpos($image->imageLink, 'http') === 0) {
                // Extract the path from the full URL
                $path = parse_url($image->imageLink, PHP_URL_PATH);
                if ($path) {
                    $image->update(['imageLink' => $path]);
                }
            }
        }

        // Refresh the images collection
        $images = \App\Models\EcomProductVariantImage::active()
            ->byVariant($variantId)
            ->orderBy('imageOrder', 'ASC')
            ->get();

        // Debug: Log the images found
        \Illuminate\Support\Facades\Log::info('Variant images found', [
            'variantId' => $variantId,
            'count' => $images->count(),
            'images' => $images->toArray()
        ]);

        return view('ecommerce.products.variants.photos', compact('variant', 'product', 'images'));
    }

    /**
     * Show the videos page for a variant.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function variantVideos(Request $request)
    {
        $variantId = $request->get('id');

        // Get the variant details
        $variant = EcomProductVariant::active()->find($variantId);

        if (!$variant) {
            return redirect()->route('ecom-products')
                ->with('error', 'Variant not found.');
        }

        // Get the product details
        $product = EcomProduct::active()->find($variant->ecomProductsId);

        if (!$product) {
            return redirect()->route('ecom-products')
                ->with('error', 'Product not found.');
        }

        // Get the variant videos
        $videos = \App\Models\EcomProductVariantVideo::active()
            ->byVariant($variantId)
            ->orderBy('videoOrder', 'ASC')
            ->get();

        return view('ecommerce.products.variants.videos', compact('variant', 'product', 'videos'));
    }

    /**
     * Upload a new video for a variant.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadVariantVideo(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'variantId' => 'required|integer|exists:ecom_products_variants,id',
                'videoLink' => 'required|url|regex:/^https:\/\/(www\.)?youtube\.com\/watch\?v=[a-zA-Z0-9_-]+/',
            ], [
                'videoLink.regex' => 'Please enter a valid YouTube video URL.',
            ]);

            $variantId = $request->variantId;
            $videoLink = $request->videoLink;

            // Convert YouTube watch URL to embed URL
            $videoId = null;
            if (preg_match('/[?&]v=([^&]+)/', $videoLink, $matches)) {
                $videoId = $matches[1];
            }

            if (!$videoId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid YouTube URL format.'
                ], 400);
            }

            $embedUrl = "https://www.youtube.com/embed/{$videoId}";
            $thumbnailUrl = "https://img.youtube.com/vi/{$videoId}/maxresdefault.jpg";

            // Get the next video order
            $lastVideo = \App\Models\EcomProductVariantVideo::active()
                ->byVariant($variantId)
                ->orderBy('videoOrder', 'DESC')
                ->first();

            $nextOrder = $lastVideo ? $lastVideo->videoOrder + 1 : 1;

            // Save to database
            $variantVideo = \App\Models\EcomProductVariantVideo::create([
                'ecomVariantsId' => $variantId,
                'videoLink' => $embedUrl,
                'videoOrder' => $nextOrder,
                'deleteStatus' => 1,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Video uploaded successfully!',
                'video' => [
                    'id' => $variantVideo->id,
                    'videoLink' => $embedUrl,
                    'thumbnailUrl' => $thumbnailUrl,
                    'videoOrder' => $variantVideo->videoOrder,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while uploading the video: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a variant video.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteVariantVideo($id)
    {
        try {
            $video = \App\Models\EcomProductVariantVideo::find($id);

            if (!$video) {
                return response()->json([
                    'success' => false,
                    'message' => 'Video not found.'
                ], 404);
            }

            // Soft delete by updating deleteStatus to 0
            $video->update(['deleteStatus' => 0]);

            return response()->json([
                'success' => true,
                'message' => 'Video has been removed successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the video: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a variant.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteVariant($id)
    {
        try {
            $variant = \App\Models\EcomProductVariant::find($id);

            if (!$variant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Variant not found.'
                ], 404);
            }

            // Soft delete by updating deleteStatus to 0
            $variant->update(['deleteStatus' => 0]);

            return response()->json([
                'success' => true,
                'message' => 'Variant has been deleted successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the variant: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload a new image for a variant.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadVariantImage(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'variantId' => 'required|integer|exists:ecom_products_variants,id',
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240', // 10MB max
            ]);

            $variantId = $request->variantId;
            $image = $request->file('image');

            // Get the next image order
            $lastImage = \App\Models\EcomProductVariantImage::active()
                ->byVariant($variantId)
                ->orderBy('imageOrder', 'DESC')
                ->first();

            $nextOrder = $lastImage ? $lastImage->imageOrder + 1 : 1;

                        // Generate unique filename
            $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

            // Store the image in public/uploads/variants directory
            $path = $image->storeAs('uploads/variants', $filename, 'public');
            $imageLink = '/storage/' . $path;

            // Save to database
            $variantImage = \App\Models\EcomProductVariantImage::create([
                'ecomVariantsId' => $variantId,
                'imageName' => $image->getClientOriginalName(),
                'imageLink' => $imageLink,
                'imageOrder' => $nextOrder,
                'deleteStatus' => 1,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Image uploaded successfully!',
                'image' => [
                    'id' => $variantImage->id,
                    'imageLink' => $imageLink,
                    'imageName' => $variantImage->imageName,
                    'imageOrder' => $variantImage->imageOrder,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while uploading the image: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reorder variant images.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reorderVariantImages(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'variantId' => 'required|integer|exists:ecom_products_variants,id',
                'imageOrders' => 'required|array',
                'imageOrders.*.id' => 'required|integer|exists:ecom_products_variants_images,id',
                'imageOrders.*.order' => 'required|integer|min:1',
            ]);

            $variantId = $request->variantId;
            $imageOrders = $request->imageOrders;

            // Update each image's order
            foreach ($imageOrders as $imageOrder) {
                \App\Models\EcomProductVariantImage::where('id', $imageOrder['id'])
                    ->where('ecomVariantsId', $variantId)
                    ->update(['imageOrder' => $imageOrder['order']]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Image order updated successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating image order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a variant image.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteVariantImage($id)
    {
        try {
            // Find the image
            $image = \App\Models\EcomProductVariantImage::findOrFail($id);

            // Soft delete by setting deleteStatus to 0
            $image->update(['deleteStatus' => 0]);

            return response()->json([
                'success' => true,
                'message' => 'Image deleted successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the image: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the status of a product.
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
                'isActive.in' => 'Status must be either Yes or No.',
            ]);

            // Find the product
            $product = EcomProduct::active()->findOrFail($id);

            // Update the status
            $product->update(['isActive' => $request->isActive]);

            $statusText = $request->isActive ? 'Yes' : 'No';

            return response()->json([
                'success' => true,
                'message' => 'Product status has been updated successfully!',
                'status' => $request->isActive,
                'statusText' => $statusText
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the product status. Please try again.'
            ], 500);
        }
    }

    /**
     * Update the status of a product variant.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateVariantStatus(Request $request, $id)
    {
        try {
            // Validate the request
            $request->validate([
                'isActive' => 'required|in:0,1',
            ], [
                'isActive.required' => 'Status is required.',
                'isActive.in' => 'Status must be either Yes or No.',
            ]);

            // Find the variant
            $variant = EcomProductVariant::active()->findOrFail($id);

            // Update the status
            $variant->update(['isActive' => $request->isActive]);

            $statusText = $request->isActive ? 'Yes' : 'No';

            return response()->json([
                'success' => true,
                'message' => 'Variant status has been updated successfully!',
                'status' => $request->isActive,
                'statusText' => $statusText
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the variant status. Please try again.'
            ], 500);
        }
    }

    /**
     * Update the stocks of a product variant.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateVariantStocks(Request $request, $id)
    {
        try {
            // Validate the request
            $request->validate([
                'stocksAvailable' => 'required|integer|min:0',
            ], [
                'stocksAvailable.required' => 'Stocks available is required.',
                'stocksAvailable.integer' => 'Stocks available must be a whole number.',
                'stocksAvailable.min' => 'Stocks available must be greater than or equal to 0.',
            ]);

            // Find the variant
            $variant = EcomProductVariant::active()->findOrFail($id);

            // Update the stocks
            $variant->update(['stocksAvailable' => $request->stocksAvailable]);

            return response()->json([
                'success' => true,
                'message' => 'Variant stocks have been updated successfully!',
                'stocksAvailable' => $request->stocksAvailable
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the variant stocks. Please try again.'
            ], 500);
        }
    }

        /**
     * Display the product triggers page.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function triggers(Request $request)
    {
        $productId = $request->query('id');

        // Get the product details
        $product = EcomProduct::active()->find($productId);

        // Get product tags from ecom_products_tags table where ecomProductsId matches the current product id
        // and deleteStatus is 1, then join with axis_tags to get tagName and expirationLength
        $productTags = DB::table('ecom_products_tags')
            ->join('axis_tags', 'ecom_products_tags.axisTagId', '=', 'axis_tags.id')
            ->where('ecom_products_tags.ecomProductsId', $productId)
            ->where('ecom_products_tags.deleteStatus', 1)
            ->select(
                'ecom_products_tags.id',
                'axis_tags.tagName',
                'axis_tags.expirationLength',
                'ecom_products_tags.ecomProductsId'
            )
            ->get();

        return view('ecommerce.products.triggers', compact('product', 'productTags'));
    }

    /**
     * Display the product edit page.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function edit(Request $request)
    {
        $productId = $request->query('id');

        // Get the product details
        $product = EcomProduct::active()->find($productId);

        if (!$product) {
            return redirect()->route('ecom-products')
                ->with('error', 'Product not found.');
        }

        // Get active and enabled stores for the dropdown
        $stores = EcomProductStore::active()->enabled()->orderBy('storeName')->get();

        return view('ecommerce.products.edit', compact('product', 'stores'));
    }

    /**
     * Update the specified product in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        // Validate the request
        $validationRules = [
            'productName' => 'required|string|max:255',
            'productStore' => 'required|string|max:255',
            'productType' => 'required|string|in:access,ship',
            'productDescription' => 'required|string',
        ];

        // Only validate shipCoverage if productType is 'ship'
        if ($request->productType === 'ship') {
            $validationRules['shipCoverage'] = 'required|string|in:Town,Province,Region,National';
        }

        $request->validate($validationRules, [
            'productName.required' => 'Product name is required.',
            'productStore.required' => 'Product store is required.',
            'productType.required' => 'Product type is required.',
            'productType.in' => 'Product type must be either access or ship.',
            'productDescription.required' => 'Product description is required.',
            'shipCoverage.required' => 'Shipping coverage is required for ship products.',
            'shipCoverage.in' => 'Shipping coverage must be one of: Town, Province, Region, National.',
        ]);

        try {
            // Find the product
            $product = EcomProduct::active()->findOrFail($id);

            // Prepare data for update
            $productData = [
                'productName' => $request->productName,
                'productStore' => $request->productStore,
                'productType' => $request->productType,
                'productDescription' => $request->productDescription,
            ];

            // Add shipCoverage only if product type is 'ship', otherwise set to 'n/a'
            if ($request->productType === 'ship') {
                $productData['shipCoverage'] = $request->shipCoverage;
            } else {
                $productData['shipCoverage'] = 'n/a';
            }

            // Update the product
            $product->update($productData);

            return redirect()->route('ecom-products')
                ->with('success', 'Product has been updated successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'An error occurred while updating the product. Please try again.');
        }
    }






    /**
     * Get available tags that can be added to the product.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableTags(Request $request)
    {
        $productId = $request->query('id');

        // Get available tags from axis_tags that are not yet in ecom_products_tags for this product
        $availableTags = DB::table('axis_tags')
            ->leftJoin('ecom_products_tags', function($join) use ($productId) {
                $join->on('axis_tags.id', '=', 'ecom_products_tags.axisTagId')
                     ->where('ecom_products_tags.ecomProductsId', '=', $productId)
                     ->where('ecom_products_tags.deleteStatus', '=', 1);
            })
            ->leftJoin('as_courses', function($join) {
                $join->on('axis_tags.targetId', '=', 'as_courses.id')
                     ->where('axis_tags.tagType', '=', 'course');
            })
            ->where('axis_tags.deleteStatus', 1)
            ->whereNull('ecom_products_tags.id') // Only tags not yet assigned to this product
            ->select(
                'axis_tags.id',
                'axis_tags.tagName',
                'axis_tags.expirationLength',
                'axis_tags.tagType',
                'as_courses.courseName'
            )
            ->get();

        return response()->json([
            'success' => true,
            'tags' => $availableTags
        ]);
    }

    /**
     * Save selected tags to ecom_products_tags table.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveTags(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'productId' => 'required|integer|exists:ecom_products,id',
                'tagIds' => 'required|array',
                'tagIds.*' => 'integer|exists:axis_tags,id'
            ], [
                'productId.required' => 'Product ID is required.',
                'productId.integer' => 'Product ID must be an integer.',
                'productId.exists' => 'Product not found.',
                'tagIds.required' => 'Tag IDs are required.',
                'tagIds.array' => 'Tag IDs must be an array.',
                'tagIds.*.integer' => 'Tag ID must be an integer.',
                'tagIds.*.exists' => 'Tag not found.'
            ]);

            $productId = $request->productId;
            $tagIds = $request->tagIds;

            // Insert tags into ecom_products_tags table
            $insertData = [];
            foreach ($tagIds as $tagId) {
                $insertData[] = [
                    'ecomProductsId' => $productId,
                    'axisTagId' => $tagId,
                    'deleteStatus' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }

            DB::table('ecom_products_tags')->insert($insertData);

            return response()->json([
                'success' => true,
                'message' => 'Tags have been successfully added to the product!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while saving the tags: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a tag from ecom_products_tags table.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteTag($id)
    {
        try {
            // Find the tag in ecom_products_tags table
            $tag = DB::table('ecom_products_tags')->where('id', $id)->first();

            if (!$tag) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tag not found.'
                ], 404);
            }

            // Soft delete by setting deleteStatus to 0
            DB::table('ecom_products_tags')
                ->where('id', $id)
                ->update(['deleteStatus' => 0]);

            return response()->json([
                'success' => true,
                'message' => 'Tag has been successfully removed from the product!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the tag: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the variant triggers page.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function variantTriggers(Request $request)
    {
        $variantId = $request->query('id');

        // Get the variant details
        $variant = EcomProductVariant::active()->find($variantId);

        if (!$variant) {
            return redirect()->route('ecom-products')
                ->with('error', 'Variant not found.');
        }

        // Get the product details
        $product = EcomProduct::active()->find($variant->ecomProductsId);

        if (!$product) {
            return redirect()->route('ecom-products')
                ->with('error', 'Product not found.');
        }

        // Get variant tags from ecom_products_variants_tags table where ecomVariantsId matches the current variant id
        // and deleteStatus is 1, then join with ecom_trigger_tags to get triggerTagName and triggerTagDescription
        $variantTags = DB::table('ecom_products_variants_tags')
            ->join('ecom_trigger_tags', 'ecom_products_variants_tags.ecomTriggerTagId', '=', 'ecom_trigger_tags.id')
            ->where('ecom_products_variants_tags.ecomVariantsId', $variantId)
            ->where('ecom_products_variants_tags.deleteStatus', 1)
            ->where('ecom_trigger_tags.deleteStatus', 1)
            ->select(
                'ecom_products_variants_tags.id',
                'ecom_trigger_tags.id as triggerTagId',
                'ecom_trigger_tags.triggerTagName',
                'ecom_trigger_tags.triggerTagDescription',
                'ecom_products_variants_tags.ecomVariantsId'
            )
            ->get();

        // If this is an AJAX refresh request, return only the table HTML
        if ($request->has('refresh') && $request->ajax()) {
            return view('ecommerce.products.variants.triggers', compact('variant', 'product', 'variantTags'))
                ->render();
        }

        return view('ecommerce.products.variants.triggers', compact('variant', 'product', 'variantTags'));
    }

    /**
     * Get available tags that can be added to the variant.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableVariantTags(Request $request)
    {
        $variantId = $request->query('id');

        // Get available trigger tags from ecom_trigger_tags that are not yet in ecom_products_variants_tags for this variant
        $availableTags = DB::table('ecom_trigger_tags')
            ->leftJoin('ecom_products_variants_tags', function($join) use ($variantId) {
                $join->on('ecom_trigger_tags.id', '=', 'ecom_products_variants_tags.ecomTriggerTagId')
                     ->where('ecom_products_variants_tags.ecomVariantsId', '=', $variantId)
                     ->where('ecom_products_variants_tags.deleteStatus', '=', 1);
            })
            ->where('ecom_trigger_tags.deleteStatus', 1)
            ->whereNull('ecom_products_variants_tags.id') // Only tags not yet assigned to this variant
            ->select(
                'ecom_trigger_tags.id',
                'ecom_trigger_tags.triggerTagName',
                'ecom_trigger_tags.triggerTagDescription',
                'ecom_trigger_tags.created_at'
            )
            ->orderBy('ecom_trigger_tags.triggerTagName', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'tags' => $availableTags
        ]);
    }

    /**
     * Save selected tags to ecom_products_variants_tags table.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveVariantTags(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'variantId' => 'required|integer|exists:ecom_products_variants,id',
                'tagIds' => 'required|array',
                'tagIds.*' => 'integer|exists:ecom_trigger_tags,id'
            ], [
                'variantId.required' => 'Variant ID is required.',
                'variantId.integer' => 'Variant ID must be an integer.',
                'variantId.exists' => 'Variant not found.',
                'tagIds.required' => 'Tag IDs are required.',
                'tagIds.array' => 'Tag IDs must be an array.',
                'tagIds.*.integer' => 'Tag ID must be an integer.',
                'tagIds.*.exists' => 'Trigger tag not found.'
            ]);

            $variantId = $request->variantId;
            $tagIds = $request->tagIds;

            // Insert tags into ecom_products_variants_tags table
            $insertData = [];
            foreach ($tagIds as $tagId) {
                $insertData[] = [
                    'ecomVariantsId' => $variantId,
                    'ecomTriggerTagId' => $tagId,
                    'deleteStatus' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }

            DB::table('ecom_products_variants_tags')->insert($insertData);

            return response()->json([
                'success' => true,
                'message' => 'Trigger tags have been successfully added to the variant!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while saving the tags: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new trigger tag.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createTriggerTag(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'triggerTagName' => 'required|string|max:255',
                'triggerTagDescription' => 'nullable|string'
            ], [
                'triggerTagName.required' => 'Trigger tag name is required.',
                'triggerTagName.max' => 'Trigger tag name cannot exceed 255 characters.'
            ]);

            // Check if a trigger tag with the same name already exists
            $existingTag = DB::table('ecom_trigger_tags')
                ->where('triggerTagName', $request->triggerTagName)
                ->where('deleteStatus', 1)
                ->first();

            if ($existingTag) {
                return response()->json([
                    'success' => false,
                    'message' => 'A trigger tag with this name already exists.'
                ], 400);
            }

            // Create the new trigger tag
            $tagId = DB::table('ecom_trigger_tags')->insertGetId([
                'usersId' => Auth::id(),
                'triggerTagName' => $request->triggerTagName,
                'triggerTagDescription' => $request->triggerTagDescription,
                'deleteStatus' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Trigger tag created successfully!',
                'tag' => [
                    'id' => $tagId,
                    'triggerTagName' => $request->triggerTagName,
                    'triggerTagDescription' => $request->triggerTagDescription
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the trigger tag: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a tag from ecom_products_variants_tags table.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteVariantTag($id)
    {
        try {
            // Find the tag in ecom_products_variants_tags table
            $tag = DB::table('ecom_products_variants_tags')->where('id', $id)->first();

            if (!$tag) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tag not found.'
                ], 404);
            }

            // Soft delete by setting deleteStatus to 0
            DB::table('ecom_products_variants_tags')
                ->where('id', $id)
                ->update(['deleteStatus' => 0]);

            return response()->json([
                'success' => true,
                'message' => 'Tag has been successfully removed from the variant!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the tag: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the shipping page for a specific variant.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function variantShipping(Request $request)
    {
        $variantId = $request->get('id');

        // Get the variant details
        $variant = EcomProductVariant::active()->find($variantId);

        if (!$variant) {
            return redirect()->route('ecom-products')
                ->with('error', 'Variant not found.');
        }

        // Get the product details
        $product = EcomProduct::active()->find($variant->ecomProductsId);

        if (!$product) {
            return redirect()->route('ecom-products')
                ->with('error', 'Product not found.');
        }

        // Get active shipping methods
        $shippingMethods = EcomProductsShipping::active()
            ->orderBy('created_at', 'desc')
            ->get();

        return view('ecommerce.products.variants.shipping', compact('variant', 'product', 'shippingMethods'));
    }

    /**
     * Get shipping options for a specific shipping method.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getShippingOptions(Request $request)
    {
        try {
            $shippingId = $request->get('shipping_id');

            if (!$shippingId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Shipping ID is required.'
                ], 400);
            }

            // Get shipping options for the specified shipping method
            $shippingOptions = EcomProductsShippingOptions::active()
                ->byShippingId($shippingId)
                ->where('isActive', 1)
                ->orderBy('provinceTarget', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $shippingOptions,
                'count' => $shippingOptions->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching shipping options: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get shipping methods with search and pagination.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getShippingMethods(Request $request)
    {
        try {
            $query = EcomProductsShipping::active();

            // Search functionality
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = $request->search;
                $query->where(function($q) use ($searchTerm) {
                    $q->where('shippingName', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('shippingDescription', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('defaultPrice', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('defaultMaxQuantity', 'LIKE', "%{$searchTerm}%");
                });
            }

            // Get total count before pagination
            $totalRecords = $query->count();

            // Pagination
            $perPage = $request->get('per_page', 10);
            $page = $request->get('page', 1);
            $offset = ($page - 1) * $perPage;

            $shippingMethods = $query->orderBy('created_at', 'desc')
                ->offset($offset)
                ->limit($perPage)
                ->get();

            $lastPage = ceil($totalRecords / $perPage);

            return response()->json([
                'success' => true,
                'data' => $shippingMethods,
                'pagination' => [
                    'current_page' => $page,
                    'last_page' => $lastPage,
                    'per_page' => $perPage,
                    'total' => $totalRecords,
                    'from' => $offset + 1,
                    'to' => min($offset + $perPage, $totalRecords)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching shipping methods: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get existing shipping selections for a variant.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getVariantShippingSelections(Request $request)
    {
        try {
            $variantId = $request->get('variant_id');

            if (!$variantId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Variant ID is required.'
                ], 400);
            }

            // Get existing shipping selections for this variant
            $selections = EcomProductsVariantsShipping::active()
                ->byVariant($variantId)
                ->pluck('ecomShippingId')
                ->toArray();

            return response()->json([
                'success' => true,
                'data' => $selections
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching shipping selections: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add shipping method to variant.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addVariantShipping(Request $request)
    {
        try {
            $request->validate([
                'variant_id' => 'required|integer|exists:ecom_products_variants,id',
                'shipping_id' => 'required|integer|exists:ecom_products_shipping,id'
            ]);

            $variantId = $request->variant_id;
            $shippingId = $request->shipping_id;

            // Check if the relationship already exists
            $existing = EcomProductsVariantsShipping::where('ecomVariantId', $variantId)
                ->where('ecomShippingId', $shippingId)
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'This shipping method is already assigned to this variant.'
                ], 400);
            }

            // Create the relationship
            EcomProductsVariantsShipping::create([
                'ecomVariantId' => $variantId,
                'ecomShippingId' => $shippingId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Shipping method has been successfully assigned to this variant.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while assigning shipping method: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove shipping method from variant.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeVariantShipping(Request $request)
    {
        try {
            $request->validate([
                'variant_id' => 'required|integer|exists:ecom_products_variants,id',
                'shipping_id' => 'required|integer|exists:ecom_products_shipping,id'
            ]);

            $variantId = $request->variant_id;
            $shippingId = $request->shipping_id;

            // Find and delete the relationship
            $relationship = EcomProductsVariantsShipping::where('ecomVariantId', $variantId)
                ->where('ecomShippingId', $shippingId)
                ->first();

            if (!$relationship) {
                return response()->json([
                    'success' => false,
                    'message' => 'This shipping method is not assigned to this variant.'
                ], 400);
            }

            // Hard delete the relationship
            $relationship->delete();

            return response()->json([
                'success' => true,
                'message' => 'Shipping method has been successfully removed from this variant.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while removing shipping method: ' . $e->getMessage()
            ], 500);
        }
    }
}
