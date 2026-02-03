@extends('layouts.master')

@section('title') Edit Lead @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/select2/css/select2.min.css') }}" rel="stylesheet" type="text/css" />

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
    .priority-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        border: 2px solid transparent;
        transition: all 0.2s ease;
    }
    .priority-badge:hover {
        transform: scale(1.05);
    }
    .priority-badge.selected {
        border-color: currentColor;
        box-shadow: 0 0 0 2px rgba(0,0,0,0.1);
    }
    .social-input-group {
        position: relative;
    }
    .social-input-group .social-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 18px;
        z-index: 4;
    }
    .social-input-group input {
        padding-left: 40px;
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
    .lead-status-display {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 0.5rem;
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
</style>
@endsection

@section('content')

    @component('components.breadcrumb')
        @slot('li_1') CRM @endslot
        @slot('li_2') <a href="{{ route('crm-leads') }}">Leads</a> @endslot
        @slot('title') Edit Lead @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bx bx-error-circle me-2"></i>
            <strong>Please fix the following errors:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('crm-leads.update') }}" id="leadForm">
        @csrf
        @method('PUT')
        <input type="hidden" name="id" value="{{ $lead->id }}">

        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
                <!-- Lead Header Card -->
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h4 class="mb-1 text-dark">{{ $lead->fullName }}</h4>
                                @if($lead->companyName)
                                    <p class="text-secondary mb-0">{{ $lead->companyName }} {{ $lead->jobTitle ? '- ' . $lead->jobTitle : '' }}</p>
                                @endif
                            </div>
                            <div class="text-end">
                                <span class="badge bg-{{ $lead->status_color }} fs-6 mb-1">{{ $lead->status_label }}</span>
                                <div class="small text-secondary">
                                    Created {{ $lead->created_at->format('M d, Y') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Basic Information -->
                <div class="card card-section">
                    <div class="card-body">
                        <div class="section-header">
                            <i class="mdi mdi-account me-2"></i>Basic Information
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="firstName" class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('firstName') is-invalid @enderror"
                                       id="firstName" name="firstName" value="{{ old('firstName', $lead->firstName) }}" required>
                                @error('firstName')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="middleName" class="form-label">Middle Name</label>
                                <input type="text" class="form-control" id="middleName" name="middleName"
                                       value="{{ old('middleName', $lead->middleName) }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="lastName" class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('lastName') is-invalid @enderror"
                                       id="lastName" name="lastName" value="{{ old('lastName', $lead->lastName) }}" required>
                                @error('lastName')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror"
                                       id="email" name="email" value="{{ old('email', $lead->email) }}" placeholder="lead@example.com">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                       id="phone" name="phone" value="{{ old('phone', $lead->phone) }}" placeholder="09xxxxxxxxx">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="alternatePhone" class="form-label">Alternate Phone</label>
                                <input type="text" class="form-control" id="alternatePhone" name="alternatePhone"
                                       value="{{ old('alternatePhone', $lead->alternatePhone) }}" placeholder="Optional">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Company Information -->
                <div class="card card-section">
                    <div class="card-body">
                        <div class="section-header">
                            <i class="mdi mdi-domain me-2"></i>Company Information
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="companyName" class="form-label">Company Name</label>
                                <input type="text" class="form-control" id="companyName" name="companyName"
                                       value="{{ old('companyName', $lead->companyName) }}" placeholder="Company or business name">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="jobTitle" class="form-label">Job Title</label>
                                <input type="text" class="form-control" id="jobTitle" name="jobTitle"
                                       value="{{ old('jobTitle', $lead->jobTitle) }}" placeholder="Position">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="department" class="form-label">Department</label>
                                <input type="text" class="form-control" id="department" name="department"
                                       value="{{ old('department', $lead->department) }}" placeholder="Department">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="industry" class="form-label">Industry</label>
                                <input type="text" class="form-control" id="industry" name="industry"
                                       value="{{ old('industry', $lead->industry) }}" placeholder="e.g., Technology, Healthcare">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="companySize" class="form-label">Company Size</label>
                                <select class="form-select" id="companySize" name="companySize">
                                    <option value="">Select size...</option>
                                    @foreach(\App\Models\CrmLead::COMPANY_SIZE_OPTIONS as $value => $label)
                                        <option value="{{ $value }}" {{ old('companySize', $lead->companySize) == $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="website" class="form-label">Website</label>
                                <input type="url" class="form-control" id="website" name="website"
                                       value="{{ old('website', $lead->website) }}" placeholder="https://example.com">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Address Information -->
                <div class="card card-section">
                    <div class="card-body">
                        <div class="section-header">
                            <i class="mdi mdi-map-marker me-2"></i>Address Information
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="province" class="form-label">Province</label>
                                <input type="text" class="form-control" id="province" name="province"
                                       value="{{ old('province', $lead->province) }}" placeholder="Province">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="municipality" class="form-label">City/Municipality</label>
                                <input type="text" class="form-control" id="municipality" name="municipality"
                                       value="{{ old('municipality', $lead->municipality) }}" placeholder="City or Municipality">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="barangay" class="form-label">Barangay</label>
                                <input type="text" class="form-control" id="barangay" name="barangay"
                                       value="{{ old('barangay', $lead->barangay) }}" placeholder="Barangay">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="streetAddress" class="form-label">Street Address</label>
                                <input type="text" class="form-control" id="streetAddress" name="streetAddress"
                                       value="{{ old('streetAddress', $lead->streetAddress) }}" placeholder="House/Building, Street">
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="zipCode" class="form-label">Zip Code</label>
                                <input type="text" class="form-control" id="zipCode" name="zipCode"
                                       value="{{ old('zipCode', $lead->zipCode) }}" placeholder="Zip">
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="country" class="form-label">Country</label>
                                <input type="text" class="form-control" id="country" name="country"
                                       value="{{ old('country', $lead->country) }}">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Social Media -->
                <div class="card card-section">
                    <div class="card-body">
                        <div class="section-header">
                            <i class="mdi mdi-share-variant me-2"></i>Social Media
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Facebook</label>
                                <div class="social-input-group">
                                    <i class="mdi mdi-facebook social-icon" style="color: #1877F2;"></i>
                                    <input type="url" class="form-control" name="facebookUrl"
                                           value="{{ old('facebookUrl', $lead->facebookUrl) }}" placeholder="https://facebook.com/username">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Instagram</label>
                                <div class="social-input-group">
                                    <i class="mdi mdi-instagram social-icon" style="color: #E4405F;"></i>
                                    <input type="url" class="form-control" name="instagramUrl"
                                           value="{{ old('instagramUrl', $lead->instagramUrl) }}" placeholder="https://instagram.com/username">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">LinkedIn</label>
                                <div class="social-input-group">
                                    <i class="mdi mdi-linkedin social-icon" style="color: #0A66C2;"></i>
                                    <input type="url" class="form-control" name="linkedinUrl"
                                           value="{{ old('linkedinUrl', $lead->linkedinUrl) }}" placeholder="https://linkedin.com/in/username">
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Twitter/X</label>
                                <div class="social-input-group">
                                    <i class="mdi mdi-twitter social-icon" style="color: #1DA1F2;"></i>
                                    <input type="url" class="form-control" name="twitterUrl"
                                           value="{{ old('twitterUrl', $lead->twitterUrl) }}" placeholder="https://twitter.com/username">
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">TikTok</label>
                                <div class="social-input-group">
                                    <i class="mdi mdi-music-note social-icon" style="color: #000000;"></i>
                                    <input type="url" class="form-control" name="tiktokUrl"
                                           value="{{ old('tiktokUrl', $lead->tiktokUrl) }}" placeholder="https://tiktok.com/@username">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Viber</label>
                                <div class="social-input-group">
                                    <i class="mdi mdi-phone social-icon" style="color: #7360F2;"></i>
                                    <input type="text" class="form-control" name="viberNumber"
                                           value="{{ old('viberNumber', $lead->viberNumber) }}" placeholder="09xxxxxxxxx">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">WhatsApp</label>
                                <div class="social-input-group">
                                    <i class="mdi mdi-whatsapp social-icon" style="color: #25D366;"></i>
                                    <input type="text" class="form-control" name="whatsappNumber"
                                           value="{{ old('whatsappNumber', $lead->whatsappNumber) }}" placeholder="+63 9xxxxxxxxx">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="card card-section">
                    <div class="card-body">
                        <div class="section-header">
                            <i class="mdi mdi-note-text me-2"></i>Additional Notes
                        </div>

                        <div class="mb-0">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"
                                      placeholder="Any additional notes about this lead...">{{ old('notes', $lead->notes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- Status & Source -->
                <div class="card card-section">
                    <div class="card-body">
                        <div class="section-header">
                            <i class="mdi mdi-cog me-2"></i>Lead Settings
                        </div>

                        <div class="mb-3">
                            <label for="leadStatus" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select @error('leadStatus') is-invalid @enderror" id="leadStatus" name="leadStatus" required>
                                @foreach(\App\Models\CrmLead::STATUS_OPTIONS as $value => $option)
                                    <option value="{{ $value }}" {{ old('leadStatus', $lead->leadStatus) == $value ? 'selected' : '' }}>
                                        {{ $option['label'] }}
                                    </option>
                                @endforeach
                            </select>
                            @error('leadStatus')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Priority</label>
                            <div class="d-flex flex-wrap gap-2">
                                <input type="hidden" name="leadPriority" id="leadPriority" value="{{ old('leadPriority', $lead->leadPriority) }}">
                                <span class="priority-badge bg-secondary text-white {{ old('leadPriority', $lead->leadPriority) == 'low' ? 'selected' : '' }}"
                                      data-priority="low" role="button">
                                    <i class="mdi mdi-chevron-down"></i> Low
                                </span>
                                <span class="priority-badge bg-info text-white {{ old('leadPriority', $lead->leadPriority) == 'medium' ? 'selected' : '' }}"
                                      data-priority="medium" role="button">
                                    <i class="mdi mdi-minus"></i> Medium
                                </span>
                                <span class="priority-badge bg-warning text-dark {{ old('leadPriority', $lead->leadPriority) == 'high' ? 'selected' : '' }}"
                                      data-priority="high" role="button">
                                    <i class="mdi mdi-chevron-up"></i> High
                                </span>
                                <span class="priority-badge bg-danger text-white {{ old('leadPriority', $lead->leadPriority) == 'urgent' ? 'selected' : '' }}"
                                      data-priority="urgent" role="button">
                                    <i class="mdi mdi-chevron-double-up"></i> Urgent
                                </span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="leadSourceId" class="form-label">Lead Source</label>
                            <select class="form-select" id="leadSourceId" name="leadSourceId">
                                <option value="">Select source...</option>
                                @foreach($sources as $source)
                                    <option value="{{ $source->id }}" {{ old('leadSourceId', $lead->leadSourceId) == $source->id ? 'selected' : '' }}>
                                        {{ $source->sourceName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3" id="otherSourceGroup" style="{{ (old('leadSourceId', $lead->leadSourceId) && $sources->where('id', old('leadSourceId', $lead->leadSourceId))->first()?->sourceName == 'Other') ? '' : 'display: none;' }}">
                            <label for="leadSourceOther" class="form-label">Other Source</label>
                            <input type="text" class="form-control" id="leadSourceOther" name="leadSourceOther"
                                   value="{{ old('leadSourceOther', $lead->leadSourceOther) }}" placeholder="Specify source">
                        </div>

                        <div class="mb-0">
                            <label for="referredBy" class="form-label">Referred By</label>
                            <input type="text" class="form-control" id="referredBy" name="referredBy"
                                   value="{{ old('referredBy', $lead->referredBy) }}" placeholder="Name of referrer">
                        </div>
                    </div>
                </div>

                <!-- Store Targets -->
                @if($stores->count() > 0)
                <div class="card card-section">
                    <div class="card-body">
                        <div class="section-header">
                            <i class="mdi mdi-store me-2"></i>Store Targets
                        </div>

                        <p class="text-secondary small mb-3">Select the stores this lead is interested in:</p>

                        @php
                            $selectedStoreIds = old('store_targets', $lead->targetStores->pluck('id')->toArray());
                        @endphp

                        <div class="store-targets-list">
                            @foreach($stores as $store)
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox"
                                       name="store_targets[]" value="{{ $store->id }}"
                                       id="store_{{ $store->id }}"
                                       {{ in_array($store->id, $selectedStoreIds) ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="store_{{ $store->id }}">
                                    @if($store->storeLogo)
                                        <img src="{{ asset($store->storeLogo) }}" alt="{{ $store->storeName }}"
                                             style="width: 20px; height: 20px; object-fit: contain; margin-right: 5px;">
                                    @else
                                        <i class="mdi mdi-store text-primary me-1"></i>
                                    @endif
                                    {{ $store->storeName }}
                                </label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                <!-- Custom Data -->
                <div class="card card-section">
                    <div class="card-body">
                        <div class="section-header" style="background: linear-gradient(135deg, #f1b44c 0%, #e09b30 100%);">
                            <i class="mdi mdi-database me-2"></i>Custom Data
                            <span class="badge bg-white text-warning ms-auto" id="customDataCount">{{ $lead->customData->count() }}</span>
                        </div>

                        <div id="customDataList">
                            @forelse($lead->customData as $data)
                            <div class="custom-data-item d-flex align-items-center mb-2 p-2 border rounded" data-id="{{ $data->id }}">
                                <div class="flex-grow-1">
                                    <strong class="text-dark small d-block">{{ $data->fieldName }}</strong>
                                    <span class="text-secondary small">{{ $data->fieldValue ?: '-' }}</span>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-soft-primary edit-custom-data"
                                            data-id="{{ $data->id }}" data-name="{{ $data->fieldName }}" data-value="{{ $data->fieldValue }}">
                                        <i class="bx bx-edit-alt"></i>
                                    </button>
                                    <button type="button" class="btn btn-soft-danger delete-custom-data" data-id="{{ $data->id }}">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </div>
                            </div>
                            @empty
                            <div class="text-center py-2" id="noCustomData">
                                <small class="text-secondary">No custom data added yet.</small>
                            </div>
                            @endforelse
                        </div>

                        <button type="button" class="btn btn-soft-warning btn-sm w-100 mt-2" id="addCustomDataBtn">
                            <i class="mdi mdi-plus me-1"></i> Add Custom Field
                        </button>
                    </div>
                </div>

                <!-- Loss Reason (shown if status is lost) -->
                @if($lead->leadStatus == 'lost')
                <div class="card card-section">
                    <div class="card-body">
                        <div class="section-header bg-danger">
                            <i class="mdi mdi-close-circle me-2"></i>Loss Information
                        </div>

                        <div class="mb-3">
                            <label for="lossReason" class="form-label">Loss Reason</label>
                            <input type="text" class="form-control" id="lossReason" name="lossReason"
                                   value="{{ old('lossReason', $lead->lossReason) }}" placeholder="Why was this lead lost?">
                        </div>

                        <div class="mb-0">
                            <label for="lossDetails" class="form-label">Loss Details</label>
                            <textarea class="form-control" id="lossDetails" name="lossDetails" rows="2"
                                      placeholder="Additional details...">{{ old('lossDetails', $lead->lossDetails) }}</textarea>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Activity Timeline -->
                <div class="card card-section">
                    <div class="card-body">
                        <div class="section-header">
                            <i class="mdi mdi-timeline-text me-2"></i>Recent Activity
                        </div>

                        <div id="activityTimeline">
                            @forelse($lead->activities->take(5) as $activity)
                                <div class="activity-item activity-{{ $activity->activityType }}">
                                    <div class="d-flex align-items-start">
                                        <div class="activity-icon bg-{{ $activity->type_color }} text-white me-2">
                                            <i class="{{ $activity->type_icon }}"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between">
                                                <strong class="text-dark small">{{ $activity->type_label }}</strong>
                                                <small class="text-secondary">{{ $activity->activityDate->diffForHumans() }}</small>
                                            </div>
                                            <p class="mb-0 small text-secondary">{{ Str::limit($activity->activityDescription, 100) }}</p>
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

                        <button type="button" class="btn btn-soft-primary btn-sm w-100 mt-2" id="addActivityBtn">
                            <i class="mdi mdi-plus me-1"></i> Log Activity
                        </button>
                    </div>
                </div>

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
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i> Update Lead
                            </button>
                            <a href="{{ route('crm-leads') }}" class="btn btn-secondary">
                                <i class="bx bx-arrow-back me-1"></i> Back to Leads
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Add Activity Modal -->
    <div class="modal fade" id="activityModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="mdi mdi-note-plus me-2"></i>Log Activity</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Activity Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="modalActivityType">
                            <option value="call_outbound">Outbound Call</option>
                            <option value="call_inbound">Inbound Call</option>
                            <option value="email_sent">Email Sent</option>
                            <option value="email_received">Email Received</option>
                            <option value="meeting">Meeting</option>
                            <option value="note" selected>Note</option>
                            <option value="follow_up">Follow-up</option>
                            <option value="proposal_sent">Proposal Sent</option>
                            <option value="document_sent">Document Sent</option>
                            <option value="social_media">Social Media</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <input type="text" class="form-control" id="modalActivitySubject" placeholder="Brief subject (optional)">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="modalActivityDescription" rows="3" placeholder="Describe the activity..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Duration (minutes)</label>
                        <input type="number" class="form-control" id="modalActivityDuration" placeholder="For calls/meetings">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveActivityBtn">
                        <i class="bx bx-save me-1"></i> Save Activity
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom Data Modal -->
    <div class="modal fade" id="customDataModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="mdi mdi-database-plus me-2 text-warning"></i><span id="customDataModalTitle">Add Custom Field</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="customDataId">
                    <div class="mb-3">
                        <label class="form-label">Field Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="customDataName" placeholder="e.g., Budget, Timeline, Source Details">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Field Value</label>
                        <textarea class="form-control" id="customDataValue" rows="3" placeholder="Enter the value..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" id="saveCustomDataBtn">
                        <i class="bx bx-save me-1"></i> Save
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/select2/js/select2.min.js') }}"></script>

<script>
$(document).ready(function() {
    const leadId = {{ $lead->id }};
    let activityModal = new bootstrap.Modal(document.getElementById('activityModal'));
    let customDataModal = new bootstrap.Modal(document.getElementById('customDataModal'));

    // Track currently linked items
    let linkedLoginId = {{ $lead->linkedStoreLoginId ?? 'null' }};
    let linkedClientId = {{ $lead->convertedToClientId ?? 'null' }};

    // Priority badge selection
    $('.priority-badge').on('click', function() {
        $('.priority-badge').removeClass('selected');
        $(this).addClass('selected');
        $('#leadPriority').val($(this).data('priority'));
    });

    // Show/hide other source field
    $('#leadSourceId').on('change', function() {
        const selectedText = $(this).find('option:selected').text().toLowerCase();
        if (selectedText.includes('other')) {
            $('#otherSourceGroup').show();
        } else {
            $('#otherSourceGroup').hide();
            $('#leadSourceOther').val('');
        }
    });

    // Add Activity button
    $('#addActivityBtn').on('click', function() {
        $('#modalActivityType').val('note');
        $('#modalActivitySubject').val('');
        $('#modalActivityDescription').val('');
        $('#modalActivityDuration').val('');
        activityModal.show();
    });

    // Save Activity
    $('#saveActivityBtn').on('click', function() {
        const activityType = $('#modalActivityType').val();
        const description = $('#modalActivityDescription').val().trim();
        const subject = $('#modalActivitySubject').val().trim();
        const duration = $('#modalActivityDuration').val();

        if (!description) {
            toastr.error('Please enter a description', 'Validation Error');
            return;
        }

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Saving...');

        $.ajax({
            url: '{{ route("crm-leads.add-activity") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                lead_id: leadId,
                activity_type: activityType,
                description: description,
                subject: subject,
                duration: duration
            },
            success: function(response) {
                if (response.success) {
                    activityModal.hide();
                    toastr.success(response.message, 'Success!');
                    // Reload page to show new activity
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toastr.error(response.message, 'Error!');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to save activity', 'Error!');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save Activity');
            }
        });
    });

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
    }, 500);

    // ============================
    // CUSTOM DATA MANAGEMENT
    // ============================

    // Add Custom Data button
    $('#addCustomDataBtn').on('click', function() {
        $('#customDataModalTitle').text('Add Custom Field');
        $('#customDataId').val('');
        $('#customDataName').val('');
        $('#customDataValue').val('');
        customDataModal.show();
    });

    // Edit Custom Data button
    $(document).on('click', '.edit-custom-data', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const value = $(this).data('value');

        $('#customDataModalTitle').text('Edit Custom Field');
        $('#customDataId').val(id);
        $('#customDataName').val(name);
        $('#customDataValue').val(value);
        customDataModal.show();
    });

    // Save Custom Data
    $('#saveCustomDataBtn').on('click', function() {
        const id = $('#customDataId').val();
        const name = $('#customDataName').val().trim();
        const value = $('#customDataValue').val().trim();

        if (!name) {
            toastr.error('Please enter a field name', 'Validation Error');
            return;
        }

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Saving...');

        const isEdit = id !== '';
        const url = isEdit ? '{{ route("crm-leads.update-custom-data") }}' : '{{ route("crm-leads.add-custom-data") }}';
        const method = isEdit ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            type: method,
            data: {
                _token: '{{ csrf_token() }}',
                id: id,
                lead_id: leadId,
                field_name: name,
                field_value: value
            },
            success: function(response) {
                if (response.success) {
                    customDataModal.hide();
                    toastr.success(response.message, 'Success!');

                    if (isEdit) {
                        // Update existing item
                        const $item = $(`.custom-data-item[data-id="${id}"]`);
                        $item.find('strong').text(name);
                        $item.find('span.text-secondary').text(value || '-');
                        $item.find('.edit-custom-data').data('name', name).data('value', value);
                    } else {
                        // Add new item
                        $('#noCustomData').remove();
                        const newItem = `
                            <div class="custom-data-item d-flex align-items-center mb-2 p-2 border rounded" data-id="${response.data.id}">
                                <div class="flex-grow-1">
                                    <strong class="text-dark small d-block">${escapeHtml(name)}</strong>
                                    <span class="text-secondary small">${escapeHtml(value) || '-'}</span>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-soft-primary edit-custom-data"
                                            data-id="${response.data.id}" data-name="${escapeHtml(name)}" data-value="${escapeHtml(value)}">
                                        <i class="bx bx-edit-alt"></i>
                                    </button>
                                    <button type="button" class="btn btn-soft-danger delete-custom-data" data-id="${response.data.id}">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                        $('#customDataList').append(newItem);
                        updateCustomDataCount();
                    }
                } else {
                    toastr.error(response.message, 'Error!');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to save', 'Error!');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save');
            }
        });
    });

    // Delete Custom Data
    $(document).on('click', '.delete-custom-data', function() {
        const id = $(this).data('id');
        const $item = $(this).closest('.custom-data-item');

        if (!confirm('Are you sure you want to delete this custom field?')) {
            return;
        }

        $.ajax({
            url: '{{ route("crm-leads.delete-custom-data") }}',
            type: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}',
                id: id
            },
            success: function(response) {
                if (response.success) {
                    $item.fadeOut(300, function() {
                        $(this).remove();
                        updateCustomDataCount();
                        if ($('#customDataList .custom-data-item').length === 0) {
                            $('#customDataList').html('<div class="text-center py-2" id="noCustomData"><small class="text-secondary">No custom data added yet.</small></div>');
                        }
                    });
                    toastr.success(response.message, 'Success!');
                } else {
                    toastr.error(response.message, 'Error!');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to delete', 'Error!');
            }
        });
    });

    // Update custom data count badge
    function updateCustomDataCount() {
        const count = $('#customDataList .custom-data-item').length;
        $('#customDataCount').text(count);
    }
});
</script>
@endsection
