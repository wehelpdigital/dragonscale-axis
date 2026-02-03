<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\CrmBusinessContact;
use App\Models\EcomProductStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BusinessContactsController extends Controller
{
    /**
     * Display listing of business contacts
     */
    public function index(Request $request)
    {
        $query = CrmBusinessContact::active()
            ->forUser(Auth::id())
            ->with('stores');

        // Filter by contact type
        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        // Filter by relationship strength
        if ($request->filled('strength')) {
            $query->where('relationshipStrength', $request->strength);
        }

        // Filter by store
        if ($request->filled('store')) {
            $query->whereHas('stores', function ($q) use ($request) {
                $q->where('storeId', $request->store);
            });
        }

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('firstName', 'like', "%{$search}%")
                  ->orWhere('lastName', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('companyName', 'like', "%{$search}%")
                  ->orWhere('nickname', 'like', "%{$search}%");
            });
        }

        $contacts = $query->orderBy('created_at', 'desc')->paginate(50);
        $stores = EcomProductStore::active()->enabled()->orderBy('storeName')->get();

        return view('crm.business-contacts.index', compact('contacts', 'stores'));
    }

    /**
     * Show form for creating new contact
     */
    public function create()
    {
        $stores = EcomProductStore::active()->enabled()->orderBy('storeName')->get();
        return view('crm.business-contacts.create', compact('stores'));
    }

    /**
     * Store new business contact
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firstName' => 'nullable|string|max:100',
            'lastName' => 'nullable|string|max:100',
            'middleName' => 'nullable|string|max:100',
            'nickname' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'alternatePhone' => 'nullable|string|max:50',
            'contactType' => 'required|string|in:' . implode(',', array_keys(CrmBusinessContact::CONTACT_TYPE_OPTIONS)),
            'contactStatus' => 'required|string|in:' . implode(',', array_keys(CrmBusinessContact::STATUS_OPTIONS)),
            'relationshipStrength' => 'nullable|string|in:' . implode(',', array_keys(CrmBusinessContact::RELATIONSHIP_STRENGTH_OPTIONS)),
            'companyName' => 'nullable|string|max:255',
            'jobTitle' => 'nullable|string|max:150',
            'department' => 'nullable|string|max:150',
            'industry' => 'nullable|string|max:150',
            'companySize' => 'nullable|string|in:' . implode(',', array_keys(CrmBusinessContact::COMPANY_SIZE_OPTIONS)),
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
            'firstContactDate' => 'nullable|date',
            'howWeMet' => 'nullable|string|max:255',
            'referredBy' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'tags' => 'nullable|string',
            'store_associations' => 'nullable|array',
        ], [
            'contactType.required' => 'Contact type is required.',
            'email.email' => 'Please enter a valid email address.',
            'website.url' => 'Please enter a valid website URL.',
        ]);

        // Custom validation: must have either name or company name
        if (empty($request->firstName) && empty($request->lastName) && empty($request->companyName)) {
            $validator->after(function ($validator) {
                $validator->errors()->add('firstName', 'Please provide either a name or company name.');
            });
        }

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Process tags
        $tags = null;
        if ($request->filled('tags')) {
            $tagsArray = array_map('trim', explode(',', $request->tags));
            $tagsArray = array_filter($tagsArray);
            $tags = !empty($tagsArray) ? $tagsArray : null;
        }

        $contact = CrmBusinessContact::create([
            'usersId' => Auth::id(),
            'contactType' => $request->contactType,
            'contactStatus' => $request->contactStatus ?? 'active',
            'firstName' => $request->firstName,
            'middleName' => $request->middleName,
            'lastName' => $request->lastName,
            'nickname' => $request->nickname,
            'email' => $request->email,
            'phone' => $request->phone,
            'alternatePhone' => $request->alternatePhone,
            'companyName' => $request->companyName,
            'jobTitle' => $request->jobTitle,
            'department' => $request->department,
            'industry' => $request->industry,
            'companySize' => $request->companySize,
            'website' => $request->website,
            'province' => $request->province,
            'municipality' => $request->municipality,
            'barangay' => $request->barangay,
            'streetAddress' => $request->streetAddress,
            'zipCode' => $request->zipCode,
            'country' => $request->country ?? 'Philippines',
            'facebookUrl' => $request->facebookUrl,
            'instagramUrl' => $request->instagramUrl,
            'linkedinUrl' => $request->linkedinUrl,
            'twitterUrl' => $request->twitterUrl,
            'tiktokUrl' => $request->tiktokUrl,
            'relationshipStrength' => $request->relationshipStrength ?? 'neutral',
            'firstContactDate' => $request->firstContactDate,
            'howWeMet' => $request->howWeMet,
            'referredBy' => $request->referredBy,
            'notes' => $request->notes,
            'tags' => $tags,
            'delete_status' => 'active',
        ]);

        // Sync store associations
        if ($request->has('store_associations')) {
            $contact->stores()->sync($request->store_associations);
        }

        return redirect()->route('crm-business-contacts')->with('success', 'Business contact created successfully!');
    }

    /**
     * Display a single business contact
     */
    public function show(Request $request)
    {
        $contact = CrmBusinessContact::where('id', $request->id)
            ->where('usersId', Auth::id())
            ->where('delete_status', 'active')
            ->with('stores')
            ->firstOrFail();

        return view('crm.business-contacts.show', compact('contact'));
    }

    /**
     * Show form for editing contact
     */
    public function edit(Request $request)
    {
        $contact = CrmBusinessContact::where('id', $request->id)
            ->where('usersId', Auth::id())
            ->where('delete_status', 'active')
            ->with('stores')
            ->firstOrFail();

        $stores = EcomProductStore::active()->enabled()->orderBy('storeName')->get();

        return view('crm.business-contacts.edit', compact('contact', 'stores'));
    }

    /**
     * Update business contact
     */
    public function update(Request $request)
    {
        $contact = CrmBusinessContact::where('id', $request->id)
            ->where('usersId', Auth::id())
            ->where('delete_status', 'active')
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'firstName' => 'nullable|string|max:100',
            'lastName' => 'nullable|string|max:100',
            'middleName' => 'nullable|string|max:100',
            'nickname' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'alternatePhone' => 'nullable|string|max:50',
            'contactType' => 'required|string|in:' . implode(',', array_keys(CrmBusinessContact::CONTACT_TYPE_OPTIONS)),
            'contactStatus' => 'required|string|in:' . implode(',', array_keys(CrmBusinessContact::STATUS_OPTIONS)),
            'relationshipStrength' => 'nullable|string|in:' . implode(',', array_keys(CrmBusinessContact::RELATIONSHIP_STRENGTH_OPTIONS)),
            'companyName' => 'nullable|string|max:255',
            'jobTitle' => 'nullable|string|max:150',
            'department' => 'nullable|string|max:150',
            'industry' => 'nullable|string|max:150',
            'companySize' => 'nullable|string|in:' . implode(',', array_keys(CrmBusinessContact::COMPANY_SIZE_OPTIONS)),
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
            'firstContactDate' => 'nullable|date',
            'howWeMet' => 'nullable|string|max:255',
            'referredBy' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'tags' => 'nullable|string',
            'store_associations' => 'nullable|array',
        ]);

        // Custom validation: must have either name or company name
        if (empty($request->firstName) && empty($request->lastName) && empty($request->companyName)) {
            $validator->after(function ($validator) {
                $validator->errors()->add('firstName', 'Please provide either a name or company name.');
            });
        }

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Process tags
        $tags = null;
        if ($request->filled('tags')) {
            $tagsArray = array_map('trim', explode(',', $request->tags));
            $tagsArray = array_filter($tagsArray);
            $tags = !empty($tagsArray) ? $tagsArray : null;
        }

        $contact->update([
            'contactType' => $request->contactType,
            'contactStatus' => $request->contactStatus,
            'firstName' => $request->firstName,
            'middleName' => $request->middleName,
            'lastName' => $request->lastName,
            'nickname' => $request->nickname,
            'email' => $request->email,
            'phone' => $request->phone,
            'alternatePhone' => $request->alternatePhone,
            'companyName' => $request->companyName,
            'jobTitle' => $request->jobTitle,
            'department' => $request->department,
            'industry' => $request->industry,
            'companySize' => $request->companySize,
            'website' => $request->website,
            'province' => $request->province,
            'municipality' => $request->municipality,
            'barangay' => $request->barangay,
            'streetAddress' => $request->streetAddress,
            'zipCode' => $request->zipCode,
            'country' => $request->country,
            'facebookUrl' => $request->facebookUrl,
            'instagramUrl' => $request->instagramUrl,
            'linkedinUrl' => $request->linkedinUrl,
            'twitterUrl' => $request->twitterUrl,
            'tiktokUrl' => $request->tiktokUrl,
            'relationshipStrength' => $request->relationshipStrength,
            'firstContactDate' => $request->firstContactDate,
            'howWeMet' => $request->howWeMet,
            'referredBy' => $request->referredBy,
            'notes' => $request->notes,
            'tags' => $tags,
        ]);

        // Sync store associations
        $contact->stores()->sync($request->store_associations ?? []);

        return redirect()->route('crm-business-contacts.show', ['id' => $contact->id])
            ->with('success', 'Business contact updated successfully!');
    }

    /**
     * Soft delete business contact
     */
    public function destroy(Request $request, $id)
    {
        $contact = CrmBusinessContact::where('id', $id)
            ->where('usersId', Auth::id())
            ->where('delete_status', 'active')
            ->first();

        if (!$contact) {
            return response()->json([
                'success' => false,
                'message' => 'Contact not found or already deleted.'
            ], 404);
        }

        $contact->update(['delete_status' => 'deleted']);
        $contact->stores()->detach();

        return response()->json([
            'success' => true,
            'message' => 'Business contact deleted successfully.'
        ]);
    }

    /**
     * Update contact status via AJAX
     */
    public function updateStatus(Request $request)
    {
        $contact = CrmBusinessContact::where('id', $request->id)
            ->where('usersId', Auth::id())
            ->where('delete_status', 'active')
            ->first();

        if (!$contact) {
            return response()->json([
                'success' => false,
                'message' => 'Contact not found.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:' . implode(',', array_keys(CrmBusinessContact::STATUS_OPTIONS)),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status value.'
            ], 422);
        }

        $contact->update(['contactStatus' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.',
            'data' => [
                'status' => $contact->contactStatus,
                'statusLabel' => $contact->status_label,
                'statusColor' => $contact->status_color,
            ]
        ]);
    }

    /**
     * Update last contact date
     */
    public function updateLastContact(Request $request)
    {
        $contact = CrmBusinessContact::where('id', $request->id)
            ->where('usersId', Auth::id())
            ->where('delete_status', 'active')
            ->first();

        if (!$contact) {
            return response()->json([
                'success' => false,
                'message' => 'Contact not found.'
            ], 404);
        }

        $contact->update(['lastContactDate' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Last contact date updated.',
            'data' => [
                'lastContactDate' => $contact->lastContactDate->format('M d, Y'),
            ]
        ]);
    }
}
