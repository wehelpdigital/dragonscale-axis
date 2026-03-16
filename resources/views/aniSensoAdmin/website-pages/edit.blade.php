@extends('layouts.master')

@section('title') Edit Page - {{ $page->pageName }} @endsection

@php
    $isHomepage = ($page->pageSlug === 'home');
    $homepageSections = $isHomepage ? \App\Models\AsHomepageSection::orderBy('sectionOrder')->get() : collect();
@endphp

@section('css')
<!-- Toastr CSS -->
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />

@if($isHomepage)
<style>
    /* Compact Tabs */
    .nav-tabs-custom {
        border-bottom: 1px solid #e9ecef;
        flex-wrap: nowrap;
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }
    .nav-tabs-custom::-webkit-scrollbar { display: none; }
    .nav-tabs-custom .nav-link {
        border: none;
        padding: 8px 12px;
        font-weight: 500;
        font-size: 13px;
        color: #6c757d;
        background: transparent;
        border-bottom: 2px solid transparent;
        white-space: nowrap;
    }
    .nav-tabs-custom .nav-link:hover { color: #556ee6; }
    .nav-tabs-custom .nav-link.active {
        color: #556ee6;
        border-bottom-color: #556ee6;
    }
    .tab-icon { margin-right: 5px; font-size: 14px; }
    .section-badge { font-size: 10px; padding: 2px 5px; }

    /* Compact Section Header */
    .section-header-compact {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        margin-bottom: 12px;
        border-bottom: 1px solid #e9ecef;
    }
    .section-header-compact h6 { margin: 0; font-size: 14px; color: #495057; }

    /* Compact Accordion */
    .settings-accordion { margin-bottom: 8px; }
    .settings-accordion .accordion-item { margin-bottom: 6px !important; }
    .settings-accordion .accordion-button {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 6px !important;
        padding: 8px 12px;
        font-weight: 500;
        font-size: 13px;
        color: #495057;
        box-shadow: none;
    }
    .settings-accordion .accordion-button:not(.collapsed) {
        background: #e8f0fe;
        color: #556ee6;
        border-color: #c5d4f7;
        border-bottom-left-radius: 0 !important;
        border-bottom-right-radius: 0 !important;
    }
    .settings-accordion .accordion-button:focus { box-shadow: none; }
    .settings-accordion .accordion-button::after { background-size: 10px; }
    .settings-accordion .accordion-collapse {
        border: 1px solid #e9ecef;
        border-top: none;
        border-radius: 0 0 6px 6px;
    }
    .settings-accordion .accordion-body {
        padding: 12px;
        background: #fff;
        border-radius: 0 0 6px 6px;
    }
    .accordion-icon { margin-right: 6px; font-size: 14px; }

    /* Compact Form Fields */
    .accordion-body .form-label {
        font-size: 12px;
        font-weight: 500;
        margin-bottom: 4px;
        color: #495057;
    }
    .accordion-body .form-control,
    .accordion-body .form-select {
        font-size: 13px;
        padding: 6px 10px;
    }
    .accordion-body .form-control-sm { padding: 4px 8px; font-size: 12px; }
    .accordion-body small, .accordion-body .text-secondary { font-size: 11px; }
    .accordion-body .mb-3 { margin-bottom: 10px !important; }
    .accordion-body .row { margin-left: -6px; margin-right: -6px; }
    .accordion-body .row > [class*="col-"] { padding-left: 6px; padding-right: 6px; }

    /* Compact Items */
    .item-card {
        border: 1px solid #e9ecef;
        border-radius: 6px;
        padding: 10px;
        margin-bottom: 8px;
        background: #fff;
        transition: box-shadow 0.15s;
    }
    .item-card:hover { box-shadow: 0 2px 6px rgba(0,0,0,0.06); }
    .item-card.sortable-ghost { opacity: 0.4; background: #f8f9fa; }
    .drag-handle { cursor: grab; color: #adb5bd; }
    .drag-handle:hover { color: #495057; }

    /* Compact Image Upload */
    .image-preview {
        max-width: 100px;
        max-height: 60px;
        object-fit: cover;
        border-radius: 4px;
        border: 1px solid #e9ecef;
    }
    .image-upload-zone {
        border: 2px dashed #dee2e6;
        border-radius: 6px;
        padding: 10px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
        background: #f8f9fa;
        max-width: 150px;
    }
    .image-upload-zone:hover { border-color: #556ee6; background: #f0f4ff; }
    .image-upload-zone .upload-placeholder i { font-size: 1.2rem; }
    .image-upload-zone .upload-placeholder p { font-size: 11px; }

    /* Feature Items Compact */
    .feature-item .card-body { padding: 8px 10px !important; }
    .feature-title, .feature-desc { font-size: 12px !important; }
    .icon-picker-box {
        width: 40px !important;
        height: 40px !important;
    }
    .icon-picker-box i { font-size: 22px !important; }
</style>
@endif

<style>
.page-editor-container {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 1.5rem;
}

@media (max-width: 1199px) {
    .page-editor-container {
        grid-template-columns: 1fr;
    }
}

.editor-main {
    min-height: 500px;
}

.editor-sidebar .card {
    margin-bottom: 1rem;
}

.section-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #e9ecef;
}

.form-label {
    font-weight: 500;
    color: #495057;
}

.form-text {
    font-size: 0.8rem;
}

.page-slug-preview {
    background: #f8f9fa;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    font-family: monospace;
    font-size: 0.9rem;
    color: #6c757d;
}


.save-indicator {
    display: none;
    align-items: center;
    gap: 0.5rem;
    color: #34c38f;
    font-size: 0.85rem;
}

.save-indicator.saving {
    color: #f1b44c;
}

.system-page-notice {
    background: #fff3cd;
    border: 1px solid #ffc107;
    border-radius: 4px;
    padding: 0.75rem 1rem;
    margin-bottom: 1rem;
    font-size: 0.9rem;
    color: #856404;
}

.system-page-notice i {
    margin-right: 0.5rem;
}
</style>
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Ani-Senso @endslot
@slot('li_2') Website @endslot
@slot('li_3') Pages @endslot
@slot('title') Edit: {{ $page->pageName }} @endslot
@endcomponent

<form id="pageForm" method="POST" action="{{ route('anisenso-website-pages.update', $page->id) }}">
    @csrf
    @method('PUT')

    <div class="page-editor-container">
        <!-- Main Editor Area -->
        <div class="editor-main">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Page Content</h5>
                    <div class="save-indicator" id="saveIndicator">
                        <i class="bx bx-check-circle"></i>
                        <span>Saved</span>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Page Name -->
                    <div class="mb-3">
                        <label for="pageName" class="form-label">Page Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('pageName') is-invalid @enderror"
                               id="pageName" name="pageName" value="{{ old('pageName', $page->pageName) }}"
                               required>
                        @error('pageName')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    @if($isHomepage)
                    <!-- Homepage Section Settings -->
                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-end mb-2">
                            <small class="text-secondary"><i class="bx bx-info-circle me-1"></i>Tip: Use <code>&lt;span class="yellow"&gt;text&lt;/span&gt;</code> to highlight text in yellow</small>
                        </div>

                        <!-- Section Tabs -->
                        <ul class="nav nav-tabs nav-tabs-custom mb-3" id="sectionTabs" role="tablist">
                            @foreach($homepageSections as $section)
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link {{ $loop->first ? 'active' : '' }}"
                                            id="{{ $section->sectionKey }}-tab"
                                            data-bs-toggle="tab"
                                            data-bs-target="#{{ $section->sectionKey }}"
                                            type="button"
                                            role="tab"
                                            aria-controls="{{ $section->sectionKey }}"
                                            aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                        <i class="bx {{ $section->sectionIcon ?? 'bx-cog' }} tab-icon"></i>
                                        <span class="d-none d-md-inline">{{ $section->sectionName }}</span>
                                        @if(!$section->isEnabled)
                                            <span class="badge bg-secondary section-badge ms-1">Off</span>
                                        @endif
                                    </button>
                                </li>
                            @endforeach
                        </ul>

                        <!-- Tab Content -->
                        <div class="tab-content" id="sectionTabsContent">
                            @foreach($homepageSections as $section)
                                <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                                     id="{{ $section->sectionKey }}"
                                     role="tabpanel"
                                     aria-labelledby="{{ $section->sectionKey }}-tab"
                                     data-section-key="{{ $section->sectionKey }}">

                                    <!-- Section Header (Compact) -->
                                    <div class="section-header-compact">
                                        <h6 class="text-dark"><i class="bx {{ $section->sectionIcon ?? 'bx-cog' }} me-1"></i>{{ $section->sectionName }}</h6>
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input section-toggle" type="checkbox" id="toggle-{{ $section->sectionKey }}" data-section="{{ $section->sectionKey }}" {{ $section->isEnabled ? 'checked' : '' }}>
                                            <label class="form-check-label small" for="toggle-{{ $section->sectionKey }}">{{ $section->isEnabled ? 'On' : 'Off' }}</label>
                                        </div>
                                    </div>

                                    @include('aniSensoAdmin.homepage-settings.partials.' . $section->sectionKey, ['section' => $section])

                                </div>
                            @endforeach
                        </div>
                    </div>
                    @else
                    <!-- Page Content (TinyMCE) -->
                    <div class="mb-3">
                        <label for="pageContent" class="form-label">Page Content</label>
                        <textarea class="form-control" id="pageContent" name="pageContent"
                                  rows="20">{{ old('pageContent', $page->pageContent) }}</textarea>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="editor-sidebar">
            <!-- Actions Card -->
            <div class="card">
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary" id="saveBtn">
                            <i class="bx bx-save me-1"></i> Save Changes
                        </button>
                        <a href="{{ route('anisenso-website-pages') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-arrow-back me-1"></i> Back to Pages
                        </a>
                    </div>
                </div>
            </div>

            <!-- Status always published for homepage -->
            <input type="hidden" name="pageStatus" id="pageStatus" value="published">

            @if(!$isHomepage)
            <!-- URL Settings Card (not shown for homepage since it has no slug) -->
            <div class="card">
                <div class="card-body">
                    <h6 class="section-title"><i class="bx bx-link me-2"></i>URL Settings</h6>

                    <!-- Page Slug -->
                    <div class="mb-3">
                        <label for="pageSlug" class="form-label">Page Slug</label>
                        @if($page->isSystemPage)
                        <div class="page-slug-preview">/{{ $page->pageSlug }}</div>
                        <input type="hidden" name="pageSlug" value="{{ $page->pageSlug }}">
                        @else
                        <div class="input-group">
                            <span class="input-group-text">/</span>
                            <input type="text" class="form-control @error('pageSlug') is-invalid @enderror"
                                   id="pageSlug" name="pageSlug"
                                   value="{{ old('pageSlug', $page->pageSlug) }}"
                                   placeholder="page-url">
                        </div>
                        <small class="form-text text-secondary">Leave empty to auto-generate from page name</small>
                        @error('pageSlug')
                        <div class="text-danger mt-1" style="font-size: 0.875rem;">{{ $message }}</div>
                        @enderror
                        @endif
                    </div>
                </div>
            </div>
            @endif

            @if(!$isHomepage)
            <!-- SEO Card (for non-homepage pages only) -->
            <div class="card">
                <div class="card-body">
                    <h6 class="section-title"><i class="bx bx-search-alt me-2"></i>SEO Settings</h6>

                    <!-- Meta Title -->
                    <div class="mb-3">
                        <label for="metaTitle" class="form-label">Meta Title</label>
                        <input type="text" class="form-control" id="metaTitle" name="metaTitle"
                               value="{{ old('metaTitle', $page->metaTitle) }}"
                               placeholder="Page title for search engines"
                               maxlength="255">
                        <small class="form-text text-secondary">Recommended: 50-60 characters</small>
                    </div>

                    <!-- Meta Description -->
                    <div class="mb-3">
                        <label for="metaDescription" class="form-label">Meta Description</label>
                        <textarea class="form-control" id="metaDescription" name="metaDescription"
                                  rows="3" placeholder="Brief description for search results"
                                  maxlength="500">{{ old('metaDescription', $page->metaDescription) }}</textarea>
                        <small class="form-text text-secondary">Recommended: 150-160 characters</small>
                    </div>

                    <!-- Meta Keywords -->
                    <div class="mb-0">
                        <label for="metaKeywords" class="form-label">Meta Keywords</label>
                        <input type="text" class="form-control" id="metaKeywords" name="metaKeywords"
                               value="{{ old('metaKeywords', $page->metaKeywords) }}"
                               placeholder="keyword1, keyword2, keyword3"
                               maxlength="500">
                        <small class="form-text text-secondary">Comma-separated keywords</small>
                    </div>
                </div>
            </div>
            @endif

            <!-- Page Info Card -->
            <div class="card">
                <div class="card-body">
                    <h6 class="section-title"><i class="bx bx-info-circle me-2"></i>Page Information</h6>
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-secondary">Created:</td>
                            <td class="text-dark">{{ $page->created_at->format('M d, Y h:i A') }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary">Updated:</td>
                            <td class="text-dark">{{ $page->updated_at->format('M d, Y h:i A') }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary">Type:</td>
                            <td class="text-dark">{{ $page->isSystemPage ? 'System Page' : 'Custom Page' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</form>

@if($isHomepage)
<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bx bx-plus-circle text-primary me-2"></i>Add New Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addItemForm">
                    <input type="hidden" id="addItemSectionKey" name="sectionKey">
                    <input type="hidden" id="addItemType" name="itemType">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-dark">Title</label>
                            <input type="text" class="form-control" name="title" id="addItemTitle">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-dark">Subtitle</label>
                            <input type="text" class="form-control" name="subtitle" id="addItemSubtitle">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark">Description</label>
                        <textarea class="form-control" name="description" id="addItemDescription" rows="3"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-dark">Icon Image</label>
                            <div class="add-icon-upload image-upload-zone" id="addItemIconZone">
                                <input type="file" class="d-none add-icon-input" accept="image/*" id="addItemIconInput">
                                <input type="hidden" name="icon" id="addItemIcon">
                                <div class="upload-placeholder">
                                    <i class="bx bx-cloud-upload text-muted" style="font-size: 1.5rem;"></i>
                                    <p class="text-secondary mb-0 mt-1 small">Upload Icon</p>
                                </div>
                                <img src="" class="image-preview d-none" id="addItemIconPreview" alt="Icon Preview">
                            </div>
                            <small class="text-secondary">Upload a custom icon image (PNG, SVG preferred)</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-dark">Link URL</label>
                            <input type="text" class="form-control" name="linkUrl" id="addItemLinkUrl">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark">Link Text</label>
                        <input type="text" class="form-control" name="linkText" id="addItemLinkText">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveNewItem">
                    <i class="bx bx-save me-1"></i> Save Item
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Item Modal -->
<div class="modal fade" id="editItemModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bx bx-edit text-primary me-2"></i>Edit Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editItemForm">
                    <input type="hidden" id="editItemId" name="itemId">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-dark">Title</label>
                            <input type="text" class="form-control" name="title" id="editItemTitle">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-dark">Subtitle</label>
                            <input type="text" class="form-control" name="subtitle" id="editItemSubtitle">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark">Description</label>
                        <textarea class="form-control" name="description" id="editItemDescription" rows="3"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-dark">Link URL</label>
                            <input type="text" class="form-control" name="linkUrl" id="editItemLinkUrl">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-dark">Link Text</label>
                            <input type="text" class="form-control" name="linkText" id="editItemLinkText">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-dark">Status</label>
                            <select class="form-select" name="isActive" id="editItemActive">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-dark">Icon Image</label>
                            <div class="image-upload-zone edit-modal-upload" id="editItemIconZone" data-field="icon" data-item-id="">
                                <input type="file" class="d-none edit-item-file-input" accept="image/*">
                                <div class="upload-placeholder" id="editItemIconPlaceholder">
                                    <i class="bx bx-cloud-upload text-muted" style="font-size: 1.5rem;"></i>
                                    <p class="text-secondary mb-0 mt-1 small">Upload Icon</p>
                                </div>
                                <img src="" class="image-preview d-none" id="editItemIconPreview" alt="Icon Preview">
                            </div>
                        </div>
                    </div>

                    <!-- Image uploads -->
                    <div class="row" id="editItemImagesRow">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-dark">Image (Before)</label>
                            <div class="image-upload-zone edit-modal-upload" data-field="image" data-item-id="">
                                <input type="file" class="d-none edit-item-file-input" accept="image/*">
                                <div class="upload-placeholder">
                                    <i class="bx bx-cloud-upload text-muted" style="font-size: 2rem;"></i>
                                    <p class="text-secondary mb-0 mt-2">Click to upload</p>
                                </div>
                                <img src="" class="image-preview d-none" alt="Preview">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3" id="editImage2Container" style="display: none;">
                            <label class="form-label text-dark">Image 2 (After)</label>
                            <div class="image-upload-zone edit-modal-upload" data-field="image2" data-item-id="">
                                <input type="file" class="d-none edit-item-file-input" accept="image/*">
                                <div class="upload-placeholder">
                                    <i class="bx bx-cloud-upload text-muted" style="font-size: 2rem;"></i>
                                    <p class="text-secondary mb-0 mt-2">Click to upload</p>
                                </div>
                                <img src="" class="image-preview d-none" alt="Preview">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveEditItem">
                    <i class="bx bx-save me-1"></i> Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteItemModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bx bx-trash text-danger me-2"></i>Delete Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-dark">Are you sure you want to delete <strong id="deleteItemName"></strong>?</p>
                <p class="text-secondary mb-0">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteItem">
                    <i class="bx bx-trash me-1"></i> Delete
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@section('script')
<!-- Toastr JS -->
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
@if(!$isHomepage)
<!-- TinyMCE -->
<script src="{{ URL::asset('build/libs/tinymce/tinymce.min.js') }}"></script>
@else
<!-- Sortable JS for Homepage -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
@endif

<script>
$(document).ready(function() {
    // Configure Toastr
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "timeOut": 3000
    };

@if(!$isHomepage)
    // Initialize TinyMCE
    tinymce.init({
        selector: '#pageContent',
        height: 500,
        menubar: true,
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount'
        ],
        toolbar: 'undo redo | blocks | ' +
            'bold italic forecolor backcolor | alignleft aligncenter ' +
            'alignright alignjustify | bullist numlist outdent indent | ' +
            'removeformat | link image media | code fullscreen | help',
        content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 14px; }',
        skin: 'oxide',
        branding: false,
        promotion: false,
        relative_urls: false,
        remove_script_host: false,
        convert_urls: true
    });

    // Form submission
    $('#pageForm').on('submit', function(e) {
        e.preventDefault();

        // Sync TinyMCE content
        tinymce.triggerSave();

        var $btn = $('#saveBtn');
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Saving...');

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    showSaveIndicator(false);
                } else {
                    toastr.error(response.message || 'Failed to save changes');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to save changes');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save Changes');
            }
        });
    });
@else
    // Homepage settings - no TinyMCE needed
    const csrfToken = '{{ csrf_token() }}';
    let itemToDelete = null;
    let currentEditItem = null;

    // Form submission - AJAX save without page reload
    $('#pageForm').on('submit', function(e) {
        e.preventDefault();

        var $btn = $('#saveBtn');
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Saving...');

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Changes saved successfully');
                } else {
                    toastr.error(response.message || 'Failed to save changes');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to save changes');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save Changes');
            }
        });
    });

    // Section toggle
    $('.section-toggle').on('change', function() {
        const sectionKey = $(this).data('section');
        const $label = $(this).next('label');

        $.ajax({
            url: `/anisenso-homepage-settings/toggle/${sectionKey}`,
            type: 'POST',
            data: { _token: csrfToken },
            success: function(response) {
                if (response.success) {
                    $label.text(response.isEnabled ? 'Enabled' : 'Disabled');
                    toastr.success(response.message);

                    // Update tab badge
                    const $tab = $(`#${sectionKey}-tab`);
                    if (response.isEnabled) {
                        $tab.find('.section-badge').remove();
                    } else {
                        if ($tab.find('.section-badge').length === 0) {
                            $tab.append('<span class="badge bg-secondary section-badge ms-1">Off</span>');
                        }
                    }
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error toggling section');
            }
        });
    });

    // Save section settings on blur
    $('.section-setting-input').on('blur', function() {
        const $input = $(this);
        const sectionKey = $input.data('section');
        const settingKey = $input.data('setting');
        const value = $input.val();

        const settings = {};
        settings[settingKey] = value;

        $.ajax({
            url: `/anisenso-homepage-settings/section/${sectionKey}`,
            type: 'PUT',
            data: { _token: csrfToken, settings: settings },
            success: function(response) {
                if (response.success) {
                    toastr.success('Settings saved');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error saving settings');
            }
        });
    });

    // Initialize sortable for items
    document.querySelectorAll('.sortable-items').forEach(function(el) {
        new Sortable(el, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: function(evt) {
                const items = [];
                $(evt.to).find('[data-item-id]').each(function(index) {
                    items.push({
                        id: $(this).data('item-id'),
                        order: index + 1
                    });
                });

                $.ajax({
                    url: '/anisenso-homepage-settings/items/reorder',
                    type: 'POST',
                    data: { _token: csrfToken, items: items },
                    success: function(response) {
                        if (response.success) {
                            toastr.success('Order updated');
                        }
                    }
                });
            }
        });
    });

    // Image upload for sections
    $('.section-image-upload').each(function() {
        const $zone = $(this);

        $zone.on('click', function() {
            $(this).find('.section-image-input').click();
        });
    });

    $('.section-image-input').on('change', function() {
        const file = this.files[0];
        if (!file) return;

        const $zone = $(this).closest('.section-image-upload');
        const sectionKey = $zone.data('section');
        const settingKey = $zone.data('setting');

        const formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('image', file);
        formData.append('settingKey', settingKey);

        $.ajax({
            url: `/anisenso-homepage-settings/section/${sectionKey}/image`,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $zone.find('.image-preview').attr('src', response.imageUrl).removeClass('d-none');
                    $zone.find('.upload-placeholder').addClass('d-none');
                    toastr.success('Image uploaded');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error uploading image');
            }
        });
    });
