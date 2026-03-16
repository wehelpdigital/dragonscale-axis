{{-- Testimonials Section Settings --}}
<div class="accordion settings-accordion" id="testimonialsAccordion">
    {{-- Section Settings --}}
    <div class="accordion-item border-0 mb-1">
        <h2 class="accordion-header">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#testimonialsSettings" aria-expanded="true">
                <i class="bx bx-cog accordion-icon"></i>Section Settings
            </button>
        </h2>
        <div id="testimonialsSettings" class="accordion-collapse collapse show" data-bs-parent="#testimonialsAccordion">
            <div class="accordion-body">
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">Section Title</label>
                        <input type="text" class="form-control section-setting-input" data-section="testimonials" data-setting="title"
                               value="{{ $section->getSetting('title', 'What Our Students Say') }}">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">Subtitle</label>
                        <input type="text" class="form-control section-setting-input" data-section="testimonials" data-setting="subtitle"
                               value="{{ $section->getSetting('subtitle', 'Testimonials from our community') }}">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">Display Style</label>
                        <select class="form-select section-setting-input" data-section="testimonials" data-setting="displayStyle">
                            <option value="grid" {{ $section->getSetting('displayStyle', 'grid') == 'grid' ? 'selected' : '' }}>Grid</option>
                            <option value="carousel" {{ $section->getSetting('displayStyle') == 'carousel' ? 'selected' : '' }}>Carousel</option>
                            <option value="masonry" {{ $section->getSetting('displayStyle') == 'masonry' ? 'selected' : '' }}>Masonry</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">Show Rating Stars</label>
                        <select class="form-select section-setting-input" data-section="testimonials" data-setting="showRating">
                            <option value="1" {{ $section->getSetting('showRating', '1') == '1' ? 'selected' : '' }}>Yes</option>
                            <option value="0" {{ $section->getSetting('showRating') == '0' ? 'selected' : '' }}>No</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Testimonial Cards --}}
    <div class="accordion-item border-0 mb-1">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#testimonialsCards">
                <i class="bx bx-message-square-dots accordion-icon"></i>Testimonials
                <span class="badge bg-primary ms-2">{{ $section->items->count() }}</span>
            </button>
        </h2>
        <div id="testimonialsCards" class="accordion-collapse collapse" data-bs-parent="#testimonialsAccordion">
            <div class="accordion-body">
                <div class="d-flex justify-content-end mb-3">
                    <button type="button" class="btn btn-sm btn-soft-primary" onclick="openAddItemModal('testimonials', 'testimonial')">
                        <i class="bx bx-plus me-1"></i> Add Testimonial
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
                                <div class="d-flex align-items-center mb-2">
                                    @if($item->image)
                                        <img src="{{ $item->image }}" alt="{{ $item->title }}" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                    @else
                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px; font-size: 14px; font-weight: bold;">{{ strtoupper(substr($item->title ?? 'U', 0, 1)) }}</div>
                                    @endif
                                    <div>
                                        <h6 class="text-dark mb-0 small">{{ $item->title }}</h6>
                                        @if($item->subtitle)<small class="text-secondary" style="font-size: 11px;">{{ $item->subtitle }}</small>@endif
                                    </div>
                                </div>
                                @php $rating = $item->getExtra('rating', 5); @endphp
                                <div class="mb-1">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="bx {{ $i <= $rating ? 'bxs-star text-warning' : 'bx-star text-muted' }}" style="font-size: 12px;"></i>
                                    @endfor
                                </div>
                                @if($item->description)<small class="text-secondary fst-italic">"{{ Str::limit($item->description, 60) }}"</small>@endif
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($section->items->isEmpty())
                    <div class="text-center py-3"><i class="bx bx-message-square-dots text-secondary" style="font-size: 2rem;"></i><p class="text-dark mt-2 mb-0">No testimonials yet</p></div>
                @endif
            </div>
        </div>
    </div>

    {{-- SEO --}}
    <div class="accordion-item border-0 mb-1">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#testimonialsSeo">
                <i class="bx bx-search-alt accordion-icon"></i>Section SEO
            </button>
        </h2>
        <div id="testimonialsSeo" class="accordion-collapse collapse" data-bs-parent="#testimonialsAccordion">
            <div class="accordion-body">
                @include('aniSensoAdmin.homepage-settings.partials._seo-section', ['section' => $section, 'sectionKey' => 'testimonials'])
            </div>
        </div>
    </div>
</div>

<script>
// Extend the add item modal for testimonials to include rating
(function waitForJQuery() {
    if (typeof $ === 'undefined' || typeof jQuery === 'undefined') {
        setTimeout(waitForJQuery, 50);
        return;
    }
    $(document).ready(function() {
        const originalOpenAdd = window.openAddItemModal;
        window.openAddItemModal = function(sectionKey, itemType) {
            originalOpenAdd(sectionKey, itemType);
            if (sectionKey === 'testimonials') {
                if ($('#addItemRating').length === 0) {
                    const ratingHtml = `
                        <div class="mb-2" id="addItemRatingContainer">
                            <label class="form-label text-dark">Rating (1-5 stars)</label>
                            <select class="form-select" id="addItemRating">
                                <option value="5">5 Stars</option>
                                <option value="4">4 Stars</option>
                                <option value="3">3 Stars</option>
                                <option value="2">2 Stars</option>
                                <option value="1">1 Star</option>
                            </select>
                        </div>
                    `;
                    $('#addItemForm').append(ratingHtml);
                }
                $('#addItemRatingContainer').show();
            } else {
                $('#addItemRatingContainer').hide();
            }
        };
    });
})();
</script>
