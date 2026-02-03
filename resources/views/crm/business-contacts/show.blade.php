@extends('layouts.master')

@section('title') View Contact - {{ $contact->fullName }} @endsection

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
    .contact-header-card {
        background: linear-gradient(135deg, #556ee6 0%, #3b5de7 100%);
        color: #fff;
    }
    .contact-header-card .text-muted {
        color: rgba(255,255,255,0.7) !important;
    }
    .type-badge {
        font-size: 12px;
        padding: 4px 12px;
        border-radius: 16px;
    }
    .strength-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 12px;
    }
    .tag-badge {
        font-size: 11px;
        padding: 3px 8px;
        border-radius: 4px;
        background: #e8f0fe;
        color: #556ee6;
        margin-right: 4px;
        margin-bottom: 4px;
        display: inline-block;
    }
    .store-badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 8px;
        background: #f3f6f9;
        margin-right: 6px;
        margin-bottom: 6px;
        font-size: 13px;
    }
    .store-badge img {
        width: 18px;
        height: 18px;
        object-fit: contain;
        margin-right: 6px;
    }
</style>
@endsection

@section('content')

    @component('components.breadcrumb')
        @slot('li_1') CRM @endslot
        @slot('li_2') <a href="{{ route('crm-business-contacts') }}">Business Contacts</a> @endslot
        @slot('title') View Contact @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Contact Header Card -->
            <div class="card mb-3 contact-header-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-1 text-white">{{ $contact->display_name }}</h4>
                            @if($contact->companyName)
                                <p class="text-muted mb-0">{{ $contact->companyName }} {{ $contact->jobTitle ? '- ' . $contact->jobTitle : '' }}</p>
                            @endif
                        </div>
                        <div class="text-end">
                            <span class="badge bg-white text-{{ $contact->type_color }} fs-6 mb-1">
                                <i class="{{ $contact->type_icon }} me-1"></i>{{ $contact->type_label }}
                            </span>
                            <div class="small text-muted">
                                Created {{ $contact->created_at->format('M d, Y') }}
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
                            <div class="info-value">{{ $contact->fullName }}</div>
                        </div>
                        @if($contact->nickname)
                        <div class="col-md-6 mb-3">
                            <div class="info-label">Nickname</div>
                            <div class="info-value">{{ $contact->nickname }}</div>
                        </div>
                        @endif
                        <div class="col-md-6 mb-3">
                            <div class="info-label">Email Address</div>
                            <div class="info-value">
                                @if($contact->email)
                                    <a href="mailto:{{ $contact->email }}"><i class="mdi mdi-email me-1"></i>{{ $contact->email }}</a>
                                @else
                                    <span class="text-secondary">-</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="info-label">Phone Number</div>
                            <div class="info-value">
                                @if($contact->phone)
                                    <a href="tel:{{ $contact->phone }}"><i class="mdi mdi-phone me-1"></i>{{ $contact->phone }}</a>
                                @else
                                    <span class="text-secondary">-</span>
                                @endif
                            </div>
                        </div>
                        @if($contact->alternatePhone)
                        <div class="col-md-6 mb-3">
                            <div class="info-label">Alternate Phone</div>
                            <div class="info-value">
                                <a href="tel:{{ $contact->alternatePhone }}">{{ $contact->alternatePhone }}</a>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Company Information -->
            @if($contact->companyName || $contact->jobTitle || $contact->industry)
            <div class="card card-section">
                <div class="card-body">
                    <div class="section-header">
                        <i class="mdi mdi-domain me-2"></i>Company Information
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="info-label">Company Name</div>
                            <div class="info-value">{{ $contact->companyName ?: '-' }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="info-label">Job Title</div>
                            <div class="info-value">{{ $contact->jobTitle ?: '-' }}</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="info-label">Department</div>
                            <div class="info-value">{{ $contact->department ?: '-' }}</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="info-label">Industry</div>
                            <div class="info-value">{{ $contact->industry ?: '-' }}</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="info-label">Company Size</div>
                            <div class="info-value">{{ $contact->companySize ? \App\Models\CrmBusinessContact::COMPANY_SIZE_OPTIONS[$contact->companySize] ?? $contact->companySize : '-' }}</div>
                        </div>
                        @if($contact->website)
                        <div class="col-md-12">
                            <div class="info-label">Website</div>
                            <div class="info-value">
                                <a href="{{ $contact->website }}" target="_blank"><i class="mdi mdi-open-in-new me-1"></i>{{ $contact->website }}</a>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <!-- Address Information -->
            @if($contact->province || $contact->municipality || $contact->streetAddress)
            <div class="card card-section">
                <div class="card-body">
                    <div class="section-header">
                        <i class="mdi mdi-map-marker me-2"></i>Address Information
                    </div>

                    <div class="info-value">
                        <i class="mdi mdi-map-marker-outline me-1 text-secondary"></i>
                        {{ $contact->fullAddress ?: 'No address provided' }}
                    </div>
                </div>
            </div>
            @endif

            <!-- Social Media -->
            @if($contact->hasSocialMedia())
            <div class="card card-section">
                <div class="card-body">
                    <div class="section-header">
                        <i class="mdi mdi-share-variant me-2"></i>Social Media
                    </div>

                    <div class="d-flex flex-wrap">
                        @if($contact->facebookUrl)
                            <a href="{{ $contact->facebookUrl }}" target="_blank" class="social-link" style="background: #1877F220; color: #1877F2;">
                                <i class="mdi mdi-facebook"></i> Facebook
                            </a>
                        @endif
                        @if($contact->instagramUrl)
                            <a href="{{ $contact->instagramUrl }}" target="_blank" class="social-link" style="background: #E4405F20; color: #E4405F;">
                                <i class="mdi mdi-instagram"></i> Instagram
                            </a>
                        @endif
                        @if($contact->linkedinUrl)
                            <a href="{{ $contact->linkedinUrl }}" target="_blank" class="social-link" style="background: #0A66C220; color: #0A66C2;">
                                <i class="mdi mdi-linkedin"></i> LinkedIn
                            </a>
                        @endif
                        @if($contact->twitterUrl)
                            <a href="{{ $contact->twitterUrl }}" target="_blank" class="social-link" style="background: #1DA1F220; color: #1DA1F2;">
                                <i class="mdi mdi-twitter"></i> Twitter/X
                            </a>
                        @endif
                        @if($contact->tiktokUrl)
                            <a href="{{ $contact->tiktokUrl }}" target="_blank" class="social-link" style="background: #00000020; color: #000000;">
                                <i class="mdi mdi-music-note"></i> TikTok
                            </a>
                        @endif
                        @if($contact->viberNumber)
                            <a href="viber://chat?number={{ preg_replace('/[^0-9]/', '', $contact->viberNumber) }}" class="social-link" style="background: #7360F220; color: #7360F2;">
                                <i class="mdi mdi-phone"></i> {{ $contact->viberNumber }}
                            </a>
                        @endif
                        @if($contact->whatsappNumber)
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $contact->whatsappNumber) }}" target="_blank" class="social-link" style="background: #25D36620; color: #25D366;">
                                <i class="mdi mdi-whatsapp"></i> {{ $contact->whatsappNumber }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <!-- Relationship Details -->
            @if($contact->howWeMet || $contact->referredBy || $contact->firstContactDate)
            <div class="card card-section">
                <div class="card-body">
                    <div class="section-header" style="background: linear-gradient(135deg, #f1b44c 0%, #e09b30 100%);">
                        <i class="mdi mdi-handshake me-2"></i>Relationship Details
                    </div>

                    <div class="row">
                        @if($contact->howWeMet)
                        <div class="col-md-6 mb-3">
                            <div class="info-label">How We Met</div>
                            <div class="info-value">{{ $contact->howWeMet }}</div>
                        </div>
                        @endif
                        @if($contact->referredBy)
                        <div class="col-md-6 mb-3">
                            <div class="info-label">Referred By</div>
                            <div class="info-value">{{ $contact->referredBy }}</div>
                        </div>
                        @endif
                        @if($contact->firstContactDate)
                        <div class="col-md-6 mb-3">
                            <div class="info-label">First Contact</div>
                            <div class="info-value">{{ $contact->firstContactDate->format('M d, Y') }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <!-- Notes -->
            @if($contact->notes)
            <div class="card card-section">
                <div class="card-body">
                    <div class="section-header">
                        <i class="mdi mdi-note-text me-2"></i>Notes
                    </div>

                    <p class="text-dark mb-0" style="white-space: pre-wrap;">{{ $contact->notes }}</p>
                </div>
            </div>
            @endif
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Contact Details -->
            <div class="card card-section">
                <div class="card-body">
                    <div class="section-header">
                        <i class="mdi mdi-cog me-2"></i>Contact Details
                    </div>

                    <div class="mb-3">
                        <div class="info-label">Contact Type</div>
                        <div class="info-value">
                            <span class="type-badge bg-{{ $contact->type_color }} text-white">
                                <i class="{{ $contact->type_icon }} me-1"></i>{{ $contact->type_label }}
                            </span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="info-label">Status</div>
                        <div class="info-value">
                            <span class="badge bg-{{ $contact->status_color }}">
                                {{ $contact->status_label }}
                            </span>
                        </div>
                    </div>

                    <div class="mb-0">
                        <div class="info-label">Relationship Strength</div>
                        <div class="info-value">
                            <span class="strength-badge bg-{{ $contact->relationship_color }} bg-opacity-10 text-{{ $contact->relationship_color }}">
                                <i class="mdi {{ \App\Models\CrmBusinessContact::RELATIONSHIP_STRENGTH_OPTIONS[$contact->relationshipStrength]['icon'] ?? 'mdi-star-outline' }}"></i>
                                {{ $contact->relationship_label }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tags -->
            @if($contact->tags_array && count($contact->tags_array) > 0)
            <div class="card card-section">
                <div class="card-body">
                    <div class="section-header" style="background: linear-gradient(135deg, #50a5f1 0%, #3d8bd9 100%);">
                        <i class="mdi mdi-tag-multiple me-2"></i>Tags
                    </div>

                    <div class="d-flex flex-wrap">
                        @foreach($contact->tags_array as $tag)
                            <span class="tag-badge">{{ $tag }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Associated Stores -->
            @if($contact->stores->count() > 0)
            <div class="card card-section">
                <div class="card-body">
                    <div class="section-header" style="background: linear-gradient(135deg, #34c38f 0%, #2ca67a 100%);">
                        <i class="mdi mdi-store me-2"></i>Associated Stores
                        <span class="badge bg-white text-success ms-auto">{{ $contact->stores->count() }}</span>
                    </div>

                    <div class="d-flex flex-wrap">
                        @foreach($contact->stores as $store)
                            <span class="store-badge">
                                @if($store->storeLogo)
                                    <img src="{{ asset($store->storeLogo) }}" alt="{{ $store->storeName }}">
                                @else
                                    <i class="mdi mdi-store text-primary me-1"></i>
                                @endif
                                {{ $store->storeName }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Quick Actions -->
            <div class="card card-section">
                <div class="card-body">
                    <div class="section-header" style="background: linear-gradient(135deg, #74788d 0%, #5a5f6e 100%);">
                        <i class="mdi mdi-lightning-bolt me-2"></i>Quick Actions
                    </div>

                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-soft-success btn-sm" id="markContactedBtn">
                            <i class="mdi mdi-calendar-check me-1"></i> Mark as Contacted Today
                        </button>
                        @if($contact->email)
                        <a href="mailto:{{ $contact->email }}" class="btn btn-soft-primary btn-sm">
                            <i class="mdi mdi-email-outline me-1"></i> Send Email
                        </a>
                        @endif
                        @if($contact->phone)
                        <a href="tel:{{ $contact->phone }}" class="btn btn-soft-info btn-sm">
                            <i class="mdi mdi-phone me-1"></i> Call
                        </a>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="card">
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('crm-business-contacts.edit', ['id' => $contact->id]) }}" class="btn btn-primary">
                            <i class="bx bx-edit me-1"></i> Edit Contact
                        </a>
                        <a href="{{ route('crm-business-contacts') }}" class="btn btn-secondary">
                            <i class="bx bx-arrow-back me-1"></i> Back to Contacts
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
    // Toastr options
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };

    // Mark as contacted
    $('#markContactedBtn').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Updating...');

        $.ajax({
            url: '{{ route("crm-business-contacts.update-last-contact") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: {{ $contact->id }}
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Updated!');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toastr.error(response.message, 'Error!');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to update', 'Error!');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="mdi mdi-calendar-check me-1"></i> Mark as Contacted Today');
            }
        });
    });
});
</script>
@endsection
