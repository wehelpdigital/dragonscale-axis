{{-- ===================== FIELD RECORDS =====================
     What the season produced away from the plan: what was written, what was
     photographed, what was drawn and mapped, what came off the field, and
     what the client asked the AI about.

     None of it is copied here. Every list reads the same rows the farmer app
     writes, through the same endpoints the Member Media screens use — those
     endpoints simply learned `?scheduleId=`, so this page and those screens
     can never drift apart. --}}
<style>
    .rec-subtabs { display: flex; flex-wrap: wrap; gap: .4rem; margin-bottom: 1rem; }
    .rec-subtabs .nav-link {
        border: 1px solid #e6e8ec; border-radius: 999px; padding: .35rem .9rem;
        font-size: 12.5px; font-weight: 500; color: #495057; background: #fff;
        display: flex; align-items: center; gap: .4rem; cursor: pointer;
    }
    .rec-subtabs .nav-link:hover { background: #eef2ff; color: #2c3e8c; }
    .rec-subtabs .nav-link.active { background: #556ee6; border-color: #556ee6; color: #fff; }
    .rec-subtabs .nav-link .badge { font-size: 10.5px; font-weight: 600; background: #eef1f6; color: #495057; }
    .rec-subtabs .nav-link.active .badge { background: rgba(255,255,255,.85); color: #2c3e8c; }

    .rec-empty { text-align: center; padding: 2.5rem 1rem; color: #98a4b6; }
    .rec-empty i { font-size: 2.2rem; display: block; margin-bottom: .4rem; }

    .rec-card { border: 1px solid #e6e8ec; border-radius: 10px; padding: .8rem .95rem; margin-bottom: .6rem; }
    .rec-title { font-weight: 600; color: #343a40; font-size: 13.5px; }
    .rec-meta { font-size: 11.5px; color: #98a4b6; margin-top: .12rem; }
    .rec-words { font-size: 12.5px; color: #74788d; margin-top: .3rem; }
    .rec-chip {
        display: inline-flex; align-items: center; gap: .25rem; font-size: 10.5px; font-weight: 600;
        background: #eef1f6; color: #556ee6; border-radius: 999px; padding: .1rem .5rem;
    }

    .rec-tiles { display: grid; grid-template-columns: repeat(auto-fill, minmax(148px, 1fr)); gap: .6rem; }
    .rec-tile { border: 1px solid #e6e8ec; border-radius: 10px; overflow: hidden; background: #fff; }
    .rec-tile img { width: 100%; height: 118px; object-fit: cover; display: block; cursor: zoom-in; }
    .rec-tile .rec-tile-body { padding: .45rem .55rem; }
    .rec-tile .rec-title { font-size: 12px; }
    .rec-gone {
        width: 100%; height: 118px; display: flex; align-items: center; justify-content: center;
        background: #f6f8fb; color: #c3cbd6; font-size: 1.6rem;
    }

    .rec-pager { display: flex; align-items: center; justify-content: space-between; gap: .6rem; margin-top: .8rem; }
    .rec-pager small { color: #98a4b6; }

    .rec-turn { display: flex; gap: .6rem; margin-bottom: .7rem; }
    .rec-turn.is-ai { flex-direction: row-reverse; }
    .rec-bubble {
        max-width: 78%; border-radius: 12px; padding: .55rem .75rem; font-size: 13px;
        background: #f6f8fb; color: #343a40; white-space: pre-wrap;
    }
    .rec-turn.is-ai .rec-bubble { background: #eef2ff; color: #2c3e8c; }
</style>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <h5 class="text-dark mb-1">Field records</h5>
        <small class="text-secondary">
            Everything this season produced outside the plan itself — photos, drawings, maps,
            harvest records and AI threads, read live from their app. The words live in
            <strong>Notes</strong>.
        </small>
    </div>
    <button type="button" class="btn btn-light btn-sm" id="recReload"><i class="bx bx-refresh"></i> Refresh</button>
</div>

<div class="rec-subtabs" id="recSubtabs">
    <a class="nav-link active" data-section="photos"><i class="bx bx-image"></i> Photos <span class="badge" data-count="photos">–</span></a>
    <a class="nav-link" data-section="drawings"><i class="bx bx-pencil"></i> Drawings <span class="badge" data-count="drawings">–</span></a>
    <a class="nav-link" data-section="maps"><i class="bx bx-map-alt"></i> Maps <span class="badge" data-count="maps">–</span></a>
    <a class="nav-link" data-section="harvest"><i class="bx bx-basket"></i> Post-harvest <span class="badge" data-count="harvest">–</span></a>
    <a class="nav-link" data-section="ai"><i class="bx bx-bot"></i> AI threads <span class="badge" data-count="ai">–</span></a>
</div>

<div class="d-flex align-items-center gap-2 mb-3" id="recSearchRow">
    <div class="input-group" style="max-width: 420px;">
        <span class="input-group-text bg-white" style="border-right:0;"><i class="bx bx-search text-secondary"></i></span>
        <input type="search" class="form-control" id="recSearch" placeholder="Search these records…" autocomplete="off" style="border-left:0;">
    </div>
</div>

<div id="recBody"></div>

<div class="rec-pager" id="recPager" style="display:none;">
    <small id="recRange"></small>
    <div class="btn-group btn-group-sm">
        <button type="button" class="btn btn-outline-secondary" id="recPrev"><i class="bx bx-chevron-left"></i> Newer</button>
        <button type="button" class="btn btn-outline-secondary" id="recNext">Older <i class="bx bx-chevron-right"></i></button>
    </div>
</div>

{{-- One AI thread, read only. --}}
<div class="modal fade" id="recThreadModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0" id="recThreadTitle">Thread</h5>
                    <small class="text-secondary" id="recThreadSub"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="recThreadBody"></div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>

{{-- One picture, big. --}}
<div class="modal fade" id="recViewer" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="recViewerTitle">Photo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center" id="recViewerBody"></div>
            <div class="modal-footer">
                <a href="#" target="_blank" class="btn btn-outline-primary btn-sm" id="recViewerOpen">Open the file</a>
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
