@extends('layouts.master')

@section('title') AI Settings @endsection

@section('css')
    <link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
    <style>
        .settings-card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .provider-card {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 24px;
            overflow: hidden;
        }
        .tag-list-item {
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 12px 15px;
            margin-bottom: 10px;
            background-color: #fff;
            transition: all 0.2s ease;
        }
        .tag-list-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .empty-state {
            padding: 60px 20px;
            text-align: center;
        }
        .empty-state i {
            font-size: 64px;
            color: #c3cbe4;
            margin-bottom: 20px;
        }
        .provider-header {
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #e9ecef;
        }
        .provider-header-left {
            display: flex;
            align-items: center;
        }
        .provider-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-size: 20px;
            color: white;
        }
        .provider-title {
            font-size: 16px;
            font-weight: 600;
            color: #495057;
            margin: 0;
        }
        .provider-subtitle {
            font-size: 12px;
            color: #6c757d;
            margin: 0;
        }
        .provider-body {
            padding: 20px;
            background: #fff;
        }
        .api-key-input {
            font-family: monospace;
            letter-spacing: 1px;
        }
        .status-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .form-section-title {
            font-size: 13px;
            font-weight: 600;
            color: #495057;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #f0f0f0;
        }
        .form-section-title i {
            margin-right: 6px;
            color: #556ee6;
        }
        .masked-key {
            font-family: monospace;
            color: #6c757d;
            font-size: 12px;
        }
        .default-star {
            color: #f1b44c;
            margin-left: 8px;
        }
        .provider-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }
    </style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') AI Technician @endslot
        @slot('title') Settings @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card settings-card">
                <div class="card-body">
                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs nav-tabs-custom nav-justified" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#apiSettings" role="tab">
                                <i class="bx bx-cog me-1"></i>
                                <span class="d-none d-sm-inline">API Settings</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#accessTags" role="tab">
                                <i class="bx bx-tag me-1"></i>
                                <span class="d-none d-sm-inline">Access Tags</span>
                                <span class="badge bg-secondary ms-1">{{ $accessTags->count() }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#imageSearch" role="tab">
                                <i class="bx bx-images me-1"></i>
                                <span class="d-none d-sm-inline">Image Search</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#currencySettings" role="tab">
                                <i class="bx bx-money me-1"></i>
                                <span class="d-none d-sm-inline">Currency</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#avatarSettings" role="tab">
                                <i class="bx bx-user-circle me-1"></i>
                                <span class="d-none d-sm-inline">Avatar</span>
                            </a>
                        </li>
                    </ul>

                    <!-- Tab panes -->
                    <div class="tab-content p-3 pt-4">
                        <!-- Tab 1: API Settings -->
                        <div class="tab-pane active" id="apiSettings" role="tabpanel">
                            <h5 class="text-dark mb-2">
                                <i class="mdi mdi-api me-2"></i>AI API Settings
                            </h5>
                            <p class="text-secondary mb-4">
                                Configure your AI provider API keys and settings. You can enable multiple providers and set one as the default for AI operations.
                            </p>

                            <!-- Provider Sections -->
                            @foreach($settings as $provider => $setting)
                        <div class="provider-card">
                            <!-- Provider Header -->
                            <div class="provider-header" style="background-color: {{ $providerColors[$provider] }}15;">
                                <div class="provider-header-left">
                                    <div class="provider-icon" style="background-color: {{ $providerColors[$provider] }}">
                                        <i class="{{ $providerIcons[$provider] }}"></i>
                                    </div>
                                    <div>
                                        <h5 class="provider-title">
                                            {{ $providerLabels[$provider] }}
                                            @if($setting->isDefault)
                                                <i class="bx bxs-star default-star" title="Default Provider"></i>
                                            @endif
                                        </h5>
                                        <p class="provider-subtitle">
                                            @if($provider === 'claude')
                                                Anthropic's Claude AI models
                                            @elseif($provider === 'openai')
                                                OpenAI's GPT models
                                            @else
                                                Google's Gemini models
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="provider-actions">
                                    <div class="status-indicator">
                                        <span id="{{ $provider }}-status-badge">{!! $setting->status_badge !!}</span>
                                    </div>
                                    <button type="button"
                                            class="btn btn-outline-primary btn-sm test-connection-btn"
                                            data-provider="{{ $provider }}"
                                            {{ !$setting->hasApiKey() ? 'disabled' : '' }}>
                                        <i class="bx bx-check-circle me-1"></i>Test
                                    </button>
                                </div>
                            </div>

                            <!-- Provider Body -->
                            <div class="provider-body">
                                <form id="{{ $provider }}-form" class="provider-form" data-provider="{{ $provider }}">
                                    @csrf

                                    <div class="row">
                                        <!-- Left Column: API Credentials -->
                                        <div class="col-md-6">
                                            <div class="form-section-title">
                                                <i class="bx bx-key"></i>API Credentials
                                            </div>

                                            <div class="mb-3">
                                                <label for="{{ $provider }}-apiKey" class="form-label">
                                                    API Key
                                                    @if($setting->hasApiKey())
                                                        <span class="badge bg-success ms-1" style="font-size: 10px;">Configured</span>
                                                    @endif
                                                </label>
                                                <div class="input-group">
                                                    <input type="password"
                                                           class="form-control api-key-input"
                                                           id="{{ $provider }}-apiKey"
                                                           name="apiKey"
                                                           placeholder="{{ $setting->hasApiKey() ? 'Enter new key to update' : 'Enter your API key' }}"
                                                           autocomplete="off">
                                                    <button class="btn btn-outline-secondary toggle-password" type="button">
                                                        <i class="bx bx-show"></i>
                                                    </button>
                                                </div>
                                                @if($setting->hasApiKey())
                                                    <small class="text-secondary">
                                                        Current: <span class="masked-key">{{ $setting->masked_api_key }}</span>
                                                    </small>
                                                @endif
                                                <div class="form-text text-secondary">
                                                    @if($provider === 'claude')
                                                        Get from <a href="https://console.anthropic.com/settings/keys" target="_blank">Anthropic Console</a>
                                                    @elseif($provider === 'openai')
                                                        Get from <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a>
                                                    @else
                                                        Get from <a href="https://aistudio.google.com/app/apikey" target="_blank">Google AI Studio</a>
                                                    @endif
                                                </div>
                                            </div>

                                            @if($provider === 'openai')
                                                <div class="mb-3">
                                                    <label for="{{ $provider }}-organizationId" class="form-label">
                                                        Organization ID <span class="text-secondary">(Optional)</span>
                                                    </label>
                                                    <input type="text"
                                                           class="form-control"
                                                           id="{{ $provider }}-organizationId"
                                                           name="organizationId"
                                                           value="{{ $setting->organizationId }}"
                                                           placeholder="org-xxxxxxxxxxxxxxxx">
                                                    <div class="form-text text-secondary">
                                                        Required only for multiple organizations.
                                                    </div>
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Right Column: Model Configuration -->
                                        <div class="col-md-6">
                                            <div class="form-section-title">
                                                <i class="bx bx-chip"></i>Model Configuration
                                            </div>

                                            <div class="mb-3">
                                                <label for="{{ $provider }}-defaultModel" class="form-label">Default Model</label>
                                                <select class="form-select"
                                                        id="{{ $provider }}-defaultModel"
                                                        name="defaultModel">
                                                    <option value="">Select a model</option>
                                                    @foreach($modelsByProvider[$provider] as $modelId => $modelName)
                                                        <option value="{{ $modelId }}"
                                                                {{ $setting->defaultModel === $modelId ? 'selected' : '' }}>
                                                            {{ $modelName }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="mb-3">
                                                        <label for="{{ $provider }}-maxTokens" class="form-label">Max Tokens</label>
                                                        <input type="number"
                                                               class="form-control"
                                                               id="{{ $provider }}-maxTokens"
                                                               name="maxTokens"
                                                               value="{{ $setting->maxTokens }}"
                                                               min="1"
                                                               max="200000">
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="mb-3">
                                                        <label for="{{ $provider }}-temperature" class="form-label">
                                                            Temperature: <span id="{{ $provider }}-temp-value" class="text-primary">{{ number_format($setting->temperature, 1) }}</span>
                                                        </label>
                                                        <input type="range"
                                                               class="form-range"
                                                               id="{{ $provider }}-temperature"
                                                               name="temperature"
                                                               value="{{ $setting->temperature }}"
                                                               min="0"
                                                               max="2"
                                                               step="0.1"
                                                               oninput="document.getElementById('{{ $provider }}-temp-value').textContent = parseFloat(this.value).toFixed(1)">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Bottom Row: Status & Actions -->
                                    <div class="border-top pt-3 mt-2">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center gap-4">
                                                <div class="form-check form-switch">
                                                    <input type="hidden" name="isActive" value="0">
                                                    <input class="form-check-input"
                                                           type="checkbox"
                                                           id="{{ $provider }}-isActive"
                                                           name="isActive"
                                                           value="1"
                                                           {{ $setting->isActive ? 'checked' : '' }}>
                                                    <label class="form-check-label text-dark" for="{{ $provider }}-isActive">
                                                        Enable this provider
                                                    </label>
                                                </div>

                                                <div class="form-check form-switch">
                                                    <input type="hidden" name="visionEnabled" value="0">
                                                    <input class="form-check-input"
                                                           type="checkbox"
                                                           id="{{ $provider }}-visionEnabled"
                                                           name="visionEnabled"
                                                           value="1"
                                                           {{ $setting->visionEnabled ? 'checked' : '' }}>
                                                    <label class="form-check-label text-dark" for="{{ $provider }}-visionEnabled">
                                                        <i class="bx bx-image text-info me-1"></i>Enable Vision
                                                    </label>
                                                </div>

                                                @if(!$setting->isDefault)
                                                    <button type="button"
                                                            class="btn btn-outline-warning btn-sm set-default-btn"
                                                            data-provider="{{ $provider }}">
                                                        <i class="bx bx-star me-1"></i>Set as Default
                                                    </button>
                                                @else
                                                    <span class="text-warning">
                                                        <i class="bx bxs-star me-1"></i>Default Provider
                                                    </span>
                                                @endif
                                            </div>

                                            <button type="submit" class="btn btn-primary">
                                                <i class="bx bx-save me-1"></i>Save {{ explode(' ', $providerLabels[$provider])[0] }} Settings
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                            @endforeach
                        </div>

                        <!-- Tab 2: Access Tags -->
                        <div class="tab-pane" id="accessTags" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div>
                                    <h5 class="text-dark mb-1">
                                        <i class="bx bx-tag me-2"></i>Access Tags
                                    </h5>
                                    <p class="text-secondary mb-0">
                                        Create and manage access tags for AI Technician client access. Each tag defines how long a client's access will last.
                                    </p>
                                </div>
                                <button type="button" class="btn btn-primary" id="addTagBtn">
                                    <i class="bx bx-plus me-1"></i> Add New Tag
                                </button>
                            </div>

                            <div id="tagsList">
                                @if($accessTags->isEmpty())
                                    <div class="empty-state" id="emptyState">
                                        <i class="bx bx-tag"></i>
                                        <h5 class="text-dark">No Access Tags Found</h5>
                                        <p class="text-secondary">Create your first access tag to start managing client access durations.</p>
                                    </div>
                                @else
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="text-dark">Tag Name</th>
                                                    <th class="text-dark">Expiration Length</th>
                                                    <th class="text-dark">Description</th>
                                                    <th class="text-center text-dark" style="width: 120px;">Actions</th>
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
                                                        <td class="text-secondary">
                                                            {{ $tag->description ?? '-' }}
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="btn-group btn-group-sm">
                                                                <button type="button" class="btn btn-outline-primary edit-tag-btn"
                                                                        data-tag-id="{{ $tag->id }}"
                                                                        data-tag-name="{{ $tag->tagName }}"
                                                                        data-tag-expiration="{{ $tag->expirationLength }}"
                                                                        data-tag-description="{{ $tag->description }}"
                                                                        title="Edit">
                                                                    <i class="bx bx-edit"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-outline-danger delete-tag-btn"
                                                                        data-tag-id="{{ $tag->id }}"
                                                                        data-tag-name="{{ $tag->tagName }}"
                                                                        title="Delete">
                                                                    <i class="bx bx-trash"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Tab 3: Image Search -->
                        <div class="tab-pane" id="imageSearch" role="tabpanel">
                            <h5 class="text-dark mb-2">
                                <i class="bx bx-images me-2"></i>Image Search Settings
                            </h5>
                            <p class="text-secondary mb-4">
                                Configure how images are displayed when users request photos in the chat.
                                The system shows a combination of <strong>AI-generated images</strong> and <strong>web search results</strong>.
                            </p>

                            <form id="imageSearchForm">
                                <!-- General Settings -->
                                <div class="card border mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0 text-dark"><i class="bx bx-cog me-2"></i>General Settings</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="imageSearchMaxImages" class="form-label text-dark">Total Images Per Request</label>
                                                    <input type="number" class="form-control" id="imageSearchMaxImages" name="maxImagesPerRequest"
                                                           value="{{ $imageSearchSettings->maxImagesPerRequest ?? 4 }}" min="2" max="6">
                                                    <small class="text-secondary">Total images to show (2-6). Split between AI-generated and web results.</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label text-dark">Feature Status</label>
                                                    <div class="form-check form-switch mt-2">
                                                        <input class="form-check-input" type="checkbox" id="imageSearchEnabled" name="isEnabled"
                                                               {{ $imageSearchSettings->isEnabled ? 'checked' : '' }}>
                                                        <label class="form-check-label text-dark" for="imageSearchEnabled">
                                                            Enable Image Search Feature
                                                        </label>
                                                    </div>
                                                    <small class="text-secondary">When enabled, users can request images in chat.</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- AI Image Generation (Gemini) -->
                                <div class="card border mb-4">
                                    <div class="card-header bg-primary bg-opacity-10">
                                        <h6 class="mb-0 text-dark">
                                            <i class="bx bx-brain me-2"></i>AI Image Generation
                                            <span class="badge bg-success ms-2">Uses Gemini</span>
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info mb-3">
                                            <i class="bx bx-info-circle me-2"></i>
                                            AI-generated images use your existing <strong>Gemini API</strong> configuration. No additional setup needed!
                                        </div>
                                        <ul class="text-dark mb-0" style="list-style: none; padding-left: 0;">
                                            <li class="mb-2"><i class="bx bx-check text-success me-2"></i>Generates custom illustrations based on the AI response</li>
                                            <li class="mb-2"><i class="bx bx-check text-success me-2"></i>Great for educational diagrams and visual explanations</li>
                                            <li><i class="bx bx-check text-success me-2"></i>No text/labels in images (AI can't render readable text)</li>
                                        </ul>
                                    </div>
                                </div>

                                <!-- Web Image Search (Serper) -->
                                <div class="card border mb-4">
                                    <div class="card-header bg-warning bg-opacity-10">
                                        <h6 class="mb-0 text-dark">
                                            <i class="bx bx-search me-2"></i>Web Image Search
                                            <span class="badge bg-warning text-dark ms-2">Serper API</span>
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-secondary mb-3">
                                            Search for real photos from Google Images using Serper API.
                                            <a href="https://serper.dev" target="_blank" class="text-primary">Get your API key at serper.dev</a>
                                        </p>

                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="mb-3">
                                                    <label for="serperApiKey" class="form-label text-dark">
                                                        Serper API Key
                                                        @if($imageSearchSettings->apiKey)
                                                            <span class="badge bg-success ms-2">Configured</span>
                                                        @else
                                                            <span class="badge bg-secondary ms-2">Not Set</span>
                                                        @endif
                                                    </label>
                                                    <div class="input-group">
                                                        <input type="password" class="form-control" id="serperApiKey" name="apiKey"
                                                               placeholder="{{ $imageSearchSettings->apiKey ? '••••••••••••' : 'Enter your Serper API key' }}"
                                                               autocomplete="off">
                                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('serperApiKey', this)">
                                                            <i class="bx bx-show"></i>
                                                        </button>
                                                    </div>
                                                    <small class="text-secondary">
                                                        @if($imageSearchSettings->maskedApiKey)
                                                            Current: {{ $imageSearchSettings->maskedApiKey }} • Leave empty to keep existing key
                                                        @else
                                                            Sign up at <a href="https://serper.dev" target="_blank">serper.dev</a> to get your free API key ($50 credit = ~50,000 searches)
                                                        @endif
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label text-dark">&nbsp;</label>
                                                    <div>
                                                        <button type="button" class="btn btn-outline-info w-100" id="testSerperBtn">
                                                            <i class="bx bx-test-tube me-1"></i> Test Serper
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div id="serperTestResult" class="mt-2" style="display: none;">
                                            <div class="alert alert-success py-2" id="serperTestSuccess" style="display: none;">
                                                <i class="bx bx-check-circle me-2"></i><span id="serperTestSuccessMsg"></span>
                                            </div>
                                            <div class="alert alert-danger py-2" id="serperTestError" style="display: none;">
                                                <i class="bx bx-error-circle me-2"></i><span id="serperTestErrorMsg"></span>
                                            </div>
                                        </div>

                                        <div class="alert alert-light border mt-3 mb-0">
                                            <small class="text-dark">
                                                <i class="bx bx-bulb me-1"></i>
                                                <strong>Without Serper:</strong> Only AI-generated images will be shown.
                                                <strong>With Serper:</strong> Mix of AI images + real photos from Google.
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <input type="hidden" id="imageSearchProvider" name="provider" value="serper">

                                <hr class="my-4">

                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <button type="submit" class="btn btn-primary me-2" id="saveImageSearchBtn">
                                            <i class="bx bx-save me-1"></i> Save Settings
                                        </button>
                                        <button type="button" class="btn btn-outline-info" id="testImageSearchBtn">
                                            <i class="bx bx-test-tube me-1"></i> Test AI Image Generation
                                        </button>
                                    </div>
                                </div>

                                <div id="imageSearchTestResult" class="mt-3" style="display: none;">
                                    <div class="alert alert-success" id="imageSearchTestSuccess" style="display: none;">
                                        <i class="bx bx-check-circle me-2"></i>
                                        <span id="imageSearchTestSuccessMsg"></span>
                                    </div>
                                    <div class="alert alert-danger" id="imageSearchTestError" style="display: none;">
                                        <i class="bx bx-error-circle me-2"></i>
                                        <span id="imageSearchTestErrorMsg"></span>
                                    </div>
                                </div>
                            </form>

                            <div class="alert alert-light mt-4 border">
                                <h6 class="alert-heading text-dark"><i class="bx bx-info-circle me-2"></i>How It Works</h6>
                                <hr>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="text-dark mb-2"><strong><span class="badge bg-primary me-1">AI</span> AI-Generated Images:</strong></p>
                                        <ul class="text-dark mb-3">
                                            <li>Uses Gemini to create custom illustrations</li>
                                            <li>Based on the actual AI response content</li>
                                            <li>No text/labels (AI can't render readable text)</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="text-dark mb-2"><strong><span class="badge bg-success me-1">Web</span> Web Search Images:</strong></p>
                                        <ul class="text-dark mb-3">
                                            <li>Real photos from Google Images via Serper</li>
                                            <li>Downloaded and saved locally for persistence</li>
                                            <li>Shows source attribution in lightbox</li>
                                        </ul>
                                    </div>
                                </div>
                                <hr>
                                <p class="text-secondary mb-0"><small><i class="bx bx-bulb me-1"></i><strong>Tip:</strong> Users see a mix of both types, marked with "AI" or "Web" badges.</small></p>
                            </div>
                        </div>

                        <!-- Tab 4: Currency Settings -->
                        <div class="tab-pane" id="currencySettings" role="tabpanel">
                            <h5 class="text-dark mb-2">
                                <i class="bx bx-money me-2"></i>Currency Settings
                            </h5>
                            <p class="text-secondary mb-4">
                                Configure the USD to PHP exchange rate for displaying API costs in Philippine Peso.
                            </p>

                            <div class="row">
                                <div class="col-lg-8">
                                    <div class="provider-card">
                                        <div class="provider-header" style="background-color: #28a74515;">
                                            <div class="provider-header-left">
                                                <div class="provider-icon" style="background-color: #28a745">
                                                    <i class="bx bx-transfer"></i>
                                                </div>
                                                <div>
                                                    <h5 class="provider-title">Exchange Rate</h5>
                                                    <p class="provider-subtitle">USD to PHP conversion rate</p>
                                                </div>
                                            </div>
                                            <div class="provider-actions">
                                                <span class="badge bg-light text-dark" id="currencyLastUpdate">
                                                    <i class="bx bx-time me-1"></i>{{ $currencySettings->last_update_ago }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="provider-body">
                                            <form id="currencySettingsForm">
                                                @csrf
                                                <div class="row align-items-end">
                                                    <div class="col-md-4">
                                                        <label for="usdToPhpRate" class="form-label">
                                                            USD to PHP Rate
                                                        </label>
                                                        <div class="input-group">
                                                            <span class="input-group-text">$1 =</span>
                                                            <input type="number"
                                                                   class="form-control"
                                                                   id="usdToPhpRate"
                                                                   name="usdToPhpRate"
                                                                   value="{{ $currencySettings->usdToPhpRate }}"
                                                                   step="0.0001"
                                                                   min="1"
                                                                   max="1000">
                                                            <span class="input-group-text">PHP</span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-check form-switch mb-0">
                                                            <input class="form-check-input" type="checkbox" id="autoUpdateRate"
                                                                   name="autoUpdate" {{ $currencySettings->autoUpdate ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="autoUpdateRate">
                                                                Auto-update daily
                                                            </label>
                                                        </div>
                                                        <small class="text-secondary">Updates automatically every 24 hours</small>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <button type="button" class="btn btn-outline-success me-2" id="refreshRateBtn">
                                                            <i class="bx bx-refresh me-1"></i>Refresh Now
                                                        </button>
                                                        <button type="submit" class="btn btn-primary" id="saveCurrencyBtn">
                                                            <i class="bx bx-save me-1"></i>Save
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>

                                            <hr class="my-4">

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h6 class="text-dark mb-3"><i class="bx bx-info-circle me-1"></i>Current Rate Preview</h6>
                                                    <table class="table table-sm table-borderless mb-0">
                                                        <tbody>
                                                            <tr>
                                                                <td class="text-secondary">$0.01 USD</td>
                                                                <td class="text-dark fw-medium" id="previewSmall">₱{{ number_format(0.01 * $currencySettings->usdToPhpRate, 4) }}</td>
                                                            </tr>
                                                            <tr>
                                                                <td class="text-secondary">$0.10 USD</td>
                                                                <td class="text-dark fw-medium" id="previewMedium">₱{{ number_format(0.10 * $currencySettings->usdToPhpRate, 4) }}</td>
                                                            </tr>
                                                            <tr>
                                                                <td class="text-secondary">$1.00 USD</td>
                                                                <td class="text-dark fw-medium" id="previewLarge">₱{{ number_format(1.00 * $currencySettings->usdToPhpRate, 2) }}</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6 class="text-dark mb-3"><i class="bx bx-globe me-1"></i>Data Source</h6>
                                                    <p class="text-secondary mb-1">
                                                        <strong>API:</strong> <a href="https://www.exchangerate-api.com" target="_blank">ExchangeRate-API</a>
                                                    </p>
                                                    <p class="text-secondary mb-0">
                                                        <small>Free tier, no API key required. Updates daily.</small>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="alert alert-light border">
                                        <h6 class="alert-heading text-dark"><i class="bx bx-bulb text-warning me-2"></i>How It Works</h6>
                                        <p class="text-dark mb-2">The exchange rate is used to display estimated API costs in Philippine Peso (₱) instead of US Dollars ($) in the AI Flow Log modal.</p>
                                        <ul class="text-secondary mb-0">
                                            <li>Token costs from AI providers (Claude, OpenAI, Gemini) are calculated in USD</li>
                                            <li>The rate is applied to convert all costs to PHP for easier understanding</li>
                                            <li>Enable auto-update to keep the rate current without manual intervention</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 5: Avatar Settings -->
                        <div class="tab-pane" id="avatarSettings" role="tabpanel">
                            <h5 class="text-dark mb-2">
                                <i class="bx bx-user-circle me-2"></i>Chat Avatar Settings
                            </h5>
                            <p class="text-secondary mb-4">
                                Customize the avatar and display name that appears for AI responses in the chat interface.
                            </p>

                            <div class="row">
                                <div class="col-lg-8">
                                    <div class="provider-card">
                                        <div class="provider-header" style="background-color: #6f42c115;">
                                            <div class="provider-header-left">
                                                <div class="provider-icon" style="background-color: #6f42c1">
                                                    <i class="bx bx-user-circle"></i>
                                                </div>
                                                <div>
                                                    <h5 class="provider-title">AI Avatar</h5>
                                                    <p class="provider-subtitle">Customize how the AI appears in chat</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="provider-body">
                                            <form id="avatarSettingsForm" enctype="multipart/form-data">
                                                @csrf
                                                <div class="row">
                                                    <!-- Avatar Preview Column -->
                                                    <div class="col-md-4 text-center mb-4">
                                                        <div class="mb-3">
                                                            <div class="avatar-preview-container position-relative d-inline-block">
                                                                <img src="{{ $avatarSettings->avatar_url }}"
                                                                     id="avatarPreview"
                                                                     class="rounded-circle border shadow-sm"
                                                                     style="width: 120px; height: 120px; object-fit: cover;"
                                                                     alt="AI Avatar">
                                                                @if($avatarSettings->hasCustomAvatar())
                                                                    <button type="button" class="btn btn-danger btn-sm position-absolute d-flex align-items-center justify-content-center"
                                                                            style="top: -5px; right: -5px; border-radius: 50%; width: 28px; height: 28px; padding: 0; line-height: 1;"
                                                                            id="removeAvatarBtn" title="Remove custom avatar">
                                                                        <i class="bx bx-x" style="font-size: 18px;"></i>
                                                                    </button>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <p class="text-dark mb-1 fw-medium" id="avatarDisplayNamePreview">{{ $avatarSettings->displayName }}</p>
                                                        <span class="badge {{ $avatarSettings->useCustomAvatar ? 'bg-success' : 'bg-secondary' }} d-inline-flex align-items-center" id="avatarTypeBadge" style="line-height: 1.2;">
                                                            {{ $avatarSettings->useCustomAvatar ? 'Custom Avatar' : 'Default Avatar' }}
                                                        </span>
                                                    </div>

                                                    <!-- Settings Column -->
                                                    <div class="col-md-8">
                                                        <div class="mb-3">
                                                            <label for="avatarDisplayName" class="form-label text-dark">
                                                                Display Name
                                                            </label>
                                                            <input type="text"
                                                                   class="form-control"
                                                                   id="avatarDisplayName"
                                                                   name="displayName"
                                                                   value="{{ $avatarSettings->displayName }}"
                                                                   placeholder="e.g., AI Technician"
                                                                   maxlength="100">
                                                            <small class="text-secondary">The name shown alongside AI responses in chat</small>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="avatarFile" class="form-label text-dark">
                                                                Upload Avatar Image
                                                            </label>
                                                            <input type="file"
                                                                   class="form-control"
                                                                   id="avatarFile"
                                                                   name="avatar"
                                                                   accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                                                            <small class="text-secondary">
                                                                Accepts JPEG, PNG, GIF, WebP. Max 2MB. Recommended: 200x200px square image.
                                                            </small>
                                                        </div>

                                                        @if($avatarSettings->hasCustomAvatar())
                                                            <div class="mb-3">
                                                                <div class="form-check form-switch">
                                                                    <input class="form-check-input" type="checkbox"
                                                                           id="useCustomAvatar" name="useCustomAvatar"
                                                                           {{ $avatarSettings->useCustomAvatar ? 'checked' : '' }}>
                                                                    <label class="form-check-label text-dark" for="useCustomAvatar">
                                                                        Use custom avatar
                                                                    </label>
                                                                </div>
                                                                <small class="text-secondary">Toggle off to use the default avatar</small>
                                                            </div>
                                                        @endif

                                                        <hr class="my-4">

                                                        <button type="submit" class="btn btn-primary" id="saveAvatarBtn">
                                                            <i class="bx bx-save me-1"></i>Save Avatar Settings
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>

                                    <div class="alert alert-light border">
                                        <h6 class="alert-heading text-dark"><i class="bx bx-info-circle text-primary me-2"></i>About Chat Avatar</h6>
                                        <ul class="text-secondary mb-0">
                                            <li>The avatar appears next to AI responses in the chat interface</li>
                                            <li>Upload a custom image to personalize the AI technician</li>
                                            <li>Square images (1:1 ratio) work best for circular avatars</li>
                                            <li>If no custom avatar is set, a default icon will be used</li>
                                        </ul>
                                    </div>
                                </div>

                                <!-- Preview Column -->
                                <div class="col-lg-4">
                                    <div class="card border">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0 text-dark"><i class="bx bx-show me-2"></i>Chat Preview</h6>
                                        </div>
                                        <div class="card-body" style="background: #f8f9fa;">
                                            <div class="d-flex mb-3">
                                                <img src="{{ $avatarSettings->avatar_url }}"
                                                     id="chatPreviewAvatar"
                                                     class="rounded-circle me-2 flex-shrink-0"
                                                     style="width: 36px; height: 36px; object-fit: cover;"
                                                     alt="AI">
                                                <div class="bg-white rounded p-2 shadow-sm" style="max-width: 85%;">
                                                    <p class="mb-1 text-dark small">Magandang araw po! Paano ko po kayo matutulungan ngayon?</p>
                                                    <small class="text-secondary" style="font-size: 10px;">10:30 AM</small>
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-end mb-3">
                                                <div class="bg-primary text-white rounded p-2" style="max-width: 85%;">
                                                    <p class="mb-1 small">Ano po ang gamot sa uod sa mais?</p>
                                                    <small class="opacity-75" style="font-size: 10px;">10:31 AM</small>
                                                </div>
                                            </div>
                                            <div class="d-flex">
                                                <img src="{{ $avatarSettings->avatar_url }}"
                                                     id="chatPreviewAvatar2"
                                                     class="rounded-circle me-2 flex-shrink-0"
                                                     style="width: 36px; height: 36px; object-fit: cover;"
                                                     alt="AI">
                                                <div class="bg-white rounded p-2 shadow-sm" style="max-width: 85%;">
                                                    <p class="mb-1 text-dark small">Para sa uod sa mais, pwede po kayong gumamit ng...</p>
                                                    <small class="text-secondary" style="font-size: 10px;">10:32 AM</small>
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
    </div>

    <!-- Add/Edit Tag Modal -->
    <div class="modal fade" id="tagModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark" id="tagModalTitle">
                        <i class="bx bx-tag text-primary me-2"></i>Add Access Tag
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="tagForm">
                        <input type="hidden" id="tagId" name="tagId">
                        <div class="mb-3">
                            <label for="tagName" class="form-label text-dark">Tag Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="tagName" name="tagName" placeholder="e.g. Premium Access, Monthly Plan" required>
                        </div>
                        <div class="mb-3">
                            <label for="expirationLength" class="form-label text-dark">Expiration Length (Days) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="expirationLength" name="expirationLength" min="1" max="3650" value="30" required>
                            <small class="text-secondary">Number of days access will last when this tag is assigned.</small>
                        </div>
                        <div class="mb-3">
                            <label for="tagDescription" class="form-label text-dark">Description</label>
                            <textarea class="form-control" id="tagDescription" name="description" rows="2" placeholder="Optional description..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveTagBtn">
                        <i class="bx bx-save me-1"></i> Save Tag
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Tag Confirmation Modal -->
    <div class="modal fade" id="deleteTagModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark">
                        <i class="bx bx-trash text-danger me-2"></i>Delete Access Tag
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-dark">Are you sure you want to delete the tag <strong id="deleteTagName"></strong>?</p>
                    <p class="text-secondary mb-0">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteTagBtn">
                        <i class="bx bx-trash me-1"></i> Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
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

            // Handle tab activation from URL hash
            if (window.location.hash) {
                const hash = window.location.hash;
                const tabLink = $('a[href="' + hash + '"]');
                if (tabLink.length) {
                    // Deactivate all tabs and panes
                    $('.nav-link').removeClass('active');
                    $('.tab-pane').removeClass('active show');
                    // Activate the target tab
                    tabLink.addClass('active');
                    $(hash).addClass('active show');
                }
            }

            // Update URL hash when tab is clicked
            $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                window.location.hash = $(e.target).attr('href');
            });

            // Toggle password visibility
            $('.toggle-password').on('click', function() {
                const $input = $(this).siblings('input');
                const type = $input.attr('type') === 'password' ? 'text' : 'password';
                $input.attr('type', type);
                $(this).find('i').toggleClass('bx-show bx-hide');
            });

            // Handle form submission
            $('.provider-form').on('submit', function(e) {
                e.preventDefault();

                const $form = $(this);
                const provider = $form.data('provider');
                const $submitBtn = $form.find('button[type="submit"]');
                const originalText = $submitBtn.html();

                $submitBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...');

                $.ajax({
                    url: '/ai-technician-settings/' + provider,
                    type: 'PUT',
                    data: $form.serialize(),
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message, 'Success!');

                            // Update status badge
                            if (response.data && response.data.statusBadge) {
                                $('#' + provider + '-status-badge').html(response.data.statusBadge);
                            }

                            // Clear password field after save
                            $form.find('input[name="apiKey"]').val('');

                            // Update masked key display if API key was saved
                            if (response.data && response.data.hasApiKey) {
                                $form.find('.masked-key').text(response.data.maskedApiKey);
                                $form.closest('.provider-card').find('.test-connection-btn').prop('disabled', false);
                            }
                        } else {
                            toastr.error(response.message, 'Error!');
                        }
                    },
                    error: function(xhr) {
                        const response = xhr.responseJSON;
                        if (response && response.errors) {
                            Object.values(response.errors).forEach(function(errors) {
                                errors.forEach(function(error) {
                                    toastr.error(error, 'Validation Error');
                                });
                            });
                        } else {
                            toastr.error(response?.message || 'An error occurred. Please try again.', 'Error!');
                        }
                    },
                    complete: function() {
                        $submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });

            // Handle test connection
            $('.test-connection-btn').on('click', function() {
                const $btn = $(this);
                const provider = $btn.data('provider');
                const originalText = $btn.html();

                $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');

                $.ajax({
                    url: '/ai-technician-settings/' + provider + '/test',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message, 'Success!');

                            if (response.data && response.data.statusBadge) {
                                $('#' + provider + '-status-badge').html(response.data.statusBadge);
                            }
                        } else {
                            toastr.error(response.message, 'Connection Failed');

                            if (response.data && response.data.statusBadge) {
                                $('#' + provider + '-status-badge').html(response.data.statusBadge);
                            }
                        }
                    },
                    error: function(xhr) {
                        const response = xhr.responseJSON;
                        toastr.error(response?.message || 'Connection test failed.', 'Error!');

                        if (response && response.data && response.data.statusBadge) {
                            $('#' + provider + '-status-badge').html(response.data.statusBadge);
                        }
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html(originalText);
                    }
                });
            });

            // Handle set as default
            $('.set-default-btn').on('click', function() {
                const $btn = $(this);
                const provider = $btn.data('provider');
                const originalText = $btn.html();

                $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Setting...');

                $.ajax({
                    url: '/ai-technician-settings/' + provider + '/default',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message, 'Success!');
                            // Reload to update all default indicators
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            toastr.error(response.message, 'Error!');
                            $btn.prop('disabled', false).html(originalText);
                        }
                    },
                    error: function(xhr) {
                        const response = xhr.responseJSON;
                        toastr.error(response?.message || 'Failed to set default provider.', 'Error!');
                        $btn.prop('disabled', false).html(originalText);
                    }
                });
            });

            // ==================== ACCESS TAGS ====================

            let tagToDelete = null;
            let isEditing = false;

            // Add new tag button
            $('#addTagBtn').on('click', function() {
                isEditing = false;
                $('#tagModalTitle').html('<i class="bx bx-tag text-primary me-2"></i>Add Access Tag');
                $('#tagForm')[0].reset();
                $('#tagId').val('');
                $('#expirationLength').val(30);
                $('#tagModal').modal('show');
            });

            // Edit tag button
            $(document).on('click', '.edit-tag-btn', function() {
                isEditing = true;
                const $btn = $(this);
                $('#tagModalTitle').html('<i class="bx bx-edit text-primary me-2"></i>Edit Access Tag');
                $('#tagId').val($btn.data('tag-id'));
                $('#tagName').val($btn.data('tag-name'));
                $('#expirationLength').val($btn.data('tag-expiration'));
                $('#tagDescription').val($btn.data('tag-description') || '');
                $('#tagModal').modal('show');
            });

            // Save tag
            $('#saveTagBtn').on('click', function() {
                const tagName = $('#tagName').val().trim();
                const expirationLength = $('#expirationLength').val();
                const description = $('#tagDescription').val().trim();
                const tagId = $('#tagId').val();

                if (!tagName) {
                    toastr.error('Tag name is required.', 'Error');
                    return;
                }

                if (!expirationLength || expirationLength < 1) {
                    toastr.error('Expiration length must be at least 1 day.', 'Error');
                    return;
                }

                const $btn = $(this);
                $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Saving...');

                const url = isEditing
                    ? '/ai-technician-settings/access-tags/' + tagId
                    : '/ai-technician-settings/access-tags';
                const method = isEditing ? 'PUT' : 'POST';

                $.ajax({
                    url: url,
                    type: method,
                    data: {
                        _token: '{{ csrf_token() }}',
                        tagName: tagName,
                        expirationLength: expirationLength,
                        description: description
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message, 'Success');
                            $('#tagModal').modal('hide');
                            // Reload while staying on Access Tags tab
                            window.location.href = window.location.pathname + '#accessTags';
                            window.location.reload();
                        } else {
                            toastr.error(response.message, 'Error');
                        }
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON?.message || 'Failed to save tag.', 'Error');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save Tag');
                    }
                });
            });

            // Delete tag button
            $(document).on('click', '.delete-tag-btn', function() {
                tagToDelete = {
                    id: $(this).data('tag-id'),
                    name: $(this).data('tag-name')
                };
                $('#deleteTagName').text(tagToDelete.name);
                $('#deleteTagModal').modal('show');
            });

            // Confirm delete
            $('#confirmDeleteTagBtn').on('click', function() {
                if (!tagToDelete) return;

                const $btn = $(this);
                $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Deleting...');

                $.ajax({
                    url: '/ai-technician-settings/access-tags/' + tagToDelete.id,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message, 'Success');
                            $('#deleteTagModal').modal('hide');

                            // Remove the row from the table
                            $('tr[data-tag-id="' + tagToDelete.id + '"]').fadeOut(400, function() {
                                $(this).remove();

                                // Check if table is empty
                                if ($('tbody tr').length === 0) {
                                    $('#tagsList').html(`
                                        <div class="empty-state" id="emptyState">
                                            <i class="bx bx-tag"></i>
                                            <h5 class="text-dark">No Access Tags Found</h5>
                                            <p class="text-secondary">Create your first access tag to start managing client access durations.</p>
                                        </div>
                                    `);
                                }
                            });
                        } else {
                            toastr.error(response.message, 'Error');
                        }
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON?.message || 'Failed to delete tag.', 'Error');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html('<i class="bx bx-trash me-1"></i> Delete');
                        tagToDelete = null;
                    }
                });
            });

            // ==================== IMAGE SEARCH SETTINGS ====================

            // Save image search settings (with Serper API key)
            $('#imageSearchForm').on('submit', function(e) {
                e.preventDefault();

                const $btn = $('#saveImageSearchBtn');
                $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Saving...');

                $.ajax({
                    url: '/ai-technician-settings/image-search',
                    type: 'PUT',
                    data: {
                        _token: '{{ csrf_token() }}',
                        provider: 'serper',
                        apiKey: $('#serperApiKey').val(),
                        maxImagesPerRequest: $('#imageSearchMaxImages').val(),
                        isEnabled: $('#imageSearchEnabled').is(':checked') ? 1 : 0
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message, 'Success');
                            // Clear the API key field and update the placeholder
                            if ($('#serperApiKey').val()) {
                                $('#serperApiKey').val('').attr('placeholder', '••••••••••••');
                            }
                        } else {
                            toastr.error(response.message, 'Error');
                        }
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON?.message || 'Failed to save settings.', 'Error');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save Settings');
                    }
                });
            });

            // Test Serper API connection
            $('#testSerperBtn').on('click', function() {
                const $btn = $(this);
                $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Testing...');

                $('#serperTestResult').hide();
                $('#serperTestSuccess').hide();
                $('#serperTestError').hide();

                // Use the API key from the input if provided, otherwise test existing key
                const apiKey = $('#serperApiKey').val();

                $.ajax({
                    url: '/ai-technician-settings/image-search/test-serper',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        apiKey: apiKey
                    },
                    success: function(response) {
                        $('#serperTestResult').show();
                        if (response.success) {
                            $('#serperTestSuccessMsg').text(response.message);
                            $('#serperTestSuccess').show();
                        } else {
                            $('#serperTestErrorMsg').text(response.message);
                            $('#serperTestError').show();
                        }
                    },
                    error: function(xhr) {
                        $('#serperTestResult').show();
                        $('#serperTestErrorMsg').text(xhr.responseJSON?.message || 'Serper API test failed.');
                        $('#serperTestError').show();
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html('<i class="bx bx-test-tube me-1"></i> Test Serper');
                    }
                });
            });

            // Test Gemini connection for image generation
            $('#testImageSearchBtn').on('click', function() {
                const $btn = $(this);
                $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Testing...');

                $('#imageSearchTestResult').hide();
                $('#imageSearchTestSuccess').hide();
                $('#imageSearchTestError').hide();

                $.ajax({
                    url: '/ai-technician-settings/image-search/test',
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        $('#imageSearchTestResult').show();
                        if (response.success) {
                            $('#imageSearchTestSuccessMsg').text(response.message);
                            $('#imageSearchTestSuccess').show();
                        } else {
                            $('#imageSearchTestErrorMsg').text(response.message);
                            $('#imageSearchTestError').show();
                        }
                    },
                    error: function(xhr) {
                        $('#imageSearchTestResult').show();
                        $('#imageSearchTestErrorMsg').text(xhr.responseJSON?.message || 'Gemini connection test failed.');
                        $('#imageSearchTestError').show();
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html('<i class="bx bx-test-tube me-1"></i> Test Gemini Connection');
                    }
                });
            });

            // Toggle API key visibility helper
            window.toggleApiKeyVisibility = function(inputId) {
                const $input = $('#' + inputId);
                const $icon = $input.next('button').find('i');
                if ($input.attr('type') === 'password') {
                    $input.attr('type', 'text');
                    $icon.removeClass('bx-show').addClass('bx-hide');
                } else {
                    $input.attr('type', 'password');
                    $icon.removeClass('bx-hide').addClass('bx-show');
                }
            };

            // Currency Settings - Update preview when rate changes
            $('#usdToPhpRate').on('input', function() {
                const rate = parseFloat($(this).val()) || 56;
                $('#previewSmall').text('₱' + (0.01 * rate).toFixed(4));
                $('#previewMedium').text('₱' + (0.10 * rate).toFixed(4));
                $('#previewLarge').text('₱' + (1.00 * rate).toFixed(2));
            });

            // Currency Settings - Save form
            $('#currencySettingsForm').on('submit', function(e) {
                e.preventDefault();

                const $btn = $('#saveCurrencyBtn');
                const originalHtml = $btn.html();
                $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...');

                $.ajax({
                    url: '/ai-technician-settings/currency',
                    type: 'PUT',
                    data: {
                        _token: '{{ csrf_token() }}',
                        usdToPhpRate: $('#usdToPhpRate').val(),
                        autoUpdate: $('#autoUpdateRate').is(':checked') ? 1 : 0
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message, 'Success!');
                        } else {
                            toastr.error(response.message, 'Error!');
                        }
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON?.message || 'Failed to save currency settings.', 'Error!');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html(originalHtml);
                    }
                });
            });

            // Currency Settings - Refresh rate from API
            $('#refreshRateBtn').on('click', function() {
                const $btn = $(this);
                const originalHtml = $btn.html();
                $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Refreshing...');

                $.ajax({
                    url: '/ai-technician-settings/currency/refresh',
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message, 'Success!');
                            // Update the rate input and preview
                            $('#usdToPhpRate').val(response.data.usdToPhpRate).trigger('input');
                            $('#currencyLastUpdate').html('<i class="bx bx-time me-1"></i>' + response.data.lastUpdateAgo);
                        } else {
                            toastr.error(response.message, 'Error!');
                        }
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON?.message || 'Failed to refresh exchange rate.', 'Error!');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html(originalHtml);
                    }
                });
            });

            // ==================== AVATAR SETTINGS ====================

            // Preview avatar image when selected
            $('#avatarFile').on('change', function() {
                const file = this.files[0];
                if (file) {
                    // Validate file size (2MB max)
                    if (file.size > 2 * 1024 * 1024) {
                        toastr.error('Image size must not exceed 2MB.', 'Error');
                        this.value = '';
                        return;
                    }

                    // Validate file type
                    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                    if (!allowedTypes.includes(file.type)) {
                        toastr.error('Please select a valid image file (JPEG, PNG, GIF, or WebP).', 'Error');
                        this.value = '';
                        return;
                    }

                    // Preview the image
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#avatarPreview').attr('src', e.target.result);
                        $('#chatPreviewAvatar, #chatPreviewAvatar2').attr('src', e.target.result);
                    };
                    reader.readAsDataURL(file);
                }
            });

            // Update display name preview
            $('#avatarDisplayName').on('input', function() {
                const name = $(this).val() || 'AI Technician';
                $('#avatarDisplayNamePreview').text(name);
            });

            // Save avatar settings
            $('#avatarSettingsForm').on('submit', function(e) {
                e.preventDefault();

                const $btn = $('#saveAvatarBtn');
                const originalHtml = $btn.html();
                $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...');

                const formData = new FormData(this);

                // Add useCustomAvatar checkbox value
                if ($('#useCustomAvatar').length) {
                    formData.set('useCustomAvatar', $('#useCustomAvatar').is(':checked') ? 1 : 0);
                }

                $.ajax({
                    url: '/ai-technician-settings/avatar',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message, 'Success!');

                            // Update preview images
                            if (response.data.avatarUrl) {
                                $('#avatarPreview').attr('src', response.data.avatarUrl);
                                $('#chatPreviewAvatar, #chatPreviewAvatar2').attr('src', response.data.avatarUrl);
                            }

                            // Update badge
                            if (response.data.useCustomAvatar) {
                                $('#avatarTypeBadge').removeClass('bg-secondary').addClass('bg-success').text('Custom Avatar');
                            } else {
                                $('#avatarTypeBadge').removeClass('bg-success').addClass('bg-secondary').text('Default Avatar');
                            }

                            // Clear file input
                            $('#avatarFile').val('');

                            // Reload to show/hide remove button if needed
                            if (response.data.hasCustomAvatar && !$('#removeAvatarBtn').length) {
                                location.reload();
                            }
                        } else {
                            toastr.error(response.message, 'Error!');
                        }
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON?.message || 'Failed to save avatar settings.', 'Error!');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html(originalHtml);
                    }
                });
            });

            // Remove custom avatar
            $(document).on('click', '#removeAvatarBtn', function() {
                if (!confirm('Are you sure you want to remove the custom avatar?')) {
                    return;
                }

                const $btn = $(this);
                $btn.prop('disabled', true);

                $.ajax({
                    url: '/ai-technician-settings/avatar',
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message, 'Success!');

                            // Update preview images to default
                            $('#avatarPreview').attr('src', response.data.avatarUrl);
                            $('#chatPreviewAvatar, #chatPreviewAvatar2').attr('src', response.data.avatarUrl);

                            // Update badge
                            $('#avatarTypeBadge').removeClass('bg-success').addClass('bg-secondary').text('Default Avatar');

                            // Remove the remove button
                            $btn.remove();

                            // Remove the toggle if it exists
                            $('#useCustomAvatar').closest('.mb-3').remove();
                        } else {
                            toastr.error(response.message, 'Error!');
                        }
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON?.message || 'Failed to remove avatar.', 'Error!');
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                    }
                });
            });

            // Toggle useCustomAvatar checkbox
            $('#useCustomAvatar').on('change', function() {
                // Immediate visual feedback - will be saved when form is submitted
            });
        });
    </script>
@endsection
