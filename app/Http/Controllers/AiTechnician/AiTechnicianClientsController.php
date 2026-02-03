<?php

namespace App\Http\Controllers\AiTechnician;

use App\Http\Controllers\Controller;
use App\Models\AiTechnicianClientAccess;
use App\Models\ClientAccessLogin;
use App\Models\EcomProductStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AiTechnicianClientsController extends Controller
{
    /**
     * Display the clients list page.
     */
    public function index(Request $request)
    {
        // Get all active stores for the dropdown filter
        $stores = EcomProductStore::where('deleteStatus', 1)
            ->where('isActive', 1)
            ->orderBy('storeName')
            ->get();

        return view('ai-technician.clients.index', compact('stores'));
    }

    /**
     * Get clients list (AJAX).
     */
    public function getClients(Request $request)
    {
        try {
            $query = AiTechnicianClientAccess::active()
                ->forUser(Auth::id());

            // Apply search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereIn('accessClientId', function ($q) use ($search) {
                    $q->select('id')
                        ->from('clients_access_login')
                        ->where('deleteStatus', 1)
                        ->where(function ($sq) use ($search) {
                            $sq->where('clientFirstName', 'like', "%{$search}%")
                                ->orWhere('clientLastName', 'like', "%{$search}%")
                                ->orWhere('clientEmailAddress', 'like', "%{$search}%")
                                ->orWhere('clientPhoneNumber', 'like', "%{$search}%");
                        });
                });
            }

            // Apply status filter
            if ($request->filled('status')) {
                if ($request->status === 'expired') {
                    $query->whereNotNull('expirationDate')
                          ->where('expirationDate', '<', Carbon::now());
                } elseif ($request->status === 'active') {
                    $query->where('isActive', true)
                          ->where(function ($q) {
                              $q->whereNull('expirationDate')
                                ->orWhere('expirationDate', '>=', Carbon::now());
                          });
                } elseif ($request->status === 'inactive') {
                    $query->where('isActive', false);
                } elseif ($request->status === 'expiring') {
                    $query->where('isActive', true)
                          ->whereNotNull('expirationDate')
                          ->where('expirationDate', '>=', Carbon::now())
                          ->where('expirationDate', '<=', Carbon::now()->addDays(7));
                }
            }

            // Apply store filter
            if ($request->filled('store')) {
                $query->whereIn('accessClientId', function ($q) use ($request) {
                    $q->select('id')
                        ->from('clients_access_login')
                        ->where('deleteStatus', 1)
                        ->where('productStore', $request->store);
                });
            }

            // Apply expiration date range filter
            if ($request->filled('expirationFrom')) {
                $query->whereNotNull('expirationDate')
                      ->whereDate('expirationDate', '>=', $request->expirationFrom);
            }
            if ($request->filled('expirationTo')) {
                $query->whereNotNull('expirationDate')
                      ->whereDate('expirationDate', '<=', $request->expirationTo);
            }

            $accessRecords = $query->orderBy('grantedAt', 'desc')->get();

            // Fetch client details
            $clientIds = $accessRecords->pluck('accessClientId')->unique();
            $clients = DB::table('clients_access_login')
                ->whereIn('id', $clientIds)
                ->where('deleteStatus', 1)
                ->get()
                ->keyBy('id');

            // Build response
            $clientsList = $accessRecords->map(function ($access) use ($clients) {
                $client = $clients->get($access->accessClientId);

                return [
                    'accessId' => $access->id,
                    'accessClientId' => $access->accessClientId,
                    'fullName' => $client
                        ? trim("{$client->clientFirstName} {$client->clientMiddleName} {$client->clientLastName}")
                        : 'Unknown',
                    'email' => $client->clientEmailAddress ?? 'N/A',
                    'phone' => $client->clientPhoneNumber ?? 'N/A',
                    'store' => $client->productStore ?? 'N/A',
                    'grantedAt' => $access->grantedAt->format('M j, Y'),
                    'expirationDate' => $access->expirationDate?->format('Y-m-d'),
                    'formattedExpiration' => $access->formatted_expiration,
                    'daysRemaining' => $access->days_remaining,
                    'isExpired' => $access->is_expired,
                    'isActive' => $access->isActive,
                    'statusLabel' => $access->status_label,
                    'statusBadge' => $access->status_badge,
                    'expirationBadge' => $access->expiration_badge,
                    'notes' => $access->notes
                ];
            });

            // Calculate stats
            $totalClients = $clientsList->count();
            $activeClients = $clientsList->where('statusLabel', 'Active')->count();
            $expiredClients = $clientsList->where('isExpired', true)->count();
            $expiringClients = $clientsList->filter(function ($c) {
                return !$c['isExpired'] && $c['daysRemaining'] !== null && $c['daysRemaining'] <= 7;
            })->count();

            return response()->json([
                'success' => true,
                'clients' => $clientsList,
                'stats' => [
                    'total' => $totalClients,
                    'active' => $activeClients,
                    'expired' => $expiredClients,
                    'expiring' => $expiringClients
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching AI Technician clients: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load clients'
            ], 500);
        }
    }

    /**
     * Get a single client access for editing.
     */
    public function getClient($id)
    {
        try {
            $access = AiTechnicianClientAccess::active()
                ->forUser(Auth::id())
                ->findOrFail($id);

            $client = DB::table('clients_access_login')
                ->where('id', $access->accessClientId)
                ->where('deleteStatus', 1)
                ->first();

            return response()->json([
                'success' => true,
                'access' => [
                    'id' => $access->id,
                    'accessClientId' => $access->accessClientId,
                    'grantedAt' => $access->grantedAt->format('Y-m-d'),
                    'expirationDate' => $access->expirationDate?->format('Y-m-d'),
                    'isActive' => $access->isActive,
                    'notes' => $access->notes,
                ],
                'client' => $client ? [
                    'id' => $client->id,
                    'fullName' => trim("{$client->clientFirstName} {$client->clientMiddleName} {$client->clientLastName}"),
                    'email' => $client->clientEmailAddress,
                    'phone' => $client->clientPhoneNumber,
                    'store' => $client->productStore
                ] : null
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Client access not found'
            ], 404);
        }
    }

    /**
     * Search available clients (not yet granted AI Technician access).
     * Searches only in Ani-Senso store logins.
     */
    public function searchAvailableClients(Request $request)
    {
        try {
            $search = $request->search ?? '';
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 20);

            // Get already granted client IDs
            $grantedIds = AiTechnicianClientAccess::active()
                ->forUser(Auth::id())
                ->pluck('accessClientId')
                ->toArray();

            // Get Ani-Senso store names (stores that contain "Ani-Senso" or similar)
            $aniSensoStores = EcomProductStore::where('deleteStatus', 1)
                ->where('isActive', 1)
                ->where(function ($q) {
                    $q->where('storeName', 'like', '%Ani-Senso%')
                      ->orWhere('storeName', 'like', '%AniSenso%')
                      ->orWhere('storeName', 'like', '%ani-senso%')
                      ->orWhere('storeName', 'like', '%anisenso%');
                })
                ->pluck('storeName')
                ->toArray();

            // If no Ani-Senso stores found, get all active stores as fallback
            if (empty($aniSensoStores)) {
                $aniSensoStores = EcomProductStore::where('deleteStatus', 1)
                    ->where('isActive', 1)
                    ->pluck('storeName')
                    ->toArray();
            }

            // Search in clients_access_login for Ani-Senso stores
            $query = DB::table('clients_access_login')
                ->where('deleteStatus', 1)
                ->where('isActive', 1)
                ->whereIn('productStore', $aniSensoStores);

            if (!empty($grantedIds)) {
                $query->whereNotIn('id', $grantedIds);
            }

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('clientFirstName', 'like', "%{$search}%")
                      ->orWhere('clientMiddleName', 'like', "%{$search}%")
                      ->orWhere('clientLastName', 'like', "%{$search}%")
                      ->orWhere('clientEmailAddress', 'like', "%{$search}%")
                      ->orWhere('clientPhoneNumber', 'like', "%{$search}%");
                });
            }

            // Get total count for pagination
            $total = $query->count();

            // Apply pagination
            $clients = $query->select('id', 'clientFirstName', 'clientMiddleName', 'clientLastName', 'clientPhoneNumber', 'clientEmailAddress', 'productStore')
                ->orderBy('clientFirstName', 'asc')
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get()
                ->map(function ($client) {
                    return [
                        'id' => $client->id,
                        'fullName' => trim("{$client->clientFirstName} {$client->clientMiddleName} {$client->clientLastName}"),
                        'email' => $client->clientEmailAddress,
                        'phone' => $client->clientPhoneNumber,
                        'store' => $client->productStore
                    ];
                });

            $lastPage = ceil($total / $perPage);

            return response()->json([
                'success' => true,
                'data' => $clients,
                'current_page' => (int) $page,
                'last_page' => $lastPage,
                'per_page' => (int) $perPage,
                'total' => $total
            ]);

        } catch (\Exception $e) {
            Log::error('Error searching available clients: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to search clients'
            ], 500);
        }
    }

    /**
     * Grant AI Technician access to a client.
     */
    public function grantAccess(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'accessClientId' => 'required|integer',
                'expirationDate' => 'nullable|date',
                'notes' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            // Check if client exists
            $client = DB::table('clients_access_login')
                ->where('id', $request->accessClientId)
                ->where('deleteStatus', 1)
                ->first();

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client not found'
                ], 404);
            }

            // Check if already granted
            $existing = AiTechnicianClientAccess::active()
                ->forUser(Auth::id())
                ->where('accessClientId', $request->accessClientId)
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'This client already has AI Technician access'
                ], 422);
            }

            $access = AiTechnicianClientAccess::create([
                'usersId' => Auth::id(),
                'accessClientId' => $request->accessClientId,
                'grantedAt' => Carbon::now(),
                'expirationDate' => $request->expirationDate
                    ? Carbon::parse($request->expirationDate)->endOfDay()
                    : null,
                'isActive' => true,
                'notes' => $request->notes,
                'delete_status' => 'active'
            ]);

            $clientName = trim("{$client->clientFirstName} {$client->clientLastName}");
            Log::info("AI Technician access granted to client: {$clientName}", [
                'access_id' => $access->id,
                'client_id' => $request->accessClientId,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Access granted to {$clientName}",
                'accessId' => $access->id
            ]);

        } catch (\Exception $e) {
            Log::error('Error granting AI Technician access: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to grant access'
            ], 500);
        }
    }

    /**
     * Update client access (expiration, status, notes).
     */
    public function updateAccess(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'expirationDate' => 'nullable|date',
                'isActive' => 'nullable|boolean',
                'notes' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $access = AiTechnicianClientAccess::active()
                ->forUser(Auth::id())
                ->findOrFail($id);

            // Update expiration
            if ($request->has('expirationDate')) {
                $access->expirationDate = $request->expirationDate
                    ? Carbon::parse($request->expirationDate)->endOfDay()
                    : null;
            }

            // Update active status
            if ($request->has('isActive')) {
                $access->isActive = $request->isActive;
            }

            // Update notes
            if ($request->has('notes')) {
                $access->notes = $request->notes;
            }

            $access->save();

            return response()->json([
                'success' => true,
                'message' => 'Access updated successfully',
                'access' => [
                    'formattedExpiration' => $access->formatted_expiration,
                    'daysRemaining' => $access->days_remaining,
                    'isExpired' => $access->is_expired,
                    'isActive' => $access->isActive,
                    'statusBadge' => $access->status_badge,
                    'expirationBadge' => $access->expiration_badge
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating AI Technician access: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update access'
            ], 500);
        }
    }

    /**
     * Revoke client access (soft delete).
     */
    public function revokeAccess($id)
    {
        try {
            $access = AiTechnicianClientAccess::active()
                ->forUser(Auth::id())
                ->findOrFail($id);

            // Get client name for logging
            $client = DB::table('clients_access_login')
                ->where('id', $access->accessClientId)
                ->first();
            $clientName = $client ? trim("{$client->clientFirstName} {$client->clientLastName}") : 'Unknown';

            $access->delete_status = 'deleted';
            $access->save();

            Log::info("AI Technician access revoked for client: {$clientName}", [
                'access_id' => $id,
                'client_id' => $access->accessClientId,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Access revoked for {$clientName}"
            ]);

        } catch (\Exception $e) {
            Log::error('Error revoking AI Technician access: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to revoke access'
            ], 500);
        }
    }

    /**
     * Toggle client access status.
     */
    public function toggleStatus($id)
    {
        try {
            $access = AiTechnicianClientAccess::active()
                ->forUser(Auth::id())
                ->findOrFail($id);

            $access->isActive = !$access->isActive;
            $access->save();

            $statusText = $access->isActive ? 'enabled' : 'disabled';

            return response()->json([
                'success' => true,
                'message' => "Access has been {$statusText}",
                'isActive' => $access->isActive,
                'statusBadge' => $access->status_badge
            ]);

        } catch (\Exception $e) {
            Log::error('Error toggling AI Technician access status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle status'
            ], 500);
        }
    }

    /**
     * Extend expiration for multiple clients.
     */
    public function bulkExtend(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'accessIds' => 'required|array|min:1',
                'accessIds.*' => 'integer',
                'extensionDays' => 'required|integer|min:1|max:365'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $updated = 0;
            foreach ($request->accessIds as $accessId) {
                $access = AiTechnicianClientAccess::active()
                    ->forUser(Auth::id())
                    ->find($accessId);

                if ($access) {
                    // If currently no expiration, set from now
                    // If has expiration, extend from current expiration
                    $baseDate = $access->expirationDate ?? Carbon::now();
                    if ($baseDate < Carbon::now()) {
                        $baseDate = Carbon::now();
                    }
                    $access->expirationDate = $baseDate->copy()->addDays($request->extensionDays)->endOfDay();
                    $access->save();
                    $updated++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Extended access for {$updated} client(s) by {$request->extensionDays} days"
            ]);

        } catch (\Exception $e) {
            Log::error('Error bulk extending AI Technician access: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to extend access'
            ], 500);
        }
    }
}
