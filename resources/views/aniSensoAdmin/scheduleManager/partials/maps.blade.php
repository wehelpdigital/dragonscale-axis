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

    /* ---- the map itself ----
       Drawn from the points, fitted to the box they occupy, with no basemap
       under them. Deliberately: the points are latitudes and longitudes, and
       putting a real map behind them would mean a second Google loader on a
       page that already has one. What an admin needs from this screen is
       which shape is which and what it is called — the shapes' sizes and
       positions relative to one another answer that, and answer it offline. */
    .mp-canvas {
        background:
            linear-gradient(#eef1f6 1px, transparent 1px) 0 0 / 24px 24px,
            linear-gradient(90deg, #eef1f6 1px, transparent 1px) 0 0 / 24px 24px,
            #fbfcfe;
        border: 1px solid #e6e8ec; border-radius: 10px; width: 100%; height: 340px;
    }
    .mp-canvas text { font: 600 7px system-ui, sans-serif; fill: #343a40; paint-order: stroke; stroke: #fff; stroke-width: 2px; }
    .mp-scale { font-size: 11px; color: #98a4b6; margin-top: .3rem; }

    .mp-shape { display: flex; align-items: center; gap: .5rem; padding: .4rem 0; border-bottom: 1px solid #f1f3f7; }
    .mp-shape:last-child { border-bottom: 0; }
    .mp-shape input[type="text"] { flex: 1 1 auto; font-size: 12.5px; }
    .mp-shape input[type="color"] { width: 34px; height: 30px; padding: 2px; flex: 0 0 auto; }
    .mp-shape .mp-what { font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .03em;
        color: #74788d; min-width: 3.4rem; }
    .mp-shape.is-cut { opacity: .4; }
    .mp-shape.is-cut input { text-decoration: line-through; }
</style>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <h5 class="text-dark mb-1">Maps</h5>
        <small class="text-secondary">
            The ground as the client drew it — lot outlines, pins, walked lines. This is their
            map, not a copy of it: draw here and it is drawn on theirs.
        </small>
    </div>
    <button type="button" class="btn btn-light btn-sm" id="mpReload"><i class="bx bx-refresh"></i> Refresh saved maps</button>
</div>

{{-- The map itself — the same editor the client uses, over the same shapes.
     Above the shelf, because the shelf is a filing cabinet and this is the
     desk. --}}
@include('aniSensoAdmin.scheduleManager.partials.map-canvas',
    ['schedule' => $schedule, 'mapChrome' => 'admin'])
{{-- 'admin', not 'team': the Collab Room's chrome puts a "Team map" heading
     above the tools, because there the map is one tab among several and has
     to say which. Here it is the whole tab and the page already says Maps.
     Everything else the room's chrome brings — the save shelf, Clear — is
     gated on NOT being a lot's own map, so it stays. --}}

<h6 class="text-dark mt-4 mb-1" style="font-size:12.5px;">
    <i class="bx bx-folder-open me-1"></i>Saved maps
</h6>
<p class="text-secondary mb-2" style="font-size:11.5px;">
    Plans filed from the canvas above. Opening one puts it back on the map — which replaces
    what is drawn there now, because a saved map is a picture of a whole canvas and not a
    layer to lay over one.
</p>

<div id="mpBody"></div>

<div class="modal fade" id="mpModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title mb-0" id="mpModalTitle">Map</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <svg class="mp-canvas" id="mpCanvas" viewBox="0 0 200 120" preserveAspectRatio="xMidYMid meet"></svg>
                <p class="mp-scale mb-3" id="mpScale"></p>
                <h6 class="text-dark mb-1" style="font-size:12.5px;">What is on it</h6>
                <p class="text-secondary mb-2" style="font-size:11.5px;">
                    A shape can be renamed, recoloured, or taken off the map. Where it sits is the
                    client's — this does not move the ground.
                </p>
                <div id="mpShapes"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="mpSave"><i class="bx bx-save"></i> Save the map</button>
            </div>
        </div>
    </div>
</div>
