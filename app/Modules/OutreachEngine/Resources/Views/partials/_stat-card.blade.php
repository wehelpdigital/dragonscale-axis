{{--
    Lead Finder headline tile.

    Usage:
        @include('outreach::partials._stat-card', [
            'id'     => 'statTotalLeads',              // element JS writes the number into
            'label'  => 'Total Leads Scraped',
            'icon'   => 'bx-map-pin',                  // boxicons class, sidebar family
            'accent' => 'primary',                     // primary|success|info|warning|danger|secondary
            'value'  => '0',                           // server-rendered starting value
            'hint'   => 'Across every region sweep',   // optional sub-line
            'hintId' => 'statTotalLeadsHint',          // optional, only when JS rewrites the hint
            'col'    => 'col-xl col-md-6',             // optional grid column classes
        ])

    The accent is resolved to a literal Skote hex rather than a Bootstrap border/bg
    utility on purpose: `.border-4` carries !important and would thicken all four
    sides of the card on Bootstrap 5.0/5.1, and a translucent `bg-opacity-*` icon
    chip is not contrast-safe on every theme. A solid chip with white glyph always is.

    Every text node names its own colour (CLAUDE.md 12) because this partial is
    dropped into containers whose inherited colour is not guaranteed.
--}}
@php
    $cardCol = $col ?? 'col-xl col-md-6';
    $cardAccent = $accent ?? 'primary';
    $cardIcon = $icon ?? 'bx-bar-chart-alt-2';
    $cardValue = $value ?? '0';
    $cardHint = $hint ?? null;
    $cardHintId = $hintId ?? null;

    $accentHexMap = [
        'primary' => '#556ee6',
        'success' => '#34c38f',
        'info' => '#50a5f1',
        'warning' => '#f1b44c',
        'danger' => '#f46a6a',
        'secondary' => '#74788d',
    ];
    $accentHex = $accentHexMap[$cardAccent] ?? $accentHexMap['primary'];
@endphp
<div class="{{ $cardCol }} mb-3">
    <div class="card outreach-stat-card h-100 mb-0" style="border-left: 4px solid {{ $accentHex }};">
        <div class="card-body">
            <div class="d-flex align-items-start justify-content-between">
                <div class="flex-grow-1 pe-2">
                    <p class="text-secondary mb-2" style="font-size: 0.8125rem;">{{ $label }}</p>
                    <h4 class="text-dark fw-bold mb-0" id="{{ $id }}">{{ $cardValue }}</h4>
                    @if(!is_null($cardHint))
                        <small class="text-secondary d-block mt-1" @if($cardHintId) id="{{ $cardHintId }}" @endif>{{ $cardHint }}</small>
                    @endif
                </div>
                <span class="rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0 text-white"
                      style="width: 44px; height: 44px; font-size: 1.35rem; background-color: {{ $accentHex }};">
                    <i class="bx {{ $cardIcon }}"></i>
                </span>
            </div>
        </div>
    </div>
</div>
