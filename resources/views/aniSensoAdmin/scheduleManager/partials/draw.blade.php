{{-- ===================== DRAW =====================
     Every drawing the season produced, from the drawing pad and from the
     Collab Room's board. A drawing is an entry inside the media list of the
     note that holds it, which is why it has no name of its own: the note's
     title is the drawing's, and that is edited in Notes. --}}
<style>
    .dw-tiles { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: .6rem; }
    .dw-tile { border: 1px solid #e6e8ec; border-radius: 10px; overflow: hidden; background: #fff; }
    .dw-tile img { width: 100%; height: 132px; object-fit: contain; background: #fbfcfe; display: block; cursor: zoom-in; }
    .dw-body { padding: .45rem .55rem; }
    .dw-title { font-size: 12px; font-weight: 600; color: #343a40; }
    .dw-meta { font-size: 11px; color: #98a4b6; margin-top: .1rem; }
    .dw-acts { display: flex; gap: .3rem; margin-top: .4rem; }
    .dw-gone {
        width: 100%; height: 132px; display: flex; align-items: center; justify-content: center;
        background: #f6f8fb; color: #c3cbd6; font-size: 1.6rem;
    }
    .dw-empty { text-align: center; padding: 2.5rem 1rem; color: #98a4b6; }
    .dw-empty i { font-size: 2.2rem; display: block; margin-bottom: .4rem; }
</style>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <h5 class="text-dark mb-1">Draw</h5>
        <small class="text-secondary">
            What was sketched on this season, from the drawing pad and from the team board. A
            drawing lives inside the note that holds it, so its name is that note's — fix it in
            <strong>Notes</strong>.
        </small>
    </div>
    <button type="button" class="btn btn-light btn-sm" id="dwReload"><i class="bx bx-refresh"></i> Refresh</button>
</div>

<div id="dwBody"></div>
