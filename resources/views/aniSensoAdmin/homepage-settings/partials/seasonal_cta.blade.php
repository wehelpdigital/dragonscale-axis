{{-- Seasonal CTA Section Settings --}}
<div class="accordion settings-accordion" id="seasonalCtaAccordion">
    {{-- Background Media --}}
    <div class="accordion-item border-0 mb-1">
        <h2 class="accordion-header">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#seasonalCtaMedia" aria-expanded="true">
                <i class="bx bx-video accordion-icon"></i>Background Media
            </button>
        </h2>
        <div id="seasonalCtaMedia" class="accordion-collapse collapse show" data-bs-parent="#seasonalCtaAccordion">
            <div class="accordion-body">
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">Background Video URL</label>
                        <input type="text" class="form-control section-setting-input" data-section="seasonal_cta" data-setting="videoUrl"
                               value="{{ $section->getSetting('videoUrl', '') }}" placeholder="https://youtube.com/embed/...">
                        <small class="text-secondary">YouTube embed URL</small>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">Fallback Image</label>
                        <div class="section-image-upload image-upload-zone" data-section="seasonal_cta" data-setting="backgroundImage">
                            <input type="file" class="d-none section-image-input" accept="image/*">
                            <div class="upload-placeholder {{ $section->getSetting('backgroundImage') ? 'd-none' : '' }}">
                                <i class="bx bx-cloud-upload text-muted" style="font-size: 1.5rem;"></i>
                                <p class="text-secondary mb-0 mt-1 small">Click to upload</p>
                            </div>
                            <img src="{{ $section->getSetting('backgroundImage', '') }}" class="image-preview {{ $section->getSetting('backgroundImage') ? '' : 'd-none' }}" alt="Background">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Content --}}
    <div class="accordion-item border-0 mb-1">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#seasonalCtaContent">
                <i class="bx bx-text accordion-icon"></i>Content
            </button>
        </h2>
        <div id="seasonalCtaContent" class="accordion-collapse collapse" data-bs-parent="#seasonalCtaAccordion">
            <div class="accordion-body">
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">Title</label>
                        <input type="text" class="form-control section-setting-input" data-section="seasonal_cta" data-setting="title"
                               value="{{ $section->getSetting('title', 'Seasonal Offers') }}">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">Subtitle</label>
                        <input type="text" class="form-control section-setting-input" data-section="seasonal_cta" data-setting="subtitle"
                               value="{{ $section->getSetting('subtitle', 'Limited Time Deals') }}">
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label text-dark">Description</label>
                    <textarea class="form-control section-setting-input" data-section="seasonal_cta" data-setting="description" rows="2">{{ $section->getSetting('description', '') }}</textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">CTA Button Text</label>
                        <input type="text" class="form-control section-setting-input" data-section="seasonal_cta" data-setting="ctaText"
                               value="{{ $section->getSetting('ctaText', 'Explore Offers') }}">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">CTA Button URL</label>
                        <input type="text" class="form-control section-setting-input" data-section="seasonal_cta" data-setting="ctaUrl"
                               value="{{ $section->getSetting('ctaUrl', '/courses') }}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Carousel Slides --}}
    <div class="accordion-item border-0 mb-1">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#seasonalCtaSlides">
                <i class="bx bx-carousel accordion-icon"></i>Carousel Slides
                <span class="badge bg-primary ms-2">{{ $section->items->count() }}</span>
            </button>
        </h2>
        <div id="seasonalCtaSlides" class="accordion-collapse collapse" data-bs-parent="#seasonalCtaAccordion">
            <div class="accordion-body">
                <div class="d-flex justify-content-end mb-3">
                    <button type="button" class="btn btn-sm btn-soft-primary" onclick="openAddItemModal('seasonal_cta', 'slide')">
                        <i class="bx bx-plus me-1"></i> Add Slide
                    </button>
                </div>
                <div class="row sortable-items">
                    @foreach($section->items as $item)
                        <div class="col-md-4" data-item-id="{{ $item->id }}">
                            <div class="item-card">
                                <div class="d-flex justify-content-between mb-2">
                                    <div class="drag-handle"><i class="bx bx-menu"></i></div>
                                    <div>
                                        @if(!$item->isActive)<span class="badge bg-secondary me-1">Off</span>@endif
                                        <button type="button" class="btn btn-sm btn-soft-primary me-1" onclick='openEditItemModal(@json($item))'><i class="bx bx-edit"></i></button>
                                        <button type="button" class="btn btn-sm btn-soft-danger" onclick="openDeleteItemModal({{ $item->id }}, '{{ addslashes($item->title) }}')"><i class="bx bx-trash"></i></button>
                                    </div>
                                </div>
                                @if($item->image)<img src="{{ $item->image }}" alt="{{ $item->title }}" class="img-fluid rounded mb-2" style="height: 80px; width: 100%; object-fit: cover;">
                                @else<div class="bg-light rounded d-flex align-items-center justify-content-center mb-2" style="height: 80px;"><i class="bx bx-image text-secondary" style="font-size: 1.5rem;"></i></div>@endif
                                <h6 class="text-dark mb-1 small">{{ $item->title }}</h6>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($section->items->isEmpty())
                    <div class="text-center py-3"><i class="bx bx-carousel text-secondary" style="font-size: 2rem;"></i><p class="text-dark mt-2 mb-0">No slides yet</p></div>
                @endif
            </div>
        </div>
    </div>

    {{-- SEO --}}
    <div class="accordion-item border-0 mb-1">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#seasonalCtaSeo">
                <i class="bx bx-search-alt accordion-icon"></i>Section SEO
            </button>
        </h2>
        <div id="seasonalCtaSeo" class="accordion-collapse collapse" data-bs-parent="#seasonalCtaAccordion">
            <div class="accordion-body">
                @include('aniSensoAdmin.homepage-settings.partials._seo-section', ['section' => $section, 'sectionKey' => 'seasonal_cta'])
            </div>
        </div>
    </div>
</div>
