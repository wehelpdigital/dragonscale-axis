{{-- Hero Section Settings --}}
<div class="accordion settings-accordion" id="heroAccordion">
    {{-- Slider Images --}}
    <div class="accordion-item border-0 mb-1">
        <h2 class="accordion-header">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#heroSlider" aria-expanded="true">
                <i class="bx bx-images accordion-icon"></i>Slider Images
                <span class="badge bg-primary ms-2" id="slideCount">{{ $section->items->count() }}</span>
            </button>
        </h2>
        <div id="heroSlider" class="accordion-collapse collapse show" data-bs-parent="#heroAccordion">
            <div class="accordion-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <small class="text-secondary">Images will fade in/out automatically</small>
                    <button type="button" class="btn btn-sm btn-soft-primary" id="addSlideBtn">
                        <i class="bx bx-plus me-1"></i>Add Image
                    </button>
                </div>

                {{-- Upload Progress Bar --}}
                <div id="uploadProgress" class="d-none mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="progress flex-grow-1" style="height: 8px;">
                            <div id="uploadProgressFill" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width: 0%;"></div>
                        </div>
                        <small id="uploadProgressText" class="text-secondary" style="min-width: 35px;">0%</small>
                    </div>
                </div>

                <div class="slider-images-list" id="sliderImagesList">
                    @forelse($section->items as $item)
                    <div class="slide-item d-flex align-items-center gap-2 mb-2 p-2 border rounded" data-item-id="{{ $item->id }}">
                        <div class="drag-handle"><i class="bx bx-menu"></i></div>
                        <div class="slide-thumb" style="width:80px;height:50px;overflow:hidden;border-radius:4px;background:#f1f1f1;">
                            <img src="{{ $item->imageUrl }}" style="width:100%;height:100%;object-fit:cover;" alt="">
                        </div>
                        <div class="flex-grow-1">
                            <input type="text" class="form-control form-control-sm slide-title"
                                   value="{{ $item->title }}" placeholder="Slide label (optional)"
                                   data-item-id="{{ $item->id }}">
                        </div>
                        <button type="button" class="btn btn-sm btn-soft-danger delete-slide-btn" data-item-id="{{ $item->id }}">
                            <i class="bx bx-trash"></i>
                        </button>
                    </div>
                    @empty
                    <div class="text-center py-3 empty-slides-msg">
                        <i class="bx bx-image text-secondary" style="font-size: 2rem;"></i>
                        <p class="text-dark mt-2 mb-0">No slider images yet</p>
                    </div>
                    @endforelse
                </div>

                <div class="mt-3 pt-2 border-top">
                    <label class="form-label text-dark">Overlay Opacity</label>
                    <div class="d-flex align-items-center gap-2">
                        <input type="range" class="form-range section-setting-input" style="max-width: 180px;"
                               data-section="hero" data-setting="overlayOpacity"
                               value="{{ $section->getSetting('overlayOpacity', 50) }}"
                               min="0" max="100"
                               oninput="this.nextElementSibling.textContent = this.value + '%'">
                        <span class="badge bg-secondary">{{ $section->getSetting('overlayOpacity', 50) }}%</span>
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col-md-6">
                        <label class="form-label text-dark">Slide Duration (seconds)</label>
                        <input type="number" class="form-control form-control-sm section-setting-input"
                               data-section="hero" data-setting="slideDuration"
                               value="{{ $section->getSetting('slideDuration', 5) }}"
                               min="2" max="15">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-dark">Fade Speed (ms)</label>
                        <input type="number" class="form-control form-control-sm section-setting-input"
                               data-section="hero" data-setting="fadeSpeed"
                               value="{{ $section->getSetting('fadeSpeed', 1000) }}"
                               min="200" max="3000" step="100">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Hero Text --}}
    <div class="accordion-item border-0 mb-1">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#heroText">
                <i class="bx bx-text accordion-icon"></i>Hero Text
            </button>
        </h2>
        <div id="heroText" class="accordion-collapse collapse" data-bs-parent="#heroAccordion">
            <div class="accordion-body">
                <div class="mb-2">
                    <label class="form-label text-dark">Supertext (Above Title)</label>
                    <input type="text"
                           class="form-control section-setting-input"
                           data-section="hero"
                           data-setting="supertext"
                           value="{{ $section->getSetting('supertext', 'Maximizing Crop Yields for Palay, Mais, and More') }}">
                    <small class="text-secondary">Small text displayed above the main title</small>
                </div>
                <div class="mb-2">
                    <label class="form-label text-dark">Main Title</label>
                    <input type="text"
                           class="form-control section-setting-input"
                           data-section="hero"
                           data-setting="title"
                           value="{{ $section->getSetting('title', 'Helping Filipino Farmers Reach Maximum Yield and Income') }}">
                    <small class="text-secondary">Use <code>&lt;span class="yellow"&gt;text&lt;/span&gt;</code> to highlight in yellow</small>
                </div>
                <div class="mb-2">
                    <label class="form-label text-dark">Description</label>
                    <textarea class="form-control section-setting-input"
                              data-section="hero"
                              data-setting="description"
                              rows="3">{{ $section->getSetting('description', 'At Ani (Yield) + Senso (Sensei means Teacher and Asenso means Success) — we help farmers maximize their yield through our exclusive technical research, support, fertilization, and management technologies.') }}</textarea>
                    <small class="text-secondary">Use <code>&lt;span class="yellow"&gt;text&lt;/span&gt;</code> to highlight in yellow</small>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">CTA Button Text</label>
                        <input type="text"
                               class="form-control section-setting-input"
                               data-section="hero"
                               data-setting="ctaText"
                               value="{{ $section->getSetting('ctaText', 'Join Our Community Now') }}">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">CTA Button URL</label>
                        <input type="text"
                               class="form-control section-setting-input"
                               data-section="hero"
                               data-setting="ctaUrl"
                               value="{{ $section->getSetting('ctaUrl', '/courses') }}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SEO Settings --}}
    <div class="accordion-item border-0 mb-1">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#heroSeo">
                <i class="bx bx-search-alt accordion-icon"></i>Section SEO
            </button>
        </h2>
        <div id="heroSeo" class="accordion-collapse collapse" data-bs-parent="#heroAccordion">
            <div class="accordion-body">
                @include('aniSensoAdmin.homepage-settings.partials._seo-section', ['section' => $section, 'sectionKey' => 'hero'])
            </div>
        </div>
    </div>
