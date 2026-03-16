@extends('layouts.master')

@section('title') Create New Page @endsection

@section('css')
<!-- Toastr CSS -->
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />

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

.status-toggle {
    display: flex;
    gap: 0.5rem;
}

.status-toggle .btn {
    flex: 1;
}

.status-toggle .btn.active {
    pointer-events: none;
}

.icon-selector {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 0.5rem;
    max-height: 200px;
    overflow-y: auto;
    padding: 0.5rem;
    border: 1px solid #e9ecef;
    border-radius: 4px;
}

.icon-option {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #e9ecef;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 1.1rem;
    color: #6c757d;
}

.icon-option:hover {
    background: #e9ecef;
    color: #495057;
}

.icon-option.selected {
    background: #556ee6;
    color: #fff;
    border-color: #556ee6;
}
</style>
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Ani-Senso @endslot
@slot('li_2') Website @endslot
@slot('li_3') Pages @endslot
@slot('title') Create New Page @endslot
@endcomponent

<form id="pageForm" method="POST" action="{{ route('anisenso-website-pages.store') }}">
    @csrf

    <div class="page-editor-container">
        <!-- Main Editor Area -->
        <div class="editor-main">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Page Content</h5>
                </div>
                <div class="card-body">
                    <!-- Page Name -->
                    <div class="mb-3">
                        <label for="pageName" class="form-label">Page Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('pageName') is-invalid @enderror"
                               id="pageName" name="pageName" value="{{ old('pageName') }}"
                               placeholder="Enter page name" required>
                        @error('pageName')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Page Content (TinyMCE) -->
                    <div class="mb-3">
                        <label for="pageContent" class="form-label">Page Content</label>
                        <textarea class="form-control" id="pageContent" name="pageContent"
                                  rows="20">{{ old('pageContent') }}</textarea>
                    </div>
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
                            <i class="bx bx-save me-1"></i> Create Page
                        </button>
                        <a href="{{ route('anisenso-website-pages') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-arrow-back me-1"></i> Back to Pages
                        </a>
                    </div>
                </div>
            </div>

            <!-- Status Card -->
            <div class="card">
                <div class="card-body">
                    <h6 class="section-title"><i class="bx bx-toggle-left me-2"></i>Status</h6>
                    <div class="status-toggle">
                        <button type="button" class="btn btn-sm btn-warning active"
                                onclick="setStatus('draft')">
                            <i class="bx bx-edit me-1"></i>Draft
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success"
                                onclick="setStatus('published')">
                            <i class="bx bx-check me-1"></i>Published
                        </button>
                    </div>
                    <input type="hidden" name="pageStatus" id="pageStatus" value="draft">
                </div>
            </div>

            <!-- URL Settings Card -->
            <div class="card">
                <div class="card-body">
                    <h6 class="section-title"><i class="bx bx-link me-2"></i>URL Settings</h6>

                    <!-- Page Slug -->
                    <div class="mb-0">
                        <label for="pageSlug" class="form-label">Page Slug</label>
                        <div class="input-group">
                            <span class="input-group-text">/</span>
                            <input type="text" class="form-control @error('pageSlug') is-invalid @enderror"
                                   id="pageSlug" name="pageSlug"
                                   value="{{ old('pageSlug') }}"
                                   placeholder="page-url">
                        </div>
                        <small class="form-text text-secondary">Leave empty to auto-generate from page name</small>
                        @error('pageSlug')
                        <div class="text-danger mt-1" style="font-size: 0.875rem;">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Page Icon Card -->
            <div class="card">
                <div class="card-body">
                    <h6 class="section-title"><i class="bx bx-palette me-2"></i>Page Icon</h6>
                    <div class="mb-2 d-flex align-items-center gap-2">
                        <div class="page-icon bg-soft-primary text-primary" id="selectedIconPreview"
                             style="width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                            <i class="bx bx-file"></i>
                        </div>
                        <span class="text-secondary" id="selectedIconName">bx-file</span>
                    </div>
                    <input type="hidden" name="pageIcon" id="pageIcon" value="bx-file">
                    <div class="icon-selector">
                        @php
                        $icons = ['bx-home', 'bx-file', 'bx-info-circle', 'bx-help-circle', 'bx-envelope', 'bx-phone', 'bx-map', 'bx-user', 'bx-group', 'bx-store', 'bx-cart', 'bx-package', 'bx-book', 'bx-news', 'bx-image', 'bx-video', 'bx-music', 'bx-calendar', 'bx-star', 'bx-heart', 'bx-comment', 'bx-share', 'bx-link', 'bx-cog'];
                        @endphp
                        @foreach($icons as $icon)
                        <div class="icon-option {{ $icon === 'bx-file' ? 'selected' : '' }}"
                             data-icon="{{ $icon }}" onclick="selectIcon('{{ $icon }}')">
                            <i class="bx {{ $icon }}"></i>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- SEO Card -->
            <div class="card">
                <div class="card-body">
                    <h6 class="section-title"><i class="bx bx-search-alt me-2"></i>SEO Settings</h6>

                    <!-- Meta Title -->
                    <div class="mb-3">
                        <label for="metaTitle" class="form-label">Meta Title</label>
                        <input type="text" class="form-control" id="metaTitle" name="metaTitle"
                               value="{{ old('metaTitle') }}"
                               placeholder="Page title for search engines"
                               maxlength="255">
                        <small class="form-text text-secondary">Recommended: 50-60 characters</small>
                    </div>

                    <!-- Meta Description -->
                    <div class="mb-3">
                        <label for="metaDescription" class="form-label">Meta Description</label>
                        <textarea class="form-control" id="metaDescription" name="metaDescription"
                                  rows="3" placeholder="Brief description for search results"
                                  maxlength="500">{{ old('metaDescription') }}</textarea>
                        <small class="form-text text-secondary">Recommended: 150-160 characters</small>
                    </div>

                    <!-- Meta Keywords -->
                    <div class="mb-0">
                        <label for="metaKeywords" class="form-label">Meta Keywords</label>
                        <input type="text" class="form-control" id="metaKeywords" name="metaKeywords"
                               value="{{ old('metaKeywords') }}"
                               placeholder="keyword1, keyword2, keyword3"
                               maxlength="500">
                        <small class="form-text text-secondary">Comma-separated keywords</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection

