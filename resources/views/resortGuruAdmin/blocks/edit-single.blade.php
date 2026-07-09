{{-- Minimal "edit one block" view loaded inside the Live Editor's
     Bootstrap modal as an iframe. We extend master-without-nav so
     the JS deps (jQuery, Bootstrap, Quill, SortableJS) the builder
     partial relies on are all loaded — but we then override the
     Skote dashboard theme aggressively so the editor feels like a
     focused popup, not a stripped-down dashboard page.

     Required vars: $block, $ownerType, $ownerId, $focusBlockId

     The builder partial accepts $focusBlockId and:
       - filters state.blocks to just that one row, and
       - hides the add-block toolbar via the .rg-builder--focus class.

     Save / delete / reorder all hit the same existing
     /resort-guru-blocks-{save,delete,reorder} endpoints. After the
     admin clicks Save inside this iframe, the parent live-edit
     view detects the modal close + reloads the preview iframe so
     the change shows up immediately. --}}

@extends('layouts.master-without-nav')

@section('title') Edit Block #{{ $block->id }} @endsection

@section('content')
<style>
    /* =================================================================
       Strip the Skote dashboard look. master-without-nav still pulls
       app.min.css through head-css, which assumes a sidebar + card
       grid layout. We neutralize the wrapper styling, then apply a
       modern, focused-editor look on top. The aim: the iframe should
       feel like a Notion-style edit panel, not a shrunken dashboard
       page.
       ================================================================= */

    html, body {
        background: #ffffff !important;
        margin: 0 !important;
        padding: 0 !important;
        min-height: 0 !important;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif !important;
        color: #0f172a;
    }

    /* Kill every Skote wrapper that adds dashboard padding / margin /
       background. The builder lives directly under <body> in this
       layout, so any of these classes elsewhere come from the partial. */
    .main-content,
    .page-content,
    .container-fluid,
    .layout-wrapper {
        padding: 0 !important;
        margin: 0 !important;
        background: transparent !important;
        min-height: 0 !important;
    }
    .page-content { animation: none !important; }
    .page-title-box,
    #page-topbar,
    .footer,
    .vertical-menu,
    .navbar-brand-box { display: none !important; }

    /* The Skote .card is the visual signature of the dashboard.
       Neutralize it so blocks read as plain edit panels. */
    .card {
        border: 0 !important;
        box-shadow: none !important;
        background: transparent !important;
        border-radius: 0 !important;
        margin: 0 !important;
    }
    .card-body { padding: 0 !important; }
    .card-header {
        background: transparent !important;
        border: 0 !important;
        padding: 0 0 12px 0 !important;
    }

    /* Modern form fields. Skote's defaults are fine for tables but
       look heavy in an editor. Cleaner inputs read as a content
       editor, not a dashboard form. */
    .form-label {
        font-size: 12px !important;
        font-weight: 600 !important;
        color: #475569 !important;
        margin-bottom: 4px !important;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .form-control,
    .form-select,
    .input-group-text {
        border: 1px solid #d1d5db !important;
        border-radius: 8px !important;
        padding: 8px 12px !important;
        font-size: 14px !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: none !important;
        transition: border-color 0.12s ease, box-shadow 0.12s ease !important;
    }
    .form-control:focus,
    .form-select:focus {
        border-color: #6366f1 !important;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.18) !important;
        outline: 0 !important;
    }
    textarea.form-control { min-height: 96px; resize: vertical; }

    /* Buttons: keep Bootstrap colors but flatten the shadow so they
       read as buttons inside a panel, not as glassy dashboard CTAs. */
    .btn {
        border-radius: 8px !important;
        font-weight: 600;
        padding: 7px 16px !important;
        font-size: 13px !important;
        transition: background-color 0.12s ease, color 0.12s ease, border-color 0.12s ease !important;
    }
    .btn-primary {
        background: #4f46e5 !important;
        border-color: #4f46e5 !important;
    }
    .btn-primary:hover { background: #4338ca !important; border-color: #4338ca !important; }
    .btn-outline-secondary { color: #475569 !important; border-color: #cbd5e1 !important; }
    .btn-outline-secondary:hover { background: #f1f5f9 !important; color: #0f172a !important; }
    .btn-danger { background: #dc2626 !important; border-color: #dc2626 !important; }
    .btn-success { background: #059669 !important; border-color: #059669 !important; }

    /* Header chip sits at the top of the iframe so the admin always
       sees what block they're editing. Sticky so it stays visible if
       the form scrolls. */
    .rg-edit-single-header {
        position: sticky;
        top: 0;
        z-index: 50;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 14px 20px;
        background: linear-gradient(180deg, #eef2ff 0%, #f8fafc 100%);
        border-bottom: 1px solid #e2e8f0;
    }
    .rg-edit-single-header .meta {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .rg-edit-single-header .block-num {
        font-weight: 700;
        font-size: 13px;
        color: #4338ca;
    }
    .rg-edit-single-header .type-pill {
        background: #4338ca;
        color: #ffffff;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        padding: 3px 8px;
        border-radius: 999px;
        white-space: nowrap;
    }
    .rg-edit-single-header .hint {
        color: #64748b;
        font-size: 12px;
        text-align: right;
    }

    /* Edit panel — wraps the focused block so it reads as a real
       editor card with breathing room. The .rg-builder--focus rule
       already strips the outer builder padding; this is the inner
       canvas for the block being edited. */
    .rg-edit-canvas {
        padding: 20px 24px 32px 24px;
        background: #ffffff;
    }

    /* Reset legacy block-card padding / borders the builder partial
       applies — in focus mode we want one continuous editor panel,
       not a nested card. */
    .rg-builder--focus .rg-block,
    .rg-builder--focus .rg-block-card,
    .rg-builder--focus .rg-edit-card {
        background: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 12px !important;
        box-shadow: 0 4px 12px -2px rgba(15, 23, 42, 0.06) !important;
        padding: 16px 18px !important;
        margin: 0 !important;
    }
    .rg-builder--focus .rg-block + .rg-block { margin-top: 16px !important; }

    /* Quill rich-text wrapper inside the builder. Make the editor
       feel native to the popup look. */
    .ql-toolbar.ql-snow {
        border: 1px solid #d1d5db !important;
        border-bottom: 0 !important;
        border-radius: 8px 8px 0 0 !important;
        background: #f8fafc !important;
    }
    .ql-container.ql-snow {
        border: 1px solid #d1d5db !important;
        border-radius: 0 0 8px 8px !important;
        min-height: 140px;
        font-family: inherit !important;
    }
    .ql-editor { font-size: 14px; line-height: 1.55; }

    /* Empty / error states inside the builder. */
    .rg-builder-empty,
    .rg-builder-error {
        text-align: center;
        padding: 24px;
        color: #64748b;
        background: #f8fafc;
        border-radius: 8px;
        margin: 0;
    }

    /* Bootstrap alerts inside the popup */
    .alert {
        border: 0 !important;
        border-radius: 10px !important;
        padding: 10px 14px !important;
        font-size: 13px !important;
    }

    /* =================================================================
       AUTO-EDIT MODE: in focus, the admin should see the EDIT FIELDS
       immediately, not the collapsed display row + an Edit button. We
       hide the .rg-block row UI entirely, then transform the builder's
       blockEditorModal so it reads as the natural inline content of
       the popup — no backdrop overlay, no centered dialog floating in
       the middle, just edit fields flush in the iframe.
       ================================================================= */

    /* Hide the collapsed-row block list — user sees the editor straight away. */
    .rg-builder--focus .rg-block,
    .rg-builder--focus .rg-builder-empty { display: none !important; }
    .rg-builder--focus #rgBlocksCanvas { padding: 0; min-height: 0; }

    /* Show the modal as inline content rather than as a centered dialog
       with a dark backdrop. The parent live-edit modal already provides
       the modal frame; this just lets the edit form fill the iframe. */
    .rg-builder--focus #blockEditorModal {
        position: static !important;
        display: block !important;
        background: transparent !important;
        z-index: 1 !important;
        opacity: 1 !important;
        pointer-events: auto !important;
        overflow: visible !important;
    }
    .rg-builder--focus #blockEditorModal .modal-dialog {
        position: static !important;
        margin: 0 !important;
        max-width: 100% !important;
        pointer-events: auto !important;
    }
    .rg-builder--focus #blockEditorModal .modal-content {
        border: 0 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        background: transparent !important;
    }
    .rg-builder--focus #blockEditorModal .modal-header {
        background: transparent !important;
        border-bottom: 1px solid #e2e8f0 !important;
        padding: 12px 0 !important;
    }
    .rg-builder--focus #blockEditorModal .modal-header .btn-close { display: none !important; }
    .rg-builder--focus #blockEditorModal .modal-body { padding: 16px 0 !important; }
    .rg-builder--focus #blockEditorModal .modal-footer {
        background: transparent !important;
        border-top: 1px solid #e2e8f0 !important;
        padding: 12px 0 !important;
    }
    /* Bootstrap's modal backdrop overlay would dim the popup — we live
       inside a modal already, so hide it. */
    .rg-builder--focus .modal-backdrop { display: none !important; }
    /* The Skote head-css enforces .modal { background: rgba(0,0,0,0.5) } —
       override for our inline-modal display. */
    .rg-builder--focus #blockEditorModal.show { background: transparent !important; }
</style>

<div class="rg-edit-single-header">
    <div class="meta">
        <i class="bx bx-edit-alt" style="font-size: 18px; color: #4338ca;"></i>
        <span class="block-num">Block #{{ $block->id }}</span>
        <span class="type-pill">{{ $block->block_type }}</span>
    </div>
    <div class="hint">
        Save here, then close the modal to refresh the preview.
    </div>
</div>

<div class="rg-edit-canvas">
    @include('resortGuruAdmin.blocks.builder', [
        'ownerType' => $ownerType,
        'ownerId' => $ownerId,
        'focusBlockId' => $focusBlockId,
    ])
</div>

<script>
// Auto-fire the focused block's Edit click as soon as the builder
// finishes painting the row, so the admin lands directly on the edit
// fields instead of having to click Edit a second time.
//
// The builder fetches blocks async (XHR → render) and attaches the
// .edit button click handler inside its own IIFE. We watch the
// canvas with MutationObserver and pull the trigger as soon as the
// matching row appears. Time-cap the observer so we don't watch
// forever if something goes wrong.
(function () {
    var FOCUS_ID = @json((int) $focusBlockId);
    var CANVAS_ID = 'rgBlocksCanvas';
    var SEL = '.rg-block .edit, .rg-edit-card .edit, [data-block-id="' + FOCUS_ID + '"] .edit';
    var fired = false;
    var deadline = Date.now() + 8000; // safety cap

    function tryFire() {
        if (fired) return true;
        var btn = document.querySelector(SEL);
        if (!btn) return false;
        fired = true;
        btn.click();
        // If the block render is wrapped in any conditional UI that
        // delays its edit handler, retry a tick later in case the
        // first click bound to a stale handler.
        setTimeout(function () {
            // Check that the editor modal actually opened; if not, fire again.
            var openModal = document.querySelector('#blockEditorModal.show, #blockEditorBody form, #blockEditorBody .form-control');
            if (!openModal) {
                fired = false;
                btn = document.querySelector(SEL);
                if (btn) { fired = true; btn.click(); }
            }
        }, 120);
        return true;
    }

    function start() {
        if (tryFire()) return;
        var canvas = document.getElementById(CANVAS_ID);
        if (!canvas) {
            // Builder hasn't even rendered yet, retry shortly.
            if (Date.now() < deadline) return setTimeout(start, 50);
            return;
        }
        var obs = new MutationObserver(function () {
            if (tryFire()) obs.disconnect();
            else if (Date.now() > deadline) obs.disconnect();
        });
        obs.observe(canvas, { childList: true, subtree: true });
        // Also poll just in case mutations don't fire (rare edge with
        // virtual rendering frameworks). Cheap insurance.
        var poll = setInterval(function () {
            if (tryFire() || Date.now() > deadline) clearInterval(poll);
        }, 100);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();

// When the focused block editor modal closes (after Save or Cancel), tell
// the parent Live Editor to close the OUTER modal and refresh the preview,
// so the admin returns straight to the live page instead of being left on
// the bare builder canvas behind the form.
(function () {
    function attach() {
        var el = document.getElementById('blockEditorModal');
        if (!el) return false;
        el.addEventListener('hidden.bs.modal', function () {
            if (window.parent && window.parent !== window) {
                window.parent.postMessage({ rgLiveEditBlockClose: true }, '*');
            }
        });
        return true;
    }
    if (!attach()) {
        var t0 = Date.now();
        var iv = setInterval(function () {
            if (attach() || Date.now() - t0 > 8000) clearInterval(iv);
        }, 100);
    }
})();
</script>
@endsection
