{{-- Drag-drop content block builder.
     Required variables: $ownerType, $ownerId
     Optional: $allowed (array of block types)
     Optional: $focusBlockId (int) — when set, the builder filters to
               that one block + hides the add-block toolbar, used by
               the Live Editor's "edit one block" modal. --}}
@php
    $allowed = $allowed ?? [
        // Generic blocks.
        'heading','rich_text','image','gallery','video','faq','cta','two_column',
        'listing_slot','quote','divider','custom_html',
        // Resort Guru custom elements that match what the seeded keyword
        // and food pages already ship today.
        'hero_slider','quick_facts','editor_rating','listing_block',
        'attractions','how_to_get_to','text_section',
        // Standardized section templates covering more patterns from the
        // seeded pages so admins never have to drop into custom_html for
        // common layouts.
        'short_version','pros_cons','summary_accordion','image_text_pair',
        'traveler_reviews','map_embed','local_tip','related_guides','data_table',
        // Section opener (H2 + small lede), replaces the hardcoded
        // "What's in [Area]?" header on keyword pages so it's editable.
        'section_header',
        // Inline patterns extracted from seeded prose: cuisine/category
        // tag pills + the "Compare picks on third-party guides" grid.
        'tag_pills', 'external_guides',
        // Bottom-of-page author/byline card with avatar + bio + socials.
        'author',
        // Cross-vertical recommendation strips for food / restaurant pages.
        'nearby_destinations', 'related_blogs',
        // Vertical-list variant of quick_facts used on destination pages.
        'facts_list',
        // Researched origin / history of a venue or district (sourced
        // from web research, NOT invented prose).
        'place_history',
        // Page-header content elements migrated out of rg_seo_pages
        // columns into blocks so the admin can reorder / remove / add
        // them like any other content. subtitle_intro replaces the
        // hardcoded italic line under H1; tldr_card + wwww_card port
        // the partials/summary-blocks accordion into two independent
        // collapsible blocks.
        'subtitle_intro','tldr_card','wwww_card',
        // Page-top hardcoded includes converted to blocks. social_share
        // is the share row; we_recommend_band is the "We Recommend"
        // listings band header + the listings partial (branches on
        // keyword.category — restaurants vs resorts).
        'social_share','we_recommend_band',
        // Page-tail hardcoded sections converted to blocks. Each
        // only fires when its data is non-empty + the keyword
        // category matches.
        'restaurant_recs_band','adventures_band','reviews_band',
        // /destinations page custom block types — see frontend
        // BlockRenderer::destHeroSearch / destFeaturedSlider /
        // destRegionClusters. These read live data from the
        // DestinationsController context (orderedClusters / stats
        // / featuredSpots / searchIndex).
        'dest_hero_search','dest_featured_slider','dest_region_clusters',
        // Destination CLUSTER page blocks (shared destination-cluster template).
        'destcluster_hero','destcluster_whats_in','destcluster_featured_spots',
        'destcluster_testimonials','destcluster_explore_regions','destcluster_hashtags',
        // Homepage custom block types.
        'home_hero_centered','home_keyword_grid','home_region_grid',
        'home_resort_grid','home_blog_strip','home_cta_band',
        // Phase-2 homepage blocks.
        'home_unified_search','home_editorial_intro','home_experience_grid',
        'home_hub_links','home_season_guide','home_testimonials',
        'home_faq','home_values_grid',
        // Newer homepage blocks added after the initial build.
        'home_press_logos','home_category_accordion','home_alt_features',
        'home_badge_explainer','home_partner_perks','home_keyword_hashtags',
        'home_how_it_works',
        // Hub-page blocks (foods/activities/buys/cultures).
        'hub_hero','hub_category_nav','hub_category_grid','hub_footer_rail',
        // Partner-program blocks (the /become-a-partner page).
        'partner_hero','partner_audience','partner_steps','partner_badge','partner_perks',
    ];
    $labels = [
        'heading'=>'Heading','rich_text'=>'Rich Text','image'=>'Image','gallery'=>'Gallery',
        'video'=>'Video','faq'=>'FAQ','cta'=>'Call to Action','two_column'=>'Two Columns',
        'listing_slot'=>'Listing Slot','quote'=>'Quote','divider'=>'Divider','custom_html'=>'Custom HTML',
        'hero_slider'=>'Hero Slider','quick_facts'=>'Quick Facts','editor_rating'=>'Editor Rating',
        'listing_block'=>'Listings Band','attractions'=>'Attractions','how_to_get_to'=>'How to Get There',
        'text_section'=>'Text Section',
        'short_version'=>'Short Version','pros_cons'=>'Best for / Skip if',
        'summary_accordion'=>'Summary Accordion','image_text_pair'=>'Image + Text',
        'traveler_reviews'=>'Traveler Reviews','map_embed'=>'Google Map',
        'local_tip'=>'Local Tip','related_guides'=>'Related Guides',
        'data_table'=>'Data Table',
        'section_header'=>'Section Header',
        'tag_pills'=>'Tag Pills','external_guides'=>'External Guides',
        'author'=>'Author / Byline',
        'nearby_destinations'=>'Nearby Destinations',
        'related_blogs'=>'Related Blog Posts',
        'facts_list'=>'Facts List (vertical)',
        'place_history'=>'Place History',
        'subtitle_intro'=>'Subtitle (italic)',
        'tldr_card'=>'TL;DR Card',
        'wwww_card'=>'WWWW Card',
        'social_share'=>'Social Share Row',
        'we_recommend_band'=>'"We Recommend" Listings Band',
        'restaurant_recs_band'=>'Restaurant Recommendations Band',
        'adventures_band'=>'Memorable Adventures Band',
        'reviews_band'=>'Reviews Band',
        'dest_hero_search'=>'Destinations Hero + Search',
        'dest_featured_slider'=>'Destinations Featured Slider',
        'dest_region_clusters'=>'Destinations Region Clusters',
        'destcluster_hero'=>'Cluster Hero (region name + photo bg)',
        'destcluster_whats_in'=>"Cluster: What's In (intro + keyword cards)",
        'destcluster_featured_spots'=>'Cluster: Featured Tourist Spots',
        'destcluster_testimonials'=>'Cluster: Traveler Reviews',
        'destcluster_explore_regions'=>'Cluster: Explore Other Regions',
        'destcluster_hashtags'=>'Cluster: Tags',
        'home_hero_centered'=>'Home Hero (centered + stats)',
        'home_keyword_grid'=>'Home Keyword Grid (popular destinations)',
        'home_region_grid'=>'Home Region Grid',
        'home_resort_grid'=>'Home Resort Grid (featured properties)',
        'home_blog_strip'=>'Home Blog Strip',
        'home_cta_band'=>'Home CTA Band (full-bleed color)',
        'home_unified_search'=>'Home Unified Search (hero + typeahead)',
        'home_editorial_intro'=>'Home Editorial Intro (heading + paragraphs)',
        'home_experience_grid'=>'Home Experience Grid (6 image tiles)',
        'home_hub_links'=>'Home Hub Links (4 icon cards)',
        'home_season_guide'=>'Home Season Guide (3 weather cards)',
        'home_testimonials'=>'Testimonials (review cards)',
        'home_faq'=>'FAQ (accordion + JSON-LD)',
        'home_how_it_works'=>'Home How It Works (expandable accordion)',
        'home_values_grid'=>'Home Values Grid (principles)',
        'home_press_logos'=>'Home Press Logos ("As Seen On")',
        'home_category_accordion'=>'Home Category Accordion (Find Your Kind of Trip)',
        'home_alt_features'=>'Home Alt Features (alternating image rows)',
        'home_badge_explainer'=>'Home Badge Explainer (Recommended Badge)',
        'home_partner_perks'=>'Home Partner Perks (Become a Partner)',
        'home_keyword_hashtags'=>'Keyword Hashtags (tag cloud)',
        'hub_hero'=>'Hub Hero (eyebrow + colored H1)',
        'hub_category_nav'=>'Hub Category Nav (sticky jump pills)',
        'hub_category_grid'=>'Hub Category Grid (collapsible items)',
        'hub_footer_rail'=>'Hub Footer Rail (related links)',
        'partner_hero'=>'Partner Hero (split + verified badge)',
        'partner_audience'=>'Partner: Who Can Join (icon cards)',
        'partner_steps'=>'Partner: How It Works (1-2-3)',
        'partner_badge'=>'Partner: Verified Badge (image + checklist)',
        'partner_perks'=>'Partner: Perks Grid',
    ];
    $icons = [
        'heading'=>'bx-heading','rich_text'=>'bx-text','image'=>'bx-image','gallery'=>'bx-images',
        'video'=>'bx-video','faq'=>'bx-help-circle','cta'=>'bx-mouse-alt','two_column'=>'bx-columns',
        'listing_slot'=>'bx-trophy','quote'=>'bx-quote-alt-left','divider'=>'bx-minus','custom_html'=>'bx-code-alt',
        'hero_slider'=>'bx-slideshow','quick_facts'=>'bx-info-square','editor_rating'=>'bx-star',
        'listing_block'=>'bx-list-ul','attractions'=>'bx-map-pin','how_to_get_to'=>'bx-bus',
        'text_section'=>'bx-paragraph',
        'short_version'=>'bx-zap','pros_cons'=>'bx-list-check',
        'summary_accordion'=>'bx-chevron-down-square','image_text_pair'=>'bx-image-alt',
        'traveler_reviews'=>'bx-message-rounded-detail','map_embed'=>'bx-map',
        'local_tip'=>'bx-bulb','related_guides'=>'bx-link-external',
        'data_table'=>'bx-table',
        'section_header'=>'bx-heading',
        'tag_pills'=>'bx-purchase-tag','external_guides'=>'bx-link',
        'author'=>'bx-user-pin',
        'nearby_destinations'=>'bx-map-alt',
        'related_blogs'=>'bx-news',
        'facts_list'=>'bx-list-ol',
        'place_history'=>'bx-history',
        'subtitle_intro'=>'bx-italic',
        'tldr_card'=>'bx-bulb',
        'wwww_card'=>'bx-collection',
        'social_share'=>'bx-share-alt',
        'we_recommend_band'=>'bx-trophy',
        'restaurant_recs_band'=>'bx-restaurant',
        'adventures_band'=>'bx-compass',
        'reviews_band'=>'bx-star',
        'dest_hero_search'=>'bx-search-alt-2',
        'dest_featured_slider'=>'bx-carousel',
        'dest_region_clusters'=>'bx-map-alt',
        'destcluster_hero'=>'bx-map-pin',
        'destcluster_whats_in'=>'bx-grid-alt',
        'destcluster_featured_spots'=>'bx-images',
        'destcluster_testimonials'=>'bx-chat',
        'destcluster_explore_regions'=>'bx-map',
        'destcluster_hashtags'=>'bx-hash',
        'home_hero_centered'=>'bx-home-heart',
        'home_keyword_grid'=>'bx-grid-alt',
        'home_region_grid'=>'bx-map',
        'home_resort_grid'=>'bx-building-house',
        'home_blog_strip'=>'bx-news',
        'home_cta_band'=>'bx-megaphone',
        'home_unified_search'=>'bx-search-alt-2',
        'home_editorial_intro'=>'bx-pen',
        'home_experience_grid'=>'bx-image',
        'home_hub_links'=>'bx-link-alt',
        'home_season_guide'=>'bx-calendar',
        'home_testimonials'=>'bx-chat',
        'home_faq'=>'bx-help-circle',
        'home_how_it_works'=>'bx-list-check',
        'home_values_grid'=>'bx-medal',
        'home_press_logos'=>'bx-bookmark-star',
        'home_category_accordion'=>'bx-collapse',
        'home_alt_features'=>'bx-layout',
        'home_badge_explainer'=>'bx-badge-check',
        'home_partner_perks'=>'bx-handshake',
        'home_keyword_hashtags'=>'bx-hash',
        'hub_hero'=>'bx-heading',
        'hub_category_nav'=>'bx-menu',
        'hub_category_grid'=>'bx-grid',
        'hub_footer_rail'=>'bx-link-external',
        'partner_hero'=>'bx-handshake',
        'partner_audience'=>'bx-group',
        'partner_steps'=>'bx-list-ol',
        'partner_badge'=>'bx-badge-check',
        'partner_perks'=>'bx-gift',
    ];
    // Group the toolbar into Generic / Custom so the palette stays scannable
    // as the type list grows. `rich_text` is deliberately omitted from the
    // toolbar — text_section replaces it for new authoring, but the type
    // stays in $allowed so any pre-migration blocks still render.
    $genericTypes = ['heading','image','gallery','video','faq','cta','two_column','listing_slot','quote','divider','custom_html'];
    $customTypes = [
        'hero_slider','quick_facts','editor_rating','listing_block',
        'attractions','how_to_get_to','text_section',
        'short_version','pros_cons','summary_accordion','image_text_pair',
        'traveler_reviews','map_embed','local_tip','related_guides','data_table',
        'section_header','tag_pills','external_guides','author',
        'nearby_destinations','related_blogs','facts_list','place_history',
        'subtitle_intro','tldr_card','wwww_card',
        'social_share','we_recommend_band',
        'restaurant_recs_band','adventures_band','reviews_band',
        'dest_hero_search','dest_featured_slider','dest_region_clusters',
        'destcluster_hero','destcluster_whats_in','destcluster_featured_spots',
        'destcluster_testimonials','destcluster_explore_regions','destcluster_hashtags',
        'home_hero_centered','home_keyword_grid','home_region_grid',
        'home_resort_grid','home_blog_strip','home_cta_band',
        'home_unified_search','home_editorial_intro','home_experience_grid',
        'home_hub_links','home_season_guide','home_testimonials',
        'home_faq','home_values_grid',
        'home_press_logos','home_category_accordion','home_alt_features',
        'home_badge_explainer','home_partner_perks','home_keyword_hashtags',
        'home_how_it_works',
        'hub_hero','hub_category_nav','hub_category_grid','hub_footer_rail',
        'partner_hero','partner_audience','partner_steps','partner_badge','partner_perks',
    ];
@endphp

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css">
<style>
    .rg-builder { background: #f8f9fc; padding: 16px; border-radius: 6px; }
    /* Focus mode: Live Editor "edit one block" modal — hide the
       add-block toolbar so the admin only sees the chosen block's
       edit form. The builder JS already filters state.blocks to the
       focus id; this is the visual chrome to match. */
    .rg-builder--focus { padding: 0; background: transparent; }
    .rg-builder--focus .rg-builder-toolbar { display: none !important; }
    .rg-builder--focus .rg-builder-empty { display: none !important; }
    .rg-builder-toolbar { display: flex; flex-wrap: wrap; gap: 6px; padding: 12px; background: white; border-radius: 6px; margin-bottom: 14px; box-shadow: 0 1px 2px rgba(0,0,0,.04); }
    .rg-builder-toolbar .btn-add { display: inline-flex; align-items: center; gap: 4px; padding: 6px 10px; background: #eef2ff; border: 1px solid #c7d2fe; color: #4338ca; border-radius: 4px; font-size: 12px; cursor: pointer; transition: all .15s; }
    .rg-builder-toolbar .btn-add:hover { background: #c7d2fe; }
    .rg-builder-toolbar .btn-add--custom { background: #fef3c7; border-color: #fcd34d; color: #b45309; }
    .rg-builder-toolbar .btn-add--custom:hover { background: #fcd34d; color: #78350f; }
    /* Per-block-type tags + previews so the admin can recognize each block
       at a glance — color matches the public-page accent. */
    .rg-block-preview .rg-block-typelabel[data-bt^="hero_"],
    .rg-block-preview .rg-block-typelabel[data-bt="quick_facts"],
    .rg-block-preview .rg-block-typelabel[data-bt="editor_rating"],
    .rg-block-preview .rg-block-typelabel[data-bt="listing_block"],
    .rg-block-preview .rg-block-typelabel[data-bt="attractions"],
    .rg-block-preview .rg-block-typelabel[data-bt="how_to_get_to"] {
        color: #b45309; background: #fef3c7;
    }
    /* Quick-facts preview cards — minified version of the public render. */
    .rg-pv-qf { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 6px; }
    .rg-pv-qf .rg-pv-qf-c { padding: 6px; border-radius: 4px; text-align: center; font-size: 10px; line-height: 1.25; }
    .rg-pv-qf .rg-pv-qf-c b { display: block; font-size: 12px; font-weight: 800; margin-bottom: 2px; }
    .rg-pv-qf .rg-pv-qf-c u { text-transform: uppercase; letter-spacing: .03em; font-style: normal; text-decoration: none; font-weight: 700; font-size: 8px; opacity: .85; }
    .rg-pv-qf .rg-pv-qf-c em { display: block; font-style: normal; color: #475569; margin-top: 2px; font-size: 9px; }
    /* Hero-slider preview — stacked thumbnails */
    .rg-pv-hero { display: flex; gap: 4px; align-items: center; }
    .rg-pv-hero .rg-pv-hero-stack { display: inline-flex; }
    .rg-pv-hero .rg-pv-hero-stack img { width: 56px; height: 36px; object-fit: cover; border-radius: 3px; border: 2px solid white; margin-left: -10px; box-shadow: 0 1px 2px rgba(0,0,0,.15); }
    .rg-pv-hero .rg-pv-hero-stack img:first-child { margin-left: 0; }
    .rg-pv-hero .rg-pv-hero-meta { font-size: 11px; color: #475569; }
    .rg-pv-hero .rg-pv-hero-meta b { display: block; color: #0f172a; font-size: 12px; }
    /* Editor-rating preview */
    .rg-pv-rate { display: flex; align-items: center; gap: 8px; }
    .rg-pv-rate .rg-pv-rate-num { font-size: 22px; font-weight: 800; color: #0f172a; line-height: 1; }
    .rg-pv-rate .rg-pv-rate-stars { color: #f59e0b; font-size: 14px; letter-spacing: 1px; }
    .rg-pv-rate .rg-pv-rate-bars { flex: 1; }
    .rg-pv-rate .rg-pv-rate-bar { font-size: 10px; color: #475569; display: flex; align-items: center; gap: 4px; }
    .rg-pv-rate .rg-pv-rate-bar i { flex: 1; height: 4px; background: #e2e8f0; border-radius: 2px; overflow: hidden; font-style: normal; }
    .rg-pv-rate .rg-pv-rate-bar i u { display: block; height: 100%; background: #f59e0b; text-decoration: none; }
    /* Listing-block preview */
    .rg-pv-listing { background: linear-gradient(135deg, #fef3c7 0%, #fffbeb 100%); border: 1px dashed #fcd34d; border-radius: 4px; padding: 10px 14px; }
    .rg-pv-listing strong { display: block; color: #92400e; font-size: 13px; margin-bottom: 4px; }
    .rg-pv-listing .rg-pv-listing-meta { font-size: 11px; color: #b45309; }
    /* Attractions preview — mini card grid */
    .rg-pv-attr { display: grid; grid-template-columns: repeat(3, 1fr); gap: 4px; }
    .rg-pv-attr .rg-pv-attr-c { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 4px; display: flex; gap: 4px; align-items: center; }
    .rg-pv-attr .rg-pv-attr-c img { width: 32px; height: 24px; object-fit: cover; border-radius: 2px; flex: 0 0 auto; }
    .rg-pv-attr .rg-pv-attr-c span { font-size: 10px; font-weight: 600; color: #0f172a; line-height: 1.2; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    /* How-to-get-to preview */
    .rg-pv-htgt { display: grid; grid-template-columns: repeat(2, 1fr); gap: 4px; }
    .rg-pv-htgt .rg-pv-htgt-c { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 6px; }
    .rg-pv-htgt .rg-pv-htgt-c u { font-size: 9px; font-weight: 700; color: #475569; text-transform: uppercase; text-decoration: none; }
    .rg-pv-htgt .rg-pv-htgt-c em { font-style: normal; font-size: 10px; color: #334155; display: block; margin-top: 2px; }
    /* ─── Editor card system (Elementor-style) ────────────────────────
       Each list item is a card with a header (number + title + delete)
       and a body that lays out the image thumbnail on the LEFT (when
       relevant) and the form fields on the RIGHT. The thumb has a big
       obvious "Change image" CTA directly underneath. */
    [data-rg-rows] { counter-reset: rg-row-counter; margin-bottom: 12px; }
    .rg-edit-card {
        counter-increment: rg-row-counter;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        margin-bottom: 12px;
        box-shadow: 0 1px 2px rgba(15,23,42,.04);
        overflow: hidden;
        transition: box-shadow .15s, border-color .15s;
    }
    .rg-edit-card:hover { box-shadow: 0 4px 10px rgba(15,23,42,.08); border-color: #cbd5e1; }
    .rg-edit-card-hdr {
        display: flex; align-items: center; gap: 10px;
        padding: 10px 14px;
        background: linear-gradient(180deg, #f8fafc, #ffffff);
        border-bottom: 1px solid #e2e8f0;
    }
    .rg-edit-card-num {
        flex: 0 0 auto;
        width: 24px; height: 24px;
        background: #4338ca; color: white;
        border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 11px; font-weight: 800;
    }
    .rg-edit-card-num::before { content: counter(rg-row-counter); }
    .rg-edit-card-title {
        flex: 1;
        font-size: 13px; font-weight: 700; color: #0f172a;
        display: flex; align-items: center; gap: 6px;
    }
    .rg-edit-card-actions { display: flex; gap: 4px; }
    .rg-edit-card-body { padding: 14px; display: grid; gap: 14px; }
    .rg-edit-card-body.with-thumb { grid-template-columns: 170px 1fr; }
    @media (max-width: 720px) {
        .rg-edit-card-body.with-thumb { grid-template-columns: 1fr; }
    }
    .rg-edit-card-fields { display: grid; gap: 10px; }
    .rg-edit-card-fields .row-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .rg-edit-card-fields label {
        font-size: 10px !important;
        margin-bottom: 2px !important;
        color: #475569;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .06em;
    }
    .rg-edit-card-fields .form-control,
    .rg-edit-card-fields .form-select {
        font-size: 13px; padding: 6px 10px; height: auto;
    }
    .rg-edit-card-fields textarea.form-control { min-height: 60px; resize: vertical; }
    /* Attractions multi-image picker — grid of removable thumbnails
       plus an inline "Add image" button. Each thumb is square (1:1) so
       the grid stays rhythmic regardless of source aspect ratio. */
    .rg-attr-images { width: 100%; }
    .rg-attr-image-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(96px, 1fr));
        gap: 8px;
        padding: 8px;
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 6px;
        min-height: 80px;
    }
    .rg-attr-image-thumb {
        position: relative;
        aspect-ratio: 1/1;
        border-radius: 6px;
        overflow: hidden;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
    }
    .rg-attr-image-thumb img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .rg-attr-image-thumb-remove {
        position: absolute;
        top: 4px;
        right: 4px;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: rgba(15,23,42,.85);
        color: white;
        border: 0;
        font-size: 16px;
        line-height: 1;
        cursor: pointer;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity .15s;
    }
    .rg-attr-image-thumb:hover .rg-attr-image-thumb-remove { opacity: 1; }
    .rg-attr-image-empty {
        grid-column: 1 / -1;
        text-align: center;
        font-size: 12px;
        color: #94a3b8;
        padding: 18px 12px;
    }
    /* Image thumbnail column */
    .rg-edit-thumb-wrap { display: flex; flex-direction: column; gap: 8px; }
    .rg-edit-thumb {
        width: 100%;
        aspect-ratio: 4/3;
        border-radius: 6px;
        object-fit: cover;
        background: #f1f5f9;
        border: 1px dashed #cbd5e1;
        display: block;
    }
    .rg-edit-thumb.is-empty {
        background-image: linear-gradient(135deg, #f1f5f9 25%, #e2e8f0 25%, #e2e8f0 50%, #f1f5f9 50%, #f1f5f9 75%, #e2e8f0 75%, #e2e8f0 100%);
        background-size: 16px 16px;
    }
    .rg-edit-thumb-pick {
        display: flex; align-items: center; justify-content: center; gap: 6px;
        padding: 9px 12px;
        border: 1.5px solid #4338ca;
        background: #4338ca;
        color: white;
        border-radius: 6px;
        font-weight: 700;
        font-size: 12px;
        cursor: pointer;
        transition: all .15s;
    }
    .rg-edit-thumb-pick:hover { background: #3730a3; }
    .rg-edit-thumb-pick.is-empty {
        background: white; color: #4338ca; border-style: dashed;
    }
    .rg-edit-thumb-pick.is-empty:hover { background: #eef2ff; }
    .rg-edit-thumb-pick i { font-size: 16px; }
    /* Visual icon picker (replaces <select> for icon fields) */
    .rg-icon-picker {
        display: flex; flex-wrap: wrap; gap: 4px;
        padding: 6px; background: #f8fafc; border-radius: 6px;
        border: 1px solid #e2e8f0;
    }
    .rg-icon-btn {
        width: 32px; height: 32px;
        border: 1.5px solid transparent;
        background: white;
        border-radius: 4px;
        cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        color: #64748b;
        transition: all .12s;
    }
    .rg-icon-btn:hover { border-color: #4338ca; color: #4338ca; transform: translateY(-1px); }
    .rg-icon-btn.is-active {
        background: #4338ca; color: white; border-color: #4338ca;
        box-shadow: 0 2px 4px rgba(67,56,202,.3);
    }
    .rg-icon-btn svg { width: 18px; height: 18px; pointer-events: none; }
    /* Visual color swatch picker (replaces <select> for color fields) */
    .rg-color-picker {
        display: flex; gap: 8px;
        padding: 6px; background: #f8fafc; border-radius: 6px;
        border: 1px solid #e2e8f0;
    }
    .rg-color-swatch {
        width: 30px; height: 30px;
        border-radius: 50%;
        border: 2px solid #e2e8f0;
        cursor: pointer;
        transition: all .15s;
    }
    .rg-color-swatch:hover { transform: scale(1.1); }
    .rg-color-swatch.is-active {
        border-color: #0f172a;
        box-shadow: 0 0 0 2px white inset, 0 0 0 4px #0f172a;
    }
    .rg-color-swatch[data-color="blue"]    { background: #3b82f6; }
    .rg-color-swatch[data-color="emerald"] { background: #10b981; }
    .rg-color-swatch[data-color="rose"]    { background: #f43f5e; }
    .rg-color-swatch[data-color="amber"]   { background: #f59e0b; }
    .rg-color-swatch[data-color="violet"]  { background: #8b5cf6; }
    .rg-color-swatch[data-color="slate"]   { background: #64748b; }
    /* Settings section header that appears above lists / before items */
    .rg-edit-section {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 14px;
        margin-bottom: 14px;
    }
    .rg-edit-section-title {
        font-size: 10px;
        font-weight: 800;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .12em;
        margin-bottom: 10px;
        padding-bottom: 8px;
        border-bottom: 1px dashed #cbd5e1;
        display: flex; align-items: center; gap: 6px;
    }
    /* Modal upgrade: more horizontal real estate for the layout-matching editors */
    #blockEditorModal .modal-dialog { max-width: 1100px; }
    /* Back-compat: keep .rg-row styling for any blocks that haven't been
       migrated to .rg-edit-card yet — visually consistent baseline. */
    .rg-row {
        counter-increment: rg-row-counter;
        display: grid; gap: 6px;
        padding: 10px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        background: white;
        margin-bottom: 8px;
    }
    .rg-row .rg-row-hdr { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
    .rg-row label { font-size: 11px !important; margin-bottom: 2px !important; color: #475569; font-weight: 600; }
    .rg-row .form-control, .rg-row .form-select { font-size: 12px; padding: 5px 8px; height: auto; }
    /* Media picker library grid */
    .rg-media-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 8px;
        min-height: 200px;
    }
    .rg-media-tile {
        position: relative;
        aspect-ratio: 4/3;
        border: 2px solid transparent;
        border-radius: 6px;
        background: #f1f5f9;
        overflow: hidden;
        cursor: pointer;
        transition: all .15s;
    }
    .rg-media-tile:hover { border-color: #4338ca; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(15,23,42,.15); }
    .rg-media-tile img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .rg-media-tile .rg-media-tile-meta {
        position: absolute; bottom: 0; left: 0; right: 0;
        background: linear-gradient(to top, rgba(0,0,0,.75), rgba(0,0,0,0));
        color: white;
        padding: 16px 6px 4px;
        font-size: 10px;
        font-weight: 600;
        line-height: 1.2;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .rg-media-empty, .rg-media-loading {
        grid-column: 1 / -1;
        text-align: center;
        padding: 40px 20px;
        color: #94a3b8;
        font-size: 13px;
    }
    /* "Pick image" / "Change image" trigger button used everywhere instead
       of a raw file input — opens the media picker modal. */
    .rg-pick-btn {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 6px 12px;
        border: 1.5px solid #c7d2fe;
        background: #eef2ff;
        color: #4338ca;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all .15s;
    }
    .rg-pick-btn:hover { background: #c7d2fe; border-color: #4338ca; }
    .rg-pick-btn.is-empty { border-style: dashed; background: white; }
    /* Add-row button: prominent dashed CTA that visually closes the list. */
    .rg-rows-add {
        display: flex; align-items: center; justify-content: center; gap: 8px;
        border: 2px dashed #cbd5e1; background: white; color: #64748b;
        padding: 12px 20px; border-radius: 8px;
        font-size: 13px; font-weight: 700;
        cursor: pointer; transition: all .15s;
        width: 100%; margin-top: 4px;
    }
    .rg-rows-add:hover {
        border-color: #4338ca; color: #4338ca; background: #eef2ff;
    }
    .rg-rows-add i { font-size: 18px; }
    .rg-builder-canvas { min-height: 200px; }
    .rg-builder-canvas.empty { display: flex; align-items: center; justify-content: center; min-height: 220px; background: white; border: 2px dashed #d1d5db; border-radius: 6px; color: #9ca3af; flex-direction: column; gap: 6px; }
    /* WPBakery-style compact block row: one line tall, drag handle on
       left, colored icon square, title + 1-line metadata, optional
       thumbnail(s), expand toggle, edit/delete on the right. Click the
       chevron or row body to expand a real iframe preview inline. */
    .rg-block {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        margin-bottom: 8px;
        overflow: hidden;
        transition: box-shadow .12s, border-color .12s;
    }
    .rg-block:hover { box-shadow: 0 2px 8px rgba(15,23,42,.08); border-color: #cbd5e1; }
    .rg-block.is-expanded { border-color: #4338ca; box-shadow: 0 4px 14px rgba(67,56,202,.12); }
    .rg-block-row {
        display: grid;
        grid-template-columns: 28px 40px 1fr auto auto 32px auto;
        align-items: center;
        gap: 12px;
        padding: 10px 14px;
        min-height: 60px;
    }
    .rg-block-toggle {
        width: 32px; height: 32px;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        color: #475569;
        cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        font-size: 18px;
        transition: all .12s;
    }
    .rg-block-toggle:hover { background: #eef2ff; border-color: #c7d2fe; color: #4338ca; }
    .rg-block-toggle i { transition: transform .15s ease; }
    .rg-block.is-expanded .rg-block-toggle { background: #4338ca; border-color: #4338ca; color: white; }
    .rg-block.is-expanded .rg-block-toggle i { transform: rotate(180deg); }
    .rg-block-expansion {
        max-height: 0;
        overflow: hidden;
        background: #f8fafc;
        border-top: 0 solid #e2e8f0;
        transition: max-height .25s ease, border-top-width .15s ease;
    }
    .rg-block.is-expanded .rg-block-expansion {
        max-height: 1400px;
        border-top: 1px solid #e2e8f0;
    }
    .rg-block-expansion-body {
        padding: 14px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    /* Inside the expansion: same iframe pattern we built earlier, but
       lazy-loaded only when the row is opened. */
    .rg-block-expansion .rg-iframe-wrap {
        position: relative;
        width: 100%;
        height: 220px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        background: white;
        transition: height 0.18s ease;
    }
    .rg-block-handle {
        display: flex; align-items: center; justify-content: center;
        color: #cbd5e1;
        cursor: grab;
        user-select: none;
        font-size: 18px;
        padding: 0;
        background: transparent;
        border: 0;
    }
    .rg-block-handle:hover { color: #64748b; background: transparent; }
    .rg-block-icon {
        width: 40px; height: 40px;
        border-radius: 6px;
        color: white;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
        box-shadow: 0 1px 2px rgba(0,0,0,.1);
    }
    .rg-block-body { min-width: 0; }
    .rg-block-title {
        font-size: 14px;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 2px;
        line-height: 1.2;
    }
    .rg-block-meta {
        font-size: 12px;
        color: #64748b;
        font-style: italic;
        line-height: 1.3;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .rg-block-meta strong { color: #334155; font-style: normal; font-weight: 600; }
    /* Inline thumbnails: bigger, clickable for lightbox, with a hover
       "Change" overlay so admins can swap an image without opening the
       block editor at all. */
    .rg-block-thumbs {
        display: flex;
        align-items: center;
        gap: 4px;
        flex-shrink: 0;
    }
    .rg-block-thumb {
        position: relative;
        display: block;
        width: 72px;
        height: 54px;
        border-radius: 6px;
        overflow: hidden;
        background: #f1f5f9;
        border: 2px solid white;
        box-shadow: 0 1px 3px rgba(0,0,0,.15);
        cursor: zoom-in;
        transition: transform .12s, box-shadow .12s;
    }
    .rg-block-thumb + .rg-block-thumb { margin-left: -10px; }
    .rg-block-thumb:hover {
        transform: translateY(-2px) scale(1.05);
        box-shadow: 0 4px 10px rgba(0,0,0,.18);
        z-index: 2;
    }
    .rg-block-thumb img {
        width: 100%; height: 100%; object-fit: cover; display: block;
        pointer-events: none;
    }
    /* Hover overlay with the Change action */
    .rg-block-thumb-change {
        position: absolute;
        inset: 0;
        background: rgba(15,23,42,.65);
        color: white;
        display: flex; align-items: center; justify-content: center;
        flex-direction: column;
        font-size: 10px;
        font-weight: 700;
        opacity: 0;
        cursor: pointer;
        transition: opacity .12s;
        gap: 2px;
    }
    .rg-block-thumb-change i { font-size: 16px; }
    .rg-block-thumb:hover .rg-block-thumb-change { opacity: 1; }
    .rg-block-thumb-more {
        display: flex; align-items: center;
        font-size: 11px;
        color: #64748b;
        background: #f1f5f9;
        padding: 6px 10px;
        border-radius: 4px;
        margin-left: 6px;
        font-weight: 700;
        height: 54px;
    }
    /* Lightbox modal */
    .rg-lightbox-backdrop {
        position: fixed; inset: 0;
        background: rgba(15,23,42,.85);
        z-index: 1090;
        display: flex; align-items: center; justify-content: center;
        padding: 40px;
        cursor: zoom-out;
        animation: rgLightboxFade .15s ease-out;
    }
    @keyframes rgLightboxFade { from { opacity: 0; } to { opacity: 1; } }
    .rg-lightbox-img {
        max-width: 100%;
        max-height: 90vh;
        object-fit: contain;
        border-radius: 6px;
        box-shadow: 0 20px 60px rgba(0,0,0,.5);
        cursor: default;
    }
    .rg-lightbox-close {
        position: absolute;
        top: 16px; right: 16px;
        width: 40px; height: 40px;
        background: rgba(255,255,255,.15);
        color: white; border: none;
        border-radius: 50%;
        font-size: 22px;
        cursor: pointer;
        display: flex; align-items: center; justify-content: center;
    }
    .rg-lightbox-close:hover { background: rgba(255,255,255,.3); }
    .rg-lightbox-actions {
        position: absolute;
        bottom: 24px; left: 50%;
        transform: translateX(-50%);
        display: flex; gap: 8px;
    }
    .rg-lightbox-actions .btn {
        background: rgba(255,255,255,.95);
        color: #0f172a;
        border: 1px solid rgba(255,255,255,1);
        font-weight: 700;
        font-size: 13px;
        padding: 8px 16px;
        border-radius: 6px;
    }
    .rg-lightbox-actions .btn:hover { background: white; }
    /* Legacy iframe + preview-body wrappers retained for the editor modal
       (where we still iframe individual blocks for live editing). Not
       rendered in the canvas anymore. */
    .rg-block-preview { min-width: 0; padding: 0; }
    .rg-block-preview .rg-block-typelabel {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 10px; font-weight: 800; letter-spacing: .08em;
        text-transform: uppercase;
        color: #4338ca; background: #eef2ff;
        padding: 4px 9px; border-radius: 999px;
        margin-bottom: 10px;
    }
    /* Preview body grows to fit its content. Hard caps on the abstract
       previews (image thumbs etc.) get cropped — better to let the row
       expand. iframe-based previews handle their own sizing via the
       postMessage handshake below. */
    .rg-block-preview .rg-preview-body { color: #374151; font-size: 13px; line-height: 1.5; max-height: 600px; overflow: hidden; }
    .rg-block-preview .rg-preview-body img { max-width: 100px; max-height: 80px; border-radius: 3px; }
    /* True-fidelity miniature preview — iframes the public render at 50%
       scale. The wrapper is overflow-hidden and its height is updated to
       fit measured iframe content (postMessage from /_preview/block/N).
       pointer-events:none on the iframe so clicks fall through to the
       row's edit/delete buttons. */
    .rg-iframe-wrap {
        position: relative;
        width: 100%;
        height: 220px; /* placeholder until postMessage arrives */
        overflow: hidden;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        background: #f8fafc;
        transition: height 0.18s ease;
    }
    /* The iframe ALWAYS renders at 1200px (desktop) — the visible size
       comes from a JS-computed CSS variable --rg-scale applied to its
       transform. That way the layout looks like the live desktop page
       at every canvas width, just shrunk to fit. */
    .rg-block-iframe {
        display: block;
        width: 1200px;
        height: 600px;
        border: 0;
        background: white;
        transform-origin: top left;
        transform: scale(var(--rg-scale, 0.5));
        pointer-events: none;
    }
    .rg-iframe-loading {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #94a3b8;
        font-size: 12px;
        background: rgba(248, 250, 252, 0.95);
        z-index: 2;
        gap: 6px;
    }
    .rg-iframe-loading i { font-size: 18px; }
    .rg-iframe-error {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #b91c1c;
        font-size: 12px;
        background: #fef2f2;
        padding: 0 12px;
        text-align: center;
        z-index: 2;
    }
    .rg-block-actions {
        display: flex; flex-direction: column; gap: 6px;
        padding: 16px 12px;
        border-left: 1px solid #e2e8f0;
        background: #f8fafc;
    }
    .rg-block-actions button {
        width: 34px; height: 34px;
        border: 1px solid #e2e8f0; background: white;
        border-radius: 6px; cursor: pointer;
        color: #475569; font-size: 16px;
        display: flex; align-items: center; justify-content: center;
        transition: all .12s;
    }
    .rg-block-actions button:hover { background: #eef2ff; color: #4338ca; border-color: #c7d2fe; }
    .rg-block-actions button.delete:hover { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }
    .rg-block.sortable-ghost { opacity: .4; }
    .rg-block.sortable-chosen { box-shadow: 0 8px 24px rgba(67,56,202,.25); }
    #blockEditorBody .ql-editor { min-height: 200px; font-size: 14px; }
    #blockEditorBody label { font-weight: 600; font-size: 13px; margin-bottom: 4px; }
    #blockEditorBody .form-help { font-size: 11px; color: #6b7280; margin-top: -4px; margin-bottom: 8px; }
    .rg-faq-row, .rg-gallery-row { display: flex; gap: 8px; margin-bottom: 6px; }
    .rg-faq-row input, .rg-faq-row textarea { flex: 1; }
    .rg-image-preview { display: block; max-width: 200px; max-height: 140px; margin-top: 6px; border-radius: 4px; }
</style>

<div class="rg-builder @if(!empty($focusBlockId)) rg-builder--focus @endif" data-owner-type="{{ $ownerType }}" data-owner-id="{{ $ownerId }}" @if(!empty($focusBlockId)) data-focus-block-id="{{ (int) $focusBlockId }}" @endif>
    <div class="rg-builder-toolbar">
        <div style="width:100%;display:flex;align-items:center;gap:8px;margin-bottom:8px;">
            <strong style="font-size:12px;color:#0f172a;letter-spacing:.05em;text-transform:uppercase;">Resort Guru blocks</strong>
            <span style="font-size:11px;color:#64748b;">— preview-matched to the public page</span>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:6px;width:100%;margin-bottom:10px;">
            @foreach(array_intersect($customTypes, $allowed) as $type)
                <button type="button" class="btn-add btn-add--custom" data-add-type="{{ $type }}">
                    <i class="bx {{ $icons[$type] ?? 'bx-plus' }}"></i> {{ $labels[$type] ?? ucfirst($type) }}
                </button>
            @endforeach
        </div>
        <div style="width:100%;display:flex;align-items:center;gap:8px;margin-bottom:8px;border-top:1px dashed #e5e7eb;padding-top:10px;">
            <strong style="font-size:12px;color:#0f172a;letter-spacing:.05em;text-transform:uppercase;">Generic blocks</strong>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:6px;width:100%;">
            @foreach(array_intersect($genericTypes, $allowed) as $type)
                <button type="button" class="btn-add" data-add-type="{{ $type }}">
                    <i class="bx {{ $icons[$type] ?? 'bx-plus' }}"></i> {{ $labels[$type] ?? ucfirst($type) }}
                </button>
            @endforeach
        </div>
    </div>
    @if(!empty($legacyBodyHtml) && empty($hasBlocks))
        <div class="alert alert-warning d-flex align-items-center gap-2" style="border-radius:6px;font-size:13px;margin-bottom:14px;">
            <i class="bx bx-info-circle" style="font-size:20px;"></i>
            <div>
                <strong>This page still uses seeded HTML body content.</strong>
                The public page renders <code>body_html</code> until you add at least one block here.
                The moment you add a block, the legacy HTML stops rendering and only the blocks below show.
            </div>
        </div>
    @endif
    <div id="rgBlocksCanvas" class="rg-builder-canvas empty">
        <i class="bx bx-loader bx-spin" style="font-size: 28px;"></i>
        <div>Loading blocks...</div>
    </div>
</div>

{{-- Media-picker modal: nested inside the block editor. Two tabs (Upload /
     Library) so admins can either drop a fresh file in or reuse one already
     in rg_media. Opened via openMediaPicker({onSelect}) from any image
     field. Higher z-index than the block editor so it stacks correctly. --}}
<div class="modal fade" id="rgMediaPickerModal" tabindex="-1" aria-hidden="true" style="z-index:1080;">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bx bx-image-add me-1"></i>Choose image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <ul class="nav nav-tabs px-3 pt-3 mb-0" role="tablist">
                    <li class="nav-item"><button type="button" class="nav-link active" data-media-tab="upload"><i class="bx bx-upload me-1"></i>Upload new</button></li>
                    <li class="nav-item"><button type="button" class="nav-link" data-media-tab="library"><i class="bx bx-photo-album me-1"></i>From library</button></li>
                </ul>
                <div data-media-pane="upload" class="p-4">
                    <label class="form-label">Pick a file to upload</label>
                    <input type="file" class="form-control" id="rgMediaUploadInput" accept="image/*">
                    <div class="form-help mt-1">Saved into rg_media and immediately available in the library tab. Max 50MB.</div>
                    <div id="rgMediaUploadStatus" class="mt-3"></div>
                </div>
                <div data-media-pane="library" class="p-4" hidden>
                    <div class="d-flex gap-2 mb-3">
                        <input type="search" class="form-control" id="rgMediaSearch" placeholder="Search filenames, alt text, source…">
                    </div>
                    <div id="rgMediaGrid" class="rg-media-grid"></div>
                    <div id="rgMediaPager" class="d-flex justify-content-between align-items-center mt-3"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

{{-- Block editor modal --}}
<div class="modal fade" id="blockEditorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="blockEditorTitle">Edit Block</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="blockEditorBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="blockEditorSave"><i class="bx bx-save me-1"></i>Save Block</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
<script>
(function () {
    const cfg = {
        ownerType: @json($ownerType),
        ownerId: @json($ownerId),
        focusBlockId: @json(!empty($focusBlockId) ? (int) $focusBlockId : null),
        csrf: @json(csrf_token()),
        listingsAllowed: @json(in_array('listing_slot', $allowed, true)),
        frontendUrl: @json(\App\Support\RgFrontend::url()),
        urls: {
            list: '/resort-guru-blocks-list',
            save: '/resort-guru-blocks-save',
            delete: '/resort-guru-blocks-delete',
            reorder: '/resort-guru-blocks-reorder',
            upload: '/resort-guru-blocks-upload-media',
        },
    };

    const state = { blocks: [], current: null, quill: null };
    const escapeHtml = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    // Files live on the frontend app, not this admin app, so rewrite /storage/... URLs
    // through the configured frontend base so admin-side previews load correctly.
    const mediaUrl = (s) => {
        if (!s) return '';
        if (/^https?:\/\//i.test(s)) return s;
        if (s.startsWith('/storage/') && cfg.frontendUrl) return cfg.frontendUrl + s;
        return s;
    };
    const labels = @json($labels);

    function defaultPayload(type) {
        const defaults = {
            heading: { level: 'h2', text: 'New heading' },
            rich_text: { html: '<p>Write your content here...</p>' },
            image: { src: '', alt: '', caption: '', align: 'center' },
            gallery: { images: [], columns: 3 },
            video: { src: '', youtube_id: '', caption: '' },
            faq: { items: [{ question: '', answer: '' }] },
            cta: { headline: '', text: '', button_text: 'Click here', button_url: '#', style: 'primary' },
            two_column: { left_html: '<p>Left column</p>', right_html: '<p>Right column</p>' },
            listing_slot: { slot_label: 'Featured Resorts', fallback_html: '<p>We are still finalizing partners for this destination. <a href="/register">Sign up</a> to list here.</p>' },
            quote: { text: 'A great quote.', author: '' },
            divider: { style: 'line' },
            custom_html: { html: '<!-- custom html -->' },
            // Custom RG element defaults — sized so a freshly-added block
            // already looks like the rendered page version, no boilerplate.
            hero_slider: {
                images: [], height: 'md', autoplay: true, interval: 6500,
                style: 'card', eyebrow_title: '', eyebrow_subtitle: '',
            },
            quick_facts: { heading: '', cards: [
                { icon: 'money',   big: 'Multiple',         label: 'Per person',       detail: 'tiers from quick eats to sit-down', color: 'blue' },
                { icon: 'clock',   big: '3-5 PM',           label: 'Easiest window',   detail: 'Walk-in friendly',                  color: 'emerald' },
                { icon: 'warning', big: '12-2 PM',          label: 'Avoid (weekends)', detail: 'Peak lunch crush',                  color: 'rose' },
                { icon: 'traffic', big: 'Moderate',         label: 'Traffic',          detail: 'Weekday off-peak is calmest',       color: 'amber' },
            ] },
            editor_rating: {
                title: 'Resort Guru Editor Rating',
                overall: 4.0,
                criteria: [
                    { name: 'Food',    score: 4.0 },
                    { name: 'Vibe',    score: 4.0 },
                    { name: 'Service', score: 4.0 },
                    { name: 'Value',   score: 4.0 },
                ],
                summary: '',
            },
            listing_block: {
                area: '',
                fallback_offer: '',
                note: 'Renders live from rg_restaurant_listings / rg_listings for this keyword. Sort by bid_gp DESC.',
            },
            attractions: {
                heading: "What's beyond the food",
                intro: '',
                items: [
                    { name: '', image: '', short: '', blurb: '', url: '' },
                ],
            },
            how_to_get_to: {
                heading: 'How to get there',
                intro: '',
                methods: [
                    { icon: 'car', title: 'By private car', subtitle: '', detail: '', color: 'amber', operators: [] },
                    { icon: 'bus', title: 'By bus',         subtitle: '', detail: '', color: 'blue',  operators: [] },
                ],
                footer: '',
            },
            text_section: {
                heading: 'Section heading',
                heading_level: 'h2',
                anchor: '',
                // Single multi-line text field, blank-line separated for
                // paragraphs. Replaces the legacy paragraphs[] array.
                body: '',
            },
            short_version: {
                eyebrow: 'The short version',
                body: '',
                accent_color: 'amber',
            },
            pros_cons: {
                pros_label: 'Best for',
                pros: [''],
                cons_label: 'Skip if',
                cons: [''],
            },
            summary_accordion: {
                items: [
                    { eyebrow: 'At a glance', title: '', body: '', open: true },
                    { eyebrow: 'The short version', title: '', body: '', open: false },
                ],
            },
            image_text_pair: {
                image: '',
                image_position: 'left',
                eyebrow: '',
                title: '',
                body: '',
                url: '',
                url_label: 'Learn more',
            },
            traveler_reviews: {
                heading: 'What travelers are saying',
                intro: '',
                items: [
                    { name: '', avatar: '', rating: 5, quote: '', date: '' },
                ],
            },
            map_embed: {
                heading: '',
                embed_url: '',
                height: 450,
            },
            local_tip: {
                eyebrow: 'Local tip',
                body: '',
                color: 'amber',
            },
            related_guides: {
                heading: 'Related guides',
                items: [
                    { eyebrow: '', title: '', blurb: '', url: '' },
                ],
            },
            data_table: {
                caption: '',
                headers: ['Tier', 'Detail', 'What it gets you'],
                rows: [['', '', '']],
            },
            section_header: {
                heading: "What's in this area?",
                subtitle: "The local read on the area, who it's for, and how to plan a trip that actually works.",
                anchor: '',
            },
            tag_pills: {
                label: '',
                items: [
                    { text: 'Japanese · ramen · sushi', color: 'amber' },
                    { text: 'Korean BBQ · unli sets', color: 'rose' },
                    { text: 'Filipino chains', color: 'emerald' },
                ],
            },
            external_guides: {
                heading: 'Compare picks on third-party guides',
                intro: '',
                footnote: 'External links open in a new tab. We do not get paid for clicks.',
                items: [
                    { name: 'TripAdvisor', url: '', blurb: 'Top-rated picks from travellers', color: 'emerald', logo: '', screenshot: '' },
                    { name: 'Google Maps', url: '', blurb: 'Reviews + crowdsourced info', color: 'blue', logo: '', screenshot: '' },
                ],
            },
            author: {
                eyebrow: 'Written by',
                name: 'Resort Guru Editorial',
                role: 'Resort Guru PH Editorial Team',
                bio: 'A small team of Filipino travel writers covering resorts, food finds, and DIY trips across the Philippines.',
                avatar: '',
                links: [],
                more_url: '',
                more_label: '',
            },
            nearby_destinations: {
                heading: 'Stay nearby after the meal',
                intro: '',
                // When items[] is empty AND auto_from_cluster is set, the
                // renderer pulls resort-category keyword pages in the same
                // cluster automatically. Admin can override by populating
                // items[] manually.
                auto_from_cluster: '',
                exclude_slug: '',
                max: 6,
                items: [],
            },
            related_blogs: {
                heading: 'More reads on the area',
                intro: '',
                // Auto-resolved by tag-matching the area keywords against
                // rg_blog_posts.tags. Leave items[] empty to use auto mode.
                auto_from_keywords: [],
                max: 3,
                items: [],
            },
            facts_list: {
                heading: '',
                // Same shape as quick_facts.cards — keeps the editor
                // simple and lets a block flip between grid and list
                // with just a block_type rename.
                cards: [
                    { icon: 'clock',    big: '2-3 hours from Manila', label: 'Travel time', detail: 'Easiest by private car via NLEX.',         color: 'blue' },
                    { icon: 'calendar', big: 'November to February',  label: 'Best time',   detail: 'Cooler, drier, lower prices midweek.',     color: 'emerald' },
                    { icon: 'warning',  big: 'Skip Holy Week',        label: 'Local rule',  detail: 'Rates double, parking is impossible.',     color: 'amber' },
                ],
            },
            place_history: {
                eyebrow: 'Local history',
                heading: 'How this place came to be',
                body: '',
                founded: '',
                citation_label: '',
                citation_url: '',
            },
            // Page-header content elements ported out of rg_seo_pages
            // columns. Each one stands alone — admin can drop one
            // without the others.
            subtitle_intro: {
                text: 'A short italic line introducing the page.',
            },
            tldr_card: {
                eyebrow: 'The short version',
                caption: 'Tap to read the key takeaways before you scroll',
                body: '- First takeaway\n- Second takeaway\n- Third takeaway',
            },
            wwww_card: {
                eyebrow: 'At a glance',
                caption: 'Why, when, where, and who this guide is for',
                why: '',
                when: '',
                where: '',
                whom: '',
            },
            // Marker blocks — payload is just configuration. The
            // renderer reads page + keyword + listings from the
            // request context (KeywordPageController feeds them).
            social_share: { align: 'between' },
            we_recommend_band: {},
            // Tail-section marker blocks — payloads are eyebrow + heading
            // + caption. Data (listings, reviews) is fetched live from
            // the DB by KeywordPageController and passed via context.
            restaurant_recs_band: {
                eyebrow: 'Eat nearby',
                heading: 'Restaurant Recommendations',
                caption: 'Paid placements where your guests will likely want to eat.',
            },
            adventures_band: {
                eyebrow: 'Things to do',
                heading: 'Memorable Adventures & Activities',
                caption: 'Surf schools, ATV trails, island hops, and paintball arenas open in the area.',
            },
            reviews_band: {
                heading: 'What travelers are saying',
            },
            dest_hero_search: {
                eyebrow: '',
                title: 'Discover Destinations @{{accent}}Across the Philippines@{{/accent}}',
                accent: 'brand',
                paragraphs: ['Curated destination guides for every region. Search a city, browse by area, or jump straight to a place that calls.'],
                bg_gradient: 'brand-emerald',
                background_image: '',
                breadcrumbs: [
                    { label: 'Home', url: '/' },
                    { label: 'Destinations', url: '' },
                ],
                stats_pills: [
                    { label: 'destinations', value: '', value_source: 'stats.total_destinations' },
                    { label: 'regions covered', value: '', value_source: 'stats.total_regions' },
                    { label: 'monthly search peak', value: '', value_source: 'stats.top_volume' },
                ],
                search_placeholder: 'Search a region, destination, or tourist spot…',
                search_tabs: [
                    { value: 'all', label: 'All' },
                    { value: 'region', label: 'Regions' },
                    { value: 'destination', label: 'Destinations' },
                    { value: 'spot', label: 'Tourist spots' },
                ],
                search_chips: ['Cebu','Palawan','Tagaytay','La Union','Boracay','Bicol'],
                search_labels_json: '{"region":"Regions","destination":"Destinations","spot":"Tourist spots"}',
                search_empty_hint: 'Try a region, a destination, or a spot name.',
            },
            dest_featured_slider: {
                eyebrow: 'Featured this week',
                heading: 'Tourist spots worth the trip',
                subhead: 'Picks from the destinations below — the spots locals actually point visitors at.',
                source: 'featuredSpots',
                accent: 'brand',
                bg: 'light',
                cta_label: 'Explore stays',
                slides_per_view: 3,
                autoplay: true,
                interval: 5500,
            },
            dest_region_clusters: {
                heading: 'Browse by Region',
                jump_label: 'Jump to',
                source: 'orderedClusters',
                accent: 'brand',
                show_volume: true,
                sticky_nav: false,
                icon_set: 'destination',
            },
            destcluster_hero: { eyebrow: 'Destination Guide', show_breadcrumb: true },
            destcluster_whats_in: { heading_prefix: "What's In", show_intro: true },
            destcluster_featured_spots: { heading_prefix: 'Featured', tahu_word: 'Tourist Spots', description: 'A rotating look at the places worth building a trip around. Tap any card to open it on the map.' },
            destcluster_testimonials: { heading_prefix: 'What', tahu_word: 'Travelers Say', heading_suffix: 'About', description: '' },
            destcluster_explore_regions: { heading: 'Explore Other Regions' },
            destcluster_hashtags: { heading: 'Tags', description: '' },
            home_hero_centered: {
                title: 'Discover your next stay | across the Philippines.',
                tagline: 'Compare resorts, hotels, and Airbnb stays across the islands.',
                accent: 'brand',
                bg_gradient: 'brand-emerald',
                primary_cta: { label: 'Browse destinations', url: '/destinations' },
                secondary_cta: { label: 'List your resort', url: '/register' },
                stats: [
                    { label: 'destinations', value: '', value_source: 'stats.pages' },
                    { label: 'verified properties', value: '', value_source: 'stats.resorts' },
                    { label: 'islands', value: '7,641', value_source: 'literal' },
                ],
            },
            home_keyword_grid: {
                heading: 'Popular destinations',
                subhead: 'The most-searched places we cover this month.',
                view_all: { label: 'View all destinations', url: '/destinations' },
                source: 'featuredKeywords',
                columns: 3,
                accent: 'brand',
            },
            home_region_grid: {
                heading: 'Browse by region',
                subhead: 'Regional clusters across Luzon, Visayas, Mindanao, and Palawan.',
                view_all: { label: 'All regions', url: '/destinations' },
                source: 'regions',
                accent: 'brand',
                bg: 'soft',
            },
            home_resort_grid: {
                heading: 'Featured properties',
                subhead: 'Recently approved resorts, hotels, and beach houses.',
                source: 'featuredResorts',
                bg: 'slate',
            },
            home_blog_strip: {
                heading: 'From the blog',
                subhead: 'Travel tips, destination guides, and resort stories.',
                source: 'latestPosts',
                accent: 'brand',
            },
            home_cta_band: {
                heading: 'Run a resort, hotel, or beach house?',
                body: 'Get featured on the destination pages your future guests are already searching.',
                cta: { label: 'Start listing free', url: '/register' },
                accent: 'brand',
            },
            home_unified_search: {
                eyebrow: 'Search the Philippines',
                title: 'Where to next, @{{accent}}kababayan?@{{/accent}}',
                tagline: 'Search 1,100+ destinations, stays, food finds, and tourist spots across the islands.',
                accent: 'brand',
                bg_gradient: 'brand-emerald',
                placeholder: 'Try Coron, lechon, Sinulog, El Nido…',
                tabs: [
                    { value: 'all', label: 'All' },
                    { value: 'destination', label: 'Destinations' },
                    { value: 'resort', label: 'Stays' },
                    { value: 'restaurant', label: 'Food' },
                    { value: 'spot', label: 'Spots' },
                    { value: 'region', label: 'Regions' },
                    { value: 'blog', label: 'Blog' },
                ],
                chips: ['Cebu', 'Palawan', 'Tagaytay', 'Boracay', 'Siargao', 'Vigan', 'Mt. Pulag'],
                labels_json: '{"destination":"Destinations","resort":"Stays","restaurant":"Food finds","spot":"Tourist spots","region":"Regions","blog":"Blog"}',
                empty_hint: 'Try a region, a city name, a dish, or a spot like Mt. Pulag.',
                stats: [
                    { label: 'destinations', value: '', value_source: 'stats.pages' },
                    { label: 'verified properties', value: '', value_source: 'stats.resorts' },
                    { label: 'islands', value: '7,641', value_source: 'literal' },
                ],
            },
            home_editorial_intro: {
                heading: 'Editorial section heading',
                paragraphs: ['First intro paragraph.', 'Second intro paragraph.'],
                accent: 'brand',
                image_src: '',
                image_alt: '',
                image_caption: '',
                image_position: 'none',
            },
            home_experience_grid: {
                heading: 'Explore by experience',
                subhead: 'Pick the kind of trip you want, then plan around it.',
                columns: 3,
                categories: [
                    { name: 'Beach Escapes', tagline: 'White sand, island hops, dive sites.', url: '/destinations', image_src: '', image_alt: '' },
                    { name: 'Mountain Trips', tagline: 'Cordillera ridges, volcano climbs.', url: '/destinations', image_src: '', image_alt: '' },
                    { name: 'Heritage Tours', tagline: 'Spanish colonial towns, UNESCO sites.', url: '/destinations', image_src: '', image_alt: '' },
                    { name: 'Food Trips', tagline: 'Filipino food culture, regional specialties.', url: '/food-trip', image_src: '', image_alt: '' },
                    { name: 'Festivals', tagline: 'Fiestas, religious + cultural events.', url: '/philippine-fiestas-festivals-guide', image_src: '', image_alt: '' },
                    { name: 'Adventure', tagline: 'Surf, dive, ATV, canyoneering.', url: '/philippine-tourist-activities-adventures-what-to-do', image_src: '', image_alt: '' },
                ],
            },
            home_hub_links: {
                heading: 'Where to start your planning',
                subhead: 'Four guides that cover the country the way Filipinos travel it.',
                hubs: [
                    { label: 'What to eat', url: '/filipino-food-dishes-what-to-eat', icon: '🍲', accent: 'rose', tagline: 'Adobo, sinigang, regional dishes worth the side trip.' },
                    { label: 'What to do', url: '/philippine-tourist-activities-adventures-what-to-do', icon: '🏄', accent: 'emerald', tagline: 'Surf, dive, hike, canyoneer.' },
                    { label: 'What to buy', url: '/philippine-souvenirs-pasalubong-what-to-buy', icon: '🎁', accent: 'violet', tagline: 'Pasalubong picks sourced to province.' },
                    { label: 'Cultures to meet', url: '/philippine-tribes-ethnic-groups-cultures-to-meet', icon: '🪡', accent: 'teal', tagline: 'Seventy-five ethnolinguistic groups.' },
                ],
            },
            home_season_guide: {
                heading: 'When to come, weather wise',
                subhead: 'The Philippines runs two clear seasons plus shoulder windows in between.',
                seasons: [
                    { name: 'Dry Season', months: 'November to May', blurb: 'Hot peaks April-May, cooler Dec-Feb.', tip: 'Book El Nido / Coron months ahead if dates touch Holy Week.' },
                    { name: 'Wet Season', months: 'June to October', blurb: 'Typhoon risk peaks Aug-Sep across Luzon.', tip: 'Head south to Davao + Camiguin where typhoons rarely reach.' },
                    { name: 'Shoulder Months', months: 'April-May, October-November', blurb: 'Trade-off windows, fewer crowds.', tip: 'Late October is great for Siargao (surf season).' },
                ],
            },
            home_testimonials: {
                heading: 'What travelers say',
                subhead: 'Real notes from kababayan and foreign visitors who used the guides.',
                flush: false,
                reviews: [
                    { text: 'Sample review mentioning a specific destination or experience...', author: 'Anna R.', location: 'Quezon City', rating: 5, avatar_url: '' },
                ],
            },
            home_faq: {
                heading: 'Questions before you go',
                subhead: 'Quick answers to the things every first-time visitor asks.',
                faqs: [
                    { question: 'When is the best time to visit?', answer: 'Dry season runs roughly November to May…' },
                ],
            },
            home_keyword_hashtags: {
                eyebrow: '#Tags',
                heading: 'Explore by Tag',
                subhead: '',
                limit: 40,
                flush: false,
                category: '',
                source: '',
            },
            home_how_it_works: {
                eyebrow: 'How It Works',
                heading: 'How it works',
                subhead: 'A quick walkthrough. Tap a step to read more.',
                layout: 'accordion',
                pillars: [
                    { icon: 'globe', title: 'First step title', body: 'Short paragraph explaining this step.' },
                    { icon: 'search', title: 'Second step title', body: 'Short paragraph explaining this step.' },
                    { icon: 'badgecheck', title: 'Third step title', body: 'Short paragraph explaining this step.' },
                ],
            },
            home_values_grid: {
                heading: 'How we work',
                subhead: 'The principles that shape what gets featured and what does not.',
                values: [
                    { title: 'First principle', body: 'Short paragraph explaining the principle.', icon: '🌟' },
                ],
                columns: 4,
                bg: 'light',
            },
            hub_hero: {
                eyebrow: 'Page Eyebrow',
                title: 'Page Title with @{{accent}}colored portion@{{/accent}}',
                accent: 'rose',
                paragraphs: ['First intro paragraph.', 'Second intro paragraph.'],
                background_image: '',
                centered: false,
                title_case: false,
                search: false,
            },
            hub_category_nav: {
                label: 'Jump to',
                accent: 'slate',
                sticky: true,
            },
            hub_category_grid: {
                category_key: '',
                item_url_prefix: '',
                cards_per_row: 4,
                as_accordion: true,
                image_cards: false,
                divider: '',
            },
            hub_footer_rail: {
                heading: 'Closing heading',
                body: 'Closing paragraph for the hub page.',
                links: [
                    { label: 'Destinations', url: '/destinations', theme: 'slate' },
                ],
            },
            partner_hero: {
                eyebrow: 'Partner Directory · Free to Join',
                title: 'Join Our Community, @{{accent}}Become a Partner@{{/accent}}',
                subhead: 'Run a tourism business in the Philippines? List it for free and get in front of the travelers already planning their trip here.',
                cta_primary_label: 'List Your Business for Free',
                cta_primary_url: '/register',
                cta_secondary_label: 'Talk to Us First',
                cta_secondary_url: '/contact',
                trust_points: ['Free to list', 'No credit card', 'Verified badge once approved'],
                image: '',
                image_alt: '',
                badge_title: 'Verified Partner',
                badge_subtitle: 'We Highly Recommend',
            },
            partner_audience: {
                eyebrow: 'Who Can Join',
                heading: 'Built for *every kind* of tourism business',
                subhead: 'If a traveler would look for you, you belong in the directory.',
                items: [
                    { title: 'Tour Guides', subtitle: 'Solo and licensed', icon: 'M12 2a5 5 0 0 1 5 5c0 3.5-5 11-5 11S7 10.5 7 7a5 5 0 0 1 5-5z M12 7.5a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z' },
                ],
            },
            partner_steps: {
                eyebrow: 'How It Works',
                heading: 'From a free listing to a *verified partner*',
                steps: [
                    { number: '1', color: 'brand', title: 'List for free', body: 'Send us your details and we set up your listing at no cost.' },
                    { number: '2', color: 'amber', title: 'Get discovered', body: 'Your listing sits on pages built to rank, so travelers find you.' },
                    { number: '3', color: 'emerald', title: 'Become verified', body: 'Meet our standards and earn the We Highly Recommend badge.' },
                ],
            },
            partner_badge: {
                eyebrow: 'The Verified Badge',
                eyebrow_color: 'amber',
                heading: 'The *We Highly Recommend* badge',
                body: 'Once your place is approved, you carry our We Highly Recommend badge on your listing and at your storefront.',
                image: '',
                image_alt: '',
                image_side: 'left',
                points: ['A clear mark of trust on your listing', 'A window sticker for your storefront', 'A badge number guests can look up'],
            },
            partner_perks: {
                eyebrow: 'Why Partner With Us',
                heading: 'Everything you get, from day one',
                subhead: '',
                perks: [
                    { title: 'Get found on Google', body: 'Your listing lives on pages built to rank.', icon: 'M11 3a8 8 0 1 0 5.3 14 M21 21l-4.3-4.3 M11 7a4 4 0 0 0-4 4' },
                ],
            },
        };
        return defaults[type] || {};
    }

    // For saved custom blocks we swap the abstract mini for a true iframe
    // miniature of the public render. Returns the wrapper HTML; the iframe
    // posts its measured height back to us so the wrapper auto-sizes (no
    // cropping).
    function iframePreview(block) {
        if (!block.id || !cfg.frontendUrl) return null;
        const src = cfg.frontendUrl + '/_preview/block/' + block.id;
        return `<div class="rg-iframe-wrap" data-iframe-wrap data-block-id="${block.id}">
            <div class="rg-iframe-loading" data-iframe-loading><i class="bx bx-loader bx-spin"></i> Loading preview</div>
            <iframe class="rg-block-iframe" data-block-id="${block.id}" data-block-type="${escapeHtml(block.type)}" src="${escapeHtml(src)}" loading="lazy" referrerpolicy="no-referrer" sandbox="allow-scripts allow-same-origin" onerror="this.parentElement.querySelector('[data-iframe-loading]').innerHTML='Preview failed to load'"></iframe>
        </div>`;
    }

    // Block types that get the true-fidelity iframe preview. Generic blocks
    // (heading, rich_text, image, etc.) keep the abstract previews — the
    // iframe is worth it only for the bespoke Resort Guru elements.
    // Every renderable block gets a true-fidelity iframe miniature. Divider
    // is the only generic exception (a horizontal line at scale isn't very
    // informative), and it falls through to the abstract preview.
    const IFRAME_PREVIEW_TYPES = new Set([
        // Resort Guru custom elements
        'hero_slider', 'quick_facts', 'editor_rating',
        'listing_block', 'attractions', 'how_to_get_to', 'text_section',
        // Generic blocks — all flow through BlockRenderer cleanly
        'rich_text', 'custom_html', 'faq', 'image', 'gallery',
        'cta', 'quote', 'heading', 'two_column', 'video',
        'listing_slot',
    ]);
    // The iframe content always renders at this width (desktop layout).
    // Wrap width controls scale; computed JS-side to keep aspect right.
    const IFRAME_FIXED_WIDTH = 1200;

    function previewHtml(block) {
        // Custom-element saved blocks → iframe-rendered miniature. All
        // home_/hub_/dest_ blocks render cleanly through the frontend
        // BuilderPreviewController, so preview them as true front-site
        // miniatures (covers newer home blocks not in the explicit set).
        if ((IFRAME_PREVIEW_TYPES.has(block.type) || /^(home|hub|dest)_/.test(block.type)) && block.id) {
            const iframe = iframePreview(block);
            if (iframe) return iframe;
        }
        const p = block.payload || {};
        switch (block.type) {
            case 'heading':
                return `<${p.level || 'h2'} style="margin:0;font-size:${p.level==='h3'?'18px':'22px'};">${escapeHtml(p.text)}</${p.level||'h2'}>`;
            case 'rich_text':
                return p.html ? p.html : '<em>Empty rich text</em>';
            case 'image':
                return p.src ? `<img src="${mediaUrl(p.src)}" alt="${escapeHtml(p.alt)}">${p.caption ? '<div style="font-size:11px;color:#6b7280">'+escapeHtml(p.caption)+'</div>' : ''}` : '<em>No image yet</em>';
            case 'gallery':
                return (p.images && p.images.length) ? p.images.slice(0,4).map(i => `<img src="${mediaUrl(i.src)}" alt="" style="display:inline-block;margin-right:4px">`).join('') + (p.images.length>4?` <em>+${p.images.length-4} more</em>`:'') : '<em>No images yet</em>';
            case 'video':
                return p.youtube_id ? `<em>YouTube video: ${escapeHtml(p.youtube_id)}</em>` : (p.src ? `<em>Video file: ${escapeHtml(p.src)}</em>` : '<em>No video yet</em>');
            case 'faq':
                return (p.items||[]).map(f => `<strong>${escapeHtml(f.question)}</strong><div style="font-size:12px;color:#6b7280">${escapeHtml(f.answer).slice(0,120)}</div>`).join('<br>') || '<em>No FAQs yet</em>';
            case 'cta':
                return `<strong>${escapeHtml(p.headline||'')}</strong><div style="font-size:12px;color:#6b7280">${escapeHtml(p.text||'')}</div><span style="display:inline-block;margin-top:6px;padding:4px 10px;background:#6366f1;color:white;border-radius:3px;font-size:11px">${escapeHtml(p.button_text)}</span>`;
            case 'two_column':
                return `<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:12px"><div>${p.left_html||'<em>Empty</em>'}</div><div>${p.right_html||'<em>Empty</em>'}</div></div>`;
            case 'listing_slot':
                return `<strong>Listing slot: ${escapeHtml(p.slot_label||'Featured')}</strong><div style="font-size:11px;color:#6b7280;margin-top:4px">Fallback HTML if no bids: ${escapeHtml((p.fallback_html||'').slice(0,80))}...</div>`;
            case 'quote':
                return `<em>"${escapeHtml(p.text)}"</em>${p.author ? ' &mdash; '+escapeHtml(p.author):''}`;
            case 'divider':
                return `<hr style="margin:6px 0">`;
            case 'custom_html':
                return `<code style="font-size:11px;color:#6b7280">${escapeHtml((p.html||'').slice(0,100))}...</code>`;

            // ── Resort Guru custom-element previews. Each one renders a
            //    visual mini that mirrors the public-page output so the
            //    admin can scan the canvas without opening every editor.

            case 'hero_slider': {
                const imgs = (p.images || []).filter(i => i.src);
                if (!imgs.length) return '<em>No slides yet — add images.</em>';
                const stack = imgs.slice(0, 3).map(i => `<img src="${mediaUrl(i.src)}" alt="">`).join('');
                const extra = imgs.length > 3 ? ` +${imgs.length - 3}` : '';
                return `<div class="rg-pv-hero"><div class="rg-pv-hero-stack">${stack}</div>` +
                    `<div class="rg-pv-hero-meta"><b>Splide hero slider</b>${imgs.length} slide${imgs.length===1?'':'s'}${extra} · height ${escapeHtml(p.height||'md')} · ${p.autoplay?'autoplay':'manual'}</div></div>`;
            }
            case 'quick_facts': {
                const cards = (p.cards || []).slice(0, 4);
                if (!cards.length) return '<em>No cards yet.</em>';
                const palettes = {
                    blue:    ['#eff6ff','#bfdbfe','#1d4ed8','#1e3a8a'],
                    emerald: ['#ecfdf5','#a7f3d0','#047857','#064e3b'],
                    rose:    ['#fff1f2','#fecdd3','#be123c','#881337'],
                    amber:   ['#fffbeb','#fcd34d','#b45309','#78350f'],
                    violet:  ['#f5f3ff','#ddd6fe','#6d28d9','#4c1d95'],
                    slate:   ['#f8fafc','#cbd5e1','#334155','#0f172a'],
                };
                const cells = cards.map(c => {
                    const pal = palettes[c.color] || palettes.blue;
                    return `<div class="rg-pv-qf-c" style="background:${pal[0]};border:1px solid ${pal[1]};color:${pal[2]}">` +
                        `<b>${escapeHtml(c.big || '—')}</b>` +
                        `<u style="color:${pal[3]}">${escapeHtml(c.label || '')}</u>` +
                        `<em>${escapeHtml(c.detail || '')}</em></div>`;
                }).join('');
                return `<div class="rg-pv-qf" style="grid-template-columns:repeat(${cards.length},minmax(0,1fr))">${cells}</div>`;
            }
            case 'editor_rating': {
                const overall = parseFloat(p.overall || 0) || 0;
                const filled = Math.round(overall);
                const stars = '★'.repeat(filled) + '☆'.repeat(5 - filled);
                const bars = (p.criteria || []).slice(0, 4).map(c => {
                    const sc = parseFloat(c.score || 0) || 0;
                    const pct = Math.max(0, Math.min(100, (sc / 5) * 100));
                    return `<div class="rg-pv-rate-bar"><span style="width:60px;font-weight:600">${escapeHtml(c.name || '')}</span>` +
                        `<i><u style="width:${pct}%"></u></i><span style="width:24px;text-align:right;font-weight:700;color:#0f172a">${sc.toFixed(1)}</span></div>`;
                }).join('');
                return `<div class="rg-pv-rate"><div><div class="rg-pv-rate-num">${overall.toFixed(1)}</div>` +
                    `<div class="rg-pv-rate-stars">${stars}</div></div><div class="rg-pv-rate-bars">${bars}</div></div>`;
            }
            case 'listing_block':
                return `<div class="rg-pv-listing"><strong><i class="bx bx-trophy"></i> Listings band</strong>` +
                    `<div class="rg-pv-listing-meta">Renders the top-bid restaurants / resorts live for this keyword. ` +
                    `Empty-state CTA invites owners to list${p.area ? ` in ${escapeHtml(p.area)}` : ''}.</div></div>`;
            case 'attractions': {
                const items = (p.items || []).filter(i => i.name);
                if (!items.length) return '<em>No attractions yet.</em>';
                const cells = items.slice(0, 6).map(i => {
                    const img = i.image
                        ? `<img src="${mediaUrl(i.image)}" alt="">`
                        : `<span style="width:32px;height:24px;background:#e2e8f0;border-radius:2px;flex:0 0 auto"></span>`;
                    return `<div class="rg-pv-attr-c">${img}<span>${escapeHtml(i.name)}</span></div>`;
                }).join('');
                const more = items.length > 6 ? `<div class="rg-pv-attr-c"><span>+${items.length - 6} more</span></div>` : '';
                return `<div style="font-size:12px;font-weight:700;color:#0f172a;margin-bottom:4px">${escapeHtml(p.heading || '')}</div>` +
                    `<div class="rg-pv-attr">${cells}${more}</div>`;
            }
            case 'how_to_get_to': {
                const methods = (p.methods || []).filter(m => m.title);
                if (!methods.length) return '<em>No methods yet.</em>';
                const cells = methods.slice(0, 4).map(m =>
                    `<div class="rg-pv-htgt-c"><u>${escapeHtml(m.title)}</u><em>${escapeHtml((m.detail || '').slice(0, 60))}${(m.detail || '').length > 60 ? '…' : ''}</em></div>`
                ).join('');
                return `<div style="font-size:12px;font-weight:700;color:#0f172a;margin-bottom:4px">${escapeHtml(p.heading || '')}</div>` +
                    `<div class="rg-pv-htgt">${cells}</div>`;
            }

            case 'text_section': {
                const lvl = (p.heading_level || 'h2').toUpperCase();
                const headingPv = p.heading
                    ? `<div style="font-size:14px;font-weight:700;color:#0f172a;margin-bottom:6px"><span style="font-size:9px;color:#94a3b8;letter-spacing:.05em;text-transform:uppercase;margin-right:4px">${lvl}</span>${escapeHtml(p.heading)}</div>`
                    : '';
                const paras = (p.paragraphs || []).filter(x => (x || '').trim() !== '');
                if (!paras.length && !p.heading) return '<em>Empty text section.</em>';
                const paraPv = paras.slice(0, 3).map(t => {
                    const plain = String(t).replace(/<[^>]+>/g, '').trim();
                    return `<div style="font-size:12px;color:#475569;line-height:1.45;margin-bottom:4px;display:flex;gap:6px;align-items:start"><span style="color:#cbd5e1;flex:0 0 auto">¶</span><span>${escapeHtml(plain.slice(0, 140))}${plain.length > 140 ? '…' : ''}</span></div>`;
                }).join('');
                const more = paras.length > 3 ? `<div style="font-size:11px;color:#94a3b8;margin-top:2px">+${paras.length - 3} more paragraph${paras.length - 3 === 1 ? '' : 's'}</div>` : '';
                return headingPv + paraPv + more;
            }

            default:
                return `<em>Unknown block type: ${escapeHtml(block.type)}</em>`;
        }
    }

    // ── Canvas row helpers (WPBakery-style compact list) ──────────────
    // Each block in the canvas is one short row. Drag handle / colored
    // icon square / title / 1-line metadata / optional thumbnail(s) /
    // edit + delete buttons. No iframes — keeps the canvas scannable
    // even on long pages (15+ blocks).

    // Boxicons class per block type (squares show the icon in white on
    // a colored background — color comes from BLOCK_COLORS).
    const BLOCK_ICONS = @json($icons);

    // Square background color per block type. Grouped by visual category
    // so related blocks read together at a glance.
    const BLOCK_COLORS = {
        // Headings / text
        heading: '#f59e0b', rich_text: '#64748b', text_section: '#475569',
        quote: '#8b5cf6', divider: '#94a3b8',
        // Media
        image: '#3b82f6', gallery: '#2563eb', video: '#dc2626',
        hero_slider: '#1d4ed8',
        // Listings
        listing_block: '#10b981', listing_slot: '#059669',
        // Custom Resort Guru elements
        quick_facts: '#6366f1', editor_rating: '#d97706',
        attractions: '#0891b2', how_to_get_to: '#0e7490',
        // Standardized templates
        short_version: '#0f172a', pros_cons: '#059669',
        summary_accordion: '#d97706', image_text_pair: '#2563eb',
        traveler_reviews: '#ea580c', map_embed: '#16a34a',
        local_tip: '#ca8a04', related_guides: '#0d9488',
        data_table: '#475569',
        section_header: '#9333ea',
        tag_pills: '#db2777', external_guides: '#0d9488',
        author: '#0f172a',
        nearby_destinations: '#0d9488', related_blogs: '#b45309',
        facts_list: '#0891b2', place_history: '#b45309',
        // Generic / structural
        faq: '#06b6d4', cta: '#a855f7', two_column: '#7c3aed',
        custom_html: '#1f2937',
    };

    /**
     * 1-line metadata string per block. Italicised under the title.
     * Designed to give the admin enough context to know "what this is"
     * without opening the editor — same pattern as WPBakery's
     * "Text: WP Bakery Page Builder" element-row subtitle.
     */
    function blockMeta(block) {
        const p = block.payload || {};
        const trunc = (s, n) => { s = String(s || ''); return s.length > n ? s.slice(0, n) + '…' : s; };
        const strip = (html) => String(html || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
        switch (block.type) {
            case 'heading': return `<strong>${(p.level || 'h2').toUpperCase()}</strong> · "${escapeHtml(trunc(p.text, 60))}"`;
            case 'rich_text': {
                const txt = strip(p.html);
                const words = txt ? txt.split(/\s+/).filter(Boolean).length : 0;
                return `<strong>${words}</strong> words · "${escapeHtml(trunc(txt, 70))}"`;
            }
            case 'image': return p.src
                ? `${escapeHtml(p.src.split('/').pop())}${p.alt ? ' · "' + escapeHtml(trunc(p.alt, 40)) + '"' : ''}`
                : '<em>No image selected</em>';
            case 'gallery': return `<strong>${(p.images||[]).length}</strong> images · ${p.columns||3} columns`;
            case 'video':
                if (p.youtube_id) return `YouTube: <strong>${escapeHtml(p.youtube_id)}</strong>`;
                if (p.src) return escapeHtml(p.src.split('/').pop());
                return '<em>No video</em>';
            case 'faq': return `<strong>${(p.items||[]).length}</strong> Q&As · "${escapeHtml(p.heading || 'Frequently Asked Questions')}"`;
            case 'cta': return `"${escapeHtml(p.button_text || 'Click')}" → ${escapeHtml(trunc(p.button_url || '#', 40))}`;
            case 'two_column': {
                const l = strip(p.left_html).length, r = strip(p.right_html).length;
                return `Left: <strong>${l}</strong>c · Right: <strong>${r}</strong>c`;
            }
            case 'listing_slot': return `Slot: "${escapeHtml(p.slot_label || 'Featured')}"`;
            case 'quote': return `"${escapeHtml(trunc(p.text, 60))}"${p.author ? ' — ' + escapeHtml(p.author) : ''}`;
            case 'divider': return `Style: <strong>${escapeHtml(p.style || 'line')}</strong>`;
            case 'custom_html': return `HTML · <strong>${(p.html || '').length}</strong> chars`;
            case 'hero_slider': return `<strong>${(p.images||[]).length}</strong> slides · ${p.height || 'md'} height · ${p.autoplay ? 'autoplay' : 'manual'}`;
            case 'quick_facts': return `<strong>${(p.cards||[]).length}</strong> cards${p.heading ? ' · "' + escapeHtml(trunc(p.heading, 40)) + '"' : ''}`;
            case 'facts_list': return `<strong>${(p.cards||[]).length}</strong> list rows${p.heading ? ' · "' + escapeHtml(trunc(p.heading, 40)) + '"' : ''}`;
            case 'place_history': {
                const wc = (p.body || '').trim().split(/\s+/).filter(Boolean).length;
                const since = p.founded ? `Since <strong>${escapeHtml(p.founded)}</strong>` : '';
                return `${since}${since && p.heading ? ' · ' : ''}${p.heading ? '"' + escapeHtml(trunc(p.heading, 50)) + '"' : ''}${wc ? ` · ${wc} words` : ''}`;
            }
            case 'editor_rating': return `<strong>${(parseFloat(p.overall || 0) || 0).toFixed(1)}</strong> overall · ${(p.criteria||[]).length} criteria`;
            case 'listing_block': return `Live listings band${p.area ? ' · area: "' + escapeHtml(p.area) + '"' : ''}`;
            case 'attractions': return `<strong>${(p.items||[]).length}</strong> items · "${escapeHtml(trunc(p.heading || "What's beyond the food", 45))}"`;
            case 'how_to_get_to': return `<strong>${(p.methods||[]).length}</strong> methods · "${escapeHtml(trunc(p.heading || 'How to get there', 45))}"`;
            case 'text_section': {
                // Prefer body word count; fall back to legacy paragraphs[].
                const bodyText = (p.body || '').trim();
                const stats = bodyText
                    ? `${bodyText.split(/\s+/).length} words`
                    : `${(p.paragraphs||[]).length} paragraphs`;
                return `<strong>${(p.heading_level || 'h2').toUpperCase()}</strong> "${escapeHtml(trunc(p.heading || '', 40))}" · ${stats}`;
            }
            case 'short_version': return `<strong>${escapeHtml(p.eyebrow || 'The short version')}</strong> · "${escapeHtml(trunc(strip(p.body), 70))}"`;
            case 'subtitle_intro': return `<strong>Subtitle</strong> · "${escapeHtml(trunc(p.text || '', 80))}"`;
            case 'tldr_card': {
                const bodyText = (p.body || '').trim();
                const wc = bodyText ? bodyText.split(/\s+/).filter(Boolean).length : 0;
                return `<strong>TL;DR</strong> · "${escapeHtml(trunc(p.eyebrow || 'The short version', 30))}" · ${wc} words`;
            }
            case 'wwww_card': {
                const filled = ['why','when','where','whom'].filter(k => (p[k] || '').trim() !== '').length;
                return `<strong>WWWW</strong> · ${filled}/4 fields · "${escapeHtml(trunc(p.eyebrow || 'At a glance', 30))}"`;
            }
            case 'social_share': return `<strong>Social Share Row</strong> · align: ${escapeHtml(p.align || 'between')} · live URL`;
            case 'we_recommend_band': return `<strong>"We Recommend" Listings Band</strong> · live from rg_listings · branches food vs resort by keyword.category`;
            case 'restaurant_recs_band': return `<strong>${escapeHtml(p.heading || 'Restaurant Recommendations')}</strong> · live from rg_restaurant_listings · non-food pages only`;
            case 'adventures_band': return `<strong>${escapeHtml(p.heading || 'Memorable Adventures')}</strong> · live from rg_adventure_listings · non-food pages only`;
            case 'reviews_band': return `<strong>${escapeHtml(p.heading || 'What travelers are saying')}</strong> · live from rg_destination_reviews · aggregate rating`;
            case 'dest_hero_search': return `<strong>Destinations Hero + Search</strong> · accent:${escapeHtml(p.accent || 'brand')} · "${escapeHtml(trunc(p.title || '', 50))}" · ${(p.stats_pills || []).length} stats · ${(p.search_tabs || []).length} tabs · ${(p.search_chips || []).length} chips · reads <code>searchIndex</code> + <code>stats</code> from controller`;
            case 'dest_featured_slider': return `<strong>Destinations Featured Slider</strong> · "${escapeHtml(trunc(p.heading || '', 40))}" · source:<code>${escapeHtml(p.source || 'featuredSpots')}</code> · ${p.slides_per_view || 3}-up Splide · ${p.autoplay !== false ? 'autoplay ' + (p.interval || 5500) + 'ms' : 'no autoplay'}`;
            case 'dest_region_clusters': return `<strong>Destinations Region Clusters</strong> · "${escapeHtml(trunc(p.heading || '', 35))}" · source:<code>${escapeHtml(p.source || 'orderedClusters')}</code> · ${p.sticky_nav === false ? 'flat nav' : 'sticky jump-to nav'} · accent:${escapeHtml(p.accent || 'brand')}`;
            case 'destcluster_hero': return `<strong>Cluster Hero</strong> · eyebrow:"${escapeHtml(trunc(p.eyebrow || 'Destination Guide', 28))}" · ${p.show_breadcrumb === false ? 'no breadcrumb' : 'breadcrumb'} · region name auto (Tahu) + photo bg`;
            case 'destcluster_whats_in': return `<strong>${escapeHtml(trunc(p.heading_prefix || "What's In", 20))} [region]?</strong> · ${p.show_intro === false ? 'no intro' : 'intro'} + keyword photo cards (live)`;
            case 'destcluster_featured_spots': return `<strong>Featured ${escapeHtml(trunc(p.tahu_word || 'Tourist Spots', 20))}</strong> · 3-col crossfading spot cards · "${escapeHtml(trunc(p.description || '', 40))}"`;
            case 'destcluster_testimonials': return `<strong>What ${escapeHtml(trunc(p.tahu_word || 'Travelers Say', 18))} About [region]</strong> · placeholder reviews (real spot names) · ${escapeHtml(trunc(p.description || 'auto blurb', 36))}`;
            case 'destcluster_explore_regions': return `<strong>${escapeHtml(trunc(p.heading || 'Explore Other Regions', 30))}</strong> · other-region cards + crossfading circles`;
            case 'destcluster_hashtags': return `<strong>#${escapeHtml(trunc(p.heading || 'Tags', 18))}</strong> · dynamic hashtags from the region's keyword pages`;
            case 'home_hero_centered': return `<strong>Home Hero (centered)</strong> · accent:${escapeHtml(p.accent || 'brand')} · "${escapeHtml(trunc(p.title || '', 40))}" · ${(p.stats || []).length} stats · ${(p.primary_cta && p.primary_cta.label) ? 'primary+secondary' : 'no CTAs'}`;
            case 'home_keyword_grid': return `<strong>Home Keyword Grid</strong> · "${escapeHtml(trunc(p.heading || '', 35))}" · source:<code>${escapeHtml(p.source || 'featuredKeywords')}</code> · ${p.columns || 3}-up`;
            case 'home_region_grid': return `<strong>Home Region Grid</strong> · "${escapeHtml(trunc(p.heading || '', 35))}" · source:<code>${escapeHtml(p.source || 'regions')}</code> · bg:${escapeHtml(p.bg || 'soft')}`;
            case 'home_resort_grid': return `<strong>Home Resort Grid</strong> · "${escapeHtml(trunc(p.heading || '', 35))}" · source:<code>${escapeHtml(p.source || 'featuredResorts')}</code>`;
            case 'home_blog_strip': return `<strong>Home Blog Strip</strong> · "${escapeHtml(trunc(p.heading || '', 35))}" · source:<code>${escapeHtml(p.source || 'latestPosts')}</code>`;
            case 'home_cta_band': return `<strong>Home CTA Band</strong> · accent:${escapeHtml(p.accent || 'brand')} · "${escapeHtml(trunc(p.heading || '', 40))}" · ${(p.cta && p.cta.label) ? 'CTA:' + escapeHtml(p.cta.label) : 'no CTA'}`;
            case 'home_unified_search': return `<strong>Home Unified Search</strong> · "${escapeHtml(trunc(p.title || '', 35))}" · ${(p.tabs || []).length} tabs · ${(p.chips || []).length} chips · reads <code>unifiedSearchIndex</code>`;
            case 'home_editorial_intro': return `<strong>Home Editorial Intro</strong> · "${escapeHtml(trunc(p.heading || '', 40))}" · ${(p.paragraphs || []).length} paragraphs${p.image_position && p.image_position !== 'none' ? ' · image-' + p.image_position : ''}`;
            case 'home_experience_grid': return `<strong>Home Experience Grid</strong> · "${escapeHtml(trunc(p.heading || '', 30))}" · ${(p.categories || []).length} tiles · ${p.columns || 3}-up`;
            case 'home_hub_links': return `<strong>Home Hub Links</strong> · "${escapeHtml(trunc(p.heading || '', 35))}" · ${(p.hubs || []).length} cards`;
            case 'home_season_guide': return `<strong>Home Season Guide</strong> · "${escapeHtml(trunc(p.heading || '', 35))}" · ${(p.seasons || []).length} season cards`;
            case 'home_testimonials': return `<strong>Home Testimonials</strong> · "${escapeHtml(trunc(p.heading || '', 35))}" · ${(p.reviews || []).length} reviews`;
            case 'home_faq': return `<strong>Home FAQ</strong> · "${escapeHtml(trunc(p.heading || '', 35))}" · ${(p.faqs || []).length} Q&A · FAQPage JSON-LD`;
            case 'home_how_it_works': return `<strong>Home How It Works</strong> · "${escapeHtml(trunc(p.heading || '', 35))}" · ${(p.pillars || p.items || []).length} steps · ${escapeHtml(p.layout || 'accordion')}`;
            case 'home_values_grid': return `<strong>Home Values Grid</strong> · "${escapeHtml(trunc(p.heading || '', 35))}" · ${(p.values || []).length} cards · ${p.columns || 4}-up`;
            case 'hub_hero': return `<strong>Hub Hero</strong> · accent:${escapeHtml(p.accent || 'brand')} · "${escapeHtml(trunc(p.title || '', 40))}" · ${(p.paragraphs || []).length} paragraphs`;
            case 'hub_category_nav': return `<strong>Hub Category Nav</strong> · ${p.sticky === false ? 'flat' : 'sticky'} · accent:${escapeHtml(p.accent || 'slate')} · reads <code>categories</code>`;
            case 'hub_category_grid': return `<strong>Hub Category Grid</strong> · key:<code>${escapeHtml(p.category_key || '(none)')}</code> · ${p.as_accordion === false ? 'flat' : 'accordion'} · ${p.cards_per_row || 4}-up`;
            case 'hub_footer_rail': return `<strong>Hub Footer Rail</strong> · "${escapeHtml(trunc(p.heading || '', 40))}" · ${(p.links || []).length} link pills`;
            case 'partner_hero': return `<strong>Partner Hero</strong> · "${escapeHtml(trunc((p.title || '').replace(/\{\{\/?accent\}\}/g, ''), 42))}" · ${Array.isArray(p.trust_points) ? p.trust_points.length : 0} trust points${p.image ? ' · image' : ''}`;
            case 'partner_audience': return `<strong>Who Can Join</strong> · "${escapeHtml(trunc((p.heading || '').replace(/\*/g, ''), 38))}" · ${(p.items || []).length} icon cards`;
            case 'partner_steps': return `<strong>How It Works</strong> · "${escapeHtml(trunc((p.heading || '').replace(/\*/g, ''), 38))}" · ${(p.steps || []).length} steps`;
            case 'partner_badge': return `<strong>Verified Badge</strong> · "${escapeHtml(trunc((p.heading || '').replace(/\*/g, ''), 38))}" · ${Array.isArray(p.points) ? p.points.length : 0} points · image ${escapeHtml(p.image_side || 'left')}`;
            case 'partner_perks': return `<strong>Perks Grid</strong> · "${escapeHtml(trunc((p.heading || '').replace(/\*/g, ''), 38))}" · ${(p.perks || []).length} perks`;
            case 'pros_cons': return `<strong>${(p.pros||[]).length}</strong> "${escapeHtml(p.pros_label || 'Best for')}" · <strong>${(p.cons||[]).length}</strong> "${escapeHtml(p.cons_label || 'Skip if')}"`;
            case 'summary_accordion': return `<strong>${(p.items||[]).length}</strong> sections${(p.items||[])[0]?.title ? ' · "' + escapeHtml(trunc((p.items[0]||{}).title, 40)) + '"' : ''}`;
            case 'image_text_pair': return `${p.image_position === 'right' ? 'Text ↔ image' : 'Image ↔ text'} · "${escapeHtml(trunc(p.title || '', 50))}"`;
            case 'traveler_reviews': return `<strong>${(p.items||[]).length}</strong> reviews · "${escapeHtml(p.heading || 'What travelers are saying')}"`;
            case 'map_embed': return `Map · ${p.height || 450}px · ${p.embed_url ? '<strong>configured</strong>' : '<em>no URL yet</em>'}`;
            case 'local_tip': return `<strong>${escapeHtml(p.eyebrow || 'Local tip')}</strong> · "${escapeHtml(trunc(p.body, 70))}"`;
            case 'related_guides': return `<strong>${(p.items||[]).length}</strong> guides · "${escapeHtml(p.heading || 'Related guides')}"`;
            case 'data_table': return `<strong>${(p.headers||[]).length}</strong> columns · <strong>${(p.rows||[]).length}</strong> rows${p.caption ? ' · "' + escapeHtml(trunc(p.caption, 40)) + '"' : ''}`;
            case 'section_header': return `H2 "${escapeHtml(trunc(p.heading || '', 50))}"${p.subtitle ? ' · "' + escapeHtml(trunc(p.subtitle, 60)) + '"' : ''}`;
            case 'tag_pills': return `<strong>${(p.items||[]).length}</strong> pills${(p.items||[])[0] ? ' · "' + escapeHtml(trunc((p.items[0]||{}).text || p.items[0], 50)) + '"' : ''}`;
            case 'external_guides': return `<strong>${(p.items||[]).length}</strong> guides · "${escapeHtml(trunc(p.heading || 'Compare picks', 50))}"`;
            case 'author': {
                const links = (p.links||[]).filter(l => l.url).length;
                const name = (p.name||'').trim() || 'Author card';
                return `<strong>${escapeHtml(trunc(name, 40))}</strong>${p.role ? ` · ${escapeHtml(trunc(p.role, 35))}` : ''}${links ? ` · ${links} link${links===1?'':'s'}` : ''}`;
            }
            case 'nearby_destinations': {
                const auto = (p.auto_from_cluster||'').trim();
                const itemCount = (p.items||[]).length;
                if (itemCount) return `<strong>${itemCount}</strong> destinations · "${escapeHtml(trunc(p.heading || 'Stay nearby', 45))}"`;
                if (auto) return `<em>auto from cluster <strong>${escapeHtml(auto)}</strong></em> · max ${p.max||6}`;
                return `<em>No items yet</em>`;
            }
            case 'related_blogs': {
                const auto = (p.auto_from_keywords||[]).filter(Boolean);
                const itemCount = (p.items||[]).length;
                if (itemCount) return `<strong>${itemCount}</strong> posts · "${escapeHtml(trunc(p.heading || 'More reads', 45))}"`;
                if (auto.length) return `<em>auto by tags</em> · "${auto.slice(0,3).join(', ')}${auto.length>3?', …':''}" · max ${p.max||3}`;
                return `<em>No items yet</em>`;
            }
            default: return '';
        }
    }

    /**
     * Return up to 3 image URLs the block carries (for inline thumbs).
     * For custom_html, parse the first <img> tag heuristically.
     */
    function blockThumbs(block) {
        const p = block.payload || {};
        let srcs = [];
        if (block.type === 'image' && p.src) srcs = [p.src];
        else if (block.type === 'hero_slider') srcs = (p.images || []).map(i => i.src).filter(Boolean);
        else if (block.type === 'gallery') srcs = (p.images || []).map(i => i.src).filter(Boolean);
        else if (block.type === 'attractions') srcs = (p.items || []).map(i => i.image).filter(Boolean);
        else if (block.type === 'custom_html' && p.html) {
            const matches = [...String(p.html).matchAll(/<img\s[^>]*src=["']([^"']+)["']/gi)];
            srcs = matches.slice(0, 3).map(m => m[1]);
        }
        else if (block.type === 'image_text_pair' && p.image) srcs = [p.image];
        return srcs.slice(0, 3).map(s => mediaUrl(s));
    }

    function renderThumbs(srcs, total) {
        if (!srcs.length) return '';
        const imgs = srcs.map((s, i) => `
            <a href="#" class="rg-block-thumb" data-thumb-src="${escapeHtml(s)}" data-thumb-slot="${i}" title="Click to enlarge / change">
                <img src="${escapeHtml(s)}" alt="" loading="lazy">
                <span class="rg-block-thumb-change" data-thumb-change>
                    <i class="bx bx-image-add"></i>
                    <span>Change</span>
                </span>
            </a>
        `).join('');
        const more = (total && total > srcs.length)
            ? `<span class="rg-block-thumb-more">+${total - srcs.length}</span>`
            : '';
        return `<div class="rg-block-thumbs">${imgs}${more}</div>`;
    }

    function blockEl(block) {
        const div = document.createElement('div');
        div.className = 'rg-block';
        div.dataset.id = block.id;
        div.dataset.type = block.type;
        const color = BLOCK_COLORS[block.type] || '#6b7280';
        const iconClass = BLOCK_ICONS[block.type] || 'bx-shape-square';
        const title = labels[block.type] || block.type;
        const meta = blockMeta(block);
        const thumbs = blockThumbs(block);
        // Total image count (for the "+N" pill when there are more than 3).
        const p = block.payload || {};
        let totalImages = thumbs.length;
        if (block.type === 'hero_slider') totalImages = (p.images || []).filter(i => i.src).length;
        if (block.type === 'gallery')     totalImages = (p.images || []).filter(i => i.src).length;
        if (block.type === 'attractions') totalImages = (p.items || []).filter(i => i.image).length;
        div.innerHTML = `
            <div class="rg-block-row">
                <button type="button" class="rg-block-handle" title="Drag to reorder">
                    <i class="bx bx-grid-vertical"></i>
                </button>
                <div class="rg-block-icon" style="background:${color}" title="${escapeHtml(title)}">
                    <i class="bx ${iconClass}"></i>
                </div>
                <div class="rg-block-body">
                    <div class="rg-block-title">${escapeHtml(title)}</div>
                    <div class="rg-block-meta">${meta || '<em>No content yet</em>'}</div>
                </div>
                ${renderThumbs(thumbs, totalImages)}
                <div class="rg-block-actions">
                    <button type="button" class="edit" title="Edit block"><i class="bx bx-edit"></i></button>
                    <button type="button" class="delete" title="Delete block"><i class="bx bx-trash"></i></button>
                </div>
                <button type="button" class="rg-block-toggle" title="Expand / collapse preview" data-rg-toggle>
                    <i class="bx bx-chevron-down"></i>
                </button>
            </div>
            <div class="rg-block-expansion" data-rg-expansion>
                <div class="rg-block-expansion-body" data-rg-expansion-body></div>
            </div>
        `;
        div.querySelector('.edit').addEventListener('click', () => openEditor(block));
        div.querySelector('.delete').addEventListener('click', () => deleteBlock(block));
        const toggle = div.querySelector('[data-rg-toggle]');
        toggle.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleExpand(div, block);
        });
        return div;
    }

    /**
     * Lazy-mount the iframe preview when a row first expands. Reuses
     * the iframe wrap + postMessage height sync we built for the
     * editor modal — same scaling, same preview endpoint.
     */
    function toggleExpand(rowEl, block) {
        const isOpen = rowEl.classList.toggle('is-expanded');
        const body = rowEl.querySelector('[data-rg-expansion-body]');
        if (!isOpen || !body) return;
        // Already populated? Don't refetch.
        if (body.dataset.rgFilled) return;
        body.dataset.rgFilled = '1';
        if (!block.id || !cfg.frontendUrl) {
            body.innerHTML = '<div style="color:#94a3b8;font-size:12px;font-style:italic;text-align:center;padding:16px">Preview is available after the block is saved.</div>';
            return;
        }
        body.innerHTML = `
            <div class="rg-iframe-wrap" data-iframe-wrap data-block-id="${block.id}">
                <div class="rg-iframe-loading" data-iframe-loading><i class="bx bx-loader bx-spin"></i> Loading preview</div>
                <iframe class="rg-block-iframe" data-block-id="${block.id}"
                    src="${escapeHtml(cfg.frontendUrl + '/_preview/block/' + block.id)}"
                    loading="lazy" referrerpolicy="no-referrer"
                    sandbox="allow-scripts allow-same-origin"></iframe>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:6px;">
                <button type="button" class="btn btn-sm btn-outline-primary" data-rg-fulledit>
                    <i class="bx bx-edit"></i> Full edit
                </button>
            </div>
        `;
        body.querySelector('[data-rg-fulledit]')?.addEventListener('click', () => openEditor(block));
    }

    function render() {
        const canvas = document.getElementById('rgBlocksCanvas');
        if (!state.blocks.length) {
            canvas.classList.add('empty');
            canvas.innerHTML = '<i class="bx bx-layer" style="font-size:36px;color:#cbd5e1"></i><div>No blocks yet. Use the toolbar above to add one.</div>';
            return;
        }
        canvas.classList.remove('empty');
        canvas.innerHTML = '';
        state.blocks.forEach(b => canvas.appendChild(blockEl(b)));
    }

    function fetchBlocks() {
        const url = `${cfg.urls.list}?owner_type=${encodeURIComponent(cfg.ownerType)}&owner_id=${encodeURIComponent(cfg.ownerId)}`;
        fetch(url, { credentials: 'same-origin' })
            .then(r => r.json())
            .then(d => {
                state.blocks = d.blocks || [];
                // Focus mode (Live Editor's "edit one block" modal):
                // filter the list to just the requested block so the
                // builder shows nothing else. Add/move/delete actions
                // still post against this single block.
                if (cfg.focusBlockId) {
                    state.blocks = state.blocks.filter(b => b.id === cfg.focusBlockId);
                }
                render();
                initSortable();
            });
    }

    function initSortable() {
        const canvas = document.getElementById('rgBlocksCanvas');
        if (canvas._sortable || canvas.classList.contains('empty')) return;
        canvas._sortable = new Sortable(canvas, {
            handle: '.rg-block-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            onEnd: () => {
                const ids = Array.from(canvas.querySelectorAll('.rg-block')).map(el => el.dataset.id);
                fetch(cfg.urls.reorder, {
                    method: 'POST',
                    headers: rgCsrfHeaders({ 'Content-Type': 'application/json' }),
                    credentials: 'same-origin',
                    body: JSON.stringify({ ids }),
                });
                state.blocks.sort((a,b) => ids.indexOf(String(a.id)) - ids.indexOf(String(b.id)));
            },
        });
    }

    function deleteBlock(block) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ title: 'Delete this block?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete', confirmButtonColor: '#dc2626' })
                .then(r => { if (r.isConfirmed) doDelete(block); });
        } else if (confirm('Delete this block?')) {
            doDelete(block);
        }
    }
    function doDelete(block) {
        fetch(cfg.urls.delete, {
            method: 'POST',
            headers: rgCsrfHeaders({ 'Content-Type': 'application/json' }),
            credentials: 'same-origin',
            body: JSON.stringify({ id: block.id }),
        }).then(r => r.json()).then(() => {
            state.blocks = state.blocks.filter(b => b.id !== block.id);
            render();
            if (typeof toastr !== 'undefined') toastr.success('Block deleted');
        });
    }

    // ════════════════════════════════════════════════════════════════
    //  Home-block repeatable-row editors with real image pickers.
    //  Each image-bearing home array (logos, features, items, badge
    //  slider images, reviews) is edited as rows of fields where every
    //  image uses the media box / upload picker — no raw JSON.
    // ════════════════════════════════════════════════════════════════
    const HOME_ARRAY_SCHEMAS = {
        home_press_logos: [
            { field: 'logos', title: 'Logo', add: '+ Add logo', cols: [
                { k: 'image', t: 'image', label: 'Logo image' },
                { k: 'name', t: 'text', label: 'Name (used as alt text)' },
            ] },
        ],
        home_alt_features: [
            { field: 'features', title: 'Feature row', add: '+ Add row', cols: [
                { k: 'eyebrow', t: 'text', label: 'Eyebrow' },
                { k: 'heading', t: 'text', label: 'Heading' },
                { k: 'body', t: 'textarea', label: 'Body' },
                { k: 'accent', t: 'select', label: 'Accent', opts: ['blue','rose','emerald','amber','violet','teal'] },
                { k: 'image', t: 'image', label: 'Main image', altk: 'image_alt' },
                { k: 'images', t: 'imagelist', label: 'Crossfade slides (optional)' },
            ] },
        ],
        home_category_accordion: [
            { field: 'items', title: 'Panel', add: '+ Add panel', cols: [
                { k: 'label', t: 'text', label: 'Label' },
                { k: 'eyebrow', t: 'text', label: 'Eyebrow' },
                { k: 'description', t: 'textarea', label: 'Description' },
                { k: 'cta_label', t: 'text', label: 'CTA label' },
                { k: 'url', t: 'text', label: 'CTA url' },
                { k: 'accent', t: 'select', label: 'Accent', opts: ['brand','blue','rose','emerald','amber','violet','teal'] },
                { k: 'image', t: 'image', label: 'Main image', altk: 'image_alt' },
                { k: 'images', t: 'imagelist', label: 'Crossfade slides (optional)' },
            ] },
        ],
        home_badge_explainer: [
            { field: 'badge_images', title: 'Slider image', add: '+ Add slider image', cols: [
                { k: 'src', t: 'image', label: 'Image', altk: 'alt' },
            ] },
            { field: 'reviews', title: 'Review', add: '+ Add review', cols: [
                { k: 'text', t: 'textarea', label: 'Quote' },
                { k: 'author', t: 'text', label: 'Author' },
                { k: 'role', t: 'text', label: 'Role' },
                { k: 'avatar', t: 'image', label: 'Avatar' },
            ] },
        ],
        home_partner_perks: [
            { field: 'perks', title: 'Perk', add: '+ Add perk', cols: [
                { k: 'icon', t: 'iconpick', label: 'Icon (pick one or upload an image)' },
                { k: 'title', t: 'text', label: 'Title' },
                { k: 'body', t: 'textarea', label: 'Body' },
            ] },
            { field: 'reviews', title: 'Review', add: '+ Add review', cols: [
                { k: 'text', t: 'textarea', label: 'Quote' },
                { k: 'author', t: 'text', label: 'Author' },
                { k: 'role', t: 'text', label: 'Role' },
                { k: 'avatar', t: 'image', label: 'Avatar' },
            ] },
        ],
        home_how_it_works: [
            { field: 'pillars', title: 'Step', add: '+ Add step', cols: [
                { k: 'icon', t: 'iconpick', label: 'Icon (pick one or upload an image)' },
                { k: 'title', t: 'text', label: 'Title' },
                { k: 'body', t: 'textarea', label: 'Body (shown when expanded)' },
            ] },
        ],
        home_editorial_intro: [
            { field: 'images', title: 'Column image', add: '+ Add column image', cols: [
                { k: 'src', t: 'image', label: 'Image', altk: 'alt' },
            ] },
        ],
        home_season_guide: [
            { field: 'seasons', title: 'Slide', add: '+ Add slide', cols: [
                { k: 'image', t: 'image', label: 'Slide image', altk: 'image_alt' },
                { k: 'label', t: 'text', label: 'Season label (e.g. Summer)' },
                { k: 'headline', t: 'text', label: 'Headline' },
                { k: 'icon', t: 'text', label: 'Icon name (sun, rain, leaf, …)' },
                { k: 'accent', t: 'select', label: 'Accent', opts: ['amber', 'blue', 'rose', 'emerald', 'violet', 'teal', 'sky'] },
                { k: 'activities', t: 'stringlist', label: 'Activity chips' },
            ] },
            { field: 'mosaic', kind: 'flatimages', title: 'Mosaic images', add: '+ Add image' },
        ],
        home_testimonials: [
            { field: 'reviews', title: 'Review', add: '+ Add review', cols: [
                { k: 'text', t: 'textarea', label: 'Quote (card hidden if blank)' },
                { k: 'author', t: 'text', label: 'Author' },
                { k: 'location', t: 'text', label: 'Location' },
                { k: 'rating', t: 'select', label: 'Rating (stars)', opts: ['5', '4', '3', '2', '1'] },
                { k: 'avatar_url', t: 'image', label: 'Avatar (optional — falls back to an initial badge)' },
            ] },
        ],
        home_faq: [
            { field: 'faqs', title: 'Q&A', add: '+ Add Q&A', cols: [
                { k: 'question', t: 'text', label: 'Question' },
                { k: 'answer', t: 'textarea', label: 'Answer' },
            ] },
        ],
        hub_footer_rail: [
            { field: 'links', title: 'Link pill', add: '+ Add link', cols: [
                { k: 'label', t: 'text', label: 'Label' },
                { k: 'url', t: 'text', label: 'URL' },
                { k: 'theme', t: 'select', label: 'Theme', opts: ['slate', 'amber', 'indigo', 'rose', 'emerald', 'violet', 'teal'] },
            ] },
        ],
        dest_hero_search: [
            { field: 'breadcrumbs', title: 'Breadcrumb', add: '+ Add crumb', cols: [
                { k: 'label', t: 'text', label: 'Label' },
                { k: 'url', t: 'text', label: 'URL (leave blank on the last crumb = current page)' },
            ] },
            { field: 'stats_pills', title: 'Stat pill', add: '+ Add pill', cols: [
                { k: 'label', t: 'text', label: 'Label (e.g. "destinations")' },
                { k: 'value', t: 'text', label: 'Value (only used for the Literal source)' },
                { k: 'value_source', t: 'select', label: 'Value source', opts: ['literal', 'stats.total_destinations', 'stats.total_regions', 'stats.top_volume', 'stats.total_keywords', 'stats.total_areas', 'stats.featured_count'] },
            ] },
            { field: 'search_tabs', title: 'Filter tab', add: '+ Add tab', cols: [
                { k: 'value', t: 'text', label: 'Filter value (all / region / destination / spot / restaurant)' },
                { k: 'label', t: 'text', label: 'Tab label' },
            ] },
        ],
        partner_audience: [
            { field: 'items', title: 'Business type', add: '+ Add type', cols: [
                { k: 'title', t: 'text', label: 'Title (e.g. Tour Guides)' },
                { k: 'subtitle', t: 'text', label: 'Subtitle' },
                { k: 'icon', t: 'text', label: 'Icon SVG path d (advanced)' },
            ] },
        ],
        partner_steps: [
            { field: 'steps', title: 'Step', add: '+ Add step', cols: [
                { k: 'number', t: 'text', label: 'Number / label' },
                { k: 'color', t: 'select', label: 'Circle color', opts: ['brand', 'amber', 'emerald', 'rose', 'violet', 'teal'] },
                { k: 'title', t: 'text', label: 'Title' },
                { k: 'body', t: 'textarea', label: 'Body' },
            ] },
        ],
        partner_perks: [
            { field: 'perks', title: 'Perk', add: '+ Add perk', cols: [
                { k: 'title', t: 'text', label: 'Title' },
                { k: 'body', t: 'textarea', label: 'Body' },
                { k: 'icon', t: 'text', label: 'Icon SVG path d (advanced)' },
            ] },
        ],
    };

    function findHomeSchema(field) {
        for (const t in HOME_ARRAY_SCHEMAS) {
            const found = HOME_ARRAY_SCHEMAS[t].find(s => s.field === field);
            if (found) return found;
        }
        return null;
    }

    function homeListThumbHtml(src, alt, title) {
        return `<div class="rg-hlist-thumb" data-rg-img style="position:relative;display:inline-block;width:92px;vertical-align:top">
            <img data-rg-img-src="${escapeHtml(src)}" src="${escapeHtml(mediaUrl(src))}" style="width:92px;height:60px;object-fit:cover;border-radius:5px;border:1px solid #e2e8f0;display:block">
            <button type="button" class="btn btn-danger" data-rg-img-remove title="Remove" style="position:absolute;top:-7px;right:-7px;padding:0 6px;line-height:1.3;border-radius:50%;font-size:11px">&times;</button>
            <input type="text" data-rg-img-alt class="form-control form-control-sm mt-1" placeholder="Alt (SEO)" value="${escapeHtml(alt || '')}" style="font-size:10px;padding:1px 5px;height:auto">
            <input type="text" data-rg-img-title class="form-control form-control-sm mt-1" placeholder="Title (SEO)" value="${escapeHtml(title || '')}" style="font-size:10px;padding:1px 5px;height:auto">
        </div>`;
    }

    // The SEO "title" attribute key for an image column. Mirrors the alt
    // key naming so item shapes stay clean: altk 'alt' -> 'title',
    // 'image_alt' -> 'image_title'; columns with no altk (logo/avatar,
    // whose alt comes from a sibling name/author) -> '<key>_title'.
    function homeTitleKey(col) {
        if (col.titlek) return col.titlek;
        if (col.altk) return col.altk.replace(/alt$/i, 'title');
        return col.k + '_title';
    }

    function homeColHtml(col, item) {
        const v = item[col.k];
        if (col.t === 'image') {
            const src = v || '';
            const hasImg = !!src;
            const titleKey = homeTitleKey(col);
            let h = `<div class="rg-hcol"><label>${escapeHtml(col.label || col.k)}</label>
                <div class="d-flex align-items-center gap-2">
                    <img data-rg-col-thumb="${col.k}" src="${hasImg ? escapeHtml(mediaUrl(src)) : ''}" alt="" style="width:74px;height:54px;object-fit:cover;border-radius:6px;background:#f1f5f9;border:1px solid #e2e8f0">
                    <button type="button" class="rg-pick-btn" data-rg-col-pick="${col.k}"><i class="bx bx-image-add"></i> ${hasImg ? 'Change image' : 'Choose image'}</button>
                </div>
                <input type="hidden" data-rg-col="${col.k}" value="${escapeHtml(src)}">`;
            if (col.altk) {
                h += `<input type="text" class="form-control form-control-sm mt-1" data-rg-col="${col.altk}" placeholder="Alt text (SEO)" value="${escapeHtml(item[col.altk] || '')}">`;
            }
            h += `<input type="text" class="form-control form-control-sm mt-1" data-rg-col="${titleKey}" placeholder="Title text (SEO, optional)" value="${escapeHtml(item[titleKey] || '')}">`;
            return h + `</div>`;
        }
        if (col.t === 'imagelist') {
            const arr = Array.isArray(v) ? v : [];
            const thumbs = arr.map(im => homeListThumbHtml(typeof im === 'string' ? im : (im && im.src || ''), (im && im.alt) || '', (im && im.title) || '')).join('');
            return `<div class="rg-hcol"><label>${escapeHtml(col.label || col.k)}</label>
                <div class="d-flex flex-wrap gap-2 align-items-center" data-rg-collist="${col.k}">${thumbs}</div>
                <button type="button" class="rg-pick-btn mt-1" data-rg-collist-add="${col.k}"><i class="bx bx-image-add"></i> Add image</button>
            </div>`;
        }
        if (col.t === 'iconpick') {
            const cur = (v != null && v !== '') ? v : 'star';
            const isImg = isImageVal(cur);
            const buttons = PERK_ICON_KEYS.map(k => `<button type="button" class="rg-icon-btn ${(!isImg && cur === k) ? 'is-active' : ''}" data-rg-iconpick-opt="${k}" title="${k}">${perkIconSvg(k)}</button>`).join('');
            return `<div class="rg-hcol rg-iconpick">
                <label>${escapeHtml(col.label || col.k)}</label>
                <input type="hidden" data-rg-col="${col.k}" value="${escapeHtml(cur)}">
                <div class="rg-icon-picker">${buttons}</div>
                <div class="d-flex align-items-center gap-2 mt-1">
                    <img data-rg-iconpick-prev src="${isImg ? escapeHtml(mediaUrl(cur)) : ''}" style="${isImg ? '' : 'display:none;'}width:34px;height:34px;object-fit:cover;border-radius:6px;border:1px solid #e2e8f0">
                    <button type="button" class="rg-pick-btn" data-rg-iconpick-img><i class="bx bx-image-add"></i> Use an image icon</button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-rg-iconpick-clear title="Back to icon set" style="${isImg ? '' : 'display:none;'}"><i class="bx bx-x"></i></button>
                </div>
                <div class="form-text small">Pick an icon, or upload / choose an image to use as the icon.</div>
            </div>`;
        }
        if (col.t === 'select') {
            const opts = (col.opts || []).map(o => `<option value="${escapeHtml(o)}" ${String(v) === String(o) ? 'selected' : ''}>${escapeHtml(o)}</option>`).join('');
            return `<div class="rg-hcol"><label>${escapeHtml(col.label || col.k)}</label><select class="form-select form-select-sm" data-rg-col="${col.k}">${opts}</select></div>`;
        }
        if (col.t === 'textarea') {
            return `<div class="rg-hcol"><label>${escapeHtml(col.label || col.k)}</label><textarea class="form-control form-control-sm" rows="2" data-rg-col="${col.k}">${escapeHtml(v || '')}</textarea></div>`;
        }
        if (col.t === 'stringlist') {
            const arr = Array.isArray(v) ? v : (typeof v === 'string' ? v.split(/\r?\n/) : []);
            return `<div class="rg-hcol"><label>${escapeHtml(col.label || col.k)}</label><textarea class="form-control form-control-sm" rows="3" data-rg-col="${col.k}" placeholder="One item per line">${escapeHtml(arr.join('\n'))}</textarea><div class="form-text small">One item per line.</div></div>`;
        }
        return `<div class="rg-hcol"><label>${escapeHtml(col.label || col.k)}</label><input type="text" class="form-control form-control-sm" data-rg-col="${col.k}" value="${escapeHtml(v || '')}"></div>`;
    }

    function homeFlatThumbHtml(src) {
        return `<div class="rg-flat-thumb" data-rg-flat style="position:relative;display:inline-block">
            <img data-rg-flat-src="${escapeHtml(src)}" src="${escapeHtml(mediaUrl(src))}" style="width:64px;height:48px;object-fit:cover;border-radius:5px;border:1px solid #e2e8f0">
            <button type="button" class="btn btn-danger" data-rg-flat-remove title="Remove" style="position:absolute;top:-7px;right:-7px;padding:0 6px;line-height:1.3;border-radius:50%;font-size:11px">&times;</button>
        </div>`;
    }

    function homeRowHtml(field, sch, item) {
        item = item || {};
        const cols = sch.cols.map(col => homeColHtml(col, item)).join('');
        return `<div class="rg-edit-card" data-rg-hrow data-rg-field="${field}" style="border:1px solid #e2e8f0;border-radius:10px;padding:12px;background:#fff">
            <div class="rg-edit-card-hdr d-flex justify-content-between align-items-center mb-2">
                <div class="rg-edit-card-title" style="font-weight:600;color:#4338ca"><i class="bx bx-grid-vertical"></i> ${escapeHtml(sch.title)}</div>
                <button type="button" class="btn btn-sm btn-outline-danger" data-rg-row-remove title="Remove">&times;</button>
            </div>
            <div style="display:grid;gap:8px">${cols}</div>
        </div>`;
    }

    function homeArraySectionsHtml(blockType, p) {
        const schemas = HOME_ARRAY_SCHEMAS[blockType] || [];
        return schemas.map(sch => {
            // Tolerate a legacy JSON-string payload (blocks last saved through
            // an older JSON-textarea editor) so their rows still load + persist
            // instead of silently dropping to an empty array on next save.
            let raw = p[sch.field];
            if (typeof raw === 'string') { try { raw = JSON.parse(raw); } catch (e) { raw = []; } }
            const items = Array.isArray(raw) ? raw : [];
            if (sch.kind === 'flatimages') {
                const thumbs = items.map(s => homeFlatThumbHtml(typeof s === 'string' ? s : (s && s.src) || '')).join('');
                return `<div class="rg-edit-section">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label mb-0">${escapeHtml(sch.title)} (${items.length})</label>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-rg-flatadd="${sch.field}">${escapeHtml(sch.add)}</button>
                    </div>
                    <div data-rg-flatlist="${sch.field}" class="d-flex flex-wrap gap-2" style="max-height:280px;overflow:auto;padding:8px;border:1px solid #e2e8f0;border-radius:8px">${thumbs}</div>
                </div>`;
            }
            const rows = items.map(it => homeRowHtml(sch.field, sch, it)).join('');
            return `<div class="rg-edit-section">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label mb-0" style="text-transform:capitalize">${escapeHtml(sch.field.replace(/_/g, ' '))}</label>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-rg-add-field="${sch.field}">${escapeHtml(sch.add)}</button>
                </div>
                <div data-rg-hlist="${sch.field}" style="display:grid;gap:10px">${rows}</div>
            </div>`;
        }).join('');
    }

    function readHomeArrays(blockType) {
        const schemas = HOME_ARRAY_SCHEMAS[blockType] || [];
        const out = {};
        schemas.forEach(sch => {
            // Flat image grid (e.g. the season-guide mosaic) — stored as a
            // plain array of image-path strings, not row objects.
            if (sch.kind === 'flatimages') {
                const fa = [];
                document.querySelectorAll('[data-rg-flatlist="' + sch.field + '"] [data-rg-flat-src]').forEach(t => {
                    const s = t.getAttribute('data-rg-flat-src');
                    if (s) fa.push(s);
                });
                out[sch.field] = fa;
                return;
            }
            const arr = [];
            document.querySelectorAll('[data-rg-hrow][data-rg-field="' + sch.field + '"]').forEach(row => {
                const item = {};
                sch.cols.forEach(col => {
                    if (col.t === 'imagelist') {
                        const imgs = [];
                        row.querySelectorAll('[data-rg-collist="' + col.k + '"] [data-rg-img]').forEach(t => {
                            const srcEl = t.querySelector('[data-rg-img-src]');
                            const s = srcEl ? srcEl.getAttribute('data-rg-img-src') : '';
                            const altEl = t.querySelector('[data-rg-img-alt]');
                            const titleEl = t.querySelector('[data-rg-img-title]');
                            if (s) imgs.push({ src: s, alt: altEl ? altEl.value : '', title: titleEl ? titleEl.value : '' });
                        });
                        item[col.k] = imgs;
                    } else if (col.t === 'stringlist') {
                        const el = row.querySelector('[data-rg-col="' + col.k + '"]');
                        item[col.k] = el ? el.value.split(/\r?\n/).map(s => s.trim()).filter(Boolean) : [];
                    } else {
                        const el = row.querySelector('[data-rg-col="' + col.k + '"]');
                        if (el) item[col.k] = el.value;
                        if (col.altk) {
                            const ae = row.querySelector('[data-rg-col="' + col.altk + '"]');
                            if (ae) item[col.altk] = ae.value;
                        }
                        if (col.t === 'image') {
                            const tk = homeTitleKey(col);
                            const te = row.querySelector('[data-rg-col="' + tk + '"]');
                            if (te) item[tk] = te.value;
                        }
                    }
                });
                arr.push(item);
            });
            out[sch.field] = arr;
        });
        return out;
    }

    function wireHomeArrays() {
        const body = document.getElementById('blockEditorBody');
        if (!body || body.dataset.hrowWired === '1') return;
        body.dataset.hrowWired = '1';
        body.addEventListener('click', function (e) {
            const addBtn = e.target.closest('[data-rg-add-field]');
            if (addBtn) {
                const field = addBtn.getAttribute('data-rg-add-field');
                // Scope to the OPEN block's type first: the same field name
                // (e.g. 'reviews') exists in several schemas, so the global
                // findHomeSchema() would build a row from the wrong block.
                const bt = state.current ? state.current.type : null;
                const sch = (HOME_ARRAY_SCHEMAS[bt] || []).find(s => s.field === field) || findHomeSchema(field);
                const list = body.querySelector('[data-rg-hlist="' + field + '"]');
                if (sch && list) list.insertAdjacentHTML('beforeend', homeRowHtml(field, sch, {}));
                return;
            }
            const rm = e.target.closest('[data-rg-hrow] [data-rg-row-remove]');
            if (rm) { const r = rm.closest('[data-rg-hrow]'); if (r) r.remove(); return; }
            const imgRm = e.target.closest('[data-rg-img-remove]');
            if (imgRm) { const t = imgRm.closest('[data-rg-img]'); if (t) t.remove(); return; }
            const pick = e.target.closest('[data-rg-col-pick]');
            if (pick) {
                const key = pick.getAttribute('data-rg-col-pick');
                const row = pick.closest('[data-rg-hrow]');
                openMediaPicker({ onSelect: function (media) {
                    const hidden = row.querySelector('[data-rg-col="' + key + '"]');
                    if (hidden) hidden.value = media.url;
                    const thumb = row.querySelector('[data-rg-col-thumb="' + key + '"]');
                    if (thumb) thumb.src = mediaUrl(media.url);
                    pick.innerHTML = '<i class="bx bx-image-add"></i> Change image';
                } });
                return;
            }
            const listAdd = e.target.closest('[data-rg-collist-add]');
            if (listAdd) {
                const key = listAdd.getAttribute('data-rg-collist-add');
                const row = listAdd.closest('[data-rg-hrow]');
                const list = row.querySelector('[data-rg-collist="' + key + '"]');
                openMediaPicker({ onSelect: function (media) {
                    if (list) list.insertAdjacentHTML('beforeend', homeListThumbHtml(media.url, '', ''));
                } });
                return;
            }
            // Flat image grid (mosaic): add / remove bare-string images.
            const flatAdd = e.target.closest('[data-rg-flatadd]');
            if (flatAdd) {
                const field = flatAdd.getAttribute('data-rg-flatadd');
                const list = body.querySelector('[data-rg-flatlist="' + field + '"]');
                openMediaPicker({ onSelect: function (media) {
                    if (list) list.insertAdjacentHTML('beforeend', homeFlatThumbHtml(media.url));
                } });
                return;
            }
            const flatRm = e.target.closest('[data-rg-flat-remove]');
            if (flatRm) { const t = flatRm.closest('[data-rg-flat]'); if (t) t.remove(); return; }
            // Icon-or-image picker (e.g. Partner perks): pick a named icon,
            // upload/choose an image to use as the icon, or reset to icons.
            const iconOpt = e.target.closest('[data-rg-iconpick-opt]');
            if (iconOpt) {
                const wrap = iconOpt.closest('.rg-iconpick');
                const hidden = wrap.querySelector('[data-rg-col]');
                if (hidden) hidden.value = iconOpt.getAttribute('data-rg-iconpick-opt');
                wrap.querySelectorAll('.rg-icon-btn.is-active').forEach(b => b.classList.remove('is-active'));
                iconOpt.classList.add('is-active');
                const prev = wrap.querySelector('[data-rg-iconpick-prev]');
                if (prev) { prev.style.display = 'none'; prev.removeAttribute('src'); }
                const clr = wrap.querySelector('[data-rg-iconpick-clear]');
                if (clr) clr.style.display = 'none';
                return;
            }
            const iconImg = e.target.closest('[data-rg-iconpick-img]');
            if (iconImg) {
                const wrap = iconImg.closest('.rg-iconpick');
                openMediaPicker({ onSelect: function (media) {
                    const hidden = wrap.querySelector('[data-rg-col]');
                    if (hidden) hidden.value = media.url;
                    wrap.querySelectorAll('.rg-icon-btn.is-active').forEach(b => b.classList.remove('is-active'));
                    const prev = wrap.querySelector('[data-rg-iconpick-prev]');
                    if (prev) { prev.src = mediaUrl(media.url); prev.style.display = ''; }
                    const clr = wrap.querySelector('[data-rg-iconpick-clear]');
                    if (clr) clr.style.display = '';
                } });
                return;
            }
            const iconClr = e.target.closest('[data-rg-iconpick-clear]');
            if (iconClr) {
                const wrap = iconClr.closest('.rg-iconpick');
                const hidden = wrap.querySelector('[data-rg-col]');
                if (hidden) hidden.value = 'star';
                const prev = wrap.querySelector('[data-rg-iconpick-prev]');
                if (prev) { prev.style.display = 'none'; prev.removeAttribute('src'); }
                iconClr.style.display = 'none';
                wrap.querySelectorAll('.rg-icon-btn.is-active').forEach(b => b.classList.remove('is-active'));
                const first = wrap.querySelector('.rg-icon-btn[data-rg-iconpick-opt="star"]') || wrap.querySelector('.rg-icon-btn');
                if (first) first.classList.add('is-active');
                return;
            }
        });
    }

    function editorForm(block) {
        const p = block.payload || {};
        switch (block.type) {
            case 'heading':
                return `
                    <div class="mb-3"><label>Level</label>
                        <select class="form-select" data-field="level">
                            <option value="h2" ${p.level==='h2'?'selected':''}>H2 (section heading)</option>
                            <option value="h3" ${p.level==='h3'?'selected':''}>H3 (sub heading)</option>
                        </select>
                    </div>
                    <div class="mb-3"><label>Text</label>
                        <input type="text" class="form-control" data-field="text" value="${escapeHtml(p.text)}">
                    </div>`;
            case 'rich_text':
                return htmlTabbedEditor(p.html || '', 'visual', 'rich_text');
            case 'image':
                return `
                    <div class="mb-3"><label>Image</label>
                        <div class="d-flex align-items-center gap-2 mb-2" data-rg-image-row>
                            <button type="button" class="rg-pick-btn ${p.src ? '' : 'is-empty'}" data-rg-pick="src">
                                <i class="bx bx-image-add"></i> ${p.src ? 'Change image' : 'Choose image'}
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-rg-clear="src" ${p.src ? '' : 'style="display:none"'}><i class="bx bx-x"></i></button>
                        </div>
                        <input type="hidden" data-field="src" value="${escapeHtml(p.src||'')}">
                        <img class="rg-image-preview" data-rg-preview="src" src="${p.src ? escapeHtml(mediaUrl(p.src)) : ''}" ${p.src ? '' : 'style="display:none"'}>
                    </div>
                    <div class="mb-3"><label>Alt text (for accessibility + SEO)</label>
                        <input type="text" class="form-control" data-field="alt" value="${escapeHtml(p.alt||'')}">
                    </div>
                    <div class="mb-3"><label>Title text (SEO, optional)</label>
                        <input type="text" class="form-control" data-field="title" value="${escapeHtml(p.title||'')}">
                    </div>
                    <div class="mb-3"><label>Caption (optional)</label>
                        <input type="text" class="form-control" data-field="caption" value="${escapeHtml(p.caption||'')}">
                    </div>
                    <div class="mb-3"><label>Alignment</label>
                        <select class="form-select" data-field="align">
                            <option value="left" ${p.align==='left'?'selected':''}>Left</option>
                            <option value="center" ${p.align==='center'?'selected':''}>Center</option>
                            <option value="right" ${p.align==='right'?'selected':''}>Right</option>
                        </select>
                    </div>`;
            case 'gallery':
                return `
                    <div class="mb-3 d-flex gap-2 align-items-center">
                        <button type="button" class="rg-pick-btn" id="rgGalleryAddPick"><i class="bx bx-image-add"></i> Add image to gallery</button>
                        <div class="form-help m-0">Pick from the library or upload a new file. Add as many as you need.</div>
                    </div>
                    <div class="mb-3"><label>Columns</label>
                        <select class="form-select" data-field="columns">
                            <option value="2" ${p.columns==2?'selected':''}>2</option>
                            <option value="3" ${p.columns==3?'selected':''}>3</option>
                            <option value="4" ${p.columns==4?'selected':''}>4</option>
                        </select>
                    </div>
                    <div id="rgGalleryList">${(p.images||[]).map((i, idx) => `<div class="rg-gallery-row" data-idx="${idx}" data-src="${escapeHtml(i.src||'')}"><img src="${mediaUrl(i.src)}" style="width:80px;height:60px;object-fit:cover;border-radius:3px"><div class="d-flex flex-column gap-1 flex-grow-1"><input type="text" class="form-control form-control-sm" placeholder="Alt text (SEO)" data-gallery-alt value="${escapeHtml(i.alt||'')}"><input type="text" class="form-control form-control-sm" placeholder="Title text (SEO, optional)" data-gallery-title value="${escapeHtml(i.title||'')}"></div><button type="button" class="btn btn-sm btn-outline-danger" data-gallery-remove>&times;</button></div>`).join('')}</div>
                    <input type="hidden" data-field="images" value='${JSON.stringify(p.images||[])}'>`;
            case 'video':
                return `
                    <div class="mb-3"><label>YouTube video ID</label>
                        <input type="text" class="form-control" data-field="youtube_id" value="${escapeHtml(p.youtube_id||'')}" placeholder="e.g. dQw4w9WgXcQ">
                        <div class="form-help">From a URL like https://www.youtube.com/watch?v=<strong>dQw4w9WgXcQ</strong></div>
                    </div>
                    <div class="mb-3"><label>OR upload a video file</label>
                        <input type="file" class="form-control" accept="video/*" data-upload-target="src">
                        <input type="hidden" data-field="src" value="${escapeHtml(p.src||'')}">
                        ${p.src ? `<div class="mt-2"><small>Current: ${escapeHtml(p.src)}</small></div>` : ''}
                    </div>
                    <div class="mb-3"><label>Caption (optional)</label>
                        <input type="text" class="form-control" data-field="caption" value="${escapeHtml(p.caption||'')}">
                    </div>`;
            case 'faq':
                return `
                    <div id="rgFaqList">${(p.items||[]).map((f, idx) => `<div class="rg-faq-row" data-idx="${idx}"><input type="text" class="form-control form-control-sm" placeholder="Question" data-faq-q value="${escapeHtml(f.question||'')}"><input type="text" class="form-control form-control-sm" placeholder="Answer" data-faq-a value="${escapeHtml(f.answer||'')}"><button type="button" class="btn btn-sm btn-outline-danger" data-faq-remove>&times;</button></div>`).join('')}</div>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="rgFaqAdd"><i class="bx bx-plus"></i> Add FAQ</button>
                    <input type="hidden" data-field="items" value='${JSON.stringify(p.items||[])}'>`;
            case 'cta':
                return `
                    <div class="mb-3"><label>Headline</label>
                        <input type="text" class="form-control" data-field="headline" value="${escapeHtml(p.headline||'')}">
                    </div>
                    <div class="mb-3"><label>Body text</label>
                        <textarea class="form-control" rows="2" data-field="text">${escapeHtml(p.text||'')}</textarea>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3"><label>Button text</label>
                            <input type="text" class="form-control" data-field="button_text" value="${escapeHtml(p.button_text||'')}">
                        </div>
                        <div class="col-6 mb-3"><label>Button URL</label>
                            <input type="text" class="form-control" data-field="button_url" value="${escapeHtml(p.button_url||'')}">
                        </div>
                    </div>
                    <div class="mb-3"><label>Style</label>
                        <select class="form-select" data-field="style">
                            <option value="primary" ${p.style==='primary'?'selected':''}>Primary</option>
                            <option value="secondary" ${p.style==='secondary'?'selected':''}>Secondary</option>
                            <option value="outline" ${p.style==='outline'?'selected':''}>Outline</option>
                        </select>
                    </div>`;
            case 'two_column':
                return `
                    <div class="mb-3"><label>Left column</label>
                        <div id="rgQuillLeft" style="background:white">${p.left_html || ''}</div>
                        <input type="hidden" data-field="left_html">
                    </div>
                    <div class="mb-3"><label>Right column</label>
                        <div id="rgQuillRight" style="background:white">${p.right_html || ''}</div>
                        <input type="hidden" data-field="right_html">
                    </div>`;
            case 'listing_slot':
                return `
                    <div class="alert alert-info"><i class="bx bx-info-circle me-1"></i>This block renders paid resort listings sorted by bid. Owners bid GP to climb here.</div>
                    <div class="mb-3"><label>Section label (shown above listings)</label>
                        <input type="text" class="form-control" data-field="slot_label" value="${escapeHtml(p.slot_label||'Featured Resorts')}">
                    </div>
                    <div class="mb-3"><label>Fallback HTML (shown when no paid listings exist)</label>
                        <textarea class="form-control" rows="4" data-field="fallback_html">${escapeHtml(p.fallback_html||'')}</textarea>
                    </div>`;
            case 'quote':
                return `
                    <div class="mb-3"><label>Quote</label>
                        <textarea class="form-control" rows="3" data-field="text">${escapeHtml(p.text||'')}</textarea>
                    </div>
                    <div class="mb-3"><label>Author (optional)</label>
                        <input type="text" class="form-control" data-field="author" value="${escapeHtml(p.author||'')}">
                    </div>`;
            case 'divider':
                return `<div class="mb-3"><label>Style</label>
                    <select class="form-select" data-field="style">
                        <option value="line" ${p.style==='line'?'selected':''}>Plain line</option>
                        <option value="dots" ${p.style==='dots'?'selected':''}>Dots</option>
                        <option value="thick" ${p.style==='thick'?'selected':''}>Thick line</option>
                    </select>
                </div>`;
            case 'custom_html':
                return htmlTabbedEditor(p.html || '', 'code', 'custom_html');

            // ── Resort Guru custom-element editors. Each one is a structured
            //    form (not raw HTML) that maps 1:1 to the public-page render.

            case 'hero_slider':
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-slideshow me-1"></i>Splide carousel. Pick a layout style, set an optional eyebrow header, then add slides. Each slide can have a title + caption + optional credit URL.
                    </div>
                    <div class="rg-edit-section">
                        <div class="rg-edit-section-title"><i class="bx bx-cog"></i> Slider settings</div>
                        <div class="row">
                            <div class="col-4 mb-2"><label class="form-label">Layout style</label>
                                <select class="form-select" data-field="style">
                                    <option value="card" ${(!p.style||p.style==='card')?'selected':''}>Card (figcaption below)</option>
                                    <option value="fullbleed" ${p.style==='fullbleed'?'selected':''}>Fullbleed (overlay)</option>
                                </select>
                            </div>
                            <div class="col-4 mb-2"><label class="form-label">Autoplay</label>
                                <select class="form-select" data-field="autoplay">
                                    <option value="1" ${p.autoplay?'selected':''}>On</option>
                                    <option value="0" ${!p.autoplay?'selected':''}>Off</option>
                                </select>
                            </div>
                            <div class="col-4 mb-2"><label class="form-label">Interval (ms)</label>
                                <input type="number" class="form-control" data-field="interval" value="${parseInt(p.interval||6500,10)}" min="2000" step="500">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-8 mb-2"><label class="form-label">Eyebrow title (small caps header above slider)</label>
                                <input type="text" class="form-control" data-field="eyebrow_title" value="${escapeHtml(p.eyebrow_title||'')}" placeholder="e.g. Inside Mall of Asia">
                            </div>
                            <div class="col-4 mb-2"><label class="form-label">Eyebrow subtitle</label>
                                <input type="text" class="form-control" data-field="eyebrow_subtitle" value="${escapeHtml(p.eyebrow_subtitle||'')}" placeholder="Photos: Wikimedia Commons (CC-BY-SA)">
                            </div>
                        </div>
                    </div>
                    <div class="rg-edit-section-title"><i class="bx bx-images"></i> Slides &middot; ${(p.images||[]).length} so far</div>
                    <div class="d-flex gap-2 align-items-center mb-2">
                        <button type="button" class="rg-pick-btn" id="rgHeroAddPick"><i class="bx bx-image-add"></i> Quick-add slide from library / upload</button>
                    </div>
                    <div id="rgHeroList" data-rg-rows="hero">${(p.images||[]).map(i => heroRowHtml(i)).join('')}</div>
                    <button type="button" class="rg-rows-add" id="rgHeroAdd"><i class="bx bx-plus"></i> Add empty slide</button>`;

            case 'quick_facts':
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-info-square me-1"></i>4-card quick-facts strip. Each card has its own icon + color &mdash; mirrors the food-page Per-person / Easiest window / Avoid / Traffic pattern.
                    </div>
                    <div class="rg-edit-section">
                        <div class="rg-edit-section-title"><i class="bx bx-cog"></i> Strip settings</div>
                        <label class="form-label">Heading (optional &mdash; sits above the strip)</label>
                        <input type="text" class="form-control" data-field="heading" value="${escapeHtml(p.heading||'')}" placeholder="Leave blank for no heading">
                    </div>
                    <div class="rg-edit-section-title"><i class="bx bx-grid-alt"></i> Cards &middot; ${(p.cards||[]).length} so far</div>
                    <div id="rgQfList" data-rg-rows="quickfacts">${(p.cards||[]).map((c, i) => quickFactRowHtml(c, i)).join('')}</div>
                    <button type="button" class="rg-rows-add" id="rgQfAdd"><i class="bx bx-plus"></i> Add card</button>`;

            case 'facts_list':
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-list-ol me-1"></i>Vertical-list variant of quick_facts. Same per-row fields (icon, color, big text, eyebrow, detail) but renders as a single rounded card with one row per fact. Used on destination pages where each fact is a full sentence.
                    </div>
                    <div class="rg-edit-section">
                        <div class="rg-edit-section-title"><i class="bx bx-cog"></i> List settings</div>
                        <label class="form-label">Heading (optional &mdash; sits above the list)</label>
                        <input type="text" class="form-control" data-field="heading" value="${escapeHtml(p.heading||'')}" placeholder="Leave blank for no heading">
                    </div>
                    <div class="rg-edit-section-title"><i class="bx bx-list-ul"></i> Rows &middot; ${(p.cards||[]).length} so far</div>
                    <div id="rgFlList" data-rg-rows="factslist">${(p.cards||[]).map((c, i) => quickFactRowHtml(c, i, 'factslist')).join('')}</div>
                    <button type="button" class="rg-rows-add" id="rgFlAdd"><i class="bx bx-plus"></i> Add row</button>`;

            case 'place_history':
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-history me-1"></i>Researched origin / history of the venue or district. Authored from web-sourced facts (NOT invented prose). Renders as a parchment-toned framed section with scroll icon, eyebrow, headline, and multi-paragraph body. The optional "Since" chip and citation footer signal that the content is verifiable, which helps E-E-A-T signals to search engines.
                    </div>
                    <div class="rg-edit-section">
                        <div class="rg-edit-section-title"><i class="bx bx-cog"></i> Header</div>
                        <div class="row-2col" style="grid-template-columns:1fr 180px;gap:12px">
                            <div>
                                <label class="form-label">Eyebrow (small uppercase label)</label>
                                <input type="text" class="form-control" data-field="eyebrow" value="${escapeHtml(p.eyebrow || 'Local history')}" placeholder="Local history">
                            </div>
                            <div>
                                <label class="form-label">Founded (year chip, optional)</label>
                                <input type="text" class="form-control" data-field="founded" value="${escapeHtml(p.founded || '')}" placeholder="1980, 2013, etc.">
                            </div>
                        </div>
                        <label class="form-label mt-2">Headline</label>
                        <input type="text" class="form-control" data-field="heading" value="${escapeHtml(p.heading || '')}" placeholder="How BGC came to be">
                    </div>
                    <div class="rg-edit-section">
                        <div class="rg-edit-section-title"><i class="bx bx-text"></i> Body</div>
                        <label class="form-label">Multi-paragraph body (blank line for new paragraph)</label>
                        <textarea class="form-control" data-field="body" rows="10" style="font-family:inherit;line-height:1.55;" placeholder="Write the researched history. Press Enter twice to start a new paragraph.">${escapeHtml(p.body || '')}</textarea>
                    </div>
                    <div class="rg-edit-section">
                        <div class="rg-edit-section-title"><i class="bx bx-link-external"></i> Source citation (optional)</div>
                        <div class="row-2col" style="grid-template-columns:1fr 1fr;gap:12px">
                            <div>
                                <label class="form-label">Citation label</label>
                                <input type="text" class="form-control" data-field="citation_label" value="${escapeHtml(p.citation_label || '')}" placeholder="NHCP, Megaworld Corporation, etc.">
                            </div>
                            <div>
                                <label class="form-label">Citation URL (opens in new tab)</label>
                                <input type="text" class="form-control" data-field="citation_url" value="${escapeHtml(p.citation_url || '')}" placeholder="https://...">
                            </div>
                        </div>
                    </div>`;

            case 'editor_rating': {
                const overallNum = parseFloat(p.overall || 4) || 4;
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-star me-1"></i>Resort Guru editor rating card. Set the overall score on top, then add one criterion card per dimension.
                    </div>
                    <div class="rg-edit-section">
                        <div class="rg-edit-section-title"><i class="bx bx-cog"></i> Rating header</div>
                        <div class="row">
                            <div class="col-7 mb-2"><label class="form-label">Card title</label>
                                <input type="text" class="form-control" data-field="title" value="${escapeHtml(p.title||'Resort Guru Editor Rating')}">
                            </div>
                            <div class="col-5 mb-2"><label class="form-label">Overall score (<span data-rg-overall-display>${overallNum.toFixed(1)}</span> / 5)</label>
                                <input type="range" class="form-range" data-field="overall" min="0" max="5" step="0.1" value="${overallNum}" oninput="document.querySelector('[data-rg-overall-display]').textContent = parseFloat(this.value).toFixed(1)">
                            </div>
                        </div>
                    </div>
                    <div class="rg-edit-section-title"><i class="bx bx-list-check"></i> Criteria &middot; ${(p.criteria||[]).length} so far</div>
                    <div id="rgRateList" data-rg-rows="rating">${(p.criteria||[]).map((c, i) => criterionRowHtml(c, i)).join('')}</div>
                    <button type="button" class="rg-rows-add" id="rgRateAdd"><i class="bx bx-plus"></i> Add criterion</button>
                    <div class="rg-edit-section mt-3">
                        <div class="rg-edit-section-title"><i class="bx bx-text"></i> Summary</div>
                        <label class="form-label">1-2 sentences (optional)</label>
                        <textarea class="form-control" rows="2" data-field="summary" placeholder="Quick read on the overall vibe">${escapeHtml(p.summary||'')}</textarea>
                    </div>`;
            }

            case 'listing_block':
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-trophy me-1"></i>This block renders the top-bid restaurants / resorts for this keyword <strong>live from the database</strong>. No per-page authoring &mdash; the section heading, CTA, and empty-state messaging all come from the public-site partial.
                    </div>
                    <div class="rg-edit-section">
                        <div class="rg-edit-section-title"><i class="bx bx-cog"></i> Settings</div>
                        <label class="form-label">Area override (optional)</label>
                        <input type="text" class="form-control" data-field="area" value="${escapeHtml(p.area||'')}" placeholder="e.g. Mall of Asia, BGC">
                        <div class="form-help mt-1">Leave blank to auto-extract from the keyword. Used in the empty-state CTA ("List your restaurant in <em>X</em>").</div>
                    </div>`;

            case 'attractions':
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-map-pin me-1"></i>"What's beyond the food / Around this place" card grid. Each attraction below is a stand-alone editor card &mdash; image on the left, name / blurb / link on the right.
                    </div>
                    <div class="rg-edit-section">
                        <div class="rg-edit-section-title"><i class="bx bx-cog"></i> Section header</div>
                        <label class="form-label">Heading</label>
                        <input type="text" class="form-control" data-field="heading" value="${escapeHtml(p.heading||"What's beyond the food")}">
                        <label class="form-label mt-2">Intro (optional)</label>
                        <textarea class="form-control" rows="2" data-field="intro" placeholder="Half a day of activity steps from your dinner reservation.">${escapeHtml(p.intro||'')}</textarea>
                    </div>
                    <div class="rg-edit-section-title"><i class="bx bx-grid-alt"></i> Attractions &middot; ${(p.items||[]).length} so far</div>
                    <div id="rgAttrList" data-rg-rows="attractions">${(p.items||[]).map((it, i) => attractionRowHtml(it, i)).join('')}</div>
                    <button type="button" class="rg-rows-add" id="rgAttrAdd"><i class="bx bx-plus"></i> Add attraction</button>`;

            case 'how_to_get_to':
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-bus me-1"></i>"How to get there" section &mdash; renders as a single card with intro lede, a divided list of transport methods (each with icon + bold subtitle + paragraph), and an optional closing footer note. Matches the destination page "How to get to ..." design.
                    </div>
                    <div class="rg-edit-section">
                        <div class="rg-edit-section-title"><i class="bx bx-cog"></i> Section header</div>
                        <label class="form-label">Heading</label>
                        <input type="text" class="form-control" data-field="heading" value="${escapeHtml(p.heading||'How to get there')}">
                        <label class="form-label mt-2">Intro paragraph (shown under the heading)</label>
                        <textarea class="form-control" rows="3" data-field="intro" placeholder="Getting to {Place} usually comes down to ...">${escapeHtml(p.intro||'')}</textarea>
                    </div>
                    <div class="rg-edit-section-title"><i class="bx bx-list-ul"></i> Transport methods &middot; ${(p.methods||[]).length} so far</div>
                    <div id="rgHtgtList" data-rg-rows="howto">${(p.methods||[]).map((m, i) => methodRowHtml(m, i)).join('')}</div>
                    <button type="button" class="rg-rows-add" id="rgHtgtAdd"><i class="bx bx-plus"></i> Add method</button>
                    <div class="rg-edit-section mt-3">
                        <div class="rg-edit-section-title"><i class="bx bx-message-rounded-dots"></i> Closing note (optional)</div>
                        <label class="form-label">Footer paragraph (slate-tinted band at the bottom of the card)</label>
                        <textarea class="form-control" rows="3" data-field="footer" placeholder="Whichever route you take, build in a buffer for ...">${escapeHtml(p.footer||'')}</textarea>
                    </div>`;

            case 'tag_pills': {
                const items = p.items || [];
                const rowsHtml = items.map((it, i) => {
                    const text = typeof it === 'string' ? it : (it.text || '');
                    const color = (typeof it === 'object' ? it.color : '') || 'amber';
                    const colorOpts = ['amber','rose','emerald','indigo','pink','cyan','violet','slate']
                        .map(c => `<option value="${c}" ${color===c?'selected':''}>${c}</option>`).join('');
                    return `<div class="rg-edit-card" data-rg-row="pill">
                        <div class="rg-edit-card-hdr">
                            <span class="rg-edit-card-num"></span>
                            <div class="rg-edit-card-title"><i class="bx bx-purchase-tag"></i> Tag pill</div>
                            <div class="rg-edit-card-actions">
                                <button type="button" class="btn btn-sm btn-outline-danger" data-rg-row-remove>&times;</button>
                            </div>
                        </div>
                        <div class="rg-edit-card-body">
                            <div class="rg-edit-card-fields">
                                <div class="row-2col" style="grid-template-columns: 1fr 140px;">
                                    <div><label>Text (use &middot; between sub-terms)</label><input type="text" class="form-control" data-rg-pill-text value="${escapeHtml(text)}" placeholder="Japanese &middot; ramen &middot; sushi"></div>
                                    <div><label>Color</label><select class="form-select" data-rg-pill-color>${colorOpts}</select></div>
                                </div>
                            </div>
                        </div>
                    </div>`;
                }).join('');
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-purchase-tag me-1"></i>Row of coloured pills. Each pill is a separate card below &mdash; pick a color per pill or let it cycle through the palette.
                    </div>
                    <div class="rg-edit-section">
                        <label class="form-label">Accessibility label (optional, screen-reader description of the row)</label>
                        <input type="text" class="form-control" data-field="label" value="${escapeHtml(p.label||'')}" placeholder="Strong cuisines at Mall of Asia">
                    </div>
                    <div class="rg-edit-section-title"><i class="bx bx-grid-alt"></i> Pills &middot; ${items.length}</div>
                    <div id="rgPillList" data-rg-rows="pill">${rowsHtml}</div>
                    <button type="button" class="rg-rows-add" id="rgPillAdd"><i class="bx bx-plus"></i> Add pill</button>`;
            }

            case 'external_guides': {
                const items = p.items || [];
                const rowsHtml = items.map((it, i) => {
                    const hasLogo = !!it.logo;
                    const hasShot = !!it.screenshot;
                    const colorOpts = ['emerald','blue','rose','amber','violet','slate']
                        .map(c => `<option value="${c}" ${it.color===c?'selected':''}>${c}</option>`).join('');
                    return `<div class="rg-edit-card" data-rg-row="guide">
                        <div class="rg-edit-card-hdr">
                            <span class="rg-edit-card-num"></span>
                            <div class="rg-edit-card-title"><i class="bx bx-link"></i> External guide</div>
                            <div class="rg-edit-card-actions">
                                <button type="button" class="btn btn-sm btn-outline-danger" data-rg-row-remove>&times;</button>
                            </div>
                        </div>
                        <div class="rg-edit-card-body">
                            <div class="rg-edit-card-fields">
                                <div class="row-2col">
                                    <div><label>Platform name</label><input type="text" class="form-control" data-rg-guide-name value="${escapeHtml(it.name || '')}" placeholder="TripAdvisor"></div>
                                    <div><label>Accent color</label><select class="form-select" data-rg-guide-color>${colorOpts}</select></div>
                                </div>
                                <div><label>Outbound URL</label><input type="text" class="form-control" data-rg-guide-url value="${escapeHtml(it.url || '')}" placeholder="https://..."></div>
                                <div><label>Short blurb (one line)</label><input type="text" class="form-control" data-rg-guide-blurb value="${escapeHtml(it.blurb || '')}" placeholder="Top-rated picks from travellers"></div>
                                <div class="row-2col">
                                    <div>
                                        <label>Logo (optional &mdash; PNG / SVG works best)</label>
                                        <div class="d-flex gap-2 align-items-center">
                                            <button type="button" class="rg-pick-btn ${hasLogo?'':'is-empty'}" data-rg-guide-logo-pick>
                                                <i class="bx bx-image-add"></i> ${hasLogo?'Change':'Pick logo'}
                                            </button>
                                            <input type="hidden" data-rg-guide-logo value="${escapeHtml(it.logo || '')}">
                                            ${hasLogo ? `<img src="${escapeHtml(mediaUrl(it.logo))}" style="height:28px;object-fit:contain">` : ''}
                                        </div>
                                    </div>
                                    <div>
                                        <label>Sneak peek screenshot (optional)</label>
                                        <div class="d-flex gap-2 align-items-center">
                                            <button type="button" class="rg-pick-btn ${hasShot?'':'is-empty'}" data-rg-guide-shot-pick>
                                                <i class="bx bx-image-add"></i> ${hasShot?'Change':'Pick screenshot'}
                                            </button>
                                            <input type="hidden" data-rg-guide-shot value="${escapeHtml(it.screenshot || '')}">
                                            ${hasShot ? `<img src="${escapeHtml(mediaUrl(it.screenshot))}" style="height:28px;object-fit:contain">` : ''}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>`;
                }).join('');
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-link me-1"></i>"Compare picks on third-party guides" card grid &mdash; one card per platform. Skip the logo / screenshot fields to fall back to a styled placeholder.
                    </div>
                    <div class="rg-edit-section">
                        <label class="form-label">Heading</label>
                        <input type="text" class="form-control" data-field="heading" value="${escapeHtml(p.heading || 'Compare picks on third-party guides')}">
                        <label class="form-label mt-2">Intro (optional)</label>
                        <textarea class="form-control" rows="2" data-field="intro">${escapeHtml(p.intro || '')}</textarea>
                        <label class="form-label mt-2">Footnote (small grey text under the grid)</label>
                        <input type="text" class="form-control" data-field="footnote" value="${escapeHtml(p.footnote || 'External links open in a new tab. We do not get paid for clicks.')}">
                    </div>
                    <div class="rg-edit-section-title"><i class="bx bx-grid-alt"></i> Guides &middot; ${items.length}</div>
                    <div id="rgGuideList" data-rg-rows="guide">${rowsHtml}</div>
                    <button type="button" class="rg-rows-add" id="rgGuideAdd"><i class="bx bx-plus"></i> Add guide</button>`;
            }

            case 'author': {
                const hasAvatar = !!p.avatar;
                const links = p.links || [];
                const linkTypes = ['facebook','instagram','twitter','tiktok','youtube','linkedin','email','website'];
                const linkRowsHtml = links.map(l => {
                    const typeOpts = linkTypes.map(t => `<option value="${t}" ${l.type===t?'selected':''}>${t}</option>`).join('');
                    return `<div class="rg-edit-card" data-rg-row="authorlink" style="background:#fafafa">
                        <div class="rg-edit-card-hdr" style="padding:6px 10px">
                            <div class="rg-edit-card-title" style="font-size:11px"><i class="bx bx-link-external"></i> Social link</div>
                            <div class="rg-edit-card-actions">
                                <button type="button" class="btn btn-sm btn-outline-danger" data-rg-row-remove>&times;</button>
                            </div>
                        </div>
                        <div class="rg-edit-card-body" style="padding:8px 10px">
                            <div class="row-2col" style="grid-template-columns:140px 1fr;gap:8px">
                                <div><select class="form-select form-select-sm" data-rg-author-link-type>${typeOpts}</select></div>
                                <div><input type="text" class="form-control form-control-sm" data-rg-author-link-url value="${escapeHtml(l.url || '')}" placeholder="https://..."></div>
                            </div>
                        </div>
                    </div>`;
                }).join('');
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-user-pin me-1"></i>Bottom-of-page author / byline card &mdash; avatar + name + role + bio + social links. Drop this at the bottom of an SEO page so the editorial voice gets attributed without competing with the top listing band.
                    </div>
                    <div class="rg-edit-section">
                        <div class="rg-edit-section-title"><i class="bx bx-cog"></i> Identity</div>
                        <div class="row-2col" style="grid-template-columns:1fr 200px;gap:12px">
                            <div>
                                <label class="form-label">Display name</label>
                                <input type="text" class="form-control" data-field="name" value="${escapeHtml(p.name || '')}" placeholder="Resort Guru Editorial">
                            </div>
                            <div>
                                <label class="form-label">Eyebrow label</label>
                                <input type="text" class="form-control" data-field="eyebrow" value="${escapeHtml(p.eyebrow || 'Written by')}">
                            </div>
                        </div>
                        <label class="form-label mt-2">Role / tagline (shown under the name)</label>
                        <input type="text" class="form-control" data-field="role" value="${escapeHtml(p.role || '')}" placeholder="Resort Guru PH Editorial Team">
                        <label class="form-label mt-2">Bio (1-2 sentence intro)</label>
                        <textarea class="form-control" rows="3" data-field="bio" placeholder="A small team of Filipino travel writers covering resorts, food finds, and DIY trips across the Philippines.">${escapeHtml(p.bio || '')}</textarea>
                    </div>
                    <div class="rg-edit-section">
                        <div class="rg-edit-section-title"><i class="bx bx-image"></i> Avatar</div>
                        <div class="d-flex gap-2 align-items-center">
                            <button type="button" class="rg-pick-btn ${hasAvatar?'':'is-empty'}" data-rg-author-avatar-pick>
                                <i class="bx bx-image-add"></i> ${hasAvatar?'Change avatar':'Pick avatar'}
                            </button>
                            <input type="hidden" data-field="avatar" value="${escapeHtml(p.avatar || '')}">
                            ${hasAvatar ? `<img data-rg-author-avatar-thumb src="${escapeHtml(mediaUrl(p.avatar))}" style="width:48px;height:48px;border-radius:50%;object-fit:cover;border:1px solid #e2e8f0">` : `<img data-rg-author-avatar-thumb src="" style="display:none">`}
                            <span class="text-muted" style="font-size:11px">Leave blank to fall back to initials on a slate gradient.</span>
                        </div>
                    </div>
                    <div class="rg-edit-section-title"><i class="bx bx-link-external"></i> Social links &middot; ${links.length}</div>
                    <div id="rgAuthorLinkList" data-rg-rows="authorlink">${linkRowsHtml}</div>
                    <button type="button" class="rg-rows-add" id="rgAuthorLinkAdd"><i class="bx bx-plus"></i> Add link</button>
                    <div class="rg-edit-section mt-3">
                        <div class="rg-edit-section-title"><i class="bx bx-right-arrow-alt"></i> Optional "More from this author" link</div>
                        <div class="row-2col" style="grid-template-columns:1fr 240px;gap:12px">
                            <div>
                                <label class="form-label">URL</label>
                                <input type="text" class="form-control" data-field="more_url" value="${escapeHtml(p.more_url || '')}" placeholder="/blog or https://...">
                            </div>
                            <div>
                                <label class="form-label">Link label (optional)</label>
                                <input type="text" class="form-control" data-field="more_label" value="${escapeHtml(p.more_label || '')}" placeholder="More from {name}">
                            </div>
                        </div>
                    </div>`;
            }

            case 'nearby_destinations': {
                const items = p.items || [];
                const itemRowsHtml = items.map(it => {
                    const hasImg = !!it.image;
                    return `<div class="rg-edit-card" data-rg-row="nbyd">
                        <div class="rg-edit-card-hdr">
                            <span class="rg-edit-card-num"></span>
                            <div class="rg-edit-card-title"><i class="bx bx-map-pin"></i> Destination</div>
                            <div class="rg-edit-card-actions">
                                <button type="button" class="btn btn-sm btn-outline-danger" data-rg-row-remove>&times;</button>
                            </div>
                        </div>
                        <div class="rg-edit-card-body with-thumb">
                            <div class="rg-edit-thumb-wrap">
                                <img data-rg-nbyd-thumb class="rg-edit-thumb ${hasImg?'':'is-empty'}" src="${hasImg ? escapeHtml(mediaUrl(it.image)) : ''}" alt="">
                                <button type="button" class="rg-edit-thumb-pick ${hasImg?'':'is-empty'}" data-rg-nbyd-pick>
                                    <i class="bx bx-image-add"></i> ${hasImg ? 'Change' : 'Choose image'}
                                </button>
                                <input type="hidden" data-rg-nbyd-image value="${escapeHtml(it.image || '')}">
                            </div>
                            <div class="rg-edit-card-fields">
                                <div class="row-2col">
                                    <div><label>Title</label><input type="text" class="form-control" data-rg-nbyd-title value="${escapeHtml(it.title || '')}" placeholder="Resort in Tagaytay"></div>
                                    <div><label>Eyebrow (cluster label)</label><input type="text" class="form-control" data-rg-nbyd-eyebrow value="${escapeHtml(it.eyebrow || '')}" placeholder="Cavite"></div>
                                </div>
                                <div><label>URL</label><input type="text" class="form-control" data-rg-nbyd-url value="${escapeHtml(it.url || '')}" placeholder="/resort-in-tagaytay"></div>
                                <div class="row-2col" style="grid-template-columns:1fr 200px">
                                    <div><label>Blurb</label><input type="text" class="form-control" data-rg-nbyd-blurb value="${escapeHtml(it.blurb || '')}"></div>
                                    <div><label>Distance label (optional)</label><input type="text" class="form-control" data-rg-nbyd-distance value="${escapeHtml(it.distance_label || '')}" placeholder="2 hrs from Manila"></div>
                                </div>
                            </div>
                        </div>
                    </div>`;
                }).join('');
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-map-alt me-1"></i>Nearby destination keyword pages, shown as a 3-col card grid. Leave the manual items list empty and fill in <code>auto_from_cluster</code> to pull resort-category pages from the same cluster automatically.
                    </div>
                    <div class="rg-edit-section">
                        <div class="rg-edit-section-title"><i class="bx bx-cog"></i> Section header</div>
                        <label class="form-label">Heading</label>
                        <input type="text" class="form-control" data-field="heading" value="${escapeHtml(p.heading || 'Stay nearby after the meal')}">
                        <label class="form-label mt-2">Intro (optional)</label>
                        <textarea class="form-control" rows="2" data-field="intro" placeholder="Done eating? Here are the resorts within driving range.">${escapeHtml(p.intro || '')}</textarea>
                    </div>
                    <div class="rg-edit-section">
                        <div class="rg-edit-section-title"><i class="bx bx-flash"></i> Auto-resolve (used when items list is empty)</div>
                        <div class="row-2col" style="grid-template-columns:1fr 1fr 100px;gap:10px">
                            <div>
                                <label class="form-label">Cluster tag</label>
                                <input type="text" class="form-control" data-field="auto_from_cluster" value="${escapeHtml(p.auto_from_cluster || '')}" placeholder="metro-manila">
                            </div>
                            <div>
                                <label class="form-label">Exclude slug</label>
                                <input type="text" class="form-control" data-field="exclude_slug" value="${escapeHtml(p.exclude_slug || '')}" placeholder="restaurant-in-mall-of-asia">
                            </div>
                            <div>
                                <label class="form-label">Max</label>
                                <input type="number" class="form-control" data-field="max" value="${p.max || 6}" min="1" max="12">
                            </div>
                        </div>
                    </div>
                    <div class="rg-edit-section-title"><i class="bx bx-list-ul"></i> Manual items &middot; ${items.length}</div>
                    <div id="rgNbydList" data-rg-rows="nbyd">${itemRowsHtml}</div>
                    <button type="button" class="rg-rows-add" id="rgNbydAdd"><i class="bx bx-plus"></i> Add destination</button>`;
            }

            case 'related_blogs': {
                const items = p.items || [];
                const autoKeywords = (p.auto_from_keywords || []).join(', ');
                const itemRowsHtml = items.map(it => {
                    const hasImg = !!it.cover;
                    return `<div class="rg-edit-card" data-rg-row="rblog">
                        <div class="rg-edit-card-hdr">
                            <span class="rg-edit-card-num"></span>
                            <div class="rg-edit-card-title"><i class="bx bx-news"></i> Blog post</div>
                            <div class="rg-edit-card-actions">
                                <button type="button" class="btn btn-sm btn-outline-danger" data-rg-row-remove>&times;</button>
                            </div>
                        </div>
                        <div class="rg-edit-card-body with-thumb">
                            <div class="rg-edit-thumb-wrap">
                                <img data-rg-rblog-thumb class="rg-edit-thumb ${hasImg?'':'is-empty'}" src="${hasImg ? escapeHtml(mediaUrl(it.cover)) : ''}" alt="">
                                <button type="button" class="rg-edit-thumb-pick ${hasImg?'':'is-empty'}" data-rg-rblog-pick>
                                    <i class="bx bx-image-add"></i> ${hasImg ? 'Change' : 'Choose cover'}
                                </button>
                                <input type="hidden" data-rg-rblog-cover value="${escapeHtml(it.cover || '')}">
                            </div>
                            <div class="rg-edit-card-fields">
                                <div class="row-2col">
                                    <div><label>Title</label><input type="text" class="form-control" data-rg-rblog-title value="${escapeHtml(it.title || '')}"></div>
                                    <div><label>Eyebrow (category / tag)</label><input type="text" class="form-control" data-rg-rblog-eyebrow value="${escapeHtml(it.eyebrow || '')}" placeholder="Itinerary"></div>
                                </div>
                                <div><label>URL</label><input type="text" class="form-control" data-rg-rblog-url value="${escapeHtml(it.url || '')}" placeholder="/blog/..."></div>
                                <div><label>Excerpt</label><textarea class="form-control" rows="2" data-rg-rblog-excerpt>${escapeHtml(it.excerpt || '')}</textarea></div>
                                <div><label>Meta line (e.g. "Published Jun 1, 2026")</label><input type="text" class="form-control" data-rg-rblog-meta value="${escapeHtml(it.meta || '')}"></div>
                            </div>
                        </div>
                    </div>`;
                }).join('');
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-news me-1"></i>Related blog post strip. Leave the manual items list empty and fill in <code>auto_from_keywords</code> (comma-separated) to pull posts whose tags match.
                    </div>
                    <div class="rg-edit-section">
                        <div class="rg-edit-section-title"><i class="bx bx-cog"></i> Section header</div>
                        <label class="form-label">Heading</label>
                        <input type="text" class="form-control" data-field="heading" value="${escapeHtml(p.heading || 'More reads on the area')}">
                        <label class="form-label mt-2">Intro (optional)</label>
                        <textarea class="form-control" rows="2" data-field="intro" placeholder="Long-form takes from our editorial team on this part of the country.">${escapeHtml(p.intro || '')}</textarea>
                    </div>
                    <div class="rg-edit-section">
                        <div class="rg-edit-section-title"><i class="bx bx-flash"></i> Auto-resolve (used when items list is empty)</div>
                        <div class="row-2col" style="grid-template-columns:1fr 100px;gap:10px">
                            <div>
                                <label class="form-label">Tag keywords (comma-separated)</label>
                                <input type="text" class="form-control" data-field="auto_from_keywords_csv" value="${escapeHtml(autoKeywords)}" placeholder="Manila, Metro Manila, BGC">
                            </div>
                            <div>
                                <label class="form-label">Max</label>
                                <input type="number" class="form-control" data-field="max" value="${p.max || 3}" min="1" max="6">
                            </div>
                        </div>
                    </div>
                    <div class="rg-edit-section-title"><i class="bx bx-list-ul"></i> Manual items &middot; ${items.length}</div>
                    <div id="rgRblogList" data-rg-rows="rblog">${itemRowsHtml}</div>
                    <button type="button" class="rg-rows-add" id="rgRblogAdd"><i class="bx bx-plus"></i> Add blog post</button>`;
            }

            case 'section_header':
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-heading me-1"></i>Article-body section opener &mdash; large H2 + small subtitle lede. Used for "What's in [Area]?" style intros.
                    </div>
                    <div class="rg-edit-section">
                        <div class="rg-edit-section-title"><i class="bx bx-cog"></i> Heading</div>
                        <label class="form-label">H2 heading</label>
                        <input type="text" class="form-control" data-field="heading" value="${escapeHtml(p.heading || '')}" placeholder="What's in Mall of Asia?">
                        <label class="form-label mt-2">Subtitle (small grey lede below the heading)</label>
                        <input type="text" class="form-control" data-field="subtitle" value="${escapeHtml(p.subtitle || '')}" placeholder="The local read on the area, who it's for, and how to plan a trip that actually works.">
                        <label class="form-label mt-2">Anchor slug (optional &mdash; becomes the heading's id="")</label>
                        <input type="text" class="form-control form-control-sm" data-field="anchor" value="${escapeHtml(p.anchor || '')}" placeholder="whats-in-the-area">
                    </div>`;

            case 'short_version':
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-zap me-1"></i>Dark gradient summary card with eyebrow + one-line statement. Mirrors the "The short version" callout on the food pages.
                    </div>
                    <div class="rg-edit-section">
                        <div class="rg-edit-section-title"><i class="bx bx-cog"></i> Card settings</div>
                        <div class="row">
                            <div class="col-9 mb-2"><label class="form-label">Eyebrow (small caps tagline above the body)</label>
                                <input type="text" class="form-control" data-field="eyebrow" value="${escapeHtml(p.eyebrow || 'The short version')}">
                            </div>
                            <div class="col-3 mb-2"><label class="form-label">Accent</label>
                                <select class="form-select" data-field="accent_color">
                                    <option value="amber" ${(!p.accent_color||p.accent_color==='amber')?'selected':''}>Amber</option>
                                    <option value="emerald" ${p.accent_color==='emerald'?'selected':''}>Emerald</option>
                                    <option value="rose" ${p.accent_color==='rose'?'selected':''}>Rose</option>
                                    <option value="blue" ${p.accent_color==='blue'?'selected':''}>Blue</option>
                                </select>
                            </div>
                        </div>
                        <label class="form-label">Body (1-3 sentences)</label>
                        <textarea class="form-control" rows="3" data-field="body" placeholder="If you have one meal at MOA: pick the longest-running family-run spot on the main strip.">${escapeHtml(p.body || '')}</textarea>
                    </div>`;

            case 'pros_cons':
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-list-check me-1"></i>Two-column comparison list. Left = green "Best for" / Right = rose "Skip if". Each side is a bullet list.
                    </div>
                    <div class="rg-edit-section">
                        <div class="rg-edit-section-title"><i class="bx bx-check"></i> Pros side (green)</div>
                        <label class="form-label">Heading</label>
                        <input type="text" class="form-control mb-2" data-field="pros_label" value="${escapeHtml(p.pros_label || 'Best for')}">
                        <label class="form-label">Items (one per line)</label>
                        <textarea class="form-control" rows="4" data-field="pros_text" placeholder="One item per line">${escapeHtml((p.pros||[]).join('\n'))}</textarea>
                    </div>
                    <div class="rg-edit-section">
                        <div class="rg-edit-section-title"><i class="bx bx-x"></i> Cons side (rose)</div>
                        <label class="form-label">Heading</label>
                        <input type="text" class="form-control mb-2" data-field="cons_label" value="${escapeHtml(p.cons_label || 'Skip if')}">
                        <label class="form-label">Items (one per line)</label>
                        <textarea class="form-control" rows="4" data-field="cons_text" placeholder="One item per line">${escapeHtml((p.cons||[]).join('\n'))}</textarea>
                    </div>`;

            case 'summary_accordion': {
                const items = p.items || [];
                const itemRows = items.map((it, i) => `
                    <div class="rg-edit-card" data-rg-row="accordion">
                        <div class="rg-edit-card-hdr">
                            <span class="rg-edit-card-num"></span>
                            <div class="rg-edit-card-title"><i class="bx bx-chevron-down-square"></i> Accordion item</div>
                            <div class="rg-edit-card-actions">
                                <label class="d-flex align-items-center gap-1 mb-0" style="font-size:11px"><input type="checkbox" data-rg-acc-open ${it.open ? 'checked' : ''}> Default open</label>
                                <button type="button" class="btn btn-sm btn-outline-danger" data-rg-row-remove>&times;</button>
                            </div>
                        </div>
                        <div class="rg-edit-card-body">
                            <div class="rg-edit-card-fields">
                                <div class="row-2col">
                                    <div><label>Eyebrow (small caps tag)</label><input type="text" class="form-control" data-rg-acc-eyebrow value="${escapeHtml(it.eyebrow || '')}" placeholder="At a glance"></div>
                                    <div><label>Title</label><input type="text" class="form-control" data-rg-acc-title value="${escapeHtml(it.title || '')}" placeholder="Worth a weekend"></div>
                                </div>
                                <div><label>Body (HTML allowed)</label><textarea class="form-control" rows="3" data-rg-acc-body>${escapeHtml(it.body || '')}</textarea></div>
                            </div>
                        </div>
                    </div>
                `).join('');
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-chevron-down-square me-1"></i>Expandable summary panels (native &lt;details&gt; — works without JS). Typical pair: "At a glance" + "The short version".
                    </div>
                    <div class="rg-edit-section-title"><i class="bx bx-list-ul"></i> Accordion items &middot; ${items.length}</div>
                    <div id="rgAccList" data-rg-rows="accordion">${itemRows}</div>
                    <button type="button" class="rg-rows-add" id="rgAccAdd"><i class="bx bx-plus"></i> Add panel</button>`;
            }

            case 'image_text_pair':
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-image-alt me-1"></i>Left/right pair with one image on one side and rich content (eyebrow + title + body + CTA) on the other. Use this for tourist-spot rows that alternate left ↔ right.
                    </div>
                    <div class="rg-edit-card">
                        <div class="rg-edit-card-body with-thumb">
                            <div class="rg-edit-thumb-wrap">
                                <img data-rg-itp-thumb class="rg-edit-thumb ${p.image ? '' : 'is-empty'}" src="${p.image ? escapeHtml(mediaUrl(p.image)) : ''}" alt="">
                                <button type="button" class="rg-edit-thumb-pick ${p.image ? '' : 'is-empty'}" data-rg-itp-pick>
                                    <i class="bx bx-image-add"></i> ${p.image ? 'Change image' : 'Choose image'}
                                </button>
                                <input type="hidden" data-field="image" value="${escapeHtml(p.image || '')}">
                                <select class="form-select form-select-sm mt-2" data-field="image_position">
                                    <option value="left" ${(!p.image_position||p.image_position==='left')?'selected':''}>Image on left</option>
                                    <option value="right" ${p.image_position==='right'?'selected':''}>Image on right</option>
                                </select>
                            </div>
                            <div class="rg-edit-card-fields">
                                <div><label>Eyebrow (small caps tag)</label><input type="text" class="form-control" data-field="eyebrow" value="${escapeHtml(p.eyebrow || '')}" placeholder="Beach + Tagaytay vibe"></div>
                                <div><label>Title</label><input type="text" class="form-control" data-field="title" value="${escapeHtml(p.title || '')}" placeholder="Mind Museum"></div>
                                <div><label>Body</label><textarea class="form-control" rows="3" data-field="body">${escapeHtml(p.body || '')}</textarea></div>
                                <div class="row-2col">
                                    <div><label>Outbound URL (optional)</label><input type="text" class="form-control" data-field="url" value="${escapeHtml(p.url || '')}" placeholder="https://..."></div>
                                    <div><label>CTA label</label><input type="text" class="form-control" data-field="url_label" value="${escapeHtml(p.url_label || 'Learn more')}"></div>
                                </div>
                            </div>
                        </div>
                    </div>`;

            case 'traveler_reviews': {
                const items = p.items || [];
                const itemRows = items.map((it, i) => `
                    <div class="rg-edit-card" data-rg-row="reviews">
                        <div class="rg-edit-card-hdr">
                            <span class="rg-edit-card-num"></span>
                            <div class="rg-edit-card-title"><i class="bx bx-user-circle"></i> Review</div>
                            <div class="rg-edit-card-actions">
                                <button type="button" class="btn btn-sm btn-outline-danger" data-rg-row-remove>&times;</button>
                            </div>
                        </div>
                        <div class="rg-edit-card-body with-thumb">
                            <div class="rg-edit-thumb-wrap">
                                <img data-rg-tr-thumb class="rg-edit-thumb ${it.avatar ? '' : 'is-empty'}" src="${it.avatar ? escapeHtml(mediaUrl(it.avatar)) : ''}" alt="">
                                <button type="button" class="rg-edit-thumb-pick ${it.avatar ? '' : 'is-empty'}" data-rg-tr-pick>
                                    <i class="bx bx-image-add"></i> ${it.avatar ? 'Change avatar' : 'Choose avatar'}
                                </button>
                                <input type="hidden" data-rg-tr-avatar value="${escapeHtml(it.avatar || '')}">
                            </div>
                            <div class="rg-edit-card-fields">
                                <div class="row-2col">
                                    <div><label>Name</label><input type="text" class="form-control" data-rg-tr-name value="${escapeHtml(it.name || '')}" placeholder="Maria S."></div>
                                    <div><label>Date</label><input type="text" class="form-control" data-rg-tr-date value="${escapeHtml(it.date || '')}" placeholder="Aug 2026"></div>
                                </div>
                                <div><label>Rating (0-5)</label><input type="number" min="0" max="5" step="1" class="form-control" data-rg-tr-rating value="${parseInt(it.rating||5,10)}"></div>
                                <div><label>Quote</label><textarea class="form-control" rows="2" data-rg-tr-quote>${escapeHtml(it.quote || '')}</textarea></div>
                            </div>
                        </div>
                    </div>
                `).join('');
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-message-rounded-detail me-1"></i>Testimonial grid. Each review = avatar + name + rating stars + quote.
                    </div>
                    <div class="rg-edit-section">
                        <div class="rg-edit-section-title"><i class="bx bx-cog"></i> Section header</div>
                        <label class="form-label">Heading</label>
                        <input type="text" class="form-control" data-field="heading" value="${escapeHtml(p.heading || 'What travelers are saying')}">
                        <label class="form-label mt-2">Intro (optional)</label>
                        <textarea class="form-control" rows="2" data-field="intro">${escapeHtml(p.intro || '')}</textarea>
                    </div>
                    <div class="rg-edit-section-title"><i class="bx bx-message-rounded-detail"></i> Reviews &middot; ${items.length}</div>
                    <div id="rgTrList" data-rg-rows="reviews">${itemRows}</div>
                    <button type="button" class="rg-rows-add" id="rgTrAdd"><i class="bx bx-plus"></i> Add review</button>`;
            }

            case 'map_embed':
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-map me-1"></i>Embed a Google Maps or OpenStreetMap iframe. Paste the URL from Maps → Share → Embed.
                    </div>
                    <div class="rg-edit-section">
                        <div class="rg-edit-section-title"><i class="bx bx-cog"></i> Map settings</div>
                        <label class="form-label">Heading (optional)</label>
                        <input type="text" class="form-control" data-field="heading" value="${escapeHtml(p.heading || '')}" placeholder="Where MOA is on the map">
                        <label class="form-label mt-2">Embed URL</label>
                        <input type="text" class="form-control" data-field="embed_url" value="${escapeHtml(p.embed_url || '')}" placeholder="https://www.google.com/maps/embed?pb=...">
                        <div class="form-help mt-1">Must start with <code>https://www.google.com/maps/embed</code> or <code>https://www.openstreetmap.org</code>.</div>
                        <label class="form-label mt-2">Height (px)</label>
                        <input type="number" min="200" max="800" step="10" class="form-control" data-field="height" value="${parseInt(p.height || 450,10)}">
                    </div>`;

            case 'local_tip':
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-bulb me-1"></i>Coloured callout with eyebrow + body. Perfect for "Local tip from a [Place] regular" style notes.
                    </div>
                    <div class="rg-edit-section">
                        <div class="row">
                            <div class="col-9 mb-2"><label class="form-label">Eyebrow</label>
                                <input type="text" class="form-control" data-field="eyebrow" value="${escapeHtml(p.eyebrow || 'Local tip from a regular')}">
                            </div>
                            <div class="col-3 mb-2"><label class="form-label">Style</label>
                                <select class="form-select" data-field="color">
                                    <option value="amber" ${(!p.color||p.color==='amber')?'selected':''}>Amber (light tip)</option>
                                    <option value="emerald" ${p.color==='emerald'?'selected':''}>Emerald (eco)</option>
                                    <option value="blue" ${p.color==='blue'?'selected':''}>Blue (info)</option>
                                    <option value="rose" ${p.color==='rose'?'selected':''}>Rose (warning)</option>
                                    <option value="dark" ${p.color==='dark'?'selected':''}>Dark gradient (pull-quote)</option>
                                </select>
                            </div>
                        </div>
                        <label class="form-label">Body</label>
                        <textarea class="form-control" rows="3" data-field="body" placeholder="Cross the SM by the Bay walkway before 5 PM to skip the Globe Wheel line.">${escapeHtml(p.body || '')}</textarea>
                    </div>`;

            case 'related_guides': {
                const items = p.items || [];
                const itemRows = items.map((it, i) => `
                    <div class="rg-edit-card" data-rg-row="related">
                        <div class="rg-edit-card-hdr">
                            <span class="rg-edit-card-num"></span>
                            <div class="rg-edit-card-title"><i class="bx bx-link-external"></i> Guide link</div>
                            <div class="rg-edit-card-actions">
                                <button type="button" class="btn btn-sm btn-outline-danger" data-rg-row-remove>&times;</button>
                            </div>
                        </div>
                        <div class="rg-edit-card-body">
                            <div class="rg-edit-card-fields">
                                <div class="row-2col">
                                    <div><label>Eyebrow</label><input type="text" class="form-control" data-rg-rel-eyebrow value="${escapeHtml(it.eyebrow || '')}" placeholder="Food guide"></div>
                                    <div><label>Title</label><input type="text" class="form-control" data-rg-rel-title value="${escapeHtml(it.title || '')}" placeholder="Restaurant in Makati"></div>
                                </div>
                                <div><label>Blurb</label><input type="text" class="form-control" data-rg-rel-blurb value="${escapeHtml(it.blurb || '')}" placeholder="Cluster guide for the Ayala Triangle scene."></div>
                                <div><label>URL (internal /slug or external https://)</label><input type="text" class="form-control" data-rg-rel-url value="${escapeHtml(it.url || '')}" placeholder="/restaurant-in-makati"></div>
                            </div>
                        </div>
                    </div>
                `).join('');
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-link-external me-1"></i>Cross-link cards. Internal URLs (/slug) stay on-site; external https URLs open in a new tab with rel="nofollow".
                    </div>
                    <div class="rg-edit-section">
                        <label class="form-label">Heading</label>
                        <input type="text" class="form-control" data-field="heading" value="${escapeHtml(p.heading || 'Related guides')}">
                    </div>
                    <div class="rg-edit-section-title"><i class="bx bx-link"></i> Guides &middot; ${items.length}</div>
                    <div id="rgRelList" data-rg-rows="related">${itemRows}</div>
                    <button type="button" class="rg-rows-add" id="rgRelAdd"><i class="bx bx-plus"></i> Add guide</button>`;
            }

            case 'data_table': {
                const headers = p.headers || [];
                const rows = p.rows || [];
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-table me-1"></i>Simple data table. First column is bold; even rows get a subtle alt background.
                    </div>
                    <div class="rg-edit-section">
                        <label class="form-label">Caption (small caps tag above the table, optional)</label>
                        <input type="text" class="form-control" data-field="caption" value="${escapeHtml(p.caption || '')}">
                    </div>
                    <div class="rg-edit-section">
                        <div class="rg-edit-section-title"><i class="bx bx-columns"></i> Headers (comma-separated)</div>
                        <input type="text" class="form-control" data-field="headers_text" value="${escapeHtml(headers.join(', '))}" placeholder="Tier, Detail, What it gets you">
                        <div class="form-help mt-1">Change the number of headers to add/remove columns; rows align to header count.</div>
                    </div>
                    <div class="rg-edit-section">
                        <div class="rg-edit-section-title"><i class="bx bx-list-ul"></i> Rows (one row per line · cells separated by <code>|</code>)</div>
                        <textarea class="form-control" rows="6" data-field="rows_text" placeholder="Walk-up | Tray plates | Fastest queue&#10;Mid-range | Table service | Full menu">${escapeHtml(rows.map(r => Array.isArray(r) ? r.join(' | ') : '').join('\n'))}</textarea>
                    </div>`;
            }

            case 'text_section': {
                // Promote legacy paragraphs[] to body text the first time
                // an admin opens an old block — joined with blank lines so
                // the visual structure is preserved while moving to the new
                // single-textarea editor.
                const body = (p.body || '').trim() !== ''
                    ? p.body
                    : ((p.paragraphs || []).map(s => String(s || '').trim()).filter(Boolean).join('\n\n'));
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-paragraph me-1"></i>Title + body element. Type prose in the text area below; press <strong>Enter twice</strong> to start a new paragraph. Basic inline HTML (&lt;strong&gt;, &lt;em&gt;, &lt;a href&gt;) still works if you need it.
                    </div>
                    <div class="rg-edit-section">
                        <div class="rg-edit-section-title"><i class="bx bx-cog"></i> Title</div>
                        <div class="row">
                            <div class="col-3 mb-2"><label class="form-label">Heading level</label>
                                <select class="form-select" data-field="heading_level">
                                    <option value="h2" ${(!p.heading_level||p.heading_level==='h2')?'selected':''}>H2</option>
                                    <option value="h3" ${p.heading_level==='h3'?'selected':''}>H3</option>
                                    <option value="h4" ${p.heading_level==='h4'?'selected':''}>H4</option>
                                </select>
                            </div>
                            <div class="col-9 mb-2"><label class="form-label">Title text (leave blank for body-only section)</label>
                                <input type="text" class="form-control" data-field="heading" value="${escapeHtml(p.heading||'')}" placeholder="e.g. The lay of the land at BGC">
                            </div>
                        </div>
                        <label class="form-label">Anchor slug (optional &mdash; becomes the heading's id="" for internal scroll links)</label>
                        <input type="text" class="form-control form-control-sm" data-field="anchor" value="${escapeHtml(p.anchor||'')}" placeholder="lay-of-the-land">
                    </div>
                    <div class="rg-edit-section">
                        <div class="rg-edit-section-title"><i class="bx bx-text"></i> Body</div>
                        <label class="form-label">Text</label>
                        <textarea class="form-control" data-field="body" rows="10" style="font-family:inherit;line-height:1.55;" placeholder="Write the section copy here. Use a blank line between paragraphs.">${escapeHtml(body)}</textarea>
                    </div>`;
            }

            case 'subtitle_intro':
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-italic me-1"></i>Italic 1-2 sentence positioner shown directly under the H1. One short text field.
                    </div>
                    <div class="rg-edit-section">
                        <label class="form-label">Subtitle text</label>
                        <textarea class="form-control" data-field="text" rows="3" placeholder="A short italic line introducing the page.">${escapeHtml(p.text || '')}</textarea>
                        <div class="form-help mt-1">Leave empty to hide. No HTML — plain text only.</div>
                    </div>`;

            case 'tldr_card':
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-bulb me-1"></i>Collapsible "short version" accordion shown near the top of the page. Body can be one paragraph or a bulleted list (lines starting with <code>-</code> or <code>*</code>).
                    </div>
                    <div class="rg-edit-section">
                        <div class="row">
                            <div class="col-6 mb-2">
                                <label class="form-label">Eyebrow</label>
                                <input type="text" class="form-control" data-field="eyebrow" value="${escapeHtml(p.eyebrow || 'The short version')}">
                            </div>
                            <div class="col-6 mb-2">
                                <label class="form-label">Caption (subhead)</label>
                                <input type="text" class="form-control" data-field="caption" value="${escapeHtml(p.caption || 'Tap to read the key takeaways before you scroll')}">
                            </div>
                        </div>
                        <label class="form-label mt-2">Body</label>
                        <textarea class="form-control" data-field="body" rows="6" style="font-family:inherit;line-height:1.55;" placeholder="- First takeaway&#10;- Second takeaway&#10;- Third takeaway">${escapeHtml(p.body || '')}</textarea>
                        <div class="form-help mt-1">Use a single paragraph for a plain summary, or list lines (one bullet per line) for a checklist.</div>
                    </div>`;

            case 'wwww_card':
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-collection me-1"></i>Collapsible "at a glance" card with up to 4 rows: Why, When, Where, Whom. Any empty row is automatically hidden.
                    </div>
                    <div class="rg-edit-section">
                        <div class="row">
                            <div class="col-6 mb-2">
                                <label class="form-label">Eyebrow</label>
                                <input type="text" class="form-control" data-field="eyebrow" value="${escapeHtml(p.eyebrow || 'At a glance')}">
                            </div>
                            <div class="col-6 mb-2">
                                <label class="form-label">Caption (subhead)</label>
                                <input type="text" class="form-control" data-field="caption" value="${escapeHtml(p.caption || 'Why, when, where, and who this guide is for')}">
                            </div>
                        </div>
                        <label class="form-label mt-2">Why go (orange)</label>
                        <textarea class="form-control" data-field="why" rows="2" placeholder="Why is this place worth a trip?">${escapeHtml(p.why || '')}</textarea>
                        <label class="form-label mt-2">When to go (cyan)</label>
                        <textarea class="form-control" data-field="when" rows="2" placeholder="Best season / time of day to visit.">${escapeHtml(p.when || '')}</textarea>
                        <label class="form-label mt-2">Where to go (emerald)</label>
                        <textarea class="form-control" data-field="where" rows="2" placeholder="Specific spots or neighborhoods to head for.">${escapeHtml(p.where || '')}</textarea>
                        <label class="form-label mt-2">Whom to go with (fuchsia)</label>
                        <textarea class="form-control" data-field="whom" rows="2" placeholder="Best travel companions: family, solo, barkada, dates.">${escapeHtml(p.whom || '')}</textarea>
                        <div class="form-help mt-1">Leave any row empty to hide it on the public page.</div>
                    </div>`;

            case 'social_share':
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-share-alt me-1"></i>Renders the Facebook / X / LinkedIn / WhatsApp / Telegram / Copy-link share row from <code>partials/social-share.blade.php</code>. URL + title come from the page being edited — nothing to set here besides alignment.
                    </div>
                    <div class="rg-edit-section">
                        <label class="form-label">Alignment</label>
                        <select class="form-select" data-field="align">
                            <option value="start" ${p.align === 'start' ? 'selected' : ''}>Left</option>
                            <option value="between" ${(!p.align || p.align === 'between') ? 'selected' : ''}>Between (default)</option>
                            <option value="end" ${p.align === 'end' ? 'selected' : ''}>Right</option>
                        </select>
                    </div>`;

            case 'we_recommend_band':
                return `
                    <div class="alert alert-warning" style="font-size:12px;">
                        <i class="bx bx-trophy me-1"></i>Live listings band — pulls active rows from <code>rg_listings</code> for this page's keyword and renders the "We Recommend" header + cards via <code>partials/listings-rows.blade.php</code>.
                        <br><br>
                        On <strong>food</strong> keyword pages the band shows restaurants/eateries and the heading reads "Restaurants, Eateries & Food Destinations We Recommend". On <strong>resort/hotel</strong> keyword pages it shows resorts and the heading reads "We Recommend".
                        <br><br>
                        Nothing to set here — the partial handles its own empty state ("List your property" CTA) when there are no active listings.
                    </div>
                    <div class="rg-edit-section">
                        <small class="text-muted">No editable fields. Drag this block to move the listings band on the page, or delete it to hide the band entirely.</small>
                    </div>`;

            case 'restaurant_recs_band':
                return `
                    <div class="alert alert-warning" style="font-size:12px;">
                        <i class="bx bx-restaurant me-1"></i>Live "Restaurant Recommendations" band — pulls active rows from <code>rg_restaurant_listings</code> for this page's keyword. <strong>Only renders on non-food keyword pages</strong> (resort/hotel/airbnb). Empty on food pages.
                    </div>
                    <div class="rg-edit-section">
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <label class="form-label">Eyebrow</label>
                                <input type="text" class="form-control" data-field="eyebrow" value="${escapeHtml(p.eyebrow || 'Eat nearby')}">
                            </div>
                            <div class="col-md-8 mb-2">
                                <label class="form-label">Heading (H2)</label>
                                <input type="text" class="form-control" data-field="heading" value="${escapeHtml(p.heading || 'Restaurant Recommendations')}">
                            </div>
                        </div>
                        <label class="form-label mt-2">Caption (subhead)</label>
                        <textarea class="form-control" data-field="caption" rows="2">${escapeHtml(p.caption || 'Paid placements where your guests will likely want to eat.')}</textarea>
                    </div>`;

            case 'adventures_band':
                return `
                    <div class="alert alert-warning" style="font-size:12px;">
                        <i class="bx bx-compass me-1"></i>Live "Memorable Adventures" band — pulls active rows from <code>rg_adventure_listings</code> for this page's keyword. <strong>Only renders on non-food keyword pages</strong>. Empty on food pages.
                    </div>
                    <div class="rg-edit-section">
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <label class="form-label">Eyebrow</label>
                                <input type="text" class="form-control" data-field="eyebrow" value="${escapeHtml(p.eyebrow || 'Things to do')}">
                            </div>
                            <div class="col-md-8 mb-2">
                                <label class="form-label">Heading (H2)</label>
                                <input type="text" class="form-control" data-field="heading" value="${escapeHtml(p.heading || 'Memorable Adventures & Activities')}">
                            </div>
                        </div>
                        <label class="form-label mt-2">Caption (subhead)</label>
                        <textarea class="form-control" data-field="caption" rows="2">${escapeHtml(p.caption || 'Surf schools, ATV trails, island hops, and paintball arenas open in the area.')}</textarea>
                    </div>`;

            case 'reviews_band':
                return `
                    <div class="alert alert-warning" style="font-size:12px;">
                        <i class="bx bx-star me-1"></i>Live "What travelers are saying" reviews grid — pulls published reviews from <code>rg_destination_reviews</code> scoped to this keyword. Shows the aggregate star rating + a 3-column card grid. Empty when there are no reviews yet.
                    </div>
                    <div class="rg-edit-section">
                        <label class="form-label">Heading (H2)</label>
                        <input type="text" class="form-control" data-field="heading" value="${escapeHtml(p.heading || 'What travelers are saying')}">
                    </div>`;

            case 'dest_hero_search': {
                const chipsText = Array.isArray(p.search_chips) ? p.search_chips.join('\n') : (p.search_chips || '');
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-search-alt-2 me-1"></i><strong>Destinations Hero + Search</strong> — top section combining gradient hero, breadcrumb, eyebrow chip, H1 (with <code>@{{accent}}…@{{/accent}}</code> markers), intro paragraphs, stats pills, and TripAdvisor-style typeahead search (filter tabs + popular chips + grouped result panel + fuzzy matching + keyboard nav).
                        <br><br>
                        Stats pills + search results read live from the controller context (<code>stats</code> + <code>searchIndex</code>).
                    </div>
                    <div class="rg-edit-section">
                        <div class="row">
                            <div class="col-md-8 mb-2">
                                <label class="form-label">Eyebrow chip (small uppercase above title)</label>
                                <input type="text" class="form-control" data-field="eyebrow" value="${escapeHtml(p.eyebrow || '')}">
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="form-label">Accent color</label>
                                <select class="form-select" data-field="accent">
                                    <option value="brand" ${(!p.accent || p.accent === 'brand') ? 'selected' : ''}>Brand (blue)</option>
                                    <option value="amber" ${p.accent === 'amber' ? 'selected' : ''}>Amber</option>
                                    <option value="emerald" ${p.accent === 'emerald' ? 'selected' : ''}>Emerald</option>
                                    <option value="rose" ${p.accent === 'rose' ? 'selected' : ''}>Rose</option>
                                    <option value="violet" ${p.accent === 'violet' ? 'selected' : ''}>Violet</option>
                                    <option value="teal" ${p.accent === 'teal' ? 'selected' : ''}>Teal</option>
                                    <option value="slate" ${p.accent === 'slate' ? 'selected' : ''}>Slate</option>
                                </select>
                            </div>
                        </div>
                        <label class="form-label mt-2">H1 Title (use <code>@{{accent}}…@{{/accent}}</code> around the colored portion)</label>
                        <input type="text" class="form-control" data-field="title" value="${escapeHtml(p.title || '')}">
                        <label class="form-label mt-2">Intro paragraphs (one per line, blank line between)</label>
                        <textarea class="form-control" data-field="paragraphs" rows="3" style="font-family:inherit;">${escapeHtml(Array.isArray(p.paragraphs) ? p.paragraphs.join('\n\n') : (p.paragraphs || ''))}</textarea>
                        <label class="form-label mt-2">Background gradient</label>
                        <select class="form-select" data-field="bg_gradient">
                            <option value="none" ${p.bg_gradient === 'none' ? 'selected' : ''}>None</option>
                            <option value="brand-emerald" ${(!p.bg_gradient || p.bg_gradient === 'brand-emerald') ? 'selected' : ''}>Brand → Emerald</option>
                            <option value="amber-rose" ${p.bg_gradient === 'amber-rose' ? 'selected' : ''}>Amber → Rose</option>
                            <option value="rose-amber" ${p.bg_gradient === 'rose-amber' ? 'selected' : ''}>Rose → Amber</option>
                            <option value="violet-teal" ${p.bg_gradient === 'violet-teal' ? 'selected' : ''}>Violet → Teal</option>
                            <option value="teal-emerald" ${p.bg_gradient === 'teal-emerald' ? 'selected' : ''}>Teal → Emerald</option>
                        </select>
                        <label class="form-label mt-2">Background image (faded behind the hero — overrides the gradient)</label>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <button type="button" class="rg-pick-btn ${p.background_image ? '' : 'is-empty'}" data-rg-pick="background_image"><i class="bx bx-image-add"></i> ${p.background_image ? 'Change image' : 'Choose image'}</button>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-rg-clear="background_image" ${p.background_image ? '' : 'style="display:none"'}><i class="bx bx-x"></i></button>
                        </div>
                        <input type="hidden" data-field="background_image" value="${escapeHtml(p.background_image || '')}">
                        <img class="rg-image-preview" data-rg-preview="background_image" src="${p.background_image ? escapeHtml(mediaUrl(p.background_image)) : ''}" ${p.background_image ? '' : 'style="display:none"'}>
                    </div>
                    ${homeArraySectionsHtml(block.type, p)}
                    <div class="rg-edit-section">
                        <h6 class="text-muted mb-2" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em;">Search</h6>
                        <label class="form-label">Search placeholder</label>
                        <input type="text" class="form-control" data-field="search_placeholder" value="${escapeHtml(p.search_placeholder || '')}">
                        <label class="form-label mt-2">Popular chips (one per line)</label>
                        <textarea class="form-control" data-field="search_chips" rows="3">${escapeHtml(chipsText)}</textarea>
                        <label class="form-label mt-2">Result-type label map (JSON object — advanced)</label>
                        <input type="text" class="form-control" data-field="search_labels_json" value="${escapeHtml(p.search_labels_json || '{}')}">
                        <label class="form-label mt-2">Empty-state hint</label>
                        <input type="text" class="form-control" data-field="search_empty_hint" value="${escapeHtml(p.search_empty_hint || '')}">
                    </div>`;
            }

            case 'dest_featured_slider':
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-carousel me-1"></i><strong>Destinations Featured Slider</strong> — Splide carousel of featured tourist spots. Reads from <code>$context[source]</code> (default: <code>featuredSpots</code>). Each card has gradient backdrop + image + name + cuisine/category + city + link.
                    </div>
                    <div class="rg-edit-section">
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Eyebrow</label>
                                <input type="text" class="form-control" data-field="eyebrow" value="${escapeHtml(p.eyebrow || '')}">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="form-label">Slides per view (lg+)</label>
                                <select class="form-select" data-field="slides_per_view">
                                    <option value="1" ${parseInt(p.slides_per_view, 10) === 1 ? 'selected' : ''}>1</option>
                                    <option value="2" ${parseInt(p.slides_per_view, 10) === 2 ? 'selected' : ''}>2</option>
                                    <option value="3" ${(!p.slides_per_view || parseInt(p.slides_per_view, 10) === 3) ? 'selected' : ''}>3 (default)</option>
                                    <option value="4" ${parseInt(p.slides_per_view, 10) === 4 ? 'selected' : ''}>4</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="form-label">Background</label>
                                <select class="form-select" data-field="bg">
                                    <option value="light" ${(!p.bg || p.bg === 'light') ? 'selected' : ''}>Light band</option>
                                    <option value="none" ${p.bg === 'none' ? 'selected' : ''}>None</option>
                                </select>
                            </div>
                        </div>
                        <label class="form-label">Heading (H2)</label>
                        <input type="text" class="form-control" data-field="heading" value="${escapeHtml(p.heading || '')}">
                        <label class="form-label mt-2">Subhead</label>
                        <textarea class="form-control" data-field="subhead" rows="2">${escapeHtml(p.subhead || '')}</textarea>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label mt-2">Source context key</label>
                                <select class="form-select" data-field="source">
                                    <option value="featuredSpots" ${(!p.source || p.source === 'featuredSpots') ? 'selected' : ''}>featuredSpots (destinations)</option>
                                    <option value="featuredRestaurants" ${p.source === 'featuredRestaurants' ? 'selected' : ''}>featuredRestaurants (food-trip)</option>
                                    <option value="featured" ${p.source === 'featured' ? 'selected' : ''}>featured (hub pages)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label mt-2">Eyebrow accent</label>
                                <select class="form-select" data-field="accent">
                                    <option value="brand" ${(!p.accent || p.accent === 'brand') ? 'selected' : ''}>Brand</option>
                                    <option value="amber" ${p.accent === 'amber' ? 'selected' : ''}>Amber</option>
                                    <option value="emerald" ${p.accent === 'emerald' ? 'selected' : ''}>Emerald</option>
                                    <option value="rose" ${p.accent === 'rose' ? 'selected' : ''}>Rose</option>
                                    <option value="slate" ${p.accent === 'slate' ? 'selected' : ''}>Slate</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-2 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" data-field="autoplay" id="dfs_ap_${block.id || 'new'}" ${p.autoplay !== false ? 'checked' : ''}>
                                    <label class="form-check-label" for="dfs_ap_${block.id || 'new'}">Autoplay</label>
                                </div>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="form-label">Interval (ms)</label>
                                <input type="number" class="form-control" data-field="interval" value="${escapeHtml(p.interval || 5500)}" min="1500" max="20000" step="500">
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="form-label">Card link label</label>
                                <input type="text" class="form-control" data-field="cta_label" placeholder="Explore stays" value="${escapeHtml(p.cta_label || '')}">
                            </div>
                        </div>
                    </div>`;

            case 'home_hero_centered': {
                const statsJsonH = JSON.stringify(p.stats || [], null, 2);
                const pctaH = p.primary_cta || {};
                const sctaH = p.secondary_cta || {};
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-home-heart me-1"></i><strong>Home Hero (centered)</strong> — full-bleed gradient hero with centered title + tagline + two CTA buttons + 3-stat row. Title supports <code>|</code> as a line-break marker. Stats can be literal or read from <code>$context['stats']</code>.
                    </div>
                    <div class="rg-edit-section">
                        <label class="form-label">Title (use <code>|</code> for line break)</label>
                        <input type="text" class="form-control" data-field="title" value="${escapeHtml(p.title || '')}">
                        <label class="form-label mt-2">Tagline</label>
                        <textarea class="form-control" data-field="tagline" rows="2">${escapeHtml(p.tagline || '')}</textarea>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label mt-2">Accent (CTA + stat color)</label>
                                <select class="form-select" data-field="accent">
                                    <option value="brand" ${(!p.accent || p.accent === 'brand') ? 'selected' : ''}>Brand (blue)</option>
                                    <option value="amber" ${p.accent === 'amber' ? 'selected' : ''}>Amber</option>
                                    <option value="emerald" ${p.accent === 'emerald' ? 'selected' : ''}>Emerald</option>
                                    <option value="rose" ${p.accent === 'rose' ? 'selected' : ''}>Rose</option>
                                    <option value="violet" ${p.accent === 'violet' ? 'selected' : ''}>Violet</option>
                                    <option value="teal" ${p.accent === 'teal' ? 'selected' : ''}>Teal</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label mt-2">Background gradient</label>
                                <select class="form-select" data-field="bg_gradient">
                                    <option value="none" ${p.bg_gradient === 'none' ? 'selected' : ''}>None</option>
                                    <option value="brand-emerald" ${(!p.bg_gradient || p.bg_gradient === 'brand-emerald') ? 'selected' : ''}>Brand → Emerald</option>
                                    <option value="amber-rose" ${p.bg_gradient === 'amber-rose' ? 'selected' : ''}>Amber → Rose</option>
                                    <option value="rose-amber" ${p.bg_gradient === 'rose-amber' ? 'selected' : ''}>Rose → Amber</option>
                                    <option value="violet-teal" ${p.bg_gradient === 'violet-teal' ? 'selected' : ''}>Violet → Teal</option>
                                    <option value="teal-emerald" ${p.bg_gradient === 'teal-emerald' ? 'selected' : ''}>Teal → Emerald</option>
                                </select>
                            </div>
                        </div>
                        <label class="form-label mt-2">Background image (faded behind the hero — overrides the gradient)</label>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <button type="button" class="rg-pick-btn ${p.background_image ? '' : 'is-empty'}" data-rg-pick="background_image"><i class="bx bx-image-add"></i> ${p.background_image ? 'Change image' : 'Choose image'}</button>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-rg-clear="background_image" ${p.background_image ? '' : 'style="display:none"'}><i class="bx bx-x"></i></button>
                        </div>
                        <input type="hidden" data-field="background_image" value="${escapeHtml(p.background_image || '')}">
                        <img class="rg-image-preview" data-rg-preview="background_image" src="${p.background_image ? escapeHtml(mediaUrl(p.background_image)) : ''}" ${p.background_image ? '' : 'style="display:none"'}>
                        <hr class="my-3">
                        <h6 class="text-muted mb-2" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em;">CTA buttons</h6>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Primary label</label>
                                <input type="text" class="form-control" data-field="primary_cta.label" value="${escapeHtml(pctaH.label || '')}">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Primary URL</label>
                                <input type="text" class="form-control" data-field="primary_cta.url" value="${escapeHtml(pctaH.url || '')}">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Secondary label</label>
                                <input type="text" class="form-control" data-field="secondary_cta.label" value="${escapeHtml(sctaH.label || '')}">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Secondary URL</label>
                                <input type="text" class="form-control" data-field="secondary_cta.url" value="${escapeHtml(sctaH.url || '')}">
                            </div>
                        </div>
                        <hr class="my-3">
                        <label class="form-label">Stats (JSON: <code>[{label, value, value_source}]</code>)</label>
                        <div class="form-text small mb-1">value_source: <code>literal</code> · <code>stats.pages</code> · <code>stats.resorts</code> · <code>stats.total_destinations</code> · etc.</div>
                        <textarea class="form-control" data-field="stats" rows="6" style="font-family:ui-monospace,Menlo,Consolas,monospace;font-size:12px;">${escapeHtml(statsJsonH)}</textarea>
                    </div>`;
            }

            case 'home_keyword_grid': {
                const vaKw = p.view_all || {};
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-grid-alt me-1"></i><strong>Home Keyword Grid</strong> — popular destinations card grid. Reads <code>$context[source]</code> (default: <code>featuredKeywords</code>).
                    </div>
                    <div class="rg-edit-section">
                        <label class="form-label">Heading (H2)</label>
                        <input type="text" class="form-control" data-field="heading" value="${escapeHtml(p.heading || '')}">
                        <label class="form-label mt-2">Subhead</label>
                        <input type="text" class="form-control" data-field="subhead" value="${escapeHtml(p.subhead || '')}">
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <label class="form-label">Columns</label>
                                <select class="form-select" data-field="columns">
                                    <option value="2" ${parseInt(p.columns,10)===2?'selected':''}>2</option>
                                    <option value="3" ${(!p.columns||parseInt(p.columns,10)===3)?'selected':''}>3 (default)</option>
                                    <option value="4" ${parseInt(p.columns,10)===4?'selected':''}>4</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="form-label">Source</label>
                                <select class="form-select" data-field="source">
                                    <option value="featuredKeywords" ${(!p.source||p.source==='featuredKeywords')?'selected':''}>featuredKeywords</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="form-label">Accent</label>
                                <select class="form-select" data-field="accent">
                                    <option value="brand" ${(!p.accent||p.accent==='brand')?'selected':''}>Brand</option>
                                    <option value="amber" ${p.accent==='amber'?'selected':''}>Amber</option>
                                    <option value="emerald" ${p.accent==='emerald'?'selected':''}>Emerald</option>
                                    <option value="rose" ${p.accent==='rose'?'selected':''}>Rose</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label">View-all label</label>
                                <input type="text" class="form-control" data-field="view_all.label" value="${escapeHtml(vaKw.label || '')}">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label">View-all URL</label>
                                <input type="text" class="form-control" data-field="view_all.url" value="${escapeHtml(vaKw.url || '')}">
                            </div>
                        </div>
                    </div>`;
            }

            case 'home_region_grid': {
                const vaRg = p.view_all || {};
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-map me-1"></i><strong>Home Region Grid</strong> — region-cluster summary cards. Reads <code>$context[source]</code> (default: <code>regions</code>).
                    </div>
                    <div class="rg-edit-section">
                        <label class="form-label">Heading (H2)</label>
                        <input type="text" class="form-control" data-field="heading" value="${escapeHtml(p.heading || '')}">
                        <label class="form-label mt-2">Subhead</label>
                        <input type="text" class="form-control" data-field="subhead" value="${escapeHtml(p.subhead || '')}">
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Background</label>
                                <select class="form-select" data-field="bg">
                                    <option value="soft" ${(!p.bg||p.bg==='soft')?'selected':''}>Soft gradient (white → slate-50)</option>
                                    <option value="none" ${p.bg==='none'?'selected':''}>None</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Accent</label>
                                <select class="form-select" data-field="accent">
                                    <option value="brand" ${(!p.accent||p.accent==='brand')?'selected':''}>Brand</option>
                                    <option value="amber" ${p.accent==='amber'?'selected':''}>Amber</option>
                                    <option value="emerald" ${p.accent==='emerald'?'selected':''}>Emerald</option>
                                    <option value="rose" ${p.accent==='rose'?'selected':''}>Rose</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label">View-all label</label>
                                <input type="text" class="form-control" data-field="view_all.label" value="${escapeHtml(vaRg.label || '')}">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label">View-all URL</label>
                                <input type="text" class="form-control" data-field="view_all.url" value="${escapeHtml(vaRg.url || '')}">
                            </div>
                        </div>
                    </div>`;
            }

            case 'home_resort_grid':
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-building-house me-1"></i><strong>Home Resort Grid</strong> — featured properties grid with hero images. Reads <code>$context[source]</code> (default: <code>featuredResorts</code>).
                    </div>
                    <div class="rg-edit-section">
                        <label class="form-label">Heading (H2)</label>
                        <input type="text" class="form-control" data-field="heading" value="${escapeHtml(p.heading || '')}">
                        <label class="form-label mt-2">Subhead</label>
                        <input type="text" class="form-control" data-field="subhead" value="${escapeHtml(p.subhead || '')}">
                        <label class="form-label mt-2">Background</label>
                        <select class="form-select" data-field="bg">
                            <option value="slate" ${(!p.bg||p.bg==='slate')?'selected':''}>Slate-50 band</option>
                            <option value="none" ${p.bg==='none'?'selected':''}>None</option>
                        </select>
                    </div>`;

            case 'home_blog_strip':
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-news me-1"></i><strong>Home Blog Strip</strong> — 3-up latest-post cards. Reads <code>$context[source]</code> (default: <code>latestPosts</code>).
                    </div>
                    <div class="rg-edit-section">
                        <label class="form-label">Heading (H2)</label>
                        <input type="text" class="form-control" data-field="heading" value="${escapeHtml(p.heading || '')}">
                        <label class="form-label mt-2">Subhead</label>
                        <input type="text" class="form-control" data-field="subhead" value="${escapeHtml(p.subhead || '')}">
                    </div>`;

            case 'home_cta_band': {
                const ctaH = p.cta || {};
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-megaphone me-1"></i><strong>Home CTA Band</strong> — full-bleed brand-colored band with H2 + body + single CTA button.
                    </div>
                    <div class="rg-edit-section">
                        <label class="form-label">Heading (H2)</label>
                        <input type="text" class="form-control" data-field="heading" value="${escapeHtml(p.heading || '')}">
                        <label class="form-label mt-2">Body</label>
                        <textarea class="form-control" data-field="body" rows="3">${escapeHtml(p.body || '')}</textarea>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label">CTA label</label>
                                <input type="text" class="form-control" data-field="cta.label" value="${escapeHtml(ctaH.label || '')}">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label">CTA URL</label>
                                <input type="text" class="form-control" data-field="cta.url" value="${escapeHtml(ctaH.url || '')}">
                            </div>
                        </div>
                        <label class="form-label mt-2">Accent band color</label>
                        <select class="form-select" data-field="accent">
                            <option value="brand" ${(!p.accent||p.accent==='brand')?'selected':''}>Brand (blue)</option>
                            <option value="amber" ${p.accent==='amber'?'selected':''}>Amber</option>
                            <option value="emerald" ${p.accent==='emerald'?'selected':''}>Emerald</option>
                            <option value="rose" ${p.accent==='rose'?'selected':''}>Rose</option>
                            <option value="violet" ${p.accent==='violet'?'selected':''}>Violet</option>
                            <option value="teal" ${p.accent==='teal'?'selected':''}>Teal</option>
                            <option value="slate" ${p.accent==='slate'?'selected':''}>Slate (dark)</option>
                        </select>
                    </div>`;
            }

            case 'destcluster_hero':
                return `
                    <div class="alert alert-info" style="font-size:12px;"><i class="bx bx-map-pin me-1"></i><strong>Cluster Hero</strong> — faded tourist-spot photo background + the region name in the Tahu brush + tagline. Region name, tagline and photo are automatic per cluster.</div>
                    <div class="rg-edit-section">
                        <div class="mb-2"><label class="form-label">Eyebrow chip</label>
                            <input type="text" class="form-control" data-field="eyebrow" placeholder="Destination Guide" value="${escapeHtml(p.eyebrow || '')}"></div>
                        <div class="form-check"><input class="form-check-input" type="checkbox" data-field="show_breadcrumb" id="dch_bc_${block.id || 'new'}" ${p.show_breadcrumb !== false ? 'checked' : ''}><label class="form-check-label" for="dch_bc_${block.id || 'new'}">Show breadcrumb</label></div>
                    </div>`;
            case 'destcluster_whats_in':
                return `
                    <div class="alert alert-info" style="font-size:12px;"><i class="bx bx-grid-alt me-1"></i><strong>What's In [region]?</strong> — centered heading (region name in Tahu) + region intro + a photo card per keyword page. Intro and cards are automatic per cluster.</div>
                    <div class="rg-edit-section">
                        <div class="mb-2"><label class="form-label">Heading prefix (before the region name)</label>
                            <input type="text" class="form-control" data-field="heading_prefix" placeholder="What's In" value="${escapeHtml(p.heading_prefix || '')}"></div>
                        <div class="form-check"><input class="form-check-input" type="checkbox" data-field="show_intro" id="dcw_si_${block.id || 'new'}" ${p.show_intro !== false ? 'checked' : ''}><label class="form-check-label" for="dcw_si_${block.id || 'new'}">Show intro paragraphs</label></div>
                    </div>`;
            case 'destcluster_featured_spots':
                return `
                    <div class="alert alert-info" style="font-size:12px;"><i class="bx bx-images me-1"></i><strong>Featured Tourist Spots</strong> — 3-column crossfading, clickable spot cards. Photos, names and blurbs come from the region's tourist spots automatically.</div>
                    <div class="rg-edit-section">
                        <div class="row">
                            <div class="col-md-4 mb-2"><label class="form-label">Heading prefix</label>
                                <input type="text" class="form-control" data-field="heading_prefix" placeholder="Featured" value="${escapeHtml(p.heading_prefix || '')}"></div>
                            <div class="col-md-8 mb-2"><label class="form-label">Tahu word(s)</label>
                                <input type="text" class="form-control" data-field="tahu_word" placeholder="Tourist Spots" value="${escapeHtml(p.tahu_word || '')}"></div>
                        </div>
                        <div class="mb-2"><label class="form-label">Description</label>
                            <textarea class="form-control" rows="2" data-field="description" placeholder="A rotating look at the places...">${escapeHtml(p.description || '')}</textarea></div>
                    </div>`;
            case 'destcluster_testimonials':
                return `
                    <div class="alert alert-info" style="font-size:12px;"><i class="bx bx-chat me-1"></i><strong>Traveler Reviews</strong> — placeholder review cards referencing real spots in the region. Names, ratings and text are generated per cluster.</div>
                    <div class="rg-edit-section">
                        <div class="row">
                            <div class="col-md-4 mb-2"><label class="form-label">Heading prefix</label>
                                <input type="text" class="form-control" data-field="heading_prefix" placeholder="What" value="${escapeHtml(p.heading_prefix || '')}"></div>
                            <div class="col-md-4 mb-2"><label class="form-label">Tahu word(s)</label>
                                <input type="text" class="form-control" data-field="tahu_word" placeholder="Travelers Say" value="${escapeHtml(p.tahu_word || '')}"></div>
                            <div class="col-md-4 mb-2"><label class="form-label">Heading suffix</label>
                                <input type="text" class="form-control" data-field="heading_suffix" placeholder="About" value="${escapeHtml(p.heading_suffix || '')}"></div>
                        </div>
                        <div class="mb-2"><label class="form-label">Description (blank = auto region blurb)</label>
                            <textarea class="form-control" rows="2" data-field="description">${escapeHtml(p.description || '')}</textarea></div>
                    </div>`;
            case 'destcluster_explore_regions':
                return `
                    <div class="alert alert-info" style="font-size:12px;"><i class="bx bx-map me-1"></i><strong>Explore Other Regions</strong> — cards linking to the other destination regions, with crossfading circle thumbnails. Cards are automatic.</div>
                    <div class="rg-edit-section">
                        <div class="mb-2"><label class="form-label">Section heading</label>
                            <input type="text" class="form-control" data-field="heading" placeholder="Explore Other Regions" value="${escapeHtml(p.heading || '')}"></div>
                    </div>`;
            case 'destcluster_hashtags':
                return `
                    <div class="alert alert-info" style="font-size:12px;"><i class="bx bx-hash me-1"></i><strong>Tags</strong> — dynamic hashtags generated from the region's keyword pages. Tags are automatic.</div>
                    <div class="rg-edit-section">
                        <div class="mb-2"><label class="form-label">Section heading</label>
                            <input type="text" class="form-control" data-field="heading" placeholder="Tags" value="${escapeHtml(p.heading || '')}"></div>
                        <div class="mb-2"><label class="form-label">Description (blank = auto region blurb)</label>
                            <textarea class="form-control" rows="2" data-field="description">${escapeHtml(p.description || '')}</textarea></div>
                    </div>`;
            case 'dest_region_clusters':
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-map-alt me-1"></i><strong>Destinations Region Clusters</strong> — sticky "Jump to region" pill nav + cluster grids of keyword cards. Reads <code>$context[source]</code> (default: <code>orderedClusters</code>). Each cluster section gets a <code>#cluster-{slug}</code> anchor so jump-nav pills can scroll to it.
                    </div>
                    <div class="rg-edit-section">
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Section heading (H2)</label>
                                <input type="text" class="form-control" data-field="heading" placeholder="Browse by region" value="${escapeHtml(p.heading || '')}">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Jump-nav prefix label</label>
                                <input type="text" class="form-control" data-field="jump_label" placeholder="Jump to" value="${escapeHtml(p.jump_label || 'Jump to')}">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Source context key</label>
                                <select class="form-select" data-field="source">
                                    <option value="orderedClusters" ${(!p.source || p.source === 'orderedClusters') ? 'selected' : ''}>orderedClusters (destinations)</option>
                                    <option value="groups" ${p.source === 'groups' ? 'selected' : ''}>groups (food-trip)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Accent color</label>
                                <select class="form-select" data-field="accent">
                                    <option value="brand" ${(!p.accent || p.accent === 'brand') ? 'selected' : ''}>Brand</option>
                                    <option value="amber" ${p.accent === 'amber' ? 'selected' : ''}>Amber</option>
                                    <option value="slate" ${p.accent === 'slate' ? 'selected' : ''}>Slate</option>
                                    <option value="teal" ${p.accent === 'teal' ? 'selected' : ''}>Teal</option>
                                    <option value="rose" ${p.accent === 'rose' ? 'selected' : ''}>Rose</option>
                                    <option value="violet" ${p.accent === 'violet' ? 'selected' : ''}>Violet</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Card icon set</label>
                                <select class="form-select" data-field="icon_set">
                                    <option value="destination" ${(!p.icon_set || p.icon_set === 'destination') ? 'selected' : ''}>Destination (beach, hotel, island…)</option>
                                    <option value="food" ${p.icon_set === 'food' ? 'selected' : ''}>Food (utensils, coffee, seafood…)</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" data-field="sticky_nav" id="drc_sn_${block.id || 'new'}" ${p.sticky_nav !== false ? 'checked' : ''}>
                            <label class="form-check-label" for="drc_sn_${block.id || 'new'}">Sticky jump-to-region nav (recommended)</label>
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" data-field="show_volume" id="drc_sv_${block.id || 'new'}" ${p.show_volume !== false ? 'checked' : ''}>
                            <label class="form-check-label" for="drc_sv_${block.id || 'new'}">Show "N people search this monthly" under each card title</label>
                        </div>
                    </div>`;

            case 'home_unified_search': {
                const tabsJson = JSON.stringify(p.tabs || [], null, 2);
                const chipsJson = JSON.stringify(p.chips || [], null, 2);
                const statsJsonUS = JSON.stringify(p.stats || [], null, 2);
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-search-alt-2 me-1"></i><strong>Home Unified Search</strong> — full-bleed gradient hero with title + tagline + filter tabs + powerful typeahead + popular chips + stats row. Reads <code>$context['unifiedSearchIndex']</code> (~1,100 cross-site items).
                    </div>
                    <div class="rg-edit-section">
                        <label class="form-label">Eyebrow</label>
                        <input type="text" class="form-control" data-field="eyebrow" value="${escapeHtml(p.eyebrow || '')}">
                        <label class="form-label mt-2">Title (use @{{accent}}…@{{/accent}} for colored portion)</label>
                        <input type="text" class="form-control" data-field="title" value="${escapeHtml(p.title || '')}">
                        <label class="form-label mt-2">Tagline</label>
                        <textarea class="form-control" data-field="tagline" rows="2">${escapeHtml(p.tagline || '')}</textarea>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label mt-2">Accent (button color)</label>
                                <select class="form-select" data-field="accent">
                                    <option value="brand" ${(!p.accent || p.accent === 'brand') ? 'selected' : ''}>Brand (blue)</option>
                                    <option value="amber" ${p.accent === 'amber' ? 'selected' : ''}>Amber</option>
                                    <option value="emerald" ${p.accent === 'emerald' ? 'selected' : ''}>Emerald</option>
                                    <option value="rose" ${p.accent === 'rose' ? 'selected' : ''}>Rose</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label mt-2">Background gradient</label>
                                <select class="form-select" data-field="bg_gradient">
                                    <option value="brand-emerald" ${(!p.bg_gradient || p.bg_gradient === 'brand-emerald') ? 'selected' : ''}>Brand → Emerald</option>
                                    <option value="amber-rose" ${p.bg_gradient === 'amber-rose' ? 'selected' : ''}>Amber → Rose</option>
                                    <option value="none" ${p.bg_gradient === 'none' ? 'selected' : ''}>None</option>
                                </select>
                            </div>
                        </div>
                        <label class="form-label mt-2">Search placeholder</label>
                        <input type="text" class="form-control" data-field="placeholder" value="${escapeHtml(p.placeholder || '')}">
                        <label class="form-label mt-2">Filter tabs (JSON: <code>[{value, label}]</code>)</label>
                        <textarea class="form-control" data-field="tabs" rows="6" style="font-family:ui-monospace,Menlo,Consolas,monospace;font-size:12px;">${escapeHtml(tabsJson)}</textarea>
                        <label class="form-label mt-2">Popular chips (JSON: array of strings)</label>
                        <textarea class="form-control" data-field="chips" rows="3" style="font-family:ui-monospace,Menlo,Consolas,monospace;font-size:12px;">${escapeHtml(chipsJson)}</textarea>
                        <label class="form-label mt-2">Group label map (JSON)</label>
                        <input type="text" class="form-control" data-field="labels_json" value="${escapeHtml(p.labels_json || '{}')}">
                        <label class="form-label mt-2">Empty-state hint</label>
                        <input type="text" class="form-control" data-field="empty_hint" value="${escapeHtml(p.empty_hint || '')}">
                        <label class="form-label mt-2">Stats (JSON: <code>[{label, value, value_source}]</code>)</label>
                        <textarea class="form-control" data-field="stats" rows="5" style="font-family:ui-monospace,Menlo,Consolas,monospace;font-size:12px;">${escapeHtml(statsJsonUS)}</textarea>
                    </div>
                    <div class="rg-edit-section">
                        <label class="form-label"><i class="bx bx-movie-play me-1"></i>Background video (YouTube URL or 11-char ID)</label>
                        <input type="text" class="form-control" data-field="background_video" value="${escapeHtml(p.background_video || '')}" placeholder="https://youtube.com/watch?v=… or dQw4w9WgXcQ">
                        <div class="form-text small mb-1">Plays muted + looped behind the hero with a light scrim so the text stays readable. Leave blank for the plain gradient.</div>
                        <label class="form-label mt-2">Video start (seconds)</label>
                        <input type="number" class="form-control" data-field="video_start_seconds" min="0" value="${escapeHtml(String(p.video_start_seconds != null ? p.video_start_seconds : 10))}">
                        <label class="form-label mt-2">Background image (use a video <em>or</em> an image, not both)</label>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <button type="button" class="rg-pick-btn ${p.background_image ? '' : 'is-empty'}" data-rg-pick="background_image"><i class="bx bx-image-add"></i> ${p.background_image ? 'Change image' : 'Choose image'}</button>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-rg-clear="background_image" ${p.background_image ? '' : 'style="display:none"'}><i class="bx bx-x"></i></button>
                        </div>
                        <input type="hidden" data-field="background_image" value="${escapeHtml(p.background_image || '')}">
                        <img class="rg-image-preview" data-rg-preview="background_image" src="${p.background_image ? escapeHtml(mediaUrl(p.background_image)) : ''}" ${p.background_image ? '' : 'style="display:none"'}>
                        <label class="form-label mt-2">Background image alt (SEO)</label>
                        <input type="text" class="form-control" data-field="background_alt" value="${escapeHtml(p.background_alt || '')}">
                        <label class="form-label mt-2">Background image title (SEO, optional)</label>
                        <input type="text" class="form-control" data-field="background_title" value="${escapeHtml(p.background_title || '')}">
                    </div>`;
            }

            case 'home_editorial_intro': {
                const paragraphsRaw = Array.isArray(p.paragraphs) ? p.paragraphs.join('\n\n') : (p.paragraphs || '');
                return `
                    <div class="alert alert-info" style="font-size:12px;"><i class="bx bx-pen me-1"></i><strong>Home Editorial Intro</strong> — H2 + paragraphs, an optional single featured photo, and an optional multi-column image strip (the "Travel the Philippines" 3 images). All images use the media box.</div>
                    <div class="rg-edit-section">
                        <label class="form-label">Eyebrow</label>
                        <input type="text" class="form-control" data-field="eyebrow" value="${escapeHtml(p.eyebrow || '')}">
                        <label class="form-label mt-2">Heading (H2 — use *word* for the red Tahu accent)</label>
                        <input type="text" class="form-control" data-field="heading" value="${escapeHtml(p.heading || '')}">
                        <label class="form-label mt-2">Paragraphs (blank line between)</label>
                        <textarea class="form-control" data-field="paragraphs" rows="8">${escapeHtml(paragraphsRaw)}</textarea>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label mt-2">Accent (rule bar color)</label>
                                <select class="form-select" data-field="accent">
                                    <option value="brand" ${(!p.accent || p.accent === 'brand') ? 'selected' : ''}>Brand</option>
                                    <option value="amber" ${p.accent === 'amber' ? 'selected' : ''}>Amber</option>
                                    <option value="emerald" ${p.accent === 'emerald' ? 'selected' : ''}>Emerald</option>
                                    <option value="rose" ${p.accent === 'rose' ? 'selected' : ''}>Rose</option>
                                    <option value="violet" ${p.accent === 'violet' ? 'selected' : ''}>Violet</option>
                                    <option value="teal" ${p.accent === 'teal' ? 'selected' : ''}>Teal</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label mt-2">Single image position</label>
                                <select class="form-select" data-field="image_position">
                                    <option value="none" ${(!p.image_position || p.image_position === 'none') ? 'selected' : ''}>None</option>
                                    <option value="left" ${p.image_position === 'left' ? 'selected' : ''}>Left</option>
                                    <option value="right" ${p.image_position === 'right' ? 'selected' : ''}>Right</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6"><label class="form-label mt-2">CTA label</label><input type="text" class="form-control" data-field="cta_label" value="${escapeHtml(p.cta_label || '')}"></div>
                            <div class="col-md-6"><label class="form-label mt-2">CTA url</label><input type="text" class="form-control" data-field="cta_url" value="${escapeHtml(p.cta_url || '')}"></div>
                        </div>
                        <hr>
                        <label class="form-label">Single featured image (used when the column strip below is empty)</label>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <button type="button" class="rg-pick-btn ${p.image_src ? '' : 'is-empty'}" data-rg-pick="image_src"><i class="bx bx-image-add"></i> ${p.image_src ? 'Change image' : 'Choose image'}</button>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-rg-clear="image_src" ${p.image_src ? '' : 'style="display:none"'}><i class="bx bx-x"></i></button>
                        </div>
                        <input type="hidden" data-field="image_src" value="${escapeHtml(p.image_src || '')}">
                        <img class="rg-image-preview" data-rg-preview="image_src" src="${p.image_src ? escapeHtml(mediaUrl(p.image_src)) : ''}" ${p.image_src ? '' : 'style="display:none"'}>
                        <label class="form-label mt-2">Single image alt</label>
                        <input type="text" class="form-control" data-field="image_alt" value="${escapeHtml(p.image_alt || '')}">
                        <label class="form-label mt-2">Single image title (SEO, optional)</label>
                        <input type="text" class="form-control" data-field="image_title" value="${escapeHtml(p.image_title || '')}">
                        <label class="form-label mt-2">Single image caption</label>
                        <input type="text" class="form-control" data-field="image_caption" value="${escapeHtml(p.image_caption || '')}">
                    </div>
                    ${homeArraySectionsHtml(block.type, p)}`;
            }

            case 'home_experience_grid': {
                const expCategoriesJson = JSON.stringify(p.categories || [], null, 2);
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-image me-1"></i><strong>Home Experience Grid</strong> — 6 large image tiles. Each tile: photo + gradient overlay + name + tagline + CTA arrow.
                    </div>
                    <div class="rg-edit-section">
                        <label class="form-label">Heading</label>
                        <input type="text" class="form-control" data-field="heading" value="${escapeHtml(p.heading || '')}">
                        <label class="form-label mt-2">Subhead</label>
                        <textarea class="form-control" data-field="subhead" rows="2">${escapeHtml(p.subhead || '')}</textarea>
                        <label class="form-label mt-2">Columns (lg+)</label>
                        <select class="form-select" data-field="columns">
                            <option value="2" ${parseInt(p.columns,10)===2?'selected':''}>2</option>
                            <option value="3" ${(!p.columns||parseInt(p.columns,10)===3)?'selected':''}>3 (default)</option>
                        </select>
                        <label class="form-label mt-2">Categories (JSON: <code>[{name, tagline, url, image_src, image_alt}]</code>)</label>
                        <textarea class="form-control" data-field="categories" rows="12" style="font-family:ui-monospace,Menlo,Consolas,monospace;font-size:12px;">${escapeHtml(expCategoriesJson)}</textarea>
                    </div>`;
            }

            case 'home_hub_links': {
                const hubsJson = JSON.stringify(p.hubs || [], null, 2);
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-link-alt me-1"></i><strong>Home Hub Links</strong> — 4 compact icon cards routing to discovery hubs. Each card uses its own accent (rose/emerald/violet/teal).
                    </div>
                    <div class="rg-edit-section">
                        <label class="form-label">Heading</label>
                        <input type="text" class="form-control" data-field="heading" value="${escapeHtml(p.heading || '')}">
                        <label class="form-label mt-2">Subhead</label>
                        <textarea class="form-control" data-field="subhead" rows="2">${escapeHtml(p.subhead || '')}</textarea>
                        <label class="form-label mt-2">Hubs (JSON: <code>[{label, url, icon, accent, tagline}]</code>)</label>
                        <div class="form-text small mb-1">accent: rose | emerald | violet | teal | amber | brand. icon: any emoji.</div>
                        <textarea class="form-control" data-field="hubs" rows="10" style="font-family:ui-monospace,Menlo,Consolas,monospace;font-size:12px;">${escapeHtml(hubsJson)}</textarea>
                    </div>`;
            }

            case 'home_season_guide': {
                return `
                    <div class="alert alert-info" style="font-size:12px;"><i class="bx bx-calendar me-1"></i><strong>Home Season Guide</strong> — the "There's Always Something to Do" slide carousel (each slide = image + activity chips) plus the crossfading mosaic grid. All images use the media box.</div>
                    <div class="rg-edit-section">
                        <label class="form-label">Eyebrow</label>
                        <input type="text" class="form-control" data-field="eyebrow" value="${escapeHtml(p.eyebrow || '')}">
                        <label class="form-label mt-2">Heading (rotating words use <code>@{{a|b|c}}</code>)</label>
                        <input type="text" class="form-control" data-field="heading" value="${escapeHtml(p.heading || '')}">
                        <label class="form-label mt-2">Subhead</label>
                        <textarea class="form-control" data-field="subhead" rows="2">${escapeHtml(p.subhead || '')}</textarea>
                        <div class="row">
                            <div class="col-md-6"><label class="form-label mt-2">CTA 1 label</label><input type="text" class="form-control" data-field="cta_label" value="${escapeHtml(p.cta_label || '')}"></div>
                            <div class="col-md-6"><label class="form-label mt-2">CTA 1 url</label><input type="text" class="form-control" data-field="cta_url" value="${escapeHtml(p.cta_url || '')}"></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6"><label class="form-label mt-2">CTA 2 label</label><input type="text" class="form-control" data-field="cta2_label" value="${escapeHtml(p.cta2_label || '')}"></div>
                            <div class="col-md-6"><label class="form-label mt-2">CTA 2 url</label><input type="text" class="form-control" data-field="cta2_url" value="${escapeHtml(p.cta2_url || '')}"></div>
                        </div>
                    </div>
                    ${homeArraySectionsHtml(block.type, p)}`;
            }

            case 'home_testimonials': {
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-chat me-1"></i><strong>Home Testimonials</strong> — review cards with a star row, quote, author, location, and avatar. Add a card per review below.
                    </div>
                    <div class="rg-edit-section">
                        <label class="form-label">Heading (use <code>*word*</code> for the red Tahu accent)</label>
                        <input type="text" class="form-control" data-field="heading" value="${escapeHtml(p.heading || '')}">
                        <label class="form-label mt-2">Subhead</label>
                        <textarea class="form-control" data-field="subhead" rows="2">${escapeHtml(p.subhead || '')}</textarea>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" data-field="flush" id="rgTestiFlush_${block.id || 'new'}" ${p.flush ? 'checked' : ''}>
                            <label class="form-check-label" for="rgTestiFlush_${block.id || 'new'}">Full-width (no inner max-width/padding — match page sections)</label>
                        </div>
                    </div>
                    ${homeArraySectionsHtml(block.type, p)}`;
            }

            case 'home_faq': {
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-help-circle me-1"></i><strong>Home FAQ</strong> — native &lt;details&gt; accordion + automatic FAQPage JSON-LD baked in (SEO rich-result eligible). Add a row per question below.
                    </div>
                    <div class="rg-edit-section">
                        <label class="form-label">Heading</label>
                        <input type="text" class="form-control" data-field="heading" value="${escapeHtml(p.heading || '')}">
                        <label class="form-label mt-2">Subhead</label>
                        <textarea class="form-control" data-field="subhead" rows="2">${escapeHtml(p.subhead || '')}</textarea>
                    </div>
                    ${homeArraySectionsHtml(block.type, p)}`;
            }

            case 'home_how_it_works': {
                return `
                    <div class="alert alert-info" style="font-size:12px;"><i class="bx bx-list-check me-1"></i><strong>Home How It Works</strong> — a "how the site works" explainer. The accordion layout stays light (collapsed by default, first step open). Each step's icon can be a chosen icon or an uploaded image.</div>
                    <div class="rg-edit-section">
                        <label class="form-label">Eyebrow</label>
                        <input type="text" class="form-control" data-field="eyebrow" value="${escapeHtml(p.eyebrow || '')}">
                        <label class="form-label mt-2">Heading (use *word* for the red Tahu accent)</label>
                        <input type="text" class="form-control" data-field="heading" value="${escapeHtml(p.heading || '')}">
                        <label class="form-label mt-2">Subhead</label>
                        <textarea class="form-control" data-field="subhead" rows="2">${escapeHtml(p.subhead || '')}</textarea>
                        <label class="form-label mt-2">Layout</label>
                        <select class="form-select" data-field="layout">
                            <option value="accordion" ${(!p.layout || p.layout === 'accordion') ? 'selected' : ''}>Expandable accordion (recommended)</option>
                            <option value="pillars" ${p.layout === 'pillars' ? 'selected' : ''}>Cards grid</option>
                        </select>
                    </div>
                    ${homeArraySectionsHtml(block.type, p)}`;
            }

            case 'home_values_grid': {
                const valuesJson = JSON.stringify(p.values || [], null, 2);
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-medal me-1"></i><strong>Home Values Grid</strong> — 4-card grid of principles. Each card: emoji icon tile + title + body. Per-card accent palette cycles automatically.
                    </div>
                    <div class="rg-edit-section">
                        <label class="form-label">Heading</label>
                        <input type="text" class="form-control" data-field="heading" value="${escapeHtml(p.heading || '')}">
                        <label class="form-label mt-2">Subhead</label>
                        <textarea class="form-control" data-field="subhead" rows="2">${escapeHtml(p.subhead || '')}</textarea>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label mt-2">Columns (lg+)</label>
                                <select class="form-select" data-field="columns">
                                    <option value="2" ${parseInt(p.columns,10)===2?'selected':''}>2</option>
                                    <option value="3" ${parseInt(p.columns,10)===3?'selected':''}>3</option>
                                    <option value="4" ${(!p.columns||parseInt(p.columns,10)===4)?'selected':''}>4 (default)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label mt-2">Background</label>
                                <select class="form-select" data-field="bg">
                                    <option value="light" ${(!p.bg||p.bg==='light')?'selected':''}>Light (slate-50)</option>
                                    <option value="gradient" ${p.bg==='gradient'?'selected':''}>Soft gradient</option>
                                    <option value="none" ${p.bg==='none'?'selected':''}>None</option>
                                </select>
                            </div>
                        </div>
                        <label class="form-label mt-2">Values (JSON: <code>[{title, body, icon}]</code>)</label>
                        <textarea class="form-control" data-field="values" rows="12" style="font-family:ui-monospace,Menlo,Consolas,monospace;font-size:12px;">${escapeHtml(valuesJson)}</textarea>
                    </div>`;
            }

            case 'hub_hero': {
                const hubParagraphsRaw = Array.isArray(p.paragraphs) ? p.paragraphs.join('\n\n') : (p.paragraphs || '');
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-heading me-1"></i><strong>Hub Hero</strong> — top of each hub page (foods/activities/buys/cultures). Eyebrow + colored H1 + multi-paragraph intro. Use <code>@{{accent}}…@{{/accent}}</code> around the colored portion of the title.
                    </div>
                    <div class="rg-edit-section">
                        <label class="form-label">Eyebrow chip</label>
                        <input type="text" class="form-control" data-field="eyebrow" value="${escapeHtml(p.eyebrow || '')}">
                        <label class="form-label mt-2">Title (use <code>@{{accent}}…@{{/accent}}</code>)</label>
                        <input type="text" class="form-control" data-field="title" value="${escapeHtml(p.title || '')}">
                        <label class="form-label mt-2">Accent color</label>
                        <select class="form-select" data-field="accent">
                            <option value="brand" ${(!p.accent || p.accent === 'brand') ? 'selected' : ''}>Brand (blue)</option>
                            <option value="rose" ${p.accent === 'rose' ? 'selected' : ''}>Rose (foods)</option>
                            <option value="emerald" ${p.accent === 'emerald' ? 'selected' : ''}>Emerald (activities)</option>
                            <option value="violet" ${p.accent === 'violet' ? 'selected' : ''}>Violet (buys)</option>
                            <option value="teal" ${p.accent === 'teal' ? 'selected' : ''}>Teal (cultures)</option>
                            <option value="amber" ${p.accent === 'amber' ? 'selected' : ''}>Amber</option>
                        </select>
                        <label class="form-label mt-2">Paragraphs (blank line between)</label>
                        <textarea class="form-control" data-field="paragraphs" rows="8">${escapeHtml(hubParagraphsRaw)}</textarea>
                        <label class="form-label mt-2">Background image (faded full-bleed hero — optional)</label>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <button type="button" class="rg-pick-btn ${p.background_image ? '' : 'is-empty'}" data-rg-pick="background_image"><i class="bx bx-image-add"></i> ${p.background_image ? 'Change image' : 'Choose image'}</button>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-rg-clear="background_image" ${p.background_image ? '' : 'style="display:none"'}><i class="bx bx-x"></i></button>
                        </div>
                        <input type="hidden" data-field="background_image" value="${escapeHtml(p.background_image || '')}">
                        <img class="rg-image-preview" data-rg-preview="background_image" src="${p.background_image ? escapeHtml(mediaUrl(p.background_image)) : ''}" ${p.background_image ? '' : 'style="display:none"'}>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" data-field="centered" id="hubHeroCentered_${block.id || 'new'}" ${p.centered ? 'checked' : ''}>
                            <label class="form-check-label" for="hubHeroCentered_${block.id || 'new'}">Centered hero + Tahu accent on its own line (site style)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" data-field="title_case" id="hubHeroTitleCase_${block.id || 'new'}" ${p.title_case ? 'checked' : ''}>
                            <label class="form-check-label" for="hubHeroTitleCase_${block.id || 'new'}">Title Case the heading</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" data-field="search" id="hubHeroSearch_${block.id || 'new'}" ${p.search ? 'checked' : ''}>
                            <label class="form-check-label" for="hubHeroSearch_${block.id || 'new'}">Location search bar (type a place → items for it; needs the controller's searchIndex)</label>
                        </div>
                    </div>`;
            }

            case 'hub_category_nav':
                return `
                    <div class="alert alert-warning" style="font-size:12px;">
                        <i class="bx bx-menu me-1"></i><strong>Hub Category Nav</strong> — sticky horizontal pill nav with one pill per category. Pulls the category list from the hub controller's <code>categories()</code> array. No payload list to maintain.
                    </div>
                    <div class="rg-edit-section">
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Label prefix</label>
                                <input type="text" class="form-control" data-field="label" value="${escapeHtml(p.label || 'Jump to')}">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Accent (hover color)</label>
                                <select class="form-select" data-field="accent">
                                    <option value="slate" ${(!p.accent || p.accent === 'slate') ? 'selected' : ''}>Slate</option>
                                    <option value="rose" ${p.accent === 'rose' ? 'selected' : ''}>Rose</option>
                                    <option value="emerald" ${p.accent === 'emerald' ? 'selected' : ''}>Emerald</option>
                                    <option value="violet" ${p.accent === 'violet' ? 'selected' : ''}>Violet</option>
                                    <option value="teal" ${p.accent === 'teal' ? 'selected' : ''}>Teal</option>
                                    <option value="amber" ${p.accent === 'amber' ? 'selected' : ''}>Amber</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" data-field="sticky" id="hcn2_sticky_${block.id || 'new'}" ${p.sticky !== false ? 'checked' : ''}>
                            <label class="form-check-label" for="hcn2_sticky_${block.id || 'new'}">Sticky to viewport top while scrolling (recommended)</label>
                        </div>
                    </div>`;

            case 'hub_category_grid':
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-grid me-1"></i><strong>Hub Category Grid</strong> — renders ONE category as a collapsible accordion (or flat section) with an item card grid. <code>category_key</code> selects which category from the hub's <code>categories()</code> array.
                        <br><br>
                        <strong>Category keys</strong> (use one): <em>Foods</em>: staples, street, exotic, sweets, luzon, visayas, mindanao. <em>Activities</em>: water, land, air, festival, nature, urban. <em>Buys</em>: agriculture, textiles, crafts, sweets. <em>Cultures</em>: lowland, cordillera, caraballo, mimaropa, visayas, lumad, moro.
                    </div>
                    <div class="rg-edit-section">
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Category key</label>
                                <input type="text" class="form-control" data-field="category_key" placeholder="staples" value="${escapeHtml(p.category_key || '')}">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Item URL prefix</label>
                                <input type="text" class="form-control" data-field="item_url_prefix" placeholder="/filipino-food-dishes-what-to-eat" value="${escapeHtml(p.item_url_prefix || '')}">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Cards per row (lg+)</label>
                                <select class="form-select" data-field="cards_per_row">
                                    <option value="2" ${parseInt(p.cards_per_row,10)===2?'selected':''}>2</option>
                                    <option value="3" ${parseInt(p.cards_per_row,10)===3?'selected':''}>3</option>
                                    <option value="4" ${(!p.cards_per_row||parseInt(p.cards_per_row,10)===4)?'selected':''}>4 (default)</option>
                                    <option value="5" ${parseInt(p.cards_per_row,10)===5?'selected':''}>5</option>
                                    <option value="6" ${parseInt(p.cards_per_row,10)===6?'selected':''}>6</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-2 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" data-field="as_accordion" id="hcg2_acc_${block.id || 'new'}" ${p.as_accordion !== false ? 'checked' : ''}>
                                    <label class="form-check-label" for="hcg2_acc_${block.id || 'new'}">Render as collapsible accordion (recommended)</label>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Section divider (flat mode only)</label>
                                <select class="form-select" data-field="divider">
                                    <option value="" ${!p.divider ? 'selected' : ''}>None</option>
                                    <option value="bowl" ${p.divider === 'bowl' ? 'selected' : ''}>Steaming bowl (foods)</option>
                                    <option value="compass" ${p.divider === 'compass' ? 'selected' : ''}>Compass (activities)</option>
                                    <option value="diver" ${p.divider === 'diver' ? 'selected' : ''}>Scuba diver over seabed (activities)</option>
                                    <option value="bag" ${p.divider === 'bag' ? 'selected' : ''}>Shopping bag (buys)</option>
                                    <option value="sun" ${p.divider === 'sun' ? 'selected' : ''}>Philippine sun (cultures)</option>
                                    <option value="zigzag" ${p.divider === 'zigzag' ? 'selected' : ''}>Tribal zigzag (cultures)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-2 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" data-field="image_cards" id="hcg_img_${block.id || 'new'}" ${p.image_cards ? 'checked' : ''}>
                                    <label class="form-check-label" for="hcg_img_${block.id || 'new'}">Photo cards (image on each item)</label>
                                </div>
                            </div>
                        </div>
                    </div>`;

            case 'hub_footer_rail': {
                return `
                    <div class="alert alert-info" style="font-size:12px;">
                        <i class="bx bx-link-external me-1"></i><strong>Hub Footer Rail</strong> — heading + body + themed link pills. Add a pill per link below and pick its theme colour.
                    </div>
                    <div class="rg-edit-section">
                        <label class="form-label">Heading</label>
                        <input type="text" class="form-control" data-field="heading" value="${escapeHtml(p.heading || '')}">
                        <label class="form-label mt-2">Body</label>
                        <textarea class="form-control" data-field="body" rows="4">${escapeHtml(p.body || '')}</textarea>
                    </div>
                    ${homeArraySectionsHtml(block.type, p)}`;
            }

            case 'home_press_logos': {
                return `
                    <div class="alert alert-info" style="font-size:12px;"><i class="bx bx-bookmark-star me-1"></i><strong>Home Press Logos</strong> — the "As Seen On" logo strip. Each logo image uses the media box / upload.</div>
                    <div class="rg-edit-section">
                        <label class="form-label">Eyebrow</label>
                        <input type="text" class="form-control" data-field="eyebrow" value="${escapeHtml(p.eyebrow || '')}">
                    </div>
                    ${homeArraySectionsHtml(block.type, p)}`;
            }

            case 'home_category_accordion': {
                const accRotJson = JSON.stringify(p.rotating_words || [], null, 2);
                return `
                    <div class="alert alert-info" style="font-size:12px;"><i class="bx bx-collapse me-1"></i><strong>Home Category Accordion</strong> — "Find Your Kind of Trip" panels. Each panel's images use the media box.</div>
                    <div class="rg-edit-section">
                        <label class="form-label">Eyebrow</label>
                        <input type="text" class="form-control" data-field="eyebrow" value="${escapeHtml(p.eyebrow || '')}">
                        <label class="form-label mt-2">Heading (use *word* for the red Tahu accent)</label>
                        <input type="text" class="form-control" data-field="heading" value="${escapeHtml(p.heading || '')}">
                        <label class="form-label mt-2">Subhead</label>
                        <textarea class="form-control" data-field="subhead" rows="2">${escapeHtml(p.subhead || '')}</textarea>
                        <div class="row">
                            <div class="col-md-6"><label class="form-label mt-2">CTA label</label><input type="text" class="form-control" data-field="cta2_label" value="${escapeHtml(p.cta2_label || '')}"></div>
                            <div class="col-md-6"><label class="form-label mt-2">CTA url</label><input type="text" class="form-control" data-field="cta2_url" value="${escapeHtml(p.cta2_url || '')}"></div>
                        </div>
                    </div>
                    ${homeArraySectionsHtml(block.type, p)}
                    <div class="rg-edit-section">
                        <label class="form-label mt-2">Rotating words (JSON array of strings)</label>
                        <textarea class="form-control" data-field="rotating_words" rows="4" style="font-family:ui-monospace,Menlo,Consolas,monospace;font-size:12px;">${escapeHtml(accRotJson)}</textarea>
                    </div>`;
            }

            case 'home_alt_features': {
                return `
                    <div class="alert alert-info" style="font-size:12px;"><i class="bx bx-layout me-1"></i><strong>Home Alt Features</strong> — alternating image / text rows; each row's images use the media box and crossfade.</div>
                    <div class="rg-edit-section">
                        <label class="form-label">Eyebrow</label>
                        <input type="text" class="form-control" data-field="eyebrow" value="${escapeHtml(p.eyebrow || '')}">
                        <label class="form-label mt-2">Heading (use *word* for the red Tahu accent)</label>
                        <input type="text" class="form-control" data-field="heading" value="${escapeHtml(p.heading || '')}">
                        <label class="form-label mt-2">Subhead</label>
                        <textarea class="form-control" data-field="subhead" rows="2">${escapeHtml(p.subhead || '')}</textarea>
                    </div>
                    ${homeArraySectionsHtml(block.type, p)}`;
            }

            case 'home_badge_explainer': {
                return `
                    <div class="alert alert-info" style="font-size:12px;"><i class="bx bx-badge-check me-1"></i><strong>Home Badge Explainer</strong> — "Get the Recommended Badge" with image slider + reviews. Every image uses the media box.</div>
                    <div class="rg-edit-section">
                        <label class="form-label">Eyebrow</label>
                        <input type="text" class="form-control" data-field="eyebrow" value="${escapeHtml(p.eyebrow || '')}">
                        <label class="form-label mt-2">Heading (use ~word~ for big red Tahu)</label>
                        <input type="text" class="form-control" data-field="heading" value="${escapeHtml(p.heading || '')}">
                        <label class="form-label mt-2">Body</label>
                        <textarea class="form-control" data-field="body" rows="3">${escapeHtml(p.body || '')}</textarea>
                        <label class="form-label mt-2">Primary image (shown when the slider has only one image)</label>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <button type="button" class="rg-pick-btn ${p.badge_image ? '' : 'is-empty'}" data-rg-pick="badge_image"><i class="bx bx-image-add"></i> ${p.badge_image ? 'Change image' : 'Choose image'}</button>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-rg-clear="badge_image" ${p.badge_image ? '' : 'style="display:none"'}><i class="bx bx-x"></i></button>
                        </div>
                        <input type="hidden" data-field="badge_image" value="${escapeHtml(p.badge_image || '')}">
                        <img class="rg-image-preview" data-rg-preview="badge_image" src="${p.badge_image ? escapeHtml(mediaUrl(p.badge_image)) : ''}" ${p.badge_image ? '' : 'style="display:none"'}>
                        <label class="form-label mt-2">Primary image alt (SEO)</label>
                        <input type="text" class="form-control" data-field="badge_alt" value="${escapeHtml(p.badge_alt || '')}">
                        <label class="form-label mt-2">Primary image title (SEO, optional)</label>
                        <input type="text" class="form-control" data-field="badge_title" value="${escapeHtml(p.badge_title || '')}">
                        <hr>
                        <div class="row">
                            <div class="col-md-6"><label class="form-label">CTA label</label><input type="text" class="form-control" data-field="cta_label" value="${escapeHtml(p.cta_label || '')}"></div>
                            <div class="col-md-6"><label class="form-label">CTA url</label><input type="text" class="form-control" data-field="cta_url" value="${escapeHtml(p.cta_url || '')}"></div>
                        </div>
                        <label class="form-label mt-2">CTA description</label>
                        <textarea class="form-control" data-field="cta_desc" rows="2">${escapeHtml(p.cta_desc || '')}</textarea>
                        <label class="form-label mt-2">CTA note (e.g. pricing)</label>
                        <input type="text" class="form-control" data-field="cta_note" value="${escapeHtml(p.cta_note || '')}">
                    </div>
                    ${homeArraySectionsHtml(block.type, p)}`;
            }

            case 'home_partner_perks': {
                return `
                    <div class="alert alert-info" style="font-size:12px;"><i class="bx bx-handshake me-1"></i><strong>Home Partner Perks</strong> — "Become a Partner" perks grid + reviews. Review avatars use the media box.</div>
                    <div class="rg-edit-section">
                        <label class="form-label">Eyebrow</label>
                        <input type="text" class="form-control" data-field="eyebrow" value="${escapeHtml(p.eyebrow || '')}">
                        <label class="form-label mt-2">Heading (use ~word~ for big red Tahu)</label>
                        <input type="text" class="form-control" data-field="heading" value="${escapeHtml(p.heading || '')}">
                        <label class="form-label mt-2">Subhead</label>
                        <textarea class="form-control" data-field="subhead" rows="3">${escapeHtml(p.subhead || '')}</textarea>
                        <div class="row">
                            <div class="col-md-6"><label class="form-label mt-2">CTA label</label><input type="text" class="form-control" data-field="cta_label" value="${escapeHtml(p.cta_label || '')}"></div>
                            <div class="col-md-6"><label class="form-label mt-2">CTA url</label><input type="text" class="form-control" data-field="cta_url" value="${escapeHtml(p.cta_url || '')}"></div>
                        </div>
                        <label class="form-label mt-2">CTA description</label>
                        <textarea class="form-control" data-field="cta_desc" rows="2">${escapeHtml(p.cta_desc || '')}</textarea>
                        <label class="form-label mt-2">CTA note (e.g. pricing)</label>
                        <input type="text" class="form-control" data-field="cta_note" value="${escapeHtml(p.cta_note || '')}">
                    </div>
                    ${homeArraySectionsHtml(block.type, p)}`;
            }

            case 'home_keyword_hashtags': {
                return `
                    <div class="alert alert-info" style="font-size:12px;"><i class="bx bx-hash me-1"></i><strong>Keyword Hashtags</strong> — auto-built tag cloud from published keyword pages; each tag links to its page.</div>
                    <div class="rg-edit-section">
                        <label class="form-label">Eyebrow (small label above heading)</label>
                        <input type="text" class="form-control" data-field="eyebrow" value="${escapeHtml(p.eyebrow || '')}">
                        <label class="form-label mt-2">Heading (use <code>*word*</code> for a Tahu accent)</label>
                        <input type="text" class="form-control" data-field="heading" value="${escapeHtml(p.heading || '')}">
                        <label class="form-label mt-2">Sub-heading</label>
                        <textarea class="form-control" data-field="subhead" rows="2">${escapeHtml(p.subhead || '')}</textarea>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label">How many tags</label>
                                <input type="number" class="form-control" data-field="limit" min="5" max="200" value="${escapeHtml(String(p.limit || 40))}">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Category filter</label>
                                <select class="form-select" data-field="category">
                                    <option value="" ${!p.category ? 'selected' : ''}>All categories</option>
                                    <option value="resort" ${p.category === 'resort' ? 'selected' : ''}>Resort / destinations</option>
                                    <option value="food" ${p.category === 'food' ? 'selected' : ''}>Food</option>
                                </select>
                            </div>
                        </div>
                        <label class="form-label">Tag source</label>
                        <select class="form-select" data-field="source">
                            <option value="" ${!p.source ? 'selected' : ''}>Auto — live keyword pages (homepage / destinations)</option>
                            <option value="hubTags" ${p.source === 'hubTags' ? 'selected' : ''}>This page's hub tags (what to eat / do / buy / cultures)</option>
                        </select>
                        <div class="form-text small">Leave on <em>Auto</em> for the homepage and destinations. Choose <em>hub tags</em> on the what-to-eat / do / buy / cultures pages, where tags come from the page's own item list. In Auto mode tags are generated live + shuffled each load; no manual list needed.</div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" data-field="flush" id="rgHashFlush" ${p.flush ? 'checked' : ''}>
                            <label class="form-check-label" for="rgHashFlush">Flush style (blue #, full width, light top divider)</label>
                        </div>
                    </div>`;
            }

            case 'partner_hero':
                return `
                    <div class="alert alert-info" style="font-size:12px;"><i class="bx bx-handshake me-1"></i><strong>Partner Hero</strong> — split hero: eyebrow chip, big title (wrap the red brush-script line in <code>@{{accent}}…@{{/accent}}</code>), subhead, two CTAs, trust points, and an image with a floating "verified" badge card.</div>
                    <div class="rg-edit-section">
                        <label class="form-label">Eyebrow chip</label>
                        <input type="text" class="form-control" data-field="eyebrow" value="${escapeHtml(p.eyebrow || '')}">
                        <label class="form-label mt-2">Title (wrap the red brush line in <code>@{{accent}}…@{{/accent}}</code>)</label>
                        <input type="text" class="form-control" data-field="title" value="${escapeHtml(p.title || '')}">
                        <label class="form-label mt-2">Subhead</label>
                        <textarea class="form-control" rows="2" data-field="subhead">${escapeHtml(p.subhead || '')}</textarea>
                    </div>
                    <div class="rg-edit-section">
                        <h6 class="text-muted mb-2" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em;">Call to action</h6>
                        <div class="row">
                            <div class="col-md-6 mb-2"><label class="form-label">Primary button label</label>
                                <input type="text" class="form-control" data-field="cta_primary_label" value="${escapeHtml(p.cta_primary_label || '')}"></div>
                            <div class="col-md-6 mb-2"><label class="form-label">Primary button URL</label>
                                <input type="text" class="form-control" data-field="cta_primary_url" value="${escapeHtml(p.cta_primary_url || '')}"></div>
                            <div class="col-md-6 mb-2"><label class="form-label">Secondary button label</label>
                                <input type="text" class="form-control" data-field="cta_secondary_label" value="${escapeHtml(p.cta_secondary_label || '')}"></div>
                            <div class="col-md-6 mb-2"><label class="form-label">Secondary button URL</label>
                                <input type="text" class="form-control" data-field="cta_secondary_url" value="${escapeHtml(p.cta_secondary_url || '')}"></div>
                        </div>
                        <label class="form-label mt-1">Trust points (one per line)</label>
                        <textarea class="form-control" rows="3" data-field="trust_points">${escapeHtml(Array.isArray(p.trust_points) ? p.trust_points.join('\n') : (p.trust_points || ''))}</textarea>
                    </div>
                    <div class="rg-edit-section">
                        <h6 class="text-muted mb-2" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em;">Image + verified badge</h6>
                        <label class="form-label">Hero image</label>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <button type="button" class="rg-pick-btn ${p.image ? '' : 'is-empty'}" data-rg-pick="image"><i class="bx bx-image-add"></i> ${p.image ? 'Change image' : 'Choose image'}</button>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-rg-clear="image" ${p.image ? '' : 'style="display:none"'}><i class="bx bx-x"></i></button>
                        </div>
                        <input type="hidden" data-field="image" value="${escapeHtml(p.image || '')}">
                        <img class="rg-image-preview" data-rg-preview="image" src="${p.image ? escapeHtml(mediaUrl(p.image)) : ''}" ${p.image ? '' : 'style="display:none"'}>
                        <label class="form-label mt-2">Image alt text</label>
                        <input type="text" class="form-control" data-field="image_alt" value="${escapeHtml(p.image_alt || '')}">
                        <div class="row">
                            <div class="col-md-6 mb-2"><label class="form-label mt-2">Badge title</label>
                                <input type="text" class="form-control" data-field="badge_title" value="${escapeHtml(p.badge_title || '')}"></div>
                            <div class="col-md-6 mb-2"><label class="form-label mt-2">Badge subtitle</label>
                                <input type="text" class="form-control" data-field="badge_subtitle" value="${escapeHtml(p.badge_subtitle || '')}"></div>
                        </div>
                    </div>`;
            case 'partner_audience':
                return `
                    <div class="alert alert-info" style="font-size:12px;"><i class="bx bx-group me-1"></i><strong>Who Can Join</strong> — heading (wrap emphasis in <code>*asterisks*</code> for the red brush) + icon cards. Each card = title, subtitle, and an SVG icon path.</div>
                    <div class="rg-edit-section">
                        <label class="form-label">Eyebrow</label>
                        <input type="text" class="form-control" data-field="eyebrow" value="${escapeHtml(p.eyebrow || '')}">
                        <label class="form-label mt-2">Heading (use <code>*word*</code> for the red brush accent)</label>
                        <input type="text" class="form-control" data-field="heading" value="${escapeHtml(p.heading || '')}">
                        <label class="form-label mt-2">Subhead</label>
                        <textarea class="form-control" rows="2" data-field="subhead">${escapeHtml(p.subhead || '')}</textarea>
                    </div>
                    ${homeArraySectionsHtml(block.type, p)}`;
            case 'partner_steps':
                return `
                    <div class="alert alert-info" style="font-size:12px;"><i class="bx bx-list-ol me-1"></i><strong>How It Works</strong> — centered heading (<code>*word*</code> = red brush) + numbered step cards (number, circle color, title, body).</div>
                    <div class="rg-edit-section">
                        <label class="form-label">Eyebrow</label>
                        <input type="text" class="form-control" data-field="eyebrow" value="${escapeHtml(p.eyebrow || '')}">
                        <label class="form-label mt-2">Heading (use <code>*word*</code> for the red brush accent)</label>
                        <input type="text" class="form-control" data-field="heading" value="${escapeHtml(p.heading || '')}">
                    </div>
                    ${homeArraySectionsHtml(block.type, p)}`;
            case 'partner_badge':
                return `
                    <div class="alert alert-info" style="font-size:12px;"><i class="bx bx-badge-check me-1"></i><strong>Verified Badge</strong> — image + heading (<code>*word*</code> = red brush) + body + a checklist. Choose which side the image sits on.</div>
                    <div class="rg-edit-section">
                        <div class="row">
                            <div class="col-md-8 mb-2"><label class="form-label">Eyebrow</label>
                                <input type="text" class="form-control" data-field="eyebrow" value="${escapeHtml(p.eyebrow || '')}"></div>
                            <div class="col-md-4 mb-2"><label class="form-label">Eyebrow color</label>
                                <select class="form-select" data-field="eyebrow_color">
                                    <option value="brand" ${p.eyebrow_color === 'brand' ? 'selected' : ''}>Brand</option>
                                    <option value="amber" ${(!p.eyebrow_color || p.eyebrow_color === 'amber') ? 'selected' : ''}>Amber</option>
                                    <option value="emerald" ${p.eyebrow_color === 'emerald' ? 'selected' : ''}>Emerald</option>
                                    <option value="rose" ${p.eyebrow_color === 'rose' ? 'selected' : ''}>Rose</option>
                                    <option value="violet" ${p.eyebrow_color === 'violet' ? 'selected' : ''}>Violet</option>
                                    <option value="teal" ${p.eyebrow_color === 'teal' ? 'selected' : ''}>Teal</option>
                                </select></div>
                        </div>
                        <label class="form-label mt-1">Heading (use <code>*word*</code> for the red brush accent)</label>
                        <input type="text" class="form-control" data-field="heading" value="${escapeHtml(p.heading || '')}">
                        <label class="form-label mt-2">Body</label>
                        <textarea class="form-control" rows="2" data-field="body">${escapeHtml(p.body || '')}</textarea>
                    </div>
                    <div class="rg-edit-section">
                        <div class="row">
                            <div class="col-md-8 mb-2"><label class="form-label">Image</label>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <button type="button" class="rg-pick-btn ${p.image ? '' : 'is-empty'}" data-rg-pick="image"><i class="bx bx-image-add"></i> ${p.image ? 'Change image' : 'Choose image'}</button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-rg-clear="image" ${p.image ? '' : 'style="display:none"'}><i class="bx bx-x"></i></button>
                                </div>
                                <input type="hidden" data-field="image" value="${escapeHtml(p.image || '')}">
                                <img class="rg-image-preview" data-rg-preview="image" src="${p.image ? escapeHtml(mediaUrl(p.image)) : ''}" ${p.image ? '' : 'style="display:none"'}></div>
                            <div class="col-md-4 mb-2"><label class="form-label">Image side</label>
                                <select class="form-select" data-field="image_side">
                                    <option value="left" ${(p.image_side || 'left') === 'left' ? 'selected' : ''}>Left</option>
                                    <option value="right" ${p.image_side === 'right' ? 'selected' : ''}>Right</option>
                                </select></div>
                        </div>
                        <label class="form-label">Image alt text</label>
                        <input type="text" class="form-control" data-field="image_alt" value="${escapeHtml(p.image_alt || '')}">
                        <label class="form-label mt-2">Checklist points (one per line)</label>
                        <textarea class="form-control" rows="3" data-field="points">${escapeHtml(Array.isArray(p.points) ? p.points.join('\n') : (p.points || ''))}</textarea>
                    </div>`;
            case 'partner_perks':
                return `
                    <div class="alert alert-info" style="font-size:12px;"><i class="bx bx-gift me-1"></i><strong>Perks Grid</strong> — centered heading (<code>*word*</code> = red brush) + perk cards (icon, title, body).</div>
                    <div class="rg-edit-section">
                        <label class="form-label">Eyebrow</label>
                        <input type="text" class="form-control" data-field="eyebrow" value="${escapeHtml(p.eyebrow || '')}">
                        <label class="form-label mt-2">Heading (use <code>*word*</code> for the red brush accent)</label>
                        <input type="text" class="form-control" data-field="heading" value="${escapeHtml(p.heading || '')}">
                        <label class="form-label mt-2">Subhead (optional)</label>
                        <textarea class="form-control" rows="2" data-field="subhead">${escapeHtml(p.subhead || '')}</textarea>
                    </div>
                    ${homeArraySectionsHtml(block.type, p)}`;

            default:
                return '<p>Unknown block type.</p>';
        }
    }

    // ── Row builders for the custom-element editors. Returning HTML strings
    //    so they slot into the editorForm template directly. Each row gets a
    //    data-idx and a Remove button; readEditor() walks the live DOM so
    //    re-ordering / removing rows just works.

    function heroRowHtml(slide) {
        slide = slide || {};
        const hasImg = !!slide.src;
        return `<div class="rg-edit-card" data-rg-row="hero">
            <div class="rg-edit-card-hdr">
                <span class="rg-edit-card-num"></span>
                <div class="rg-edit-card-title"><i class="bx bx-slideshow"></i> Slide</div>
                <div class="rg-edit-card-actions">
                    <button type="button" class="btn btn-sm btn-outline-danger" data-rg-row-remove title="Remove slide">&times;</button>
                </div>
            </div>
            <div class="rg-edit-card-body with-thumb">
                <div class="rg-edit-thumb-wrap">
                    <img data-rg-slide-thumb class="rg-edit-thumb ${hasImg ? '' : 'is-empty'}" src="${hasImg ? escapeHtml(mediaUrl(slide.src)) : ''}" alt="">
                    <button type="button" class="rg-edit-thumb-pick ${hasImg ? '' : 'is-empty'}" data-rg-slide-pick>
                        <i class="bx bx-image-add"></i> ${hasImg ? 'Change image' : 'Choose image'}
                    </button>
                    <input type="hidden" data-rg-slide-src value="${escapeHtml(slide.src || '')}">
                </div>
                <div class="rg-edit-card-fields">
                    <div>
                        <label>Alt text (for accessibility + SEO)</label>
                        <input type="text" class="form-control" data-rg-slide-alt value="${escapeHtml(slide.alt || '')}" placeholder="Describe what's in the photo">
                    </div>
                    <div>
                        <label>Title text (SEO, optional)</label>
                        <input type="text" class="form-control" data-rg-slide-title value="${escapeHtml(slide.title || '')}" placeholder="Tooltip / title attribute">
                    </div>
                    <div class="row-2col">
                        <div>
                            <label>Caption title (bold, on top of figcaption)</label>
                            <input type="text" class="form-control" data-rg-slide-caption-title value="${escapeHtml(slide.caption_title || '')}" placeholder="The Mall of Asia Globe">
                        </div>
                        <div>
                            <label>Caption text (description or credit label)</label>
                            <input type="text" class="form-control" data-rg-slide-caption value="${escapeHtml(slide.caption || '')}" placeholder="The 12-metre globe at the entrance">
                        </div>
                    </div>
                    <div>
                        <label>Credit URL (optional — wraps the caption text as a nofollow link)</label>
                        <input type="text" class="form-control" data-rg-slide-credit-url value="${escapeHtml(slide.credit_url || '')}" placeholder="https://commons.wikimedia.org/...">
                    </div>
                </div>
            </div>
        </div>`;
    }

    function quickFactRowHtml(card, _i, rowTag) {
        // rowTag lets facts_list reuse the same row layout under a
        // different `data-rg-row` value so its wire/read handlers find
        // the right rows without colliding with quick_facts.
        card = card || {};
        const tag = rowTag || 'quickfacts';
        const qfIcons = ['money','clock','warning','traffic','calendar','location','food','star','info'];
        const qfColors = ['blue','emerald','rose','amber','violet','slate'];
        return `<div class="rg-edit-card" data-rg-row="${tag}">
            <div class="rg-edit-card-hdr">
                <span class="rg-edit-card-num"></span>
                <div class="rg-edit-card-title"><i class="bx bx-info-square"></i> Card</div>
                <div class="rg-edit-card-actions">
                    <button type="button" class="btn btn-sm btn-outline-danger" data-rg-row-remove title="Remove card">&times;</button>
                </div>
            </div>
            <div class="rg-edit-card-body">
                <div class="rg-edit-card-fields">
                    <div class="row-2col">
                        <div>
                            <label>Icon</label>
                            ${renderIconPicker(qfIcons, card.icon || 'info', 'data-rg-qf-icon')}
                        </div>
                        <div>
                            <label>Color</label>
                            ${renderColorPicker(qfColors, card.color || 'blue', 'data-rg-qf-color')}
                        </div>
                    </div>
                    <div class="row-2col">
                        <div>
                            <label>Big text (top headline)</label>
                            <input type="text" class="form-control" data-rg-qf-big value="${escapeHtml(card.big || '')}" placeholder="Heavy weekends">
                        </div>
                        <div>
                            <label>Label (small caps under big text)</label>
                            <input type="text" class="form-control" data-rg-qf-label value="${escapeHtml(card.label || '')}" placeholder="Traffic">
                        </div>
                    </div>
                    <div>
                        <label>Detail (one line under the label)</label>
                        <input type="text" class="form-control" data-rg-qf-detail value="${escapeHtml(card.detail || '')}" placeholder="Aguinaldo Hwy jams Fri-Sun">
                    </div>
                </div>
            </div>
        </div>`;
    }

    function criterionRowHtml(c) {
        c = c || {};
        const sc = parseFloat(c.score || 0) || 0;
        return `<div class="rg-edit-card" data-rg-row="rating">
            <div class="rg-edit-card-hdr">
                <span class="rg-edit-card-num"></span>
                <div class="rg-edit-card-title"><i class="bx bx-star"></i> Criterion</div>
                <div class="rg-edit-card-actions">
                    <button type="button" class="btn btn-sm btn-outline-danger" data-rg-row-remove title="Remove criterion">&times;</button>
                </div>
            </div>
            <div class="rg-edit-card-body">
                <div class="rg-edit-card-fields">
                    <div class="row-2col" style="grid-template-columns: 1fr 240px;">
                        <div>
                            <label>Name (what you're rating)</label>
                            <input type="text" class="form-control" data-rg-rate-name value="${escapeHtml(c.name || '')}" placeholder="e.g. Food, Service, Value">
                        </div>
                        <div>
                            <label>Score (<span data-rg-rate-display>${sc.toFixed(1)}</span> / 5.0)</label>
                            <input type="range" class="form-range" data-rg-rate-score min="0" max="5" step="0.1" value="${sc}" oninput="this.closest('.rg-edit-card-fields').querySelector('[data-rg-rate-display]').textContent = parseFloat(this.value).toFixed(1)">
                        </div>
                    </div>
                </div>
            </div>
        </div>`;
    }

    function attractionRowHtml(it) {
        it = it || {};
        // Backward-compat: legacy items stored a single `image`. Promote
        // it into images[] so the new multi-image grid handles both shapes
        // uniformly.
        const images = Array.isArray(it.images) && it.images.length
            ? it.images
            : (it.image ? [it.image] : []);
        const thumbsHtml = images.map(src => attractionImageThumbHtml(src)).join('');
        return `<div class="rg-edit-card" data-rg-row="attractions">
            <div class="rg-edit-card-hdr">
                <span class="rg-edit-card-num"></span>
                <div class="rg-edit-card-title"><i class="bx bx-map-pin"></i> Attraction</div>
                <div class="rg-edit-card-actions">
                    <button type="button" class="btn btn-sm btn-outline-danger" data-rg-row-remove title="Remove attraction">&times;</button>
                </div>
            </div>
            <div class="rg-edit-card-body">
                <div class="rg-attr-images" data-rg-attr-images>
                    <label class="form-label d-flex align-items-center justify-content-between" style="margin-bottom:6px">
                        <span><i class="bx bx-images"></i> Images <span class="text-muted" style="font-size:11px;font-weight:400">(fade in/out on the public card; click any thumbnail in the grid to open the lightbox)</span></span>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-rg-attr-add-image>
                            <i class="bx bx-image-add"></i> Add image
                        </button>
                    </label>
                    <div class="rg-attr-image-grid" data-rg-attr-image-grid>
                        ${thumbsHtml || '<div class="rg-attr-image-empty">No images yet. Click <strong>Add image</strong> to pick from the media library.</div>'}
                    </div>
                </div>
                <div class="rg-edit-card-fields mt-3">
                    <div class="row-2col">
                        <div>
                            <label>Name</label>
                            <input type="text" class="form-control" data-rg-attr-name value="${escapeHtml(it.name || '')}" placeholder="e.g. MOA Eye Ferris Wheel">
                        </div>
                        <div>
                            <label>Eyebrow (short tag)</label>
                            <input type="text" class="form-control" data-rg-attr-short value="${escapeHtml(it.short || '')}" placeholder="55m Ferris wheel, sunset best">
                        </div>
                    </div>
                    <div>
                        <label>Blurb (1-2 sentences)</label>
                        <textarea class="form-control" data-rg-attr-blurb rows="2" placeholder="What it is and why a visitor would go.">${escapeHtml(it.blurb || '')}</textarea>
                    </div>
                    <div>
                        <label>Outbound URL (optional — opens in new tab with rel=nofollow)</label>
                        <input type="text" class="form-control" data-rg-attr-url value="${escapeHtml(it.url || '')}" placeholder="https://...">
                    </div>
                </div>
            </div>
        </div>`;
    }

    function attractionImageThumbHtml(src) {
        return `<div class="rg-attr-image-thumb" data-rg-attr-image-thumb data-src="${escapeHtml(src)}">
            <img src="${escapeHtml(mediaUrl(src))}" alt="" loading="lazy">
            <button type="button" class="rg-attr-image-thumb-remove" data-rg-attr-image-remove title="Remove image">&times;</button>
        </div>`;
    }

    function methodRowHtml(m) {
        m = m || {};
        const htgtIcons = ['car','bus','jeepney','tricycle','plane','train','boat'];
        const htgtColors = ['amber','blue','emerald','rose','violet','slate'];
        const colorOpts = htgtColors
            .map(c => `<option value="${c}" ${(m.color||'amber')===c?'selected':''}>${c}</option>`).join('');
        return `<div class="rg-edit-card" data-rg-row="howto">
            <div class="rg-edit-card-hdr">
                <span class="rg-edit-card-num"></span>
                <div class="rg-edit-card-title"><i class="bx bx-bus"></i> Transport method</div>
                <div class="rg-edit-card-actions">
                    <button type="button" class="btn btn-sm btn-outline-danger" data-rg-row-remove title="Remove method">&times;</button>
                </div>
            </div>
            <div class="rg-edit-card-body">
                <div class="rg-edit-card-fields">
                    <div class="row-2col" style="grid-template-columns:1fr 160px;gap:12px">
                        <div>
                            <label>Title (uppercase eyebrow on the card)</label>
                            <input type="text" class="form-control" data-rg-htgt-title value="${escapeHtml(m.title || '')}" placeholder="e.g. Ride-hailing">
                        </div>
                        <div>
                            <label>Icon accent color</label>
                            <select class="form-select" data-rg-htgt-color>${colorOpts}</select>
                        </div>
                    </div>
                    <div>
                        <label>Icon (shown in the gradient square)</label>
                        ${renderIconPicker(htgtIcons, m.icon || 'car', 'data-rg-htgt-icon')}
                    </div>
                    <div>
                        <label>Subtitle (bold second line)</label>
                        <input type="text" class="form-control" data-rg-htgt-subtitle value="${escapeHtml(m.subtitle || '')}" placeholder="e.g. Grab or Joyride">
                    </div>
                    <div>
                        <label>Detail paragraph</label>
                        <textarea class="form-control" data-rg-htgt-detail rows="3" placeholder="Drop-off at the main entrance. Surge pricing kicks in after 5 PM on weekends.">${escapeHtml(m.detail || '')}</textarea>
                    </div>
                </div>
            </div>
        </div>`;
    }

    // ── Visual selectors (icon picker + color swatches) ───────────────
    // Replaces <select> with button groups so admins click an actual icon
    // or color, not a slug. Each row stores the selected key in a hidden
    // input the readEditor walks at save time.

    const ICON_SVG = {
        // quick_facts icon set
        money:    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M9 8.5h4.5a2.25 2.25 0 0 1 0 4.5H9m0-4.5v8m0-3.5h5"/></svg>',
        clock:    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/></svg>',
        warning:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4m0 3.5h.01M3.86 17.74 10.4 4.95a1.8 1.8 0 0 1 3.2 0l6.54 12.79c.66 1.3-.27 2.86-1.6 2.86H5.46c-1.33 0-2.26-1.56-1.6-2.86Z"/></svg>',
        traffic:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="3" width="6" height="18" rx="2"/><circle cx="12" cy="7.5" r="1.2"/><circle cx="12" cy="12" r="1.2"/><circle cx="12" cy="16.5" r="1.2"/></svg>',
        calendar: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 9h18M8 3v4M16 3v4"/></svg>',
        location: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="10" r="3"/><path d="M12 2a8 8 0 0 1 8 8c0 5-8 12-8 12S4 15 4 10a8 8 0 0 1 8-8z"/></svg>',
        food:     '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3v18M16 3v18M5 3v6a3 3 0 0 0 3 3M19 3v6a3 3 0 0 1-3 3"/></svg>',
        star:     '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l2.4 6.6L21 9.7l-5 4.6L17.4 21 12 17.6 6.6 21 8 14.3 3 9.7l6.6-1.1z"/></svg>',
        info:     '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v5m0 3h.01"/></svg>',
        // how_to_get_to transport set
        car:      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17h2m14 0h2M5 17V9l1.5-3.5h11L19 9v8M5 17h14M7 13h2m6 0h2"/><circle cx="7.5" cy="17.5" r="1.5"/><circle cx="16.5" cy="17.5" r="1.5"/></svg>',
        bus:      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="13" rx="2"/><circle cx="8" cy="19" r="1.5"/><circle cx="16" cy="19" r="1.5"/><path d="M4 11h16M8 8h8"/></svg>',
        jeepney:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 16h18M4 16V9l3-3h10l3 3v7"/><circle cx="7" cy="18" r="1.5"/><circle cx="17" cy="18" r="1.5"/><path d="M7 12h10"/></svg>',
        tricycle: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="17" r="2"/><circle cx="17" cy="17" r="2"/><path d="M6 17l3-9h6l2 9M9 8l-1-3"/></svg>',
        plane:    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12l-9 5-9-5 9-9z"/><path d="M12 7v10"/></svg>',
        train:    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="3" width="14" height="14" rx="2"/><path d="M5 11h14M8 7h8"/><circle cx="9" cy="14" r="1"/><circle cx="15" cy="14" r="1"/><path d="M7 21l3-4M14 17l3 4"/></svg>',
        boat:     '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 14l9-9 9 9-3 6H6z"/><path d="M3 18c2 1 4 1 6 0s4-1 6 0 4 1 6 0"/></svg>',
    };

    // ── Wide perk icon set (shared 1:1 with BlockRenderer::homePartnerPerks
    //    so the editor preview matches the rendered output exactly). Each
    //    value is the INNER svg markup; the <svg> wrapper is added below and
    //    on the frontend. A perk's `icon` may instead hold an image URL —
    //    isImageVal() distinguishes the two. Keep keys + paths in sync with
    //    the PHP $icons map in homePartnerPerks().
    const PERK_ICONS = {
        star: '<path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01z"/>',
        check: '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4 12 14.01l-3-3"/>',
        badgecheck: '<path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"/><path d="m9 12 2 2 4-4"/>',
        trophy: '<path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/>',
        award: '<circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/>',
        shield: '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/>',
        trend: '<path d="M3 17l6-6 4 4 8-8"/><path d="M21 7v6h-6"/>',
        target: '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>',
        rocket: '<path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"/><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/>',
        zap: '<path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/>',
        heart: '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>',
        thumbsup: '<path d="M7 10v12"/><path d="M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2a3.13 3.13 0 0 1 3 3.88Z"/>',
        gift: '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5A4.8 8 0 0 1 12 8a4.8 8 0 0 1 4.5-5 2.5 2.5 0 0 1 0 5"/>',
        tag: '<path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r="1.4"/>',
        percent: '<line x1="19" x2="5" y1="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/>',
        money: '<line x1="12" x2="12" y1="2" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
        globe: '<circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/>',
        pin: '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
        users: '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        briefcase: '<path d="M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/><rect width="20" height="14" x="2" y="6" rx="2"/>',
        search: '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        sparkles: '<path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/>',
        key: '<path d="m15.5 7.5 2.3 2.3a1 1 0 0 0 1.4 0l2.1-2.1a1 1 0 0 0 0-1.4L19 4"/><path d="m21 2-9.6 9.6"/><circle cx="7.5" cy="15.5" r="5.5"/>',
        eye: '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>',
    };
    const PERK_ICON_KEYS = Object.keys(PERK_ICONS);

    // True when an icon value is actually an image (upload/media URL) rather
    // than one of the named PERK_ICONS keys.
    function isImageVal(s) {
        s = String(s || '');
        return /^https?:\/\//i.test(s) || s.charAt(0) === '/' || /\.(jpe?g|png|webp|gif|avif|svg)(\?|#|$)/i.test(s) || s.indexOf('/storage/') !== -1;
    }

    function perkIconSvg(key) {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">' + (PERK_ICONS[key] || PERK_ICONS.star) + '</svg>';
    }

    /**
     * Render a button-group icon picker. options = ['money','clock',...].
     * dataAttr = unique data-* selector for readEditor to find this group
     * inside a row (e.g. 'data-rg-qf-icon'). current = currently selected key.
     */
    function renderIconPicker(options, current, dataAttr) {
        const buttons = options.map(key => {
            const isActive = key === current ? 'is-active' : '';
            const svg = ICON_SVG[key] || ICON_SVG.info;
            return `<button type="button" class="rg-icon-btn ${isActive}" data-icon-value="${key}" title="${key}">${svg}</button>`;
        }).join('');
        return `<div class="rg-icon-picker" ${dataAttr}="${current || options[0]}">${buttons}</div>`;
    }

    /**
     * Render a color swatch picker. Same contract as renderIconPicker.
     */
    function renderColorPicker(options, current, dataAttr) {
        const buttons = options.map(key => {
            const isActive = key === current ? 'is-active' : '';
            return `<button type="button" class="rg-color-swatch ${isActive}" data-color="${key}" data-color-value="${key}" title="${key}"></button>`;
        }).join('');
        return `<div class="rg-color-picker" ${dataAttr}="${current || options[0]}">${buttons}</div>`;
    }

    /**
     * Wire icon + color picker button clicks: toggle is-active, sync the
     * parent container's data-* value attribute. ReadEditor walks
     * `[data-rg-*-icon]` / `[data-rg-*-color]` and reads via
     * getAttribute(matching data attr name).
     */
    function wireVisualPickers(scope) {
        (scope || document).querySelectorAll('.rg-icon-picker, .rg-color-picker').forEach(picker => {
            picker.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-icon-value], [data-color-value]');
                if (!btn) return;
                picker.querySelectorAll('.is-active').forEach(b => b.classList.remove('is-active'));
                btn.classList.add('is-active');
                const value = btn.dataset.iconValue || btn.dataset.colorValue;
                // Walk picker's attributes to find the data-rg-* hook for
                // readEditor (e.g. data-rg-qf-icon, data-rg-htgt-icon).
                for (const attr of picker.attributes) {
                    if (attr.name.startsWith('data-rg-')) {
                        picker.setAttribute(attr.name, value);
                    }
                }
            });
        });
    }

    /**
     * Tabbed Visual / Code editor used by BOTH rich_text and custom_html.
     * Same UX: Visual = Quill WYSIWYG, Code = raw HTML textarea. The
     * active tab decides what the save reads, so admins can edit either
     * way and Save grabs the right source. Tab switches sync content
     * across panes so neither view goes stale.
     *
     * defaultTab: 'visual' for rich_text, 'code' for custom_html.
     * blockType is included as a data attr so readEditor can target
     * the correct editor instance.
     */
    function htmlTabbedEditor(html, defaultTab, blockType) {
        const tabVisualActive = defaultTab === 'visual';
        const tabCodeActive   = defaultTab === 'code';
        return `
            <ul class="nav nav-tabs mb-3" data-rg-html-tabs role="tablist">
                <li class="nav-item">
                    <button type="button" class="nav-link ${tabVisualActive ? 'active' : ''}" data-html-tab="visual">
                        <i class="bx bx-edit-alt me-1"></i>Visual
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link ${tabCodeActive ? 'active' : ''}" data-html-tab="code">
                        <i class="bx bx-code-alt me-1"></i>Code (HTML)
                    </button>
                </li>
            </ul>
            <div data-html-pane="visual" data-rg-block-type="${escapeHtml(blockType)}" ${tabVisualActive ? '' : 'hidden'}>
                <div id="rgQuillEditor" style="background:white;min-height:280px">${html}</div>
                <div class="form-help mt-1"><i class="bx bx-info-circle"></i> Visual mode runs through Quill. Custom inline styles or &lt;script&gt; tags get stripped &mdash; use the Code tab for those.</div>
            </div>
            <div data-html-pane="code" ${tabCodeActive ? '' : 'hidden'}>
                <textarea class="form-control" rows="14" data-rg-html-code style="font-family:'JetBrains Mono', Menlo, Consolas, monospace; font-size:12px; line-height:1.5">${escapeHtml(html)}</textarea>
                <div class="form-help mt-1"><i class="bx bx-info-circle"></i> Code mode: raw HTML, rendered as-is on the public page. Switch to Visual to use the WYSIWYG toolbar.</div>
            </div>`;
    }

    function paragraphRowHtml(text) {
        text = text || '';
        return `<div class="rg-edit-card" data-rg-row="paragraphs">
            <div class="rg-edit-card-hdr">
                <span class="rg-edit-card-num"></span>
                <div class="rg-edit-card-title"><i class="bx bx-paragraph"></i> Paragraph</div>
                <div class="rg-edit-card-actions">
                    <button type="button" class="btn btn-sm btn-outline-danger" data-rg-row-remove title="Remove paragraph">&times;</button>
                </div>
            </div>
            <div class="rg-edit-card-body">
                <div class="rg-edit-card-fields">
                    <div>
                        <label>Paragraph text — inline HTML allowed (&lt;strong&gt;, &lt;em&gt;, &lt;a href&gt;)</label>
                        <textarea class="form-control" data-rg-para rows="4" placeholder="Write one paragraph. Multiple paragraphs stack as separate cards.">${escapeHtml(text)}</textarea>
                    </div>
                </div>
            </div>
        </div>`;
    }

    function openEditor(block) {
        state.current = JSON.parse(JSON.stringify(block));
        document.getElementById('blockEditorTitle').textContent = (block.id ? 'Edit ' : 'Add ') + (labels[block.type] || block.type);
        document.getElementById('blockEditorBody').innerHTML = editorForm(state.current);
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('blockEditorModal'));
        modal.show();
        wireEditor(state.current);
    }

    function wireEditor(block) {
        // Visual icon + color pickers replace <select> dropdowns. Wire
        // their click handlers up-front for any pickers in the modal —
        // this covers the picker instances inside row builders that get
        // freshly rendered when the modal opens.
        wireVisualPickers(document.getElementById('blockEditorBody'));

        // Home repeatable-row editors (logos / features / panels / slider
        // images / reviews) — delegated add/remove/image-pick handlers.
        wireHomeArrays();

        // Rich-text + custom_html share the tabbed Visual/Code editor.
        // Quill is initialized lazily — the moment the Visual pane is
        // shown (initial or via tab switch). Tab switches sync content
        // both ways so neither view goes stale.
        if (block.type === 'rich_text' || block.type === 'custom_html') {
            const initQuill = () => {
                if (state.quill || !document.getElementById('rgQuillEditor')) return;
                state.quill = new Quill('#rgQuillEditor', {
                    theme: 'snow',
                    modules: { toolbar: [
                        [{header: [2,3,4,false]}], ['bold','italic','underline','strike'],
                        [{list:'ordered'},{list:'bullet'}], ['link','blockquote','image'],
                        [{align: []}], [{color: []},{background: []}], ['clean']
                    ]},
                });
            };

            // Init now if Visual pane is the starting pane.
            const visualPane = document.querySelector('[data-html-pane="visual"]');
            if (visualPane && !visualPane.hidden) initQuill();

            const codeTa = () => document.querySelector('[data-rg-html-code]');
            const visualToCode = () => {
                if (state.quill && codeTa()) codeTa().value = state.quill.root.innerHTML;
            };
            const codeToVisual = () => {
                initQuill();
                if (state.quill && codeTa()) {
                    state.quill.clipboard.dangerouslyPasteHTML(codeTa().value || '');
                }
            };

            document.querySelectorAll('[data-html-tab]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const target = btn.dataset.htmlTab;
                    // Sync FROM the currently-visible pane TO the target.
                    if (target === 'code') visualToCode();
                    else codeToVisual();
                    // Switch active state + visibility.
                    document.querySelectorAll('[data-html-tab]').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    document.querySelectorAll('[data-html-pane]').forEach(pane => {
                        pane.hidden = pane.dataset.htmlPane !== target;
                    });
                });
            });
        }
        if (block.type === 'two_column') {
            state.quillLeft = new Quill('#rgQuillLeft', { theme: 'snow', modules: { toolbar: [['bold','italic','link'],[{list:'bullet'}]] } });
            state.quillRight = new Quill('#rgQuillRight', { theme: 'snow', modules: { toolbar: [['bold','italic','link'],[{list:'bullet'}]] } });
        }
        // Image / video uploads — only legacy file inputs remain here
        // (video block keeps a raw file input; image/gallery/hero/attractions
        // now use the data-rg-pick=* media picker buttons below).
        document.querySelectorAll('[data-upload-target]').forEach(input => {
            input.addEventListener('change', (e) => handleUpload(e, input.dataset.uploadTarget));
        });

        // ── Generic single-image picker (image block) ────────────────
        document.querySelectorAll('[data-rg-pick="src"]').forEach(btn => {
            btn.addEventListener('click', () => {
                openMediaPicker({ onSelect: (media) => {
                    const hidden = document.querySelector('[data-field="src"]');
                    if (hidden) hidden.value = media.url;
                    const preview = document.querySelector('[data-rg-preview="src"]');
                    if (preview) { preview.src = mediaUrl(media.url); preview.style.display = ''; }
                    const clear = document.querySelector('[data-rg-clear="src"]');
                    if (clear) clear.style.display = '';
                    btn.innerHTML = '<i class="bx bx-image-add"></i> Change image';
                    btn.classList.remove('is-empty');
                }});
            });
        });
        document.querySelectorAll('[data-rg-clear="src"]').forEach(btn => {
            btn.addEventListener('click', () => {
                const hidden = document.querySelector('[data-field="src"]');
                if (hidden) hidden.value = '';
                const preview = document.querySelector('[data-rg-preview="src"]');
                if (preview) { preview.removeAttribute('src'); preview.style.display = 'none'; }
                btn.style.display = 'none';
                const pickBtn = document.querySelector('[data-rg-pick="src"]');
                if (pickBtn) {
                    pickBtn.innerHTML = '<i class="bx bx-image-add"></i> Choose image';
                    pickBtn.classList.add('is-empty');
                }
            });
        });

        // ── Generic single-image pickers (home / custom blocks) ──────
        //    Any data-rg-pick="<field>" other than the image block's
        //    "src" drives a hidden data-field + preview + clear by field
        //    name, so new single-image fields (e.g. badge_image) get the
        //    media box / upload with no extra wiring.
        document.querySelectorAll('[data-rg-pick]').forEach(btn => {
            const field = btn.getAttribute('data-rg-pick');
            if (field === 'src') return;
            btn.addEventListener('click', () => {
                openMediaPicker({ onSelect: (media) => {
                    const hidden = document.querySelector('[data-field="' + field + '"]');
                    if (hidden) hidden.value = media.url;
                    const preview = document.querySelector('[data-rg-preview="' + field + '"]');
                    if (preview) { preview.src = mediaUrl(media.url); preview.style.display = ''; }
                    const clear = document.querySelector('[data-rg-clear="' + field + '"]');
                    if (clear) clear.style.display = '';
                    btn.innerHTML = '<i class="bx bx-image-add"></i> Change image';
                    btn.classList.remove('is-empty');
                }});
            });
        });
        document.querySelectorAll('[data-rg-clear]').forEach(btn => {
            const field = btn.getAttribute('data-rg-clear');
            if (field === 'src') return;
            btn.addEventListener('click', () => {
                const hidden = document.querySelector('[data-field="' + field + '"]');
                if (hidden) hidden.value = '';
                const preview = document.querySelector('[data-rg-preview="' + field + '"]');
                if (preview) { preview.removeAttribute('src'); preview.style.display = 'none'; }
                btn.style.display = 'none';
                const pickBtn = document.querySelector('[data-rg-pick="' + field + '"]');
                if (pickBtn) { pickBtn.innerHTML = '<i class="bx bx-image-add"></i> Choose image'; pickBtn.classList.add('is-empty'); }
            });
        });

        // ── Gallery: each pick appends a row ─────────────────────────
        if (block.type === 'gallery') {
            const addBtn = document.getElementById('rgGalleryAddPick');
            if (addBtn) {
                addBtn.addEventListener('click', () => {
                    openMediaPicker({ onSelect: (media) => {
                        const list = document.getElementById('rgGalleryList');
                        const row = document.createElement('div');
                        row.className = 'rg-gallery-row';
                        row.dataset.src = media.url;
                        row.innerHTML =
                            '<img src="' + escapeHtml(mediaUrl(media.url)) + '" style="width:80px;height:60px;object-fit:cover;border-radius:3px">' +
                            '<div class="d-flex flex-column gap-1 flex-grow-1">' +
                            '<input type="text" class="form-control form-control-sm" placeholder="Alt text (SEO)" data-gallery-alt>' +
                            '<input type="text" class="form-control form-control-sm" placeholder="Title text (SEO, optional)" data-gallery-title>' +
                            '</div>' +
                            '<button type="button" class="btn btn-sm btn-outline-danger" data-gallery-remove>&times;</button>';
                        list.appendChild(row);
                    }});
                });
            }
        }

        // ── Hero slider: per-slide pick + top-level "Add slide" pick ──
        if (block.type === 'hero_slider') {
            const list = document.getElementById('rgHeroList');
            // Per-row picker click — delegated.
            list?.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-rg-slide-pick]');
                if (!btn) return;
                const row = btn.closest('[data-rg-row]');
                openMediaPicker({ onSelect: (media) => {
                    const hidden = row.querySelector('[data-rg-slide-src]');
                    if (hidden) hidden.setAttribute('value', media.url);
                    const thumb = row.querySelector('[data-rg-slide-thumb]');
                    if (thumb) { thumb.src = mediaUrl(media.url); thumb.style.opacity = '1'; }
                    btn.innerHTML = '<i class="bx bx-image-add"></i> Change';
                    btn.classList.remove('is-empty');
                }});
            });
            // Top-level "add slide" picker — appends a new row populated.
            const addPick = document.getElementById('rgHeroAddPick');
            addPick?.addEventListener('click', () => {
                openMediaPicker({ onSelect: (media) => {
                    list.insertAdjacentHTML('beforeend',
                        heroRowHtml({ src: media.url, alt: '', caption: '' }));
                }});
            });
        }

        // ── Attractions: per-item multi-image picker ─────────────────
        // Each attraction row holds a grid of image thumbnails. The Add
        // button appends one image at a time (so the admin can keep
        // picking sequentially); per-thumb remove buttons drop from the
        // grid. The "No images yet" placeholder auto-vanishes once any
        // thumb is added and re-appears once the last is removed.
        if (block.type === 'attractions') {
            const list = document.getElementById('rgAttrList');
            function refreshEmptyPlaceholder(grid) {
                const hasThumbs = grid.querySelector('[data-rg-attr-image-thumb]');
                const empty = grid.querySelector('.rg-attr-image-empty');
                if (hasThumbs && empty) empty.remove();
                else if (!hasThumbs && !empty) {
                    grid.insertAdjacentHTML('beforeend', '<div class="rg-attr-image-empty">No images yet. Click <strong>Add image</strong> to pick from the media library.</div>');
                }
            }
            list?.addEventListener('click', (e) => {
                const addBtn = e.target.closest('[data-rg-attr-add-image]');
                if (addBtn) {
                    const row = addBtn.closest('[data-rg-row]');
                    const grid = row.querySelector('[data-rg-attr-image-grid]');
                    openMediaPicker({ onSelect: (media) => {
                        grid.insertAdjacentHTML('beforeend', attractionImageThumbHtml(media.url));
                        refreshEmptyPlaceholder(grid);
                    }});
                    return;
                }
                const removeBtn = e.target.closest('[data-rg-attr-image-remove]');
                if (removeBtn) {
                    const thumb = removeBtn.closest('[data-rg-attr-image-thumb]');
                    const grid = thumb?.parentElement;
                    thumb?.remove();
                    if (grid) refreshEmptyPlaceholder(grid);
                }
            });
        }
        // FAQ + Gallery dynamic
        if (block.type === 'faq') {
            document.getElementById('rgFaqAdd').addEventListener('click', () => addFaqRow());
            document.getElementById('rgFaqList').addEventListener('click', (e) => { if (e.target.dataset.faqRemove !== undefined) e.target.closest('.rg-faq-row').remove(); });
        }
        if (block.type === 'gallery') {
            document.getElementById('rgGalleryList').addEventListener('click', (e) => { if (e.target.dataset.galleryRemove !== undefined) { e.target.closest('.rg-gallery-row').remove(); } });
        }

        // ── Custom-element row wiring. Each one binds:
        //    - Add-row button (appends a fresh row via *RowHtml builder)
        //    - Remove-row button (delegated click on the list)
        //    - Per-row file upload (delegated change on the list)

        if (block.type === 'hero_slider') {
            const list = document.getElementById('rgHeroList');
            document.getElementById('rgHeroAdd').addEventListener('click', () => {
                list.insertAdjacentHTML('beforeend', heroRowHtml({}));
            });
            list.addEventListener('click', (e) => {
                if (e.target.closest('[data-rg-row-remove]')) e.target.closest('[data-rg-row]').remove();
            });
            // Per-row image change + top-level "add slide" picker are wired
            // further down in the picker-button handlers section.
        }

        if (block.type === 'quick_facts') {
            const list = document.getElementById('rgQfList');
            document.getElementById('rgQfAdd').addEventListener('click', () => {
                list.insertAdjacentHTML('beforeend', quickFactRowHtml({}));
                // Re-bind icon/color picker clicks on the new row.
                wireVisualPickers(list.lastElementChild);
            });
            list.addEventListener('click', (e) => {
                if (e.target.closest('[data-rg-row-remove]')) e.target.closest('[data-rg-row]').remove();
            });
        }

        if (block.type === 'facts_list') {
            const list = document.getElementById('rgFlList');
            const addBtn = document.getElementById('rgFlAdd');
            if (addBtn && list) {
                addBtn.addEventListener('click', () => {
                    list.insertAdjacentHTML('beforeend', quickFactRowHtml({}, 0, 'factslist'));
                    wireVisualPickers(list.lastElementChild);
                });
                list.addEventListener('click', (e) => {
                    if (e.target.closest('[data-rg-row-remove]')) e.target.closest('[data-rg-row]').remove();
                });
            }
        }

        if (block.type === 'editor_rating') {
            const list = document.getElementById('rgRateList');
            document.getElementById('rgRateAdd').addEventListener('click', () => {
                list.insertAdjacentHTML('beforeend', criterionRowHtml({}));
            });
            list.addEventListener('click', (e) => {
                if (e.target.closest('[data-rg-row-remove]')) e.target.closest('[data-rg-row]').remove();
            });
        }

        if (block.type === 'attractions') {
            const list = document.getElementById('rgAttrList');
            document.getElementById('rgAttrAdd').addEventListener('click', () => {
                list.insertAdjacentHTML('beforeend', attractionRowHtml({}));
            });
            list.addEventListener('click', (e) => {
                if (e.target.closest('[data-rg-row-remove]')) e.target.closest('[data-rg-row]').remove();
            });
            // Per-row image change is wired in the picker-button handlers section.
        }

        if (block.type === 'how_to_get_to') {
            const list = document.getElementById('rgHtgtList');
            document.getElementById('rgHtgtAdd').addEventListener('click', () => {
                list.insertAdjacentHTML('beforeend', methodRowHtml({}));
                wireVisualPickers(list.lastElementChild);
            });
            list.addEventListener('click', (e) => {
                if (e.target.closest('[data-rg-row-remove]')) e.target.closest('[data-rg-row]').remove();
            });
        }

        // text_section: the new editor is a single textarea bound via
        // data-field="body", which the generic readEditor loop already
        // captures. No per-row wiring needed.

        // ── New standardized templates ───────────────────────────────
        if (block.type === 'image_text_pair') {
            const root = document.getElementById('blockEditorBody');
            root.querySelectorAll('[data-rg-itp-pick]').forEach(btn => {
                btn.addEventListener('click', () => {
                    openMediaPicker({ onSelect: (media) => {
                        const hidden = root.querySelector('[data-field="image"]');
                        if (hidden) hidden.value = media.url;
                        const thumb = root.querySelector('[data-rg-itp-thumb]');
                        if (thumb) { thumb.src = mediaUrl(media.url); thumb.classList.remove('is-empty'); }
                        btn.innerHTML = '<i class="bx bx-image-add"></i> Change image';
                        btn.classList.remove('is-empty');
                    }});
                });
            });
        }

        if (block.type === 'summary_accordion') {
            const list = document.getElementById('rgAccList');
            document.getElementById('rgAccAdd').addEventListener('click', () => {
                list.insertAdjacentHTML('beforeend', `
                    <div class="rg-edit-card" data-rg-row="accordion">
                        <div class="rg-edit-card-hdr">
                            <span class="rg-edit-card-num"></span>
                            <div class="rg-edit-card-title"><i class="bx bx-chevron-down-square"></i> Accordion item</div>
                            <div class="rg-edit-card-actions">
                                <label class="d-flex align-items-center gap-1 mb-0" style="font-size:11px"><input type="checkbox" data-rg-acc-open> Default open</label>
                                <button type="button" class="btn btn-sm btn-outline-danger" data-rg-row-remove>&times;</button>
                            </div>
                        </div>
                        <div class="rg-edit-card-body">
                            <div class="rg-edit-card-fields">
                                <div class="row-2col">
                                    <div><label>Eyebrow</label><input type="text" class="form-control" data-rg-acc-eyebrow></div>
                                    <div><label>Title</label><input type="text" class="form-control" data-rg-acc-title></div>
                                </div>
                                <div><label>Body</label><textarea class="form-control" rows="3" data-rg-acc-body></textarea></div>
                            </div>
                        </div>
                    </div>
                `);
            });
            list.addEventListener('click', (e) => {
                if (e.target.closest('[data-rg-row-remove]')) e.target.closest('[data-rg-row]').remove();
            });
        }

        if (block.type === 'traveler_reviews') {
            const list = document.getElementById('rgTrList');
            document.getElementById('rgTrAdd').addEventListener('click', () => {
                list.insertAdjacentHTML('beforeend', `
                    <div class="rg-edit-card" data-rg-row="reviews">
                        <div class="rg-edit-card-hdr">
                            <span class="rg-edit-card-num"></span>
                            <div class="rg-edit-card-title"><i class="bx bx-user-circle"></i> Review</div>
                            <div class="rg-edit-card-actions">
                                <button type="button" class="btn btn-sm btn-outline-danger" data-rg-row-remove>&times;</button>
                            </div>
                        </div>
                        <div class="rg-edit-card-body with-thumb">
                            <div class="rg-edit-thumb-wrap">
                                <img data-rg-tr-thumb class="rg-edit-thumb is-empty" src="" alt="">
                                <button type="button" class="rg-edit-thumb-pick is-empty" data-rg-tr-pick>
                                    <i class="bx bx-image-add"></i> Choose avatar
                                </button>
                                <input type="hidden" data-rg-tr-avatar value="">
                            </div>
                            <div class="rg-edit-card-fields">
                                <div class="row-2col">
                                    <div><label>Name</label><input type="text" class="form-control" data-rg-tr-name></div>
                                    <div><label>Date</label><input type="text" class="form-control" data-rg-tr-date></div>
                                </div>
                                <div><label>Rating (0-5)</label><input type="number" min="0" max="5" step="1" class="form-control" data-rg-tr-rating value="5"></div>
                                <div><label>Quote</label><textarea class="form-control" rows="2" data-rg-tr-quote></textarea></div>
                            </div>
                        </div>
                    </div>
                `);
            });
            // Delegated remove + avatar picker (works for existing + new rows).
            list.addEventListener('click', (e) => {
                if (e.target.closest('[data-rg-row-remove]')) {
                    e.target.closest('[data-rg-row]').remove();
                    return;
                }
                const pickBtn = e.target.closest('[data-rg-tr-pick]');
                if (!pickBtn) return;
                const row = pickBtn.closest('[data-rg-row]');
                openMediaPicker({ onSelect: (media) => {
                    const hidden = row.querySelector('[data-rg-tr-avatar]');
                    if (hidden) hidden.setAttribute('value', media.url);
                    const thumb = row.querySelector('[data-rg-tr-thumb]');
                    if (thumb) { thumb.src = mediaUrl(media.url); thumb.classList.remove('is-empty'); }
                    pickBtn.innerHTML = '<i class="bx bx-image-add"></i> Change avatar';
                    pickBtn.classList.remove('is-empty');
                }});
            });
        }

        if (block.type === 'tag_pills') {
            const list = document.getElementById('rgPillList');
            document.getElementById('rgPillAdd').addEventListener('click', () => {
                list.insertAdjacentHTML('beforeend', `
                    <div class="rg-edit-card" data-rg-row="pill">
                        <div class="rg-edit-card-hdr">
                            <span class="rg-edit-card-num"></span>
                            <div class="rg-edit-card-title"><i class="bx bx-purchase-tag"></i> Tag pill</div>
                            <div class="rg-edit-card-actions">
                                <button type="button" class="btn btn-sm btn-outline-danger" data-rg-row-remove>&times;</button>
                            </div>
                        </div>
                        <div class="rg-edit-card-body">
                            <div class="rg-edit-card-fields">
                                <div class="row-2col" style="grid-template-columns: 1fr 140px;">
                                    <div><label>Text</label><input type="text" class="form-control" data-rg-pill-text></div>
                                    <div><label>Color</label><select class="form-select" data-rg-pill-color>
                                        <option value="amber">amber</option><option value="rose">rose</option>
                                        <option value="emerald">emerald</option><option value="indigo">indigo</option>
                                        <option value="pink">pink</option><option value="cyan">cyan</option>
                                        <option value="violet">violet</option><option value="slate">slate</option>
                                    </select></div>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
            });
            list.addEventListener('click', (e) => {
                if (e.target.closest('[data-rg-row-remove]')) e.target.closest('[data-rg-row]').remove();
            });
        }

        if (block.type === 'external_guides') {
            const list = document.getElementById('rgGuideList');
            document.getElementById('rgGuideAdd').addEventListener('click', () => {
                list.insertAdjacentHTML('beforeend', `
                    <div class="rg-edit-card" data-rg-row="guide">
                        <div class="rg-edit-card-hdr">
                            <span class="rg-edit-card-num"></span>
                            <div class="rg-edit-card-title"><i class="bx bx-link"></i> External guide</div>
                            <div class="rg-edit-card-actions">
                                <button type="button" class="btn btn-sm btn-outline-danger" data-rg-row-remove>&times;</button>
                            </div>
                        </div>
                        <div class="rg-edit-card-body">
                            <div class="rg-edit-card-fields">
                                <div class="row-2col">
                                    <div><label>Platform name</label><input type="text" class="form-control" data-rg-guide-name></div>
                                    <div><label>Accent color</label><select class="form-select" data-rg-guide-color>
                                        <option value="emerald">emerald</option><option value="blue">blue</option>
                                        <option value="rose">rose</option><option value="amber">amber</option>
                                        <option value="violet">violet</option><option value="slate" selected>slate</option>
                                    </select></div>
                                </div>
                                <div><label>Outbound URL</label><input type="text" class="form-control" data-rg-guide-url></div>
                                <div><label>Short blurb</label><input type="text" class="form-control" data-rg-guide-blurb></div>
                                <div class="row-2col">
                                    <div><label>Logo</label><div class="d-flex gap-2 align-items-center">
                                        <button type="button" class="rg-pick-btn is-empty" data-rg-guide-logo-pick><i class="bx bx-image-add"></i> Pick logo</button>
                                        <input type="hidden" data-rg-guide-logo value=""></div></div>
                                    <div><label>Screenshot</label><div class="d-flex gap-2 align-items-center">
                                        <button type="button" class="rg-pick-btn is-empty" data-rg-guide-shot-pick><i class="bx bx-image-add"></i> Pick screenshot</button>
                                        <input type="hidden" data-rg-guide-shot value=""></div></div>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
            });
            // Delegated remove + media picker clicks (works for existing + new rows).
            list.addEventListener('click', (e) => {
                if (e.target.closest('[data-rg-row-remove]')) {
                    e.target.closest('[data-rg-row]').remove();
                    return;
                }
                const logoBtn = e.target.closest('[data-rg-guide-logo-pick]');
                if (logoBtn) {
                    const row = logoBtn.closest('[data-rg-row]');
                    openMediaPicker({ onSelect: (media) => {
                        const hidden = row.querySelector('[data-rg-guide-logo]');
                        if (hidden) hidden.setAttribute('value', media.url);
                        logoBtn.innerHTML = '<i class="bx bx-image-add"></i> Change';
                        logoBtn.classList.remove('is-empty');
                    }});
                    return;
                }
                const shotBtn = e.target.closest('[data-rg-guide-shot-pick]');
                if (shotBtn) {
                    const row = shotBtn.closest('[data-rg-row]');
                    openMediaPicker({ onSelect: (media) => {
                        const hidden = row.querySelector('[data-rg-guide-shot]');
                        if (hidden) hidden.setAttribute('value', media.url);
                        shotBtn.innerHTML = '<i class="bx bx-image-add"></i> Change';
                        shotBtn.classList.remove('is-empty');
                    }});
                }
            });
        }

        if (block.type === 'author') {
            const root = document.getElementById('blockEditorBody');
            // Avatar picker — sets the hidden field + flips the preview img.
            const avatarBtn = root.querySelector('[data-rg-author-avatar-pick]');
            if (avatarBtn) {
                avatarBtn.addEventListener('click', () => {
                    openMediaPicker({ onSelect: (media) => {
                        const hidden = root.querySelector('[data-field="avatar"]');
                        if (hidden) hidden.value = media.url;
                        const thumb = root.querySelector('[data-rg-author-avatar-thumb]');
                        if (thumb) {
                            thumb.src = mediaUrl(media.url);
                            thumb.style.display = '';
                            thumb.style.width = '48px';
                            thumb.style.height = '48px';
                            thumb.style.borderRadius = '50%';
                            thumb.style.objectFit = 'cover';
                            thumb.style.border = '1px solid #e2e8f0';
                        }
                        avatarBtn.innerHTML = '<i class="bx bx-image-add"></i> Change avatar';
                        avatarBtn.classList.remove('is-empty');
                    }});
                });
            }
            // Social link rows — add / remove from delegated handlers.
            const linkList = document.getElementById('rgAuthorLinkList');
            const linkAdd = document.getElementById('rgAuthorLinkAdd');
            const linkTypes = ['facebook','instagram','twitter','tiktok','youtube','linkedin','email','website'];
            if (linkAdd && linkList) {
                linkAdd.addEventListener('click', () => {
                    const typeOpts = linkTypes.map(t => `<option value="${t}">${t}</option>`).join('');
                    linkList.insertAdjacentHTML('beforeend', `
                        <div class="rg-edit-card" data-rg-row="authorlink" style="background:#fafafa">
                            <div class="rg-edit-card-hdr" style="padding:6px 10px">
                                <div class="rg-edit-card-title" style="font-size:11px"><i class="bx bx-link-external"></i> Social link</div>
                                <div class="rg-edit-card-actions">
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-rg-row-remove>&times;</button>
                                </div>
                            </div>
                            <div class="rg-edit-card-body" style="padding:8px 10px">
                                <div class="row-2col" style="grid-template-columns:140px 1fr;gap:8px">
                                    <div><select class="form-select form-select-sm" data-rg-author-link-type>${typeOpts}</select></div>
                                    <div><input type="text" class="form-control form-control-sm" data-rg-author-link-url value="" placeholder="https://..."></div>
                                </div>
                            </div>
                        </div>
                    `);
                });
                linkList.addEventListener('click', (e) => {
                    if (e.target.closest('[data-rg-row-remove]')) e.target.closest('[data-rg-row]').remove();
                });
            }
        }

        if (block.type === 'nearby_destinations') {
            const list = document.getElementById('rgNbydList');
            const addBtn = document.getElementById('rgNbydAdd');
            function emptyRow() {
                return `<div class="rg-edit-card" data-rg-row="nbyd">
                    <div class="rg-edit-card-hdr">
                        <span class="rg-edit-card-num"></span>
                        <div class="rg-edit-card-title"><i class="bx bx-map-pin"></i> Destination</div>
                        <div class="rg-edit-card-actions">
                            <button type="button" class="btn btn-sm btn-outline-danger" data-rg-row-remove>&times;</button>
                        </div>
                    </div>
                    <div class="rg-edit-card-body with-thumb">
                        <div class="rg-edit-thumb-wrap">
                            <img data-rg-nbyd-thumb class="rg-edit-thumb is-empty" src="" alt="">
                            <button type="button" class="rg-edit-thumb-pick is-empty" data-rg-nbyd-pick>
                                <i class="bx bx-image-add"></i> Choose image
                            </button>
                            <input type="hidden" data-rg-nbyd-image value="">
                        </div>
                        <div class="rg-edit-card-fields">
                            <div class="row-2col">
                                <div><label>Title</label><input type="text" class="form-control" data-rg-nbyd-title></div>
                                <div><label>Eyebrow</label><input type="text" class="form-control" data-rg-nbyd-eyebrow></div>
                            </div>
                            <div><label>URL</label><input type="text" class="form-control" data-rg-nbyd-url></div>
                            <div class="row-2col" style="grid-template-columns:1fr 200px">
                                <div><label>Blurb</label><input type="text" class="form-control" data-rg-nbyd-blurb></div>
                                <div><label>Distance label</label><input type="text" class="form-control" data-rg-nbyd-distance></div>
                            </div>
                        </div>
                    </div>
                </div>`;
            }
            if (addBtn && list) {
                addBtn.addEventListener('click', () => list.insertAdjacentHTML('beforeend', emptyRow()));
                list.addEventListener('click', (e) => {
                    if (e.target.closest('[data-rg-row-remove]')) {
                        e.target.closest('[data-rg-row]').remove();
                        return;
                    }
                    const pick = e.target.closest('[data-rg-nbyd-pick]');
                    if (!pick) return;
                    const row = pick.closest('[data-rg-row]');
                    openMediaPicker({ onSelect: (media) => {
                        const hidden = row.querySelector('[data-rg-nbyd-image]');
                        if (hidden) hidden.setAttribute('value', media.url);
                        const thumb = row.querySelector('[data-rg-nbyd-thumb]');
                        if (thumb) { thumb.src = mediaUrl(media.url); thumb.classList.remove('is-empty'); }
                        pick.innerHTML = '<i class="bx bx-image-add"></i> Change';
                        pick.classList.remove('is-empty');
                    }});
                });
            }
        }

        if (block.type === 'related_blogs') {
            const list = document.getElementById('rgRblogList');
            const addBtn = document.getElementById('rgRblogAdd');
            function emptyRblogRow() {
                return `<div class="rg-edit-card" data-rg-row="rblog">
                    <div class="rg-edit-card-hdr">
                        <span class="rg-edit-card-num"></span>
                        <div class="rg-edit-card-title"><i class="bx bx-news"></i> Blog post</div>
                        <div class="rg-edit-card-actions">
                            <button type="button" class="btn btn-sm btn-outline-danger" data-rg-row-remove>&times;</button>
                        </div>
                    </div>
                    <div class="rg-edit-card-body with-thumb">
                        <div class="rg-edit-thumb-wrap">
                            <img data-rg-rblog-thumb class="rg-edit-thumb is-empty" src="" alt="">
                            <button type="button" class="rg-edit-thumb-pick is-empty" data-rg-rblog-pick>
                                <i class="bx bx-image-add"></i> Choose cover
                            </button>
                            <input type="hidden" data-rg-rblog-cover value="">
                        </div>
                        <div class="rg-edit-card-fields">
                            <div class="row-2col">
                                <div><label>Title</label><input type="text" class="form-control" data-rg-rblog-title></div>
                                <div><label>Eyebrow</label><input type="text" class="form-control" data-rg-rblog-eyebrow></div>
                            </div>
                            <div><label>URL</label><input type="text" class="form-control" data-rg-rblog-url></div>
                            <div><label>Excerpt</label><textarea class="form-control" rows="2" data-rg-rblog-excerpt></textarea></div>
                            <div><label>Meta line</label><input type="text" class="form-control" data-rg-rblog-meta></div>
                        </div>
                    </div>
                </div>`;
            }
            if (addBtn && list) {
                addBtn.addEventListener('click', () => list.insertAdjacentHTML('beforeend', emptyRblogRow()));
                list.addEventListener('click', (e) => {
                    if (e.target.closest('[data-rg-row-remove]')) {
                        e.target.closest('[data-rg-row]').remove();
                        return;
                    }
                    const pick = e.target.closest('[data-rg-rblog-pick]');
                    if (!pick) return;
                    const row = pick.closest('[data-rg-row]');
                    openMediaPicker({ onSelect: (media) => {
                        const hidden = row.querySelector('[data-rg-rblog-cover]');
                        if (hidden) hidden.setAttribute('value', media.url);
                        const thumb = row.querySelector('[data-rg-rblog-thumb]');
                        if (thumb) { thumb.src = mediaUrl(media.url); thumb.classList.remove('is-empty'); }
                        pick.innerHTML = '<i class="bx bx-image-add"></i> Change';
                        pick.classList.remove('is-empty');
                    }});
                });
            }
        }

        if (block.type === 'related_guides') {
            const list = document.getElementById('rgRelList');
            document.getElementById('rgRelAdd').addEventListener('click', () => {
                list.insertAdjacentHTML('beforeend', `
                    <div class="rg-edit-card" data-rg-row="related">
                        <div class="rg-edit-card-hdr">
                            <span class="rg-edit-card-num"></span>
                            <div class="rg-edit-card-title"><i class="bx bx-link-external"></i> Guide link</div>
                            <div class="rg-edit-card-actions">
                                <button type="button" class="btn btn-sm btn-outline-danger" data-rg-row-remove>&times;</button>
                            </div>
                        </div>
                        <div class="rg-edit-card-body">
                            <div class="rg-edit-card-fields">
                                <div class="row-2col">
                                    <div><label>Eyebrow</label><input type="text" class="form-control" data-rg-rel-eyebrow></div>
                                    <div><label>Title</label><input type="text" class="form-control" data-rg-rel-title></div>
                                </div>
                                <div><label>Blurb</label><input type="text" class="form-control" data-rg-rel-blurb></div>
                                <div><label>URL</label><input type="text" class="form-control" data-rg-rel-url></div>
                            </div>
                        </div>
                    </div>
                `);
            });
            list.addEventListener('click', (e) => {
                if (e.target.closest('[data-rg-row-remove]')) e.target.closest('[data-rg-row]').remove();
            });
        }
    }

    /**
     * Promise wrapper around the existing /resort-guru-blocks-upload-media
     * endpoint. Returns the parsed JSON or null on failure.
     */
    function uploadFile(file) {
        const fd = new FormData();
        fd.append('file', file);
        // No _token field — Laravel checks _token first, so a stale one
        // would block us; rgCsrfHeaders sends the current XSRF-TOKEN.
        return fetch(cfg.urls.upload, { method: 'POST', credentials: 'same-origin', headers: rgCsrfHeaders({ 'Accept': 'application/json' }), body: fd })
            .then(function (r) {
                return r.text().then(function (text) {
                    try { return JSON.parse(text); }
                    catch (e) {
                        // Non-JSON (PHP error / login redirect) — surface the
                        // status instead of a silent "Unknown error".
                        return { ok: false, message: 'Server returned HTTP ' + r.status + ' (not JSON).' };
                    }
                });
            })
            .catch(function (e) { return { ok: false, message: 'Network error: ' + e.message }; });
    }

    /**
     * For per-row file inputs (hero slider slide, attraction image): upload
     * the file, set the hidden input's value to the returned URL, and
     * inject a small thumbnail into the row header.
     */
    function handleRowUpload(input, row, hiddenSelector) {
        const file = (input.files || [])[0];
        if (!file) return;
        uploadFile(file).then(d => {
            if (!d || !d.ok) return;
            const hidden = row.querySelector('[' + hiddenSelector + ']');
            if (hidden) hidden.setAttribute('value', d.url);
            // Replace or insert the thumbnail at the row-header start.
            const hdr = row.querySelector('.rg-row-hdr');
            if (!hdr) return;
            const existing = hdr.querySelector('img, span.placeholder-thumb');
            const thumb = document.createElement('img');
            thumb.src = d.url;
            thumb.style.cssText = 'width:60px;height:36px;object-fit:cover;border-radius:3px';
            if (existing) existing.replaceWith(thumb); else hdr.prepend(thumb);
            if (typeof toastr !== 'undefined') toastr.success('Uploaded');
        });
    }

    function addFaqRow(question = '', answer = '') {
        const list = document.getElementById('rgFaqList');
        const idx = list.children.length;
        const row = document.createElement('div');
        row.className = 'rg-faq-row';
        row.dataset.idx = idx;
        row.innerHTML = `<input type="text" class="form-control form-control-sm" placeholder="Question" data-faq-q value="${escapeHtml(question)}"><input type="text" class="form-control form-control-sm" placeholder="Answer" data-faq-a value="${escapeHtml(answer)}"><button type="button" class="btn btn-sm btn-outline-danger" data-faq-remove>&times;</button>`;
        list.appendChild(row);
    }

    function handleUpload(e, target) {
        const files = Array.from(e.target.files || []);
        if (!files.length) return;
        if (target === 'gallery') {
            const list = document.getElementById('rgGalleryList');
            files.forEach(file => {
                const fd = new FormData();
                fd.append('file', file);
                fetch(cfg.urls.upload, { method: 'POST', credentials: 'same-origin', headers: rgCsrfHeaders(), body: fd })
                    .then(r => r.json())
                    .then(d => {
                        if (!d.ok) return;
                        const idx = list.children.length;
                        const row = document.createElement('div');
                        row.className = 'rg-gallery-row';
                        row.dataset.idx = idx;
                        row.dataset.src = d.url;
                        row.innerHTML = `<img src="${d.url}" style="width:80px;height:60px;object-fit:cover;border-radius:3px"><div class="d-flex flex-column gap-1 flex-grow-1"><input type="text" class="form-control form-control-sm" placeholder="Alt text (SEO)" data-gallery-alt><input type="text" class="form-control form-control-sm" placeholder="Title text (SEO, optional)" data-gallery-title></div><button type="button" class="btn btn-sm btn-outline-danger" data-gallery-remove>&times;</button>`;
                        list.appendChild(row);
                    });
            });
        } else {
            const fd = new FormData();
            fd.append('file', files[0]);
            fetch(cfg.urls.upload, { method: 'POST', credentials: 'same-origin', headers: rgCsrfHeaders(), body: fd })
                .then(r => r.json())
                .then(d => {
                    if (!d.ok) return;
                    const hiddenInput = document.querySelector(`[data-field="${target}"]`);
                    if (hiddenInput) hiddenInput.value = d.url;
                    if (typeof toastr !== 'undefined') toastr.success('Uploaded');
                    // show preview
                    if (d.kind === 'image') {
                        const existing = e.target.parentElement.querySelector('.rg-image-preview');
                        if (existing) existing.src = d.url;
                        else {
                            const img = document.createElement('img');
                            img.src = d.url; img.className = 'rg-image-preview';
                            e.target.parentElement.appendChild(img);
                        }
                    }
                });
        }
    }

    function readEditor(block) {
        const payload = {};
        // Set a value into payload, supporting dotted paths like
        // "primary_cta.label" -> { primary_cta: { label: ... } }.
        function setDeep(target, path, value) {
            const parts = path.split('.');
            let cur = target;
            for (let i = 0; i < parts.length - 1; i++) {
                if (typeof cur[parts[i]] !== 'object' || cur[parts[i]] === null) {
                    cur[parts[i]] = {};
                }
                cur = cur[parts[i]];
            }
            cur[parts[parts.length - 1]] = value;
        }
        document.querySelectorAll('[data-field]').forEach(input => {
            const field = input.dataset.field;
            // Honor checkbox state instead of literal `.value`.
            const value = input.type === 'checkbox' ? input.checked : input.value;
            if (field.includes('.')) {
                setDeep(payload, field, value);
            } else {
                payload[field] = value;
            }
        });
        // Rich-text + custom_html both use the tabbed Visual/Code editor.
        // Sync the active pane's content to the canonical 'html' field. If
        // user is currently on Visual, the Quill content is authoritative;
        // if on Code, the textarea is. The other pane is kept in sync at
        // every tab switch so either is safe.
        if (block.type === 'rich_text' || block.type === 'custom_html') {
            const codePane = document.querySelector('[data-html-pane="code"]:not([hidden])');
            if (codePane) {
                payload.html = document.querySelector('[data-rg-html-code]')?.value || '';
            } else if (state.quill) {
                payload.html = state.quill.root.innerHTML;
            }
            // Strip the legacy data-field="html" entry the generic loop
            // may have read into payload (the textarea uses data-rg-html-code).
            delete payload.html_raw;
        }
        if (block.type === 'two_column') {
            payload.left_html = state.quillLeft.root.innerHTML;
            payload.right_html = state.quillRight.root.innerHTML;
        }
        if (block.type === 'faq') {
            const items = [];
            document.querySelectorAll('#rgFaqList .rg-faq-row').forEach(row => {
                const q = row.querySelector('[data-faq-q]').value.trim();
                const a = row.querySelector('[data-faq-a]').value.trim();
                if (q || a) items.push({ question: q, answer: a });
            });
            payload.items = items;
        }
        if (block.type === 'gallery') {
            const images = [];
            document.querySelectorAll('#rgGalleryList .rg-gallery-row').forEach(row => {
                const src = row.dataset.src || row.querySelector('img')?.src || '';
                const alt = row.querySelector('[data-gallery-alt]')?.value?.trim() || '';
                const title = row.querySelector('[data-gallery-title]')?.value?.trim() || '';
                if (src) images.push({ src, alt, title });
            });
            payload.images = images;
            payload.columns = parseInt(payload.columns || '3', 10);
        }

        // ── Custom-element payload extraction. Walks live DOM rows so
        //    drag-reorder / remove / add operations all read correctly.

        if (block.type === 'hero_slider') {
            const images = [];
            document.querySelectorAll('#rgHeroList [data-rg-row="hero"]').forEach(row => {
                const src = row.querySelector('[data-rg-slide-src]')?.getAttribute('value') || '';
                if (!src) return;
                images.push({
                    src,
                    alt: row.querySelector('[data-rg-slide-alt]')?.value?.trim() || '',
                    title: row.querySelector('[data-rg-slide-title]')?.value?.trim() || '',
                    caption_title: row.querySelector('[data-rg-slide-caption-title]')?.value?.trim() || '',
                    caption: row.querySelector('[data-rg-slide-caption]')?.value?.trim() || '',
                    credit_url: row.querySelector('[data-rg-slide-credit-url]')?.value?.trim() || '',
                });
            });
            payload.images = images;
            payload.autoplay = String(payload.autoplay) === '1' || payload.autoplay === true;
            payload.interval = parseInt(payload.interval || 6500, 10);
            // payload.style + eyebrow_* come through the generic data-field loop.
        }
        if (block.type === 'quick_facts' || block.type === 'facts_list') {
            // Same row template feeds both block types; the only
            // difference is the parent list ID + data-rg-row tag.
            const listId = block.type === 'facts_list' ? 'rgFlList' : 'rgQfList';
            const rowTag = block.type === 'facts_list' ? 'factslist' : 'quickfacts';
            const cards = [];
            document.querySelectorAll(`#${listId} [data-rg-row="${rowTag}"]`).forEach(row => {
                // Icon + color come from picker containers (attribute), not
                // from <select>.value. The pickers store the active value
                // in their own data-rg-qf-icon / data-rg-qf-color attr.
                const iconPicker  = row.querySelector('[data-rg-qf-icon]');
                const colorPicker = row.querySelector('[data-rg-qf-color]');
                cards.push({
                    icon:   iconPicker?.getAttribute('data-rg-qf-icon')   || 'info',
                    color:  colorPicker?.getAttribute('data-rg-qf-color') || 'blue',
                    big:    row.querySelector('[data-rg-qf-big]')?.value?.trim()    || '',
                    label:  row.querySelector('[data-rg-qf-label]')?.value?.trim()  || '',
                    detail: row.querySelector('[data-rg-qf-detail]')?.value?.trim() || '',
                });
            });
            payload.cards = cards;
        }
        if (block.type === 'editor_rating') {
            const criteria = [];
            document.querySelectorAll('#rgRateList [data-rg-row="rating"]').forEach(row => {
                const name = row.querySelector('[data-rg-rate-name]')?.value?.trim() || '';
                const score = parseFloat(row.querySelector('[data-rg-rate-score]')?.value || 0) || 0;
                if (name) criteria.push({ name, score });
            });
            payload.criteria = criteria;
            payload.overall = parseFloat(payload.overall || 0) || 0;
        }
        if (block.type === 'attractions') {
            const items = [];
            document.querySelectorAll('#rgAttrList [data-rg-row="attractions"]').forEach(row => {
                const name = row.querySelector('[data-rg-attr-name]')?.value?.trim() || '';
                if (!name) return;
                // Walk the thumb grid in DOM order so admins can reorder
                // visually (drag-drop not yet wired but the contract is
                // "first thumb = first image to fade in").
                const images = Array.from(row.querySelectorAll('[data-rg-attr-image-thumb]'))
                    .map(t => t.getAttribute('data-src'))
                    .filter(Boolean);
                items.push({
                    name,
                    // Both `images` (new array shape) and `image` (legacy
                    // single-image fallback) are saved so the renderer
                    // resolves to the same first image regardless of which
                    // field a downstream consumer reads.
                    images,
                    image: images[0] || '',
                    short: row.querySelector('[data-rg-attr-short]')?.value?.trim() || '',
                    blurb: row.querySelector('[data-rg-attr-blurb]')?.value?.trim() || '',
                    url:   row.querySelector('[data-rg-attr-url]')?.value?.trim()   || '',
                });
            });
            payload.items = items;
        }
        if (block.type === 'how_to_get_to') {
            const methods = [];
            document.querySelectorAll('#rgHtgtList [data-rg-row="howto"]').forEach(row => {
                const title = row.querySelector('[data-rg-htgt-title]')?.value?.trim() || '';
                if (!title) return;
                const iconPicker = row.querySelector('[data-rg-htgt-icon]');
                methods.push({
                    icon:     iconPicker?.getAttribute('data-rg-htgt-icon') || 'car',
                    title,
                    subtitle: row.querySelector('[data-rg-htgt-subtitle]')?.value?.trim() || '',
                    detail:   row.querySelector('[data-rg-htgt-detail]')?.value?.trim() || '',
                    color:    row.querySelector('[data-rg-htgt-color]')?.value || 'amber',
                });
            });
            payload.methods = methods;
            payload.footer = document.querySelector('[data-field="footer"]')?.value?.trim() || '';
        }

        if (block.type === 'text_section') {
            // body lands in payload via the generic data-field="body"
            // textarea. Drop the legacy paragraphs[] array on save so the
            // block converges on the single-shape representation.
            payload.body = (payload.body || '').toString().trim();
            delete payload.paragraphs;
        }

        // ── New standardized templates ───────────────────────────────
        if (block.type === 'pros_cons') {
            // Field-style "pros_text" / "cons_text" get split into arrays.
            const proText = (payload.pros_text || '').split('\n').map(s => s.trim()).filter(Boolean);
            const conText = (payload.cons_text || '').split('\n').map(s => s.trim()).filter(Boolean);
            payload.pros = proText;
            payload.cons = conText;
            delete payload.pros_text;
            delete payload.cons_text;
        }
        if (block.type === 'summary_accordion') {
            const items = [];
            document.querySelectorAll('#rgAccList [data-rg-row="accordion"]').forEach(row => {
                items.push({
                    eyebrow: row.querySelector('[data-rg-acc-eyebrow]')?.value?.trim() || '',
                    title:   row.querySelector('[data-rg-acc-title]')?.value?.trim()   || '',
                    body:    row.querySelector('[data-rg-acc-body]')?.value?.trim()    || '',
                    open:    !!row.querySelector('[data-rg-acc-open]')?.checked,
                });
            });
            payload.items = items;
        }
        if (block.type === 'traveler_reviews') {
            const items = [];
            document.querySelectorAll('#rgTrList [data-rg-row="reviews"]').forEach(row => {
                items.push({
                    name:   row.querySelector('[data-rg-tr-name]')?.value?.trim()   || '',
                    avatar: row.querySelector('[data-rg-tr-avatar]')?.getAttribute('value') || '',
                    date:   row.querySelector('[data-rg-tr-date]')?.value?.trim()   || '',
                    rating: parseInt(row.querySelector('[data-rg-tr-rating]')?.value || 5, 10),
                    quote:  row.querySelector('[data-rg-tr-quote]')?.value?.trim()  || '',
                });
            });
            payload.items = items;
        }
        if (block.type === 'related_guides') {
            const items = [];
            document.querySelectorAll('#rgRelList [data-rg-row="related"]').forEach(row => {
                const url = row.querySelector('[data-rg-rel-url]')?.value?.trim() || '';
                const title = row.querySelector('[data-rg-rel-title]')?.value?.trim() || '';
                if (!url || !title) return;
                items.push({
                    eyebrow: row.querySelector('[data-rg-rel-eyebrow]')?.value?.trim() || '',
                    title,
                    blurb:   row.querySelector('[data-rg-rel-blurb]')?.value?.trim()   || '',
                    url,
                });
            });
            payload.items = items;
        }
        if (block.type === 'data_table') {
            const headers = (payload.headers_text || '').split(',').map(s => s.trim()).filter(Boolean);
            const rowsRaw = (payload.rows_text || '').split('\n').map(s => s.trim()).filter(Boolean);
            const rows = rowsRaw.map(line => line.split('|').map(c => c.trim()));
            payload.headers = headers;
            payload.rows = rows;
            delete payload.headers_text;
            delete payload.rows_text;
        }
        if (block.type === 'map_embed') {
            payload.height = parseInt(payload.height || 450, 10);
        }
        if (block.type === 'tag_pills') {
            const items = [];
            document.querySelectorAll('#rgPillList [data-rg-row="pill"]').forEach(row => {
                const text = row.querySelector('[data-rg-pill-text]')?.value?.trim() || '';
                if (text === '') return;
                items.push({
                    text,
                    color: row.querySelector('[data-rg-pill-color]')?.value || 'amber',
                });
            });
            payload.items = items;
        }
        if (block.type === 'external_guides') {
            const items = [];
            document.querySelectorAll('#rgGuideList [data-rg-row="guide"]').forEach(row => {
                const name = row.querySelector('[data-rg-guide-name]')?.value?.trim() || '';
                const url = row.querySelector('[data-rg-guide-url]')?.value?.trim() || '';
                if (!name || !url) return;
                items.push({
                    name, url,
                    color: row.querySelector('[data-rg-guide-color]')?.value || 'slate',
                    blurb: row.querySelector('[data-rg-guide-blurb]')?.value?.trim() || '',
                    logo: row.querySelector('[data-rg-guide-logo]')?.getAttribute('value') || '',
                    screenshot: row.querySelector('[data-rg-guide-shot]')?.getAttribute('value') || '',
                });
            });
            payload.items = items;
        }

        if (block.type === 'author') {
            const links = [];
            document.querySelectorAll('#rgAuthorLinkList [data-rg-row="authorlink"]').forEach(row => {
                const url = row.querySelector('[data-rg-author-link-url]')?.value?.trim() || '';
                if (!url) return;
                links.push({
                    type: row.querySelector('[data-rg-author-link-type]')?.value || 'website',
                    url,
                });
            });
            payload.links = links;
        }

        if (block.type === 'nearby_destinations') {
            const items = [];
            document.querySelectorAll('#rgNbydList [data-rg-row="nbyd"]').forEach(row => {
                const title = row.querySelector('[data-rg-nbyd-title]')?.value?.trim() || '';
                const url = row.querySelector('[data-rg-nbyd-url]')?.value?.trim() || '';
                if (!title || !url) return;
                items.push({
                    title, url,
                    eyebrow: row.querySelector('[data-rg-nbyd-eyebrow]')?.value?.trim() || '',
                    image: row.querySelector('[data-rg-nbyd-image]')?.getAttribute('value') || '',
                    blurb: row.querySelector('[data-rg-nbyd-blurb]')?.value?.trim() || '',
                    distance_label: row.querySelector('[data-rg-nbyd-distance]')?.value?.trim() || '',
                });
            });
            payload.items = items;
            payload.max = parseInt(payload.max, 10) || 6;
        }

        if (block.type === 'related_blogs') {
            const items = [];
            document.querySelectorAll('#rgRblogList [data-rg-row="rblog"]').forEach(row => {
                const title = row.querySelector('[data-rg-rblog-title]')?.value?.trim() || '';
                const url = row.querySelector('[data-rg-rblog-url]')?.value?.trim() || '';
                if (!title || !url) return;
                items.push({
                    title, url,
                    eyebrow: row.querySelector('[data-rg-rblog-eyebrow]')?.value?.trim() || '',
                    cover: row.querySelector('[data-rg-rblog-cover]')?.getAttribute('value') || '',
                    excerpt: row.querySelector('[data-rg-rblog-excerpt]')?.value?.trim() || '',
                    meta: row.querySelector('[data-rg-rblog-meta]')?.value?.trim() || '',
                });
            });
            payload.items = items;
            // CSV input becomes the keywords[] array; strip empty entries.
            const csv = (payload.auto_from_keywords_csv || '').toString();
            payload.auto_from_keywords = csv.split(',').map(s => s.trim()).filter(Boolean);
            delete payload.auto_from_keywords_csv;
            payload.max = parseInt(payload.max, 10) || 3;
        }

        // ── Home blocks: image-bearing arrays are edited as live rows
        //    (HOME_ARRAY_SCHEMAS) — read them back from the DOM so each
        //    media-picked image + alt is captured. rotating_words stays a
        //    simple JSON textarea.
        if (HOME_ARRAY_SCHEMAS[block.type]) {
            Object.assign(payload, readHomeArrays(block.type));
        }
        if (block.type === 'home_category_accordion' && typeof payload.rotating_words === 'string') {
            try { payload.rotating_words = JSON.parse(payload.rotating_words || '[]'); } catch (e) { console.warn('Invalid rotating_words JSON'); }
        }
        if (block.type === 'home_keyword_hashtags') {
            payload.limit = parseInt(payload.limit, 10) || 40;
        }
        // dest_hero_search: the popular-chips textarea is one-per-line; the
        // renderer wants an array of strings. (breadcrumbs/stats_pills/
        // search_tabs are handled by the HOME_ARRAY_SCHEMAS repeater merge above.)
        if (block.type === 'dest_hero_search' && typeof payload.search_chips === 'string') {
            payload.search_chips = payload.search_chips.split(/\r?\n/).map(s => s.trim()).filter(Boolean);
        }
        // partner_hero trust points + partner_badge checklist points are
        // edited as one-per-line textareas; the renderer wants string arrays.
        // (audience.items / steps.steps / perks.perks are captured by the
        // HOME_ARRAY_SCHEMAS repeater merge above.)
        if (block.type === 'partner_hero' && typeof payload.trust_points === 'string') {
            payload.trust_points = payload.trust_points.split(/\r?\n/).map(s => s.trim()).filter(Boolean);
        }
        if (block.type === 'partner_badge' && typeof payload.points === 'string') {
            payload.points = payload.points.split(/\r?\n/).map(s => s.trim()).filter(Boolean);
        }

        return payload;
    }

    // Always send the CURRENT CSRF token. Laravel refreshes the readable
    // XSRF-TOKEN cookie on every response, so it never goes stale like the
    // page-baked cfg.csrf (which broke after re-logins → 419 on save).
    function rgCsrfHeaders(base) {
        base = base || {};
        var m = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
        if (m) { base['X-XSRF-TOKEN'] = decodeURIComponent(m[1]); }
        else { base['X-CSRF-TOKEN'] = cfg.csrf; }
        return base;
    }
    function rgNotify(kind, msg) {
        if (typeof toastr !== 'undefined') { toastr[kind === 'error' ? 'error' : 'success'](msg); }
        else if (kind === 'error') { alert(msg); }
    }

    function saveCurrent() {
        if (!state.current) { alert('No block is open to save.'); return; }
        const payload = readEditor(state.current);
        const body = {
            owner_type: cfg.ownerType,
            owner_id: cfg.ownerId,
            block_type: state.current.type,
            payload: payload,
        };
        if (state.current.id) body.id = state.current.id;
        fetch(cfg.urls.save, {
            method: 'POST',
            headers: rgCsrfHeaders({ 'Content-Type': 'application/json', 'Accept': 'application/json' }),
            credentials: 'same-origin',
            body: JSON.stringify(body),
        })
        .then(function (r) {
            return r.text().then(function (text) {
                let d = null;
                try { d = JSON.parse(text); } catch (e) {}
                if (!r.ok || !d || !d.ok) {
                    let msg = (d && d.message) || ('Save failed (HTTP ' + r.status + ')');
                    if (r.status === 419) msg = 'Your session expired (CSRF). Please reload this page and try again.';
                    rgNotify('error', msg);
                    return;
                }
                const idx = state.blocks.findIndex(function (b) { return String(b.id) === String(d.block.id); });
                if (idx >= 0) state.blocks[idx] = d.block; else state.blocks.push(d.block);
                state.blocks.sort(function (a, b) { return a.sort_order - b.sort_order; });
                render();
                initSortable();
                bootstrap.Modal.getInstance(document.getElementById('blockEditorModal')).hide();
                rgNotify('success', 'Block saved');
            });
        })
        .catch(function (e) { rgNotify('error', 'Save error: ' + e.message); });
    }

    // ── Media picker ──────────────────────────────────────────────
    // Standardized image chooser for every block type. openMediaPicker()
    // shows the nested Bootstrap modal, the admin either uploads a new
    // file or clicks one in the library grid, and the supplied onSelect
    // callback receives the chosen media item. Resolves the
    // "image cannot be changed" issue end-to-end:
    //   - Uploads go through the fixed POST → frontend storage path.
    //   - Library lists rg_media so already-uploaded images surface.
    //   - The picker's success callback fires AFTER the upload completes,
    //     so there's no race with the editor save.

    let mediaPickerOnSelect = null;
    let mediaPickerSearchDebounce = 0;
    let mediaPickerInited = false;

    function openMediaPicker(opts) {
        mediaPickerOnSelect = opts?.onSelect || null;
        const modalEl = document.getElementById('rgMediaPickerModal');
        if (!mediaPickerInited) initMediaPicker();
        // Reset to upload tab + clear previous status.
        activateMediaTab('upload');
        const status = document.getElementById('rgMediaUploadStatus');
        if (status) status.innerHTML = '';
        const fileInput = document.getElementById('rgMediaUploadInput');
        if (fileInput) fileInput.value = '';
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
        // Preload the library grid so switching tabs is instant.
        loadMediaGrid(1, '');
    }

    function activateMediaTab(name) {
        document.querySelectorAll('[data-media-tab]').forEach(t => {
            t.classList.toggle('active', t.dataset.mediaTab === name);
        });
        document.querySelectorAll('[data-media-pane]').forEach(p => {
            p.hidden = p.dataset.mediaPane !== name;
        });
    }

    function loadMediaGrid(page, query) {
        const grid = document.getElementById('rgMediaGrid');
        const pager = document.getElementById('rgMediaPager');
        if (!grid || !pager) return;
        grid.innerHTML = '<div class="rg-media-loading"><i class="bx bx-loader bx-spin"></i> Loading images…</div>';
        pager.innerHTML = '';
        const url = '/resort-guru-blocks-media-list?kind=image&page=' + page +
            '&q=' + encodeURIComponent(query || '');
        fetch(url, { credentials: 'same-origin' })
            .then(r => r.json())
            .then(d => {
                if (!d || !d.ok) {
                    grid.innerHTML = '<div class="rg-media-empty">Failed to load library.</div>';
                    return;
                }
                if (!d.items.length) {
                    grid.innerHTML = '<div class="rg-media-empty">No images match. Upload a new one in the other tab.</div>';
                    return;
                }
                grid.innerHTML = d.items.map(m => {
                    const url = mediaUrl(m.url);
                    return '<div class="rg-media-tile" data-media-id="' + m.id + '" ' +
                        'data-media-url="' + escapeHtml(m.url) + '" ' +
                        'data-media-name="' + escapeHtml(m.filename) + '" ' +
                        'title="' + escapeHtml(m.filename) + '">' +
                        '<img src="' + escapeHtml(url) + '" alt="" loading="lazy">' +
                        '<div class="rg-media-tile-meta">' + escapeHtml(m.filename) + '</div>' +
                        '</div>';
                }).join('');
                grid.querySelectorAll('.rg-media-tile').forEach(tile => {
                    tile.addEventListener('click', () => {
                        pickFromMedia({
                            id: parseInt(tile.dataset.mediaId, 10) || null,
                            url: tile.dataset.mediaUrl,
                            name: tile.dataset.mediaName,
                            kind: 'image',
                        });
                    });
                });
                const pageNum = d.page;
                const totalPages = d.pages || 1;
                pager.innerHTML =
                    '<button type="button" class="btn btn-sm btn-outline-secondary" data-media-prev ' +
                    (pageNum <= 1 ? 'disabled' : '') + '><i class="bx bx-chevron-left"></i> Prev</button>' +
                    '<span class="text-muted small">Page ' + pageNum + ' of ' + totalPages +
                    ' &middot; ' + d.total + ' total</span>' +
                    '<button type="button" class="btn btn-sm btn-outline-secondary" data-media-next ' +
                    (pageNum >= totalPages ? 'disabled' : '') + '>Next <i class="bx bx-chevron-right"></i></button>';
                pager.querySelector('[data-media-prev]')?.addEventListener('click',
                    () => loadMediaGrid(pageNum - 1, query));
                pager.querySelector('[data-media-next]')?.addEventListener('click',
                    () => loadMediaGrid(pageNum + 1, query));
            })
            .catch(() => {
                grid.innerHTML = '<div class="rg-media-empty">Failed to load library.</div>';
            });
    }

    function pickFromMedia(media) {
        if (typeof mediaPickerOnSelect === 'function') {
            try { mediaPickerOnSelect(media); } catch (e) { console.error(e); }
        }
        mediaPickerOnSelect = null;
        bootstrap.Modal.getInstance(document.getElementById('rgMediaPickerModal'))?.hide();
    }

    function initMediaPicker() {
        if (mediaPickerInited) return;
        mediaPickerInited = true;
        document.querySelectorAll('[data-media-tab]').forEach(t => {
            t.addEventListener('click', () => activateMediaTab(t.dataset.mediaTab));
        });
        const upload = document.getElementById('rgMediaUploadInput');
        if (upload) {
            upload.addEventListener('change', () => {
                const file = upload.files?.[0];
                if (!file) return;
                const status = document.getElementById('rgMediaUploadStatus');
                if (status) status.innerHTML =
                    '<i class="bx bx-loader bx-spin"></i> Uploading "' + escapeHtml(file.name) + '"…';
                uploadFile(file).then(d => {
                    upload.value = ''; // Reset so re-picking same file refires change.
                    if (!d || !d.ok) {
                        if (status) status.innerHTML =
                            '<div class="text-danger"><i class="bx bx-error-circle"></i> Upload failed: ' +
                            escapeHtml(d?.message || 'Unknown error') + '</div>';
                        return;
                    }
                    if (status) status.innerHTML =
                        '<div class="text-success"><i class="bx bx-check-circle"></i> Uploaded ' +
                        escapeHtml(d.name) + '</div>';
                    pickFromMedia({ id: null, url: d.url, name: d.name, kind: d.kind });
                });
            });
        }
        const search = document.getElementById('rgMediaSearch');
        if (search) {
            search.addEventListener('input', () => {
                clearTimeout(mediaPickerSearchDebounce);
                mediaPickerSearchDebounce = setTimeout(
                    () => loadMediaGrid(1, search.value),
                    220
                );
            });
        }
    }

    function bindToolbar() {
        document.querySelectorAll('[data-add-type]').forEach(btn => {
            btn.addEventListener('click', () => {
                const type = btn.dataset.addType;
                openEditor({ id: 0, type, payload: defaultPayload(type) });
            });
        });
        document.getElementById('blockEditorSave').addEventListener('click', saveCurrent);

        // Canvas thumbnail interactions: lightbox on image click, Change
        // overlay on hover → media picker → POST to update-image endpoint
        // → patch the block in state and re-render that row only.
        const canvas = document.getElementById('rgBlocksCanvas');
        canvas.addEventListener('click', (e) => {
            const changeBtn = e.target.closest('[data-thumb-change]');
            if (changeBtn) {
                e.preventDefault();
                const thumb = changeBtn.closest('.rg-block-thumb');
                const row = changeBtn.closest('.rg-block');
                const slot = parseInt(thumb?.dataset.thumbSlot || 0, 10);
                const blockId = parseInt(row?.dataset.id || 0, 10);
                if (!blockId) return;
                openMediaPicker({ onSelect: (media) => updateBlockImage(blockId, slot, media.url) });
                return;
            }
            const thumb = e.target.closest('.rg-block-thumb');
            if (thumb) {
                e.preventDefault();
                openLightbox(thumb.dataset.thumbSrc, {
                    blockId: parseInt(thumb.closest('.rg-block')?.dataset.id || 0, 10),
                    slot: parseInt(thumb.dataset.thumbSlot || 0, 10),
                });
            }
        });
    }

    /**
     * Patch a single image slot on a block via the new
     * /resort-guru-blocks-update-image endpoint. On success, replace
     * the block in state + re-render its row in place — no full canvas
     * reload, no editor modal needed.
     */
    function updateBlockImage(blockId, slot, url) {
        fetch('/resort-guru-blocks-update-image', {
            method: 'POST',
            headers: rgCsrfHeaders({ 'Content-Type': 'application/json' }),
            credentials: 'same-origin',
            body: JSON.stringify({ id: blockId, slot, url }),
        }).then(r => r.json()).then(d => {
            if (!d || !d.ok) {
                if (typeof toastr !== 'undefined') toastr.error(d?.message || 'Update failed');
                return;
            }
            const idx = state.blocks.findIndex(b => b.id === d.block.id);
            if (idx < 0) return;
            state.blocks[idx] = d.block;
            const oldRow = document.querySelector('.rg-block[data-id="' + d.block.id + '"]');
            if (oldRow) oldRow.replaceWith(blockEl(d.block));
            if (typeof toastr !== 'undefined') toastr.success('Image updated');
        });
    }

    /**
     * Lightbox: dark backdrop + full-size image + Close + Change action.
     * Click backdrop or Esc to close; click image itself does nothing
     * (so users can see it full-size without dismissing).
     */
    function openLightbox(src, ctx) {
        closeLightbox(); // ensure no duplicates
        const ov = document.createElement('div');
        ov.className = 'rg-lightbox-backdrop';
        ov.innerHTML = `
            <img class="rg-lightbox-img" src="${escapeHtml(mediaUrl(src))}" alt="">
            <button type="button" class="rg-lightbox-close" aria-label="Close">&times;</button>
            ${ctx && ctx.blockId ? `
                <div class="rg-lightbox-actions">
                    <button type="button" class="btn" data-lightbox-change><i class="bx bx-image-add"></i> Change image</button>
                </div>
            ` : ''}
        `;
        document.body.appendChild(ov);
        const close = () => closeLightbox();
        ov.addEventListener('click', (e) => {
            if (e.target.closest('.rg-lightbox-img')) return;
            if (e.target.closest('[data-lightbox-change]')) {
                close();
                openMediaPicker({ onSelect: (media) => updateBlockImage(ctx.blockId, ctx.slot || 0, media.url) });
                return;
            }
            close();
        });
        document.addEventListener('keydown', lightboxEscHandler);
    }
    function closeLightbox() {
        document.querySelectorAll('.rg-lightbox-backdrop').forEach(el => el.remove());
        document.removeEventListener('keydown', lightboxEscHandler);
    }
    function lightboxEscHandler(e) { if (e.key === 'Escape') closeLightbox(); }

    // ── Iframe canvas previews ────────────────────────────────────────
    // Each iframe always renders at IFRAME_FIXED_WIDTH (1200px desktop).
    // Per-wrap scale = wrap.clientWidth / 1200, applied via the
    // --rg-scale CSS variable on the iframe. Wrap height = inner content
    // height × scale so there's no cropping or empty space. Recomputes
    // on window resize so the canvas stays consistent.

    function rgComputeScale(wrap) {
        const target = wrap.clientWidth || 600;
        return Math.max(0.15, Math.min(1, target / IFRAME_FIXED_WIDTH));
    }

    function rgFitIframe(wrap, innerHeight) {
        const iframe = wrap.querySelector('iframe.rg-block-iframe');
        if (!iframe) return;
        const scale = rgComputeScale(wrap);
        iframe.style.setProperty('--rg-scale', String(scale));
        if (innerHeight) {
            iframe.style.height = innerHeight + 'px';
            wrap.style.height = Math.round(innerHeight * scale) + 'px';
            // Remember the last reported inner height so resize events can
            // recompute the wrap height without re-querying postMessage.
            wrap.dataset.rgInnerHeight = String(innerHeight);
        } else if (wrap.dataset.rgInnerHeight) {
            const stored = parseInt(wrap.dataset.rgInnerHeight, 10) || 0;
            if (stored > 0) wrap.style.height = Math.round(stored * scale) + 'px';
        }
    }

    if (!window.__rgBuilderMsgInit) {
        window.__rgBuilderMsgInit = true;
        // postMessage from /_preview/block/N tells us the inner content
        // height. We size both the iframe (full height to fit content)
        // and the wrap (scaled height).
        window.addEventListener('message', (e) => {
            const d = e.data;
            if (!d || d.type !== 'rg-preview-height' || !d.blockId) return;
            const innerH = Math.max(60, Math.min(parseInt(d.height, 10) || 0, 4000));
            const wraps = document.querySelectorAll(
                '.rg-iframe-wrap[data-block-id="' + d.blockId + '"]'
            );
            wraps.forEach(wrap => {
                rgFitIframe(wrap, innerH);
                const loading = wrap.querySelector('[data-iframe-loading]');
                if (loading) loading.remove();
            });
        });
        // Re-scale every iframe wrap on window resize so the canvas keeps
        // its consistent miniature appearance regardless of viewport.
        let resizeT = 0;
        window.addEventListener('resize', () => {
            clearTimeout(resizeT);
            resizeT = setTimeout(() => {
                document.querySelectorAll('.rg-iframe-wrap').forEach(w => rgFitIframe(w, null));
            }, 80);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => { bindToolbar(); fetchBlocks(); });
    } else {
        bindToolbar(); fetchBlocks();
    }

    // Host hooks: let an embedding page (e.g. the Live Editor) open a
    // block's editor natively in this page — no edit iframe, no re-login.
    // openEditorById uses the builder's own (async-fetched) list; the
    // host can also pass a fully-formed block object directly via
    // openEditorBlock, which doesn't depend on the builder having
    // finished its fetch.
    window.rgBuilderOpenEditor = function (id) {
        var blocks = state.blocks || [];
        var b = blocks.find(function (x) { return String(x.id) === String(id); });
        if (b) { openEditor(b); return true; }
        return false;
    };
    window.rgBuilderOpenEditorBlock = function (block) {
        if (block && (block.type || block.payload)) { openEditor(block); return true; }
        return false;
    };
})();
</script>
