@extends('layouts.master')

@section('title') Landing page @endsection

@section('css')
<style>
    /* This page's styles load before Bootstrap's, so every rule is scoped
       under #lpEd to win its ties. Colours come from Bootstrap's own
       variables, so the admin's dark mode dresses it too. */
    /* Skote's .main-content hides its overflow, which makes it the scroll
       box that sticky measures against, so nothing sticks. Clip still cuts
       what spills, without being a scroll box. */
    .main-content:has(#lpEd) { overflow: clip; }
    #lpEd .lp-nav { position: sticky; top: 90px; }
    #lpEd .lp-nav a { display: flex; align-items: center; gap: .5rem; padding: .45rem .75rem; border-radius: .5rem; color: var(--bs-body-color);
        font-weight: 500; transition: background .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    #lpEd .lp-nav a:hover, #lpEd .lp-nav a.is-on { background: var(--bs-primary-bg-subtle); color: var(--bs-primary-text-emphasis); }
    #lpEd .lp-nav a small { margin-left: auto; color: var(--bs-secondary-color); }
    #lpEd .lp-sec { scroll-margin-top: 90px; }
    #lpEd .lp-sec .card-title { display: flex; align-items: center; gap: .5rem; }
    #lpEd .lp-sec .card-title .n { display: inline-grid; place-items: center; width: 1.6rem; height: 1.6rem; border-radius: 999px;
        background: var(--bs-primary-bg-subtle); color: var(--bs-primary-text-emphasis); font-size: .8rem; font-weight: 700; }
    #lpEd .lp-lead { color: var(--bs-secondary-color); margin-bottom: 1rem; }
    /* Repeatable rows */
    #lpEd .lp-list { counter-reset: lprow; display: grid; gap: .75rem; }
    #lpEd .lp-row { counter-increment: lprow; border: 1px solid var(--bs-border-color); border-radius: .75rem; padding: .85rem 1rem 1rem;
        background: var(--bs-tertiary-bg); }
    #lpEd .lp-row-head { display: flex; align-items: center; gap: .5rem; margin-bottom: .6rem; }
    #lpEd .lp-row-head b { font-weight: 600; }
    #lpEd .lp-row-head b::after { content: ' ' counter(lprow); }
    #lpEd .lp-row-tools { margin-left: auto; display: flex; gap: .25rem; }
    #lpEd .lp-row-tools .btn { --bs-btn-padding-y: .15rem; --bs-btn-padding-x: .45rem; line-height: 1; }
    #lpEd .lp-row:first-child [data-row-up], #lpEd .lp-row:last-child [data-row-down] { visibility: hidden; }
    #lpEd .lp-add[hidden] { display: none !important; }
    /* Picture slots */
    #lpEd .lp-pic { display: flex; gap: 1rem; align-items: flex-start; flex-wrap: wrap; }
    #lpEd .lp-pic-frame { flex: none; width: 9rem; aspect-ratio: 4 / 3; border-radius: .6rem; overflow: hidden; background: var(--bs-secondary-bg);
        border: 1px solid var(--bs-border-color); display: grid; place-items: center; }
    #lpEd .lp-pic-frame.is-phone { width: 5.5rem; aspect-ratio: 39 / 80; border-radius: .9rem; border: 3px solid #1f2937; }
    #lpEd .lp-pic-frame.is-face { width: 4.5rem; aspect-ratio: 1; border-radius: 999px; }
    #lpEd .lp-pic-frame img { width: 100%; height: 100%; object-fit: cover; display: block; }
    #lpEd .lp-pic-frame.is-phone img { object-position: top; }
    #lpEd .lp-pic-none { font-size: .7rem; text-align: center; color: var(--bs-secondary-color); line-height: 1.2; }
    #lpEd .lp-pic-side { flex: 1 1 14rem; min-width: 0; }
    #lpEd .lp-pic-state { margin-bottom: .4rem; }
    #lpEd .lp-pic [data-pic-state="upload"], #lpEd .lp-pic.has-upload [data-pic-state="builtin"] { display: none; }
    #lpEd .lp-pic.has-upload [data-pic-state="upload"] { display: inline-block; }
    #lpEd .lp-pic:not(.has-upload) .lp-pic-clear { display: none; }
    /* Tokens and the ad link */
    #lpEd .lp-tokens { display: grid; gap: .3rem; font-size: .85rem; }
    #lpEd .lp-tokens code { font-size: .8rem; }
    #lpEd .lp-link-out { font-family: var(--bs-font-monospace); font-size: .8rem; word-break: break-all; }
    /* The save bar stays in reach at the foot of the screen. */
    #lpEd .lp-savebar { position: sticky; bottom: 0; z-index: 5; display: flex; flex-wrap: wrap; align-items: center; gap: .75rem;
        padding: .8rem 1rem; margin-top: .5rem; border-radius: .75rem .75rem 0 0; background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color); box-shadow: 0 -10px 30px -18px rgb(0 0 0 / .35); }
    #lpEd .lp-dirty { font-size: .85rem; color: var(--bs-warning-text-emphasis); opacity: 0; transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    #lpEd .lp-dirty.is-on { opacity: 1; }
    @media (prefers-reduced-motion: reduce) { #lpEd .lp-nav a, #lpEd .lp-dirty { transition: none; } }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') AniSystem @endslot
        @slot('title') Landing page @endslot
    @endcomponent

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    @php
        $live = $base . '/ph/start';
        $shots = \App\Http\Controllers\aniSensoAdmin\AnisystemLandingController::SHOTS;
        $photos = \App\Http\Controllers\aniSensoAdmin\AnisystemLandingController::PHOTOS;
        $d = $defaults ?? [];
        $sections = [
            'meta' => ['Title & link preview', null],
            'hero' => ['Hero', 1], 'proof' => ['Proof strip', 2], 'problem' => ['The weather & 3 steps', 2],
            'costs' => ['Fuel & fertilizer', 2],
            'losses' => ['What guessing costs', null], 'precision' => ['Precision agriculture', null],
            'pillars' => ['Feature pillars', 4], 'more' => ['Everything in one app', null], 'testimonials' => ['Testimonials', 5],
            'faq' => ['Questions (FAQ)', 6], 'closer' => ['The closer', 7],
            'tracking' => ['Ad tracking', null], 'signups' => ['Signups from ads', null],
        ];
    @endphp

    <div id="lpEd">
    @if (! $page)
        <div class="card"><div class="card-body">
            <h4 class="card-title">The landing page's words are not here yet</h4>
            <p class="mb-3">anee.io publishes the page's default words the first time the page is drawn. Open it once, then come back to this page.</p>
            <a href="{{ $live }}" target="_blank" rel="noopener" class="btn btn-primary">Open the landing page</a>
        </div></div>
    @else
    <div class="row">
        <div class="col-xl-3 d-none d-xl-block">
            <nav class="lp-nav card"><div class="card-body p-2">
                @foreach ($sections as $id => [$label, $n])
                    {{-- A section anee's page no longer has (or does not have yet) is not listed. --}}
                    @continue(! in_array($id, ['meta', 'tracking', 'signups'], true) && ! isset($page[$id]))
                    <a href="#sec-{{ $id }}" data-nav="{{ $id }}">{{ $label }} @if ($n)<small>{{ $n }}</small>@endif</a>
                @endforeach
            </div></nav>
        </div>

        <div class="col-xl-9">
            {{-- What this page is, the live link, and the ad link maker. --}}
            <div class="card"><div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                    <div>
                        <h4 class="card-title mb-1">The ads landing page</h4>
                        <p class="text-secondary mb-0">The page your ads point to: <a href="{{ $live }}" target="_blank" rel="noopener">{{ preg_replace('#^https?://#', '', $live) }}</a>. Every word and picture on it is set here. Change a field and save; empty a field and it goes back to the default shown in grey.</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ $live }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm"><i class="bx bx-link-external"></i> View the page</a>
                        @if ($edited)
                            <form method="POST" action="{{ route('anisenso-landing.reset') }}" onsubmit="return confirm('Put the whole page back to its default words and pictures? Your edits, testimonials and uploaded pictures will be removed.');">
                                @csrf
                                <button class="btn btn-outline-danger btn-sm" type="submit"><i class="bx bx-reset"></i> Reset to defaults</button>
                            </form>
                        @endif
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-lg-6">
                        <h6 class="mb-2">Words that fill themselves in</h6>
                        <div class="lp-tokens">
                            @foreach (\App\Http\Controllers\aniSensoAdmin\AnisystemLandingController::TOKENS as $t => $says)
                                <div><code>{{ $t }}</code> <span class="text-secondary">{{ $says }}</span></div>
                            @endforeach
                        </div>
                        <div class="form-text">Type one anywhere; the page puts the real value in, so prices and counts never go stale.</div>
                    </div>
                    <div class="col-lg-6">
                        <h6 class="mb-2">Make a link for an ad</h6>
                        <div class="row g-2" id="lpLinkMaker" data-base="{{ $base }}">
                            <div class="col-6">
                                <select class="form-select form-select-sm" data-l="source" aria-label="Where the ad runs">
                                    <option value="facebook">Facebook</option><option value="instagram">Instagram</option>
                                    <option value="google">Google</option><option value="tiktok">TikTok</option>
                                    <option value="youtube">YouTube</option><option value="messenger">Messenger</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <select class="form-select form-select-sm" data-l="face" aria-label="Which face of the site">
                                    <option value="ph">Philippines</option><option value="en">International</option>
                                </select>
                            </div>
                            <div class="col-6"><input class="form-control form-control-sm" data-l="campaign" placeholder="Campaign, e.g. wet-season-2026" aria-label="Campaign"></div>
                            <div class="col-6"><input class="form-control form-control-sm" data-l="content" placeholder="Which ad, e.g. video-1" aria-label="Which ad"></div>
                            <div class="col-12 d-flex gap-2 align-items-center">
                                <div class="lp-link-out flex-grow-1 form-control form-control-sm bg-body-tertiary" data-l-out></div>
                                <button type="button" class="btn btn-sm btn-primary" data-l-copy>Copy</button>
                            </div>
                        </div>
                        <div class="form-text">The tags ride along to the signup, so each ad can be counted to the accounts it made.</div>
                    </div>
                </div>
            </div></div>

            <form method="POST" action="{{ route('anisenso-landing.save') }}" enctype="multipart/form-data" id="lpForm">
                @csrf

                {{-- ===== Title & link preview ===== --}}
                <div class="card lp-sec" id="sec-meta"><div class="card-body">
                    <h4 class="card-title">Title &amp; link preview</h4>
                    <p class="lp-lead">What the browser tab says, and the text under the link when it is shared or shown by a search engine.</p>
                    <div class="row g-3">
                        @include('aniSensoAdmin.landing.field', ['name' => 'meta[title]', 'label' => 'Page title', 'value' => $page['meta']['title'], 'default' => $d['meta']['title'], 'max' => 120])
                        @include('aniSensoAdmin.landing.field', ['name' => 'meta[description]', 'label' => 'Link preview text', 'value' => $page['meta']['description'], 'default' => 'Blank: the hero\'s sub-headline', 'type' => 'textarea', 'rows' => 2, 'max' => 300])
                    </div>
                </div></div>

                {{-- ===== 1. Hero ===== --}}
                <div class="card lp-sec" id="sec-hero"><div class="card-body">
                    <h4 class="card-title"><span class="n">1</span> Hero</h4>
                    <p class="lp-lead">The first screen: the promise, the email box and the phone.</p>
                    <div class="row g-3">
                        @include('aniSensoAdmin.landing.field', ['name' => 'hero[kicker]', 'label' => 'Small badge above the headline', 'value' => $page['hero']['kicker'], 'default' => $d['hero']['kicker'], 'col' => 'col-md-6'])
                        @include('aniSensoAdmin.landing.field', ['name' => 'hero[cta]', 'label' => 'Button', 'value' => $page['hero']['cta'], 'default' => $d['hero']['cta'], 'col' => 'col-md-6', 'max' => 60, 'help' => 'Also used by the middle button and the phone\'s bottom bar.'])
                        @include('aniSensoAdmin.landing.field', ['name' => 'hero[headline]', 'label' => 'Headline', 'value' => $page['hero']['headline'], 'default' => $d['hero']['headline'], 'max' => 160, 'help' => 'The benefit, in the farmer\'s words. Short wins. Wrap words in *stars* to mark them in yellow.'])
                        @include('aniSensoAdmin.landing.field', ['name' => 'hero[sub]', 'label' => 'Sub-headline', 'value' => $page['hero']['sub'], 'default' => $d['hero']['sub'], 'type' => 'textarea', 'rows' => 3])
                        @isset($page['hero']['align'])
                            <div class="col-md-6">
                                <label class="form-label" for="lpHeroAlign">Words beside the phone, on a computer</label>
                                <select class="form-select" name="hero[align]" id="lpHeroAlign">
                                    <option value="right" {{ $page['hero']['align'] !== 'left' ? 'selected' : '' }}>Right-aligned, against the phone</option>
                                    <option value="left" {{ $page['hero']['align'] === 'left' ? 'selected' : '' }}>Left-aligned</option>
                                </select>
                                <div class="form-text">On a phone the words always read from the left.</div>
                            </div>
                        @endisset
                        @include('aniSensoAdmin.landing.field', ['name' => 'hero[note]', 'label' => 'Ticks under the email box', 'value' => $page['hero']['note'], 'default' => $d['hero']['note'], 'help' => 'Each sentence (ending with a full stop) becomes one tick.'])
                        <div class="col-12">
                            <label class="form-label">The phone</label>
                            @include('aniSensoAdmin.landing.picture', ['prefix' => 'hero', 'field' => 'image', 'current' => $page['hero']['image'], 'builtIn' => $shots['board'][1], 'shape' => 'phone', 'base' => $base,
                                'hint' => 'A phone screenshot, tall (about 780 × 1600). JPG, PNG or WebP, up to 6 MB.'])
                        </div>
                        <div class="col-12">
                            <label class="form-label">The two cards floating beside the phone</label>
                            <div class="row g-2">
                                @foreach (array_slice(array_pad($page['hero']['chips'], 2, ['icon' => '', 'title' => '', 'sub' => '']), 0, 2) as $ci => $chip)
                                    <div class="col-2 col-md-1"><input class="form-control text-center" name="hero[chips][c{{ $ci }}][icon]" value="{{ $chip['icon'] ?? '' }}" maxlength="16" placeholder="🌱" aria-label="Card {{ $ci + 1 }} emoji"></div>
                                    <div class="col-10 col-md-5"><input class="form-control" name="hero[chips][c{{ $ci }}][title]" value="{{ $chip['title'] ?? '' }}" maxlength="80" placeholder="Card {{ $ci + 1 }} title (blank hides it)" aria-label="Card {{ $ci + 1 }} title"></div>
                                    <div class="col-12 col-md-6"><input class="form-control" name="hero[chips][c{{ $ci }}][sub]" value="{{ $chip['sub'] ?? '' }}" maxlength="80" placeholder="Card {{ $ci + 1 }} small line" aria-label="Card {{ $ci + 1 }} small line"></div>
                                @endforeach
                            </div>
                            <div class="form-text">They describe the phone's picture; change them with it. A card without a title is not shown.</div>
                        </div>
                    </div>
                </div></div>

                {{-- ===== 2. Proof (taken off the page 2026-09-30; kept while older defaults have it) ===== --}}
                @isset($page['proof'])
                <div class="card lp-sec" id="sec-proof"><div class="card-body">
                    <h4 class="card-title"><span class="n">2</span> Proof strip</h4>
                    <p class="lp-lead">The thin band right under the hero.</p>
                    <div class="row g-3">
                        @include('aniSensoAdmin.landing.field', ['name' => 'proof[lead]', 'label' => 'Line above the ticks', 'value' => $page['proof']['lead'], 'default' => $d['proof']['lead']])
                        @include('aniSensoAdmin.landing.field', ['name' => 'proof[items]', 'label' => 'Ticks, one per line', 'value' => implode("\n", $page['proof']['items']), 'type' => 'textarea', 'rows' => 4, 'max' => 2000])
                        <div class="col-md-6">
                            <label class="form-label" for="lpStats">The live count</label>
                            <select class="form-select" name="proof[stats]" id="lpStats">
                                <option value="show" {{ $page['proof']['stats'] !== 'hide' ? 'selected' : '' }}>Show "430+ farm activities planned so far, across 24 seasons"</option>
                                <option value="hide" {{ $page['proof']['stats'] === 'hide' ? 'selected' : '' }}>Hide it</option>
                            </select>
                            <div class="form-text">Counted from the app itself, rounded down.</div>
                        </div>
                    </div>
                </div></div>
                @endisset

                {{-- ===== 3. Problem & steps ===== --}}
                <div class="card lp-sec" id="sec-problem"><div class="card-body">
                    <h4 class="card-title"><span class="n">2</span> The weather &amp; 3 steps</h4>
                    <p class="lp-lead">The first problem row, under the hero: the weather nobody can predict and the pests and diseases it brings (photo on the left), then how anee.io helps. The three steps sit further down, after precision agriculture.</p>
                    <div class="row g-3">
                        @include('aniSensoAdmin.landing.field', ['name' => 'problem[kicker]', 'label' => 'Small line', 'value' => $page['problem']['kicker'], 'default' => $d['problem']['kicker'], 'col' => 'col-md-4'])
                        @include('aniSensoAdmin.landing.field', ['name' => 'problem[headline]', 'label' => 'Headline', 'value' => $page['problem']['headline'], 'default' => $d['problem']['headline'], 'col' => 'col-md-8'])
                        @include('aniSensoAdmin.landing.field', ['name' => 'problem[bullets]', 'label' => 'The pains, one per line', 'value' => implode("\n", $page['problem']['bullets']), 'type' => 'textarea', 'rows' => 4, 'max' => 2000])
                        @isset($page['problem']['fixes'])
                            @include('aniSensoAdmin.landing.field', ['name' => 'problem[fixes]', 'label' => 'How anee.io helps, one per line (green ticks)', 'help' => 'Keep each to about 36 characters: every tick shows on one line, even on a phone, and a longer one is cut short with …', 'value' => implode("\n", $page['problem']['fixes']), 'type' => 'textarea', 'rows' => 3, 'max' => 2000])
                        @endisset
                        <div class="col-12">
                            <label class="form-label">The photo</label>
                            @include('aniSensoAdmin.landing.picture', ['prefix' => 'problem', 'field' => 'image', 'current' => $page['problem']['image'], 'builtIn' => isset($page['costs']) ? 'lp/storm.webp' : 'lp/sacks.webp', 'base' => $base,
                                'hint' => 'A real photo, landscape (4:3 works best). JPG, PNG or WebP, up to 6 MB.'])
                        </div>
                        @include('aniSensoAdmin.landing.field', ['name' => 'problem[solutionKicker]', 'label' => 'Small line over the steps', 'value' => $page['problem']['solutionKicker'], 'default' => $d['problem']['solutionKicker'], 'col' => 'col-md-4'])
                        @include('aniSensoAdmin.landing.field', ['name' => 'problem[solutionHeadline]', 'label' => 'Headline over the steps', 'value' => $page['problem']['solutionHeadline'], 'default' => $d['problem']['solutionHeadline'], 'col' => 'col-md-8'])
                        <div class="col-12">
                            <label class="form-label">The steps</label>
                            <div class="lp-list" data-list="steps" data-max="4">
                                @foreach ($page['problem']['steps'] as $i => $st)
                                    @include('aniSensoAdmin.landing.row-step', ['k' => 's' . $i, 'st' => $st])
                                @endforeach
                            </div>
                            <template data-tpl="steps">@include('aniSensoAdmin.landing.row-step', ['k' => '__K__', 'st' => ['title' => '', 'text' => '']])</template>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2 lp-add" data-add="steps"><i class="bx bx-plus"></i> Add a step</button>
                        </div>
                    </div>
                </div></div>

                {{-- ===== Fuel & fertilizer: the cost rows after the weather ===== --}}
                @isset($page['costs'])
                <div class="card lp-sec" id="sec-costs"><div class="card-body">
                    <h4 class="card-title"><span class="n">2</span> Fuel &amp; fertilizer</h4>
                    <p class="lp-lead">The problem rows after the weather, one per rising cost. Their photos alternate sides: the first on the right, the next on the left, and so on. Each says what goes wrong, then how anee.io helps.</p>
                    <div class="row g-3 mb-2">
                        @include('aniSensoAdmin.landing.field', ['name' => 'costs[helpsLabel]', 'label' => 'The label over every green-tick list', 'value' => $page['costs']['helpsLabel'], 'default' => $d['costs']['helpsLabel'], 'col' => 'col-md-6', 'max' => 60])
                    </div>
                    <div class="lp-list" data-list="costs" data-max="4">
                        @foreach ($page['costs']['items'] as $i => $c)
                            @include('aniSensoAdmin.landing.row-cost', ['k' => 'c' . $i, 'c' => $c, 'photos' => $photos, 'base' => $base])
                        @endforeach
                    </div>
                    <template data-tpl="costs">@include('aniSensoAdmin.landing.row-cost', ['k' => '__K__', 'c' => ['kicker' => '', 'headline' => '', 'text' => '', 'fixes' => [], 'image' => 'tractor', 'upload' => ''], 'photos' => $photos, 'base' => $base])</template>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2 lp-add" data-add="costs"><i class="bx bx-plus"></i> Add a cost row</button>
                </div></div>
                @endisset

                {{-- ===== What guessing costs (the dark band of "up to" figures) =====
                     Both new sections are skipped while anee's published
                     defaults are an older page that has no such section. --}}
                @isset($page['losses'])
                <div class="card lp-sec" id="sec-losses"><div class="card-body">
                    <h4 class="card-title">What guessing costs</h4>
                    <p class="lp-lead">The dark band after the problem: how much of a harvest late, early or wrong work can cost, as "up to" figures that count up as they appear. Keep them defensible: say where the numbers come from in the note.</p>
                    <div class="row g-3 mb-2">
                        @include('aniSensoAdmin.landing.field', ['name' => 'losses[headline]', 'label' => 'Headline', 'value' => $page['losses']['headline'], 'default' => $d['losses']['headline']])
                        @isset($page['losses']['sub'])
                            @include('aniSensoAdmin.landing.field', ['name' => 'losses[sub]', 'label' => 'Line under it', 'value' => $page['losses']['sub'], 'default' => $d['losses']['sub'] ?? '', 'type' => 'textarea', 'rows' => 2])
                        @endisset
                    </div>
                    <div class="lp-list" data-list="losses" data-max="12">
                        @foreach ($page['losses']['items'] as $i => $l)
                            @include('aniSensoAdmin.landing.row-loss', ['k' => 'l' . $i, 'l' => $l])
                        @endforeach
                    </div>
                    <template data-tpl="losses">@include('aniSensoAdmin.landing.row-loss', ['k' => '__K__', 'l' => isset($page['losses']['sub']) ? ['n' => '', 'title' => '', 'text' => '', 'peso' => '', 'image' => 'palay-heads', 'upload' => ''] : ['n' => '', 'title' => '', 'text' => ''], 'base' => $base])</template>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2 lp-add" data-add="losses"><i class="bx bx-plus"></i> Add a figure</button>
                    <div class="row g-3 mt-1">
                        @include('aniSensoAdmin.landing.field', ['name' => 'losses[note]', 'label' => 'The source note under the figures', 'value' => $page['losses']['note'], 'default' => $d['losses']['note'], 'type' => 'textarea', 'rows' => 2])
                    </div>
                </div></div>
                @endisset

                {{-- ===== Precision agriculture: the four "rights" ===== --}}
                @isset($page['precision'])
                <div class="card lp-sec" id="sec-precision"><div class="card-body">
                    <h4 class="card-title">Precision agriculture</h4>
                    <p class="lp-lead">The answer to the problem, in the words precision agriculture is built on, before the three steps.</p>
                    <div class="row g-3 mb-2">
                        @include('aniSensoAdmin.landing.field', ['name' => 'precision[kicker]', 'label' => 'Small line', 'value' => $page['precision']['kicker'], 'default' => $d['precision']['kicker'], 'col' => 'col-md-4'])
                        @include('aniSensoAdmin.landing.field', ['name' => 'precision[headline]', 'label' => 'Headline', 'value' => $page['precision']['headline'], 'default' => $d['precision']['headline'], 'col' => 'col-md-8'])
                        @include('aniSensoAdmin.landing.field', ['name' => 'precision[sub]', 'label' => 'Line under it', 'value' => $page['precision']['sub'], 'default' => $d['precision']['sub'], 'type' => 'textarea', 'rows' => 2])
                    </div>
                    <div class="lp-list" data-list="precision" data-max="6">
                        @foreach ($page['precision']['items'] as $i => $r)
                            @include('aniSensoAdmin.landing.row-right', ['k' => 'r' . $i, 'r' => $r])
                        @endforeach
                    </div>
                    <template data-tpl="precision">@include('aniSensoAdmin.landing.row-right', ['k' => '__K__', 'r' => ['icon' => '', 'title' => '', 'text' => '']])</template>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2 lp-add" data-add="precision"><i class="bx bx-plus"></i> Add a card</button>
                </div></div>
                @endisset

                {{-- ===== 4. Pillars ===== --}}
                <div class="card lp-sec" id="sec-pillars"><div class="card-body">
                    <h4 class="card-title"><span class="n">4</span> Feature pillars</h4>
                    <p class="lp-lead">The big zigzag: a picture on one side, the benefit on the other. Three or four is right.</p>
                    <div class="lp-list" data-list="pillars" data-max="6">
                        @foreach ($page['pillars'] as $i => $p)
                            @include('aniSensoAdmin.landing.row-pillar', ['k' => 'p' . $i, 'p' => $p, 'shots' => $shots, 'base' => $base])
                        @endforeach
                    </div>
                    <template data-tpl="pillars">@include('aniSensoAdmin.landing.row-pillar', ['k' => '__K__', 'p' => ['kicker' => '', 'title' => '', 'text' => '', 'bullets' => [], 'image' => 'board', 'plan' => '', 'upload' => '', 'frame' => 'phone'], 'shots' => $shots, 'base' => $base])</template>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2 lp-add" data-add="pillars"><i class="bx bx-plus"></i> Add a pillar</button>
                </div></div>

                {{-- ===== Everything in one app ===== --}}
                <div class="card lp-sec" id="sec-more"><div class="card-body">
                    <h4 class="card-title">Everything in one app</h4>
                    <p class="lp-lead">Every tool the farm gets, in groups (Plan, Grow, Manage, Measure), beside a phone showing the season's modules. Tiles with the same group sit together under its name, in the order below.</p>
                    <div class="row g-3 mb-2">
                        @include('aniSensoAdmin.landing.field', ['name' => 'more[headline]', 'label' => 'Headline', 'value' => $page['more']['headline'], 'default' => $d['more']['headline']])
                        @isset($page['more']['sub'])
                            @include('aniSensoAdmin.landing.field', ['name' => 'more[sub]', 'label' => 'Line under it', 'value' => $page['more']['sub'], 'default' => $d['more']['sub'] ?? '', 'type' => 'textarea', 'rows' => 2])
                            <div class="col-12">
                                <label class="form-label">The phone</label>
                                @include('aniSensoAdmin.landing.picture', ['prefix' => 'more', 'field' => 'image', 'current' => $page['more']['image'] ?? '', 'builtIn' => 'lp/hub.webp', 'shape' => 'phone', 'base' => $base,
                                    'hint' => 'A phone screenshot, tall (about 780 × 1600). JPG, PNG or WebP, up to 6 MB.'])
                            </div>
                        @endisset
                    </div>
                    <div class="lp-list" data-list="more" data-max="24">
                        @foreach ($page['more']['items'] as $i => $t)
                            @include('aniSensoAdmin.landing.row-tile', ['k' => 'm' . $i, 't' => $t])
                        @endforeach
                    </div>
                    <template data-tpl="more">@include('aniSensoAdmin.landing.row-tile', ['k' => '__K__', 't' => ['group' => '', 'icon' => '', 'title' => '', 'text' => '']])</template>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2 lp-add" data-add="more"><i class="bx bx-plus"></i> Add a tile</button>
                </div></div>

                {{-- ===== 5. Testimonials ===== --}}
                <div class="card lp-sec" id="sec-testimonials"><div class="card-body">
                    <h4 class="card-title"><span class="n">5</span> Testimonials</h4>
                    <p class="lp-lead">Real farmers only, with their consent: a name, a photo, what they do and where, and a result that can be counted. The first three show. With none, the section is not shown at all.</p>
                    <div class="row g-3 mb-2">
                        @include('aniSensoAdmin.landing.field', ['name' => 'testimonials[headline]', 'label' => 'Headline', 'value' => $page['testimonials']['headline'], 'default' => $d['testimonials']['headline']])
                    </div>
                    <div class="lp-list" data-list="testimonials" data-max="6">
                        @foreach ($page['testimonials']['items'] as $i => $t)
                            @include('aniSensoAdmin.landing.row-quote', ['k' => 't' . $i, 't' => $t, 'base' => $base])
                        @endforeach
                    </div>
                    <template data-tpl="testimonials">@include('aniSensoAdmin.landing.row-quote', ['k' => '__K__', 't' => [], 'base' => $base])</template>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2 lp-add" data-add="testimonials"><i class="bx bx-plus"></i> Add a testimonial</button>
                </div></div>

                {{-- ===== 6. FAQ ===== --}}
                <div class="card lp-sec" id="sec-faq"><div class="card-body">
                    <h4 class="card-title"><span class="n">6</span> Questions (FAQ)</h4>
                    <p class="lp-lead">The doubts that stop a signup: the card, cancelling, the phone, the crops. Answer them plainly.</p>
                    <div class="row g-3 mb-2">
                        @include('aniSensoAdmin.landing.field', ['name' => 'faq[headline]', 'label' => 'Headline', 'value' => $page['faq']['headline'], 'default' => $d['faq']['headline']])
                    </div>
                    <div class="lp-list" data-list="faq" data-max="14">
                        @foreach ($page['faq']['items'] as $i => $qa)
                            @include('aniSensoAdmin.landing.row-faq', ['k' => 'q' . $i, 'qa' => $qa])
                        @endforeach
                    </div>
                    <template data-tpl="faq">@include('aniSensoAdmin.landing.row-faq', ['k' => '__K__', 'qa' => ['q' => '', 'a' => '']])</template>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2 lp-add" data-add="faq"><i class="bx bx-plus"></i> Add a question</button>
                </div></div>

                {{-- ===== 7. Closer ===== --}}
                <div class="card lp-sec" id="sec-closer"><div class="card-body">
                    <h4 class="card-title"><span class="n">7</span> The closer</h4>
                    <p class="lp-lead">The last push: the offer again, the email box, and why there is nothing to lose.</p>
                    <div class="row g-3">
                        @include('aniSensoAdmin.landing.field', ['name' => 'closer[headline]', 'label' => 'Headline', 'value' => $page['closer']['headline'], 'default' => $d['closer']['headline'], 'col' => 'col-md-6', 'help' => 'Wrap words in *stars* to mark them.'])
                        @include('aniSensoAdmin.landing.field', ['name' => 'closer[cta]', 'label' => 'Button', 'value' => $page['closer']['cta'], 'default' => $d['closer']['cta'], 'col' => 'col-md-6', 'max' => 60])
                        @include('aniSensoAdmin.landing.field', ['name' => 'closer[sub]', 'label' => 'Line under it', 'value' => $page['closer']['sub'], 'default' => $d['closer']['sub'], 'type' => 'textarea', 'rows' => 2])
                        @include('aniSensoAdmin.landing.field', ['name' => 'closer[risk]', 'label' => 'The no-risk line', 'value' => $page['closer']['risk'], 'default' => $d['closer']['risk'], 'help' => 'Under the button: why signing up costs nothing.'])
                        <div class="col-12">
                            <label class="form-label">The background photo</label>
                            @include('aniSensoAdmin.landing.picture', ['prefix' => 'closer', 'field' => 'image', 'current' => $page['closer']['image'], 'builtIn' => 'lp/palay-phone.webp', 'base' => $base,
                                'hint' => 'A wide photo; it is darkened so the words stay readable. JPG, PNG or WebP, up to 6 MB.'])
                        </div>
                    </div>
                </div></div>

                {{-- ===== Ad tracking ===== --}}
                <div class="card lp-sec" id="sec-tracking"><div class="card-body">
                    <h4 class="card-title">Ad tracking</h4>
                    <p class="lp-lead">So the ad networks see who signed up and can find more people like them. Leave blank to send them nothing. The tags run only on the landing page, the signup form and the "check your inbox" page, never inside the app.</p>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="tkMeta">Meta Pixel id</label>
                            <input class="form-control" id="tkMeta" name="tracking[metaPixel]" value="{{ old('tracking.metaPixel', $tracking['metaPixel']) }}" placeholder="123456789012345" inputmode="numeric" maxlength="20">
                            <div class="form-text">Facebook and Instagram ads. Events Manager › your pixel › its id.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="tkGoogle">Google tag id</label>
                            <input class="form-control" id="tkGoogle" name="tracking[googleTag]" value="{{ old('tracking.googleTag', $tracking['googleTag']) }}" placeholder="G-XXXXXXX or AW-1234567890" maxlength="24">
                            <div class="form-text">Google Analytics or Google Ads.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="tkSendTo">Google Ads conversion</label>
                            <input class="form-control" id="tkSendTo" name="tracking[googleAdsSendTo]" value="{{ old('tracking.googleAdsSendTo', $tracking['googleAdsSendTo']) }}" placeholder="AW-1234567890/AbCdEfGh" maxlength="60">
                            <div class="form-text">Optional: the "send to" of a sign-up conversion action.</div>
                        </div>
                    </div>
                    <div class="form-text mt-2">A signup is reported as <code>CompleteRegistration</code> to Meta and <code>sign_up</code> to Google, the moment the form is sent.</div>
                </div></div>

                <div class="lp-savebar">
                    <button type="submit" class="btn btn-primary"><i class="bx bx-save"></i> Save the page</button>
                    <a href="{{ $live }}" target="_blank" rel="noopener" class="btn btn-light">View the page</a>
                    <span class="lp-dirty" id="lpDirty">Unsaved changes</span>
                </div>
            </form>

            {{-- ===== Signups from ads (read only) ===== --}}
            <div class="card lp-sec mt-4" id="sec-signups"><div class="card-body">
                <h4 class="card-title">Signups from ads</h4>
                <p class="lp-lead">The last 90 days. An ad link carries tags (make one at the top of this page); a signup that came through one is counted here under its source and campaign. "Confirmed" opened the link in their email.</p>
                @if (! $sources['ready'])
                    <div class="alert alert-warning mb-0">The table that keeps these (<code>as_signup_sources</code>) is not in the database yet; anee.io's next deploy adds it.</div>
                @elseif ($sources['rows']->isEmpty())
                    <p class="mb-0 text-secondary">No signups from tagged links yet. {{ $sources['all'] }} {{ $sources['all'] === 1 ? 'account was' : 'accounts were' }} made in these days in all, none through an ad link.</p>
                @else
                    <p class="mb-2"><b>{{ $sources['tagged'] }}</b> of the <b>{{ $sources['all'] }}</b> accounts made in these days came through an ad link.</p>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Source</th><th>Campaign</th><th class="text-end">Signups</th><th class="text-end">Confirmed</th><th class="text-end">Latest</th></tr></thead>
                            <tbody>
                                @foreach ($sources['rows'] as $r)
                                    <tr>
                                        <td>{{ $r->source ?: '—' }}</td>
                                        <td>{{ $r->campaign ?: '—' }}</td>
                                        <td class="text-end fw-semibold">{{ $r->signups }}</td>
                                        <td class="text-end">{{ (int) $r->confirmed }} <span class="text-secondary small">({{ $r->signups ? round($r->confirmed / $r->signups * 100) : 0 }}%)</span></td>
                                        <td class="text-end text-secondary small">{{ \Illuminate\Support\Carbon::parse($r->lastAt)->diffForHumans() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div></div>
        </div>
    </div>
    @endif
    </div>
@endsection

@section('script')
<script>
(() => {
    const root = document.getElementById('lpEd');
    const form = document.getElementById('lpForm');
    if (!root || !form) return;
    const calm = matchMedia('(prefers-reduced-motion: reduce)').matches;
    const EASE = 'cubic-bezier(.22,1,.36,1)';
    let seq = 0;

    // ---- Unsaved changes: a word by the button, and a question on leaving.
    let dirty = false;
    const markDirty = () => { dirty = true; document.getElementById('lpDirty')?.classList.add('is-on'); };
    form.addEventListener('input', markDirty);
    form.addEventListener('change', markDirty);
    form.addEventListener('submit', () => { dirty = false; });
    window.addEventListener('beforeunload', (e) => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });

    // ---- Repeatable rows: add, move, remove, each animated.
    const count = (list) => list.querySelectorAll(':scope > .lp-row').length;
    const paintAdd = (name) => {
        const list = root.querySelector(`[data-list="${name}"]`);
        const btn = root.querySelector(`[data-add="${name}"]`);
        if (list && btn) btn.hidden = count(list) >= Number(list.dataset.max || 99);
    };
    root.querySelectorAll('[data-list]').forEach((l) => paintAdd(l.dataset.list));
    const flash = (row) => { if (!calm) row.animate([{ opacity: 0, transform: 'translateY(-6px)' }, { opacity: 1, transform: 'none' }], { duration: 280, easing: EASE }); };

    root.addEventListener('click', (e) => {
        const add = e.target.closest('[data-add]');
        if (add) {
            const name = add.dataset.add;
            const list = root.querySelector(`[data-list="${name}"]`);
            const tpl = root.querySelector(`template[data-tpl="${name}"]`);
            if (!list || !tpl || count(list) >= Number(list.dataset.max || 99)) return;
            const key = 'n' + Date.now().toString(36) + (seq++);
            list.insertAdjacentHTML('beforeend', tpl.innerHTML.replaceAll('__K__', key));
            const row = list.lastElementChild;
            flash(row);
            row.querySelector('input, textarea')?.focus({ preventScroll: true });
            row.scrollIntoView({ behavior: calm ? 'auto' : 'smooth', block: 'nearest' });
            paintAdd(name);
            markDirty();
            return;
        }
        const row = e.target.closest('.lp-row');
        if (!row) return;
        if (e.target.closest('[data-row-remove]')) {
            const list = row.parentElement;
            const done = () => { row.remove(); paintAdd(list.dataset.list); markDirty(); };
            if (calm) { done(); return; }
            const h = row.offsetHeight;
            row.style.overflow = 'hidden';
            row.animate([{ height: h + 'px', opacity: 1 }, { height: '0px', opacity: 0, paddingTop: '0px', paddingBottom: '0px', marginTop: '-.75rem' }], { duration: 280, easing: EASE }).onfinish = done;
        } else if (e.target.closest('[data-row-up]') && row.previousElementSibling) {
            row.parentElement.insertBefore(row, row.previousElementSibling); flash(row); markDirty();
        } else if (e.target.closest('[data-row-down]') && row.nextElementSibling) {
            row.parentElement.insertBefore(row.nextElementSibling, row); flash(row); markDirty();
        }
    });

    // ---- Picture slots: a chosen file shows at once; "built-in" puts it back.
    root.addEventListener('change', (e) => {
        const pic = e.target.closest('[data-pic]');
        if (pic) {
            const img = pic.querySelector('[data-pic-img]');
            const none = pic.querySelector('[data-pic-none]');
            const file = pic.querySelector('[data-pic-file]');
            const clear = pic.querySelector('[data-pic-clear]');
            const held = pic.querySelector('input[type=hidden]');
            if (e.target === file && file.files[0]) {
                img.src = URL.createObjectURL(file.files[0]);
                img.hidden = false; if (none) none.hidden = true;
                pic.classList.add('has-upload');
                if (clear) clear.checked = false;
            } else if (e.target === clear && clear.checked) {
                file.value = '';
                if (held) held.value = '';
                pic.classList.remove('has-upload');
                if (pic.dataset.builtIn) img.src = pic.dataset.builtIn;
                else { img.hidden = true; if (none) none.hidden = false; }
            }
        }
        // A pillar's built-in picture follows its menu while nothing is uploaded.
        const shot = e.target.closest('[data-shot]');
        if (shot) {
            const p = shot.closest('.lp-row').querySelector('[data-pic]');
            const opt = shot.selectedOptions[0];
            p.dataset.builtIn = opt.dataset.src;
            if (!p.classList.contains('has-upload')) p.querySelector('[data-pic-img]').src = opt.dataset.src;
        }
    });

    // ---- The ad link maker.
    const maker = document.getElementById('lpLinkMaker');
    if (maker) {
        const out = maker.querySelector('[data-l-out]');
        const val = (k) => maker.querySelector(`[data-l="${k}"]`).value.trim();
        const slug = (s) => s.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        const paint = () => {
            const src = val('source');
            const q = new URLSearchParams({ utm_source: src, utm_medium: src === 'google' ? 'cpc' : 'paid_social' });
            if (slug(val('campaign'))) q.set('utm_campaign', slug(val('campaign')));
            if (slug(val('content'))) q.set('utm_content', slug(val('content')));
            out.textContent = maker.dataset.base + '/' + val('face') + '/start?' + q.toString();
        };
        maker.addEventListener('input', paint);
        maker.addEventListener('change', paint);
        paint();
        maker.querySelector('[data-l-copy]').addEventListener('click', (e) => {
            const b = e.currentTarget;
            navigator.clipboard?.writeText(out.textContent).then(() => { b.textContent = 'Copied'; setTimeout(() => { b.textContent = 'Copy'; }, 1600); });
        });
    }

    // ---- The side list lights the section in view.
    const links = [...root.querySelectorAll('[data-nav]')];
    if (links.length && 'IntersectionObserver' in window) {
        const io = new IntersectionObserver((entries) => {
            entries.forEach((en) => {
                if (!en.isIntersecting) return;
                const id = en.target.id.replace('sec-', '');
                links.forEach((a) => a.classList.toggle('is-on', a.dataset.nav === id));
            });
        }, { rootMargin: '-35% 0px -60% 0px' });
        root.querySelectorAll('.lp-sec').forEach((s) => io.observe(s));
    }
})();
</script>
@endsection
