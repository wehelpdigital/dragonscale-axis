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
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeTab === 'cart-recovery' ? 'active' : '' }}" id="cart-recovery-tab"
                                data-bs-toggle="tab" data-bs-target="#cart-recovery" type="button" role="tab"
                                aria-controls="cart-recovery" aria-selected="{{ $activeTab === 'cart-recovery' ? 'true' : 'false' }}">
                            <i class="bx bx-cart-download"></i>Cart Recovery
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeTab === 'invoice' ? 'active' : '' }}" id="invoice-tab"
                                data-bs-toggle="tab" data-bs-target="#invoice" type="button" role="tab"
                                aria-controls="invoice" aria-selected="{{ $activeTab === 'invoice' ? 'true' : 'false' }}">
                            <i class="bx bx-file"></i>Invoice Settings
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

                                        <!-- Bank QR Code -->
                                        <div class="row mt-3">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label text-dark">Bank QR Code</label>
                                                <small class="text-secondary d-block mb-2">Upload your bank's QR code for easy payment</small>
                                                <div class="payment-image-container border rounded p-3 text-center" id="bankQrcodeContainer">
                                                    @if($paymentSettings && $paymentSettings->bankQrCodeImage)
                                                        <img src="{{ asset($paymentSettings->bankQrCodeImage) }}" class="img-fluid mb-2" style="max-height: 150px;" id="bankQrcodePreview">
                                                        <div>
                                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removePaymentImage('bankQrcode')">
                                                                <i class="bx bx-trash me-1"></i>Remove
                                                            </button>
                                                        </div>
                                                    @else
                                                        <div id="bankQrcodePlaceholder">
                                                            <i class="bx bx-qr text-secondary" style="font-size: 2rem;"></i>
                                                            <p class="text-secondary mb-0 small">No QR code uploaded</p>
                                                        </div>
                                                    @endif
                                                </div>
                                                <input type="file" class="form-control mt-2" id="bankQrcodeUpload" accept="image/*">
                                                <button type="button" class="btn btn-sm btn-outline-primary mt-2 w-100" id="uploadBankQrcodeBtn">
                                                    <i class="bx bx-upload me-1"></i>Upload QR Code
                                                </button>
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

                                        <!-- GCash QR Code -->
                                        <div class="row mt-3">
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

                                    <!-- Maya Section -->
                                    @php
                                        $mayaComplete = $paymentSettings && $paymentSettings->isMayaComplete();
                                        $mayaActive = $paymentSettings && $paymentSettings->isMayaActive;
                                    @endphp
                                    <div class="form-section">
                                        <div class="form-section-title">
                                            <i class="bx bx-wallet-alt me-1"></i>Maya Details
                                            @if($mayaActive)
                                                <span class="badge bg-success ms-2">Active</span>
                                            @endif
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="mayaNumber" class="form-label text-dark">Maya Number</label>
                                                <input type="text" class="form-control maya-field" id="mayaNumber" name="mayaNumber"
                                                       value="{{ $paymentSettings->mayaNumber ?? '' }}"
                                                       placeholder="e.g., 09171234567">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="mayaAccountName" class="form-label text-dark">Maya Account Name</label>
                                                <input type="text" class="form-control maya-field" id="mayaAccountName" name="mayaAccountName"
                                                       value="{{ $paymentSettings->mayaAccountName ?? '' }}"
                                                       placeholder="e.g., Juan D.">
                                            </div>
                                        </div>

                                        <!-- Maya QR Code -->
                                        <div class="row mt-3">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label text-dark">Maya QR Code</label>
                                                <small class="text-secondary d-block mb-2">Upload your Maya QR code for easy payment</small>
                                                <div class="payment-image-container border rounded p-3 text-center" id="mayaQrcodeContainer">
                                                    @if($paymentSettings && $paymentSettings->mayaQrCodeImage)
                                                        <img src="{{ asset($paymentSettings->mayaQrCodeImage) }}" class="img-fluid mb-2" style="max-height: 150px;" id="mayaQrcodePreview">
                                                        <div>
                                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removePaymentImage('mayaQrcode')">
                                                                <i class="bx bx-trash me-1"></i>Remove
                                                            </button>
                                                        </div>
                                                    @else
                                                        <div id="mayaQrcodePlaceholder">
                                                            <i class="bx bx-qr text-secondary" style="font-size: 2rem;"></i>
                                                            <p class="text-secondary mb-0 small">No QR code uploaded</p>
                                                        </div>
                                                    @endif
                                                </div>
                                                <input type="file" class="form-control mt-2" id="mayaQrcodeUpload" accept="image/*">
                                                <button type="button" class="btn btn-sm btn-outline-primary mt-2 w-100" id="uploadMayaQrcodeBtn">
                                                    <i class="bx bx-upload me-1"></i>Upload QR Code
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Maya Toggle Switch -->
                                        <div class="toggle-container {{ $mayaActive ? 'toggle-active' : '' }} {{ !$mayaComplete ? 'toggle-disabled' : '' }}" id="mayaToggleContainer">
                                            <div>
                                                <span class="toggle-label text-dark">
                                                    <i class="bx bx-wallet-alt me-1"></i>Enable Maya
                                                </span>
                                                @if(!$mayaComplete)
                                                    <span class="toggle-status text-secondary" id="mayaToggleHint">(Complete Maya Number and Account Name first)</span>
                                                @endif
                                            </div>
                                            <label class="toggle-switch">
                                                <input type="checkbox" id="mayaToggle" data-method="maya" {{ $mayaActive ? 'checked' : '' }} {{ !$mayaComplete ? 'disabled' : '' }}>
                                                <span class="toggle-slider"></span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- PayPal Section -->
                                    @php
                                        $paypalComplete = $paymentSettings && $paymentSettings->isPaypalComplete();
                                        $paypalActive = $paymentSettings && $paymentSettings->isPaypalActive;
                                    @endphp
                                    <div class="form-section">
                                        <div class="form-section-title">
                                            <i class="bx bxl-paypal me-1"></i>PayPal Details
                                            @if($paypalActive)
                                                <span class="badge bg-success ms-2">Active</span>
                                            @endif
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="paypalEmail" class="form-label text-dark">PayPal Email</label>
                                                <input type="email" class="form-control paypal-field" id="paypalEmail" name="paypalEmail"
                                                       value="{{ $paymentSettings->paypalEmail ?? '' }}"
                                                       placeholder="e.g., your-email@gmail.com">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="paypalAccountName" class="form-label text-dark">PayPal Account Name (Optional)</label>
                                                <input type="text" class="form-control" id="paypalAccountName" name="paypalAccountName"
                                                       value="{{ $paymentSettings->paypalAccountName ?? '' }}"
                                                       placeholder="e.g., Juan Dela Cruz">
                                            </div>
                                        </div>

                                        <!-- PayPal Toggle Switch -->
                                        <div class="toggle-container {{ $paypalActive ? 'toggle-active' : '' }} {{ !$paypalComplete ? 'toggle-disabled' : '' }}" id="paypalToggleContainer">
                                            <div>
                                                <span class="toggle-label text-dark">
                                                    <i class="bx bxl-paypal me-1"></i>Enable PayPal
                                                </span>
                                                @if(!$paypalComplete)
                                                    <span class="toggle-status text-secondary" id="paypalToggleHint">(Enter PayPal Email first)</span>
                                                @endif
                                            </div>
                                            <label class="toggle-switch">
                                                <input type="checkbox" id="paypalToggle" data-method="paypal" {{ $paypalActive ? 'checked' : '' }} {{ !$paypalComplete ? 'disabled' : '' }}>
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

                                        <h6 class="text-dark small fw-bold">Available Payment Methods:</h6>
                                        <ul class="text-secondary small ps-3 mb-3">
                                            <li><strong>Bank Transfer</strong> - Direct bank deposits with QR code support</li>
                                            <li><strong>GCash</strong> - Philippine mobile wallet</li>
                                            <li><strong>Maya</strong> - Philippine digital bank</li>
                                            <li><strong>PayPal</strong> - International payments</li>
                                        </ul>

                                        <h6 class="text-dark small fw-bold">Recommended:</h6>
                                        <ul class="text-secondary small ps-3 mb-3">
                                            <li>Upload QR codes for faster payments</li>
                                            <li>Include clear payment instructions</li>
                                            <li>Enable multiple payment options</li>
                                        </ul>

                                        <div class="alert alert-info mb-0 py-2">
                                            <small class="text-dark">
                                                <i class="bx bx-info-circle me-1"></i>
                                                QR codes help customers easily send payments to your accounts.
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

                    <!-- Cart Recovery Tab -->
                    <div class="tab-pane fade {{ $activeTab === 'cart-recovery' ? 'show active' : '' }}" id="cart-recovery" role="tabpanel" aria-labelledby="cart-recovery-tab">
                        <div class="row">
                            <div class="col-lg-8">
                                <!-- Recovery URL Info -->
                                <div class="form-section">
                                    <div class="form-section-title">
                                        <i class="bx bx-link me-1"></i>Recovery URL Configuration
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label text-dark">Base Recovery URL</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control bg-light" id="recoveryBaseUrl"
                                                   value="http://localhost:8001/checkout/continue/" readonly>
                                            <button class="btn btn-outline-primary" type="button" onclick="copyToClipboard('recoveryBaseUrl')">
                                                <i class="bx bx-copy"></i>
                                            </button>
                                        </div>
                                        <small class="text-secondary">This is the base URL for cart recovery links. The recovery token will be appended to this URL.</small>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label text-dark">Example Recovery URL</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control bg-light" id="recoveryExampleUrl"
                                                   value="http://localhost:8001/checkout/continue/abc123xyz..." readonly>
                                            <button class="btn btn-outline-primary" type="button" onclick="copyToClipboard('recoveryExampleUrl')">
                                                <i class="bx bx-copy"></i>
                                            </button>
                                        </div>
                                        <small class="text-secondary">Each order gets a unique recovery token that expires after 7 days.</small>
                                    </div>
                                </div>

                                <!-- Email Template Variables -->
                                <div class="form-section">
                                    <div class="form-section-title">
                                        <i class="bx bx-code-curly me-1"></i>Email Template Merge Tags
                                    </div>
                                    <p class="text-secondary mb-3">Use these merge tags in your abandoned cart email templates to include dynamic recovery information:</p>

                                    <div class="table-responsive">
                                        <table class="table table-bordered mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 40%">Merge Tag</th>
                                                    <th>Description</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td><code class="bg-light px-2 py-1 rounded">@{{recovery_url}}</code></td>
                                                    <td>Full recovery URL with token (clickable link)</td>
                                                </tr>
                                                <tr>
                                                    <td><code class="bg-light px-2 py-1 rounded">@{{order_number}}</code></td>
                                                    <td>The order number (e.g., ORD-20260307-ABCD)</td>
                                                </tr>
                                                <tr>
                                                    <td><code class="bg-light px-2 py-1 rounded">@{{client_first_name}}</code></td>
                                                    <td>Customer's first name</td>
                                                </tr>
                                                <tr>
                                                    <td><code class="bg-light px-2 py-1 rounded">@{{client_name}}</code></td>
                                                    <td>Customer's full name</td>
                                                </tr>
                                                <tr>
                                                    <td><code class="bg-light px-2 py-1 rounded">@{{product_name}}</code></td>
                                                    <td>Name of the product in cart</td>
                                                </tr>
                                                <tr>
                                                    <td><code class="bg-light px-2 py-1 rounded">@{{order_total}}</code></td>
                                                    <td>Total amount of the order</td>
                                                </tr>
                                                <tr>
                                                    <td><code class="bg-light px-2 py-1 rounded">@{{recovery_expires}}</code></td>
                                                    <td>When the recovery link expires</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- How It Works -->
                                <div class="form-section">
                                    <div class="form-section-title">
                                        <i class="bx bx-info-circle me-1"></i>How Cart Recovery Works
                                    </div>

                                    <div class="d-flex align-items-start gap-3 mb-3 p-3 bg-light rounded">
                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 14px; font-weight: bold;">1</div>
                                        <div>
                                            <h6 class="mb-1 text-dark">Customer Starts Checkout</h6>
                                            <p class="mb-0 small text-secondary">When a customer enters their info at checkout, a unique recovery token is generated and stored with the order.</p>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-start gap-3 mb-3 p-3 bg-light rounded">
                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 14px; font-weight: bold;">2</div>
                                        <div>
                                            <h6 class="mb-1 text-dark">Shopping Abandonment Flow Triggers</h6>
                                            <p class="mb-0 small text-secondary">If the customer doesn't complete payment, the shopping abandonment trigger flow runs and can send recovery emails.</p>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-start gap-3 mb-3 p-3 bg-light rounded">
                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 14px; font-weight: bold;">3</div>
                                        <div>
                                            <h6 class="mb-1 text-dark">Customer Clicks Recovery Link</h6>
                                            <p class="mb-0 small text-secondary">The link takes them directly to the payment step with their info pre-filled, making it easy to complete.</p>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-start gap-3 p-3 bg-light rounded">
                                        <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 14px; font-weight: bold;">4</div>
                                        <div>
                                            <h6 class="mb-1 text-dark">Order Status is Checked</h6>
                                            <p class="mb-0 small text-secondary">The system automatically checks if the order is already paid and shows the appropriate message.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <!-- Help Card -->
                                <div class="card bg-light border-0 mb-4">
                                    <div class="card-body">
                                        <h6 class="card-title text-dark">
                                            <i class="bx bx-help-circle me-1"></i>Cart Recovery Help
                                        </h6>
                                        <p class="text-secondary small mb-3">
                                            Cart recovery helps you recover lost sales by sending reminder emails to customers who didn't complete their purchase.
                                        </p>

                                        <h6 class="text-dark small fw-bold">Key Features:</h6>
                                        <ul class="text-secondary small ps-3 mb-3">
                                            <li>Unique recovery URL per order</li>
                                            <li>7-day link expiration</li>
                                            <li>Auto-detects payment status</li>
                                            <li>Pre-fills customer information</li>
                                        </ul>

                                        <div class="alert alert-info mb-0 py-2">
                                            <small class="text-dark">
                                                <i class="bx bx-bulb me-1"></i>
                                                <strong>Tip:</strong> Set up a Shopping Abandonment trigger flow to automatically send recovery emails.
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Status Info Card -->
                                <div class="card bg-light border-0">
                                    <div class="card-body">
                                        <h6 class="card-title text-dark">
                                            <i class="bx bx-check-shield me-1"></i>Link Status Handling
                                        </h6>

                                        <div class="mb-3">
                                            <span class="badge bg-success mb-1">Pending Payment</span>
                                            <p class="text-secondary small mb-0">Shows checkout page with payment form pre-filled.</p>
                                        </div>

                                        <div class="mb-3">
                                            <span class="badge bg-warning mb-1">Payment Pending Verification</span>
                                            <p class="text-secondary small mb-0">Shows message that payment is being verified.</p>
                                        </div>

                                        <div class="mb-3">
                                            <span class="badge bg-primary mb-1">Order Completed</span>
                                            <p class="text-secondary small mb-0">Shows success message with login link.</p>
                                        </div>

                                        <div>
                                            <span class="badge bg-danger mb-1">Link Expired/Invalid</span>
                                            <p class="text-secondary small mb-0">Shows error message with option to start new checkout.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Invoice Settings Tab -->
                    <div class="tab-pane fade {{ $activeTab === 'invoice' ? 'show active' : '' }}" id="invoice" role="tabpanel" aria-labelledby="invoice-tab">
                        <div class="row">
                            <div class="col-lg-8">
                                <form id="invoiceForm">
                                    <!-- Business Information -->
                                    <div class="form-section">
                                        <div class="form-section-title">
                                            <i class="bx bx-building me-1"></i>Business Information
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="invoiceBusinessName" class="form-label text-dark">Business Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="invoiceBusinessName" name="businessName"
                                                       value="{{ $invoiceSettings->businessName ?? $store->storeName }}"
                                                       placeholder="Your Business Name">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="invoiceTaxId" class="form-label text-dark">Tax ID (TIN)</label>
                                                <input type="text" class="form-control" id="invoiceTaxId" name="taxId"
                                                       value="{{ $invoiceSettings->taxId ?? '' }}"
                                                       placeholder="e.g., 123-456-789-000">
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="invoiceBusinessAddress" class="form-label text-dark">Business Address</label>
                                            <textarea class="form-control" id="invoiceBusinessAddress" name="businessAddress" rows="2"
                                                      placeholder="Enter your business address">{{ $invoiceSettings->businessAddress ?? '' }}</textarea>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="invoiceBusinessPhone" class="form-label text-dark">Business Phone</label>
                                                <input type="text" class="form-control" id="invoiceBusinessPhone" name="businessPhone"
                                                       value="{{ $invoiceSettings->businessPhone ?? '' }}"
                                                       placeholder="e.g., 09171234567">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="invoiceBusinessEmail" class="form-label text-dark">Business Email</label>
                                                <input type="email" class="form-control" id="invoiceBusinessEmail" name="businessEmail"
                                                       value="{{ $invoiceSettings->businessEmail ?? '' }}"
                                                       placeholder="e.g., billing@yourstore.com">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Logo Upload -->
                                    <div class="form-section">
                                        <div class="form-section-title">
                                            <i class="bx bx-image me-1"></i>Invoice Logo
                                        </div>
                                        <div class="row align-items-center">
                                            <div class="col-md-4">
                                                <div class="invoice-logo-container border rounded p-3 text-center" id="invoiceLogoContainer">
                                                    @if($invoiceSettings && $invoiceSettings->logoPath)
                                                        <img src="{{ asset($invoiceSettings->logoPath) }}" class="img-fluid" style="max-height: 100px;" id="invoiceLogoPreview">
                                                    @else
                                                        <div id="invoiceLogoPlaceholder">
                                                            <i class="bx bx-image text-secondary" style="font-size: 3rem;"></i>
                                                            <p class="text-secondary mb-0 small">No logo uploaded</p>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <input type="file" class="form-control mb-2" id="invoiceLogoUpload" accept="image/*">
                                                <div class="d-flex gap-2">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" id="uploadInvoiceLogoBtn">
                                                        <i class="bx bx-upload me-1"></i>Upload Logo
                                                    </button>
                                                    @if($invoiceSettings && $invoiceSettings->logoPath)
                                                    <button type="button" class="btn btn-sm btn-outline-danger" id="removeInvoiceLogoBtn">
                                                        <i class="bx bx-trash me-1"></i>Remove Logo
                                                    </button>
                                                    @endif
                                                </div>
                                                <small class="text-secondary d-block mt-2">Recommended size: 300x100 pixels. PNG or JPG format.</small>
                                            </div>
                                        </div>

                                        <!-- Show Logo Toggle -->
                                        <div class="toggle-container mt-3 {{ ($invoiceSettings->showLogo ?? true) ? 'toggle-active' : '' }}">
                                            <div>
                                                <span class="toggle-label text-dark">
                                                    <i class="bx bx-show me-1"></i>Show Logo on Invoice
                                                </span>
                                            </div>
                                            <label class="toggle-switch">
                                                <input type="checkbox" id="showLogoToggle" name="showLogo" {{ ($invoiceSettings->showLogo ?? true) ? 'checked' : '' }}>
                                                <span class="toggle-slider"></span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Color Theme -->
                                    <div class="form-section">
                                        <div class="form-section-title">
                                            <i class="bx bx-palette me-1"></i>Color Theme
                                        </div>
                                        <div class="row">
                                            <div class="col-md-3 mb-3">
                                                <label for="primaryColor" class="form-label text-dark">Primary Color</label>
                                                <div class="input-group">
                                                    <input type="color" class="form-control form-control-color" id="primaryColorPicker"
                                                           value="{{ $invoiceSettings->primaryColor ?? '#556ee6' }}"
                                                           style="width: 50px;">
                                                    <input type="text" class="form-control" id="primaryColor" name="primaryColor"
                                                           value="{{ $invoiceSettings->primaryColor ?? '#556ee6' }}"
                                                           placeholder="#556ee6">
                                                </div>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label for="secondaryColor" class="form-label text-dark">Secondary Color</label>
                                                <div class="input-group">
                                                    <input type="color" class="form-control form-control-color" id="secondaryColorPicker"
                                                           value="{{ $invoiceSettings->secondaryColor ?? '#34c38f' }}"
                                                           style="width: 50px;">
                                                    <input type="text" class="form-control" id="secondaryColor" name="secondaryColor"
                                                           value="{{ $invoiceSettings->secondaryColor ?? '#34c38f' }}"
                                                           placeholder="#34c38f">
                                                </div>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label for="headerBgColor" class="form-label text-dark">Header Background</label>
                                                <div class="input-group">
                                                    <input type="color" class="form-control form-control-color" id="headerBgColorPicker"
                                                           value="{{ $invoiceSettings->headerBgColor ?? '#556ee6' }}"
                                                           style="width: 50px;">
                                                    <input type="text" class="form-control" id="headerBgColor" name="headerBgColor"
                                                           value="{{ $invoiceSettings->headerBgColor ?? '#556ee6' }}"
                                                           placeholder="#556ee6">
                                                </div>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label for="headerTextColor" class="form-label text-dark">Header Text</label>
                                                <div class="input-group">
                                                    <input type="color" class="form-control form-control-color" id="headerTextColorPicker"
                                                           value="{{ $invoiceSettings->headerTextColor ?? '#ffffff' }}"
                                                           style="width: 50px;">
                                                    <input type="text" class="form-control" id="headerTextColor" name="headerTextColor"
                                                           value="{{ $invoiceSettings->headerTextColor ?? '#ffffff' }}"
                                                           placeholder="#ffffff">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Color Preview -->
                                        <div class="mt-3 p-3 rounded" id="colorPreview" style="background: {{ $invoiceSettings->headerBgColor ?? '#556ee6' }};">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div>
                                                    <h5 class="mb-0" id="previewText" style="color: {{ $invoiceSettings->headerTextColor ?? '#ffffff' }};">INVOICE</h5>
                                                    <small id="previewSubtext" style="color: {{ $invoiceSettings->headerTextColor ?? '#ffffff' }}; opacity: 0.9;">Preview of invoice header</small>
                                                </div>
                                                <div style="color: {{ $invoiceSettings->primaryColor ?? '#556ee6' }}; background: #fff; padding: 5px 15px; border-radius: 4px; font-weight: 600;">
                                                    <span id="previewPrimary">Primary</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Payment Information for Invoice -->
                                    <div class="form-section">
                                        <div class="form-section-title">
                                            <i class="bx bx-credit-card me-1"></i>Payment Details on Invoice
                                        </div>
                                        <p class="text-secondary small mb-3">These details will be displayed on invoices for customers to see your payment options.</p>

                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="invoiceBankName" class="form-label text-dark">Bank Name</label>
                                                <input type="text" class="form-control" id="invoiceBankName" name="bankName"
                                                       value="{{ $invoiceSettings->bankName ?? '' }}"
                                                       placeholder="e.g., BDO">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="invoiceBankAccountName" class="form-label text-dark">Account Name</label>
                                                <input type="text" class="form-control" id="invoiceBankAccountName" name="bankAccountName"
                                                       value="{{ $invoiceSettings->bankAccountName ?? '' }}"
                                                       placeholder="Account holder name">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="invoiceBankAccountNumber" class="form-label text-dark">Account Number</label>
                                                <input type="text" class="form-control" id="invoiceBankAccountNumber" name="bankAccountNumber"
                                                       value="{{ $invoiceSettings->bankAccountNumber ?? '' }}"
                                                       placeholder="e.g., 1234567890">
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="invoiceGcashNumber" class="form-label text-dark">GCash Number</label>
                                                <input type="text" class="form-control" id="invoiceGcashNumber" name="gcashNumber"
                                                       value="{{ $invoiceSettings->gcashNumber ?? '' }}"
                                                       placeholder="e.g., 09171234567">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="invoiceMayaNumber" class="form-label text-dark">Maya Number</label>
                                                <input type="text" class="form-control" id="invoiceMayaNumber" name="mayaNumber"
                                                       value="{{ $invoiceSettings->mayaNumber ?? '' }}"
                                                       placeholder="e.g., 09171234567">
                                            </div>
                                        </div>

                                        <!-- Show Bank Details Toggle -->
                                        <div class="toggle-container {{ ($invoiceSettings->showBankDetails ?? true) ? 'toggle-active' : '' }}">
                                            <div>
                                                <span class="toggle-label text-dark">
                                                    <i class="bx bx-show me-1"></i>Show Payment Details on Invoice
                                                </span>
                                            </div>
                                            <label class="toggle-switch">
                                                <input type="checkbox" id="showBankDetailsToggle" name="showBankDetails" {{ ($invoiceSettings->showBankDetails ?? true) ? 'checked' : '' }}>
                                                <span class="toggle-slider"></span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Messages & Terms -->
                                    <div class="form-section">
                                        <div class="form-section-title">
                                            <i class="bx bx-message-detail me-1"></i>Messages & Terms
                                        </div>

                                        <div class="mb-3">
                                            <label for="thankYouMessage" class="form-label text-dark">Thank You Message</label>
                                            <input type="text" class="form-control" id="thankYouMessage" name="thankYouMessage"
                                                   value="{{ $invoiceSettings->thankYouMessage ?? 'Thank you for your business!' }}"
                                                   placeholder="e.g., Thank you for your business!">
                                            <!-- Show Thank You Toggle -->
                                            <div class="toggle-container mt-2 {{ ($invoiceSettings->showThankYou ?? true) ? 'toggle-active' : '' }}">
                                                <div>
                                                    <span class="toggle-label text-dark small">
                                                        <i class="bx bx-show me-1"></i>Show Thank You Message
                                                    </span>
                                                </div>
                                                <label class="toggle-switch">
                                                    <input type="checkbox" id="showThankYouToggle" name="showThankYou" {{ ($invoiceSettings->showThankYou ?? true) ? 'checked' : '' }}>
                                                    <span class="toggle-slider"></span>
                                                </label>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="footerNote" class="form-label text-dark">Footer Note</label>
                                            <input type="text" class="form-control" id="footerNote" name="footerNote"
                                                   value="{{ $invoiceSettings->footerNote ?? '' }}"
                                                   placeholder="e.g., For questions, contact us at support@yourstore.com">
                                        </div>

                                        <div class="mb-3">
                                            <label for="termsAndConditions" class="form-label text-dark">Terms & Conditions</label>
                                            <textarea class="form-control" id="termsAndConditions" name="termsAndConditions" rows="4"
                                                      placeholder="Enter your terms and conditions for invoices...">{{ $invoiceSettings->termsAndConditions ?? '' }}</textarea>
                                            <!-- Show Terms Toggle -->
                                            <div class="toggle-container mt-2 {{ ($invoiceSettings->showTerms ?? true) ? 'toggle-active' : '' }}">
                                                <div>
                                                    <span class="toggle-label text-dark small">
                                                        <i class="bx bx-show me-1"></i>Show Terms & Conditions
                                                    </span>
                                                </div>
                                                <label class="toggle-switch">
                                                    <input type="checkbox" id="showTermsToggle" name="showTerms" {{ ($invoiceSettings->showTerms ?? true) ? 'checked' : '' }}>
                                                    <span class="toggle-slider"></span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Show Tax ID Toggle -->
                                        <div class="toggle-container {{ ($invoiceSettings->showTaxId ?? false) ? 'toggle-active' : '' }}">
                                            <div>
                                                <span class="toggle-label text-dark">
                                                    <i class="bx bx-id-card me-1"></i>Show Tax ID (TIN) on Invoice
                                                </span>
                                            </div>
                                            <label class="toggle-switch">
                                                <input type="checkbox" id="showTaxIdToggle" name="showTaxId" {{ ($invoiceSettings->showTaxId ?? false) ? 'checked' : '' }}>
                                                <span class="toggle-slider"></span>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary" id="saveInvoiceBtn">
                                            <i class="bx bx-save me-1"></i>Save Invoice Settings
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <div class="col-lg-4">
                                <!-- Help Card -->
                                <div class="card bg-light border-0 mb-4">
                                    <div class="card-body">
                                        <h6 class="card-title text-dark">
                                            <i class="bx bx-help-circle me-1"></i>Invoice Settings Help
                                        </h6>
                                        <p class="text-secondary small mb-3">
                                            Customize how your invoices look when generated for verified payments. Invoices are automatically created when a payment is verified.
                                        </p>

                                        <h6 class="text-dark small fw-bold">What's Included:</h6>
                                        <ul class="text-secondary small ps-3 mb-3">
                                            <li>Your business logo and details</li>
                                            <li>Custom color theme</li>
                                            <li>Payment information</li>
                                            <li>Terms and conditions</li>
                                        </ul>

                                        <div class="alert alert-info mb-0 py-2">
                                            <small class="text-dark">
                                                <i class="bx bx-link me-1"></i>
                                                Each invoice gets a unique public URL that can be shared with customers.
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Preview Card -->
                                <div class="card border-0" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                                    <div class="card-body">
                                        <h6 class="card-title text-dark">
                                            <i class="bx bx-show me-1"></i>Invoice Preview Elements
                                        </h6>

                                        <div class="mb-3">
                                            <span class="badge bg-primary mb-1">Header</span>
                                            <p class="text-secondary small mb-0">Shows "INVOICE" title with invoice number</p>
                                        </div>

                                        <div class="mb-3">
                                            <span class="badge bg-info text-white mb-1">Bill To</span>
                                            <p class="text-secondary small mb-0">Customer name, email, phone, address</p>
                                        </div>

                                        <div class="mb-3">
                                            <span class="badge bg-success mb-1">Items Table</span>
                                            <p class="text-secondary small mb-0">Product details, quantities, prices</p>
                                        </div>

                                        <div class="mb-3">
                                            <span class="badge bg-warning text-dark mb-1">Payment Info</span>
                                            <p class="text-secondary small mb-0">Payment method, amount, reference</p>
                                        </div>

                                        <div>
                                            <span class="badge bg-secondary mb-1">Footer</span>
                                            <p class="text-secondary small mb-0">Thank you message, terms, notes</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
            url: `/ecom-store-settings/smtp?id=${storeId}`,
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
            url: `/ecom-store-settings/smtp/test?id=${storeId}`,
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
            url: `/ecom-store-settings/smtp/toggle?id=${storeId}`,
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
                mayaNumber: $('#mayaNumber').val(),
                mayaAccountName: $('#mayaAccountName').val(),
                paypalEmail: $('#paypalEmail').val(),
                paypalAccountName: $('#paypalAccountName').val(),
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

    // Upload GCash QR Code
    $('#uploadQrcodeBtn').on('click', function() {
        uploadPaymentImage('qrcode', '#qrcodeUpload', '#uploadQrcodeBtn');
    });

    // Upload Bank QR Code
    $('#uploadBankQrcodeBtn').on('click', function() {
        uploadPaymentImage('bankQrcode', '#bankQrcodeUpload', '#uploadBankQrcodeBtn');
    });

    // Upload Maya QR Code
    $('#uploadMayaQrcodeBtn').on('click', function() {
        uploadPaymentImage('mayaQrcode', '#mayaQrcodeUpload', '#uploadMayaQrcodeBtn');
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

    // Check if Maya fields are complete
    function isMayaComplete() {
        return $('#mayaNumber').val().trim() !== '' &&
               $('#mayaAccountName').val().trim() !== '';
    }

    // Check if PayPal fields are complete
    function isPaypalComplete() {
        return $('#paypalEmail').val().trim() !== '';
    }

    // Update Maya toggle state based on field completion
    function updateMayaToggleState() {
        const complete = isMayaComplete();
        const $toggle = $('#mayaToggle');
        const $container = $('#mayaToggleContainer');
        const $hint = $('#mayaToggleHint');

        if (complete) {
            $toggle.prop('disabled', false);
            $container.removeClass('toggle-disabled');
            $hint.hide();
        } else {
            $toggle.prop('disabled', true).prop('checked', false);
            $container.removeClass('toggle-active').addClass('toggle-disabled');
            if ($hint.length === 0) {
                $container.find('.toggle-label').parent().append('<span class="toggle-status text-secondary" id="mayaToggleHint">(Complete Maya Number and Account Name first)</span>');
            } else {
                $hint.show();
            }
        }
    }

    // Update PayPal toggle state based on field completion
    function updatePaypalToggleState() {
        const complete = isPaypalComplete();
        const $toggle = $('#paypalToggle');
        const $container = $('#paypalToggleContainer');
        const $hint = $('#paypalToggleHint');

        if (complete) {
            $toggle.prop('disabled', false);
            $container.removeClass('toggle-disabled');
            $hint.hide();
        } else {
            $toggle.prop('disabled', true).prop('checked', false);
            $container.removeClass('toggle-active').addClass('toggle-disabled');
            if ($hint.length === 0) {
                $container.find('.toggle-label').parent().append('<span class="toggle-status text-secondary" id="paypalToggleHint">(Enter PayPal Email first)</span>');
            } else {
                $hint.show();
            }
        }
    }

    // Listen for changes on Maya fields
    $('.maya-field').on('input', function() {
        updateMayaToggleState();
    });

    // Listen for changes on PayPal fields
    $('.paypal-field').on('input', function() {
        updatePaypalToggleState();
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

    // Toggle Maya payment method
    $('#mayaToggle').on('change', function() {
        const $toggle = $(this);
        const isChecked = $toggle.prop('checked');

        $toggle.prop('disabled', true);

        $.ajax({
            url: `/ecom-store-settings/payment/toggle?id=${storeId}`,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                method: 'maya'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    if (response.isActive) {
                        $('#mayaToggleContainer').addClass('toggle-active');
                    } else {
                        $('#mayaToggleContainer').removeClass('toggle-active');
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

    // Toggle PayPal payment method
    $('#paypalToggle').on('change', function() {
        const $toggle = $(this);
        const isChecked = $toggle.prop('checked');

        $toggle.prop('disabled', true);

        $.ajax({
            url: `/ecom-store-settings/payment/toggle?id=${storeId}`,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                method: 'paypal'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    if (response.isActive) {
                        $('#paypalToggleContainer').addClass('toggle-active');
                    } else {
                        $('#paypalToggleContainer').removeClass('toggle-active');
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

    // Get the correct container ID based on image type
    const containerMapping = {
        'qrcode': '#qrcodeContainer',
        'bankQrcode': '#bankQrcodeContainer',
        'mayaQrcode': '#mayaQrcodeContainer'
    };
    const previewMapping = {
        'qrcode': 'qrcodePreview',
        'bankQrcode': 'bankQrcodePreview',
        'mayaQrcode': 'mayaQrcodePreview'
    };
    const btnTextMapping = {
        'qrcode': 'QR Code',
        'bankQrcode': 'QR Code',
        'mayaQrcode': 'QR Code'
    };

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
                const containerId = containerMapping[imageType];
                const previewId = previewMapping[imageType];

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
            $btn.prop('disabled', false).html(`<i class="bx bx-upload me-1"></i>Upload ${btnTextMapping[imageType]}`);
        }
    });
}

// Copy to clipboard helper function
function copyToClipboard(elementId) {
    const input = document.getElementById(elementId);
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value).then(function() {
        toastr.success('Copied to clipboard!');
    }).catch(function() {
        toastr.error('Failed to copy to clipboard.');
    });
}

// =====================
    // Invoice Settings
    // =====================

    // Save Invoice settings
    $('#invoiceForm').on('submit', function(e) {
        e.preventDefault();

        const $btn = $('#saveInvoiceBtn');
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...');

        $.ajax({
            url: `/ecom-store-settings/invoice?id=${storeId}`,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                businessName: $('#invoiceBusinessName').val(),
                businessAddress: $('#invoiceBusinessAddress').val(),
                businessPhone: $('#invoiceBusinessPhone').val(),
                businessEmail: $('#invoiceBusinessEmail').val(),
                taxId: $('#invoiceTaxId').val(),
                primaryColor: $('#primaryColor').val(),
                secondaryColor: $('#secondaryColor').val(),
                headerBgColor: $('#headerBgColor').val(),
                headerTextColor: $('#headerTextColor').val(),
                bankName: $('#invoiceBankName').val(),
                bankAccountName: $('#invoiceBankAccountName').val(),
                bankAccountNumber: $('#invoiceBankAccountNumber').val(),
                gcashNumber: $('#invoiceGcashNumber').val(),
                mayaNumber: $('#invoiceMayaNumber').val(),
                thankYouMessage: $('#thankYouMessage').val(),
                footerNote: $('#footerNote').val(),
                termsAndConditions: $('#termsAndConditions').val(),
                showLogo: $('#showLogoToggle').is(':checked') ? 1 : 0,
                showTaxId: $('#showTaxIdToggle').is(':checked') ? 1 : 0,
                showBankDetails: $('#showBankDetailsToggle').is(':checked') ? 1 : 0,
                showTerms: $('#showTermsToggle').is(':checked') ? 1 : 0,
                showThankYou: $('#showThankYouToggle').is(':checked') ? 1 : 0
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
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Invoice Settings');
            }
        });
    });

    // Upload Invoice Logo
    $('#uploadInvoiceLogoBtn').on('click', function() {
        const fileInput = $('#invoiceLogoUpload')[0];
        const $btn = $(this);

        if (!fileInput.files || !fileInput.files[0]) {
            toastr.error('Please select an image to upload.');
            return;
        }

        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('logo', fileInput.files[0]);

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Uploading...');

        $.ajax({
            url: `/ecom-store-settings/invoice/upload-logo?id=${storeId}`,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);

                    // Update the logo preview
                    $('#invoiceLogoContainer').html(`
                        <img src="${response.logoUrl}" class="img-fluid" style="max-height: 100px;" id="invoiceLogoPreview">
                    `);

                    // Show remove button if not exists
                    if ($('#removeInvoiceLogoBtn').length === 0) {
                        $btn.after(`
                            <button type="button" class="btn btn-sm btn-outline-danger" id="removeInvoiceLogoBtn">
                                <i class="bx bx-trash me-1"></i>Remove Logo
                            </button>
                        `);
                    }

                    // Clear the file input
                    $('#invoiceLogoUpload').val('');
                } else {
                    toastr.error(response.message || 'Failed to upload logo.');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'An error occurred while uploading.');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-upload me-1"></i>Upload Logo');
            }
        });
    });

    // Remove Invoice Logo
    $(document).on('click', '#removeInvoiceLogoBtn', function() {
        if (!confirm('Are you sure you want to remove the invoice logo?')) {
            return;
        }

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Removing...');

        $.ajax({
            url: `/ecom-store-settings/invoice/remove-logo?id=${storeId}`,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);

                    // Update the logo container
                    $('#invoiceLogoContainer').html(`
                        <div id="invoiceLogoPlaceholder">
                            <i class="bx bx-image text-secondary" style="font-size: 3rem;"></i>
                            <p class="text-secondary mb-0 small">No logo uploaded</p>
                        </div>
                    `);

                    // Remove the button
                    $btn.remove();
                } else {
                    toastr.error(response.message || 'Failed to remove logo.');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'An error occurred.');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-trash me-1"></i>Remove Logo');
            }
        });
    });

    // Color picker sync
    $('#primaryColorPicker').on('input', function() {
        $('#primaryColor').val($(this).val());
        updateColorPreview();
    });
    $('#primaryColor').on('input', function() {
        $('#primaryColorPicker').val($(this).val());
        updateColorPreview();
    });

    $('#secondaryColorPicker').on('input', function() {
        $('#secondaryColor').val($(this).val());
        updateColorPreview();
    });
    $('#secondaryColor').on('input', function() {
        $('#secondaryColorPicker').val($(this).val());
        updateColorPreview();
    });

    $('#headerBgColorPicker').on('input', function() {
        $('#headerBgColor').val($(this).val());
        updateColorPreview();
    });
    $('#headerBgColor').on('input', function() {
        $('#headerBgColorPicker').val($(this).val());
        updateColorPreview();
    });

    $('#headerTextColorPicker').on('input', function() {
        $('#headerTextColor').val($(this).val());
        updateColorPreview();
    });
    $('#headerTextColor').on('input', function() {
        $('#headerTextColorPicker').val($(this).val());
        updateColorPreview();
    });

    // Update color preview
    function updateColorPreview() {
        const headerBg = $('#headerBgColor').val();
        const headerText = $('#headerTextColor').val();
        const primary = $('#primaryColor').val();

        $('#colorPreview').css('background', headerBg);
        $('#previewText').css('color', headerText);
        $('#previewSubtext').css('color', headerText);
        $('#previewPrimary').css('color', primary);
    }

    // Toggle container styling updates for Invoice settings
    $('#showLogoToggle, #showTaxIdToggle, #showBankDetailsToggle, #showTermsToggle, #showThankYouToggle').on('change', function() {
        const $toggle = $(this);
        const $container = $toggle.closest('.toggle-container');

        if ($toggle.is(':checked')) {
            $container.addClass('toggle-active');
        } else {
            $container.removeClass('toggle-active');
        }
    });
});

// Remove payment image helper function
function removePaymentImage(imageType) {
    const typeNames = {
        'qrcode': 'QR code',
        'bankQrcode': 'bank QR code',
        'mayaQrcode': 'Maya QR code'
    };

    if (!confirm(`Are you sure you want to remove this ${typeNames[imageType] || 'image'}?`)) {
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

                // Get the correct container ID based on image type
                const containerMapping = {
                    'qrcode': '#qrcodeContainer',
                    'bankQrcode': '#bankQrcodeContainer',
                    'mayaQrcode': '#mayaQrcodeContainer'
                };
                const iconMapping = {
                    'qrcode': 'bx-qr',
                    'bankQrcode': 'bx-qr',
                    'mayaQrcode': 'bx-qr'
                };
                const placeholderMapping = {
                    'qrcode': 'No QR code uploaded',
                    'bankQrcode': 'No QR code uploaded',
                    'mayaQrcode': 'No QR code uploaded'
                };

                const containerId = containerMapping[imageType];
                const iconClass = iconMapping[imageType];
                const placeholderText = placeholderMapping[imageType];

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
