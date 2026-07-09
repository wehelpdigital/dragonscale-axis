{{-- Focused page-metadata editor loaded inside the Live Editor's
     Bootstrap modal as an iframe. Mirrors the look of
     pages-edit-single (block edit modal): minimal layout,
     popup-style aesthetic, one field shown.

     Required vars:
       $page, $field, $spec, $currentValue
       $currentTldr, $currentWwww (only used when $field === 'tldr_wwww')

     Posts to the focused update route, which only writes the field
     in question — no risk of zeroing out unrelated columns. --}}

@extends('layouts.master-without-nav')

@section('title') Edit {{ $spec['label'] }} @endsection

@section('content')
<style>
    html, body {
        background: #ffffff !important;
        margin: 0 !important;
        padding: 0 !important;
        min-height: 0 !important;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif !important;
        color: #0f172a;
    }
    .main-content, .page-content, .container-fluid, .layout-wrapper {
        padding: 0 !important;
        margin: 0 !important;
        background: transparent !important;
        min-height: 0 !important;
    }
    .page-content { animation: none !important; }
    .page-title-box, #page-topbar, .footer, .vertical-menu, .navbar-brand-box { display: none !important; }
    .card { border: 0 !important; box-shadow: none !important; background: transparent !important; border-radius: 0 !important; margin: 0 !important; }
    .card-body { padding: 0 !important; }

    .rg-meta-header {
        position: sticky;
        top: 0;
        z-index: 50;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 14px 20px;
        background: linear-gradient(180deg, #ccfbf1 0%, #f0fdfa 100%);
        border-bottom: 1px solid #99f6e4;
    }
    .rg-meta-header .meta { display: flex; align-items: center; gap: 10px; }
    .rg-meta-header .label { font-weight: 700; font-size: 14px; color: #0f766e; }
    .rg-meta-header .type-pill {
        background: #0f766e;
        color: #ffffff;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        padding: 3px 8px;
        border-radius: 999px;
        white-space: nowrap;
    }
    .rg-meta-canvas {
        padding: 24px 28px 28px 28px;
        background: #ffffff;
    }
    .form-label {
        font-size: 12px !important;
        font-weight: 600 !important;
        color: #475569 !important;
        margin-bottom: 6px !important;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .form-control, .form-select {
        border: 1px solid #d1d5db !important;
        border-radius: 8px !important;
        padding: 10px 14px !important;
        font-size: 15px !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: none !important;
    }
    .form-control:focus {
        border-color: #14b8a6 !important;
        box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.18) !important;
        outline: 0 !important;
    }
    textarea.form-control { min-height: 120px; resize: vertical; }
    textarea.form-control[data-wwww-json] { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; min-height: 200px; }
    .btn {
        border-radius: 8px !important;
        font-weight: 600;
        padding: 9px 20px !important;
        font-size: 14px !important;
    }
    .btn-primary { background: #0d9488 !important; border-color: #0d9488 !important; }
    .btn-primary:hover { background: #0f766e !important; border-color: #0f766e !important; }
    .btn-outline-secondary { color: #475569 !important; border-color: #cbd5e1 !important; }
    .btn-outline-secondary:hover { background: #f1f5f9 !important; color: #0f172a !important; }

    .help-text {
        margin-top: 8px;
        font-size: 12px;
        color: #64748b;
        line-height: 1.55;
    }
    .alert { border: 0 !important; border-radius: 10px !important; padding: 10px 14px !important; font-size: 13px !important; }
    .field-divider {
        border: 0;
        border-top: 1px solid #e2e8f0;
        margin: 20px 0;
    }
</style>

<div class="rg-meta-header">
    <div class="meta">
        <i class="bx bx-edit-alt" style="font-size: 18px; color: #0f766e;"></i>
        <span class="label">{{ $spec['label'] }}</span>
        <span class="type-pill">Page Metadata</span>
    </div>
    <div style="color: #64748b; font-size: 12px;">Save here, then close the modal to refresh the preview.</div>
</div>

<div class="rg-meta-canvas">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $e)
                <div>{{ $e }}</div>
            @endforeach
        </div>
    @endif

    <form action="{{ route('resort-guru-pages.meta-update') }}" method="POST">
        @csrf
        <input type="hidden" name="id" value="{{ $page->id }}">
        <input type="hidden" name="field" value="{{ $field }}">

        @if($spec['type'] === 'text')
            <label class="form-label">{{ $spec['label'] }}</label>
            <input type="text" name="value" class="form-control" value="{{ $currentValue }}" autofocus>
            <div class="help-text">{{ $spec['help'] }}</div>

        @elseif($spec['type'] === 'textarea')
            <label class="form-label">{{ $spec['label'] }}</label>
            <textarea name="value" class="form-control" autofocus>{{ $currentValue }}</textarea>
            <div class="help-text">{{ $spec['help'] }}</div>

        @elseif($spec['type'] === 'tldr_wwww')
            <label class="form-label">TL;DR (short paragraph card)</label>
            <textarea name="tldr" class="form-control" autofocus>{{ $currentTldr }}</textarea>
            <div class="help-text">1-3 sentence summary shown in the TL;DR card. Leave empty to hide the card on the public page.</div>

            <hr class="field-divider">

            <label class="form-label">WWWW Summary (JSON)</label>
            <textarea name="wwww" class="form-control" data-wwww-json placeholder='{"who": "...", "what": "...", "when": "...", "where": "..."}'>{{ $currentWwww }}</textarea>
            <div class="help-text">JSON object with keys: who, what, when, where. Leave empty to hide the WWWW card.</div>
        @endif

        <hr class="field-divider">

        <div class="d-flex gap-2 justify-content-end">
            <button type="button" class="btn btn-outline-secondary" onclick="parent.postMessage({rgLiveEditMetaClose: true}, '*')">
                Cancel
            </button>
            <button type="submit" class="btn btn-primary">
                <i class="bx bx-save me-1"></i>Save changes
            </button>
        </div>
    </form>
</div>
@endsection
