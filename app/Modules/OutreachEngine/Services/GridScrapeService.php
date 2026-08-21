<?php

namespace App\Modules\OutreachEngine\Services;

use App\Modules\OutreachEngine\Models\OutreachLead;
use App\Modules\OutreachEngine\Models\OutreachSearchGrid;
use App\Modules\OutreachEngine\Models\OutreachSetting;
use App\Modules\OutreachEngine\Support\OutreachException;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Turns a region into grid cells, and a grid cell into leads.
 *
 * queueRegion() lays a batch of overlapping circles over a region's bounding box.
 * processGrid() then works one circle end to end: page Google Places up to three
 * times, insert the businesses we have never seen, and - when the cell comes back
 * saturated at Google's 60-result ceiling - split it into four half-radius
 * children so the hidden businesses underneath get their own sweep.
 *
 * Two rules here are load bearing:
 *   - the duplicate check is GLOBAL, never scoped to the user, because placeId is
 *     unique across the whole table;
 *   - a next_page_token is only spent after a two second pause, because Google
 *     keeps a fresh token invalid for about that long.
 */
class GridScrapeService
{
    /** Google Places never pages past three (20 results each, 60 total). */
    const MAX_PAGES = 3;

    /** Settling time Google needs before a next_page_token becomes valid. */
    const PAGE_TOKEN_DELAY_SECONDS = 2;

    /** Below this a cell is "sparse" - reported to the caller, never subdivided. */
    const SPARSE_THRESHOLD = 5;

    /** Ceiling for a queued sweep radius; Google itself stops at 50 km. */
    const MAX_QUEUE_RADIUS_KM = 50.0;

    /** Rows per bulk insert when a big region tiles into hundreds of cells. */
    const INSERT_CHUNK = 200;

    /** How much of a failure message is kept in lastError. */
    const ERROR_LIMIT = 500;

    /**
     * PH provinces (plus Metro Manila) used to read a city/province out of a Places
     * address string. Matching against a closed list is what keeps the parser from
     * promoting a street or a barangay into the province column.
     */
    const PROVINCE_NAMES = [
        'Abra', 'Agusan del Norte', 'Agusan del Sur', 'Aklan', 'Albay', 'Antique', 'Apayao', 'Aurora',
        'Basilan', 'Bataan', 'Batanes', 'Batangas', 'Benguet', 'Biliran', 'Bohol', 'Bukidnon', 'Bulacan',
        'Cagayan', 'Camarines Norte', 'Camarines Sur', 'Camiguin', 'Capiz', 'Catanduanes', 'Cavite', 'Cebu',
        'Cotabato', 'Davao de Oro', 'Davao del Norte', 'Davao del Sur', 'Davao Occidental', 'Davao Oriental',
        'Dinagat Islands', 'Eastern Samar', 'Guimaras', 'Ifugao', 'Ilocos Norte', 'Ilocos Sur', 'Iloilo',
        'Isabela', 'Kalinga', 'La Union', 'Laguna', 'Lanao del Norte', 'Lanao del Sur', 'Leyte',
        'Maguindanao', 'Maguindanao del Norte', 'Maguindanao del Sur', 'Marinduque', 'Masbate', 'Metro Manila',
        'Misamis Occidental', 'Misamis Oriental', 'Mountain Province', 'Negros Occidental', 'Negros Oriental',
        'Northern Samar', 'Nueva Ecija', 'Nueva Vizcaya', 'Occidental Mindoro', 'Oriental Mindoro', 'Palawan',
        'Pampanga', 'Pangasinan', 'Quezon', 'Quirino', 'Rizal', 'Romblon', 'Samar', 'Sarangani', 'Siquijor',
        'Sorsogon', 'South Cotabato', 'Southern Leyte', 'Sultan Kudarat', 'Sulu', 'Surigao del Norte',
        'Surigao del Sur', 'Tarlac', 'Tawi-Tawi', 'Zambales', 'Zamboanga del Norte', 'Zamboanga del Sur',
        'Zamboanga Sibugay',
    ];

    /** Older or alternative names Places still returns, mapped to the name we store. */
    const PROVINCE_ALIASES = [
        'National Capital Region' => 'Metro Manila',
        'NCR' => 'Metro Manila',
        'Manila' => 'Metro Manila',
        'North Cotabato' => 'Cotabato',
        'Compostela Valley' => 'Davao de Oro',
        'Western Samar' => 'Samar',
        'Shariff Kabunsuan' => 'Maguindanao',
    ];

