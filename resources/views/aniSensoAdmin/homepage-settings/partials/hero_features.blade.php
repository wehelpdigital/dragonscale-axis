{{-- Hero Features Section Settings --}}
<div class="accordion settings-accordion" id="heroFeaturesAccordion">
    {{-- Settings --}}
    <div class="accordion-item border-0 mb-1">
        <h2 class="accordion-header">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#heroFeaturesSettings" aria-expanded="true">
                <i class="bx bx-cog accordion-icon"></i>Section Settings
            </button>
        </h2>
        <div id="heroFeaturesSettings" class="accordion-collapse collapse show" data-bs-parent="#heroFeaturesAccordion">
            <div class="accordion-body">
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">Section Title</label>
                        <input type="text" class="form-control section-setting-input" data-section="hero_features" data-setting="sectionTitle"
                               value="{{ $section->getSetting('sectionTitle', 'Why Choose Us') }}">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">Layout Style</label>
                        <select class="form-select section-setting-input" data-section="hero_features" data-setting="layoutStyle">
                            <option value="grid" {{ $section->getSetting('layoutStyle') == 'grid' ? 'selected' : '' }}>Grid (3 columns)</option>
                            <option value="horizontal" {{ $section->getSetting('layoutStyle') == 'horizontal' ? 'selected' : '' }}>Horizontal Bar</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Feature Items - Inline Edit --}}
    <div class="accordion-item border-0 mb-1">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#heroFeaturesItems">
                <i class="bx bx-star accordion-icon"></i>Features
                <span class="badge bg-primary ms-2" id="featureCount">{{ $section->items->count() }}</span>
            </button>
        </h2>
        <div id="heroFeaturesItems" class="accordion-collapse collapse" data-bs-parent="#heroFeaturesAccordion">
            <div class="accordion-body">
                <div class="d-flex justify-content-end mb-3">
                    <button type="button" class="btn btn-sm btn-soft-primary" id="addFeatureBtn">
                        <i class="bx bx-plus me-1"></i> Add Feature
                    </button>
                </div>

                <div class="features-list sortable-items" id="featuresList">
                    @foreach($section->items as $item)
                    <div class="feature-item card mb-2" data-item-id="{{ $item->id }}">
                        <div class="card-body py-2 px-3">
                            <div class="row align-items-center">
                                {{-- Drag Handle --}}
                                <div class="col-auto">
                                    <div class="drag-handle text-secondary" style="cursor: grab;"><i class="bx bx-menu" style="font-size: 20px;"></i></div>
                                </div>

                                {{-- Icon Picker --}}
                                <div class="col-auto">
                                    <div class="icon-picker-box" data-item-id="{{ $item->id }}" title="Click to change icon">
                                        <i class="bx {{ $item->icon ?: 'bx-star' }}" style="font-size: 28px; color: #34c38f;"></i>
                                    </div>
                                </div>

                                {{-- Title & Description --}}
                                <div class="col">
                                    <input type="text" class="form-control form-control-sm feature-title mb-1"
                                           placeholder="Feature Title" value="{{ $item->title }}"
                                           data-item-id="{{ $item->id }}" data-field="title">
                                    <input type="text" class="form-control form-control-sm feature-desc text-secondary"
                                           placeholder="Short description" value="{{ $item->description }}"
                                           data-item-id="{{ $item->id }}" data-field="description">
                                </div>

                                {{-- Delete --}}
                                <div class="col-auto">
                                    <button type="button" class="btn btn-sm btn-soft-danger delete-feature-btn" data-item-id="{{ $item->id }}" data-item-title="{{ $item->title }}">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                @if($section->items->isEmpty())
                <div class="text-center py-3 empty-features-msg">
                    <i class="bx bx-star text-secondary" style="font-size: 2rem;"></i>
                    <p class="text-dark mt-2 mb-0">No features yet</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- SEO --}}
    <div class="accordion-item border-0 mb-1">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#heroFeaturesSeo">
                <i class="bx bx-search-alt accordion-icon"></i>Section SEO
            </button>
        </h2>
        <div id="heroFeaturesSeo" class="accordion-collapse collapse" data-bs-parent="#heroFeaturesAccordion">
            <div class="accordion-body">
                @include('aniSensoAdmin.homepage-settings.partials._seo-section', ['section' => $section, 'sectionKey' => 'hero_features'])
            </div>
        </div>
    </div>
</div>

