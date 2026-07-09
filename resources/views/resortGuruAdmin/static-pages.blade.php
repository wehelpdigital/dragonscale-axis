@extends('layouts.master')

@section('title') Site Pages @endsection

@php
    $frontend = rtrim(\App\Support\RgFrontend::url(), '/');

    // slug -> real public path. Blank/absent = not publicly viewable (no preview).
    $urls = [
        'home' => '/',
        'destinations' => '/tourist-spots-destinations-philippines',
        'food-trip' => '/food-trip',
        'foods' => '/filipino-food-dishes-what-to-eat',
        'activities' => '/philippine-tourist-activities-adventures-what-to-do',
        'buys' => '/philippine-souvenirs-pasalubong-what-to-buy',
        'cultures' => '/philippine-tribes-ethnic-groups-cultures-to-meet',
        'become-a-partner' => '/become-a-partner',
        'destination-cluster' => '/tourist-spots-destinations-philippines/north-luzon',
        'about' => '/about-tourist-guide-ph',
        'contact' => '/contact',
        'terms' => '/terms',
        'privacy' => '/privacy',
        'blog-index' => '/blog',
        'login-page' => '/login',
        'register-page' => '/register',
        'contact-page' => '/contact',
    ];

    $groupOf = function ($slug) {
        if (in_array($slug, ['home', 'destinations', 'food-trip', 'foods', 'activities', 'buys', 'cultures'])) return 'main';
        if ($slug === 'become-a-partner') return 'partner';
        if (in_array($slug, ['about', 'contact', 'terms', 'privacy'])) return 'company';
        if ($slug === 'destination-cluster') return 'templates';
        if (in_array($slug, ['blog-index', 'login-page', 'register-page', 'contact-page'])) return 'system';
        return 'other';
    };

    $groupMeta = [
        'main' => ['label' => 'Main Pages', 'desc' => 'The homepage and the six public sections travelers browse.', 'icon' => 'bx-compass', 'color' => '#4338ca'],
        'partner' => ['label' => 'Partner Program', 'desc' => 'Business-facing pages that recruit tourism partners.', 'icon' => 'bx-handshake', 'color' => '#059669'],
        'company' => ['label' => 'Company & Legal', 'desc' => 'About, contact, and policy pages.', 'icon' => 'bx-building-house', 'color' => '#0ea5e9'],
        'templates' => ['label' => 'Templates', 'desc' => 'Shared layouts rendered across many URLs.', 'icon' => 'bx-copy-alt', 'color' => '#d97706'],
        'system' => ['label' => 'System Page Content', 'desc' => 'Editable copy for the blog, login, register, and contact pages.', 'icon' => 'bx-cog', 'color' => '#64748b'],
        'other' => ['label' => 'Other', 'desc' => 'Uncategorised pages.', 'icon' => 'bx-file', 'color' => '#475569'],
    ];

    $pageIcon = [
        'home' => '🏠', 'destinations' => '🗺️', 'food-trip' => '🍽️', 'foods' => '🥘',
        'activities' => '🏄', 'buys' => '🛍️', 'cultures' => '🪡', 'become-a-partner' => '🤝',
        'about' => 'ℹ️', 'contact' => '✉️', 'terms' => '📜', 'privacy' => '🔒',
        'destination-cluster' => '📑', 'blog-index' => '📰', 'login-page' => '🔑', 'register-page' => '📝', 'contact-page' => '✉️',
    ];

    $buckets = [];
    foreach ($pages as $pg) {
        $buckets[$groupOf($pg->slug)][] = $pg;
    }
    $order = ['main', 'partner', 'company', 'templates', 'system', 'other'];
@endphp

@section('content')
@component('components.breadcrumb')
@slot('li_1') Resort Guru @endslot
@slot('title') Site Pages @endslot
@endcomponent

