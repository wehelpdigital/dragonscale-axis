<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Worker Presentation — {{ $schedule->title }}</title>
    <style>
        /* ===== PAGE & PRINT =====
           The @bottom-center running area carries the document attribution
           on EVERY page so we don't need a standalone footer element that
           could spill onto its own near-empty trailing page. Both portrait
           and landscape (calendar) named pages get their own footer rule.

           Bottom margin is bumped to ~24mm to give the footer space below
           the content without overlap.  */
        @page {
            size: A4 portrait;
            margin: 18mm 16mm 22mm;
            @bottom-center {
                content: "{!! addslashes($schedule->title) !!} — Worker Presentation · Generated {{ $generatedAt->format('M j, Y') }} · AniSenso by John Tugare · Page " counter(page) " of " counter(pages);
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                font-size: 8pt;
                color: #6b7280;
                /* Sit the text right under the horizontal rule (was 6mm —
                   that left a big visual gap and pushed the text down to
                   the bottom edge of the page). */
                padding-top: 2mm;
                border-top: 1px solid #e5e7eb;
                vertical-align: top;
            }
        }
        @page calendar {
            size: A4 landscape;
            /* No bottom footer on calendar pages — reclaiming the ~8mm so a
               busy month fits without spilling onto another page.
               IMPORTANT: named @page rules INHERIT margin boxes from the
               base @page, so we must explicitly empty the @bottom-center
               here or the portrait-page footer would still appear on
               landscape calendar pages. */
            margin: 14mm 12mm 12mm;
            @bottom-center { content: none; }
        }
        .cal-section { page: calendar; }

        * { box-sizing: border-box; }
        html, body {
            margin: 0; padding: 0;
            background: #f3f4f6;
            color: #1f2937;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.55;
            word-break: break-word;
            overflow-wrap: anywhere;
        }
        /* Subtle agricultural palette — applied only where it adds meaning. */

        .sheet {
            background: #fff;
            max-width: 210mm;
            margin: 16px auto;
            padding: 26px 30px 40px;
            border-radius: 4px;
            border: 1px solid #e5e7eb;
        }
        @media screen {
            .sheet { max-width: min(98vw, 1180px); }
        }
        img, table, pre { max-width: 100%; }
        pre, code { white-space: pre-wrap; }

        /* ===== ACTION BAR ===== */
        .action-bar {
            position: sticky;
            top: 0;
            z-index: 50;
            background: #2d4d1c;
            color: #fff;
            padding: 10px 18px;
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .action-bar .brand { font-size: 13px; font-weight: 600; margin-right: auto; opacity: 0.95; }
        .action-bar .zoom-group {
            display: inline-flex;
            align-items: center;
            background: rgba(255,255,255,0.1);
            border-radius: 4px;
            padding: 2px;
            margin-right: 4px;
        }
        .action-bar .zoom-group button {
            background: transparent;
            border: 0;
            color: #fff;
            width: 26px; height: 26px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            border-radius: 3px;
        }
        .action-bar .zoom-group button:hover { background: rgba(255,255,255,0.15); }
        .action-bar .zoom-label {
            min-width: 50px;
            text-align: center;
            font-size: 12px;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }
        .action-bar button.act-btn, .action-bar a.act-btn {
            border: 1px solid rgba(255,255,255,0.25);
            background: transparent;
            color: #fff;
            font-size: 12px;
            font-weight: 500;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }
        .action-bar button.act-btn:hover, .action-bar a.act-btn:hover { background: rgba(255,255,255,0.12); }
        .action-bar button.act-btn.primary { background: #5b8c3a; border-color: #5b8c3a; }
        .action-bar button.act-btn.primary:hover { background: #4a7c2e; }

        /* ===== PRINT RULES ===== */
        @media print {
            html, body { background: #fff; }
            .no-print { display: none !important; }
            .sheet {
                margin: 0;
                padding: 0;
                max-width: none;
                border-radius: 0;
                border: none;
                transform: none !important;
                zoom: 1 !important;
            }
            .page-break { page-break-before: always; break-before: page; }
            /* The standalone footer block at the document end is replaced
               by the @page running footer in print mode — otherwise we'd
               get double footers and the old orphan-page problem. */
            .doc-footer { display: none !important; }
            /* Avoid lonely first/last lines on a page. */
            p, li { orphans: 3; widows: 3; }
        }

        /* ===== UNIVERSAL PAGE-BREAK SAFETY =====
           Goal: NEVER split an atomic content unit across pages. If a unit
           won't fit on the remaining page space, push the whole thing to
           the next page (which is what break-inside: avoid + break-after:
           avoid on the preceding header tells the browser to do).
        */
        h1, h2, h3, h4 {
            page-break-after: avoid;
            break-after: avoid-page;
            page-break-inside: avoid;
            break-inside: avoid-page;
        }
        /* Tables: keep the header glued to its first row and never split
           a single row across pages. The table itself MAY span pages — the
           thead repeats automatically on each new page thanks to
           display: table-header-group. */
        table { page-break-inside: auto; break-inside: auto; }
        thead {
            display: table-header-group;
            page-break-after: avoid;
            break-after: avoid-page;
            page-break-inside: avoid;
            break-inside: avoid-page;
        }
        tfoot { display: table-footer-group; }
        tr {
            page-break-inside: avoid;
            break-inside: avoid-page;
        }
        /* Atomic blocks the layout treats as cards / one logical unit. */
        .info-card,
        .notice,
        .worker-stat,
        .irr-row,
        .doc-meta,
        .cal-legend,
        .doc-footer {
            page-break-inside: avoid;
            break-inside: avoid-page;
        }
        /* Footer must not be the only thing on a fresh page. */
        .doc-footer { page-break-before: avoid; break-before: avoid-page; }

        /* ===== TYPOGRAPHY ===== */
        h1 {
            margin: 0 0 4px;
            font-size: 22pt;
            font-weight: 700;
            color: #1f2937;
        }
        h2 {
            margin: 22px 0 12px;
            font-size: 13pt;
            font-weight: 700;
            color: #2d4d1c;
            padding: 8px 14px;
            background: #eef3e4;
            border-left: 4px solid #5b8c3a;
            border-radius: 3px;
        }
        h3 { margin: 14px 0 6px; font-size: 11pt; font-weight: 600; color: #2d4d1c; }
        h4 { margin: 10px 0 4px; font-size: 10.5pt; font-weight: 600; color: #1f2937; }
        .subtitle { color: #4b5563; font-size: 11pt; }
        .doc-meta {
            display: flex; gap: 14px; flex-wrap: wrap;
            margin-top: 10px;
            font-size: 10pt;
            color: #4b5563;
        }
        .doc-meta strong { color: #1f2937; }
        .doc-meta span { white-space: nowrap; }

        .page-break { page-break-before: always; break-before: page; }

        /* ===== INFO CARDS ===== */
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .info-card {
            border-radius: 4px;
            padding: 12px 14px;
            background: #fff;
            margin-bottom: 10px;
            border: 1px solid #e5e7eb;
            border-left: 3px solid #5b8c3a;
        }
        .info-card h3 { margin-top: 0; }
        .info-card .badge {
            display: inline-block;
            background: #f3f4f6;
            color: #374151;
            padding: 2px 9px;
            border-radius: 10px;
            font-size: 9.5pt;
            margin-right: 3px;
            margin-top: 3px;
        }
        .info-card.worker { border-left-color: #c97a14; }
        .info-card.lot    { border-left-color: #6b4423; }

        /* ===== TABLES ===== */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 4px 0 14px;
            font-size: 10.5pt;
        }
        th, td { text-align: left; padding: 6px 10px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        th { color: #374151; font-weight: 600; font-size: 9.5pt; text-transform: uppercase; letter-spacing: 0.3px; background: #f9fafb; border-bottom: 2px solid #d1d5db; }
        tr:last-child td { border-bottom: none; }
        td.num, th.num { text-align: right; font-variant-numeric: tabular-nums; }
        td.center, th.center { text-align: center; }
        .muted { color: #9ca3af; }
        tr.total-row { background: #f9fafb; }
        tr.total-row td { border-top: 2px solid #d1d5db; }

        /* ===== WEATHER NOTICE ===== */
        .notice {
            background: #fef9e7;
            border: 1px solid #f7d572;
            border-left: 4px solid #d97706;
            padding: 12px 16px;
            border-radius: 4px;
            margin: 12px 0 18px;
            font-size: 10.5pt;
            color: #5a3a00;
        }
        .notice strong { color: #5a3a00; }

        /* ===== ACTIVITIES TIMELINE =====
           Page-break strategy:
           - .date-bar MUST NOT end a page (break-after: avoid keeps it glued
             to the next activity so the date never gets orphaned).
           - Each .activity is unbreakable (break-inside: avoid) so its
             internals never split across pages.
           - .date-block has NO break-inside: avoid — if a long day spills
             across pages it just continues without ever orphaning the header.
        */
        .date-block { margin: 14px 0 16px; }
        .date-bar {
            display: flex;
            align-items: baseline;
            gap: 10px;
            background: #5b8c3a;
            color: #fff;
            padding: 7px 12px;
            border-radius: 3px;
            margin-bottom: 8px;
            page-break-after: avoid;
            break-after: avoid-page;
            page-break-inside: avoid;
            break-inside: avoid-page;
        }
        .date-bar .day { font-weight: 600; font-size: 10pt; opacity: 0.92; text-transform: uppercase; letter-spacing: 0.5px; }
        .date-bar .date { font-weight: 700; font-size: 12pt; }
        .date-bar .count {
            margin-left: auto;
            background: rgba(255,255,255,0.18);
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 9.5pt;
        }
        .date-note {
            background: #fff8e6;
            border-left: 3px solid #d9a23a;
            padding: 7px 12px;
            margin: 0 0 10px;
            font-size: 10pt;
            color: #4d3a0d;
            line-height: 1.5;
            border-radius: 0 3px 3px 0;
            page-break-inside: avoid;
            break-inside: avoid-page;
        }
        .date-note strong { color: #8a5e09; }
        .activity {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-left: 4px solid #5b8c3a;
            border-radius: 3px;
            padding: 10px 14px;
            margin: 0 0 10px;
            page-break-inside: avoid;
            break-inside: avoid-page;
        }
        .activity:last-child { margin-bottom: 0; }
        .activity.priority-critical { border-left-color: #8a1d1d; }
        .activity.priority-high     { border-left-color: #d97a4f; }
        .activity.priority-medium   { border-left-color: #5b8c3a; }
        .activity.priority-low      { border-left-color: #9ca3af; }
        .activity-title-row { display: flex; gap: 8px; align-items: baseline; flex-wrap: wrap; margin-bottom: 4px; }
        .activity-title { font-weight: 700; font-size: 11.5pt; color: #1f2937; flex: 1 1 200px; }
        .activity-range { font-size: 9.5pt; color: #4b5563; }
        .priority-pill { font-size: 8.5pt; padding: 1px 8px; border-radius: 10px; font-weight: 600; }
        .type-pill { font-size: 8.5pt; padding: 1px 8px; border-radius: 10px; font-weight: 600; background: #e2efd4; color: #2d4d1c; }
        .skill-chip { display: inline-block; font-size: 9pt; padding: 2px 9px; border-radius: 11px; background: #f0ead6; color: #6b4423; margin: 0 3px 3px 0; }
        .skill-chip-on-dark { display: inline-block; font-size: 8.5pt; padding: 2px 9px; border-radius: 11px; background: rgba(255,255,255,0.22); color: #fff; margin: 0 3px 3px 0; font-weight: 500; }
        .pill-critical { background: #8a1d1d; color: #fff; text-transform: uppercase; letter-spacing: 0.3px; }
        .pill-high     { background: #fde0d2; color: #8a3a1c; }
        .pill-medium   { background: #e2efd4; color: #2d4d1c; }
        .pill-low      { background: #e5e7eb; color: #374151; }
        .pill-d0       { background: #d97706; color: #fff; }
        .activity-line { margin-top: 4px; font-size: 10pt; color: #1f2937; display: flex; gap: 6px; align-items: baseline; flex-wrap: wrap; }
        .activity-line .label { color: #6b7280; font-weight: 600; min-width: 92px; }
        .chip {
            display: inline-block;
            padding: 1px 9px;
            border-radius: 11px;
            font-size: 9pt;
            margin-right: 3px;
            margin-bottom: 2px;
            max-width: 100%;
            word-break: break-word;
        }
        .chip-lot      { background: #f3f4f6; color: #374151; }
        .chip-worker   { background: #fef3df; color: #6b4423; }
        .chip-material { background: #eaf0fb; color: #2c4694; }
        .chip-service  { background: #def4ea; color: #156d4e; }
        .activity-desc {
            font-size: 9.5pt;
            color: #4b5563;
            margin-top: 5px;
            padding: 6px 10px;
            background: #f9fafb;
            border-radius: 3px;
            border-left: 2px solid #d1d5db;
        }

        /* ===== PER-WORKER PAGES ===== */
        .worker-page { page-break-before: always; break-before: page; margin-top: 14px; }
        .worker-page:first-of-type { page-break-before: avoid; break-before: avoid; }
        .worker-header {
            background: #5b8c3a;
            color: #fff;
            padding: 14px 18px;
            border-radius: 4px;
            margin-bottom: 14px;
            /* Header always pulls the stat row + first heading with it
               so the worker name is never alone at the bottom of a page. */
            page-break-after: avoid;
            break-after: avoid-page;
            page-break-inside: avoid;
            break-inside: avoid-page;
        }
        .worker-header .name { font-size: 18pt; font-weight: 700; line-height: 1.2; }
        .worker-header .meta { font-size: 10pt; opacity: 0.92; margin-top: 4px; }
        .worker-stat-row {
            display: flex; gap: 10px; flex-wrap: wrap; margin: 10px 0 16px;
            /* Keep all 3 stat tiles + the first h3 below them together. */
            page-break-inside: avoid;
            break-inside: avoid-page;
            page-break-after: avoid;
            break-after: avoid-page;
        }
        .worker-stat {
            flex: 1; min-width: 140px;
            border: 1px solid #e5e7eb;
            border-left: 3px solid #5b8c3a;
            border-radius: 3px;
            padding: 10px 14px;
            background: #fff;
            page-break-inside: avoid;
            break-inside: avoid-page;
        }
        .worker-stat .lbl { font-size: 9pt; color: #6b7280; text-transform: uppercase; letter-spacing: 0.4px; }
        .worker-stat .val { font-size: 15pt; font-weight: 700; color: #1f2937; margin-top: 4px; }
        .worker-stat .sub { font-size: 9pt; color: #6b7280; margin-top: 2px; }
        .worker-stat.earnings { border-left-color: #5b8c3a; }
        .worker-stat.earnings .val { color: #2d4d1c; }
        .worker-stat.days     { border-left-color: #c97a14; }
        .worker-stat.time     { border-left-color: #6b4423; }
        .time-badge {
            display: inline-block;
            padding: 1px 8px;
            border-radius: 11px;
            font-size: 8.5pt;
            font-weight: 600;
            margin-right: 2px;
        }
        .time-whole { background: #fde0d2; color: #8a3a1c; }
        .time-half  { background: #fef3df; color: #6b4423; }
        .time-na    { background: #e5e7eb; color: #374151; }

        /* ===== IRRIGATION LIST ===== */
        .irr-row {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            padding: 12px 14px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-left: 4px solid #1976d2;
            border-radius: 3px;
            margin-bottom: 10px;
            page-break-inside: avoid;
            break-inside: avoid-page;
        }
        .irr-row .das {
            background: #1976d2;
            color: #fff;
            padding: 4px 12px;
            border-radius: 14px;
            font-weight: 600;
            font-size: 9.5pt;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .irr-row .title { font-weight: 700; color: #0d47a1; font-size: 11pt; }
        .irr-row .desc { color: #4b5563; font-size: 9.5pt; margin-top: 2px; }
        .irr-coverage {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px dashed #c8def0;
            font-size: 9.5pt;
        }
        .irr-coverage .cov-head {
            font-weight: 600;
            color: #0d47a1;
            margin-bottom: 4px;
            font-size: 9pt;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .irr-group-row {
            display: flex;
            gap: 10px;
            align-items: baseline;
            flex-wrap: wrap;
            padding: 4px 0;
            border-bottom: 1px dotted #e5e7eb;
        }
        .irr-group-row:last-child { border-bottom: none; }
        .irr-group-row .grp-name { font-weight: 600; color: #1f2937; min-width: 90px; }
        .irr-group-row .grp-lots { color: #6b7280; font-size: 9pt; flex: 1 1 auto; min-width: 0; }
        .irr-group-row .grp-dates {
            background: #e7f0fb;
            color: #0d47a1;
            padding: 2px 10px;
            border-radius: 11px;
            font-size: 9pt;
            font-weight: 600;
            white-space: nowrap;
        }
        .irr-group-row.no-start .grp-dates {
            background: #f3f4f6;
            color: #6b7280;
            font-style: italic;
            font-weight: 500;
        }

        /* ===== CALENDAR ===== */
        .cal-section { padding-top: 6px; }
        .cal-legend {
            display: flex; gap: 14px; font-size: 9.5pt; color: #374151;
            margin: 8px 0 12px;
            flex-wrap: wrap;
            padding: 8px 12px;
            background: #f9fafb;
            border-radius: 3px;
            border: 1px solid #e5e7eb;
        }
        .cal-legend .sw {
            display: inline-block;
            width: 12px; height: 12px;
            border-radius: 2px;
            margin-right: 5px;
            vertical-align: middle;
        }
        .cal-month {
            page-break-inside: avoid;
            break-inside: avoid-page;
            margin-bottom: 10px;
        }
        /* Each subsequent calendar month starts on a fresh page so a busy
           month never spills its last weeks onto the next page next to a
           stray footer. The first month rides on the same page as the
           calendar legend (cheap rendering, less wasted paper). */
        .cal-month + .cal-month {
            page-break-before: always;
            break-before: page;
        }
        /* ===== CALENDAR — TABLE-BASED LAYOUT =====
           Switched from CSS Grid to a real <table> so:
           - <thead> (month name + Sun–Sat row) repeats automatically at the
             top of every continuation page
           - Each week is its own <tbody class="cal-week"> with
             break-inside: avoid — when a week doesn't fit, the WHOLE week
             gets pushed to the next page (no mid-week splits)
           - Bands span columns via <td colspan="N"> — no fragile CSS-Grid
             column-positioning in print engines
           Fonts are bumped one step up across the board. */
        .cal-grid {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            background: #fff;
            margin: 0 0 12px;
        }
        .cal-grid thead { display: table-header-group; }
        .cal-grid thead th.cal-month-title-row {
            text-align: left;
            background: #5b8c3a;
            color: #fff;
            padding: 8px 14px;
            font-size: 13pt;
            font-weight: 700;
            letter-spacing: 0.3px;
            border: none;
        }
        .cal-grid thead th.cal-headcell {
            background: #4a7c2e;
            color: #fff;
            padding: 6px 4px;
            font-size: 9.5pt;
            font-weight: 600;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            border-right: 1px solid rgba(255, 255, 255, 0.18);
            border-bottom: 1px solid #6b7280;
        }
        .cal-grid thead th.cal-headcell:last-child { border-right: none; }

        /* The unit the browser will refuse to split across pages. */
        .cal-grid tbody.cal-week {
            page-break-inside: avoid;
            break-inside: avoid-page;
        }

        .cal-grid td.cal-cell {
            background: #fff;
            height: 78px;
            padding: 5px 7px 6px;
            font-size: 9pt;
            vertical-align: top;
            border: 1px solid #d1d5db;
        }
        .cal-grid td.cal-cell.other-month { background: #f9fafb; color: #b3b8c0; }
        .cal-grid td.cal-cell .cal-day {
            font-weight: 700;
            font-size: 10.5pt;
            color: #1f2937;
            margin-bottom: 3px;
            display: inline-block;
            padding: 1px 8px;
            border-radius: 3px;
            background: #f3f4f6;
        }
        .cal-grid td.cal-cell.other-month .cal-day { background: transparent; color: #b3b8c0; }
        .cal-grid td.cal-cell.weekend .cal-day { background: #fef3df; color: #6b4423; }
        .cal-grid td.cal-cell.today .cal-day { background: #5b8c3a; color: #fff; }

        /* Band rows live INSIDE the week's <tbody> as <tr class="cal-band-row">.
           Each band spans its date columns via <td colspan="N">. */
        .cal-grid tr.cal-band-row > td {
            padding: 2px 3px;
            border: 1px solid #d1d5db;
            border-top: none;
            border-bottom: none;
            vertical-align: middle;
        }
        .cal-grid tr.cal-band-row.act > td { background: #f4f7ee; }
        .cal-grid tr.cal-band-row.irr > td { background: #eaf4f9; }
        .cal-grid tr.cal-band-row > td.cal-band-cell-empty { background: transparent; border-color: transparent; }
        .cal-grid tr.cal-band-row.act > td.cal-band-cell-empty { background: #f4f7ee; }
        .cal-grid tr.cal-band-row.irr > td.cal-band-cell-empty { background: #eaf4f9; }

        .cal-act-band, .cal-irr-band {
            color: #fff;
            font-size: 8pt;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 2px;
            line-height: 1.2;
            display: flex;
            align-items: center;
            gap: 5px;
            min-width: 0;
            box-sizing: border-box;
        }
        .cal-irr-band .drop {
            flex-shrink: 0;
            font-size: 9pt;
            line-height: 1;
        }
        .cal-act-band .lbl, .cal-irr-band .lbl {
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
            min-width: 0;
            line-height: 1.2;
        }
        .cal-act-band .lbl .band-title { font-weight: 700; }
        .cal-act-band .lbl .band-workers {
            display: block;
            font-size: 7pt;
            opacity: 0.92;
            font-weight: 500;
            margin-top: 2px;
        }

        /* Activity chip rendered inside a single day cell (single-day events). */
        .cal-act {
            display: block;
            background: #5b8c3a;
            color: #fff;
            padding: 2px 6px 3px;
            margin-top: 2px;
            border-radius: 2px;
            font-size: 8pt;
            line-height: 1.25;
            word-break: break-word;
            white-space: normal;
        }
        .cal-act.pri-critical { background: #8a1d1d; }
        .cal-act.pri-high     { background: #c95a35; }
        .cal-act.pri-medium   { background: #5b8c3a; }
        .cal-act.pri-low      { background: #6b7280; }
        .cal-act .cal-act-title { font-weight: 700; display: block; }
        .cal-act .cal-act-workers {
            display: block;
            font-size: 7pt;
            opacity: 0.92;
            font-weight: 500;
            margin-top: 1px;
            line-height: 1.2;
        }

        footer.doc-footer {
            margin-top: 24px;
            font-size: 9pt;
            color: #9ca3af;
            text-align: center;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>
    @php
        // Carry every option toggle over to the PDF route so the printed
        // file matches exactly what the user is previewing on screen.
        $pdfUrl = route('anisenso-schedule-manager.worker-presentation.pdf')
            . '?scheduleId=' . $schedule->id
            . '&showDesc=' . ($showDescriptions ? '1' : '0')
            . '&showIrrigation=' . ($showIrrigation ? '1' : '0')
            . '&showCalendar=' . ($showCalendar ? '1' : '0');
    @endphp
    <div class="action-bar no-print">
        <div class="brand">Worker Presentation — {{ $schedule->title }}</div>
        <div class="zoom-group" role="group" aria-label="Zoom">
            <button type="button" onclick="adjustZoom(-10)" title="Zoom out">−</button>
            <span class="zoom-label" id="zoomLabel">130%</span>
            <button type="button" onclick="adjustZoom(10)" title="Zoom in">+</button>
        </div>
        <button type="button" class="act-btn" onclick="resetZoom()" title="Reset to default zoom">Reset</button>
        <button type="button" class="act-btn" onclick="window.close()">Close</button>
        <button type="button" class="act-btn" onclick="window.print()" title="Use the browser's built-in print dialog">
            Print
        </button>
        <button type="button" class="act-btn primary" id="downloadPdfBtn" title="Download a server-generated PDF (renders the calendar identically to this preview)">
            Download PDF
        </button>
    </div>

    <div class="sheet" id="sheet">
        {{-- Section 1: Introduction --}}
        <h1>{{ $schedule->title }}</h1>
        @if($schedule->description)
            <div class="subtitle">{!! nl2br(e($schedule->description)) !!}</div>
        @endif
        <div class="doc-meta">
            @if($firstDate && $lastDate)
                <span><strong>Season:</strong> {{ $firstDate->format('M j, Y') }} → {{ $lastDate->format('M j, Y') }}</span>
            @endif
            <span><strong>Day Type:</strong> {{ $schedule->dayType }}</span>
            <span><strong>Status:</strong> {{ ucfirst($schedule->status) }}</span>
            <span><strong>Generated:</strong> {{ $generatedAt->format('M j, Y · g:i A') }}</span>
        </div>

        <p style="margin-top: 14px;">
            This presentation summarizes the cropping plan for the upcoming season — how the lots are grouped, who's
            working it, the activity schedule, expected labor allocation, and irrigation timing. Hand the relevant pages
            to each worker so everyone understands their commitments.
        </p>

        <h2>Lot Groups</h2>
        @if($schedule->defaultGroupings->count() === 0)
            <p class="muted">No groups defined for this schedule.</p>
        @else
            <p>The schedule covers <strong>{{ $schedule->defaultGroupings->count() }}</strong>
               {{ \Illuminate\Support\Str::plural('group', $schedule->defaultGroupings->count()) }} of lots:</p>
            <div class="grid-2">
                @foreach($schedule->defaultGroupings as $group)
                    <div class="info-card">
                        <h3>{{ $group->groupName }}</h3>
                        @if($group->startDate)
                            <div style="font-size: 9.5pt; color: #4b5563; margin-bottom: 4px;">
                                Start: <strong>{{ \Illuminate\Support\Carbon::parse($group->startDate)->format('M j, Y') }}</strong>
                                @if((int) $group->staggerDays !== 0) · Stagger: {{ $group->staggerDays }} days @endif
                            </div>
                        @elseif((int) $group->staggerDays !== 0)
                            <div style="font-size: 9.5pt; color: #4b5563; margin-bottom: 4px;">
                                Stagger: {{ $group->staggerDays }} days
                            </div>
                        @endif
                        @if($group->lots->count())
                            @foreach($group->lots as $lot)
                                <span class="badge">{{ $lot->lotName }}</span>
                            @endforeach
                        @else
                            <span class="muted">No lots assigned.</span>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        @if($schedule->lots->count() > 0)
            <h2>Lots</h2>
            <table>
                <thead>
                    <tr>
                        <th>Lot</th>
                        <th>Size</th>
                        <th>{{ $schedule->dayType }} 0 anchor</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($schedule->lots as $lot)
                        <tr>
                            <td><strong>{{ $lot->lotName }}</strong></td>
                            <td>{{ rtrim(rtrim((string) $lot->lotSize, '0'), '.') }} {{ $lot->lotSizeUnit }}</td>
                            <td>
                                @if(isset($lotDayZero[$lot->id]))
                                    {{ $lotDayZero[$lot->id]->format('M j, Y') }}
                                @else
                                    <span class="muted">— not set —</span>
                                @endif
                            </td>
                            <td>{{ $lot->notes }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if($schedule->workers->count() > 0)
            @php $skillsCatalog = \App\Models\AsScheduleWorker::SKILLS; @endphp
            <h2 class="page-break">Workers</h2>
            <p>The crop will be worked by <strong>{{ $schedule->workers->count() }}</strong>
               {{ \Illuminate\Support\Str::plural('worker', $schedule->workers->count()) }}.
               Per-worker breakdowns appear in their own pages later in this document.</p>
            <table>
                <thead>
                    <tr>
                        <th class="center">Priority</th>
                        <th>Name</th>
                        <th class="num">Half-day rate</th>
                        <th>Skills</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($schedule->workers as $w)
                        @php $wSkills = is_array($w->skills) ? $w->skills : []; @endphp
                        <tr>
                            <td class="center">#{{ $w->priority }}</td>
                            <td><strong>{{ $w->workerName }}</strong></td>
                            <td class="num">₱ {{ number_format((float) $w->costPerHalfDay, 2) }}</td>
                            <td>
                                @if(count($wSkills) === 0)
                                    <span class="muted">—</span>
                                @else
                                    @foreach($wSkills as $k)
                                        @if(isset($skillsCatalog[$k]))
                                            <span class="skill-chip">{{ $skillsCatalog[$k] }}</span>
                                        @endif
                                    @endforeach
                                @endif
                            </td>
                            <td>{{ $w->notes }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        {{-- Section 2: Schedule of Activities --}}
        <h2 class="page-break">Schedule of Activities</h2>
        <div class="notice">
            <strong>Weather flexibility.</strong>
            These activity schedules are <strong>not fixed</strong>. They may be <strong>changed, combined, or cancelled</strong>
            depending on weather conditions (rain, typhoon, drought), pest pressure, market timing, or any other field condition.
            Treat the dates below as the planned baseline. Always confirm with the field supervisor before mobilizing for any activity.
        </div>

        @php
            $sortedAct = $schedule->activities->sortBy(function ($a) {
                $date = $a->targetDate ? $a->targetDate->format('Y-m-d') : 'ZZZZ-12-31';
                $seq = str_pad((string) (int) $a->sequenceOrder, 10, '0', STR_PAD_LEFT);
                return $date . '|' . $seq . '|' . str_pad((string) $a->id, 10, '0', STR_PAD_LEFT);
            })->values();
            $actByDate = $sortedAct->groupBy(function ($a) {
                return $a->targetDate ? $a->targetDate->format('Y-m-d') : '__no-date__';
            });
            $actKeys = $actByDate->keys()->sort()->values();

            // Index per-date notes once so every block lookup is O(1).
            $presentationNotesByDate = $schedule->dateNotes->keyBy(fn ($n) => $n->noteDate->format('Y-m-d'));
        @endphp

        @forelse($actKeys as $dateKey)
            @php
                $bucket = $actByDate->get($dateKey);
                $dateCarbon = $dateKey !== '__no-date__' ? \Illuminate\Support\Carbon::parse($dateKey) : null;
                $presentationNote = $presentationNotesByDate->get($dateKey);
            @endphp
            <div class="date-block">
                <div class="date-bar">
                    @if($dateCarbon)
                        <span class="day">{{ $dateCarbon->format('D') }}</span>
                        <span class="date">{{ $dateCarbon->format('F j, Y') }}</span>
                    @else
                        <span class="date">No date assigned</span>
                    @endif
                    <span class="count">{{ $bucket->count() }} {{ \Illuminate\Support\Str::plural('activity', $bucket->count()) }}</span>
                </div>
                @if($presentationNote)
                    <div class="date-note">
                        <strong>Note:</strong> {!! nl2br(e($presentationNote->noteContent)) !!}
                    </div>
                @endif
                @foreach($bucket as $a)
                    @php
                        $endC = $a->targetEndDate ?: null;
                        $startC = $a->targetDate;
                        $isRange = $endC && $startC && $endC->greaterThan($startC);
                        $rangeDays = $isRange ? ($startC->diffInDays($endC) + 1) : 1;
                        $timeLabel = ['half' => 'Half day', 'whole' => 'Whole day', 'n/a' => 'N/A'][$a->timeRequired] ?? ucfirst($a->timeRequired);
                    @endphp
                    <div class="activity priority-{{ $a->priority }}">
                        <div class="activity-title-row">
                            <span class="activity-title">{{ $a->activityTitle }}</span>
                            @if($isRange)
                                <span class="activity-range">→ {{ $endC->format('M j') }} ({{ $rangeDays }}d)</span>
                            @endif
                            @if($a->activityType && isset(\App\Models\AsScheduleActivity::ACTIVITY_TYPES[$a->activityType]))
                                <span class="type-pill">{{ \App\Models\AsScheduleActivity::ACTIVITY_TYPES[$a->activityType] }}</span>
                            @endif
                            <span class="priority-pill pill-{{ $a->priority }}">{{ ucfirst($a->priority) }}</span>
                            @if($a->isDayZero)
                                <span class="priority-pill pill-d0">{{ $schedule->dayType }} 0</span>
                            @endif
                        </div>
                        @if($showDescriptions && $a->description)
                            <div class="activity-desc">{!! $a->description !!}</div>
                        @endif
                        <div class="activity-line">
                            <span class="label">Time:</span>
                            <span>{{ $timeLabel }}</span>
                        </div>
                        @if($a->lots->count())
                            <div class="activity-line">
                                <span class="label">Lots:</span>
                                <span>
                                    @foreach($a->lots as $lot)
                                        <span class="chip chip-lot">{{ $lot->lotName }}</span>
                                    @endforeach
                                </span>
                            </div>
                        @endif
                        @if($a->workers->count())
                            <div class="activity-line">
                                <span class="label">Workers:</span>
                                <span>
                                    @foreach($a->workers as $worker)
                                        <span class="chip chip-worker">{{ $worker->workerName }}</span>
                                    @endforeach
                                </span>
                            </div>
                        @endif
                        @if($a->items->count())
                            <div class="activity-line">
                                <span class="label">Materials/Services:</span>
                                <span>
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
                                </span>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @empty
            <p class="muted" style="font-style: italic;">No activities defined for this schedule.</p>
        @endforelse

        {{-- Section 3: Monthly labor (counts only) --}}
        <h2 class="page-break">Labor Counts per Month</h2>
        <p>
            The table below shows the <strong>number of calendar days each month</strong> that have any scheduled activity.
            If multiple workers share the same day, it still counts as one day (not multiplied by the worker count).
            Per-worker breakdowns and monetary details appear in each worker's individual page.
        </p>
        @if(count($aggregateMonthly) === 0)
            <p class="muted">No labor commitments scheduled yet.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Month</th>
                        <th class="num">Days</th>
                    </tr>
                </thead>
                <tbody>
                    @php $monthlyTotal = 0; @endphp
                    @foreach($aggregateMonthly as $monthKey => $count)
                        @php
                            $monthlyTotal += $count;
                            $monthCarbon = \Illuminate\Support\Carbon::createFromFormat('Y-m', $monthKey);
                        @endphp
                        <tr>
                            <td>{{ $monthCarbon->format('F Y') }}</td>
                            <td class="num">{{ $count }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td><strong>Season Total</strong></td>
                        <td class="num"><strong>{{ $monthlyTotal }}</strong></td>
                    </tr>
                </tbody>
            </table>
        @endif

        {{-- Section 4: Per-worker pages --}}
        @php $skillsCatalogForPages = \App\Models\AsScheduleWorker::SKILLS; @endphp
        @foreach($workerStats as $stats)
            @php
                $w = $stats['worker'];
                $wSkills = is_array($w->skills) ? $w->skills : [];
            @endphp
            <section class="worker-page">
                <div class="worker-header">
                    <div class="name">{{ $w->workerName }}</div>
                    <div class="meta">
                        Priority #{{ $w->priority }} ·
                        ₱{{ number_format((float) $w->costPerHalfDay, 2) }} per half-day
                        @if($w->notes)
                            · {{ $w->notes }}
                        @endif
                    </div>
                    @if(count($wSkills) > 0)
                        <div class="meta" style="margin-top: 6px;">
                            <strong>Skills:</strong>
                            @foreach($wSkills as $k)
                                @if(isset($skillsCatalogForPages[$k]))
                                    <span class="skill-chip-on-dark">{{ $skillsCatalogForPages[$k] }}</span>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="worker-stat-row">
                    <div class="worker-stat days">
                        <div class="lbl">Total Work Days</div>
                        <div class="val">{{ $stats['totalDays'] }}</div>
                        <div class="sub">for the entire season</div>
                    </div>
                    <div class="worker-stat time">
                        <div class="lbl">Time Breakdown</div>
                        <div class="val" style="font-size: 12pt; font-weight: 600; line-height: 1.6;">
                            <span class="time-badge time-whole">{{ $stats['wholeCount'] }} Whole</span>
                            <span class="time-badge time-half">{{ $stats['halfCount'] }} Half</span>
                            @if($stats['naCount'] > 0)
                                <span class="time-badge time-na">{{ $stats['naCount'] }} N/A</span>
                            @endif
                        </div>
                        <div class="sub">{{ $stats['units'] }} half-day units total</div>
                    </div>
                    <div class="worker-stat earnings">
                        <div class="lbl">Total Earnings</div>
                        <div class="val">₱ {{ number_format($stats['earnings'], 2) }}</div>
                        <div class="sub">{{ $stats['units'] }} × ₱{{ number_format((float) $w->costPerHalfDay, 2) }}</div>
                    </div>
                </div>

                <h3>Work Days by Month</h3>
                @if(count($stats['byMonth']) === 0)
                    <p class="muted">No work days scheduled for {{ $w->workerName }}.</p>
                @else
                    <table>
                        <thead>
                            <tr><th>Month</th><th class="num">Work Days</th></tr>
                        </thead>
                        <tbody>
                            @foreach($stats['byMonth'] as $monthKey => $count)
                                @php $monthCarbon = \Illuminate\Support\Carbon::createFromFormat('Y-m', $monthKey); @endphp
                                <tr>
                                    <td>{{ $monthCarbon->format('F Y') }}</td>
                                    <td class="num">{{ $count }}</td>
                                </tr>
                            @endforeach
                            <tr class="total-row">
                                <td><strong>Total</strong></td>
                                <td class="num"><strong>{{ $stats['totalDays'] }}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                @endif

                @if(count($stats['workDays']) > 0)
                    <h3>All Scheduled Work Days</h3>
                    <p style="font-size: 9.5pt; color: #6b7280;">
                        Dates listed individually — activity titles are intentionally hidden so this page can be handed
                        to {{ $w->workerName }} privately.
                    </p>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Day</th>
                                <th class="center">Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stats['workDays'] as $wd)
                                <tr>
                                    <td>{{ $wd['date']->format('M j, Y') }}</td>
                                    <td class="muted">{{ $wd['date']->format('l') }}</td>
                                    <td class="center">
                                        @if($wd['timeRequired'] === 'whole')
                                            <span class="time-badge time-whole">Whole</span>
                                        @elseif($wd['timeRequired'] === 'half')
                                            <span class="time-badge time-half">Half</span>
                                        @else
                                            <span class="time-badge time-na">N/A</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>
        @endforeach

        {{-- Section 5: Irrigation Schedules (gated by modal toggle) --}}
        @if($showIrrigation)
        <h2 class="page-break">Irrigation Schedules</h2>
        @if($schedule->irrigations->count() === 0)
            <p class="muted">No irrigation schedules defined.</p>
        @else
            <p>
                Irrigation cycles are defined as <strong>{{ $schedule->dayType }}</strong> ranges relative to each group's
                Day 0 anchor. The calendar later in this document maps each cycle to its actual calendar dates per group.
            </p>
            {{-- Task-type legend so workers know what each color means at a glance --}}
            <div style="display:flex; flex-wrap:wrap; gap:8px; margin: 6px 0 12px;">
                @foreach(\App\Models\AsScheduleIrrigation::TASK_TYPES as $slug => $label)
                    @php $tm = \App\Models\AsScheduleIrrigation::taskTypeMeta($slug); @endphp
                    <span style="display:inline-flex; align-items:center; gap:4px; font-size:9.5pt; padding:3px 10px; border-radius:11px; background: {{ $tm['color'] }}; color:#fff; font-weight:600;">
                        {{ $tm['icon'] }} {{ $tm['label'] }}
                    </span>
                @endforeach
            </div>

            @foreach($schedule->irrigations as $irrigation)
                @php $irrMeta = \App\Models\AsScheduleIrrigation::taskTypeMeta($irrigation->taskType); @endphp
                <div class="irr-row" style="border-left-color: {{ $irrMeta['color'] }};">
                    <span class="das" style="background: {{ $irrMeta['color'] }};">{{ $irrMeta['icon'] }} {{ $schedule->dayType }} {{ $irrigation->startDay }}–{{ $irrigation->endDay }}</span>
                    <div style="flex: 1; min-width: 0;">
                        <div class="title">
                            {{ $irrigation->irrigationTitle }}
                            <span style="display:inline-block; font-size:9pt; padding:2px 8px; border-radius:10px; background: {{ $irrMeta['color'] }}; color:#fff; font-weight:600; margin-left:6px;">
                                {{ $irrMeta['icon'] }} {{ $irrMeta['label'] }}
                            </span>
                        </div>
                        @if($irrigation->description)
                            <div class="desc">{{ $irrigation->description }}</div>
                        @endif
                        @if($irrigation->assignedWorker)
                            <div class="desc">
                                <strong>Assigned:</strong> {{ $irrigation->assignedWorker->workerName }}
                            </div>
                        @endif

                        @if($schedule->defaultGroupings->count() > 0)
                            <div class="irr-coverage">
                                <div class="cov-head">Calendar Coverage by Group</div>
                                @foreach($schedule->defaultGroupings as $group)
                                    @php
                                        $groupStart = $group->startDate
                                            ? \Illuminate\Support\Carbon::parse($group->startDate)
                                            : null;
                                        $hasDates = $groupStart !== null;
                                        $cycleStart = $hasDates ? $groupStart->copy()->addDays((int) $irrigation->startDay) : null;
                                        $cycleEnd   = $hasDates ? $groupStart->copy()->addDays((int) $irrigation->endDay)   : null;
                                        $cycleDays  = $hasDates ? ($cycleStart->diffInDays($cycleEnd) + 1) : 0;
                                        $lotNames = $group->lots->pluck('lotName')->implode(', ');
                                    @endphp
                                    <div class="irr-group-row {{ $hasDates ? '' : 'no-start' }}">
                                        <span class="grp-name">{{ $group->groupName }}</span>
                                        <span class="grp-lots">
                                            @if($lotNames)
                                                ({{ $lotNames }})
                                            @else
                                                (no lots)
                                            @endif
                                        </span>
                                        @if($hasDates)
                                            <span class="grp-dates">
                                                {{ $cycleStart->format('M j, Y') }}@if($cycleDays > 1) → {{ $cycleEnd->format('M j, Y') }} ({{ $cycleDays }}d)@endif
                                            </span>
                                        @else
                                            <span class="grp-dates">no group start date — dates unavailable</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        @endif
        @endif {{-- /Section 5 showIrrigation --}}
    </div>

    {{-- Section 6: Calendar (landscape, gated by modal toggle) --}}
    @if($showCalendar)
    <div class="sheet cal-section">
        <h2 style="page-break-before: always; break-before: page; margin-top: 0;">Calendar</h2>
        <div class="cal-legend">
            <strong style="font-size:9pt; color:#374151; margin-right:6px;">Activity priority:</strong>
            <span><span class="sw" style="background:#8a1d1d;"></span>Critical</span>
            <span><span class="sw" style="background:#c95a35;"></span>High</span>
            <span><span class="sw" style="background:#5b8c3a;"></span>Medium</span>
            <span><span class="sw" style="background:#6b7280;"></span>Low</span>
        </div>
        <div class="cal-legend" style="margin-top: 6px;">
            <strong style="font-size:9pt; color:#374151; margin-right:6px;">Irrigation task type:</strong>
            @foreach(\App\Models\AsScheduleIrrigation::TASK_TYPES as $slug => $label)
                @php $tmLegend = \App\Models\AsScheduleIrrigation::taskTypeMeta($slug); @endphp
                <span><span class="sw" style="background: {{ $tmLegend['color'] }};"></span>{{ $tmLegend['icon'] }} {{ $tmLegend['label'] }}</span>
            @endforeach
            <span class="muted">Bands span the cycle's calendar dates per group.</span>
        </div>

        @if(count($calendarMonths) === 0)
            <p class="muted">Nothing scheduled — the calendar is empty.</p>
        @else
            @foreach($calendarMonths as $monthCursor)
                @php
                    $firstOfMonth = $monthCursor->copy()->startOfMonth();
                    $lastOfMonth  = $monthCursor->copy()->endOfMonth();
                    $gridStart = $firstOfMonth->copy();
                    while ($gridStart->dayOfWeek !== \Carbon\Carbon::SUNDAY) {
                        $gridStart->subDay();
                    }
                    $weeksToRender = (int) ceil(($gridStart->diffInDays($lastOfMonth) + 1) / 7);
                    if ($weeksToRender < 5) $weeksToRender = 5;
                    if ($weeksToRender > 6) $weeksToRender = 6;
                @endphp
                <div class="cal-month">
                    <table class="cal-grid">
                        <thead>
                            <tr>
                                <th colspan="7" class="cal-month-title-row">{{ $monthCursor->format('F Y') }}</th>
                            </tr>
                            <tr>
                                @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $dayName)
                                    <th class="cal-headcell">{{ $dayName }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        @php $cellCursor = $gridStart->copy(); @endphp
                        @for($week = 0; $week < $weeksToRender; $week++)
                            @php
                                $weekSunday = $cellCursor->copy();
                                $weekKey    = $weekSunday->format('Y-m-d');
                                $actBands   = $activityBandsByWeek[$weekKey] ?? [];
                                $irrBands   = $irrigationBandsByWeek[$weekKey] ?? [];

                                // Group bands by packed-row so we render one
                                // <tr> per row. Inside each <tr>, bands span
                                // their date-columns via <td colspan>.
                                $actByRow = [];
                                foreach ($actBands as $b) { $actByRow[$b['row']][] = $b; }
                                ksort($actByRow);
                                $irrByRow = [];
                                foreach ($irrBands as $b) { $irrByRow[$b['row']][] = $b; }
                                ksort($irrByRow);
                            @endphp
                            <tbody class="cal-week">
                                {{-- Activity band rows (multi-day ranges that overlap this week). --}}
                                @foreach($actByRow as $rowBands)
                                    @php
                                        usort($rowBands, fn($x, $y) => $x['startCol'] <=> $y['startCol']);
                                        $colCursor = 1;
                                    @endphp
                                    <tr class="cal-band-row act">
                                        @foreach($rowBands as $band)
                                            @if($colCursor < $band['startCol'])
                                                <td colspan="{{ $band['startCol'] - $colCursor }}" class="cal-band-cell-empty"></td>
                                            @endif
                                            @php
                                                $span = $band['endCol'] - $band['startCol'] + 1;
                                                $bandRangeLabel = $band['totalStart']->format('M j');
                                                if (!$band['totalStart']->equalTo($band['totalEnd'])) {
                                                    $bandRangeLabel .= '–' . $band['totalEnd']->format('M j');
                                                }
                                            @endphp
                                            <td colspan="{{ $span }}" class="cal-band-cell">
                                                <div class="cal-act-band"
                                                     style="background: {{ $band['color'] }};"
                                                     title="{{ $band['title'] }} · {{ ucfirst($band['priority']) }} · {{ $bandRangeLabel }}">
                                                    <span class="lbl">
                                                        <span class="band-title">{{ $band['title'] }} <span style="opacity:.85;font-weight:500;">· {{ $bandRangeLabel }} ↓</span></span>
                                                        @if($band['workers'])
                                                            <span class="band-workers">{{ $band['workers'] }}</span>
                                                        @endif
                                                    </span>
                                                </div>
                                            </td>
                                            @php $colCursor = $band['endCol'] + 1; @endphp
                                        @endforeach
                                        @if($colCursor <= 7)
                                            <td colspan="{{ 7 - $colCursor + 1 }}" class="cal-band-cell-empty"></td>
                                        @endif
                                    </tr>
                                @endforeach

                                {{-- Irrigation band rows. --}}
                                @foreach($irrByRow as $rowBands)
                                    @php
                                        usort($rowBands, fn($x, $y) => $x['startCol'] <=> $y['startCol']);
                                        $colCursor = 1;
                                    @endphp
                                    <tr class="cal-band-row irr">
                                        @foreach($rowBands as $band)
                                            @if($colCursor < $band['startCol'])
                                                <td colspan="{{ $band['startCol'] - $colCursor }}" class="cal-band-cell-empty"></td>
                                            @endif
                                            @php $span = $band['endCol'] - $band['startCol'] + 1; @endphp
                                            <td colspan="{{ $span }}" class="cal-band-cell">
                                                <div class="cal-irr-band"
                                                     style="background: {{ $band['color'] }};"
                                                     title="{{ $band['taskLabel'] ?? 'Irrigate' }} — {{ $band['title'] }} · {{ $band['groupName'] }} · {{ $schedule->dayType }} {{ $band['dasStart'] }}–{{ $band['dasEnd'] }}">
                                                    <span class="drop">{{ $band['taskIcon'] ?? '💧' }}</span>
                                                    <span class="lbl">{{ $band['title'] }} · {{ $band['groupName'] }} · {{ $band['startDate']->format('M j') }}@if(!$band['startDate']->equalTo($band['endDate']))–{{ $band['endDate']->format('M j') }}@endif ↓</span>
                                                </div>
                                            </td>
                                            @php $colCursor = $band['endCol'] + 1; @endphp
                                        @endforeach
                                        @if($colCursor <= 7)
                                            <td colspan="{{ 7 - $colCursor + 1 }}" class="cal-band-cell-empty"></td>
                                        @endif
                                    </tr>
                                @endforeach

                                {{-- Day cells row — the 7 days of this week. --}}
                                <tr class="cal-day-row">
                                    @for($dow = 0; $dow < 7; $dow++)
                                        @php
                                            $key = $cellCursor->format('Y-m-d');
                                            $inMonth = $cellCursor->month == $monthCursor->month && $cellCursor->year == $monthCursor->year;
                                            $isWeekend = $cellCursor->dayOfWeek == 0 || $cellCursor->dayOfWeek == 6;
                                            $acts = $activitiesByDate[$key] ?? [];
                                        @endphp
                                        <td class="cal-cell {{ $inMonth ? '' : 'other-month' }} {{ $isWeekend ? 'weekend' : '' }}">
                                            <div class="cal-day">{{ $cellCursor->day }}</div>
                                            @foreach($acts as $act)
                                                @php
                                                    $prClass = 'pri-medium';
                                                    if ($act->priority === 'critical') $prClass = 'pri-critical';
                                                    elseif ($act->priority === 'high') $prClass = 'pri-high';
                                                    elseif ($act->priority === 'low')  $prClass = 'pri-low';
                                                    $workerList = $act->workers->pluck('workerName')->implode(', ');
                                                @endphp
                                                <span class="cal-act {{ $prClass }}">
                                                    <span class="cal-act-title">{{ $act->activityTitle }}</span>
                                                    @if($workerList)
                                                        <span class="cal-act-workers">{{ $workerList }}</span>
                                                    @endif
                                                </span>
                                            @endforeach
                                        </td>
                                        @php $cellCursor->addDay(); @endphp
                                    @endfor
                                </tr>
                            </tbody>
                        @endfor
                    </table>
                </div>
            @endforeach
        @endif

        <footer class="doc-footer">
            {{ $schedule->title }} — Worker Presentation · Generated {{ $generatedAt->format('M j, Y · g:i A') }} ·
            AniSenso by John Tugare
        </footer>
    </div>
    @endif {{-- /Section 6 showCalendar --}}

    <script>
        const ZOOM_MIN = 75, ZOOM_MAX = 200, ZOOM_DEFAULT = 130;
        let zoom = parseInt(sessionStorage.getItem('wp-zoom') || String(ZOOM_DEFAULT), 10);

        function applyZoom() {
            document.querySelectorAll('.sheet').forEach(s => { s.style.zoom = (zoom / 100); });
            const label = document.getElementById('zoomLabel');
            if (label) label.textContent = zoom + '%';
            sessionStorage.setItem('wp-zoom', String(zoom));
        }
        function adjustZoom(delta) { zoom = Math.max(ZOOM_MIN, Math.min(ZOOM_MAX, zoom + delta)); applyZoom(); }
        function resetZoom() { zoom = ZOOM_DEFAULT; applyZoom(); }
        applyZoom();

        document.addEventListener('keydown', function (e) {
            if (!(e.ctrlKey || e.metaKey)) return;
            if (e.key === '=' || e.key === '+') { e.preventDefault(); adjustZoom(10); }
            else if (e.key === '-') { e.preventDefault(); adjustZoom(-10); }
            else if (e.key === '0') { e.preventDefault(); resetZoom(); }
            else if (e.key === 'p' || e.key === 'P') { e.preventDefault(); window.print(); }
        });

        // Drop the zoom during print so the PDF is 1:1, then restore after.
        let printedZoom = null;
        window.addEventListener('beforeprint', function () {
            printedZoom = zoom;
            document.querySelectorAll('.sheet').forEach(s => { s.style.zoom = 1; });
        });
        window.addEventListener('afterprint', function () {
            if (printedZoom !== null) {
                zoom = printedZoom;
                applyZoom();
                printedZoom = null;
            }
        });

        // Direct PDF download — hits the server-side route that renders the
        // presentation through headless Chrome and streams back a real PDF.
        const PDF_URL = @json($pdfUrl);
        const $pdfBtn = document.getElementById('downloadPdfBtn');
        if ($pdfBtn) {
            $pdfBtn.addEventListener('click', function () {
                const original = $pdfBtn.textContent;
                $pdfBtn.disabled = true;
                $pdfBtn.textContent = 'Generating PDF…';
                // Use a hidden iframe so the browser handles the file
                // download without leaving the current tab.
                let frame = document.getElementById('pdfDownloadFrame');
                if (!frame) {
                    frame = document.createElement('iframe');
                    frame.id = 'pdfDownloadFrame';
                    frame.style.display = 'none';
                    document.body.appendChild(frame);
                }
                frame.src = PDF_URL + '&t=' + Date.now();
                // Re-enable after a generous wait — Chrome headless takes
                // 3–6 seconds for a full presentation.
                setTimeout(() => {
                    $pdfBtn.disabled = false;
                    $pdfBtn.textContent = original;
                }, 9000);
            });
        }
    </script>
</body>
</html>
