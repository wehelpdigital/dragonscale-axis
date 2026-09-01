@extends('layouts.master')

@section('title') Setup — {{ $schedule->title }} @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
{{-- Quill rich-text editor (replaces TinyMCE — MIT licensed, no usage cap) --}}
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet" />
<style>
    /* Quill editor host + visual tuning to match the rest of the form */
    .sm-quill-wrap { background: #fff; }
    .sm-quill-wrap .ql-toolbar {
        border-color: #ced4da; border-top-left-radius: 4px; border-top-right-radius: 4px;
        background: #f8f9fa;
    }
    .sm-quill-wrap .ql-container {
        border-color: #ced4da;
        border-bottom-left-radius: 4px; border-bottom-right-radius: 4px;
        min-height: 200px; font-size: 13px;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
    }
    .sm-quill-wrap .ql-editor { min-height: 200px; }
    .sm-quill-wrap .ql-editor h1,
    .sm-quill-wrap .ql-editor h2,
    .sm-quill-wrap .ql-editor h3,
    .sm-quill-wrap .ql-editor h4 { margin: .5em 0 .25em; }
    .sm-quill-wrap .ql-editor ul,
    .sm-quill-wrap .ql-editor ol { padding-left: 1.4rem; }
    /* HTML source-edit textarea sits next to the Quill mount; we toggle
       which one is visible via the description-mode handler.
       Note: textarea.form-control has Bootstrap's `display: block` rule
       at equal specificity, so we use a child combinator + !important
       to make sure the hide/show toggle wins regardless of stylesheet
       load order. */
    .sm-quill-wrap > .sm-quill-html-source { display: none !important; width: 100%;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 12px;
    }
    .sm-quill-wrap.is-html-mode > .sm-quill-host { display: none !important; }
    .sm-quill-wrap.is-html-mode > .sm-quill-html-source { display: block !important; }
</style>
<style>
    /* The client's hub is a shelf of tags you tap, not a filing cabinet.
       Sixteen of them in two rows of tab stubs read as a cabinet; the same
       sixteen as tags read as a shelf, which is what they are. */
    .sm-tabs { display: flex; flex-wrap: wrap; gap: .4rem; border-bottom: 0; margin-bottom: 1rem !important; }
    .sm-tabs .nav-item { margin: 0; }
    .sm-tabs .nav-link {
        border: 1px solid #e6e8ec; border-radius: 999px; padding: .4rem .9rem;
        color: #495057; font-weight: 500; font-size: 13px; white-space: nowrap;
        background: #fff; display: inline-flex; align-items: center; gap: .35rem;
    }
    .sm-tabs .nav-link:hover { background: #eef2ff; border-color: #c7d2fe; color: #2c3e8c; }
    .sm-tabs .nav-link.active {
        background: #556ee6; border-color: #556ee6; color: #fff;
    }
    .sm-tabs .nav-link .badge { background: #eef1f6 !important; color: #495057 !important; }
    .sm-tabs .nav-link.active .badge { background: rgba(255,255,255,.9) !important; color: #2c3e8c !important; }

    /* The drawers inside a module: pills, so they never read as another
       shelf of their own. */
    .sm-subtabs { display: flex; flex-wrap: wrap; gap: .4rem; border-bottom: 0; }
    .sm-subtabs .nav-link {
        border: 1px solid #e6e8ec; border-radius: 999px; padding: .35rem .9rem;
        font-size: 12.5px; font-weight: 500; color: #495057; background: #fff;
        display: flex; align-items: center; gap: .4rem;
    }
    .sm-subtabs .nav-link:hover { background: #eef2ff; color: #2c3e8c; }
    .sm-subtabs .nav-link.active { background: #556ee6; border-color: #556ee6; color: #fff; }
    .sm-subtabs .nav-link .badge { font-size: 10.5px; font-weight: 600; background: #eef1f6; color: #495057; }
    .sm-subtabs .nav-link.active .badge { background: rgba(255,255,255,.85); color: #2c3e8c; }
    .sm-pill { border-radius: 50px; padding: 4px 12px; font-size: 11px; font-weight: 500; }

    /* One question per row: what it is, what it means, and where it stands. */
    .vf-row { width: 100%; display: flex; align-items: center; gap: .75rem; text-align: left;
        padding: .7rem .8rem; border-radius: .8rem; background: #fff;
        border: 1px solid #e6e8ec; margin-bottom: .45rem; }
    .vf-row:hover { border-color: #c7d2fe; background: #fbfcff; }
    .vf-ico { width: 2.25rem; height: 2.25rem; border-radius: .7rem; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        background: #eef1f6; color: #556ee6; font-size: 1.1rem; }
    .vf-txt { flex: 1 1 auto; min-width: 0; }
    .vf-txt b { display: block; font-size: 13.5px; font-weight: 600; color: #343a40; }
    .vf-txt i { display: block; font-size: 11.5px; color: #98a4b6; font-style: normal; }
    .vf-state, .vf-go { font-size: 11px; font-weight: 700; border-radius: 999px; padding: .15rem .55rem;
        background: #e9f7ef; color: #0f8a5f; white-space: nowrap; }
    .vf-state.is-off { background: #eef1f6; color: #74788d; }
    .vf-go { background: #eef2ff; color: #3a4699; }

    /* The mirror: a plan to be read, with nothing to press. */
    .mir-day { border: 1px solid #e6e8ec; border-radius: 10px; margin-bottom: .6rem; overflow: hidden; }
    .mir-dayhead { background: #f8fafd; padding: .45rem .8rem; font-weight: 700; font-size: 12.5px; color: #495057;
        display: flex; justify-content: space-between; gap: .6rem; }
    .mir-act { padding: .45rem .8rem; border-top: 1px solid #f1f3f7; font-size: 12.5px; color: #343a40; }
    .mir-act.is-done { color: #98a4b6; }
    .mir-act.is-done .mir-title { text-decoration: line-through; }
    .mir-act.is-hidden { background: #fbfbfd; }
    .mir-title { font-weight: 600; }
    .mir-meta { font-size: 11px; color: #98a4b6; }
    .mir-flag { font-size: 10px; font-weight: 700; border-radius: 999px; padding: .05rem .4rem;
        background: #eef1f6; color: #74788d; margin-left: .3rem; }
    .priority-critical { background:#9c1c1c; color:#fff; font-weight:700; text-transform:uppercase; letter-spacing:.3px; }
    .priority-high { background:#e14b4b; color:#fff; }
    .priority-medium { background:#f1b44c; color:#212529; }
    .priority-low { background:#74788d; color:#fff; }
    .activity-card { border-left: 3px solid #556ee6; }
    .item-tag { background:#eef0fb; color:#3a4699; padding:2px 8px; border-radius:8px; font-size:11px; margin-right:4px; display:inline-block; margin-bottom:3px;}
    .item-tag.service { background:#e6f7f1; color:#0f8a5f; }
    /* A line that is only a name and a price — no catalogue behind it. */
    .item-tag.custom { background:#fff4e5; color:#a66200; }

    /* ---- Activities grouped by date with flat color coding ---- */
    .activity-timeline { padding: 4px 0; }
    .date-group {
        margin-bottom: 18px;
        --date-color: #556ee6;
        /* What the band is written in, and the washes it lays over its own
           colour for the chips and buttons.

           The washes are dark on every colour. A white one lightens the band
           toward the white text on top of it, which made a chip the hardest
           thing on the band to read — 2.47 to 1 on the blue, against 3.29 for
           the bare band beside it. Dark deepens it instead, and a chip that
           reads as inset is as ordinary as one that reads as raised.

           Only the ink changes per colour, and only for the three light
           ones. */
        --date-ink: #fff;
        --date-veil: rgba(0,0,0,0.16);
        --date-btn: rgba(0,0,0,0.10);
        --date-btn-hover: rgba(0,0,0,0.22);
        --date-edge: rgba(255,255,255,0.32);
    }
    .date-header {
        display: flex; align-items: center; gap: 10px;
        padding: 10px 14px;
        border-radius: 8px 8px 0 0;
        background: var(--date-color);
        color: var(--date-ink);
    }
    .date-header .date-header-day { font-weight: 600; font-size: 13px; opacity: .85; }
    .date-header .date-header-date { font-weight: 700; font-size: 16px; }
    /* Group-level range badge — appears in the date header when at least
       one activity in the group has targetEndDate > targetDate. Renders
       in the white-on-color header band so it stays subtle but visible. */
    .date-header .date-header-range {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: var(--date-veil);
        padding: 2px 9px;
        border-radius: 11px;
        font-size: 12px;
        font-weight: 600;
        color: var(--date-ink);
    }
    .date-header .date-header-range i { font-size: 14px; }
    .date-header .date-header-range-days {
        font-weight: 500;
        font-size: 11px;
        opacity: 0.85;
    }
    .date-header .date-header-count {
        margin-left: auto;
        background: var(--date-veil);
        padding: 3px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
    }
    .date-header-edit-btn {
        background: var(--date-btn);
        color: var(--date-ink);
        border: 1px solid var(--date-edge);
        border-radius: 6px;
        padding: 3px 9px;
        font-size: 13px;
        cursor: pointer;
        line-height: 1;
        transition: background .12s ease;
    }
    .date-header-edit-btn:hover { background: var(--date-btn-hover); }
    .date-header-delete-btn:hover { background: #e14b4b; border-color: #e14b4b; color: #fff; }
    .date-activities {
        padding: 10px;
        background: #fff;
        border: 1px solid var(--date-color);
        border-top: none;
        border-radius: 0 0 8px 8px;
    }
    /* Per-date commentary block — slots between the date-header and the
       activity cards. Tinted with the date-group color so it visually
       belongs to its date but stays subordinate to the activity cards. */
    .date-note-block {
        background: #fffaf0;
        border-left: 1px solid var(--date-color);
        border-right: 1px solid var(--date-color);
        border-bottom: 1px dashed #f0d899;
        padding: 8px 14px;
    }
    .date-note-inner {
        display: flex;
        align-items: flex-start;
        gap: 8px;
    }
    .date-note-icon {
        color: #b78103;
        font-size: 16px;
        flex-shrink: 0;
        margin-top: 1px;
    }
    .date-note-text {
        color: #5a4413;
        font-size: 13px;
        line-height: 1.55;
        flex-grow: 1;
        word-break: break-word;
    }
    /* Note-icon button states */
    .date-note-btn.has-note { background: rgba(255,255,255,0.62); color: #2b3242; border-color: rgba(255,255,255,0.9); }
    .date-note-btn.has-note:hover { background: rgba(255,255,255,0.82); color: #20262f; }

    /* Global activity note (version-wide commentary above the timeline) */
    .global-activity-note {
        background: linear-gradient(180deg, #eef4ff 0%, #e5edff 100%);
        border-left: 4px solid #4a73e3;
        border-radius: 6px;
        padding: 12px 16px;
    }
    .global-note-header {
        display: flex; align-items: center; gap: 8px;
        color: #2c3e8c; font-weight: 600; font-size: 13px;
        margin-bottom: 6px;
    }
    .global-note-header i { font-size: 18px; }
    .global-note-edit-btn { color: #2c3e8c !important; font-size: 12px; font-weight: 500; text-decoration: none; }
    .global-note-edit-btn:hover { text-decoration: underline; }
    .global-note-body {
        color: #1a2655;
        font-size: 13.5px;
        line-height: 1.6;
        word-break: break-word;
    }
    .activity-card {
        background: #fff;
        border: 1px solid #e6e8ec;
        border-left: 4px solid var(--date-color);
        border-radius: 6px;
        padding: 12px 14px;
        margin-bottom: 8px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        transition: box-shadow .15s ease, opacity .15s ease, filter .15s ease;
    }
    /* Hidden activities — by default, completely removed from the
       activities tab (display: none) so the user gets a clean view
       of only the active activities. Toggle "Show Hidden (N)" at the
       top of the tab to surface them in a dimmed state for re-enabling.
       Hidden activities remain excluded from worker presentation,
       card viewer, and export at the server level regardless of
       this client-side toggle. */
    .activity-card.is-hidden { display: none; }
    body.show-hidden-activities .activity-card.is-hidden {
        display: block;
        opacity: 0.55;
        filter: grayscale(0.4);
        background: #fafbfc;
    }
    body.show-hidden-activities .activity-card.is-hidden .activity-card-image img { opacity: 0.7; }
    /* Date-group wrapper (the day row that holds activity cards) gets
       auto-hidden when every card inside it is in the hidden state.
       A sibling .rest-day-marker.rest-day-substitute (rendered next to
       the group server-side AND in the JS rebuild) takes its place so
       all-hidden dates read like a true rest day instead of vanishing.
       Toggling "Show Hidden" inverts: date-group reappears (with dimmed
       cards), substitute hides. Uses :has() — stable in Chrome 105+/Firefox 121+/Safari 15.4+. */
    .date-group:not(:has(.activity-card:not(.is-hidden))) { display: none; }
    .rest-day-marker.rest-day-substitute { display: flex; }
    body.show-hidden-activities .date-group { display: block; }
    body.show-hidden-activities .rest-day-marker.rest-day-substitute { display: none; }
    /* Visibility switch in the activity card actions row */
    .hide-activity-switch { padding-left: 1.8em; }
    .hide-activity-switch .form-check-input { cursor: pointer; }
    .hide-activity-switch .form-check-input:checked { background-color: #34c38f; border-color: #34c38f; }
    .hide-activity-tag i { vertical-align: middle; margin-right: 2px; }
    .activity-card:last-child { margin-bottom: 0; }
    .activity-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
    .activity-card .step-meta { color:#74788d; font-size:12px; }
    .activity-card .step-meta i { color: var(--date-color); margin-right: 2px; }
    .activity-card-lots {
        font-size: 13px;
        color: #74788d;
        line-height: 1.6;
        margin-top: 8px;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px 4px;
    }
    .activity-card-lots i.bx-map-pin {
        color: var(--date-color);
        font-size: 17px;
        vertical-align: middle;
    }
    .activity-card-lots .item-tag {
        font-size: 13px;
        padding: 5px 12px;
        border-radius: 14px;
        font-weight: 500;
        margin: 0;            /* gap on the parent handles spacing */
    }
    /* "No activities scheduled" gap markers between date groups —
       redesigned to be bigger and more usable. Each empty day carries
       a quick "Add Activity" button so the user can drop a card in
       without scrolling back to the top toolbar. The marker animates
       on hover to make the button easy to spot. */
    .rest-day-marker {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 12px 18px;
        margin-bottom: 8px;
        background: linear-gradient(180deg, #fafbfc 0%, #f3f5f9 100%);
        border: 1px solid #e5e9f0;
        border-left: 4px solid #c7cee0;
        border-radius: 8px;
        transition: border-color .12s ease, box-shadow .12s ease, transform .12s ease;
    }
    .rest-day-marker:hover {
        border-left-color: #556ee6;
        box-shadow: 0 2px 8px rgba(20, 30, 60, 0.06);
    }
    .rest-day-marker .rest-day-icon {
        color: #8893a8;
        font-size: 22px;
        flex-shrink: 0;
    }
    .rest-day-marker .rest-day-text {
        display: flex;
        flex-direction: column;
        gap: 2px;
        flex-grow: 1;
        min-width: 0;
    }
    .rest-day-marker .rest-day-date {
        font-weight: 600;
        color: #495160;
        font-size: 14px;
    }
    .rest-day-marker .rest-day-tag {
        color: #6b7689;
        font-style: italic;
        font-size: 12px;
    }
    .rest-day-add-btn {
        flex-shrink: 0;
        font-size: 12.5px;
        padding: 5px 14px;
        border-radius: 14px;
        transition: all .12s ease;
    }
    .rest-day-add-btn i { font-size: 14px; vertical-align: middle; margin-right: 1px; }
    /* Drag-and-drop visuals */
    .activity-card[draggable="true"] { cursor: grab; }
    .activity-card.dragging { opacity: .45; cursor: grabbing; }
    .date-activities.drop-target { background: rgba(0,0,0,0.04); outline: 2px dashed var(--date-color); outline-offset: -4px; }
    /* Rest-day markers (no-activities-scheduled placeholders) are now
       drop targets too: dragging an activity card onto one re-dates it
       to that day. The drop-target outline mirrors .date-activities so
       the user sees the same visual confirmation everywhere. */
    .rest-day-marker.drop-target {
        background: rgba(85, 110, 230, 0.08);
        outline: 2px dashed #556ee6;
        outline-offset: -4px;
    }

    /* Progress markers — "resume here" bookmarks the user drops into the
       timeline to remember where they left off. The line itself reads as a
       horizontal divider with a small bookmark chip floating on top of it
       (centered, so the divider passes behind the label). Optional note
       renders as a small callout right under the line. */
    .progress-marker {
        position: relative;
        margin: 14px 0 16px;
        padding: 0;
    }
    .progress-marker-line {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 0 8px;
    }
    .progress-marker-line::before {
        content: "";
        position: absolute;
        left: 0;
        right: 0;
        top: 50%;
        height: 0;
        border-top: 2px dashed #f0a020;
        z-index: 0;
    }
    .progress-marker.has-note .progress-marker-line::before {
        border-top-style: solid;
    }
    .progress-marker-bookmark {
        position: relative;
        z-index: 1;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        background: linear-gradient(180deg, #fff7e0 0%, #ffe6a8 100%);
        border: 1px solid #f0a020;
        border-radius: 14px;
        color: #6b4e00;
        font-weight: 600;
        font-size: 12.5px;
        box-shadow: 0 1px 3px rgba(240, 160, 32, 0.18);
    }
    .progress-marker-bookmark i {
        font-size: 14px;
        color: #d18a00;
    }
    .progress-marker-label { letter-spacing: 0.2px; }
    .progress-marker-date {
        font-weight: 500;
        color: #8a6800;
        font-size: 12px;
    }
    .progress-marker-actions {
        position: relative;
        z-index: 1;
        display: inline-flex;
        gap: 4px;
        background: #fff;
        padding: 2px 4px;
        border-radius: 10px;
    }
    .progress-marker-actions .btn {
        font-size: 11.5px;
        padding: 2px 7px;
        line-height: 1.2;
    }
    .progress-marker-actions .btn i { font-size: 13px; vertical-align: middle; }
    .progress-marker-note {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        margin: 6px 12px 0 12px;
        padding: 8px 12px;
        background: #fffaef;
        border-left: 3px solid #f0a020;
        border-radius: 0 6px 6px 0;
        font-size: 13px;
        color: #5c4300;
    }
    .progress-marker-note-icon { color: #d18a00; font-size: 15px; flex-shrink: 0; margin-top: 1px; }
    .progress-marker-note-text { flex-grow: 1; line-height: 1.45; }

    /* Date-header bookmark button — yellow tint when a marker exists,
       neutral when no marker is set. Keeps the rest of the date-header
       buttons visually consistent. */
    .date-header-edit-btn.date-marker-btn.has-marker {
        background: rgba(255,255,255,0.92);
        border-color: rgba(255,255,255,0.95);
        color: #96660a;
    }
    .date-header-edit-btn.date-marker-btn.has-marker:hover { background: #fff; }
    .date-header-edit-btn.date-marker-btn.has-marker i {
        color: #96660a;
    }

    /* Flat color palette — cycled by date order (0..7) */
    .date-color-0 { --date-color: #4A90E2; } /* blue   */
    /* Light enough that white on them is about 2.1:1, so these three
       are written in a deep version of their own hue instead — around
       5.5:1 — and wash themselves with black rather than white. */
    .date-color-1 { --date-color: #50C878; } /* green  */
    .date-color-2 { --date-color: #F39C12; } /* orange */
    .date-color-3 { --date-color: #9B59B6; } /* purple */
    .date-color-4 { --date-color: #1ABC9C; } /* teal   */
    .date-color-5 { --date-color: #E74C3C; } /* red    */
    .date-color-6 { --date-color: #5C6BC0; } /* indigo */
    .date-color-7 { --date-color: #16A085; } /* sea    */
    .date-color-1, .date-color-2, .date-color-4 {
        /* Dark ink wants a dark edge to sit inside; the washes are already
           dark for everyone. */
        --date-edge: rgba(0,0,0,0.22);
    }
    .date-color-1 { --date-ink: #0e3a22; } /* on green  */
    .date-color-2 { --date-ink: #4a2e00; } /* on orange */
    .date-color-4 { --date-ink: #06382e; } /* on teal   */
    .day-pill { display:inline-block; padding:4px 10px; margin:2px; border-radius:20px; background:#f1f1f1; cursor:pointer; font-size:12px; color:#495057; }
    .day-pill.active { background:#556ee6; color:#fff; }
    /* Lot selector chips used inside Default Groupings */
    .lot-chip { display:inline-block; padding:5px 12px; margin:3px; border-radius:20px; background:#fff; border:1px solid #d3d6db; cursor:pointer; font-size:12px; color:#495057; user-select:none; transition: all .12s ease; }
    .lot-chip:hover { border-color:#556ee6; color:#556ee6; }
    .lot-chip.active { background:#556ee6; color:#fff; border-color:#556ee6; }
    .lot-chip.active:hover { background:#4458c4; border-color:#4458c4; color:#fff; }
    /* "N/A" pseudo-chip — visually distinct from real lots so the user
       sees at a glance it isn't a real lot. Border-dash + slate accent
       hints at "not a real entity". */
    .lot-chip.lot-chip-na { border-style: dashed; color: #6b7280; }
    .lot-chip.lot-chip-na:hover { border-color: #4a5568; color: #4a5568; }
    .lot-chip.lot-chip-na.active { background: #4a5568; border-style: solid; border-color: #4a5568; color: #fff; }
    .lot-chip.lot-chip-na.active:hover { background: #3a4453; border-color: #3a4453; }
    .lot-chip.lot-chip-na i { vertical-align: middle; margin-right: 2px; }
    /* N/A indicator rendered on activity cards in place of lot chips
       when the activity isn't tied to any specific lot. Slate accent
       matches the lot-chip-na chip in the modal so the user sees the
       continuity. */
    .activity-na-tag {
        background: #f1f3f7 !important;
        color: #495160 !important;
        border: 1px dashed #c5cad9;
        font-style: italic;
        font-weight: 500;
    }
    .activity-na-tag i { vertical-align: middle; margin-right: 3px; color: #6b7280; }
    .lot-chip-container { padding:6px; background:#fff; border:1px dashed #d3d6db; border-radius:6px; min-height:44px; }
    /* Activity reference image — preview in the edit modal + thumbnail on the activity card */
    .activity-image-wrap { padding: 8px; background: #f8f9fc; border: 1px solid #e1e6ef; border-radius: 6px; }
    .activity-image-preview { max-width: 100%; max-height: 220px; border-radius: 4px; display: block; margin: 0 auto; }
    .activity-image-uploading { color: #556ee6; font-size: 13px; padding: 6px 0; }
    .activity-card-image { margin-top: 8px; }
    .activity-card-image img { max-width: 100%; max-height: 160px; border: 1px solid #e1e6ef; border-radius: 4px; display: block; }
    /* Rich-text description rendering inside an activity card */
    .activity-description-content p { margin: 0 0 .5em; }
    .activity-description-content p:last-child { margin-bottom: 0; }
    .activity-description-content ul, .activity-description-content ol { margin: .25em 0 .5em 1.25rem; padding: 0; }
    .activity-description-content li { margin-bottom: .15em; }
    .activity-description-content h1, .activity-description-content h2,
    .activity-description-content h3, .activity-description-content h4 { margin: .5em 0 .25em; font-weight: 600; color: #212529; }
    .activity-description-content h1 { font-size: 1.05rem; }
    .activity-description-content h2 { font-size: 1rem; }
    .activity-description-content h3 { font-size: .95rem; }
    .activity-description-content h4 { font-size: .9rem; }
    .activity-description-content a { color: #556ee6; text-decoration: underline; }
    .activity-description-content table { border-collapse: collapse; }
    .activity-description-content table td, .activity-description-content table th { border: 1px solid #e6e8ec; padding: 2px 6px; }
    /* Quill 2 stores BOTH bullet + ordered lists in <ol> with the marker
       type on each <li> via data-list. Without this override, an
       authored bullet list renders as numbered because <ol> defaults to
       decimal. Per-item list-style-type lets the marker follow Quill's
       intent. Sub-bullets get progressive left indent (matches Quill
       snow theme). */
    .activity-description-content ol > li[data-list="bullet"]  { list-style-type: disc; }
    .activity-description-content ol > li[data-list="ordered"] { list-style-type: decimal; }
    .activity-description-content .ql-ui { display: none; }
    .activity-description-content li.ql-indent-1 { margin-left: 1.5em; }
    .activity-description-content li.ql-indent-2 { margin-left: 3em; }
    .activity-description-content li.ql-indent-3 { margin-left: 4.5em; }
    .activity-description-content li.ql-indent-4 { margin-left: 6em; }
    .activity-description-content li.ql-indent-5 { margin-left: 7.5em; }
    .activity-description-content li.ql-indent-6 { margin-left: 9em; }
    .activity-description-content li.ql-indent-7 { margin-left: 10.5em; }
    .activity-description-content li.ql-indent-8 { margin-left: 12em; }

    /* ---- Documentation subtabs (pill strip) ----
       Pills inside the Documentation tab so the four sections share a
       visual frame distinct from the main tab strip. */
    .doc-subtabs {
        border-bottom: 1px solid #e6e8ec;
        padding-bottom: 6px;
        gap: 4px;
    }
    .doc-subtabs .nav-link {
        background: #f6f7fb;
        color: #495160;
        border: 1px solid transparent;
        font-weight: 500;
        font-size: 13px;
        padding: 6px 14px;
        border-radius: 18px;
        transition: background .12s ease, color .12s ease, border-color .12s ease;
    }
    .doc-subtabs .nav-link:hover { background: #eef2ff; color: #2c3e8c; }
    .doc-subtabs .nav-link.active {
        background: #556ee6;
        color: #fff;
        border-color: #556ee6;
    }
    .doc-subtabs .nav-link .badge { font-size: 10.5px; font-weight: 600; }
    .doc-subtabs .nav-link.active .badge { background: rgba(255,255,255,.85) !important; color: #2c3e8c !important; }
    .doc-subtab-dot {
        display: inline-block;
        width: 7px; height: 7px;
        border-radius: 50%;
        background: #1abc9c;
        margin-left: 5px;
        vertical-align: middle;
    }
    .doc-subtab-content { padding-top: 2px; }

    /* ---- Protocol / Documentation tab ----
       Three card sections: Introduction (rich text), Attachments (grid),
       Critical Rules (sortable list). */
    .protocol-section .card-body { padding: 16px 18px; }
    .protocol-empty-state { color: #6b7280; }
    .protocol-intro-preview {
        background: #fafbff;
        border: 1px solid #e1e6f5;
        border-left: 4px solid #4a73e3;
        border-radius: 4px;
        padding: 12px 14px;
        color: #1a2655;
        line-height: 1.55;
        word-break: break-word;
    }
    .protocol-intro-preview h1,
    .protocol-intro-preview h2,
    .protocol-intro-preview h3 { color: #2c3e8c; margin: 0.5em 0 0.3em; }
    .protocol-intro-preview ul, .protocol-intro-preview ol { margin-left: 1.2rem; }

    /* Attachments grid */
    .attachments-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 14px;
    }
    .attachment-card {
        background: #fff;
        border: 1px solid #e6e8ec;
        border-radius: 6px;
        overflow: hidden;
        display: flex; flex-direction: column;
        transition: box-shadow .15s ease;
    }
    .attachment-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.07); }
    .attachment-thumb {
        background: #f4f6fb;
        height: 150px;
        display: flex; align-items: center; justify-content: center;
        overflow: hidden;
    }
    .attachment-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .attachment-file-icon {
        text-align: center;
        color: #6b7280;
        text-decoration: none;
        position: relative;
    }
    .attachment-file-icon i { font-size: 3rem; }
    .attachment-file-icon .ext-badge {
        position: absolute;
        bottom: 18px; left: 50%;
        transform: translateX(-50%);
        background: #fff;
        font-size: 10px;
        font-weight: 700;
        padding: 1px 6px;
        border-radius: 3px;
        text-transform: uppercase;
        color: #374151;
    }
    .attachment-body { padding: 10px 12px; display: flex; flex-direction: column; gap: 4px; }
    .attachment-filename {
        font-weight: 600;
        font-size: 13px;
        color: #1f2937;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .attachment-description {
        font-size: 12px;
        color: #495057;
        line-height: 1.45;
        word-break: break-word;
        max-height: 60px;
        overflow: hidden;
    }
    .attachment-actions { display: flex; gap: 4px; margin-top: 4px; }

    /* Critical rules list */
    .critical-rules-list { display: flex; flex-direction: column; gap: 6px; }
    .critical-rule-row {
        display: flex; align-items: center; gap: 8px;
        background: #fff7f7;
        border: 1px solid #f0d6d6;
        border-left: 4px solid #d9534f;
        border-radius: 5px;
        padding: 8px 10px;
        transition: box-shadow .15s ease, opacity .12s ease;
    }
    .critical-rule-row.dragging { opacity: .45; }
    .critical-rule-row.drag-over { outline: 2px dashed #d9534f; outline-offset: -4px; }
    .critical-rule-handle {
        color: #c8a4a4; cursor: grab; font-size: 18px;
        user-select: none;
    }
    .critical-rule-row.dragging .critical-rule-handle { cursor: grabbing; }
    .critical-rule-text {
        flex: 1; min-width: 0;
        color: #5a2828; font-size: 13.5px; line-height: 1.45;
        white-space: pre-wrap; word-break: break-word;
    }
    .critical-rule-actions { display: flex; gap: 4px; flex-shrink: 0; }

    /* ---- Irrigation card list (matches activity-card visual language) ----
       Cards stack vertically, each tinted by the irrigation's taskType
       color on the left border and the title badge. Drag handle on the
       left lets the user reorder rows; the drop-target outline lights
       up while dragging. */
    .irrigation-card-list { padding: 4px 0; display: flex; flex-direction: column; gap: 8px; }
    .irrigation-card {
        position: relative;
        display: flex;
        align-items: stretch;
        background: #fff;
        border: 1px solid #e6e8ec;
        border-left: 4px solid var(--irr-color, #1976d2);
        border-radius: 6px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        transition: box-shadow .15s ease, transform .12s ease;
        cursor: default;
    }
    .irrigation-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
    .irrigation-card.dragging { opacity: .45; transform: scale(0.99); }
    .irrigation-card.drag-over {
        outline: 2px dashed var(--irr-color, #1976d2);
        outline-offset: -4px;
        background: rgba(25, 118, 210, 0.04);
    }
    .irr-drag-handle {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 6px 0 4px;
        color: #9aa0a6;
        cursor: grab;
        user-select: none;
        font-size: 18px;
    }
    .irr-drag-handle:hover { color: var(--irr-color, #1976d2); }
    .irrigation-card.dragging .irr-drag-handle { cursor: grabbing; }
    .irr-card-body { flex: 1; padding: 10px 12px 10px 4px; min-width: 0; }
    .irr-meta-row {
        font-size: 12px;
        color: #6b7280;
        line-height: 1.6;
        margin-top: 4px;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 4px;
    }
    .irr-meta-row > i { color: var(--irr-color, #1976d2); font-size: 14px; margin-right: 2px; }
    .irr-chip {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 11px;
        font-weight: 500;
        margin: 0;
    }
    .irr-chip-lot    { background:#eef0fb; color:#3a4699; }
    .irr-chip-worker { background:#fef3e8; color:#a66200; }
    /* Priority pill — five levels, lower number = stronger visual weight. */
    .irr-priority-pill {
        font-weight: 700;
        font-size: 11px;
        letter-spacing: 0.4px;
        color: #fff;
    }
    .irr-prio-1 { background:#9c1c1c; }   /* deep red — critical */
    .irr-prio-2 { background:#d97a4f; }   /* coral — high */
    .irr-prio-3 { background:#d9a23a; color:#3a2c0a; }   /* amber on dark — medium */
    .irr-prio-4 { background:#7a8a99; }   /* slate — low */
    .irr-prio-5 { background:#c8cdd5; color:#495160; }   /* light gray — lowest */
    .irr-description {
        font-size: 12.5px;
        color: #495057;
        margin-top: 6px;
        line-height: 1.5;
        word-break: break-word;
    }

    /* ---- Full-page loading overlay ----
       The setup page renders 100+ activity cards + a heavy JS bundle, so
       the first paint can take several seconds. The overlay fires before
       any other style/script and hides on window.load so the user always
       sees feedback instead of a blank/half-rendered page. */
    #setupPageLoader {
        position: fixed; top: 0; left: 0; right: 0; bottom: 0;
        z-index: 99999;
        background: rgba(248, 249, 252, 0.96);
        display: flex; align-items: center; justify-content: center;
        flex-direction: column;
        transition: opacity .25s ease;
    }
    #setupPageLoader.is-fading { opacity: 0; pointer-events: none; }
    #setupPageLoader .loader-spinner {
        width: 56px; height: 56px;
        border-radius: 50%;
        border: 5px solid #d9def0;
        border-top-color: #556ee6;
        animation: setupLoaderSpin 0.9s linear infinite;
    }
    #setupPageLoader .loader-text {
        margin-top: 18px;
        color: #495077;
        font-weight: 600;
        font-size: 15px;
        letter-spacing: 0.3px;
    }
    #setupPageLoader .loader-sub {
        margin-top: 4px;
        color: #8893a8;
        font-size: 12.5px;
    }
    @keyframes setupLoaderSpin { to { transform: rotate(360deg); } }
</style>

{{-- The mode layer goes last in the head, so it answers after every
     rule above it. --}}
@include('aniSensoAdmin.partials.dark')

@endsection

@section('content')
    {{-- Loading overlay — covers the page while the browser parses 1MB+ of
         setup HTML, downloads Quill from the CDN, and initializes the JS
         bundle. Removed on window.load (after CSS, scripts, and CDN assets
         have all settled) so the user gets a real ready signal instead of
         clicking into a half-rendered tab. --}}
    <div id="setupPageLoader" aria-live="polite" aria-busy="true">
        <div class="loader-spinner"></div>
        <div class="loader-text">Loading schedule…</div>
        <div class="loader-sub">{{ $schedule->title }} — preparing activities, lots, workers, and irrigation</div>
    </div>

    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('anisenso-schedule-manager.index') }}" class="text-decoration-none">Schedule Manager</a> @endslot
        @slot('title') {{ $schedule->title }} @endslot
    @endcomponent

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
        $readinessIssues = $schedule->getReadinessIssues();
        $isReadyToGenerate = empty($readinessIssues);
    @endphp

    {{-- Header --}}
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    @php
                        $statusMap = [
                            // Light backgrounds take dark ink, and get it
                            // from the shared layer — which .text-white,
                            // being marked important, would have overruled.
                            'draft' => 'bg-secondary text-white',
                            'setup' => 'bg-info',
                            'generated' => 'bg-primary text-white',
                            'completed' => 'bg-success',
                            'archived' => 'bg-dark text-white',
                        ];
                        $cls = $statusMap[$schedule->status] ?? 'bg-secondary text-white';
                    @endphp
                    <h4 class="text-dark mb-1">
                        <span id="scheduleHeaderTitle">{{ $schedule->title }}</span>
                        <span class="badge {{ $cls }} ms-1" style="text-transform:capitalize;">{{ $schedule->status }}</span>
                        @if($schedule->anisystemUserId)
                            <span class="badge bg-warning text-dark ms-1" title="{{ $schedule->anisystemUser->email ?? '' }}">
                                <i class="bx bx-user-pin me-1"></i>AniSystem Client Schedule — owned by {{ $schedule->anisystemUser->fullName ?? 'Unknown client' }}
                            </span>
                        @endif
                    </h4>
                    <p class="text-secondary mb-0" id="scheduleHeaderDescription">{{ $schedule->description ?: 'No description provided.' }}</p>
                </div>
                <div class="d-flex gap-2 flex-wrap align-items-center">
                    {{-- Live-sync presence: filled by script-sync with the other
                         users currently viewing this schedule. --}}
                    <div id="syncPresence" style="display:none;gap:6px;align-items:center;flex-wrap:wrap;"></div>
                    {{-- Generate Calendar stood here. It builds a season from
                         a form the farmer app has no counterpart for — over
                         there a plan is written activity by activity — so it
                         is not something this side is the admin OF. The
                         generator still answers at its own address for anyone
                         who wants it; it is simply not offered beside the
                         modules any more. --}}
                </div>
            </div>
        </div>
    </div>


    {{-- Schedule-level actions — the ones that read the whole season out
         rather than opening a screen of their own. Card Viewer stood here
         too, a slide deck the client app has no counterpart for. --}}
    <div class="d-flex justify-content-end align-items-center flex-wrap gap-2 mb-3">
        <button type="button" class="btn btn-outline-success btn-sm" id="openLaborSummaryBtn" title="See the total labor expense across all activities">
            <i class="bx bx-money me-1"></i> Labor Expenses
        </button>
        <button type="button" class="btn btn-outline-dark btn-sm" id="openWorkerPresentationBtn" title="Open a printable presentation (intro, activities, monthly labor, per-worker pages, irrigation, calendar) in a new tab">
            <i class="bx bx-book-open me-1"></i> Worker Presentation
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="openExportScheduleBtn" title="Open a formatted preview, download PDF, or copy as text">
            <i class="bx bx-file-blank me-1"></i> Export Schedule
        </button>
    </div>

    {{-- Tabs --}}
    <div class="card">
        <div class="card-body">
            {{-- The shelf is the client's module shelf, in their order. What
                 is not a module over there is not a tab over here. --}}
            {{-- No `nav-tabs`: that class is what draws tab stubs, and equal
                 specificity means it beats these rules on source order alone.
                 A shelf of tags is not a tab bar wearing different paint. --}}
            <ul class="nav sm-tabs mb-3" role="tablist">
                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-activities"><i class="bx bx-task me-1"></i> Activities <span class="badge bg-light text-dark ms-1" id="badge-activities">{{ $schedule->activities->count() }}</span></a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-settings"><i class="bx bx-cog me-1"></i> Settings</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-lots"><i class="bx bx-map-pin me-1"></i> Lots <span class="badge bg-light text-dark ms-1" id="badge-lots">{{ $schedule->lots->count() }}</span></a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-workers"><i class="bx bx-user me-1"></i> Workers <span class="badge bg-light text-dark ms-1" id="badge-workers">{{ $schedule->workers->count() }}</span></a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-inventory"><i class="bx bx-package me-1"></i> Inventory</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-protocol-doc"><i class="bx bx-clipboard me-1"></i> Documentation
                    @php
                        $hasProto = optional($schedule->protocol)->protocolContent || optional($schedule->protocol)->protocolFile;
                        // The client's own documents count too. Without them
                        // the badge read 0 on a season holding five, because
                        // it only knew about the two older tables.
                        $protoCount = \Illuminate\Support\Facades\DB::table('as_schedule_doc_entries')
                                        ->where('croppingScheduleId', $schedule->id)
                                        ->where('deleteStatus', 1)->count()
                                    + $schedule->attachments->count()
                                    + $schedule->criticalRules->count()
                                    + ($hasProto ? 1 : 0);
                    @endphp
                    <span class="badge bg-light text-dark ms-1" id="badge-protocol-doc">{{ $protoCount }}</span>
                </a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-post-harvest"><i class="bx bx-basket me-1"></i> Post-harvest</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-notes"><i class="bx bx-edit me-1"></i> Notes</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-weather"><i class="bx bx-cloud me-1"></i> Weather</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-growth"><i class="bx bx-leaf me-1"></i> Growth Stages</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-gallery"><i class="bx bx-images me-1"></i> Gallery</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-maps"><i class="bx bx-map-alt me-1"></i> Maps</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-draw"><i class="bx bx-pencil me-1"></i> Draw</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-chat-technician"><i class="bx bx-bot me-1"></i> Chat Technician</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-collab"><i class="bx bx-group me-1"></i> Collab room</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-reports"><i class="bx bx-line-chart me-1"></i> Reports</a></li>
            </ul>

            <div class="tab-content">
                {{-- ACTIVITIES — the plan itself, and the three catalogues it
                     draws from. They were tabs of their own; in the client's
                     app irrigation is a mode of the add-activity sheet and
                     materials and services are picked while writing one, so
                     none of the three is a place you go. --}}
                <div class="tab-pane fade show active" id="tab-activities">
                    {{-- The plan. It had four drawers beside it — Day cash,
                         Irrigation, Materials, Services — and not one of them
                         is a place in the client's app. --}}
                    @include('aniSensoAdmin.scheduleManager.partials.activities', ['schedule' => $schedule])
                </div>

                {{-- SETTINGS --}}
                <div class="tab-pane fade" id="tab-settings">
                    @include('aniSensoAdmin.scheduleManager.partials.settings', ['schedule' => $schedule])
                </div>

                {{-- LOTS --}}
                <div class="tab-pane fade" id="tab-lots">
                    @include('aniSensoAdmin.scheduleManager.partials.lots', ['schedule' => $schedule])
                </div>

                {{-- WORKERS --}}
                <div class="tab-pane fade" id="tab-workers">
                    @include('aniSensoAdmin.scheduleManager.partials.workers', ['schedule' => $schedule])
                </div>

                {{-- INVENTORY --}}
                <div class="tab-pane fade" id="tab-inventory">
                    @include('aniSensoAdmin.scheduleManager.partials.inventory', ['schedule' => $schedule])
                </div>

                {{-- DOCUMENTATION (Introduction + Attachments + Critical Rules
                     + the protocol document, which was once a tab of its own) --}}
                <div class="tab-pane fade" id="tab-protocol-doc">
                    @include('aniSensoAdmin.scheduleManager.partials.protocol-doc', ['schedule' => $schedule])
                </div>

                {{-- POST-HARVEST --}}
                <div class="tab-pane fade" id="tab-post-harvest">
                    @include('aniSensoAdmin.scheduleManager.partials.post-harvest', ['schedule' => $schedule])
                </div>

                {{-- NOTES --}}
                <div class="tab-pane fade" id="tab-notes">
                    @include('aniSensoAdmin.scheduleManager.partials.notes', ['schedule' => $schedule])
                </div>

                {{-- WEATHER --}}
                <div class="tab-pane fade" id="tab-weather">
                    @include('aniSensoAdmin.scheduleManager.partials.weather', ['schedule' => $schedule])
                </div>

                {{-- GROWTH STAGES --}}
                <div class="tab-pane fade" id="tab-growth">
                    @include('aniSensoAdmin.scheduleManager.partials.growth', ['schedule' => $schedule])
                </div>

                {{-- GALLERY --}}
                <div class="tab-pane fade" id="tab-gallery">
                    @include('aniSensoAdmin.scheduleManager.partials.gallery', ['schedule' => $schedule])
                </div>

                {{-- MAPS --}}
                <div class="tab-pane fade" id="tab-maps">
                    @include('aniSensoAdmin.scheduleManager.partials.maps', ['schedule' => $schedule])
                </div>

                {{-- DRAW --}}
                <div class="tab-pane fade" id="tab-draw">
                    @include('aniSensoAdmin.scheduleManager.partials.draw', ['schedule' => $schedule])
                </div>

                {{-- CHAT TECHNICIAN --}}
                <div class="tab-pane fade" id="tab-chat-technician">
                    @include('aniSensoAdmin.scheduleManager.partials.chat-technician', ['schedule' => $schedule])
                </div>

                {{-- COLLAB ROOM --}}
                <div class="tab-pane fade" id="tab-collab">
                    @include('aniSensoAdmin.scheduleManager.partials.collab', ['schedule' => $schedule])
                </div>

                {{-- REPORTS --}}
                <div class="tab-pane fade" id="tab-reports">
                    @include('aniSensoAdmin.scheduleManager.partials.reports', ['schedule' => $schedule])
                </div>
            </div>
        </div>
    </div>

    {{-- Shared confirm modal — used by every delete handler in this page. --}}
    <div class="modal fade" id="confirmActionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark">
                        <i class="bx bx-help-circle me-2" id="confirmActionIcon"></i>
                        <span id="confirmActionTitle">Confirm</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-dark mb-0" id="confirmActionMessage">Are you sure?</p>
                    <small class="text-secondary d-block mt-2" id="confirmActionDetail" style="display:none;"></small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" id="confirmActionCancelBtn">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmActionConfirmBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<!-- Quill 2 — free MIT-licensed rich-text editor (replaces TinyMCE) -->
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.min.js"></script>
<script>
// Hide the full-page loader once everything (DOM, CSS, scripts, Quill
// CDN) has loaded. window.load fires later than DOMContentLoaded — exactly
// what we want here since Quill and DataTables both need their assets
// downloaded before the page is truly interactive.
(function () {
    function hideSetupLoader() {
        var el = document.getElementById('setupPageLoader');
        if (!el) return;
        if (el.classList.contains('is-fading')) return;
        el.classList.add('is-fading');
        // Remove from DOM after the fade so it doesn't trap focus or
        // intercept clicks even with pointer-events: none as a safety net.
        setTimeout(function () { if (el && el.parentNode) el.parentNode.removeChild(el); }, 350);
    }
    // Primary trigger: full window load (waits for all external assets).
    if (document.readyState === 'complete') {
        // Already loaded by the time this script runs — hide on next tick
        // so the spinner is at least visible briefly (looks intentional).
        setTimeout(hideSetupLoader, 50);
    } else {
        window.addEventListener('load', hideSetupLoader);
    }
    // Safety net: hide after 12s even if something hangs (e.g. CDN blocked).
    setTimeout(hideSetupLoader, 12000);
})();

toastr.options = { closeButton: true, progressBar: true, positionClass: "toast-top-right", timeOut: 3000 };

const SCHEDULE_ID = {{ $schedule->id }};
const ROOT = '{{ url('/') }}';
const CSRF = '{{ csrf_token() }}';

// URL helpers — flat paths, IDs in query string.
const Q = `?scheduleId=${SCHEDULE_ID}`;
const URLS = {
    scheduleUpdate:    () => `${ROOT}/anisenso-schedule-manager-update?id=${SCHEDULE_ID}`,

    lotsStore:         () => `${ROOT}/anisenso-schedule-manager-lots-store${Q}`,
    lotsUpdate:        (id) => `${ROOT}/anisenso-schedule-manager-lots-update${Q}&id=${id}`,
    lotsDelete:        (id) => `${ROOT}/anisenso-schedule-manager-lots-delete${Q}&id=${id}`,

    workersStore:      () => `${ROOT}/anisenso-schedule-manager-workers-store${Q}`,
    workersUpdate:     (id) => `${ROOT}/anisenso-schedule-manager-workers-update${Q}&id=${id}`,
    workersDelete:     (id) => `${ROOT}/anisenso-schedule-manager-workers-delete${Q}&id=${id}`,
    workersRules:      (id) => `${ROOT}/anisenso-schedule-manager-workers-rules${Q}&id=${id}`,
    workersRulesSave:  (id) => `${ROOT}/anisenso-schedule-manager-workers-rules-save${Q}&id=${id}`,

    protocolSave:      () => `${ROOT}/anisenso-schedule-manager-protocol-save${Q}`,



    attachmentsStore:  () => `${ROOT}/anisenso-schedule-manager-attachments-store${Q}`,
    attachmentsUpdate: (id) => `${ROOT}/anisenso-schedule-manager-attachments-update${Q}&id=${id}`,
    attachmentsDelete: (id) => `${ROOT}/anisenso-schedule-manager-attachments-delete${Q}&id=${id}`,

    criticalRulesStore:  () => `${ROOT}/anisenso-schedule-manager-critical-rules-store${Q}`,
    criticalRulesUpdate: (id) => `${ROOT}/anisenso-schedule-manager-critical-rules-update${Q}&id=${id}`,
    criticalRulesDelete: (id) => `${ROOT}/anisenso-schedule-manager-critical-rules-delete${Q}&id=${id}`,
    criticalRulesReorder:() => `${ROOT}/anisenso-schedule-manager-critical-rules-reorder${Q}`,

    activitiesStore:     () => `${ROOT}/anisenso-schedule-manager-activities-store${Q}`,
    activitiesShow:      (id) => `${ROOT}/anisenso-schedule-manager-activities-show${Q}&id=${id}`,
    activitiesUpdate:    (id) => `${ROOT}/anisenso-schedule-manager-activities-update${Q}&id=${id}`,
    activitiesDelete:    (id) => `${ROOT}/anisenso-schedule-manager-activities-delete${Q}&id=${id}`,
    activitiesImageUpload: () => `${ROOT}/anisenso-schedule-manager-activities-image-upload${Q}`,
    activitiesToggleHidden: (id) => `${ROOT}/anisenso-schedule-manager-activities-toggle-hidden${Q}&id=${id}`,
    activitiesToggleDone: (id) => `${ROOT}/anisenso-schedule-manager-activities-toggle-done${Q}&id=${id}`,
    activitiesDuplicate: (id) => `${ROOT}/anisenso-schedule-manager-activities-duplicate${Q}&id=${id}`,
    activitiesSetDate:   (id) => `${ROOT}/anisenso-schedule-manager-activities-set-date${Q}&id=${id}`,
    activitiesReorder:   () => `${ROOT}/anisenso-schedule-manager-activities-reorder${Q}`,
    activitiesExport:    () => `${ROOT}/anisenso-schedule-manager-activities-export${Q}`,
    activitiesRestore:   (id) => `${ROOT}/anisenso-schedule-manager-activities-restore${Q}&id=${id}`,
    activitiesToDraft:   (id) => `${ROOT}/anisenso-schedule-manager-activities-to-draft${Q}&id=${id}`,
    activitiesFromDraft: (id) => `${ROOT}/anisenso-schedule-manager-activities-from-draft${Q}&id=${id}`,
    activitiesDrafts:    () => `${ROOT}/anisenso-schedule-manager-activities-drafts${Q}`,
    activitiesLabor:     () => `${ROOT}/anisenso-schedule-manager-activities-labor${Q}`,
    workerPresentation:  () => `${ROOT}/anisenso-schedule-manager-worker-presentation${Q}`,
    cardViewer:          () => `${ROOT}/anisenso-schedule-manager-card-viewer${Q}`,

    activityVersionsIndex:      () => `${ROOT}/anisenso-schedule-manager-activity-versions${Q}`,
    activityVersionsStore:      () => `${ROOT}/anisenso-schedule-manager-activity-versions-store${Q}`,
    activityVersionsUpdate:     (id) => `${ROOT}/anisenso-schedule-manager-activity-versions-update${Q}&id=${id}`,
    activityVersionsDelete:     (id) => `${ROOT}/anisenso-schedule-manager-activity-versions-delete${Q}&id=${id}`,
    activityVersionsSetActive:  (id) => `${ROOT}/anisenso-schedule-manager-activity-versions-set-active${Q}&id=${id}`,
    activityVersionsGlobalNote: (id) => `${ROOT}/anisenso-schedule-manager-activity-versions-global-note${Q}&id=${id}`,

    activitiesDateNoteSave:     () => `${ROOT}/anisenso-schedule-manager-activities-date-note-save${Q}`,
    activitiesDateNoteDelete:   () => `${ROOT}/anisenso-schedule-manager-activities-date-note-delete${Q}`,
    activitiesDateNoteMove:     () => `${ROOT}/anisenso-schedule-manager-activities-date-note-move${Q}`,

    markersSave:    () => `${ROOT}/anisenso-schedule-manager-markers-save${Q}`,
    markersMove:    (id) => `${ROOT}/anisenso-schedule-manager-markers-move${Q}&id=${id}`,
    markersDelete:  (id) => `${ROOT}/anisenso-schedule-manager-markers-delete${Q}&id=${id}`,


    // Readings rather than writes: what is missing, what the crop is doing,
    // what the sky is about to do over each lot.
    scheduleNotice:   () => `${ROOT}/anisenso-schedule-manager-notice${Q}`,
    scheduleGrowth:   (date) => `${ROOT}/anisenso-schedule-manager-growth${Q}` + (date ? `&date=${date}` : ''),
    // `hourly` is what turns six day cards into six days you can open.
    scheduleWeather:  () => `${ROOT}/anisenso-schedule-manager-weather${Q}&hourly=1`,
    scheduleShare:       () => `${ROOT}/anisenso-schedule-manager-share${Q}`,
    scheduleShareCreate: () => `${ROOT}/anisenso-schedule-manager-share-create${Q}`,
};

function escapeHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}
function fmtNumber(n, d=2) { const v = Number(n ?? 0); return isNaN(v) ? '0' : v.toFixed(d); }

/**
 * A GET that asks again when the answer was not an answer.
 *
 * The session for this page lives in a remote MySQL and the read drops now
 * and then: the same cookie gets 200, then 401, then 200 again, three seconds
 * apart. A list that gave up on the first blip told the operator the data
 * could not be read, which was not true of the data.
 *
 * Only the blips are retried — a dropped session, a lost connection, a server
 * that fell over. A 404 or a refusal is an answer, and asking twice will not
 * change it. Returns a promise, so `.fail(...)` still chains.
 */
function smGet(url, data, onDone) {
    // Called both ways: (url, done) and (url, data, done), as $.get is.
    if (typeof data === 'function') { onDone = data; data = undefined; }
    const d = $.Deferred();
    let tries = 0;
    (function go() {
        tries++;
        $.ajax({ url: url, data: data, dataType: 'json' })
            .done((res) => d.resolve(res))
            .fail((xhr) => {
                const blip = xhr.status === 401 || xhr.status === 0 || xhr.status >= 500;
                // The access log has runs of four dropped polls in a row —
                // twelve seconds — so one quick retry is not enough, and
                // backing off gives the connection time to come back rather
                // than hammering the same bad one.
                if (blip && tries < 4) { setTimeout(go, [600, 1800, 4000][tries - 1]); return; }
                d.reject(xhr);
            });
    })();
    if (onDone) d.done(onDone);
    return d.promise();
}

/** What to tell someone whose list did not arrive. */
function smWhyFailed(xhr) {
    if (!xhr || xhr.status === 401) return 'The session dropped. Press Refresh, or reload the page.';
    if (xhr.status === 0) return 'The connection went. Try again in a moment.';
    if (xhr.status === 404) return 'That is not there any more.';
    return 'Something went wrong reading this.';
}
function fmtPeso(n) { return '₱ ' + Number(n ?? 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

// --- Tab persistence (works even if some saves still reload) ---
// Both shelves, because a save that reloads inside Materials should come
// back to Materials and not to the top of Activities.
$('.sm-tabs a[data-bs-toggle="tab"], .sm-subtabs a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
    history.replaceState(null, null, e.target.getAttribute('href'));
});
$(function () {
    if (!location.hash) return;
    const $tab = $('.sm-tabs a[data-bs-toggle="tab"][href="' + location.hash + '"]');
    if ($tab.length) { $tab.tab('show'); return; }
    // A drawer: open the module that holds it first, then the drawer.
    const $sub = $('.sm-subtabs a[data-bs-toggle="tab"][href="' + location.hash + '"]');
    if (!$sub.length) return;
    const $outer = $sub.closest('.tab-pane');
    if ($outer.length) $('.sm-tabs a[href="#' + $outer.attr('id') + '"]').tab('show');
    $sub.tab('show');
});

function bumpBadge(id, delta = 1) {
    const $b = $('#' + id);
    const v = parseInt($b.text(), 10) || 0;
    $b.text(Math.max(0, v + delta));
    recomputeReadiness();
}

// ---- Day 0 anchors per lot: DAS (sowing) + DAT (transplant) ----
// Transplanted rice has two anchors in one crop cycle: sowing (DAS 0) in the
// seedbed, then transplanting (DAT 0) into the field. Days before transplant
// are counted from DAS 0; days on/after are counted from DAT 0. Each phase has
// three layered maps:
//   LOT_MANUAL_DAY_ZERO  / LOT_MANUAL_TRANSPLANT → dates set on the lot itself
//   LOT_DAY_ZERO_DATES   / LOT_DAT_ZERO_DATES    → effective anchors used by
//         computeDayLabel(); recomputeLotDayZero() lets an activity flagged as
//         the sowing (isDayZero) or transplant (isTransplant) anchor override
//         the manual baseline (earliest date wins).
//   LOT_DAY_ZERO_SOURCE  / LOT_DAT_ZERO_SOURCE   → 'manual' or the activity id
//         that supplied each anchor, so the modal can hide a "mark as anchor"
//         toggle when the lot is already anchored elsewhere.
window.LOT_MANUAL_DAY_ZERO = @json($schedule->lots->mapWithKeys(fn($l) => [
    $l->id => $l->dayZeroDate ? $l->dayZeroDate->format('Y-m-d') : null,
]));
window.LOT_MANUAL_TRANSPLANT = @json($schedule->lots->mapWithKeys(fn($l) => [
    $l->id => $l->transplantDate ? $l->transplantDate->format('Y-m-d') : null,
]));
window.LOT_DAY_ZERO_DATES = Object.assign({}, window.LOT_MANUAL_DAY_ZERO);
window.LOT_DAT_ZERO_DATES = Object.assign({}, window.LOT_MANUAL_TRANSPLANT);

window.MONTH_SHORT = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

window.parseLocalDate = window.parseLocalDate || function (s) {
    if (!s) return null;
    const [y, m, d] = String(s).slice(0, 10).split('-').map(n => parseInt(n, 10));
    if (!y || !m || !d) return null;
    return new Date(y, m - 1, d);
};

window.getScheduleDayType = window.getScheduleDayType || function () {
    return ($('.day-type-label').first().text() || 'DAS').trim();
};

// Compute the " · DAS+N" / " · DAT+N" suffix for a lot/date pair, or '' when
// no anchor applies. Phase-aware: once the date reaches the lot's DAT 0
// (transplant) anchor, the label switches from the schedule's base type
// (e.g. DAS) to DAT and counts from transplanting — matching transplanted-rice
// practice where day counting restarts at transplant. Before transplant (or
// when no transplant anchor exists) it counts from DAS 0 as before.
// Exposed under both names; computeDasLabel kept for existing callers.
window.computeDayLabel = window.computeDasLabel = function (lotId, targetDate) {
    if (!targetDate) return '';
    const b = parseLocalDate(targetDate);
    if (!b) return '';

    const datAnchor = (window.LOT_DAT_ZERO_DATES || {})[lotId];
    if (datAnchor) {
        const t = parseLocalDate(datAnchor);
        if (t && b >= t) {
            const delta = Math.round((b - t) / 86400000);
            // At the transplant pivot itself (DAT 0), also surface the DAS it
            // converts FROM, so the user sees e.g. "DAS+21 → DAT0". Needs a
            // sowing anchor to know the DAS.
            if (delta === 0) {
                const dasAnchor = (window.LOT_DAY_ZERO_DATES || {})[lotId];
                const a0 = parseLocalDate(dasAnchor);
                if (a0) {
                    const dasDelta = Math.round((b - a0) / 86400000);
                    return ' · ' + getScheduleDayType() + (dasDelta > 0 ? '+' : '') + dasDelta + ' → DAT0';
                }
            }
            return ' · DAT' + (delta > 0 ? '+' : '') + delta;
        }
    }

    const dasAnchor = (window.LOT_DAY_ZERO_DATES || {})[lotId];
    if (dasAnchor) {
        const a = parseLocalDate(dasAnchor);
        if (a) {
            const delta = Math.round((b - a) / 86400000);
            return ' · ' + getScheduleDayType() + (delta > 0 ? '+' : '') + delta;
        }
    }
    return '';
};

// Walk every activity card on the timeline and refresh the DAS suffix on
// each of its lot chips. Called after any lot Day 0 change.
window.refreshActivityCardDasLabels = function () {
    $('#activitiesList .activity-card[data-id]').each(function () {
        const targetDate = ($(this).attr('data-target-date') || '').trim();
        $(this).find('.activity-card-lots .item-tag[data-lot-id]').each(function () {
            const lotId = parseInt($(this).attr('data-lot-id'), 10);
            const lotName = $(this).attr('data-lot-name') || $(this).text().split(' · ')[0];
            $(this).text(lotName + computeDasLabel(lotId, targetDate));
        });
    });
};

// Rebuild LOT_DAY_ZERO_DATES from (a) every activity-card flagged as Day 0
// anchor, and (b) the manual per-lot fallback. When multiple activities anchor
// the same lot, the earliest targetDate wins (Day 0 = start of the cycle).
// LOT_DAY_ZERO_SOURCE runs in parallel so callers can tell where each anchor
// came from — 'manual' (set on the lot itself) or the activity id that
// flagged it. The activity modal uses this to hide the "Mark as Day 0"
// checkbox whenever a selected lot is already anchored elsewhere.
window.recomputeLotDayZero = function () {
    // DAS (sowing) map — manual baseline first.
    const map = Object.assign({}, window.LOT_MANUAL_DAY_ZERO || {});
    const source = {};
    Object.keys(map).forEach(lotId => {
        if (map[lotId]) source[lotId] = 'manual';
    });
    // DAT (transplant) map — manual baseline first.
    const datMap = Object.assign({}, window.LOT_MANUAL_TRANSPLANT || {});
    const datSource = {};
    Object.keys(datMap).forEach(lotId => {
        if (datMap[lotId]) datSource[lotId] = 'manual';
    });

    // One pass over the timeline: a card flagged as the sowing anchor feeds the
    // DAS map, one flagged as the transplant anchor feeds the DAT map. Earliest
    // date wins per lot in each map (Day 0 = start of that phase).
    $('#activitiesList .activity-card[data-id]').each(function () {
        const $card = $(this);
        const isDz = $card.attr('data-is-day-zero') === '1';
        const isTp = $card.attr('data-is-transplant') === '1';
        if (!isDz && !isTp) return;
        const activityId = parseInt($card.attr('data-id'), 10);
        const targetDate = ($card.attr('data-target-date') || '').trim();
        if (!targetDate) return;
        $card.find('.activity-card-lots .item-tag[data-lot-id]').each(function () {
            const lotId = parseInt($(this).attr('data-lot-id'), 10);
            if (!lotId) return;
            if (isDz && (!map[lotId] || targetDate < map[lotId])) {
                map[lotId] = targetDate;
                source[lotId] = activityId;
            }
            if (isTp && (!datMap[lotId] || targetDate < datMap[lotId])) {
                datMap[lotId] = targetDate;
                datSource[lotId] = activityId;
            }
        });
    });
    window.LOT_DAY_ZERO_DATES = map;
    window.LOT_DAY_ZERO_SOURCE = source;
    window.LOT_DAT_ZERO_DATES = datMap;
    window.LOT_DAT_ZERO_SOURCE = datSource;
    refreshActivityCardDasLabels();
};

// Seed the derived map immediately so server-rendered Day 0 anchors apply
// without waiting for the first mutation.
$(function () { recomputeLotDayZero(); });

/**
 * Shared confirm-modal helper. Replaces native confirm() so deletes use a
 * consistent in-page modal instead of the browser's blocking alert.
 *
 * Usage:
 *   confirmAction({
 *       title: 'Delete lot',
 *       message: 'Delete lot "Lot A"?',
 *       detail: 'This will mark the lot as deleted.',
 *       confirmText: 'Delete',
 *       confirmClass: 'btn-danger',
 *       icon: 'bx-trash text-danger',
 *       onConfirm: () => { ... },
 *   });
 */
window.confirmAction = function (opts) {
    const o = Object.assign({
        title: 'Confirm',
        message: 'Are you sure?',
        detail: '',
        confirmText: 'Delete',
        confirmClass: 'btn-danger',
        icon: 'bx-trash text-danger',
        onConfirm: null,
    }, opts || {});

    $('#confirmActionTitle').text(o.title);
    $('#confirmActionMessage').html(o.message);
    if (o.detail) {
        $('#confirmActionDetail').show().text(o.detail);
    } else {
        $('#confirmActionDetail').hide().text('');
    }
    $('#confirmActionIcon').attr('class', 'bx me-2 ' + o.icon);

    const $confirm = $('#confirmActionConfirmBtn');
    $confirm
        .removeClass('btn-primary btn-danger btn-warning btn-success btn-info btn-secondary')
        .addClass(o.confirmClass)
        .text(o.confirmText)
        .prop('disabled', false);

    // Detach previous click handlers so old callbacks don't fire again.
    $confirm.off('click.confirmAction').on('click.confirmAction', function () {
        if (typeof o.onConfirm === 'function') {
            try { o.onConfirm(); } catch (err) { console.error(err); }
        }
        $('#confirmActionModal').modal('hide');
    });

    $('#confirmActionModal').modal('show');
};

// --- Readiness recompute: updates the Generate button + banner live ---
const READINESS_RULES = [
    { badge: 'badge-lots',       label: 'Add at least one lot' },
    { badge: 'badge-workers',    label: 'Add at least one worker' },
    { badge: 'badge-activities', label: 'Add at least one activity' },
];

/* What a season still needs before it reads as a season. This used to gate a
   Generate button as well; the banner outlived it, because "no lots yet" is
   worth saying whether or not anything is about to be generated. */
function recomputeReadiness() {
    const issues = READINESS_RULES.filter(r => (parseInt($('#' + r.badge).text(), 10) || 0) === 0);
    if (issues.length === 0) {
        $('#readinessBanner').hide();
        $('#readinessOk').show();
    } else {
        $('#readinessIssuesList').html(issues.map(r => `<li>${escapeHtml(r.label)}</li>`).join(''));
        $('#readinessBanner').show();
        $('#readinessOk').hide();
    }
}

@include('aniSensoAdmin.scheduleManager.partials.script-settings')
@include('aniSensoAdmin.scheduleManager.partials.script-lots')
@include('aniSensoAdmin.scheduleManager.partials.script-workers')
@include('aniSensoAdmin.scheduleManager.partials.script-protocol')
@include('aniSensoAdmin.scheduleManager.partials.script-activities')
@include('aniSensoAdmin.scheduleManager.partials.script-board-tools')
@include('aniSensoAdmin.scheduleManager.partials.script-doc-entries')
@include('aniSensoAdmin.scheduleManager.partials.script-protocol-doc')
@include('aniSensoAdmin.scheduleManager.partials.script-inventory')
@include('aniSensoAdmin.scheduleManager.partials.script-post-harvest')
@include('aniSensoAdmin.scheduleManager.partials.script-notes')
@include('aniSensoAdmin.scheduleManager.partials.script-gallery')
@include('aniSensoAdmin.scheduleManager.partials.script-client-records')
@include('aniSensoAdmin.scheduleManager.partials.script-collab')
@include('aniSensoAdmin.scheduleManager.partials.script-reports')
@include('aniSensoAdmin.scheduleManager.partials.script-sync')
</script>

{{-- Machine-readable snapshot of the per-lot anchor maps. The live-sync
     in-place update fetches this page in the background and reads this block
     (from the fetched document) to refresh window.LOT_MANUAL_DAY_ZERO /
     LOT_MANUAL_TRANSPLANT without re-running any scripts. Keep the
     expressions identical to the window.* assignments above. --}}
@php
    // Blade's @json() directive can't parse a multi-line array argument, so
    // build the payload here and echo the variable.
    $smgrSyncState = [
        'lotManualDayZero' => $schedule->lots->mapWithKeys(fn($l) => [
            $l->id => $l->dayZeroDate ? $l->dayZeroDate->format('Y-m-d') : null,
        ]),
        'lotManualTransplant' => $schedule->lots->mapWithKeys(fn($l) => [
            $l->id => $l->transplantDate ? $l->transplantDate->format('Y-m-d') : null,
        ]),
    ];
@endphp
<script type="application/json" id="smgrSyncState">@json($smgrSyncState)</script>
@endsection