<style>
    .rg-pages-head { display:flex; flex-wrap:wrap; gap:12px; align-items:center; justify-content:space-between; margin-bottom:18px; }
    .rg-pages-search { position:relative; max-width:320px; width:100%; }
    .rg-pages-search i { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; }
    .rg-pages-search input { padding-left:34px; border-radius:9999px; }
    .rg-tabs { display:flex; flex-wrap:wrap; gap:8px; margin:0 0 6px; padding:0; list-style:none; }
    .rg-tab { display:inline-flex; align-items:center; gap:8px; padding:.5rem .95rem; border-radius:9999px; border:1px solid #e2e8f0; background:#fff; color:#475569; font-size:13px; font-weight:600; cursor:pointer; transition:all .12s ease; }
    .rg-tab:hover { border-color:#cbd5e1; background:#f8fafc; }
    .rg-tab.active { color:#fff; border-color:transparent; box-shadow:0 4px 10px rgba(15,23,42,.14); }
    .rg-tab-dot { width:9px; height:9px; border-radius:50%; flex:0 0 auto; }
    .rg-tab.active .rg-tab-dot { background:#fff !important; }
    .rg-tab-count { font-size:11px; font-weight:700; background:rgba(100,116,139,.14); color:#475569; border-radius:9999px; padding:1px 8px; }
    .rg-tab.active .rg-tab-count { background:rgba(255,255,255,.25); color:#fff; }
    .rg-groupdesc { color:#94a3b8; font-size:13px; margin:8px 0 18px; }
    .rg-page-card { height:100%; border:1px solid #e9ecef; transition:transform .12s ease, box-shadow .12s ease; }
    .rg-page-card:hover { transform:translateY(-3px); box-shadow:0 .6rem 1.4rem rgba(15,23,42,.12); }
    .rg-page-emoji { width:44px; height:44px; border-radius:11px; display:flex; align-items:center; justify-content:center; font-size:24px; line-height:1; flex:0 0 auto; }
    .rg-page-title { font-size:18px; font-weight:700; margin:0 0 3px; color:#1e293b; line-height:1.25; }
    .rg-page-slug { font-size:12px; margin-bottom:16px; word-break:break-all; }
    .rg-page-slug code { color:#64748b; }
    .rg-tpl-note { font-size:11.5px; color:#b45309; background:#fffbeb; border:1px solid #fde68a; border-radius:8px; padding:6px 9px; margin:-6px 0 14px; display:flex; gap:6px; align-items:flex-start; line-height:1.35; }
    .rg-page-actions { display:flex; gap:6px; }
    .rg-page-actions .btn, .rg-page-view { padding:.4rem .6rem; font-size:12.5px; font-weight:600; display:inline-flex; align-items:center; justify-content:center; gap:5px; }
    .rg-noresult { display:none; }
</style>

<div class="rg-pages-head">
    <div>
        <p class="text-muted mb-0">Every page on the public site. Open one to edit its content blocks, or use Live Edit to change it right on the page.</p>
        <small class="text-muted"><strong id="rgPageTotal">{{ $pages->count() }}</strong> pages</small>
    </div>
    <div class="rg-pages-search">
        <i class="bx bx-search"></i>
        <input type="text" id="rgPageSearch" class="form-control" placeholder="Search pages by name or slug…" autocomplete="off">
    </div>
</div>

@if($pages->isEmpty())
    <div class="alert alert-warning">No site pages found. Run <code>php artisan db:seed --class=RgStaticPagesSeeder</code> from the frontend project to seed the defaults.</div>
@else
    <ul class="rg-tabs" id="rgTabs">
        @php $firstTab = true; @endphp
        @foreach($order as $gkey)
            @if(!empty($buckets[$gkey]))
                @php $gm = $groupMeta[$gkey]; @endphp
                <li>
                    <button type="button" class="rg-tab{{ $firstTab ? ' active' : '' }}" data-group="{{ $gkey }}" data-color="{{ $gm['color'] }}" data-desc="{{ $gm['desc'] }}" @if($firstTab) style="background:{{ $gm['color'] }}" @endif>
                        <span class="rg-tab-dot" style="background:{{ $gm['color'] }}"></span>
                        <i class="bx {{ $gm['icon'] }}"></i>
                        {{ $gm['label'] }}
                        <span class="rg-tab-count">{{ count($buckets[$gkey]) }}</span>
                    </button>
                </li>
                @php $firstTab = false; @endphp
            @endif
        @endforeach
    </ul>
    <div class="rg-groupdesc" id="rgGroupDesc"></div>

    <div class="row g-3" id="rgPageGrid">
        @foreach($order as $gkey)
            @if(!empty($buckets[$gkey]))
                @php $gm = $groupMeta[$gkey]; @endphp
                @foreach($buckets[$gkey] as $pg)
                    @php
                        $path = $urls[$pg->slug] ?? '';
                        $full = $path !== '' ? $frontend . $path : '';
                    @endphp
                    <div class="col-xxl-3 col-xl-4 col-md-6 rg-page-col" data-group="{{ $gkey }}" data-search="{{ strtolower($pg->title . ' ' . $pg->slug) }}">
                        <div class="card rg-page-card mb-0">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <span class="rg-page-emoji" style="background:{{ $gm['color'] }}1a">{{ $pageIcon[$pg->slug] ?? '📄' }}</span>
                                    <span class="badge bg-{{ $pg->is_published ? 'success' : 'secondary' }}">
                                        <i class="bx {{ $pg->is_published ? 'bx-check-circle' : 'bx-hide' }} align-middle"></i>
                                        {{ $pg->is_published ? 'Published' : 'Draft' }}
                                    </span>
                                </div>
                                <h5 class="rg-page-title">{{ $pg->title }}</h5>
                                <div class="rg-page-slug"><code>{{ $full ? $path : '/' . $pg->slug }}</code></div>
                                @if($gkey === 'templates')
                                    <div class="rg-tpl-note"><i class="bx bx-info-circle"></i><span>Template — renders on every region page. Editing changes them all.</span></div>
                                @endif
                                <div class="rg-page-actions">
                                    <a href="{{ route('resort-guru-static.edit', ['id' => $pg->id]) }}" class="btn btn-primary flex-fill"><i class="bx bx-edit-alt"></i> {{ $gkey === 'templates' ? 'Build Template' : 'Edit' }}</a>
                                    <a href="{{ route('resort-guru-static.live-edit', ['id' => $pg->id]) }}" class="btn btn-soft-primary flex-fill" title="Edit on the live page"><i class="bx bx-brush"></i> Live Editor</a>
                                </div>
                                @if($full)
                                    <a href="{{ $full }}" target="_blank" rel="noopener" class="btn btn-light rg-page-view w-100 mt-2" title="Open a sample page in a new tab"><i class="bx bx-link-external"></i> {{ $gkey === 'templates' ? 'Preview a Page' : 'View Page' }}</a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        @endforeach
    </div>

    <div class="rg-noresult alert alert-light border text-center text-muted" id="rgNoResult">
        <i class="bx bx-search-alt me-1"></i> No pages match your search.
    </div>
@endif

<script>
(function () {
    var search = document.getElementById('rgPageSearch');
    var tabs = Array.prototype.slice.call(document.querySelectorAll('.rg-tab'));
    var cols = Array.prototype.slice.call(document.querySelectorAll('.rg-page-col'));
    var desc = document.getElementById('rgGroupDesc');
    var noResult = document.getElementById('rgNoResult');
    if (!tabs.length) return;
    var active = tabs[0].getAttribute('data-group');

    function apply() {
        var q = (search ? search.value : '').trim().toLowerCase();
        var searching = q.length > 0;
        var shown = 0;
        cols.forEach(function (c) {
            var match = searching
                ? (c.getAttribute('data-search') || '').indexOf(q) > -1
                : c.getAttribute('data-group') === active;
            c.style.display = match ? '' : 'none';
            if (match) shown++;
        });
        tabs.forEach(function (t) {
            var on = !searching && t.getAttribute('data-group') === active;
            t.classList.toggle('active', on);
            t.style.background = on ? t.getAttribute('data-color') : '';
        });
        if (desc) {
            if (searching) {
                desc.textContent = shown + ' page' + (shown === 1 ? '' : 's') + ' matching “' + search.value.trim() + '”';
            } else {
                var at = tabs.filter(function (t) { return t.getAttribute('data-group') === active; })[0];
                desc.textContent = at ? at.getAttribute('data-desc') : '';
            }
        }
        if (noResult) noResult.style.display = shown ? 'none' : 'block';
    }

    tabs.forEach(function (t) {
        t.addEventListener('click', function () {
            active = t.getAttribute('data-group');
            if (search) search.value = '';
            apply();
        });
    });
    if (search) search.addEventListener('input', apply);
    apply();
})();
</script>
@endsection
