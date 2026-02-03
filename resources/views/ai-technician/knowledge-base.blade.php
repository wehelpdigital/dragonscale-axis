@extends('layouts.master')

@section('title') Knowledge Base @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    .upload-zone {
        border: 2px dashed #556ee6;
        border-radius: 8px;
        padding: 40px 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        background-color: rgba(85, 110, 230, 0.05);
    }
    .upload-zone:hover {
        border-color: #3c4ccf;
        background-color: rgba(85, 110, 230, 0.1);
    }
    .upload-zone.drag-over {
        border-color: #34c38f;
        background-color: rgba(52, 195, 143, 0.1);
    }
    .upload-zone i {
        font-size: 48px;
        color: #556ee6;
        margin-bottom: 15px;
    }
    .upload-zone h5 {
        color: #495057;
        margin-bottom: 8px;
    }
    .upload-zone p {
        color: #74788d;
        margin-bottom: 0;
    }
    .kb-item {
        border: 1px solid #e9ecef;
        border-radius: 6px;
        padding: 12px 15px;
        margin-bottom: 10px;
        background-color: #fff;
        transition: all 0.2s ease;
    }
    .kb-item:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .kb-item.type-doc { border-left: 4px solid #556ee6; }
    .kb-item.type-website { border-left: 4px solid #34c38f; }
    .kb-item.type-image { border-left: 4px solid #f1b44c; }
    .item-icon {
        width: 40px;
        height: 40px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }
    .item-icon.doc { background-color: rgba(85, 110, 230, 0.1); color: #556ee6; }
    .item-icon.website { background-color: rgba(52, 195, 143, 0.1); color: #34c38f; }
    .item-icon.image { background-color: rgba(241, 180, 76, 0.1); color: #f1b44c; }
    .item-icon.pdf { background-color: rgba(244, 67, 54, 0.1); color: #f44336; }
    .item-icon.txt { background-color: rgba(33, 150, 243, 0.1); color: #2196f3; }
    .item-icon.json { background-color: rgba(255, 152, 0, 0.1); color: #ff9800; }
    .item-icon.csv { background-color: rgba(76, 175, 80, 0.1); color: #4caf50; }
    .image-thumbnail {
        width: 50px;
        height: 50px;
        border-radius: 6px;
        object-fit: cover;
        border: 1px solid #e9ecef;
    }
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
    .empty-state {
        padding: 60px 20px;
        text-align: center;
    }
    .empty-state i {
        font-size: 64px;
        color: #c3cbe4;
        margin-bottom: 20px;
    }
    .upload-progress {
        display: none;
    }
    .upload-progress.active {
        display: block;
    }
    .type-selector {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
    }
    .type-selector .type-option {
        flex: 1;
        padding: 20px 15px;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .type-selector .type-option:hover {
        border-color: #556ee6;
        background-color: rgba(85, 110, 230, 0.05);
    }
    .type-selector .type-option.selected {
        border-color: #556ee6;
        background-color: rgba(85, 110, 230, 0.1);
    }
    .type-selector .type-option i {
        font-size: 32px;
        margin-bottom: 10px;
    }
    .type-selector .type-option.doc-type i { color: #556ee6; }
    .type-selector .type-option.website-type i { color: #34c38f; }
    .type-selector .type-option.image-type i { color: #f1b44c; }
    .type-selector .type-option.product-type i { color: #e83e8c; }
    .kb-item.type-product { border-left: 4px solid #e83e8c; }
    .item-icon.product { background-color: rgba(232, 62, 140, 0.1); color: #e83e8c; }
    .type-selector .type-option h6 {
        margin-bottom: 5px;
        color: #495057;
    }
    .type-selector .type-option p {
        font-size: 12px;
        color: #74788d;
        margin-bottom: 0;
    }
    .filter-tabs .nav-link {
        padding: 8px 16px;
        font-size: 13px;
        border-radius: 20px;
        margin-right: 8px;
        color: #495057;
    }
    .filter-tabs .nav-link.active {
        background-color: #556ee6;
        color: white;
    }
    .filter-tabs .nav-link .badge {
        font-size: 10px;
        margin-left: 5px;
    }
    .website-url {
        font-family: monospace;
        font-size: 0.85rem;
        color: #556ee6;
        word-break: break-all;
    }
    .description-textarea {
        resize: vertical;
        min-height: 80px;
    }
    .scrape-progress-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
    }
    .scrape-progress-overlay.active {
        display: flex;
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
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') AI Technician @endslot
        @slot('title') Knowledge Base @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card settings-card">
                <div class="card-body">
                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs nav-tabs-custom nav-justified" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#knowledgeBaseTab" role="tab">
                                <i class="bx bx-data me-1"></i>
                                <span class="d-none d-sm-inline">Knowledge Base</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#addContentTab" role="tab">
                                <i class="bx bx-plus-circle me-1"></i>
                                <span class="d-none d-sm-inline">Add Content</span>
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
                        <!-- Knowledge Base Tab -->
                        <div class="tab-pane active" id="knowledgeBaseTab" role="tabpanel">
                            <!-- Filter Tabs -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <ul class="nav filter-tabs" id="typeFilter">
                                    <li class="nav-item">
                                        <a class="nav-link active" href="#" data-filter="all">
                                            All <span class="badge bg-secondary" id="countAll">{{ $files->count() + $websites->count() + $images->count() + ($products ?? collect())->count() }}</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="#" data-filter="doc">
                                            <i class="bx bx-file me-1"></i>Docs <span class="badge bg-primary">{{ $files->count() }}</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="#" data-filter="website">
                                            <i class="bx bx-globe me-1"></i>Websites <span class="badge bg-success">{{ $websites->count() }}</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="#" data-filter="image">
                                            <i class="bx bx-image me-1"></i>Images <span class="badge bg-warning text-dark">{{ $images->count() }}</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="#" data-filter="product">
                                            <i class="bx bx-package me-1"></i>External Products <span class="badge text-white" style="background-color: #e83e8c;" id="countProducts">{{ ($products ?? collect())->count() }}</span>
                                        </a>
                                    </li>
                                </ul>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-primary d-inline-flex align-items-center" id="addContentBtn">
                                        <i class="bx bx-plus me-1"></i> <span>Add Content</span>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-soft-secondary d-inline-flex align-items-center" id="refreshAllBtn">
                                        <i class="bx bx-refresh me-1"></i> <span>Refresh</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Items List -->
                            <div id="kbItemsList">
                                @if($files->isEmpty() && $websites->isEmpty() && $images->isEmpty() && ($products ?? collect())->isEmpty())
                                    <div class="empty-state" id="emptyState">
                                        <i class="bx bx-data"></i>
                                        <h5 class="text-dark">No content in knowledge base</h5>
                                        <p class="text-secondary mb-0">Click the "Add Content" button above to add documents, websites, images, or products.</p>
                                    </div>
                                @else
                                    <!-- Documents -->
                                    @foreach($files as $file)
                                        <div class="kb-item type-doc" data-type="doc" data-id="doc-{{ $file->id }}">
                                            <div class="d-flex align-items-center">
                                                <div class="item-icon {{ strtolower(pathinfo($file->originalName, PATHINFO_EXTENSION)) }}">
                                                    <i class="bx bx-file"></i>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h6 class="mb-1 text-dark text-truncate" style="max-width: 500px;" title="{{ $file->originalName }}">
                                                        {{ $file->originalName }}
                                                    </h6>
                                                    <small class="text-secondary">
                                                        <span class="badge bg-primary">Document</span>
                                                        {{ $file->formatted_file_size }} &bull; {{ $file->created_at->format('M d, Y H:i') }}
                                                    </small>
                                                </div>
                                                <div class="d-flex align-items-center gap-1">
                                                    <span class="badge {{ $file->status_badge_class }}">{{ $file->status_display }}</span>

                                                    <div class="btn-group ms-2">
                                                        <!-- View Document Button -->
                                                        <button type="button" class="btn btn-sm btn-soft-primary view-doc-btn"
                                                            data-id="{{ $file->id }}"
                                                            data-name="{{ $file->originalName }}"
                                                            data-path="{{ $file->filePath }}"
                                                            data-type="{{ strtolower(pathinfo($file->originalName, PATHINFO_EXTENSION)) }}"
                                                            title="View Document">
                                                            <i class="bx bx-show"></i>
                                                        </button>

                                                        @if($file->isProcessing())
                                                            <button type="button" class="btn btn-sm btn-soft-info refresh-doc-btn" data-id="{{ $file->id }}" title="Refresh Status">
                                                                <i class="bx bx-sync"></i>
                                                            </button>
                                                        @endif

                                                        @if($file->canRetry())
                                                            <button type="button" class="btn btn-sm btn-soft-warning retry-doc-btn" data-id="{{ $file->id }}" title="Retry">
                                                                <i class="bx bx-refresh"></i>
                                                            </button>
                                                        @endif

                                                        <button type="button" class="btn btn-sm btn-soft-danger delete-doc-btn" data-id="{{ $file->id }}" data-name="{{ $file->originalName }}" title="Delete">
                                                            <i class="bx bx-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            @if($file->status === 'failed' && $file->errorMessage)
                                                <div class="mt-2">
                                                    <small class="text-danger"><i class="bx bx-error-circle me-1"></i>{{ $file->errorMessage }}</small>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach

                                    <!-- Websites -->
                                    @foreach($websites as $website)
                                        <div class="kb-item type-website" data-type="website" data-id="website-{{ $website->id }}">
                                            <div class="d-flex align-items-center">
                                                <div class="item-icon website">
                                                    <i class="bx bx-globe"></i>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h6 class="mb-1 text-dark">{{ $website->websiteName }}</h6>
                                                    <small class="website-url d-block">{{ $website->websiteUrl }}</small>
                                                    <small class="text-secondary">
                                                        <span class="badge bg-success">Website</span>
                                                        {{ $website->pages_count ?? 0 }} pages
                                                        @if($website->lastScrapedAt)
                                                            &bull; Last scraped {{ $website->lastScrapedAt->diffForHumans() }}
                                                        @endif
                                                    </small>
                                                </div>
                                                <div class="d-flex align-items-center flex-wrap gap-1">
                                                    <!-- Scrape Status Badge -->
                                                    <span class="badge {{ $website->status_badge_class ?? 'bg-secondary' }}">{{ $website->status_display ?? 'Pending' }}</span>

                                                    <!-- Pinecone Status Badge -->
                                                    @if($website->pineconeStatus)
                                                        <span class="badge {{ $website->pineconeStatus === 'indexed' ? 'bg-primary' : ($website->pineconeStatus === 'processing' ? 'bg-info text-dark' : ($website->pineconeStatus === 'failed' ? 'bg-danger' : 'bg-secondary')) }}">
                                                            RAG: {{ ucfirst($website->pineconeStatus) }}
                                                        </span>
                                                    @endif

                                                    <!-- Action Buttons -->
                                                    <div class="btn-group ms-2">
                                                        <!-- Scrape Button - always available -->
                                                        <button type="button" class="btn btn-sm btn-soft-info scrape-website-btn" data-id="{{ $website->id }}" title="Scrape Website">
                                                            <i class="bx bx-refresh"></i>
                                                        </button>

                                                        <!-- Upload to RAG Button - show after scraping -->
                                                        @if($website->lastScrapeStatus === 'success' && $website->pages_count > 0)
                                                            <button type="button" class="btn btn-sm btn-soft-success upload-website-rag-btn" data-id="{{ $website->id }}" title="Upload to Knowledge Base">
                                                                <i class="bx bx-cloud-upload"></i>
                                                            </button>
                                                        @endif

                                                        <!-- View Pages Button -->
                                                        @if($website->pages_count > 0)
                                                            <button type="button" class="btn btn-sm btn-soft-secondary view-pages-btn" data-id="{{ $website->id }}" data-name="{{ $website->websiteName }}" title="View Scraped Pages">
                                                                <i class="bx bx-list-ul"></i>
                                                            </button>
                                                        @endif

                                                        <!-- Delete Button -->
                                                        <button type="button" class="btn btn-sm btn-soft-danger delete-website-btn" data-id="{{ $website->id }}" data-name="{{ $website->websiteName }}" title="Delete">
                                                            <i class="bx bx-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            @if($website->pineconeError)
                                                <div class="mt-2">
                                                    <small class="text-danger"><i class="bx bx-error-circle me-1"></i>{{ $website->pineconeError }}</small>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach

                                    <!-- Images -->
                                    @foreach($images as $image)
                                        <div class="kb-item type-image" data-type="image" data-id="image-{{ $image->id }}">
                                            <div class="d-flex align-items-start">
                                                <img src="{{ $image->thumbnail_url }}" alt="{{ $image->originalName }}" class="image-thumbnail me-3">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1 text-dark text-truncate" style="max-width: 400px;" title="{{ $image->originalName }}">
                                                        {{ $image->originalName }}
                                                    </h6>
                                                    <p class="text-secondary mb-1 small">{{ $image->description_short }}</p>
                                                    <small class="text-secondary">
                                                        <span class="badge bg-warning text-dark">Image</span>
                                                        {{ $image->file_size_human }} &bull; {{ $image->created_at->format('M d, Y H:i') }}
                                                    </small>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <span class="badge {{ $image->pinecone_status_badge }} me-2">{{ $image->pinecone_status_display }}</span>
                                                    <!-- View Image Button -->
                                                    <button type="button" class="btn btn-sm btn-soft-primary view-image-btn me-1"
                                                        data-id="{{ $image->id }}"
                                                        data-name="{{ $image->originalName }}"
                                                        data-url="{{ $image->thumbnail_url }}"
                                                        title="View Image & Analysis">
                                                        <i class="bx bx-show"></i>
                                                    </button>
                                                    <!-- Hidden data for description and analysis -->
                                                    <script type="application/json" id="image-data-{{ $image->id }}">
                                                        {!! json_encode(['description' => $image->description, 'analysis' => $image->aiAnalysis]) !!}
                                                    </script>
                                                    @if($image->isPending())
                                                        <button type="button" class="btn btn-sm btn-soft-success sync-image-btn me-1" data-id="{{ $image->id }}" title="Sync to Pinecone">
                                                            <i class="bx bx-cloud-upload"></i>
                                                        </button>
                                                    @endif
                                                    @if($image->isProcessing())
                                                        <button type="button" class="btn btn-sm btn-soft-info refresh-image-btn me-1" data-id="{{ $image->id }}" title="Refresh Status">
                                                            <i class="bx bx-sync"></i>
                                                        </button>
                                                    @endif
                                                    @if($image->canRetry())
                                                        <button type="button" class="btn btn-sm btn-soft-warning retry-image-btn me-1" data-id="{{ $image->id }}" title="Retry Upload">
                                                            <i class="bx bx-refresh"></i>
                                                        </button>
                                                    @endif
                                                    <button type="button" class="btn btn-sm btn-soft-secondary edit-image-btn me-1" data-id="{{ $image->id }}" data-description="{{ $image->description }}" title="Edit">
                                                        <i class="bx bx-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-soft-danger delete-image-btn" data-id="{{ $image->id }}" data-name="{{ $image->originalName }}" title="Delete">
                                                        <i class="bx bx-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach

                                    <!-- External Products -->
                                    @foreach($products ?? [] as $product)
                                        <div class="kb-item type-product" data-type="product" data-id="product-{{ $product->id }}">
                                            <div class="d-flex align-items-start">
                                                @if($product->primary_image_url)
                                                    <img src="{{ $product->primary_image_url }}" alt="{{ $product->productName }}" class="image-thumbnail me-3">
                                                @else
                                                    <div class="item-icon product me-3">
                                                        <i class="bx bx-package"></i>
                                                    </div>
                                                @endif
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1 text-dark">
                                                        {{ $product->productName }}
                                                        @if($product->brandName)
                                                            <small class="text-secondary">({{ $product->brandName }})</small>
                                                        @endif
                                                        @if($product->image_count > 0)
                                                            <span class="badge bg-light text-dark ms-1" title="{{ $product->image_count }} image(s)">
                                                                <i class="bx bx-image me-1"></i>{{ $product->image_count }}
                                                            </span>
                                                        @endif
                                                    </h6>
                                                    <p class="text-secondary mb-1 small">
                                                        {{ $product->summary ?? ($product->purpose ?? 'Processing...') }}
                                                    </p>
                                                    <small class="text-secondary">
                                                        <span class="badge text-white" style="background-color: #e83e8c;">{{ $product->type_display }}</span>
                                                        @if($product->manufacturer)
                                                            {{ $product->manufacturer }} &bull;
                                                        @endif
                                                        {{ $product->created_at->format('M d, Y H:i') }}
                                                    </small>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    {!! $product->rag_status_badge !!}
                                                    <!-- View Product Button -->
                                                    <button type="button" class="btn btn-sm btn-soft-primary view-product-btn ms-2"
                                                        data-id="{{ $product->id }}"
                                                        data-name="{{ $product->productName }}"
                                                        title="View Product Details">
                                                        <i class="bx bx-show"></i>
                                                    </button>
                                                    <!-- Hidden data for product details -->
                                                    <script type="application/json" id="product-data-{{ $product->id }}">
                                                        {!! json_encode([
                                                            'productName' => $product->productName,
                                                            'brandName' => $product->brandName,
                                                            'manufacturer' => $product->manufacturer,
                                                            'productType' => $product->productType,
                                                            'typeDisplay' => $product->type_display,
                                                            'manualText' => $product->manualText,
                                                            'combinedOcrText' => $product->combined_ocr_text,
                                                            'aiAnalysis' => $product->aiAnalysis,
                                                            'primaryImageUrl' => $product->primary_image_url,
                                                            'images' => $product->images->map(fn($img) => [
                                                                'id' => $img->id,
                                                                'imageUrl' => $img->image_url,
                                                                'originalName' => $img->originalName,
                                                                'status' => $img->status,
                                                                'isPrimary' => $img->isPrimary,
                                                                'imageType' => $img->image_type_display,
                                                                'ocrText' => $img->ocrText,
                                                            ]),
                                                            'imageCount' => $product->images->count(),
                                                            'ragStatus' => $product->ragStatus,
                                                            'ragError' => $product->ragError,
                                                        ]) !!}
                                                    </script>
                                                    @if($product->needsProcessing())
                                                        <button type="button" class="btn btn-sm btn-soft-success process-product-btn ms-1" data-id="{{ $product->id }}" title="Process & Index">
                                                            <i class="bx bx-cog"></i>
                                                        </button>
                                                    @endif
                                                    @if($product->ragStatus === 'processing' || $product->ragStatus === 'analyzing' || $product->ragStatus === 'uploading')
                                                        <button type="button" class="btn btn-sm btn-soft-info refresh-product-btn ms-1" data-id="{{ $product->id }}" title="Refresh Status">
                                                            <i class="bx bx-sync"></i>
                                                        </button>
                                                    @endif
                                                    @if($product->ragStatus === 'failed')
                                                        <button type="button" class="btn btn-sm btn-soft-warning retry-product-btn ms-1" data-id="{{ $product->id }}" title="Retry">
                                                            <i class="bx bx-refresh"></i>
                                                        </button>
                                                    @endif
                                                    <button type="button" class="btn btn-sm btn-soft-danger delete-product-btn ms-1" data-id="{{ $product->id }}" data-name="{{ $product->productName }}" title="Delete">
                                                        <i class="bx bx-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            @if($product->ragStatus === 'failed' && $product->ragError)
                                                <div class="mt-2">
                                                    <small class="text-danger"><i class="bx bx-error-circle me-1"></i>{{ $product->ragError }}</small>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>

                        <!-- Add Content Tab -->
                        <div class="tab-pane" id="addContentTab" role="tabpanel">
                            <h5 class="text-dark mb-3">
                                <i class="bx bx-plus-circle me-1"></i> Add Content to Knowledge Base
                            </h5>

                            <!-- Type Selector -->
                            <div class="type-selector" id="typeSelector">
                                <div class="type-option doc-type selected" data-type="doc">
                                    <i class="bx bx-file-blank"></i>
                                    <h6 class="text-dark">Documents</h6>
                                    <p>Upload PDF, TXT, MD files</p>
                                </div>
                                <div class="type-option website-type" data-type="website">
                                    <i class="bx bx-globe"></i>
                                    <h6 class="text-dark">Website</h6>
                                    <p>Scrape website content</p>
                                </div>
                                <div class="type-option image-type" data-type="image">
                                    <i class="bx bx-image"></i>
                                    <h6 class="text-dark">Images</h6>
                                    <p>Images with descriptions</p>
                                </div>
                                <div class="type-option product-type" data-type="product">
                                    <i class="bx bx-package"></i>
                                    <h6 class="text-dark">External Products</h6>
                                    <p>Agri products (pesticides, fertilizers)</p>
                                </div>
                            </div>

                            <!-- Document Upload Section -->
                            <div class="upload-section" id="docUploadSection">
                                <div class="upload-zone" id="docUploadZone">
                                    <i class="bx bx-cloud-upload"></i>
                                    <h5 class="text-dark">Drop files here or click to upload</h5>
                                    <p class="text-secondary">Supported: PDF, TXT, MD, DOC, DOCX, JSON, CSV (Max 50MB)</p>
                                </div>
                                <input type="file" id="docFileInput" style="display: none;" accept=".pdf,.txt,.md,.doc,.docx,.json,.csv">

                                <!-- Upload Progress -->
                                <div class="upload-progress mt-3" id="docUploadProgress">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bx bx-loader-alt bx-spin me-2 text-primary"></i>
                                        <span class="text-dark" id="docUploadFileName">Uploading...</span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar" id="docUploadProgressBar" role="progressbar" style="width: 0%"></div>
                                    </div>
                                </div>

                                <div class="alert alert-info mt-3 mb-0">
                                    <i class="bx bx-info-circle me-1"></i>
                                    Files are processed and indexed into Pinecone for RAG queries.
                                </div>
                            </div>

                            <!-- Website Add Section -->
                            <div class="upload-section" id="websiteUploadSection" style="display: none;">
                                <form id="websiteForm">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="websiteName" class="form-label text-dark">Website Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="websiteName" name="websiteName" placeholder="e.g., Company Blog" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="websiteUrl" class="form-label text-dark">Website URL <span class="text-danger">*</span></label>
                                                <input type="url" class="form-control" id="websiteUrl" name="websiteUrl" placeholder="https://example.com" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="websiteDescription" class="form-label text-dark">Description</label>
                                        <textarea class="form-control" id="websiteDescription" name="description" rows="2" placeholder="Brief description of the website content..."></textarea>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="scrapeType" class="form-label text-dark">Scrape Type</label>
                                                <select class="form-select" id="scrapeType" name="scrapeType">
                                                    <option value="full_page">Full Page Content</option>
                                                    <option value="whole_site">Whole Site (Crawl Links)</option>
                                                    <option value="specific_selector">Specific CSS Selector</option>
                                                    <option value="sitemap">From Sitemap</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="maxPages" class="form-label text-dark">Max Pages</label>
                                                <input type="number" class="form-control" id="maxPages" name="maxPages" value="100" min="1" max="1000">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="maxDepth" class="form-label text-dark">Max Depth</label>
                                                <input type="number" class="form-control" id="maxDepth" name="maxDepth" value="3" min="1" max="10">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3" id="cssSelectorGroup" style="display: none;">
                                        <label for="cssSelector" class="form-label text-dark">CSS Selector</label>
                                        <input type="text" class="form-control" id="cssSelector" name="cssSelector" placeholder=".article-content, #main-content">
                                    </div>
                                    <button type="submit" class="btn btn-primary" id="addWebsiteBtn">
                                        <i class="bx bx-plus me-1"></i> Add Website
                                    </button>
                                </form>

                                <div class="alert alert-info mt-3 mb-0">
                                    <i class="bx bx-info-circle me-1"></i>
                                    After adding, use the scrape button to fetch and index website content.
                                </div>
                            </div>

                            <!-- Image Upload Section -->
                            <div class="upload-section" id="imageUploadSection" style="display: none;">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="upload-zone" id="imageUploadZone">
                                            <i class="bx bx-image-add"></i>
                                            <h5 class="text-dark">Drop image here or click to upload</h5>
                                            <p class="text-secondary">Supported: JPG, PNG, GIF, WebP (Max 10MB)</p>
                                        </div>
                                        <input type="file" id="imageFileInput" style="display: none;" accept=".jpg,.jpeg,.png,.gif,.webp">

                                        <!-- Image Preview -->
                                        <div id="imagePreview" class="mt-3" style="display: none;">
                                            <img id="previewImg" src="" alt="Preview" class="img-fluid rounded" style="max-height: 200px;">
                                            <p class="text-dark mt-2 mb-0"><strong id="previewFileName"></strong></p>
                                            <small class="text-secondary" id="previewFileSize"></small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="imageDescription" class="form-label text-dark">
                                                Description <span class="text-danger">*</span>
                                            </label>
                                            <textarea class="form-control description-textarea" id="imageDescription" rows="4" placeholder="Describe what this image shows. This description will be used for RAG context..." required></textarea>
                                            <small class="text-secondary">Minimum 10 characters. Be descriptive for better AI understanding.</small>
                                        </div>
                                        <button type="button" class="btn btn-primary" id="uploadImageBtn" disabled>
                                            <i class="bx bx-cloud-upload me-1"></i> Upload Image
                                        </button>
                                    </div>
                                </div>

                                <!-- Image Upload Progress -->
                                <div class="upload-progress mt-3" id="imageUploadProgress">
                                    <div class="alert alert-light border mb-0">
                                        <h6 class="text-dark mb-3" id="imageUploadStatus">
                                            <i class="bx bx-loader-alt bx-spin me-2 text-primary"></i>
                                            Uploading image...
                                        </h6>
                                        <div class="progress mb-2" style="height: 6px;">
                                            <div class="progress-bar" id="imageUploadProgressBar" role="progressbar" style="width: 0%"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-info mt-3 mb-0">
                                    <i class="bx bx-info-circle me-1"></i>
                                    Images are analyzed with Vision AI and indexed into Pinecone for visual queries.
                                </div>
                            </div>

                            <!-- Product Upload Section -->
                            <div class="upload-section" id="productUploadSection" style="display: none;">
                                <form id="productForm">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="productName" class="form-label text-dark">Product Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="productName" name="productName" placeholder="e.g., Karate 2.5 EC" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="productBrand" class="form-label text-dark">Brand Name</label>
                                                <input type="text" class="form-control" id="productBrand" name="brandName" placeholder="e.g., Syngenta">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="productManufacturer" class="form-label text-dark">Manufacturer</label>
                                                <input type="text" class="form-control" id="productManufacturer" name="manufacturer" placeholder="e.g., Syngenta Philippines">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="productType" class="form-label text-dark">Product Type <span class="text-danger">*</span></label>
                                                <select class="form-select" id="productType" name="productType" required>
                                                    <optgroup label="Pest Control">
                                                        <option value="pesticide">Pesticide (General)</option>
                                                        <option value="insecticide">Insecticide</option>
                                                        <option value="fungicide">Fungicide</option>
                                                        <option value="herbicide">Herbicide</option>
                                                        <option value="bactericide">Bactericide</option>
                                                        <option value="nematicide">Nematicide</option>
                                                        <option value="molluscicide">Molluscicide (Snail/Slug)</option>
                                                        <option value="rodenticide">Rodenticide</option>
                                                    </optgroup>
                                                    <optgroup label="Fertilizers">
                                                        <option value="fertilizer_granular">Fertilizer - Granular</option>
                                                        <option value="fertilizer_foliar">Fertilizer - Foliar</option>
                                                        <option value="fertilizer_liquid">Fertilizer - Liquid</option>
                                                        <option value="fertilizer_organic">Fertilizer - Organic</option>
                                                    </optgroup>
                                                    <optgroup label="Others">
                                                        <option value="plant_growth_regulator">Plant Growth Regulator</option>
                                                        <option value="soil_conditioner">Soil Conditioner</option>
                                                        <option value="seed_treatment">Seed Treatment</option>
                                                        <option value="adjuvant">Adjuvant/Spreader-Sticker</option>
                                                        <option value="other">Other</option>
                                                    </optgroup>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <!-- Multiple Product Images Upload -->
                                            <div class="mb-3">
                                                <label class="form-label text-dark">Product Images (Labels) <small class="text-secondary">- Up to 10 images</small></label>
                                                <div class="upload-zone py-3" id="productImageZone" style="border-width: 1px;">
                                                    <i class="bx bx-images" style="font-size: 32px;"></i>
                                                    <p class="text-secondary mb-0 small">Click or drag to upload product label images</p>
                                                    <p class="text-secondary mb-0 small">Upload front label, back label, ingredients, instructions, etc.</p>
                                                </div>
                                                <input type="file" id="productImageInput" style="display: none;" accept=".jpg,.jpeg,.png,.gif,.webp" multiple>

                                                <!-- Image Previews Container -->
                                                <div id="productImagesPreview" class="mt-3 d-flex flex-wrap gap-2" style="display: none;"></div>

                                                <small class="text-secondary d-block mt-2">
                                                    <i class="bx bx-info-circle me-1"></i>
                                                    AI will extract text from all images (OCR) and combine the information.
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <!-- Product Documents Upload -->
                                            <div class="mb-3">
                                                <label class="form-label text-dark">Product Documents <small class="text-secondary">- PDF, TXT, DOC, DOCX (Up to 5 files)</small></label>
                                                <div class="upload-zone py-3" id="productDocumentZone" style="border-width: 1px;">
                                                    <i class="bx bx-file" style="font-size: 32px;"></i>
                                                    <p class="text-secondary mb-0 small">Click or drag to upload product documents</p>
                                                    <p class="text-secondary mb-0 small">Upload manuals, specification sheets, data sheets, etc.</p>
                                                </div>
                                                <input type="file" id="productDocumentInput" style="display: none;" accept=".pdf,.txt,.doc,.docx" multiple>

                                                <!-- Document Previews Container -->
                                                <div id="productDocumentsPreview" class="mt-3" style="display: none;"></div>

                                                <small class="text-secondary d-block mt-2">
                                                    <i class="bx bx-info-circle me-1"></i>
                                                    Text will be automatically extracted from documents and indexed for search.
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="mb-3">
                                                <label for="productDescription" class="form-label text-dark">Additional Description / Manual Text</label>
                                                <textarea class="form-control" id="productDescription" name="manualText" rows="4" placeholder="Enter any additional product information, usage instructions, or details not visible in the images..."></textarea>
                                                <small class="text-secondary">Optional. Add any information you know about this product.</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary" id="addProductBtn">
                                            <i class="bx bx-plus me-1"></i> Add External Product
                                        </button>
                                        <span class="text-secondary align-self-center small">
                                            <i class="bx bx-info-circle me-1"></i>
                                            Product will be analyzed by AI and indexed into RAG automatically
                                        </span>
                                    </div>
                                </form>

                                <!-- Product Upload Progress -->
                                <div class="upload-progress mt-3" id="productUploadProgress">
                                    <div class="alert alert-light border mb-0">
                                        <h6 class="text-dark mb-3" id="productUploadStatus">
                                            <i class="bx bx-loader-alt bx-spin me-2 text-primary"></i>
                                            Processing product...
                                        </h6>
                                        <div class="progress mb-2" style="height: 6px;">
                                            <div class="progress-bar" id="productUploadProgressBar" role="progressbar" style="width: 0%"></div>
                                        </div>
                                        <small class="text-secondary" id="productUploadDetails">Uploading...</small>
                                    </div>
                                </div>

                                <div class="alert alert-info mt-3 mb-0">
                                    <i class="bx bx-info-circle me-1"></i>
                                    <strong>How it works:</strong> Product image is analyzed using AI Vision (OCR) to extract label text.
                                    AI then generates comprehensive product details including active ingredients, target pests/diseases,
                                    application methods, and safety precautions. All data is indexed for accurate product recommendations.
                                </div>
                            </div>
                        </div>

                        <!-- Settings Tab -->
                        <div class="tab-pane" id="settingsTab" role="tabpanel">
                            <div class="row justify-content-center">
                                <div class="col-lg-8">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <div>
                                            <h5 class="text-dark mb-1">
                                                <i class="bx bx-key me-1"></i> Pinecone API Settings
                                            </h5>
                                            <p class="text-secondary mb-0">Configure your Pinecone API credentials for knowledge base indexing.</p>
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
                                                <input type="password" class="form-control api-key-input" id="apiKey" name="apiKey" value="{{ $settings->apiKey }}" placeholder="pcsk_xxxxxxxx..." required>
                                                <button class="btn btn-outline-secondary" type="button" id="toggleApiKey">
                                                    <i class="bx bx-show"></i>
                                                </button>
                                            </div>
                                            <small class="text-secondary">Your Pinecone API key from the Pinecone console.</small>
                                        </div>

                                        <div class="mb-3">
                                            <label for="indexName" class="form-label text-dark">Pinecone Assistant Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="indexName" name="indexName" value="{{ $settings->indexName }}" placeholder="e.g., anisenso-kb" required>
                                            <small class="text-secondary">All content (documents, websites, images) will be stored in this single Pinecone Assistant.</small>
                                        </div>

                                        <div class="mb-3">
                                            <label for="indexHost" class="form-label text-dark">Index Host <span class="text-muted">(Optional)</span></label>
                                            <input type="text" class="form-control" id="indexHost" name="indexHost" value="{{ $settings->indexHost }}" placeholder="e.g., https://your-index-xxxxxxx.svc.environment.pinecone.io">
                                            <small class="text-secondary">The host URL of your Pinecone index (if using direct index access).</small>
                                        </div>

                                        <div class="mb-4">
                                            <label for="email" class="form-label text-dark">Account Email <span class="text-muted">(Optional)</span></label>
                                            <input type="email" class="form-control" id="email" name="email" value="{{ $settings->email }}" placeholder="e.g., your@email.com">
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
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark">
                        <i class="bx bx-trash text-danger me-2"></i>Confirm Delete
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-dark">Are you sure you want to delete <strong id="deleteItemName"></strong>?</p>
                    <p class="text-secondary mb-0">This will also remove it from the Pinecone knowledge base.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                        <i class="bx bx-trash me-1"></i> Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Document Modal -->
    <div class="modal fade" id="viewDocModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark">
                        <i class="bx bx-file text-primary me-2"></i>
                        <span id="viewDocTitle">Document</span>
                    </h5>
                    <div class="ms-auto me-3">
                        <a href="#" id="viewDocDownload" class="btn btn-sm btn-soft-primary" download>
                            <i class="bx bx-download me-1"></i> Download
                        </a>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0" style="min-height: 500px; max-height: 70vh;">
                    <!-- PDF Viewer -->
                    <div id="pdfViewer" style="display: none; width: 100%; height: 70vh;">
                        <iframe id="pdfIframe" src="" style="width: 100%; height: 100%; border: none;"></iframe>
                    </div>
                    <!-- Text Content Viewer -->
                    <div id="textViewer" style="display: none; padding: 20px;">
                        <pre id="textContent" class="mb-0" style="white-space: pre-wrap; word-wrap: break-word; font-size: 13px; background: #f8f9fa; padding: 20px; border-radius: 6px; max-height: 60vh; overflow-y: auto;"></pre>
                    </div>
                    <!-- Unsupported Format Message -->
                    <div id="unsupportedViewer" style="display: none; padding: 60px; text-align: center;">
                        <i class="bx bx-file text-secondary" style="font-size: 64px;"></i>
                        <h5 class="text-dark mt-3">Preview not available</h5>
                        <p class="text-secondary">This file type cannot be previewed in browser.</p>
                        <p class="text-secondary">Click the Download button to view this file.</p>
                    </div>
                    <!-- Loading State -->
                    <div id="docLoading" style="display: none; padding: 100px; text-align: center;">
                        <i class="bx bx-loader-alt bx-spin text-primary" style="font-size: 48px;"></i>
                        <p class="text-dark mt-3">Loading document...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Image Description Modal -->
    <div class="modal fade" id="editImageModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark">
                        <i class="bx bx-edit text-primary me-2"></i>Edit Description
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="editImageDescription" class="form-label text-dark">Description</label>
                        <textarea class="form-control description-textarea" id="editImageDescription" rows="4"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmEditImageBtn">
                        <i class="bx bx-save me-1"></i> Save
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Image Modal -->
    <div class="modal fade" id="viewImageModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark">
                        <i class="bx bx-image text-warning me-2"></i>
                        <span id="viewImageTitle">Image</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <!-- Image Display -->
                        <div class="col-lg-6 mb-3 mb-lg-0">
                            <div class="text-center">
                                <img id="viewImagePreview" src="" alt="Image Preview" class="img-fluid rounded shadow-sm" style="max-height: 400px; object-fit: contain;">
                            </div>
                        </div>
                        <!-- Text Content -->
                        <div class="col-lg-6">
                            <!-- User Description -->
                            <div class="mb-4">
                                <h6 class="text-dark mb-2">
                                    <i class="bx bx-comment-detail text-primary me-1"></i> User Description
                                </h6>
                                <div class="p-3 bg-light rounded" style="max-height: 120px; overflow-y: auto;">
                                    <p class="text-dark mb-0" id="viewImageDescription">No description provided.</p>
                                </div>
                            </div>
                            <!-- AI Extracted Text -->
                            <div>
                                <h6 class="text-dark mb-2">
                                    <i class="bx bx-brain text-success me-1"></i> AI Analysis / Extracted Text
                                </h6>
                                <div class="p-3 bg-light rounded" style="max-height: 220px; overflow-y: auto;">
                                    <pre id="viewImageAnalysis" class="mb-0 text-dark" style="white-space: pre-wrap; word-wrap: break-word; font-size: 13px; font-family: inherit;">No AI analysis available yet.</pre>
                                </div>
                                <small class="text-secondary mt-2 d-block">
                                    <i class="bx bx-info-circle me-1"></i>
                                    This text is extracted by AI vision and indexed for knowledge base queries.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Product Modal -->
    <div class="modal fade" id="viewProductModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header" style="background-color: rgba(232, 62, 140, 0.1);">
                    <h5 class="modal-title text-dark">
                        <i class="bx bx-package me-2" style="color: #e83e8c;"></i>
                        <span id="viewProductTitle">Product Details</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <!-- Product Images -->
                        <div class="col-lg-4 mb-3 mb-lg-0">
                            <!-- Main Image Display -->
                            <div class="text-center mb-2" id="viewProductImageContainer">
                                <img id="viewProductImage" src="" alt="Product Image" class="img-fluid rounded shadow-sm" style="max-height: 220px; object-fit: contain; cursor: pointer;" title="Click to enlarge">
                            </div>
                            <div id="viewProductNoImage" class="text-center py-4 bg-light rounded" style="display: none;">
                                <i class="bx bx-package text-secondary" style="font-size: 64px;"></i>
                                <p class="text-secondary mb-0 mt-2">No images uploaded</p>
                            </div>

                            <!-- Image Thumbnails Gallery -->
                            <div id="viewProductImagesGallery" class="d-flex flex-wrap gap-2 justify-content-center mb-3" style="display: none;"></div>

                            <!-- Basic Info -->
                            <div class="mt-2">
                                <h6 class="text-dark border-bottom pb-2"><i class="bx bx-info-circle me-1"></i> Basic Information</h6>
                                <table class="table table-sm mb-0">
                                    <tr>
                                        <td class="text-secondary" style="width: 40%;">Brand:</td>
                                        <td class="text-dark" id="viewProductBrand">-</td>
                                    </tr>
                                    <tr>
                                        <td class="text-secondary">Manufacturer:</td>
                                        <td class="text-dark" id="viewProductManufacturer">-</td>
                                    </tr>
                                    <tr>
                                        <td class="text-secondary">Type:</td>
                                        <td><span class="badge text-white" style="background-color: #e83e8c;" id="viewProductType">-</span></td>
                                    </tr>
                                    <tr>
                                        <td class="text-secondary">Images:</td>
                                        <td class="text-dark" id="viewProductImageCount">0</td>
                                    </tr>
                                    <tr>
                                        <td class="text-secondary">RAG Status:</td>
                                        <td id="viewProductRagStatus">-</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Product Details -->
                        <div class="col-lg-8">
                            <!-- AI Analysis Section -->
                            <div class="mb-3" id="viewProductAnalysisSection">
                                <h6 class="text-dark border-bottom pb-2"><i class="bx bx-brain text-success me-1"></i> AI Analysis</h6>

                                <!-- Summary -->
                                <div class="mb-3" id="viewProductSummarySection">
                                    <label class="form-label text-dark fw-semibold small mb-1">Summary</label>
                                    <p class="text-dark mb-0 small" id="viewProductSummary">-</p>
                                </div>

                                <!-- Purpose -->
                                <div class="mb-3" id="viewProductPurposeSection">
                                    <label class="form-label text-dark fw-semibold small mb-1">Purpose</label>
                                    <p class="text-dark mb-0 small" id="viewProductPurpose">-</p>
                                </div>

                                <!-- Active Ingredients -->
                                <div class="mb-3" id="viewProductIngredientsSection">
                                    <label class="form-label text-dark fw-semibold small mb-1">Active Ingredients</label>
                                    <div id="viewProductIngredients" class="small">-</div>
                                </div>

                                <!-- Target Pests -->
                                <div class="mb-3" id="viewProductPestsSection">
                                    <label class="form-label text-dark fw-semibold small mb-1">Target Pests</label>
                                    <div id="viewProductPests">-</div>
                                </div>

                                <!-- Target Diseases -->
                                <div class="mb-3" id="viewProductDiseasesSection">
                                    <label class="form-label text-dark fw-semibold small mb-1">Target Diseases</label>
                                    <div id="viewProductDiseases">-</div>
                                </div>

                                <!-- Target Crops -->
                                <div class="mb-3" id="viewProductCropsSection">
                                    <label class="form-label text-dark fw-semibold small mb-1">Target Crops</label>
                                    <div id="viewProductCrops">-</div>
                                </div>

                                <!-- Application -->
                                <div class="row mb-3">
                                    <div class="col-md-6" id="viewProductApplicationSection">
                                        <label class="form-label text-dark fw-semibold small mb-1">Application Method</label>
                                        <p class="text-dark mb-0 small" id="viewProductApplication">-</p>
                                    </div>
                                    <div class="col-md-6" id="viewProductDosageSection">
                                        <label class="form-label text-dark fw-semibold small mb-1">Dosage</label>
                                        <p class="text-dark mb-0 small" id="viewProductDosage">-</p>
                                    </div>
                                </div>

                                <!-- Safety Precautions -->
                                <div class="mb-3" id="viewProductSafetySection">
                                    <label class="form-label text-dark fw-semibold small mb-1">Safety Precautions</label>
                                    <ul class="mb-0 small ps-3" id="viewProductSafety"></ul>
                                </div>

                                <!-- Search Tags -->
                                <div class="mb-3" id="viewProductTagsSection">
                                    <label class="form-label text-dark fw-semibold small mb-1">Search Tags</label>
                                    <div id="viewProductTags">-</div>
                                </div>
                            </div>

                            <!-- No Analysis Message -->
                            <div class="alert alert-warning mb-3" id="viewProductNoAnalysis" style="display: none;">
                                <i class="bx bx-info-circle me-1"></i>
                                AI analysis not yet available. Click "Process & Index" to analyze this product.
                            </div>

                            <!-- Manual Text -->
                            <div class="mb-3" id="viewProductManualSection">
                                <h6 class="text-dark border-bottom pb-2"><i class="bx bx-edit me-1"></i> User-Provided Description</h6>
                                <div class="p-2 bg-light rounded small" style="max-height: 100px; overflow-y: auto;">
                                    <p class="text-dark mb-0" id="viewProductManualText">No additional description provided.</p>
                                </div>
                            </div>

                            <!-- OCR Text -->
                            <div class="mb-3" id="viewProductOcrSection">
                                <h6 class="text-dark border-bottom pb-2"><i class="bx bx-text me-1"></i> Extracted Label Text (OCR)</h6>
                                <div class="p-2 bg-light rounded small" style="max-height: 120px; overflow-y: auto;">
                                    <pre class="text-dark mb-0" id="viewProductOcrText" style="white-space: pre-wrap; word-wrap: break-word; font-family: inherit;">No text extracted yet.</pre>
                                </div>
                            </div>

                            <!-- Error Message -->
                            <div class="alert alert-danger mb-0" id="viewProductError" style="display: none;">
                                <i class="bx bx-error-circle me-1"></i>
                                <span id="viewProductErrorText"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scrape Progress Overlay -->
    <div class="scrape-progress-overlay" id="scrapeProgressOverlay">
        <div class="scrape-progress-card">
            <div class="scrape-spinner"></div>
            <h5 class="text-dark mb-2">Scraping Website</h5>
            <p class="text-secondary mb-3" id="scrapeProgressStatus">Initializing...</p>
            <div class="progress mb-2" style="height: 8px;">
                <div class="progress-bar bg-success" id="scrapeProgressBar" role="progressbar" style="width: 0%"></div>
            </div>
            <small class="text-secondary" id="scrapeProgressDetails">0 pages scraped</small>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
$(document).ready(function() {
    const csrfToken = '{{ csrf_token() }}';
    let itemToDelete = null;
    let imageToEdit = null;
    let selectedImageFile = null;

    // Toastr configuration
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };

    // ==================== CHECK RAG INDEX STATUS ON PAGE LOAD ====================

    function checkRagIndexStatus() {
        // Only check if there are items with pineconeFileId
        const hasIndexedItems = $('.kb-item').length > 0;
        if (!hasIndexedItems) return;

        // Show checking indicator
        const $refreshBtn = $('#refreshAllBtn');
        const originalHtml = $refreshBtn.html();
        $refreshBtn.html('<i class="bx bx-loader-alt bx-spin me-1"></i> <span>Checking...</span>').prop('disabled', true);

        $.ajax({
            url: '{{ route("ai-technician.knowledge-base.check-index-status") }}',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    // Update UI based on results
                    const results = response.results;

                    // Update document statuses
                    if (results.docs) {
                        Object.entries(results.docs).forEach(([id, data]) => {
                            updateItemStatus('doc', id, data);
                        });
                    }

                    // Update website statuses
                    if (results.websites) {
                        Object.entries(results.websites).forEach(([id, data]) => {
                            updateItemStatus('website', id, data);
                        });
                    }

                    // Update image statuses
                    if (results.images) {
                        Object.entries(results.images).forEach(([id, data]) => {
                            updateItemStatus('image', id, data);
                        });
                    }

                    // Show summary if there were changes
                    if (results.missing_count > 0) {
                        toastr.warning(`${results.missing_count} item(s) no longer in RAG and need re-upload`, 'RAG Status');
                    } else if (results.processing_count > 0) {
                        toastr.info(`${results.processing_count} item(s) still processing in RAG`, 'RAG Status');
                    }

                    console.log('RAG status check:', response.message);
                }
            },
            error: function(xhr) {
                console.error('RAG status check failed:', xhr.responseJSON?.message);
            },
            complete: function() {
                $refreshBtn.html(originalHtml).prop('disabled', false);
            }
        });
    }

    function updateItemStatus(type, id, data) {
        const $item = $(`.kb-item[data-type="${type}"][data-id="${type}-${id}"]`);
        if (!$item.length) return;

        const $badge = $item.find('.badge').first();
        if (!$badge.length) return;

        if (!data.indexed && data.localStatus !== 'pending') {
            // Item was removed from RAG - update badge to show pending
            $badge.removeClass('bg-success bg-info bg-warning bg-danger')
                  .addClass('bg-warning')
                  .text('Pending');

            // Show sync button if not already visible
            if (!$item.find('.sync-doc-btn, .upload-website-btn, .sync-image-btn').length) {
                // Item needs re-upload - could add a visual indicator here
                $item.find('.d-flex.align-items-center').last()
                    .prepend('<span class="badge bg-danger me-1" title="Needs re-upload"><i class="bx bx-error-circle"></i></span>');
            }
        } else if (data.indexed && data.pineconeStatus === 'available') {
            $badge.removeClass('bg-warning bg-info bg-danger')
                  .addClass('bg-success')
                  .text('Indexed');
        } else if (data.indexed && data.pineconeStatus === 'processing') {
            $badge.removeClass('bg-success bg-warning bg-danger')
                  .addClass('bg-info')
                  .text('Processing');
        }
    }

    // Check RAG status on page load (with slight delay to not block initial render)
    setTimeout(checkRagIndexStatus, 500);

    // ==================== TYPE FILTER ====================

    $('#typeFilter .nav-link').on('click', function(e) {
        e.preventDefault();
        const filter = $(this).data('filter');

        $('#typeFilter .nav-link').removeClass('active');
        $(this).addClass('active');

        if (filter === 'all') {
            $('.kb-item').show();
        } else {
            $('.kb-item').hide();
            $(`.kb-item[data-type="${filter}"]`).show();
        }
    });

    // ==================== TYPE SELECTOR ====================

    $('#typeSelector .type-option').on('click', function() {
        const type = $(this).data('type');

        $('#typeSelector .type-option').removeClass('selected');
        $(this).addClass('selected');

        $('.upload-section').hide();
        $(`#${type}UploadSection`).show();
    });

    // Show/hide CSS selector field based on scrape type
    $('#scrapeType').on('change', function() {
        if ($(this).val() === 'specific_selector') {
            $('#cssSelectorGroup').show();
        } else {
            $('#cssSelectorGroup').hide();
        }
    });

    // ==================== TOGGLE API KEY ====================

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

    // ==================== SAVE SETTINGS ====================

    $('#settingsForm').on('submit', function(e) {
        e.preventDefault();

        const $btn = $('#saveSettingsBtn');
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Saving...');

        $.ajax({
            url: '{{ route("ai-technician.knowledge-base.settings.save") }}',
            type: 'POST',
            data: {
                _token: csrfToken,
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

    // ==================== TEST CONNECTION ====================

    $('#testConnectionBtn').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Testing...');

        $.ajax({
            url: '{{ route("ai-technician.knowledge-base.settings.test") }}',
            type: 'POST',
            data: { _token: csrfToken },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Connection Successful');
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

    // ==================== DOCUMENT UPLOAD ====================

    const docUploadZone = $('#docUploadZone');
    const docFileInput = $('#docFileInput');

    docUploadZone.on('click', function(e) {
        e.preventDefault();
        docFileInput[0].click();
    });

    docUploadZone.on('dragover', function(e) {
        e.preventDefault();
        $(this).addClass('drag-over');
    });

    docUploadZone.on('dragleave', function() {
        $(this).removeClass('drag-over');
    });

    docUploadZone.on('drop', function(e) {
        e.preventDefault();
        $(this).removeClass('drag-over');
        const files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            uploadDocument(files[0]);
        }
    });

    docFileInput.on('change', function() {
        if (this.files.length > 0) {
            uploadDocument(this.files[0]);
        }
    });

    function uploadDocument(file) {
        const allowedExtensions = ['pdf', 'txt', 'md', 'doc', 'docx', 'json', 'csv'];
        const extension = file.name.split('.').pop().toLowerCase();

        if (!allowedExtensions.includes(extension)) {
            toastr.error('Unsupported file type. Please upload PDF, TXT, MD, DOC, DOCX, JSON, or CSV files.', 'Error');
            return;
        }

        if (file.size > 50 * 1024 * 1024) {
            toastr.error('File size must be less than 50MB.', 'Error');
            return;
        }

        const formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('file', file);

        $('#docUploadProgress').addClass('active');
        $('#docUploadFileName').text('Uploading ' + file.name + '...');
        $('#docUploadProgressBar').css('width', '0%');

        $.ajax({
            url: '{{ route("ai-technician.knowledge-base.upload-doc") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percent = (e.loaded / e.total) * 100;
                        $('#docUploadProgressBar').css('width', percent + '%');
                    }
                });
                return xhr;
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Success');
                    location.reload();
                } else {
                    toastr.error(response.message || 'Upload failed', 'Error');
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                if (xhr.status === 409 && response?.isDuplicate) {
                    toastr.warning(response.message, 'Duplicate File');
                } else {
                    toastr.error(response?.message || 'Upload failed', 'Error');
                }
            },
            complete: function() {
                $('#docUploadProgress').removeClass('active');
                docFileInput.val('');
            }
        });
    }

    // ==================== WEBSITE ADD ====================

    $('#websiteForm').on('submit', function(e) {
        e.preventDefault();

        const $btn = $('#addWebsiteBtn');
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Adding...');

        $.ajax({
            url: '{{ route("ai-technician.knowledge-base.add-website") }}',
            type: 'POST',
            data: {
                _token: csrfToken,
                websiteName: $('#websiteName').val(),
                websiteUrl: $('#websiteUrl').val(),
                description: $('#websiteDescription').val(),
                scrapeType: $('#scrapeType').val(),
                maxPages: $('#maxPages').val(),
                maxDepth: $('#maxDepth').val(),
                cssSelector: $('#cssSelector').val(),
                scrapeFrequency: 'manual',
                isActive: true
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Success');
                    location.reload();
                } else {
                    toastr.error(response.message || 'Failed to add website', 'Error');
                }
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors;
                if (errors) {
                    Object.values(errors).forEach(function(msgs) {
                        toastr.error(msgs[0], 'Validation Error');
                    });
                } else {
                    toastr.error(xhr.responseJSON?.message || 'Failed to add website', 'Error');
                }
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-plus me-1"></i> Add Website');
            }
        });
    });

    // ==================== IMAGE UPLOAD ====================

    const imageUploadZone = $('#imageUploadZone');
    const imageFileInput = $('#imageFileInput');

    imageUploadZone.on('click', function(e) {
        e.preventDefault();
        imageFileInput[0].click();
    });

    imageUploadZone.on('dragover', function(e) {
        e.preventDefault();
        $(this).addClass('drag-over');
    });

    imageUploadZone.on('dragleave', function() {
        $(this).removeClass('drag-over');
    });

    imageUploadZone.on('drop', function(e) {
        e.preventDefault();
        $(this).removeClass('drag-over');
        const files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            handleImageSelect(files[0]);
        }
    });

    imageFileInput.on('change', function() {
        if (this.files.length > 0) {
            handleImageSelect(this.files[0]);
        }
    });

    function handleImageSelect(file) {
        const allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        const extension = file.name.split('.').pop().toLowerCase();

        if (!allowedExtensions.includes(extension)) {
            toastr.error('Unsupported file type. Please upload JPG, PNG, GIF, or WebP.', 'Error');
            return;
        }

        if (file.size > 10 * 1024 * 1024) {
            toastr.error('File size must be less than 10MB.', 'Error');
            return;
        }

        selectedImageFile = file;

        const reader = new FileReader();
        reader.onload = function(e) {
            $('#previewImg').attr('src', e.target.result);
            $('#previewFileName').text(file.name);
            $('#previewFileSize').text(formatFileSize(file.size));
            $('#imagePreview').show();
        };
        reader.readAsDataURL(file);

        updateImageUploadButton();
    }

    function formatFileSize(bytes) {
        if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
        if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' KB';
        return bytes + ' bytes';
    }

    $('#imageDescription').on('input', updateImageUploadButton);

    function updateImageUploadButton() {
        const hasFile = selectedImageFile !== null;
        const hasDescription = $('#imageDescription').val().trim().length >= 10;
        $('#uploadImageBtn').prop('disabled', !(hasFile && hasDescription));
    }

    $('#uploadImageBtn').on('click', function() {
        if (!selectedImageFile) {
            toastr.error('Please select an image first.', 'Error');
            return;
        }

        const description = $('#imageDescription').val().trim();
        if (description.length < 10) {
            toastr.error('Description must be at least 10 characters.', 'Error');
            return;
        }

        const formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('image', selectedImageFile);
        formData.append('description', description);

        $('#imageUploadProgress').addClass('active');
        $('#imageUploadProgressBar').css('width', '0%');
        $('#uploadImageBtn').prop('disabled', true);

        $.ajax({
            url: '{{ route("ai-technician.knowledge-base.upload-image") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percent = (e.loaded / e.total) * 100;
                        $('#imageUploadProgressBar').css('width', percent + '%');
                    }
                });
                return xhr;
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Success');
                    location.reload();
                } else {
                    toastr.error(response.message || 'Upload failed', 'Error');
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                if (xhr.status === 409 && response?.isDuplicate) {
                    toastr.warning(response.message, 'Duplicate Image');
                } else {
                    toastr.error(response?.message || 'Upload failed', 'Error');
                }
            },
            complete: function() {
                $('#imageUploadProgress').removeClass('active');
                $('#uploadImageBtn').prop('disabled', false);
                imageFileInput.val('');
                selectedImageFile = null;
            }
        });
    });

    // ==================== VIEW DOCUMENT ====================

    $(document).on('click', '.view-doc-btn', function() {
        const docId = $(this).data('id');
        const docName = $(this).data('name');
        const docPath = $(this).data('path');
        const docType = $(this).data('type');

        // Set title and download link
        $('#viewDocTitle').text(docName);
        $('#viewDocDownload').attr('href', '{{ asset("storage") }}/' + docPath);

        // Hide all viewers, show loading
        $('#pdfViewer, #textViewer, #unsupportedViewer').hide();
        $('#docLoading').show();

        // Determine how to display based on file type
        const textTypes = ['txt', 'md', 'json', 'csv'];
        const pdfTypes = ['pdf'];

        setTimeout(function() {
            $('#docLoading').hide();

            if (pdfTypes.includes(docType)) {
                // PDF - show in iframe
                $('#pdfIframe').attr('src', '{{ asset("storage") }}/' + docPath);
                $('#pdfViewer').show();
            } else if (textTypes.includes(docType)) {
                // Text files - fetch and display content
                $.ajax({
                    url: '{{ asset("storage") }}/' + docPath,
                    type: 'GET',
                    dataType: 'text',
                    success: function(content) {
                        // For JSON, try to pretty print
                        if (docType === 'json') {
                            try {
                                content = JSON.stringify(JSON.parse(content), null, 2);
                            } catch (e) {
                                // Not valid JSON, show as-is
                            }
                        }
                        $('#textContent').text(content);
                        $('#textViewer').show();
                    },
                    error: function() {
                        $('#textContent').text('Error loading file content.');
                        $('#textViewer').show();
                    }
                });
            } else {
                // Unsupported format (DOC, DOCX, etc.)
                $('#unsupportedViewer').show();
            }
        }, 300);

        $('#viewDocModal').modal('show');
    });

    // Clean up PDF iframe when modal closes
    $('#viewDocModal').on('hidden.bs.modal', function() {
        $('#pdfIframe').attr('src', '');
        $('#textContent').text('');
    });

    // ==================== VIEW IMAGE ====================

    $(document).on('click', '.view-image-btn', function() {
        const imageId = $(this).data('id');
        const imageName = $(this).data('name');
        const imageUrl = $(this).data('url');

        // Get description and analysis from JSON script tag (handles special characters properly)
        let imageDescription = 'No description provided.';
        let imageAnalysis = 'No AI analysis available yet.';

        try {
            const jsonData = JSON.parse($('#image-data-' + imageId).text());
            if (jsonData.description) {
                imageDescription = jsonData.description;
            }
            if (jsonData.analysis) {
                imageAnalysis = jsonData.analysis;
            }
        } catch (e) {
            console.error('Error parsing image data:', e);
        }

        // Set modal content
        $('#viewImageTitle').text(imageName);
        $('#viewImagePreview').attr('src', imageUrl);
        $('#viewImageDescription').text(imageDescription);
        $('#viewImageAnalysis').text(imageAnalysis);

        // Show modal
        $('#viewImageModal').modal('show');
    });

    // Clean up image when modal closes
    $('#viewImageModal').on('hidden.bs.modal', function() {
        $('#viewImagePreview').attr('src', '');
        $('#viewImageDescription').text('No description provided.');
        $('#viewImageAnalysis').text('No AI analysis available yet.');
    });

    // ==================== DELETE HANDLERS ====================

    // Delete Document
    $(document).on('click', '.delete-doc-btn', function() {
        itemToDelete = {
            type: 'doc',
            id: $(this).data('id'),
            name: $(this).data('name')
        };
        $('#deleteItemName').text(itemToDelete.name);
        $('#deleteModal').modal('show');
    });

    // Delete Website
    $(document).on('click', '.delete-website-btn', function() {
        itemToDelete = {
            type: 'website',
            id: $(this).data('id'),
            name: $(this).data('name')
        };
        $('#deleteItemName').text(itemToDelete.name);
        $('#deleteModal').modal('show');
    });

    // Delete Image
    $(document).on('click', '.delete-image-btn', function() {
        itemToDelete = {
            type: 'image',
            id: $(this).data('id'),
            name: $(this).data('name')
        };
        $('#deleteItemName').text(itemToDelete.name);
        $('#deleteModal').modal('show');
    });

    $('#confirmDeleteBtn').on('click', function() {
        if (!itemToDelete) return;

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Deleting...');

        let url = '';
        if (itemToDelete.type === 'doc') {
            url = '{{ url("ai-technician-knowledge-base/docs") }}/' + itemToDelete.id;
        } else if (itemToDelete.type === 'website') {
            url = '{{ url("ai-technician-knowledge-base/websites") }}/' + itemToDelete.id;
        } else if (itemToDelete.type === 'image') {
            url = '{{ url("ai-technician-knowledge-base/images") }}/' + itemToDelete.id;
        } else if (itemToDelete.type === 'product') {
            url = '{{ url("ai-technician-knowledge-base/products") }}/' + itemToDelete.id;
        }

        $.ajax({
            url: url,
            type: 'DELETE',
            data: { _token: csrfToken },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Success');
                    $('#deleteModal').modal('hide');
                    $(`[data-id="${itemToDelete.type}-${itemToDelete.id}"]`).fadeOut(400, function() {
                        $(this).remove();
                        updateCounts();
                    });
                } else {
                    toastr.error(response.message, 'Error');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Delete failed', 'Error');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-trash me-1"></i> Delete');
                itemToDelete = null;
            }
        });
    });

    // ==================== EDIT IMAGE ====================

    $(document).on('click', '.edit-image-btn', function() {
        imageToEdit = {
            id: $(this).data('id'),
            description: $(this).data('description')
        };
        $('#editImageDescription').val(imageToEdit.description);
        $('#editImageModal').modal('show');
    });

    $('#confirmEditImageBtn').on('click', function() {
        if (!imageToEdit) return;

        const newDescription = $('#editImageDescription').val().trim();
        if (newDescription.length < 10) {
            toastr.error('Description must be at least 10 characters.', 'Error');
            return;
        }

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Saving...');

        $.ajax({
            url: '{{ url("ai-technician-knowledge-base/images") }}/' + imageToEdit.id,
            type: 'PUT',
            data: {
                _token: csrfToken,
                description: newDescription
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Success');
                    $('#editImageModal').modal('hide');
                    location.reload();
                } else {
                    toastr.error(response.message, 'Error');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Update failed', 'Error');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save');
                imageToEdit = null;
            }
        });
    });

    // ==================== SCRAPE WEBSITE ====================

    $(document).on('click', '.scrape-website-btn', function() {
        const websiteId = $(this).data('id');
        const $btn = $(this);

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');
        $('#scrapeProgressOverlay').addClass('active');
        $('#scrapeProgressStatus').text('Starting scrape...');
        $('#scrapeProgressBar').css('width', '0%');
        $('#scrapeProgressDetails').text('0 pages scraped');

        $.ajax({
            url: '{{ url("ai-technician-kb-websites-settings") }}/' + websiteId + '/scrape',
            type: 'POST',
            data: { _token: csrfToken },
            success: function(response) {
                if (response.success) {
                    $('#scrapeProgressStatus').text('Scrape completed!');
                    $('#scrapeProgressBar').css('width', '100%');
                    toastr.success(response.message, 'Success');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    toastr.error(response.message, 'Error');
                    $('#scrapeProgressOverlay').removeClass('active');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Scrape failed', 'Error');
                $('#scrapeProgressOverlay').removeClass('active');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-refresh"></i>');
            }
        });
    });

    // ==================== UPLOAD WEBSITE TO RAG ====================

    $(document).on('click', '.upload-website-rag-btn', function() {
        const websiteId = $(this).data('id');
        const $btn = $(this);

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');
        $('#scrapeProgressOverlay').addClass('active');
        $('#scrapeProgressStatus').text('Uploading to knowledge base...');
        $('#scrapeProgressBar').css('width', '50%');
        $('#scrapeProgressDetails').text('Processing...');

        $.ajax({
            url: '{{ url("ai-technician-kb-websites-settings") }}/' + websiteId + '/upload-pinecone',
            type: 'POST',
            data: { _token: csrfToken },
            success: function(response) {
                if (response.success) {
                    $('#scrapeProgressStatus').text('Upload completed!');
                    $('#scrapeProgressBar').css('width', '100%');
                    toastr.success(response.message, 'Success');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    toastr.error(response.message, 'Error');
                    $('#scrapeProgressOverlay').removeClass('active');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Upload failed', 'Error');
                $('#scrapeProgressOverlay').removeClass('active');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-cloud-upload"></i>');
            }
        });
    });

    // ==================== VIEW SCRAPED PAGES ====================

    $(document).on('click', '.view-pages-btn', function() {
        const websiteId = $(this).data('id');
        const websiteName = $(this).data('name');

        // Open the pages in the dedicated websites settings page
        window.location.href = '{{ url("ai-technician-kb-websites-settings") }}/' + websiteId;
    });

    // ==================== SYNC IMAGE TO PINECONE ====================

    $(document).on('click', '.sync-image-btn', function() {
        const imageId = $(this).data('id');
        const $btn = $(this);

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');

        $.ajax({
            url: '{{ url("ai-technician-kb-images-settings/images") }}/' + imageId + '/upload-to-pinecone',
            type: 'POST',
            data: { _token: csrfToken },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Success');
                    location.reload();
                } else {
                    toastr.error(response.message, 'Error');
                    $btn.prop('disabled', false).html('<i class="bx bx-cloud-upload"></i>');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Sync failed', 'Error');
                $btn.prop('disabled', false).html('<i class="bx bx-cloud-upload"></i>');
            }
        });
    });

    // ==================== REFRESH DOCUMENT STATUS ====================

    $(document).on('click', '.refresh-doc-btn', function() {
        const docId = $(this).data('id');
        const $btn = $(this);

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');

        $.ajax({
            url: '{{ url("ai-technician-kb-docs-settings/files") }}/' + docId + '/refresh',
            type: 'POST',
            data: { _token: csrfToken },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Status refreshed', 'Success');
                    location.reload();
                } else {
                    toastr.error(response.message || 'Refresh failed', 'Error');
                    $btn.prop('disabled', false).html('<i class="bx bx-sync"></i>');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Refresh failed', 'Error');
                $btn.prop('disabled', false).html('<i class="bx bx-sync"></i>');
            }
        });
    });

    // ==================== RETRY DOCUMENT UPLOAD ====================

    $(document).on('click', '.retry-doc-btn', function() {
        const docId = $(this).data('id');
        const $btn = $(this);

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');

        $.ajax({
            url: '{{ url("ai-technician-kb-docs-settings/files") }}/' + docId + '/retry',
            type: 'POST',
            data: { _token: csrfToken },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Retry initiated', 'Success');
                    location.reload();
                } else {
                    toastr.error(response.message || 'Retry failed', 'Error');
                    $btn.prop('disabled', false).html('<i class="bx bx-refresh"></i>');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Retry failed', 'Error');
                $btn.prop('disabled', false).html('<i class="bx bx-refresh"></i>');
            }
        });
    });

    // ==================== REFRESH IMAGE STATUS ====================

    $(document).on('click', '.refresh-image-btn', function() {
        const imageId = $(this).data('id');
        const $btn = $(this);

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');

        $.ajax({
            url: '{{ url("ai-technician-kb-images-settings/images") }}/' + imageId + '/refresh-status',
            type: 'POST',
            data: { _token: csrfToken },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Status refreshed', 'Success');
                    location.reload();
                } else {
                    toastr.error(response.message || 'Refresh failed', 'Error');
                    $btn.prop('disabled', false).html('<i class="bx bx-sync"></i>');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Refresh failed', 'Error');
                $btn.prop('disabled', false).html('<i class="bx bx-sync"></i>');
            }
        });
    });

    // ==================== RETRY IMAGE UPLOAD ====================

    $(document).on('click', '.retry-image-btn', function() {
        const imageId = $(this).data('id');
        const $btn = $(this);

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');

        $.ajax({
            url: '{{ url("ai-technician-kb-images-settings/images") }}/' + imageId + '/retry',
            type: 'POST',
            data: { _token: csrfToken },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Retry initiated', 'Success');
                    location.reload();
                } else {
                    toastr.error(response.message || 'Retry failed', 'Error');
                    $btn.prop('disabled', false).html('<i class="bx bx-refresh"></i>');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Retry failed', 'Error');
                $btn.prop('disabled', false).html('<i class="bx bx-refresh"></i>');
            }
        });
    });

    // ==================== EXTERNAL PRODUCT UPLOAD (Multiple Images) ====================

    let selectedProductImages = []; // Array to hold multiple images
    const maxProductImages = 10;
    const productImageZone = $('#productImageZone');
    const productImageInput = $('#productImageInput');

    productImageZone.on('click', function(e) {
        e.preventDefault();
        productImageInput[0].click();
    });

    productImageZone.on('dragover', function(e) {
        e.preventDefault();
        $(this).addClass('drag-over');
    });

    productImageZone.on('dragleave', function() {
        $(this).removeClass('drag-over');
    });

    productImageZone.on('drop', function(e) {
        e.preventDefault();
        $(this).removeClass('drag-over');
        const files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            handleProductImagesSelect(files);
        }
    });

    productImageInput.on('change', function() {
        if (this.files.length > 0) {
            handleProductImagesSelect(this.files);
        }
    });

    function handleProductImagesSelect(files) {
        const allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        for (let i = 0; i < files.length; i++) {
            if (selectedProductImages.length >= maxProductImages) {
                toastr.warning('Maximum ' + maxProductImages + ' images allowed.', 'Limit Reached');
                break;
            }

            const file = files[i];
            const extension = file.name.split('.').pop().toLowerCase();

            if (!allowedExtensions.includes(extension)) {
                toastr.error('Unsupported file type: ' + file.name, 'Error');
                continue;
            }

            if (file.size > 10 * 1024 * 1024) {
                toastr.error('File too large: ' + file.name + ' (max 10MB)', 'Error');
                continue;
            }

            // Add to array with unique ID
            const imageId = 'img_' + Date.now() + '_' + i;
            selectedProductImages.push({
                id: imageId,
                file: file,
                name: file.name
            });

            // Create preview
            createImagePreview(imageId, file);
        }

        updateProductImagesPreview();
        productImageInput.val(''); // Reset input to allow selecting same file again
    }

    function createImagePreview(imageId, file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const previewHtml = `
                <div class="product-image-preview-item position-relative" data-image-id="${imageId}" style="width: 100px;">
                    <img src="${e.target.result}" alt="${file.name}" class="img-fluid rounded" style="width: 100px; height: 80px; object-fit: cover; border: 2px solid #e9ecef;">
                    <button type="button" class="btn btn-danger btn-sm position-absolute remove-product-image" style="top: -8px; right: -8px; padding: 2px 6px; border-radius: 50%;" data-image-id="${imageId}">
                        <i class="bx bx-x"></i>
                    </button>
                    <p class="text-truncate text-secondary mb-0 small text-center" style="font-size: 10px;" title="${file.name}">${file.name}</p>
                </div>
            `;
            $('#productImagesPreview').append(previewHtml);
        };
        reader.readAsDataURL(file);
    }

    function updateProductImagesPreview() {
        if (selectedProductImages.length > 0) {
            $('#productImagesPreview').show();
        } else {
            $('#productImagesPreview').hide();
        }
    }

    // Remove individual image
    $(document).on('click', '.remove-product-image', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const imageId = $(this).data('image-id');
        selectedProductImages = selectedProductImages.filter(img => img.id !== imageId);
        $(`[data-image-id="${imageId}"]`).remove();
        updateProductImagesPreview();
    });

    // ==================== PRODUCT DOCUMENT UPLOAD ====================

    let selectedProductDocuments = [];
    const maxProductDocuments = 5;
    const productDocumentZone = $('#productDocumentZone');
    const productDocumentInput = $('#productDocumentInput');

    productDocumentZone.on('click', function(e) {
        e.preventDefault();
        productDocumentInput[0].click();
    });

    productDocumentZone.on('dragover', function(e) {
        e.preventDefault();
        $(this).addClass('drag-over');
    });

    productDocumentZone.on('dragleave', function() {
        $(this).removeClass('drag-over');
    });

    productDocumentZone.on('drop', function(e) {
        e.preventDefault();
        $(this).removeClass('drag-over');
        const files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            handleProductDocumentsSelect(files);
        }
    });

    productDocumentInput.on('change', function() {
        if (this.files.length > 0) {
            handleProductDocumentsSelect(this.files);
        }
    });

    function handleProductDocumentsSelect(files) {
        const allowedExtensions = ['pdf', 'txt', 'doc', 'docx'];
        const docIcons = {
            'pdf': 'bx bxs-file-pdf text-danger',
            'txt': 'bx bxs-file-txt text-secondary',
            'doc': 'bx bxs-file-doc text-primary',
            'docx': 'bx bxs-file-doc text-primary'
        };

        for (let i = 0; i < files.length; i++) {
            if (selectedProductDocuments.length >= maxProductDocuments) {
                toastr.warning('Maximum ' + maxProductDocuments + ' documents allowed.', 'Limit Reached');
                break;
            }

            const file = files[i];
            const extension = file.name.split('.').pop().toLowerCase();

            if (!allowedExtensions.includes(extension)) {
                toastr.error('Unsupported file type: ' + file.name + '. Only PDF, TXT, DOC, DOCX allowed.', 'Error');
                continue;
            }

            if (file.size > 50 * 1024 * 1024) {
                toastr.error('File too large: ' + file.name + ' (max 50MB)', 'Error');
                continue;
            }

            const docId = 'doc_' + Date.now() + '_' + i;
            selectedProductDocuments.push({
                id: docId,
                file: file,
                name: file.name,
                extension: extension
            });

            // Create document preview
            const iconClass = docIcons[extension] || 'bx bxs-file text-secondary';
            const fileSizeStr = formatFileSize(file.size);
            const previewHtml = `
                <div class="product-document-preview-item d-flex align-items-center p-2 border rounded mb-2" data-doc-id="${docId}">
                    <i class="${iconClass} me-2" style="font-size: 24px;"></i>
                    <div class="flex-grow-1">
                        <p class="text-dark mb-0 small text-truncate" style="max-width: 300px;" title="${file.name}">${file.name}</p>
                        <small class="text-secondary">${fileSizeStr}</small>
                    </div>
                    <button type="button" class="btn btn-outline-danger btn-sm remove-product-document" data-doc-id="${docId}">
                        <i class="bx bx-x"></i>
                    </button>
                </div>
            `;
            $('#productDocumentsPreview').append(previewHtml);
        }

        updateProductDocumentsPreview();
        productDocumentInput.val('');
    }

    function formatFileSize(bytes) {
        if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
        if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' KB';
        return bytes + ' bytes';
    }

    function updateProductDocumentsPreview() {
        if (selectedProductDocuments.length > 0) {
            $('#productDocumentsPreview').show();
        } else {
            $('#productDocumentsPreview').hide();
        }
    }

    // Remove individual document
    $(document).on('click', '.remove-product-document', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const docId = $(this).data('doc-id');
        selectedProductDocuments = selectedProductDocuments.filter(doc => doc.id !== docId);
        $(`[data-doc-id="${docId}"]`).remove();
        updateProductDocumentsPreview();
    });

    // Product Form Submit
    $('#productForm').on('submit', function(e) {
        e.preventDefault();

        const productName = $('#productName').val().trim();
        if (!productName) {
            toastr.error('Product name is required.', 'Error');
            return;
        }

        const formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('productName', productName);
        formData.append('brandName', $('#productBrand').val().trim());
        formData.append('manufacturer', $('#productManufacturer').val().trim());
        formData.append('productType', $('#productType').val());
        formData.append('manualText', $('#productDescription').val().trim());

        // Append all images
        selectedProductImages.forEach(function(imgObj, index) {
            formData.append('images[]', imgObj.file);
        });

        // Append all documents
        selectedProductDocuments.forEach(function(docObj, index) {
            formData.append('documents[]', docObj.file);
        });

        const $btn = $('#addProductBtn');
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Adding...');

        $('#productUploadProgress').addClass('active');
        $('#productUploadStatus').html('<i class="bx bx-loader-alt bx-spin me-2 text-primary"></i> Uploading external product...');
        $('#productUploadProgressBar').css('width', '20%');

        let filesText = [];
        if (selectedProductImages.length > 0) filesText.push(selectedProductImages.length + ' image(s)');
        if (selectedProductDocuments.length > 0) filesText.push(selectedProductDocuments.length + ' document(s)');
        $('#productUploadDetails').text('Saving product with ' + (filesText.length > 0 ? filesText.join(' and ') : 'no files') + '...');

        $.ajax({
            url: '{{ route("ai-technician.knowledge-base.products.store") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percent = 20 + (e.loaded / e.total) * 30;
                        $('#productUploadProgressBar').css('width', percent + '%');
                    }
                });
                return xhr;
            },
            success: function(response) {
                if (response.success) {
                    $('#productUploadProgressBar').css('width', '50%');
                    let savedFiles = [];
                    if (response.product.imageCount > 0) savedFiles.push(response.product.imageCount + ' image(s)');
                    if (response.product.documentCount > 0) savedFiles.push(response.product.documentCount + ' document(s)');
                    $('#productUploadDetails').text('Product saved with ' + (savedFiles.length > 0 ? savedFiles.join(' and ') : 'no files') + '. Starting AI analysis...');

                    // Now trigger processing
                    processProduct(response.product.id);
                } else {
                    toastr.error(response.message || 'Failed to add product', 'Error');
                    $('#productUploadProgress').removeClass('active');
                    $btn.prop('disabled', false).html('<i class="bx bx-plus me-1"></i> Add External Product');
                }
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors;
                if (errors) {
                    Object.values(errors).forEach(function(msgs) {
                        toastr.error(msgs[0], 'Validation Error');
                    });
                } else {
                    toastr.error(xhr.responseJSON?.message || 'Failed to add product', 'Error');
                }
                $('#productUploadProgress').removeClass('active');
                $btn.prop('disabled', false).html('<i class="bx bx-plus me-1"></i> Add External Product');
            }
        });
    });

    function processProduct(productId) {
        $('#productUploadStatus').html('<i class="bx bx-loader-alt bx-spin me-2 text-primary"></i> Processing with AI...');
        $('#productUploadProgressBar').css('width', '60%');
        $('#productUploadDetails').text('Extracting text and analyzing product details...');

        $.ajax({
            url: '{{ url("ai-technician-knowledge-base/products") }}/' + productId + '/process',
            type: 'POST',
            data: { _token: csrfToken },
            success: function(response) {
                if (response.success) {
                    $('#productUploadProgressBar').css('width', '100%');
                    $('#productUploadStatus').html('<i class="bx bx-check-circle me-2 text-success"></i> Product processed successfully!');
                    $('#productUploadDetails').text('Product has been analyzed and indexed.');
                    toastr.success(response.message, 'Success');

                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    toastr.warning(response.message || 'Processing initiated. Refresh to check status.', 'Processing');
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                }
            },
            error: function(xhr) {
                toastr.warning('Product saved. Processing may take a moment. Refresh to check status.', 'Processing');
                setTimeout(function() {
                    location.reload();
                }, 2000);
            }
        });
    }

    // ==================== VIEW EXTERNAL PRODUCT ====================

    $(document).on('click', '.view-product-btn', function() {
        const productId = $(this).data('id');

        try {
            const jsonData = JSON.parse($('#product-data-' + productId).text());

            // Set title
            $('#viewProductTitle').text(jsonData.productName + ' (External Product)');

            // Handle images (multiple)
            const images = jsonData.images || [];
            const primaryUrl = jsonData.primaryImageUrl;

            if (images.length > 0 || primaryUrl) {
                // Show primary image
                const mainImageUrl = primaryUrl || (images.length > 0 ? images[0].imageUrl : null);
                if (mainImageUrl) {
                    $('#viewProductImage').attr('src', mainImageUrl).show();
                    $('#viewProductImageContainer').show();
                }
                $('#viewProductNoImage').hide();

                // Build thumbnails gallery if multiple images
                $('#viewProductImagesGallery').empty();
                if (images.length > 1) {
                    images.forEach(function(img, index) {
                        const isActive = (img.isPrimary || index === 0) ? 'border-primary' : '';
                        const thumbHtml = `
                            <img src="${img.imageUrl}" alt="${escapeHtml(img.originalName)}"
                                class="product-gallery-thumb rounded ${isActive}"
                                style="width: 50px; height: 50px; object-fit: cover; cursor: pointer; border: 2px solid #dee2e6;"
                                data-image-url="${img.imageUrl}" data-image-type="${img.imageType || 'Image'}"
                                title="${escapeHtml(img.imageType || img.originalName)}">
                        `;
                        $('#viewProductImagesGallery').append(thumbHtml);
                    });
                    $('#viewProductImagesGallery').show();
                } else {
                    $('#viewProductImagesGallery').hide();
                }
            } else {
                $('#viewProductImage').hide();
                $('#viewProductImageContainer').hide();
                $('#viewProductImagesGallery').hide();
                $('#viewProductNoImage').show();
            }

            // Set basic info
            $('#viewProductBrand').text(jsonData.brandName || '-');
            $('#viewProductManufacturer').text(jsonData.manufacturer || '-');
            $('#viewProductType').text(jsonData.typeDisplay || jsonData.productType);
            $('#viewProductImageCount').text(jsonData.imageCount || images.length || '0');

            // Set RAG status
            const ragStatusBadges = {
                'pending': '<span class="badge bg-secondary">Pending</span>',
                'processing': '<span class="badge bg-info">Processing</span>',
                'analyzing': '<span class="badge bg-primary">Analyzing</span>',
                'uploading': '<span class="badge bg-warning text-dark">Uploading</span>',
                'indexed': '<span class="badge bg-success">Indexed</span>',
                'failed': '<span class="badge bg-danger">Failed</span>'
            };
            $('#viewProductRagStatus').html(ragStatusBadges[jsonData.ragStatus] || '<span class="badge bg-secondary">Unknown</span>');

            // Handle AI Analysis
            const analysis = jsonData.aiAnalysis;
            if (analysis && Object.keys(analysis).length > 0) {
                $('#viewProductAnalysisSection').show();
                $('#viewProductNoAnalysis').hide();

                // Summary
                if (analysis.summary) {
                    $('#viewProductSummary').text(analysis.summary);
                    $('#viewProductSummarySection').show();
                } else {
                    $('#viewProductSummarySection').hide();
                }

                // Purpose
                if (analysis.purpose) {
                    $('#viewProductPurpose').text(analysis.purpose);
                    $('#viewProductPurposeSection').show();
                } else {
                    $('#viewProductPurposeSection').hide();
                }

                // Active Ingredients
                if (analysis.activeIngredients && analysis.activeIngredients.length > 0) {
                    let ingredientsHtml = '<ul class="mb-0 ps-3">';
                    analysis.activeIngredients.forEach(function(ing) {
                        ingredientsHtml += '<li class="text-dark">';
                        ingredientsHtml += '<strong>' + escapeHtml(ing.name) + '</strong>';
                        if (ing.concentration) ingredientsHtml += ' (' + escapeHtml(ing.concentration) + ')';
                        if (ing.purpose) ingredientsHtml += ' - ' + escapeHtml(ing.purpose);
                        ingredientsHtml += '</li>';
                    });
                    ingredientsHtml += '</ul>';
                    $('#viewProductIngredients').html(ingredientsHtml);
                    $('#viewProductIngredientsSection').show();
                } else {
                    $('#viewProductIngredientsSection').hide();
                }

                // Target Pests
                if (analysis.targetPests && analysis.targetPests.length > 0) {
                    let pestsHtml = analysis.targetPests.map(p => '<span class="badge bg-danger me-1 mb-1">' + escapeHtml(p) + '</span>').join('');
                    $('#viewProductPests').html(pestsHtml);
                    $('#viewProductPestsSection').show();
                } else {
                    $('#viewProductPestsSection').hide();
                }

                // Target Diseases
                if (analysis.targetDiseases && analysis.targetDiseases.length > 0) {
                    let diseasesHtml = analysis.targetDiseases.map(d => '<span class="badge bg-warning text-dark me-1 mb-1">' + escapeHtml(d) + '</span>').join('');
                    $('#viewProductDiseases').html(diseasesHtml);
                    $('#viewProductDiseasesSection').show();
                } else {
                    $('#viewProductDiseasesSection').hide();
                }

                // Target Crops
                if (analysis.targetCrops && analysis.targetCrops.length > 0) {
                    let cropsHtml = analysis.targetCrops.map(c => '<span class="badge bg-success me-1 mb-1">' + escapeHtml(c) + '</span>').join('');
                    $('#viewProductCrops').html(cropsHtml);
                    $('#viewProductCropsSection').show();
                } else {
                    $('#viewProductCropsSection').hide();
                }

                // Application & Dosage
                if (analysis.applicationMethod) {
                    $('#viewProductApplication').text(analysis.applicationMethod);
                    $('#viewProductApplicationSection').show();
                } else {
                    $('#viewProductApplicationSection').hide();
                }

                if (analysis.dosage) {
                    $('#viewProductDosage').text(analysis.dosage);
                    $('#viewProductDosageSection').show();
                } else {
                    $('#viewProductDosageSection').hide();
                }

                // Safety Precautions
                if (analysis.safetyPrecautions && analysis.safetyPrecautions.length > 0) {
                    let safetyHtml = '';
                    analysis.safetyPrecautions.forEach(function(s) {
                        safetyHtml += '<li class="text-dark">' + escapeHtml(s) + '</li>';
                    });
                    $('#viewProductSafety').html(safetyHtml);
                    $('#viewProductSafetySection').show();
                } else {
                    $('#viewProductSafetySection').hide();
                }

                // Search Tags
                if (analysis.searchTags && analysis.searchTags.length > 0) {
                    let tagsHtml = analysis.searchTags.map(t => '<span class="badge bg-light text-dark me-1 mb-1">' + escapeHtml(t) + '</span>').join('');
                    $('#viewProductTags').html(tagsHtml);
                    $('#viewProductTagsSection').show();
                } else {
                    $('#viewProductTagsSection').hide();
                }
            } else {
                $('#viewProductAnalysisSection').hide();
                $('#viewProductNoAnalysis').show();
            }

            // Manual Text
            if (jsonData.manualText) {
                $('#viewProductManualText').text(jsonData.manualText);
                $('#viewProductManualSection').show();
            } else {
                $('#viewProductManualSection').hide();
            }

            // OCR Text (combined from all images)
            const combinedOcr = jsonData.combinedOcrText || jsonData.ocrText;
            if (combinedOcr) {
                $('#viewProductOcrText').text(combinedOcr);
                $('#viewProductOcrSection').show();
            } else {
                $('#viewProductOcrSection').hide();
            }

            // Error
            if (jsonData.ragStatus === 'failed' && jsonData.ragError) {
                $('#viewProductErrorText').text(jsonData.ragError);
                $('#viewProductError').show();
            } else {
                $('#viewProductError').hide();
            }

            $('#viewProductModal').modal('show');
        } catch (e) {
            console.error('Error parsing product data:', e);
            toastr.error('Failed to load product details', 'Error');
        }
    });

    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // ==================== PRODUCT GALLERY THUMBNAIL CLICK ====================

    $(document).on('click', '.product-gallery-thumb', function() {
        const imageUrl = $(this).data('image-url');
        const imageType = $(this).data('image-type');

        // Update main image
        $('#viewProductImage').attr('src', imageUrl);

        // Update active state on thumbnails
        $('.product-gallery-thumb').removeClass('border-primary').css('border-color', '#dee2e6');
        $(this).addClass('border-primary').css('border-color', '');
    });

    // ==================== DELETE PRODUCT ====================

    $(document).on('click', '.delete-product-btn', function() {
        itemToDelete = {
            type: 'product',
            id: $(this).data('id'),
            name: $(this).data('name')
        };
        $('#deleteItemName').text(itemToDelete.name);
        $('#deleteModal').modal('show');
    });

    // ==================== PROCESS PRODUCT ====================

    $(document).on('click', '.process-product-btn', function() {
        const productId = $(this).data('id');
        const $btn = $(this);

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');

        $.ajax({
            url: '{{ url("ai-technician-knowledge-base/products") }}/' + productId + '/process',
            type: 'POST',
            data: { _token: csrfToken },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Success');
                    location.reload();
                } else {
                    toastr.error(response.message || 'Processing failed', 'Error');
                    $btn.prop('disabled', false).html('<i class="bx bx-cog"></i>');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Processing failed', 'Error');
                $btn.prop('disabled', false).html('<i class="bx bx-cog"></i>');
            }
        });
    });

    // ==================== RETRY PRODUCT ====================

    $(document).on('click', '.retry-product-btn', function() {
        const productId = $(this).data('id');
        const $btn = $(this);

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');

        $.ajax({
            url: '{{ url("ai-technician-knowledge-base/products") }}/' + productId + '/retry',
            type: 'POST',
            data: { _token: csrfToken },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Retry initiated', 'Success');
                    location.reload();
                } else {
                    toastr.error(response.message || 'Retry failed', 'Error');
                    $btn.prop('disabled', false).html('<i class="bx bx-refresh"></i>');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Retry failed', 'Error');
                $btn.prop('disabled', false).html('<i class="bx bx-refresh"></i>');
            }
        });
    });

    // ==================== REFRESH PRODUCT STATUS ====================

    $(document).on('click', '.refresh-product-btn', function() {
        const productId = $(this).data('id');
        const $btn = $(this);

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');

        $.ajax({
            url: '{{ url("ai-technician-knowledge-base/products") }}/' + productId,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    toastr.success('Status refreshed', 'Success');
                    location.reload();
                } else {
                    toastr.info('Product status unchanged', 'Info');
                    $btn.prop('disabled', false).html('<i class="bx bx-sync"></i>');
                }
            },
            error: function(xhr) {
                toastr.error('Failed to refresh status', 'Error');
                $btn.prop('disabled', false).html('<i class="bx bx-sync"></i>');
            }
        });
    });

    // ==================== ADD CONTENT BUTTON ====================

    $('#addContentBtn').on('click', function(e) {
        e.preventDefault();
        // Activate the Add Content tab
        const addContentTabLink = $('a[href="#addContentTab"]').first();
        if (addContentTabLink.length) {
            // Use Bootstrap's Tab API
            const tab = new bootstrap.Tab(addContentTabLink[0]);
            tab.show();
        }
    });

    // ==================== REFRESH ALL ====================

    $('#refreshAllBtn').on('click', function() {
        location.reload();
    });

    // ==================== HELPER FUNCTIONS ====================

    function updateCounts() {
        const docCount = $('.kb-item[data-type="doc"]').length;
        const websiteCount = $('.kb-item[data-type="website"]').length;
        const imageCount = $('.kb-item[data-type="image"]').length;
        const productCount = $('.kb-item[data-type="product"]').length;
        const totalCount = docCount + websiteCount + imageCount + productCount;

        $('#countAll').text(totalCount);
        $('#typeFilter .nav-link[data-filter="doc"] .badge').text(docCount);
        $('#typeFilter .nav-link[data-filter="website"] .badge').text(websiteCount);
        $('#typeFilter .nav-link[data-filter="image"] .badge').text(imageCount);
        $('#countProducts').text(productCount);

        if (totalCount === 0) {
            $('#kbItemsList').html(`
                <div class="empty-state" id="emptyState">
                    <i class="bx bx-data"></i>
                    <h5 class="text-dark">No content in knowledge base</h5>
                    <p class="text-secondary mb-0">Click the "Add Content" button above to add documents, websites, images, or products.</p>
                </div>
            `);
        }
    }
});
</script>
@endsection
