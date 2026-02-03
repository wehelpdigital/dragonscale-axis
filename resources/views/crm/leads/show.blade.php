@extends('layouts.master')

@section('title') View Lead - {{ $lead->fullName }} @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />

<style>
    .section-header {
        background: linear-gradient(135deg, #556ee6 0%, #3b5de7 100%);
        color: #fff;
        padding: 0.75rem 1rem;
        margin: -1rem -1rem 1rem -1rem;
        border-radius: 0.25rem 0.25rem 0 0;
        font-size: 14px;
        font-weight: 600;
    }
    .section-header i {
        opacity: 0.85;
    }
    .card-section {
        margin-bottom: 1.5rem;
    }
    .info-label {
        font-size: 12px;
        color: #74788d;
        margin-bottom: 2px;
    }
    .info-value {
        font-size: 14px;
        color: #495057;
        font-weight: 500;
    }
    .info-value a {
        color: #556ee6;
    }
    .social-link {
        display: inline-flex;
        align-items: center;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 13px;
        text-decoration: none;
        margin-right: 6px;
        margin-bottom: 6px;
        transition: all 0.2s ease;
    }
    .social-link:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }
    .social-link i {
        margin-right: 6px;
        font-size: 16px;
    }
    .activity-item {
        padding: 0.75rem;
        border-left: 3px solid #e9ebec;
        margin-bottom: 0.5rem;
        background: #f8f9fa;
        border-radius: 0 4px 4px 0;
    }
    .activity-item.activity-call_outbound { border-left-color: #556ee6; }
    .activity-item.activity-call_inbound { border-left-color: #50a5f1; }
    .activity-item.activity-email_sent { border-left-color: #34c38f; }
    .activity-item.activity-email_received { border-left-color: #50a5f1; }
    .activity-item.activity-meeting { border-left-color: #f1b44c; }
    .activity-item.activity-note { border-left-color: #74788d; }
    .activity-item.activity-status_change { border-left-color: #343a40; }
    .activity-item.activity-follow_up { border-left-color: #556ee6; }
    .activity-item.activity-proposal_sent { border-left-color: #34c38f; }
    .activity-item.activity-document_sent { border-left-color: #50a5f1; }
    .activity-item.activity-social_media { border-left-color: #556ee6; }
    .activity-icon {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
    }
    .match-item {
        padding: 0.6rem;
        border: 1px solid #e9ebec;
        border-radius: 6px;
        margin-bottom: 0.5rem;
        background: #fafbfc;
        transition: all 0.2s ease;
    }
    .match-item:hover {
        background: #f3f6f9;
        border-color: #556ee6;
    }
    .match-item:last-child {
        margin-bottom: 0;
    }
    .confidence-bar {
        height: 4px;
        border-radius: 2px;
        background: #e9ebec;
        overflow: hidden;
    }
    .confidence-fill {
        height: 100%;
        border-radius: 2px;
        transition: width 0.3s ease;
    }
    .confidence-high { background: #34c38f; }
    .confidence-medium { background: #f1b44c; }
    .confidence-low { background: #74788d; }
    .match-badge {
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 8px;
    }
    .match-loading {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
    }
    .match-reason {
        font-size: 10px;
        padding: 1px 5px;
        border-radius: 4px;
        background: #e8f0fe;
        color: #556ee6;
        margin-right: 3px;
        margin-bottom: 3px;
        display: inline-block;
    }
    .link-btn {
        border: none;
        background: none;
        cursor: pointer;
        padding: 2px 6px;
        border-radius: 4px;
        transition: all 0.2s ease;
    }
    .link-btn:hover {
        background: rgba(0,0,0,0.05);
    }
    .link-btn.linked {
        color: #f1b44c;
    }
    .link-btn.not-linked {
        color: #c4c4c4;
    }
    .link-btn.not-linked:hover {
        color: #f1b44c;
    }
    .linked-indicator {
        background: linear-gradient(135deg, #f1b44c 0%, #e09b30 100%);
        color: #fff;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .linked-card {
        border: 2px solid #f1b44c;
        background: #fffbf5;
    }
    .priority-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
    }
    .lead-header-card {
        background: linear-gradient(135deg, #556ee6 0%, #3b5de7 100%);
        color: #fff;
    }
    .lead-header-card .text-muted {
        color: rgba(255,255,255,0.7) !important;
    }
</style>
@endsection

@section('content')

    @component('components.breadcrumb')
        @slot('li_1') CRM @endslot
        @slot('li_2') <a href="{{ route('crm-leads') }}">Leads</a> @endslot
        @slot('title') View Lead @endslot
    @endcomponent

    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Lead Header Card -->
            <div class="card mb-3 lead-header-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-1 text-white">{{ $lead->fullName }}</h4>
                            @if($lead->companyName)
                                <p class="text-muted mb-0">{{ $lead->companyName }} {{ $lead->jobTitle ? '- ' . $lead->jobTitle : '' }}</p>
                            @endif
                        </div>
                        <div class="text-end">
                            <span class="badge bg-white text-{{ $lead->status_color }} fs-6 mb-1">{{ $lead->status_label }}</span>
                            <div class="small text-muted">
                                Created {{ $lead->created_at->format('M d, Y') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="card card-section">
                <div class="card-body">
                    <div class="section-header">
                        <i class="mdi mdi-account me-2"></i>Contact Information
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="info-label">Full Name</div>
                            <div class="info-value">{{ $lead->fullName }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="info-label">Email Address</div>
                            <div class="info-value">
                                @if($lead->email)
                                    <a href="mailto:{{ $lead->email }}"><i class="mdi mdi-email me-1"></i>{{ $lead->email }}</a>
                                @else
                                    <span class="text-secondary">-</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="info-label">Phone Number</div>
                            <div class="info-value">
                                @if($lead->phone)
                                    <a href="tel:{{ $lead->phone }}"><i class="mdi mdi-phone me-1"></i>{{ $lead->phone }}</a>
                                @else
                                    <span class="text-secondary">-</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="info-label">Alternate Phone</div>
                            <div class="info-value">
                                @if($lead->alternatePhone)
                                    <a href="tel:{{ $lead->alternatePhone }}">{{ $lead->alternatePhone }}</a>
                                @else
                                    <span class="text-secondary">-</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Company Information -->
            @if($lead->companyName || $lead->jobTitle || $lead->industry)
            <div class="card card-section">
                <div class="card-body">
                    <div class="section-header">
                        <i class="mdi mdi-domain me-2"></i>Company Information
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="info-label">Company Name</div>
                            <div class="info-value">{{ $lead->companyName ?: '-' }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="info-label">Job Title</div>
                            <div class="info-value">{{ $lead->jobTitle ?: '-' }}</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="info-label">Department</div>
                            <div class="info-value">{{ $lead->department ?: '-' }}</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="info-label">Industry</div>
                            <div class="info-value">{{ $lead->industry ?: '-' }}</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="info-label">Company Size</div>
                            <div class="info-value">{{ $lead->companySize ? \App\Models\CrmLead::COMPANY_SIZE_OPTIONS[$lead->companySize] ?? $lead->companySize : '-' }}</div>
                        </div>
                        @if($lead->website)
                        <div class="col-md-12">
                            <div class="info-label">Website</div>
                            <div class="info-value">
                                <a href="{{ $lead->website }}" target="_blank"><i class="mdi mdi-open-in-new me-1"></i>{{ $lead->website }}</a>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <!-- Address Information -->
            @if($lead->province || $lead->municipality || $lead->streetAddress)
            <div class="card card-section">
                <div class="card-body">
                    <div class="section-header">
                        <i class="mdi mdi-map-marker me-2"></i>Address Information
                    </div>

                    <div class="info-value">
                        <i class="mdi mdi-map-marker-outline me-1 text-secondary"></i>
                        {{ $lead->fullAddress ?: 'No address provided' }}
                    </div>
                </div>
            </div>
            @endif

            <!-- Social Media -->
            @if($lead->facebookUrl || $lead->instagramUrl || $lead->linkedinUrl || $lead->twitterUrl || $lead->tiktokUrl || $lead->viberNumber || $lead->whatsappNumber)
            <div class="card card-section">
                <div class="card-body">
                    <div class="section-header">
                        <i class="mdi mdi-share-variant me-2"></i>Social Media
                    </div>

                    <div class="d-flex flex-wrap">
                        @if($lead->viberNumber)
                            <a href="viber://chat?number={{ preg_replace('/[^0-9]/', '', $lead->viberNumber) }}" class="social-link" style="background: #7360F220; color: #7360F2;">
                                <i class="mdi mdi-phone"></i> {{ $lead->viberNumber }}
                            </a>
                        @endif
                        @if($lead->whatsappNumber)
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $lead->whatsappNumber) }}" target="_blank" class="social-link" style="background: #25D36620; color: #25D366;">
                                <i class="mdi mdi-whatsapp"></i> {{ $lead->whatsappNumber }}
                            </a>
                        @endif
                        @if($lead->facebookUrl)
                            <a href="{{ $lead->facebookUrl }}" target="_blank" class="social-link" style="background: #1877F220; color: #1877F2;">
                                <i class="mdi mdi-facebook"></i> Facebook
                            </a>
                        @endif
                        @if($lead->instagramUrl)
                            <a href="{{ $lead->instagramUrl }}" target="_blank" class="social-link" style="background: #E4405F20; color: #E4405F;">
                                <i class="mdi mdi-instagram"></i> Instagram
                            </a>
                        @endif
                        @if($lead->linkedinUrl)
                            <a href="{{ $lead->linkedinUrl }}" target="_blank" class="social-link" style="background: #0A66C220; color: #0A66C2;">
                                <i class="mdi mdi-linkedin"></i> LinkedIn
                            </a>
                        @endif
                        @if($lead->twitterUrl)
                            <a href="{{ $lead->twitterUrl }}" target="_blank" class="social-link" style="background: #1DA1F220; color: #1DA1F2;">
                                <i class="mdi mdi-twitter"></i> Twitter/X
                            </a>
                        @endif
                        @if($lead->tiktokUrl)
                            <a href="{{ $lead->tiktokUrl }}" target="_blank" class="social-link" style="background: #00000020; color: #000000;">
                                <i class="mdi mdi-music-note"></i> TikTok
                            </a>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <!-- Notes -->
            @if($lead->notes)
            <div class="card card-section">
                <div class="card-body">
                    <div class="section-header">
                        <i class="mdi mdi-note-text me-2"></i>Notes
                    </div>

                    <p class="text-dark mb-0" style="white-space: pre-wrap;">{{ $lead->notes }}</p>
                </div>
            </div>
            @endif

            <!-- Custom Data -->
            @if($lead->customData->count() > 0)
            <div class="card card-section">
                <div class="card-body">
                    <div class="section-header" style="background: linear-gradient(135deg, #f1b44c 0%, #e09b30 100%);">
                        <i class="mdi mdi-database me-2"></i>Custom Data
                        <span class="badge bg-white text-warning ms-auto">{{ $lead->customData->count() }}</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <tbody>
                                @foreach($lead->customData as $data)
                                <tr>
                                    <td class="fw-medium text-dark" style="width: 35%;">{{ $data->fieldName }}</td>
                                    <td class="text-dark">{{ $data->fieldValue ?: '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Activity Timeline -->
            <div class="card card-section">
                <div class="card-body">
                    <div class="section-header">
                        <i class="mdi mdi-timeline-text me-2"></i>Activity Timeline
                    </div>

                    <div id="activityTimeline">
                        @forelse($lead->activities as $activity)
                            <div class="activity-item activity-{{ $activity->activityType }}">
                                <div class="d-flex align-items-start">
                                    <div class="activity-icon bg-{{ $activity->type_color }} text-white me-2">
                                        <i class="{{ $activity->type_icon }}"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between">
                                            <strong class="text-dark small">{{ $activity->type_label }}</strong>
                                            <small class="text-secondary">{{ $activity->activityDate->format('M d, Y h:i A') }}</small>
                                        </div>
                                        <p class="mb-0 small text-secondary">{{ $activity->activityDescription }}</p>
                                        @if($activity->user)
                                            <small class="text-muted"><i class="mdi mdi-account me-1"></i>{{ $activity->user->name }}</small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-3">
                                <i class="mdi mdi-timeline-text-outline text-secondary" style="font-size: 2rem;"></i>
                                <p class="text-secondary mb-0 small">No activities logged yet.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Lead Settings -->
            <div class="card card-section">
                <div class="card-body">
                    <div class="section-header">
                        <i class="mdi mdi-cog me-2"></i>Lead Details
                    </div>

                    <div class="mb-3">
                        <div class="info-label">Status</div>
                        <div class="info-value">
                            <span class="badge bg-{{ $lead->status_color }}">
                                <i class="{{ $lead->status_icon }} me-1"></i>{{ $lead->status_label }}
                            </span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="info-label">Priority</div>
                        <div class="info-value">
                            <span class="priority-badge bg-{{ $lead->priority_color }} {{ in_array($lead->leadPriority, ['low', 'medium']) ? 'text-white' : 'text-dark' }}">
                                {{ $lead->priority_label }}
                            </span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="info-label">Lead Source</div>
                        <div class="info-value">
                            @if($lead->source)
                                <i class="{{ $lead->source->formatted_icon }} me-1" style="color: {{ $lead->source->sourceColor ?? '#74788d' }}"></i>
                                {{ $lead->source->sourceName }}
                                @if($lead->leadSourceOther)
                                    <small class="text-secondary">({{ $lead->leadSourceOther }})</small>
                                @endif
                            @else
                                <span class="text-secondary">-</span>
                            @endif
                        </div>
                    </div>

                    @if($lead->referredBy)
                    <div class="mb-3">
                        <div class="info-label">Referred By</div>
                        <div class="info-value">{{ $lead->referredBy }}</div>
                    </div>
                    @endif

                    @if($lead->assignee)
                    <div class="mb-0">
                        <div class="info-label">Assigned To</div>
                        <div class="info-value"><i class="mdi mdi-account me-1"></i>{{ $lead->assignee->name }}</div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Store Targets -->
            @if($lead->targetStores->count() > 0)
            <div class="card card-section">
                <div class="card-body">
                    <div class="section-header">
                        <i class="mdi mdi-store me-2"></i>Store Targets
                        <span class="badge bg-white text-primary ms-auto">{{ $lead->targetStores->count() }}</span>
                    </div>

                    <div class="store-targets-list">
                        @foreach($lead->targetStores as $store)
                        <div class="d-flex align-items-center mb-2">
                            @if($store->storeLogo)
                                <img src="{{ asset($store->storeLogo) }}" alt="{{ $store->storeName }}"
                                     style="width: 24px; height: 24px; object-fit: contain; margin-right: 8px;">
                            @else
                                <i class="mdi mdi-store text-primary me-2" style="font-size: 20px;"></i>
                            @endif
                            <span class="text-dark">{{ $store->storeName }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Loss Reason (shown if status is lost) -->
            @if($lead->leadStatus == 'lost' && ($lead->lossReason || $lead->lossDetails))
            <div class="card card-section">
                <div class="card-body">
                    <div class="section-header bg-danger">
                        <i class="mdi mdi-close-circle me-2"></i>Loss Information
                    </div>

                    @if($lead->lossReason)
                    <div class="mb-2">
                        <div class="info-label">Loss Reason</div>
                        <div class="info-value">{{ $lead->lossReason }}</div>
                    </div>
                    @endif

                    @if($lead->lossDetails)
                    <div class="mb-0">
                        <div class="info-label">Details</div>
                        <div class="info-value">{{ $lead->lossDetails }}</div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Potential Store Logins -->
            <div class="card card-section">
                <div class="card-body">
                    <div class="section-header" style="background: linear-gradient(135deg, #34c38f 0%, #2ca67a 100%);">
                        <i class="mdi mdi-account-key me-2"></i>Potential Store Logins
                        <span class="badge bg-white text-success ms-auto" id="loginMatchCount">...</span>
                    </div>

                    <div id="potentialLoginsContainer">
                        <div class="match-loading">
                            <div class="spinner-border spinner-border-sm text-success me-2" role="status"></div>
                            <span class="text-secondary small">Checking matches...</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Potential Clients -->
            <div class="card card-section">
                <div class="card-body">
                    <div class="section-header" style="background: linear-gradient(135deg, #50a5f1 0%, #3d8bd9 100%);">
                        <i class="mdi mdi-account-group me-2"></i>Potential Clients
                        <span class="badge bg-white text-info ms-auto" id="clientMatchCount">...</span>
                    </div>

                    <div id="potentialClientsContainer">
                        <div class="match-loading">
                            <div class="spinner-border spinner-border-sm text-info me-2" role="status"></div>
                            <span class="text-secondary small">Checking matches...</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="card">
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('crm-leads.edit', ['id' => $lead->id]) }}" class="btn btn-primary">
                            <i class="bx bx-edit me-1"></i> Edit Lead
                        </a>
                        <a href="{{ route('crm-leads') }}" class="btn btn-secondary">
                            <i class="bx bx-arrow-back me-1"></i> Back to Leads
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>

<script>
$(document).ready(function() {
    const leadId = {{ $lead->id }};

    // Track currently linked items
    let linkedLoginId = {{ $lead->linkedStoreLoginId ?? 'null' }};
    let linkedClientId = {{ $lead->convertedToClientId ?? 'null' }};

    // Toastr options
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };

    // ============================
    // POTENTIAL MATCHES LOADING
    // ============================

    // Load potential store logins
    function loadPotentialLogins() {
        $.ajax({
            url: '{{ route("crm-leads.potential-logins") }}',
            type: 'GET',
            data: { lead_id: leadId },
            success: function(response) {
                if (response.success) {
                    renderLoginMatches(response.data);
                    $('#loginMatchCount').text(response.total);
                } else {
                    $('#potentialLoginsContainer').html('<div class="text-center py-3 text-secondary small">Failed to load matches</div>');
                    $('#loginMatchCount').text('0');
                }
            },
            error: function() {
                $('#potentialLoginsContainer').html('<div class="text-center py-3 text-secondary small">Error loading matches</div>');
                $('#loginMatchCount').text('!');
            }
        });
    }

    // Load potential clients
    function loadPotentialClients() {
        $.ajax({
            url: '{{ route("crm-leads.potential-clients") }}',
            type: 'GET',
            data: { lead_id: leadId },
            success: function(response) {
                if (response.success) {
                    renderClientMatches(response.data);
                    $('#clientMatchCount').text(response.total);
                } else {
                    $('#potentialClientsContainer').html('<div class="text-center py-3 text-secondary small">Failed to load matches</div>');
                    $('#clientMatchCount').text('0');
                }
            },
            error: function() {
                $('#potentialClientsContainer').html('<div class="text-center py-3 text-secondary small">Error loading matches</div>');
                $('#clientMatchCount').text('!');
            }
        });
    }

    // ============================
    // LINK/UNLINK FUNCTIONS
    // ============================

    // Link store login
    function linkStoreLogin(loginId) {
        $.ajax({
            url: '{{ route("crm-leads.link-store-login") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                lead_id: leadId,
                login_id: loginId
            },
            success: function(response) {
                if (response.success) {
                    linkedLoginId = loginId;
                    toastr.success(response.message, 'Linked!');
                    loadPotentialLogins(); // Refresh the list
                } else {
                    toastr.error(response.message, 'Error');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to link', 'Error');
            }
        });
    }

    // Unlink store login
    function unlinkStoreLogin() {
        $.ajax({
            url: '{{ route("crm-leads.unlink-store-login") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                lead_id: leadId
            },
            success: function(response) {
                if (response.success) {
                    linkedLoginId = null;
                    toastr.success(response.message, 'Unlinked!');
                    loadPotentialLogins(); // Refresh the list
                } else {
                    toastr.error(response.message, 'Error');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to unlink', 'Error');
            }
        });
    }

    // Link client
    function linkClient(clientId) {
        $.ajax({
            url: '{{ route("crm-leads.link-client") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                lead_id: leadId,
                client_id: clientId
            },
            success: function(response) {
                if (response.success) {
                    linkedClientId = clientId;
                    toastr.success(response.message, 'Linked!');
                    loadPotentialClients(); // Refresh the list
                } else {
                    toastr.error(response.message, 'Error');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to link', 'Error');
            }
        });
    }

    // Unlink client
    function unlinkClient() {
        $.ajax({
            url: '{{ route("crm-leads.unlink-client") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                lead_id: leadId
            },
            success: function(response) {
                if (response.success) {
                    linkedClientId = null;
                    toastr.success(response.message, 'Unlinked!');
                    loadPotentialClients(); // Refresh the list
                } else {
                    toastr.error(response.message, 'Error');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to unlink', 'Error');
            }
        });
    }

    // Render store login matches
    function renderLoginMatches(matches) {
        const $container = $('#potentialLoginsContainer');

        if (matches.length === 0 && !linkedLoginId) {
            $container.html(`
                <div class="text-center py-3">
                    <i class="mdi mdi-account-search-outline text-secondary" style="font-size: 1.8rem;"></i>
                    <p class="text-secondary mb-0 small mt-1">No potential matches found</p>
                </div>
            `);
            return;
        }

        // Sort: linked items first, then by confidence
        matches.sort(function(a, b) {
            if (a.isLinked && !b.isLinked) return -1;
            if (!a.isLinked && b.isLinked) return 1;
            return b.confidence - a.confidence;
        });

        let html = '';
        matches.forEach(function(match) {
            const isLinked = match.isLinked;
            if (isLinked) linkedLoginId = match.id; // Update local state

            const confidenceClass = match.confidence >= 70 ? 'confidence-high' :
                                   (match.confidence >= 40 ? 'confidence-medium' : 'confidence-low');
            const statusBadge = match.isActive
                ? '<span class="match-badge bg-success text-white">Active</span>'
                : '<span class="match-badge bg-secondary text-white">Inactive</span>';

            const linkedBadge = isLinked
                ? '<span class="linked-indicator"><i class="mdi mdi-star"></i> Confirmed</span>'
                : '';

            let reasonsHtml = '';
            match.matchReasons.forEach(function(reason) {
                reasonsHtml += `<span class="match-reason">${escapeHtml(reason)}</span>`;
            });

            html += `
                <div class="match-item ${isLinked ? 'linked-card' : ''}">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <div class="d-flex align-items-center gap-2">
                            <strong class="text-dark small">${escapeHtml(match.fullName)}</strong>
                            ${statusBadge}
                            ${linkedBadge}
                        </div>
                        <span class="badge bg-success bg-opacity-10 text-success small">${match.confidence}%</span>
                    </div>
                    <div class="confidence-bar mb-1">
                        <div class="confidence-fill ${confidenceClass}" style="width: ${match.confidence}%;"></div>
                    </div>
                    <div class="small text-secondary mb-1">
                        <i class="mdi mdi-store me-1"></i>${escapeHtml(match.store || 'Unknown Store')}
                    </div>
                    ${match.email ? `<div class="small text-secondary"><i class="mdi mdi-email me-1"></i>${escapeHtml(match.email)}</div>` : ''}
                    ${match.phone ? `<div class="small text-secondary"><i class="mdi mdi-phone me-1"></i>${escapeHtml(match.phone)}</div>` : ''}
                    <div class="mt-1">${reasonsHtml}</div>
                    <div class="mt-2">
                        ${isLinked
                            ? `<button type="button" class="btn btn-sm btn-warning unlink-login-btn" data-login-id="${match.id}">
                                   <i class="mdi mdi-star me-1"></i>Unlink
                               </button>`
                            : `<button type="button" class="btn btn-sm btn-outline-warning link-login-btn" data-login-id="${match.id}">
                                   <i class="mdi mdi-star-outline me-1"></i>Confirm this Login
                               </button>`
                        }
                    </div>
                </div>
            `;
        });

        $container.html(html);

        // Attach event handlers
        $container.find('.link-login-btn').on('click', function() {
            const loginId = $(this).data('login-id');
            linkStoreLogin(loginId);
        });

        $container.find('.unlink-login-btn').on('click', function() {
            unlinkStoreLogin();
        });
    }

    // Render client matches
    function renderClientMatches(matches) {
        const $container = $('#potentialClientsContainer');

        if (matches.length === 0 && !linkedClientId) {
            $container.html(`
                <div class="text-center py-3">
                    <i class="mdi mdi-account-group-outline text-secondary" style="font-size: 1.8rem;"></i>
                    <p class="text-secondary mb-0 small mt-1">No potential matches found</p>
                </div>
            `);
            return;
        }

        // Sort: linked items first, then by confidence
        matches.sort(function(a, b) {
            if (a.isLinked && !b.isLinked) return -1;
            if (!a.isLinked && b.isLinked) return 1;
            return b.confidence - a.confidence;
        });

        let html = '';
        matches.forEach(function(match) {
            const isLinked = match.isLinked;
            if (isLinked) linkedClientId = match.id; // Update local state

            const confidenceClass = match.confidence >= 70 ? 'confidence-high' :
                                   (match.confidence >= 40 ? 'confidence-medium' : 'confidence-low');

            const linkedBadge = isLinked
                ? '<span class="linked-indicator"><i class="mdi mdi-star"></i> Confirmed</span>'
                : '';

            let reasonsHtml = '';
            match.matchReasons.forEach(function(reason) {
                reasonsHtml += `<span class="match-reason">${escapeHtml(reason)}</span>`;
            });

            html += `
                <div class="match-item ${isLinked ? 'linked-card' : ''}">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <div class="d-flex align-items-center gap-2">
                            <strong class="text-dark small">${escapeHtml(match.fullName)}</strong>
                            ${linkedBadge}
                        </div>
                        <span class="badge bg-info bg-opacity-10 text-info small">${match.confidence}%</span>
                    </div>
                    <div class="confidence-bar mb-1">
                        <div class="confidence-fill ${confidenceClass}" style="width: ${match.confidence}%;"></div>
                    </div>
                    ${match.email ? `<div class="small text-secondary"><i class="mdi mdi-email me-1"></i>${escapeHtml(match.email)}</div>` : ''}
                    ${match.phone ? `<div class="small text-secondary"><i class="mdi mdi-phone me-1"></i>${escapeHtml(match.phone)}</div>` : ''}
                    <div class="mt-1">${reasonsHtml}</div>
                    <div class="mt-2 d-flex gap-2 align-items-center">
                        ${isLinked
                            ? `<button type="button" class="btn btn-sm btn-info unlink-client-btn" data-client-id="${match.id}">
                                   <i class="mdi mdi-star me-1"></i>Unlink
                               </button>`
                            : `<button type="button" class="btn btn-sm btn-outline-info link-client-btn" data-client-id="${match.id}">
                                   <i class="mdi mdi-star-outline me-1"></i>Confirm this Client
                               </button>`
                        }
                        <a href="{{ url('ecom-clients') }}?highlight=${match.id}" class="btn btn-sm btn-outline-secondary" target="_blank">
                            <i class="mdi mdi-open-in-new me-1"></i>View
                        </a>
                    </div>
                </div>
            `;
        });

        $container.html(html);

        // Attach event handlers
        $container.find('.link-client-btn').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const clientId = $(this).data('client-id');
            linkClient(clientId);
        });

        $container.find('.unlink-client-btn').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            unlinkClient();
        });
    }

    // Helper function to escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Load matches on page load (delayed to not block initial render)
    setTimeout(function() {
        loadPotentialLogins();
        loadPotentialClients();
    }, 300);
});
</script>
@endsection
