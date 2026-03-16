{{-- Page-Level SEO Settings --}}
<div class="alert alert-info mb-4">
    <i class="bx bx-info-circle me-2"></i>
    <strong>Page-Level SEO</strong> - These settings apply to the entire homepage and will be rendered in the <code>&lt;head&gt;</code> section.
</div>

<div class="settings-card">
    <h6 class="text-dark mb-3"><i class="bx bx-search-alt me-2"></i>Meta Tags</h6>
    <div class="row">
        <div class="col-md-6 mb-2">
            <label class="form-label text-dark">Meta Title</label>
            <input type="text"
                   class="form-control section-setting-input"
                   data-section="page_seo"
                   data-setting="metaTitle"
                   value="{{ $section->getSetting('metaTitle', 'AniSenso - Maximizing Crop Yields for Filipino Farmers') }}"
                   maxlength="60">
            <small class="text-secondary">Recommended: 50-60 characters. Current: <span id="metaTitleCount">0</span>/60</small>
        </div>
        <div class="col-md-6 mb-2">
            <label class="form-label text-dark">Canonical URL</label>
            <input type="text"
                   class="form-control section-setting-input"
                   data-section="page_seo"
                   data-setting="canonicalUrl"
                   value="{{ $section->getSetting('canonicalUrl', '') }}"
                   placeholder="https://anisenso.com/">
            <small class="text-secondary">Leave empty to use current page URL</small>
        </div>
    </div>
    <div class="mb-2">
        <label class="form-label text-dark">Meta Description</label>
        <textarea class="form-control section-setting-input"
                  data-section="page_seo"
                  data-setting="metaDescription"
                  rows="2"
                  maxlength="160">{{ $section->getSetting('metaDescription', 'Join AniSenso to maximize your crop yields with expert courses, research-based fertilization, and 24/7 technician support. Trusted by Filipino farmers nationwide.') }}</textarea>
        <small class="text-secondary">Recommended: 150-160 characters. Current: <span id="metaDescCount">0</span>/160</small>
    </div>
    <div class="mb-2">
        <label class="form-label text-dark">Meta Keywords</label>
        <input type="text"
               class="form-control section-setting-input"
               data-section="page_seo"
               data-setting="metaKeywords"
               value="{{ $section->getSetting('metaKeywords', 'agriculture, farming, crop yield, fertilization, Filipino farmers, AniSenso, rice farming, palay, mais') }}">
        <small class="text-secondary">Comma-separated keywords (less important for modern SEO but still used by some search engines)</small>
    </div>
    <div class="row">
        <div class="col-md-6 mb-2">
            <label class="form-label text-dark">Robots Meta</label>
            <select class="form-select section-setting-input"
                    data-section="page_seo"
                    data-setting="robotsMeta">
                @php $robots = $section->getSetting('robotsMeta', 'index,follow'); @endphp
                <option value="index,follow" {{ $robots === 'index,follow' ? 'selected' : '' }}>Index, Follow (Recommended)</option>
                <option value="index,nofollow" {{ $robots === 'index,nofollow' ? 'selected' : '' }}>Index, No Follow</option>
                <option value="noindex,follow" {{ $robots === 'noindex,follow' ? 'selected' : '' }}>No Index, Follow</option>
                <option value="noindex,nofollow" {{ $robots === 'noindex,nofollow' ? 'selected' : '' }}>No Index, No Follow</option>
            </select>
        </div>
        <div class="col-md-6 mb-2">
            <label class="form-label text-dark">Language</label>
            <select class="form-select section-setting-input"
                    data-section="page_seo"
                    data-setting="language">
                @php $lang = $section->getSetting('language', 'en-PH'); @endphp
                <option value="en-PH" {{ $lang === 'en-PH' ? 'selected' : '' }}>English (Philippines)</option>
                <option value="en" {{ $lang === 'en' ? 'selected' : '' }}>English</option>
                <option value="tl" {{ $lang === 'tl' ? 'selected' : '' }}>Filipino/Tagalog</option>
            </select>
        </div>
    </div>
