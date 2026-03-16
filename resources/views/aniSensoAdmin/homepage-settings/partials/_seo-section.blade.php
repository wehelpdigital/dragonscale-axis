{{-- Section-Specific SEO Settings (Simplified) --}}
{{-- Only contains SEO relevant to THIS section, not page-level meta tags --}}

{{-- Hero Slider Images SEO --}}
@if($sectionKey === 'hero')
    @php
        $sliderImages = $section->items->where('itemType', 'slide') ?? collect();
    @endphp
    @if($sliderImages->count() > 0)
    <div class="mb-3">
        <label class="form-label text-dark">Featured Image for Social Sharing</label>
        <select class="form-select section-setting-input"
                data-section="{{ $sectionKey }}"
                data-setting="seo_featuredImage">
            <option value="">-- Select Primary Image --</option>
            @foreach($sliderImages as $slide)
                <option value="{{ $slide->imageUrl }}" {{ $section->getSetting('seo_featuredImage') === $slide->imageUrl ? 'selected' : '' }}>
                    {{ $slide->title ?: 'Slide ' . $loop->iteration }}
                </option>
            @endforeach
        </select>
        <small class="text-secondary">This image will be used for og:image and social media previews</small>
    </div>

    <div class="mb-3">
        <label class="form-label text-dark d-flex align-items-center justify-content-between">
            <span>Slider Images Alt Text</span>
            <span class="badge bg-secondary">{{ $sliderImages->count() }} images</span>
        </label>
        <div class="slider-seo-list border rounded p-2" style="max-height: 200px; overflow-y: auto;">
            @foreach($sliderImages as $slide)
            <div class="d-flex align-items-center gap-2 mb-2 pb-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                <div style="width:50px;height:35px;overflow:hidden;border-radius:4px;background:#f1f1f1;flex-shrink:0;">
                    <img src="{{ $slide->imageUrl }}" style="width:100%;height:100%;object-fit:cover;" alt="">
                </div>
                <div class="flex-grow-1">
                    <input type="text"
                           class="form-control form-control-sm slide-alt-input"
                           data-item-id="{{ $slide->id }}"
                           value="{{ $slide->getExtra('altText', '') }}"
                           placeholder="Alt text for {{ $slide->title ?: 'Slide ' . $loop->iteration }}">
                </div>
            </div>
            @endforeach
        </div>
        <small class="text-secondary">Alt text improves accessibility and image SEO</small>
    </div>
    @else
    <div class="alert alert-info py-2 mb-2">
        <i class="bx bx-info-circle me-1"></i>
        Add slider images in the "Slider Images" section above to configure image SEO.
    </div>
    @endif
@endif

{{-- Image Alt Texts for other sections --}}
@if(in_array($sectionKey, ['award', 'about', 'partners', 'before_after', 'success_stories', 'seasonal_cta']))
<div class="mb-2">
    <label class="form-label text-dark">Image Alt Text</label>
    <input type="text"
           class="form-control section-setting-input"
           data-section="{{ $sectionKey }}"
           data-setting="seo_imageAlt"
           value="{{ $section->getSetting('seo_imageAlt', '') }}"
           placeholder="Descriptive text for images in this section">
    <small class="text-secondary">For accessibility and image SEO</small>
</div>
@endif