{{-- Icon Picker Modal --}}
<div class="modal fade" id="iconPickerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bx bx-grid-alt me-2"></i>Select Icon</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" class="form-control mb-3" id="iconSearchInput" placeholder="Search icons...">
                <div class="icon-grid" id="iconGrid" style="display: grid; grid-template-columns: repeat(8, 1fr); gap: 8px; max-height: 350px; overflow-y: auto;">
                    {{-- Icons will be populated by JS --}}
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.icon-picker-box {
    width: 50px;
    height: 50px;
    border: 2px dashed #ced4da;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    background: #f8f9fa;
}
.icon-picker-box:hover {
    border-color: #556ee6;
    background: #eef2ff;
}
.feature-title {
    font-weight: 500;
    border: 1px solid transparent;
    background: transparent;
}
.feature-title:focus {
    background: #fff;
    border-color: #ced4da;
}
.feature-desc {
    font-size: 12px;
    border: 1px solid transparent;
    background: transparent;
}
.feature-desc:focus {
    background: #fff;
    border-color: #ced4da;
}
.icon-grid .icon-option {
    width: 45px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.15s;
}
.icon-grid .icon-option:hover {
    background: #556ee6;
    color: #fff;
    border-color: #556ee6;
}
.icon-grid .icon-option i {
    font-size: 22px;
}
</style>

<script>
(function waitForJQuery() {
    if (typeof $ === 'undefined' || typeof jQuery === 'undefined') {
        setTimeout(waitForJQuery, 50);
        return;
    }
    initHeroFeaturesScripts();
})();