@endif
});


function showSaveIndicator(saving) {
    var indicator = $('#saveIndicator');
    if (saving) {
        indicator.addClass('saving').find('span').text('Saving...');
        indicator.find('i').removeClass('bx-check-circle').addClass('bx-loader-alt bx-spin');
    } else {
        indicator.removeClass('saving').find('span').text('Saved');
        indicator.find('i').removeClass('bx-loader-alt bx-spin').addClass('bx-check-circle');
    }
    indicator.fadeIn();

    if (!saving) {
        setTimeout(function() {
            indicator.fadeOut();
        }, 3000);
    }
}

@if($isHomepage)
// Homepage item management functions
let pendingIconFile = null;

function openAddItemModal(sectionKey, itemType) {
    $('#addItemSectionKey').val(sectionKey);
    $('#addItemType').val(itemType);
    $('#addItemForm')[0].reset();
    // Reset icon upload
    $('#addItemIconPreview').addClass('d-none').attr('src', '');
    $('#addItemIconZone .upload-placeholder').removeClass('d-none');
    $('#addItemIcon').val('');
    pendingIconFile = null;
    $('#addItemModal').modal('show');
}

function openEditItemModal(item, showImage2 = false) {
    $('#editItemId').val(item.id);
    $('#editItemTitle').val(item.title || '');
    $('#editItemSubtitle').val(item.subtitle || '');
    $('#editItemDescription').val(item.description || '');
    $('#editItemLinkUrl').val(item.linkUrl || '');
    $('#editItemLinkText').val(item.linkText || '');
    $('#editItemActive').val(item.isActive ? '1' : '0');

    // Set item ID on ALL upload zones in edit modal
    $('.edit-modal-upload').attr('data-item-id', item.id);

    // Handle image (Before)
    const $img1 = $('#editItemImagesRow').find('[data-field="image"]');
    if (item.image) {
        $img1.find('.image-preview').attr('src', item.image).removeClass('d-none');
        $img1.find('.upload-placeholder').addClass('d-none');
    } else {
        $img1.find('.image-preview').addClass('d-none');
        $img1.find('.upload-placeholder').removeClass('d-none');
    }

    // Handle icon image (using the correct selector)
    const $iconZone = $('#editItemIconZone');
    if (item.icon && item.icon.startsWith('/')) {
        $iconZone.find('.image-preview').attr('src', item.icon).removeClass('d-none');
        $iconZone.find('.upload-placeholder').addClass('d-none');
    } else {
        $iconZone.find('.image-preview').addClass('d-none');
        $iconZone.find('.upload-placeholder').removeClass('d-none');
    }

    if (showImage2) {
        $('#editImage2Container').show();
        const $img2 = $('#editItemImagesRow').find('[data-field="image2"]');
        if (item.image2) {
            $img2.find('.image-preview').attr('src', item.image2).removeClass('d-none');
            $img2.find('.upload-placeholder').addClass('d-none');
        } else {
            $img2.find('.image-preview').addClass('d-none');
            $img2.find('.upload-placeholder').removeClass('d-none');
        }
    } else {
        $('#editImage2Container').hide();
    }

    $('#editItemModal').modal('show');
}

