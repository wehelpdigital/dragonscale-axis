<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\EcomProduct;
use App\Models\EcomProductVariant;
use Illuminate\Http\Request;

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

        // Get paginated results
        $products = $query->orderBy('created_at', 'desc')->paginate(10);

        // Get unique stores for filter dropdown
        $stores = EcomProduct::active()->distinct()->pluck('productStore')->filter();

        return view('ecommerce.products.index', compact('products', 'stores'));
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
            'productDescription' => 'required|string',
        ], [
            'productName.required' => 'Product name is required.',
            'productStore.required' => 'Product store is required.',
            'productDescription.required' => 'Product description is required.',
        ]);

        try {
            // Create the product
            EcomProduct::create([
                'productName' => $request->productName,
                'productStore' => $request->productStore,
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
        ]);

        try {
            // Create the variant with specified field mappings
            EcomProductVariant::create([
                'ecomProductsId' => $request->ecomProductsId,
                'ecomVariantName' => $request->ecomVariantName,
                'ecomVariantDescription' => $request->ecomVariantDescription,
                'ecomVariantPrice' => $request->ecomVariantPrice,
                'stocksAvailable' => $request->stocksAvailable,
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
}
