{{-- ===================== DAY CASH =====================
     What each day cost and what it brought in — the "Extra expenses" block
     the farmer app opens under a day header, and the income beside it. Both
     belong to the version the board is showing. --}}
<style>
    .dc-sum { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: 1rem; }
    .dc-fig { border: 1px solid #e6e8ec; border-radius: 10px; padding: .55rem .85rem; background: #fff; min-width: 9rem; }
    .dc-fig b { display: block; font-size: 17px; font-weight: 700; line-height: 1.2; }
    .dc-fig span { font-size: 11.5px; color: #98a4b6; }
    .dc-fig.is-out b { color: #c0392b; }
    .dc-fig.is-in b { color: #0f8a5f; }
    .dc-fig.is-net b { color: #556ee6; }

    .dc-day { border: 1px solid #e6e8ec; border-radius: 10px; margin-bottom: .6rem; overflow: hidden; }
    .dc-dayhead {
        display: flex; justify-content: space-between; align-items: center; gap: .6rem;
        padding: .5rem .8rem; background: #f8fafd; font-size: 12.5px; font-weight: 700; color: #495057;
    }
    .dc-row { display: flex; justify-content: space-between; align-items: center; gap: .6rem; padding: .45rem .8rem; border-top: 1px solid #f1f3f7; }
    .dc-amt { font-weight: 700; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .dc-amt.is-out { color: #c0392b; }
    .dc-amt.is-in { color: #0f8a5f; }
    .dc-what { font-size: 12.5px; color: #343a40; }
    .dc-note { font-size: 11.5px; color: #98a4b6; }
    .dc-empty { text-align: center; padding: 2.2rem 1rem; color: #98a4b6; }
    .dc-empty i { font-size: 2.2rem; display: block; margin-bottom: .4rem; }
</style>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <h6 class="text-dark mb-1">Day cash</h6>
        <small class="text-secondary">
            What each day cost and what it brought in — the diesel for the pump, the sacks sold
            at the gate. Kept against the version the board is showing.
        </small>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-light btn-sm" id="dcReload"><i class="bx bx-refresh"></i> Refresh</button>
        <button type="button" class="btn btn-outline-success btn-sm" id="dcNewIncome"><i class="bx bx-trending-up me-1"></i> Add income</button>
        <button type="button" class="btn btn-primary btn-sm" id="dcNewExpense"><i class="bx bx-plus me-1"></i> Add expense</button>
    </div>
</div>

<div class="dc-sum" id="dcSummary"></div>
<div id="dcBody"></div>

<div class="modal fade" id="dcModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title mb-0" id="dcModalTitle">Expense</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="dcId">
                <input type="hidden" id="dcKind">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-dark">When <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="dcDate">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-dark">How much <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" class="form-control" id="dcAmount">
                    </div>
                    <div class="col-12" id="dcTitleRow">
                        <label class="form-label text-dark">What it was for</label>
                        <input type="text" class="form-control" id="dcTitle" maxlength="191" placeholder="e.g. 40 sacks to the trader">
                    </div>
                    <div class="col-12">
                        <label class="form-label text-dark" id="dcNoteLabel">Note</label>
                        <input type="text" class="form-control" id="dcNote" maxlength="500" placeholder="e.g. water pump diesel">
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-outline-danger btn-sm" id="dcDeleteBtn"><i class="bx bx-trash"></i> Delete</button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="dcSaveBtn"><i class="bx bx-save me-1"></i> Save</button>
                </div>
            </div>
        </div>
    </div>
</div>
