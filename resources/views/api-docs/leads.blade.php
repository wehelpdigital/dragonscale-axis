@extends('layouts.master')

@section('title') Leads API Documentation @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    .api-section {
        margin-bottom: 2rem;
    }
    .api-card {
        border-left: 4px solid #556ee6;
    }
    .endpoint-badge {
        font-family: 'Courier New', monospace;
        font-size: 13px;
        padding: 4px 12px;
        border-radius: 4px;
    }
    .param-table th {
        background: #f8f9fa;
        font-weight: 600;
        font-size: 13px;
    }
    .param-table td {
        font-size: 13px;
        vertical-align: middle;
    }
    .param-name {
        font-family: 'Courier New', monospace;
        color: #556ee6;
        font-weight: 600;
    }
    .param-required {
        color: #f46a6a;
        font-size: 11px;
        font-weight: 600;
    }
    .param-optional {
        color: #74788d;
        font-size: 11px;
    }
    .code-block {
        background: #2d2d2d;
        color: #f8f8f2;
        padding: 1rem;
        border-radius: 6px;
        font-family: 'Courier New', monospace;
        font-size: 12px;
        overflow-x: auto;
        white-space: pre-wrap;
        word-break: break-all;
    }
    .code-block .string { color: #a6e22e; }
    .code-block .key { color: #66d9ef; }
    .code-block .number { color: #ae81ff; }
    .code-block .boolean { color: #f92672; }
    .api-key-display {
        font-family: 'Courier New', monospace;
        background: #f8f9fa;
        padding: 12px 16px;
        border-radius: 6px;
        border: 1px dashed #ced4da;
        word-break: break-all;
    }
    .copy-btn {
        cursor: pointer;
    }
    .copy-btn:hover {
        color: #556ee6 !important;
    }
    .option-badge {
        font-size: 11px;
        padding: 2px 8px;
        margin-right: 4px;
        margin-bottom: 4px;
        display: inline-block;
    }
    .base-url {
        font-family: 'Courier New', monospace;
        background: #e8f4f8;
        padding: 8px 12px;
        border-radius: 4px;
        color: #0a58ca;
    }
    .response-success {
        border-left: 4px solid #34c38f;
    }
    .response-error {
        border-left: 4px solid #f46a6a;
    }
</style>
@endsection

@section('content')

    @component('components.breadcrumb')
        @slot('li_1') APIs @endslot
        @slot('title') Leads API @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <!-- API Key Section -->
            <div class="card api-card">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bx bx-key text-primary me-2" style="font-size: 24px;"></i>
                        <h5 class="card-title mb-0">Your API Key</h5>
                    </div>

                    <p class="text-secondary mb-3">
                        Your API key is required for all API requests. Keep it secure and never share it publicly.
                    </p>

                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="api-key-display flex-grow-1" id="apiKeyDisplay">
                            {{ $user->api_key }}
                        </div>
                        <button type="button" class="btn btn-soft-primary copy-btn" onclick="copyApiKey()" title="Copy to clipboard">
                            <i class="bx bx-copy"></i>
                        </button>
                        <button type="button" class="btn btn-soft-warning" onclick="regenerateApiKey()" title="Generate new key">
                            <i class="bx bx-refresh"></i> Regenerate
                        </button>
                    </div>

                    <div class="alert alert-warning mb-0">
                        <i class="bx bx-error-circle me-1"></i>
                        <strong>Warning:</strong> Regenerating your API key will invalidate the current key. Any integrations using the old key will stop working.
                    </div>
                </div>
            </div>

            <!-- Overview Section -->
            <div class="card api-section">
                <div class="card-body">
                    <h5 class="card-title"><i class="bx bx-info-circle text-info me-2"></i>Overview</h5>

                    <p class="text-dark">
                        The Leads API allows you to programmatically add new leads to your CRM system. This is useful for integrating with external forms, landing pages, third-party tools, or any system that needs to capture lead information.
                    </p>

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <h6 class="text-dark"><i class="bx bx-link me-1"></i>Base URL</h6>
                            <div class="base-url">{{ url('/api/leads') }}</div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-dark"><i class="bx bx-shield me-1"></i>Authentication</h6>
                            <p class="text-secondary mb-0">API Key via <code>api_key</code> query parameter</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Lead Endpoint -->
            <div class="card api-section">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-4">
                        <span class="endpoint-badge bg-success text-white me-3">GET</span>
                        <h5 class="card-title mb-0">/api/leads/add</h5>
                    </div>

                    <p class="text-dark mb-4">
                        Creates a new lead in the CRM system. All parameters are passed via query string (URL parameters).
                    </p>

                    <!-- Required Parameters -->
                    <h6 class="text-dark mb-3"><i class="bx bx-list-check text-danger me-1"></i>Required Parameters</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered param-table mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 180px;">Parameter</th>
                                    <th style="width: 120px;">Type</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="param-name">api_key</span> <span class="param-required">REQUIRED</span></td>
                                    <td>string</td>
                                    <td class="text-dark">Your unique API key for authentication</td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">firstName</span> <span class="param-required">REQUIRED*</span></td>
                                    <td>string</td>
                                    <td class="text-dark">Lead's first name (max 100 characters). *Can use <code>fullName</code> instead</td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">fullName</span> <span class="param-required">REQUIRED*</span></td>
                                    <td>string</td>
                                    <td class="text-dark">Lead's full name - will be split into firstName and lastName automatically. *Alternative to firstName</td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">email</span> <span class="param-required">REQUIRED</span></td>
                                    <td>string</td>
                                    <td class="text-dark">Lead's email address (must be valid email format)</td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">store_ids</span> <span class="param-required">REQUIRED</span></td>
                                    <td>string</td>
                                    <td class="text-dark">Comma-separated store IDs to target (e.g., "1,2,3")</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Optional Parameters - Personal Info -->
                    <h6 class="text-dark mb-3"><i class="bx bx-user text-primary me-1"></i>Optional Parameters - Personal Information</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered param-table mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 180px;">Parameter</th>
                                    <th style="width: 120px;">Type</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="param-name">lastName</span> <span class="param-optional">optional</span></td>
                                    <td>string</td>
                                    <td class="text-dark">Lead's last name (max 100 characters)</td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">middleName</span> <span class="param-optional">optional</span></td>
                                    <td>string</td>
                                    <td class="text-dark">Lead's middle name (max 100 characters)</td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">phone</span> <span class="param-optional">optional</span></td>
                                    <td>string</td>
                                    <td class="text-dark">Primary phone number (max 50 characters)</td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">alternatePhone</span> <span class="param-optional">optional</span></td>
                                    <td>string</td>
                                    <td class="text-dark">Alternate phone number (max 50 characters)</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Optional Parameters - Company Info -->
                    <h6 class="text-dark mb-3"><i class="bx bx-buildings text-info me-1"></i>Optional Parameters - Company Information</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered param-table mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 180px;">Parameter</th>
                                    <th style="width: 120px;">Type</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="param-name">companyName</span> <span class="param-optional">optional</span></td>
                                    <td>string</td>
                                    <td class="text-dark">Company or business name (max 255 characters)</td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">jobTitle</span> <span class="param-optional">optional</span></td>
                                    <td>string</td>
                                    <td class="text-dark">Job title or position (max 150 characters)</td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">department</span> <span class="param-optional">optional</span></td>
                                    <td>string</td>
                                    <td class="text-dark">Department name (max 150 characters)</td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">industry</span> <span class="param-optional">optional</span></td>
                                    <td>string</td>
                                    <td class="text-dark">Industry type (max 150 characters)</td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">companySize</span> <span class="param-optional">optional</span></td>
                                    <td>string</td>
                                    <td class="text-dark">
                                        Company size. Valid values:<br>
                                        @foreach($companySizeOptions as $value => $label)
                                            <span class="option-badge bg-light text-dark">{{ $value }}</span>
                                        @endforeach
                                    </td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">website</span> <span class="param-optional">optional</span></td>
                                    <td>string (URL)</td>
                                    <td class="text-dark">Company website (must be valid URL)</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Optional Parameters - Address -->
                    <h6 class="text-dark mb-3"><i class="bx bx-map text-success me-1"></i>Optional Parameters - Address</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered param-table mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 180px;">Parameter</th>
                                    <th style="width: 120px;">Type</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="param-name">streetAddress</span> <span class="param-optional">optional</span></td>
                                    <td>string</td>
                                    <td class="text-dark">Street address, building, unit number</td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">barangay</span> <span class="param-optional">optional</span></td>
                                    <td>string</td>
                                    <td class="text-dark">Barangay (max 100 characters)</td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">municipality</span> <span class="param-optional">optional</span></td>
                                    <td>string</td>
                                    <td class="text-dark">City or Municipality (max 100 characters)</td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">province</span> <span class="param-optional">optional</span></td>
                                    <td>string</td>
                                    <td class="text-dark">Province (max 100 characters)</td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">zipCode</span> <span class="param-optional">optional</span></td>
                                    <td>string</td>
                                    <td class="text-dark">Postal/ZIP code (max 20 characters)</td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">country</span> <span class="param-optional">optional</span></td>
                                    <td>string</td>
                                    <td class="text-dark">Country (max 100 characters, defaults to "Philippines")</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Optional Parameters - Social Media -->
                    <h6 class="text-dark mb-3"><i class="bx bx-share-alt text-warning me-1"></i>Optional Parameters - Social Media</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered param-table mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 180px;">Parameter</th>
                                    <th style="width: 120px;">Type</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="param-name">facebookUrl</span> <span class="param-optional">optional</span></td>
                                    <td>string (URL)</td>
                                    <td class="text-dark">Facebook profile URL</td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">instagramUrl</span> <span class="param-optional">optional</span></td>
                                    <td>string (URL)</td>
                                    <td class="text-dark">Instagram profile URL</td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">linkedinUrl</span> <span class="param-optional">optional</span></td>
                                    <td>string (URL)</td>
                                    <td class="text-dark">LinkedIn profile URL</td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">twitterUrl</span> <span class="param-optional">optional</span></td>
                                    <td>string (URL)</td>
                                    <td class="text-dark">Twitter/X profile URL</td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">tiktokUrl</span> <span class="param-optional">optional</span></td>
                                    <td>string (URL)</td>
                                    <td class="text-dark">TikTok profile URL</td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">viberNumber</span> <span class="param-optional">optional</span></td>
                                    <td>string</td>
                                    <td class="text-dark">Viber number (max 50 characters)</td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">whatsappNumber</span> <span class="param-optional">optional</span></td>
                                    <td>string</td>
                                    <td class="text-dark">WhatsApp number (max 50 characters)</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Optional Parameters - Lead Settings -->
                    <h6 class="text-dark mb-3"><i class="bx bx-cog text-secondary me-1"></i>Optional Parameters - Lead Settings</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered param-table mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 180px;">Parameter</th>
                                    <th style="width: 120px;">Type</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="param-name">leadStatus</span> <span class="param-optional">optional</span></td>
                                    <td>string</td>
                                    <td class="text-dark">
                                        Lead status (defaults to "new"). Valid values:<br>
                                        @foreach($statusOptions as $value => $option)
                                            <span class="option-badge bg-{{ $option['color'] }} {{ in_array($option['color'], ['warning', 'light']) ? 'text-dark' : 'text-white' }}">{{ $value }}</span>
                                        @endforeach
                                    </td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">leadPriority</span> <span class="param-optional">optional</span></td>
                                    <td>string</td>
                                    <td class="text-dark">
                                        Lead priority (defaults to "medium"). Valid values:<br>
                                        @foreach($priorityOptions as $value => $option)
                                            <span class="option-badge bg-{{ $option['color'] }} {{ in_array($option['color'], ['warning', 'light']) ? 'text-dark' : 'text-white' }}">{{ $value }}</span>
                                        @endforeach
                                    </td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">referredBy</span> <span class="param-optional">optional</span></td>
                                    <td>string</td>
                                    <td class="text-dark">Referral source or person (max 255 characters)</td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">notes</span> <span class="param-optional">optional</span></td>
                                    <td>string</td>
                                    <td class="text-dark">Additional notes about the lead</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Custom Fields -->
                    <h6 class="text-dark mb-3"><i class="bx bx-customize text-danger me-1"></i>Custom Fields</h6>
                    <div class="alert alert-info">
                        <p class="mb-2 text-dark"><strong>You can add custom fields using the following format:</strong></p>
                        <code>custom[fieldName]=fieldValue</code>
                        <p class="mt-2 mb-0 text-secondary">
                            Example: <code>custom[source_campaign]=Summer2024&custom[utm_medium]=facebook</code>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Available Stores -->
            <div class="card api-section">
                <div class="card-body">
                    <h5 class="card-title"><i class="bx bx-store text-primary me-2"></i>Available Store IDs</h5>
                    <p class="text-secondary mb-3">Use these store IDs in the <code>store_ids</code> parameter:</p>

                    @if($stores->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 80px;">ID</th>
                                    <th>Store Name</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stores as $store)
                                <tr>
                                    <td><code class="text-primary">{{ $store->id }}</code></td>
                                    <td class="text-dark">{{ $store->storeName }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="alert alert-warning mb-0">
                        <i class="bx bx-error-circle me-1"></i> No active stores found. Please create a store first.
                    </div>
                    @endif
                </div>
            </div>

            <!-- Example Request -->
            <div class="card api-section">
                <div class="card-body">
                    <h5 class="card-title"><i class="bx bx-code-curly text-success me-2"></i>Example Request</h5>

                    <h6 class="text-dark mt-4 mb-2">Minimal Request (Required Fields Only)</h6>
                    <div class="code-block" id="minimalExample">{{ url('/api/leads/add') }}?api_key={{ $user->api_key }}&firstName=John&email=john@example.com&store_ids=@if($stores->count() > 0){{ $stores->first()->id }}@else{{ '1' }}@endif</div>
                    <button class="btn btn-sm btn-soft-primary mt-2" onclick="copyCode('minimalExample')">
                        <i class="bx bx-copy me-1"></i> Copy
                    </button>

                    <h6 class="text-dark mt-4 mb-2">Full Request (With Optional Fields)</h6>
                    <div class="code-block" id="fullExample">{{ url('/api/leads/add') }}?api_key={{ $user->api_key }}&fullName=John%20Doe&email=john.doe@example.com&phone=09171234567&companyName=Acme%20Corp&jobTitle=Marketing%20Manager&province=Metro%20Manila&municipality=Makati&leadStatus=new&leadPriority=high&store_ids=@if($stores->count() > 0){{ $stores->first()->id }}@else{{ '1' }}@endif&custom[source]=website&custom[campaign]=launch2024</div>
                    <button class="btn btn-sm btn-soft-primary mt-2" onclick="copyCode('fullExample')">
                        <i class="bx bx-copy me-1"></i> Copy
                    </button>
                </div>
            </div>

            <!-- Response Examples -->
            <div class="card api-section">
                <div class="card-body">
                    <h5 class="card-title"><i class="bx bx-message-square-detail text-info me-2"></i>Response Examples</h5>

                    <h6 class="text-dark mt-4 mb-2"><i class="bx bx-check-circle text-success me-1"></i>Success Response (HTTP 201)</h6>
                    <div class="card response-success mb-4">
                        <div class="card-body p-0">
<pre class="code-block mb-0">{
    <span class="key">"success"</span>: <span class="boolean">true</span>,
    <span class="key">"message"</span>: <span class="string">"Lead created successfully"</span>,
    <span class="key">"data"</span>: {
        <span class="key">"id"</span>: <span class="number">123</span>,
        <span class="key">"fullName"</span>: <span class="string">"John Doe"</span>,
        <span class="key">"email"</span>: <span class="string">"john.doe@example.com"</span>,
        <span class="key">"phone"</span>: <span class="string">"09171234567"</span>,
        <span class="key">"status"</span>: <span class="string">"new"</span>,
        <span class="key">"priority"</span>: <span class="string">"high"</span>,
        <span class="key">"stores"</span>: {
            <span class="key">"1"</span>: <span class="string">"My Store Name"</span>
        },
        <span class="key">"customFields"</span>: {
            <span class="key">"source"</span>: <span class="string">"website"</span>,
            <span class="key">"campaign"</span>: <span class="string">"launch2024"</span>
        },
        <span class="key">"created_at"</span>: <span class="string">"2024-01-17 10:30:00"</span>
    }
}</pre>
                        </div>
                    </div>

                    <h6 class="text-dark mb-2"><i class="bx bx-x-circle text-danger me-1"></i>Error Response - Validation Error (HTTP 400)</h6>
                    <div class="card response-error mb-4">
                        <div class="card-body p-0">
<pre class="code-block mb-0">{
    <span class="key">"success"</span>: <span class="boolean">false</span>,
    <span class="key">"error"</span>: <span class="string">"Validation failed"</span>,
    <span class="key">"error_code"</span>: <span class="string">"VALIDATION_ERROR"</span>,
    <span class="key">"errors"</span>: {
        <span class="key">"email"</span>: [<span class="string">"Email address is required."</span>],
        <span class="key">"store_ids"</span>: [<span class="string">"At least one store target is required."</span>]
    }
}</pre>
                        </div>
                    </div>

                    <h6 class="text-dark mb-2"><i class="bx bx-x-circle text-danger me-1"></i>Error Response - Duplicate Email (HTTP 409)</h6>
                    <div class="card response-error mb-4">
                        <div class="card-body p-0">
<pre class="code-block mb-0">{
    <span class="key">"success"</span>: <span class="boolean">false</span>,
    <span class="key">"error"</span>: <span class="string">"A lead with this email already exists"</span>,
    <span class="key">"error_code"</span>: <span class="string">"DUPLICATE_EMAIL"</span>,
    <span class="key">"existing_lead_id"</span>: <span class="number">45</span>
}</pre>
                        </div>
                    </div>

                    <h6 class="text-dark mb-2"><i class="bx bx-x-circle text-danger me-1"></i>Error Response - Invalid API Key (HTTP 401)</h6>
                    <div class="card response-error">
                        <div class="card-body p-0">
<pre class="code-block mb-0">{
    <span class="key">"success"</span>: <span class="boolean">false</span>,
    <span class="key">"error"</span>: <span class="string">"Invalid API key"</span>,
    <span class="key">"error_code"</span>: <span class="string">"INVALID_API_KEY"</span>
}</pre>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Error Codes Reference -->
            <div class="card api-section">
                <div class="card-body">
                    <h5 class="card-title"><i class="bx bx-error-alt text-warning me-2"></i>Error Codes Reference</h5>

                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 200px;">Error Code</th>
                                    <th style="width: 100px;">HTTP Status</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>MISSING_API_KEY</code></td>
                                    <td><span class="badge bg-danger">401</span></td>
                                    <td class="text-dark">API key was not provided in the request</td>
                                </tr>
                                <tr>
                                    <td><code>INVALID_API_KEY</code></td>
                                    <td><span class="badge bg-danger">401</span></td>
                                    <td class="text-dark">The provided API key is invalid or has been revoked</td>
                                </tr>
                                <tr>
                                    <td><code>VALIDATION_ERROR</code></td>
                                    <td><span class="badge bg-warning text-dark">400</span></td>
                                    <td class="text-dark">One or more required fields are missing or invalid</td>
                                </tr>
                                <tr>
                                    <td><code>INVALID_STORE_IDS</code></td>
                                    <td><span class="badge bg-warning text-dark">400</span></td>
                                    <td class="text-dark">No valid store IDs were provided</td>
                                </tr>
                                <tr>
                                    <td><code>STORES_NOT_FOUND</code></td>
                                    <td><span class="badge bg-warning text-dark">400</span></td>
                                    <td class="text-dark">None of the provided store IDs exist or are active</td>
                                </tr>
                                <tr>
                                    <td><code>DUPLICATE_EMAIL</code></td>
                                    <td><span class="badge bg-info text-white">409</span></td>
                                    <td class="text-dark">A lead with the same email already exists in the system</td>
                                </tr>
                                <tr>
                                    <td><code>SERVER_ERROR</code></td>
                                    <td><span class="badge bg-danger">500</span></td>
                                    <td class="text-dark">An unexpected error occurred on the server</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Get Stores Endpoint -->
            <div class="card api-section">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-4">
                        <span class="endpoint-badge bg-primary text-white me-3">GET</span>
                        <h5 class="card-title mb-0">/api/leads/stores</h5>
                    </div>

                    <p class="text-dark mb-3">
                        Helper endpoint to retrieve a list of all available stores and their IDs.
                    </p>

                    <h6 class="text-dark mb-2">Example Request</h6>
                    <div class="code-block" id="storesExample">{{ url('/api/leads/stores') }}?api_key={{ $user->api_key }}</div>
                    <button class="btn btn-sm btn-soft-primary mt-2" onclick="copyCode('storesExample')">
                        <i class="bx bx-copy me-1"></i> Copy
                    </button>

                    <h6 class="text-dark mt-4 mb-2">Response</h6>
<pre class="code-block">{
    <span class="key">"success"</span>: <span class="boolean">true</span>,
    <span class="key">"data"</span>: [
        {
            <span class="key">"id"</span>: <span class="number">1</span>,
            <span class="key">"storeName"</span>: <span class="string">"Main Store"</span>
        },
        {
            <span class="key">"id"</span>: <span class="number">2</span>,
            <span class="key">"storeName"</span>: <span class="string">"Secondary Store"</span>
        }
    ]
}</pre>
                </div>
            </div>

        </div>
    </div>

@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>

<script>
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };

    function copyApiKey() {
        const apiKey = document.getElementById('apiKeyDisplay').innerText;
        navigator.clipboard.writeText(apiKey).then(() => {
            toastr.success('API key copied to clipboard!');
        }).catch(() => {
            toastr.error('Failed to copy API key');
        });
    }

    function copyCode(elementId) {
        const code = document.getElementById(elementId).innerText;
        navigator.clipboard.writeText(code).then(() => {
            toastr.success('Copied to clipboard!');
        }).catch(() => {
            toastr.error('Failed to copy');
        });
    }

    function regenerateApiKey() {
        if (!confirm('Are you sure you want to regenerate your API key? This will invalidate your current key.')) {
            return;
        }

        $.ajax({
            url: '{{ route("api-docs.regenerate-key") }}',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    document.getElementById('apiKeyDisplay').innerText = response.api_key;

                    // Update example URLs
                    const oldKey = '{{ $user->api_key }}';
                    document.querySelectorAll('.code-block').forEach(el => {
                        el.innerText = el.innerText.replace(oldKey, response.api_key);
                    });

                    toastr.success('API key regenerated successfully!');
                }
            },
            error: function() {
                toastr.error('Failed to regenerate API key');
            }
        });
    }
</script>
@endsection
