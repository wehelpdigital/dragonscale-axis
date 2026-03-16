{{-- Reusable SEO Settings Partial --}}
{{-- Usage: @include('aniSensoAdmin.homepage-settings.partials._seo-settings', ['section' => $section, 'sectionKey' => 'hero']) --}}

<div class="settings-card">
    <h6 class="text-dark mb-3">
        <i class="bx bx-search-alt me-2"></i>SEO Settings
        <span class="badge bg-info text-white ms-2">Schema.org</span>
    </h6>

    {{-- Image Alt Texts --}}
    @if(in_array($sectionKey, ['hero', 'award', 'about', 'partners', 'before_after', 'success_stories', 'seasonal_cta']))
    <div class="mb-2">
        <label class="form-label text-dark">
            <i class="bx bx-image-alt me-1"></i>Image Alt Text
        </label>
        <input type="text"
               class="form-control section-setting-input"
               data-section="{{ $sectionKey }}"
               data-setting="seo_imageAlt"
               value="{{ $section->getSetting('seo_imageAlt', '') }}"
               placeholder="Descriptive text for screen readers and SEO">
        <small class="text-secondary">Describes the main image for accessibility and search engines</small>
    </div>
    @endif

    {{-- Section Title for SEO --}}
    <div class="mb-2">
        <label class="form-label text-dark">
            <i class="bx bx-heading me-1"></i>SEO Section Title
        </label>
        <input type="text"
               class="form-control section-setting-input"
               data-section="{{ $sectionKey }}"
               data-setting="seo_title"
               value="{{ $section->getSetting('seo_title', '') }}"
               placeholder="Title for search engine snippets">
        <small class="text-secondary">Used in schema markup and meta tags</small>
    </div>

    {{-- Section Description for SEO --}}
    <div class="mb-2">
        <label class="form-label text-dark">
            <i class="bx bx-detail me-1"></i>SEO Description
        </label>
        <textarea class="form-control section-setting-input"
                  data-section="{{ $sectionKey }}"
                  data-setting="seo_description"
                  rows="2"
                  placeholder="Brief description for search engines (150-160 characters recommended)">{{ $section->getSetting('seo_description', '') }}</textarea>
        <small class="text-secondary">Appears in search results. Keep it concise and compelling.</small>
    </div>

    {{-- Keywords --}}
    <div class="mb-2">
        <label class="form-label text-dark">
            <i class="bx bx-purchase-tag me-1"></i>Keywords
        </label>
        <input type="text"
               class="form-control section-setting-input"
               data-section="{{ $sectionKey }}"
               data-setting="seo_keywords"
               value="{{ $section->getSetting('seo_keywords', '') }}"
               placeholder="keyword1, keyword2, keyword3">
        <small class="text-secondary">Comma-separated keywords related to this section</small>
    </div>

    {{-- Schema Type Selection --}}
    <div class="row">
        <div class="col-md-6 mb-2">
            <label class="form-label text-dark">
                <i class="bx bx-code-alt me-1"></i>Schema Type
            </label>
            <select class="form-select section-setting-input"
                    data-section="{{ $sectionKey }}"
                    data-setting="seo_schemaType">
                @php
                    $schemaTypes = [
                        'hero' => ['WebPage', 'Organization', 'LocalBusiness', 'EducationalOrganization'],
                        'award' => ['Organization', 'Award', 'AggregateRating'],
                        'about' => ['AboutPage', 'Organization', 'Service'],
                        'partners' => ['Organization', 'ItemList'],
                        'before_after' => ['ImageGallery', 'ItemList', 'HowTo'],
                        'seasonal_cta' => ['Offer', 'Product', 'Event'],
                        'process' => ['HowTo', 'ItemList', 'Course'],
                        'success_stories' => ['Article', 'NewsArticle', 'Review'],
                        'testimonials' => ['Review', 'AggregateRating', 'ItemList'],
                        'hero_features' => ['ItemList', 'Service', 'OfferCatalog'],
                    ];
                    $options = $schemaTypes[$sectionKey] ?? ['WebPageElement', 'Thing'];
                    $currentSchema = $section->getSetting('seo_schemaType', $options[0] ?? 'Thing');
                @endphp
                @foreach($options as $option)
                    <option value="{{ $option }}" {{ $currentSchema === $option ? 'selected' : '' }}>
                        {{ $option }}
                    </option>
                @endforeach
            </select>
            <small class="text-secondary">Schema.org type for structured data</small>
        </div>
        <div class="col-md-6 mb-2">
            <label class="form-label text-dark">
                <i class="bx bx-globe me-1"></i>Canonical URL
            </label>
            <input type="text"
                   class="form-control section-setting-input"
                   data-section="{{ $sectionKey }}"
                   data-setting="seo_canonicalUrl"
                   value="{{ $section->getSetting('seo_canonicalUrl', '') }}"
                   placeholder="https://example.com/page#section">
            <small class="text-secondary">Optional canonical URL with anchor</small>
        </div>
    </div>

    {{-- Open Graph Settings --}}
    <div class="border-top pt-3 mt-2">
        <h6 class="text-dark mb-3">
            <i class="bx bxl-facebook-circle me-2"></i>Open Graph (Social Sharing)
        </h6>
        <div class="row">
            <div class="col-md-6 mb-2">
                <label class="form-label text-dark">OG Title</label>
                <input type="text"
                       class="form-control section-setting-input"
                       data-section="{{ $sectionKey }}"
                       data-setting="seo_ogTitle"
                       value="{{ $section->getSetting('seo_ogTitle', '') }}"
                       placeholder="Title for Facebook/LinkedIn sharing">
            </div>
            <div class="col-md-6 mb-2">
                <label class="form-label text-dark">OG Image URL</label>
                <input type="text"
                       class="form-control section-setting-input"
                       data-section="{{ $sectionKey }}"
                       data-setting="seo_ogImage"
                       value="{{ $section->getSetting('seo_ogImage', '') }}"
                       placeholder="https://example.com/image.jpg">
            </div>
        </div>
        <div class="mb-2">
            <label class="form-label text-dark">OG Description</label>
            <textarea class="form-control section-setting-input"
                      data-section="{{ $sectionKey }}"
                      data-setting="seo_ogDescription"
                      rows="2"
                      placeholder="Description for social media sharing">{{ $section->getSetting('seo_ogDescription', '') }}</textarea>
        </div>
    </div>

    {{-- Twitter Card Settings --}}
    <div class="border-top pt-3 mt-2">
        <h6 class="text-dark mb-3">
            <i class="bx bxl-twitter me-2"></i>Twitter Card
        </h6>
        <div class="row">
            <div class="col-md-4 mb-2">
                <label class="form-label text-dark">Card Type</label>
                <select class="form-select section-setting-input"
                        data-section="{{ $sectionKey }}"
                        data-setting="seo_twitterCard">
                    @php $twitterCard = $section->getSetting('seo_twitterCard', 'summary_large_image'); @endphp
                    <option value="summary" {{ $twitterCard === 'summary' ? 'selected' : '' }}>Summary</option>
                    <option value="summary_large_image" {{ $twitterCard === 'summary_large_image' ? 'selected' : '' }}>Summary Large Image</option>
                    <option value="player" {{ $twitterCard === 'player' ? 'selected' : '' }}>Player (Video)</option>
                </select>
            </div>
            <div class="col-md-4 mb-2">
                <label class="form-label text-dark">Twitter Title</label>
                <input type="text"
                       class="form-control section-setting-input"
                       data-section="{{ $sectionKey }}"
                       data-setting="seo_twitterTitle"
                       value="{{ $section->getSetting('seo_twitterTitle', '') }}"
                       placeholder="Title for Twitter">
            </div>
            <div class="col-md-4 mb-2">
                <label class="form-label text-dark">Twitter Image URL</label>
                <input type="text"
                       class="form-control section-setting-input"
                       data-section="{{ $sectionKey }}"
                       data-setting="seo_twitterImage"
                       value="{{ $section->getSetting('seo_twitterImage', '') }}"
                       placeholder="https://example.com/image.jpg">
            </div>
        </div>
    </div>

    {{-- Section-Specific Schema Fields --}}
    @if($sectionKey === 'testimonials' || $sectionKey === 'success_stories')
    <div class="border-top pt-3 mt-2">
        <h6 class="text-dark mb-3">
            <i class="bx bx-star me-2"></i>Review/Rating Schema
        </h6>
        <div class="row">
            <div class="col-md-4 mb-2">
                <label class="form-label text-dark">Aggregate Rating</label>
                <input type="number"
                       class="form-control section-setting-input"
                       data-section="{{ $sectionKey }}"
                       data-setting="seo_aggregateRating"
                       value="{{ $section->getSetting('seo_aggregateRating', '4.8') }}"
                       min="1" max="5" step="0.1"
                       placeholder="4.8">
            </div>
            <div class="col-md-4 mb-2">
                <label class="form-label text-dark">Review Count</label>
                <input type="number"
                       class="form-control section-setting-input"
                       data-section="{{ $sectionKey }}"
                       data-setting="seo_reviewCount"
                       value="{{ $section->getSetting('seo_reviewCount', '') }}"
                       placeholder="150">
            </div>
            <div class="col-md-4 mb-2">
                <label class="form-label text-dark">Best Rating</label>
                <input type="number"
                       class="form-control section-setting-input"
                       data-section="{{ $sectionKey }}"
                       data-setting="seo_bestRating"
                       value="{{ $section->getSetting('seo_bestRating', '5') }}"
                       placeholder="5">
            </div>
        </div>
    </div>
    @endif

    @if($sectionKey === 'process')
    <div class="border-top pt-3 mt-2">
        <h6 class="text-dark mb-3">
            <i class="bx bx-list-ol me-2"></i>HowTo Schema
        </h6>
        <div class="row">
            <div class="col-md-6 mb-2">
                <label class="form-label text-dark">Total Time (e.g., PT30M, P1D)</label>
                <input type="text"
                       class="form-control section-setting-input"
                       data-section="{{ $sectionKey }}"
                       data-setting="seo_totalTime"
                       value="{{ $section->getSetting('seo_totalTime', '') }}"
                       placeholder="PT30M (30 minutes) or P1D (1 day)">
                <small class="text-secondary">ISO 8601 duration format</small>
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
    </div>
    @endif

    @if($sectionKey === 'hero' || $sectionKey === 'about')
    <div class="border-top pt-3 mt-2">
        <h6 class="text-dark mb-3">
            <i class="bx bx-buildings me-2"></i>Organization Schema
        </h6>
        <div class="row">
            <div class="col-md-6 mb-2">
                <label class="form-label text-dark">Organization Name</label>
                <input type="text"
                       class="form-control section-setting-input"
                       data-section="{{ $sectionKey }}"
                       data-setting="seo_orgName"
                       value="{{ $section->getSetting('seo_orgName', 'AniSenso') }}"
                       placeholder="AniSenso">
            </div>
            <div class="col-md-6 mb-2">
                <label class="form-label text-dark">Organization Logo URL</label>
                <input type="text"
                       class="form-control section-setting-input"
                       data-section="{{ $sectionKey }}"
                       data-setting="seo_orgLogo"
                       value="{{ $section->getSetting('seo_orgLogo', '') }}"
                       placeholder="https://example.com/logo.png">
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-2">
                <label class="form-label text-dark">Contact Phone</label>
                <input type="text"
                       class="form-control section-setting-input"
                       data-section="{{ $sectionKey }}"
                       data-setting="seo_contactPhone"
                       value="{{ $section->getSetting('seo_contactPhone', '') }}"
                       placeholder="+63 912 345 6789">
            </div>
            <div class="col-md-6 mb-2">
                <label class="form-label text-dark">Contact Email</label>
                <input type="text"
                       class="form-control section-setting-input"
                       data-section="{{ $sectionKey }}"
                       data-setting="seo_contactEmail"
                       value="{{ $section->getSetting('seo_contactEmail', '') }}"
                       placeholder="info@anisenso.com">
            </div>
        </div>
        <div class="mb-2">
            <label class="form-label text-dark">Social Media URLs (one per line)</label>
            <textarea class="form-control section-setting-input"
                      data-section="{{ $sectionKey }}"
                      data-setting="seo_socialUrls"
                      rows="3"
                      placeholder="https://facebook.com/anisenso&#10;https://twitter.com/anisenso&#10;https://instagram.com/anisenso">{{ $section->getSetting('seo_socialUrls', '') }}</textarea>
        </div>
    </div>
    @endif

    @if($sectionKey === 'award')
    <div class="border-top pt-3 mt-2">
        <h6 class="text-dark mb-3">
            <i class="bx bx-trophy me-2"></i>Award Schema
        </h6>
        <div class="row">
            <div class="col-md-6 mb-2">
                <label class="form-label text-dark">Award Name</label>
                <input type="text"
                       class="form-control section-setting-input"
                       data-section="{{ $sectionKey }}"
                       data-setting="seo_awardName"
                       value="{{ $section->getSetting('seo_awardName', '') }}"
                       placeholder="Best Agricultural Technology Award">
            </div>
            <div class="col-md-6 mb-2">
                <label class="form-label text-dark">Award Date</label>
                <input type="date"
                       class="form-control section-setting-input"
                       data-section="{{ $sectionKey }}"
                       data-setting="seo_awardDate"
                       value="{{ $section->getSetting('seo_awardDate', '') }}">
            </div>
        </div>
        <div class="mb-2">
            <label class="form-label text-dark">Awarding Organization</label>
            <input type="text"
                   class="form-control section-setting-input"
                   data-section="{{ $sectionKey }}"
                   data-setting="seo_awardOrg"
                   value="{{ $section->getSetting('seo_awardOrg', '') }}"
                   placeholder="Department of Agriculture">
        </div>
    </div>
    @endif

    {{-- Structured Data Preview --}}
    <div class="border-top pt-3 mt-2">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="text-dark mb-0">
                <i class="bx bx-code-block me-2"></i>Schema Preview
            </h6>
            <button type="button" class="btn btn-sm btn-soft-secondary" onclick="toggleSchemaPreview('{{ $sectionKey }}')">
                <i class="bx bx-show me-1"></i>Toggle Preview
            </button>
        </div>
        <div id="schemaPreview_{{ $sectionKey }}" class="d-none">
            <pre class="bg-light p-3 rounded" style="font-size: 11px; max-height: 200px; overflow-y: auto;"><code id="schemaCode_{{ $sectionKey }}">Loading...</code></pre>
        </div>
    </div>
</div>
