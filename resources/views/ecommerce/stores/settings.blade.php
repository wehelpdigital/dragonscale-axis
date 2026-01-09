@extends('layouts.master')

@section('title') Store Settings - {{ $store->storeName }} @endsection

@section('css')
<!-- Toastr -->
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />

<style>
.settings-tabs .nav-link {
    color: #495057;
    border: none;
    border-bottom: 2px solid transparent;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
}

.settings-tabs .nav-link:hover {
    border-color: #dee2e6;
    color: #556ee6;
}

.settings-tabs .nav-link.active {
    color: #556ee6;
    border-bottom-color: #556ee6;
    background: transparent;
}

.settings-tabs .nav-link i {
    margin-right: 0.5rem;
}

.smtp-status-badge {
    font-size: 0.75rem;
    padding: 0.35rem 0.75rem;
}

.form-section {
    background: #f8f9fa;
    border-radius: 0.5rem;
    padding: 1.25rem;
    margin-bottom: 1rem;
}

.form-section-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #e9ecef;
}

.password-toggle {
    cursor: pointer;
    padding: 0.375rem 0.75rem;
}

.test-email-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 1px dashed #dee2e6;
    border-radius: 0.5rem;
    padding: 1.25rem;
}
</style>
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') E-commerce @endslot
@slot('li_2') <a href="{{ route('ecom-stores') }}">Stores</a> @endslot
@slot('title') Store Settings @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-1 text-dark">
                            <i class="bx bx-cog me-2"></i>Settings for {{ $store->storeName }}
                        </h5>
                        <p class="text-secondary mb-0 small">Configure store-specific settings</p>
                    </div>
                    <a href="{{ route('ecom-stores') }}" class="btn btn-outline-secondary">
                        <i class="bx bx-arrow-back me-1"></i>Back to Stores
                    </a>
                </div>
            </div>

            <div class="card-body">
                <!-- Settings Tabs -->
                <ul class="nav nav-tabs settings-tabs mb-4" id="settingsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeTab === 'smtp' ? 'active' : '' }}" id="smtp-tab"
                                data-bs-toggle="tab" data-bs-target="#smtp" type="button" role="tab"
                                aria-controls="smtp" aria-selected="{{ $activeTab === 'smtp' ? 'true' : 'false' }}">
                            <i class="bx bx-envelope"></i>SMTP Settings
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeTab === 'notifications' ? 'active' : '' }}" id="notifications-tab"
                                data-bs-toggle="tab" data-bs-target="#notifications" type="button" role="tab"
                                aria-controls="notifications" aria-selected="{{ $activeTab === 'notifications' ? 'true' : 'false' }}">
                            <i class="bx bx-bell"></i>Notifications
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeTab === 'general' ? 'active' : '' }}" id="general-tab"
                                data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab"
                                aria-controls="general" aria-selected="{{ $activeTab === 'general' ? 'true' : 'false' }}">
                            <i class="bx bx-slider-alt"></i>General
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="settingsTabContent">
                    <!-- SMTP Settings Tab -->
                    <div class="tab-pane fade {{ $activeTab === 'smtp' ? 'show active' : '' }}" id="smtp" role="tabpanel" aria-labelledby="smtp-tab">
                        <div class="row">
                            <div class="col-lg-8">
                                <!-- SMTP Status Card -->
                                <div class="alert {{ $smtpSettings && $smtpSettings->isActive ? 'alert-success' : 'alert-secondary' }} d-flex align-items-center mb-4">
                                    <div class="flex-grow-1">
                                        <strong class="text-dark">SMTP Status:</strong>
                                        @if($smtpSettings && $smtpSettings->isActive)
                                            <span class="badge bg-success smtp-status-badge ms-2">
                                                <i class="bx bx-check-circle me-1"></i>Active
                                            </span>
                                            @if($smtpSettings->isVerified)
                                                <span class="badge bg-info smtp-status-badge ms-1">
                                                    <i class="bx bx-badge-check me-1"></i>Verified
                                                </span>
                                            @endif
                                        @else
                                            <span class="badge bg-secondary smtp-status-badge ms-2">
                                                <i class="bx bx-stop-circle me-1"></i>Inactive
                                            </span>
                                        @endif
                                    </div>
                                    @if($smtpSettings && $smtpSettings->isConfigured())
                                        <button type="button" class="btn btn-sm {{ $smtpSettings->isActive ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                                id="toggleSmtpStatus">
                                            <i class="bx {{ $smtpSettings->isActive ? 'bx-stop' : 'bx-play' }} me-1"></i>
                                            {{ $smtpSettings->isActive ? 'Disable' : 'Enable' }}
                                        </button>
                                    @endif
                                </div>

                                <!-- SMTP Configuration Form -->
                                <form id="smtpForm">
                                    <div class="form-section">
                                        <div class="form-section-title">
                                            <i class="bx bx-server me-1"></i>Server Configuration
                                        </div>
                                        <div class="row">
                                            <div class="col-md-8 mb-3">
                                                <label for="smtpHost" class="form-label text-dark">SMTP Host <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="smtpHost" name="smtpHost"
                                                       value="{{ $smtpSettings->smtpHost ?? '' }}"
                                                       placeholder="e.g., smtp.gmail.com">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="smtpPort" class="form-label text-dark">Port <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control" id="smtpPort" name="smtpPort"
                                                       value="{{ $smtpSettings->smtpPort ?? 587 }}"
                                                       placeholder="587">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="smtpUsername" class="form-label text-dark">Username</label>
                                                <input type="text" class="form-control" id="smtpUsername" name="smtpUsername"
                                                       value="{{ $smtpSettings->smtpUsername ?? '' }}"
                                                       placeholder="your-email@gmail.com">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="smtpPassword" class="form-label text-dark">Password</label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control" id="smtpPassword" name="smtpPassword"
                                                           placeholder="{{ $smtpSettings && $smtpSettings->smtpPassword ? '••••••••' : 'Enter password' }}">
                                                    <button class="btn btn-outline-secondary password-toggle" type="button" id="togglePassword">
                                                        <i class="bx bx-show"></i>
                                                    </button>
                                                </div>
                                                @if($smtpSettings && $smtpSettings->smtpPassword)
                                                    <small class="text-secondary">Leave blank to keep existing password</small>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="mb-0">
                                            <label for="smtpEncryption" class="form-label text-dark">Encryption <span class="text-danger">*</span></label>
                                            <select class="form-select" id="smtpEncryption" name="smtpEncryption">
                                                <option value="tls" {{ ($smtpSettings->smtpEncryption ?? 'tls') === 'tls' ? 'selected' : '' }}>TLS (Recommended)</option>
                                                <option value="ssl" {{ ($smtpSettings->smtpEncryption ?? '') === 'ssl' ? 'selected' : '' }}>SSL</option>
                                                <option value="none" {{ ($smtpSettings->smtpEncryption ?? '') === 'none' ? 'selected' : '' }}>None</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-section">
                                        <div class="form-section-title">
                                            <i class="bx bx-user me-1"></i>Sender Information
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="smtpFromEmail" class="form-label text-dark">From Email <span class="text-danger">*</span></label>
                                                <input type="email" class="form-control" id="smtpFromEmail" name="smtpFromEmail"
                                                       value="{{ $smtpSettings->smtpFromEmail ?? '' }}"
                                                       placeholder="noreply@yourstore.com">
                                            </div>
                                            <div class="col-md-6 mb-3 mb-md-0">
                                                <label for="smtpFromName" class="form-label text-dark">From Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="smtpFromName" name="smtpFromName"
                                                       value="{{ $smtpSettings->smtpFromName ?? $store->storeName }}"
                                                       placeholder="{{ $store->storeName }}">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary" id="saveSmtpBtn">
                                            <i class="bx bx-save me-1"></i>Save Settings
                                        </button>
                                    </div>
                                </form>

                                <!-- Test Email Section -->
                                <div class="test-email-section mt-4">
                                    <h6 class="text-dark mb-3">
                                        <i class="bx bx-mail-send me-1"></i>Test SMTP Connection
                                    </h6>
                                    <div class="row align-items-end">
                                        <div class="col-md-8 mb-3 mb-md-0">
                                            <label for="testEmail" class="form-label text-dark">Send test email to:</label>
                                            <input type="email" class="form-control" id="testEmail"
                                                   value="{{ $smtpSettings->smtpFromEmail ?? '' }}"
                                                   placeholder="test@example.com">
                                        </div>
                                        <div class="col-md-4">
                                            <button type="button" class="btn btn-outline-info w-100" id="sendTestEmail"
                                                    {{ !$smtpSettings || !$smtpSettings->isConfigured() ? 'disabled' : '' }}>
                                                <i class="bx bx-send me-1"></i>Send Test
                                            </button>
                                        </div>
                                    </div>
                                    @if($smtpSettings && $smtpSettings->lastTestedAt)
                                        <small class="text-secondary mt-2 d-block">
                                            <i class="bx bx-time me-1"></i>Last tested: {{ $smtpSettings->lastTestedAt->format('M j, Y g:i A') }}
                                        </small>
                                    @endif
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <!-- Help Card -->
                                <div class="card bg-light border-0">
                                    <div class="card-body">
                                        <h6 class="card-title text-dark">
                                            <i class="bx bx-help-circle me-1"></i>SMTP Help
                                        </h6>
                                        <p class="text-secondary small mb-3">
                                            Configure SMTP settings to send emails from your store, such as order confirmations and notifications.
                                        </p>

                                        <h6 class="text-dark small fw-bold">Common SMTP Settings:</h6>
                                        <ul class="text-secondary small ps-3 mb-3">
                                            <li><strong>Gmail:</strong> smtp.gmail.com, Port 587</li>
                                            <li><strong>Outlook:</strong> smtp.office365.com, Port 587</li>
                                            <li><strong>Yahoo:</strong> smtp.mail.yahoo.com, Port 587</li>
                                        </ul>

                                        <div class="alert alert-warning mb-0 py-2">
                                            <small class="text-dark">
                                                <i class="bx bx-info-circle me-1"></i>
                                                For Gmail, you may need to use an App Password instead of your regular password.
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notifications Tab -->
                    <div class="tab-pane fade {{ $activeTab === 'notifications' ? 'show active' : '' }}" id="notifications" role="tabpanel" aria-labelledby="notifications-tab">
                        <div class="text-center py-5">
                            <i class="bx bx-bell display-1 text-secondary"></i>
                            <h5 class="mt-3 text-dark">Notification Settings</h5>
                            <p class="text-secondary">Coming soon. Configure email notification preferences for this store.</p>
                        </div>
                    </div>

                    <!-- General Tab -->
                    <div class="tab-pane fade {{ $activeTab === 'general' ? 'show active' : '' }}" id="general" role="tabpanel" aria-labelledby="general-tab">
                        <div class="text-center py-5">
                            <i class="bx bx-slider-alt display-1 text-secondary"></i>
                            <h5 class="mt-3 text-dark">General Settings</h5>
                            <p class="text-secondary">Coming soon. Configure general store settings.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<!-- Toastr -->
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>

