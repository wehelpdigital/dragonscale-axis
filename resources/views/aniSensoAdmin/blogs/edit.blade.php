@extends('layouts.master')

@section('title') Edit Blog Post @endsection

@section('css')
<!-- GrapesJS -->
<link href="https://unpkg.com/grapesjs/dist/css/grapes.min.css" rel="stylesheet">
<link href="https://unpkg.com/grapesjs-preset-webpage/dist/grapesjs-preset-webpage.min.css" rel="stylesheet">

<style>
/* Builder Container */
.builder-container {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
    background: #fff;
}

.editor-row {
    display: flex;
    height: 600px;
}

.editor-canvas {
    flex: 1;
    overflow: hidden;
}

#gjs {
    border: none;
    height: 100%;
}

.panel-right {
    width: 280px;
    border-left: 1px solid #dee2e6;
    background: #f8f9fa;
    display: flex;
    flex-direction: column;
}

.panel-switcher {
    display: flex;
    flex-wrap: wrap;
    gap: 2px;
    padding: 8px;
    background: #fff;
    border-bottom: 1px solid #dee2e6;
}

.panel-btn {
    flex: 1;
    min-width: 45%;
    padding: 8px 4px;
    border: 1px solid #dee2e6;
    background: #fff;
    border-radius: 4px;
    font-size: 11px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    color: #495057;
    transition: all 0.2s;
}

.panel-btn:hover {
    background: #e9ecef;
}

.panel-btn.active {
    background: #556ee6;
    color: #fff;
    border-color: #556ee6;
}

.panel-btn i {
    font-size: 14px;
}

.panel-content {
    display: none;
    flex: 1;
    overflow-y: auto;
    padding: 10px;
}

.panel-content.active {
    display: block;
}

#blocks-container .gjs-block {
    width: calc(50% - 5px);
    min-height: 70px;
    margin: 2px;
}

#blocks-container .gjs-blocks-c {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-start;
}

#blocks-container .gjs-block-category {
    width: 100%;
}

#blocks-container .gjs-block-category .gjs-title {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 8px 10px;
    font-weight: 600;
    font-size: 12px;
}