// Icon upload for Add Item modal
$('#addItemIconZone').on('click', function() {
    $('#addItemIconInput').click();
});

$('#addItemIconInput').on('change', function() {
    const file = this.files[0];
    if (!file) return;

    pendingIconFile = file;

    // Show preview
    const reader = new FileReader();
    reader.onload = function(e) {
        $('#addItemIconPreview').attr('src', e.target.result).removeClass('d-none');
        $('#addItemIconZone .upload-placeholder').addClass('d-none');
    };
    reader.readAsDataURL(file);
});

function openDeleteItemModal(itemId, itemName) {
    window.itemToDelete = itemId;
    $('#deleteItemName').text(itemName || 'this item');
    $('#deleteItemModal').modal('show');
}

// Save new item
$('#saveNewItem').on('click', function() {
    const $btn = $(this);
    const sectionKey = $('#addItemSectionKey').val();
    const csrfToken = '{{ csrf_token() }}';

    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Saving...');

    // Use FormData to support file upload
    const formData = new FormData();
    formData.append('_token', csrfToken);
    formData.append('itemType', $('#addItemType').val());
    formData.append('title', $('#addItemTitle').val());
    formData.append('subtitle', $('#addItemSubtitle').val());
    formData.append('description', $('#addItemDescription').val());
    formData.append('linkUrl', $('#addItemLinkUrl').val());
    formData.append('linkText', $('#addItemLinkText').val());

    // Add icon file if selected
    if (pendingIconFile) {
        formData.append('iconFile', pendingIconFile);
    }

    $.ajax({
        url: `/anisenso-homepage-settings/section/${sectionKey}/items`,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                $('#addItemModal').modal('hide');
                toastr.success('Item added successfully!');
                pendingIconFile = null;
                $('#addItemForm')[0].reset();
            }
        },
        error: function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'Error adding item');
        },
        complete: function() {
            $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save Item');
        }
    });
});

