<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\EcomProduct;
use App\Models\EcomProductVariant;
use App\Models\EcomProductDiscount;
use Illuminate\Http\Request;
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
        $query = EcomProduct::active();

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

        // Get unique stores for filter dropdown
        $stores = EcomProduct::active()->distinct()->pluck('productStore')->filter();

        // Get unique product types for filter dropdown
        $productTypes = EcomProduct::active()->distinct()->pluck('productType')->filter();

        return view('ecommerce.products.index', compact('products', 'stores', 'productTypes'));
    }

    /**
     * Show the form for creating a new product.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('ecommerce.products.create');
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
        $request->validate([
            'productName' => 'required|string|max:255',
            'productStore' => 'required|string|max:255',
            'productType' => 'required|string|in:access,ship',
            'productDescription' => 'required|string',
        ], [
            'productName.required' => 'Product name is required.',
            'productStore.required' => 'Product store is required.',
            'productType.required' => 'Product type is required.',
            'productType.in' => 'Product type must be either access or ship.',
            'productDescription.required' => 'Product description is required.',
        ]);

        try {
            // Create the product
            EcomProduct::create([
                'productName' => $request->productName,
                'productStore' => $request->productStore,
                'productType' => $request->productType,
                'productDescription' => $request->productDescription,
                'isActive' => 1,
                'deleteStatus' => 1,
            ]);

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

        return view('ecommerce.products.edit', compact('product'));
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
        $request->validate([
            'productName' => 'required|string|max:255',
            'productStore' => 'required|string|max:255',
            'productType' => 'required|string|in:access,ship',
            'productDescription' => 'required|string',
        ], [
            'productName.required' => 'Product name is required.',
            'productStore.required' => 'Product store is required.',
            'productType.required' => 'Product type is required.',
            'productType.in' => 'Product type must be either access or ship.',
            'productDescription.required' => 'Product description is required.',
        ]);

        try {
            // Find the product
            $product = EcomProduct::active()->findOrFail($id);

            // Update the product
            $product->update([
                'productName' => $request->productName,
                'productStore' => $request->productStore,
                'productType' => $request->productType,
                'productDescription' => $request->productDescription,
            ]);

            return redirect()->route('ecom-products')
                ->with('success', 'Product has been updated successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'An error occurred while updating the product. Please try again.');
        }
    }

    /**
     * Display the product discounts page.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function discounts(Request $request)
    {
        $productId = $request->query('id');

        // Get the product details
        $product = EcomProduct::active()->find($productId);

        if (!$product) {
            abort(404, 'Product not found');
        }

        // Get discounts for this product
        $discounts = EcomProductDiscount::active()
            ->where('ecomProductsId', $productId)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('ecommerce.products.discounts', compact('product', 'discounts'));
    }

    /**
     * Show the form for creating a new discount.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function createDiscount(Request $request)
    {
        $productId = $request->query('id');

        // Get the product details
        $product = EcomProduct::active()->find($productId);

        if (!$product) {
            abort(404, 'Product not found');
        }

        return view('ecommerce.products.discounts.create', compact('product'));
    }

    /**
     * Store a newly created discount in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeDiscount(Request $request)
    {
        // Validate the request
        $request->validate([
            'ecomProductsId' => 'required|integer|exists:ecom_products,id',
            'discountName' => 'required|string|max:255',
            'discountType' => 'required|string|in:discount code,auto apply',
            'discountCode' => 'nullable|string|max:255',
            'timerType' => 'required|string|in:cookie countdown,date and time,slots remaining',
            'discountValueType' => 'required|string|in:percentage,discount amount,price change',
            'countdownValueDays' => 'nullable|integer|min:0',
            'countdownValueMinutes' => 'nullable|integer|min:0',
            'scheduledEnding' => 'nullable|date|after:now',
            'slotsRemainingValue' => 'nullable|integer|min:0',
            'discountValuePercentage' => 'nullable|numeric|min:0|max:100',
            'discountValueChange' => 'nullable|numeric|min:0',
            'newDiscountedPrice' => 'nullable|numeric|min:0',
            'discountValueMax' => 'required|numeric|min:0',
            'discountPriceMax' => 'required|numeric|min:0',
        ], [
            'ecomProductsId.required' => 'Product ID is required.',
            'discountName.required' => 'Discount name is required.',
            'discountType.required' => 'Discount type is required.',
            'discountCode.string' => 'Discount code must be a valid text.',
            'discountCode.max' => 'Discount code cannot exceed 255 characters.',
            'timerType.required' => 'Timer type is required.',
            'discountValueType.required' => 'Discount value type is required.',
            'countdownValueDays.integer' => 'Countdown days must be a whole number.',
            'countdownValueMinutes.integer' => 'Countdown minutes must be a whole number.',
            'scheduledEnding.date' => 'Scheduled ending must be a valid date.',
            'scheduledEnding.after' => 'Scheduled ending must be in the future.',
            'slotsRemainingValue.integer' => 'Slots remaining must be a whole number.',
            'discountValuePercentage.numeric' => 'Discount percentage must be a valid number.',
            'discountValuePercentage.max' => 'Discount percentage cannot exceed 100%.',
            'discountValueChange.numeric' => 'Discount amount must be a valid number.',
            'newDiscountedPrice.numeric' => 'New discounted price must be a valid number.',
            'discountValueMax.required' => 'Discount value max ceiling is required.',
            'discountValueMax.numeric' => 'Discount value max ceiling must be a valid number.',
            'discountPriceMax.required' => 'Discount price max ceiling is required.',
            'discountPriceMax.numeric' => 'Discount price max ceiling must be a valid number.',
        ]);

        // Additional conditional validation
        $discountType = $request->discountType;
        $timerType = $request->timerType;
        $discountValueType = $request->discountValueType;

        // Validate discount code field if discount type is discount code
        if ($discountType === 'discount code') {
            $request->validate([
                'discountCode' => 'required|string|min:2|max:255',
            ], [
                'discountCode.required' => 'Discount code is required when discount type is discount code.',
                'discountCode.min' => 'Discount code must be at least 2 characters long.',
            ]);
        }

        // Validate timer type specific fields
        if ($timerType === 'cookie countdown') {
            $request->validate([
                'countdownValueDays' => 'required|integer|min:0',
                'countdownValueMinutes' => 'required|integer|min:0',
            ], [
                'countdownValueDays.required' => 'Countdown days is required for cookie countdown.',
                'countdownValueMinutes.required' => 'Countdown minutes is required for cookie countdown.',
            ]);
        } elseif ($timerType === 'date and time') {
            $request->validate([
                'scheduledEnding' => 'required|date|after:now',
            ], [
                'scheduledEnding.required' => 'Promo ends schedule is required for date and time timer.',
            ]);
        } elseif ($timerType === 'slots remaining') {
            $request->validate([
                'slotsRemainingValue' => 'required|integer|min:0',
            ], [
                'slotsRemainingValue.required' => 'How many slots is required for slots remaining timer.',
            ]);
        }

        // Validate discount value type specific fields
        if ($discountValueType === 'percentage') {
            $request->validate([
                'discountValuePercentage' => 'required|numeric|min:0|max:100',
            ], [
                'discountValuePercentage.required' => 'Discount percentage is required for percentage discount.',
            ]);
        } elseif ($discountValueType === 'discount amount') {
            $request->validate([
                'discountValueChange' => 'required|numeric|min:0',
            ], [
                'discountValueChange.required' => 'Discount amount is required for discount amount type.',
            ]);
        } elseif ($discountValueType === 'price change') {
            $request->validate([
                'newDiscountedPrice' => 'required|numeric|min:0',
            ], [
                'newDiscountedPrice.required' => 'New discounted price is required for price change type.',
            ]);
        }

        try {
            // Prepare data for creation
            $discountData = [
                'ecomProductsId' => $request->ecomProductsId,
                'discountName' => $request->discountName,
                'discountType' => $request->discountType,
                'discountCode' => $request->discountCode ?? null,
                'timerType' => $request->timerType,
                'discountValueType' => $request->discountValueType,
                'countdownValueDays' => $request->countdownValueDays ?? 0,
                'countdownValueMinutes' => $request->countdownValueMinutes ?? 0,
                'scheduledEnding' => $request->scheduledEnding ?? now()->addDays(30),
                'slotsRemainingValue' => $request->slotsRemainingValue ?? 0,
                'discountValuePercentage' => $request->discountValuePercentage ?? 0,
                'discountValueAmount' => 0, // Will be set based on discount value type
                'discountValueChange' => 0, // Will be set based on discount value type
                'discountValueMax' => $request->discountValueMax,
                'discountPriceMax' => $request->discountPriceMax,
                'isActive' => 1,
                'deleteStatus' => 1,
            ];

            // Handle discount value based on type
            if ($discountValueType === 'discount amount' && $request->discountValueChange) {
                $discountData['discountValueAmount'] = $request->discountValueChange;
            } elseif ($discountValueType === 'price change' && $request->newDiscountedPrice) {
                $discountData['discountValueChange'] = $request->newDiscountedPrice;
            }

            // Create the discount
            EcomProductDiscount::create($discountData);

            // Check if this is an AJAX request
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Discount has been created successfully!',
                    'redirect' => route('ecom-products.discounts', ['id' => $request->ecomProductsId])
                ]);
            }

            return redirect()->route('ecom-products.discounts', ['id' => $request->ecomProductsId]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors for AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please check the form for errors and try again.',
                    'errors' => $e->errors()
                ], 422);
            }

            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            // Check if this is an AJAX request
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while saving the discount. Please try again.',
                    'error' => $e->getMessage()
                ], 500);
            }

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
    public function editDiscount(Request $request)
    {
        $discountId = $request->query('id');

        // Get the discount details
        $discount = EcomProductDiscount::active()->find($discountId);

        if (!$discount) {
            abort(404, 'Discount not found');
        }

        // Get the product details
        $product = $discount->product;

        if (!$product) {
            abort(404, 'Product not found');
        }

        return view('ecommerce.products.discounts.edit', compact('discount', 'product'));
    }

    /**
     * Update the specified discount.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function updateDiscount(Request $request)
    {
        try {
            $discountId = $request->query('id');

            // Get the discount
            $discount = EcomProductDiscount::active()->find($discountId);

            if (!$discount) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Discount not found'
                    ], 404);
                }
                abort(404, 'Discount not found');
            }

            // Validation rules
            $rules = [
                'discountName' => 'required|string|min:2|max:255',
                'discountType' => 'required|in:discount code,auto apply',
                'discountCode' => 'nullable|string|min:2|max:255',
                'timerType' => 'required|in:cookie countdown,date and time,slots remaining',
                'discountValueType' => 'required|in:percentage,discount amount,price change',
                'countdownValueDays' => 'nullable|integer|min:0',
                'countdownValueMinutes' => 'nullable|integer|min:0',
                'scheduledEnding' => 'nullable|date|after:now',
                'slotsRemainingValue' => 'nullable|integer|min:0',
                'discountValuePercentage' => 'nullable|numeric|min:0|max:100',
                'discountValueChange' => 'nullable|numeric|min:0',
                'newDiscountedPrice' => 'nullable|numeric|min:0',
                'discountValueMax' => 'required|numeric|min:0',
                'discountPriceMax' => 'required|numeric|min:0',
            ];

            // Conditional validation for discount code
            if ($request->discountType === 'discount code') {
                $rules['discountCode'] = 'required|string|min:2|max:255';
            }

            // Conditional validation for timer type specific fields
            if ($request->timerType === 'cookie countdown') {
                $rules['countdownValueDays'] = 'required|integer|min:0';
                $rules['countdownValueMinutes'] = 'required|integer|min:0';
            } elseif ($request->timerType === 'date and time') {
                $rules['scheduledEnding'] = 'required|date|after:now';
            } elseif ($request->timerType === 'slots remaining') {
                $rules['slotsRemainingValue'] = 'required|integer|min:0';
            }

            // Conditional validation for discount value type specific fields
            if ($request->discountValueType === 'percentage') {
                $rules['discountValuePercentage'] = 'required|numeric|min:0|max:100';
            } elseif ($request->discountValueType === 'discount amount') {
                $rules['discountValueChange'] = 'required|numeric|min:0';
            } elseif ($request->discountValueType === 'price change') {
                $rules['newDiscountedPrice'] = 'required|numeric|min:0';
            }

            $validatedData = $request->validate($rules);

            // Prepare discount data for database
            $discountData = [
                'discountName' => $validatedData['discountName'],
                'discountType' => $validatedData['discountType'],
                'discountCode' => $validatedData['discountCode'] ?? null,
                'timerType' => $validatedData['timerType'],
                'discountValueType' => $validatedData['discountValueType'],
                'countdownValueDays' => $validatedData['countdownValueDays'] ?? 0,
                'countdownValueMinutes' => $validatedData['countdownValueMinutes'] ?? 0,
                'scheduledEnding' => $validatedData['scheduledEnding'] ?? null,
                'slotsRemainingValue' => $validatedData['slotsRemainingValue'] ?? 0,
                'discountValuePercentage' => $validatedData['discountValuePercentage'] ?? 0,
                'discountValueAmount' => $validatedData['discountValueChange'] ?? 0,
                'discountValueChange' => $validatedData['newDiscountedPrice'] ?? 0,
                'discountValueMax' => $validatedData['discountValueMax'],
                'discountPriceMax' => $validatedData['discountPriceMax'],
            ];

            // Update the discount
            $discount->update($discountData);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Discount updated successfully',
                    'redirect' => route('ecom-products.discounts', ['id' => $discount->ecomProductsId])
                ]);
            }

            return redirect()->route('ecom-products.discounts', ['id' => $discount->ecomProductsId]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while updating the discount. Please try again.'
                ], 500);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'An error occurred while updating the discount. Please try again.');
        }
    }

    /**
     * Get discount details for viewing.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function viewDiscount(Request $request)
    {
        try {
            $discountId = $request->query('id');

            // Get the discount with all details
            $discount = EcomProductDiscount::active()->find($discountId);

            if (!$discount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Discount not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'discount' => $discount
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching discount details.'
            ], 500);
        }
    }

    /**
     * Delete the specified discount (soft delete by setting deleteStatus to 0).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteDiscount(Request $request)
    {
        try {
            $discountId = $request->query('id');

            // Get the discount
            $discount = EcomProductDiscount::active()->find($discountId);

            if (!$discount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Discount not found'
                ], 404);
            }

            // Soft delete by setting deleteStatus to 0
            $discount->update(['deleteStatus' => 0]);

            return response()->json([
                'success' => true,
                'message' => 'Discount has been deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the discount. Please try again.'
            ], 500);
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
        // and deleteStatus is 1, then join with axis_tags to get tagName and expirationLength
        $variantTags = DB::table('ecom_products_variants_tags')
            ->join('axis_tags', 'ecom_products_variants_tags.axisTagId', '=', 'axis_tags.id')
            ->where('ecom_products_variants_tags.ecomVariantsId', $variantId)
            ->where('ecom_products_variants_tags.deleteStatus', 1)
            ->select(
                'ecom_products_variants_tags.id',
                'axis_tags.tagName',
                'axis_tags.expirationLength',
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

        // Get available tags from axis_tags that are not yet in ecom_products_variants_tags for this variant
        $availableTags = DB::table('axis_tags')
            ->leftJoin('ecom_products_variants_tags', function($join) use ($variantId) {
                $join->on('axis_tags.id', '=', 'ecom_products_variants_tags.axisTagId')
                     ->where('ecom_products_variants_tags.ecomVariantsId', '=', $variantId)
                     ->where('ecom_products_variants_tags.deleteStatus', '=', 1);
            })
            ->leftJoin('as_courses', function($join) {
                $join->on('axis_tags.targetId', '=', 'as_courses.id')
                     ->where('axis_tags.tagType', '=', 'course');
            })
            ->where('axis_tags.deleteStatus', 1)
            ->whereNull('ecom_products_variants_tags.id') // Only tags not yet assigned to this variant
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
                'tagIds.*' => 'integer|exists:axis_tags,id'
            ], [
                'variantId.required' => 'Variant ID is required.',
                'variantId.integer' => 'Variant ID must be an integer.',
                'variantId.exists' => 'Variant not found.',
                'tagIds.required' => 'Tag IDs are required.',
                'tagIds.array' => 'Tag IDs must be an array.',
                'tagIds.*.integer' => 'Tag ID must be an integer.',
                'tagIds.*.exists' => 'Tag not found.'
            ]);

            $variantId = $request->variantId;
            $tagIds = $request->tagIds;

            // Insert tags into ecom_products_variants_tags table
            $insertData = [];
            foreach ($tagIds as $tagId) {
                $insertData[] = [
                    'ecomVariantsId' => $variantId,
                    'axisTagId' => $tagId,
                    'deleteStatus' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }

            DB::table('ecom_products_variants_tags')->insert($insertData);

            return response()->json([
                'success' => true,
                'message' => 'Tags have been successfully added to the variant!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while saving the tags: ' . $e->getMessage()
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
}
