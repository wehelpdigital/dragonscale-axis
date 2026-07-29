<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h5 class="text-dark mb-1">Lot Details</h5>
        <small class="text-secondary">Define each lot (parcel). You can add multiple lots — each can be grouped during calendar generation.</small>
    </div>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#lotModal" id="addLotBtn">
        <i class="bx bx-plus me-1"></i> Add Lot
    </button>
</div>

<div class="table-responsive">
    <table class="table table-hover align-middle mb-0" id="lotsTable">
        <thead class="table-light">
            <tr>
                <th>Lot Name</th>
                <th>Size</th>
                <th>Variety</th>
                <th>Notes</th>
                <th class="text-end" style="width: 160px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($schedule->lots as $lot)
                <tr data-id="{{ $lot->id }}">
                    <td class="text-dark">
                        <strong data-field="lotName">{{ $lot->lotName }}</strong>
                        @if($lot->dayZeroDate)
                            <span class="badge bg-info text-white ms-1 day-zero-badge" style="font-size:10px;font-weight:500;" title="{{ $schedule->dayType }} Day 0 anchor">
                                <i class="bx bx-target-lock"></i>
                                <span class="day-type-label">{{ $schedule->dayType }}</span> 0:
                                {{ \Illuminate\Support\Carbon::parse($lot->dayZeroDate)->format('M j, Y') }}
                            </span>
                        @endif
                        @if($lot->transplantDate)
                            <span class="badge ms-1 transplant-badge" style="background:#0ca678;color:#fff;font-size:10px;font-weight:500;" title="DAT 0 (transplant) anchor">
                                <i class="bx bx-transfer-alt"></i>
                                DAT 0: {{ \Illuminate\Support\Carbon::parse($lot->transplantDate)->format('M j, Y') }}
                            </span>
                        @endif
                    </td>
                    <td class="text-dark"><span data-field="lotSize">{{ rtrim(rtrim($lot->lotSize, '0'), '.') }}</span> <small class="text-secondary" data-field="lotSizeUnit">{{ $lot->lotSizeUnit }}</small></td>
                    <td>
                        @if(!empty($lot->variety))
                            <span class="badge bg-success-subtle text-success" data-field="variety" style="font-weight:500;font-size:11px;">
                                <i class="bx bx-leaf me-1"></i>{{ $lot->variety }}
                            </span>
                        @else
                            <small class="text-secondary" data-field="variety">—</small>
                        @endif
                    </td>
                    <td><small class="text-secondary" data-field="notes">{{ $lot->notes }}</small></td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary edit-lot-btn"
                                data-id="{{ $lot->id }}"
                                data-name="{{ $lot->lotName }}"
                                data-size="{{ $lot->lotSize }}"
                                data-unit="{{ $lot->lotSizeUnit }}"
                                data-variety="{{ $lot->variety }}"
                                data-day-zero-date="{{ $lot->dayZeroDate ? \Illuminate\Support\Carbon::parse($lot->dayZeroDate)->format('Y-m-d') : '' }}"
                                data-transplant-date="{{ $lot->transplantDate ? \Illuminate\Support\Carbon::parse($lot->transplantDate)->format('Y-m-d') : '' }}"
                                data-notes="{{ $lot->notes }}"><i class="bx bx-edit-alt"></i></button>
                        <button class="btn btn-sm btn-outline-danger delete-lot-btn" data-id="{{ $lot->id }}" data-name="{{ $lot->lotName }}"><i class="bx bx-trash"></i></button>
                    </td>
                </tr>
            @empty
                <tr id="lotsEmpty"><td colspan="5" class="text-center text-secondary py-4"><i class="bx bx-map-pin" style="font-size:2rem;"></i><br>No lots yet. Add at least one before generating the calendar.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Lot modal --}}
<div class="modal fade" id="lotModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-dark"><i class="bx bx-map-pin me-2"></i><span id="lotModalTitle">Add Lot</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="lotId">
                <div class="mb-3">
                    <label class="form-label text-dark">Lot Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="lotName" placeholder="e.g. Lot A">
                </div>
                <div class="row">
                    <div class="col-md-7 mb-3">
                        <label class="form-label text-dark">Size <span class="text-danger">*</span></label>
                        <input type="number" step="0.0001" min="0" class="form-control" id="lotSize" placeholder="0">
                    </div>
                    <div class="col-md-5 mb-3">
                        <label class="form-label text-dark">Unit</label>
                        <select class="form-select" id="lotSizeUnit">
                            <option value="hectare">Hectare</option>
                            <option value="sqm">Square Meter</option>
                            <option value="acre">Acre</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-dark">
                        <i class="bx bx-leaf me-1 text-success"></i>
                        Variety
                        <span class="badge bg-light text-secondary ms-1" style="font-weight:500;">Optional</span>
                    </label>
                    <input type="text" class="form-control" id="lotVariety" maxlength="255" placeholder="e.g. IR64, NSIC Rc222, hybrid mestiso">
                    <small class="text-secondary">Crop variety planted in this lot. Appears in the Worker Presentation, Export Schedule, and Card Viewer.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label text-dark">
                        <i class="bx bx-target-lock me-1 text-info"></i>
                        <span class="day-type-label">{{ $schedule->dayType }}</span> Day 0 Date
                        <span class="badge bg-light text-secondary ms-1" style="font-weight:500;">Optional</span>
                    </label>
                    <div class="input-group">
                        <input type="date" class="form-control" id="lotDayZeroDate">
                        <button type="button" class="btn btn-outline-secondary" id="lotDayZeroDateClear" title="Clear">
                            <i class="bx bx-x"></i>
                        </button>
                    </div>
                    <small class="text-secondary">
                        Anchor date for this lot's <span class="day-type-label">{{ $schedule->dayType }}</span> Day 0
                        (sowing / seeding day).
                        Once set, every activity for this lot shows its day number
                        (e.g. <span class="day-type-label">{{ $schedule->dayType }}</span>+5).
                    </small>
                </div>
                <div class="mb-3">
                    <label class="form-label text-dark">
                        <i class="bx bx-transfer-alt me-1 text-success"></i>
                        DAT 0 Date (Transplant)
                        <span class="badge bg-light text-secondary ms-1" style="font-weight:500;">Optional</span>
                    </label>
                    <div class="input-group">
                        <input type="date" class="form-control" id="lotTransplantDate">
                        <button type="button" class="btn btn-outline-secondary" id="lotTransplantDateClear" title="Clear">
                            <i class="bx bx-x"></i>
                        </button>
                    </div>
                    <small class="text-secondary">
                        For transplanted rice — the transplanting day. From this date on, activities
                        for this lot are counted in <strong>DAT</strong> (e.g. DAT+14) instead of
                        <span class="day-type-label">{{ $schedule->dayType }}</span>.
                        An activity marked as the transplant overrides this (earliest wins).
                    </small>
                </div>
                <div class="mb-3">
                    <label class="form-label text-dark">Notes</label>
                    <textarea class="form-control" id="lotNotes" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveLotBtn"><i class="bx bx-save me-1"></i>Save Lot</button>
            </div>
        </div>
    </div>
</div>
