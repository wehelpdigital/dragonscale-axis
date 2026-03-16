@extends('layouts.master')

@section('title') Generate Recommendations @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }
    .empty-state i {
        font-size: 4rem;
        color: #d1d5db;
        margin-bottom: 1rem;
    }
    .recommendation-card {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .recommendation-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    .provider-card {
        border-left: 4px solid #6c757d;
        transition: box-shadow 0.2s;
    }
    .provider-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    .provider-card.claude { border-left-color: #D97706; }
    .provider-card.openai { border-left-color: #10A37F; }
    .provider-card.gemini { border-left-color: #4285F4; }

    .provider-icon {
        width: 48px;
        height: 48px;
        min-width: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
        flex-shrink: 0;
    }
    .provider-icon.claude { background: linear-gradient(135deg, #D97706, #F59E0B); }
    .provider-icon.openai { background: linear-gradient(135deg, #10A37F, #34D399); }
    .provider-icon.gemini { background: linear-gradient(135deg, #4285F4, #60A5FA); }

    .provider-card .accordion-button {
        padding-right: 3rem;
    }
    .provider-card .accordion-button::after {
        position: absolute;
        right: 1rem;
        flex-shrink: 0;
    }
    .provider-badges {
        flex-shrink: 0;
        white-space: nowrap;
    }

    .password-toggle {
        cursor: pointer;
    }

    .nav-tabs .nav-link {
        font-weight: 500;
    }
    .nav-tabs .nav-link.active {
        border-bottom: 2px solid #556ee6;
    }

    /* Prevent text cursor/caret from appearing anywhere except inputs */
    .card,
    .card *:not(input):not(textarea):not(select):not(button):not(.btn) {
        caret-color: transparent !important;
    }

    /* Prevent text selection and focus outlines on non-interactive elements */
    .tab-pane,
    .tab-pane *:not(input):not(textarea):not(select):not(button):not(.btn):not(a),
    .empty-state,
    .empty-state *,
    .accordion-button,
    .accordion-button *,
    .accordion-body *:not(input):not(textarea):not(select):not(button):not(.btn),
    .table,
    .table *:not(button):not(.btn),
    .provider-icon,
    .provider-badges {
        user-select: none;
        outline: none !important;
    }

    /* Remove focus styles from non-interactive elements */
    .tab-pane:focus,
    .tab-pane *:not(input):not(textarea):not(select):not(button):not(.btn):focus,
    .empty-state:focus,
    .empty-state *:focus,
    .accordion-button:focus,
    .table:focus,
    .table *:not(button):not(.btn):focus {
        outline: none !important;
        box-shadow: none !important;
    }

    .accordion-button {
        cursor: pointer;
    }

    /* Ensure inputs still show caret */
    input, textarea, select {
        caret-color: auto !important;
    }

    /* =====================================================
       MOBILE RESPONSIVE STYLES WITH ANIMATIONS
       ===================================================== */

    /* Smooth transitions */
    .btn, .form-control, .form-select, .recommendation-card, .provider-card {
        transition: all 0.3s ease;
    }

    /* Card entrance animation */
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(15px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .card {
        animation: slideInUp 0.3s ease forwards;
    }

    .provider-card {
        animation: slideInUp 0.3s ease forwards;
    }

    .provider-card:nth-child(1) { animation-delay: 0.1s; }
    .provider-card:nth-child(2) { animation-delay: 0.2s; }
    .provider-card:nth-child(3) { animation-delay: 0.3s; }

    /* Small monitors (1280px - 1400px) */
    @media (max-width: 1400px) {
        .provider-icon {
            width: 44px;
            height: 44px;
            min-width: 44px;
            font-size: 1.35rem;
        }

        .nav-tabs .nav-link {
            padding: 10px 16px;
            font-size: 13.5px;
        }

        .table th, .table td {
            padding: 11px 10px;
            font-size: 13px;
        }

        .accordion-button h6 {
            font-size: 14.5px;
        }

        .accordion-body {
            padding: 16px;
        }
    }

    /* iPad landscape / 1024px monitors */
    @media (max-width: 1024px) {
        .provider-icon {
            width: 40px;
            height: 40px;
            min-width: 40px;
            font-size: 1.2rem;
            border-radius: 10px;
        }

        .nav-tabs .nav-link {
            padding: 9px 14px;
            font-size: 13px;
        }

        .table th {
            font-size: 11.5px;
            padding: 10px 8px;
        }

        .table td {
            font-size: 12.5px;
            padding: 10px 8px;
        }

        .accordion-button {
            padding: 14px 16px;
        }

        .accordion-button h6 {
            font-size: 14px;
        }

        .accordion-button small {
            font-size: 11px;
        }

        .accordion-body {
            padding: 14px;
        }

        .badge {
            font-size: 10px;
            padding: 3px 6px;
        }

        .form-label {
            font-size: 12.5px;
        }

        .form-control, .form-select {
            font-size: 13px;
            padding: 7px 10px;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }

        /* Empty state */
        .empty-state {
            padding: 50px 20px;
        }

        .empty-state i {
            font-size: 3.5rem;
        }

        .empty-state h5 {
            font-size: 16px;
        }

        .empty-state p {
            font-size: 13px;
        }
    }

    /* Tablet Styles */
    @media (max-width: 991px) {
        .provider-icon {
            width: 40px;
            height: 40px;
            min-width: 40px;
            font-size: 1.25rem;
        }

        .nav-tabs .nav-link {
            padding: 10px 14px;
            font-size: 13px;
        }

        .table th, .table td {
            padding: 10px 8px;
            font-size: 13px;
        }
    }

    /* Mobile Styles */
    @media (max-width: 767px) {
        /* Tabs - horizontal scroll */
        .nav-tabs {
            flex-wrap: nowrap;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }

        .nav-tabs::-webkit-scrollbar {
            display: none;
        }

        .nav-tabs .nav-item {
            flex: 0 0 auto;
        }

        .nav-tabs .nav-link {
            padding: 10px 14px;
            font-size: 12px;
            white-space: nowrap;
        }

        /* Provider cards */
        .provider-card .accordion-button {
            padding: 12px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .provider-icon {
            width: 36px;
            height: 36px;
            min-width: 36px;
            font-size: 1rem;
            border-radius: 8px;
        }

        .provider-badges {
            width: 100%;
            justify-content: flex-start;
            margin-top: 5px;
        }

        .accordion-body {
            padding: 15px;
        }

        /* Form elements */
        .form-control, .form-select {
            font-size: 14px;
            padding: 10px 12px;
        }

        /* Table */
        .table {
            font-size: 12px;
        }

        .table thead th {
            font-size: 11px;
            padding: 10px 6px;
        }

        /* Card header */
        .card-header h4 {
            font-size: 16px;
        }

        .card-header p {
            font-size: 12px;
        }

        /* Empty state */
        .empty-state {
            padding: 40px 15px;
        }

        .empty-state i {
            font-size: 3rem;
        }

        /* Breadcrumb */
        .page-title-box h4 {
            font-size: 16px;
        }
    }

    /* Small Mobile */
    @media (max-width: 575px) {
        .card-body {
            padding: 12px;
        }

        /* Provider card stacked layout */
        .provider-card .d-flex.align-items-center {
            flex-direction: column;
            text-align: center;
        }

        .provider-icon {
            margin-bottom: 10px;
        }

        .provider-badges {
            justify-content: center;
        }

        /* Tabs - icon only on very small */
        .nav-tabs .nav-link {
            padding: 8px 10px;
        }

        .nav-tabs .nav-link i {
            margin-right: 0 !important;
        }

        /* Table as cards */
        .table thead {
            display: none;
        }

        .table tbody tr {
            display: block;
            margin-bottom: 12px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 12px;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .table tbody td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border: none;
            border-bottom: 1px solid #f0f0f0;
        }

        .table tbody td:last-child {
            border-bottom: none;
            justify-content: flex-end;
            padding-top: 12px;
        }

        .table tbody td::before {
            content: attr(data-label);
            font-weight: 600;
            color: #495057;
            font-size: 11px;
        }

        /* Touch-friendly buttons */
        .btn {
            min-height: 44px;
        }

        .btn-sm {
            min-height: 36px;
        }

        /* Form inputs */
        .form-control, .form-select {
            font-size: 16px;
        }
    }

    /* Touch device */
    @media (hover: none) and (pointer: coarse) {
        .recommendation-card:active,
        .provider-card:active {
            transform: scale(0.99);
        }

        .btn:active {
            transform: scale(0.98);
        }

        .form-check-input {
            width: 20px;
            height: 20px;
        }

        .form-switch .form-check-input {
            width: 48px;
            height: 24px;
        }
    }

    /* Loading animation */
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    .btn .bx-loader-alt {
        animation: spin 1s linear infinite;
    }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') Recommendations @endslot
        @slot('title') Generate Recommendations @endslot
    @endcomponent

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h4 class="card-title mb-0">
                <i class="bx bx-bulb text-warning me-2"></i>Generate Recommendations
            </h4>
            <p class="text-secondary mb-0 mt-1">AI-powered recommendations, settings, and access management</p>
        </div>
        <div class="card-body">
            <!-- Tabs -->
            <ul class="nav nav-tabs mb-4" id="mainTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="recommendations-tab" data-bs-toggle="tab" data-bs-target="#recommendations"
                            type="button" role="tab" aria-controls="recommendations" aria-selected="true">
                        <i class="bx bx-bulb me-1"></i>Recommendations
                        @if($recommendations->count() > 0)
                            <span class="badge bg-warning text-dark ms-1">{{ $recommendations->count() }}</span>
                        @endif
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="api-tab" data-bs-toggle="tab" data-bs-target="#api-settings"
                            type="button" role="tab" aria-controls="api-settings" aria-selected="false">
                        <i class="bx bx-key me-1"></i>AI API Settings
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tags-tab" data-bs-toggle="tab" data-bs-target="#access-tags"
                            type="button" role="tab" aria-controls="access-tags" aria-selected="false">
                        <i class="bx bx-tag me-1"></i>Access Tags
                        <span class="badge bg-primary ms-1">{{ $accessTags->count() }}</span>
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="mainTabsContent">
                <!-- Tab 1: Recommendations -->
                <div class="tab-pane fade show active" id="recommendations" role="tabpanel" aria-labelledby="recommendations-tab">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h5 class="text-dark mb-1">Your Recommendations</h5>
                            <p class="text-secondary mb-0">AI-powered recommendations generated from questionnaires</p>
                        </div>
                        <a href="{{ route('recommendation-generate.create') }}" class="btn btn-primary d-inline-flex align-items-center">
                            <i class="bx bx-plus me-1"></i>
                            <span>Generate New Recommendation</span>
                        </a>
                    </div>

                    @if($recommendations->isEmpty())
                        <div class="empty-state">
                            <i class="bx bx-bulb"></i>
                            <h5 class="text-dark mt-3">No Recommendations Yet</h5>
                            <p class="text-secondary mb-0">
                                Create your first AI-powered recommendation by answering a questionnaire.
                            </p>
                        </div>
                    @else
                        <div class="row">
                            @foreach($recommendations as $recommendation)
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card recommendation-card h-100 border">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h5 class="card-title text-dark mb-0">{{ $recommendation->title }}</h5>
                                                {!! $recommendation->status_badge !!}
                                            </div>
                                            <p class="text-secondary small mb-3">
                                                Created {{ $recommendation->created_at->diffForHumans() }}
                                            </p>
                                            @if($recommendation->ai_response)
                                                <p class="text-dark">{{ Str::limit($recommendation->ai_response, 150) }}</p>
                                            @else
                                                <p class="text-secondary fst-italic">No AI response generated yet.</p>
                                            @endif
                                        </div>
                                        <div class="card-footer bg-transparent border-top">
                                            <div class="d-flex justify-content-between">
                                                <button class="btn btn-sm btn-outline-primary" disabled>
                                                    <i class="bx bx-show me-1"></i>View
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger delete-btn"
                                                        data-id="{{ $recommendation->id }}"
                                                        data-title="{{ $recommendation->title }}">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Tab 2: AI API Settings -->
                <div class="tab-pane fade" id="api-settings" role="tabpanel" aria-labelledby="api-tab">
                    <div class="row">
                        <div class="col-12">
                            <p class="text-secondary mb-4">
                                Configure your AI provider API keys to enable recommendation generation.
                                At least one provider must be configured and enabled.
                            </p>

                            <div class="accordion" id="providersAccordion">
                                @foreach(['claude', 'openai', 'gemini'] as $provider)
                                    @php $setting = $settings[$provider]; @endphp
                                    <div class="accordion-item provider-card {{ $provider }} mb-3 border rounded">
                                        <h2 class="accordion-header" id="heading-{{ $provider }}">
                                            <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button"
                                                    data-bs-toggle="collapse" data-bs-target="#collapse-{{ $provider }}"
                                                    aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                                                    aria-controls="collapse-{{ $provider }}">
                                                <div class="d-flex align-items-center w-100">
                                                    <div class="provider-icon {{ $provider }} me-3">
                                                        <i class="{{ $providerIcons[$provider] }}"></i>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-0 text-dark">{{ $providerLabels[$provider] }}</h6>
                                                        <small class="text-secondary">
                                                            {{ $setting->hasApiKey() ? 'API Key: ' . $setting->masked_api_key : 'Not configured' }}
                                                        </small>
                                                    </div>
                                                    <div class="provider-badges">
                                                        {!! $setting->status_badge !!}
                                                        @if($setting->isDefault)
                                                            <span class="badge bg-primary ms-1">Default</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </button>
                                        </h2>
                                        <div id="collapse-{{ $provider }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}"
                                             aria-labelledby="heading-{{ $provider }}" data-bs-parent="#providersAccordion">
                                            <div class="accordion-body">
                                                <form id="form-{{ $provider }}" class="provider-form" data-provider="{{ $provider }}">
                                                    @csrf
                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">API Key</label>
                                                            <div class="input-group">
                                                                <input type="password" class="form-control api-key-input"
                                                                       name="apiKey" placeholder="Enter API key"
                                                                       autocomplete="off">
                                                                <button class="btn btn-outline-secondary password-toggle" type="button">
                                                                    <i class="bx bx-hide"></i>
                                                                </button>
                                                            </div>
                                                            <small class="text-secondary">
                                                                @if($setting->hasApiKey())
                                                                    Current: {{ $setting->masked_api_key }} (leave blank to keep)
                                                                @else
                                                                    No API key configured
                                                                @endif
                                                            </small>
                                                        </div>
                                                        @if($provider === 'openai')
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Organization ID (Optional)</label>
                                                                <input type="text" class="form-control" name="organizationId"
                                                                       value="{{ $setting->organizationId }}"
                                                                       placeholder="org-xxxxx">
                                                            </div>
                                                        @endif
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Default Model</label>
                                                            <select class="form-select" name="defaultModel">
                                                                <option value="">Select a model</option>
                                                                @foreach($modelsByProvider[$provider] as $modelId => $modelName)
                                                                    <option value="{{ $modelId }}"
                                                                        {{ $setting->defaultModel === $modelId ? 'selected' : '' }}>
                                                                        {{ $modelName }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-md-3 mb-3">
                                                            <label class="form-label">Max Tokens</label>
                                                            <input type="number" class="form-control" name="maxTokens"
                                                                   value="{{ $setting->maxTokens ?? 4096 }}" min="1" max="200000">
                                                        </div>
                                                        <div class="col-md-3 mb-3">
                                                            <label class="form-label">Temperature</label>
                                                            <input type="number" class="form-control" name="temperature"
                                                                   value="{{ $setting->temperature ?? 0.7 }}"
                                                                   step="0.1" min="0" max="2">
                                                        </div>
                                                    </div>

                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox" name="isActive"
                                                                       id="isActive-{{ $provider }}" value="1"
                                                                       {{ $setting->isActive ? 'checked' : '' }}>
                                                                <label class="form-check-label" for="isActive-{{ $provider }}">
                                                                    Enable this provider
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="d-flex justify-content-between">
                                                        <div>
                                                            <button type="submit" class="btn btn-primary">
                                                                <i class="bx bx-save me-1"></i>Save Settings
                                                            </button>
                                                            <button type="button" class="btn btn-outline-secondary ms-2 test-connection-btn"
                                                                    data-provider="{{ $provider }}">
                                                                <i class="bx bx-plug me-1"></i>Test Connection
                                                            </button>
                                                        </div>
                                                        @if(!$setting->isDefault)
                                                            <button type="button" class="btn btn-outline-primary set-default-btn"
                                                                    data-provider="{{ $provider }}">
                                                                <i class="bx bx-star me-1"></i>Set as Default
                                                            </button>
                                                        @endif
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 3: Access Tags -->
                <div class="tab-pane fade" id="access-tags" role="tabpanel" aria-labelledby="tags-tab">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h5 class="text-dark mb-1">Access Tags</h5>
                            <p class="text-secondary mb-0">Manage access tags for recommendation clients</p>
                        </div>
                        <button class="btn btn-primary" id="addTagBtn">
                            <i class="bx bx-plus me-1"></i>Add New Tag
                        </button>
                    </div>

                    @if($accessTags->isEmpty())
                        <div class="empty-state" id="emptyState">
                            <i class="bx bx-tag"></i>
                            <h5 class="text-dark mt-3">No Access Tags Found</h5>
                            <p class="text-secondary mb-0">Create your first access tag to manage client access to recommendations.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover" id="tagsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-dark">Tag Name</th>
                                        <th class="text-dark">Expiration Length</th>
                                        <th class="text-dark">Description</th>
                                        <th class="text-dark text-center" style="width: 120px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($accessTags as $tag)
                                        <tr data-tag-id="{{ $tag->id }}">
                                            <td class="text-dark">
                                                <i class="bx bx-tag text-primary me-1"></i>
                                                <strong>{{ $tag->tagName }}</strong>
                                            </td>
                                            <td class="text-dark">
                                                <span class="badge bg-info text-white">{{ $tag->expirationLength }} days</span>
                                                <small class="text-secondary ms-1">({{ $tag->expiration_length_human }})</small>
                                            </td>
                                            <td class="text-secondary">{{ $tag->description ?? '-' }}</td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-outline-primary edit-tag-btn me-1"
                                                        data-tag-id="{{ $tag->id }}"
                                                        data-tag-name="{{ $tag->tagName }}"
                                                        data-tag-expiration="{{ $tag->expirationLength }}"
                                                        data-tag-description="{{ $tag->description }}">
                                                    <i class="bx bx-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger delete-tag-btn"
                                                        data-tag-id="{{ $tag->id }}"
                                                        data-tag-name="{{ $tag->tagName }}">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Recommendation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bx bx-trash text-danger me-2"></i>Confirm Delete
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-dark mb-0">Are you sure you want to delete "<strong id="deleteItemTitle"></strong>"?</p>
                    <p class="text-secondary small mb-0 mt-2">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">
                        <i class="bx bx-trash me-1"></i>Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tag Add/Edit Modal -->
    <div class="modal fade" id="tagModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tagModalTitle">
                        <i class="bx bx-tag text-primary me-2"></i>Add New Tag
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="tagForm" novalidate>
                    <div class="modal-body">
                        <input type="hidden" id="tagId" name="tagId">
                        <div class="mb-3">
                            <label for="tagName" class="form-label">Tag Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="tagName" name="tagName"
                                   placeholder="e.g. Premium Access, Monthly Plan" maxlength="255">
                            <div class="invalid-feedback" id="tagNameError"></div>
                        </div>
                        <div class="mb-3">
                            <label for="expirationLength" class="form-label">Expiration Length (Days) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="expirationLength" name="expirationLength"
                                   value="30" min="1" max="3650">
                            <small class="text-secondary">How many days until access expires (1-3650 days)</small>
                            <div class="invalid-feedback" id="expirationLengthError"></div>
                        </div>
                        <div class="mb-3">
                            <label for="tagDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="tagDescription" name="description"
                                      rows="3" maxlength="1000" placeholder="Optional description..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="saveTagBtn">
                            <i class="bx bx-save me-1"></i>Save Tag
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Tag Delete Confirmation Modal -->
    <div class="modal fade" id="deleteTagModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bx bx-trash text-danger me-2"></i>Confirm Delete
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-dark mb-0">Are you sure you want to delete the tag "<strong id="deleteTagName"></strong>"?</p>
                    <p class="text-secondary small mb-0 mt-2">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteTag">
                        <i class="bx bx-trash me-1"></i>Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
    // Toastr configuration
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };

    // Helper function to escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Tab persistence via URL hash
    $(document).ready(function() {
        let hash = window.location.hash;
        if (hash) {
            $('button[data-bs-target="' + hash + '"]').tab('show');
        }

        $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
            window.location.hash = $(e.target).data('bs-target');
            // Remove focus to prevent text cursor
            document.activeElement.blur();
        });

        // Remove focus when clicking anywhere that's not an input
        $(document).on('click', '.card', function(e) {
            const target = e.target;
            const tagName = target.tagName.toLowerCase();
            const isInteractive = ['input', 'textarea', 'select', 'button', 'a'].includes(tagName) ||
                                  $(target).hasClass('btn') ||
                                  $(target).closest('button, .btn, a').length > 0;

            if (!isInteractive) {
                document.activeElement.blur();
            }
        });

        // Prevent focus on non-interactive elements
        $(document).on('focus', '.card *', function(e) {
            const target = e.target;
            const tagName = target.tagName.toLowerCase();
            const isInteractive = ['input', 'textarea', 'select', 'button', 'a'].includes(tagName) ||
                                  $(target).hasClass('btn');

            if (!isInteractive) {
                e.preventDefault();
                target.blur();
            }
        });
    });

    // ==================== RECOMMENDATIONS ====================

    let itemToDelete = null;

    // Delete recommendation button click
    $(document).on('click', '.delete-btn', function() {
        itemToDelete = {
            id: $(this).data('id'),
            title: $(this).data('title')
        };
        $('#deleteItemTitle').text(itemToDelete.title);
        $('#deleteModal').modal('show');
    });

    // Confirm delete recommendation
    $('#confirmDelete').on('click', function() {
        if (!itemToDelete) return;

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Deleting...');

        $.ajax({
            url: '/recommendation-generate/' + itemToDelete.id,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    $('#deleteModal').modal('hide');
                    toastr.success('Recommendation deleted successfully!', 'Success');

                    // Remove the card from UI
                    const $card = $('.delete-btn[data-id="' + itemToDelete.id + '"]').closest('.col-md-6');
                    $card.fadeOut(400, function() {
                        $(this).remove();
                        // Check if no more recommendations
                        if ($('#recommendations .recommendation-card').length === 0) {
                            $('#recommendations .row').replaceWith(`
                                <div class="empty-state">
                                    <i class="bx bx-bulb"></i>
                                    <h5 class="text-dark mt-3">No Recommendations Yet</h5>
                                    <p class="text-secondary mb-0">
                                        Create your first AI-powered recommendation by answering a questionnaire.
                                    </p>
                                </div>
                            `);
                        }
                        // Update badge count
                        const $badge = $('#recommendations-tab .badge');
                        if ($badge.length) {
                            const currentCount = parseInt($badge.text()) - 1;
                            if (currentCount > 0) {
                                $badge.text(currentCount);
                            } else {
                                $badge.remove();
                            }
                        }
                    });
                } else {
                    toastr.error(response.message || 'Failed to delete recommendation.', 'Error');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'An error occurred.', 'Error');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-trash me-1"></i>Delete');
                itemToDelete = null;
            }
        });
    });

    // ==================== AI API SETTINGS ====================

    // Password toggle
    $(document).on('click', '.password-toggle', function() {
        const input = $(this).siblings('.api-key-input');
        const icon = $(this).find('i');

        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('bx-hide').addClass('bx-show');
        } else {
            input.attr('type', 'password');
            icon.removeClass('bx-show').addClass('bx-hide');
        }
    });

    // Save provider settings
    $(document).on('submit', '.provider-form', function(e) {
        e.preventDefault();

        const form = $(this);
        const provider = form.data('provider');
        const $btn = form.find('button[type="submit"]');
        const $accordionItem = form.closest('.accordion-item');
        const $badgesDiv = $accordionItem.find('.provider-badges');
        const $subtitleText = $accordionItem.find('.accordion-button small.text-secondary');
        const isActive = form.find('input[name="isActive"]').is(':checked');
        const apiKeyValue = form.find('input[name="apiKey"]').val();

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...');

        $.ajax({
            url: '/recommendation-settings/' + provider,
            type: 'PUT',
            data: form.serialize(),
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Success');

                    // Update status badge dynamically
                    let statusBadge = '';
                    if (!isActive) {
                        statusBadge = '<span class="badge bg-secondary">Disabled</span>';
                    } else {
                        statusBadge = '<span class="badge bg-warning text-dark">Not Tested</span>';
                    }

                    // Preserve Default badge if exists
                    const hasDefault = $badgesDiv.find('.badge.bg-primary').length > 0;
                    $badgesDiv.html(statusBadge + (hasDefault ? '<span class="badge bg-primary ms-1">Default</span>' : ''));

                    // Update API key display if a new key was entered
                    if (apiKeyValue) {
                        const maskedKey = apiKeyValue.substring(0, 4) + '****' + apiKeyValue.substring(apiKeyValue.length - 4);
                        $subtitleText.text('API Key: ' + maskedKey);
                        // Update the helper text below the input
                        form.find('.api-key-input').siblings('small').html('Current: ' + maskedKey + ' (leave blank to keep)');
                        // Clear the input field
                        form.find('.api-key-input').val('');
                    }
                } else {
                    toastr.error(response.message, 'Error');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to save settings.', 'Error');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Settings');
            }
        });
    });

    // Test connection
    $(document).on('click', '.test-connection-btn', function() {
        const provider = $(this).data('provider');
        const $btn = $(this);
        const $accordionItem = $btn.closest('.accordion-item');
        const $badgesDiv = $accordionItem.find('.provider-badges');

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Testing...');

        $.ajax({
            url: '/recommendation-settings/' + provider + '/test',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                // Preserve Default badge if exists
                const hasDefault = $badgesDiv.find('.badge.bg-primary').length > 0;
                const defaultBadge = hasDefault ? '<span class="badge bg-primary ms-1">Default</span>' : '';

                if (response.success) {
                    toastr.success(response.message, 'Connection Successful');
                    // Update to Connected badge
                    $badgesDiv.html('<span class="badge bg-success">Connected</span>' + defaultBadge);
                } else {
                    toastr.error(response.message, 'Connection Failed');
                    // Update to Failed badge
                    $badgesDiv.html('<span class="badge bg-danger">Failed</span>' + defaultBadge);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Connection test failed.', 'Error');
                // Update to Failed badge
                const hasDefault = $badgesDiv.find('.badge.bg-primary').length > 0;
                const defaultBadge = hasDefault ? '<span class="badge bg-primary ms-1">Default</span>' : '';
                $badgesDiv.html('<span class="badge bg-danger">Failed</span>' + defaultBadge);
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-plug me-1"></i>Test Connection');
            }
        });
    });

    // Set default provider
    $(document).on('click', '.set-default-btn', function() {
        const provider = $(this).data('provider');
        const $btn = $(this);
        const $accordionItem = $btn.closest('.accordion-item');

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Setting...');

        $.ajax({
            url: '/recommendation-settings/' + provider + '/default',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    toastr.success('Default provider set to ' + response.data.label, 'Success');

                    // Remove Default badge from all providers
                    $('.provider-badges .badge.bg-primary').remove();

                    // Show all Set as Default buttons
                    $('.set-default-btn').show();

                    // Hide this Set as Default button
                    $btn.hide();

                    // Add Default badge to this provider
                    $accordionItem.find('.provider-badges').append('<span class="badge bg-primary ms-1">Default</span>');
                } else {
                    toastr.error(response.message, 'Error');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to set default.', 'Error');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-star me-1"></i>Set as Default');
            }
        });
    });

    // ==================== ACCESS TAGS ====================

    let tagToDelete = null;
    let isEditing = false;

    // Add new tag
    $(document).on('click', '#addTagBtn', function() {
        isEditing = false;
        $('#tagModalTitle').html('<i class="bx bx-tag text-primary me-2"></i>Add New Tag');
        $('#tagForm')[0].reset();
        $('#tagId').val('');
        $('#expirationLength').val(30);
        $('#tagModal').modal('show');
    });

    // Edit tag
    $(document).on('click', '.edit-tag-btn', function() {
        isEditing = true;
        const $btn = $(this);

        $('#tagModalTitle').html('<i class="bx bx-edit text-primary me-2"></i>Edit Tag');
        $('#tagId').val($btn.data('tag-id'));
        $('#tagName').val($btn.data('tag-name'));
        $('#expirationLength').val($btn.data('tag-expiration'));
        $('#tagDescription').val($btn.data('tag-description') || '');
        $('#tagModal').modal('show');
    });

    // Clear validation errors when modal is opened
    $('#tagModal').on('show.bs.modal', function() {
        $('#tagForm .is-invalid').removeClass('is-invalid');
        $('#tagForm .invalid-feedback').text('');
    });

    // Clear validation error when user starts typing
    $('#tagName, #expirationLength').on('input', function() {
        $(this).removeClass('is-invalid');
        $(this).siblings('.invalid-feedback').text('');
    });

    // Save tag
    $('#tagForm').on('submit', function(e) {
        e.preventDefault();

        // Clear previous validation errors
        $('#tagForm .is-invalid').removeClass('is-invalid');
        $('#tagForm .invalid-feedback').text('');

        // Validate form
        let isValid = true;
        const tagName = $('#tagName').val().trim();
        const expirationLength = $('#expirationLength').val();

        if (!tagName) {
            $('#tagName').addClass('is-invalid');
            $('#tagNameError').text('Tag name is required.');
            isValid = false;
        } else if (tagName.length > 255) {
            $('#tagName').addClass('is-invalid');
            $('#tagNameError').text('Tag name cannot exceed 255 characters.');
            isValid = false;
        }

        if (!expirationLength) {
            $('#expirationLength').addClass('is-invalid');
            $('#expirationLengthError').text('Expiration length is required.');
            isValid = false;
        } else if (expirationLength < 1 || expirationLength > 3650) {
            $('#expirationLength').addClass('is-invalid');
            $('#expirationLengthError').text('Expiration must be between 1 and 3650 days.');
            isValid = false;
        }

        if (!isValid) {
            return;
        }

        const tagId = $('#tagId').val();
        const url = isEditing
            ? '/recommendation-settings/access-tags/' + tagId
            : '/recommendation-settings/access-tags';
        const method = isEditing ? 'PUT' : 'POST';

        const $btn = $('#saveTagBtn');
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...');

        $.ajax({
            url: url,
            type: method,
            data: {
                _token: '{{ csrf_token() }}',
                tagName: $('#tagName').val(),
                expirationLength: $('#expirationLength').val(),
                description: $('#tagDescription').val()
            },
            success: function(response) {
                if (response.success) {
                    $('#tagModal').modal('hide');
                    toastr.success(response.message, 'Success');

                    const tag = response.data;
                    const rowHtml = `
                        <tr data-tag-id="${tag.id}">
                            <td class="text-dark">
                                <i class="bx bx-tag text-primary me-1"></i>
                                <strong>${escapeHtml(tag.tagName)}</strong>
                            </td>
                            <td class="text-dark">
                                <span class="badge bg-info text-white">${tag.expirationLength} days</span>
                                <small class="text-secondary ms-1">(${escapeHtml(tag.expirationLengthHuman)})</small>
                            </td>
                            <td class="text-secondary">${tag.description ? escapeHtml(tag.description) : '-'}</td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary edit-tag-btn me-1"
                                        data-tag-id="${tag.id}"
                                        data-tag-name="${escapeHtml(tag.tagName)}"
                                        data-tag-expiration="${tag.expirationLength}"
                                        data-tag-description="${tag.description || ''}">
                                    <i class="bx bx-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger delete-tag-btn"
                                        data-tag-id="${tag.id}"
                                        data-tag-name="${escapeHtml(tag.tagName)}">
                                    <i class="bx bx-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;

                    if (isEditing) {
                        // Update existing row
                        $('tr[data-tag-id="' + tagId + '"]').replaceWith(rowHtml);
                    } else {
                        // Add new row or create table if empty
                        if ($('#tagsTable').length) {
                            $('#tagsTable tbody').prepend(rowHtml);
                        } else {
                            // Hide empty state and show table
                            $('#emptyState').replaceWith(`
                                <div class="table-responsive">
                                    <table class="table table-hover" id="tagsTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="text-dark">Tag Name</th>
                                                <th class="text-dark">Expiration Length</th>
                                                <th class="text-dark">Description</th>
                                                <th class="text-dark text-center" style="width: 120px;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>${rowHtml}</tbody>
                                    </table>
                                </div>
                            `);
                        }
                        // Update badge count
                        const currentCount = parseInt($('#tags-tab .badge').text()) || 0;
                        $('#tags-tab .badge').text(currentCount + 1);
                    }
                } else {
                    toastr.error(response.message, 'Error');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to save tag.', 'Error');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Tag');
            }
        });
    });

    // Delete tag button click
    $(document).on('click', '.delete-tag-btn', function() {
        tagToDelete = {
            id: $(this).data('tag-id'),
            name: $(this).data('tag-name')
        };
        $('#deleteTagName').text(tagToDelete.name);
        $('#deleteTagModal').modal('show');
    });

    // Confirm delete tag
    $('#confirmDeleteTag').on('click', function() {
        if (!tagToDelete) return;

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Deleting...');

        $.ajax({
            url: '/recommendation-settings/access-tags/' + tagToDelete.id,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    $('#deleteTagModal').modal('hide');
                    toastr.success('Access tag deleted successfully!', 'Success');

                    // Remove row from table
                    $('tr[data-tag-id="' + tagToDelete.id + '"]').fadeOut(400, function() {
                        $(this).remove();
                        // Check if table is empty - show empty state
                        if ($('#tagsTable tbody tr').length === 0) {
                            $('#tagsTable').closest('.table-responsive').replaceWith(`
                                <div class="empty-state" id="emptyState">
                                    <i class="bx bx-tag"></i>
                                    <h5 class="text-dark mt-3">No Access Tags Found</h5>
                                    <p class="text-secondary mb-0">Create your first access tag to manage client access to recommendations.</p>
                                </div>
                            `);
                        }
                    });

                    // Update badge count
                    const currentCount = parseInt($('#tags-tab .badge').text());
                    $('#tags-tab .badge').text(currentCount - 1);
                } else {
                    toastr.error(response.message, 'Error');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to delete tag.', 'Error');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-trash me-1"></i>Delete');
                tagToDelete = null;
            }
        });
    });
</script>
@endsection