</div>

<div class="settings-card">
    <h6 class="text-dark mb-3"><i class="bx bxl-facebook-circle me-2"></i>Open Graph (Facebook, LinkedIn)</h6>
    <div class="row">
        <div class="col-md-6 mb-2">
            <label class="form-label text-dark">OG Title</label>
            <input type="text"
                   class="form-control section-setting-input"
                   data-section="page_seo"
                   data-setting="ogTitle"
                   value="{{ $section->getSetting('ogTitle', '') }}"
                   placeholder="Leave empty to use Meta Title">
        </div>
        <div class="col-md-6 mb-2">
            <label class="form-label text-dark">OG Type</label>
            <select class="form-select section-setting-input"
                    data-section="page_seo"
                    data-setting="ogType">
                @php $ogType = $section->getSetting('ogType', 'website'); @endphp
                <option value="website" {{ $ogType === 'website' ? 'selected' : '' }}>Website</option>
                <option value="article" {{ $ogType === 'article' ? 'selected' : '' }}>Article</option>
                <option value="business.business" {{ $ogType === 'business.business' ? 'selected' : '' }}>Business</option>
            </select>
        </div>
    </div>
    <div class="mb-2">
        <label class="form-label text-dark">OG Description</label>
        <textarea class="form-control section-setting-input"
                  data-section="page_seo"
                  data-setting="ogDescription"
                  rows="2"
                  placeholder="Leave empty to use Meta Description">{{ $section->getSetting('ogDescription', '') }}</textarea>
    </div>
    <div class="row">
        <div class="col-md-6 mb-2">
            <label class="form-label text-dark">OG Image URL</label>
            <input type="text"
                   class="form-control section-setting-input"
                   data-section="page_seo"
                   data-setting="ogImage"
                   value="{{ $section->getSetting('ogImage', '') }}"
                   placeholder="https://anisenso.com/images/og-image.jpg">
            <small class="text-secondary">Recommended: 1200x630 pixels</small>
        </div>
        <div class="col-md-6 mb-2">
            <label class="form-label text-dark">OG Image Alt Text</label>
            <input type="text"
                   class="form-control section-setting-input"
                   data-section="page_seo"
                   data-setting="ogImageAlt"
                   value="{{ $section->getSetting('ogImageAlt', '') }}"
                   placeholder="AniSenso - Agriculture Technology">
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-2">
            <label class="form-label text-dark">OG Site Name</label>
            <input type="text"
                   class="form-control section-setting-input"
                   data-section="page_seo"
                   data-setting="ogSiteName"
                   value="{{ $section->getSetting('ogSiteName', 'AniSenso') }}">
        </div>
        <div class="col-md-6 mb-2">
            <label class="form-label text-dark">OG Locale</label>
            <input type="text"
                   class="form-control section-setting-input"
                   data-section="page_seo"
                   data-setting="ogLocale"
                   value="{{ $section->getSetting('ogLocale', 'en_PH') }}"
                   placeholder="en_PH">
        </div>
    </div>
</div>