// Save edit item
$('#saveEditItem').on('click', function() {
    const $btn = $(this);
    const itemId = $('#editItemId').val();
    const csrfToken = '{{ csrf_token() }}';

    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Saving...');

    $.ajax({
        url: `/anisenso-homepage-settings/items/${itemId}`,
        type: 'PUT',
        data: {
            _token: csrfToken,
            title: $('#editItemTitle').val(),
            subtitle: $('#editItemSubtitle').val(),
            description: $('#editItemDescription').val(),
            icon: $('#editItemIcon').val(),
            linkUrl: $('#editItemLinkUrl').val(),
            linkText: $('#editItemLinkText').val(),
            isActive: $('#editItemActive').val() == '1'
        },
        success: function(response) {
            if (response.success) {
                $('#editItemModal').modal('hide');
                toastr.success('Item updated successfully!');
                // Update the item card title in DOM
                const $card = $(`[data-item-id="${itemId}"]`);
                if ($card.length) {
                    $card.find('h6').first().text($('#editItemTitle').val() || 'Untitled');
                }
            }
        },
        error: function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'Error updating item');
        },
        complete: function() {
            $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save Changes');
        }
    });
});

// Delete item
$('#confirmDeleteItem').on('click', function() {
    if (!window.itemToDelete) return;

    const $btn = $(this);
    const csrfToken = '{{ csrf_token() }}';
    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Deleting...');

    $.ajax({
        url: `/anisenso-homepage-settings/items/${window.itemToDelete}`,
        type: 'DELETE',
        data: { _token: csrfToken },
        success: function(response) {
            if (response.success) {
                $('#deleteItemModal').modal('hide');
                toastr.success('Item deleted successfully');
                $(`[data-item-id="${window.itemToDelete}"]`).fadeOut(300, function() {
                    $(this).remove();
                });
            }
        },
        error: function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'Error deleting item');
        },
        complete: function() {
            $btn.prop('disabled', false).html('<i class="bx bx-trash me-1"></i> Delete');
            window.itemToDelete = null;
        }
    });
});

