@extends('layouts.master')

@section('title') Edit SEO Page @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') TouristGuidePh @endslot
@slot('li_2') Keywords @endslot
@slot('li_2_link') {{ route('resort-guru-keywords.index') }} @endslot
@slot('li_3') Pages @endslot
@slot('li_3_link') {{ route('resort-guru-keywords-pages.index', ['id' => $keyword->id ?? 0]) }} @endslot
@slot('title') {{ $page->title ?: 'Edit Page' }} @endslot
@endcomponent

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">@foreach($errors->all() as $e)<p class="mb-0">{{ $e }}</p>@endforeach</div>
@endif

<div class="row">
    <div class="col-lg-8">
        {{-- Linked keyword card --}}
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div>
                        <div class="text-muted small mb-1">
                            Page under keyword
                            @if($page->is_primary)
                                <span class="badge bg-warning text-dark ms-1">&#x1F31F; Primary</span>
                            @endif
                        </div>
                        <h5 class="mb-1 text-capitalize">{{ $keyword->phrase ?? 'n/a' }}</h5>
                        <small class="text-muted">
                            Keyword slug: <code>{{ $keyword->slug ?? '' }}</code> ·
                            Volume: <strong>{{ number_format($keyword->search_volume_monthly ?? 0) }}/mo</strong> ·
                            KD: <strong>{{ $keyword->keyword_difficulty ?? 0 }}</strong>
                            @if(isset($siblingPages) && $siblingPages->count() > 0)
                                · <strong>{{ $siblingPages->count() }}</strong> other page(s) for this keyword
                            @endif
                        </small>
                    </div>
                    <div>
                        <a href="{{ route('resort-guru-keywords-pages.index', ['id' => $keyword->id ?? 0]) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bx bx-arrow-back"></i> All pages
                        </a>
                        @if($page->is_published)
                            <a href="{{ route('resort-guru-pages.live-edit', ['id' => $page->id]) }}"
                               class="btn btn-sm btn-primary"
                               title="Open the rendered page with inline edit, drag-reorder, and add buttons on every block">
                                <i class="bx bx-edit-alt"></i> Live Editor
                            </a>
                            <a href="{{ \App\Support\RgFrontend::urlFor($page->slug) }}" target="_blank" class="btn btn-sm btn-outline-success">
                                <i class="bx bx-link-external"></i> View live
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Page metadata form --}}
        <form action="{{ route('resort-guru-pages.update', ['id' => $page->id]) }}" method="POST" id="rg-page-meta-form">
            @csrf
            @method('PUT')
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0">Page Settings</h5>
                    <div class="d-flex gap-3 align-items-center">
                        <div class="form-check form-switch mb-0">
                            <input type="checkbox" class="form-check-input" name="is_published" id="isPublished" value="1" {{ $page->is_published ? 'checked' : '' }}>
                            <label class="form-check-label" for="isPublished">Published</label>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input type="checkbox" class="form-check-input" name="is_primary" id="isPrimary" value="1" {{ $page->is_primary ? 'checked' : '' }}>
                            <label class="form-check-label" for="isPrimary">Primary (canonical)</label>
                        </div>
                        {{-- Duplicate save button in the header: the original
                             save lives at the bottom of the form which the
                             user reported was easy to miss on long pages. --}}
                        <button type="submit" form="rg-page-meta-form" class="btn btn-primary btn-sm">
                            <i class="bx bx-save me-1"></i>Save
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Page Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" value="{{ old('title', $page->title) }}" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">URL Slug <small class="text-muted">(unique across all pages)</small></label>
                            <div class="input-group">
                                <span class="input-group-text">{{ \App\Support\RgFrontend::url() }}/</span>
                                <input type="text" name="slug" class="form-control" value="{{ old('slug', $page->slug) }}" pattern="[a-z0-9-]+" required>
                            </div>
                            <small class="text-muted">Lowercase letters, numbers, hyphens. Changing the slug breaks any existing links.</small>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">H1 Heading</label>
                            <input type="text" name="h1" class="form-control" value="{{ old('h1', $page->h1) }}">
                        </div>
                        {{-- The Italic Intro (subtitle), TL;DR, and WWWW Summary
                             fields used to live here. They moved to content
                             blocks (subtitle_intro, tldr_card, wwww_card) so
                             admins reorder / remove / add them through the
                             builder below and through the Live Editor like
                             any other block. The source columns are kept on
                             rg_seo_pages as a rollback path but are no longer
                             written from this form. --}}
                        <div class="col-md-12 mb-3">
                            <div class="alert alert-info py-2 px-3 mb-0 small">
                                <i class="bx bx-info-circle me-1"></i>
                                <strong>Italic Intro / TL;DR / WWWW</strong> are now content blocks. Edit them in the <strong>Page Content Builder</strong> below, or hover them in the Live Editor.
                            </div>
                        </div>

                        <hr>
                        <h6 class="mb-3"><i class="bx bx-user-pin me-1"></i>Byline</h6>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Author</label>
                            @php
                                $authors = \Illuminate\Support\Facades\Schema::hasTable('rg_authors')
                                    ? \Illuminate\Support\Facades\DB::table('rg_authors')->where('status', 'active')->orderBy('sort_order')->orderBy('name')->get()
                                    : collect();
                            @endphp
                            <select name="author_id" class="form-select">
                                <option value="">— No byline —</option>
                                @foreach($authors as $a)
                                    <option value="{{ $a->id }}" {{ (int) old('author_id', $page->author_id ?? 0) === (int) $a->id ? 'selected' : '' }}>{{ $a->name }}@if($a->role) — {{ $a->role }}@endif</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Shown on the public page as the byline. Manage the author list at <a href="/resort-guru-authors">Authors</a>.</small>
                        </div>

                        <hr>
                        <h6 class="mb-3"><i class="bx bx-search me-1"></i>SEO Meta</h6>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Meta Title <span class="text-muted small">(50-60 chars)</span></label>
                            <input type="text" name="meta_title" class="form-control" value="{{ old('meta_title', $page->meta_title) }}" maxlength="70" id="metaTitle">
                            <small class="text-muted" id="metaTitleCount">0 chars</small>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Meta Description <span class="text-muted small">(140-160 chars)</span></label>
                            <textarea name="meta_description" class="form-control" rows="2" maxlength="200" id="metaDesc">{{ old('meta_description', $page->meta_description) }}</textarea>
                            <small class="text-muted" id="metaDescCount">0 chars</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Meta Keywords <small class="text-muted">(optional)</small></label>
                            <input type="text" name="meta_keywords" class="form-control" value="{{ old('meta_keywords', $page->meta_keywords) }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Canonical URL <small class="text-muted">(optional)</small></label>
                            <input type="url" name="canonical_url" class="form-control" value="{{ old('canonical_url', $page->canonical_url) }}" placeholder="https://...">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Robots Meta</label>
                            <select name="robots" class="form-select">
                                <option value="" {{ ($page->robots ?? '') === '' ? 'selected' : '' }}>Default (index, follow)</option>
                                <option value="index,follow" {{ ($page->robots ?? '') === 'index,follow' ? 'selected' : '' }}>index, follow</option>
                                <option value="noindex,follow" {{ ($page->robots ?? '') === 'noindex,follow' ? 'selected' : '' }}>noindex, follow</option>
                                <option value="index,nofollow" {{ ($page->robots ?? '') === 'index,nofollow' ? 'selected' : '' }}>index, nofollow</option>
                                <option value="noindex,nofollow" {{ ($page->robots ?? '') === 'noindex,nofollow' ? 'selected' : '' }}>noindex, nofollow</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">OG Image Path</label>
                            <div class="input-group">
                                <input type="text" name="og_image_path" id="ogImagePath" class="form-control" value="{{ old('og_image_path', $page->og_image_path) }}" placeholder="rg-blocks/2026/05/...">
                                <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('ogImageUpload').click()"><i class="bx bx-upload"></i></button>
                            </div>
                            <input type="file" id="ogImageUpload" accept="image/*" style="display:none" onchange="uploadOgImage(this)">
                            <small class="text-muted">Shown when this page is shared on social. 1200×630 recommended.</small>
                        </div>
                        <hr>
                        <h6 class="mb-3"><i class="bx bx-code-curly me-1"></i>Advanced</h6>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Custom Schema JSON-LD <small class="text-muted">(merged with auto-generated)</small></label>
                            <textarea name="schema_json" class="form-control" rows="4" style="font-family:monospace;font-size:12px" placeholder='{"@context":"https://schema.org",...}'>{{ old('schema_json', $page->schema_json) }}</textarea>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Fallback HTML <small class="text-muted">(shown when no paid listings exist)</small></label>
                            <textarea name="fallback_listing_html" class="form-control" rows="3">{{ old('fallback_listing_html', $page->fallback_listing_html) }}</textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-save me-1"></i>Save Page Settings
                    </button>
                </div>
            </div>
        </form>

        {{-- Drag-drop content builder --}}
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0"><i class="bx bx-layer me-1"></i>Page Content Builder</h5>
                <small class="text-muted">Drag blocks to reorder. Click edit to change a block's content. Saves are live — frontend updates immediately.</small>
            </div>
            <div class="card-body">
                @include('resortGuruAdmin.blocks.builder', [
                    'ownerType' => 'seo_page',
                    'ownerId' => $page->id,
                    // The legacy warning pops if there is non-empty seeded
                    // body_html and zero blocks yet; adding the first block
                    // hides this banner because the public render switches
                    // from body_html to the block list.
                    'legacyBodyHtml' => trim((string) ($page->body_html ?? '')) !== '',
                    'hasBlocks' => \App\Models\RgContentBlock::where('owner_type', 'seo_page')
                        ->where('owner_id', $page->id)->exists(),
                ])
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        {{-- SEO Recommendations sidebar --}}
        <div class="card mb-3 sticky-top" style="top: 80px;">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bx bx-bar-chart-alt-2 me-1"></i>SEO Score</h5>
                <button class="btn btn-sm btn-outline-primary" onclick="refreshSeo()"><i class="bx bx-refresh"></i></button>
            </div>
            <div class="card-body p-0">
                <div id="rgSeoPanel" class="p-3 text-center text-muted">
                    <i class="bx bx-loader bx-spin"></i> Analyzing...
                </div>
            </div>
        </div>

        @if(isset($siblingPages) && $siblingPages->count() > 0)
            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0">Other pages for this keyword</h6></div>
                <div class="card-body">
                    <ul class="list-group list-group-flush small">
                        @foreach($siblingPages as $sib)
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <div>
                                    @if($sib->is_primary)<span title="Primary">&#x1F31F;</span> @endif
                                    <a href="{{ route('resort-guru-pages.edit', ['id' => $sib->id]) }}">{{ $sib->title }}</a><br>
                                    <code class="text-muted small">/{{ $sib->slug }}</code>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        {{-- AI Generate (placeholder) --}}
        <div class="card">
            <div class="card-body">
                <h6 class="mb-2"><i class="bx bx-bot me-1"></i>AI Assist</h6>
                <p class="small text-muted mb-2">Generate or expand content using AI.</p>
                <button type="button" class="btn btn-sm btn-outline-secondary w-100" onclick="aiGenerate()" disabled>
                    Coming in Phase 4
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    function updateCount(input, counter, ideal) {
        const len = input.value.length;
        counter.textContent = len + ' chars';
        counter.className = 'small';
        if (len < ideal[0]) counter.classList.add('text-warning');
        else if (len > ideal[1]) counter.classList.add('text-danger');
        else counter.classList.add('text-success');
    }
    const mt = document.getElementById('metaTitle'), mtc = document.getElementById('metaTitleCount');
    const md = document.getElementById('metaDesc'), mdc = document.getElementById('metaDescCount');
    if (mt) { mt.addEventListener('input', () => updateCount(mt, mtc, [50,60])); updateCount(mt, mtc, [50,60]); }
    if (md) { md.addEventListener('input', () => updateCount(md, mdc, [140,160])); updateCount(md, mdc, [140,160]); }

    window.refreshSeo = function() {
        const panel = document.getElementById('rgSeoPanel');
        panel.innerHTML = '<i class="bx bx-loader bx-spin"></i> Analyzing...';
        fetch('/resort-guru-pages-seo-analyze?id={{ $page->id }}', { credentials: 'same-origin' })
            .then(r => r.json())
            .then(d => {
                if (!d.ok) { panel.innerHTML = '<p class="text-muted">Could not analyze.</p>'; return; }
                const scoreColor = d.score >= 80 ? 'success' : d.score >= 60 ? 'warning' : 'danger';
                let html = `<div class="text-center py-3 bg-${scoreColor} bg-opacity-10">
                    <div style="font-size:38px;font-weight:bold;color:var(--bs-${scoreColor})">${d.score}/100</div>
                    <small class="text-muted text-uppercase">${d.label}</small>
                </div><ul class="list-group list-group-flush">`;
                d.checks.forEach(c => {
                    const icon = c.status === 'pass' ? '<i class="bx bx-check text-success"></i>' : c.status === 'warn' ? '<i class="bx bx-error text-warning"></i>' : '<i class="bx bx-x text-danger"></i>';
                    html += `<li class="list-group-item py-2"><div class="d-flex gap-2"><div>${icon}</div><div class="flex-grow-1"><strong class="small">${c.label}</strong><div class="text-muted" style="font-size:11px">${c.message}</div></div></div></li>`;
                });
                html += '</ul>';
                panel.innerHTML = html;
            })
            .catch(() => { panel.innerHTML = '<p class="text-muted p-2">Analysis service unavailable.</p>'; });
    };

    window.aiGenerate = function() {
        if (typeof toastr !== 'undefined') toastr.info('AI generation lands in Phase 4. Coming soon.');
    };

    window.uploadOgImage = function(input) {
        const file = input.files[0];
        if (!file) return;
        const fd = new FormData();
        fd.append('file', file);
        fd.append('_token', '{{ csrf_token() }}');
        fetch('/resort-guru-blocks-upload-media', { method: 'POST', credentials: 'same-origin', body: fd })
            .then(r => r.json())
            .then(d => {
                if (d.ok) {
                    document.getElementById('ogImagePath').value = d.path;
                    if (typeof toastr !== 'undefined') toastr.success('OG image uploaded');
                }
            });
    };

    document.addEventListener('DOMContentLoaded', () => setTimeout(refreshSeo, 1500));
})();
</script>
@endsection