</div>

{{-- Hidden file input for slide upload --}}
<input type="file" id="slideImageInput" class="d-none" accept="image/*">

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sectionId = {{ $section->id }};
    const slideInput = document.getElementById('slideImageInput');

    // Add new slide
    document.getElementById('addSlideBtn').addEventListener('click', function() {
        slideInput.click();
    });

    // Handle file selection
    slideInput.addEventListener('change', function() {
        if (!this.files || !this.files[0]) return;

        const formData = new FormData();
        formData.append('image', this.files[0]);
        formData.append('sectionId', sectionId);
        formData.append('itemType', 'slide');
        formData.append('title', 'Slide ' + (document.querySelectorAll('.slide-item').length + 1));
        formData.append('_token', '{{ csrf_token() }}');

        const btn = document.getElementById('addSlideBtn');
        const progressBar = document.getElementById('uploadProgress');
        const progressFill = document.getElementById('uploadProgressFill');
        const progressText = document.getElementById('uploadProgressText');

        btn.disabled = true;
        btn.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i>Uploading...';
        progressBar.classList.remove('d-none');
        progressFill.style.width = '0%';
        progressText.textContent = '0%';

        const xhr = new XMLHttpRequest();

        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                progressFill.style.width = percent + '%';
                progressText.textContent = percent + '%';
            }
        });

        xhr.addEventListener('load', function() {
            try {
                const data = JSON.parse(xhr.responseText);
                if (xhr.status === 200 && data.success && data.item) {
                    addSlideToList(data.item);
                    document.querySelector('.empty-slides-msg')?.remove();
                    updateSlideCount();
                    toastr.success('Image added!');
                } else {
                    toastr.error(data.message || data.errors?.image?.[0] || 'Upload failed');
                }
            } catch (e) {
                console.error('Parse error:', e);
                toastr.error('Upload failed');
            }
            resetUploadUI();
        });

        xhr.addEventListener('error', function() {
            toastr.error('Upload failed - network error');
            resetUploadUI();
        });

        xhr.addEventListener('abort', function() {
            toastr.warning('Upload cancelled');
            resetUploadUI();
        });

        function resetUploadUI() {
            btn.disabled = false;
            btn.innerHTML = '<i class="bx bx-plus me-1"></i>Add Image';
            slideInput.value = '';
            setTimeout(() => {
                progressBar.classList.add('d-none');
                progressFill.style.width = '0%';
            }, 500);
        }

        xhr.open('POST', '/anisenso-homepage-settings/upload-slide');
        xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.send(formData);
    });

    // Add slide to list
    function addSlideToList(item) {
        const html = `
        <div class="slide-item d-flex align-items-center gap-2 mb-2 p-2 border rounded" data-item-id="${item.id}">
            <div class="drag-handle"><i class="bx bx-menu"></i></div>
            <div class="slide-thumb" style="width:80px;height:50px;overflow:hidden;border-radius:4px;background:#f1f1f1;">
                <img src="${item.imageUrl}" style="width:100%;height:100%;object-fit:cover;" alt="">
            </div>
            <div class="flex-grow-1">
                <input type="text" class="form-control form-control-sm slide-title"
                       value="${item.title || ''}" placeholder="Slide label (optional)"
                       data-item-id="${item.id}">
            </div>
            <button type="button" class="btn btn-sm btn-soft-danger delete-slide-btn" data-item-id="${item.id}">
                <i class="bx bx-trash"></i>
            </button>
        </div>`;
        document.getElementById('sliderImagesList').insertAdjacentHTML('beforeend', html);
        bindSlideEvents(item.id);
    }

    // Bind events for a slide
    function bindSlideEvents(itemId) {
        const item = document.querySelector(`.slide-item[data-item-id="${itemId}"]`);
        if (!item) return;

        item.querySelector('.slide-title').addEventListener('blur', function() {
            updateSlideTitle(itemId, this.value);
        });

        item.querySelector('.delete-slide-btn').addEventListener('click', function() {
            deleteSlide(itemId);
        });
    }

    // Bind existing slides
    document.querySelectorAll('.slide-item').forEach(item => {
        const itemId = item.dataset.itemId;
        bindSlideEvents(itemId);
    });

    // Update slide title
    function updateSlideTitle(itemId, title) {
        fetch('/anisenso-homepage-settings/items/' + itemId, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ title: title })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) toastr.success('Updated!');
        });
    }

    // Delete slide
    function deleteSlide(itemId) {
        if (!confirm('Delete this slide?')) return;

        fetch('/anisenso-homepage-settings/items/' + itemId, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.querySelector(`.slide-item[data-item-id="${itemId}"]`)?.remove();
                updateSlideCount();
                if (document.querySelectorAll('.slide-item').length === 0) {
                    document.getElementById('sliderImagesList').innerHTML = `
                        <div class="text-center py-3 empty-slides-msg">
                            <i class="bx bx-image text-secondary" style="font-size: 2rem;"></i>
                            <p class="text-dark mt-2 mb-0">No slider images yet</p>
                        </div>`;
                }
                toastr.success('Deleted!');
            }
        });
    }

    function updateSlideCount() {
        document.getElementById('slideCount').textContent = document.querySelectorAll('.slide-item').length;
    }

    // Save slide alt text (in SEO section)
    document.querySelectorAll('.slide-alt-input').forEach(input => {
        input.addEventListener('blur', function() {
            const itemId = this.dataset.itemId;
            const altText = this.value;

            fetch('/anisenso-homepage-settings/items/' + itemId + '/extra', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ key: 'altText', value: altText })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) toastr.success('Alt text saved!');
            })
            .catch(err => {
                toastr.error('Failed to save alt text');
            });
        });
    });
});
</script>
