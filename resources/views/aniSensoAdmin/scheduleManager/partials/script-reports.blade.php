// ---------- REPORTS ----------
(function () {

const RP = `${ROOT}/anisenso-schedule-manager-saved-reports`;
const SQ = `?scheduleId=${SCHEDULE_ID}`;
const esc = (v) => escapeHtml(v);
let started = false;

// The labor screen already exists above the tabs; this is the same door.
$('#rpLaborBtn').on('click', () => $('#openLaborSummaryBtn').trigger('click'));

function load() {
    $('#rpBody').html('<div class="rp-empty"><i class="bx bx-loader-alt bx-spin"></i>Reading the saved reports…</div>');
    smGet(`${RP}-data${SQ}`, function (res) {
        const rows = (res && res.data) || [];
        $('#rpBody').html(rows.length ? rows.map(r => `
            <div class="rp-card">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="min-w-0">
                        <div class="rp-title">${esc(r.title)}</div>
                        <div class="rp-meta">${esc(r.when || '')}${r.yieldAmount !== null ? ` · ${fmtNumber(r.yieldAmount)} ${esc(r.yieldUnit)}` : ''}</div>
                    </div>
                    <button class="btn btn-sm btn-outline-danger js-rp-del" data-id="${r.id}"><i class="bx bx-trash"></i></button>
                </div>
                <div class="rp-figs">
                    <span class="rp-fig">Revenue ${fmtPeso(r.grossRevenue)}</span>
                    <span class="rp-fig">Materials ${fmtPeso(r.materialsCost)}</span>
                    <span class="rp-fig">Services ${fmtPeso(r.servicesCost)}</span>
                    <span class="rp-fig">Labour ${fmtPeso(r.laborCost)}</span>
                    <span class="rp-fig">Other ${fmtPeso(r.expensesCost)}</span>
                    <span class="rp-fig">Cost ${fmtPeso(r.totalCost)}</span>
                    <span class="rp-fig ${r.netProfit >= 0 ? 'is-good' : 'is-bad'}">Net ${fmtPeso(r.netProfit)}</span>
                </div>
                ${r.notes ? `<div class="rp-meta mt-2" style="white-space:pre-wrap">${esc(r.notes)}</div>` : ''}
            </div>`).join('')
            : '<div class="rp-empty"><i class="bx bx-line-chart"></i>The client has not saved a post-harvest report on this season.</div>');
    }).fail((xhr) => {
        // The tab is not marked as read when the read failed, so coming back
        // to it asks again instead of showing this for ever.
        started = false;
        $('#rpBody').html('<div class="rp-empty"><i class="bx bx-error"></i>HERE</div>'.replace('HERE', escapeHtml(smWhyFailed(xhr))));
    });
}

$('#rpReload').on('click', load);

$(document).on('click', '.js-rp-del', function () {
    if (!confirm('Remove this saved report from the client\'s app?')) return;
    $.ajax({
        url: `${RP}-delete${SQ}&id=${$(this).data('id')}`,
        type: 'DELETE',
        data: { _token: CSRF },
        success: (res) => { res.success ? (toastr.success(res.message), load()) : toastr.error(res.message); },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Delete failed')
    });
});

$('.sm-tabs a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
    if ($(e.target).attr('href') !== '#tab-reports' || started) return;
    started = true;
    load();
});
if (location.hash === '#tab-reports') { started = true; $(load); }

})();
