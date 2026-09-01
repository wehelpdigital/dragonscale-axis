{{-- ===================== NOTES =====================
     The client's Notes module. Notes are words: what was seen, what was
     decided, what to remember. Three shelves hold them in the farmer app — a
     note of its own, a note pinned to a day in the plan, and one written
     inline against a lot or an activity — and all three are the same rows
     this reads and writes. Nothing is copied. --}}
<style>
    .nt-card { border: 1px solid #e6e8ec; border-radius: 10px; padding: .8rem .95rem; margin-bottom: .6rem; }
    .nt-card:hover { border-color: #c7d2fe; }
    .nt-title { font-weight: 600; color: #343a40; font-size: 13.5px; }
    .nt-meta { font-size: 11.5px; color: #98a4b6; margin-top: .12rem; }
    .nt-words { font-size: 12.5px; color: #74788d; margin-top: .35rem; white-space: pre-wrap; }
    .nt-shelf {
        display: inline-flex; align-items: center; gap: .25rem; font-size: 10.5px; font-weight: 600;
        background: #eef1f6; color: #556ee6; border-radius: 999px; padding: .1rem .5rem;
    }
    .nt-shelf.is-date { background: #fff4e5; color: #b26b00; }
    .nt-shelf.is-inline { background: #e9f7ef; color: #0f8a5f; }
    /* ---- what is attached, said rather than counted ----
       A paperclip and a 3 does not tell you whether the map is on this note.
       One chip per kind, in the order a note is usually searched: the map
       first, then the drawing, then a recording, then photographs. Coloured
       the way each thing is coloured everywhere else on this page. */
    .nt-atts { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .4rem; }
    .nt-att {
        display: inline-flex; align-items: center; gap: .25rem;
        font-size: 10.5px; font-weight: 700; letter-spacing: .01em;
        border-radius: 999px; padding: .16rem .55rem;
        background: #eef1f6; color: #556ee6;
        border: 1px solid transparent; cursor: pointer;
    }
    .nt-att:hover { filter: brightness(.94); border-color: currentColor; }
    .nt-att:active { transform: scale(.96); }
    .nt-att i { font-size: 12px; }
    .nt-att b { font-weight: 800; opacity: .75; }
    .nt-att.is-map { background: #e6f0ff; color: #2c5bb5; }
    .nt-att.is-draw { background: #efe8fb; color: #6b3fa0; }
    .nt-att.is-video { background: #fdeaea; color: #a33a3a; }
    .nt-att.is-photo { background: #e9f7ef; color: #0f8a5f; }

    /* A thumbnail's own mark. The card names what is on a note; opening it
       should not go back to squares that could be anything. */
    .nt-thumb { position: relative; display: inline-block; }
    .nt-thumb .nt-kind {
        position: absolute; left: 3px; bottom: 3px;
        display: inline-flex; align-items: center; gap: .18rem;
        font-size: 9.5px; font-weight: 800; letter-spacing: .02em;
        border-radius: 999px; padding: .05rem .35rem;
        background: rgb(255 255 255 / .93); color: #2c5bb5;
        pointer-events: none;
    }
    .nt-thumb .nt-kind i { font-size: 11px; }
    .nt-thumb .nt-kind.is-draw { color: #6b3fa0; }

    .nt-empty { text-align: center; padding: 2.5rem 1rem; color: #98a4b6; }
    .nt-empty i { font-size: 2.2rem; display: block; margin-bottom: .4rem; }
    .nt-thumbs { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .5rem; }
    .nt-thumbs img { width: 76px; height: 60px; object-fit: cover; border-radius: 6px; border: 1px solid #e6e8ec; cursor: zoom-in; }
</style>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <h5 class="text-dark mb-1">Notes</h5>
        <small class="text-secondary">
            What the client wrote down about this season — their own notes, the ones pinned to a day,
            and the ones written against a lot or an activity. Edited here, changed there.
        </small>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-light btn-sm" id="ntReload"><i class="bx bx-refresh"></i> Refresh</button>
        <button type="button" class="btn btn-primary btn-sm" id="ntNewBtn"><i class="bx bx-plus me-1"></i> New note</button>
    </div>
</div>

<div class="d-flex align-items-center gap-2 mb-3">
    <div class="input-group" style="max-width: 420px;">
        <span class="input-group-text bg-white" style="border-right:0;"><i class="bx bx-search text-secondary"></i></span>
        <input type="search" class="form-control" id="ntSearch" placeholder="Search these notes…" autocomplete="off" style="border-left:0;">
    </div>
    <small class="text-secondary" id="ntCount"></small>
</div>

<div id="ntBody"></div>

{{-- One note, open for reading and for fixing. A day note has no title of
     its own — it IS its words — so that field steps aside for one. --}}
{{-- A photo full size, or a recording playing. A map and a drawing do not
     come here — they open in their own editors, because on a note they are
     work rather than pictures. --}}
<div class="modal fade" id="ntSeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title mb-0" id="ntSeeTitle">Attachment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center" style="background:#f6f8fb;" id="ntSeeBody"></div>
            <div class="modal-footer">
                <a class="btn btn-light" id="ntSeeOpen" href="#" target="_blank">
                    <i class="bx bx-link-external"></i> Full size
                </a>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="ntModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0" id="ntModalTitle">Note</h5>
                    <small class="text-secondary" id="ntModalSub"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="ntId">
                <input type="hidden" id="ntShelf">
                <div class="mb-3" id="ntTitleRow">
                    <label class="form-label text-dark">Title</label>
                    <input type="text" class="form-control" id="ntTitle" maxlength="191" placeholder="e.g. Pest scouting — west corner">
                </div>
                <div class="mb-2">
                    <label class="form-label text-dark">Words</label>
                    <textarea class="form-control" id="ntBodyField" rows="10" placeholder="What was seen, what was done, what to remember."></textarea>
                </div>
                <div id="ntMedia" class="nt-thumbs"></div>
                <small class="text-secondary d-none" id="ntMediaHint">
                    Attached from the client's phone. Shown here, changed there.
                </small>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-outline-danger btn-sm" id="ntDeleteBtn"><i class="bx bx-trash"></i> Delete</button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="ntSaveBtn"><i class="bx bx-save me-1"></i> Save note</button>
                </div>
            </div>
        </div>
    </div>
</div>