    /** Lazily built lookup: normalised name => canonical province. */
    protected static ?array $provinceLookup = null;

    protected OutreachSetting $settings;
    protected GooglePlacesService $places;
    protected GeoGridService $geo;

    public function __construct(OutreachSetting $settings)
    {
        $this->settings = $settings;
        $this->places = new GooglePlacesService($settings);
        $this->geo = new GeoGridService();
    }

    /**
     * Work one grid cell end to end.
     *
     * @return array ['results'=>int,'new'=>int,'split'=>bool,'sparse'=>bool,'error'=>?string]
     */
    public function processGrid(OutreachSearchGrid $grid): array
    {
        $totalResults = 0;
        $newLeads = 0;
        $didSplit = false;
        $pageNote = null;
        $seenPlaceIds = [];

        try {
            // Claim the cell before the first network call, so a cron run and the
            // scraper screen's inline "run batch" cannot both work the same row.
            $grid->update([
                'status' => OutreachSearchGrid::STATUS_PROCESSING,
                'attempts' => (int) $grid->attempts + 1,
                'lastError' => null,
            ]);

            if (!$this->places->isConfigured()) {
                throw new OutreachException('No Google Places API key is saved. Add one under Lead Finder > Settings.');
            }

            $radiusKm = (float) $grid->radiusKm;
            $businessType = (string) $grid->businessType;
            $pageToken = null;

            for ($page = 1; $page <= self::MAX_PAGES; $page++) {
                if ($pageToken !== null) {
                    // Google keeps a freshly issued next_page_token invalid for a second
                    // or two and answers INVALID_REQUEST if it is used any sooner. The
                    // scraper runs from cron/CLI, so simply waiting it out is fine.
                    sleep(self::PAGE_TOKEN_DELAY_SECONDS);
                }

                $response = $this->places->nearbySearch(
                    (float) $grid->latitude,
                    (float) $grid->longitude,
                    $radiusKm * 1000,
                    $businessType,
                    $pageToken
                );

                if (!empty($response['error'])) {
                    if ($page === 1) {
                        // Nothing was collected at all, so fail the cell and surface the
                        // real Google status - the command decides whether to retry it.
                        throw new OutreachException((string) $response['error']);
                    }

                    // A later page died: page one's leads are already saved, so keep them,
                    // stop paging, and record why the cell is only partly covered.
                    $pageNote = (string) $response['error'];
                    Log::warning('[OutreachEngine] Grid ' . $grid->id . ' stopped at page ' . $page . ' - ' . $pageNote);
                    break;
                }

                foreach ($response['results'] as $place) {
                    if (!is_array($place)) {
                        continue;
                    }

                    $placeId = trim((string) ($place['place_id'] ?? ''));

                    // Pages overlap now and then; count each business once per cell.
                    if ($placeId === '' || isset($seenPlaceIds[$placeId])) {
                        continue;
                    }

                    $seenPlaceIds[$placeId] = true;
                    $totalResults++;

                    // GLOBAL on purpose - placeId is unique across the whole table, not
                    // per user. Scoping this to the current user would let another
                    // account's row slip past and blow up on the unique index.
                    if (OutreachLead::where('placeId', $placeId)->exists()) {
                        continue;
                    }

                    if ($this->createLead($grid, $place, $placeId)) {
                        $newLeads++;
                    }
                }

                $pageToken = $response['nextPageToken'];

                if ($pageToken === null) {
                    break;
                }
            }

            // Saturation: 60 results means Google truncated the answer, so the cell is
            // hiding businesses. Only split while there is depth budget left AND the
            // children would still be wider than the configured floor - otherwise the
            // batch would grow forever over a dense city block.
            $childRadiusKm = $radiusKm / 2;
            $maxDepth = (int) ($this->settings->maxSubdivisionDepth ?? 4);
            $minRadiusKm = (float) ($this->settings->minGridRadiusKm ?? 0.5);

            $shouldSplit = $totalResults >= OutreachSearchGrid::SATURATION_THRESHOLD
                && (int) $grid->depth < $maxDepth
                && $childRadiusKm >= $minRadiusKm;

            if ($shouldSplit) {
                $didSplit = $this->createChildren($grid, $childRadiusKm) > 0;
            }

            $grid->update([
                'status' => $didSplit ? OutreachSearchGrid::STATUS_SPLIT : OutreachSearchGrid::STATUS_COMPLETED,
                'resultsCount' => $totalResults,
                'newLeadsCount' => $newLeads,
                'pageToken' => null,
                'lastError' => $pageNote,
                'processedAt' => Carbon::now('Asia/Manila'),
            ]);

            return [
                'results' => $totalResults,
                'new' => $newLeads,
                'split' => $didSplit,
                'sparse' => $totalResults < self::SPARSE_THRESHOLD,
                'error' => $pageNote,
            ];
        } catch (\Throwable $e) {
            $message = Str::limit(trim($e->getMessage()) !== '' ? $e->getMessage() : get_class($e), self::ERROR_LIMIT);

            Log::error('[OutreachEngine] Grid ' . $grid->id . ' failed - ' . $message);

            // Whatever went wrong, the row must not be left stuck on 'processing' or the
            // cron will never look at it again. A second failure while writing that (a
            // dropped DB connection, say) is logged and swallowed - the caller still
            // gets a truthful result array.
            try {
                $grid->update([
                    'status' => OutreachSearchGrid::STATUS_FAILED,
                    'resultsCount' => $totalResults,
                    'newLeadsCount' => $newLeads,
                    'pageToken' => null,
                    'lastError' => $message,
                    'processedAt' => Carbon::now('Asia/Manila'),
                ]);
            } catch (\Throwable $inner) {
                Log::error('[OutreachEngine] Could not mark grid ' . $grid->id . ' as failed - ' . $inner->getMessage());
            }

            return [
                'results' => $totalResults,
                'new' => $newLeads,
                'split' => false,
                'sparse' => false,
                'error' => $message,
            ];
        }
    }

