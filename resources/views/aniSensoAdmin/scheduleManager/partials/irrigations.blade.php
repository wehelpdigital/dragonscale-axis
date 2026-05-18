<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h5 class="text-dark mb-1">Irrigation Schedules</h5>
        <small class="text-secondary">Define irrigation windows by DAS/DAP/DAT day ranges (e.g. Irrigation 1 from DAS 0 to 5).</small>
    </div>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#irrigationModal" id="addIrrigationBtn">
        <i class="bx bx-plus me-1"></i> Add Irrigation
    </button>
</div>

@php $taskTypeCatalog = \App\Models\AsScheduleIrrigation::TASK_TYPES; @endphp

<div class="table-responsive">
    <table class="table table-hover align-middle mb-0" id="irrigationsTable">
        <thead class="table-light">
            <tr>
                <th>Title</th>
                <th>Task Type</th>
                <th>Day Range</th>
                <th>Worker</th>
                <th>Description</th>
                <th class="text-end" style="width: 160px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($schedule->irrigations as $i)
                @php $meta = \App\Models\AsScheduleIrrigation::taskTypeMeta($i->taskType); @endphp
                <tr data-id="{{ $i->id }}">
                    <td class="text-dark"><strong>{{ $i->irrigationTitle }}</strong></td>
                    <td>
                        <span class="badge text-white" style="background: {{ $meta['color'] }}; font-weight: 600;">
                            {{ $meta['icon'] }} {{ $meta['label'] }}
                        </span>
                    </td>
                    <td class="text-dark"><span class="badge bg-info text-white"><span class="day-type-label">{{ $schedule->dayType }}</span> {{ $i->startDay }} — {{ $i->endDay }}</span></td>
                    <td class="text-dark">{{ optional($i->assignedWorker)->workerName ?: '—' }}</td>
                    <td><small class="text-secondary">{{ \Illuminate\Support\Str::limit($i->description, 60) }}</small></td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary edit-irrigation-btn"
                                data-id="{{ $i->id }}"
                                data-title="{{ $i->irrigationTitle }}"
                                data-description="{{ $i->description }}"
                                data-start-day="{{ $i->startDay }}"
                                data-end-day="{{ $i->endDay }}"
                                data-task-type="{{ $i->taskType ?: 'irrigate' }}"
                                data-worker-id="{{ $i->assignedWorkerId }}"><i class="bx bx-edit-alt"></i></button>
                        <button class="btn btn-sm btn-outline-danger delete-irrigation-btn" data-id="{{ $i->id }}" data-name="{{ $i->irrigationTitle }}"><i class="bx bx-trash"></i></button>
                    </td>
                </tr>
            @empty
                <tr id="irrigationsEmpty"><td colspan="6" class="text-center text-secondary py-4"><i class="bx bx-water" style="font-size:2rem;"></i><br>No irrigation schedules yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Irrigation modal --}}
<div class="modal fade" id="irrigationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-dark"><i class="bx bx-water me-2"></i><span id="irrigationModalTitle">Add Irrigation</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="irrigationId">
                <div class="mb-3">
                    <label class="form-label text-dark">Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="irrigationTitle" placeholder="e.g. Irrigation 1">
                </div>
                <div class="mb-3">
                    <label class="form-label text-dark">Task Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="irrigationTaskType">
                        @foreach(\App\Models\AsScheduleIrrigation::TASK_TYPES as $slug => $label)
                            @php $tMeta = \App\Models\AsScheduleIrrigation::taskTypeMeta($slug); @endphp
                            <option value="{{ $slug }}" {{ $slug === 'irrigate' ? 'selected' : '' }}>
                                {{ $tMeta['icon'] }} {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-secondary">
                        <strong>Irrigate</strong> = fill water · <strong>Maintain</strong> = hold level ·
                        <strong>Overflow</strong> = flush/excess · <strong>Drain</strong> = stop / let water out
                    </small>
                </div>
                <div class="mb-3">
                    <label class="form-label text-dark">Description</label>
                    <textarea class="form-control" id="irrigationDescription" rows="2"></textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-dark">Start Day (<span class="day-type-label">{{ $schedule->dayType }}</span>) <span class="text-danger">*</span></label>
                        <input type="number" step="1" class="form-control" id="irrigationStartDay" value="0">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-dark">End Day (<span class="day-type-label">{{ $schedule->dayType }}</span>) <span class="text-danger">*</span></label>
                        <input type="number" step="1" class="form-control" id="irrigationEndDay" value="5">
                    </div>
                </div>
                <small class="text-secondary d-block mb-3">Day type is set once in <strong>Settings</strong>.</small>
                <div class="mb-3">
                    <label class="form-label text-dark">Assigned Worker</label>
                    <select class="form-select" id="irrigationWorker">
                        <option value="">(none)</option>
                        @foreach($schedule->workers as $w)
                            <option value="{{ $w->id }}">{{ $w->workerName }} (priority #{{ $w->priority }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveIrrigationBtn"><i class="bx bx-save me-1"></i>Save Irrigation</button>
            </div>
        </div>
    </div>
</div>
