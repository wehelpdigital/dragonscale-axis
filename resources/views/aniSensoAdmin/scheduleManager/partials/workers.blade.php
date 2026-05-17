<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h5 class="text-dark mb-1">Worker Details</h5>
        <small class="text-secondary">Define your workers, their per-half-day cost, and priority. Lower priority number = first choice when assigning during calendar generation.</small>
    </div>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#workerModal" id="addWorkerBtn">
        <i class="bx bx-plus me-1"></i> Add Worker
    </button>
</div>

<div class="table-responsive">
    <table class="table table-hover align-middle mb-0" id="workersTable">
        <thead class="table-light">
            <tr>
                <th>Priority</th>
                <th>Worker Name</th>
                <th>Cost/Half Day</th>
                <th>Off Rules</th>
                <th>Notes</th>
                <th class="text-end" style="width: 220px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($schedule->workers as $w)
                <tr data-id="{{ $w->id }}">
                    <td><span class="badge bg-primary text-white">#{{ $w->priority }}</span></td>
                    <td class="text-dark"><strong>{{ $w->workerName }}</strong></td>
                    <td class="text-dark">₱ {{ number_format($w->costPerHalfDay, 2) }}</td>
                    <td>
                        @if($w->offDays->count() || $w->offDates->count())
                            <small class="text-dark">
                                @if($w->offDays->count())
                                    @php $dayNames = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat']; @endphp
                                    <span class="badge bg-light text-dark">Off: {{ collect($w->offDays)->map(fn($d) => $dayNames[$d->dayOfWeek])->implode(', ') }}</span>
                                @endif
                                @if($w->offDates->count())
                                    <span class="badge bg-light text-dark">{{ $w->offDates->count() }} off date(s)</span>
                                @endif
                            </small>
                        @else
                            <small class="text-secondary">—</small>
                        @endif
                    </td>
                    <td><small class="text-secondary">{{ $w->notes }}</small></td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-info worker-rules-btn" data-id="{{ $w->id }}" data-name="{{ $w->workerName }}" title="Edit availability rules">
                            <i class="bx bx-calendar-x"></i> Rules
                        </button>
                        <button class="btn btn-sm btn-outline-primary edit-worker-btn"
                                data-id="{{ $w->id }}"
                                data-name="{{ $w->workerName }}"
                                data-cost="{{ $w->costPerHalfDay }}"
                                data-priority="{{ $w->priority }}"
                                data-notes="{{ $w->notes }}"><i class="bx bx-edit-alt"></i></button>
                        <button class="btn btn-sm btn-outline-danger delete-worker-btn" data-id="{{ $w->id }}" data-name="{{ $w->workerName }}"><i class="bx bx-trash"></i></button>
                    </td>
                </tr>
            @empty
                <tr id="workersEmpty"><td colspan="6" class="text-center text-secondary py-4"><i class="bx bx-user-x" style="font-size:2rem;"></i><br>No workers yet. Add at least one worker.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Worker modal --}}
<div class="modal fade" id="workerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-dark"><i class="bx bx-user me-2"></i><span id="workerModalTitle">Add Worker</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="workerId">
                <div class="mb-3">
                    <label class="form-label text-dark">Worker Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="workerName">
                </div>
                <div class="row">
                    <div class="col-md-7 mb-3">
                        <label class="form-label text-dark">Cost Per Half Day (PHP) <span class="text-danger">*</span></label>
                        <input type="number" min="0" step="0.01" class="form-control" id="workerCost" placeholder="0.00">
                    </div>
                    <div class="col-md-5 mb-3">
                        <label class="form-label text-dark">Priority <span class="text-danger">*</span></label>
                        <input type="number" min="1" step="1" class="form-control" id="workerPriority" value="1">
                        <small class="text-secondary">1 = highest. Used when picking workers for activities.</small>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-dark">Notes</label>
                    <textarea class="form-control" id="workerNotes" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveWorkerBtn"><i class="bx bx-save me-1"></i>Save Worker</button>
            </div>
        </div>
    </div>
</div>

{{-- Worker rules modal --}}
<div class="modal fade" id="workerRulesModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-dark"><i class="bx bx-calendar-x me-2"></i>Availability Rules — <span id="rulesWorkerName" class="text-primary"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="rulesWorkerId">
                <h6 class="text-dark mb-2">Days of the week worker is NOT available</h6>
                <small class="text-secondary d-block mb-2">Tap a day to toggle.</small>
                <div class="mb-3" id="ruleDayPills">
                    @php $dayNames = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat']; @endphp
                    @foreach($dayNames as $i => $d)
                        <span class="day-pill" data-day="{{ $i }}">{{ $d }}</span>
                    @endforeach
                </div>
                <hr>
                <h6 class="text-dark mb-2">Specific dates worker is NOT available</h6>
                <div class="row g-2 align-items-end mb-2">
                    <div class="col-md-6">
                        <label class="form-label text-dark">Add a date</label>
                        <input type="date" class="form-control" id="newOffDate">
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-outline-primary w-100" id="addOffDateBtn"><i class="bx bx-plus"></i> Add</button>
                    </div>
                </div>
                <div id="offDatesList" class="d-flex flex-wrap gap-2"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveWorkerRulesBtn"><i class="bx bx-save me-1"></i>Save Rules</button>
            </div>
        </div>
    </div>
</div>
