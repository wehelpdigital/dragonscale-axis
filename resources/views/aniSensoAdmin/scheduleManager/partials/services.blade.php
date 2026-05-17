<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h5 class="text-dark mb-1">Services</h5>
        <small class="text-secondary">External services like drying, tractor rental, hauling — each with a flat cost.</small>
    </div>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#serviceModal" id="addServiceBtn">
        <i class="bx bx-plus me-1"></i> Add Service
    </button>
</div>

<div class="table-responsive">
    <table class="table table-hover align-middle mb-0" id="servicesTable">
        <thead class="table-light">
            <tr>
                <th>Name</th>
                <th>Cost</th>
                <th>Description</th>
                <th class="text-end" style="width: 160px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($schedule->services as $s)
                <tr data-id="{{ $s->id }}">
                    <td class="text-dark"><strong>{{ $s->serviceName }}</strong></td>
                    <td class="text-dark">₱ {{ number_format($s->serviceCost, 2) }}</td>
                    <td><small class="text-secondary">{{ \Illuminate\Support\Str::limit($s->description, 80) }}</small></td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary edit-service-btn"
                                data-id="{{ $s->id }}"
                                data-name="{{ $s->serviceName }}"
                                data-description="{{ $s->description }}"
                                data-cost="{{ $s->serviceCost }}"><i class="bx bx-edit-alt"></i></button>
                        <button class="btn btn-sm btn-outline-danger delete-service-btn" data-id="{{ $s->id }}" data-name="{{ $s->serviceName }}"><i class="bx bx-trash"></i></button>
                    </td>
                </tr>
            @empty
                <tr id="servicesEmpty"><td colspan="4" class="text-center text-secondary py-4"><i class="bx bx-wrench" style="font-size:2rem;"></i><br>No services yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Service modal --}}
<div class="modal fade" id="serviceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-dark"><i class="bx bx-wrench me-2"></i><span id="serviceModalTitle">Add Service</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="serviceId">
                <div class="mb-3">
                    <label class="form-label text-dark">Service Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="serviceName">
                </div>
                <div class="mb-3">
                    <label class="form-label text-dark">Description</label>
                    <textarea class="form-control" id="serviceDescription" rows="2"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label text-dark">Cost (PHP) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">₱</span>
                        <input type="number" min="0" step="0.01" class="form-control" id="serviceCost">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveServiceBtn"><i class="bx bx-save me-1"></i>Save Service</button>
            </div>
        </div>
    </div>
</div>
