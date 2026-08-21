<?php

namespace App\Modules\OutreachEngine\Services;

use App\Modules\OutreachEngine\Models\OutreachLead;
use App\Modules\OutreachEngine\Models\OutreachSetting;
use Illuminate\Support\Facades\Log;

/**
 * Assigns every scraped lead a business category using the language model.
 *
 * Google hands back a types[] array, but its first entry is chosen by Google's
 * taxonomy rather than by usefulness - a beach resort routinely arrives as
 * "point_of_interest", and a dental clinic as "health". GridScrapeService still
 * stores that raw answer in `category` for provenance; this service writes the
 * readable one into `aiCategory`.
 *
 * Leads are sent to the model in batches. One call carrying 25 businesses costs
 * a fraction of 25 calls carrying one, and the model sees sibling businesses
 * from the same sweep together, which measurably steadies its phrasing - the
 * same shop does not come back "Cafe" in one batch and "Coffee Shop" in the next.
 */
class LeadCategorizationService
{
    /**
     * Leads per model call. Large enough to amortise the prompt, small enough
     * that one malformed reply cannot cost a whole run.
     */
    const BATCH_SIZE = 25;

    /**
     * Give up on a lead after this many failed passes so a business the model
     * refuses to name cannot be retried by cron forever.
     */
    const MAX_ATTEMPTS = 3;

    /**
     * Output ceiling. Each lead needs roughly 20 tokens of JSON; this leaves
     * generous headroom for a full batch.
     */
    const MAX_TOKENS = 1600;

    /**
     * The taxonomy the model must choose from.
     *
     * A closed list is the point: left open, the model invents a new synonym for
     * every third business and the category filter on the leads screen fills up
     * with one-off values nobody can group by.
     */
    const CATEGORIES = [
        'Accommodation',
        'Restaurant',
        'Cafe',
        'Bar & Nightlife',
        'Retail Store',
        'Grocery & Market',
        'Health & Medical',
        'Dental',
        'Veterinary',
        'Beauty & Wellness',
        'Fitness & Sports',
        'Automotive',
        'Construction & Hardware',
        'Real Estate',
        'Professional Services',
        'Financial Services',
        'Education & Training',
        'Travel & Tours',
        'Events & Entertainment',
        'Agriculture & Farming',
        'Manufacturing',
        'Logistics & Transport',
        'Technology & IT',
        'Government & Public',
        'Religious & Community',
        'Other',
    ];

    protected OutreachSetting $settings;

    protected LlmService $llm;

    public function __construct(OutreachSetting $settings, ?LlmService $llm = null)
    {
        $this->settings = $settings;
        $this->llm = $llm ?: new LlmService($settings);
    }

    /**
     * Is there a model configured to categorise with?
     */
    public function isConfigured(): bool
    {
        return $this->llm->isConfigured();
    }

    /**
     * The taxonomy, for the leads-screen filter dropdown.
     *
     * @return array<int, string>
     */
    public function categories(): array
    {
        return self::CATEGORIES;
    }

    /**
     * Categorise one batch of leads.
     *
     * Every lead handed in is written to - success or failure - so a caller that
     * claimed rows as 'processing' can never leave them stuck in that state.
     *
     * @param  \Illuminate\Support\Collection|array  $leads
     * @return array{categorized:int,failed:int,skipped:int,error:?string}
     */
    public function categorizeBatch($leads): array
    {
        $leads = collect($leads)->values();

        if ($leads->isEmpty()) {
            return ['categorized' => 0, 'failed' => 0, 'skipped' => 0, 'error' => null];
        }

        if (!$this->isConfigured()) {
            // Skipped, not failed: the moment a key is saved these become
            // workable again, and 'failed' rows are not retried.
            $this->markAll($leads, OutreachLead::CATEGORY_SKIPPED);

            return [
                'categorized' => 0,
                'failed' => 0,
                'skipped' => $leads->count(),
                'error' => 'No AI provider is configured.',
            ];
        }

        $answers = $this->askModel($leads);

        if ($answers === null) {
            $this->markAll($leads, OutreachLead::CATEGORY_FAILED, 'The AI provider did not return a usable answer.');

            return [
                'categorized' => 0,
                'failed' => $leads->count(),
                'skipped' => 0,
                'error' => 'The AI provider did not return a usable answer.',
            ];
        }

        $categorized = 0;
        $failed = 0;

        foreach ($leads as $lead) {
            $category = $answers[(int) $lead->id] ?? null;

            if ($category === null) {
                // The model skipped this row. Falling back to Google's own guess
                // beats leaving the lead blank, and it is still better than the
                // raw type because normalizeGoogleType() maps it into the taxonomy.
                $category = $this->fromGoogleType((string) $lead->category);
            }

            if ($category === null) {
                $lead->forceFill([
                    'categoryStatus' => OutreachLead::CATEGORY_FAILED,
                    'categoryAttempts' => (int) $lead->categoryAttempts + 1,
                ])->save();
                $failed++;
                continue;
            }

            $lead->forceFill([
                'aiCategory' => $category,
                'categoryStatus' => OutreachLead::CATEGORY_CATEGORIZED,
                'categoryAttempts' => (int) $lead->categoryAttempts + 1,
                'categorizedAt' => now('Asia/Manila'),
            ])->save();
            $categorized++;
        }

        return ['categorized' => $categorized, 'failed' => $failed, 'skipped' => 0, 'error' => null];
    }