<div class="row">
    <div class="col-md-6 mb-2">
        <label class="form-label text-dark">Section Schema Type</label>
        <select class="form-select section-setting-input"
                data-section="{{ $sectionKey }}"
                data-setting="seo_schemaType">
            @php
                $schemaTypes = [
                    'hero' => ['WebPage', 'Organization'],
                    'award' => ['Organization', 'Award'],
                    'about' => ['AboutPage', 'Organization'],
                    'partners' => ['Organization', 'ItemList'],
                    'before_after' => ['ImageGallery', 'HowTo'],
                    'seasonal_cta' => ['Offer', 'Event'],
                    'process' => ['HowTo', 'Course'],
                    'success_stories' => ['Article', 'Review'],
                    'testimonials' => ['Review', 'AggregateRating'],
                    'hero_features' => ['ItemList', 'Service'],
                ];
                $options = $schemaTypes[$sectionKey] ?? ['Thing'];
                $currentSchema = $section->getSetting('seo_schemaType', $options[0] ?? 'Thing');
            @endphp
            @foreach($options as $option)
                <option value="{{ $option }}" {{ $currentSchema === $option ? 'selected' : '' }}>{{ $option }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6 mb-2">
        <label class="form-label text-dark">Section Anchor ID</label>
        <input type="text"
               class="form-control section-setting-input"
               data-section="{{ $sectionKey }}"
               data-setting="seo_anchorId"
               value="{{ $section->getSetting('seo_anchorId', $sectionKey) }}"
               placeholder="{{ $sectionKey }}">
        <small class="text-secondary">For #anchor links</small>
    </div>
</div>

{{-- Section-Specific Schema Fields --}}
@if($sectionKey === 'testimonials' || $sectionKey === 'success_stories')
<div class="row">
    <div class="col-md-4 mb-2">
        <label class="form-label text-dark">Aggregate Rating</label>
        <input type="number"
               class="form-control section-setting-input"
               data-section="{{ $sectionKey }}"
               data-setting="seo_aggregateRating"
               value="{{ $section->getSetting('seo_aggregateRating', '4.8') }}"
               min="1" max="5" step="0.1">
    </div>
    <div class="col-md-4 mb-2">
        <label class="form-label text-dark">Review Count</label>
        <input type="number"
               class="form-control section-setting-input"
               data-section="{{ $sectionKey }}"
               data-setting="seo_reviewCount"
               value="{{ $section->getSetting('seo_reviewCount', '100') }}">
    </div>
    <div class="col-md-4 mb-2">
        <label class="form-label text-dark">Best Rating</label>
        <input type="number"
               class="form-control section-setting-input"
               data-section="{{ $sectionKey }}"
               data-setting="seo_bestRating"
               value="{{ $section->getSetting('seo_bestRating', '5') }}">
    </div>
</div>
@endif

@if($sectionKey === 'process')
<div class="row">
    <div class="col-md-6 mb-2">
        <label class="form-label text-dark">Total Time (ISO 8601)</label>
        <input type="text"
               class="form-control section-setting-input"
               data-section="{{ $sectionKey }}"
               data-setting="seo_totalTime"
               value="{{ $section->getSetting('seo_totalTime', '') }}"
               placeholder="PT30M or P1D">
        <small class="text-secondary">e.g., PT30M (30 min), P1D (1 day)</small>
    </div>
    <div class="col-md-6 mb-2">
        <label class="form-label text-dark">Estimated Cost</label>
        <input type="text"
               class="form-control section-setting-input"
               data-section="{{ $sectionKey }}"
               data-setting="seo_estimatedCost"
               value="{{ $section->getSetting('seo_estimatedCost', '') }}"
               placeholder="Free or PHP 500">
    </div>
</div>
@endif

@if($sectionKey === 'award')
<div class="row">
    <div class="col-md-4 mb-2">
        <label class="form-label text-dark">Award Name</label>
        <input type="text"
               class="form-control section-setting-input"
               data-section="{{ $sectionKey }}"
               data-setting="seo_awardName"
               value="{{ $section->getSetting('seo_awardName', '') }}"
               placeholder="Best Agricultural Technology">
    </div>
    <div class="col-md-4 mb-2">
        <label class="form-label text-dark">Award Year</label>
        <input type="text"
               class="form-control section-setting-input"
               data-section="{{ $sectionKey }}"
               data-setting="seo_awardYear"
               value="{{ $section->getSetting('seo_awardYear', '') }}"
               placeholder="2024">
    </div>
    <div class="col-md-4 mb-2">
        <label class="form-label text-dark">Awarding Body</label>
        <input type="text"
               class="form-control section-setting-input"
               data-section="{{ $sectionKey }}"
               data-setting="seo_awardOrg"
               value="{{ $section->getSetting('seo_awardOrg', '') }}"
               placeholder="Dept. of Agriculture">
    </div>
</div>
@endif
