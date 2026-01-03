<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\EcomProductStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;

class StoresController extends Controller
{
    /**
     * Display a listing of stores.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        $query = EcomProductStore::active();

        // Filter by store name
        if ($request->filled('name')) {
            $query->where('storeName', 'like', '%' . $request->name . '%');
        }

        // Filter by status
        if ($request->has('status') && $request->status !== '' && $request->status !== null) {
            $query->where('isActive', $request->status);
        }

        $stores = $query->orderBy('storeName', 'asc')->paginate(20);

        return view('ecommerce.stores.index', compact('stores'));
    }

    /**
     * Show the form for creating a new store.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function create()
    {
        return view('ecommerce.stores.create');
    }

    /**
     * Store a newly created store in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'storeName' => 'required|string|max:255|unique:ecom_product_stores,storeName',
            'storeDescription' => 'nullable|string|max:1000',
            'storeLogo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'isActive' => 'required|in:0,1',
        ], [
            'storeName.required' => 'Store name is required.',
            'storeName.unique' => 'This store name already exists.',
            'storeName.max' => 'Store name cannot exceed 255 characters.',
            'storeDescription.max' => 'Store description cannot exceed 1000 characters.',
            'storeLogo.image' => 'Store logo must be an image.',
            'storeLogo.mimes' => 'Store logo must be a JPEG, PNG, JPG, GIF, or SVG file.',
            'storeLogo.max' => 'Store logo cannot exceed 2MB.',
            'isActive.required' => 'Status is required.',
            'isActive.in' => 'Status must be Active or Inactive.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $logoPath = null;

            // Handle logo upload
            if ($request->hasFile('storeLogo')) {
                $logo = $request->file('storeLogo');
                $logoName = time() . '_' . uniqid() . '.' . $logo->getClientOriginalExtension();
                $logo->move(public_path('images/stores'), $logoName);
                $logoPath = 'images/stores/' . $logoName;
            }

            EcomProductStore::create([
                'storeName' => $request->storeName,
                'storeDescription' => $request->storeDescription,
                'storeLogo' => $logoPath,
                'isActive' => $request->isActive,
                'deleteStatus' => 1, // Active by default
            ]);

            return redirect()->route('ecom-stores')
                ->with('success', 'Store created successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'An error occurred while creating the store. Please try again.')
                ->withInput();
        }
    }

    /**
     * Show the form for editing the specified store.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function edit(Request $request)
    {
        $store = EcomProductStore::active()->findOrFail($request->id);
        return view('ecommerce.stores.edit', compact('store'));
    }

    /**
     * Update the specified store in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $store = EcomProductStore::active()->findOrFail($id);

        // Handle status-only update (AJAX request)
        if ($request->has('_status_only')) {
            try {
                $store->update(['isActive' => $request->isActive]);

                return response()->json([
                    'success' => true,
                    'message' => 'Store status updated successfully.'
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while updating the store status.'
                ], 500);
            }
        }

        $validator = Validator::make($request->all(), [
            'storeName' => 'required|string|max:255|unique:ecom_product_stores,storeName,' . $id,
            'storeDescription' => 'nullable|string|max:1000',
            'storeLogo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'isActive' => 'required|in:0,1',
        ], [
            'storeName.required' => 'Store name is required.',
            'storeName.unique' => 'This store name already exists.',
            'storeName.max' => 'Store name cannot exceed 255 characters.',
            'storeDescription.max' => 'Store description cannot exceed 1000 characters.',
            'storeLogo.image' => 'Store logo must be an image.',
            'storeLogo.mimes' => 'Store logo must be a JPEG, PNG, JPG, GIF, or SVG file.',
            'storeLogo.max' => 'Store logo cannot exceed 2MB.',
            'isActive.required' => 'Status is required.',
            'isActive.in' => 'Status must be Active or Inactive.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $logoPath = $store->storeLogo;

            // Handle logo upload
            if ($request->hasFile('storeLogo')) {
                // Delete old logo if exists
                if ($store->storeLogo && File::exists(public_path($store->storeLogo))) {
                    File::delete(public_path($store->storeLogo));
                }

                $logo = $request->file('storeLogo');
                $logoName = time() . '_' . uniqid() . '.' . $logo->getClientOriginalExtension();
                $logo->move(public_path('images/stores'), $logoName);
                $logoPath = 'images/stores/' . $logoName;
            }

            // Check if store name is being changed and update related products
            $oldStoreName = $store->storeName;
            $newStoreName = $request->storeName;

            $store->update([
                'storeName' => $newStoreName,
                'storeDescription' => $request->storeDescription,
                'storeLogo' => $logoPath,
                'isActive' => $request->isActive,
            ]);

            // If store name changed, update all products using this store
            if ($oldStoreName !== $newStoreName) {
                \App\Models\EcomProduct::where('productStore', $oldStoreName)
                    ->update(['productStore' => $newStoreName]);
            }

            return redirect()->route('ecom-stores')
                ->with('success', 'Store updated successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'An error occurred while updating the store. Please try again.')
                ->withInput();
        }
    }

    /**
     * Update the store status via AJAX.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $store = EcomProductStore::active()->find($id);

            if (!$store) {
                return response()->json([
                    'success' => false,
                    'message' => 'Store not found.'
                ], 404);
            }

            $store->update(['isActive' => $request->isActive]);

            return response()->json([
                'success' => true,
                'message' => 'Store status updated successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the store status.'
            ], 500);
        }
    }

    /**
     * Soft delete the specified store.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $store = EcomProductStore::active()->find($id);

            if (!$store) {
                return response()->json([
                    'success' => false,
                    'message' => 'Store not found or already deleted.'
                ], 404);
            }

            // Soft delete - set deleteStatus to 0
            $store->update(['deleteStatus' => 0]);

            return response()->json([
                'success' => true,
                'message' => 'Store deleted successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the store.'
            ], 500);
        }
    }

    /**
     * Remove the store logo.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeLogo($id)
    {
        try {
            $store = EcomProductStore::active()->find($id);

            if (!$store) {
                return response()->json([
                    'success' => false,
                    'message' => 'Store not found.'
                ], 404);
            }

            // Delete the logo file if exists
            if ($store->storeLogo && File::exists(public_path($store->storeLogo))) {
                File::delete(public_path($store->storeLogo));
            }

            $store->update(['storeLogo' => null]);

            return response()->json([
                'success' => true,
                'message' => 'Store logo removed successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while removing the logo.'
            ], 500);
        }
    }
}
