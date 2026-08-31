{{-- ===================== CHAT TECHNICIAN =====================
     What the client asked the technician about this season, and what the team
     asked in the Collab Room. One list, because to somebody reading a season
     they are the same thing. Read only, apart from the name and the removal:
     a conversation is a record of what was said. --}}
<style>
    .ai-row { border: 1px solid #e6e8ec; border-radius: 10px; padding: .8rem .95rem; margin-bottom: .6rem; }
    .ai-row:hover { border-color: #c7d2fe; }
    .ai-row-t { font-weight: 600; color: #343a40; font-size: 13.5px; }
    .ai-row-m { font-size: 11.5px; color: #98a4b6; margin-top: .12rem; }
    .ai-kind {
        display: inline-flex; align-items: center; gap: .25rem; font-size: 10.5px; font-weight: 600;
        background: #eef1f6; color: #556ee6; border-radius: 999px; padding: .1rem .5rem;
    }
    .ai-kind.is-team { background: #e9f7ef; color: #0f8a5f; }
    .ai-empty { text-align: center; padding: 2.5rem 1rem; color: #98a4b6; }
    .ai-empty i { font-size: 2.2rem; display: block; margin-bottom: .4rem; }
    .ai-turn { display: flex; gap: .6rem; margin-bottom: .7rem; }
    .ai-turn.is-bot { flex-direction: row-reverse; }
    .ai-bubble {
        max-width: 78%; border-radius: 12px; padding: .55rem .75rem; font-size: 13px;
        background: #f6f8fb; color: #343a40; white-space: pre-wrap;
    }
    .ai-turn.is-bot .ai-bubble { background: #eef2ff; color: #2c3e8c; }
</style>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <h5 class="text-dark mb-1">Chat Technician</h5>
        <small class="text-secondary">
            What the client asked about this season, and what the team asked in the Collab Room.
            A thread can be renamed or removed; what was said in it is a record.
        </small>
    </div>
    <button type="button" class="btn btn-light btn-sm" id="ctReload"><i class="bx bx-refresh"></i> Refresh</button>
</div>

<div id="ctBody"></div>

<div class="modal fade" id="ctModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title mb-0" id="ctModalTitle">Thread</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="ctModalBody"></div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>
