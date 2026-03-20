@extends('layouts.master')

@section('title') Create Blog Post @endsection

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

#gjs {
    border: none;
    min-height: 600px;
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
@slot('title') Create Blog Post @endslot
@endcomponent

<form action="{{ route('anisenso-blogs.store') }}" method="POST" enctype="multipart/form-data" id="blogForm">
    @csrf
    <input type="hidden" name="builderContent" id="builderContent">
    <input type="hidden" name="useBuilder" id="useBuilder" value="1">
    <input type="hidden" name="blogContent" id="blogContentHidden">

    <div class="row">
        <!-- Main Content Area -->
        <div class="col-lg-9">
            <div class="card">
                <div class="card-body">
                    <!-- Header with Back Button -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="card-title mb-1">Create New Blog Post</h4>
                            <p class="text-secondary mb-0">Build your blog content with drag & drop</p>
                        </div>
                        <a href="{{ route('anisenso-blogs') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-arrow-back me-1"></i> Back
                        </a>
                    </div>

                    <!-- Title Field -->
                    <div class="mb-4">
                        <label for="blogTitle" class="form-label">Blog Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-lg @error('blogTitle') is-invalid @enderror"
                               id="blogTitle" name="blogTitle" value="{{ old('blogTitle') }}"
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
                                  maxlength="500" required>{{ old('blogExcerpt') }}</textarea>
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
                            <button type="button" class="btn active" id="builderModeBtn" onclick="switchEditorMode('builder')">
                                <i class="bx bx-layout me-1"></i> Visual Builder
                            </button>
                            <button type="button" class="btn" id="codeModeBtn" onclick="switchEditorMode('code')">
                                <i class="bx bx-code-alt me-1"></i> HTML Code
                            </button>
                        </div>
                    </div>

                    <!-- Visual Builder -->
                    <div id="builderEditor" class="builder-container">
                        <div id="gjs"></div>
                    </div>

                    <!-- Code Editor (Hidden by default) -->
                    <div id="codeEditor" style="display: none;">
                        <textarea class="form-control" id="htmlCodeEditor" rows="20"
                                  placeholder="Write or paste your HTML content here..."></textarea>
                    </div>
                </div>
            </div>

            <!-- SEO Settings Card -->
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bx bx-search-alt me-2"></i>SEO Settings</h5>
                </div>
                <div class="card-body">
                    <!-- Tabs for SEO sections -->
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
                                               value="{{ old('focusKeyword') }}" placeholder="e.g., rice farming tips">
                                        <small class="text-secondary">The main keyword you want this post to rank for</small>
                                    </div>

                                    <div class="mb-3">
                                        <label for="metaTitle" class="form-label">Meta Title</label>
                                        <input type="text" class="form-control" id="metaTitle" name="metaTitle"
                                               value="{{ old('metaTitle') }}" placeholder="SEO title (leave empty to use blog title)">
                                        <div class="d-flex justify-content-between mt-1">
                                            <small class="text-secondary">Recommended: 50-60 characters</small>
                                            <span class="char-counter" id="metaTitleCounter">0/60</span>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="metaDescription" class="form-label">Meta Description</label>
                                        <textarea class="form-control" id="metaDescription" name="metaDescription" rows="3"
                                                  placeholder="SEO description (leave empty to use excerpt)">{{ old('metaDescription') }}</textarea>
                                        <div class="d-flex justify-content-between mt-1">
                                            <small class="text-secondary">Recommended: 150-160 characters</small>
                                            <span class="char-counter" id="metaDescCounter">0/160</span>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="metaKeywords" class="form-label">Meta Keywords</label>
                                        <input type="text" class="form-control" id="metaKeywords" name="metaKeywords"
                                               value="{{ old('metaKeywords') }}" placeholder="farming, rice, agriculture, tips (comma separated)">
                                    </div>

                                    <!-- Google Preview -->
                                    <div class="preview-panel mt-4">
                                        <label class="form-label text-dark mb-2"><i class="bx bxl-google me-1"></i>Google Search Preview</label>
                                        <div class="preview-title" id="googlePreviewTitle">Your Blog Title</div>
                                        <div class="preview-url" id="googlePreviewUrl">https://anisenso.com/blog/your-blog-slug</div>
                                        <div class="preview-description" id="googlePreviewDesc">Your blog excerpt or meta description will appear here...</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <!-- SEO Score -->
                                    <div class="card border">
                                        <div class="card-body text-center">
                                            <label class="form-label text-dark">SEO Score</label>
                                            <div class="seo-score-circle seo-score-bad mb-3" id="seoScoreCircle">0</div>
                                            <div id="seoAnalysisItems">
                                                <div class="seo-item bad">
                                                    <i class="bx bx-x-circle"></i>
                                                    <span class="text-dark">Start writing to see SEO analysis</span>
                                                </div>
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
                                               value="{{ old('ogTitle') }}" placeholder="Leave empty to use blog title">
                                    </div>
                                    <div class="mb-3">
                                        <label for="ogDescription" class="form-label">OG Description</label>
                                        <textarea class="form-control" id="ogDescription" name="ogDescription" rows="2"
                                                  placeholder="Leave empty to use excerpt">{{ old('ogDescription') }}</textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">OG Image</label>
                                        <div class="image-preview-container" style="height: 120px;" onclick="document.getElementById('ogImage').click()">
                                            <div class="placeholder" id="ogImagePlaceholder">
                                                <i class="bx bx-image"></i>
                                                <p class="mb-0 small">1200x630px recommended</p>
                                            </div>
                                            <img id="ogImagePreview" src="" alt="Preview" style="display: none;">
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
                                               value="{{ old('twitterTitle') }}" placeholder="Leave empty to use blog title">
                                    </div>
                                    <div class="mb-3">
                                        <label for="twitterDescription" class="form-label">Twitter Description</label>
                                        <textarea class="form-control" id="twitterDescription" name="twitterDescription" rows="2"
                                                  placeholder="Leave empty to use excerpt">{{ old('twitterDescription') }}</textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Twitter Image</label>
                                        <div class="image-preview-container" style="height: 120px;" onclick="document.getElementById('twitterImage').click()">
                                            <div class="placeholder" id="twitterImagePlaceholder">
                                                <i class="bx bx-image"></i>
                                                <p class="mb-0 small">1200x600px recommended</p>
                                            </div>
                                            <img id="twitterImagePreview" src="" alt="Preview" style="display: none;">
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
                                               value="{{ old('canonicalUrl') }}" placeholder="https://...">
                                        <small class="text-secondary">Set if this content exists elsewhere</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="schemaType" class="form-label">Schema Type</label>
                                        <select class="form-select" id="schemaType" name="schemaType">
                                            @foreach($schemaTypes as $key => $label)
                                                <option value="{{ $key }}" {{ old('schemaType', 'BlogPosting') == $key ? 'selected' : '' }}>
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
                                    <option value="{{ $key }}" {{ old('blogStatus', 'draft') == $key ? 'selected' : '' }}>
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
                                    <option value="{{ $category }}" {{ old('blogCategory') == $category ? 'selected' : '' }}>
                                        {{ $category }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="authorName" class="form-label">Author Name</label>
                            <input type="text" class="form-control" id="authorName" name="authorName"
                                   value="{{ old('authorName') }}" placeholder="Optional">
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="isFeatured" name="isFeatured" value="1">
                            <label class="form-check-label" for="isFeatured">
                                <i class="bx bxs-star text-warning"></i> Featured Post
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Featured Image -->
                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bx bx-image me-2"></i>Featured Image</h6>
                    </div>
                    <div class="card-body">
                        <div class="image-preview-container" onclick="document.getElementById('blogFeaturedImage').click()">
                            <div class="placeholder" id="imagePlaceholder">
                                <i class="bx bx-cloud-upload"></i>
                                <p class="mb-0">Click to upload</p>
                                <small>Max 5MB</small>
                            </div>
                            <img id="imagePreview" src="" alt="Preview" style="display: none;">
                        </div>
                        <input type="file" class="d-none" id="blogFeaturedImage" name="blogFeaturedImage" accept="image/*">
                    </div>
                </div>

                <!-- Actions -->
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bx bx-save me-1"></i> Save Blog Post
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
let currentMode = 'builder';

$(document).ready(function() {
    initGrapesJS();
    initCharCounters();
    initImagePreviews();
    updateSeoAnalysis();
});

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
                modalImportTitle: 'Import Template',
                modalImportLabel: '<div style="margin-bottom: 10px;">Paste your HTML/CSS here</div>',
                modalImportContent: function(editor) {
                    return editor.getHtml() + '<style>' + editor.getCss() + '</style>';
                },
            }
        },
        canvas: {
            styles: [
                'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'
            ]
        },
        blockManager: {
            appendTo: '#blocks',
        },
        panels: {
            defaults: []
        },
        deviceManager: {
            devices: [
                { name: 'Desktop', width: '' },
                { name: 'Tablet', width: '768px', widthMedia: '992px' },
                { name: 'Mobile', width: '320px', widthMedia: '480px' },
            ]
        }
    });

    // Add custom blocks for blog content
    const blockManager = editor.BlockManager;

    // Clear default blocks and add custom ones
    blockManager.add('heading', {
        label: '<i class="bx bx-heading"></i><div>Heading</div>',
        category: 'Basic',
        content: '<h2 class="mb-3">Your Heading Here</h2>',
    });

    blockManager.add('paragraph', {
        label: '<i class="bx bx-paragraph"></i><div>Paragraph</div>',
        category: 'Basic',
        content: '<p class="mb-3">Start writing your content here. You can add more paragraphs, images, and other elements to create engaging blog posts.</p>',
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
            <div class="col-md-6"><p>Left column content</p></div>
            <div class="col-md-6"><p>Right column content</p></div>
        </div>`,
    });

    blockManager.add('image-text', {
        label: '<i class="bx bx-dock-left"></i><div>Image + Text</div>',
        category: 'Layout',
        content: `<div class="row mb-4 align-items-center">
            <div class="col-md-5"><img src="https://via.placeholder.com/400x300" class="img-fluid rounded" alt="Image"></div>
            <div class="col-md-7"><h3>Heading</h3><p>Your text content here describing the image or related topic.</p></div>
        </div>`,
    });

    blockManager.add('quote', {
        label: '<i class="bx bxs-quote-alt-left"></i><div>Quote</div>',
        category: 'Content',
        content: `<blockquote class="blockquote border-start border-4 border-success ps-4 py-2 mb-4">
            <p class="mb-2">"Your inspiring quote or important statement goes here."</p>
            <footer class="blockquote-footer">Author Name</footer>
        </blockquote>`,
    });

    blockManager.add('callout', {
        label: '<i class="bx bx-info-circle"></i><div>Callout Box</div>',
        category: 'Content',
        content: `<div class="alert alert-info border-0 mb-4">
            <h5><i class="bx bx-bulb me-2"></i>Pro Tip</h5>
            <p class="mb-0">Add your helpful tip or important information here.</p>
        </div>`,
    });

    blockManager.add('list', {
        label: '<i class="bx bx-list-ul"></i><div>List</div>',
        category: 'Content',
        content: `<ul class="mb-4">
            <li>First item in your list</li>
            <li>Second item with more details</li>
            <li>Third item to complete the list</li>
        </ul>`,
    });

    blockManager.add('numbered-list', {
        label: '<i class="bx bx-list-ol"></i><div>Numbered List</div>',
        category: 'Content',
        content: `<ol class="mb-4">
            <li>First step in your process</li>
            <li>Second step with instructions</li>
            <li>Third step to complete</li>
        </ol>`,
    });

    blockManager.add('divider', {
        label: '<i class="bx bx-minus"></i><div>Divider</div>',
        category: 'Basic',
        content: '<hr class="my-4">',
    });

    blockManager.add('button', {
        label: '<i class="bx bx-pointer"></i><div>Button</div>',
        category: 'Basic',
        content: '<a href="#" class="btn btn-success btn-lg mb-3">Click Here</a>',
    });

    blockManager.add('video', {
        label: '<i class="bx bx-play-circle"></i><div>Video</div>',
        category: 'Media',
        content: `<div class="ratio ratio-16x9 mb-4">
            <iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" allowfullscreen></iframe>
        </div>`,
    });

    blockManager.add('card', {
        label: '<i class="bx bx-card"></i><div>Card</div>',
        category: 'Content',
        content: `<div class="card mb-4">
            <img src="https://via.placeholder.com/400x200" class="card-img-top" alt="Card image">
            <div class="card-body">
                <h5 class="card-title">Card Title</h5>
                <p class="card-text">Some quick example text for the card content.</p>
                <a href="#" class="btn btn-primary">Learn More</a>
            </div>
        </div>`,
    });

    // Add panels
    editor.Panels.addPanel({
        id: 'panel-top',
        el: '.panel-top',
    });

    editor.Panels.addButton('options', [{
        id: 'save',
        className: 'fa fa-floppy-o',
        command: 'save-db',
        attributes: { title: 'Save' }
    }]);

    // Set default content
    editor.setComponents(`
        <div class="container py-4">
            <p class="lead mb-4">Start by adding content blocks from the right panel, or drag and drop elements to build your blog post.</p>
        </div>
    `);

    // Listen for changes
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

        // Sync HTML from code editor to builder
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

        // Sync HTML from builder to code editor
        const html = editor.getHtml();
        $('#htmlCodeEditor').val(html);
    }
}

function initCharCounters() {
    // Title counter
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

    // Excerpt counter
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

    // Meta title counter
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

    // Meta description counter
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

    // Focus keyword
    $('#focusKeyword').on('input', function() {
        updateSeoAnalysis();
    });
}

function initImagePreviews() {
    // Featured image
    $('#blogFeaturedImage').on('change', function() {
        previewImage(this, '#imagePreview', '#imagePlaceholder');
    });

    // OG image
    $('#ogImage').on('change', function() {
        previewImage(this, '#ogImagePreview', '#ogImagePlaceholder');
    });

    // Twitter image
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

function updateGooglePreview() {
    const title = $('#metaTitle').val() || $('#blogTitle').val() || 'Your Blog Title';
    const description = $('#metaDescription').val() || $('#blogExcerpt').val() || 'Your blog description...';
    const slug = $('#blogTitle').val().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');

    $('#googlePreviewTitle').text(title);
    $('#googlePreviewUrl').text('https://anisenso.com/blog/' + (slug || 'your-blog-slug'));
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

    // Title length check
    if (title.length >= 50 && title.length <= 60) {
        score += 15;
        items.push({ status: 'good', message: 'Title length is optimal (' + title.length + ' chars)' });
    } else if (title.length >= 30 && title.length <= 70) {
        score += 10;
        items.push({ status: 'ok', message: 'Title length is acceptable (' + title.length + ' chars)' });
    } else if (title.length > 0) {
        items.push({ status: 'bad', message: 'Title should be 50-60 characters' });
    }

    // Meta description check
    if (metaDesc.length >= 150 && metaDesc.length <= 160) {
        score += 15;
        items.push({ status: 'good', message: 'Meta description is optimal' });
    } else if (metaDesc.length >= 120 && metaDesc.length <= 180) {
        score += 10;
        items.push({ status: 'ok', message: 'Meta description length is acceptable' });
    } else if (metaDesc.length > 0) {
        items.push({ status: 'bad', message: 'Meta description should be 150-160 chars' });
    }

    // Focus keyword check
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

    // Content length check
    const wordCount = contentText.split(/\s+/).filter(w => w.length > 0).length;
    if (wordCount >= 1000) {
        score += 15;
        items.push({ status: 'good', message: 'Content has ' + wordCount + ' words' });
    } else if (wordCount >= 500) {
        score += 10;
        items.push({ status: 'ok', message: wordCount + ' words. Aim for 1000+' });
    } else if (wordCount > 0) {
        score += 5;
        items.push({ status: 'bad', message: 'Only ' + wordCount + ' words. Add more content' });
    }

    // Check for headings
    const headingCount = (content.match(/<h[2-6][^>]*>/gi) || []).length;
    if (headingCount >= 2) {
        score += 10;
        items.push({ status: 'good', message: headingCount + ' subheadings found' });
    } else if (headingCount >= 1) {
        score += 5;
        items.push({ status: 'ok', message: 'Add more subheadings' });
    } else {
        items.push({ status: 'bad', message: 'Add H2/H3 headings to structure' });
    }

    // Check for images
    const imageCount = (content.match(/<img[^>]*>/gi) || []).length;
    if (imageCount >= 1) {
        score += 10;
        items.push({ status: 'good', message: imageCount + ' image(s) in content' });
    } else {
        items.push({ status: 'bad', message: 'Add images to your content' });
    }

    // Check for links
    const linkCount = (content.match(/<a[^>]*href[^>]*>/gi) || []).length;
    if (linkCount >= 2) {
        score += 5;
        items.push({ status: 'good', message: linkCount + ' links found' });
    } else if (linkCount >= 1) {
        score += 3;
        items.push({ status: 'ok', message: 'Add more internal/external links' });
    }

    // Update UI
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

    // Update analysis items
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
    // Open preview in new window
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

// Before form submit, sync content
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
