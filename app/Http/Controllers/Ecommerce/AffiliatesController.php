<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\EcomAffiliate;
use App\Models\EcomAffiliateStore;
use App\Models\EcomAffiliateDocument;
use App\Models\EcomAffiliateReferral;
use App\Models\EcomProductStore;
use App\Models\ClientAllDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class AffiliatesController extends Controller
{
    /**
     * Display a listing of affiliates.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        $query = EcomAffiliate::active()->with(['client', 'stores', 'affiliateStores']);

        // Filter by name
        if ($request->filled('name')) {
            $searchName = $request->name;
            $query->where(function ($q) use ($searchName) {
                $q->where('firstName', 'like', '%' . $searchName . '%')
                  ->orWhere('middleName', 'like', '%' . $searchName . '%')
                  ->orWhere('lastName', 'like', '%' . $searchName . '%');
            });
        }

        // Filter by account status
        if ($request->has('status') && $request->status !== '' && $request->status !== null) {
            $query->where('accountStatus', $request->status);
        }

        // Filter by store
        if ($request->filled('store')) {
            $storeId = $request->store;
            $query->whereHas('affiliateStores', function ($q) use ($storeId) {
                $q->where('storeId', $storeId)->where('deleteStatus', 1);
            });
        }

        // Filter by expiration status
        if ($request->filled('expiration')) {
            if ($request->expiration === 'expired') {
                $query->whereNotNull('expirationDate')
                      ->where('expirationDate', '<', now()->toDateString());
            } elseif ($request->expiration === 'active') {
                $query->where(function ($q) {
                    $q->whereNull('expirationDate')
                      ->orWhere('expirationDate', '>=', now()->toDateString());
                });
            }
        }

        $affiliates = $query->orderBy('lastName', 'asc')
                           ->orderBy('firstName', 'asc')
                           ->paginate(20);

        // Get stores for filter dropdown
        $stores = EcomProductStore::active()->enabled()->orderBy('storeName', 'asc')->get();

        return view('ecommerce.affiliates.index', compact('affiliates', 'stores'));
    }

    /**
     * Show the form for creating a new affiliate.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function create()
    {
        // Get clients that are not already affiliates (only active clients)
        $existingClientIds = EcomAffiliate::active()->pluck('clientId')->toArray();
        $clients = ClientAllDatabase::active()
                                    ->whereNotIn('id', $existingClientIds)
                                    ->orderBy('clientLastName', 'asc')
                                    ->orderBy('clientFirstName', 'asc')
                                    ->get();

        // Get active stores
        $stores = EcomProductStore::active()->enabled()->orderBy('storeName', 'asc')->get();

        return view('ecommerce.affiliates.create', compact('clients', 'stores'));
    }

    /**
     * Store a newly created affiliate in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'clientId' => 'nullable|integer|exists:clients_all_database,id',
            'firstName' => 'required|string|max:100',
            'middleName' => 'nullable|string|max:100',
            'lastName' => 'required|string|max:100',
            'phoneNumber' => 'required|string|max:50',
            'emailAddress' => 'nullable|email|max:255',
            // Location fields
            'houseNumber' => 'nullable|string|max:100',
            'street' => 'nullable|string|max:255',
            'barangay' => 'nullable|string|max:100',
            'zone' => 'nullable|string|max:100',
            'municipality' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'zipCode' => 'nullable|string|max:20',
            // Payment fields
            'bankName' => 'nullable|string|max:255',
            'bankAccountNumber' => 'nullable|string|max:100',
            'bankAccountName' => 'nullable|string|max:255',
            'gcashNumber' => 'nullable|string|max:50',
            'userPhoto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'expirationDate' => 'required|date',
            'accountStatus' => 'required|in:active,inactive',
            'stores' => 'required|array|min:1',
            'stores.*' => 'integer|exists:ecom_product_stores,id',
            'documents' => 'nullable|array',
            'documents.*' => 'nullable|file|mimes:jpeg,png,jpg,gif,pdf,doc,docx|max:5120',
        ], [
            'firstName.required' => 'First name is required.',
            'firstName.max' => 'First name cannot exceed 100 characters.',
            'lastName.required' => 'Last name is required.',
            'lastName.max' => 'Last name cannot exceed 100 characters.',
            'phoneNumber.required' => 'Phone number is required.',
            'emailAddress.email' => 'Please enter a valid email address.',
            'municipality.required' => 'Municipality/City is required.',
            'province.required' => 'Province is required.',
            'userPhoto.image' => 'Photo must be an image file.',
            'userPhoto.mimes' => 'Photo must be a JPEG, PNG, JPG, or GIF file.',
            'userPhoto.max' => 'Photo cannot exceed 2MB.',
            'expirationDate.required' => 'Expiration date is required.',
            'expirationDate.date' => 'Please enter a valid date.',
            'accountStatus.required' => 'Account status is required.',
            'stores.required' => 'Please select at least one store.',
            'stores.min' => 'Please select at least one store.',
            'documents.*.file' => 'Each document must be a valid file.',
            'documents.*.mimes' => 'Documents must be JPEG, PNG, JPG, GIF, PDF, DOC, or DOCX files.',
            'documents.*.max' => 'Each document cannot exceed 5MB.',
        ]);

        // Custom validation: either bank details or gcash must be provided
        $validator->after(function ($validator) use ($request) {
            $hasBankDetails = $request->filled('bankName') &&
                             $request->filled('bankAccountNumber') &&
                             $request->filled('bankAccountName');
            $hasGcash = $request->filled('gcashNumber');

            if (!$hasBankDetails && !$hasGcash) {
                $validator->errors()->add('payment', 'Either bank details or GCash number must be provided.');
            }
        });

        // Check if client is already an affiliate
        if ($request->filled('clientId')) {
            $existingAffiliate = EcomAffiliate::active()
                ->where('clientId', $request->clientId)
                ->first();
            if ($existingAffiliate) {
                return redirect()->back()
                    ->with('error', 'This client is already registered as an affiliate.')
                    ->withInput();
            }
        }

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $photoPath = null;

            // Handle photo upload
            if ($request->hasFile('userPhoto')) {
                $photo = $request->file('userPhoto');
                $photoName = time() . '_' . uniqid() . '.' . $photo->getClientOriginalExtension();
                $photo->move(public_path('images/affiliates'), $photoName);
                $photoPath = 'images/affiliates/' . $photoName;
            }

            // Prepare bank details as JSON
            $bankDetails = null;
            if ($request->filled('bankName') || $request->filled('bankAccountNumber') || $request->filled('bankAccountName')) {
                $bankDetails = [
                    'bankName' => $request->bankName,
                    'accountNumber' => $request->bankAccountNumber,
                    'accountName' => $request->bankAccountName,
                ];
            }

            // Create affiliate
            $affiliate = EcomAffiliate::create([
                'clientId' => $request->clientId,
                'firstName' => $request->firstName,
                'middleName' => $request->middleName,
                'lastName' => $request->lastName,
                'phoneNumber' => $request->phoneNumber,
                'emailAddress' => $request->emailAddress,
                'houseNumber' => $request->houseNumber,
                'street' => $request->street,
                'barangay' => $request->barangay,
                'zone' => $request->zone,
                'municipality' => $request->municipality,
                'province' => $request->province,
                'zipCode' => $request->zipCode,
                'bankDetails' => $bankDetails,
                'gcashNumber' => $request->gcashNumber,
                'userPhoto' => $photoPath,
                'expirationDate' => $request->expirationDate,
                'accountStatus' => $request->accountStatus,
                'deleteStatus' => 1,
            ]);

            // Create affiliate-store relationships
            foreach ($request->stores as $storeId) {
                EcomAffiliateStore::create([
                    'affiliateId' => $affiliate->id,
                    'storeId' => $storeId,
                    'deleteStatus' => 1,
                ]);
            }

            // Handle document uploads
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $document) {
                    $originalName = $document->getClientOriginalName();
                    $docName = time() . '_' . uniqid() . '.' . $document->getClientOriginalExtension();
                    $document->move(public_path('images/affiliates/documents'), $docName);
                    $docPath = 'images/affiliates/documents/' . $docName;

                    EcomAffiliateDocument::create([
                        'affiliateId' => $affiliate->id,
                        'documentName' => $originalName,
                        'documentType' => 'ID',
                        'documentPath' => $docPath,
                        'documentNotes' => null,
                        'deleteStatus' => 1,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('ecom-affiliates')
                ->with('success', 'Affiliate created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'An error occurred while creating the affiliate. Please try again.')
                ->withInput();
        }
    }

    /**
     * Show the form for editing the specified affiliate.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function edit(Request $request)
    {
        $affiliate = EcomAffiliate::active()
            ->with(['client', 'affiliateStores.store'])
            ->findOrFail($request->id);

        // Get clients that are not already affiliates (except current affiliate's client, only active clients)
        $existingClientIds = EcomAffiliate::active()
            ->where('id', '!=', $affiliate->id)
            ->pluck('clientId')
            ->toArray();
        $clients = ClientAllDatabase::active()
                                    ->whereNotIn('id', $existingClientIds)
                                    ->orderBy('clientLastName', 'asc')
                                    ->orderBy('clientFirstName', 'asc')
                                    ->get();

        // Get active stores
        $stores = EcomProductStore::active()->enabled()->orderBy('storeName', 'asc')->get();

        // Get current store IDs for the affiliate
        $selectedStoreIds = $affiliate->affiliateStores->pluck('storeId')->toArray();

        return view('ecommerce.affiliates.edit', compact('affiliate', 'clients', 'stores', 'selectedStoreIds'));
    }

    /**
     * Update the specified affiliate in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $affiliate = EcomAffiliate::active()->findOrFail($id);

        // Handle status-only update (AJAX request)
        if ($request->has('_status_only')) {
            try {
                $affiliate->update(['accountStatus' => $request->accountStatus]);

                return response()->json([
                    'success' => true,
                    'message' => 'Affiliate status updated successfully.'
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while updating the affiliate status.'
                ], 500);
            }
        }

        $validator = Validator::make($request->all(), [
            'clientId' => 'nullable|integer|exists:clients_all_database,id',
            'firstName' => 'required|string|max:100',
            'middleName' => 'nullable|string|max:100',
            'lastName' => 'required|string|max:100',
            'phoneNumber' => 'required|string|max:50',
            'emailAddress' => 'nullable|email|max:255',
            // Location fields
            'houseNumber' => 'nullable|string|max:100',
            'street' => 'nullable|string|max:255',
            'barangay' => 'nullable|string|max:100',
            'zone' => 'nullable|string|max:100',
            'municipality' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'zipCode' => 'nullable|string|max:20',
            // Payment fields
            'bankName' => 'nullable|string|max:255',
            'bankAccountNumber' => 'nullable|string|max:100',
            'bankAccountName' => 'nullable|string|max:255',
            'gcashNumber' => 'nullable|string|max:50',
            'userPhoto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'expirationDate' => 'required|date',
            'accountStatus' => 'required|in:active,inactive',
            'stores' => 'required|array|min:1',
            'stores.*' => 'integer|exists:ecom_product_stores,id',
        ], [
            'firstName.required' => 'First name is required.',
            'firstName.max' => 'First name cannot exceed 100 characters.',
            'lastName.required' => 'Last name is required.',
            'lastName.max' => 'Last name cannot exceed 100 characters.',
            'phoneNumber.required' => 'Phone number is required.',
            'emailAddress.email' => 'Please enter a valid email address.',
            'municipality.required' => 'Municipality/City is required.',
            'province.required' => 'Province is required.',
            'userPhoto.image' => 'Photo must be an image file.',
            'userPhoto.mimes' => 'Photo must be a JPEG, PNG, JPG, or GIF file.',
            'userPhoto.max' => 'Photo cannot exceed 2MB.',
            'expirationDate.required' => 'Expiration date is required.',
            'expirationDate.date' => 'Please enter a valid date.',
            'accountStatus.required' => 'Account status is required.',
            'stores.required' => 'Please select at least one store.',
            'stores.min' => 'Please select at least one store.',
        ]);

        // Custom validation: either bank details or gcash must be provided
        $validator->after(function ($validator) use ($request) {
            $hasBankDetails = $request->filled('bankName') &&
                             $request->filled('bankAccountNumber') &&
                             $request->filled('bankAccountName');
            $hasGcash = $request->filled('gcashNumber');

            if (!$hasBankDetails && !$hasGcash) {
                $validator->errors()->add('payment', 'Either bank details or GCash number must be provided.');
            }
        });

        // Check if client is already an affiliate (for different affiliate)
        if ($request->filled('clientId')) {
            $existingAffiliate = EcomAffiliate::active()
                ->where('clientId', $request->clientId)
                ->where('id', '!=', $id)
                ->first();
            if ($existingAffiliate) {
                return redirect()->back()
                    ->with('error', 'This client is already registered as an affiliate.')
                    ->withInput();
            }
        }

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $photoPath = $affiliate->userPhoto;

            // Handle photo upload
            if ($request->hasFile('userPhoto')) {
                // Delete old photo if exists
                if ($affiliate->userPhoto && File::exists(public_path($affiliate->userPhoto))) {
                    File::delete(public_path($affiliate->userPhoto));
                }

                $photo = $request->file('userPhoto');
                $photoName = time() . '_' . uniqid() . '.' . $photo->getClientOriginalExtension();
                $photo->move(public_path('images/affiliates'), $photoName);
                $photoPath = 'images/affiliates/' . $photoName;
            }

            // Prepare bank details as JSON
            $bankDetails = null;
            if ($request->filled('bankName') || $request->filled('bankAccountNumber') || $request->filled('bankAccountName')) {
                $bankDetails = [
                    'bankName' => $request->bankName,
                    'accountNumber' => $request->bankAccountNumber,
                    'accountName' => $request->bankAccountName,
                ];
            }

            // Update affiliate
            $affiliate->update([
                'clientId' => $request->clientId,
                'firstName' => $request->firstName,
                'middleName' => $request->middleName,
                'lastName' => $request->lastName,
                'phoneNumber' => $request->phoneNumber,
                'emailAddress' => $request->emailAddress,
                'houseNumber' => $request->houseNumber,
                'street' => $request->street,
                'barangay' => $request->barangay,
                'zone' => $request->zone,
                'municipality' => $request->municipality,
                'province' => $request->province,
                'zipCode' => $request->zipCode,
                'bankDetails' => $bankDetails,
                'gcashNumber' => $request->gcashNumber,
                'userPhoto' => $photoPath,
                'expirationDate' => $request->expirationDate,
                'accountStatus' => $request->accountStatus,
            ]);

            // Update affiliate-store relationships
            // Soft delete existing relationships
            EcomAffiliateStore::where('affiliateId', $id)
                ->where('deleteStatus', 1)
                ->update(['deleteStatus' => 0]);

            // Create new relationships
            foreach ($request->stores as $storeId) {
                EcomAffiliateStore::create([
                    'affiliateId' => $affiliate->id,
                    'storeId' => $storeId,
                    'deleteStatus' => 1,
                ]);
            }

            DB::commit();

            return redirect()->route('ecom-affiliates')
                ->with('success', 'Affiliate updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'An error occurred while updating the affiliate. Please try again.')
                ->withInput();
        }
    }

    /**
     * Update the affiliate status via AJAX.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $affiliate = EcomAffiliate::active()->find($id);

            if (!$affiliate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Affiliate not found.'
                ], 404);
            }

            $affiliate->update(['accountStatus' => $request->accountStatus]);

            return response()->json([
                'success' => true,
                'message' => 'Affiliate status updated successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the affiliate status.'
            ], 500);
        }
    }

    /**
     * Soft delete the specified affiliate.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $affiliate = EcomAffiliate::active()->find($id);

            if (!$affiliate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Affiliate not found or already deleted.'
                ], 404);
            }

            // Soft delete - set deleteStatus to 0
            $affiliate->update(['deleteStatus' => 0]);

            // Also soft delete the affiliate-store relationships
            EcomAffiliateStore::where('affiliateId', $id)
                ->where('deleteStatus', 1)
                ->update(['deleteStatus' => 0]);

            return response()->json([
                'success' => true,
                'message' => 'Affiliate deleted successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the affiliate.'
            ], 500);
        }
    }

    /**
     * Remove the affiliate photo.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function removePhoto($id)
    {
        try {
            $affiliate = EcomAffiliate::active()->find($id);

            if (!$affiliate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Affiliate not found.'
                ], 404);
            }

            // Delete the photo file if exists
            if ($affiliate->userPhoto && File::exists(public_path($affiliate->userPhoto))) {
                File::delete(public_path($affiliate->userPhoto));
            }

            $affiliate->update(['userPhoto' => null]);

            return response()->json([
                'success' => true,
                'message' => 'Photo removed successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while removing the photo.'
            ], 500);
        }
    }

    /**
     * Get client details via AJAX for auto-fill.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getClientDetails($id)
    {
        try {
            $client = ClientAllDatabase::active()->find($id);

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client not found.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'firstName' => $client->clientFirstName,
                    'middleName' => $client->clientMiddleName,
                    'lastName' => $client->clientLastName,
                    'phoneNumber' => $client->clientPhoneNumber,
                    'emailAddress' => $client->clientEmailAddress,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching client details.'
            ], 500);
        }
    }

    /**
     * Upload documents for an affiliate.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadDocuments(Request $request, $id)
    {
        try {
            $affiliate = EcomAffiliate::active()->find($id);

            if (!$affiliate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Affiliate not found.'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'documents' => 'required|array|min:1',
                'documents.*' => 'required|file|mimes:jpeg,png,jpg,gif,pdf,doc,docx|max:5120',
                'documentType' => 'nullable|string|max:100',
                'documentNotes' => 'nullable|string|max:500',
            ], [
                'documents.required' => 'Please select at least one document to upload.',
                'documents.*.file' => 'Each document must be a valid file.',
                'documents.*.mimes' => 'Documents must be JPEG, PNG, JPG, GIF, PDF, DOC, or DOCX files.',
                'documents.*.max' => 'Each document cannot exceed 5MB.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $uploadedDocuments = [];

            foreach ($request->file('documents') as $document) {
                $originalName = $document->getClientOriginalName();
                $docName = time() . '_' . uniqid() . '.' . $document->getClientOriginalExtension();
                $document->move(public_path('images/affiliates/documents'), $docName);
                $docPath = 'images/affiliates/documents/' . $docName;

                $doc = EcomAffiliateDocument::create([
                    'affiliateId' => $id,
                    'documentName' => $originalName,
                    'documentType' => $request->documentType ?? 'ID',
                    'documentPath' => $docPath,
                    'documentNotes' => $request->documentNotes,
                    'deleteStatus' => 1,
                ]);

                $uploadedDocuments[] = [
                    'id' => $doc->id,
                    'name' => $doc->documentName,
                    'type' => $doc->documentType,
                    'path' => asset($doc->documentPath),
                ];
            }

            return response()->json([
                'success' => true,
                'message' => count($uploadedDocuments) . ' document(s) uploaded successfully.',
                'documents' => $uploadedDocuments
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while uploading documents.'
            ], 500);
        }
    }

    /**
     * Delete a document.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteDocument($id)
    {
        try {
            $document = EcomAffiliateDocument::active()->find($id);

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document not found.'
                ], 404);
            }

            // Delete the file
            if ($document->documentPath && File::exists(public_path($document->documentPath))) {
                File::delete(public_path($document->documentPath));
            }

            // Soft delete
            $document->update(['deleteStatus' => 0]);

            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the document.'
            ], 500);
        }
    }

    /**
     * Get affiliate details for the View Details modal.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $affiliate = EcomAffiliate::active()
                ->with(['client', 'stores', 'documents', 'affiliateStores.store'])
                ->find($id);

            if (!$affiliate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Affiliate not found.'
                ], 404);
            }

            // Format stores
            $stores = $affiliate->stores->map(function ($store) {
                return [
                    'id' => $store->id,
                    'name' => $store->storeName,
                ];
            });

            // Format documents
            $documents = $affiliate->documents->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'name' => $doc->documentName,
                    'type' => $doc->documentType,
                    'path' => asset($doc->documentPath),
                    'notes' => $doc->documentNotes,
                    'created_at' => $doc->created_at->format('M d, Y'),
                ];
            });

            // Get earnings breakdown by store
            $earningsByStore = $affiliate->getEarningsByStore();
            $totalEarnings = $earningsByStore->sum('totalEarnings');
            $totalPending = $earningsByStore->sum('totalPending');

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $affiliate->id,
                    'fullName' => $affiliate->full_name,
                    'firstName' => $affiliate->firstName,
                    'middleName' => $affiliate->middleName,
                    'lastName' => $affiliate->lastName,
                    'phoneNumber' => $affiliate->phoneNumber,
                    'emailAddress' => $affiliate->emailAddress,
                    // Location fields
                    'houseNumber' => $affiliate->houseNumber,
                    'street' => $affiliate->street,
                    'barangay' => $affiliate->barangay,
                    'zone' => $affiliate->zone,
                    'municipality' => $affiliate->municipality,
                    'province' => $affiliate->province,
                    'zipCode' => $affiliate->zipCode,
                    'fullAddress' => $affiliate->full_address,
                    // Payment fields
                    'gcashNumber' => $affiliate->gcashNumber,
                    'bankDetails' => $affiliate->bankDetails,
                    'formattedBankDetails' => $affiliate->formatted_bank_details,
                    'userPhoto' => $affiliate->userPhoto ? asset($affiliate->userPhoto) : null,
                    'expirationDate' => $affiliate->expirationDate ? $affiliate->expirationDate->format('M d, Y') : null,
                    'isExpired' => $affiliate->is_expired,
                    'accountStatus' => $affiliate->accountStatus,
                    'clientId' => $affiliate->clientId,
                    'clientName' => $affiliate->client ? ($affiliate->client->clientFirstName . ' ' . $affiliate->client->clientLastName) : null,
                    'stores' => $stores,
                    'documents' => $documents,
                    'earningsByStore' => $earningsByStore,
                    'totalEarnings' => (float) $totalEarnings,
                    'totalPending' => (float) $totalPending,
                    'createdAt' => $affiliate->created_at->format('M d, Y h:i A'),
                    'updatedAt' => $affiliate->updated_at->format('M d, Y h:i A'),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching affiliate details.'
            ], 500);
        }
    }

    /**
     * Get affiliate earnings for the Earnings modal.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getEarnings($id)
    {
        try {
            $affiliate = EcomAffiliate::active()
                ->with(['affiliateStores.store'])
                ->find($id);

            if (!$affiliate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Affiliate not found.'
                ], 404);
            }

            // Get earnings breakdown by store
            $earningsByStore = $affiliate->getEarningsByStore();

            // Calculate totals from store earnings
            $totalEarnings = $earningsByStore->sum('totalEarnings');
            $totalPending = $earningsByStore->sum('totalPending');
            $totalPaid = $totalEarnings; // For now, totalEarnings represents paid earnings

            return response()->json([
                'success' => true,
                'data' => [
                    'affiliateId' => $affiliate->id,
                    'affiliateName' => $affiliate->full_name,
                    'totalEarnings' => (float) $totalEarnings,
                    'totalPending' => (float) $totalPending,
                    'totalPaid' => (float) $totalPaid,
                    'earningsByStore' => $earningsByStore,
                    'transactions' => [], // Will contain detailed transactions when implemented
                    'summary' => [
                        'thisMonth' => 0.00, // TODO: Calculate from transaction records
                        'lastMonth' => 0.00,
                        'thisYear' => (float) ($totalEarnings + $totalPending),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching affiliate earnings.'
            ], 500);
        }
    }

    /**
     * Get documents for an affiliate.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDocuments($id)
    {
        try {
            $affiliate = EcomAffiliate::active()->find($id);

            if (!$affiliate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Affiliate not found.'
                ], 404);
            }

            $documents = $affiliate->documents()->get()->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'name' => $doc->documentName,
                    'type' => $doc->documentType,
                    'path' => asset($doc->documentPath),
                    'notes' => $doc->documentNotes,
                    'created_at' => $doc->created_at->format('M d, Y h:i A'),
                ];
            });

            return response()->json([
                'success' => true,
                'documents' => $documents
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching documents.'
            ], 500);
        }
    }

    // ==================== REFERRAL MANAGEMENT ====================

    /**
     * Get all referrals for an affiliate grouped by store.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getReferrals($id)
    {
        try {
            $affiliate = EcomAffiliate::active()
                ->with(['affiliateStores.store'])
                ->find($id);

            if (!$affiliate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Affiliate not found.'
                ], 404);
            }

            // Get all stores the affiliate is connected to
            $affiliateStores = $affiliate->affiliateStores->map(function ($as) {
                return [
                    'storeId' => $as->storeId,
                    'storeName' => $as->store ? $as->store->storeName : 'Unknown Store',
                ];
            });

            // Get referrals grouped by store
            $referrals = EcomAffiliateReferral::active()
                ->forAffiliate($id)
                ->with(['store', 'client'])
                ->orderBy('referralDate', 'desc')
                ->get()
                ->groupBy('storeId')
                ->map(function ($storeReferrals) {
                    return $storeReferrals->map(function ($ref) {
                        return [
                            'id' => $ref->id,
                            'clientId' => $ref->clientId,
                            'clientName' => $ref->client ? $ref->client->full_name : 'Unknown',
                            'clientPhone' => $ref->client ? $ref->client->clientPhoneNumber : null,
                            'clientEmail' => $ref->client ? $ref->client->clientEmailAddress : null,
                            'referralDate' => $ref->referralDate->format('M d, Y'),
                            'referralNotes' => $ref->referralNotes,
                        ];
                    });
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'affiliateId' => $affiliate->id,
                    'affiliateName' => $affiliate->full_name,
                    'stores' => $affiliateStores,
                    'referrals' => $referrals,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching referrals.'
            ], 500);
        }
    }

    /**
     * Get available clients for referral in a specific store.
     * Excludes clients already referred by any affiliate in that store.
     *
     * @param  int  $affiliateId
     * @param  int  $storeId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableClients($affiliateId, $storeId)
    {
        try {
            $affiliate = EcomAffiliate::active()->find($affiliateId);

            if (!$affiliate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Affiliate not found.'
                ], 404);
            }

            // Check if affiliate is connected to this store
            $isConnected = EcomAffiliateStore::active()
                ->where('affiliateId', $affiliateId)
                ->where('storeId', $storeId)
                ->exists();

            if (!$isConnected) {
                return response()->json([
                    'success' => false,
                    'message' => 'Affiliate is not connected to this store.'
                ], 403);
            }

            // Get IDs of clients already referred in this store
            $referredClientIds = EcomAffiliateReferral::getReferredClientIdsInStore($storeId);

            // Get available clients (not yet referred in this store, only active clients)
            $clients = ClientAllDatabase::active()
                ->whereNotIn('id', $referredClientIds)
                ->orderBy('clientLastName', 'asc')
                ->orderBy('clientFirstName', 'asc')
                ->get()
                ->map(function ($client) {
                    return [
                        'id' => $client->id,
                        'fullName' => $client->full_name,
                        'phone' => $client->clientPhoneNumber,
                        'email' => $client->clientEmailAddress,
                    ];
                });

            return response()->json([
                'success' => true,
                'clients' => $clients
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching available clients.'
            ], 500);
        }
    }

    /**
     * Check if a client is available for referral in a store.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkClientAvailability(Request $request)
    {
        try {
            $storeId = $request->storeId;
            $clientId = $request->clientId;

            if (!$storeId || !$clientId) {
                return response()->json([
                    'success' => false,
                    'available' => false,
                    'message' => 'Store and client are required.'
                ], 400);
            }

            $isReferred = EcomAffiliateReferral::isClientReferredInStore($storeId, $clientId);

            if ($isReferred) {
                // Get the affiliate who referred this client
                $existingReferral = EcomAffiliateReferral::active()
                    ->where('storeId', $storeId)
                    ->where('clientId', $clientId)
                    ->with('affiliate')
                    ->first();

                $referredBy = $existingReferral && $existingReferral->affiliate
                    ? $existingReferral->affiliate->full_name
                    : 'another affiliate';

                return response()->json([
                    'success' => true,
                    'available' => false,
                    'message' => "This customer is already referred by {$referredBy} in this store."
                ]);
            }

            return response()->json([
                'success' => true,
                'available' => true,
                'message' => 'Customer is available for referral.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'available' => false,
                'message' => 'An error occurred while checking availability.'
            ], 500);
        }
    }

    /**
     * Add an existing client as a referral.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeReferral(Request $request, $id)
    {
        try {
            $affiliate = EcomAffiliate::active()->find($id);

            if (!$affiliate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Affiliate not found.'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'storeId' => 'required|integer|exists:ecom_product_stores,id',
                'clientId' => 'required|integer|exists:clients_all_database,id',
                'referralDate' => 'required|date',
                'referralNotes' => 'nullable|string|max:500',
            ], [
                'storeId.required' => 'Please select a store.',
                'clientId.required' => 'Please select a customer.',
                'referralDate.required' => 'Referral date is required.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            // Check if affiliate is connected to this store
            $isConnected = EcomAffiliateStore::active()
                ->where('affiliateId', $id)
                ->where('storeId', $request->storeId)
                ->exists();

            if (!$isConnected) {
                return response()->json([
                    'success' => false,
                    'message' => 'Affiliate is not connected to this store.'
                ], 403);
            }

            // Check if client is already referred in this store
            if (EcomAffiliateReferral::isClientReferredInStore($request->storeId, $request->clientId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This customer is already referred by another affiliate in this store.'
                ], 422);
            }

            // Create the referral
            $referral = EcomAffiliateReferral::create([
                'affiliateId' => $id,
                'storeId' => $request->storeId,
                'clientId' => $request->clientId,
                'referralDate' => $request->referralDate,
                'referralNotes' => $request->referralNotes,
                'deleteStatus' => 1,
            ]);

            // Get client details for response (only active clients)
            $client = ClientAllDatabase::active()->find($request->clientId);

            return response()->json([
                'success' => true,
                'message' => 'Referral added successfully.',
                'referral' => [
                    'id' => $referral->id,
                    'clientId' => $referral->clientId,
                    'clientName' => $client ? $client->full_name : 'Unknown',
                    'clientPhone' => $client ? $client->clientPhoneNumber : null,
                    'clientEmail' => $client ? $client->clientEmailAddress : null,
                    'referralDate' => $referral->referralDate->format('M d, Y'),
                    'referralNotes' => $referral->referralNotes,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while adding the referral.'
            ], 500);
        }
    }

    /**
     * Add a new client and create a referral.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeNewClientReferral(Request $request, $id)
    {
        try {
            $affiliate = EcomAffiliate::active()->find($id);

            if (!$affiliate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Affiliate not found.'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'storeId' => 'required|integer|exists:ecom_product_stores,id',
                'clientFirstName' => 'required|string|max:100',
                'clientMiddleName' => 'nullable|string|max:100',
                'clientLastName' => 'required|string|max:100',
                'clientPhoneNumber' => 'required|string|max:50',
                'clientEmailAddress' => 'nullable|email|max:255',
                'referralDate' => 'required|date',
                'referralNotes' => 'nullable|string|max:500',
            ], [
                'storeId.required' => 'Please select a store.',
                'clientFirstName.required' => 'First name is required.',
                'clientLastName.required' => 'Last name is required.',
                'clientPhoneNumber.required' => 'Phone number is required.',
                'referralDate.required' => 'Referral date is required.',
            ]);

            // Check for duplicate phone/email among active clients only
            $validator->after(function ($validator) use ($request) {
                if ($request->clientPhoneNumber && ClientAllDatabase::active()->where('clientPhoneNumber', $request->clientPhoneNumber)->exists()) {
                    $validator->errors()->add('clientPhoneNumber', 'This phone number already exists.');
                }
                if ($request->clientEmailAddress && ClientAllDatabase::active()->where('clientEmailAddress', $request->clientEmailAddress)->exists()) {
                    $validator->errors()->add('clientEmailAddress', 'This email address already exists.');
                }
            });

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            // Check if affiliate is connected to this store
            $isConnected = EcomAffiliateStore::active()
                ->where('affiliateId', $id)
                ->where('storeId', $request->storeId)
                ->exists();

            if (!$isConnected) {
                return response()->json([
                    'success' => false,
                    'message' => 'Affiliate is not connected to this store.'
                ], 403);
            }

            DB::beginTransaction();

            // Create new client
            $client = ClientAllDatabase::create([
                'clientFirstName' => $request->clientFirstName,
                'clientMiddleName' => $request->clientMiddleName,
                'clientLastName' => $request->clientLastName,
                'clientPhoneNumber' => $request->clientPhoneNumber,
                'clientEmailAddress' => $request->clientEmailAddress,
                'deleteStatus' => 1,
            ]);

            // Create the referral
            $referral = EcomAffiliateReferral::create([
                'affiliateId' => $id,
                'storeId' => $request->storeId,
                'clientId' => $client->id,
                'referralDate' => $request->referralDate,
                'referralNotes' => $request->referralNotes,
                'deleteStatus' => 1,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'New customer and referral added successfully.',
                'referral' => [
                    'id' => $referral->id,
                    'clientId' => $client->id,
                    'clientName' => $client->full_name,
                    'clientPhone' => $client->clientPhoneNumber,
                    'clientEmail' => $client->clientEmailAddress,
                    'referralDate' => $referral->referralDate->format('M d, Y'),
                    'referralNotes' => $referral->referralNotes,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while adding the customer and referral.'
            ], 500);
        }
    }

    /**
     * Remove a referral (soft delete).
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeReferral($id)
    {
        try {
            $referral = EcomAffiliateReferral::active()->find($id);

            if (!$referral) {
                return response()->json([
                    'success' => false,
                    'message' => 'Referral not found.'
                ], 404);
            }

            $referral->update(['deleteStatus' => 0]);

            return response()->json([
                'success' => true,
                'message' => 'Referral removed successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while removing the referral.'
            ], 500);
        }
    }

    /**
     * Display the referrals page for an affiliate.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function referralsPage(Request $request)
    {
        $affiliate = EcomAffiliate::active()
            ->with(['stores', 'affiliateStores.store'])
            ->find($request->id);

        if (!$affiliate) {
            return redirect()->route('ecom-affiliates')
                ->with('error', 'Affiliate not found.');
        }

        // Get affiliate's active stores
        $affiliateStores = $affiliate->affiliateStores;

        // Get all referrals for this affiliate, grouped by store
        $referrals = EcomAffiliateReferral::active()
            ->forAffiliate($affiliate->id)
            ->with(['store', 'client'])
            ->orderBy('referralDate', 'desc')
            ->get();

        $referralsByStore = $referrals->groupBy('storeId');
        $totalReferrals = $referrals->count();

        return view('ecommerce.affiliates.referrals', compact(
            'affiliate',
            'affiliateStores',
            'referralsByStore',
            'totalReferrals'
        ));
    }
}
