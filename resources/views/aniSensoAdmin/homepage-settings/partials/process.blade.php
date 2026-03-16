{{-- Process/How It Works Section Settings --}}
<div class="accordion settings-accordion" id="processAccordion">
    {{-- Section Settings --}}
    <div class="accordion-item border-0 mb-1">
        <h2 class="accordion-header">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#processSettings" aria-expanded="true">
                <i class="bx bx-cog accordion-icon"></i>Section Settings
            </button>
        </h2>
        <div id="processSettings" class="accordion-collapse collapse show" data-bs-parent="#processAccordion">
            <div class="accordion-body">
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">Section Title</label>
                        <input type="text" class="form-control section-setting-input" data-section="process" data-setting="title"
                               value="{{ $section->getSetting('title', 'How It Works') }}">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">Subtitle</label>
                        <input type="text" class="form-control section-setting-input" data-section="process" data-setting="subtitle"
                               value="{{ $section->getSetting('subtitle', 'Your Journey to Success') }}">
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label text-dark">Description</label>
                    <textarea class="form-control section-setting-input" data-section="process" data-setting="description" rows="2">{{ $section->getSetting('description', '') }}</textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">Layout Style</label>
                        <select class="form-select section-setting-input" data-section="process" data-setting="layoutStyle">
                            <option value="timeline" {{ $section->getSetting('layoutStyle') == 'timeline' ? 'selected' : '' }}>Timeline</option>
                            <option value="steps" {{ $section->getSetting('layoutStyle') == 'steps' ? 'selected' : '' }}>Steps (Horizontal)</option>
                            <option value="grid" {{ $section->getSetting('layoutStyle') == 'grid' ? 'selected' : '' }}>Grid</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">Show Step Numbers</label>
                        <select class="form-select section-setting-input" data-section="process" data-setting="showNumbers">
                            <option value="1" {{ $section->getSetting('showNumbers', '1') == '1' ? 'selected' : '' }}>Yes</option>
                            <option value="0" {{ $section->getSetting('showNumbers') == '0' ? 'selected' : '' }}>No</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Process Steps --}}
    <div class="accordion-item border-0 mb-1">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#processSteps">
                <i class="bx bx-list-ol accordion-icon"></i>Process Steps
                <span class="badge bg-primary ms-2">{{ $section->items->count() }}</span>
            </button>
        </h2>
        <div id="processSteps" class="accordion-collapse collapse" data-bs-parent="#processAccordion">
            <div class="accordion-body">
                <div class="d-flex justify-content-end mb-3">
                    <button type="button" class="btn btn-sm btn-soft-primary" onclick="openAddItemModal('process', 'step')">
                        <i class="bx bx-plus me-1"></i> Add Step
                    </button>
                </div>
                <div class="sortable-items">
                    @foreach($section->items as $index => $item)
                        <div class="item-card" data-item-id="{{ $item->id }}">
                            <div class="d-flex align-items-start">
                                <div class="drag-handle me-3"><i class="bx bx-menu" style="font-size: 20px;"></i></div>
                                <div class="me-3">
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                                         style="width: 40px; height: 40px; font-weight: bold;">{{ $index + 1 }}</div>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-1">
                                        @if($item->icon)<i class="bx {{ $item->icon }} text-primary me-2" style="font-size: 20px;"></i>@endif
                                        <h6 class="text-dark mb-0">{{ $item->title }}</h6>
                                        @if(!$item->isActive)<span class="badge bg-secondary ms-2">Inactive</span>@endif
                                    </div>
                                    @if($item->description)<p class="text-secondary mb-0 small">{{ Str::limit($item->description, 100) }}</p>@endif
                                </div>
                                <div class="ms-3">
                                    <button type="button" class="btn btn-sm btn-soft-primary me-1" onclick='openEditItemModal(@json($item))'><i class="bx bx-edit"></i></button>
                                    <button type="button" class="btn btn-sm btn-soft-danger" onclick="openDeleteItemModal({{ $item->id }}, '{{ addslashes($item->title) }}')"><i class="bx bx-trash"></i></button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($section->items->isEmpty())
                    <div class="text-center py-3"><i class="bx bx-list-ol text-secondary" style="font-size: 2rem;"></i><p class="text-dark mt-2 mb-0">No process steps yet</p></div>
                @endif
            </div>
        </div>
    </div>

    {{-- SEO --}}
    <div class="accordion-item border-0 mb-1">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#processSeo">
                <i class="bx bx-search-alt accordion-icon"></i>Section SEO
            </button>
        </h2>
        <div id="processSeo" class="accordion-collapse collapse" data-bs-parent="#processAccordion">
            <div class="accordion-body">
                @include('aniSensoAdmin.homepage-settings.partials._seo-section', ['section' => $section, 'sectionKey' => 'process'])
            </div>
        </div>
    </div>
</div>
