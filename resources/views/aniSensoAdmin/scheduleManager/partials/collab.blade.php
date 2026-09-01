{{-- ===================== COLLAB ROOM =====================
     The team's room for this season: what was said, what was recorded, and
     how many pages the whiteboard holds. Read live through the same endpoint
     the Member Media room screen uses. --}}
<style>
    .cb-cols { display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr); gap: 1rem; }
    @media (max-width: 991px) { .cb-cols { grid-template-columns: minmax(0, 1fr); } }
    .cb-panel { border: 1px solid #e6e8ec; border-radius: 10px; padding: .8rem .95rem; background: #fff; }
    .cb-panel h6 { font-size: 12.5px; font-weight: 700; color: #495057; text-transform: uppercase; letter-spacing: .04em; }
    .cb-msg { display: flex; justify-content: space-between; gap: .6rem; padding: .45rem .1rem; border-bottom: 1px solid #f1f3f7; }
    .cb-msg:last-child { border-bottom: 0; }
    .cb-who { font-size: 11.5px; font-weight: 700; color: #556ee6; }
    .cb-body { font-size: 12.5px; color: #343a40; white-space: pre-wrap; }
    .cb-at { font-size: 11px; color: #98a4b6; }
    .cb-msg img { max-width: 120px; border-radius: 6px; margin-top: .3rem; display: block; cursor: zoom-in; }
    /* A line this console put in the room, so it is not mistaken for one of
       theirs when scanning the column. */
    .cb-msg.is-mine { background: #f5f8ff; border-left: 2px solid #556ee6; padding-left: .5rem; }

    /* Saying something */
    .cb-say { display: flex; gap: .45rem; align-items: flex-end; margin-top: .7rem; }
    .cb-say textarea {
        flex: 1 1 auto; resize: none; min-height: 36px; max-height: 7rem;
        font-size: 12.5px; border-radius: 8px;
    }
    .cb-say-note { font-size: 10.5px; color: #98a4b6; margin: .35rem 0 0; }

    /* Anee's faces, at the size and spacing the farmer app gives them. The
       margin is leading the picture brings with it, so the lines around it
       move apart rather than being written over. */
    .anee-emo { display: inline-block; width: 1.7em; height: 1.7em; vertical-align: middle; margin: .25em .06em; }
    .anee-emo img { display: block; width: 100%; height: 100%; max-width: none; }
    .cb-rec { display: flex; gap: .6rem; align-items: center; padding: .45rem .1rem; border-bottom: 1px solid #f1f3f7; }
    .cb-rec:last-child { border-bottom: 0; }
    .cb-rec img { width: 72px; height: 48px; object-fit: cover; border-radius: 6px; background: #f6f8fb; }
    .cb-pages { display: flex; flex-wrap: wrap; gap: .35rem; }
    .cb-page {
        display: inline-flex; align-items: center; gap: .25rem; font-size: 11px; font-weight: 600;
        background: #eef1f6; color: #556ee6; border-radius: 999px; padding: .1rem .55rem;
    }
    .cb-empty { text-align: center; padding: 1.6rem 1rem; color: #98a4b6; font-size: 12.5px; }
</style>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <h5 class="text-dark mb-1">Collab room</h5>
        <small class="text-secondary">
            The team's room for this season — what was said, what was recorded, and what the
            whiteboard holds. A message or a recording can be removed; the board is theirs.
        </small>
    </div>
    <button type="button" class="btn btn-light btn-sm" id="cbReload"><i class="bx bx-refresh"></i> Refresh</button>
</div>

<div id="cbBody"></div>