// Image upload in edit modal - using event delegation for reliability
$(document).on('click', '.edit-modal-upload', function(e) {
    // Prevent infinite loop: don't re-trigger if click came from the file input
    if ($(e.target).hasClass('edit-item-file-input')) {
        return;
    }
    e.preventDefault();
    e.stopPropagation();
    $(this).find('.edit-item-file-input')[0].click(); // Use native click to avoid jQuery event loop
});

$(document).on('change', '.edit-item-file-input', function(e) {
    e.preventDefault();
    e.stopPropagation();
    e.stopImmediatePropagation(); // Prevent other handlers from firing

    const file = this.files[0];
    if (!file) return;

    const $zone = $(this).closest('.image-upload-zone');
    const itemId = $zone.attr('data-item-id');
    const field = $zone.attr('data-field');
    const csrfToken = '{{ csrf_token() }}';

    // Validate that we have required data
    if (!itemId || itemId === 'undefined' || itemId === '') {
        toastr.error('Item ID not found. Please close and reopen the modal.');
        console.error('Missing item ID for image upload', { itemId, field });
        return;
    }

    if (!field) {
        toastr.error('Field type not specified');
        console.error('Missing field for image upload');
        return;
    }

    // Show loading state
    $zone.find('.upload-placeholder').html('<i class="bx bx-loader-alt bx-spin" style="font-size: 2rem;"></i><p class="text-secondary mb-0 mt-2">Uploading...</p>');

    const formData = new FormData();
    formData.append('_token', csrfToken);
    formData.append('image', file);
    formData.append('field', field);

    $.ajax({
        url: `/anisenso-homepage-settings/items/${itemId}/image`,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                $zone.find('.image-preview').attr('src', response.imageUrl).removeClass('d-none');
                $zone.find('.upload-placeholder').addClass('d-none');
                toastr.success('Image uploaded');
            } else {
                toastr.error(response.message || 'Upload failed');
                resetUploadPlaceholder($zone, field);
            }
        },
        error: function(xhr) {
            console.error('Image upload error:', xhr.responseText);
            toastr.error(xhr.responseJSON?.message || 'Error uploading image');
            resetUploadPlaceholder($zone, field);
        }
    });

    // Reset the file input so the same file can be selected again
    $(this).val('');
});

