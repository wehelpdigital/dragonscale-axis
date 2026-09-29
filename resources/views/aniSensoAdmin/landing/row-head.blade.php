{{-- A repeatable row's head: its name and number (a CSS counter), and the
     buttons to move it or let it go (wired in the editor's script). --}}
<div class="lp-row-head">
    <b>{{ $label }}</b>
    <div class="lp-row-tools">
        <button type="button" class="btn btn-light btn-sm" data-row-up title="Move up" aria-label="Move up"><i class="bx bx-up-arrow-alt"></i></button>
        <button type="button" class="btn btn-light btn-sm" data-row-down title="Move down" aria-label="Move down"><i class="bx bx-down-arrow-alt"></i></button>
        <button type="button" class="btn btn-outline-danger btn-sm" data-row-remove title="Remove" aria-label="Remove"><i class="bx bx-trash"></i></button>
    </div>
</div>