<div class="settings-card">
    <h6 class="text-dark mb-3"><i class="bx bxl-twitter me-2"></i>Twitter Card</h6>
    <div class="row">
        <div class="col-md-4 mb-2">
            <label class="form-label text-dark">Card Type</label>
            <select class="form-select section-setting-input"
                    data-section="page_seo"
                    data-setting="twitterCard">
                @php $twitterCard = $section->getSetting('twitterCard', 'summary_large_image'); @endphp
                <option value="summary" {{ $twitterCard === 'summary' ? 'selected' : '' }}>Summary</option>
                <option value="summary_large_image" {{ $twitterCard === 'summary_large_image' ? 'selected' : '' }}>Summary Large Image (Recommended)</option>
                <option value="player" {{ $twitterCard === 'player' ? 'selected' : '' }}>Player (Video)</option>
            </select>
        </div>
        <div class="col-md-4 mb-2">
            <label class="form-label text-dark">Twitter Site @username</label>
            <input type="text"
                   class="form-control section-setting-input"
                   data-section="page_seo"
                   data-setting="twitterSite"
                   value="{{ $section->getSetting('twitterSite', '') }}"
                   placeholder="@anisenso">
        </div>
        <div class="col-md-4 mb-2">
            <label class="form-label text-dark">Twitter Creator @username</label>
            <input type="text"
                   class="form-control section-setting-input"
                   data-section="page_seo"
                   data-setting="twitterCreator"
                   value="{{ $section->getSetting('twitterCreator', '') }}"
                   placeholder="@anisenso">
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-2">
            <label class="form-label text-dark">Twitter Title</label>
            <input type="text"
                   class="form-control section-setting-input"
                   data-section="page_seo"
                   data-setting="twitterTitle"
                   value="{{ $section->getSetting('twitterTitle', '') }}"
                   placeholder="Leave empty to use OG Title">
        </div>
        <div class="col-md-6 mb-2">
            <label class="form-label text-dark">Twitter Image URL</label>
            <input type="text"
                   class="form-control section-setting-input"
                   data-section="page_seo"
                   data-setting="twitterImage"
                   value="{{ $section->getSetting('twitterImage', '') }}"
                   placeholder="Leave empty to use OG Image">
        </div>
    </div>
</div>

<div class="settings-card">
    <h6 class="text-dark mb-3"><i class="bx bx-buildings me-2"></i>Organization Schema (JSON-LD)</h6>
    <div class="row">
        <div class="col-md-6 mb-2">
            <label class="form-label text-dark">Organization Name</label>
            <input type="text"
                   class="form-control section-setting-input"
                   data-section="page_seo"
                   data-setting="orgName"
                   value="{{ $section->getSetting('orgName', 'AniSenso') }}">
        </div>
        <div class="col-md-6 mb-2">
            <label class="form-label text-dark">Organization Type</label>
            <select class="form-select section-setting-input"
                    data-section="page_seo"
                    data-setting="orgType">
                @php $orgType = $section->getSetting('orgType', 'Organization'); @endphp
                <option value="Organization" {{ $orgType === 'Organization' ? 'selected' : '' }}>Organization</option>
                <option value="Corporation" {{ $orgType === 'Corporation' ? 'selected' : '' }}>Corporation</option>
                <option value="LocalBusiness" {{ $orgType === 'LocalBusiness' ? 'selected' : '' }}>Local Business</option>
                <option value="EducationalOrganization" {{ $orgType === 'EducationalOrganization' ? 'selected' : '' }}>Educational Organization</option>
                <option value="NGO" {{ $orgType === 'NGO' ? 'selected' : '' }}>NGO / Non-Profit</option>
            </select>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-2">
            <label class="form-label text-dark">Organization Logo URL</label>
            <input type="text"
                   class="form-control section-setting-input"
                   data-section="page_seo"
                   data-setting="orgLogo"
                   value="{{ $section->getSetting('orgLogo', '') }}"
                   placeholder="https://anisenso.com/images/logo.png">
        </div>
        <div class="col-md-6 mb-2">
            <label class="form-label text-dark">Organization URL</label>
            <input type="text"
                   class="form-control section-setting-input"
                   data-section="page_seo"
                   data-setting="orgUrl"
                   value="{{ $section->getSetting('orgUrl', '') }}"
                   placeholder="https://anisenso.com">
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 mb-2">
            <label class="form-label text-dark">Contact Phone</label>
            <input type="text"
                   class="form-control section-setting-input"
                   data-section="page_seo"
                   data-setting="contactPhone"
                   value="{{ $section->getSetting('contactPhone', '') }}"
                   placeholder="+63 912 345 6789">
        </div>
        <div class="col-md-4 mb-2">
            <label class="form-label text-dark">Contact Email</label>
            <input type="text"
                   class="form-control section-setting-input"
                   data-section="page_seo"
                   data-setting="contactEmail"
                   value="{{ $section->getSetting('contactEmail', '') }}"
                   placeholder="info@anisenso.com">
        </div>
        <div class="col-md-4 mb-2">
            <label class="form-label text-dark">Founded Year</label>
            <input type="number"
                   class="form-control section-setting-input"
                   data-section="page_seo"
                   data-setting="foundedYear"
                   value="{{ $section->getSetting('foundedYear', '') }}"
                   placeholder="1980">
        </div>
    </div>
    <div class="mb-2">
        <label class="form-label text-dark">Address (Street, City, Region, Country)</label>
        <input type="text"
               class="form-control section-setting-input"
               data-section="page_seo"
               data-setting="address"
               value="{{ $section->getSetting('address', '') }}"
               placeholder="123 Agriculture St., Quezon City, Metro Manila, Philippines">
    </div>
    <div class="mb-2">
        <label class="form-label text-dark">Social Media URLs (one per line)</label>
        <textarea class="form-control section-setting-input"
                  data-section="page_seo"
                  data-setting="socialUrls"
                  rows="4"
                  placeholder="https://facebook.com/anisenso&#10;https://twitter.com/anisenso&#10;https://instagram.com/anisenso&#10;https://youtube.com/anisenso">{{ $section->getSetting('socialUrls', '') }}</textarea>
        <small class="text-secondary">Used in Organization schema's sameAs property</small>
    </div>
