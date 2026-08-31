{{-- ===================== MAPS =====================
     The maps this season was drawn on. Same rows the farmer app saves; the
     name is the admin's to fix, the shapes are not. --}}
<style>
    .mp-card { border: 1px solid #e6e8ec; border-radius: 10px; padding: .8rem .95rem; margin-bottom: .6rem; }
    .mp-title { font-weight: 600; color: #343a40; font-size: 13.5px; }
    .mp-meta { font-size: 11.5px; color: #98a4b6; margin-top: .12rem; }
    .mp-kinds { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .45rem; }
    .mp-kind {
        display: inline-flex; align-items: center; gap: .25rem; font-size: 11px; font-weight: 600;
        background: #eef1f6; color: #556ee6; border-radius: 999px; padding: .1rem .55rem;
    }
    .mp-labels { font-size: 11.5px; color: #74788d; margin-top: .35rem; }
    .mp-empty { text-align: center; padding: 2.5rem 1rem; color: #98a4b6; }
    .mp-empty i { font-size: 2.2rem; display: block; margin-bottom: .4rem; }
</style>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <h5 class="text-dark mb-1">Maps</h5>
        <small class="text-secondary">
            The ground as the client drew it — lot outlines, pins, walked lines. The name of a
            map can be fixed here; what is on it is drawn in their app.
        </small>
    </div>
    <button type="button" class="btn btn-light btn-sm" id="mpReload"><i class="bx bx-refresh"></i> Refresh</button>
</div>

<div id="mpBody"></div>
