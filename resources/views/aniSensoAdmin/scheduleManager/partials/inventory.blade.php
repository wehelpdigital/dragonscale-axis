{{-- ===================== INVENTORY =====================
     What the farm holds. There is no stock column anywhere: on hand is the
     sum of the moves, so everything written here is written as a move. --}}
@php
    $invKinds = \App\Http\Controllers\aniSensoAdmin\ScheduleManager\InventoryController::KINDS;
@endphp
<style>
    .iv-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: .6rem; }
    .iv-item { border: 1px solid #e6e8ec; border-radius: 10px; padding: .7rem .85rem; background: #fff; }
    .iv-item:hover { border-color: #c7d2fe; }
    .iv-item.is-low { border-color: #f1b44c; background: #fffaf1; }
    .iv-name { font-weight: 600; color: #343a40; font-size: 13.5px; }
    .iv-kind { font-size: 11.5px; color: #98a4b6; }
    .iv-have { font-size: 20px; font-weight: 700; color: #556ee6; line-height: 1.1; margin-top: .35rem; }
    .iv-have small { font-size: 12px; font-weight: 600; color: #74788d; }
    .iv-low { font-size: 11px; font-weight: 600; color: #b26b00; }
    .iv-acts { display: flex; gap: .3rem; margin-top: .5rem; }
    .iv-empty { text-align: center; padding: 2.5rem 1rem; color: #98a4b6; }
    .iv-empty i { font-size: 2.2rem; display: block; margin-bottom: .4rem; }
    .iv-move { display: flex; justify-content: space-between; gap: .6rem; padding: .45rem .1rem; border-bottom: 1px solid #f1f3f7; font-size: 12.5px; }
    .iv-move:last-child { border-bottom: 0; }
    .iv-delta { font-weight: 700; font-variant-numeric: tabular-nums; }
    .iv-delta.is-in { color: #0f8a5f; }
    .iv-delta.is-out { color: #c0392b; }
</style>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <h5 class="text-dark mb-1">Inventory</h5>
        <small class="text-secondary">
            What this farm holds and what it has spent. On hand is the sum of the movements —
            adding stock here writes a movement, exactly as the client's app does.
        </small>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-light btn-sm" id="ivReload"><i class="bx bx-refresh"></i> Refresh</button>
        <button type="button" class="btn btn-primary btn-sm" id="ivNewItem"><i class="bx bx-plus me-1"></i> New item</button>
    </div>
</div>

<div id="ivBody"></div>

<h6 class="text-dark mt-4 mb-2"><i class="bx bx-history me-1"></i>Movements</h6>
<div class="card border"><div class="card-body py-2" id="ivMoves"></div></div>

{{-- One item. --}}
<div class="modal fade" id="ivItemModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title mb-0" id="ivItemModalTitle">Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="ivItemId">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label text-dark">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="ivName" maxlength="191" placeholder="e.g. Urea 46-0-0">
                    </div>
                    <div class="col-md-7">
                        <label class="form-label text-dark">Kind</label>
                        <select class="form-select" id="ivKind">
                            @foreach ($invKinds as $key => $k)
                                <option value="{{ $key }}">{{ $k['icon'] }} {{ $k['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- COUNTED IN WHAT.
                         One answer, with the pack inside it: "bags (50 kg)"
                         sits in the same list as "kg", so a farm that buys
                         bags counts bags. Which answers appear follows from
                         the kind — a fuel is not sold in sachets. Filled in by
                         the script from the one list both apps read, so
                         nothing here keeps a second copy of it. --}}
                    <div class="col-md-5">
                        <label class="form-label text-dark">Counted in</label>
                        <select class="form-select" id="ivUnit"></select>
                        <small class="text-secondary" id="ivUnitHint"></small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-dark">Say it is low at</label>
                        <div class="input-group">
                            <input type="number" step="0.001" min="0" class="form-control" id="ivLowAt">
                            <span class="input-group-text" id="ivLowUnit"></span>
                        </div>
                    </div>
                    <div class="col-md-6" id="ivOpeningRow">
                        <label class="form-label text-dark">Opening count</label>
                        <div class="input-group">
                            <input type="number" step="0.001" min="0" class="form-control" id="ivOpening">
                            <span class="input-group-text" id="ivOpeningUnit"></span>
                        </div>
                        <small class="text-secondary">Written as the first movement, and it shows in the log.</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-dark">Note</label>
                        <textarea class="form-control" id="ivNote" rows="2" maxlength="500"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-outline-danger btn-sm" id="ivItemDeleteBtn"><i class="bx bx-trash"></i> Delete</button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="ivItemSaveBtn"><i class="bx bx-save me-1"></i> Save item</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- One movement. --}}
<div class="modal fade" id="ivMoveModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title mb-0" id="ivMoveModalTitle">Move stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="ivMoveItemId">
                <p class="text-secondary mb-3" id="ivMoveWhat"></p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-dark">Which way</label>
                        <select class="form-select" id="ivDirection">
                            <option value="in">In — delivered or carried over</option>
                            <option value="out">Out — used or lost</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-dark">How much <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" step="0.001" min="0.001" class="form-control" id="ivQty">
                            <span class="input-group-text" id="ivQtyUnit"></span>
                        </div>
                        {{-- What the count will BE. The question somebody opens
                             this modal to answer, answered before the button is
                             pressed rather than after it. --}}
                        <small class="text-secondary" id="ivAfter"></small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-dark">Why</label>
                        <select class="form-select" id="ivReason">
                            <option value="">The obvious one</option>
                            <option value="open">Opening stock</option>
                            <option value="in">Stock added</option>
                            <option value="out">Used</option>
                            <option value="adjust">Correction</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-dark">When</label>
                        <input type="date" class="form-control" id="ivOn">
                    </div>
                    <div class="col-12">
                        <label class="form-label text-dark">Note</label>
                        <input type="text" class="form-control" id="ivMoveNote" maxlength="500">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="ivMoveSaveBtn"><i class="bx bx-transfer me-1"></i> Record it</button>
            </div>
        </div>
    </div>
</div>
