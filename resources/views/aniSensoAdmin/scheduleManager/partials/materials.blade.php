<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h5 class="text-dark mb-1">Materials</h5>
        <small class="text-secondary">Inputs you'll consume (granular, foliar, pesticide, herbicide, molluscicide, fungicide, fertilizer, seed, etc.). Used by activities.</small>
    </div>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#materialModal" id="addMaterialBtn">
        <i class="bx bx-plus me-1"></i> Add Material
    </button>
</div>

<div class="row g-2 mb-3">
    <div class="col-md-6 col-lg-5">
        <div class="input-group input-group-sm">
            <span class="input-group-text bg-light"><i class="bx bx-search"></i></span>
            <input type="text" class="form-control" id="materialsSearch"
                   placeholder="Search by name, type, or description to check for duplicates..."
                   autocomplete="off">
            <button type="button" class="btn btn-outline-secondary" id="materialsSearchClear" title="Clear">
                <i class="bx bx-x"></i>
            </button>
        </div>
        <small class="text-secondary" id="materialsSearchCount"></small>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-hover align-middle mb-0" id="materialsTable">
        <thead class="table-light">
            <tr>
                <th>Name</th>
                <th>Type</th>
                <th>Unit</th>
                <th>Price</th>
                <th>Description</th>
                <th class="text-end" style="width: 160px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($schedule->materials as $m)
                <tr data-id="{{ $m->id }}"
                    data-search="{{ strtolower($m->materialName . ' ' . $m->materialType . ' ' . $m->unitOfMeasure . ' ' . $m->description) }}">
                    <td class="text-dark"><strong>{{ $m->materialName }}</strong></td>
                    <td><span class="badge bg-info text-white">{{ ucfirst($m->materialType) }}</span></td>
                    <td class="text-dark">{{ $m->unitOfMeasure }}</td>
                    <td class="text-dark">₱ {{ number_format($m->priceAmount, 2) }} <small class="text-secondary">per {{ rtrim(rtrim($m->priceQuantity, '0'), '.') }} {{ $m->unitOfMeasure }}</small></td>
                    <td><small class="text-secondary">{{ \Illuminate\Support\Str::limit($m->description, 60) }}</small></td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary edit-material-btn"
                                data-id="{{ $m->id }}"
                                data-name="{{ $m->materialName }}"
                                data-description="{{ $m->description }}"
                                data-type="{{ $m->materialType }}"
                                data-unit="{{ $m->unitOfMeasure }}"
                                data-amount="{{ $m->priceAmount }}"
                                data-quantity="{{ $m->priceQuantity }}"><i class="bx bx-edit-alt"></i></button>
                        <button class="btn btn-sm btn-outline-danger delete-material-btn" data-id="{{ $m->id }}" data-name="{{ $m->materialName }}"><i class="bx bx-trash"></i></button>
                    </td>
                </tr>
            @empty
                <tr id="materialsEmpty"><td colspan="6" class="text-center text-secondary py-4"><i class="bx bx-package" style="font-size:2rem;"></i><br>No materials yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Material modal --}}
<div class="modal fade" id="materialModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-dark"><i class="bx bx-package me-2"></i><span id="materialModalTitle">Add Material</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="materialId">
                <div class="mb-3">
                    <label class="form-label text-dark">Material Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="materialName">
                </div>
                <div class="mb-3">
                    <label class="form-label text-dark">Description</label>
                    <textarea class="form-control" id="materialDescription" rows="2"></textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-dark">Material Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="materialType">
                            <option value="granular">Granular</option>
                            <option value="foliar">Foliar</option>
                            <option value="pesticide">Pesticide</option>
                            <option value="herbicide">Herbicide</option>
                            <option value="molluscicide">Molluscicide</option>
                            <option value="fungicide">Fungicide</option>
                            <option value="fertilizer">Fertilizer</option>
                            <option value="seed">Seed</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-dark">Unit of Measure <span class="text-danger">*</span></label>
                        <select class="form-select" id="materialUnit">
                            <option value="kg">kg</option>
                            <option value="g">g</option>
                            <option value="l">L</option>
                            <option value="ml">ml</option>
                            <option value="bottle">bottle</option>
                            <option value="sachet">sachet</option>
                            <option value="pack">pack</option>
                            <option value="piece">piece</option>
                        </select>
                    </div>
                </div>
                <label class="form-label text-dark">Price <span class="text-danger">*</span></label>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" min="0" step="0.01" class="form-control" id="materialAmount" placeholder="Amount">
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="input-group">
                            <span class="input-group-text">per</span>
                            <input type="number" min="0.0001" step="0.0001" class="form-control" id="materialQuantity" placeholder="Quantity">
                            <span class="input-group-text" id="materialUnitDisplay">kg</span>
                        </div>
                    </div>
                </div>
                <small class="text-secondary">e.g. ₱3000 per 50 kg — set Amount = 3000, Quantity = 50, Unit = kg.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveMaterialBtn"><i class="bx bx-save me-1"></i>Save Material</button>
            </div>
        </div>
    </div>
</div>
