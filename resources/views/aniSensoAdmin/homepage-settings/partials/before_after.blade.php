{{-- Before & After Section Settings --}}
<div class="accordion settings-accordion" id="beforeAfterAccordion">
    {{-- Settings --}}
    <div class="accordion-item border-0 mb-1">
        <h2 class="accordion-header">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#beforeAfterSettings" aria-expanded="true">
                <i class="bx bx-cog accordion-icon"></i>Section Settings
            </button>
        </h2>
        <div id="beforeAfterSettings" class="accordion-collapse collapse show" data-bs-parent="#beforeAfterAccordion">
            <div class="accordion-body">
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">Section Title</label>
                        <input type="text" class="form-control section-setting-input" data-section="before_after" data-setting="title"
                               value="{{ $section->getSetting('title', 'Before & After') }}">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">Subtitle</label>
                        <input type="text" class="form-control section-setting-input" data-section="before_after" data-setting="subtitle"
                               value="{{ $section->getSetting('subtitle', 'See the Transformation') }}">
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label text-dark">Description</label>
                    <textarea class="form-control section-setting-input" data-section="before_after" data-setting="description" rows="2">{{ $section->getSetting('description', '') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- Comparison Cards --}}
    <div class="accordion-item border-0 mb-1">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#beforeAfterCards">
                <i class="bx bx-images accordion-icon"></i>Comparison Cards
                <span class="badge bg-primary ms-2">{{ $section->items->count() }}</span>
            </button>
        </h2>
        <div id="beforeAfterCards" class="accordion-collapse collapse" data-bs-parent="#beforeAfterAccordion">
            <div class="accordion-body">
                <p class="text-secondary small mb-3"><i class="bx bx-info-circle me-1"></i>Each card has Before (Image 1) and After (Image 2) images.</p>
                <div class="d-flex justify-content-end mb-3">
                    <button type="button" class="btn btn-sm btn-soft-primary" onclick="openAddItemModal('before_after', 'comparison')">
                        <i class="bx bx-plus me-1"></i> Add Comparison
                    </button>
                </div>
                <div class="row sortable-items">
                    @foreach($section->items as $item)
                        <div class="col-md-6 col-lg-3" data-item-id="{{ $item->id }}">
                            <div class="item-card">
                                <div class="d-flex justify-content-between mb-2">
                                    <div class="drag-handle"><i class="bx bx-menu"></i></div>
                                    <div>
                                        @if(!$item->isActive)<span class="badge bg-secondary me-1">Off</span>@endif
                                        <button type="button" class="btn btn-sm btn-soft-primary me-1" onclick='openEditItemModal(@json($item), true)'><i class="bx bx-edit"></i></button>
                                        <button type="button" class="btn btn-sm btn-soft-danger" onclick="openDeleteItemModal({{ $item->id }}, '{{ addslashes($item->title) }}')"><i class="bx bx-trash"></i></button>
                                    </div>
                                </div>
                                <div class="row g-1 mb-2">
                                    <div class="col-6">
                                        @if($item->image)<img src="{{ $item->image }}" alt="Before" class="img-fluid rounded" style="height: 60px; width: 100%; object-fit: cover;">
                                        @else<div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 60px;"><i class="bx bx-image text-secondary"></i></div>@endif
                                        <span class="badge bg-danger" style="font-size: 9px;">Before</span>
                                    </div>
                                    <div class="col-6">
                                        @if($item->image2)<img src="{{ $item->image2 }}" alt="After" class="img-fluid rounded" style="height: 60px; width: 100%; object-fit: cover;">
                                        @else<div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 60px;"><i class="bx bx-image text-secondary"></i></div>@endif
                                        <span class="badge bg-success" style="font-size: 9px;">After</span>
                                    </div>
                                </div>
                                <h6 class="text-dark mb-0 text-center small">{{ $item->title }}</h6>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($section->items->isEmpty())
                    <div class="text-center py-3"><i class="bx bx-images text-secondary" style="font-size: 2rem;"></i><p class="text-dark mt-2 mb-0">No comparison cards yet</p></div>
                @endif
            </div>
        </div>
    </div>

    {{-- SEO --}}
    <div class="accordion-item border-0 mb-1">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#beforeAfterSeo">
                <i class="bx bx-search-alt accordion-icon"></i>Section SEO
            </button>
        </h2>
        <div id="beforeAfterSeo" class="accordion-collapse collapse" data-bs-parent="#beforeAfterAccordion">
            <div class="accordion-body">
                @include('aniSensoAdmin.homepage-settings.partials._seo-section', ['section' => $section, 'sectionKey' => 'before_after'])
            </div>
        </div>
    </div>
</div>
