{{-- ===================== POST-HARVEST =====================
     What came off the field and what happened to it. Same table the farmer
     app writes; each kind of observation asks a few extra questions of its
     own, and those answers are the client's — shown, never rewritten from
     here. --}}
@php
    $phCategories = \App\Http\Controllers\aniSensoAdmin\ScheduleManager\PostHarvestController::CATEGORIES;
@endphp
<style>
    .ph-card { border: 1px solid #e6e8ec; border-radius: 10px; padding: .8rem .95rem; margin-bottom: .6rem; }
    .ph-card:hover { border-color: #c7d2fe; }
    .ph-title { font-weight: 600; color: #343a40; font-size: 13.5px; }
    .ph-meta { font-size: 11.5px; color: #98a4b6; margin-top: .12rem; }
    .ph-figs { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .45rem; }
    .ph-fig {
        display: inline-flex; align-items: center; gap: .25rem; font-size: 11px; font-weight: 600;
        background: #eef1f6; color: #556ee6; border-radius: 999px; padding: .1rem .55rem;
    }
    .ph-shots { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .5rem; }
    .ph-shots img { width: 76px; height: 60px; object-fit: cover; border-radius: 6px; border: 1px solid #e6e8ec; cursor: zoom-in; }
    .ph-empty { text-align: center; padding: 2.5rem 1rem; color: #98a4b6; }
    .ph-empty i { font-size: 2.2rem; display: block; margin-bottom: .4rem; }
    .ph-total { font-size: 12.5px; color: #495057; }
    .ph-total b { color: #0f8a5f; }
</style>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <h5 class="text-dark mb-1">Post-harvest</h5>
        <small class="text-secondary">
            What came off this season and what happened to it — yield, moisture, who bought it
            and for how much, and the lessons for next year.
        </small>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-light btn-sm" id="phReload"><i class="bx bx-refresh"></i> Refresh</button>
        <button type="button" class="btn btn-primary btn-sm" id="phNewBtn"><i class="bx bx-plus me-1"></i> New record</button>
    </div>
</div>

<p class="ph-total mb-3" id="phTotal"></p>
<div id="phBody"></div>

<div class="modal fade" id="phModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title mb-0" id="phModalTitle">Post-harvest record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="phId">
                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label text-dark">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="phTitle" maxlength="191" placeholder="e.g. Lot A harvest — 62 sacks">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label text-dark">Kind of observation <span class="text-danger">*</span></label>
                        <select class="form-select" id="phCategory">
                            @foreach ($phCategories as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-dark">When</label>
                        <input type="date" class="form-control" id="phDate">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-dark">Lot</label>
                        <select class="form-select" id="phLot">
                            <option value="0">The whole season</option>
                            @foreach ($schedule->lots as $lot)
                                <option value="{{ $lot->id }}">{{ $lot->lotName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-dark">Buyer</label>
                        <input type="text" class="form-control" id="phBuyer" maxlength="191">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-dark">Yield</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="phYield">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-dark">Unit</label>
                        <input type="text" class="form-control" id="phUnit" maxlength="24" placeholder="sacks, kg, t">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-dark">Moisture %</label>
                        <input type="number" step="0.1" min="0" max="100" class="form-control" id="phMoisture">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-dark">Price per unit</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="phPrice">
                    </div>
                    <div class="col-12">
                        <label class="form-label text-dark">Notes</label>
                        <textarea class="form-control" id="phNotes" rows="5"></textarea>
                    </div>
                </div>
                <div class="alert alert-light border mt-3 mb-0 d-none" id="phExtrasHint">
                    <i class="bx bx-info-circle me-1"></i>
                    This kind of observation asks a few more questions in the client's app. Those
                    answers, and any photos, stay exactly as they wrote them.
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-outline-danger btn-sm" id="phDeleteBtn"><i class="bx bx-trash"></i> Delete</button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="phSaveBtn"><i class="bx bx-save me-1"></i> Save record</button>
                </div>
            </div>
        </div>
    </div>
</div>
