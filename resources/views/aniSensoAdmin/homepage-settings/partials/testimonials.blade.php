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

    {{-- Selected Testimonials --}}
    <div class="accordion-item border-0 mb-1">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#testimonialsCards">
                <i class="bx bx-message-square-dots accordion-icon"></i>Testimonials
                <span class="badge bg-primary ms-2">{{ $section->items->count() }}</span>
            </button>
        </h2>
        <div id="testimonialsCards" class="accordion-collapse collapse" data-bs-parent="#testimonialsAccordion">
            <div class="accordion-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <p class="text-secondary mb-0" style="font-size: 0.82rem;">Select testimonials to display on the homepage. <a href="{{ route('anisenso-website-testimonials') }}">Manage all testimonials</a></p>
                    <button type="button" class="btn btn-sm btn-soft-primary" id="btnPickTestimonial">
                        <i class="bx bx-plus me-1"></i> Add from List
                    </button>
                </div>

                {{-- Currently selected testimonials --}}
                <div class="row sortable-items" id="selectedTestimonials">
                    @foreach($section->items as $item)
                        @php $itemExtra = is_array($item->extraData) ? $item->extraData : (json_decode($item->extraData, true) ?? []); @endphp
                        <div class="col-md-4" data-item-id="{{ $item->id }}" data-testimonial-id="{{ $itemExtra['testimonialId'] ?? '' }}">
                            <div class="item-card">
                                <div class="d-flex justify-content-between mb-2">
                                    <div class="drag-handle"><i class="bx bx-menu"></i></div>
                                    <div>
                                        @if(!$item->isActive)<span class="badge bg-secondary me-1">Off</span>@endif
                                        <button type="button" class="btn btn-sm btn-soft-danger remove-from-homepage-btn" data-item-id="{{ $item->id }}" data-name="{{ $item->title }}"><i class="bx bx-x"></i></button>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    @if($item->image)
                                        <img src="{{ asset($item->image) }}" alt="{{ $item->title }}" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
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
                    @if($section->items->isEmpty())
                        <div class="col-12" id="noTestimonialsMsg"><div class="text-center py-3"><i class="bx bx-message-square-dots text-secondary" style="font-size: 2rem;"></i><p class="text-dark mt-2 mb-0">No testimonials selected</p><p class="text-secondary mb-0">Click "Add from List" to pick testimonials to display.</p></div></div>
                    @endif
                </div>
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

{{-- Pick Testimonial Modal --}}
<div class="modal fade" id="pickTestimonialModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bx bx-message-square-dots text-primary me-2"></i>Select Testimonial</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="testimonialPickerBody">
                <p class="text-secondary text-center py-3"><i class="bx bx-loader-alt bx-spin me-1"></i> Loading testimonials...</p>
            </div>
        </div>
    </div>
</div>

