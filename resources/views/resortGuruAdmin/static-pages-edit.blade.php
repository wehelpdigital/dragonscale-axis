@extends('layouts.master')

@section('title') Edit Page @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Resort Guru @endslot
@slot('li_2') Site Pages @endslot
@slot('li_2_link') {{ route('resort-guru-static.index') }} @endslot
@slot('title') {{ $page->title ?? 'Edit' }} @endslot
@endcomponent

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

@php
    // Slugs that map to a special frontend route. The URL is set by
    // a route → controller binding, NOT by the slug — so editing the
    // slug doesn't change the URL on these. Surface a warning.
    $specialSlugs = [
        'home' => '/',
        'about' => '/about',
        'contact' => '/contact',
        'terms' => '/terms',
        'privacy' => '/privacy',
        'destinations' => '/destinations',
        'food-trip' => '/food-trip',
        'blog-index' => '/blog',
        'register-page' => '/register',
        'login-page' => '/login',
        'contact-page' => '/contact',
    ];
    $isSpecial = isset($specialSlugs[$page->slug]);
    $publicUrl = $isSpecial ? $specialSlugs[$page->slug] : ('/' . ltrim($page->slug, '/'));
    $blockTypesAllowed = [
        // General-purpose blocks.
        'heading','rich_text','image','gallery','video','faq','cta','two_column','quote','divider','custom_html',
        // Section templates.
        'text_section','short_version','pros_cons','summary_accordion','image_text_pair',
        'traveler_reviews','map_embed','local_tip','related_guides','data_table',
        'section_header','tag_pills','external_guides','author','related_blogs',
        'facts_list','place_history','foods_to_try','hero_slider','quick_facts',
        // /destinations page custom block types.
        'dest_hero_search','dest_featured_slider','dest_region_clusters',
    ];
@endphp

<div class="row">
    <div class="col-lg-8">
        <form action="{{ route('resort-guru-static.update', ['id' => $page->id]) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Page Settings</h5>
                    <div class="d-flex gap-2">
                        <div class="form-check form-switch mb-0">
                            <input type="checkbox" class="form-check-input" name="is_published" id="isPublished" value="1" {{ $page->is_published ? 'checked' : '' }}>
                            <label class="form-check-label" for="isPublished">Published</label>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Page Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="{{ $page->title }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slug <small class="text-muted">(URL path, lowercase letters / digits / hyphens)</small></label>
                        <input type="text" name="slug" class="form-control" value="{{ $page->slug }}" pattern="[a-z0-9-]+" required>
                        @if($isSpecial)
                            <div class="form-text text-warning small">
                                <i class="bx bx-info-circle"></i>
                                <strong>Heads up:</strong> this page is served at <code>{{ $publicUrl }}</code> via a controller route, not by slug. Renaming the slug here won't change the URL — but it WILL break the controller's lookup that fetches this row. Leave as <code>{{ $page->slug }}</code> unless you're moving content between rows on purpose.
                            </div>
                        @endif
                    </div>
                    <div class="mb-3">
                        <label class="form-label d-flex justify-content-between">
                            <span>Meta Title <small class="text-muted">(for &lt;title&gt; tag)</small></span>
                            <span class="small" id="metaTitleCount">0 chars</span>
                        </label>
                        <input type="text" name="meta_title" class="form-control" id="metaTitle" maxlength="70" value="{{ $page->meta_title }}">
                        <div class="form-text small">Ideal: 50–60 characters.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label d-flex justify-content-between">
                            <span>Meta Description</span>
                            <span class="small" id="metaDescCount">0 chars</span>
                        </label>
                        <textarea name="meta_description" id="metaDesc" class="form-control" rows="3" maxlength="200">{{ $page->meta_description }}</textarea>
                        <div class="form-text small">Ideal: 140–160 characters.</div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Save Settings</button>
                </div>
            </div>
        </form>

        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0"><i class="bx bx-layer me-1"></i>Content Builder</h5>
                <small class="text-muted">Drag blocks to reorder. Click edit to change content. Saves are live — frontend updates immediately.</small>
            </div>
            <div class="card-body">
                @include('resortGuruAdmin.blocks.builder', [
                    'ownerType' => 'static_page',
                    'ownerId' => $page->id,
                    'allowed' => $blockTypesAllowed,
                ])
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        {{-- SEO Score sidebar (AJAX) --}}
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

        <div class="card">
            <div class="card-header"><h6 class="mb-0">About this page</h6></div>
            <div class="card-body small text-muted">
                <p>Public URL: <code>{{ $publicUrl }}</code></p>
                <p>Slug: <code>{{ $page->slug }}</code></p>
                <p>Last updated: {{ \Carbon\Carbon::parse($page->updated_at)->diffForHumans() }}</p>
                @if($page->is_published)
                    @if(\Illuminate\Support\Facades\Route::has('resort-guru-static.live-edit'))
                        <a href="{{ route('resort-guru-static.live-edit', ['id' => $page->id]) }}" class="btn btn-sm btn-primary w-100 mb-2">
                            <i class="bx bx-edit-alt"></i> Live Editor
                        </a>
                    @endif
                    <a href="{{ rtrim(\App\Support\RgFrontend::url(), '/') . $publicUrl }}" target="_blank" class="btn btn-sm btn-outline-success w-100">
                        <i class="bx bx-link-external"></i> View live
                    </a>
                @endif
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
    if (mt) { mt.addEventListener('input', () => updateCount(mt, mtc, [50, 60])); updateCount(mt, mtc, [50, 60]); }
    if (md) { md.addEventListener('input', () => updateCount(md, mdc, [140, 160])); updateCount(md, mdc, [140, 160]); }

    window.refreshSeo = function() {
        const panel = document.getElementById('rgSeoPanel');
        panel.innerHTML = '<i class="bx bx-loader bx-spin"></i> Analyzing...';
        fetch('/resort-guru-static-seo-analyze?id={{ $page->id }}', { credentials: 'same-origin' })
            .then(r => r.json())
            .then(d => {
                if (!d.ok) { panel.innerHTML = '<p class="text-muted">Could not analyze.</p>'; return; }
                const scoreColor = d.score >= 80 ? 'success' : d.score >= 60 ? 'warning' : 'danger';
                let html = `<div class="text-center py-3 bg-${scoreColor} bg-opacity-10">
                    <div style="font-size:38px;font-weight:bold;color:var(--bs-${scoreColor})">${d.score}/100</div>
                    <small class="text-muted text-uppercase">${d.label}</small>
                </div><ul class="list-group list-group-flush">`;
                d.checks.forEach(c => {
                    const icon = c.status === 'pass'
                        ? '<i class="bx bx-check text-success"></i>'
                        : c.status === 'warn'
                            ? '<i class="bx bx-error text-warning"></i>'
                            : '<i class="bx bx-x text-danger"></i>';
                    html += `<li class="list-group-item py-2"><div class="d-flex gap-2"><div>${icon}</div><div class="flex-grow-1"><strong class="small">${c.label}</strong><div class="text-muted" style="font-size:11px">${c.message}</div></div></div></li>`;
                });
                html += '</ul>';
                panel.innerHTML = html;
            })
            .catch(() => { panel.innerHTML = '<p class="text-muted p-2">Analysis service unavailable.</p>'; });
    };

    document.addEventListener('DOMContentLoaded', () => setTimeout(refreshSeo, 1500));
})();
</script>
@endsection
