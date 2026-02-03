@extends('layouts.master')

@section('title') AI Websites @endsection

@section('css')
<link rel="stylesheet" href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}">
<style>
    .website-card {
        border-left: 4px solid #556ee6;
        transition: all 0.2s ease;
    }
    .website-card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
    }
    .website-card.inactive {
        border-left-color: #74788d;
        opacity: 0.7;
    }
    .website-actions {
        opacity: 0;
        transition: opacity 0.2s ease;
    }
    .website-card:hover .website-actions {
        opacity: 1;
    }
    .website-url {
        font-family: monospace;
        font-size: 0.85rem;
        color: #556ee6;
        word-break: break-all;
    }
    .priority-badge {
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background-color: #556ee6;
        color: white;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .empty-state {
        padding: 80px 20px;
        text-align: center;
        background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
        border-radius: 8px;
    }
    .empty-state-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background-color: #e8f0fe;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 24px;
    }
    .empty-state-icon i {
        font-size: 2.5rem;
        color: #556ee6;
    }
    .scrape-info {
        background-color: #f8f9fa;
        border-radius: 4px;
        padding: 8px 12px;
        font-size: 0.85rem;
    }
    .field-help {
        background-color: #e8f4fd;
        border-left: 3px solid #556ee6;
        padding: 10px 12px;
        margin-bottom: 15px;
        border-radius: 0 4px 4px 0;
    }
    /* Scrape Progress Styles */
    .scrape-progress-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .scrape-progress-card {
        background: white;
        border-radius: 12px;
        padding: 30px 40px;
        min-width: 400px;
        max-width: 500px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        text-align: center;
    }
    .scrape-spinner {
        width: 60px;
        height: 60px;
        border: 4px solid #e9ecef;
        border-top: 4px solid #556ee6;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 20px;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    .scrape-progress-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #495057;
        margin-bottom: 10px;
    }
    .scrape-progress-status {
        color: #6c757d;
        margin-bottom: 15px;
    }
    .scrape-progress-bar {
        height: 8px;
        background: #e9ecef;
        border-radius: 4px;
        overflow: hidden;
        margin-bottom: 10px;
    }
    .scrape-progress-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #556ee6, #34c38f);
        border-radius: 4px;
        transition: width 0.3s ease;
        animation: progressPulse 1.5s ease-in-out infinite;
    }
    @keyframes progressPulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }
    .scrape-progress-details {
        font-size: 0.85rem;
        color: #868e96;
    }
    .scrape-elapsed-time {
        font-family: monospace;
        font-size: 0.9rem;
        color: #556ee6;
        margin-top: 10px;
    }
    .scrape-log {
        max-height: 150px;
        overflow-y: auto;
        background: #f8f9fa;
        border-radius: 6px;
        padding: 10px;
        margin-top: 15px;
        text-align: left;
        font-size: 0.8rem;
        font-family: monospace;
    }
    .scrape-log-entry {
        padding: 3px 0;
        border-bottom: 1px solid #e9ecef;
        color: #495057;
    }
    .scrape-log-entry:last-child {
        border-bottom: none;
    }
    .scrape-log-entry.success { color: #34c38f; }
    .scrape-log-entry.error { color: #f46a6a; }
    .scrape-log-entry.info { color: #556ee6; }
    /* Settings Tab Styles */
    .api-key-input {
        font-family: monospace;
        letter-spacing: 0.5px;
    }
    .nav-tabs-custom .nav-link {
        font-weight: 500;
    }
    .settings-card {
        border: none;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') AI Technician @endslot
        @slot('title') Websites @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card settings-card">
                <div class="card-body">
                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs nav-tabs-custom nav-justified" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#websitesTab" role="tab">
                                <i class="bx bx-globe me-1"></i>
                                <span class="d-none d-sm-inline">Websites</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#settingsTab" role="tab">
                                <i class="bx bx-cog me-1"></i>
                                <span class="d-none d-sm-inline">Settings</span>
                            </a>
                        </li>
                    </ul>

                    <!-- Tab panes -->
                    <div class="tab-content p-3 pt-4">
                        <!-- Websites Tab -->
                        <div class="tab-pane active" id="websitesTab" role="tabpanel">
                            <!-- Header -->
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div>
                                    <h5 class="mb-1 text-dark">Priority Websites</h5>
                                    <p class="text-secondary mb-0">Add websites that the AI can check or scrape for additional information.</p>
                                </div>
                                <button type="button" class="btn btn-primary" id="btnAddWebsite">
                                    <i class="bx bx-plus me-1"></i> Add Website
                                </button>
                            </div>

                            <!-- Websites List -->
                            @if($websites->isEmpty())
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <i class="bx bx-globe"></i>
                                    </div>
                                    <h5 class="text-dark mb-2">No Websites Added</h5>
                                    <p class="text-secondary mb-4">Add websites that the AI can use as additional information sources when answering questions.</p>
                                    <button type="button" class="btn btn-primary btn-lg" id="btnAddWebsiteEmpty">
                                        <i class="bx bx-plus me-1"></i> Add First Website
                                    </button>
                                </div>
                            @else
                                <div id="websitesList">
                                    @foreach($websites as $website)
                                        <div class="card website-card mb-3 {{ !$website->isActive ? 'inactive' : '' }}" data-website-id="{{ $website->id }}">
                                            <div class="card-body py-3">
                                                <div class="d-flex align-items-start">
                                                    <div class="priority-badge me-3 flex-shrink-0" title="Priority: {{ $website->priority }}">
                                                        {{ $website->priority }}
                                                    </div>
                                                    <div class="flex-grow-1 min-width-0">
                                                        <!-- Title row with inline actions -->
                                                        <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                                                            <h6 class="mb-0 text-dark text-truncate" style="max-width: 200px;" title="{{ $website->websiteName }}">{{ $website->websiteName }}</h6>
                                                            {!! $website->status_badge !!}
                                                            <!-- Inline action buttons -->
                                                            <div class="btn-group btn-group-sm ms-auto flex-shrink-0">
                                                                <button type="button" class="btn btn-xs btn-primary scrape-website"
                                                                        data-website-id="{{ $website->id }}" title="Scrape Now">
                                                                    <i class="bx bx-cloud-download"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-xs btn-outline-primary view-pages"
                                                                        data-website-id="{{ $website->id }}"
                                                                        data-website-name="{{ $website->websiteName }}"
                                                                        title="View Pages{{ $website->active_pages_count > 0 ? ' ('.$website->active_pages_count.')' : '' }}">
                                                                    <i class="bx bx-file"></i>
                                                                    @if($website->active_pages_count > 0)
                                                                        <span class="badge bg-info rounded-pill" style="font-size: 0.6rem; padding: 0.15rem 0.35rem;">{{ $website->active_pages_count }}</span>
                                                                    @endif
                                                                </button>
                                                                <button type="button" class="btn btn-xs btn-outline-success sync-pinecone"
                                                                        data-website-id="{{ $website->id }}"
                                                                        data-website-name="{{ $website->websiteName }}"
                                                                        title="Sync to RAG">
                                                                    <i class="bx bx-upload"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-xs btn-outline-secondary toggle-website"
                                                                        data-website-id="{{ $website->id }}"
                                                                        title="{{ $website->isActive ? 'Disable' : 'Enable' }}">
                                                                    <i class="bx {{ $website->isActive ? 'bx-pause' : 'bx-play' }}"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-xs btn-outline-primary edit-website"
                                                                        data-website-id="{{ $website->id }}" title="Edit">
                                                                    <i class="bx bx-edit"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-xs btn-outline-danger delete-website"
                                                                        data-website-id="{{ $website->id }}"
                                                                        data-website-name="{{ $website->websiteName }}" title="Delete">
                                                                    <i class="bx bx-trash"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                        <p class="website-url mb-2 text-truncate" title="{{ $website->websiteUrl }}">
                                                            <i class="bx bx-link-alt me-1"></i>{{ $website->websiteUrl }}
                                                        </p>
                                                        @if($website->description)
                                                            <p class="text-secondary small mb-2">{{ Str::limit($website->description, 100) }}</p>
                                                        @endif

                                                        <div class="d-flex flex-wrap gap-3 small">
                                                            <div>
                                                                <span class="text-secondary">Type:</span>
                                                                {!! $website->scrape_type_badge !!}
                                                            </div>
                                                            <div>
                                                                <span class="text-secondary">Freq:</span>
                                                                {!! $website->frequency_badge !!}
                                                            </div>
                                                            <div>
                                                                <span class="text-secondary">Scraped:</span>
                                                                <span class="text-dark">{{ $website->last_scraped_human }}</span>
                                                                {!! $website->scrape_status_badge !!}
                                                            </div>
                                                            <div>
                                                                <span class="text-secondary">RAG:</span>
                                                                <span class="text-dark">{{ $website->last_rag_sync_human }}</span>
                                                                @if($website->pineconeStatus)
                                                                    {!! $website->pinecone_status_badge !!}
                                                                @endif
                                                            </div>
                                                        </div>

                                                        @if($website->lastScrapeError)
                                                            <div class="alert alert-danger py-1 px-2 mb-0 mt-2 small">
                                                                <i class="bx bx-error-circle me-1"></i>{{ Str::limit($website->lastScrapeError, 80) }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <!-- End Websites Tab -->

                        <!-- Settings Tab -->
                        <div class="tab-pane" id="settingsTab" role="tabpanel">
                            <div class="row justify-content-center">
                                <div class="col-lg-8">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <div>
                                            <h5 class="text-dark mb-1">
                                                <i class="bx bx-key me-1"></i> Pinecone API Settings
                                            </h5>
                                            <p class="text-secondary mb-0">Configure your Pinecone API credentials for website indexing.</p>
                                        </div>
                                        <button type="button" class="btn btn-soft-info" id="testConnectionBtn">
                                            <i class="bx bx-link me-1"></i> Test Connection
                                        </button>
                                    </div>

                                    <form id="settingsForm">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="apiKey" class="form-label text-dark">API Key <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="password"
                                                       class="form-control api-key-input"
                                                       id="apiKey"
                                                       name="apiKey"
                                                       value="{{ $settings->apiKey }}"
                                                       placeholder="pcsk_xxxxxxxx..."
                                                       required>
                                                <button class="btn btn-outline-secondary" type="button" id="toggleApiKey">
                                                    <i class="bx bx-show"></i>
                                                </button>
                                            </div>
                                            <small class="text-secondary">Your Pinecone API key from the Pinecone console.</small>
                                        </div>

                                        <div class="mb-3">
                                            <label for="indexName" class="form-label text-dark">Assistant/Index Name <span class="text-danger">*</span></label>
                                            <input type="text"
                                                   class="form-control"
                                                   id="indexName"
                                                   name="indexName"
                                                   value="{{ $settings->indexName }}"
                                                   placeholder="e.g., website-kb"
                                                   required>
                                            <small class="text-secondary">The name of your Pinecone Assistant for website content.</small>
                                        </div>

                                        <div class="mb-3">
                                            <label for="indexHost" class="form-label text-dark">Index Host <span class="text-muted">(Optional)</span></label>
                                            <input type="text"
                                                   class="form-control"
                                                   id="indexHost"
                                                   name="indexHost"
                                                   value="{{ $settings->indexHost }}"
                                                   placeholder="e.g., https://your-index-xxxxxxx.svc.environment.pinecone.io">
                                            <small class="text-secondary">The host URL of your Pinecone index (if using direct index access).</small>
                                        </div>

                                        <div class="mb-4">
                                            <label for="email" class="form-label text-dark">Account Email <span class="text-muted">(Optional)</span></label>
                                            <input type="email"
                                                   class="form-control"
                                                   id="email"
                                                   name="email"
                                                   value="{{ $settings->email }}"
                                                   placeholder="e.g., your@email.com">
                                            <small class="text-secondary">Email associated with your Pinecone account for reference.</small>
                                        </div>

                                        <div class="d-flex justify-content-end">
                                            <button type="submit" class="btn btn-primary" id="saveSettingsBtn">
                                                <i class="bx bx-save me-1"></i> Save Settings
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <!-- End Settings Tab -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create/Edit Website Modal -->
    <div class="modal fade" id="websiteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="websiteModalTitle">
                        <i class="bx bx-globe text-primary me-2"></i>Add New Website
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="websiteForm">
                        <input type="hidden" id="websiteId" name="websiteId" value="">

                        <!-- Basic Info -->
                        <div class="mb-3">
                            <label for="websiteName" class="form-label">Website Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="websiteName" name="websiteName"
                                   placeholder="e.g., Company Blog, Product Documentation" required>
                            <small class="text-secondary">A friendly name to identify this website source.</small>
                        </div>

                        <div class="mb-3">
                            <label for="websiteUrl" class="form-label">Website URL <span class="text-danger">*</span></label>
                            <input type="url" class="form-control" id="websiteUrl" name="websiteUrl"
                                   placeholder="https://example.com" required>
                            <small class="text-secondary">The full URL including https://. This is the starting point for scraping.</small>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="2"
                                      placeholder="e.g., Contains product specifications, pricing info, and FAQs"></textarea>
                            <small class="text-secondary">Describe what kind of information can be found on this website. Helps the AI understand when to use it.</small>
                        </div>

                        <div class="mb-3">
                            <label for="priority" class="form-label">Priority</label>
                            <input type="number" class="form-control" id="priority" name="priority"
                                   value="0" min="0" max="100" placeholder="0-100" style="max-width: 120px;">
                            <small class="text-secondary">Websites with higher priority (0-100) are checked first when the AI needs information.</small>
                        </div>

                        <hr class="my-4">

                        <!-- Scrape Configuration -->
                        <h6 class="text-dark mb-3"><i class="bx bx-code-curly me-1"></i> Scrape Configuration</h6>
                        <div class="field-help">
                            <small class="text-dark"><i class="bx bx-info-circle me-1"></i><strong>What is scraping?</strong> Scraping extracts text content from websites so the AI can search through it. Choose how you want to extract content from this website.</small>
                        </div>

                        <div class="mb-3">
                            <label for="scrapeType" class="form-label">Scrape Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="scrapeType" name="scrapeType" required>
                                @foreach($scrapeTypes as $value => $label)
                                    <option value="{{ $value }}" data-description="{{ $scrapeTypeDescriptions[$value] ?? '' }}">
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-secondary" id="scrapeTypeHelp">
                                {{ $scrapeTypeDescriptions['full_page'] ?? '' }}
                            </small>
                        </div>

                        <div class="mb-3" id="cssSelectorContainer" style="display: none;">
                            <label for="cssSelector" class="form-label">CSS Selector <span class="text-danger">*</span></label>
                            <input type="text" class="form-control font-monospace" id="cssSelector" name="cssSelector"
                                   placeholder="e.g., article.content, #main-content, .post-body">
                            <small class="text-secondary">Enter a CSS selector to target specific elements (e.g., <code>article</code>, <code>#content</code>, <code>.main-text</code>).</small>
                        </div>

                        <!-- Multi-page crawl settings -->
                        <div id="multiPageContainer" style="display: none;">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="maxPages" class="form-label">Maximum Pages</label>
                                    <input type="number" class="form-control" id="maxPages" name="maxPages"
                                           value="500" min="1" max="1000" placeholder="500">
                                    <small class="text-secondary">Maximum number of pages to scrape (1-1000).</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="maxDepth" class="form-label">Maximum Depth</label>
                                    <input type="number" class="form-control" id="maxDepth" name="maxDepth"
                                           value="5" min="1" max="30" placeholder="5">
                                    <small class="text-secondary">How many levels deep to crawl from the starting URL (1-30). Only follows links within the same domain.</small>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Schedule -->
                        <h6 class="text-dark mb-3"><i class="bx bx-time me-1"></i> Scrape Schedule</h6>
                        <div class="field-help">
                            <small class="text-dark"><i class="bx bx-info-circle me-1"></i><strong>When to update?</strong> Choose how often the AI should re-scrape this website to get fresh content. For static content, "Manual Only" is fine.</small>
                        </div>

                        <div class="mb-3">
                            <label for="scrapeFrequency" class="form-label">Frequency <span class="text-danger">*</span></label>
                            <select class="form-select" id="scrapeFrequency" name="scrapeFrequency" required style="max-width: 200px;">
                                @foreach($frequencies as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <hr class="my-4">

                        <!-- Path Filters (Advanced) -->
                        <h6 class="text-dark mb-3"><i class="bx bx-filter me-1"></i> Path Filters <span class="badge bg-secondary">Advanced</span></h6>
                        <div class="field-help">
                            <small class="text-dark"><i class="bx bx-info-circle me-1"></i><strong>Optional:</strong> Limit which pages to scrape by specifying allowed or excluded URL paths.</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="allowedPaths" class="form-label">Allowed Paths</label>
                                <textarea class="form-control font-monospace" id="allowedPaths" name="allowedPaths" rows="3"
                                          placeholder="/docs/&#10;/blog/&#10;/help/"></textarea>
                                <small class="text-secondary">Only scrape pages under these paths. One path per line.</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="excludedPaths" class="form-label">Excluded Paths</label>
                                <textarea class="form-control font-monospace" id="excludedPaths" name="excludedPaths" rows="3"
                                          placeholder="/admin/&#10;/login/&#10;/private/"></textarea>
                                <small class="text-secondary">Never scrape pages under these paths. One path per line.</small>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="isActive" name="isActive" checked>
                            <label class="form-check-label text-dark" for="isActive">Enable this website as an information source</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="btnSaveWebsite">
                        <i class="bx bx-save me-1"></i> Save Website
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-trash text-danger me-2"></i>Delete Website</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-dark">Are you sure you want to delete "<strong id="deleteWebsiteName"></strong>"?</p>
                    <p class="text-secondary small mb-0">This will remove the website from your AI's information sources.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="btnConfirmDelete">
                        <i class="bx bx-trash me-1"></i> Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scraped Pages Modal -->
    <div class="modal fade" id="pagesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pagesModalTitle">
                        <i class="bx bx-file text-primary me-2"></i>Scraped Pages
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Search Box and Pinecone Stats -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bx bx-search"></i></span>
                                <input type="text" class="form-control" id="pagesSearchInput"
                                       placeholder="Search by URL or title...">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div id="pineconePagesStats" class="text-end text-secondary small pt-2" style="display: none;">
                                <span class="me-3"><i class="bx bx-file me-1"></i>Pages: <strong id="pineconeIndexedCount">0</strong></span>
                                <span id="pineconeStatusText"></span>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Page</th>
                                    <th style="width: 100px;">Status</th>
                                    <th style="width: 80px;">Words</th>
                                    <th style="width: 70px;">Size</th>
                                    <th style="width: 90px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="pagesTableBody">
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <i class="bx bx-loader-alt bx-spin me-2"></i>Loading pages...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <small class="text-secondary" id="pagesInfo"></small>
                        <div id="pagesPagination"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" id="btnClearAllPages">
                        <i class="bx bx-trash me-1"></i> Clear All
                    </button>
                    <button type="button" class="btn btn-success" id="btnSyncAllPinecone">
                        <i class="bx bx-upload me-1"></i> Sync All to RAG
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Page Content Modal -->
    <div class="modal fade" id="pageContentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="pageContentTitle">Page Content</h5>
                        <small class="text-secondary" id="pageContentUrl"></small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="pageContentBody">
                    <div class="text-center py-4">
                        <i class="bx bx-loader-alt bx-spin me-2"></i>Loading content...
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scrape Progress Overlay -->
    <div id="scrapeProgressOverlay" class="scrape-progress-overlay" style="display: none;">
        <div class="scrape-progress-card">
            <div class="scrape-spinner"></div>
            <div class="scrape-progress-title" id="scrapeProgressTitle">Scraping Website...</div>
            <div class="scrape-progress-status" id="scrapeProgressStatus">Initializing scraper...</div>
            <div class="scrape-progress-bar">
                <div class="scrape-progress-bar-fill" id="scrapeProgressBarFill" style="width: 100%;"></div>
            </div>
            <div class="scrape-progress-details" id="scrapeProgressDetails">Please wait while we extract content from the website.</div>
            <div class="scrape-elapsed-time" id="scrapeElapsedTime">Elapsed: 0:00</div>
            <div class="scrape-log" id="scrapeLog">
                <div class="scrape-log-entry info">Starting scrape process...</div>
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm mt-3" id="btnCancelScrape" style="display: none;">
                <i class="bx bx-x me-1"></i> Cancel
            </button>
        </div>
    </div>

    <!-- RAG Sync Status Modal -->
    <div class="modal fade" id="ragSyncModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">
                        <i class="bx bx-cloud-upload text-success me-2"></i>RAG Sync Status
                    </h5>
                </div>
                <div class="modal-body text-center py-4">
                    <!-- Status Icon -->
                    <div id="ragSyncIcon" class="mb-3">
                        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>

                    <!-- Website Name -->
                    <h6 id="ragSyncWebsiteName" class="text-dark mb-3">Website Name</h6>

                    <!-- Status Message -->
                    <p id="ragSyncStatus" class="text-secondary mb-3">Initializing...</p>

                    <!-- Progress Steps -->
                    <div class="text-start px-4 mb-3">
                        <div class="d-flex align-items-center mb-2" id="ragStep1">
                            <span class="me-2" id="ragStep1Icon"><i class="bx bx-circle text-secondary"></i></span>
                            <span class="text-secondary" id="ragStep1Text">Cleaning up old files</span>
                        </div>
                        <div class="d-flex align-items-center mb-2" id="ragStep2">
                            <span class="me-2" id="ragStep2Icon"><i class="bx bx-circle text-secondary"></i></span>
                            <span class="text-secondary" id="ragStep2Text">Compiling pages</span>
                        </div>
                        <div class="d-flex align-items-center mb-2" id="ragStep3">
                            <span class="me-2" id="ragStep3Icon"><i class="bx bx-circle text-secondary"></i></span>
                            <span class="text-secondary" id="ragStep3Text">Uploading to Pinecone</span>
                        </div>
                        <div class="d-flex align-items-center" id="ragStep4">
                            <span class="me-2" id="ragStep4Icon"><i class="bx bx-circle text-secondary"></i></span>
                            <span class="text-secondary" id="ragStep4Text">Verifying index status</span>
                        </div>
                    </div>

                    <!-- Result Details (shown on completion) -->
                    <div id="ragSyncResult" class="alert mb-0 mx-3" style="display: none;">
                        <div id="ragSyncResultText"></div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 justify-content-center">
                    <button type="button" class="btn btn-secondary" id="ragSyncCloseBtn" data-bs-dismiss="modal" style="display: none;">
                        Close
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
    if (typeof toastr !== 'undefined') {
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: "toast-top-right",
            timeOut: 3000
        };
    }

    const websiteModalEl = document.getElementById('websiteModal');
    const deleteModalEl = document.getElementById('deleteModal');
    const pagesModalEl = document.getElementById('pagesModal');
    const pageContentModalEl = document.getElementById('pageContentModal');
    const ragSyncModalEl = document.getElementById('ragSyncModal');
    const websiteModal = websiteModalEl ? new bootstrap.Modal(websiteModalEl) : null;
    const deleteModal = deleteModalEl ? new bootstrap.Modal(deleteModalEl) : null;
    const pagesModal = pagesModalEl ? new bootstrap.Modal(pagesModalEl) : null;
    const pageContentModal = pageContentModalEl ? new bootstrap.Modal(pageContentModalEl) : null;
    const ragSyncModal = ragSyncModalEl ? new bootstrap.Modal(ragSyncModalEl) : null;
    let websiteToDelete = null;

    // RAG Sync Modal Helper
    const RagSyncUI = {
        reset: function() {
            $('#ragSyncIcon').html('<div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"><span class="visually-hidden">Loading...</span></div>');
            $('#ragSyncStatus').text('Initializing...').removeClass('text-success text-danger').addClass('text-secondary');
            $('#ragSyncResult').hide();
            $('#ragSyncCloseBtn').hide();

            // Reset all steps
            for (let i = 1; i <= 4; i++) {
                $(`#ragStep${i}Icon`).html('<i class="bx bx-circle text-secondary"></i>');
                $(`#ragStep${i}Text`).removeClass('text-dark text-success text-danger').addClass('text-secondary');
            }
        },

        setWebsiteName: function(name) {
            $('#ragSyncWebsiteName').text(name);
        },

        setStatus: function(message) {
            $('#ragSyncStatus').text(message);
        },

        setStepActive: function(step) {
            $(`#ragStep${step}Icon`).html('<div class="spinner-border spinner-border-sm text-primary" role="status"></div>');
            $(`#ragStep${step}Text`).removeClass('text-secondary').addClass('text-dark');
        },

        setStepComplete: function(step, text = null) {
            $(`#ragStep${step}Icon`).html('<i class="bx bx-check-circle text-success"></i>');
            $(`#ragStep${step}Text`).removeClass('text-secondary text-dark').addClass('text-success');
            if (text) $(`#ragStep${step}Text`).text(text);
        },

        setStepSkipped: function(step) {
            $(`#ragStep${step}Icon`).html('<i class="bx bx-minus-circle text-secondary"></i>');
            $(`#ragStep${step}Text`).addClass('text-secondary');
        },

        setStepError: function(step) {
            $(`#ragStep${step}Icon`).html('<i class="bx bx-x-circle text-danger"></i>');
            $(`#ragStep${step}Text`).removeClass('text-secondary text-dark').addClass('text-danger');
        },

        showSuccess: function(message, details = '') {
            $('#ragSyncIcon').html('<i class="bx bx-check-circle text-success" style="font-size: 3rem;"></i>');
            $('#ragSyncStatus').text('Sync Complete!').removeClass('text-secondary text-danger').addClass('text-success');
            if (details) {
                $('#ragSyncResult').removeClass('alert-danger').addClass('alert-success').show();
                $('#ragSyncResultText').html(details);
            }
            $('#ragSyncCloseBtn').show();
        },

        showError: function(message) {
            $('#ragSyncIcon').html('<i class="bx bx-x-circle text-danger" style="font-size: 3rem;"></i>');
            $('#ragSyncStatus').text('Sync Failed').removeClass('text-secondary text-success').addClass('text-danger');
            $('#ragSyncResult').removeClass('alert-success').addClass('alert-danger').show();
            $('#ragSyncResultText').text(message);
            $('#ragSyncCloseBtn').show();
        }
    };

    // Scrape type descriptions
    const scrapeTypeDescriptions = {
        'full_page': 'Extracts all text content from the entire webpage.',
        'specific_selector': 'Extracts content only from elements matching a CSS selector.',
        'sitemap': 'Discovers and scrapes multiple pages from the site\'s sitemap.',
        'api_endpoint': 'Fetches JSON data from an API endpoint.',
        'whole_site': 'Crawls and scrapes the entire website by following internal links.'
    };

    // Handle scrape type change
    $('#scrapeType').on('change', function() {
        const type = $(this).val();
        const cssSelectorContainer = $('#cssSelectorContainer');
        const multiPageContainer = $('#multiPageContainer');
        const help = $('#scrapeTypeHelp');

        help.text(scrapeTypeDescriptions[type] || '');

        // Show/hide CSS selector field
        if (type === 'specific_selector') {
            cssSelectorContainer.show();
            $('#cssSelector').prop('required', true);
        } else {
            cssSelectorContainer.hide();
            $('#cssSelector').prop('required', false);
        }

        // Show/hide multi-page settings
        if (type === 'sitemap' || type === 'whole_site') {
            multiPageContainer.show();
        } else {
            multiPageContainer.hide();
        }
    });

    // Open add website modal
    $('#btnAddWebsite, #btnAddWebsiteEmpty').on('click', function() {
        resetForm();
        $('#websiteModalTitle').html('<i class="bx bx-globe text-primary me-2"></i>Add New Website');
        $('#btnSaveWebsite').html('<i class="bx bx-save me-1"></i> Save Website');
        websiteModal.show();
    });

    // Open edit website modal
    $(document).on('click', '.edit-website', function() {
        const websiteId = $(this).data('website-id');
        loadWebsite(websiteId);
    });

    // Toggle website status
    $(document).on('click', '.toggle-website', function() {
        const websiteId = $(this).data('website-id');
        const btn = $(this);

        $.ajax({
            url: `/ai-technician-websites/${websiteId}/toggle`,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    const card = btn.closest('.website-card');
                    const icon = btn.find('i');
                    const statusBadge = card.find('.badge.bg-success, .badge.bg-secondary').first();

                    if (response.data.isActive) {
                        card.removeClass('inactive');
                        icon.removeClass('bx-play').addClass('bx-pause');
                        btn.attr('title', 'Disable');
                        statusBadge.removeClass('bg-secondary').addClass('bg-success').text('Active');
                    } else {
                        card.addClass('inactive');
                        icon.removeClass('bx-pause').addClass('bx-play');
                        btn.attr('title', 'Enable');
                        statusBadge.removeClass('bg-success').addClass('bg-secondary').text('Inactive');
                    }
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to toggle website status.');
            }
        });
    });

    // Test website connection
    $(document).on('click', '.test-website', function() {
        const websiteId = $(this).data('website-id');
        const btn = $(this);
        const originalHtml = btn.html();

        btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');

        $.ajax({
            url: `/ai-technician-websites/${websiteId}/test`,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message + ' (' + response.data.contentLength + ')');
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to test website connection.');
            },
            complete: function() {
                btn.prop('disabled', false).html(originalHtml);
            }
        });
    });

    // Open delete confirmation
    $(document).on('click', '.delete-website', function() {
        websiteToDelete = {
            id: $(this).data('website-id'),
            name: $(this).data('website-name'),
            card: $(this).closest('.website-card')
        };
        $('#deleteWebsiteName').text(websiteToDelete.name);
        deleteModal.show();
    });

    // Confirm delete
    $('#btnConfirmDelete').on('click', function() {
        if (!websiteToDelete) return;

        const btn = $(this);
        btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Deleting...');

        $.ajax({
            url: `/ai-technician-websites/${websiteToDelete.id}`,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    deleteModal.hide();
                    toastr.success(response.message);
                    websiteToDelete.card.fadeOut(400, function() {
                        $(this).remove();
                        if ($('#websitesList .website-card').length === 0) {
                            location.reload();
                        }
                    });
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to delete website.');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="bx bx-trash me-1"></i> Delete');
                websiteToDelete = null;
            }
        });
    });

    // Save website
    $('#btnSaveWebsite').on('click', function() {
        const btn = $(this);
        const websiteId = $('#websiteId').val();
        const isEdit = !!websiteId;

        // Validate required fields
        if (!$('#websiteName').val().trim()) {
            toastr.error('Website name is required.');
            $('#websiteName').focus();
            return;
        }

        if (!$('#websiteUrl').val().trim()) {
            toastr.error('Website URL is required.');
            $('#websiteUrl').focus();
            return;
        }

        if ($('#scrapeType').val() === 'specific_selector' && !$('#cssSelector').val().trim()) {
            toastr.error('CSS selector is required for this scrape type.');
            $('#cssSelector').focus();
            return;
        }

        btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Saving...');

        const data = {
            _token: '{{ csrf_token() }}',
            websiteName: $('#websiteName').val(),
            websiteUrl: $('#websiteUrl').val(),
            description: $('#description').val(),
            scrapeType: $('#scrapeType').val(),
            cssSelector: $('#cssSelector').val(),
            maxPages: $('#maxPages').val() || 500,
            maxDepth: $('#maxDepth').val() || 5,
            allowedPaths: $('#allowedPaths').val(),
            excludedPaths: $('#excludedPaths').val(),
            scrapeFrequency: $('#scrapeFrequency').val(),
            priority: $('#priority').val() || 0,
            isActive: $('#isActive').is(':checked') ? 1 : 0
        };

        const url = isEdit ? `/ai-technician-websites/${websiteId}` : '/ai-technician-websites';
        const method = isEdit ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            type: method,
            data: data,
            success: function(response) {
                if (response.success) {
                    websiteModal.hide();
                    toastr.success(response.message);
                    location.reload();
                } else {
                    toastr.error(response.message);
                    if (response.errors) {
                        Object.values(response.errors).forEach(function(errors) {
                            errors.forEach(function(error) {
                                toastr.error(error);
                            });
                        });
                    }
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to save website.');
                if (xhr.responseJSON?.errors) {
                    Object.values(xhr.responseJSON.errors).forEach(function(errors) {
                        errors.forEach(function(error) {
                            toastr.error(error);
                        });
                    });
                }
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save Website');
            }
        });
    });

    // Load website for editing
    function loadWebsite(id) {
        $.ajax({
            url: `/ai-technician-websites/${id}`,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const website = response.data;

                    $('#websiteId').val(website.id);
                    $('#websiteName').val(website.websiteName);
                    $('#websiteUrl').val(website.websiteUrl);
                    $('#description').val(website.description);
                    $('#scrapeType').val(website.scrapeType).trigger('change');
                    $('#cssSelector').val(website.cssSelector);
                    $('#maxPages').val(website.maxPages || 50);
                    $('#maxDepth').val(website.maxDepth || 3);
                    $('#allowedPaths').val(website.allowedPathsText || '');
                    $('#excludedPaths').val(website.excludedPathsText || '');
                    $('#scrapeFrequency').val(website.scrapeFrequency);
                    $('#priority').val(website.priority);
                    $('#isActive').prop('checked', website.isActive);

                    $('#websiteModalTitle').html('<i class="bx bx-edit text-primary me-2"></i>Edit Website');
                    $('#btnSaveWebsite').html('<i class="bx bx-save me-1"></i> Update Website');
                    websiteModal.show();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to load website.');
            }
        });
    }

    // Reset form
    function resetForm() {
        $('#websiteForm')[0].reset();
        $('#websiteId').val('');
        $('#scrapeType').val('full_page').trigger('change');
        $('#scrapeFrequency').val('manual');
        $('#maxPages').val(500);
        $('#maxDepth').val(5);
        $('#isActive').prop('checked', true);
    }

    // Scrape Progress Manager with Auto-Continue Support
    const ScrapeProgress = {
        overlay: null,
        startTime: null,
        timerInterval: null,
        currentXhr: null,
        websiteId: null,
        websiteName: '',
        card: null,
        isStopped: false,
        totalScraped: 0,
        totalSkipped: 0,
        batchNumber: 0,
        statusMessages: [
            'Connecting to website...',
            'Fetching page content...',
            'Extracting text and metadata...',
            'Processing headings and links...',
            'Analyzing page structure...',
            'Saving content to database...',
            'Checking for additional pages...',
            'Crawling discovered links...',
            'Extracting clean content...',
            'Finalizing batch...'
        ],
        messageIndex: 0,
        messageInterval: null,

        init: function() {
            this.overlay = $('#scrapeProgressOverlay');

            // Stop button handler
            $('#btnCancelScrape').off('click').on('click', () => {
                this.isStopped = true;
                this.addLog('Stopping after current batch completes...', 'info');
                $('#btnCancelScrape').prop('disabled', true).text('Stopping...');
            });
        },

        show: function(websiteId, websiteName, card) {
            this.startTime = Date.now();
            this.messageIndex = 0;
            this.websiteId = websiteId;
            this.websiteName = websiteName;
            this.card = card;
            this.isStopped = false;
            this.totalScraped = 0;
            this.totalSkipped = 0;
            this.batchNumber = 0;

            // Reset spinner style
            $('.scrape-spinner').css('animation', 'spin 1s linear infinite').css('border-top-color', '#556ee6');

            $('#scrapeProgressTitle').text('Scraping: ' + websiteName);
            $('#scrapeProgressStatus').text('Initializing scraper...');
            $('#scrapeProgressDetails').text('Please wait while we discover and scrape pages.');
            $('#scrapeElapsedTime').text('Elapsed: 0:00');
            $('#scrapeLog').html('<div class="scrape-log-entry info"><i class="bx bx-play-circle me-1"></i> Starting scrape process...</div>');
            $('#btnCancelScrape').show().prop('disabled', false).html('<i class="bx bx-stop-circle me-1"></i> Stop');

            this.overlay.fadeIn(200);

            // Start elapsed time counter
            this.timerInterval = setInterval(() => this.updateElapsedTime(), 1000);

            // Rotate status messages
            this.messageInterval = setInterval(() => this.rotateStatusMessage(), 2500);
        },

        hide: function() {
            this.overlay.fadeOut(200);
            if (this.timerInterval) {
                clearInterval(this.timerInterval);
                this.timerInterval = null;
            }
            if (this.messageInterval) {
                clearInterval(this.messageInterval);
                this.messageInterval = null;
            }
        },

        updateElapsedTime: function() {
            const elapsed = Math.floor((Date.now() - this.startTime) / 1000);
            const minutes = Math.floor(elapsed / 60);
            const seconds = elapsed % 60;
            $('#scrapeElapsedTime').text(`Elapsed: ${minutes}:${seconds.toString().padStart(2, '0')}`);
        },

        rotateStatusMessage: function() {
            this.messageIndex = (this.messageIndex + 1) % this.statusMessages.length;
            $('#scrapeProgressStatus').text(this.statusMessages[this.messageIndex]);
        },

        addLog: function(message, type = 'info') {
            const icon = type === 'success' ? 'bx-check-circle' :
                        type === 'error' ? 'bx-error-circle' : 'bx-info-circle';
            const logHtml = `<div class="scrape-log-entry ${type}"><i class="bx ${icon} me-1"></i> ${escapeHtml(message)}</div>`;
            $('#scrapeLog').append(logHtml);
            $('#scrapeLog').scrollTop($('#scrapeLog')[0].scrollHeight);
        },

        setStatus: function(status, details = null) {
            $('#scrapeProgressStatus').text(status);
            if (details) {
                $('#scrapeProgressDetails').text(details);
            }
        },

        updateBatchProgress: function(data) {
            this.batchNumber++;
            this.totalScraped += data.pagesScraped || 0;
            this.totalSkipped += data.pagesSkipped || 0;

            const remaining = data.remainingInQueue || 0;
            const total = data.totalPagesScraped || this.totalScraped;

            $('#scrapeProgressStatus').text(`Batch ${this.batchNumber}: Scraped ${data.pagesScraped} pages`);
            $('#scrapeProgressDetails').text(`Total: ${total} pages scraped. ${remaining} remaining.`);

            this.addLog(`Batch ${this.batchNumber}: +${data.pagesScraped} pages (${remaining} remaining)`, 'success');
        },

        showSuccess: function(data) {
            if (this.messageInterval) {
                clearInterval(this.messageInterval);
            }

            const totalScraped = data.totalPagesScraped || this.totalScraped;
            const pagesSkipped = this.totalSkipped || data.pagesSkipped || 0;
            const pagesDiscovered = data.pagesDiscovered || 0;
            const remainingInQueue = data.remainingInQueue || 0;
            const reachedLimit = data.reachedMaxPagesLimit || false;
            const errors = data.errors || [];

            $('#scrapeProgressTitle').html('<i class="bx bx-check-circle text-success me-2"></i>Scrape Complete!');
            $('#btnCancelScrape').hide();

            let statusText = `Total: ${totalScraped} pages scraped`;
            if (pagesSkipped > 0) {
                statusText += ` (${pagesSkipped} skipped)`;
            }
            $('#scrapeProgressStatus').text(statusText);

            let detailsText = this.batchNumber > 1 ? `Completed in ${this.batchNumber} batches. ` : '';
            if (remainingInQueue > 0) {
                detailsText += `${remainingInQueue} pages still pending. Click "Scrape" again to continue.`;
            } else {
                detailsText += 'All pages scraped. Content is ready for AI processing.';
            }
            $('#scrapeProgressDetails').text(detailsText);
            $('.scrape-spinner').css('animation', 'none').css('border-top-color', '#34c38f');

            if (errors && errors.length > 0) {
                errors.slice(0, 3).forEach(err => this.addLog(err, 'error'));
            }

            // Auto-hide after a delay
            setTimeout(() => this.hide(), remainingInQueue > 0 ? 4000 : 2500);
        },

        showStopped: function(data) {
            if (this.messageInterval) {
                clearInterval(this.messageInterval);
            }

            const totalScraped = data.totalPagesScraped || this.totalScraped;
            const remainingInQueue = data.remainingInQueue || 0;

            $('#scrapeProgressTitle').html('<i class="bx bx-pause-circle text-warning me-2"></i>Scrape Paused');
            $('#btnCancelScrape').hide();

            $('#scrapeProgressStatus').text(`Scraped ${totalScraped} pages total`);
            $('#scrapeProgressDetails').text(`${remainingInQueue} pages remaining. Click "Scrape" to continue.`);
            $('.scrape-spinner').css('animation', 'none').css('border-top-color', '#f1b44c');

            this.addLog(`Stopped. ${remainingInQueue} pages remaining.`, 'info');

            setTimeout(() => this.hide(), 3000);
        },

        showError: function(message) {
            if (this.messageInterval) {
                clearInterval(this.messageInterval);
            }
            $('#scrapeProgressTitle').html('<i class="bx bx-error-circle text-danger me-2"></i>Scrape Error');
            $('#scrapeProgressStatus').text(message);
            $('#scrapeProgressDetails').html('Progress saved. Click "Scrape" to continue from where you left off.');
            $('.scrape-spinner').css('animation', 'none').css('border-top-color', '#f46a6a');
            $('#btnCancelScrape').hide();

            this.addLog(message, 'error');
            this.addLog('Progress saved. You can continue later.', 'info');

            setTimeout(() => this.hide(), 4000);
        },

        // Start scraping with auto-continue
        startScraping: function(websiteId, websiteName, card, mode = 'full') {
            this.show(websiteId, websiteName, card);
            this.runBatch(mode);
        },

        // Run a single batch
        runBatch: function(mode = 'continue') {
            if (this.isStopped) {
                return;
            }

            $.ajax({
                url: `/ai-technician-websites/${this.websiteId}/scrape`,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    mode: mode
                },
                timeout: 120000, // 2 minute timeout per batch
                success: (response) => {
                    if (response.success) {
                        this.updateBatchProgress(response.data);
                        this.updateCardBadge(response.data.totalPagesScraped);

                        // Check if there are more pages and user hasn't stopped
                        if (response.data.hasMore && !this.isStopped) {
                            // Small pause before next batch
                            setTimeout(() => this.runBatch('continue'), 1500);
                        } else if (this.isStopped) {
                            this.showStopped(response.data);
                            this.updateCardStatus(response.data);
                        } else {
                            this.showSuccess(response.data);
                            this.updateCardStatus(response.data);
                        }
                    } else {
                        this.showError(response.message || 'Scraping failed.');
                        this.updateCardStatus(response.data);
                    }
                },
                error: (xhr, status, error) => {
                    let errorMsg = 'Request failed.';
                    if (status === 'timeout') {
                        errorMsg = 'Batch timed out. Progress saved.';
                    } else if (xhr.responseJSON?.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    this.showError(errorMsg);
                }
            });
        },

        updateCardBadge: function(totalPages) {
            if (!this.card) return;
            const viewPagesBtn = this.card.find('.view-pages');
            let badge = viewPagesBtn.find('.badge');
            if (totalPages > 0) {
                if (badge.length === 0) {
                    viewPagesBtn.append('<span class="badge bg-info ms-1">' + totalPages + '</span>');
                } else {
                    badge.text(totalPages);
                }
            }
        },

        updateCardStatus: function(data) {
            if (!this.card || !data) return;

            // Update scrape status badge
            if (data.scrapeStatusBadge) {
                const scrapeStatusContainer = this.card.find('.col-md-4').last();
                const existingStatusBadge = scrapeStatusContainer.find('.badge[class*="bg-"]:not(.bg-info)');
                if (existingStatusBadge.length > 0) {
                    existingStatusBadge.replaceWith(data.scrapeStatusBadge);
                } else {
                    scrapeStatusContainer.append(' ' + data.scrapeStatusBadge);
                }
            }

            // Update last scraped time
            if (data.lastScrapedAt) {
                const lastScrapedSpan = this.card.find('.col-md-4').last().find('.text-dark').first();
                if (lastScrapedSpan.length > 0) {
                    lastScrapedSpan.text(data.lastScrapedAt);
                }
            }
        }
    };

    ScrapeProgress.init();

    // Scrape website - with auto-continue support
    $(document).on('click', '.scrape-website', function() {
        const websiteId = $(this).data('website-id');
        const btn = $(this);
        const card = btn.closest('.website-card');
        const websiteName = card.find('h5.text-dark').first().text().trim() || 'Website';

        // Check if there's a saved queue (continue mode) or start fresh
        const hasSavedQueue = card.data('has-queue') || false;
        const mode = hasSavedQueue ? 'continue' : 'full';

        btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Scraping...');

        // Start scraping with auto-continue
        ScrapeProgress.startScraping(websiteId, websiteName, card, mode);

        // Re-enable button when progress overlay is hidden
        const checkOverlay = setInterval(() => {
            if (!ScrapeProgress.overlay.is(':visible')) {
                btn.prop('disabled', false).html('<i class="bx bx-cloud-download me-1"></i> Scrape');
                clearInterval(checkOverlay);
            }
        }, 500);
    });

    // Sync to Pinecone RAG (compiles all pages into single file)
    $(document).on('click', '.sync-pinecone', function() {
        const websiteId = $(this).data('website-id');
        const websiteName = $(this).data('website-name');
        const btn = $(this);
        const originalHtml = btn.html();

        // Reset and show modal
        RagSyncUI.reset();
        RagSyncUI.setWebsiteName(websiteName);
        ragSyncModal.show();

        btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');

        // Step 1: Start - Cleaning up
        RagSyncUI.setStatus('Cleaning up old files...');
        RagSyncUI.setStepActive(1);

        $.ajax({
            url: `/ai-technician-websites/${websiteId}/upload-pinecone`,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            timeout: 180000,
            beforeSend: function() {
                // Simulate progress steps with delays
                setTimeout(() => {
                    RagSyncUI.setStepComplete(1, 'Cleanup complete');
                    RagSyncUI.setStatus('Compiling all pages...');
                    RagSyncUI.setStepActive(2);
                }, 500);

                setTimeout(() => {
                    RagSyncUI.setStepComplete(2, 'Pages compiled');
                    RagSyncUI.setStatus('Uploading to Pinecone...');
                    RagSyncUI.setStepActive(3);
                }, 1500);
            },
            success: function(response) {
                if (response.success) {
                    // Complete step 3
                    RagSyncUI.setStepComplete(3, 'Uploaded to Pinecone');

                    // Step 4: Verify
                    RagSyncUI.setStatus('Verifying index status...');
                    RagSyncUI.setStepActive(4);

                    setTimeout(() => {
                        RagSyncUI.setStepComplete(4, 'Index verified');

                        // Build success details
                        let details = '';
                        if (response.data?.pagesCompiled) {
                            details += `<i class="bx bx-file me-1"></i><strong>${response.data.pagesCompiled}</strong> pages compiled<br>`;
                        }
                        if (response.data?.wasUpdate) {
                            details += `<i class="bx bx-refresh me-1"></i>Updated existing RAG file<br>`;
                        } else {
                            details += `<i class="bx bx-upload me-1"></i>New RAG file created<br>`;
                        }
                        if (response.data?.cleanedUp > 0) {
                            details += `<i class="bx bx-trash me-1"></i>${response.data.cleanedUp} old files cleaned up`;
                        }

                        RagSyncUI.showSuccess('Sync Complete!', details);

                        // Reload page after user closes modal
                        $('#ragSyncCloseBtn').off('click').on('click', function() {
                            location.reload();
                        });
                    }, 500);
                } else {
                    RagSyncUI.setStepError(3);
                    RagSyncUI.showError(response.message || 'Failed to sync to Pinecone.');
                }
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON?.message || 'Failed to sync to Pinecone. Make sure you have configured the API settings.';
                RagSyncUI.setStepError(3);
                RagSyncUI.showError(errorMsg);
            },
            complete: function() {
                btn.prop('disabled', false).html(originalHtml);
            }
        });
    });

    // View scraped pages
    let currentWebsiteId = null;
    let currentWebsiteName = '';
    let currentPage = 1;
    let currentSearch = '';
    let pagesPerPage = 20;

    $(document).on('click', '.view-pages', function() {
        currentWebsiteId = $(this).data('website-id');
        currentWebsiteName = $(this).data('website-name');
        currentPage = 1;
        currentSearch = '';
        $('#pagesSearchInput').val('');
        $('#pagesModalTitle').html('<i class="bx bx-file text-primary me-2"></i>Scraped Pages - ' + escapeHtml(currentWebsiteName));
        loadPages(currentWebsiteId, currentPage, currentSearch);
        pagesModal.show();
    });

    // Search pages
    let searchTimeout = null;
    $(document).on('input', '#pagesSearchInput', function() {
        clearTimeout(searchTimeout);
        const search = $(this).val();
        searchTimeout = setTimeout(function() {
            currentSearch = search;
            currentPage = 1;
            loadPages(currentWebsiteId, currentPage, currentSearch);
        }, 300);
    });

    // Pagination click handlers
    $(document).on('click', '.pages-pagination-btn', function() {
        const page = $(this).data('page');
        if (page && page !== currentPage) {
            currentPage = page;
            loadPages(currentWebsiteId, currentPage, currentSearch);
        }
    });

    function loadPages(websiteId, page = 1, search = '') {
        $('#pagesTableBody').html('<tr><td colspan="5" class="text-center py-4"><i class="bx bx-loader-alt bx-spin me-2"></i>Loading pages...</td></tr>');
        $('#pagesPagination').html('');

        $.ajax({
            url: `/ai-technician-websites/${websiteId}/pages`,
            type: 'GET',
            data: {
                page: page,
                per_page: pagesPerPage,
                search: search
            },
            success: function(response) {
                if (response.success) {
                    renderPages(response.data.pages || [], response.data.pagination);

                    // Update Pinecone stats (website-level)
                    if (response.data.pineconeConfigured && response.data.pineconeStats) {
                        const stats = response.data.pineconeStats;
                        $('#pineconeIndexedCount').text(stats.totalPages || 0);
                        $('#pineconePagesStats').show();
                        $('#btnSyncAllPinecone').show();

                        // Show sync status
                        if (stats.isIndexed) {
                            $('#pineconeStatusText').html('<span class="badge bg-success">Indexed</span>');
                        } else if (stats.needsUpload) {
                            $('#pineconeStatusText').html('<span class="badge bg-warning text-dark">Needs Sync</span>');
                        }
                    } else {
                        $('#pineconePagesStats').hide();
                        if (!response.data.pineconeConfigured) {
                            $('#btnSyncAllPinecone').hide();
                        }
                    }
                } else {
                    $('#pagesTableBody').html('<tr><td colspan="5" class="text-center py-4 text-danger">Failed to load pages.</td></tr>');
                }
            },
            error: function(xhr) {
                $('#pagesTableBody').html('<tr><td colspan="5" class="text-center py-4 text-danger">Failed to load pages.</td></tr>');
            }
        });
    }

    function renderPages(pages, pagination, pineconeStats = null) {
        if (pages.length === 0) {
            const emptyMessage = currentSearch
                ? `No pages found matching "${escapeHtml(currentSearch)}".`
                : 'No pages scraped yet.';
            const emptyHint = currentSearch
                ? 'Try a different search term.'
                : 'Click "Scrape" button on the website card to start scraping.';

            $('#pagesTableBody').html(`
                <tr>
                    <td colspan="5" class="text-center py-4">
                        <i class="bx bx-file-blank text-secondary" style="font-size: 2rem;"></i>
                        <p class="text-secondary mb-0 mt-2">${emptyMessage}</p>
                        <small class="text-secondary">${emptyHint}</small>
                    </td>
                </tr>
            `);
            $('#btnClearAllPages').prop('disabled', true);
            $('#pagesPagination').html('');
            return;
        }

        $('#btnClearAllPages').prop('disabled', false);

        let html = '';
        pages.forEach(function(page, index) {
            const statusClass = page.scrapeStatus === 'completed' ? 'success' :
                               (page.scrapeStatus === 'failed' ? 'danger' :
                               (page.scrapeStatus === 'in_progress' ? 'info' : 'warning'));

            html += `
                <tr>
                    <td class="text-dark" style="max-width: 300px;">
                        <div class="text-truncate" title="${escapeHtml(page.url)}">${escapeHtml(page.title || page.url)}</div>
                        <small class="text-secondary text-truncate d-block" style="font-family: monospace;">${escapeHtml(page.url)}</small>
                    </td>
                    <td><span class="badge bg-${statusClass}">${page.scrapeStatus}</span></td>
                    <td class="text-dark">${page.wordCount ? page.wordCount.toLocaleString() : '-'}</td>
                    <td class="text-secondary">${page.pageSize || '-'}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary view-page-content"
                                    data-page-id="${page.id}" data-page-title="${escapeHtml(page.title || page.url)}"
                                    title="View Content" ${page.scrapeStatus !== 'completed' ? 'disabled' : ''}>
                                <i class="bx bx-show"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger delete-page"
                                    data-page-id="${page.id}" title="Delete">
                                <i class="bx bx-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
        $('#pagesTableBody').html(html);

        // Render pagination
        if (pagination && pagination.totalPages > 1) {
            renderPagesPagination(pagination);
        } else {
            $('#pagesPagination').html('');
        }

        // Update page info
        if (pagination) {
            const start = ((pagination.currentPage - 1) * pagination.perPage) + 1;
            const end = Math.min(pagination.currentPage * pagination.perPage, pagination.totalItems);
            $('#pagesInfo').html(`Showing ${start}-${end} of ${pagination.totalItems} pages`);
        }
    }

    function renderPagesPagination(pagination) {
        const { currentPage, totalPages } = pagination;
        let html = '<nav><ul class="pagination pagination-sm mb-0 justify-content-center">';

        // Previous button
        html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link pages-pagination-btn" href="javascript:void(0)" data-page="${currentPage - 1}">
                <i class="bx bx-chevron-left"></i>
            </a>
        </li>`;

        // Page numbers
        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(totalPages, currentPage + 2);

        // Adjust range if at start or end
        if (currentPage <= 3) {
            endPage = Math.min(5, totalPages);
        }
        if (currentPage >= totalPages - 2) {
            startPage = Math.max(1, totalPages - 4);
        }

        // First page + ellipsis
        if (startPage > 1) {
            html += `<li class="page-item">
                <a class="page-link pages-pagination-btn" href="javascript:void(0)" data-page="1">1</a>
            </li>`;
            if (startPage > 2) {
                html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        // Page numbers
        for (let i = startPage; i <= endPage; i++) {
            html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link pages-pagination-btn" href="javascript:void(0)" data-page="${i}">${i}</a>
            </li>`;
        }

        // Last page + ellipsis
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            html += `<li class="page-item">
                <a class="page-link pages-pagination-btn" href="javascript:void(0)" data-page="${totalPages}">${totalPages}</a>
            </li>`;
        }

        // Next button
        html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link pages-pagination-btn" href="javascript:void(0)" data-page="${currentPage + 1}">
                <i class="bx bx-chevron-right"></i>
            </a>
        </li>`;

        html += '</ul></nav>';
        $('#pagesPagination').html(html);
    }

    // View page content
    $(document).on('click', '.view-page-content', function() {
        const pageId = $(this).data('page-id');
        const pageTitle = $(this).data('page-title');
        const btn = $(this);

        btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');

        $.ajax({
            url: `/ai-technician-websites/${currentWebsiteId}/pages/${pageId}`,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const page = response.data;
                    $('#pageContentTitle').text(page.title || page.url);
                    $('#pageContentUrl').html(`<a href="${escapeHtml(page.url)}" target="_blank" class="text-primary">${escapeHtml(page.url)} <i class="bx bx-link-external"></i></a>`);
                    $('#pageContentBody').html(formatPageContent(page));
                    pageContentModal.show();
                } else {
                    toastr.error(response.message || 'Failed to load page content.');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to load page content.');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="bx bx-show"></i>');
            }
        });
    });

    function formatPageContent(page) {
        let html = '';

        // Meta info
        html += `<div class="mb-3">
            <div class="row text-secondary small">
                <div class="col-md-4"><strong class="text-dark">Words:</strong> ${(page.wordCount || 0).toLocaleString()}</div>
                <div class="col-md-4"><strong class="text-dark">Size:</strong> ${page.pageSize || 'N/A'}</div>
                <div class="col-md-4"><strong class="text-dark">HTTP Status:</strong> ${page.httpStatus || 'N/A'}</div>
            </div>
        </div>`;

        // Meta description
        if (page.metaDescription) {
            html += `<div class="mb-3">
                <strong class="text-dark d-block mb-1">Meta Description:</strong>
                <p class="text-secondary mb-0">${escapeHtml(page.metaDescription)}</p>
            </div>`;
        }

        // Headings
        if (page.headings && page.headings.length > 0) {
            html += `<div class="mb-3">
                <strong class="text-dark d-block mb-1">Headings:</strong>
                <div class="bg-light p-2 rounded" style="max-height: 150px; overflow-y: auto;">`;
            page.headings.forEach(function(h) {
                const indent = (parseInt(h.level.replace('h', '')) - 1) * 15;
                html += `<div class="text-dark small" style="margin-left: ${indent}px;"><span class="badge bg-secondary me-1">${h.level}</span>${escapeHtml(h.text)}</div>`;
            });
            html += `</div></div>`;
        }

        // Clean content
        if (page.cleanContent) {
            html += `<div class="mb-0">
                <strong class="text-dark d-block mb-1">Extracted Content:</strong>
                <div class="bg-light p-3 rounded text-dark" style="max-height: 400px; overflow-y: auto; white-space: pre-wrap; font-family: inherit;">${escapeHtml(page.cleanContent)}</div>
            </div>`;
        }

        return html;
    }

    // Delete single page
    $(document).on('click', '.delete-page', function() {
        const pageId = $(this).data('page-id');
        const btn = $(this);
        const row = btn.closest('tr');

        if (!confirm('Are you sure you want to delete this page?')) return;

        btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');

        $.ajax({
            url: `/ai-technician-websites/${currentWebsiteId}/pages/${pageId}`,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    toastr.success('Page deleted.');
                    row.fadeOut(300, function() {
                        $(this).remove();
                        if ($('#pagesTableBody tr').length === 0) {
                            renderPages([]);
                        }
                    });
                } else {
                    toastr.error(response.message || 'Failed to delete page.');
                    btn.prop('disabled', false).html('<i class="bx bx-trash"></i>');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to delete page.');
                btn.prop('disabled', false).html('<i class="bx bx-trash"></i>');
            }
        });
    });

    // Sync website to Pinecone from modal (compiles all pages into single file)
    $('#btnSyncAllPinecone').on('click', function() {
        if (!currentWebsiteId) return;

        const btn = $(this);
        const originalHtml = btn.html();

        // Hide pages modal and show RAG sync modal
        pagesModal.hide();

        // Reset and show RAG sync modal
        RagSyncUI.reset();
        RagSyncUI.setWebsiteName(currentWebsiteName);
        ragSyncModal.show();

        btn.prop('disabled', true);

        // Step 1: Start - Cleaning up
        RagSyncUI.setStatus('Cleaning up old files...');
        RagSyncUI.setStepActive(1);

        $.ajax({
            url: `/ai-technician-websites/${currentWebsiteId}/upload-pinecone`,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            timeout: 180000,
            beforeSend: function() {
                setTimeout(() => {
                    RagSyncUI.setStepComplete(1, 'Cleanup complete');
                    RagSyncUI.setStatus('Compiling all pages...');
                    RagSyncUI.setStepActive(2);
                }, 500);

                setTimeout(() => {
                    RagSyncUI.setStepComplete(2, 'Pages compiled');
                    RagSyncUI.setStatus('Uploading to Pinecone...');
                    RagSyncUI.setStepActive(3);
                }, 1500);
            },
            success: function(response) {
                if (response.success) {
                    RagSyncUI.setStepComplete(3, 'Uploaded to Pinecone');
                    RagSyncUI.setStatus('Verifying index status...');
                    RagSyncUI.setStepActive(4);

                    setTimeout(() => {
                        RagSyncUI.setStepComplete(4, 'Index verified');

                        let details = '';
                        if (response.data?.pagesCompiled) {
                            details += `<i class="bx bx-file me-1"></i><strong>${response.data.pagesCompiled}</strong> pages compiled<br>`;
                        }
                        if (response.data?.wasUpdate) {
                            details += `<i class="bx bx-refresh me-1"></i>Updated existing RAG file`;
                        } else {
                            details += `<i class="bx bx-upload me-1"></i>New RAG file created`;
                        }

                        RagSyncUI.showSuccess('Sync Complete!', details);

                        $('#ragSyncCloseBtn').off('click').on('click', function() {
                            location.reload();
                        });
                    }, 500);
                } else {
                    RagSyncUI.setStepError(3);
                    RagSyncUI.showError(response.message || 'Sync failed');
                }
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON?.message || 'Sync failed. Check Settings tab for API configuration.';
                RagSyncUI.setStepError(3);
                RagSyncUI.showError(errorMsg);
            },
            complete: function() {
                btn.prop('disabled', false).html(originalHtml);
            }
        });
    });

    // Clear all pages
    $('#btnClearAllPages').on('click', function() {
        if (!confirm('Are you sure you want to delete ALL scraped pages for this website? This cannot be undone.')) return;

        const btn = $(this);
        btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Clearing...');

        $.ajax({
            url: `/ai-technician-websites/${currentWebsiteId}/pages`,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    toastr.success('All pages cleared.');
                    renderPages([]);
                } else {
                    toastr.error(response.message || 'Failed to clear pages.');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to clear pages.');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="bx bx-trash me-1"></i> Clear All');
            }
        });
    });

    // Escape HTML helper
    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // =========================================
    // Settings Tab Functions
    // =========================================

    // Toggle API Key visibility
    $('#toggleApiKey').on('click', function() {
        const input = $('#apiKey');
        const icon = $(this).find('i');

        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('bx-show').addClass('bx-hide');
        } else {
            input.attr('type', 'password');
            icon.removeClass('bx-hide').addClass('bx-show');
        }
    });

    // Save Settings
    $('#settingsForm').on('submit', function(e) {
        e.preventDefault();

        const $btn = $('#saveSettingsBtn');
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Saving...');

        $.ajax({
            url: '{{ route("ai-technician.kb-websites-settings.settings.save") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                apiKey: $('#apiKey').val(),
                indexName: $('#indexName').val(),
                indexHost: $('#indexHost').val(),
                email: $('#email').val()
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Success');
                } else {
                    toastr.error(response.message || 'Failed to save settings', 'Error');
                }
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors;
                if (errors) {
                    Object.keys(errors).forEach(function(key) {
                        toastr.error(errors[key][0], 'Validation Error');
                    });
                } else {
                    toastr.error(xhr.responseJSON?.message || 'An error occurred', 'Error');
                }
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save Settings');
            }
        });
    });

    // Test Connection
    $('#testConnectionBtn').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Testing...');

        $.ajax({
            url: '{{ route("ai-technician.kb-websites-settings.settings.test") }}',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Connection Successful');

                    if (response.data?.assistants) {
                        console.log('Found assistants:', response.data.assistants);
                    }
                } else {
                    toastr.error(response.message, 'Connection Failed');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Connection test failed', 'Error');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-link me-1"></i> Test Connection');
            }
        });
    });

    // =========================================
    // Dynamic Processing Status Checker
    // =========================================

    // Track websites that are currently processing
    let processingWebsiteIds = [];
    let processingCheckInterval = null;
    const PROCESSING_CHECK_DELAY = 5000; // Check every 5 seconds

    // Initialize processing status checker on page load
    function initProcessingStatusChecker() {
        // Find all websites with processing or pending status
        processingWebsiteIds = [];
        $('.website-card').each(function() {
            const $card = $(this);
            const websiteId = $card.data('website-id');
            const statusText = $card.find('.col-md-3').last().find('.badge').text().toLowerCase();

            // Check if status badge indicates processing or pending
            if (statusText.includes('processing') || statusText.includes('pending')) {
                processingWebsiteIds.push(websiteId);
                // Add a subtle pulsing indicator to show it's being monitored
                $card.find('.col-md-3').last().find('.badge').addClass('processing-pulse');
            }
        });

        // Also check by looking for specific badge classes
        @foreach($websites as $website)
            @if($website->pineconeStatus === 'processing' || $website->pineconeStatus === 'pending')
                if (!processingWebsiteIds.includes({{ $website->id }})) {
                    processingWebsiteIds.push({{ $website->id }});
                }
            @endif
        @endforeach

        if (processingWebsiteIds.length > 0) {
            console.log('Found processing websites:', processingWebsiteIds);
            startProcessingStatusPolling();
        }
    }

    // Start polling for processing status updates
    function startProcessingStatusPolling() {
        if (processingCheckInterval) {
            clearInterval(processingCheckInterval);
        }

        // Check immediately
        checkProcessingStatus();

        // Then check periodically
        processingCheckInterval = setInterval(function() {
            checkProcessingStatus();
        }, PROCESSING_CHECK_DELAY);
    }

    // Stop polling
    function stopProcessingStatusPolling() {
        if (processingCheckInterval) {
            clearInterval(processingCheckInterval);
            processingCheckInterval = null;
        }
    }

    // Check processing status for all processing websites
    function checkProcessingStatus() {
        if (processingWebsiteIds.length === 0) {
            stopProcessingStatusPolling();
            return;
        }

        $.ajax({
            url: '{{ route("ai-technician.kb-websites-settings.check-processing") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                websiteIds: processingWebsiteIds
            },
            success: function(response) {
                if (response.success && response.data) {
                    let completedIds = [];

                    response.data.forEach(function(website) {
                        updateWebsiteStatusUI(website);

                        // If processing is complete, remove from tracking
                        if (website.isComplete) {
                            completedIds.push(website.id);
                        }
                    });

                    // Remove completed websites from tracking
                    if (completedIds.length > 0) {
                        processingWebsiteIds = processingWebsiteIds.filter(id => !completedIds.includes(id));

                        completedIds.forEach(function(id) {
                            const $card = $(`.website-card[data-website-id="${id}"]`);
                            const status = response.data.find(w => w.id === id)?.pineconeStatus;

                            if (status === 'indexed') {
                                toastr.success('RAG indexing complete for website', 'Processing Complete');
                            } else if (status === 'failed') {
                                toastr.warning('RAG indexing failed for a website', 'Processing Failed');
                            }
                        });
                    }

                    // Stop polling if no more processing websites
                    if (processingWebsiteIds.length === 0) {
                        stopProcessingStatusPolling();
                        console.log('All websites finished processing');
                    }
                }
            },
            error: function(xhr) {
                console.error('Failed to check processing status:', xhr.responseJSON?.message);
            }
        });
    }

    // Update the UI for a specific website
    function updateWebsiteStatusUI(website) {
        const $card = $(`.website-card[data-website-id="${website.id}"]`);
        if (!$card.length) return;

        // Find the Last RAG Sync column
        const $ragSyncCol = $card.find('.col-md-3').last();

        // Update the RAG sync time and badge
        let html = `<small class="text-uppercase text-secondary fw-bold d-block mb-1">Last RAG Sync</small>`;
        html += `<span class="text-dark">${escapeHtml(website.lastRagSyncHuman)}</span> `;
        html += website.pineconeStatusBadge;

        $ragSyncCol.html(html);

        // Remove processing pulse class if complete
        if (website.isComplete) {
            $ragSyncCol.find('.badge').removeClass('processing-pulse');
        }
    }

    // Start the processing status checker on page load
    initProcessingStatusChecker();
});
</script>

<style>
    /* Processing pulse animation */
    .processing-pulse {
        animation: processingPulse 1.5s ease-in-out infinite;
    }

    @keyframes processingPulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
</style>
@endsection
