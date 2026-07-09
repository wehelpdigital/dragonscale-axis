{{-- Post-save confirmation served after updateMetaSingle completes.
     Tells the parent Live Editor to close the modal + reload its
     preview iframe so the new value is visible immediately. --}}

@extends('layouts.master-without-nav')

@section('title') Saved @endsection

@section('content')
<style>
    html, body {
        background: #ffffff !important;
        margin: 0 !important;
        padding: 0 !important;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif !important;
    }
    .saved-confirm {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 280px;
        gap: 12px;
        color: #0f766e;
    }
    .saved-confirm i { font-size: 56px; color: #0d9488; }
    .saved-confirm h6 { margin: 0; font-weight: 700; color: #0f172a; }
    .saved-confirm p { margin: 0; color: #64748b; font-size: 13px; }
</style>

<div class="saved-confirm">
    <i class="bx bx-check-circle"></i>
    <h6>Saved &mdash; refreshing preview…</h6>
    <p>You can close this window if it doesn't close automatically.</p>
</div>

<script>
// Tell the parent Live Editor to close the modal + reload the preview.
parent.postMessage({rgLiveEditMetaSaved: true, field: @json($field)}, '*');
</script>
@endsection
