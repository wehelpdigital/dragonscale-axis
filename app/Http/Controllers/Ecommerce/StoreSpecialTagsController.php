<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\EcomProductStore;
use App\Models\EcomStoreSpecialTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class StoreSpecialTagsController extends Controller
{
    /**
     * Display the special tags page for a store.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $storeId = $request->query('id');

        $store = EcomProductStore::where('id', $storeId)
            ->where('deleteStatus', 1)
            ->firstOrFail();

        $tags = EcomStoreSpecialTag::active()
            ->forStore($storeId)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('ecommerce.stores.special-tags', compact('store', 'tags'));
    }

    /**
     * Store a new special tag.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'storeId' => 'required|integer|exists:ecom_product_stores,id',
                'tagName' => 'required|string|max:255',
                'tagValue' => 'required|string|max:255',
                'tagDescription' => 'nullable|string|max:1000',
            ], [
                'storeId.required' => 'Store ID is required.',
                'storeId.exists' => 'Store not found.',
                'tagName.required' => 'Tag name is required.',
                'tagName.max' => 'Tag name must not exceed 255 characters.',
                'tagValue.required' => 'Tag value is required.',
                'tagValue.max' => 'Tag value must not exceed 255 characters.',
                'tagDescription.max' => 'Description must not exceed 1000 characters.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check for duplicate tag value in the same store
            $exists = EcomStoreSpecialTag::active()
                ->forStore($request->storeId)
                ->where('tagValue', $request->tagValue)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'A tag with this value already exists for this store.'
                ], 422);
            }

            $tag = EcomStoreSpecialTag::create([
                'storeId' => $request->storeId,
                'tagName' => $request->tagName,
                'tagValue' => $request->tagValue,
                'tagDescription' => $request->tagDescription,
                'isActive' => true,
                'deleteStatus' => 1,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Special tag created successfully!',
                'tag' => $tag
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating special tag: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the tag.'
            ], 500);
        }
    }

    /**
     * Update a special tag.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $tag = EcomStoreSpecialTag::active()->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'tagName' => 'required|string|max:255',
                'tagValue' => 'required|string|max:255',
                'tagDescription' => 'nullable|string|max:1000',
            ], [
                'tagName.required' => 'Tag name is required.',
                'tagName.max' => 'Tag name must not exceed 255 characters.',
                'tagValue.required' => 'Tag value is required.',
                'tagValue.max' => 'Tag value must not exceed 255 characters.',
                'tagDescription.max' => 'Description must not exceed 1000 characters.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check for duplicate tag value (excluding current tag)
            $exists = EcomStoreSpecialTag::active()
                ->forStore($tag->storeId)
                ->where('tagValue', $request->tagValue)
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'A tag with this value already exists for this store.'
                ], 422);
            }

            $tag->update([
                'tagName' => $request->tagName,
                'tagValue' => $request->tagValue,
                'tagDescription' => $request->tagDescription,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Special tag updated successfully!',
                'tag' => $tag->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating special tag: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the tag.'
            ], 500);
        }
    }

    /**
     * Toggle tag active status.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus($id)
    {
        try {
            $tag = EcomStoreSpecialTag::active()->findOrFail($id);

            $tag->update([
                'isActive' => !$tag->isActive
            ]);

            $status = $tag->isActive ? 'activated' : 'deactivated';

            return response()->json([
                'success' => true,
                'message' => "Tag {$status} successfully!",
                'isActive' => $tag->isActive
            ]);

        } catch (\Exception $e) {
            Log::error('Error toggling tag status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the tag status.'
            ], 500);
        }
    }

    /**
     * Delete (soft) a special tag.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $tag = EcomStoreSpecialTag::active()->findOrFail($id);

            $tag->update([
                'deleteStatus' => 0
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Special tag deleted successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting special tag: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the tag.'
            ], 500);
        }
    }

    /**
     * Get tag data for editing.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $tag = EcomStoreSpecialTag::active()->findOrFail($id);

            return response()->json([
                'success' => true,
                'tag' => $tag
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tag not found.'
            ], 404);
        }
    }
}