<script>
(function waitForJQuery() {
    if (typeof $ === 'undefined') { setTimeout(waitForJQuery, 50); return; }
    $(document).ready(function() {

        var testimonialCache = {};
        var emptyStateHtml = '<div class="col-12" id="noTestimonialsMsg"><div class="text-center py-3"><i class="bx bx-message-square-dots text-secondary" style="font-size: 2rem;"></i><p class="text-dark mt-2 mb-0">No testimonials selected</p><p class="text-secondary mb-0">Click "Add from List" to pick testimonials to display.</p></div></div>';

        function updateBadgeCount() {
            var count = $('#selectedTestimonials .item-card').length;
            $('#testimonialsCards').prev().find('.badge').text(count);
        }

        function buildCardHtml(itemId, testimonialId, name, location, testimonial, rating, image) {
            var initial = (name || 'U').charAt(0).toUpperCase();
            var stars = '';
            for (var s = 1; s <= 5; s++) {
                stars += '<i class="bx ' + (s <= rating ? 'bxs-star text-warning' : 'bx-star text-muted') + '" style="font-size:12px;"></i>';
            }
            var avatarHtml = image
                ? '<img src="' + image + '" class="rounded-circle me-2" style="width:40px;height:40px;object-fit:cover;">'
                : '<div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" style="width:40px;height:40px;font-size:14px;font-weight:bold;">' + initial + '</div>';
            var quote = testimonial.length > 60 ? testimonial.substring(0, 60) + '...' : testimonial;

            return '<div class="col-md-4" data-item-id="' + itemId + '" data-testimonial-id="' + testimonialId + '">' +
                '<div class="item-card">' +
                '  <div class="d-flex justify-content-between mb-2">' +
                '    <div class="drag-handle"><i class="bx bx-menu"></i></div>' +
                '    <div><button type="button" class="btn btn-sm btn-soft-danger remove-from-homepage-btn" data-item-id="' + itemId + '" data-name="' + name.replace(/'/g, '&#39;') + '"><i class="bx bx-x"></i></button></div>' +
                '  </div>' +
                '  <div class="d-flex align-items-center mb-2">' + avatarHtml +
                '    <div><h6 class="text-dark mb-0 small">' + name + '</h6>' +
                (location ? '<small class="text-secondary" style="font-size:11px;">' + location + '</small>' : '') +
                '    </div></div>' +
                '  <div class="mb-1">' + stars + '</div>' +
                '  <small class="text-secondary fst-italic">"' + quote + '"</small>' +
                '</div></div>';
        }

        // ── Open picker modal ──
        $('#btnPickTestimonial').on('click', function() {
            $('#testimonialPickerBody').html('<p class="text-secondary text-center py-3"><i class="bx bx-loader-alt bx-spin me-1"></i> Loading testimonials...</p>');
            $('#pickTestimonialModal').modal('show');

            // Build current IDs from DOM (real-time)
            var currentIds = [];
            $('#selectedTestimonials [data-testimonial-id]').each(function() {
                var tid = parseInt($(this).data('testimonial-id'));
                if (tid) currentIds.push(tid);
            });

            $.ajax({
                url: '/anisenso-website-testimonials-list-for-picker',
                type: 'GET',
                success: function(response) {
                    if (response.success && response.testimonials.length > 0) {
                        var html = '<div class="row">';
                        response.testimonials.forEach(function(t) {
                            testimonialCache[t.id] = t;
                            var alreadyAdded = currentIds.indexOf(t.id) !== -1;
                            var initial = (t.name || 'U').charAt(0).toUpperCase();
                            html += '<div class="col-md-6 mb-3" id="picker-item-' + t.id + '">';
                            html += '  <div class="item-card d-flex align-items-start gap-3 ' + (alreadyAdded ? 'opacity-50' : '') + '">';
                            if (t.image) {
                                html += '    <img src="' + t.image + '" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;">';
                            } else {
                                html += '    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:40px;height:40px;font-size:14px;font-weight:bold;flex-shrink:0;">' + initial + '</div>';
                            }
                            html += '    <div class="flex-grow-1">';
                            html += '      <h6 class="text-dark mb-0 small">' + t.name + '</h6>';
                            if (t.location) html += '      <small class="text-secondary" style="font-size:11px;">' + t.location + '</small>';
                            html += '      <div class="my-1">';
                            for (var i = 1; i <= 5; i++) {
                                html += '<i class="bx ' + (i <= t.rating ? 'bxs-star text-warning' : 'bx-star text-muted') + '" style="font-size:12px;"></i>';
                            }
                            html += '      </div>';
                            html += '      <small class="text-secondary fst-italic">"' + (t.testimonial.length > 80 ? t.testimonial.substring(0, 80) + '...' : t.testimonial) + '"</small>';
                            html += '    </div>';
                            html += '    <div class="flex-shrink-0 picker-action">';
                            if (alreadyAdded) {
                                html += '      <span class="badge bg-success"><i class="bx bx-check"></i></span>';
                            } else {
                                html += '      <button class="btn btn-sm btn-soft-primary pick-testimonial-btn" data-id="' + t.id + '"><i class="bx bx-plus"></i></button>';
                            }
                            html += '    </div>';
                            html += '  </div>';
                            html += '</div>';
                        });
                        html += '</div>';
                        $('#testimonialPickerBody').html(html);
                    } else {
                        $('#testimonialPickerBody').html('<div class="text-center py-4"><p class="text-secondary mb-2">No testimonials available.</p><a href="{{ route("anisenso-website-testimonials") }}" class="btn btn-sm btn-primary"><i class="bx bx-plus me-1"></i>Create Testimonials</a></div>');
                    }
                },
                error: function() {
                    $('#testimonialPickerBody').html('<p class="text-danger text-center">Failed to load testimonials.</p>');
                }
            });
        });

        // ── Pick a testimonial (add as homepage item) ──
        $(document).on('click', '.pick-testimonial-btn', function() {
            var $btn = $(this);
            var tid = $btn.data('id');
            var t = testimonialCache[tid];
            if (!t) return;

            $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');

            $.ajax({
                url: '/anisenso-website-testimonials-add-to-homepage',
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data: { testimonial_id: tid },
                success: function(response) {
                    if (response.success) {
                        // Update picker button to checkmark
                        $btn.closest('.picker-action').html('<span class="badge bg-success"><i class="bx bx-check"></i></span>');
                        $('#picker-item-' + tid + ' .item-card').addClass('opacity-50');

                        // Add card to selected list
                        $('#noTestimonialsMsg').remove();
                        $('#selectedTestimonials').append(buildCardHtml(
                            response.itemId, tid,
                            t.name, t.location || '',
                            t.testimonial || '', t.rating || 5,
                            t.image || ''
                        ));
                        updateBadgeCount();
                        toastr.success('"' + t.name + '" added to homepage.');
                    } else {
                        toastr.warning(response.message || 'Could not add testimonial');
                        $btn.prop('disabled', false).html('<i class="bx bx-plus"></i>');
                    }
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Failed to add testimonial');
                    $btn.prop('disabled', false).html('<i class="bx bx-plus"></i>');
                }
            });
        });

        // ── Remove testimonial from homepage ──
        $(document).on('click', '.remove-from-homepage-btn', function() {
            var $btn = $(this);
            var itemId = $btn.data('item-id');
            var name = $btn.data('name');

            $.ajax({
                url: '/anisenso-homepage-settings-items/' + itemId,
                type: 'DELETE',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function() {
                    $('[data-item-id="' + itemId + '"]').fadeOut(300, function() {
                        $(this).remove();
                        updateBadgeCount();
                        if ($('#selectedTestimonials .item-card').length === 0 && $('#noTestimonialsMsg').length === 0) {
                            $('#selectedTestimonials').append(emptyStateHtml);
                        }
                    });
                },
                error: function() {
                    toastr.error('Failed to remove testimonial');
                }
            });
        });

    });
})();
</script>
