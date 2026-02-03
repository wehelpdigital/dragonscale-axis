<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CrmLead;
use App\Models\CrmLeadCustomData;
use App\Models\EcomProductStore;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LeadsApiController extends Controller
{
    /**
     * Add a new lead via GET request
     *
     * Required parameters: firstName OR fullName, email, store_ids
     * Optional: All other lead fields + custom fields via custom[fieldName]=value
     */
    public function addLead(Request $request)
    {
        // Get API key from request
        $apiKey = $request->query('api_key');

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => 'API key is required',
                'error_code' => 'MISSING_API_KEY'
            ], 401);
        }

        // Validate API key - find user by API key
        $user = User::where('api_key', $apiKey)->where('delete_status', 'active')->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid API key',
                'error_code' => 'INVALID_API_KEY'
            ], 401);
        }

        // Handle fullName splitting if provided instead of firstName/lastName
        $firstName = $request->query('firstName');
        $lastName = $request->query('lastName');

        if (!$firstName && $request->query('fullName')) {
            $nameParts = explode(' ', trim($request->query('fullName')), 2);
            $firstName = $nameParts[0] ?? '';
            $lastName = $nameParts[1] ?? '';
        }

        // Build validation rules
        $validator = Validator::make(array_merge($request->query(), [
            'firstName' => $firstName,
            'lastName' => $lastName,
        ]), [
            'firstName' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'store_ids' => 'required|string', // Comma-separated store IDs
            'lastName' => 'nullable|string|max:100',
            'middleName' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'alternatePhone' => 'nullable|string|max:50',
            'companyName' => 'nullable|string|max:255',
            'jobTitle' => 'nullable|string|max:150',
            'department' => 'nullable|string|max:150',
            'industry' => 'nullable|string|max:150',
            'companySize' => 'nullable|string|in:1-10,11-50,51-200,201-500,501-1000,1000+',
            'website' => 'nullable|url|max:255',
            'province' => 'nullable|string|max:100',
            'municipality' => 'nullable|string|max:100',
            'barangay' => 'nullable|string|max:100',
            'streetAddress' => 'nullable|string',
            'zipCode' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'facebookUrl' => 'nullable|url|max:255',
            'instagramUrl' => 'nullable|url|max:255',
            'linkedinUrl' => 'nullable|url|max:255',
            'twitterUrl' => 'nullable|url|max:255',
            'tiktokUrl' => 'nullable|url|max:255',
            'viberNumber' => 'nullable|string|max:50',
            'whatsappNumber' => 'nullable|string|max:50',
            'leadStatus' => 'nullable|string|in:new,contacted,qualified,proposal,negotiation,won,lost,dormant',
            'leadPriority' => 'nullable|string|in:low,medium,high,urgent',
            'referredBy' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ], [
            'firstName.required' => 'First name is required. You can also use fullName parameter.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'store_ids.required' => 'At least one store target is required (store_ids parameter).',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'error_code' => 'VALIDATION_ERROR',
                'errors' => $validator->errors()->toArray()
            ], 400);
        }

        // Validate store IDs
        $storeIds = array_filter(array_map('trim', explode(',', $request->query('store_ids'))));

        if (empty($storeIds)) {
            return response()->json([
                'success' => false,
                'error' => 'At least one valid store ID is required',
                'error_code' => 'INVALID_STORE_IDS'
            ], 400);
        }

        // Verify stores exist and are active
        $validStores = EcomProductStore::whereIn('id', $storeIds)
            ->active()
            ->enabled()
            ->pluck('id')
            ->toArray();

        if (empty($validStores)) {
            return response()->json([
                'success' => false,
                'error' => 'No valid active stores found with the provided IDs',
                'error_code' => 'STORES_NOT_FOUND',
                'provided_ids' => $storeIds
            ], 400);
        }

        // Check for duplicate email in user's leads
        $existingLead = CrmLead::active()
            ->forUser($user->id)
            ->where('email', $request->query('email'))
            ->first();

        if ($existingLead) {
            return response()->json([
                'success' => false,
                'error' => 'A lead with this email already exists',
                'error_code' => 'DUPLICATE_EMAIL',
                'existing_lead_id' => $existingLead->id
            ], 409);
        }

        try {
            // Create the lead
            $lead = CrmLead::create([
                'usersId' => $user->id,
                'firstName' => $firstName,
                'middleName' => $request->query('middleName'),
                'lastName' => $lastName,
                'email' => $request->query('email'),
                'phone' => $request->query('phone'),
                'alternatePhone' => $request->query('alternatePhone'),
                'companyName' => $request->query('companyName'),
                'jobTitle' => $request->query('jobTitle'),
                'department' => $request->query('department'),
                'industry' => $request->query('industry'),
                'companySize' => $request->query('companySize'),
                'website' => $request->query('website'),
                'province' => $request->query('province'),
                'municipality' => $request->query('municipality'),
                'barangay' => $request->query('barangay'),
                'streetAddress' => $request->query('streetAddress'),
                'zipCode' => $request->query('zipCode'),
                'country' => $request->query('country', 'Philippines'),
                'facebookUrl' => $request->query('facebookUrl'),
                'instagramUrl' => $request->query('instagramUrl'),
                'linkedinUrl' => $request->query('linkedinUrl'),
                'twitterUrl' => $request->query('twitterUrl'),
                'tiktokUrl' => $request->query('tiktokUrl'),
                'viberNumber' => $request->query('viberNumber'),
                'whatsappNumber' => $request->query('whatsappNumber'),
                'leadStatus' => $request->query('leadStatus', 'new'),
                'leadPriority' => $request->query('leadPriority', 'medium'),
                'referredBy' => $request->query('referredBy'),
                'notes' => $request->query('notes'),
                'delete_status' => 'active',
            ]);

            // Attach store targets
            $lead->targetStores()->sync($validStores);

            // Handle custom fields (custom[fieldName]=value format)
            $customFields = $request->query('custom', []);
            if (is_array($customFields) && !empty($customFields)) {
                foreach ($customFields as $fieldName => $fieldValue) {
                    if (!empty($fieldName) && $fieldValue !== null && $fieldValue !== '') {
                        CrmLeadCustomData::create([
                            'leadId' => $lead->id,
                            'fieldName' => $fieldName,
                            'fieldValue' => $fieldValue,
                            'usersId' => $user->id,
                            'delete_status' => 'active',
                        ]);
                    }
                }
            }

            // Log the activity
            $lead->logActivity('api_created', 'Lead created via API', $user->id);

            // Load relationships for response
            $lead->load(['targetStores', 'customData']);

            return response()->json([
                'success' => true,
                'message' => 'Lead created successfully',
                'data' => [
                    'id' => $lead->id,
                    'fullName' => $lead->fullName,
                    'email' => $lead->email,
                    'phone' => $lead->phone,
                    'status' => $lead->leadStatus,
                    'priority' => $lead->leadPriority,
                    'stores' => $lead->targetStores->pluck('storeName', 'id'),
                    'customFields' => $lead->customData->pluck('fieldValue', 'fieldName'),
                    'created_at' => $lead->created_at->format('Y-m-d H:i:s'),
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to create lead',
                'error_code' => 'SERVER_ERROR',
                'message' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred'
            ], 500);
        }
    }

    /**
     * Get list of available stores for the API user
     */
    public function getStores(Request $request)
    {
        $apiKey = $request->query('api_key');

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => 'API key is required',
                'error_code' => 'MISSING_API_KEY'
            ], 401);
        }

        $user = User::where('api_key', $apiKey)->where('delete_status', 'active')->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid API key',
                'error_code' => 'INVALID_API_KEY'
            ], 401);
        }

        $stores = EcomProductStore::active()
            ->enabled()
            ->orderBy('storeName')
            ->get(['id', 'storeName']);

        return response()->json([
            'success' => true,
            'data' => $stores
        ]);
    }
}
