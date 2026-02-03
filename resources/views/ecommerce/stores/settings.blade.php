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

/* Toggle Switch Styles */
.toggle-switch {
    position: relative;
    width: 50px;
    height: 26px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: 0.3s;
    border-radius: 26px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 20px;
    width: 20px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.3s;
    border-radius: 50%;
}

input:checked + .toggle-slider {
    background-color: #34c38f;
}

input:checked + .toggle-slider:before {
    transform: translateX(24px);
}

input:disabled + .toggle-slider {
    background-color: #e9ecef;
    cursor: not-allowed;
}

.toggle-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 0.375rem;
    margin-top: 1rem;
}

.toggle-container.toggle-active {
    background: #d4edda;
}

.toggle-container.toggle-disabled {
    background: #f8f9fa;
    opacity: 0.7;
}

.toggle-label {
    font-weight: 500;
    font-size: 0.875rem;
}

.toggle-status {
    font-size: 0.75rem;
    margin-left: 0.5rem;
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
                        <button class="nav-link {{ $activeTab === 'payment' ? 'active' : '' }}" id="payment-tab"
                                data-bs-toggle="tab" data-bs-target="#payment" type="button" role="tab"
                                aria-controls="payment" aria-selected="{{ $activeTab === 'payment' ? 'true' : 'false' }}">
                            <i class="bx bx-credit-card"></i>Payment Settings
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

                                        <!-- SMTP Toggle Switch -->
                                        @php
                                            $smtpConfigured = $smtpSettings && $smtpSettings->isConfigured();
                                            $smtpActive = $smtpSettings && $smtpSettings->isActive;
                                        @endphp
                                        <div class="toggle-container {{ $smtpActive ? 'toggle-active' : '' }} {{ !$smtpConfigured ? 'toggle-disabled' : '' }}" id="smtpToggleContainer">
                                            <div>
                                                <span class="toggle-label text-dark">
                                                    <i class="bx bx-power-off me-1"></i>Enable SMTP
                                                </span>
                                                @if(!$smtpConfigured)
                                                    <span class="toggle-status text-secondary">(Complete all fields first)</span>
                                                @elseif($smtpSettings && $smtpSettings->isVerified)
                                                    <span class="toggle-status text-success"><i class="bx bx-badge-check"></i> Verified</span>
                                                @endif
                                            </div>
                                            <label class="toggle-switch">
                                                <input type="checkbox" id="smtpToggle" {{ $smtpActive ? 'checked' : '' }} {{ !$smtpConfigured ? 'disabled' : '' }}>
                                                <span class="toggle-slider"></span>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2 mt-4">
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

                    <!-- Payment Settings Tab -->
                    <div class="tab-pane fade {{ $activeTab === 'payment' ? 'show active' : '' }}" id="payment" role="tabpanel" aria-labelledby="payment-tab">
                        <div class="row">
                            <div class="col-lg-8">
                                <!-- Payment Configuration Form -->
                                <form id="paymentForm">
                                    <!-- Bank Account Section -->
                                    @php
                                        $bankComplete = $paymentSettings && $paymentSettings->isBankComplete();
                                        $bankActive = $paymentSettings && $paymentSettings->isBankActive;
                                    @endphp
                                    <div class="form-section">
                                        <div class="form-section-title">
                                            <i class="bx bx-building me-1"></i>Bank Account Details
                                            @if($bankActive)
                                                <span class="badge bg-success ms-2">Active</span>
                                            @endif
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12 mb-3">
                                                <label for="bankName" class="form-label text-dark">Bank Name</label>
                                                <input type="text" class="form-control bank-field" id="bankName" name="bankName"
                                                       value="{{ $paymentSettings->bankName ?? '' }}"
                                                       placeholder="e.g., BDO, BPI, Metrobank">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="bankAccountName" class="form-label text-dark">Account Name</label>
                                                <input type="text" class="form-control bank-field" id="bankAccountName" name="bankAccountName"
                                                       value="{{ $paymentSettings->bankAccountName ?? '' }}"
                                                       placeholder="e.g., Juan Dela Cruz">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="bankAccountNumber" class="form-label text-dark">Account Number</label>
                                                <input type="text" class="form-control bank-field" id="bankAccountNumber" name="bankAccountNumber"
                                                       value="{{ $paymentSettings->bankAccountNumber ?? '' }}"
                                                       placeholder="e.g., 1234567890">
                                            </div>
                                        </div>

                                        <!-- Bank Toggle Switch -->
                                        <div class="toggle-container {{ $bankActive ? 'toggle-active' : '' }} {{ !$bankComplete ? 'toggle-disabled' : '' }}" id="bankToggleContainer">
                                            <div>
                                                <span class="toggle-label text-dark">
                                                    <i class="bx bx-credit-card me-1"></i>Enable Bank Transfer
                                                </span>
                                                @if(!$bankComplete)
                                                    <span class="toggle-status text-secondary" id="bankToggleHint">(Complete all fields first)</span>
                                                @endif
                                            </div>
                                            <label class="toggle-switch">
                                                <input type="checkbox" id="bankToggle" data-method="bank" {{ $bankActive ? 'checked' : '' }} {{ !$bankComplete ? 'disabled' : '' }}>
                                                <span class="toggle-slider"></span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- GCash Section -->
                                    @php
                                        $gcashComplete = $paymentSettings && $paymentSettings->isGcashComplete();
                                        $gcashActive = $paymentSettings && $paymentSettings->isGcashActive;
                                    @endphp
                                    <div class="form-section">
                                        <div class="form-section-title">
                                            <i class="bx bx-mobile me-1"></i>GCash Details
                                            @if($gcashActive)
                                                <span class="badge bg-success ms-2">Active</span>
                                            @endif
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="gcashNumber" class="form-label text-dark">GCash Number</label>
                                                <input type="text" class="form-control gcash-field" id="gcashNumber" name="gcashNumber"
                                                       value="{{ $paymentSettings->gcashNumber ?? '' }}"
                                                       placeholder="e.g., 09171234567">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="gcashAccountName" class="form-label text-dark">GCash Account Name</label>
                                                <input type="text" class="form-control gcash-field" id="gcashAccountName" name="gcashAccountName"
                                                       value="{{ $paymentSettings->gcashAccountName ?? '' }}"
                                                       placeholder="e.g., Juan D.">
                                            </div>
                                        </div>

                                        <!-- GCash Images -->
                                        <div class="row mt-3">
                                            <!-- GCash Screenshot -->
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label text-dark">GCash Screenshot</label>
                                                <small class="text-secondary d-block mb-2">Upload a screenshot of your GCash account details</small>
                                                <div class="payment-image-container border rounded p-3 text-center" id="screenshotContainer">
                                                    @if($paymentSettings && $paymentSettings->paymentScreenshot)
                                                        <img src="{{ asset($paymentSettings->paymentScreenshot) }}" class="img-fluid mb-2" style="max-height: 150px;" id="screenshotPreview">
                                                        <div>
                                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removePaymentImage('screenshot')">
                                                                <i class="bx bx-trash me-1"></i>Remove
                                                            </button>
                                                        </div>
                                                    @else
                                                        <div id="screenshotPlaceholder">
                                                            <i class="bx bx-image-add text-secondary" style="font-size: 2rem;"></i>
                                                            <p class="text-secondary mb-0 small">No screenshot uploaded</p>
                                                        </div>
                                                    @endif
                                                </div>
                                                <input type="file" class="form-control mt-2" id="screenshotUpload" accept="image/*">
                                                <button type="button" class="btn btn-sm btn-outline-primary mt-2 w-100" id="uploadScreenshotBtn">
                                                    <i class="bx bx-upload me-1"></i>Upload Screenshot
                                                </button>
                                            </div>

                                            <!-- GCash QR Code -->
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label text-dark">GCash QR Code</label>
                                                <small class="text-secondary d-block mb-2">Upload your GCash QR code for easy payment</small>
                                                <div class="payment-image-container border rounded p-3 text-center" id="qrcodeContainer">
                                                    @if($paymentSettings && $paymentSettings->qrCodeImage)
                                                        <img src="{{ asset($paymentSettings->qrCodeImage) }}" class="img-fluid mb-2" style="max-height: 150px;" id="qrcodePreview">
                                                        <div>
                                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removePaymentImage('qrcode')">
                                                                <i class="bx bx-trash me-1"></i>Remove
                                                            </button>
                                                        </div>
                                                    @else
                                                        <div id="qrcodePlaceholder">
                                                            <i class="bx bx-qr text-secondary" style="font-size: 2rem;"></i>
                                                            <p class="text-secondary mb-0 small">No QR code uploaded</p>
                                                        </div>
                                                    @endif
                                                </div>
                                                <input type="file" class="form-control mt-2" id="qrcodeUpload" accept="image/*">
                                                <button type="button" class="btn btn-sm btn-outline-primary mt-2 w-100" id="uploadQrcodeBtn">
                                                    <i class="bx bx-upload me-1"></i>Upload QR Code
                                                </button>
                                            </div>
                                        </div>

                                        <!-- GCash Toggle Switch -->
                                        <div class="toggle-container {{ $gcashActive ? 'toggle-active' : '' }} {{ !$gcashComplete ? 'toggle-disabled' : '' }}" id="gcashToggleContainer">
                                            <div>
                                                <span class="toggle-label text-dark">
                                                    <i class="bx bx-wallet me-1"></i>Enable GCash
                                                </span>
                                                @if(!$gcashComplete)
                                                    <span class="toggle-status text-secondary" id="gcashToggleHint">(Complete GCash Number and Account Name first)</span>
                                                @endif
                                            </div>
                                            <label class="toggle-switch">
                                                <input type="checkbox" id="gcashToggle" data-method="gcash" {{ $gcashActive ? 'checked' : '' }} {{ !$gcashComplete ? 'disabled' : '' }}>
                                                <span class="toggle-slider"></span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Payment Instructions -->
                                    <div class="form-section">
                                        <div class="form-section-title">
                                            <i class="bx bx-info-circle me-1"></i>Payment Instructions (Optional)
                                        </div>
                                        <div class="mb-0">
                                            <label for="paymentInstructions" class="form-label text-dark">Instructions for Customers</label>
                                            <textarea class="form-control" id="paymentInstructions" name="paymentInstructions" rows="3"
                                                      placeholder="e.g., Please include your order number in the payment reference...">{{ $paymentSettings->paymentInstructions ?? '' }}</textarea>
                                            <small class="text-secondary">These instructions will be shown to customers during checkout.</small>
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary" id="savePaymentBtn">
                                            <i class="bx bx-save me-1"></i>Save Settings
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <div class="col-lg-4">
                                <!-- Help Card -->
                                <div class="card bg-light border-0">
                                    <div class="card-body">
                                        <h6 class="card-title text-dark">
                                            <i class="bx bx-help-circle me-1"></i>Payment Settings Help
                                        </h6>
                                        <p class="text-secondary small mb-3">
                                            Configure payment methods that customers can use to pay for their orders.
                                        </p>

                                        <h6 class="text-dark small fw-bold">Recommended:</h6>
                                        <ul class="text-secondary small ps-3 mb-3">
                                            <li>Add bank account details for bank transfers</li>
                                            <li>Add GCash number and account name</li>
                                            <li>Upload GCash screenshot and QR code</li>
                                            <li>Include clear payment instructions</li>
                                        </ul>

                                        <div class="alert alert-info mb-0 py-2">
                                            <small class="text-dark">
                                                <i class="bx bx-info-circle me-1"></i>
                                                GCash screenshot and QR code help customers easily send payments to your account.
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

    // Check if SMTP fields are complete
    function isSmtpComplete() {
        return $('#smtpHost').val().trim() !== '' &&
               $('#smtpPort').val().trim() !== '' &&
               $('#smtpFromEmail').val().trim() !== '' &&
               $('#smtpFromName').val().trim() !== '';
    }

    // Update SMTP toggle state based on field completion
    function updateSmtpToggleState() {
        const complete = isSmtpComplete();
        const $toggle = $('#smtpToggle');
        const $container = $('#smtpToggleContainer');
        const $hint = $container.find('.toggle-status');

        if (complete) {
            $toggle.prop('disabled', false);
            $container.removeClass('toggle-disabled');
            $hint.hide();
        } else {
            $toggle.prop('disabled', true).prop('checked', false);
            $container.removeClass('toggle-active').addClass('toggle-disabled');
            // Show hint if it doesn't exist or is hidden
            if ($hint.length === 0 || $hint.text().indexOf('Verified') >= 0) {
                // Don't replace verified badge
            } else {
                $hint.show();
            }
        }
    }

    // Listen for changes on SMTP fields
    $('#smtpHost, #smtpPort, #smtpFromEmail, #smtpFromName').on('input', function() {
        updateSmtpToggleState();
    });

    // Toggle SMTP status via switch
    $('#smtpToggle').on('change', function() {
        const $toggle = $(this);
        const isChecked = $toggle.prop('checked');

        // Revert immediately while we check
        $toggle.prop('disabled', true);

        $.ajax({
            url: `/ecom-store-settings-smtp-toggle?id=${storeId}`,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    // Update container styling
                    if (response.isActive) {
                        $('#smtpToggleContainer').addClass('toggle-active');
                    } else {
                        $('#smtpToggleContainer').removeClass('toggle-active');
                    }
                } else {
                    toastr.error(response.message || 'Failed to toggle status.');
                    // Revert the toggle
                    $toggle.prop('checked', !isChecked);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'An error occurred.');
                // Revert the toggle
                $toggle.prop('checked', !isChecked);
            },
            complete: function() {
                $toggle.prop('disabled', false);
            }
        });
    });

    // =====================
    // Payment Settings
    // =====================

    // Save Payment settings
    $('#paymentForm').on('submit', function(e) {
        e.preventDefault();

        const $btn = $('#savePaymentBtn');
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...');

        $.ajax({
            url: `/ecom-store-settings/payment?id=${storeId}`,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                bankName: $('#bankName').val(),
                bankAccountName: $('#bankAccountName').val(),
                bankAccountNumber: $('#bankAccountNumber').val(),
                gcashNumber: $('#gcashNumber').val(),
                gcashAccountName: $('#gcashAccountName').val(),
                paymentInstructions: $('#paymentInstructions').val()
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
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

    // Upload Screenshot
    $('#uploadScreenshotBtn').on('click', function() {
        uploadPaymentImage('screenshot', '#screenshotUpload', '#uploadScreenshotBtn');
    });

    // Upload QR Code
    $('#uploadQrcodeBtn').on('click', function() {
        uploadPaymentImage('qrcode', '#qrcodeUpload', '#uploadQrcodeBtn');
    });

    // Check if bank fields are complete
    function isBankComplete() {
        return $('#bankName').val().trim() !== '' &&
               $('#bankAccountName').val().trim() !== '' &&
               $('#bankAccountNumber').val().trim() !== '';
    }

    // Check if GCash fields are complete
    function isGcashComplete() {
        return $('#gcashNumber').val().trim() !== '' &&
               $('#gcashAccountName').val().trim() !== '';
    }

    // Update bank toggle state based on field completion
    function updateBankToggleState() {
        const complete = isBankComplete();
        const $toggle = $('#bankToggle');
        const $container = $('#bankToggleContainer');
        const $hint = $('#bankToggleHint');

        if (complete) {
            $toggle.prop('disabled', false);
            $container.removeClass('toggle-disabled');
            $hint.hide();
        } else {
            $toggle.prop('disabled', true).prop('checked', false);
            $container.removeClass('toggle-active').addClass('toggle-disabled');
            if ($hint.length === 0) {
                $container.find('.toggle-label').parent().append('<span class="toggle-status text-secondary" id="bankToggleHint">(Complete all fields first)</span>');
            } else {
                $hint.show();
            }
        }
    }

    // Update GCash toggle state based on field completion
    function updateGcashToggleState() {
        const complete = isGcashComplete();
        const $toggle = $('#gcashToggle');
        const $container = $('#gcashToggleContainer');
        const $hint = $('#gcashToggleHint');

        if (complete) {
            $toggle.prop('disabled', false);
            $container.removeClass('toggle-disabled');
            $hint.hide();
        } else {
            $toggle.prop('disabled', true).prop('checked', false);
            $container.removeClass('toggle-active').addClass('toggle-disabled');
            if ($hint.length === 0) {
                $container.find('.toggle-label').parent().append('<span class="toggle-status text-secondary" id="gcashToggleHint">(Complete GCash Number and Account Name first)</span>');
            } else {
                $hint.show();
            }
        }
    }

    // Listen for changes on bank fields
    $('.bank-field').on('input', function() {
        updateBankToggleState();
    });

    // Listen for changes on GCash fields
    $('.gcash-field').on('input', function() {
        updateGcashToggleState();
    });

    // Toggle Bank payment method
    $('#bankToggle').on('change', function() {
        const $toggle = $(this);
        const isChecked = $toggle.prop('checked');

        $toggle.prop('disabled', true);

        $.ajax({
            url: `/ecom-store-settings/payment/toggle?id=${storeId}`,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                method: 'bank'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    if (response.isActive) {
                        $('#bankToggleContainer').addClass('toggle-active');
                    } else {
                        $('#bankToggleContainer').removeClass('toggle-active');
                    }
                } else {
                    toastr.error(response.message || 'Failed to toggle status.');
                    $toggle.prop('checked', !isChecked);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'An error occurred.');
                $toggle.prop('checked', !isChecked);
            },
            complete: function() {
                $toggle.prop('disabled', false);
            }
        });
    });

    // Toggle GCash payment method
    $('#gcashToggle').on('change', function() {
        const $toggle = $(this);
        const isChecked = $toggle.prop('checked');

        $toggle.prop('disabled', true);

        $.ajax({
            url: `/ecom-store-settings/payment/toggle?id=${storeId}`,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                method: 'gcash'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    if (response.isActive) {
                        $('#gcashToggleContainer').addClass('toggle-active');
                    } else {
                        $('#gcashToggleContainer').removeClass('toggle-active');
                    }
                } else {
                    toastr.error(response.message || 'Failed to toggle status.');
                    $toggle.prop('checked', !isChecked);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'An error occurred.');
                $toggle.prop('checked', !isChecked);
            },
            complete: function() {
                $toggle.prop('disabled', false);
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

// Upload payment image helper function
function uploadPaymentImage(imageType, inputSelector, btnSelector) {
    const fileInput = $(inputSelector)[0];
    const $btn = $(btnSelector);

    if (!fileInput.files || !fileInput.files[0]) {
        toastr.error('Please select an image to upload.');
        return;
    }

    const formData = new FormData();
    formData.append('_token', '{{ csrf_token() }}');
    formData.append('image', fileInput.files[0]);
    formData.append('imageType', imageType);

    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Uploading...');

    $.ajax({
        url: `/ecom-store-settings/payment/upload?id={{ $store->id }}`,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);

                // Update the image preview
                const containerId = imageType === 'screenshot' ? '#screenshotContainer' : '#qrcodeContainer';
                const placeholderId = imageType === 'screenshot' ? '#screenshotPlaceholder' : '#qrcodePlaceholder';
                const previewId = imageType === 'screenshot' ? 'screenshotPreview' : 'qrcodePreview';

                $(containerId).html(`
                    <img src="${response.imageUrl}" class="img-fluid mb-2" style="max-height: 150px;" id="${previewId}">
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removePaymentImage('${imageType}')">
                            <i class="bx bx-trash me-1"></i>Remove
                        </button>
                    </div>
                `);

                // Clear the file input
                $(inputSelector).val('');
            } else {
                toastr.error(response.message || 'Failed to upload image.');
            }
        },
        error: function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'An error occurred while uploading.');
        },
        complete: function() {
            $btn.prop('disabled', false).html(`<i class="bx bx-upload me-1"></i>Upload ${imageType === 'screenshot' ? 'Screenshot' : 'QR Code'}`);
        }
    });
}

// Remove payment image helper function
function removePaymentImage(imageType) {
    if (!confirm(`Are you sure you want to remove this ${imageType === 'screenshot' ? 'screenshot' : 'QR code'}?`)) {
        return;
    }

    $.ajax({
        url: `/ecom-store-settings/payment/remove-image?id={{ $store->id }}`,
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            imageType: imageType
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);

                // Update the container to show placeholder
                const containerId = imageType === 'screenshot' ? '#screenshotContainer' : '#qrcodeContainer';
                const iconClass = imageType === 'screenshot' ? 'bx-image-add' : 'bx-qr';
                const placeholderText = imageType === 'screenshot' ? 'No screenshot uploaded' : 'No QR code uploaded';

                $(containerId).html(`
                    <div id="${imageType}Placeholder">
                        <i class="bx ${iconClass} text-secondary" style="font-size: 2rem;"></i>
                        <p class="text-secondary mb-0 small">${placeholderText}</p>
                    </div>
                `);
            } else {
                toastr.error(response.message || 'Failed to remove image.');
            }
        },
        error: function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'An error occurred.');
        }
    });
}
</script>
@endsection
