<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Schedule — {{ $schedule->title }}</title>
    <style>
        @page { size: A4; margin: 20mm 18mm 22mm; }
        * { box-sizing: border-box; }
        html, body {
            margin: 0; padding: 0;
            background: #fff;
            color: #1a1f2b;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 11.5pt;
            line-height: 1.55;
            -webkit-font-smoothing: antialiased;
            /* Stop long URLs / unbreakable strings from pushing past the page gutter. */
            word-break: break-word;
            overflow-wrap: anywhere;
        }
        body { padding: 24px 28px 40px; max-width: 100%; }
        /* Belt-and-suspenders: anything that ends up wider than the page must be clipped + scrolled rather than blowing out the layout. */
        img, table, pre { max-width: 100%; }
        img { height: auto; }
        pre, code { white-space: pre-wrap; }
        .doc-header { border-bottom: 3px solid #1a1f2b; padding-bottom: 12px; margin-bottom: 18px; }
        .doc-header h1 { margin: 0 0 4px; font-size: 22pt; font-weight: 700; letter-spacing: -0.5px; }
        .doc-header .subtitle { color: #4a5160; font-size: 11pt; }
        .doc-meta { display: flex; gap: 18px; flex-wrap: wrap; margin-top: 10px; font-size: 9.5pt; color: #6b7280; }
        .doc-meta strong { color: #1a1f2b; font-weight: 600; }
        .section { margin-top: 22px; page-break-inside: auto; }
        .section h2 {
            margin: 0 0 10px; font-size: 13pt; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.6px;
            color: #1a1f2b; border-bottom: 1px solid #d9dde3; padding-bottom: 5px;
        }
        .summary-grid { display: flex; gap: 24px; flex-wrap: wrap; font-size: 10.5pt; }
        .summary-grid > div { min-width: 130px; }
        .summary-grid .label { color: #6b7280; font-size: 9.5pt; }
        .summary-grid .value { font-size: 14pt; font-weight: 700; color: #1a1f2b; }

        .description-block { color: #2a2f3a; font-size: 10pt; }
        .description-block p { margin: 0 0 0.5em; }
        .description-block ul, .description-block ol { margin: 0.25em 0 0.5em 1.4em; padding: 0; }
        .description-block li { margin-bottom: 0.15em; }
        .description-block h1, .description-block h2, .description-block h3, .description-block h4 { font-size: 10.5pt; font-weight: 700; margin: 0.5em 0 0.25em; }

        .date-block { margin-bottom: 14px; page-break-inside: avoid; }
        .date-block .date-bar {
            display: flex; align-items: baseline; gap: 10px;
            background: #f1f3f7;
            padding: 6px 10px;
            border-left: 4px solid #1a1f2b;
            margin-bottom: 6px;
        }
        .date-block .date-bar .day { font-weight: 600; color: #4a5160; font-size: 9.5pt; text-transform: uppercase; letter-spacing: 0.8px; }
        .date-block .date-bar .date { font-weight: 700; font-size: 12pt; color: #1a1f2b; }
        .date-block .date-bar .count { margin-left: auto; color: #6b7280; font-size: 9.5pt; }

        .activity {
            padding: 8px 12px 10px;
            margin: 0 0 8px 6px;
            border-left: 2px solid #d9dde3;
            page-break-inside: avoid;
        }
        .activity-title-row { display: flex; gap: 8px; align-items: baseline; flex-wrap: wrap; }
        .activity-title { font-weight: 700; font-size: 11.5pt; color: #1a1f2b; }
        .activity-range { font-size: 9.5pt; color: #4a5160; }
        .priority-pill { font-size: 8.5pt; padding: 1px 7px; border-radius: 8px; font-weight: 600; }
        .priority-critical { background: #9c1c1c; color: #fff; font-weight: 700; text-transform: uppercase; letter-spacing: .3px; }
        .priority-high { background: #ffe1e1; color: #a82929; }
        .priority-medium { background: #fff3df; color: #8a6300; }
        .priority-low { background: #e8eaee; color: #495057; }
        .activity-line { margin-top: 4px; font-size: 10pt; color: #2a2f3a; }
        .activity-line .label { color: #6b7280; font-weight: 600; margin-right: 4px; }
        .chip { display: inline-block; padding: 1px 8px; border-radius: 10px; font-size: 9pt; margin-right: 4px; margin-bottom: 2px; max-width: 100%; word-break: break-word; }
        .chip-lot { background: #eaf0fb; color: #2c4694; }
        .chip-worker { background: #fdebd9; color: #8a5400; }
        .chip-material { background: #eaf0fb; color: #3a4699; }
        .chip-service { background: #def4ea; color: #156d4e; }

        .desc-on-card { margin-top: 6px; font-size: 10pt; }

        .lot-table, .worker-table { width: 100%; border-collapse: collapse; margin-top: 6px; font-size: 10pt; table-layout: fixed; }
        .lot-table th, .worker-table th, .lot-table td, .worker-table td { text-align: left; padding: 4px 8px; border-bottom: 1px solid #ecedf0; word-break: break-word; vertical-align: top; }
        .lot-table th, .worker-table th { color: #6b7280; font-weight: 600; font-size: 9.5pt; }
        .description-block table { width: 100%; table-layout: fixed; border-collapse: collapse; }
        .description-block td, .description-block th { word-break: break-word; padding: 2px 6px; border: 1px solid #e6e8ec; }

        footer.doc-footer { margin-top: 28px; font-size: 9pt; color: #9aa0a6; text-align: center; border-top: 1px solid #ecedf0; padding-top: 8px; }

        @media print {
            body { padding: 0; }
            a { color: inherit; text-decoration: none; }
            .page-break { page-break-before: always; }
        }
    </style>
</head>
<body>
    @php
        $sortedActivities = $schedule->activities->sortBy(function ($a) {
            $date = $a->targetDate ? \Illuminate\Support\Carbon::parse($a->targetDate)->format('Y-m-d') : 'ZZZZ-12-31';
            $seq = str_pad((string) (int) $a->sequenceOrder, 10, '0', STR_PAD_LEFT);
            return $date . '|' . $seq . '|' . str_pad((string) $a->id, 10, '0', STR_PAD_LEFT);
        })->values();
        $byDate = $sortedActivities->groupBy(function ($a) {
            return $a->targetDate ? \Illuminate\Support\Carbon::parse($a->targetDate)->format('Y-m-d') : '__no-date__';
        });
        $dateKeys = $byDate->keys()->sort()->values();

        $totalActivities = $sortedActivities->count();
        $firstDate = $sortedActivities->whereNotNull('targetDate')->first()?->targetDate;
        $lastDate = $sortedActivities->whereNotNull('targetDate')->sortByDesc(function ($a) {
            $end = $a->targetEndDate ?: $a->targetDate;
            return $end;
        })->first();
        $lastEnd = $lastDate ? ($lastDate->targetEndDate ?: $lastDate->targetDate) : null;
        $generatedAt = \Illuminate\Support\Carbon::now('Asia/Manila');
    @endphp

    <header class="doc-header">
        <h1>{{ $schedule->title }}</h1>
        @if($schedule->description)
            <div class="subtitle">{{ $schedule->description }}</div>
        @endif
        <div class="doc-meta">
            <span><strong>Status:</strong> {{ ucfirst($schedule->status) }}</span>
            <span><strong>Day Type:</strong> {{ $schedule->dayType }}</span>
            @if($firstDate)
                <span><strong>Spans:</strong>
                    {{ \Illuminate\Support\Carbon::parse($firstDate)->format('M j, Y') }}
                    @if($lastEnd) — {{ \Illuminate\Support\Carbon::parse($lastEnd)->format('M j, Y') }} @endif
                </span>
            @endif
            <span><strong>Generated:</strong> {{ $generatedAt->format('M j, Y · g:i A') }}</span>
        </div>
    </header>

    <section class="section">
        <h2>Summary</h2>
        <div class="summary-grid">
            <div><div class="label">Total Activities</div><div class="value">{{ $totalActivities }}</div></div>
            <div><div class="label">Date Groups</div><div class="value">{{ $dateKeys->filter(fn($k) => $k !== '__no-date__')->count() }}</div></div>
            <div><div class="label">Lots</div><div class="value">{{ $schedule->lots->count() }}</div></div>
            <div><div class="label">Workers</div><div class="value">{{ $schedule->workers->count() }}</div></div>
        </div>
    </section>

    @if($schedule->lots->count())
        <section class="section">
            <h2>Lots</h2>
            <table class="lot-table">
                <thead><tr><th>Name</th><th>Size</th><th>Notes</th></tr></thead>
                <tbody>
                    @foreach($schedule->lots as $lot)
                        <tr>
                            <td><strong>{{ $lot->lotName }}</strong></td>
                            <td>{{ rtrim(rtrim((string) $lot->lotSize, '0'), '.') }} {{ $lot->lotSizeUnit }}</td>
                            <td>{{ $lot->notes }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif

    @if($schedule->workers->count())
        <section class="section">
            <h2>Workers</h2>
            <table class="worker-table">
                <thead><tr><th>Priority</th><th>Name</th><th>Cost / Half Day</th><th>Notes</th></tr></thead>
                <tbody>
                    @foreach($schedule->workers->sortBy('priority') as $w)
                        <tr>
                            <td>#{{ $w->priority }}</td>
                            <td><strong>{{ $w->workerName }}</strong></td>
                            <td>₱ {{ number_format($w->costPerHalfDay, 2) }}</td>
                            <td>{{ $w->notes }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif

    <section class="section">
        <h2>Activities</h2>
        @forelse($dateKeys as $dateKey)
            @php
                $activitiesForDate = $byDate->get($dateKey);
                $dateCarbon = ($dateKey !== '__no-date__') ? \Illuminate\Support\Carbon::parse($dateKey) : null;
            @endphp
            <div class="date-block">
                <div class="date-bar">
                    @if($dateCarbon)
                        <span class="day">{{ $dateCarbon->format('D') }}</span>
                        <span class="date">{{ $dateCarbon->format('F j, Y') }}</span>
                    @else
                        <span class="date">No date assigned</span>
                    @endif
                    <span class="count">{{ $activitiesForDate->count() }} {{ \Illuminate\Support\Str::plural('activity', $activitiesForDate->count()) }}</span>
                </div>

                @foreach($activitiesForDate as $a)
                    @php
                        $endCarbon = $a->targetEndDate ? \Illuminate\Support\Carbon::parse($a->targetEndDate) : null;
                        $startCarbon = $a->targetDate ? \Illuminate\Support\Carbon::parse($a->targetDate) : null;
                        $isRange = $endCarbon && $startCarbon && $endCarbon->greaterThan($startCarbon);
                        $rangeDays = $isRange ? ($startCarbon->diffInDays($endCarbon) + 1) : 1;
                        $timeLabel = ['half' => 'Half day', 'whole' => 'Whole day', 'n/a' => 'N/A'][$a->timeRequired] ?? ucfirst($a->timeRequired);
                    @endphp
                    <div class="activity">
                        <div class="activity-title-row">
                            <span class="activity-title">{{ $a->activityTitle }}</span>
                            @if($isRange)
                                <span class="activity-range">→ {{ $endCarbon->format('M j') }} ({{ $rangeDays }} days)</span>
                            @endif
                            <span class="priority-pill priority-{{ $a->priority }}">{{ ucfirst($a->priority) }}</span>
                        </div>
                        @if($a->description)
                            <div class="desc-on-card description-block">{!! $a->description !!}</div>
                        @endif
                        <div class="activity-line">
                            <span class="label">Time:</span>{{ $timeLabel }}
                        </div>
                        @if($a->lots->count())
                            <div class="activity-line">
                                <span class="label">Lots:</span>
                                @foreach($a->lots as $lot)
                                    <span class="chip chip-lot">{{ $lot->lotName }}</span>
                                @endforeach
                            </div>
                        @endif
                        @if($a->workers->count())
                            <div class="activity-line">
                                <span class="label">Workers:</span>
                                @foreach($a->workers as $worker)
                                    <span class="chip chip-worker">{{ $worker->workerName }}</span>
                                @endforeach
                            </div>
                        @endif
                        @if($a->items->count())
                            <div class="activity-line">
                                <span class="label">Materials &amp; Services:</span>
                                @foreach($a->items as $it)
                                    @php
                                        $qtyTrim = rtrim(rtrim((string) $it->quantity, '0'), '.');
                                        $unit = $it->unitOfMeasure ?: ($it->material->unitOfMeasure ?? '');
                                    @endphp
                                    @if($it->itemType === 'material' && $it->material)
                                        <span class="chip chip-material">{{ $it->material->materialName }} ×{{ $qtyTrim }}@if($unit) {{ $unit }}@endif</span>
                                    @elseif($it->itemType === 'service' && $it->service)
                                        <span class="chip chip-service">{{ $it->service->serviceName }}@if($qtyTrim !== '1' || $unit) ×{{ $qtyTrim }}@if($unit) {{ $unit }}@endif @endif</span>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @empty
            <p style="color: #6b7280; font-style: italic;">No activities have been defined for this schedule.</p>
        @endforelse
    </section>

    <footer class="doc-footer">
        {{ $schedule->title }} — printed {{ $generatedAt->format('M j, Y · g:i A') }} from DS AXIS Schedule Manager
    </footer>
</body>
</html>