@section('script')
<!-- Toastr JS -->
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<!-- TinyMCE -->
<script src="{{ URL::asset('build/libs/tinymce/tinymce.min.js') }}"></script>

<script>
$(document).ready(function() {
    // Configure Toastr
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "timeOut": 3000
    };

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

    // Auto-generate slug from page name
    $('#pageName').on('blur', function() {
        var slug = $('#pageSlug').val();
        if (!slug) {
            var name = $(this).val();
            slug = name.toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim();
            $('#pageSlug').val(slug);
        }
    });

    // Form submission - sync TinyMCE before submit
    $('#pageForm').on('submit', function() {
        tinymce.triggerSave();

        var $btn = $('#saveBtn');
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Creating...');
    });
});

function setStatus(status) {
    $('#pageStatus').val(status);

    // Update button states
    $('.status-toggle .btn').removeClass('active');
    if (status === 'draft') {
        $('.status-toggle .btn-warning, .status-toggle .btn-outline-warning')
            .removeClass('btn-outline-warning').addClass('btn-warning active');
        $('.status-toggle .btn-success')
            .removeClass('btn-success').addClass('btn-outline-success');
    } else {
        $('.status-toggle .btn-success, .status-toggle .btn-outline-success')
            .removeClass('btn-outline-success').addClass('btn-success active');
        $('.status-toggle .btn-warning')
            .removeClass('btn-warning').addClass('btn-outline-warning');
    }
}

function selectIcon(icon) {
    // Update hidden input
    $('#pageIcon').val(icon);

    // Update preview
    $('#selectedIconPreview').html('<i class="bx ' + icon + '"></i>');
    $('#selectedIconName').text(icon);

    // Update selection state
    $('.icon-option').removeClass('selected');
    $('[data-icon="' + icon + '"]').addClass('selected');
}
</script>
@endsection
