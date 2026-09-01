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
        background: #f6f8fb; color: #7d8899; font-size: 1.6rem;
    }
    .dw-empty { text-align: center; padding: 2.5rem 1rem; color: #98a4b6; }
    .dw-empty i { font-size: 2.2rem; display: block; margin-bottom: .4rem; }
</style>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <h5 class="text-dark mb-1">Draw</h5>
        <small class="text-secondary">
            What was sketched on this season, from the drawing pad and from the team board.
            Editing one opens the same pad the client draws in, and what you save replaces the
            drawing on their note.
        </small>
    </div>
    <button type="button" class="btn btn-light btn-sm" id="dwReload"><i class="bx bx-refresh"></i> Refresh</button>
</div>

<div id="dwBody"></div>

{{-- A drawing opened its file in a new tab, which is the browser showing a
     PNG rather than the console showing a drawing: no title, no idea which
     note it came from, and the page lost behind it. This keeps it here. --}}
<div class="modal fade" id="dwModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="min-w-0">
                    <h5 class="modal-title mb-0" id="dwModalTitle">Drawing</h5>
                    <small class="text-secondary" id="dwModalMeta"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center" style="background:#f6f8fb;">
                <img id="dwModalImg" src="" alt="" style="max-width:100%;max-height:70vh;border-radius:8px;background:#fff;">
            </div>
            <div class="modal-footer">
                <a class="btn btn-light" id="dwModalOpen" href="#" target="_blank"><i class="bx bx-link-external"></i> Full size</a>
                <button type="button" class="btn btn-light js-dw-note" id="dwModalNote">Its note</button>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- The pad itself. Included once, and opened by name — it is a full-screen
     surface of its own rather than something living in this tab. --}}
@include('aniSensoAdmin.scheduleManager.partials.draw-canvas')
