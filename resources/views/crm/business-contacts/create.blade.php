@extends('layouts.master')

@section('title') Add Business Contact @endsection

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
    .type-option {
        display: inline-block;
        padding: 8px 16px;
        border-radius: 20px;
        cursor: pointer;
        transition: all 0.2s ease;
        border: 2px solid transparent;
        margin-right: 8px;
        margin-bottom: 8px;
    }
    .type-option:hover {
        transform: scale(1.05);
    }
    .type-option.selected {
        border-color: currentColor;
        box-shadow: 0 0 0 2px rgba(0,0,0,0.1);
    }
    .strength-option {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 6px 12px;
        border-radius: 16px;
        cursor: pointer;
        transition: all 0.2s ease;
        border: 2px solid transparent;
        margin-right: 6px;
    }
    .strength-option:hover {
        transform: scale(1.05);
    }
    .strength-option.selected {
        border-color: currentColor;
        box-shadow: 0 0 0 2px rgba(0,0,0,0.1);
    }
</style>
@endsection

@section('content')

    @component('components.breadcrumb')
        @slot('li_1') CRM @endslot
        @slot('li_2') <a href="{{ route('crm-business-contacts') }}">Business Contacts</a> @endslot
        @slot('title') Add Contact @endslot
    @endcomponent

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

    <form method="POST" action="{{ route('crm-business-contacts.store') }}" id="contactForm">
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
                                <label for="firstName" class="form-label">First Name</label>
                                <input type="text" class="form-control @error('firstName') is-invalid @enderror"
                                       id="firstName" name="firstName" value="{{ old('firstName') }}">
                                @error('firstName')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="middleName" class="form-label">Middle Name</label>
                                <input type="text" class="form-control" id="middleName" name="middleName"
                                       value="{{ old('middleName') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="lastName" class="form-label">Last Name</label>
                                <input type="text" class="form-control @error('lastName') is-invalid @enderror"
                                       id="lastName" name="lastName" value="{{ old('lastName') }}">
                                @error('lastName')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <small class="text-secondary d-block mb-3"><i class="mdi mdi-information-outline me-1"></i>Provide either a personal name or company name below.</small>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="nickname" class="form-label">Nickname</label>
                                <input type="text" class="form-control" id="nickname" name="nickname"
                                       value="{{ old('nickname') }}" placeholder="Optional">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror"
                                       id="email" name="email" value="{{ old('email') }}" placeholder="contact@example.com">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="text" class="form-control" id="phone" name="phone"
                                       value="{{ old('phone') }}" placeholder="09xxxxxxxxx">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
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
                            <i class="mdi mdi-domain me-2"></i>Company Information
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
                                    @foreach(\App\Models\CrmBusinessContact::COMPANY_SIZE_OPTIONS as $value => $label)
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
                            <i class="mdi mdi-share-variant me-2"></i>Social Media
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
                            <i class="mdi mdi-note-text me-2"></i>Additional Information
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="howWeMet" class="form-label">How We Met</label>
                                <input type="text" class="form-control" id="howWeMet" name="howWeMet"
                                       value="{{ old('howWeMet') }}" placeholder="e.g., Conference, Referral, LinkedIn">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="referredBy" class="form-label">Referred By</label>
                                <input type="text" class="form-control" id="referredBy" name="referredBy"
                                       value="{{ old('referredBy') }}" placeholder="Name of referrer">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="firstContactDate" class="form-label">First Contact Date</label>
                                <input type="date" class="form-control" id="firstContactDate" name="firstContactDate"
                                       value="{{ old('firstContactDate', date('Y-m-d')) }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="tags" class="form-label">Tags</label>
                                <input type="text" class="form-control" id="tags" name="tags"
                                       value="{{ old('tags') }}" placeholder="Comma-separated tags (e.g., VIP, Local, Tech)">
                            </div>
                        </div>

                        <div class="mb-0">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"
                                      placeholder="Any additional notes about this contact...">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- Contact Type -->
                <div class="card card-section">
                    <div class="card-body">
                        <div class="section-header">
                            <i class="mdi mdi-tag me-2"></i>Contact Type
                        </div>

                        <input type="hidden" name="contactType" id="contactType" value="{{ old('contactType', 'general') }}">
                        <div class="d-flex flex-wrap">
                            @foreach(\App\Models\CrmBusinessContact::CONTACT_TYPE_OPTIONS as $value => $option)
                                <span class="type-option bg-{{ $option['color'] }} text-white {{ old('contactType', 'general') == $value ? 'selected' : '' }}"
                                      data-type="{{ $value }}" role="button">
                                    <i class="mdi {{ $option['icon'] }} me-1"></i>{{ $option['label'] }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Status -->
                <div class="card card-section">
                    <div class="card-body">
                        <div class="section-header" style="background: linear-gradient(135deg, #34c38f 0%, #2ca67a 100%);">
                            <i class="mdi mdi-check-circle me-2"></i>Status
                        </div>

                        <select class="form-select" name="contactStatus">
                            @foreach(\App\Models\CrmBusinessContact::STATUS_OPTIONS as $value => $option)
                                <option value="{{ $value }}" {{ old('contactStatus', 'active') == $value ? 'selected' : '' }}>
                                    {{ $option['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Relationship Strength -->
                <div class="card card-section">
                    <div class="card-body">
                        <div class="section-header" style="background: linear-gradient(135deg, #f1b44c 0%, #e09b30 100%);">
                            <i class="mdi mdi-star me-2"></i>Relationship Strength
                        </div>

                        <input type="hidden" name="relationshipStrength" id="relationshipStrength" value="{{ old('relationshipStrength', 'neutral') }}">
                        <div class="d-flex flex-wrap">
                            @foreach(\App\Models\CrmBusinessContact::RELATIONSHIP_STRENGTH_OPTIONS as $value => $option)
                                <span class="strength-option bg-{{ $option['color'] }} bg-opacity-10 text-{{ $option['color'] }} {{ old('relationshipStrength', 'neutral') == $value ? 'selected' : '' }}"
                                      data-strength="{{ $value }}" role="button">
                                    <i class="mdi {{ $option['icon'] }}"></i>{{ $option['label'] }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Store Associations -->
                @if($stores->count() > 0)
                <div class="card card-section">
                    <div class="card-body">
                        <div class="section-header" style="background: linear-gradient(135deg, #50a5f1 0%, #3d8bd9 100%);">
                            <i class="mdi mdi-store me-2"></i>Associated Stores
                        </div>

                        <p class="text-secondary small mb-3">Select stores this contact is associated with:</p>

                        @foreach($stores as $store)
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox"
                                   name="store_associations[]" value="{{ $store->id }}"
                                   id="store_{{ $store->id }}"
                                   {{ in_array($store->id, old('store_associations', [])) ? 'checked' : '' }}>
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
                @endif

                <!-- Actions -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i> Save Contact
                            </button>
                            <a href="{{ route('crm-business-contacts') }}" class="btn btn-secondary">
                                <i class="bx bx-arrow-back me-1"></i> Cancel
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

<script>
$(document).ready(function() {
    // Contact type selection
    $('.type-option').on('click', function() {
        $('.type-option').removeClass('selected');
        $(this).addClass('selected');
        $('#contactType').val($(this).data('type'));
    });

    // Relationship strength selection
    $('.strength-option').on('click', function() {
        $('.strength-option').removeClass('selected');
        $(this).addClass('selected');
        $('#relationshipStrength').val($(this).data('strength'));
    });
});
</script>
@endsection
