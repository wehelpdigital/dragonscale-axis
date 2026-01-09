<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\EcomProductStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class StoreLoginsController extends Controller
{
    /**
     * Display the store logins page.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $storeId = $request->query('id');

        if (!$storeId) {
            return redirect()->route('ecom-stores')->with('error', 'Store ID is required.');
        }

        $store = EcomProductStore::where('deleteStatus', 1)->findOrFail($storeId);

        // Get logins from clients_access_login table using store name
        $query = DB::table('clients_access_login')
            ->where('productStore', $store->storeName)
            ->where('deleteStatus', 1);

        // Search by name, phone, or email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('clientFirstName', 'like', "%{$search}%")
                  ->orWhere('clientMiddleName', 'like', "%{$search}%")
                  ->orWhere('clientLastName', 'like', "%{$search}%")
                  ->orWhere('clientPhoneNumber', 'like', "%{$search}%")
                  ->orWhere('clientEmailAddress', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('isActive', $request->status);
        }

        $logins = $query->orderBy('clientFirstName', 'asc')
            ->orderBy('clientLastName', 'asc')
            ->get();

        return view('ecommerce.stores.logins', compact('store', 'logins'));
    }

    /**
     * Store a new access login.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $storeId = $request->query('id');

            if (!$storeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Store ID is required.'
                ], 400);
            }

            $store = EcomProductStore::where('deleteStatus', 1)->find($storeId);

            if (!$store) {
                return response()->json([
                    'success' => false,
                    'message' => 'Store not found.'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'clientFirstName' => 'required|string|max:255',
                'clientMiddleName' => 'nullable|string|max:255',
                'clientLastName' => 'required|string|max:255',
                'clientPhoneNumber' => 'nullable|string|max:50',
                'clientEmailAddress' => 'nullable|email|max:255',
                'clientPassword' => 'nullable|string|max:255',
            ], [
                'clientFirstName.required' => 'First name is required.',
                'clientLastName.required' => 'Last name is required.',
                'clientEmailAddress.email' => 'Please enter a valid email address.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check for duplicate phone number in the same store
            if ($request->filled('clientPhoneNumber')) {
                $existingPhone = DB::table('clients_access_login')
                    ->where('productStore', $store->storeName)
                    ->where('clientPhoneNumber', $request->clientPhoneNumber)
                    ->where('deleteStatus', 1)
                    ->exists();

                if ($existingPhone) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This phone number already exists for this store.'
                    ], 422);
                }
            }

            // Check for duplicate email in the same store
            if ($request->filled('clientEmailAddress')) {
                $existingEmail = DB::table('clients_access_login')
                    ->where('productStore', $store->storeName)
                    ->where('clientEmailAddress', $request->clientEmailAddress)
                    ->where('deleteStatus', 1)
                    ->exists();

                if ($existingEmail) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This email address already exists for this store.'
                    ], 422);
                }
            }

            $loginId = DB::table('clients_access_login')->insertGetId([
                'productStore' => $store->storeName,
                'clientFirstName' => $request->clientFirstName,
                'clientMiddleName' => $request->clientMiddleName,
                'clientLastName' => $request->clientLastName,
                'clientPhoneNumber' => $request->clientPhoneNumber,
                'clientEmailAddress' => $request->clientEmailAddress,
                'clientPassword' => $request->filled('clientPassword') ? Hash::make($request->clientPassword) : null,
                'isActive' => 1,
                'deleteStatus' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('Access login created', [
                'store_id' => $storeId,
                'store_name' => $store->storeName,
                'login_id' => $loginId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Access login created successfully!',
                'login_id' => $loginId
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating access login: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the access login.'
            ], 500);
        }
    }

    /**
     * Get a single login for editing.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        try {
            $loginId = $request->query('login_id');
            $storeId = $request->query('id');

            if (!$loginId || !$storeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Login ID and Store ID are required.'
                ], 400);
            }

            $store = EcomProductStore::where('deleteStatus', 1)->find($storeId);

            if (!$store) {
                return response()->json([
                    'success' => false,
                    'message' => 'Store not found.'
                ], 404);
            }

            $login = DB::table('clients_access_login')
                ->where('id', $loginId)
                ->where('productStore', $store->storeName)
                ->where('deleteStatus', 1)
                ->first();

            if (!$login) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access login not found.'
                ], 404);
            }

            // Don't return the actual password hash
            $loginData = (array) $login;
            $loginData['hasPassword'] = !empty($login->clientPassword);
            unset($loginData['clientPassword']);

            return response()->json([
                'success' => true,
                'login' => $loginData
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching access login: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching the access login.'
            ], 500);
        }
    }

    /**
     * Update an existing access login.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        try {
            $loginId = $request->query('login_id');
            $storeId = $request->query('id');

            if (!$loginId || !$storeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Login ID and Store ID are required.'
                ], 400);
            }

            $store = EcomProductStore::where('deleteStatus', 1)->find($storeId);

            if (!$store) {
                return response()->json([
                    'success' => false,
                    'message' => 'Store not found.'
                ], 404);
            }

            $login = DB::table('clients_access_login')
                ->where('id', $loginId)
                ->where('productStore', $store->storeName)
                ->where('deleteStatus', 1)
                ->first();

            if (!$login) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access login not found.'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'clientFirstName' => 'required|string|max:255',
                'clientMiddleName' => 'nullable|string|max:255',
                'clientLastName' => 'required|string|max:255',
                'clientPhoneNumber' => 'nullable|string|max:50',
                'clientEmailAddress' => 'nullable|email|max:255',
                'clientPassword' => 'nullable|string|max:255',
            ], [
                'clientFirstName.required' => 'First name is required.',
                'clientLastName.required' => 'Last name is required.',
                'clientEmailAddress.email' => 'Please enter a valid email address.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check for duplicate phone number in the same store (excluding current record)
            if ($request->filled('clientPhoneNumber')) {
                $existingPhone = DB::table('clients_access_login')
                    ->where('productStore', $store->storeName)
                    ->where('clientPhoneNumber', $request->clientPhoneNumber)
                    ->where('id', '!=', $loginId)
                    ->where('deleteStatus', 1)
                    ->exists();

                if ($existingPhone) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This phone number already exists for this store.'
                    ], 422);
                }
            }

            // Check for duplicate email in the same store (excluding current record)
            if ($request->filled('clientEmailAddress')) {
                $existingEmail = DB::table('clients_access_login')
                    ->where('productStore', $store->storeName)
                    ->where('clientEmailAddress', $request->clientEmailAddress)
                    ->where('id', '!=', $loginId)
                    ->where('deleteStatus', 1)
                    ->exists();

                if ($existingEmail) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This email address already exists for this store.'
                    ], 422);
                }
            }

            $updateData = [
                'clientFirstName' => $request->clientFirstName,
                'clientMiddleName' => $request->clientMiddleName,
                'clientLastName' => $request->clientLastName,
                'clientPhoneNumber' => $request->clientPhoneNumber,
                'clientEmailAddress' => $request->clientEmailAddress,
                'updated_at' => now(),
            ];

            // Only update password if provided
            if ($request->filled('clientPassword')) {
                $updateData['clientPassword'] = Hash::make($request->clientPassword);
            }

            DB::table('clients_access_login')
                ->where('id', $loginId)
                ->update($updateData);

            Log::info('Access login updated', [
                'store_id' => $storeId,
                'login_id' => $loginId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Access login updated successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating access login: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the access login.'
            ], 500);
        }
    }

    /**
     * Delete (soft) an access login.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request)
    {
        try {
            $loginId = $request->query('login_id');
            $storeId = $request->query('id');

            if (!$loginId || !$storeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Login ID and Store ID are required.'
                ], 400);
            }

            $store = EcomProductStore::where('deleteStatus', 1)->find($storeId);

            if (!$store) {
                return response()->json([
                    'success' => false,
                    'message' => 'Store not found.'
                ], 404);
            }

            $login = DB::table('clients_access_login')
                ->where('id', $loginId)
                ->where('productStore', $store->storeName)
                ->where('deleteStatus', 1)
                ->first();

            if (!$login) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access login not found.'
                ], 404);
            }

            DB::table('clients_access_login')
                ->where('id', $loginId)
                ->update([
                    'deleteStatus' => 0,
                    'updated_at' => now()
                ]);

            Log::info('Access login deleted', [
                'store_id' => $storeId,
                'login_id' => $loginId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Access login deleted successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting access login: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the access login.'
            ], 500);
        }
    }

    /**
     * Toggle login active status.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus(Request $request)
    {
        try {
            $loginId = $request->query('login_id');
            $storeId = $request->query('id');

            if (!$loginId || !$storeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Login ID and Store ID are required.'
                ], 400);
            }

            $store = EcomProductStore::where('deleteStatus', 1)->find($storeId);

            if (!$store) {
                return response()->json([
                    'success' => false,
                    'message' => 'Store not found.'
                ], 404);
            }

            $login = DB::table('clients_access_login')
                ->where('id', $loginId)
                ->where('productStore', $store->storeName)
                ->where('deleteStatus', 1)
                ->first();

            if (!$login) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access login not found.'
                ], 404);
            }

            $newStatus = $login->isActive ? 0 : 1;

            DB::table('clients_access_login')
                ->where('id', $loginId)
                ->update([
                    'isActive' => $newStatus,
                    'updated_at' => now()
                ]);

            $statusText = $newStatus ? 'enabled' : 'disabled';

            return response()->json([
                'success' => true,
                'message' => "Access login has been {$statusText}.",
                'isActive' => $newStatus
            ]);

        } catch (\Exception $e) {
            Log::error('Error toggling access login status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while toggling the status.'
            ], 500);
        }
    }

    /**
     * Check if phone number exists for the store (among active records).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkPhone(Request $request)
    {
        try {
            $storeId = $request->query('id');
            $phone = $request->query('phone');
            $excludeId = $request->query('exclude_id');

            if (!$storeId || !$phone) {
                return response()->json([
                    'success' => false,
                    'message' => 'Store ID and phone number are required.'
                ], 400);
            }

            $store = EcomProductStore::where('deleteStatus', 1)->find($storeId);

            if (!$store) {
                return response()->json([
                    'success' => false,
                    'message' => 'Store not found.'
                ], 404);
            }

            $query = DB::table('clients_access_login')
                ->where('productStore', $store->storeName)
                ->where('clientPhoneNumber', $phone)
                ->where('deleteStatus', 1);

            // Exclude current record when editing
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            $exists = $query->exists();

            return response()->json([
                'success' => true,
                'exists' => $exists,
                'message' => $exists ? 'This phone number already exists for this store.' : 'Phone number is available.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error checking phone: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while checking phone number.'
            ], 500);
        }
    }

    /**
     * Check if email exists for the store (among active records).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkEmail(Request $request)
    {
        try {
            $storeId = $request->query('id');
            $email = $request->query('email');
            $excludeId = $request->query('exclude_id');

            if (!$storeId || !$email) {
                return response()->json([
                    'success' => false,
                    'message' => 'Store ID and email are required.'
                ], 400);
            }

            $store = EcomProductStore::where('deleteStatus', 1)->find($storeId);

            if (!$store) {
                return response()->json([
                    'success' => false,
                    'message' => 'Store not found.'
                ], 404);
            }

            $query = DB::table('clients_access_login')
                ->where('productStore', $store->storeName)
                ->where('clientEmailAddress', $email)
                ->where('deleteStatus', 1);

            // Exclude current record when editing
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            $exists = $query->exists();

            return response()->json([
                'success' => true,
                'exists' => $exists,
                'message' => $exists ? 'This email address already exists for this store.' : 'Email is available.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error checking email: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while checking email.'
            ], 500);
        }
    }
}