</div>

<div class="settings-card">
    <h6 class="text-dark mb-3"><i class="bx bx-globe me-2"></i>WebSite Schema</h6>
    <div class="row">
        <div class="col-md-6 mb-2">
            <label class="form-label text-dark">Site Name</label>
            <input type="text"
                   class="form-control section-setting-input"
                   data-section="page_seo"
                   data-setting="siteName"
                   value="{{ $section->getSetting('siteName', 'AniSenso') }}">
        </div>
        <div class="col-md-6 mb-2">
            <label class="form-label text-dark">Site Alternate Name</label>
            <input type="text"
                   class="form-control section-setting-input"
                   data-section="page_seo"
                   data-setting="siteAlternateName"
                   value="{{ $section->getSetting('siteAlternateName', '') }}"
                   placeholder="AniSenso Academy">
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-2">
            <label class="form-label text-dark">Enable Site Search Box</label>
            <select class="form-select section-setting-input"
                    data-section="page_seo"
                    data-setting="enableSearchBox">
                @php $enableSearch = $section->getSetting('enableSearchBox', '1'); @endphp
                <option value="1" {{ $enableSearch === '1' ? 'selected' : '' }}>Yes</option>
                <option value="0" {{ $enableSearch === '0' ? 'selected' : '' }}>No</option>
            </select>
            <small class="text-secondary">Shows sitelinks search box in Google results</small>
        </div>
        <div class="col-md-6 mb-2">
            <label class="form-label text-dark">Search URL Template</label>
            <input type="text"
                   class="form-control section-setting-input"
                   data-section="page_seo"
                   data-setting="searchUrlTemplate"
                   value="{{ $section->getSetting('searchUrlTemplate', '') }}"
                   placeholder="https://anisenso.com/search?q={search_term_string}">
        </div>
    </div>
</div>

<div class="settings-card">
    <h6 class="text-dark mb-3"><i class="bx bx-code-block me-2"></i>Generated Schema Preview</h6>
    <div class="mb-2">
        <button type="button" class="btn btn-soft-primary btn-sm" onclick="generateFullSchemaPreview()">
            <i class="bx bx-refresh me-1"></i>Generate Preview
        </button>
        <button type="button" class="btn btn-soft-secondary btn-sm ms-2" onclick="copySchemaToClipboard()">
            <i class="bx bx-copy me-1"></i>Copy to Clipboard
        </button>
    </div>
    <pre id="fullSchemaPreview" class="bg-light p-3 rounded" style="font-size: 11px; max-height: 400px; overflow-y: auto;"><code>Click "Generate Preview" to see the schema markup</code></pre>
</div>