/* GrapesJS Panel Customization */
.gjs-one-bg { background-color: #f8f9fa; }
.gjs-two-color { color: #495057; }
.gjs-three-bg { background-color: #556ee6; }
.gjs-four-color, .gjs-four-color-h:hover { color: #556ee6; }

.gjs-pn-btn {
    border-radius: 4px;
    padding: 8px 10px;
}

.gjs-pn-btn.gjs-pn-active {
    background-color: #556ee6;
    color: #fff;
}

.gjs-block {
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #e9ecef;
    transition: all 0.2s;
}

.gjs-block:hover {
    border-color: #556ee6;
    box-shadow: 0 2px 8px rgba(85, 110, 230, 0.15);
}

/* Image Preview */
.image-preview-container {
    width: 100%;
    max-width: 100%;
    height: 180px;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    cursor: pointer;
    transition: all 0.2s ease;
    overflow: hidden;
    position: relative;
}

.image-preview-container:hover {
    border-color: #556ee6;
    background: #f0f4ff;
}

.image-preview-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.image-preview-container .placeholder {
    text-align: center;
    color: #74788d;
}

.image-preview-container .placeholder i {
    font-size: 2.5rem;
    margin-bottom: 8px;
}

.remove-image-btn {
    position: absolute;
    top: 8px;
    right: 8px;
    background: rgba(244, 106, 106, 0.9);
    border: none;
    border-radius: 50%;
    width: 28px;
    height: 28px;
    color: white;
    cursor: pointer;
    z-index: 10;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.remove-image-btn:hover {
    background: #f46a6a;
}

/* SEO Score Indicator */
.seo-score-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: bold;
    color: #fff;
    margin: 0 auto;
}

.seo-score-good { background: linear-gradient(135deg, #34c38f, #28a745); }
.seo-score-ok { background: linear-gradient(135deg, #f1b44c, #ffc107); }
.seo-score-bad { background: linear-gradient(135deg, #f46a6a, #dc3545); }

.seo-item {
    padding: 8px 12px;
    border-radius: 6px;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.seo-item.good { background: rgba(52, 195, 143, 0.1); border-left: 3px solid #34c38f; }
.seo-item.ok { background: rgba(241, 180, 76, 0.1); border-left: 3px solid #f1b44c; }
.seo-item.bad { background: rgba(244, 106, 106, 0.1); border-left: 3px solid #f46a6a; }

.seo-item i { font-size: 1.2rem; }
.seo-item.good i { color: #34c38f; }
.seo-item.ok i { color: #f1b44c; }
.seo-item.bad i { color: #f46a6a; }

/* Tab styling */
.nav-tabs-custom {
    border-bottom: 2px solid #dee2e6;
}

.nav-tabs-custom .nav-link {
    border: none;
    padding: 12px 20px;
    color: #74788d;
    font-weight: 500;
    position: relative;
}

.nav-tabs-custom .nav-link.active {
    color: #556ee6;
    background: transparent;
}

.nav-tabs-custom .nav-link.active::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    right: 0;
    height: 2px;
    background: #556ee6;
}

/* Character counters */
.char-counter {
    font-size: 12px;
    color: #74788d;
}

.char-counter.warning { color: #f1b44c; }
.char-counter.danger { color: #f46a6a; }

/* Editor mode toggle */
.editor-mode-toggle {
    display: flex;
    background: #f8f9fa;
    border-radius: 6px;
    padding: 4px;
    gap: 4px;
}

.editor-mode-toggle .btn {
    flex: 1;
    border-radius: 4px;
    padding: 8px 16px;
    font-size: 13px;
}

.editor-mode-toggle .btn.active {
    background: #fff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

/* Scrollable panels */
.settings-panel {
    max-height: calc(100vh - 250px);
    overflow-y: auto;
}

/* Preview panel */
.preview-panel {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
}

.preview-panel .preview-title {
    font-size: 1.1rem;
    color: #1a0dab;
    margin-bottom: 4px;
}

.preview-panel .preview-url {
    font-size: 13px;
    color: #006621;
    margin-bottom: 4px;
}

.preview-panel .preview-description {
    font-size: 13px;
    color: #545454;
    line-height: 1.4;
}
</style>
@endsection

@section('content')

@component('components.breadcrumb')
@slot('li_1') Ani-Senso @endslot
@slot('li_2') Blog @endslot
@slot('title') Edit Blog Post @endslot
@endcomponent

<form action="{{ route('anisenso-blogs.update', ['id' => $blog->id]) }}" method="POST" enctype="multipart/form-data" id="blogForm">
    @csrf
    @method('PUT')
    <input type="hidden" name="builderContent" id="builderContent">
    <input type="hidden" name="useBuilder" id="useBuilder" value="{{ $blog->useBuilder ? '1' : '0' }}">
    <input type="hidden" name="blogContent" id="blogContentHidden">
    <input type="hidden" name="removeImage" id="removeImage" value="0">

    <div class="row">
        <!-- Main Content Area -->
        <div class="col-lg-9">
            <div class="card">
                <div class="card-body">
                    <!-- Header with Back Button -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="card-title mb-1">Edit Blog Post</h4>
                            <p class="text-secondary mb-0">Update your blog content</p>
                        </div>
                        <a href="{{ route('anisenso-blogs') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-arrow-back me-1"></i> Back
                        </a>
                    </div>

                    <!-- Title Field -->
                    <div class="mb-4">
                        <label for="blogTitle" class="form-label">Blog Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-lg @error('blogTitle') is-invalid @enderror"
                               id="blogTitle" name="blogTitle" value="{{ old('blogTitle', $blog->blogTitle) }}"
                               placeholder="Enter a compelling title for your blog post..." required>
                        <div class="d-flex justify-content-between mt-1">
                            <small class="text-secondary">A good title is 50-60 characters for SEO</small>
                            <span class="char-counter" id="titleCounter">0/60</span>
                        </div>
                        @error('blogTitle')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Excerpt Field -->
                    <div class="mb-4">
                        <label for="blogExcerpt" class="form-label">Excerpt <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('blogExcerpt') is-invalid @enderror"
                                  id="blogExcerpt" name="blogExcerpt" rows="3"
                                  placeholder="Write a brief summary that appears in blog listings and search results..."
                                  maxlength="500" required>{{ old('blogExcerpt', $blog->blogExcerpt) }}</textarea>
                        <div class="d-flex justify-content-between mt-1">
                            <small class="text-secondary">This appears in blog listings and search results</small>
                            <span class="char-counter" id="excerptCounter">0/500</span>
                        </div>
                        @error('blogExcerpt')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Editor Mode Toggle -->
                    <div class="mb-3">
                        <label class="form-label">Content Editor</label>
                        <div class="editor-mode-toggle mb-3">
                            <button type="button" class="btn {{ $blog->useBuilder ? 'active' : '' }}" id="builderModeBtn" onclick="switchEditorMode('builder')">
                                <i class="bx bx-layout me-1"></i> Visual Builder
                            </button>
                            <button type="button" class="btn {{ !$blog->useBuilder ? 'active' : '' }}" id="codeModeBtn" onclick="switchEditorMode('code')">
                                <i class="bx bx-code-alt me-1"></i> HTML Code
                            </button>
                        </div>
                    </div>

                    <!-- Visual Builder -->
                    <div id="builderEditor" class="builder-container" style="{{ !$blog->useBuilder ? 'display: none;' : '' }}">
                        <div class="editor-row">
                            <div class="editor-canvas">
                                <div id="gjs"></div>
                            </div>
                            <div class="panel-right">
                                <div class="panel-switcher">
                                    <button class="panel-btn active" data-panel="blocks"><i class="bx bx-grid-alt"></i> Blocks</button>
                                    <button class="panel-btn" data-panel="styles"><i class="bx bx-paint-roll"></i> Styles</button>
                                    <button class="panel-btn" data-panel="layers"><i class="bx bx-layer"></i> Layers</button>
                                    <button class="panel-btn" data-panel="traits"><i class="bx bx-cog"></i> Settings</button>
                                </div>
                                <div id="blocks-container" class="panel-content active"></div>
                                <div id="styles-container" class="panel-content"></div>
                                <div id="layers-container" class="panel-content"></div>
                                <div id="traits-container" class="panel-content"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Code Editor -->
                    <div id="codeEditor" style="{{ $blog->useBuilder ? 'display: none;' : '' }}">
                        <textarea class="form-control" id="htmlCodeEditor" rows="20"
                                  placeholder="Write or paste your HTML content here...">{{ old('blogContent', $blog->blogContent) }}</textarea>
                    </div>
                </div>
            </div>

            <!-- SEO Settings Card -->
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bx bx-search-alt me-2"></i>SEO Settings</h5>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs nav-tabs-custom mb-4" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#seoBasic">
                                <i class="bx bx-text me-1"></i> Basic
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#seoSocial">
                                <i class="bx bx-share-alt me-1"></i> Social Media
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#seoAdvanced">
                                <i class="bx bx-cog me-1"></i> Advanced
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <!-- Basic SEO -->
                        <div class="tab-pane fade show active" id="seoBasic">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label for="focusKeyword" class="form-label">Focus Keyword</label>
                                        <input type="text" class="form-control" id="focusKeyword" name="focusKeyword"
                                               value="{{ old('focusKeyword', $blog->focusKeyword) }}" placeholder="e.g., rice farming tips">
                                        <small class="text-secondary">The main keyword you want this post to rank for</small>
                                    </div>

                                    <div class="mb-3">
                                        <label for="metaTitle" class="form-label">Meta Title</label>
                                        <input type="text" class="form-control" id="metaTitle" name="metaTitle"
                                               value="{{ old('metaTitle', $blog->metaTitle) }}" placeholder="SEO title (leave empty to use blog title)">
                                        <div class="d-flex justify-content-between mt-1">
                                            <small class="text-secondary">Recommended: 50-60 characters</small>
                                            <span class="char-counter" id="metaTitleCounter">0/60</span>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="metaDescription" class="form-label">Meta Description</label>
                                        <textarea class="form-control" id="metaDescription" name="metaDescription" rows="3"
                                                  placeholder="SEO description (leave empty to use excerpt)">{{ old('metaDescription', $blog->metaDescription) }}</textarea>
                                        <div class="d-flex justify-content-between mt-1">
                                            <small class="text-secondary">Recommended: 150-160 characters</small>
                                            <span class="char-counter" id="metaDescCounter">0/160</span>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="metaKeywords" class="form-label">Meta Keywords</label>
                                        <input type="text" class="form-control" id="metaKeywords" name="metaKeywords"
                                               value="{{ old('metaKeywords', $blog->metaKeywords) }}" placeholder="farming, rice, agriculture, tips (comma separated)">
                                    </div>

                                    <!-- Google Preview -->
                                    <div class="preview-panel mt-4">
                                        <label class="form-label text-dark mb-2"><i class="bx bxl-google me-1"></i>Google Search Preview</label>
                                        <div class="preview-title" id="googlePreviewTitle">{{ $blog->getEffectiveMetaTitle() }}</div>
                                        <div class="preview-url" id="googlePreviewUrl">https://anisenso.com/blog/{{ $blog->blogSlug }}</div>
                                        <div class="preview-description" id="googlePreviewDesc">{{ $blog->getEffectiveMetaDescription() }}</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <!-- SEO Score -->
                                    <div class="card border">
                                        <div class="card-body text-center">
                                            <label class="form-label text-dark">SEO Score</label>
                                            <div class="seo-score-circle {{ $blog->seoScore >= 80 ? 'seo-score-good' : ($blog->seoScore >= 50 ? 'seo-score-ok' : 'seo-score-bad') }} mb-3" id="seoScoreCircle">{{ $blog->seoScore }}</div>
                                            <div id="seoAnalysisItems">
                                                @if($blog->seoAnalysis)
                                                    @foreach($blog->seoAnalysis as $key => $item)
                                                        <div class="seo-item {{ $item['status'] }}">
                                                            <i class="bx {{ $item['status'] === 'good' ? 'bx-check-circle' : ($item['status'] === 'ok' ? 'bx-error-circle' : 'bx-x-circle') }}"></i>
                                                            <span class="text-dark">{{ $item['message'] }}</span>
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Social Media -->
                        <div class="tab-pane fade" id="seoSocial">
                            <div class="row">
                                <!-- Open Graph -->
                                <div class="col-md-6">
                                    <h6 class="mb-3"><i class="bx bxl-facebook-circle text-primary me-1"></i>Facebook / Open Graph</h6>
                                    <div class="mb-3">
                                        <label for="ogTitle" class="form-label">OG Title</label>
                                        <input type="text" class="form-control" id="ogTitle" name="ogTitle"
                                               value="{{ old('ogTitle', $blog->ogTitle) }}" placeholder="Leave empty to use blog title">
                                    </div>
                                    <div class="mb-3">
                                        <label for="ogDescription" class="form-label">OG Description</label>
                                        <textarea class="form-control" id="ogDescription" name="ogDescription" rows="2"
                                                  placeholder="Leave empty to use excerpt">{{ old('ogDescription', $blog->ogDescription) }}</textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">OG Image</label>
                                        <div class="image-preview-container" style="height: 120px;" onclick="document.getElementById('ogImage').click()">
                                            @if($blog->ogImage)
                                                <img id="ogImagePreview" src="/{{ $blog->ogImage }}" alt="Preview">
                                                <div class="placeholder" id="ogImagePlaceholder" style="display: none;">
                                                    <i class="bx bx-image"></i>
                                                    <p class="mb-0 small">1200x630px recommended</p>
                                                </div>
                                            @else
                                                <div class="placeholder" id="ogImagePlaceholder">
                                                    <i class="bx bx-image"></i>
                                                    <p class="mb-0 small">1200x630px recommended</p>
                                                </div>
                                                <img id="ogImagePreview" src="" alt="Preview" style="display: none;">
                                            @endif
                                        </div>
                                        <input type="file" class="d-none" id="ogImage" name="ogImage" accept="image/*">
                                    </div>
                                </div>

                                <!-- Twitter Card -->
                                <div class="col-md-6">
                                    <h6 class="mb-3"><i class="bx bxl-twitter text-info me-1"></i>Twitter Card</h6>
                                    <div class="mb-3">
                                        <label for="twitterTitle" class="form-label">Twitter Title</label>
                                        <input type="text" class="form-control" id="twitterTitle" name="twitterTitle"
                                               value="{{ old('twitterTitle', $blog->twitterTitle) }}" placeholder="Leave empty to use blog title">
                                    </div>
                                    <div class="mb-3">
                                        <label for="twitterDescription" class="form-label">Twitter Description</label>
                                        <textarea class="form-control" id="twitterDescription" name="twitterDescription" rows="2"
                                                  placeholder="Leave empty to use excerpt">{{ old('twitterDescription', $blog->twitterDescription) }}</textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Twitter Image</label>
                                        <div class="image-preview-container" style="height: 120px;" onclick="document.getElementById('twitterImage').click()">
                                            @if($blog->twitterImage)
                                                <img id="twitterImagePreview" src="/{{ $blog->twitterImage }}" alt="Preview">
                                                <div class="placeholder" id="twitterImagePlaceholder" style="display: none;">
                                                    <i class="bx bx-image"></i>
                                                    <p class="mb-0 small">1200x600px recommended</p>
                                                </div>
                                            @else
                                                <div class="placeholder" id="twitterImagePlaceholder">
                                                    <i class="bx bx-image"></i>
                                                    <p class="mb-0 small">1200x600px recommended</p>
                                                </div>
                                                <img id="twitterImagePreview" src="" alt="Preview" style="display: none;">
                                            @endif
                                        </div>
                                        <input type="file" class="d-none" id="twitterImage" name="twitterImage" accept="image/*">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Advanced SEO -->
                        <div class="tab-pane fade" id="seoAdvanced">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="canonicalUrl" class="form-label">Canonical URL</label>
                                        <input type="url" class="form-control" id="canonicalUrl" name="canonicalUrl"
                                               value="{{ old('canonicalUrl', $blog->canonicalUrl) }}" placeholder="https://...">
                                        <small class="text-secondary">Set if this content exists elsewhere</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="schemaType" class="form-label">Schema Type</label>
                                        <select class="form-select" id="schemaType" name="schemaType">
                                            @foreach($schemaTypes as $key => $label)
                                                <option value="{{ $key }}" {{ old('schemaType', $blog->schemaType) == $key ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-secondary">Structured data type for search engines</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-3">
            <div class="settings-panel">
                <!-- Publish Settings -->
                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bx bx-cog me-2"></i>Publish Settings</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="blogStatus" class="form-label">Status</label>
                            <select class="form-select" id="blogStatus" name="blogStatus" required>
                                @foreach($statuses as $key => $label)
                                    <option value="{{ $key }}" {{ old('blogStatus', $blog->blogStatus) == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="blogCategory" class="form-label">Category</label>
                            <select class="form-select" id="blogCategory" name="blogCategory" required>
                                <option value="">Select Category</option>
                                @foreach($categories as $category => $color)
                                    <option value="{{ $category }}" {{ old('blogCategory', $blog->blogCategory) == $category ? 'selected' : '' }}>
                                        {{ $category }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="authorName" class="form-label">Author Name</label>
                            <input type="text" class="form-control" id="authorName" name="authorName"
                                   value="{{ old('authorName', $blog->authorName) }}" placeholder="Optional">
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="isFeatured" name="isFeatured" value="1"
                                   {{ old('isFeatured', $blog->isFeatured) ? 'checked' : '' }}>
                            <label class="form-check-label" for="isFeatured">
                                <i class="bx bxs-star text-warning"></i> Featured Post
                            </label>
                        </div>

                        @if($blog->publishedAt)
                            <div class="alert alert-light mb-0 py-2">
                                <small class="text-secondary">
                                    <i class="bx bx-calendar me-1"></i>
                                    Published: {{ $blog->publishedAt->format('M j, Y g:i A') }}
                                </small>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Featured Image -->
                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bx bx-image me-2"></i>Featured Image</h6>
                    </div>
                    <div class="card-body">
                        <div class="image-preview-container" onclick="document.getElementById('blogFeaturedImage').click()">
                            @if($blog->blogFeaturedImage)
                                <button type="button" class="remove-image-btn" onclick="event.stopPropagation(); removeCurrentImage();">
                                    <i class="bx bx-x"></i>
                                </button>
                                <img id="imagePreview" src="/{{ $blog->blogFeaturedImage }}" alt="Preview">
                                <div class="placeholder" id="imagePlaceholder" style="display: none;">
                                    <i class="bx bx-cloud-upload"></i>
                                    <p class="mb-0">Click to upload</p>
                                    <small>Max 5MB</small>
                                </div>
                            @else
                                <div class="placeholder" id="imagePlaceholder">
                                    <i class="bx bx-cloud-upload"></i>
                                    <p class="mb-0">Click to upload</p>
                                    <small>Max 5MB</small>
                                </div>
                                <img id="imagePreview" src="" alt="Preview" style="display: none;">
                            @endif
                        </div>
                        <input type="file" class="d-none" id="blogFeaturedImage" name="blogFeaturedImage" accept="image/*">
                    </div>
                </div>

                <!-- Statistics -->
                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bx bx-bar-chart-alt-2 me-2"></i>Statistics</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-secondary">Views:</span>
                            <strong class="text-dark">{{ number_format($blog->viewCount) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-secondary">Reading Time:</span>
                            <span class="text-dark">{{ $blog->readingTime }} min</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-secondary">Created:</span>
                            <span class="text-dark">{{ $blog->created_at->format('M j, Y') }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-secondary">Updated:</span>
                            <span class="text-dark">{{ $blog->updated_at->format('M j, Y') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bx bx-save me-1"></i> Update Blog Post
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="previewBlog()">
                        <i class="bx bx-show me-1"></i> Preview
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection

@section('script')
<!-- GrapesJS -->
<script src="https://unpkg.com/grapesjs"></script>
<script src="https://unpkg.com/grapesjs-preset-webpage"></script>
<script src="https://unpkg.com/grapesjs-blocks-basic"></script>

<script>
let editor;
let currentMode = '{{ $blog->useBuilder ? "builder" : "code" }}';
const existingContent = @json($blog->blogContent);
const existingBuilderContent = @json($blog->builderContent);

$(document).ready(function() {
    initGrapesJS();
    initCharCounters();
    initImagePreviews();
    initPanelSwitcher();

    // Trigger initial counter updates
    $('#blogTitle, #blogExcerpt, #metaTitle, #metaDescription').trigger('input');
});

function initPanelSwitcher() {
    $('.panel-btn').on('click', function() {
        const panel = $(this).data('panel');

        // Update button state
        $('.panel-btn').removeClass('active');
        $(this).addClass('active');

        // Show corresponding panel
        $('.panel-content').removeClass('active');
        $('#' + panel + '-container').addClass('active');
    });
}

function initGrapesJS() {
    editor = grapesjs.init({
        container: '#gjs',
        height: '600px',
        width: 'auto',
        storageManager: false,
        plugins: ['gjs-blocks-basic', 'gjs-preset-webpage'],
        pluginsOpts: {
            'gjs-blocks-basic': {},
            'gjs-preset-webpage': {
                blocksBasicOpts: {
                    flexGrid: true
                }
            }
        },
        canvas: {
            styles: [
                'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'
            ]
        },
        deviceManager: {
            devices: [
                { name: 'Desktop', width: '' },
                { name: 'Tablet', width: '768px', widthMedia: '992px' },
                { name: 'Mobile', width: '320px', widthMedia: '480px' },
            ]
        },
        blockManager: {
            appendTo: '#blocks-container'
        },
        layerManager: {
            appendTo: '#layers-container'
        },
        styleManager: {
            appendTo: '#styles-container',
            sectors: [{
                name: 'General',
                open: false,
                buildProps: ['float', 'display', 'position', 'top', 'right', 'left', 'bottom']
            }, {
                name: 'Dimension',
                open: false,
                buildProps: ['width', 'height', 'max-width', 'min-height', 'margin', 'padding']
            }, {
                name: 'Typography',
                open: false,
                buildProps: ['font-family', 'font-size', 'font-weight', 'letter-spacing', 'color', 'line-height', 'text-align', 'text-shadow']
            }, {
                name: 'Decorations',
                open: false,
                buildProps: ['background-color', 'border-radius', 'border', 'box-shadow', 'background']
            }]
        },
        traitManager: {
            appendTo: '#traits-container'
        }
    });

    // Add custom blocks
    const blockManager = editor.BlockManager;

    blockManager.add('heading', {
        label: '<i class="bx bx-heading"></i><div>Heading</div>',
        category: 'Basic',
        content: '<h2 class="mb-3">Your Heading Here</h2>',
    });

    blockManager.add('paragraph', {
        label: '<i class="bx bx-paragraph"></i><div>Paragraph</div>',
        category: 'Basic',
        content: '<p class="mb-3">Start writing your content here.</p>',
    });

    blockManager.add('image', {
        label: '<i class="bx bx-image"></i><div>Image</div>',
        category: 'Basic',
        content: { type: 'image' },
        activate: true,
    });

    blockManager.add('two-columns', {
        label: '<i class="bx bx-columns"></i><div>2 Columns</div>',
        category: 'Layout',
        content: `<div class="row mb-4">
            <div class="col-md-6"><p>Left column</p></div>
            <div class="col-md-6"><p>Right column</p></div>
        </div>`,
    });

    blockManager.add('quote', {
        label: '<i class="bx bxs-quote-alt-left"></i><div>Quote</div>',
        category: 'Content',
        content: `<blockquote class="blockquote border-start border-4 border-success ps-4 py-2 mb-4">
            <p class="mb-2">"Your quote here"</p>
            <footer class="blockquote-footer">Author</footer>
        </blockquote>`,
    });

    blockManager.add('callout', {
        label: '<i class="bx bx-info-circle"></i><div>Callout</div>',
        category: 'Content',
        content: `<div class="alert alert-info border-0 mb-4">
            <h5><i class="bx bx-bulb me-2"></i>Pro Tip</h5>
            <p class="mb-0">Add your tip here.</p>
        </div>`,
    });

    // Load existing content
    if (existingBuilderContent && existingBuilderContent.html) {
        editor.setComponents(existingBuilderContent.html);
        if (existingBuilderContent.css) {
            editor.setStyle(existingBuilderContent.css);
        }
    } else if (existingContent) {
        editor.setComponents(existingContent);
    }

    editor.on('change:changesCount', function() {
        updateSeoAnalysis();
    });
}

function switchEditorMode(mode) {
    currentMode = mode;

    if (mode === 'builder') {
        $('#builderEditor').show();
        $('#codeEditor').hide();
        $('#builderModeBtn').addClass('active');
        $('#codeModeBtn').removeClass('active');
        $('#useBuilder').val('1');

        const html = $('#htmlCodeEditor').val();
        if (html.trim()) {
            editor.setComponents(html);
        }
    } else {
        $('#builderEditor').hide();
        $('#codeEditor').show();
        $('#builderModeBtn').removeClass('active');
        $('#codeModeBtn').addClass('active');
        $('#useBuilder').val('0');

        const html = editor.getHtml();
        $('#htmlCodeEditor').val(html);
    }
}

function initCharCounters() {
    $('#blogTitle').on('input', function() {
        const length = $(this).val().length;
        const counter = $('#titleCounter');
        counter.text(length + '/60');
        counter.removeClass('warning danger');
        if (length > 60) counter.addClass('danger');
        else if (length > 50) counter.addClass('warning');
        updateGooglePreview();
        updateSeoAnalysis();
    });

    $('#blogExcerpt').on('input', function() {
        const length = $(this).val().length;
        const counter = $('#excerptCounter');
        counter.text(length + '/500');
        counter.removeClass('warning danger');
        if (length > 450) counter.addClass('danger');
        else if (length > 400) counter.addClass('warning');
        updateGooglePreview();
        updateSeoAnalysis();
    });

    $('#metaTitle').on('input', function() {
        const length = $(this).val().length;
        const counter = $('#metaTitleCounter');
        counter.text(length + '/60');
        counter.removeClass('warning danger');
        if (length > 60) counter.addClass('danger');
        else if (length > 50) counter.addClass('warning');
        updateGooglePreview();
        updateSeoAnalysis();
    });

    $('#metaDescription').on('input', function() {
        const length = $(this).val().length;
        const counter = $('#metaDescCounter');
        counter.text(length + '/160');
        counter.removeClass('warning danger');
        if (length > 160) counter.addClass('danger');
        else if (length > 150) counter.addClass('warning');
        updateGooglePreview();
        updateSeoAnalysis();
    });

    $('#focusKeyword').on('input', function() {
        updateSeoAnalysis();
    });
}

function initImagePreviews() {
    $('#blogFeaturedImage').on('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#imagePreview').attr('src', e.target.result).show();
                $('#imagePlaceholder').hide();
                $('.remove-image-btn').show();
                $('#removeImage').val('0');
            };
            reader.readAsDataURL(this.files[0]);
        }
    });

    $('#ogImage').on('change', function() {
        previewImage(this, '#ogImagePreview', '#ogImagePlaceholder');
    });

    $('#twitterImage').on('change', function() {
        previewImage(this, '#twitterImagePreview', '#twitterImagePlaceholder');
    });
}

function previewImage(input, previewSelector, placeholderSelector) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            $(previewSelector).attr('src', e.target.result).show();
            $(placeholderSelector).hide();
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function removeCurrentImage() {
    $('#imagePreview').hide().attr('src', '');
    $('#imagePlaceholder').show();
    $('.remove-image-btn').hide();
    $('#blogFeaturedImage').val('');
    $('#removeImage').val('1');
}

function updateGooglePreview() {
    const title = $('#metaTitle').val() || $('#blogTitle').val() || 'Your Blog Title';
    const description = $('#metaDescription').val() || $('#blogExcerpt').val() || 'Your blog description...';
    const slug = '{{ $blog->blogSlug }}';

    $('#googlePreviewTitle').text(title);
    $('#googlePreviewUrl').text('https://anisenso.com/blog/' + slug);
    $('#googlePreviewDesc').text(description.substring(0, 160) + (description.length > 160 ? '...' : ''));
}

function updateSeoAnalysis() {
    let score = 0;
    const items = [];

    const title = $('#blogTitle').val();
    const excerpt = $('#blogExcerpt').val();
    const metaDesc = $('#metaDescription').val() || excerpt;
    const focusKeyword = $('#focusKeyword').val();
    const content = currentMode === 'builder' ? editor.getHtml() : $('#htmlCodeEditor').val();
    const contentText = $('<div>').html(content).text();

    if (title.length >= 50 && title.length <= 60) {
        score += 15;
        items.push({ status: 'good', message: 'Title length is optimal' });
    } else if (title.length >= 30 && title.length <= 70) {
        score += 10;
        items.push({ status: 'ok', message: 'Title length is acceptable' });
    } else if (title.length > 0) {
        items.push({ status: 'bad', message: 'Title should be 50-60 characters' });
    }

    if (metaDesc.length >= 150 && metaDesc.length <= 160) {
        score += 15;
        items.push({ status: 'good', message: 'Meta description is optimal' });
    } else if (metaDesc.length >= 120 && metaDesc.length <= 180) {
        score += 10;
        items.push({ status: 'ok', message: 'Meta description is acceptable' });
    } else if (metaDesc.length > 0) {
        items.push({ status: 'bad', message: 'Meta description should be 150-160 chars' });
    }

    if (focusKeyword) {
        score += 10;
        const keyword = focusKeyword.toLowerCase();

        if (title.toLowerCase().includes(keyword)) {
            score += 10;
            items.push({ status: 'good', message: 'Focus keyword in title' });
        } else {
            items.push({ status: 'bad', message: 'Add focus keyword to title' });
        }

        const keywordCount = (contentText.toLowerCase().match(new RegExp(keyword, 'g')) || []).length;
        if (keywordCount >= 3) {
            score += 10;
            items.push({ status: 'good', message: 'Focus keyword used ' + keywordCount + ' times' });
        } else if (keywordCount >= 1) {
            score += 5;
            items.push({ status: 'ok', message: 'Use focus keyword more often' });
        } else {
            items.push({ status: 'bad', message: 'Focus keyword not in content' });
        }
    } else {
        items.push({ status: 'bad', message: 'Set a focus keyword' });
    }

    const wordCount = contentText.split(/\s+/).filter(w => w.length > 0).length;
    if (wordCount >= 1000) {
        score += 15;
        items.push({ status: 'good', message: 'Content has ' + wordCount + ' words' });
    } else if (wordCount >= 500) {
        score += 10;
        items.push({ status: 'ok', message: wordCount + ' words. Aim for 1000+' });
    } else if (wordCount > 0) {
        score += 5;
        items.push({ status: 'bad', message: 'Only ' + wordCount + ' words' });
    }

    const headingCount = (content.match(/<h[2-6][^>]*>/gi) || []).length;
    if (headingCount >= 2) {
        score += 10;
        items.push({ status: 'good', message: headingCount + ' subheadings found' });
    } else if (headingCount >= 1) {
        score += 5;
        items.push({ status: 'ok', message: 'Add more subheadings' });
    } else {
        items.push({ status: 'bad', message: 'Add H2/H3 headings' });
    }

    const imageCount = (content.match(/<img[^>]*>/gi) || []).length;
    if (imageCount >= 1) {
        score += 10;
        items.push({ status: 'good', message: imageCount + ' image(s) in content' });
    } else {
        items.push({ status: 'bad', message: 'Add images to content' });
    }

    score = Math.min(100, score);
    const circle = $('#seoScoreCircle');
    circle.text(score);
    circle.removeClass('seo-score-good seo-score-ok seo-score-bad');

    if (score >= 80) {
        circle.addClass('seo-score-good');
    } else if (score >= 50) {
        circle.addClass('seo-score-ok');
    } else {
        circle.addClass('seo-score-bad');
    }

    let html = '';
    items.forEach(item => {
        const icon = item.status === 'good' ? 'bx-check-circle' : (item.status === 'ok' ? 'bx-error-circle' : 'bx-x-circle');
        html += `<div class="seo-item ${item.status}">
            <i class="bx ${icon}"></i>
            <span class="text-dark">${item.message}</span>
        </div>`;
    });
    $('#seoAnalysisItems').html(html);
}

function previewBlog() {
    const html = currentMode === 'builder' ? editor.getHtml() : $('#htmlCodeEditor').val();
    const css = currentMode === 'builder' ? editor.getCss() : '';
    const title = $('#blogTitle').val() || 'Preview';

    const previewHtml = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>${title} - Preview</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>${css}</style>
        </head>
        <body class="bg-light">
            <div class="container py-5">
                <div class="bg-white rounded shadow-sm p-4">
                    <h1 class="mb-4">${title}</h1>
                    ${html}
                </div>
            </div>
        </body>
        </html>
    `;

    const previewWindow = window.open('', '_blank');
    previewWindow.document.write(previewHtml);
    previewWindow.document.close();
}

$('#blogForm').on('submit', function(e) {
    if (currentMode === 'builder') {
        const html = editor.getHtml();
        const css = editor.getCss();
        $('#builderContent').val(JSON.stringify({
            html: html,
            css: css,
            components: editor.getComponents()
        }));
        $('#blogContentHidden').val(html);
    } else {
        $('#blogContentHidden').val($('#htmlCodeEditor').val());
        $('#builderContent').val('');
    }
});
</script>
@endsection