    /**
     * Ask the model to name each business.
     *
     * @param  \Illuminate\Support\Collection  $leads
     * @return array<int, string>|null  id => category, or null when the call failed outright
     */
    protected function askModel($leads): ?array
    {
        $list = [];

        foreach ($leads as $lead) {
            $list[] = [
                'id' => (int) $lead->id,
                'name' => (string) $lead->businessName,
                'googleTypes' => (string) $lead->category,
                'address' => (string) $lead->address,
                'website' => (string) $lead->website,
            ];
        }

        $system = 'You classify businesses into a fixed taxonomy. You reply with JSON only, no prose, no markdown.';

        $user = "Assign each business below exactly one category from this list:\n"
            . implode(', ', self::CATEGORIES) . "\n\n"
            . "Use the business name first; the Google type is a hint and is often generic "
            . "(\"point_of_interest\", \"establishment\") - ignore it when the name is clearer. "
            . "Choose \"Other\" only when nothing else fits.\n\n"
            . "Businesses:\n"
            . json_encode($list, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n\n"
            . 'Return JSON of this exact shape, one entry per business, no extra keys: '
            . '{"results":[{"id":123,"category":"Restaurant"}]}';

        $decoded = $this->llm->completeJson($system, $user, self::MAX_TOKENS);

        if (empty($decoded) || !isset($decoded['results']) || !is_array($decoded['results'])) {
            Log::warning('[OutreachEngine] Categorisation returned no usable results', [
                'batch' => $leads->count(),
                'provider' => $this->llm->provider(),
            ]);

            return null;
        }

        $valid = array_flip(self::CATEGORIES);
        $out = [];

        foreach ($decoded['results'] as $row) {
            if (!is_array($row) || !isset($row['id'], $row['category'])) {
                continue;
            }

            $category = $this->snapToTaxonomy((string) $row['category'], $valid);

            if ($category !== null) {
                $out[(int) $row['id']] = $category;
            }
        }

        return $out;
    }

    /**
     * Force a model answer onto the taxonomy.
     *
     * Even told to pick from a list, models return "Restaurants", "restaurant"
     * or "Food & Dining". An exact match is tried first, then a case-insensitive
     * one, then a loose contains-either-way match before the answer is dropped.
     *
     * @param  array<string, int>  $valid
     */
    protected function snapToTaxonomy(string $answer, array $valid): ?string
    {
        $answer = trim($answer);

        if ($answer === '') {
            return null;
        }

        if (isset($valid[$answer])) {
            return $answer;
        }

        $needle = mb_strtolower($answer);

        foreach (self::CATEGORIES as $category) {
            if (mb_strtolower($category) === $needle) {
                return $category;
            }
        }

        // Told to pick from a list, models still answer with the word a human
        // would use - "Hotel", "Dentist", "Food & Dining". Measured against the
        // real taxonomy these fail every match below (nothing contains "hotel",
        // and "dentists" neither contains nor is contained by "dental"), so the
        // lead would land as failed for a perfectly good answer.
        $synonyms = [
            'hotel' => 'Accommodation',
            'hotels' => 'Accommodation',
            'resort' => 'Accommodation',
            'resorts' => 'Accommodation',
            'lodging' => 'Accommodation',
            'hostel' => 'Accommodation',
            'inn' => 'Accommodation',
            'food & dining' => 'Restaurant',
            'food and dining' => 'Restaurant',
            'dining' => 'Restaurant',
            'food' => 'Restaurant',
            'eatery' => 'Restaurant',
            'coffee shop' => 'Cafe',
            'coffee' => 'Cafe',
            'bakery' => 'Cafe',
            'dentist' => 'Dental',
            'dentists' => 'Dental',
            'dental clinic' => 'Dental',
            'doctor' => 'Health & Medical',
            'clinic' => 'Health & Medical',
            'hospital' => 'Health & Medical',
            'pharmacy' => 'Health & Medical',
            'healthcare' => 'Health & Medical',
            'health care' => 'Health & Medical',
            'vet' => 'Veterinary',
            'veterinarian' => 'Veterinary',
            'salon' => 'Beauty & Wellness',
            'spa' => 'Beauty & Wellness',
            'barber' => 'Beauty & Wellness',
            'gym' => 'Fitness & Sports',
            'sports' => 'Fitness & Sports',
            'car repair' => 'Automotive',
            'auto repair' => 'Automotive',
            'car dealer' => 'Automotive',
            'hardware' => 'Construction & Hardware',
            'hardware store' => 'Construction & Hardware',
            'contractor' => 'Construction & Hardware',
            'construction' => 'Construction & Hardware',
            'shop' => 'Retail Store',
            'store' => 'Retail Store',
            'retail' => 'Retail Store',
            'supermarket' => 'Grocery & Market',
            'grocery' => 'Grocery & Market',
            'market' => 'Grocery & Market',
            'bank' => 'Financial Services',
            'insurance' => 'Financial Services',
            'school' => 'Education & Training',
            'university' => 'Education & Training',
            'college' => 'Education & Training',
            'travel agency' => 'Travel & Tours',
            'tour operator' => 'Travel & Tours',
            'tourism' => 'Travel & Tours',
            'entertainment' => 'Events & Entertainment',
            'events' => 'Events & Entertainment',
            'farm' => 'Agriculture & Farming',
            'agriculture' => 'Agriculture & Farming',
            'factory' => 'Manufacturing',
            'logistics' => 'Logistics & Transport',
            'transport' => 'Logistics & Transport',
            'shipping' => 'Logistics & Transport',
            'it services' => 'Technology & IT',
            'technology' => 'Technology & IT',
            'software' => 'Technology & IT',
            'government' => 'Government & Public',
            'church' => 'Religious & Community',
            'religious' => 'Religious & Community',
            'ngo' => 'Religious & Community',
            'law firm' => 'Professional Services',
            'lawyer' => 'Professional Services',
            'accounting' => 'Professional Services',
            'consulting' => 'Professional Services',
            'unknown' => 'Other',
            'n/a' => 'Other',
            'none' => 'Other',
        ];

        if (isset($synonyms[$needle])) {
            return $synonyms[$needle];
        }

        foreach (self::CATEGORIES as $category) {
            $haystack = mb_strtolower($category);

            if (str_contains($haystack, $needle) || str_contains($needle, $haystack)) {
                return $category;
            }
        }

        return null;
    }

    /**
     * Best-effort mapping from a raw Google place type to the taxonomy.
     *
     * Only used as a safety net when the model omits a lead from its answer.
     */
    protected function fromGoogleType(string $type): ?string
    {
        $type = mb_strtolower(trim($type));

        if ($type === '') {
            return null;
        }

        $map = [
            'lodging' => 'Accommodation',
            'hotel' => 'Accommodation',
            'resort_hotel' => 'Accommodation',
            'guest_house' => 'Accommodation',
            'campground' => 'Accommodation',
            'restaurant' => 'Restaurant',
            'meal_takeaway' => 'Restaurant',
            'meal_delivery' => 'Restaurant',
            'food' => 'Restaurant',
            'cafe' => 'Cafe',
            'bakery' => 'Cafe',
            'bar' => 'Bar & Nightlife',
            'night_club' => 'Bar & Nightlife',
            'store' => 'Retail Store',
            'clothing_store' => 'Retail Store',
            'furniture_store' => 'Retail Store',
            'shopping_mall' => 'Retail Store',
            'supermarket' => 'Grocery & Market',
            'grocery_or_supermarket' => 'Grocery & Market',
            'convenience_store' => 'Grocery & Market',
            'hospital' => 'Health & Medical',
            'doctor' => 'Health & Medical',
            'pharmacy' => 'Health & Medical',
            'health' => 'Health & Medical',
            'physiotherapist' => 'Health & Medical',
            'dentist' => 'Dental',
            'veterinary_care' => 'Veterinary',
            'beauty_salon' => 'Beauty & Wellness',
            'hair_care' => 'Beauty & Wellness',
            'spa' => 'Beauty & Wellness',
            'gym' => 'Fitness & Sports',
            'car_repair' => 'Automotive',
            'car_dealer' => 'Automotive',
            'car_wash' => 'Automotive',
            'gas_station' => 'Automotive',
            'hardware_store' => 'Construction & Hardware',
            'general_contractor' => 'Construction & Hardware',
            'real_estate_agency' => 'Real Estate',
            'lawyer' => 'Professional Services',
            'accounting' => 'Professional Services',
            'insurance_agency' => 'Financial Services',
            'bank' => 'Financial Services',
            'atm' => 'Financial Services',
            'school' => 'Education & Training',
            'university' => 'Education & Training',
            'travel_agency' => 'Travel & Tours',
            'tourist_attraction' => 'Events & Entertainment',
            'movie_theater' => 'Events & Entertainment',
            'local_government_office' => 'Government & Public',
            'city_hall' => 'Government & Public',
            'police' => 'Government & Public',
            'church' => 'Religious & Community',
            'mosque' => 'Religious & Community',
            'hindu_temple' => 'Religious & Community',
            'moving_company' => 'Logistics & Transport',
            'storage' => 'Logistics & Transport',
            'electrician' => 'Construction & Hardware',
            'plumber' => 'Construction & Hardware',
        ];

        return $map[$type] ?? null;
    }

    /**
     * Stamp one status across a whole batch.
     *
     * @param  \Illuminate\Support\Collection  $leads
     */
    protected function markAll($leads, string $status, ?string $error = null): void
    {
        foreach ($leads as $lead) {
            $lead->forceFill([
                'categoryStatus' => $status,
                'categoryAttempts' => (int) $lead->categoryAttempts + 1,
            ])->save();
        }

        if ($error !== null) {
            Log::warning('[OutreachEngine] Categorisation batch marked ' . $status . ': ' . $error);
        }
    }
}
