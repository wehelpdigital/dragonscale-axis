<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\ClientAllDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ClientsController extends Controller
{
    /**
     * Display the clients list page.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        return view('ecommerce.clients.index');
    }

    /**
     * Get clients data for AJAX with pagination.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getData(Request $request)
    {
        try {
            $query = ClientAllDatabase::active();

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

            // Get total count before pagination (only active clients)
            $totalCount = ClientAllDatabase::active()->count();
            $filteredCount = $query->count();

            // Pagination
            $perPage = $request->input('per_page', 15);
            $page = $request->input('page', 1);

            $clients = $query->orderBy('clientFirstName', 'asc')
                ->orderBy('clientLastName', 'asc')
                ->paginate($perPage, ['*'], 'page', $page);

            // Format clients data
            $clientsData = $clients->map(function($client) {
                $colors = ['#556ee6', '#34c38f', '#f46a6a', '#50a5f1', '#f1b44c', '#74788d'];
                $colorIndex = $client->id % count($colors);

                return [
                    'id' => $client->id,
                    'clientFirstName' => $client->clientFirstName,
                    'clientMiddleName' => $client->clientMiddleName,
                    'clientLastName' => $client->clientLastName,
                    'clientPhoneNumber' => $client->clientPhoneNumber,
                    'clientEmailAddress' => $client->clientEmailAddress,
                    'fullName' => $client->full_name,
                    'initials' => strtoupper(substr($client->clientFirstName ?? '', 0, 1) . substr($client->clientLastName ?? '', 0, 1)),
                    'avatarColor' => $colors[$colorIndex],
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $clientsData,
                'pagination' => [
                    'current_page' => $clients->currentPage(),
                    'last_page' => $clients->lastPage(),
                    'per_page' => $clients->perPage(),
                    'total' => $clients->total(),
                    'from' => $clients->firstItem(),
                    'to' => $clients->lastItem(),
                ],
                'total_count' => $totalCount,
                'filtered_count' => $filteredCount,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching clients data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching clients.'
            ], 500);
        }
    }

    /**
     * Store a new client.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'clientFirstName' => 'required|string|max:255',
                'clientMiddleName' => 'nullable|string|max:255',
                'clientLastName' => 'required|string|max:255',
                'clientPhoneNumber' => 'nullable|string|max:50',
                'clientEmailAddress' => 'nullable|email|max:255',
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

            // Check for duplicate phone number (only among active clients)
            if ($request->filled('clientPhoneNumber')) {
                $existingPhone = ClientAllDatabase::active()
                    ->where('clientPhoneNumber', $request->clientPhoneNumber)
                    ->exists();

                if ($existingPhone) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This phone number already exists.'
                    ], 422);
                }
            }

            // Check for duplicate email (only among active clients)
            if ($request->filled('clientEmailAddress')) {
                $existingEmail = ClientAllDatabase::active()
                    ->where('clientEmailAddress', $request->clientEmailAddress)
                    ->exists();

                if ($existingEmail) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This email address already exists.'
                    ], 422);
                }
            }

            $client = ClientAllDatabase::create([
                'clientFirstName' => $request->clientFirstName,
                'clientMiddleName' => $request->clientMiddleName,
                'clientLastName' => $request->clientLastName,
                'clientPhoneNumber' => $request->clientPhoneNumber,
                'clientEmailAddress' => $request->clientEmailAddress,
                'deleteStatus' => 1,
            ]);

            Log::info('Client created', ['client_id' => $client->id]);

            return response()->json([
                'success' => true,
                'message' => 'Client created successfully!',
                'client_id' => $client->id
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating client: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the client.'
            ], 500);
        }
    }

    /**
     * Get a single client for editing.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        try {
            $clientId = $request->query('id');

            if (!$clientId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client ID is required.'
                ], 400);
            }

            $client = ClientAllDatabase::active()->find($clientId);

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client not found.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'client' => $client
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching client: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching the client.'
            ], 500);
        }
    }

    /**
     * Update an existing client.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        try {
            $clientId = $request->query('id');

            if (!$clientId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client ID is required.'
                ], 400);
            }

            $client = ClientAllDatabase::active()->find($clientId);

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client not found.'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'clientFirstName' => 'required|string|max:255',
                'clientMiddleName' => 'nullable|string|max:255',
                'clientLastName' => 'required|string|max:255',
                'clientPhoneNumber' => 'nullable|string|max:50',
                'clientEmailAddress' => 'nullable|email|max:255',
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

            // Check for duplicate phone number (excluding current record, only active clients)
            if ($request->filled('clientPhoneNumber')) {
                $existingPhone = ClientAllDatabase::active()
                    ->where('clientPhoneNumber', $request->clientPhoneNumber)
                    ->where('id', '!=', $clientId)
                    ->exists();

                if ($existingPhone) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This phone number already exists.'
                    ], 422);
                }
            }

            // Check for duplicate email (excluding current record, only active clients)
            if ($request->filled('clientEmailAddress')) {
                $existingEmail = ClientAllDatabase::active()
                    ->where('clientEmailAddress', $request->clientEmailAddress)
                    ->where('id', '!=', $clientId)
                    ->exists();

                if ($existingEmail) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This email address already exists.'
                    ], 422);
                }
            }

            $client->update([
                'clientFirstName' => $request->clientFirstName,
                'clientMiddleName' => $request->clientMiddleName,
                'clientLastName' => $request->clientLastName,
                'clientPhoneNumber' => $request->clientPhoneNumber,
                'clientEmailAddress' => $request->clientEmailAddress,
            ]);

            Log::info('Client updated', ['client_id' => $clientId]);

            return response()->json([
                'success' => true,
                'message' => 'Client updated successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating client: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the client.'
            ], 500);
        }
    }

    /**
     * Soft delete a client.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request)
    {
        try {
            $clientId = $request->query('id');

            if (!$clientId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client ID is required.'
                ], 400);
            }

            $client = ClientAllDatabase::active()->find($clientId);

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client not found.'
                ], 404);
            }

            // Soft delete by setting deleteStatus to 0
            $client->update(['deleteStatus' => 0]);

            Log::info('Client soft deleted', ['client_id' => $clientId]);

            return response()->json([
                'success' => true,
                'message' => 'Client deleted successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting client: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the client.'
            ], 500);
        }
    }

    /**
     * Check if phone number exists (among active clients).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkPhone(Request $request)
    {
        try {
            $phone = $request->query('phone');
            $excludeId = $request->query('exclude_id');

            if (!$phone) {
                return response()->json([
                    'success' => false,
                    'message' => 'Phone number is required.'
                ], 400);
            }

            $query = ClientAllDatabase::active()
                ->where('clientPhoneNumber', $phone);

            // Exclude current record when editing
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            $exists = $query->exists();

            return response()->json([
                'success' => true,
                'exists' => $exists,
                'message' => $exists ? 'This phone number already exists.' : 'Phone number is available.'
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
     * Check if email exists (among active clients).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkEmail(Request $request)
    {
        try {
            $email = $request->query('email');
            $excludeId = $request->query('exclude_id');

            if (!$email) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email address is required.'
                ], 400);
            }

            $query = ClientAllDatabase::active()
                ->where('clientEmailAddress', $email);

            // Exclude current record when editing
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            $exists = $query->exists();

            return response()->json([
                'success' => true,
                'exists' => $exists,
                'message' => $exists ? 'This email address already exists.' : 'Email is available.'
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