<script>
(function waitForJQuery() {
    if (typeof $ === 'undefined' || typeof jQuery === 'undefined') {
        setTimeout(waitForJQuery, 50);
        return;
    }
    initPageSeoScripts();
})();

function initPageSeoScripts() {
window.generateFullSchemaPreview = function() {
    const getVal = (key) => {
        const input = $(`.section-setting-input[data-section="page_seo"][data-setting="${key}"]`);
        return input.val() || '';
    };

    const baseUrl = getVal('orgUrl') || window.location.origin;

    // Organization Schema
    const orgSchema = {
        "@context": "https://schema.org",
        "@type": getVal('orgType') || "Organization",
        "name": getVal('orgName'),
        "url": baseUrl,
        "logo": getVal('orgLogo') || undefined,
        "contactPoint": {
            "@type": "ContactPoint",
            "telephone": getVal('contactPhone') || undefined,
            "email": getVal('contactEmail') || undefined,
            "contactType": "customer service"
        },
        "address": getVal('address') ? {
            "@type": "PostalAddress",
            "streetAddress": getVal('address')
        } : undefined,
        "foundingDate": getVal('foundedYear') || undefined,
        "sameAs": getVal('socialUrls') ? getVal('socialUrls').split('\n').filter(u => u.trim()) : undefined
    };

    // WebSite Schema
    const websiteSchema = {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": getVal('siteName'),
        "alternateName": getVal('siteAlternateName') || undefined,
        "url": baseUrl
    };

    if (getVal('enableSearchBox') === '1' && getVal('searchUrlTemplate')) {
        websiteSchema.potentialAction = {
            "@type": "SearchAction",
            "target": {
                "@type": "EntryPoint",
                "urlTemplate": getVal('searchUrlTemplate')
            },
            "query-input": "required name=search_term_string"
        };
    }

    // WebPage Schema
    const webpageSchema = {
        "@context": "https://schema.org",
        "@type": "WebPage",
        "name": getVal('metaTitle'),
        "description": getVal('metaDescription'),
        "url": getVal('canonicalUrl') || baseUrl,
        "inLanguage": getVal('language') || "en-PH",
        "isPartOf": {
            "@type": "WebSite",
            "name": getVal('siteName'),
            "url": baseUrl
        }
    };

    // Clean undefined values
    const cleanSchema = (obj) => {
        Object.keys(obj).forEach(key => {
            if (obj[key] === undefined || obj[key] === '' ||
                (typeof obj[key] === 'object' && !Array.isArray(obj[key]) && Object.keys(obj[key]).every(k => obj[key][k] === undefined))) {
                delete obj[key];
            } else if (typeof obj[key] === 'object' && !Array.isArray(obj[key])) {
                cleanSchema(obj[key]);
            }
        });
        return obj;
    };

    const fullSchema = {
        "@context": "https://schema.org",
        "@graph": [
            cleanSchema(orgSchema),
            cleanSchema(websiteSchema),
            cleanSchema(webpageSchema)
        ]
    };

    $('#fullSchemaPreview code').text(JSON.stringify(fullSchema, null, 2));
}

window.copySchemaToClipboard = function() {
    const schemaText = $('#fullSchemaPreview code').text();
    navigator.clipboard.writeText(schemaText).then(() => {
        toastr.success('Schema copied to clipboard!');
    }).catch(() => {
        toastr.error('Failed to copy');
    });
};

// Character counters
$(document).ready(function() {
    const updateCount = (inputSelector, countSelector, max) => {
        const len = $(inputSelector).val().length;
        $(countSelector).text(len);
        $(countSelector).css('color', len > max ? '#dc3545' : '#6c757d');
    };

    $('[data-setting="metaTitle"]').on('input', function() {
        updateCount(this, '#metaTitleCount', 60);
    }).trigger('input');

    $('[data-setting="metaDescription"]').on('input', function() {
        updateCount(this, '#metaDescCount', 160);
    }).trigger('input');
});
} // end initPageSeoScripts
</script>
