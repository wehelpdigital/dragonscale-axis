{{-- Award Winning Section Settings --}}
<div class="accordion settings-accordion" id="awardAccordion">
    {{-- Background & Media --}}
    <div class="accordion-item border-0 mb-1">
        <h2 class="accordion-header">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#awardMedia" aria-expanded="true">
                <i class="bx bx-video accordion-icon"></i>Background & Media
            </button>
        </h2>
        <div id="awardMedia" class="accordion-collapse collapse show" data-bs-parent="#awardAccordion">
            <div class="accordion-body">
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">Background Video URL</label>
                        <input type="text" class="form-control section-setting-input" data-section="award" data-setting="videoUrl"
                               value="{{ $section->getSetting('videoUrl', '') }}" placeholder="https://youtube.com/embed/...">
                        <small class="text-secondary">YouTube embed URL</small>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">Side Image</label>
                        <div class="section-image-upload image-upload-zone" data-section="award" data-setting="sideImage">
                            <input type="file" class="d-none section-image-input" accept="image/*">
                            <div class="upload-placeholder {{ $section->getSetting('sideImage') ? 'd-none' : '' }}">
                                <i class="bx bx-cloud-upload text-muted" style="font-size: 1.5rem;"></i>
                                <p class="text-secondary mb-0 mt-1 small">Click to upload</p>
                            </div>
                            <img src="{{ $section->getSetting('sideImage', '') }}" class="image-preview {{ $section->getSetting('sideImage') ? '' : 'd-none' }}" alt="Side Image">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Content --}}
    <div class="accordion-item border-0 mb-1">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#awardContent">
                <i class="bx bx-text accordion-icon"></i>Content
            </button>
        </h2>
        <div id="awardContent" class="accordion-collapse collapse" data-bs-parent="#awardAccordion">
            <div class="accordion-body">
                <div class="mb-2">
                    <label class="form-label text-dark">Top Label</label>
                    <input type="text" class="form-control section-setting-input" data-section="award" data-setting="badge"
                           value="{{ $section->getSetting('badge', 'Locally and Internationally Recognized') }}">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">Title Line 1</label>
                        <input type="text" class="form-control section-setting-input" data-section="award" data-setting="title"
                               value="{{ $section->getSetting('title', 'Award Winning') }}">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">Title Line 2 <span class="badge bg-warning text-dark" style="font-size:9px">Yellow</span></label>
                        <input type="text" class="form-control section-setting-input" data-section="award" data-setting="titleHighlight"
                               value="{{ $section->getSetting('titleHighlight', 'Technology') }}">
                        <small class="text-secondary">This text appears in yellow</small>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <label class="form-label text-dark">Stat Number</label>
                        <input type="text" class="form-control section-setting-input" data-section="award" data-setting="statNumber"
                               value="{{ $section->getSetting('statNumber', '45+') }}">
                    </div>
                    <div class="col-md-8 mb-2">
                        <label class="form-label text-dark">Stat Label</label>
                        <input type="text" class="form-control section-setting-input" data-section="award" data-setting="statLabel"
                               value="{{ $section->getSetting('statLabel', 'Years of Innovation') }}">
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label text-dark">Description</label>
                    <textarea class="form-control section-setting-input" data-section="award" data-setting="description" rows="3">{{ $section->getSetting('description', 'Proven track record of helping Filipino farmers achieve maximum crop yields through science-backed fertilization and management technologies.') }}</textarea>
                    <small class="text-secondary">Use <code>&lt;span class="yellow"&gt;text&lt;/span&gt;</code> to highlight in yellow</small>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">Button Text</label>
                        <input type="text" class="form-control section-setting-input" data-section="award" data-setting="ctaText"
                               value="{{ $section->getSetting('ctaText', 'Learn More') }}">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-dark">Button URL</label>
                        <input type="text" class="form-control section-setting-input" data-section="award" data-setting="ctaUrl"
                               value="{{ $section->getSetting('ctaUrl', '/about') }}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SEO --}}
    <div class="accordion-item border-0 mb-1">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#awardSeo">
                <i class="bx bx-search-alt accordion-icon"></i>Section SEO
            </button>
        </h2>
        <div id="awardSeo" class="accordion-collapse collapse" data-bs-parent="#awardAccordion">
            <div class="accordion-body">
                @include('aniSensoAdmin.homepage-settings.partials._seo-section', ['section' => $section, 'sectionKey' => 'award'])
            </div>
        </div>
    </div>
</div>
