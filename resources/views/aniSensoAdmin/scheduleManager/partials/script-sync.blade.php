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
//     version moves and the change was NOT made by this tab, the page reloads
//     itself to show the fresh server-rendered state. The active tab survives
//     via the existing URL-hash persistence; scroll position is restored from
//     sessionStorage.
//   • Refreshing never interrupts work: while a modal is open, a drag is in
//     progress, or an input is focused, a "changes pending" banner is shown
//     instead and the refresh happens once the user is idle again.
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
                doRefresh(pendingRefresh ? pendingRefresh.name : '');
            });
        }
        $('#syncPendingText').text((name ? name : 'Another user') + ' made changes — will refresh when you pause');
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
                    doRefresh(pendingRefresh.name);
                } else {
                    showPendingBanner(pendingRefresh.name);
                }
            })
            .always(function () { polling = false; });
    }
    setInterval(poll, POLL_MS);

    // A pending refresh fires as soon as the user goes idle — checked more
    // often than the poll so it feels immediate after closing a modal.
    setInterval(function () {
        if (pendingRefresh && !isBusy()) doRefresh(pendingRefresh.name);
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
        refresh: function () { doRefresh(pendingRefresh ? pendingRefresh.name : ''); },
    };
})();
