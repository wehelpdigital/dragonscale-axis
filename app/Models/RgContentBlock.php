<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RgContentBlock extends Model
{
    protected $table = 'rg_content_blocks';

    protected $fillable = [
        'owner_type', 'owner_id', 'sort_order', 'block_type', 'payload_json',
    ];

    /**
     * Block types accepted by the admin builder. Must stay in sync with
     * the frontend app's App\Models\RgContentBlock and with BlockRenderer.
     */
    public const ALLOWED_TYPES = [
        // Generic Phase-1 blocks.
        'heading', 'rich_text', 'image', 'gallery', 'video', 'faq',
        'cta', 'two_column', 'listing_slot', 'quote', 'divider', 'custom_html',
        // Custom Resort Guru elements matching what the food / keyword
        // page seeders ship today, so admins can author them directly.
        'hero_slider', 'quick_facts', 'editor_rating',
        'listing_block', 'attractions', 'how_to_get_to',
        // Structured prose — replaces rich_text for h2-bounded sections.
        // Authored as heading + ordered list of paragraphs, no Quill.
        'text_section',
        // Standardized section templates covering recurring patterns from
        // the seeded pages so each section gets its own structured editor
        // instead of leaving them as raw custom_html.
        'short_version', 'pros_cons', 'summary_accordion',
        'image_text_pair', 'traveler_reviews', 'map_embed',
        'local_tip', 'related_guides', 'data_table',
        // Article-body section opener (H2 + small subtitle lede), replaces
        // the hardcoded "What's in [Area]?" header on keyword pages.
        'section_header',
        // Inline content extracted from seeded text blocks: pill rows for
        // cuisines/tags + a comparison grid for third-party guides.
        'tag_pills', 'external_guides',
        // Author / byline card — typically sits at the bottom of an SEO
        // page so the editorial voice is attributed without competing with
        // the listing band at the top.
        'author',
        // Cross-vertical cards: nearby destination keyword pages + related
        // blog posts. Both render on food / restaurant pages so visitors
        // can dive deeper after the main content stream.
        'nearby_destinations', 'related_blogs',
        // Vertical-list variant of quick_facts — destinations use this
        // instead of the 4-up grid because their facts (travel time,
        // best season, local rules) read better as a scannable list.
        'facts_list',
        // Researched origin / history card. Eyebrow + headline + multi-
        // paragraph body. Authored from web-sourced facts.
        'place_history',
        // "Foods to try" grid — lists actual dishes per destination
        // (papaitan, pigar-pigar, pinakbet), not restaurants.
        'foods_to_try',
        // Page-header content elements migrated out of rg_seo_pages
        // columns into blocks so the admin can reorder / remove / add
        // them like any other content. subtitle_intro replaces the
        // hardcoded italic line under H1; tldr_card + wwww_card port
        // the partials/summary-blocks accordion into two independent
        // collapsible blocks.
        'subtitle_intro', 'tldr_card', 'wwww_card',
        // Page-top hardcoded includes converted to blocks so the
        // admin can reorder / remove / add them like any other
        // content. social_share = partials.social-share row;
        // we_recommend_band = the "We Recommend" listings band
        // header + listings partial (branches on keyword.category).
        'social_share', 'we_recommend_band',
        // Page-tail hardcoded sections converted to blocks. Each
        // only renders when its data is non-empty + the keyword
        // category matches (restaurant_recs + adventures only fire
        // on non-food keyword pages with active listings; reviews
        // only fires when at least one review is published).
        'restaurant_recs_band', 'adventures_band', 'reviews_band',
        // /destinations page custom block types — see frontend
        // RgContentBlock for full notes.
        'dest_hero_search', 'dest_featured_slider', 'dest_region_clusters',
        // Destination CLUSTER page (/destinations/{cluster}) blocks. One shared
        // `destination-cluster` template renders for every region, with that
        // region's data injected as context by DestinationsController@cluster.
        'destcluster_hero', 'destcluster_whats_in', 'destcluster_featured_spots',
        'destcluster_testimonials', 'destcluster_explore_regions', 'destcluster_hashtags',
        // Homepage custom block types — see frontend RgContentBlock
        // for full notes.
        'home_hero_centered', 'home_keyword_grid', 'home_region_grid',
        'home_resort_grid', 'home_blog_strip', 'home_cta_band',
        // Phase-2 homepage blocks.
        'home_editorial_intro', 'home_experience_grid', 'home_hub_links',
        'home_season_guide', 'home_testimonials', 'home_faq',
        'home_unified_search',
        'home_values_grid',
        // Newer homepage blocks added after the initial build (As Seen On
        // press logos, Find-Your-Kind-of-Trip accordion, alternating
        // feature rows, recommended-badge explainer, partner perks, and the
        // live keyword hashtag cloud).
        'home_press_logos', 'home_category_accordion', 'home_alt_features',
        'home_badge_explainer', 'home_partner_perks', 'home_keyword_hashtags',
        // "How the site works" explainer (accordion / pillars layouts).
        'home_how_it_works',
        // Hub-page block types.
        'hub_hero', 'hub_category_nav', 'hub_category_grid', 'hub_footer_rail',
        // Partner-program blocks (the /become-a-partner page).
        'partner_hero', 'partner_audience', 'partner_steps', 'partner_badge', 'partner_perks',
    ];

    public function payload(): array
    {
        return $this->payload_json ? (json_decode($this->payload_json, true) ?: []) : [];
    }
}
