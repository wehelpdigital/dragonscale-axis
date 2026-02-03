@extends('layouts.master')

@section('title') Add Lead @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/select2/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/flatpickr/flatpickr.min.css') }}" rel="stylesheet" type="text/css" />

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
    .source-option {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .source-icon {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        color: #fff;
    }
    .select2-container--default .select2-selection--single {
        height: 38px;
        border-color: #ced4da;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
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
</style>
@endsection

@section('content')

    @component('components.breadcrumb')
        @slot('li_1') CRM @endslot
        @slot('li_2') <a href="{{ route('crm-leads') }}">Leads</a> @endslot
        @slot('title') Add Lead @endslot
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

    <form method="POST" action="{{ route('crm-leads.store') }}" id="leadForm">
        @csrf

        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
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
                                       id="firstName" name="firstName" value="{{ old('firstName') }}" required>
                                @error('firstName')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="middleName" class="form-label">Middle Name</label>
                                <input type="text" class="form-control" id="middleName" name="middleName" value="{{ old('middleName') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="lastName" class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('lastName') is-invalid @enderror"
                                       id="lastName" name="lastName" value="{{ old('lastName') }}" required>
                                @error('lastName')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror"
                                       id="email" name="email" value="{{ old('email') }}" placeholder="lead@example.com">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                       id="phone" name="phone" value="{{ old('phone') }}" placeholder="09xxxxxxxxx">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="alternatePhone" class="form-label">Alternate Phone</label>
                                <input type="text" class="form-control" id="alternatePhone" name="alternatePhone"
                                       value="{{ old('alternatePhone') }}" placeholder="Optional">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Company Information -->
                <div class="card card-section">
                    <div class="card-body">
                        <div class="section-header">
                            <i class="mdi mdi-domain me-2"></i>Company Information (Optional)
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="companyName" class="form-label">Company Name</label>
                                <input type="text" class="form-control" id="companyName" name="companyName"
                                       value="{{ old('companyName') }}" placeholder="Company or business name">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="jobTitle" class="form-label">Job Title</label>
                                <input type="text" class="form-control" id="jobTitle" name="jobTitle"
                                       value="{{ old('jobTitle') }}" placeholder="Position">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="department" class="form-label">Department</label>
                                <input type="text" class="form-control" id="department" name="department"
                                       value="{{ old('department') }}" placeholder="Department">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="industry" class="form-label">Industry</label>
                                <input type="text" class="form-control" id="industry" name="industry"
                                       value="{{ old('industry') }}" placeholder="e.g., Technology, Healthcare">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="companySize" class="form-label">Company Size</label>
                                <select class="form-select" id="companySize" name="companySize">
                                    <option value="">Select size...</option>
                                    @foreach(\App\Models\CrmLead::COMPANY_SIZE_OPTIONS as $value => $label)
                                        <option value="{{ $value }}" {{ old('companySize') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="website" class="form-label">Website</label>
                                <input type="url" class="form-control" id="website" name="website"
                                       value="{{ old('website') }}" placeholder="https://example.com">
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
                                       value="{{ old('province') }}" placeholder="Province">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="municipality" class="form-label">City/Municipality</label>
                                <input type="text" class="form-control" id="municipality" name="municipality"
                                       value="{{ old('municipality') }}" placeholder="City or Municipality">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="barangay" class="form-label">Barangay</label>
                                <input type="text" class="form-control" id="barangay" name="barangay"
                                       value="{{ old('barangay') }}" placeholder="Barangay">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="streetAddress" class="form-label">Street Address</label>
                                <input type="text" class="form-control" id="streetAddress" name="streetAddress"
                                       value="{{ old('streetAddress') }}" placeholder="House/Building, Street">
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="zipCode" class="form-label">Zip Code</label>
                                <input type="text" class="form-control" id="zipCode" name="zipCode"
                                       value="{{ old('zipCode') }}" placeholder="Zip">
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="country" class="form-label">Country</label>
                                <input type="text" class="form-control" id="country" name="country"
                                       value="{{ old('country', 'Philippines') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Social Media -->
                <div class="card card-section">
                    <div class="card-body">
                        <div class="section-header">
                            <i class="mdi mdi-share-variant me-2"></i>Social Media (Optional)
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Facebook</label>
                                <div class="social-input-group">
                                    <i class="mdi mdi-facebook social-icon" style="color: #1877F2;"></i>
                                    <input type="url" class="form-control" name="facebookUrl"
                                           value="{{ old('facebookUrl') }}" placeholder="https://facebook.com/username">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Instagram</label>
                                <div class="social-input-group">
                                    <i class="mdi mdi-instagram social-icon" style="color: #E4405F;"></i>
                                    <input type="url" class="form-control" name="instagramUrl"
                                           value="{{ old('instagramUrl') }}" placeholder="https://instagram.com/username">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">LinkedIn</label>
                                <div class="social-input-group">
                                    <i class="mdi mdi-linkedin social-icon" style="color: #0A66C2;"></i>
                                    <input type="url" class="form-control" name="linkedinUrl"
                                           value="{{ old('linkedinUrl') }}" placeholder="https://linkedin.com/in/username">
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Twitter/X</label>
                                <div class="social-input-group">
                                    <i class="mdi mdi-twitter social-icon" style="color: #1DA1F2;"></i>
                                    <input type="url" class="form-control" name="twitterUrl"
                                           value="{{ old('twitterUrl') }}" placeholder="https://twitter.com/username">
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">TikTok</label>
                                <div class="social-input-group">
                                    <i class="mdi mdi-music-note social-icon" style="color: #000000;"></i>
                                    <input type="url" class="form-control" name="tiktokUrl"
                                           value="{{ old('tiktokUrl') }}" placeholder="https://tiktok.com/@username">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Viber</label>
                                <div class="social-input-group">
                                    <i class="mdi mdi-phone social-icon" style="color: #7360F2;"></i>
                                    <input type="text" class="form-control" name="viberNumber"
                                           value="{{ old('viberNumber') }}" placeholder="09xxxxxxxxx">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">WhatsApp</label>
                                <div class="social-input-group">
                                    <i class="mdi mdi-whatsapp social-icon" style="color: #25D366;"></i>
                                    <input type="text" class="form-control" name="whatsappNumber"
                                           value="{{ old('whatsappNumber') }}" placeholder="+63 9xxxxxxxxx">
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
                                      placeholder="Any additional notes about this lead...">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Lead Settings -->
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
                                    <option value="{{ $value }}" {{ old('leadStatus', 'new') == $value ? 'selected' : '' }}>
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
                                <input type="hidden" name="leadPriority" id="leadPriority" value="{{ old('leadPriority', 'medium') }}">
                                <span class="priority-badge bg-secondary text-white {{ old('leadPriority', 'medium') == 'low' ? 'selected' : '' }}"
                                      data-priority="low" role="button">
                                    <i class="mdi mdi-chevron-down"></i> Low
                                </span>
                                <span class="priority-badge bg-info text-white {{ old('leadPriority', 'medium') == 'medium' ? 'selected' : '' }}"
                                      data-priority="medium" role="button">
                                    <i class="mdi mdi-minus"></i> Medium
                                </span>
                                <span class="priority-badge bg-warning text-dark {{ old('leadPriority', 'medium') == 'high' ? 'selected' : '' }}"
                                      data-priority="high" role="button">
                                    <i class="mdi mdi-chevron-up"></i> High
                                </span>
                                <span class="priority-badge bg-danger text-white {{ old('leadPriority', 'medium') == 'urgent' ? 'selected' : '' }}"
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
                                    <option value="{{ $source->id }}" {{ old('leadSourceId') == $source->id ? 'selected' : '' }}>
                                        {{ $source->sourceName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3" id="otherSourceGroup" style="display: none;">
                            <label for="leadSourceOther" class="form-label">Other Source</label>
                            <input type="text" class="form-control" id="leadSourceOther" name="leadSourceOther"
                                   value="{{ old('leadSourceOther') }}" placeholder="Specify source">
                        </div>

                        <div class="mb-0">
                            <label for="referredBy" class="form-label">Referred By</label>
                            <input type="text" class="form-control" id="referredBy" name="referredBy"
                                   value="{{ old('referredBy') }}" placeholder="Name of referrer (if any)">
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

                        <div class="store-targets-list">
                            @foreach($stores as $store)
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox"
                                       name="store_targets[]" value="{{ $store->id }}"
                                       id="store_{{ $store->id }}"
                                       {{ in_array($store->id, old('store_targets', [])) ? 'checked' : '' }}>
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

                <!-- Actions -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bx bx-save me-1"></i> Save Lead
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

@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/select2/js/select2.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/flatpickr/flatpickr.min.js') }}"></script>

<script>
$(document).ready(function() {
    // Initialize flatpickr for date
    flatpickr('.flatpickr-date', {
        dateFormat: 'Y-m-d',
        minDate: 'today'
    });

    // Initialize flatpickr for time
    flatpickr('.flatpickr-time', {
        enableTime: true,
        noCalendar: true,
        dateFormat: 'H:i',
        time_24hr: false
    });

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

    // Check on page load
    const selectedSourceText = $('#leadSourceId option:selected').text().toLowerCase();
    if (selectedSourceText.includes('other')) {
        $('#otherSourceGroup').show();
    }

    // Toastr options
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };
});
</script>
@endsection
