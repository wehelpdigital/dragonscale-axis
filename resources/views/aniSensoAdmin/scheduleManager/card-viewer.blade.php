<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Card Viewer — {{ $schedule->title }}</title>
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        :root {
            --cv-bg: #f4f6fb;
            --cv-surface: #ffffff;
            --cv-ink: #1a1f2b;
            --cv-muted: #6b7280;
            --cv-line: #e1e6ef;
            --cv-accent: #556ee6;
            --cv-shadow: 0 4px 16px rgba(20, 30, 60, .08);
        }
        * { box-sizing: border-box; }
        html, body {
            margin: 0; padding: 0;
            background: var(--cv-bg);
            color: var(--cv-ink);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            font-size: 14px;
            line-height: 1.5;
        }
        body { padding-bottom: 70px; /* room for fixed footer */ }
        a { color: var(--cv-accent); }
        button { cursor: pointer; font-family: inherit; }

        /* ============ TOP TOOLBAR (sticky) ============ */
        .cv-toolbar {
            position: sticky; top: 0; z-index: 100;
            background: var(--cv-ink); color: #fff;
            display: flex; align-items: center; gap: 12px;
            padding: 10px 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,.15);
        }
        .cv-toolbar .cv-title {
            font-weight: 700; font-size: 15px;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            flex: 0 1 auto; max-width: 38%;
        }
        .cv-toolbar .cv-version {
            background: rgba(255,255,255,.15);
            padding: 3px 10px; border-radius: 10px;
            font-size: 11.5px; font-weight: 600;
        }
        .cv-toolbar .cv-counter {
            background: rgba(255,255,255,.12);
            padding: 4px 12px; border-radius: 10px;
            font-size: 12.5px; font-variant-numeric: tabular-nums;
        }
        .cv-toolbar .cv-counter strong { color: #ffd56b; }
        .cv-toolbar select.cv-jump {
            background: rgba(255,255,255,.12);
            color: #fff; border: 1px solid rgba(255,255,255,.25);
            padding: 4px 10px; border-radius: 6px; font-size: 12.5px;
            max-width: 260px;
        }
        .cv-toolbar select.cv-jump option { color: #1a1f2b; background: #fff; }
        .cv-toolbar .cv-spacer { flex: 1; }
        .cv-toolbar .cv-iconbtn {
            background: rgba(255,255,255,.12);
            color: #fff; border: 1px solid rgba(255,255,255,.25);
            padding: 5px 10px; border-radius: 6px; font-size: 13px;
            display: inline-flex; align-items: center; gap: 5px;
        }
        .cv-toolbar .cv-iconbtn:hover { background: rgba(255,255,255,.22); }

        /* ============ SLIDES — PPT-style 16:9 frame ============
           Each slide is a fixed 16:9 box that fills the available width
           up to 1280px (matching modern PowerPoint slide size of 1920x1080
           at 2/3 scale). When content overflows the frame, the body area
           scrolls internally so the slide always looks like a single
           "page" — no awkward growing past the bottom edge. */
        .cv-stage {
            position: relative;
            min-height: calc(100vh - 130px);
            padding: 18px 12px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }
        .cv-slide {
            display: none;
            width: 100%;
            max-width: 1280px;
            aspect-ratio: 16 / 9;
            margin: 0 auto;
            background: var(--cv-surface);
            border-radius: 14px;
            box-shadow: var(--cv-shadow);
            animation: cvFade .18s ease;
            overflow: hidden;
            flex-direction: column;
        }
        .cv-slide.active { display: flex; }
        .cv-slide-body {
            flex: 1; min-height: 0;
            overflow-y: auto;
            padding: 16px 32px 22px;
        }
        /* Cover slide gets centered vertical alignment for poster feel. */
        .cv-slide[data-index="0"] .cv-slide-body {
            display: flex; align-items: center; justify-content: center;
        }
        .cv-slide[data-index="0"] .cv-slide-body > .cv-cover { width: 100%; }
        .cv-slide-body::-webkit-scrollbar { width: 8px; }
        .cv-slide-body::-webkit-scrollbar-thumb {
            background: #c5cad9; border-radius: 4px;
        }
        @keyframes cvFade {
            from { opacity: 0; transform: translateY(6px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        /* On very narrow viewports (phones rotated portrait), the 16:9 box
           gets unreadably small. Fall back to natural-height flow so the
           content stays usable. */
        @media (max-width: 700px) {
            .cv-slide { aspect-ratio: auto; min-height: 80vh; }
            .cv-slide-body { padding: 14px 16px; }
        }

        /* ============ COVER SLIDE ============ */
        .cv-cover { text-align: center; padding: 16px 0; }
        .cv-cover h1 {
            font-size: 32px; margin: 0 0 8px;
            color: var(--cv-ink); letter-spacing: -0.5px;
        }
        .cv-cover .cv-cover-span {
            color: var(--cv-muted); margin: 4px 0 18px; font-size: 16px;
        }
        .cv-cover-stats {
            display: flex; justify-content: center; gap: 16px; flex-wrap: wrap;
            margin: 12px 0 22px;
        }
        .cv-cover-stats > span {
            background: #eef2ff; color: #2c3e8c;
            padding: 6px 14px; border-radius: 16px;
            font-weight: 600; font-size: 13px;
        }
        .cv-cover-rules {
            background: #fff7f7;
            border: 2px solid #d9534f;
            border-radius: 8px;
            padding: 16px 22px;
            text-align: left;
            margin: 18px 0;
        }
        .cv-cover-rules h2 {
            color: #8a1d1d; font-size: 16px; margin: 0 0 8px;
            text-transform: uppercase; letter-spacing: 0.4px;
            display: flex; align-items: center; gap: 6px;
        }
        .cv-cover-rules ol { margin: 0; padding-left: 22px; }
        .cv-cover-rules li {
            color: #5a2828; margin: 4px 0;
            font-size: 14px; line-height: 1.55;
        }
        .cv-cover-intro {
            text-align: left;
            background: #fafbff;
            border-left: 4px solid #4a73e3;
            padding: 14px 18px;
            border-radius: 0 6px 6px 0;
            margin: 18px 0;
            color: #1a2655;
            line-height: 1.6;
        }
        .cv-cover-intro h1, .cv-cover-intro h2, .cv-cover-intro h3 {
            color: #2c3e8c; margin: 0.5em 0 0.3em;
        }
        .cv-cover-intro ul, .cv-cover-intro ol { margin-left: 1.4rem; }
        .cv-cover-empty {
            color: var(--cv-muted);
            font-style: italic;
            padding: 26px 0;
        }

        /* ============ DAY SLIDE ============ */
        /* The day-head sits directly inside the slide frame (above the
           scrollable .cv-slide-body) so it stays pinned as a PPT-style
           page title bar. */
        .cv-day-head {
            flex: 0 0 auto;
            display: flex; align-items: baseline; gap: 14px; flex-wrap: wrap;
            border-bottom: 1px solid var(--cv-line);
            padding: 16px 32px 12px;
            background: linear-gradient(180deg, #fbfcff 0%, #ffffff 100%);
            border-radius: 14px 14px 0 0;
        }
        .cv-day-dayidx {
            background: var(--cv-accent); color: #fff;
            padding: 5px 13px; border-radius: 16px;
            font-weight: 700; font-size: 12px;
            letter-spacing: 0.3px;
        }
        .cv-day-date {
            font-size: 22px; font-weight: 700; color: var(--cv-ink);
            letter-spacing: -0.3px;
        }
        .cv-day-weekday {
            color: var(--cv-muted); font-size: 13.5px; font-weight: 500;
        }

        /* Compact critical-rules banner on day slides */
        .cv-rules-banner {
            display: flex; align-items: center; gap: 8px;
            background: #fff8f1;
            border-left: 3px solid #f3a55a;
            color: #7a4717;
            padding: 6px 12px; border-radius: 4px;
            font-size: 12.5px;
            margin-bottom: 14px;
        }
        .cv-rules-banner i { color: #d9534f; font-size: 15px; }
        .cv-rules-banner-count { font-weight: 700; color: #8a1d1d; }
        .cv-rules-banner-cta { margin-left: auto; font-size: 11.5px; color: #5e6878; }

        /* Date note callout on day slide */
        .cv-day-note {
            background: #fff8e6;
            border-left: 4px solid #d9a23a;
            padding: 10px 14px;
            margin: 0 0 16px;
            border-radius: 0 4px 4px 0;
            color: #4d3a0d;
            line-height: 1.55;
        }
        .cv-day-note strong { color: #8a5e09; }

        /* Section heading inside a day slide */
        .cv-section { margin-top: 14px; }
        .cv-section:first-child { margin-top: 4px; }
        .cv-section-head {
            display: flex; align-items: center; gap: 8px;
            font-size: 15px; font-weight: 700;
            margin: 0 0 8px; color: var(--cv-ink);
            text-transform: uppercase; letter-spacing: 0.4px;
        }
        .cv-section-head .cv-section-count {
            background: var(--cv-line); color: var(--cv-muted);
            padding: 1px 9px; border-radius: 10px;
            font-size: 12.5px; font-weight: 600;
        }

        /* Irrigation cards grid */
        .cv-irr-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 10px;
        }
        .cv-irr-card {
            background: #fff;
            border: 1px solid var(--cv-line);
            border-left: 4px solid var(--c, #1976d2);
            border-radius: 6px;
            padding: 10px 12px;
        }
        .cv-irr-task {
            display: inline-block;
            color: #fff;
            font-weight: 600; font-size: 11.5px;
            padding: 3px 10px;
            border-radius: 11px;
            margin-bottom: 6px;
        }
        .cv-irr-name {
            font-weight: 700; color: var(--cv-ink);
            font-size: 13.5px; margin-bottom: 4px;
        }
        .cv-irr-meta {
            font-size: 12px; color: var(--cv-muted);
            margin: 3px 0; display: flex; flex-wrap: wrap; gap: 4px; align-items: center;
        }
        .cv-irr-prio {
            display: inline-block; margin-top: 6px;
            background: var(--cv-line); color: #4a5160;
            padding: 1px 9px; border-radius: 10px;
            font-size: 10.5px; font-weight: 700;
        }
        .cv-irr-prio[data-p="1"] { background: #9c1c1c; color: #fff; }
        .cv-irr-prio[data-p="2"] { background: #d97a4f; color: #fff; }
        .cv-irr-prio[data-p="3"] { background: #d9a23a; color: #3a2c0a; }
        .cv-irr-prio[data-p="4"] { background: #7a8a99; color: #fff; }

        /* Activity cards — adaptive: single column when only a few cards
           or when description-heavy, two columns when there are several
           short ones (handled via auto-fill + minmax). */
        .cv-acts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
            gap: 10px;
        }
        .cv-act-card {
            background: #fff;
            border: 1px solid var(--cv-line);
            border-left: 5px solid #5b8c3a;
            border-radius: 6px;
            padding: 11px 14px;
        }
        .cv-act-card.priority-critical { border-left-color: #8a1d1d; }
        .cv-act-card.priority-high     { border-left-color: #c95a35; }
        .cv-act-card.priority-medium   { border-left-color: #5b8c3a; }
        .cv-act-card.priority-low      { border-left-color: #74788d; }
        .cv-act-head {
            display: flex; flex-wrap: wrap; align-items: center; gap: 8px;
            margin-bottom: 6px;
        }
        .cv-act-title { font-weight: 700; font-size: 15.5px; color: var(--cv-ink); }
        .cv-act-pill { font-size: 10.5px; font-weight: 700; padding: 2px 9px; border-radius: 10px; }
        .cv-act-type { background: #e2efd4; color: #2d4d1c; }
        .cv-act-prio { color: #fff; }
        .cv-act-prio.priority-critical { background: #8a1d1d; }
        .cv-act-prio.priority-high     { background: #c95a35; }
        .cv-act-prio.priority-medium   { background: #5b8c3a; }
        .cv-act-prio.priority-low      { background: #74788d; color: #fff; }
        .cv-act-multiday {
            background: #fef3e8; color: #a66200;
            font-size: 10.5px; font-weight: 600;
            padding: 2px 9px; border-radius: 10px;
        }
        .cv-act-time {
            background: #f1f3f7; color: #4a5160;
            font-size: 11px; font-weight: 600;
            padding: 2px 9px; border-radius: 10px;
        }
        .cv-act-desc {
            color: #1a2655;
            background: #fafbff;
            border: 1px solid #e1e6f5;
            border-radius: 4px;
            padding: 8px 12px;
            margin: 8px 0;
            font-size: 13px;
            line-height: 1.55;
        }
        .cv-act-desc img { max-width: 100%; height: auto; }
        .cv-act-meta { display: flex; flex-direction: column; gap: 4px; margin-top: 6px; }
        .cv-act-meta-row {
            display: flex; align-items: center; gap: 5px; flex-wrap: wrap;
            font-size: 12.5px; color: #4a5160;
        }
        .cv-act-meta-row > i { color: var(--cv-accent); font-size: 14px; }

        .cv-chip {
            display: inline-block;
            padding: 2px 9px;
            border-radius: 10px;
            font-size: 11.5px;
            font-weight: 500;
            background: #eef0fb;
            color: #3a4699;
        }
        .cv-chip-worker { background: #fef3e8; color: #a66200; }
        .cv-chip-service { background: #e6f7f1; color: #0f8a5f; }
        .cv-chip-material { background: #eef0fb; color: #3a4699; }

        /* Empty section message */
        .cv-empty-msg {
            background: #fafafa;
            border: 1px dashed #d9dde3;
            border-radius: 6px;
            padding: 20px;
            text-align: center;
            color: var(--cv-muted);
            font-style: italic;
        }

        /* ============ FIXED FOOTER NAV ============ */
        .cv-footer {
            position: fixed; left: 0; right: 0; bottom: 0;
            background: var(--cv-surface);
            border-top: 1px solid var(--cv-line);
            box-shadow: 0 -2px 8px rgba(0,0,0,.05);
            padding: 10px 16px;
            display: flex; align-items: center; gap: 12px; justify-content: center;
            z-index: 100;
        }
        .cv-navbtn {
            background: var(--cv-accent); color: #fff;
            border: none;
            padding: 8px 22px;
            border-radius: 6px;
            font-weight: 600; font-size: 13.5px;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .cv-navbtn:disabled {
            background: #c5cad9; cursor: not-allowed;
        }
        .cv-navbtn:not(:disabled):hover { background: #4458c4; }
        .cv-navbtn i { font-size: 18px; }
        .cv-progress {
            background: #f1f3f7; color: #4a5160;
            padding: 6px 14px; border-radius: 14px;
            font-weight: 600; font-size: 13px;
            min-width: 110px; text-align: center;
            font-variant-numeric: tabular-nums;
        }
        .cv-progress strong { color: var(--cv-accent); }

        /* ============ FULLSCREEN MODE ============ */
        :fullscreen .cv-toolbar { background: #0f1421; }
        :fullscreen .cv-stage { min-height: calc(100vh - 110px); }

        /* ============ PRINT — one slide per page ============
           When printing, ditch the 16:9 aspect-ratio frame (which would
           leave a huge blank area on letter/A4 paper) and let each slide
           fill the printable page naturally. */
        @page { size: A4 landscape; margin: 14mm; }
        @media print {
            body { background: #fff; padding: 0; }
            .cv-toolbar, .cv-footer { display: none !important; }
            .cv-stage {
                min-height: 0;
                padding: 0;
                display: block;
            }
            .cv-slide {
                display: block !important;
                aspect-ratio: auto;
                width: 100%; max-width: none;
                box-shadow: none; margin: 0; padding: 0;
                border-radius: 0; overflow: visible;
                page-break-after: always; break-after: page;
                animation: none;
            }
            .cv-slide:last-of-type { page-break-after: auto; }
            .cv-slide-body {
                overflow: visible !important;
                padding: 0 4px;
                display: block !important;
            }
            .cv-day-head {
                background: none;
                border-radius: 0;
                padding: 0 4px 10px;
            }
            .cv-act-card, .cv-irr-card { page-break-inside: avoid; }
            .cv-cover-rules, .cv-cover-intro { page-break-inside: avoid; }
        }

        /* ============ TINY RULES MODAL (compact viewer trigger) ============ */
        .cv-modal-backdrop {
            position: fixed; inset: 0; background: rgba(15, 20, 33, .55);
            display: none; align-items: center; justify-content: center;
            z-index: 200;
        }
        .cv-modal-backdrop.active { display: flex; }
        .cv-modal {
            background: #fff; max-width: 560px; width: 92vw; max-height: 80vh;
            border-radius: 8px; padding: 20px 26px;
            overflow-y: auto;
            box-shadow: 0 12px 40px rgba(0,0,0,.25);
        }
        .cv-modal h2 {
            margin: 0 0 12px; font-size: 18px; color: #8a1d1d;
            display: flex; align-items: center; gap: 6px;
        }
        .cv-modal ol { padding-left: 22px; margin: 0; }
        .cv-modal li { margin: 4px 0; color: #5a2828; line-height: 1.55; }
        .cv-modal-close {
            margin-top: 16px;
            background: #f1f3f7; border: none;
            padding: 7px 16px; border-radius: 5px;
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="cv-toolbar">
    <span class="cv-title">{{ $schedule->title }}</span>
    @if($activeVersion)
        <span class="cv-version"><i class="bx bx-git-branch"></i> {{ $activeVersion->versionName }}</span>
    @endif
    <span class="cv-counter">
        <strong class="cv-current">1</strong> / <span class="cv-total">{{ count($slides) + 1 }}</span>
    </span>
    <select class="cv-jump" title="Jump to a specific slide">
        <option value="0">— Cover —</option>
        @foreach($slides as $i => $s)
            <option value="{{ $i + 1 }}">
                Day {{ $s['dayIndex'] }} · {{ $s['date']->format('D, M j, Y') }}
            </option>
        @endforeach
    </select>
    <span class="cv-spacer"></span>
    <button class="cv-iconbtn" id="cvFullscreenBtn" title="Toggle fullscreen"><i class="bx bx-fullscreen"></i> Fullscreen</button>
    <button class="cv-iconbtn" id="cvPrintBtn" title="Print all slides (one per page)"><i class="bx bx-printer"></i> Print</button>
    <button class="cv-iconbtn" id="cvCloseBtn" title="Close (Esc) — returns to setup" onclick="window.close()"><i class="bx bx-x"></i> Close</button>
</div>

<div class="cv-stage">

    {{-- ============================================================
         SLIDE 0: Cover (always rendered)
    ============================================================ --}}
    <section class="cv-slide active" data-index="0" data-date="">
        <div class="cv-slide-body">
        <div class="cv-cover">
            <h1>{{ $schedule->title }}</h1>
            @if($firstDate && $lastDate)
                <div class="cv-cover-span">
                    {{ $firstDate->format('F j, Y') }} → {{ $lastDate->format('F j, Y') }}
                </div>
            @endif
            <div class="cv-cover-stats">
                <span>{{ count($slides) }} active {{ \Illuminate\Support\Str::plural('day', count($slides)) }}</span>
                <span>{{ $schedule->activities->count() }} {{ \Illuminate\Support\Str::plural('activity', $schedule->activities->count()) }}</span>
                <span>{{ $schedule->lots->count() }} {{ \Illuminate\Support\Str::plural('lot', $schedule->lots->count()) }}</span>
                <span>{{ $schedule->workers->count() }} {{ \Illuminate\Support\Str::plural('worker', $schedule->workers->count()) }}</span>
                @if($schedule->irrigations->count() > 0)
                    <span>{{ $schedule->irrigations->count() }} irrigation {{ \Illuminate\Support\Str::plural('cycle', $schedule->irrigations->count()) }}</span>
                @endif
            </div>

            @if($criticalRules->count() > 0)
                <div class="cv-cover-rules">
                    <h2><i class="bx bx-flag"></i> Critical Rules — Read Every Time</h2>
                    <ol>
                        @foreach($criticalRules as $rule)
                            <li>{{ $rule->ruleText }}</li>
                        @endforeach
                    </ol>
                </div>
            @endif

            @if($activeVersion && !empty($activeVersion->globalActivityNote))
                <div class="cv-cover-intro">
                    {!! $activeVersion->globalActivityNote !!}
                </div>
            @endif

            @if($criticalRules->count() === 0 && (!$activeVersion || empty($activeVersion->globalActivityNote)))
                <p class="cv-cover-empty">
                    No protocol introduction or critical rules defined yet.
                    Use the <strong>Documentation</strong> tab on the setup screen to add them.
                </p>
            @endif

            <p style="color: var(--cv-muted); font-size: 12.5px; margin-top: 18px;">
                Use <strong>→</strong> / <strong>Space</strong> to advance · <strong>←</strong> to go back ·
                <strong>Home</strong>/<strong>End</strong> to jump to start/end ·
                <strong>F</strong> for fullscreen
            </p>
        </div>
        </div> {{-- /.cv-slide-body --}}
    </section>

    {{-- ============================================================
         SLIDES 1..N: One per active day
    ============================================================ --}}
    @foreach($slides as $i => $s)
        @php
            $slideIdx = $i + 1;
            $dateCarbon = $s['date'];
            $activitiesForDay = $s['activities'];
            $irrEntries = $s['irrigations'];
            $note = $s['note'];
        @endphp
        <section class="cv-slide" data-index="{{ $slideIdx }}" data-date="{{ $s['dateKey'] }}">

            {{-- Day header pinned to the top of the slide frame so it
                 stays visible even when the body scrolls. --}}
            <div class="cv-day-head">
                <span class="cv-day-dayidx">Day {{ $s['dayIndex'] }} of {{ count($slides) }}</span>
                <span class="cv-day-date">{{ $dateCarbon->format('F j, Y') }}</span>
                <span class="cv-day-weekday">{{ $dateCarbon->format('l') }}</span>
            </div>

            <div class="cv-slide-body">
            @if($criticalRules->count() > 0)
                <div class="cv-rules-banner">
                    <i class="bx bx-flag"></i>
                    <span class="cv-rules-banner-count">{{ $criticalRules->count() }}</span> critical
                    {{ \Illuminate\Support\Str::plural('rule', $criticalRules->count()) }} apply every day
                    <span class="cv-rules-banner-cta">— click to view ▸</span>
                </div>
            @endif

            @if($note)
                <div class="cv-day-note">
                    <strong>📝 Note:</strong> {!! nl2br(e($note->noteContent)) !!}
                </div>
            @endif

            @if(!empty($irrEntries))
                <div class="cv-section">
                    <div class="cv-section-head">
                        <i class="bx bx-water" style="color: #1976d2;"></i>
                        Irrigation
                        <span class="cv-section-count">{{ count($irrEntries) }}</span>
                    </div>
                    <div class="cv-irr-grid">
                        @foreach($irrEntries as $iEntry)
                            @php
                                $iIrr = $iEntry['irrigation'];
                                $iMeta = $iEntry['taskMeta'];
                                $iPrio = (int) ($iEntry['priority'] ?? 5);
                            @endphp
                            <div class="cv-irr-card" style="--c: {{ $iMeta['color'] }};">
                                <span class="cv-irr-task" style="background: {{ $iMeta['color'] }};">
                                    {{ $iMeta['icon'] }} {{ $iMeta['label'] }}
                                </span>
                                <div class="cv-irr-name">{{ $iIrr->irrigationTitle }}</div>
                                @if(!empty($iEntry['groupNames']))
                                    <div class="cv-irr-meta">
                                        <i class="bx bx-collection"></i>
                                        {{ implode(', ', $iEntry['groupNames']) }}
                                    </div>
                                @endif
                                @if($iIrr->lots && $iIrr->lots->count() > 0)
                                    <div class="cv-irr-meta">
                                        <i class="bx bx-map-pin"></i>
                                        @foreach($iIrr->lots as $lot)
                                            <span class="cv-chip">{{ $lot->lotName }}@if(!empty($lot->variety)) · {{ $lot->variety }}@endif</span>
                                        @endforeach
                                    </div>
                                @endif
                                @if($iIrr->workers && $iIrr->workers->count() > 0)
                                    <div class="cv-irr-meta">
                                        <i class="bx bx-user"></i>
                                        @foreach($iIrr->workers as $w)
                                            <span class="cv-chip cv-chip-worker">{{ $w->workerName }}</span>
                                        @endforeach
                                    </div>
                                @endif
                                @if($iIrr->description)
                                    <div class="cv-irr-meta" style="font-style: italic; color: #555;">
                                        {{ $iIrr->description }}
                                    </div>
                                @endif
                                <span class="cv-irr-prio" data-p="{{ $iPrio }}">Priority {{ $iPrio }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($activitiesForDay->count() > 0)
                <div class="cv-section">
                    <div class="cv-section-head">
                        <i class="bx bx-task" style="color: var(--cv-accent);"></i>
                        Activities
                        <span class="cv-section-count">{{ $activitiesForDay->count() }}</span>
                    </div>
                    <div class="cv-acts-grid">
                        @foreach($activitiesForDay as $a)
                            @php
                                $start = $a->targetDate;
                                $end   = $a->targetEndDate ?: $start;
                                $isMultiDay = $end->gt($start);
                                $multiCurrent = $isMultiDay ? ($start->diffInDays($dateCarbon) + 1) : null;
                                $multiTotal   = $isMultiDay ? ($start->diffInDays($end) + 1) : null;
                                $timeLabel = ['half' => 'Half day', 'whole' => 'Whole day', 'n/a' => 'N/A'][$a->timeRequired] ?? ucfirst($a->timeRequired);
                                $typeLabel = $a->activityType ? (\App\Models\AsScheduleActivity::ACTIVITY_TYPES[$a->activityType] ?? null) : null;
                            @endphp
                            <div class="cv-act-card priority-{{ $a->priority }}">
                                <div class="cv-act-head">
                                    <span class="cv-act-title">{{ $a->activityTitle }}</span>
                                    @if($typeLabel)
                                        <span class="cv-act-pill cv-act-type">{{ $typeLabel }}</span>
                                    @endif
                                    <span class="cv-act-pill cv-act-prio priority-{{ $a->priority }}">{{ ucfirst($a->priority) }}</span>
                                    @if($isMultiDay)
                                        <span class="cv-act-multiday">
                                            <i class="bx bx-right-arrow-alt"></i>
                                            Day {{ $multiCurrent }} of {{ $multiTotal }}
                                            ({{ $start->format('M j') }}–{{ $end->format('M j') }})
                                        </span>
                                    @endif
                                    <span class="cv-act-time">
                                        <i class="bx bx-time"></i> {{ $timeLabel }}
                                    </span>
                                    @if($a->isDayZero)
                                        <span class="cv-act-pill" style="background:#ff9800; color:#fff;"><i class="bx bxs-star"></i> {{ $schedule->dayType }} 0</span>
                                    @endif
                                </div>
                                @if($a->description)
                                    <div class="cv-act-desc">{!! $a->description !!}</div>
                                @endif
                                <div class="cv-act-meta">
                                    @if($a->lots->count() > 0)
                                        <div class="cv-act-meta-row">
                                            <i class="bx bx-map-pin"></i>
                                            <strong>Lots:</strong>
                                            @foreach($a->lots as $lot)
                                                <span class="cv-chip">{{ $lot->lotName }}@if(!empty($lot->variety)) · {{ $lot->variety }}@endif</span>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if($a->workers->count() > 0)
                                        <div class="cv-act-meta-row">
                                            <i class="bx bx-user"></i>
                                            <strong>Workers:</strong>
                                            @foreach($a->workers as $w)
                                                <span class="cv-chip cv-chip-worker">{{ $w->workerName }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if($a->items->count() > 0)
                                        <div class="cv-act-meta-row">
                                            <i class="bx bx-package"></i>
                                            <strong>Items:</strong>
                                            @foreach($a->items as $it)
                                                @php
                                                    $qtyTrim = rtrim(rtrim((string) $it->quantity, '0'), '.');
                                                    $unit = $it->unitOfMeasure ?: ($it->material->unitOfMeasure ?? '');
                                                @endphp
                                                @if($it->itemType === 'material' && $it->material)
                                                    <span class="cv-chip cv-chip-material">{{ $it->material->materialName }} ×{{ $qtyTrim }}@if($unit) {{ $unit }}@endif</span>
                                                @elseif($it->itemType === 'service' && $it->service)
                                                    <span class="cv-chip cv-chip-service">{{ $it->service->serviceName }}@if($qtyTrim !== '1' || $unit) ×{{ $qtyTrim }}@if($unit) {{ $unit }}@endif @endif</span>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($activitiesForDay->count() === 0 && empty($irrEntries))
                <div class="cv-empty-msg">
                    No activities or irrigation scheduled — this day has a note only.
                </div>
            @endif

            </div> {{-- /.cv-slide-body --}}
        </section>
    @endforeach

    @if(count($slides) === 0)
        <section class="cv-slide" data-index="1">
            <div class="cv-slide-body">
                <div class="cv-empty-msg" style="padding: 60px 20px;">
                    No activities, irrigation, or notes scheduled yet. Add some on the setup screen
                    and they'll appear as daily slides here.
                </div>
            </div>
        </section>
    @endif
</div>

<div class="cv-footer">
    <button class="cv-navbtn" id="cvPrev" disabled>
        <i class="bx bx-chevron-left"></i> Previous
    </button>
    <span class="cv-progress">
        Slide <strong class="cv-current2">1</strong> of <span class="cv-total2">{{ count($slides) + 1 }}</span>
    </span>
    <button class="cv-navbtn" id="cvNext" @if(count($slides) === 0) disabled @endif>
        Next <i class="bx bx-chevron-right"></i>
    </button>
</div>

{{-- Critical rules quick-view modal (opened by clicking the day-slide banner) --}}
@if($criticalRules->count() > 0)
    <div class="cv-modal-backdrop" id="cvRulesModal">
        <div class="cv-modal">
            <h2><i class="bx bx-flag"></i> Critical Rules</h2>
            <ol>
                @foreach($criticalRules as $rule)
                    <li>{{ $rule->ruleText }}</li>
                @endforeach
            </ol>
            <button class="cv-modal-close" type="button">Close</button>
        </div>
    </div>
@endif

<script>
(function () {
    const slides   = Array.from(document.querySelectorAll('.cv-slide'));
    const total    = slides.length;
    const $current = document.querySelectorAll('.cv-current, .cv-current2');
    const $total   = document.querySelectorAll('.cv-total, .cv-total2');
    const $prev    = document.getElementById('cvPrev');
    const $next    = document.getElementById('cvNext');
    const $jump    = document.querySelector('.cv-jump');
    let current = 0;

    // Initialize total counters (in case slide count came from JS)
    $total.forEach(el => el.textContent = String(total));

    function show(i) {
        if (i < 0 || i >= total || i === current) return;
        slides[current].classList.remove('active');
        current = i;
        slides[current].classList.add('active');
        $current.forEach(el => el.textContent = String(i + 1));
        if ($jump) $jump.value = String(i);
        $prev.disabled = (i === 0);
        $next.disabled = (i === total - 1);
        // Scroll the slide container to the top — useful when a previous
        // slide was long and the user scrolled within it.
        window.scrollTo({ top: 0, behavior: 'instant' });
    }

    $prev.addEventListener('click', () => show(current - 1));
    $next.addEventListener('click', () => show(current + 1));
    if ($jump) {
        $jump.addEventListener('change', e => show(parseInt(e.target.value, 10) || 0));
    }

    // Keyboard navigation. Ignore when a form input has focus so a date
    // picker / textarea / select in a modal doesn't get hijacked.
    document.addEventListener('keydown', e => {
        if (e.target.matches('input, textarea, select')) return;
        if (e.altKey || e.ctrlKey || e.metaKey) return;
        switch (e.key) {
            case 'ArrowRight':
            case 'PageDown':
            case ' ':
                e.preventDefault(); show(current + 1); break;
            case 'ArrowLeft':
            case 'PageUp':
                e.preventDefault(); show(current - 1); break;
            case 'Home':
                e.preventDefault(); show(0); break;
            case 'End':
                e.preventDefault(); show(total - 1); break;
            case 'f':
            case 'F':
                toggleFullscreen(); break;
            case 'Escape':
                if (document.fullscreenElement) document.exitFullscreen();
                break;
        }
    });

    // Fullscreen toggle
    function toggleFullscreen() {
        if (document.fullscreenElement) {
            document.exitFullscreen();
        } else {
            document.documentElement.requestFullscreen().catch(() => {});
        }
    }
    document.getElementById('cvFullscreenBtn').addEventListener('click', toggleFullscreen);

    // Print — let the browser open its print dialog. The print CSS
    // forces one slide per page so the user gets a printed deck.
    document.getElementById('cvPrintBtn').addEventListener('click', () => window.print());

    // Critical rules quick-view modal: clicking the compact banner on a
    // day slide opens a popup with the full list, so workers don't have
    // to navigate back to the cover slide every time.
    const $rulesModal = document.getElementById('cvRulesModal');
    if ($rulesModal) {
        document.addEventListener('click', e => {
            if (e.target.closest('.cv-rules-banner')) {
                $rulesModal.classList.add('active');
            } else if (e.target === $rulesModal || e.target.matches('.cv-modal-close')) {
                $rulesModal.classList.remove('active');
            }
        });
    }

    // If the URL has #day=YYYY-MM-DD, jump to that slide on load.
    // Useful for sharing deep-links to a specific day.
    const hashMatch = location.hash.match(/day=(\d{4}-\d{2}-\d{2})/);
    if (hashMatch) {
        const targetIdx = slides.findIndex(s => s.dataset.date === hashMatch[1]);
        if (targetIdx > 0) show(targetIdx);
    }

    // Update the URL hash when navigating so refresh / bookmark works.
    const setHash = (i) => {
        const d = slides[i] && slides[i].dataset.date;
        if (d) history.replaceState(null, '', '#day=' + d);
        else   history.replaceState(null, '', '#cover');
    };
    const _origShow = show;
    // Wrap show to also update the hash.
    window.cvShow = function (i) { _origShow(i); setHash(current); };
    $prev.removeEventListener('click', _origShow);
    // We can't easily replace the handlers, so just push hash on the same events:
    $prev.addEventListener('click', () => setHash(current));
    $next.addEventListener('click', () => setHash(current));
    if ($jump) $jump.addEventListener('change', () => setHash(current));
    document.addEventListener('keydown', () => setHash(current));
})();
</script>

</body>
</html>