function resetUploadPlaceholder($zone, field) {
    const iconSize = field === 'icon' ? '1.5rem' : '2rem';
    const text = field === 'icon' ? 'Upload Icon' : 'Click to upload';
    $zone.find('.upload-placeholder')
        .html(`<i class="bx bx-cloud-upload text-muted" style="font-size: ${iconSize};"></i><p class="text-secondary mb-0 mt-2">${text}</p>`)
        .removeClass('d-none');
}

// Schema Preview Toggle
window.toggleSchemaPreview = function(sectionKey) {
    const previewDiv = $(`#schemaPreview_${sectionKey}`);
    const codeDiv = $(`#schemaCode_${sectionKey}`);

    if (previewDiv.hasClass('d-none')) {
        previewDiv.removeClass('d-none');

        // Generate schema preview based on section settings
        const schema = generateSchemaPreview(sectionKey);
        codeDiv.text(JSON.stringify(schema, null, 2));
    } else {
        previewDiv.addClass('d-none');
    }
};

function generateSchemaPreview(sectionKey) {
    const baseUrl = window.location.origin;

    // Get section settings from inputs
    const getSettingValue = (key) => {
        const input = $(`.section-setting-input[data-section="${sectionKey}"][data-setting="${key}"]`);
        return input.val() || '';
    };

    const schemaType = getSettingValue('seo_schemaType') || 'WebPageElement';
    const title = getSettingValue('seo_title') || getSettingValue('title') || '';
    const description = getSettingValue('seo_description') || getSettingValue('description') || '';
    const imageAlt = getSettingValue('seo_imageAlt') || '';

    let schema = {
        "@context": "https://schema.org",
        "@type": schemaType,
        "name": title,
        "description": description
    };

    // Add section-specific schema properties
    switch(sectionKey) {
        case 'hero':
        case 'about':
            const orgName = getSettingValue('seo_orgName');
            const orgLogo = getSettingValue('seo_orgLogo');
            const contactPhone = getSettingValue('seo_contactPhone');
            const contactEmail = getSettingValue('seo_contactEmail');

            if (schemaType === 'Organization' || schemaType === 'LocalBusiness') {
                schema = {
                    "@context": "https://schema.org",
                    "@type": schemaType,
                    "name": orgName || title,
                    "description": description,
                    "logo": orgLogo ? { "@type": "ImageObject", "url": orgLogo } : undefined,
                    "telephone": contactPhone || undefined,
                    "email": contactEmail || undefined,
                    "url": baseUrl
                };
            }
            break;

        case 'testimonials':
        case 'success_stories':
            const aggregateRating = getSettingValue('seo_aggregateRating');
            const reviewCount = getSettingValue('seo_reviewCount');
            const bestRating = getSettingValue('seo_bestRating');

            if (aggregateRating) {
                schema.aggregateRating = {
                    "@type": "AggregateRating",
                    "ratingValue": aggregateRating,
                    "reviewCount": reviewCount || "100",
                    "bestRating": bestRating || "5",
                    "worstRating": "1"
                };
            }
            break;

        case 'process':
            const totalTime = getSettingValue('seo_totalTime');
            const estimatedCost = getSettingValue('seo_estimatedCost');

            if (schemaType === 'HowTo') {
                schema = {
                    "@context": "https://schema.org",
                    "@type": "HowTo",
                    "name": title,
                    "description": description,
                    "totalTime": totalTime || undefined,
                    "estimatedCost": estimatedCost ? {
                        "@type": "MonetaryAmount",
                        "currency": "PHP",
                        "value": estimatedCost
                    } : undefined,
                    "step": []
                };
            }
            break;

        case 'award':
            const awardName = getSettingValue('seo_awardName');
            const awardDate = getSettingValue('seo_awardDate');
            const awardOrg = getSettingValue('seo_awardOrg');

            if (awardName) {
                schema.award = {
                    "@type": "Award",
                    "name": awardName,
                    "dateReceived": awardDate || undefined,
                    "issuedBy": awardOrg ? { "@type": "Organization", "name": awardOrg } : undefined
                };
            }
            break;
    }

    // Add image if alt text is provided
    if (imageAlt) {
        schema.image = {
            "@type": "ImageObject",
            "alternateName": imageAlt
        };
    }

    // Clean undefined values
    Object.keys(schema).forEach(key => {
        if (schema[key] === undefined || schema[key] === '') {
            delete schema[key];
        }
    });

    return schema;
}
@endif
</script>
@endsection
