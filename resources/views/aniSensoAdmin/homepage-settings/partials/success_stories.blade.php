{{-- Success Stories Section Settings --}}
<div class="accordion settings-accordion" id="successStoriesAccordion">
    {{-- Section Settings --}}
    <div class="accordion-item border-0 mb-1">
        <h2 class="accordion-header">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#successStoriesSettings" aria-expanded="true">
                <i class="bx bx-cog accordion-icon"></i>Section Settings
            </button>
        </h2>
        <div id="successStoriesSettings" class="accordion-collapse collapse show" data-bs-parent="#successStoriesAccordion">
            <div class="accordion-body">
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">Section Title</label>
                        <input type="text" class="form-control section-setting-input" data-section="success_stories" data-setting="title"
                               value="{{ $section->getSetting('title', 'Success Stories') }}">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">Subtitle</label>
                        <input type="text" class="form-control section-setting-input" data-section="success_stories" data-setting="subtitle"
                               value="{{ $section->getSetting('subtitle', 'Real Results from Real Farmers') }}">
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label text-dark">Description</label>
                    <textarea class="form-control section-setting-input" data-section="success_stories" data-setting="description" rows="2">{{ $section->getSetting('description', '') }}</textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">CTA Button Text</label>
                        <input type="text" class="form-control section-setting-input" data-section="success_stories" data-setting="ctaText"
                               value="{{ $section->getSetting('ctaText', 'View All Stories') }}">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">CTA Button URL</label>
                        <input type="text" class="form-control section-setting-input" data-section="success_stories" data-setting="ctaUrl"
                               value="{{ $section->getSetting('ctaUrl', '/success-stories') }}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Success Story Cards --}}
    <div class="accordion-item border-0 mb-1">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#successStoriesCards">
                <i class="bx bx-trophy accordion-icon"></i>Success Stories
                <span class="badge bg-primary ms-2">{{ $section->items->count() }}</span>
            </button>
        </h2>
        <div id="successStoriesCards" class="accordion-collapse collapse" data-bs-parent="#successStoriesAccordion">
            <div class="accordion-body">
                <div class="d-flex justify-content-end mb-3">
                    <button type="button" class="btn btn-sm btn-soft-primary" onclick="openAddItemModal('success_stories', 'story')">
                        <i class="bx bx-plus me-1"></i> Add Story
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
                                @if($item->image)<img src="{{ $item->image }}" alt="{{ $item->title }}" class="img-fluid rounded mb-2" style="height: 100px; width: 100%; object-fit: cover;">
                                @else<div class="bg-light rounded d-flex align-items-center justify-content-center mb-2" style="height: 100px;"><i class="bx bx-image text-secondary" style="font-size: 2rem;"></i></div>@endif
                                <h6 class="text-dark mb-1 small">{{ $item->title }}</h6>
                                @if($item->subtitle)<small class="text-primary d-block mb-1">{{ $item->subtitle }}</small>@endif
                                @if($item->description)<small class="text-secondary">{{ Str::limit($item->description, 60) }}</small>@endif
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($section->items->isEmpty())
                    <div class="text-center py-3"><i class="bx bx-trophy text-secondary" style="font-size: 2rem;"></i><p class="text-dark mt-2 mb-0">No success stories yet</p></div>
                @endif
            </div>
        </div>
    </div>

    {{-- SEO --}}
    <div class="accordion-item border-0 mb-1">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#successStoriesSeo">
                <i class="bx bx-search-alt accordion-icon"></i>Section SEO
            </button>
        </h2>
        <div id="successStoriesSeo" class="accordion-collapse collapse" data-bs-parent="#successStoriesAccordion">
            <div class="accordion-body">
                @include('aniSensoAdmin.homepage-settings.partials._seo-section', ['section' => $section, 'sectionKey' => 'success_stories'])
            </div>
        </div>
    </div>
</div>