function initHeroFeaturesScripts() {
    // Common icons for features
    const featureIcons = [
        'bx-star', 'bx-check-circle', 'bx-shield-quarter', 'bx-trophy', 'bx-award', 'bx-medal',
        'bx-badge-check', 'bx-certification', 'bx-rocket', 'bx-target-lock', 'bx-bulb', 'bx-brain',
        'bx-bolt', 'bx-zap', 'bx-timer', 'bx-time-five', 'bx-trending-up', 'bx-line-chart',
        'bx-dollar-circle', 'bx-money', 'bx-wallet', 'bx-credit-card', 'bx-gift', 'bx-cart',
        'bx-support', 'bx-headphone', 'bx-phone-call', 'bx-chat', 'bx-conversation', 'bx-message-dots',
        'bx-user-check', 'bx-user-circle', 'bx-group', 'bx-world', 'bx-globe', 'bx-map',
        'bx-book-open', 'bx-book-reader', 'bx-library', 'bx-graduation', 'bx-school', 'bx-edit',
        'bx-cog', 'bx-wrench', 'bx-slider', 'bx-analyse', 'bx-data', 'bx-server',
        'bx-cloud', 'bx-lock', 'bx-lock-open', 'bx-key', 'bx-shield', 'bx-shield-alt',
        'bx-heart', 'bx-like', 'bx-happy', 'bx-smile', 'bx-sun', 'bx-moon',
        'bx-home', 'bx-building', 'bx-store', 'bx-package', 'bx-box', 'bx-cube',
        'bx-leaf', 'bx-tree', 'bx-water', 'bx-wind', 'bx-planet', 'bx-diamond'
    ];

    let currentIconItemId = null;
    const iconModal = new bootstrap.Modal(document.getElementById('iconPickerModal'));

    // Populate icon grid
    function populateIconGrid(filter = '') {
        const grid = document.getElementById('iconGrid');
        grid.innerHTML = '';
        featureIcons.filter(icon => icon.includes(filter.toLowerCase())).forEach(icon => {
            const div = document.createElement('div');
            div.className = 'icon-option';
            div.innerHTML = `<i class="bx ${icon}"></i>`;
            div.onclick = () => selectIcon(icon);
            grid.appendChild(div);
        });
    }

    // Icon search
    document.getElementById('iconSearchInput').addEventListener('input', function() {
        populateIconGrid(this.value);
    });

    // Open icon picker
    document.querySelectorAll('.icon-picker-box').forEach(box => {
        box.addEventListener('click', function() {
            currentIconItemId = this.dataset.itemId;
            populateIconGrid();
            iconModal.show();
        });
    });

    // Select icon
    function selectIcon(iconClass) {
        if (!currentIconItemId) return;

        // Update UI
        const box = document.querySelector(`.icon-picker-box[data-item-id="${currentIconItemId}"]`);
        if (box) {
            box.innerHTML = `<i class="bx ${iconClass}" style="font-size: 28px; color: #34c38f;"></i>`;
        }

        // Save to server
        saveFeatureField(currentIconItemId, 'icon', iconClass);
        iconModal.hide();
    }

    // Save feature field on blur
    document.querySelectorAll('.feature-title, .feature-desc').forEach(input => {
        input.addEventListener('blur', function() {
            const itemId = this.dataset.itemId;
            const field = this.dataset.field;
            const value = this.value;
            saveFeatureField(itemId, field, value);
        });
    });

    // Save feature field via AJAX
    function saveFeatureField(itemId, field, value) {
        $.ajax({
            url: '/anisenso-homepage-settings/items/' + itemId,
            type: 'PUT',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                [field]: value
            },
            success: function(response) {
                if (response.success) {
                    toastr.success('Feature updated', 'Saved!');
                }
            },
            error: function(xhr) {
                toastr.error('Failed to save', 'Error!');
            }
        });
    }

    // Add new feature
    document.getElementById('addFeatureBtn').addEventListener('click', function() {
        const sectionId = {{ $section->id }};

        $.ajax({
            url: '/anisenso-homepage-settings/section/hero_features/items',
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                itemType: 'feature',
                title: 'New Feature',
                description: 'Feature description',
                icon: 'bx-star',
                isActive: 1
            },
            success: function(response) {
                if (response.success && response.item) {
                    const item = response.item;
                    const html = `
                    <div class="feature-item card mb-2" data-item-id="${item.id}">
                        <div class="card-body py-2 px-3">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <div class="drag-handle text-secondary" style="cursor: grab;"><i class="bx bx-menu" style="font-size: 20px;"></i></div>
                                </div>
                                <div class="col-auto">
                                    <div class="icon-picker-box" data-item-id="${item.id}" title="Click to change icon">
                                        <i class="bx ${item.icon || 'bx-star'}" style="font-size: 28px; color: #34c38f;"></i>
                                    </div>
                                </div>
                                <div class="col">
                                    <input type="text" class="form-control form-control-sm feature-title mb-1"
                                           placeholder="Feature Title" value="${item.title}"
                                           data-item-id="${item.id}" data-field="title">
                                    <input type="text" class="form-control form-control-sm feature-desc text-secondary"
                                           placeholder="Short description" value="${item.description || ''}"
                                           data-item-id="${item.id}" data-field="description">
                                </div>
                                <div class="col-auto">
                                    <button type="button" class="btn btn-sm btn-soft-danger delete-feature-btn" data-item-id="${item.id}" data-item-title="${item.title}">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>`;

                    document.getElementById('featuresList').insertAdjacentHTML('beforeend', html);
                    document.querySelector('.empty-features-msg')?.remove();

                    // Update count
                    const countBadge = document.getElementById('featureCount');
                    countBadge.textContent = parseInt(countBadge.textContent) + 1;

                    // Rebind events
                    bindNewFeatureEvents(item.id);
                    toastr.success('Feature added', 'Success!');
                }
            },
            error: function(xhr) {
                toastr.error('Failed to add feature', 'Error!');
            }
        });
    });

    // Bind events for newly added feature
    function bindNewFeatureEvents(itemId) {
        const card = document.querySelector(`.feature-item[data-item-id="${itemId}"]`);
        if (!card) return;

        // Icon picker
        card.querySelector('.icon-picker-box').addEventListener('click', function() {
            currentIconItemId = this.dataset.itemId;
            populateIconGrid();
            iconModal.show();
        });

        // Field blur events
        card.querySelectorAll('.feature-title, .feature-desc').forEach(input => {
            input.addEventListener('blur', function() {
                saveFeatureField(this.dataset.itemId, this.dataset.field, this.value);
            });
        });

        // Delete button
        card.querySelector('.delete-feature-btn').addEventListener('click', function() {
            deleteFeature(this.dataset.itemId, this.dataset.itemTitle);
        });
    }

    // Delete feature
    document.querySelectorAll('.delete-feature-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            deleteFeature(this.dataset.itemId, this.dataset.itemTitle);
        });
    });

    function deleteFeature(itemId, itemTitle) {
        if (!confirm(`Delete "${itemTitle}"?`)) return;

        $.ajax({
            url: '/anisenso-homepage-settings/items/' + itemId,
            type: 'DELETE',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                if (response.success) {
                    document.querySelector(`.feature-item[data-item-id="${itemId}"]`)?.remove();

                    // Update count
                    const countBadge = document.getElementById('featureCount');
                    const newCount = Math.max(0, parseInt(countBadge.textContent) - 1);
                    countBadge.textContent = newCount;

                    if (newCount === 0) {
                        document.getElementById('featuresList').innerHTML = `
                            <div class="text-center py-3 empty-features-msg">
                                <i class="bx bx-star text-secondary" style="font-size: 2rem;"></i>
                                <p class="text-dark mt-2 mb-0">No features yet</p>
                            </div>`;
                    }

                    toastr.success('Feature deleted', 'Deleted!');
                }
            },
            error: function(xhr) {
                toastr.error('Failed to delete', 'Error!');
            }
        });
    }

    populateIconGrid();
} // end initHeroFeaturesScripts
</script>