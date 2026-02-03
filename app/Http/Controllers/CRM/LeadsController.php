<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\CrmLead;
use App\Models\CrmLeadSource;
use App\Models\CrmLeadActivity;
use App\Models\CrmLeadCustomData;
use App\Models\ClientAllDatabase;
use App\Models\ClientAccessLogin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeadsController extends Controller
{
    /**
     * Display a listing of leads.
     */
    public function index(Request $request)
    {
        $query = CrmLead::active()
            ->with(['source', 'assignee'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('leadStatus', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('leadPriority', $request->priority);
        }

        if ($request->filled('source')) {
            $query->where('leadSourceId', $request->source);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assignedTo', $request->assigned_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('firstName', 'like', "%{$search}%")
                  ->orWhere('lastName', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('companyName', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $leads = $query->paginate(20);

        // Get filter options
        $sources = CrmLeadSource::active()->enabled()->orderBy('sourceOrder')->get();
        $users = User::orderBy('name')->get();
        $stores = \App\Models\EcomProductStore::active()->enabled()->orderBy('storeName')->get();

        // Get unique custom field names for filtering
        $customFieldNames = CrmLeadCustomData::active()
            ->select('fieldName')
            ->distinct()
            ->orderBy('fieldName')
            ->pluck('fieldName');

        // Get stats
        $stats = [
            'total' => CrmLead::active()->count(),
            'new' => CrmLead::active()->byStatus('new')->count(),
            'qualified' => CrmLead::active()->byStatus('qualified')->count(),
            'won' => CrmLead::active()->byStatus('won')->count(),
            'lost' => CrmLead::active()->byStatus('lost')->count(),
        ];

        return view('crm.leads.index', compact('leads', 'sources', 'users', 'stats', 'stores', 'customFieldNames'));
    }

    /**
     * Show the form for creating a new lead.
     */
    public function create()
    {
        $sources = CrmLeadSource::active()->enabled()->orderBy('sourceOrder')->get();
        $users = User::orderBy('name')->get();
        $stores = \App\Models\EcomProductStore::active()->enabled()->orderBy('storeName')->get();

        return view('crm.leads.create', compact('sources', 'users', 'stores'));
    }

    /**
     * Store a newly created lead in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firstName' => 'required|string|max:100',
            'middleName' => 'nullable|string|max:100',
            'lastName' => 'required|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'alternatePhone' => 'nullable|string|max:50',
            'leadSourceId' => 'nullable|exists:crm_lead_sources,id',
            'leadSourceOther' => 'nullable|string|max:255',
            'referredBy' => 'nullable|string|max:255',
            'leadStatus' => 'nullable|in:new,contacted,qualified,proposal,negotiation,won,lost,dormant',
            'leadPriority' => 'nullable|in:low,medium,high,urgent',
            'companyName' => 'nullable|string|max:255',
            'jobTitle' => 'nullable|string|max:150',
            'department' => 'nullable|string|max:150',
            'industry' => 'nullable|string|max:150',
            'companySize' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'province' => 'nullable|string|max:150',
            'municipality' => 'nullable|string|max:150',
            'barangay' => 'nullable|string|max:150',
            'streetAddress' => 'nullable|string',
            'zipCode' => 'nullable|string|max:20',
            'facebookUrl' => 'nullable|url|max:500',
            'instagramUrl' => 'nullable|url|max:500',
            'linkedinUrl' => 'nullable|url|max:500',
            'twitterUrl' => 'nullable|url|max:500',
            'tiktokUrl' => 'nullable|url|max:500',
            'assignedTo' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
            'store_targets' => 'nullable|array',
            'store_targets.*' => 'exists:ecom_product_stores,id',
        ], [
            'firstName.required' => 'First name is required.',
            'lastName.required' => 'Last name is required.',
            'email.email' => 'Please enter a valid email address.',
            'website.url' => 'Please enter a valid website URL.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $lead = CrmLead::create([
                'usersId' => Auth::id(),
                'firstName' => $request->firstName,
                'middleName' => $request->middleName,
                'lastName' => $request->lastName,
                'email' => $request->email,
                'phone' => $request->phone,
                'alternatePhone' => $request->alternatePhone,
                'leadSourceId' => $request->leadSourceId,
                'leadSourceOther' => $request->leadSourceOther,
                'referredBy' => $request->referredBy,
                'leadStatus' => $request->leadStatus ?? 'new',
                'leadPriority' => $request->leadPriority ?? 'medium',
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
                'facebookUrl' => $request->facebookUrl,
                'instagramUrl' => $request->instagramUrl,
                'linkedinUrl' => $request->linkedinUrl,
                'twitterUrl' => $request->twitterUrl,
                'tiktokUrl' => $request->tiktokUrl,
                'assignedTo' => $request->assignedTo,
                'notes' => $request->notes,
                'delete_status' => 'active',
            ]);

            // Log creation activity
            $lead->logActivity('note', 'Lead created', Auth::id());

            // Sync store targets
            if ($request->has('store_targets')) {
                $lead->targetStores()->sync($request->store_targets ?? []);
            }

            DB::commit();

            return redirect()->route('crm-leads')
                ->with('success', 'Lead created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create lead: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified lead.
     */
    /**
     * Display the specified lead with potential matches.
     */
    public function show(Request $request)
    {
        $id = $request->query('id');
        $lead = CrmLead::active()
            ->with(['source', 'owner', 'assignee', 'activities.user', 'targetStores', 'customData'])
            ->findOrFail($id);

        $sources = CrmLeadSource::active()->enabled()->orderBy('sourceOrder')->get();

        return view('crm.leads.show', compact('lead', 'sources'));
    }

    /**
     * Show the form for editing the specified lead.
     */
    public function edit(Request $request)
    {
        $id = $request->query('id');
        $lead = CrmLead::active()->with(['activities.user', 'targetStores', 'customData'])->findOrFail($id);
        $sources = CrmLeadSource::active()->enabled()->orderBy('sourceOrder')->get();
        $users = User::orderBy('name')->get();
        $stores = \App\Models\EcomProductStore::active()->enabled()->orderBy('storeName')->get();

        return view('crm.leads.edit', compact('lead', 'sources', 'users', 'stores'));
    }

    /**
     * Update the specified lead in storage.
     */
    public function update(Request $request)
    {
        $id = $request->input('id');
        $lead = CrmLead::active()->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'firstName' => 'required|string|max:100',
            'middleName' => 'nullable|string|max:100',
            'lastName' => 'required|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'alternatePhone' => 'nullable|string|max:50',
            'leadSourceId' => 'nullable|exists:crm_lead_sources,id',
            'leadSourceOther' => 'nullable|string|max:255',
            'referredBy' => 'nullable|string|max:255',
            'leadStatus' => 'nullable|in:new,contacted,qualified,proposal,negotiation,won,lost,dormant',
            'leadPriority' => 'nullable|in:low,medium,high,urgent',
            'companyName' => 'nullable|string|max:255',
            'jobTitle' => 'nullable|string|max:150',
            'department' => 'nullable|string|max:150',
            'industry' => 'nullable|string|max:150',
            'companySize' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'province' => 'nullable|string|max:150',
            'municipality' => 'nullable|string|max:150',
            'barangay' => 'nullable|string|max:150',
            'streetAddress' => 'nullable|string',
            'zipCode' => 'nullable|string|max:20',
            'facebookUrl' => 'nullable|url|max:500',
            'instagramUrl' => 'nullable|url|max:500',
            'linkedinUrl' => 'nullable|url|max:500',
            'twitterUrl' => 'nullable|url|max:500',
            'tiktokUrl' => 'nullable|url|max:500',
            'assignedTo' => 'nullable|exists:users,id',
            'nextFollowUpDate' => 'nullable|date',
            'nextFollowUpTime' => 'nullable',
            'followUpNotes' => 'nullable|string',
            'estimatedValue' => 'nullable|numeric|min:0',
            'lossReason' => 'nullable|string|max:255',
            'lossDetails' => 'nullable|string',
            'notes' => 'nullable|string',
            'tags' => 'nullable|string',
            'store_targets' => 'nullable|array',
            'store_targets.*' => 'exists:ecom_product_stores,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $oldStatus = $lead->leadStatus;
            $newStatus = $request->leadStatus ?? $lead->leadStatus;

            $lead->update([
                'firstName' => $request->firstName,
                'middleName' => $request->middleName,
                'lastName' => $request->lastName,
                'email' => $request->email,
                'phone' => $request->phone,
                'alternatePhone' => $request->alternatePhone,
                'leadSourceId' => $request->leadSourceId,
                'leadSourceOther' => $request->leadSourceOther,
                'referredBy' => $request->referredBy,
                'leadStatus' => $newStatus,
                'leadPriority' => $request->leadPriority ?? $lead->leadPriority,
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
                'facebookUrl' => $request->facebookUrl,
                'instagramUrl' => $request->instagramUrl,
                'linkedinUrl' => $request->linkedinUrl,
                'twitterUrl' => $request->twitterUrl,
                'tiktokUrl' => $request->tiktokUrl,
                'assignedTo' => $request->assignedTo,
                'lossReason' => $request->lossReason,
                'lossDetails' => $request->lossDetails,
                'notes' => $request->notes,
            ]);

            // Log status change if changed
            if ($oldStatus !== $newStatus) {
                $lead->logStatusChange($oldStatus, $newStatus, Auth::id());
            }

            // Sync store targets
            $lead->targetStores()->sync($request->store_targets ?? []);

            DB::commit();

            return redirect()->route('crm-leads')
                ->with('success', 'Lead updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update lead: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified lead from storage (soft delete).
     */
    public function destroy(Request $request)
    {
        $id = $request->input('id');
        try {
            $lead = CrmLead::active()->findOrFail($id);
            $lead->update(['delete_status' => 'deleted']);

            return response()->json([
                'success' => true,
                'message' => 'Lead deleted successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete lead: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update lead status via AJAX.
     */
    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
            'status' => 'required|in:new,contacted,qualified,proposal,negotiation,won,lost,dormant',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status provided.'
            ], 422);
        }

        try {
            $id = $request->input('id');
            $lead = CrmLead::active()->findOrFail($id);
            $oldStatus = $lead->leadStatus;
            $newStatus = $request->status;

            if ($oldStatus !== $newStatus) {
                $lead->update(['leadStatus' => $newStatus]);
                $lead->logStatusChange($oldStatus, $newStatus, Auth::id());
            }

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully!',
                'newStatus' => $newStatus,
                'statusLabel' => CrmLead::STATUS_OPTIONS[$newStatus]['label'],
                'statusColor' => CrmLead::STATUS_OPTIONS[$newStatus]['color'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add activity to a lead.
     */
    public function addActivity(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lead_id' => 'required|integer',
            'activity_type' => 'required|in:call_outbound,call_inbound,email_sent,email_received,meeting,note,follow_up,proposal_sent,document_sent,social_media,other',
            'subject' => 'nullable|string|max:255',
            'description' => 'required|string',
            'duration' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $id = $request->input('lead_id');
            $lead = CrmLead::active()->findOrFail($id);

            $activity = $lead->activities()->create([
                'usersId' => Auth::id(),
                'activityType' => $request->activity_type,
                'activitySubject' => $request->subject,
                'activityDescription' => $request->description,
                'activityDate' => now(),
                'durationMinutes' => $request->duration,
                'delete_status' => 'active',
            ]);

            // Update last contact date
            $lead->update(['lastContactDate' => now()]);

            return response()->json([
                'success' => true,
                'message' => 'Activity added successfully!',
                'activity' => $activity->load('user'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add activity: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get lead activities.
     */
    public function getActivities($id)
    {
        try {
            $lead = CrmLead::active()->findOrFail($id);
            $activities = $lead->activities()
                ->active()
                ->with('user')
                ->orderBy('activityDate', 'desc')
                ->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $activities,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get activities: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get lead sources for dropdown.
     */
    public function getSources()
    {
        $sources = CrmLeadSource::active()
            ->enabled()
            ->orderBy('sourceOrder')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sources,
        ]);
    }

    /**
     * Get leads data for AJAX listing.
     */
    public function getData(Request $request)
    {
        $query = CrmLead::active()
            ->with(['source', 'assignee', 'targetStores', 'customData']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('leadStatus', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('leadPriority', $request->priority);
        }

        if ($request->filled('source')) {
            $query->where('leadSourceId', $request->source);
        }

        // Store target filter
        if ($request->filled('store')) {
            $storeId = $request->store;
            $query->whereHas('targetStores', function ($q) use ($storeId) {
                $q->where('ecom_product_stores.id', $storeId);
            });
        }

        // Custom field filter
        if ($request->filled('custom_field')) {
            $customFieldName = $request->custom_field;
            $customFieldValue = $request->input('custom_field_value', '');

            $query->whereHas('customData', function ($q) use ($customFieldName, $customFieldValue) {
                $q->where('fieldName', $customFieldName);
                if (!empty($customFieldValue)) {
                    $q->where('fieldValue', 'like', "%{$customFieldValue}%");
                }
            });
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('firstName', 'like', "%{$search}%")
                  ->orWhere('lastName', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('companyName', 'like', "%{$search}%");
            });
        }

        // Order by created_at desc
        $query->orderBy('created_at', 'desc');

        // Paginate
        $perPage = $request->input('per_page', 25);
        $page = $request->input('page', 1);
        $leads = $query->paginate($perPage, ['*'], 'page', $page);

        // Get stats
        $stats = [
            'total' => CrmLead::active()->count(),
            'new' => CrmLead::active()->byStatus('new')->count(),
            'contacted' => CrmLead::active()->byStatus('contacted')->count(),
            'qualified' => CrmLead::active()->byStatus('qualified')->count(),
            'proposal' => CrmLead::active()->byStatus('proposal')->count(),
            'won' => CrmLead::active()->byStatus('won')->count(),
            'lost' => CrmLead::active()->byStatus('lost')->count(),
        ];

        // Add fullName attribute and format data for each lead
        $leadsData = $leads->getCollection()->map(function ($lead) {
            $lead->fullName = $lead->full_name;
            $lead->target_stores = $lead->targetStores;
            $lead->customData = $lead->customData;
            return $lead;
        });

        return response()->json([
            'success' => true,
            'data' => $leadsData,
            'pagination' => [
                'total' => $leads->total(),
                'per_page' => $leads->perPage(),
                'current_page' => $leads->currentPage(),
                'last_page' => $leads->lastPage(),
                'from' => $leads->firstItem(),
                'to' => $leads->lastItem(),
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Find potential store login matches for a lead.
     * Uses weighted matching algorithm for confidence scoring.
     */
    public function findPotentialLogins(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lead_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid lead ID provided.'
            ], 422);
        }

        try {
            $lead = CrmLead::active()->findOrFail($request->lead_id);
            $matches = [];

            // Build query for potential matches
            $query = DB::table('clients_access_login')
                ->where('deleteStatus', 1)
                ->select([
                    'id',
                    'productStore',
                    'clientFirstName',
                    'clientMiddleName',
                    'clientLastName',
                    'clientPhoneNumber',
                    'clientEmailAddress',
                    'isActive',
                    'created_at'
                ]);

            // Get all potential matches based on any criteria
            $potentialMatches = $query->where(function ($q) use ($lead) {
                // Email match (if lead has email)
                if ($lead->email) {
                    $q->orWhere('clientEmailAddress', $lead->email);
                }
                // Phone match (if lead has phone)
                if ($lead->phone) {
                    $cleanPhone = preg_replace('/[^0-9]/', '', $lead->phone);
                    $q->orWhereRaw("REPLACE(REPLACE(REPLACE(clientPhoneNumber, '-', ''), ' ', ''), '+', '') LIKE ?", ["%{$cleanPhone}%"]);
                }
                // Name match
                if ($lead->firstName && $lead->lastName) {
                    $q->orWhere(function ($nameQ) use ($lead) {
                        $nameQ->where('clientFirstName', 'LIKE', "%{$lead->firstName}%")
                              ->where('clientLastName', 'LIKE', "%{$lead->lastName}%");
                    });
                }
            })->get();

            // Calculate confidence scores for each match
            foreach ($potentialMatches as $login) {
                $confidence = 0;
                $matchReasons = [];

                // Email match: highest priority (50 points)
                if ($lead->email && $login->clientEmailAddress) {
                    if (strtolower($lead->email) === strtolower($login->clientEmailAddress)) {
                        $confidence += 50;
                        $matchReasons[] = 'Exact email match';
                    }
                }

                // Phone match: high priority (40 points)
                if ($lead->phone && $login->clientPhoneNumber) {
                    $leadPhone = preg_replace('/[^0-9]/', '', $lead->phone);
                    $loginPhone = preg_replace('/[^0-9]/', '', $login->clientPhoneNumber);
                    if ($leadPhone === $loginPhone) {
                        $confidence += 40;
                        $matchReasons[] = 'Exact phone match';
                    } elseif (strlen($leadPhone) >= 7 && strlen($loginPhone) >= 7) {
                        // Partial phone match (last 7 digits)
                        if (substr($leadPhone, -7) === substr($loginPhone, -7)) {
                            $confidence += 25;
                            $matchReasons[] = 'Partial phone match';
                        }
                    }
                }

                // First name match (20 points exact, 10 points partial)
                if ($lead->firstName && $login->clientFirstName) {
                    if (strtolower($lead->firstName) === strtolower($login->clientFirstName)) {
                        $confidence += 20;
                        $matchReasons[] = 'First name match';
                    } elseif (stripos($login->clientFirstName, $lead->firstName) !== false ||
                              stripos($lead->firstName, $login->clientFirstName) !== false) {
                        $confidence += 10;
                        $matchReasons[] = 'Partial first name match';
                    }
                }

                // Last name match (20 points exact, 10 points partial)
                if ($lead->lastName && $login->clientLastName) {
                    if (strtolower($lead->lastName) === strtolower($login->clientLastName)) {
                        $confidence += 20;
                        $matchReasons[] = 'Last name match';
                    } elseif (stripos($login->clientLastName, $lead->lastName) !== false ||
                              stripos($lead->lastName, $login->clientLastName) !== false) {
                        $confidence += 10;
                        $matchReasons[] = 'Partial last name match';
                    }
                }

                // Only include if confidence is meaningful (at least 20%)
                if ($confidence >= 20) {
                    $fullName = trim(implode(' ', array_filter([
                        $login->clientFirstName,
                        $login->clientMiddleName,
                        $login->clientLastName
                    ])));

                    // Get store name
                    $storeName = 'Unknown Store';
                    if ($login->productStore) {
                        $store = \App\Models\EcomProductStore::find($login->productStore);
                        $storeName = $store ? $store->storeName : 'Unknown Store';
                    }

                    $matches[] = [
                        'id' => $login->id,
                        'store' => $storeName,
                        'fullName' => $fullName,
                        'email' => $login->clientEmailAddress,
                        'phone' => $login->clientPhoneNumber,
                        'isActive' => (bool) $login->isActive,
                        'isLinked' => ($lead->linkedStoreLoginId === $login->id),
                        'confidence' => min($confidence, 100),
                        'matchReasons' => $matchReasons,
                        'created_at' => $login->created_at,
                    ];
                }
            }

            // Sort by confidence descending
            usort($matches, function ($a, $b) {
                return $b['confidence'] - $a['confidence'];
            });

            // Limit to top 10 matches
            $matches = array_slice($matches, 0, 10);

            return response()->json([
                'success' => true,
                'data' => $matches,
                'total' => count($matches),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to find matches: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Find potential client matches for a lead.
     * Uses weighted matching algorithm for confidence scoring.
     */
    public function findPotentialClients(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lead_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid lead ID provided.'
            ], 422);
        }

        try {
            $lead = CrmLead::active()->findOrFail($request->lead_id);
            $matches = [];

            // Build query for potential matches
            $query = \App\Models\ClientAllDatabase::active();

            // Get all potential matches based on any criteria
            $potentialMatches = $query->where(function ($q) use ($lead) {
                // Email match (if lead has email)
                if ($lead->email) {
                    $q->orWhere('clientEmailAddress', $lead->email);
                }
                // Phone match (if lead has phone)
                if ($lead->phone) {
                    $cleanPhone = preg_replace('/[^0-9]/', '', $lead->phone);
                    $q->orWhereRaw("REPLACE(REPLACE(REPLACE(clientPhoneNumber, '-', ''), ' ', ''), '+', '') LIKE ?", ["%{$cleanPhone}%"]);
                }
                // Name match
                if ($lead->firstName && $lead->lastName) {
                    $q->orWhere(function ($nameQ) use ($lead) {
                        $nameQ->where('clientFirstName', 'LIKE', "%{$lead->firstName}%")
                              ->where('clientLastName', 'LIKE', "%{$lead->lastName}%");
                    });
                }
            })->get();

            // Calculate confidence scores for each match
            foreach ($potentialMatches as $client) {
                $confidence = 0;
                $matchReasons = [];

                // Check if this client is already linked to this lead
                $isLinked = ($lead->convertedToClientId === $client->id);

                // Email match: highest priority (50 points)
                if ($lead->email && $client->clientEmailAddress) {
                    if (strtolower($lead->email) === strtolower($client->clientEmailAddress)) {
                        $confidence += 50;
                        $matchReasons[] = 'Exact email match';
                    }
                }

                // Phone match: high priority (40 points)
                if ($lead->phone && $client->clientPhoneNumber) {
                    $leadPhone = preg_replace('/[^0-9]/', '', $lead->phone);
                    $clientPhone = preg_replace('/[^0-9]/', '', $client->clientPhoneNumber);
                    if ($leadPhone === $clientPhone) {
                        $confidence += 40;
                        $matchReasons[] = 'Exact phone match';
                    } elseif (strlen($leadPhone) >= 7 && strlen($clientPhone) >= 7) {
                        // Partial phone match (last 7 digits)
                        if (substr($leadPhone, -7) === substr($clientPhone, -7)) {
                            $confidence += 25;
                            $matchReasons[] = 'Partial phone match';
                        }
                    }
                }

                // First name match (20 points exact, 10 points partial)
                if ($lead->firstName && $client->clientFirstName) {
                    if (strtolower($lead->firstName) === strtolower($client->clientFirstName)) {
                        $confidence += 20;
                        $matchReasons[] = 'First name match';
                    } elseif (stripos($client->clientFirstName, $lead->firstName) !== false ||
                              stripos($lead->firstName, $client->clientFirstName) !== false) {
                        $confidence += 10;
                        $matchReasons[] = 'Partial first name match';
                    }
                }

                // Last name match (20 points exact, 10 points partial)
                if ($lead->lastName && $client->clientLastName) {
                    if (strtolower($lead->lastName) === strtolower($client->clientLastName)) {
                        $confidence += 20;
                        $matchReasons[] = 'Last name match';
                    } elseif (stripos($client->clientLastName, $lead->lastName) !== false ||
                              stripos($lead->lastName, $client->clientLastName) !== false) {
                        $confidence += 10;
                        $matchReasons[] = 'Partial last name match';
                    }
                }

                // Middle name bonus (5 points)
                if ($lead->middleName && $client->clientMiddleName) {
                    if (strtolower($lead->middleName) === strtolower($client->clientMiddleName)) {
                        $confidence += 5;
                        $matchReasons[] = 'Middle name match';
                    }
                }

                // Only include if confidence is meaningful (at least 20%)
                if ($confidence >= 20) {
                    $matches[] = [
                        'id' => $client->id,
                        'fullName' => $client->fullName,
                        'email' => $client->clientEmailAddress,
                        'phone' => $client->clientPhoneNumber,
                        'isLinked' => $isLinked,
                        'confidence' => min($confidence, 100),
                        'matchReasons' => $matchReasons,
                        'created_at' => $client->created_at?->format('Y-m-d H:i:s'),
                    ];
                }
            }

            // Sort by confidence descending
            usort($matches, function ($a, $b) {
                return $b['confidence'] - $a['confidence'];
            });

            // Limit to top 10 matches
            $matches = array_slice($matches, 0, 10);

            return response()->json([
                'success' => true,
                'data' => $matches,
                'total' => count($matches),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to find matches: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Parse uploaded CSV/Excel file and return columns.
     */
    public function parseImportFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid file. Please upload a CSV or Excel file (max 10MB).',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('file');
            $extension = strtolower($file->getClientOriginalExtension());

            $headers = [];
            $previewData = [];

            if (in_array($extension, ['csv', 'txt'])) {
                // Parse CSV
                $handle = fopen($file->getRealPath(), 'r');
                if ($handle) {
                    // Get headers from first row
                    $headers = fgetcsv($handle);
                    if ($headers) {
                        $headers = array_map('trim', $headers);
                    }

                    // Get preview data (first 5 rows)
                    $rowCount = 0;
                    while (($row = fgetcsv($handle)) !== false && $rowCount < 5) {
                        $previewData[] = array_map('trim', $row);
                        $rowCount++;
                    }
                    fclose($handle);
                }
            } elseif (in_array($extension, ['xlsx', 'xls'])) {
                // For Excel files, we'll use a simple approach with PhpSpreadsheet if available
                // Otherwise, return error suggesting CSV
                if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Excel files require PhpSpreadsheet library. Please use CSV format instead.'
                    ], 422);
                }

                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();

                if (count($rows) > 0) {
                    $headers = array_map('trim', $rows[0]);
                    $previewData = array_slice($rows, 1, 5);
                }
            }

            if (empty($headers)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not read file headers. Please ensure the file has a header row.'
                ], 422);
            }

            // Get available lead fields for mapping
            $leadFields = CrmLead::IMPORTABLE_FIELDS;
            $sources = CrmLeadSource::active()->enabled()->orderBy('sourceOrder')->get();
            $stores = \App\Models\EcomProductStore::active()->enabled()->orderBy('storeName')->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'headers' => $headers,
                    'preview' => $previewData,
                    'totalRows' => count($previewData),
                    'leadFields' => $leadFields,
                    'sources' => $sources,
                    'stores' => $stores,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Import file parse error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to parse file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process the import with column mappings.
     */
    public function processImport(Request $request)
    {
        // Decode mappings if sent as JSON string
        $mappingsData = $request->mappings;
        if (is_string($mappingsData)) {
            $mappingsData = json_decode($mappingsData, true);
        }

        // Merge decoded mappings back for validation
        $validationData = $request->all();
        $validationData['mappings'] = $mappingsData;

        $validator = Validator::make($validationData, [
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
            'mappings' => 'required|array',
            'mappings.*.column' => 'required|integer|min:0',
            'mappings.*.field' => 'required|string',
            'defaultStatus' => 'nullable|string',
            'defaultPriority' => 'nullable|string',
            'defaultSourceId' => 'nullable|integer',
            'referredBy' => 'nullable|string|max:255',
            'storeTargets' => 'nullable',
            'customFields' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid import data.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $file = $request->file('file');
            $extension = strtolower($file->getClientOriginalExtension());
            $mappings = $mappingsData;
            $defaultStatus = $request->defaultStatus ?? 'new';
            $defaultPriority = $request->defaultPriority ?? 'medium';
            $defaultSourceId = $request->defaultSourceId;
            $referredBy = $request->referredBy;

            // Decode store targets if sent as JSON string
            $storeTargets = $request->storeTargets;
            if (is_string($storeTargets)) {
                $storeTargets = json_decode($storeTargets, true) ?? [];
            }
            $storeTargets = $storeTargets ?? [];

            // Decode custom fields if sent as JSON string
            $defaultCustomFields = $request->customFields;
            if (is_string($defaultCustomFields)) {
                $defaultCustomFields = json_decode($defaultCustomFields, true) ?? [];
            }
            $defaultCustomFields = $defaultCustomFields ?? [];

            // Parse file data
            $rows = [];
            $headers = [];

            if (in_array($extension, ['csv', 'txt'])) {
                $handle = fopen($file->getRealPath(), 'r');
                if ($handle) {
                    $headers = fgetcsv($handle);
                    while (($row = fgetcsv($handle)) !== false) {
                        $rows[] = array_map('trim', $row);
                    }
                    fclose($handle);
                }
            } elseif (in_array($extension, ['xlsx', 'xls'])) {
                if (class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
                    $worksheet = $spreadsheet->getActiveSheet();
                    $allRows = $worksheet->toArray();
                    if (count($allRows) > 0) {
                        $headers = $allRows[0];
                        $rows = array_slice($allRows, 1);
                    }
                }
            }

            // Build mapping index (column index => field name or custom:fieldName)
            $columnMappings = [];
            foreach ($mappings as $mapping) {
                $columnMappings[$mapping['column']] = $mapping['field'];
            }

            // Get standard lead fields
            $standardFields = array_keys(CrmLead::IMPORTABLE_FIELDS);

            $importedCount = 0;
            $skippedCount = 0;
            $errors = [];

            foreach ($rows as $rowIndex => $row) {
                try {
                    $leadData = [
                        'usersId' => Auth::id(),
                        'leadStatus' => $defaultStatus,
                        'leadPriority' => $defaultPriority,
                        'leadSourceId' => $defaultSourceId,
                        'referredBy' => $referredBy,
                        'delete_status' => 'active',
                    ];
                    $customData = [];

                    // Map columns to fields
                    foreach ($columnMappings as $colIndex => $field) {
                        $value = isset($row[$colIndex]) ? trim($row[$colIndex]) : '';

                        if (empty($value)) {
                            continue;
                        }

                        if (strpos($field, 'custom:') === 0) {
                            // Custom field
                            $customFieldName = substr($field, 7); // Remove 'custom:' prefix
                            $customData[$customFieldName] = $value;
                        } elseif ($field === 'fullName') {
                            // Parse full name into parts
                            $nameParts = $this->parseFullName($value);
                            $leadData['firstName'] = $nameParts['firstName'];
                            $leadData['middleName'] = $nameParts['middleName'];
                            $leadData['lastName'] = $nameParts['lastName'];
                        } elseif (in_array($field, $standardFields)) {
                            // Standard lead field
                            $leadData[$field] = $value;
                        }
                    }

                    // Validate required fields - need either (firstName AND lastName) OR fullName was parsed
                    if (empty($leadData['firstName']) && empty($leadData['lastName'])) {
                        $skippedCount++;
                        $errors[] = "Row " . ($rowIndex + 2) . ": Missing required fields (First Name and Last Name, or Full Name)";
                        continue;
                    }

                    // If only one name part exists, use it for firstName
                    if (empty($leadData['firstName']) && !empty($leadData['lastName'])) {
                        $leadData['firstName'] = $leadData['lastName'];
                        $leadData['lastName'] = '';
                    }

                    // Create the lead
                    $lead = CrmLead::create($leadData);

                    // Add custom data from column mappings
                    foreach ($customData as $fieldName => $fieldValue) {
                        CrmLeadCustomData::create([
                            'leadId' => $lead->id,
                            'fieldName' => $fieldName,
                            'fieldValue' => $fieldValue,
                            'usersId' => Auth::id(),
                            'delete_status' => 'active',
                        ]);
                    }

                    // Add default custom fields (applied to all leads)
                    foreach ($defaultCustomFields as $customField) {
                        if (!empty($customField['name'])) {
                            CrmLeadCustomData::create([
                                'leadId' => $lead->id,
                                'fieldName' => $customField['name'],
                                'fieldValue' => $customField['value'] ?? '',
                                'usersId' => Auth::id(),
                                'delete_status' => 'active',
                            ]);
                        }
                    }

                    // Sync store targets
                    if (!empty($storeTargets)) {
                        $lead->targetStores()->sync($storeTargets);
                    }

                    // Log activity
                    $lead->logActivity('note', 'Lead imported from file', Auth::id());

                    $importedCount++;

                } catch (\Exception $rowError) {
                    $skippedCount++;
                    $errors[] = "Row " . ($rowIndex + 2) . ": " . $rowError->getMessage();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Import completed! {$importedCount} leads imported, {$skippedCount} skipped.",
                'data' => [
                    'imported' => $importedCount,
                    'skipped' => $skippedCount,
                    'errors' => array_slice($errors, 0, 10), // Return first 10 errors
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Import process error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add custom data to a lead.
     */
    public function addCustomData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lead_id' => 'required|integer',
            'field_name' => 'required|string|max:255',
            'field_value' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $lead = CrmLead::active()->findOrFail($request->lead_id);

            $customData = CrmLeadCustomData::create([
                'leadId' => $lead->id,
                'fieldName' => $request->field_name,
                'fieldValue' => $request->field_value,
                'usersId' => Auth::id(),
                'delete_status' => 'active',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Custom data added successfully!',
                'data' => $customData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add custom data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update custom data.
     */
    public function updateCustomData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
            'field_name' => 'required|string|max:255',
            'field_value' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $customData = CrmLeadCustomData::active()->findOrFail($request->id);

            $customData->update([
                'fieldName' => $request->field_name,
                'fieldValue' => $request->field_value,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Custom data updated successfully!',
                'data' => $customData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update custom data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete custom data.
     */
    public function deleteCustomData(Request $request)
    {
        try {
            $customData = CrmLeadCustomData::active()->findOrFail($request->id);
            $customData->update(['delete_status' => 'deleted']);

            return response()->json([
                'success' => true,
                'message' => 'Custom data deleted successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete custom data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Parse a full name string into first, middle, and last name parts.
     */
    private function parseFullName($fullName)
    {
        $fullName = trim($fullName);
        $parts = preg_split('/\s+/', $fullName);

        $result = [
            'firstName' => '',
            'middleName' => '',
            'lastName' => '',
        ];

        $count = count($parts);

        if ($count === 1) {
            // Single name - use as first name
            $result['firstName'] = $parts[0];
        } elseif ($count === 2) {
            // Two parts - first and last name
            $result['firstName'] = $parts[0];
            $result['lastName'] = $parts[1];
        } elseif ($count === 3) {
            // Three parts - first, middle, last
            $result['firstName'] = $parts[0];
            $result['middleName'] = $parts[1];
            $result['lastName'] = $parts[2];
        } else {
            // More than 3 parts - first name, everything in middle as middle name, last as last name
            $result['firstName'] = $parts[0];
            $result['lastName'] = array_pop($parts);
            array_shift($parts); // Remove first name
            $result['middleName'] = implode(' ', $parts);
        }

        return $result;
    }

    /**
     * Link a store login to a lead (confirm the connection).
     */
    public function linkStoreLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lead_id' => 'required|integer',
            'login_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data provided.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $lead = CrmLead::active()->findOrFail($request->lead_id);

            // Verify the login exists
            $login = ClientAccessLogin::active()->find($request->login_id);
            if (!$login) {
                return response()->json([
                    'success' => false,
                    'message' => 'Store login not found.'
                ], 404);
            }

            // Link the store login
            $lead->update([
                'linkedStoreLoginId' => $request->login_id,
                'linkedStoreLoginAt' => now(),
            ]);

            // Log the activity
            $storeName = $login->store ? $login->store->storeName : 'Unknown Store';
            $lead->logActivity(
                'note',
                "Linked to store login: {$login->full_name} ({$storeName})",
                Auth::id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Store login linked successfully.',
                'data' => [
                    'login_id' => $request->login_id,
                    'login_name' => $login->full_name,
                    'store_name' => $storeName,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to link store login: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unlink a store login from a lead.
     */
    public function unlinkStoreLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lead_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data provided.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $lead = CrmLead::active()->findOrFail($request->lead_id);

            if (!$lead->linkedStoreLoginId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No store login is currently linked.'
                ], 400);
            }

            $oldLogin = $lead->linkedStoreLogin;
            $oldLoginName = $oldLogin ? $oldLogin->full_name : 'Unknown';

            // Unlink the store login
            $lead->update([
                'linkedStoreLoginId' => null,
                'linkedStoreLoginAt' => null,
            ]);

            // Log the activity
            $lead->logActivity(
                'note',
                "Unlinked from store login: {$oldLoginName}",
                Auth::id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Store login unlinked successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to unlink store login: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Link a client to a lead (confirm the connection).
     */
    public function linkClient(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lead_id' => 'required|integer',
            'client_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data provided.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $lead = CrmLead::active()->findOrFail($request->lead_id);

            // Verify the client exists
            $client = ClientAllDatabase::active()->find($request->client_id);
            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Client not found.'
                ], 404);
            }

            // Link the client
            $lead->update([
                'convertedToClientId' => $request->client_id,
                'linkedClientAt' => now(),
            ]);

            // Log the activity
            $lead->logActivity(
                'note',
                "Linked to client: {$client->fullName}",
                Auth::id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Client linked successfully.',
                'data' => [
                    'client_id' => $request->client_id,
                    'client_name' => $client->fullName,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to link client: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unlink a client from a lead.
     */
    public function unlinkClient(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lead_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data provided.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $lead = CrmLead::active()->findOrFail($request->lead_id);

            if (!$lead->convertedToClientId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No client is currently linked.'
                ], 400);
            }

            $oldClient = $lead->convertedClient;
            $oldClientName = $oldClient ? $oldClient->fullName : 'Unknown';

            // Unlink the client
            $lead->update([
                'convertedToClientId' => null,
                'linkedClientAt' => null,
                'conversionDate' => null,
            ]);

            // Log the activity
            $lead->logActivity(
                'note',
                "Unlinked from client: {$oldClientName}",
                Auth::id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Client unlinked successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to unlink client: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get currently linked store login and client for a lead.
     */
    public function getLinkedConnections(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lead_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data provided.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $lead = CrmLead::active()
                ->with(['linkedStoreLogin.store', 'convertedClient'])
                ->findOrFail($request->lead_id);

            $linkedLogin = null;
            if ($lead->linkedStoreLogin) {
                $loginStore = $lead->linkedStoreLogin->store;
                $linkedLogin = [
                    'id' => $lead->linkedStoreLogin->id,
                    'fullName' => $lead->linkedStoreLogin->full_name,
                    'email' => $lead->linkedStoreLogin->clientEmailAddress,
                    'phone' => $lead->linkedStoreLogin->clientPhoneNumber,
                    'store' => $loginStore ? $loginStore->storeName : 'Unknown Store',
                    'linkedAt' => $lead->linkedStoreLoginAt ? $lead->linkedStoreLoginAt->format('M d, Y h:i A') : null,
                ];
            }

            $linkedClient = null;
            if ($lead->convertedClient) {
                $linkedClient = [
                    'id' => $lead->convertedClient->id,
                    'fullName' => $lead->convertedClient->fullName,
                    'email' => $lead->convertedClient->clientEmailAddress,
                    'phone' => $lead->convertedClient->clientPhoneNumber,
                    'linkedAt' => $lead->linkedClientAt ? $lead->linkedClientAt->format('M d, Y h:i A') : null,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'linkedLogin' => $linkedLogin,
                    'linkedClient' => $linkedClient,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get linked connections: ' . $e->getMessage()
            ], 500);
        }
    }
}
