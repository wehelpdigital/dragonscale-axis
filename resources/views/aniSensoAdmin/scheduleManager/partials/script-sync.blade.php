// ============================================================================
// Live sync — makes two setup pages on the same schedule feel collaborative.
//
// NOTE: like every script-* partial, this renders INSIDE the page's single
// shared <script> block — raw JS only, no <script>/<style> tags here.
//
// How it works (no websockets — works on plain XAMPP / shared hosting):
//   • Every mutation bumps `syncVersion` server-side (TouchScheduleSync
//     middleware), tagged with this tab's client id via the X-Sync-Client
//     header set below.
//   • This script polls the sync-status endpoint every few seconds. When the
//     version moves and the change was NOT made by this tab, the page fetches
//     its own URL in the background, parses the fresh HTML, and swaps the
//     content regions in place — no navigation, no loading overlay, and the
//     scroll position never moves. A full reload (doRefresh) remains only as
//     a fallback when the fetch or swap fails.
//   • Updating never interrupts work: while a modal is open, a drag is in
//     progress, or an input is focused, a "changes pending" banner is shown
//     instead and the update happens once the user is idle again.
//   • The poll also carries presence, so the header shows who else has this
//     schedule open right now and whether they're editing or dragging.
// ============================================================================
(function () {
    // Presence-pulse CSS — injected from JS because this partial lives inside
    // the page's shared <script> block and can't carry its own <style> tag.
    $('<style>' +
        '@keyframes syncPresencePulse {' +
        '  0%, 100% { box-shadow: 0 0 0 0 rgba(85, 110, 230, .35); }' +
        '  50%      { box-shadow: 0 0 0 4px rgba(85, 110, 230, .12); }' +
        '}' +
        '#syncPresence .sync-presence-pulse { animation: syncPresencePulse 1.6s ease infinite; border-radius: 14px; }' +
        '@media (prefers-reduced-motion: reduce) { #syncPresence .sync-presence-pulse { animation: none; } }' +
    '</style>').appendTo('head');

    // Per-browser-tab identity, stable across our own sync reloads
    // (sessionStorage is scoped to the tab, exactly what we want).
    let CID = sessionStorage.getItem('smgrSyncCid');
    if (!CID) {
        CID = 'c' + Math.random().toString(36).slice(2, 12) + Date.now().toString(36);
        sessionStorage.setItem('smgrSyncCid', CID);
    }

    // Tag every AJAX request from this tab so the server records which client
    // made each change — that's how we recognise (and skip) our own edits.
    // ajaxSetup merges: the CSRF beforeSend from vendor-scripts is preserved.
    $.ajaxSetup({ headers: { 'X-Sync-Client': CID } });

    const SYNC_URL      = `${ROOT}/anisenso-schedule-manager-sync-status${Q}`;
    const POLL_MS       = 3000;
    let knownVersion    = {{ (int) ($schedule->syncVersion ?? 0) }};
    let lastPolledVersion = knownVersion; // to detect a still-running burst of edits
    let pendingRefresh  = null;           // { name } once a remote change awaits refresh
    let dragging        = false;
    let polling         = false;

    // ---- Busy detection -----------------------------------------------------
    // The activities board uses native HTML5 drag-and-drop on .activity-card.
    $(document).on('dragstart', '.activity-card', function () { dragging = true; });
    $(document).on('dragend drop', function () { setTimeout(function () { dragging = false; }, 60); });

    function isBusy() {
        if (dragging) return true;
        if (document.querySelector('.modal.show')) return true; // any open dialog (incl. Quill editors)
        const el = document.activeElement;
        if (el && (el.matches('input, textarea, select') || el.isContentEditable)) return true;
        return false;
    }

    function myState() {
        if (dragging) return 'dragging';
        if (document.querySelector('.modal.show')) return 'editing';
        return 'idle';
    }

    // ---- Presence chip in the header ---------------------------------------
    function renderViewers(viewers) {
        const $box = $('#syncPresence');
        if (!$box.length) return;
        if (!viewers || !viewers.length) { $box.empty().hide(); return; }

        const colors = ['#556ee6', '#34c38f', '#f1b44c', '#f46a6a', '#50a5f1'];
        let html = '<i class="bx bx-group text-secondary" style="font-size:16px;" title="Also viewing this schedule"></i>';
        viewers.slice(0, 5).forEach(function (v, i) {
            const initials = escapeHtml(String(v.name || '?').trim().split(/\s+/).map(w => w[0]).slice(0, 2).join('').toUpperCase());
            const stateTxt = v.state === 'dragging' ? ' — moving activities…'
                           : v.state === 'editing'  ? ' — editing…' : '';
            const pulse = v.state !== 'idle' ? 'sync-presence-pulse' : '';
            html += `
                <span class="d-inline-flex align-items-center gap-1 ${pulse}" style="background:#f6f7fb;border:1px solid #e6e8ec;border-radius:14px;padding:2px 8px 2px 2px;"
                      title="${escapeHtml(v.name)}${v.self ? ' (you, in another tab)' : ''}${escapeHtml(stateTxt)}">
                    <span class="d-inline-flex align-items-center justify-content-center text-white"
                          style="width:20px;height:20px;border-radius:50%;background:${colors[i % colors.length]};font-size:10px;font-weight:600;">${initials}</span>
                    <small class="text-dark" style="font-weight:500;">${escapeHtml(v.name)}${v.self ? ' (you)' : ''}</small>
                    ${v.state !== 'idle' ? '<small class="text-primary" style="font-style:italic;">' + escapeHtml(stateTxt.replace(' — ', '')) + '</small>' : ''}
                </span>`;
        });
        if (viewers.length > 5) html += `<small class="text-secondary">+${viewers.length - 5} more</small>`;
        $box.html(html).css('display', 'flex');
    }

    // ---- Pending-changes banner (shown while the user is mid-action) -------
    function showPendingBanner(name) {
        let $b = $('#syncPendingBanner');
        if (!$b.length) {
            $b = $(`
                <div id="syncPendingBanner" style="position:fixed;bottom:18px;left:18px;z-index:10500;display:none;">
                    <div class="d-flex align-items-center gap-2 bg-white border rounded shadow px-3 py-2">
                        <span class="spinner-grow spinner-grow-sm text-primary" role="status"></span>
                        <span class="text-dark" id="syncPendingText" style="font-size:13px;"></span>
                        <button type="button" class="btn btn-sm btn-primary" id="syncPendingRefreshBtn">Refresh now</button>
                    </div>
                </div>`).appendTo('body');
            $(document).on('click', '#syncPendingRefreshBtn', function () {
                applyRemoteUpdate(pendingRefresh ? pendingRefresh.name : '');
            });
        }
        $('#syncPendingText').text((name ? name : 'Another user') + ' made changes — will update when you pause');
        $b.show();
    }

    function hidePendingBanner() { $('#syncPendingBanner').hide(); }

    // ---- Refresh ------------------------------------------------------------
    // This page takes several seconds to re-render server-side, so the
    // replacement navigation stays "in flight" for a while. doRefresh MUST
    // fire only once: a second call would start a new navigation and Chrome
    // aborts the provisional one, so repeated calls (poll + idle interval)
    // would keep cancelling each other and the refresh would never commit.
    let refreshing = false;

    function doRefresh(name) {
        if (refreshing) return; // navigation already in flight — let it finish
        refreshing = true;
        // Escape hatch: if the navigation somehow dies (server hiccup), allow
        // a retry instead of leaving sync dead until a manual reload.
        setTimeout(function () { refreshing = false; }, 30000);

        // The active tab already survives reloads via the URL hash; keep the
        // scroll position and a note about who changed what for after reload.
        try {
            sessionStorage.setItem('smgrSyncScroll', String(window.scrollY || window.pageYOffset || 0));
            sessionStorage.setItem('smgrSyncNotice', name || '');
        } catch (e) { /* storage full/blocked — reload anyway */ }

        // Feedback while the slow navigation is pending.
        showPendingBanner(name);
        $('#syncPendingText').text('Syncing changes' + (name ? ' by ' + name : '') + '…');
        $('#syncPendingRefreshBtn').prop('disabled', true);

        // Navigate to the same URL explicitly rather than location.reload():
        // a changing _r param guarantees a genuine server round-trip even
        // when a #tab hash is present (identical-URL assignments with a hash
        // are fragment jumps, not loads); the param is ignored server-side
        // and the hash keeps the active tab.
        const params = new URLSearchParams(window.location.search);
        params.set('_r', Date.now().toString(36));
        window.location.href = window.location.pathname + '?' + params.toString() + window.location.hash;
    }

    // ---- In-place update (the normal path — no reload, no scroll jump) -----
    // Fetch this page's own URL in the background, parse the fresh HTML
    // (DOMParser never executes scripts), and swap the server-rendered content
    // regions into the live DOM. Handlers keep working because the page wires
    // everything through delegated $(document).on(...) listeners; per-page
    // widgets (Quill, filters, modals) are outside the swapped regions.

    // Regions replaced wholesale (innerHTML). Every one is a pure
    // server-rendered list container with no widget instances inside.
    const SWAP_REGIONS = [
        '#activitiesList',                 // activities timeline (groups, cards, rest days, markers)
        '#lotsTable tbody',
        '#workersTable tbody',
        '#materialsTable tbody',
        '#servicesTable tbody',
        '#irrigationsList',
        '#attachmentsGrid',
        '#criticalRulesList',
        '#activityLotsContainer',          // lot chips inside the activity modal
        '.version-tabs',                   // activity version strip
    ];
    // Elements whose text content mirrors server-side counts/labels.
    const TEXT_REGIONS = [
        '#scheduleHeaderTitle', '#scheduleHeaderDescription',
        // The materials, services and irrigation badges went with the drawers
        // they counted; a selector that matches nothing is a swap that
        // silently does nothing, which is how a live-sync bug hides.
        '#badge-lots', '#badge-workers',
        '#badge-protocol-doc', '#badge-activities',
    ];

    function swapFromHtml(html) {
        const doc = new DOMParser().parseFromString(html, 'text/html');
        // A session bounce serves the login page — bail to the full-reload path.
        if (!doc.querySelector('#activitiesList')) return false;

        SWAP_REGIONS.forEach(function (sel) {
            const from = doc.querySelector(sel);
            const to = document.querySelector(sel);
            if (from && to) to.innerHTML = from.innerHTML;
        });

        // Lot filter chips get replaced too, but the user's active filter
        // selections must survive the swap.
        (function () {
            const from = doc.querySelector('#activityLotFilterRow');
            const to = document.querySelector('#activityLotFilterRow');
            if (!from || !to) return;
            const active = $(to).find('.activity-lot-chip.active').map(function () {
                return String($(this).attr('data-lot-id'));
            }).get();
            to.innerHTML = from.innerHTML;
            active.forEach(function (id) {
                $(to).find('.activity-lot-chip[data-lot-id="' + id + '"]').addClass('active');
            });
        })();

        TEXT_REGIONS.forEach(function (sel) {
            const from = doc.querySelector(sel);
            const to = document.querySelector(sel);
            if (from && to) to.textContent = from.textContent;
        });

        // Badges/blocks whose inline display state matters too.
        ['#draftsBadge', '#activityLotsEmpty', '#activityLotsContainer'].forEach(function (sel) {
            const from = doc.querySelector(sel);
            const to = document.querySelector(sel);
            if (from && to) {
                if (sel === '#draftsBadge') to.textContent = from.textContent;
                to.style.display = from.style.display;
            }
        });

        // Fresh per-lot anchor maps (a teammate may have changed lot dates).
        try {
            const stateEl = doc.querySelector('#smgrSyncState');
            if (stateEl) {
                const st = JSON.parse(stateEl.textContent || '{}');
                if (st.lotManualDayZero) window.LOT_MANUAL_DAY_ZERO = st.lotManualDayZero;
                if (st.lotManualTransplant) window.LOT_MANUAL_TRANSPLANT = st.lotManualTransplant;
            }
        } catch (e) { /* keep the old maps — labels heal on next real reload */ }

        // Re-apply client-side state to the fresh markup.
        if (window.recomputeLotDayZero) window.recomputeLotDayZero();          // rebuilds anchors + DAS labels
        if (window.applyDateCollapseState) window.applyDateCollapseState();    // per-day accordion
        if (typeof refreshHiddenActivityCount === 'function') refreshHiddenActivityCount();
        // Undo/redo snapshots reference the replaced DOM — clear rather than
        // let an undo resurrect a teammate's overwritten state.
        if (typeof ACTIVITY_UNDO_STACK !== 'undefined') {
            ACTIVITY_UNDO_STACK.length = 0;
            ACTIVITY_REDO_STACK.length = 0;
            if (typeof refreshHistoryBtns === 'function') refreshHistoryBtns();
        }
        // Re-run search/type/lot filters against the new cards.
        $('#activitySearchInput').trigger('input');
        return true;
    }

    function applyRemoteUpdate(name) {
        if (refreshing) return;
        refreshing = true;

        showPendingBanner(name);
        $('#syncPendingText').text('Syncing changes' + (name ? ' by ' + name : '') + '…');
        $('#syncPendingRefreshBtn').prop('disabled', true);

        $.get(window.location.pathname + window.location.search)
            .done(function (html) {
                // Pin the scroll position across the swap — replacing large DOM
                // regions can shift layout by a few px via scroll anchoring.
                // Same-task restore happens before the next paint, so nothing
                // visibly moves.
                const scrollYBefore = window.scrollY || window.pageYOffset || 0;
                let ok = false;
                try { ok = swapFromHtml(html); } catch (e) { ok = false; }
                if (ok) window.scrollTo(0, scrollYBefore);
                if (!ok) {
                    // Release the once-only flag so the fallback reload can run.
                    refreshing = false;
                    doRefresh(name);
                    return;
                }
                refreshing = false;
                // The fetched render reflects at least the version we saw last.
                knownVersion = lastPolledVersion;
                pendingRefresh = null;
                hidePendingBanner();
                $('#syncPendingRefreshBtn').prop('disabled', false);
                toastr.info(name ? 'Updated with changes by ' + escapeHtml(name) : 'Updated with the latest changes');
            })
            .fail(function () {
                refreshing = false;
                doRefresh(name); // network hiccup — fall back to a real reload
            });
    }

    // After a sync reload: restore scroll + tell the user what happened.
    $(function () {
        let sc = null, who = null;
        try {
            sc = sessionStorage.getItem('smgrSyncScroll');
            who = sessionStorage.getItem('smgrSyncNotice');
            sessionStorage.removeItem('smgrSyncScroll');
            sessionStorage.removeItem('smgrSyncNotice');
        } catch (e) { /* ignore */ }
        if (sc !== null) {
            // Two rAFs so layout (tab restore, images) settles first.
            requestAnimationFrame(function () { requestAnimationFrame(function () {
                window.scrollTo(0, parseInt(sc, 10) || 0);
            }); });
        }
        if (who !== null) {
            toastr.info(who ? 'Updated with changes by ' + escapeHtml(who) : 'Updated with the latest changes');
        }
    });

    // ---- Poll loop ----------------------------------------------------------
    function poll() {
        if (refreshing || polling || document.hidden) return; // skip while navigating/overlapping/backgrounded
        polling = true;
        $.get(SYNC_URL, { clientId: CID, state: myState() })
            .done(function (res) {
                if (!res || !res.success) return;
                renderViewers(res.viewers || []);

                const v = Number(res.version) || 0;
                if (v <= knownVersion) { lastPolledVersion = v; return; }

                if (res.editClientId === CID) {
                    // Our own edit — the page already shows it; just absorb.
                    knownVersion = v;
                    lastPolledVersion = v;
                    pendingRefresh = null;
                    hidePendingBanner();
                    return;
                }

                pendingRefresh = { name: res.editedBy || '' };

                // While the other user is mid-burst (version still climbing,
                // e.g. dragging several cards), wait for one quiet cycle so we
                // don't reload repeatedly.
                const burstOver = (v === lastPolledVersion);
                lastPolledVersion = v;
                if (burstOver && !isBusy()) {
                    applyRemoteUpdate(pendingRefresh.name);
                } else {
                    showPendingBanner(pendingRefresh.name);
                }
            })
            .always(function () { polling = false; });
    }
    setInterval(poll, POLL_MS);

    // A pending update fires as soon as the user goes idle — checked more
    // often than the poll so it feels immediate after closing a modal.
    setInterval(function () {
        if (pendingRefresh && !isBusy()) applyRemoteUpdate(pendingRefresh.name);
    }, 1200);

    // When the tab becomes visible again, sync immediately.
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) poll();
    });

    // Debug/inspection handle (also lets support check sync state in devtools).
    window.__smgrSync = {
        cid: CID,
        get knownVersion() { return knownVersion; },
        get pendingRefresh() { return pendingRefresh; },
        isBusy: isBusy,
        poll: poll,
        refresh: function () { applyRemoteUpdate(pendingRefresh ? pendingRefresh.name : ''); },
        hardRefresh: function () { doRefresh(pendingRefresh ? pendingRefresh.name : ''); },
    };
})();