    /**
     * Tile a region into pending grid cells and return the batch id (a plain uuid).
     *
     * @throws OutreachException when the region is unknown or produces no cells.
     */
    public function queueRegion(int $userId, string $businessType, string $regionLabel, float $radiusKm): string
    {
        $businessType = trim($businessType);
        $regionLabel = trim($regionLabel);

        if ($businessType === '') {
            throw new OutreachException('Tell us what kind of business to look for, for example "resort" or "dental clinic".');
        }

        if ($regionLabel === '') {
            throw new OutreachException('Choose a region to search.');
        }

        try {
            $bounds = $this->geo->boundsForRegion($regionLabel);
        } catch (\Throwable $e) {
            Log::warning('[OutreachEngine] Region lookup failed for "' . $regionLabel . '" - ' . $e->getMessage());
            $bounds = null;
        }

        if (empty($bounds)) {
            throw new OutreachException('We have no map bounds for "' . $regionLabel . '". Pick a region from the list, or type a Philippine province name exactly as it is spelled.');
        }

        // Clamp before tiling: a radius under the configured floor would tile a
        // province into tens of thousands of cells and burn the Places quota.
        $minRadiusKm = max(0.1, (float) ($this->settings->minGridRadiusKm ?? 0.5));
        $radiusKm = round(max($minRadiusKm, min(self::MAX_QUEUE_RADIUS_KM, $radiusKm)), 3);

        try {
            $centers = $this->geo->buildGrid($bounds, $radiusKm);
        } catch (\Throwable $e) {
            Log::error('[OutreachEngine] Grid build failed for "' . $regionLabel . '" - ' . $e->getMessage());
            throw new OutreachException('We could not lay a search grid over "' . $regionLabel . '". Try a different radius.');
        }

        $batchId = (string) Str::uuid();
        $timestamp = Carbon::now('Asia/Manila')->format('Y-m-d H:i:s');
        $rows = [];

        foreach ((array) $centers as $center) {
            if (!is_array($center) || !isset($center['latitude'], $center['longitude'])) {
                continue;
            }

            $rows[] = [
                'usersId' => $userId,
                'batchId' => $batchId,
                'businessType' => mb_substr($businessType, 0, 190),
                'regionLabel' => mb_substr($regionLabel, 0, 190),
                'latitude' => round((float) $center['latitude'], 7),
                'longitude' => round((float) $center['longitude'], 7),
                'radiusKm' => round((float) ($center['radiusKm'] ?? $radiusKm), 3),
                'depth' => 0,
                'parentId' => null,
                'status' => OutreachSearchGrid::STATUS_PENDING,
                'resultsCount' => 0,
                'newLeadsCount' => 0,
                'attempts' => 0,
                'delete_status' => 'active',
                // Bulk insert bypasses Eloquent, so the timestamps are written by hand.
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        if (empty($rows)) {
            throw new OutreachException('That region and radius produced no search cells. Try a smaller radius.');
        }

        foreach (array_chunk($rows, self::INSERT_CHUNK) as $chunk) {
            OutreachSearchGrid::insert($chunk);
        }

        Log::info('[OutreachEngine] Queued ' . count($rows) . ' grid cells for "' . $regionLabel . '" (batch ' . $batchId . ')');

        return $batchId;
    }

    // ==================== INTERNALS ====================

    /**
     * Insert one Places result as a lead. Returns false when the row was skipped
     * or already existed - only a genuine insert counts as a new lead.
     */
    protected function createLead(OutreachSearchGrid $grid, array $place, string $placeId): bool
    {
        $name = trim((string) ($place['name'] ?? ''));

        // businessName is NOT NULL, and an unnamed listing is useless for outreach.
        if ($name === '') {
            return false;
        }

        // Nearby Search sends 'vicinity'; the text-search shaped payloads send
        // 'formatted_address'. Either one is what we parse the locality out of.
        $address = trim((string) ($place['vicinity'] ?? $place['formatted_address'] ?? ''));
        $locality = $this->parseLocality($address);

        $location = (isset($place['geometry']['location']) && is_array($place['geometry']['location']))
            ? $place['geometry']['location']
            : [];
        $types = (isset($place['types']) && is_array($place['types'])) ? array_values($place['types']) : [];

        try {
            OutreachLead::create([
                'usersId' => $grid->usersId,
                'batchId' => $grid->batchId,
                'gridId' => $grid->id,
                'placeId' => mb_substr($placeId, 0, 255),
                'businessName' => mb_substr($name, 0, 255),
                'category' => isset($types[0]) ? mb_substr((string) $types[0], 0, 190) : null,
                'address' => $address !== '' ? mb_substr($address, 0, 500) : null,
                'city' => $locality['city'],
                'province' => $locality['province'],
                'latitude' => isset($location['lat']) && is_numeric($location['lat'])
                    ? round((float) $location['lat'], 7)
                    : null,
                'longitude' => isset($location['lng']) && is_numeric($location['lng'])
                    ? round((float) $location['lng'], 7)
                    : null,
                'rating' => isset($place['rating']) && is_numeric($place['rating'])
                    ? round((float) $place['rating'], 2)
                    : null,
                'userRatingsTotal' => isset($place['user_ratings_total']) && is_numeric($place['user_ratings_total'])
                    ? (int) $place['user_ratings_total']
                    : null,
                'enrichmentStatus' => OutreachLead::ENRICHMENT_PENDING,
                'outreachStatus' => OutreachLead::OUTREACH_UNCONTACTED,
                'delete_status' => 'active',
            ]);

            return true;
        } catch (QueryException $e) {
            if ($this->isDuplicateKey($e)) {
                // Another sweep inserted this placeId between the exists() check and this
                // insert. Harmless: the lead exists, it just is not ours to count.
                return false;
            }

            throw $e;
        }
    }

    /**
     * Spawn the four half-radius children of a saturated cell.
     * Returns how many were actually created.
     */
    protected function createChildren(OutreachSearchGrid $grid, float $childRadiusKm): int
    {
        try {
            $centers = $this->geo->subdivide($grid);
        } catch (\Throwable $e) {
            Log::warning('[OutreachEngine] Could not subdivide grid ' . $grid->id . ' - ' . $e->getMessage());

            return 0;
        }

        $created = 0;

        foreach ((array) $centers as $center) {
            if (!is_array($center) || !isset($center['latitude'], $center['longitude'])) {
                continue;
            }

            OutreachSearchGrid::create([
                'usersId' => $grid->usersId,
                'batchId' => $grid->batchId,
                'businessType' => $grid->businessType,
                'regionLabel' => $grid->regionLabel,
                'latitude' => round((float) $center['latitude'], 7),
                'longitude' => round((float) $center['longitude'], 7),
                'radiusKm' => round((float) ($center['radiusKm'] ?? $childRadiusKm), 3),
                'depth' => (int) $grid->depth + 1,
                'parentId' => $grid->id,
                'status' => OutreachSearchGrid::STATUS_PENDING,
                'resultsCount' => 0,
                'newLeadsCount' => 0,
                'attempts' => 0,
                'delete_status' => 'active',
            ]);

            $created++;
        }

        if ($created === 0) {
            // No children means the cell stays 'completed' rather than 'split', so the
            // batch does not advertise work that was never queued.
            Log::warning('[OutreachEngine] Grid ' . $grid->id . ' hit saturation but produced no child cells.');
        }

        return $created;
    }

    /**
     * MySQL duplicate-entry check (error 1062) for the placeId unique index.
     */
    protected function isDuplicateKey(QueryException $e): bool
    {
        $driverCode = $e->errorInfo[1] ?? null;

        if ($driverCode !== null && (int) $driverCode === 1062) {
            return true;
        }

        return str_contains($e->getMessage(), 'Duplicate entry');
    }

    /**
     * Best-effort city/province from a Places address string.
     *
     * Deliberately conservative: anything the closed province list does not confirm
     * comes back NULL. A blank city is a cosmetic gap on the leads table, while a
     * wrong one poisons the filters, the CSV export and every {city} placeholder in
     * an outreach email.
     *
     * @return array ['city'=>?string,'province'=>?string]
     */
    protected function parseLocality(string $address): array
    {
        $locality = ['city' => null, 'province' => null];

        $address = trim($address);
        if ($address === '') {
            return $locality;
        }

        $segments = [];
        foreach (explode(',', $address) as $piece) {
            $piece = $this->cleanSegment($piece);
            if ($piece !== '') {
                $segments[] = $piece;
            }
        }

        if (empty($segments)) {
            return $locality;
        }

        $provinces = static::provinceLookup();

        // Walk from the tail: the province sits at the end of a Philippine address,
        // and taking the LAST match stops a street or subdivision named after a
        // province ("Rizal Street, Vigan") from claiming the slot.
        for ($i = count($segments) - 1; $i >= 0; $i--) {
            $key = static::normalizeName($segments[$i]);

            if (!isset($provinces[$key])) {
                continue;
            }

            $locality['province'] = $provinces[$key];

            // The segment immediately before the province is the city or municipality -
            // unless it reads like a street or a barangay, in which case the address
            // simply does not carry one.
            if ($i > 0 && $this->looksLikeLocality($segments[$i - 1])) {
                $locality['city'] = mb_substr($segments[$i - 1], 0, 190);
            }

            return $locality;
        }

        // No province we recognise. A single-segment vicinity ("Bauang") is almost
        // always the town itself; anything longer is guesswork, so it stays NULL.
        if (count($segments) === 1 && $this->looksLikeLocality($segments[0])) {
            $locality['city'] = mb_substr($segments[0], 0, 190);
        }

        return $locality;
    }

    /**
     * Tidy one comma-separated address segment, or return '' to drop it.
     */
    protected function cleanSegment(string $segment): string
    {
        $segment = trim($segment);

        // PH postcodes ride inside a segment ("2500 La Union"), so strip four-digit
        // runs before the name is compared against the province list.
        $segment = (string) preg_replace('/\b\d{4}\b/', ' ', $segment);
        $segment = trim((string) preg_replace('/\s+/', ' ', $segment));

        if ($segment === '') {
            return '';
        }

        $normalized = static::normalizeName($segment);

        if (in_array($normalized, ['philippines', 'pilipinas', 'ph', 'republic of the philippines'], true)) {
            return '';
        }

        return $segment;
    }

    /**
     * Could this segment be a city or municipality name?
     */
    protected function looksLikeLocality(string $segment): bool
    {
        $length = mb_strlen($segment);

        if ($length < 3 || $length > 120) {
            return false;
        }

        // House and building numbers mean a street line, never a city.
        if (preg_match('/\d/', $segment)) {
            return false;
        }

        // Street lines and barangays sit above the city in an address, so a segment
        // that reads like one is not the city we are after.
        if (preg_match('/\b(st|street|ave|avenue|rd|road|blvd|boulevard|hwy|highway|drive|lane|purok|sitio|brgy|barangay|zone|bldg|building|floor|unit|room|km|corner|cor|extension|ext)\b/i', $segment)) {
            return false;
        }

        return true;
    }

    /**
     * Normalised name => canonical province, built once per process.
     */
    protected static function provinceLookup(): array
    {
        if (self::$provinceLookup !== null) {
            return self::$provinceLookup;
        }

        $lookup = [];

        foreach (self::PROVINCE_NAMES as $name) {
            $lookup[static::normalizeName($name)] = $name;
        }

        foreach (self::PROVINCE_ALIASES as $alias => $name) {
            $lookup[static::normalizeName($alias)] = $name;
        }

        return self::$provinceLookup = $lookup;
    }

    /**
     * Lowercase, punctuation-free form used for province matching.
     */
    protected static function normalizeName(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = str_replace(['.', "'", '`'], '', $value);
        $value = (string) preg_replace('/^province of\s+/', '', $value);
        $value = (string) preg_replace('/\s+province$/', '', $value);
        $value = (string) preg_replace('/\s+/', ' ', $value);

        return trim($value);
    }
}