<script>
$(document).ready(function() {
    // Toastr configuration
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };

    const storeId = {{ $store->id }};

    // Toggle password visibility
    $('#togglePassword').on('click', function() {
        const input = $('#smtpPassword');
        const icon = $(this).find('i');

        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('bx-show').addClass('bx-hide');
        } else {
            input.attr('type', 'password');
            icon.removeClass('bx-hide').addClass('bx-show');
        }
    });

    // Save SMTP settings
    $('#smtpForm').on('submit', function(e) {
        e.preventDefault();

        const $btn = $('#saveSmtpBtn');
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...');

        $.ajax({
            url: `/ecom-store-settings-smtp?id=${storeId}`,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                smtpHost: $('#smtpHost').val(),
                smtpPort: $('#smtpPort').val(),
                smtpUsername: $('#smtpUsername').val(),
                smtpPassword: $('#smtpPassword').val(),
                smtpEncryption: $('#smtpEncryption').val(),
                smtpFromEmail: $('#smtpFromEmail').val(),
                smtpFromName: $('#smtpFromName').val()
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    // Enable test button if configured
                    if (response.data && response.data.isConfigured) {
                        $('#sendTestEmail').prop('disabled', false);
                    }
                    // Clear password field after save
                    $('#smtpPassword').val('').attr('placeholder', '••••••••');
                } else {
                    toastr.error(response.message || 'Failed to save settings.');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'An error occurred.');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Settings');
            }
        });
    });

    // Send test email
    $('#sendTestEmail').on('click', function() {
        const testEmail = $('#testEmail').val();

        if (!testEmail) {
            toastr.error('Please enter an email address for testing.');
            return;
        }

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Sending...');

        $.ajax({
            url: `/ecom-store-settings-smtp-test?id=${storeId}`,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                testEmail: testEmail
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                } else {
                    toastr.error(response.message || 'Failed to send test email.');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'An error occurred while sending test email.');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-send me-1"></i>Send Test');
            }
        });
    });

    // Toggle SMTP status
    $('#toggleSmtpStatus').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true);

        $.ajax({
            url: `/ecom-store-settings-smtp-toggle?id=${storeId}`,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    location.reload();
                } else {
                    toastr.error(response.message || 'Failed to toggle status.');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'An error occurred.');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });

    // Update URL when tab changes
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        const tabId = $(e.target).attr('aria-controls');
        const url = new URL(window.location);
        url.searchParams.set('tab', tabId);
        window.history.replaceState({}, '', url);
    });
});
</script>
@endsection
