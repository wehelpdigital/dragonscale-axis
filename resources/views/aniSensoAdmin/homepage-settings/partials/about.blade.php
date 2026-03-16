{{-- About Section Settings --}}
<div class="accordion settings-accordion" id="aboutAccordion">
    {{-- Content --}}
    <div class="accordion-item border-0 mb-1">
        <h2 class="accordion-header">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#aboutContent" aria-expanded="true">
                <i class="bx bx-text accordion-icon"></i>Section Content
            </button>
        </h2>
        <div id="aboutContent" class="accordion-collapse collapse show" data-bs-parent="#aboutAccordion">
            <div class="accordion-body">
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">Section Title</label>
                        <input type="text" class="form-control section-setting-input" data-section="about" data-setting="title"
                               value="{{ $section->getSetting('title', 'About Ani-Senso') }}">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">Subtitle</label>
                        <input type="text" class="form-control section-setting-input" data-section="about" data-setting="subtitle"
                               value="{{ $section->getSetting('subtitle', 'Your Agriculture Partner') }}">
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label text-dark">Description</label>
                    <textarea class="form-control section-setting-input" data-section="about" data-setting="description" rows="3" placeholder="Use &lt;span class=yellow&gt;text&lt;/span&gt; for yellow highlight">{{ $section->getSetting('description', '') }}</textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">CTA Button Text</label>
                        <input type="text" class="form-control section-setting-input" data-section="about" data-setting="ctaText"
                               value="{{ $section->getSetting('ctaText', 'Learn More') }}">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">CTA Button URL</label>
                        <input type="text" class="form-control section-setting-input" data-section="about" data-setting="ctaUrl"
                               value="{{ $section->getSetting('ctaUrl', '/about') }}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Service Items --}}
    <div class="accordion-item border-0 mb-1">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#aboutServices">
                <i class="bx bx-briefcase accordion-icon"></i>Services
                <span class="badge bg-primary ms-2">{{ $section->items->count() }}</span>
            </button>
        </h2>
        <div id="aboutServices" class="accordion-collapse collapse" data-bs-parent="#aboutAccordion">
            <div class="accordion-body">
                <div class="d-flex justify-content-end mb-3">
                    <button type="button" class="btn btn-sm btn-soft-primary" onclick="openAddItemModal('about', 'service')">
                        <i class="bx bx-plus me-1"></i> Add Service
                    </button>
                </div>
                <div class="row sortable-items">
                    @foreach($section->items as $item)
                        <div class="col-md-6 col-lg-3" data-item-id="{{ $item->id }}">
                            <div class="item-card text-center">
                                <div class="d-flex justify-content-between mb-2">
                                    <div class="drag-handle"><i class="bx bx-menu"></i></div>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-soft-primary me-1" onclick='openEditItemModal(@json($item))'><i class="bx bx-edit"></i></button>
                                        <button type="button" class="btn btn-sm btn-soft-danger" onclick="openDeleteItemModal({{ $item->id }}, '{{ addslashes($item->title) }}')"><i class="bx bx-trash"></i></button>
                                    </div>
                                </div>
                                @if($item->icon)<div class="mb-2"><i class="bx {{ $item->icon }} text-success" style="font-size: 2rem;"></i></div>@endif
                                <h6 class="text-dark mb-1">{{ $item->title }}</h6>
                                @if($item->description)<small class="text-secondary">{{ Str::limit($item->description, 50) }}</small>@endif
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($section->items->isEmpty())
                    <div class="text-center py-3"><i class="bx bx-briefcase text-secondary" style="font-size: 2rem;"></i><p class="text-dark mt-2 mb-0">No services yet</p></div>
                @endif
            </div>
        </div>
    </div>

    {{-- SEO --}}
    <div class="accordion-item border-0 mb-1">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#aboutSeo">
                <i class="bx bx-search-alt accordion-icon"></i>Section SEO
            </button>
        </h2>
        <div id="aboutSeo" class="accordion-collapse collapse" data-bs-parent="#aboutAccordion">
            <div class="accordion-body">
                @include('aniSensoAdmin.homepage-settings.partials._seo-section', ['section' => $section, 'sectionKey' => 'about'])
            </div>
        </div>
    </div>
</div>
