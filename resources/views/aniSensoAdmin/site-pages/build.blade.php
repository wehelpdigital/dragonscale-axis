@extends('layouts.master')

@section('title') Build — {{ $page->title }} @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    /* Scoped under #spb: this page's styles load before Bootstrap's. */
    .main-content:has(#spb) { overflow: clip; }
    #spb { --e: cubic-bezier(.22,1,.36,1); }
    #spb .top { position: sticky; top: 70px; z-index: 20; display: flex; flex-wrap: wrap; align-items: center; gap: .6rem; padding: .7rem .9rem;
        margin-bottom: .9rem; border-radius: .9rem; background: var(--bs-body-bg); border: 1px solid var(--bs-border-color); box-shadow: 0 10px 30px -22px rgb(0 0 0 / .45); }
    #spb .top h5 { margin: 0; min-width: 0; flex: 1 1 14rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    #spb .dirty { font-size: .8rem; color: var(--bs-warning-text-emphasis); opacity: 0; transition: opacity .28s var(--e); }
    #spb .dirty.is-on { opacity: 1; }
    #spb .grid { display: grid; gap: 1rem; align-items: start; grid-template-columns: minmax(0, 1fr); }
    @media (min-width: 1200px) { #spb .grid { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); } #spb .pane { position: sticky; top: 140px; } }
    #spb .tabs { display: flex; gap: .3rem; margin-bottom: .8rem; }
    #spb .tabs button { border: 0; background: var(--bs-tertiary-bg); color: var(--bs-body-color); border-radius: .6rem; padding: .45rem .8rem; font-weight: 600; font-size: .88rem;
        transition: background .28s var(--e), color .28s var(--e); }
    #spb .tabs button.is-on { background: var(--bs-primary); color: #fff; }
    #spb .tabs .score { margin-left: .3rem; font-size: .72rem; padding: .05rem .4rem; border-radius: 999px; background: rgb(255 255 255 / .25); }
    #spb .view { display: none; } #spb .view.is-on { display: block; animation: spbIn .28s var(--e); }
    @keyframes spbIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: none; } }

    /* the palette */
    #spb .pal { display: flex; flex-wrap: wrap; gap: .35rem; margin-bottom: .8rem; }
    #spb .pal button { display: inline-flex; align-items: center; gap: .3rem; border: 1px dashed var(--bs-primary-border-subtle); background: var(--bs-primary-bg-subtle);
        color: var(--bs-primary-text-emphasis); border-radius: 999px; padding: .3rem .7rem; font-size: .8rem; font-weight: 600; cursor: grab;
        transition: transform .28s var(--e), background .28s var(--e); }
    #spb .pal button:hover { transform: translateY(-1px); }
    #spb .pal button i { font-size: 1rem; }

    /* blocks */
    #spb .blocks { min-height: 6rem; }
    #spb .b { border: 1px solid var(--bs-border-color); border-radius: .8rem; background: var(--bs-body-bg); margin-bottom: .55rem;
        transition: box-shadow .28s var(--e), border-color .28s var(--e), opacity .2s; }
    #spb .b.is-sel { border-color: var(--bs-primary); box-shadow: 0 0 0 3px var(--bs-primary-bg-subtle); }
    #spb .b.is-drag { opacity: .4; }
    #spb .b.is-new { animation: spbIn .3s var(--e); }
    #spb .bh { display: flex; align-items: center; gap: .45rem; padding: .45rem .6rem; background: var(--bs-tertiary-bg); border-radius: .8rem .8rem 0 0; cursor: pointer; }
    #spb .b.is-shut .bh { border-radius: .8rem; }
    #spb .grip { cursor: grab; color: var(--bs-secondary-color); font-size: 1.1rem; }
    #spb .kind { font-size: .7rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--bs-primary-text-emphasis); flex: none; }
    #spb .sum { font-size: .8rem; color: var(--bs-secondary-color); min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1; }
    #spb .bh .btn { --bs-btn-padding-y: .1rem; --bs-btn-padding-x: .35rem; line-height: 1; }
    #spb .bb { padding: .7rem; display: grid; gap: .45rem; }
    #spb .b.is-shut .bb { display: none; }
    #spb .bb label { font-size: .72rem; font-weight: 700; color: var(--bs-secondary-color); margin-bottom: -.2rem; }
    #spb .bb textarea { resize: vertical; min-height: 4.5rem; }
    #spb .drop { height: .35rem; border-radius: 999px; margin: -.1rem 0 .45rem; background: var(--bs-primary); opacity: 0; transition: opacity .15s; }
    #spb .drop.is-on { opacity: 1; }
    #spb .tool { display: flex; gap: .25rem; }
    #spb .tool button { --bs-btn-padding-y: .1rem; --bs-btn-padding-x: .45rem; --bs-btn-font-size: .75rem; }
    #spb .rows { display: grid; gap: .45rem; }
    #spb .row2 { display: grid; gap: .35rem; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) auto; align-items: start; padding: .45rem; border-radius: .6rem; background: var(--bs-tertiary-bg); }
    #spb .row2.v { grid-template-columns: minmax(0, 1fr) auto; }
    #spb .row2 .full { grid-column: 1 / -1; }
    #spb .thumb { width: 7rem; aspect-ratio: 16 / 10; object-fit: cover; border-radius: .5rem; border: 1px solid var(--bs-border-color); background: var(--bs-tertiary-bg); }
    #spb .empty { text-align: center; padding: 2rem 1rem; color: var(--bs-secondary-color); border: 1px dashed var(--bs-border-color); border-radius: .8rem; }

    /* page and SEO */
    #spb .count { font-size: .72rem; float: right; color: var(--bs-secondary-color); }
    #spb .count.bad { color: var(--bs-danger); } #spb .count.ok { color: var(--bs-success); }
    #spb .snip { border: 1px solid var(--bs-border-color); border-radius: .7rem; padding: .8rem .9rem; background: var(--bs-body-bg); }
    #spb .snip .u { font-size: .78rem; color: #188038; } #spb .snip .ti { font-size: 1.05rem; color: #1a0dab; margin-top: .1rem; }
    #spb .snip .de { font-size: .84rem; color: var(--bs-secondary-color); margin-top: .15rem; }
    html[data-bs-theme="dark"] #spb .snip .ti { color: #8ab4f8; }

    /* SEO check */
    #spb .chk { display: grid; gap: .35rem; }
    #spb .chk div { display: flex; gap: .55rem; align-items: flex-start; font-size: .88rem; padding: .35rem .5rem; border-radius: .5rem; }
    #spb .chk i.d { flex: none; width: .75rem; height: .75rem; border-radius: 999px; margin-top: .3rem; }
    #spb .chk .good i.d { background: #34c38f; } #spb .chk .ok i.d { background: #f1b44c; } #spb .chk .bad i.d { background: #f46a6a; }
    #spb .chk .bad { background: var(--bs-danger-bg-subtle); }
    #spb .chk small { display: block; color: var(--bs-secondary-color); }

    /* preview */
    #spb .pvbar { display: flex; align-items: center; gap: .4rem; margin-bottom: .5rem; }
    #spb .pvbar .btn-group .btn { --bs-btn-padding-y: .2rem; }
    /* The page is drawn at the device's real width and scaled to fit, so
       Desktop shows anee.io's desktop layout even in half a screen. */
    #spb .pvwrap { position: relative; border: 1px solid var(--bs-border-color); border-radius: .9rem; background: #e9ecef; overflow: hidden; height: calc(100vh - 13rem); min-height: 28rem; }
    #spb .pvwrap iframe { position: absolute; top: 0; left: 0; border: 0; background: #fff; transform-origin: 0 0; transition: transform .28s var(--e), width .28s var(--e); }
    #spb .pvscale { font-size: .72rem; color: var(--bs-secondary-color); }
    #spb .pvnote { font-size: .72rem; color: var(--bs-secondary-color); margin-top: .35rem; }
    /* Write with Anee */
    #spb .tabs button[data-view="ai"] { background: linear-gradient(135deg, #fff4cc, #ffe38a); color: #5c4200; }
    #spb .tabs button[data-view="ai"].is-on { background: linear-gradient(135deg, #f5c518, #e0a800); color: #2b2000; }
    #spb .ai-intro { display: flex; gap: .8rem; align-items: flex-start; padding: .8rem .9rem; border-radius: .8rem; background: var(--bs-success-bg-subtle); margin-bottom: 1rem; }
    #spb .ai-intro img { width: 2.6rem; height: 2.6rem; border-radius: 999px; object-fit: cover; flex: none; }
    #spb .ai-intro p { margin: 0; font-size: .86rem; }
    #spb .kws { display: flex; flex-wrap: wrap; gap: .35rem; }
    #spb .kw { border: 1px solid var(--bs-border-color); background: var(--bs-body-bg); border-radius: 999px; padding: .25rem .6rem; font-size: .8rem;
        transition: background .28s var(--e), border-color .28s var(--e), color .28s var(--e); }
    #spb .kw small { margin-left: .3rem; color: var(--bs-secondary-color); }
    #spb .kw.is-on { background: var(--bs-primary); border-color: var(--bs-primary); color: #fff; }
    #spb .kw.is-on small { color: rgb(255 255 255 / .75); }
    #spb .ai-run { margin-top: 1rem; padding: .9rem; border-radius: .8rem; border: 1px solid var(--bs-border-color); }
    #spb .ai-bar { height: .5rem; border-radius: 999px; background: var(--bs-tertiary-bg); overflow: hidden; }
    #spb .ai-bar span { display: block; height: 100%; width: 0; background: linear-gradient(90deg, #6b9f3d, #f5c518); transition: width .6s var(--e); }
    #spb .ai-done { margin-top: 1rem; padding: .9rem; border-radius: .8rem; background: var(--bs-success-bg-subtle); }
    @media (prefers-reduced-motion: reduce) { #spb * { animation: none !important; transition: none !important; } }
</style>
@include('aniSensoAdmin.partials.dark')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') AniSystem @endslot
        @slot('li_2') <a href="{{ route('anisenso-site-pages.index') }}">Website pages</a> @endslot
        @slot('title') Build @endslot
    @endcomponent

    <div id="spb">
        <div class="top">
            <a href="{{ route('anisenso-site-pages.index') }}" class="btn btn-light btn-sm"><i class="bx bx-arrow-back"></i></a>
            <h5 id="spbName">{{ $page->title }}</h5>
            <span class="dirty" id="spbDirty">Unsaved changes</span>
            <div class="form-check form-switch m-0">
                <input class="form-check-input" type="checkbox" role="switch" id="spbLive" @checked($page->status === 'published')>
                <label class="form-check-label" for="spbLive" id="spbLiveLabel">{{ $page->status === 'published' ? 'Live' : 'Draft' }}</label>
            </div>
            @if ($hasSeed)<button type="button" class="btn btn-light btn-sm" id="spbReset" title="Put back the version anee.io shipped"><i class="bx bx-reset"></i> Shipped version</button>@endif
            <a class="btn btn-light btn-sm" id="spbOpen" href="{{ $liveUrl }}" target="_blank" rel="noopener"><i class="bx bx-link-external"></i> anee.io</a>
            <button type="button" class="btn btn-primary btn-sm" id="spbSave"><i class="bx bx-save"></i> Save</button>
        </div>

        <div class="grid">
            <div>
                <div class="card"><div class="card-body">
                    <div class="tabs" id="spbTabs">
                        <button type="button" class="is-on" data-view="blocks"><i class="bx bx-layer"></i> Blocks</button>
                        <button type="button" data-view="page"><i class="bx bx-cog"></i> Page and SEO</button>
                        <button type="button" data-view="check"><i class="bx bx-check-shield"></i> SEO check <span class="score" id="spbScore">…</span></button>
                        <button type="button" data-view="ai"><i class="bx bxs-magic-wand"></i> Write with Anee</button>
                    </div>

                    {{-- Blocks --}}
                    <div class="view is-on" data-view="blocks">
                        <div class="pal" id="spbPal">
                            @foreach ($kinds as $k => [$label, $icon])
                                <button type="button" draggable="true" data-kind="{{ $k }}" title="Click to add, or drag into place"><i class="bx {{ $icon }}"></i>{{ $label }}</button>
                            @endforeach
                        </div>
                        <p class="text-secondary small mb-2">Click a piece to add it after the one you have selected, or drag it where it goes. Drag the <i class="bx bx-grid-vertical"></i> handle to reorder. Inside text: <code>**bold**</code> and <code>[words](/crops/palay)</code> for a link.</p>
                        <div class="blocks" id="spbBlocks"></div>
                    </div>

                    {{-- Page and SEO --}}
                    <div class="view" data-view="page">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label">Section</label>
                                <select class="form-select" data-f="section">
                                    @foreach ($sections as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Address</label>
                                <input class="form-control" data-f="slug" maxlength="120">
                                <div class="form-text" id="spbUrl"></div>
                            </div>
                            <div class="col-12" id="spbShowInRow">
                                <label class="form-label">Show this post in</label>
                                <select class="form-select" data-f="showIn">
                                    @foreach ($showIn as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
                                </select>
                                <div class="form-text">The public blog is anee.io/blog. The Technician's Blog is the members' blog inside the app.</div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Language</label>
                                <select class="form-select" data-f="lang"><option value="en">English</option><option value="tl">Tagalog</option></select>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Category</label>
                                <input class="form-control" data-f="category" maxlength="60" placeholder="e.g. Fertilizer">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Title (the page's H1)</label>
                                <input class="form-control" data-f="title" maxlength="200">
                            </div>
                            <div class="col-12">
                                <label class="form-label">SEO title <span class="count" id="cMt"></span></label>
                                <input class="form-control" data-f="metaTitle" maxlength="120">
                                <div class="form-text">Shown in Google's results, followed by " | anee.io". 60 characters at most; the keyphrase near the start.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Meta description <span class="count" id="cMd"></span></label>
                                <textarea class="form-control" rows="3" data-f="metaDescription" maxlength="320"></textarea>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Focus keyphrase</label>
                                <input class="form-control" data-f="focusKeyword" maxlength="120">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Other keywords <span class="text-secondary">(comma separated)</span></label>
                                <input class="form-control" data-f="keywords">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Intro (shown under the title; it is the first paragraph)</label>
                                <textarea class="form-control" rows="3" data-f="excerpt"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Header picture</label>
                                <div class="d-flex gap-2 align-items-start flex-wrap">
                                    <img class="thumb" id="spbHeroImg" alt="">
                                    <div class="flex-grow-1" style="min-width:14rem">
                                        <div class="input-group input-group-sm mb-1">
                                            <input class="form-control" data-f="hero.src" placeholder="/images/site/palay.jpg or a full address">
                                            <button class="btn btn-outline-primary" type="button" data-up="hero"><i class="bx bx-upload"></i> Upload</button>
                                        </div>
                                        <input class="form-control form-control-sm mb-1" data-f="hero.alt" placeholder="Alt text: what the picture shows">
                                        <input class="form-control form-control-sm" data-f="hero.credit" placeholder="Credit (for a borrowed photo)">
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">In Google, roughly</label>
                                <div class="snip"><div class="u" id="sU"></div><div class="ti" id="sT"></div><div class="de" id="sD"></div></div>
                            </div>
                        </div>
                    </div>

                    {{-- Write with Anee --}}
                    <div class="view" data-view="ai">
                        <div class="ai-intro">
                            <img src="{{ rtrim((string) config('anisystem.url'), '/') }}/images/anee/avatar-160.jpg" alt="">
                            <p>Anee writes the page by the site's rules: plain words, the Yoast checks, keywords from your SEO list, and links only to pages that exist. Nothing is saved until you press Save.</p>
                        </div>
                        <div class="btn-group btn-group-sm mb-3" id="aiMode" role="group">
                            <button type="button" class="btn btn-outline-primary active" data-mode="new"><i class="bx bx-file-blank"></i> Write a new draft</button>
                            <button type="button" class="btn btn-outline-primary" data-mode="improve"><i class="bx bx-edit-alt"></i> Improve this page</button>
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">What is the page about?</label>
                                <input class="form-control" id="aiTopic" maxlength="300" placeholder="e.g. How much urea to apply on corn, and when">
                            </div>
                            <div class="col-sm-7">
                                <label class="form-label">Focus keyphrase</label>
                                <input class="form-control" id="aiFocus" maxlength="120" list="aiKwList" placeholder="Leave empty and Anee picks one">
                                <datalist id="aiKwList"></datalist>
                            </div>
                            <div class="col-sm-5">
                                <label class="form-label">Language</label>
                                <select class="form-select" id="aiLang"><option value="en">English</option><option value="tl">Tagalog</option></select>
                            </div>
                            <div class="col-12">
                                <label class="form-label d-flex align-items-center gap-2">Keywords to use
                                    <button type="button" class="btn btn-link btn-sm p-0 ms-auto" id="aiFind"><i class="bx bx-refresh"></i> Find keywords for this topic</button></label>
                                <div class="kws" id="aiKws"><span class="text-secondary small">Type the topic, then find keywords.</span></div>
                                <div class="form-text">From AniSystem › SEO keywords, nearest to the topic first. Tap to choose. Anee uses the ones that read naturally.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes for Anee <span class="text-secondary">(optional)</span></label>
                                <textarea class="form-control" rows="2" id="aiNotes" maxlength="2000" placeholder="e.g. Mention the wet season. Keep it for small farms."></textarea>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="aiResearch" checked>
                                    <label class="form-check-label" for="aiResearch">Read the web first <span class="text-secondary">(slower, better facts and sources)</span></label>
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="button" class="btn btn-primary" id="aiGo"><i class="bx bxs-magic-wand"></i> Write with Anee</button>
                            </div>
                        </div>
                        <div class="ai-run" id="aiRun" hidden>
                            <div class="d-flex justify-content-between small mb-2"><b id="aiPhase">Starting</b><span id="aiClock">0:00</span></div>
                            <div class="ai-bar"><span id="aiBar"></span></div>
                            <div class="small text-secondary mt-2">This takes one to three minutes. You can keep working on the other tabs.</div>
                        </div>
                        <div class="ai-done" id="aiDone" hidden>
                            <b>Anee's page is in the builder.</b>
                            <div class="small">Read it in the preview and the SEO check, change what you like, then Save.</div>
                            <button type="button" class="btn btn-light btn-sm mt-2" id="aiUndo"><i class="bx bx-undo"></i> Put back the page as it was</button>
                        </div>
                    </div>

                    {{-- SEO check --}}
                    <div class="view" data-view="check">
                        <p class="text-secondary small">Checked live as you write, by the rules the site's pages were written to: Yoast's SEO and readability rules, the owner's banned phrases, and no dashes.</p>
                        <div class="chk" id="spbChecks"></div>
                    </div>
                </div></div>
            </div>

            <div class="pane">
                <div class="card"><div class="card-body">
                    <div class="pvbar">
                        <h6 class="mb-0 me-auto"><i class="bx bx-devices"></i> Live preview on anee.io</h6>
                        <div class="btn-group btn-group-sm" id="spbDev">
                            <button type="button" class="btn btn-light active" data-dev="">Desktop</button>
                            <button type="button" class="btn btn-light" data-dev="tablet">Tablet</button>
                            <button type="button" class="btn btn-light" data-dev="phone">Phone</button>
                        </div>
                        <span class="pvscale" id="spbScale"></span>
                        <button type="button" class="btn btn-light btn-sm" id="spbRefresh" title="Draw again"><i class="bx bx-refresh"></i></button>
                    </div>
                    <div class="pvwrap" id="spbWrap"><iframe name="spbFrame" id="spbFrame" title="Preview"></iframe></div>
                    <p class="pvnote">Drawn by anee.io itself as you type, before you save. Visitors see it only once it is saved and live.</p>
                    <form id="spbPv" method="POST" target="spbFrame" class="d-none"><textarea name="page"></textarea></form>
                </div></div>
            </div>
        </div>
        <input type="file" id="spbFile" accept="image/*" class="d-none">
        <datalist id="spbUrls"></datalist>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
(() => {
    const CSRF = "{{ csrf_token() }}";
    const ID = {{ (int) $page->id }};
    const U = {
        data: "{{ route('anisenso-site-pages.data') }}?id=" + ID,
        save: "{{ route('anisenso-site-pages.save') }}?id=" + ID,
        upload: "{{ route('anisenso-site-pages.upload') }}?id=" + ID,
        reset: "{{ route('anisenso-site-pages.reset') }}?id=" + ID,
        write: "{{ route('anisenso-site-pages.write') }}?id=" + ID,
        writeState: "{{ route('anisenso-site-pages.write-state') }}?id=" + ID,
        keywords: "{{ route('anisenso-site-pages.keywords') }}?id=" + ID,
        token: "{{ route('anisenso-site-pages.token') }}",
        site: @json(rtrim((string) config('anisystem.url'), '/')),
    };
    const KINDS = @json(collect($kinds)->map(fn ($v) => $v[0]));
    const $ = (s, r = document) => r.querySelector(s);
    const $$ = (s, r = document) => [...r.querySelectorAll(s)];
    const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    let PAGE = null, PREVIEW = @json($preview), sel = -1, dirty = false, shut = new Set();

    // ---------- the addresses every link field can pick from ----------
    const URLS = ['/', '/features', '/pricing', '/about', '/tutorial', '/contact', '/signup', '/crops', '/problems', '/pests', '/diseases', '/weeds', '/blog'];

    // ---------- fields per kind ----------
    const inp = (i, k, v, ph = '', attrs = '') => `<input class="form-control form-control-sm" data-i="${i}" data-k="${k}" value="${esc(v)}" placeholder="${esc(ph)}" ${attrs}>`;
    const area = (i, k, v, rows = 4, ph = '') => `<div class="tool"><button type="button" class="btn btn-light" data-fmt="b" data-i="${i}" data-k="${k}"><b>B</b></button><button type="button" class="btn btn-light" data-fmt="a" data-i="${i}" data-k="${k}"><i class="bx bx-link"></i> Link</button></div>
        <textarea class="form-control form-control-sm" rows="${rows}" data-i="${i}" data-k="${k}" placeholder="${esc(ph)}">${esc(v)}</textarea>`;
    const sub = (i, j, f, v, ph = '', big = false, list = false) => big
        ? `<textarea class="form-control form-control-sm ${list ? '' : ''}" rows="3" data-i="${i}" data-j="${j}" data-f="${f}" placeholder="${esc(ph)}">${esc(v)}</textarea>`
        : `<input class="form-control form-control-sm" data-i="${i}" data-j="${j}" data-f="${f}" value="${esc(v)}" placeholder="${esc(ph)}" ${f === 'url' ? 'list="spbUrls"' : ''}>`;
    const del = (i, j) => `<button type="button" class="btn btn-sm btn-light" data-rm="${i}:${j}" title="Remove"><i class="bx bx-x"></i></button>`;

    function fields(b, i) {
        switch (b.type) {
            case 'heading':
                return `<div class="d-flex gap-2"><select class="form-select form-select-sm" style="width:6rem" data-i="${i}" data-k="level">
                    <option value="2" ${+b.level !== 3 ? 'selected' : ''}>H2</option><option value="3" ${+b.level === 3 ? 'selected' : ''}>H3</option></select>
                    ${inp(i, 'text', b.text, 'The section heading')}</div>`;
            case 'text':
                return area(i, 'text', b.text, 6, 'Write the section. A blank line starts a new paragraph.');
            case 'list':
                return `<div class="form-check"><input class="form-check-input" type="checkbox" id="o${i}" data-i="${i}" data-k="ordered" ${b.ordered ? 'checked' : ''}><label class="form-check-label" for="o${i}">Numbered</label></div>
                    <label>One item per line</label>${area(i, 'items', (b.items || []).join('\n'), 5)}`;
            case 'steps':
                return `<div class="rows">${(b.items || []).map((it, j) => `<div class="row2 v"><div class="d-grid gap-1">${sub(i, j, 'title', it.title, 'Step title')}${sub(i, j, 'text', it.text, 'What to do', true)}</div>${del(i, j)}</div>`).join('')}</div>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-addrow="${i}"><i class="bx bx-plus"></i> Add a step</button>`;
            case 'table':
                return `${inp(i, 'caption', b.caption, 'Caption under the table (optional)')}
                    <label>One row per line, cells split with | . The first row is the header.</label>
                    <textarea class="form-control form-control-sm" rows="6" data-i="${i}" data-k="rows" style="font-family:var(--bs-font-monospace)">${esc((b.rows || []).map((r) => r.join(' | ')).join('\n'))}</textarea>`;
            case 'callout':
                return `<div class="d-flex gap-2"><select class="form-select form-select-sm" style="width:8rem" data-i="${i}" data-k="tone">
                    ${['tip', 'warn', 'info'].map((t) => `<option value="${t}" ${b.tone === t ? 'selected' : ''}>${{ tip: 'Tip', warn: 'Warning', info: 'Note' }[t]}</option>`).join('')}</select>
                    ${inp(i, 'title', b.title, 'Title')}</div>${area(i, 'text', b.text, 3)}`;
            case 'image':
                return `<div class="d-flex gap-2 align-items-start flex-wrap"><img class="thumb" src="${esc(img(b.src))}" alt="">
                    <div class="flex-grow-1 d-grid gap-1" style="min-width:12rem"><div class="input-group input-group-sm">${inp(i, 'src', b.src, '/images/site/... or a full address')}
                    <button class="btn btn-outline-primary" type="button" data-up="${i}"><i class="bx bx-upload"></i></button></div>
                    ${inp(i, 'alt', b.alt, 'Alt text: what it shows')}${inp(i, 'caption', b.caption, 'Caption (optional)')}</div></div>`;
            case 'quote':
                return `${area(i, 'text', b.text, 3)}${inp(i, 'cite', b.cite, 'Who said it (optional)')}`;
            case 'faq':
                return `<div class="rows">${(b.items || []).map((it, j) => `<div class="row2 v"><div class="d-grid gap-1">${sub(i, j, 'q', it.q, 'The question')}${sub(i, j, 'a', it.a, 'The answer', true)}</div>${del(i, j)}</div>`).join('')}</div>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-addrow="${i}"><i class="bx bx-plus"></i> Add a question</button>`;
            case 'cta':
                return `${inp(i, 'title', b.title, 'e.g. Track every bag of fertilizer')}${area(i, 'text', b.text, 2)}
                    <div class="d-flex gap-2">${inp(i, 'label', b.label, 'Button words, e.g. Start free')}${inp(i, 'url', b.url, '/signup', 'list="spbUrls"')}</div>`;
            case 'links':
            case 'sources':
                return `${b.type === 'links' ? inp(i, 'title', b.title, 'e.g. Related guides') : ''}
                    <div class="rows">${(b.items || []).map((it, j) => `<div class="row2">${sub(i, j, 'label', it.label, b.type === 'sources' ? 'e.g. PhilRice: Rice Crop Manager' : 'Link words')}${sub(i, j, 'url', it.url, b.type === 'sources' ? 'https://...' : '/crops/palay')}${del(i, j)}</div>`).join('')}</div>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-addrow="${i}"><i class="bx bx-plus"></i> Add a link</button>`;
            case 'divider':
                return '<p class="text-secondary small mb-0">A line across the page.</p>';
        }
        return '';
    }
    function summary(b) {
        const t = (s) => String(s || '').replace(/\*\*|\[|\]\([^)]*\)/g, '');
        switch (b.type) {
            case 'heading': return 'H' + (b.level || 2) + ' · ' + t(b.text);
            case 'text': return t(b.text).slice(0, 90);
            case 'list': case 'steps': case 'faq': case 'links': case 'sources': return (b.items || []).length + ' item(s)' + (b.title ? ' · ' + t(b.title) : '');
            case 'table': return (b.rows || []).length + ' row(s)';
            case 'callout': case 'cta': return t(b.title);
            case 'image': return t(b.alt || b.src);
            case 'quote': return t(b.text).slice(0, 80);
        }
        return '';
    }
    const img = (s) => !s ? '' : (/^https?:/.test(s) ? s : U.site + s);
    const seed = (k) => ({ heading: { level: 2, text: '' }, text: { text: '' }, list: { ordered: false, items: [] }, steps: { items: [{ title: '', text: '' }] },
        table: { caption: '', rows: [['Column', 'Column'], ['', '']] }, callout: { tone: 'tip', title: '', text: '' }, image: { src: '', alt: '', caption: '' },
        quote: { text: '', cite: '' }, faq: { items: [{ q: '', a: '' }] }, cta: { title: 'Run your farm on anee.io', text: '', label: 'Start free', url: '/signup' },
        links: { title: 'Related guides', items: [{ label: '', url: '' }] }, sources: { items: [{ label: '', url: '' }] }, divider: {} }[k] || {});

    // ---------- drawing ----------
    function draw(focusNew) {
        const list = $('#spbBlocks');
        if (!PAGE.blocks.length) {
            list.innerHTML = '<div class="empty" data-drop="0"><i class="bx bx-layer"></i> Nothing here yet. Click or drag a piece from above.</div>';
            return;
        }
        list.innerHTML = PAGE.blocks.map((b, i) => `<div class="drop" data-drop="${i}"></div>
            <div class="b ${sel === i ? 'is-sel' : ''} ${shut.has(b) ? 'is-shut' : ''} ${focusNew === i ? 'is-new' : ''}" data-b="${i}">
                <div class="bh" data-head="${i}">
                    <i class="bx bx-grid-vertical grip" draggable="true" data-grip="${i}" title="Drag to move"></i>
                    <span class="kind">${esc(KINDS[b.type] || b.type)}</span><span class="sum">${esc(summary(b))}</span>
                    <button type="button" class="btn btn-sm btn-light" data-act="up" title="Up"><i class="bx bx-chevron-up"></i></button>
                    <button type="button" class="btn btn-sm btn-light" data-act="down" title="Down"><i class="bx bx-chevron-down"></i></button>
                    <button type="button" class="btn btn-sm btn-light" data-act="dup" title="Duplicate"><i class="bx bx-copy"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-act="del" title="Remove"><i class="bx bx-trash"></i></button>
                </div>
                <div class="bb">${fields(b, i)}</div>
            </div>`).join('') + `<div class="drop" data-drop="${PAGE.blocks.length}"></div>`;
        if (focusNew !== undefined) {
            const el = list.querySelector(`[data-b="${focusNew}"]`);
            el?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            el?.querySelector('input, textarea')?.focus({ preventScroll: true });
        }
    }
    function fillPage() {
        $$('[data-f]', $('.view[data-view="page"]')).forEach((el) => {
            const f = el.dataset.f;
            const v = f.startsWith('hero.') ? (PAGE.heroImage || {})[f.slice(5)] : (f === 'keywords' ? (PAGE.keywords || []).join(', ') : (f === 'showIn' ? (PAGE.showIn || 'both') : PAGE[f]));
            el.value = v ?? '';
        });
        paintPageBits();
    }
    function paintPageBits() {
        $('#spbName').textContent = PAGE.title || 'Untitled';
        const path = (PAGE.section === 'questions' ? 'question' : PAGE.section) + '/' + PAGE.slug;
        $('#spbUrl').textContent = U.site + '/' + path;
        $('#spbShowInRow').hidden = PAGE.section !== 'blog';
        $('#spbHeroImg').src = img((PAGE.heroImage || {}).src) || '';
        const mt = (PAGE.metaTitle || '').length, md = (PAGE.metaDescription || '').length;
        $('#cMt').textContent = mt + ' / 60'; $('#cMt').className = 'count ' + (mt && mt <= 60 ? 'ok' : 'bad');
        $('#cMd').textContent = md + ' (120 to 156)'; $('#cMd').className = 'count ' + (md >= 120 && md <= 156 ? 'ok' : 'bad');
        $('#sU').textContent = 'anee.io › ' + PAGE.section + ' › ' + PAGE.slug;
        $('#sT').textContent = (PAGE.metaTitle || PAGE.title || '') + ' | anee.io';
        $('#sD').textContent = PAGE.metaDescription || '';
        $('#spbLiveLabel').textContent = PAGE.status === 'published' ? 'Live' : 'Draft';
        $('#spbLive').checked = PAGE.status === 'published';
    }

    // ---------- changes ----------
    function changed(redrawList) {
        dirty = true;
        $('#spbDirty').classList.add('is-on');
        if (redrawList) draw();
        paintPageBits();
        check();
        schedulePreview();
    }
    const blocksEl = $('#spbBlocks');
    blocksEl.addEventListener('input', (e) => {
        const el = e.target, i = +el.dataset.i;
        if (Number.isNaN(i) || !PAGE.blocks[i]) return;
        const b = PAGE.blocks[i];
        if (el.dataset.j !== undefined) {
            b.items[+el.dataset.j][el.dataset.f] = el.value;
        } else if (el.dataset.k === 'items') {
            b.items = el.value.split('\n').map((s) => s.trim()).filter(Boolean);
        } else if (el.dataset.k === 'rows') {
            b.rows = el.value.split('\n').filter((l) => l.trim()).map((l) => l.split('|').map((c) => c.trim()));
        } else if (el.dataset.k === 'ordered') {
            b.ordered = el.checked;
        } else if (el.dataset.k === 'level') {
            b.level = +el.value;
        } else {
            b[el.dataset.k] = el.value;
            if (el.dataset.k === 'src') { const t = el.closest('.bb').querySelector('.thumb'); if (t) t.src = img(el.value); }
        }
        const s = el.closest('.b')?.querySelector('.sum');
        if (s) s.textContent = summary(b);
        changed(false);
    });
    blocksEl.addEventListener('click', (e) => {
        const card = e.target.closest('.b');
        const i = card ? +card.dataset.b : -1;
        const act = e.target.closest('[data-act]')?.dataset.act;
        if (act) {
            e.stopPropagation();
            const B = PAGE.blocks;
            if (act === 'up' && i > 0) { [B[i - 1], B[i]] = [B[i], B[i - 1]]; sel = i - 1; }
            if (act === 'down' && i < B.length - 1) { [B[i + 1], B[i]] = [B[i], B[i + 1]]; sel = i + 1; }
            if (act === 'dup') { B.splice(i + 1, 0, JSON.parse(JSON.stringify(B[i]))); sel = i + 1; }
            if (act === 'del') {
                if (!confirm('Remove this ' + (KINDS[B[i].type] || 'block').toLowerCase() + '?')) return;
                card.style.opacity = '0';
                setTimeout(() => { B.splice(i, 1); sel = Math.min(sel, B.length - 1); changed(true); }, 180);
                return;
            }
            changed(true);
            return;
        }
        const addrow = e.target.closest('[data-addrow]');
        if (addrow) {
            const b = PAGE.blocks[+addrow.dataset.addrow];
            b.items = b.items || [];
            b.items.push(b.type === 'steps' ? { title: '', text: '' } : (b.type === 'faq' ? { q: '', a: '' } : { label: '', url: '' }));
            changed(true);
            return;
        }
        const rm = e.target.closest('[data-rm]');
        if (rm) {
            const [bi, j] = rm.dataset.rm.split(':').map(Number);
            PAGE.blocks[bi].items.splice(j, 1);
            changed(true);
            return;
        }
        const fmt = e.target.closest('[data-fmt]');
        if (fmt) {
            const ta = fmt.closest('.bb').querySelector(`textarea[data-k="${fmt.dataset.k}"]`);
            if (!ta) return;
            const [s, en] = [ta.selectionStart, ta.selectionEnd];
            const picked = ta.value.slice(s, en) || (fmt.dataset.fmt === 'b' ? 'bold words' : 'link words');
            let put = '**' + picked + '**';
            if (fmt.dataset.fmt === 'a') {
                const to = prompt('Link to (e.g. /crops/palay or https://...)', '/');
                if (!to) return;
                put = '[' + picked + '](' + to + ')';
            }
            ta.setRangeText(put, s, en, 'end');
            ta.dispatchEvent(new Event('input', { bubbles: true }));
            ta.focus();
            return;
        }
        const head = e.target.closest('[data-head]');
        if (head && !e.target.closest('[data-grip]')) {
            const b = PAGE.blocks[i];
            if (sel === i) { shut.has(b) ? shut.delete(b) : shut.add(b); card.classList.toggle('is-shut'); }
            sel = i;
            $$('.b', blocksEl).forEach((x) => x.classList.toggle('is-sel', +x.dataset.b === i));
            return;
        }
        if (card && sel !== i) { sel = i; $$('.b', blocksEl).forEach((x) => x.classList.toggle('is-sel', +x.dataset.b === i)); }
    });

    // ---------- adding and dragging ----------
    function add(kind, at) {
        const b = Object.assign({ type: kind }, JSON.parse(JSON.stringify(seed(kind))));
        at = at ?? (sel >= 0 ? sel + 1 : PAGE.blocks.length);
        PAGE.blocks.splice(at, 0, b);
        sel = at;
        dirty = true;
        $('#spbDirty').classList.add('is-on');
        draw(at);
        check();
        schedulePreview();
    }
    $('#spbPal').addEventListener('click', (e) => { const b = e.target.closest('[data-kind]'); if (b) add(b.dataset.kind); });
    let drag = null;   // { kind } from the palette, or { from } a block
    $('#spbPal').addEventListener('dragstart', (e) => { const b = e.target.closest('[data-kind]'); if (!b) return; drag = { kind: b.dataset.kind }; e.dataTransfer.effectAllowed = 'copy'; e.dataTransfer.setData('text/plain', b.dataset.kind); });
    blocksEl.addEventListener('dragstart', (e) => {
        const g = e.target.closest('[data-grip]');
        if (!g) return;
        drag = { from: +g.dataset.grip };
        g.closest('.b').classList.add('is-drag');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', 'block');
        e.dataTransfer.setDragImage(g.closest('.b'), 20, 20);
    });
    const dropAt = (e) => {
        const d = e.target.closest('[data-drop]');
        if (d) return +d.dataset.drop;
        const card = e.target.closest('.b');
        if (!card) return null;
        const r = card.getBoundingClientRect();
        return +card.dataset.b + (e.clientY > r.top + r.height / 2 ? 1 : 0);
    };
    blocksEl.addEventListener('dragover', (e) => {
        if (!drag) return;
        e.preventDefault();
        const at = dropAt(e);
        $$('.drop', blocksEl).forEach((d) => d.classList.toggle('is-on', +d.dataset.drop === at));
    });
    blocksEl.addEventListener('drop', (e) => {
        if (!drag) return;
        e.preventDefault();
        const at = dropAt(e);
        $$('.drop', blocksEl).forEach((d) => d.classList.remove('is-on'));
        if (at === null) { drag = null; return; }
        if (drag.kind) { add(drag.kind, at); }
        else if (drag.from !== at && drag.from + 1 !== at) {
            const [b] = PAGE.blocks.splice(drag.from, 1);
            const to = at > drag.from ? at - 1 : at;
            PAGE.blocks.splice(to, 0, b);
            sel = to;
            changed(true);
        }
        drag = null;
    });
    document.addEventListener('dragend', () => { drag = null; $$('.b.is-drag').forEach((x) => x.classList.remove('is-drag')); $$('.drop').forEach((d) => d.classList.remove('is-on')); });

    // ---------- page fields ----------
    $('.view[data-view="page"]').addEventListener('input', (e) => {
        const f = e.target.dataset.f;
        if (!f) return;
        if (f.startsWith('hero.')) { PAGE.heroImage = PAGE.heroImage || {}; PAGE.heroImage[f.slice(5)] = e.target.value; }
        else if (f === 'keywords') PAGE.keywords = e.target.value.split(',').map((s) => s.trim()).filter(Boolean);
        else PAGE[f] = e.target.value;
        changed(false);
    });
    $('#spbLive').addEventListener('change', (e) => { PAGE.status = e.target.checked ? 'published' : 'draft'; changed(false); });

    // ---------- pictures ----------
    let upFor = null;
    document.addEventListener('click', (e) => { const u = e.target.closest('[data-up]'); if (u && e.target.closest('#spb')) { upFor = u.dataset.up; $('#spbFile').value = ''; $('#spbFile').click(); } });
    $('#spbFile').addEventListener('change', async (e) => {
        const f = e.target.files[0];
        if (!f || upFor === null) return;
        const fd = new FormData(); fd.append('file', f);
        toastr.info('Uploading…');
        try {
            const res = await fetch(U.upload, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' }, body: fd });
            const j = await res.json();
            if (!j.success) throw new Error(j.message);
            if (upFor === 'hero') { PAGE.heroImage = PAGE.heroImage || {}; PAGE.heroImage.src = j.url; fillPage(); }
            else PAGE.blocks[+upFor].src = j.url;
            changed(upFor !== 'hero');
            toastr.success('Picture added. Say what it shows in the alt text.');
        } catch (err) { toastr.error(err.message || 'That picture did not upload.'); }
    });

    // ---------- the live preview ----------
    let pvT = null;
    function schedulePreview() { clearTimeout(pvT); pvT = setTimeout(sendPreview, 650); }
    async function sendPreview() {
        if (PREVIEW.expires - 60 < Date.now() / 1000) {
            try { PREVIEW = (await (await fetch(U.token, { headers: { Accept: 'application/json' } })).json()).preview; } catch (_) {}
        }
        const form = $('#spbPv');
        form.action = PREVIEW.url;
        form.querySelector('textarea').value = JSON.stringify(PAGE);
        form.submit();
    }
    $('#spbRefresh').addEventListener('click', sendPreview);
    let DEV = '';
    function fit() {
        const wrap = $('#spbWrap'), fr = $('#spbFrame');
        const W = wrap.clientWidth, H = wrap.clientHeight;
        if (!W) return;
        const want = { '': 1280, tablet: 820, phone: 390 }[DEV];
        const s = Math.min(1, W / want);
        fr.style.width = want + 'px';
        fr.style.height = Math.ceil(H / s) + 'px';
        fr.style.transform = `translateX(${Math.max(0, (W - want * s) / 2)}px) scale(${s})`;
        $('#spbScale').textContent = s < 1 ? Math.round(s * 100) + '%' : '';
    }
    window.addEventListener('resize', fit);
    $('#spbDev').addEventListener('click', (e) => {
        const b = e.target.closest('[data-dev]');
        if (!b) return;
        $$('#spbDev .btn').forEach((x) => x.classList.toggle('active', x === b));
        DEV = b.dataset.dev;
        fit();
    });

    // ---------- the SEO check ----------
    // the list anee.io's database/site-pages/check.py holds
    const BANNED = [
        'by paying attention', 'in summary', 'smooth experience', 'daunting task', 'in this article', 'in conclusion', 'break the bank',
        'breaking the bank', "today's digital age", 'moreover', 'homework', 'furthermore', 'additionally', 'lastly', 'in addition', 'therefore',
        'ultimately', 'informed decision', 'dive in', 'dive into', 'delve', 'fascinating world', 'performing your research', 'doing your research',
        'explore the world of', 'consequently', 'utilize', 'utilise', 'implement', 'in order to', 'pertaining to', 'regarding', 'subsequently',
        'thus', 'facilitate', 'prior to', 'in the event of', 'owing to', 'in light of', 'on the contrary', 'in the midst of', 'despite',
        'in accordance with', 'with regard to', 'subsequent to', 'commence', 'endeavor', 'endeavour', 'in lieu of', 'notwithstanding',
        'in conjunction with', 'landscape', 'realm', 'navigating', 'tailored', 'underpins', 'unveil', 'transformative', 'encompass', 'dynamic',
        'world', 'ecosystem', 'confluence', 'engaging', 'quest', 'solutions', 'solution', 'delving', 'significant', 'significantly', 'specific',
        'specifically', 'numerous', 'unsatisfied', 'craft', 'glean', 'glance', 'enhancing', 'unlock', 'seamless', 'robust', 'leverage', 'elevate',
        'harness', 'comprehensive', 'game changer', 'boasts', 'a testament', 'treasure trove', 'nestled', 'vibrant', "whether you're",
        'look no further', 'embark', 'journey', 'tapestry', 'intricate', 'pivotal', 'crucial', 'vital', 'paramount', 'meticulous', 'bustling',
        'foster', 'empower', 'streamline', 'cutting edge', 'state of the art', "in today's", "it's worth noting", 'it is important to note',
        'when it comes to', 'at the end of the day', 'plays a key role', 'a key role', 'navigate'];
    const TRANS = ['also', 'but', 'so', 'because', 'for example', 'for instance', 'first', 'next', 'then', 'after', 'finally', 'besides', 'instead', 'in fact',
        'as a result', 'that is why', 'even so', 'still', 'while', 'since', 'before', 'when', 'if', 'although', 'though', 'yet', 'or', 'once', 'until', 'unless'];
    const flat = (s) => String(s || '').replace(/\[([^\]]+)\]\([^)]*\)/g, '$1').replace(/\*\*/g, '');
    const words = (s) => (flat(s).match(/[A-Za-zÀ-ÿñÑ0-9']+/g) || []);
    const norm = (w) => { w = w.toLowerCase(); return w.length > 3 && w.endsWith('s') && !w.endsWith('ss') ? w.slice(0, -1) : w; };
    const has = (text, kp) => { const have = new Set(words(text).map(norm)); const need = words(kp).map(norm); return need.length && need.every((w) => have.has(w)); };
    const sentences = (p) => flat(p).split(/(?<=[.!?])\s+(?=[A-Z0-9"'])/).filter((s) => s.trim());
    function check() {
        const P = PAGE, kp = (P.focusKeyword || '').trim(), en = P.lang !== 'tl', out = [];
        const add = (lvl, say, why) => out.push({ lvl, say, why });
        const B = P.blocks || [];
        const paras = [P.excerpt || ''];
        const heads = [];
        let body = P.excerpt || '';
        const links = [], outbound = [];
        B.forEach((b) => {
            if (b.type === 'text') { paras.push(...String(b.text || '').split(/\n\s*\n/)); body += ' ' + b.text; }
            if (b.type === 'heading') { heads.push(b.text || ''); body += ' ' + b.text; }
            if (['list', 'steps', 'faq', 'links', 'sources'].includes(b.type)) (b.items || []).forEach((it) => { body += ' ' + (typeof it === 'string' ? it : Object.values(it).join(' ')); });
            if (b.type === 'table') (b.rows || []).forEach((r) => { body += ' ' + r.join(' '); });
            if (['callout', 'cta', 'quote'].includes(b.type)) body += ' ' + (b.title || '') + ' ' + (b.text || '');
            const raw = JSON.stringify(b);
            (raw.match(/\]\((\/[^)\s]*)\)/g) || []).forEach((m) => links.push(m));
            if (b.type === 'sources') (b.items || []).forEach((it) => /^https?:/.test(it.url || '') && outbound.push(it.url));
            if (b.type === 'links' || b.type === 'cta') links.push(...(b.items || []).map((x) => x.url).concat(b.url ? [b.url] : []).filter((u) => (u || '').startsWith('/')));
        });
        const n = words(body).length;
        const min = P.section === 'features' ? 600 : 900;
        if (!kp) add('bad', 'No focus keyphrase', 'Set one in Page and SEO: the search this page should win.');
        else {
            add(has(P.title, kp) ? 'good' : 'bad', 'Keyphrase in the title (H1)');
            const mtStart = words(P.metaTitle || '').slice(0, words(kp).length + 2).join(' ');
            add(has(P.metaTitle, kp) ? (has(mtStart, kp) ? 'good' : 'ok') : 'bad', 'Keyphrase in the SEO title', has(P.metaTitle, kp) && !has(mtStart, kp) ? 'Better near the start.' : '');
            add(has(P.metaDescription, kp) ? 'good' : 'bad', 'Keyphrase in the meta description');
            add(has(P.excerpt, kp) ? 'good' : 'bad', 'Keyphrase in the intro');
            add(heads.some((h) => has(h, kp)) ? 'good' : 'bad', 'Keyphrase in a subheading');
            const ws = words(body).map(norm), kw = words(kp).map(norm);
            let occ = 0; for (let i = 0; i + kw.length <= ws.length; i++) if (kw.every((w, j) => ws[i + j] === w)) occ++;
            const dens = n ? 100 * occ * kw.length / n : 0;
            add(dens >= 0.5 && dens <= 3 ? 'good' : 'ok', 'Keyphrase density ' + dens.toFixed(1) + ' percent', occ + ' use(s). Aim for 0.5 to 3.');
        }
        const mt = (P.metaTitle || '').length, md = (P.metaDescription || '').length;
        add(mt && mt <= 60 ? 'good' : 'bad', 'SEO title length ' + mt, '60 characters at most.');
        add(md >= 120 && md <= 156 ? 'good' : (md ? 'ok' : 'bad'), 'Meta description length ' + md, '120 to 156 characters.');
        add(n >= min ? 'good' : (n >= min * 0.6 ? 'ok' : 'bad'), n + ' words', min + ' at least for this kind of page.');
        const inl = new Set(links.map((l) => l.replace(/^\]\(|\)$/g, '')));
        add(inl.size >= 3 ? 'good' : 'bad', inl.size + ' internal link(s)', 'Link to 3 or more other pages of the site.');
        add(outbound.length ? 'good' : (P.section === 'features' ? 'ok' : 'bad'), outbound.length + ' outbound source(s)', 'A Sources block with an authority link.');
        add(B.some((b) => b.type === 'cta') ? 'good' : 'bad', B.some((b) => b.type === 'cta') ? 'Promotes anee.io' : 'No anee.io call to action');
        add(B.some((b) => b.type === 'faq') ? 'good' : 'ok', B.some((b) => b.type === 'faq') ? 'Has an FAQ (rich results)' : 'No FAQ block');
        const noAlt = B.filter((b) => b.type === 'image' && b.src && !(b.alt || '').trim()).length + (P.heroImage?.src && !(P.heroImage.alt || '').trim() ? 1 : 0);
        add(noAlt ? 'bad' : 'good', noAlt ? noAlt + ' picture(s) without alt text' : 'Every picture has alt text');
        const longP = paras.filter((p) => words(p).length > 150).length;
        add(longP ? 'bad' : 'good', longP ? longP + ' paragraph(s) over 150 words' : 'Paragraphs are short');
        let run = 0, over = false;
        B.forEach((b) => { if (b.type === 'heading') run = 0; else if (b.type === 'text') { run += words(b.text).length; if (run > 330) over = true; } });
        add(over ? 'ok' : 'good', over ? 'A stretch of over 300 words with no subheading' : 'Subheadings are spread well');
        if (en) {
            const S = paras.flatMap(sentences);
            const long = S.filter((s) => words(s).length > 20).length;
            add(S.length && long / S.length > 0.25 ? 'ok' : 'good', long + ' of ' + S.length + ' sentences over 20 words', 'A quarter at most.');
            const tr = S.filter((s) => TRANS.some((t) => new RegExp('(^|[^a-z])' + t + '([^a-z]|$)').test(s.toLowerCase()))).length;
            add(S.length && tr / S.length < 0.3 ? 'ok' : 'good', 'Transition words in ' + tr + ' of ' + S.length + ' sentences', '30 percent at least.');
        }
        const all = [P.title, P.metaTitle, P.metaDescription, P.excerpt, JSON.stringify(B.map((b) => { const c = Object.assign({}, b); delete c.url; delete c.src; return c; }))].join(' ').toLowerCase()
            .replace(/\]\([^)]*\)/g, ']').replace(/"url":"[^"]*"/g, '').replace(/https?:\/\/\S+/g, '');
        const found = BANNED.filter((w) => new RegExp('(^|[^a-z])' + w.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '([^a-z]|$)').test(all));
        add(found.length ? 'bad' : 'good', found.length ? 'Banned words: ' + found.join(', ') : 'No banned words');
        const dash = /[—–;&%…‘’“”]|->|[a-z0-9]-[a-z0-9]|\s-\s/.test(all.replace(/\\n/g, ' '));
        add(dash ? 'bad' : 'good', dash ? 'Dashes, hyphens or special characters in the text' : 'No dashes or special characters');
        const good = out.filter((o) => o.lvl === 'good').length;
        $('#spbScore').textContent = good + '/' + out.length;
        $('#spbChecks').innerHTML = out.sort((a, b) => ({ bad: 0, ok: 1, good: 2 }[a.lvl] - { bad: 0, ok: 1, good: 2 }[b.lvl]))
            .map((o) => `<div class="${o.lvl}"><i class="d"></i><span>${esc(o.say)}${o.why ? `<small>${esc(o.why)}</small>` : ''}</span></div>`).join('');
    }

    // ---------- Write with Anee ----------
    let aiMode = 'new', aiPicked = new Set(), aiBackup = null, aiT0 = 0, aiTick = null, aiOpened = false;
    const post = async (url, body) => {
        const res = await fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json', 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
        const j = await res.json().catch(() => ({}));
        if (!res.ok || j.success === false) throw new Error(j.message || 'anee.io did not answer.');
        return j;
    };
    const getJ = async (url) => {
        const res = await fetch(url, { headers: { Accept: 'application/json' } });
        const j = await res.json().catch(() => ({}));
        if (!res.ok || j.success === false) { const e = new Error(j.message || 'anee.io did not answer.'); e.failed = (j.data || {}).status === 'failed'; throw e; }
        return j;
    };
    function aiOpen() {
        if (aiOpened) return;
        aiOpened = true;
        $('#aiTopic').value = PAGE.title || '';
        $('#aiFocus').value = PAGE.focusKeyword || '';
        $('#aiLang').value = PAGE.lang === 'tl' ? 'tl' : 'en';
        (PAGE.keywords || []).forEach((k) => aiPicked.add(k));
        // A page with words already on it is improved by default.
        const filled = (PAGE.blocks || []).length > 2;
        aiMode = filled ? 'improve' : 'new';
        $$('#aiMode [data-mode]').forEach((b) => b.classList.toggle('active', b.dataset.mode === aiMode));
        if ($('#aiTopic').value.trim()) aiFind();
    }
    async function aiFind() {
        const text = ($('#aiTopic').value + ' ' + $('#aiFocus').value).trim();
        if (!text) { toastr.info('Type the topic first.'); return; }
        $('#aiKws').innerHTML = '<span class="text-secondary small"><i class="bx bx-loader-alt bx-spin"></i> Finding keywords…</span>';
        try {
            const j = await getJ(U.keywords + '&text=' + encodeURIComponent(text));
            const list = (j.data || {}).keywords || [];
            $('#aiKwList').innerHTML = list.map((k) => `<option value="${esc(k.keyword)}">`).join('');
            const all = [...new Set([...aiPicked, ...list.map((k) => k.keyword)])];
            const vol = Object.fromEntries(list.map((k) => [k.keyword, k.volume]));
            $('#aiKws').innerHTML = all.length ? all.map((k) => `<button type="button" class="kw ${aiPicked.has(k) ? 'is-on' : ''}" data-kw="${esc(k)}">${esc(k)}${vol[k] ? `<small>${Number(vol[k]).toLocaleString()}</small>` : ''}</button>`).join('')
                : '<span class="text-secondary small">No keywords near that topic yet. Add some in SEO keywords.</span>';
        } catch (err) { $('#aiKws').innerHTML = `<span class="text-danger small">${esc(err.message)}</span>`; }
    }
    $('#aiFind').addEventListener('click', aiFind);
    $('#aiKws').addEventListener('click', (e) => {
        const b = e.target.closest('[data-kw]');
        if (!b) return;
        const k = b.dataset.kw;
        aiPicked.has(k) ? aiPicked.delete(k) : aiPicked.add(k);
        b.classList.toggle('is-on', aiPicked.has(k));
    });
    $('#aiMode').addEventListener('click', (e) => {
        const b = e.target.closest('[data-mode]');
        if (!b) return;
        aiMode = b.dataset.mode;
        $$('#aiMode [data-mode]').forEach((x) => x.classList.toggle('active', x === b));
    });
    const AI_PHASES = { start: ['Getting started', 2, 8, 12], research: ['Reading the web', 8, 50, 110], document: ['Writing the page', 50, 92, 90], 'document-json': ['Tidying the page', 92, 97, 30] };
    let aiPct = 0, aiPhase = 'start', aiPhaseAt = 0;
    function aiPaint() {
        const [label, lo, hi, tau] = AI_PHASES[aiPhase] || AI_PHASES.start;
        const t = (Date.now() - aiPhaseAt) / 1000;
        aiPct = Math.max(aiPct, lo + (hi - lo) * (1 - Math.exp(-t / tau)));
        $('#aiBar').style.width = Math.round(aiPct) + '%';
        $('#aiPhase').textContent = label + '…';
        const s = Math.round((Date.now() - aiT0) / 1000);
        $('#aiClock').textContent = Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
    }
    function aiApply(p) {
        aiBackup = JSON.parse(JSON.stringify(PAGE));
        ['title', 'metaTitle', 'metaDescription', 'focusKeyword', 'excerpt', 'lang'].forEach((k) => { if (p[k]) PAGE[k] = p[k]; });
        if (p.category && !PAGE.category) PAGE.category = p.category;
        if (Array.isArray(p.keywords)) PAGE.keywords = p.keywords;
        if (Array.isArray(p.blocks) && p.blocks.length) PAGE.blocks = p.blocks;
        // A live page keeps its address: moving it breaks every link to it.
        if (p.slug && PAGE.status !== 'published' && aiMode === 'new') PAGE.slug = p.slug;
        fillPage();
        changed(true);
        $('#aiDone').hidden = false;
    }
    $('#aiUndo').addEventListener('click', () => {
        if (!aiBackup) return;
        PAGE = aiBackup; aiBackup = null;
        fillPage(); changed(true);
        $('#aiDone').hidden = true;
        toastr.info('The page is back as it was.');
    });
    $('#aiGo').addEventListener('click', async () => {
        const topic = $('#aiTopic').value.trim();
        if (!topic) { toastr.error('Say what the page is about.'); $('#aiTopic').focus(); return; }
        if (aiMode === 'new' && (PAGE.blocks || []).length > 2 && !confirm('Anee writes a whole new page, and it replaces the words on this one (you can put them back). Go on?')) return;
        const go = $('#aiGo');
        go.disabled = true;
        $('#aiDone').hidden = true;
        $('#aiRun').hidden = false;
        aiT0 = Date.now(); aiPhaseAt = Date.now(); aiPhase = 'start'; aiPct = 0;
        clearInterval(aiTick); aiTick = setInterval(aiPaint, 700); aiPaint();
        try {
            let j = await post(U.write, {
                mode: aiMode, topic, focusKeyword: $('#aiFocus').value.trim() || null, keywords: [...aiPicked],
                lang: $('#aiLang').value, notes: $('#aiNotes').value.trim() || null, research: $('#aiResearch').checked,
                current: aiMode === 'improve' ? { title: PAGE.title, excerpt: PAGE.excerpt, metaTitle: PAGE.metaTitle, metaDescription: PAGE.metaDescription, focusKeyword: PAGE.focusKeyword, blocks: PAGE.blocks } : null,
            });
            let d = j.data || {};
            const job = d.id;
            for (let i = 0; d.status !== 'ready' && i < 200; i++) {
                await new Promise((r) => setTimeout(r, 4000));
                try { d = (await getJ(U.writeState + '&job=' + job)).data || {}; }
                catch (err) { if (err.failed) throw err; continue; }
                if (d.phase && d.phase !== aiPhase) { aiPhase = d.phase; aiPhaseAt = Date.now(); }
            }
            if (d.status !== 'ready' || !d.page) throw new Error('Anee is still writing. Try again in a minute.');
            aiPct = 100; aiPaint();
            aiApply(d.page);
            toastr.success('Anee wrote the page. Check it, then Save.');
        } catch (err) {
            toastr.error(err.message || 'Anee could not write that page.');
        } finally {
            clearInterval(aiTick);
            go.disabled = false;
            setTimeout(() => { $('#aiRun').hidden = true; }, 600);
        }
    });

    // ---------- tabs ----------
    $('#spbTabs').addEventListener('click', (e) => {
        const b = e.target.closest('[data-view]');
        if (!b) return;
        $$('#spbTabs button').forEach((x) => x.classList.toggle('is-on', x === b));
        $$('#spb .view').forEach((v) => v.classList.toggle('is-on', v.dataset.view === b.dataset.view));
        if (b.dataset.view === 'ai') aiOpen();
    });

    // ---------- saving ----------
    async function save() {
        const btn = $('#spbSave');
        btn.disabled = true;
        btn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Saving';
        try {
            const fd = new FormData(); fd.append('page', JSON.stringify(PAGE));
            const res = await fetch(U.save, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' }, body: fd });
            const j = await res.json().catch(() => ({}));
            if (!res.ok || !j.success) throw new Error(j.message || 'That did not save.');
            PAGE = j.page;
            dirty = false;
            $('#spbDirty').classList.remove('is-on');
            $('#spbOpen').href = j.liveUrl;
            fillPage();
            toastr.success(j.message);
        } catch (err) { toastr.error(err.message); }
        finally { btn.disabled = false; btn.innerHTML = '<i class="bx bx-save"></i> Save'; }
    }
    $('#spbSave').addEventListener('click', save);
    document.addEventListener('keydown', (e) => { if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') { e.preventDefault(); save(); } });
    window.addEventListener('beforeunload', (e) => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
    $('#spbReset')?.addEventListener('click', async () => {
        if (!confirm('Put back the version anee.io shipped? Your changes to this page are replaced.')) return;
        try {
            const res = await fetch(U.reset, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' } });
            const j = await res.json();
            if (!j.success) throw new Error(j.message);
            PAGE = j.page; dirty = false; $('#spbDirty').classList.remove('is-on');
            fillPage(); draw(); check(); sendPreview();
            toastr.success(j.message);
        } catch (err) { toastr.error(err.message); }
    });

    // ---------- open ----------
    fetch(U.data, { headers: { Accept: 'application/json' } }).then((r) => r.json()).then((j) => {
        PAGE = j.page;
        PAGE.heroImage = PAGE.heroImage || { src: '', alt: '', credit: '' };
        PREVIEW = j.preview || PREVIEW;
        $('#spbUrls').innerHTML = URLS.map((u) => `<option value="${u}">`).join('');
        fillPage(); draw(); check(); fit(); sendPreview();
    }).catch(() => toastr.error('Could not read the page.'));
})();
</script>
@endsection
