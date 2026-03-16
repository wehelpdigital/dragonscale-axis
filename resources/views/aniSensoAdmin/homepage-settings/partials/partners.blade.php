{{-- Partners Section Settings --}}
<div class="accordion settings-accordion" id="partnersAccordion">
    {{-- Settings --}}
    <div class="accordion-item border-0 mb-1">
        <h2 class="accordion-header">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#partnersSettings" aria-expanded="true">
                <i class="bx bx-cog accordion-icon"></i>Section Settings
            </button>
        </h2>
        <div id="partnersSettings" class="accordion-collapse collapse show" data-bs-parent="#partnersAccordion">
            <div class="accordion-body">
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">Section Title</label>
                        <input type="text" class="form-control section-setting-input" data-section="partners" data-setting="title"
                               value="{{ $section->getSetting('title', 'Our Partners') }}">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">Display Style</label>
                        <select class="form-select section-setting-input" data-section="partners" data-setting="displayStyle">
                            <option value="grid" {{ $section->getSetting('displayStyle') == 'grid' ? 'selected' : '' }}>Grid Layout</option>
                            <option value="carousel" {{ $section->getSetting('displayStyle') == 'carousel' ? 'selected' : '' }}>Carousel</option>
                            <option value="marquee" {{ $section->getSetting('displayStyle') == 'marquee' ? 'selected' : '' }}>Marquee</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Partner Logos --}}
    <div class="accordion-item border-0 mb-1">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#partnersLogos">
                <i class="bx bx-building accordion-icon"></i>Partner Logos
                <span class="badge bg-primary ms-2">{{ $section->items->count() }}</span>
            </button>
        </h2>
        <div id="partnersLogos" class="accordion-collapse collapse" data-bs-parent="#partnersAccordion">
            <div class="accordion-body">
                <div class="d-flex justify-content-end mb-3">
                    <button type="button" class="btn btn-sm btn-soft-primary" onclick="openAddItemModal('partners', 'logo')">
                        <i class="bx bx-plus me-1"></i> Add Partner
                    </button>
                </div>
                <div class="row sortable-items">
                    @foreach($section->items as $item)
                        <div class="col-md-4 col-lg-2" data-item-id="{{ $item->id }}">
                            <div class="item-card text-center p-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <div class="drag-handle"><i class="bx bx-menu" style="font-size: 14px;"></i></div>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-soft-primary btn-xs me-1" onclick='openEditItemModal(@json($item))'><i class="bx bx-edit" style="font-size: 12px;"></i></button>
                                        <button type="button" class="btn btn-sm btn-soft-danger btn-xs" onclick="openDeleteItemModal({{ $item->id }}, '{{ addslashes($item->title) }}')"><i class="bx bx-trash" style="font-size: 12px;"></i></button>
                                    </div>
                                </div>
                                @if($item->image)
                                    <img src="{{ $item->image }}" alt="{{ $item->title }}" class="img-fluid mb-2" style="max-height: 50px;">
                                @else
                                    <div class="bg-light rounded p-2 mb-2"><i class="bx bx-image text-secondary" style="font-size: 1.5rem;"></i></div>
                                @endif
                                <small class="text-dark d-block">{{ $item->title }}</small>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($section->items->isEmpty())
                    <div class="text-center py-3"><i class="bx bx-building text-secondary" style="font-size: 2rem;"></i><p class="text-dark mt-2 mb-0">No partners yet</p></div>
                @endif
            </div>
        </div>
    </div>

    {{-- SEO --}}
    <div class="accordion-item border-0 mb-1">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#partnersSeo">
                <i class="bx bx-search-alt accordion-icon"></i>Section SEO
            </button>
        </h2>
        <div id="partnersSeo" class="accordion-collapse collapse" data-bs-parent="#partnersAccordion">
            <div class="accordion-body">
                @include('aniSensoAdmin.homepage-settings.partials._seo-section', ['section' => $section, 'sectionKey' => 'partners'])
            </div>
        </div>
    </div>
</div>
