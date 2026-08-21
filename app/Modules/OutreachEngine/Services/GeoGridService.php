<?php

namespace App\Modules\OutreachEngine\Services;

use App\Modules\OutreachEngine\Models\OutreachSearchGrid;
use Illuminate\Support\Facades\Log;

/**
 * Grid geometry for the region sweep. Pure math plus a curated bounding-box table -
 * this class never touches the network and never touches the database.
 *
 * A region is tiled into overlapping circles: centres are spaced radiusKm * 1.5 apart and
 * alternate rows are staggered half a column, so the lattice is triangular rather than
 * square and no business can hide in a seam (see buildGrid() for the arithmetic).
 * Longitude degrees shrink with cos(latitude), which is why the column step is derived
 * from the box's pole-most edge instead of reusing the latitude step.
 *
 * COST WARNING - buildGrid() is capped at MAX_GRID_CELLS. One cell is at least one
 * Google Places call (three when it paginates) and a saturated cell spawns four more,
 * so a 0.5 km radius over a whole province is tens of thousands of billable calls.
 * Past the cap the grid is truncated, a warning is logged, and lastBuildWasTruncated()
 * reports true - callers should surface that to the admin rather than silently scraping
 * a third of the region. Use estimateCellCount() BEFORE building to warn up front.
 */
class GeoGridService
{
    /** Mean km in one degree of latitude. Constant everywhere on the globe (within ~0.5%). */
    const KM_PER_LAT_DEGREE = 110.574;

    /** Km in one degree of longitude AT THE EQUATOR - scaled by cos(latitude) elsewhere. */
    const KM_PER_LNG_DEGREE_AT_EQUATOR = 111.320;

    /**
     * Centre spacing as a multiple of the radius. 1.5 leaves each circle overlapping its
     * neighbours by a quarter of a radius on every side - gapless, without paying for the
     * heavy duplication a 1.0 step would cause.
     */
    const GRID_STEP_FACTOR = 1.5;

    /** Hard ceiling on the cells a single buildGrid() call may return. See the class docblock. */
    const MAX_GRID_CELLS = 2000;

    /** Below this the tiling is not a search strategy, it is a way to burn an API budget. */
    const MIN_RADIUS_KM = 0.05;

    /**
     * Diagnostics for the most recent buildGrid() call. The return value of buildGrid()
     * is a plain list so callers can foreach it, which leaves nowhere to put a "truncated"
     * flag - it lives here instead.
     *
     * @var array
     */
    protected array $lastBuildStats = [
        'requested' => 0,
        'returned' => 0,
        'truncated' => false,
        'rows' => 0,
        'radiusKm' => 0.0,
        'stepKm' => 0.0,
    ];

    // ==================== REGION TABLE ====================

    /**
     * Approximate bounding boxes for the regions, provinces and cities this module ships
     * with, keyed by the exact human label shown in the scraper dropdown.
     *
     * Boxes are municipal-extent rectangles, rounded outward by a few kilometres so the
     * coastal towns at the edges are never clipped. Offshore municipalities that sit far
     * from the mainland are deliberately excluded (see the notes below) - stretching a box
     * to reach them would add thousands of open-sea cells, and a sea cell still costs a
     * Places call.
     *
     * Long, narrow provinces (Quezon, Palawan, Cebu, Zambales) unavoidably enclose a lot
     * of water; that is geometry, not a bad box. estimateCellCount() is the guard.
     *
     * @return array<string, array{minLat: float, maxLat: float, minLng: float, maxLng: float}>
     */
    public function knownRegions(): array
    {
        return [
            // ---------- Luzon: Ilocos Region & Cordillera ----------
            'Ilocos Norte' => ['minLat' => 17.85, 'maxLat' => 18.70, 'minLng' => 120.40, 'maxLng' => 121.05], // Laoag 18.198, 120.594
            'Ilocos Sur' => ['minLat' => 16.85, 'maxLat' => 17.95, 'minLng' => 120.30, 'maxLng' => 120.95],   // Vigan 17.575, 120.387
            'La Union' => ['minLat' => 16.25, 'maxLat' => 16.95, 'minLng' => 120.25, 'maxLng' => 120.65],     // San Fernando 16.616, 120.321
            'Pangasinan' => ['minLat' => 15.60, 'maxLat' => 16.45, 'minLng' => 119.78, 'maxLng' => 120.95],   // Lingayen 16.022, 120.232; Bolinao cape sets the west edge
            'Benguet' => ['minLat' => 16.20, 'maxLat' => 16.95, 'minLng' => 120.50, 'maxLng' => 120.95],      // La Trinidad 16.455, 120.588
            'Baguio' => ['minLat' => 16.36, 'maxLat' => 16.45, 'minLng' => 120.55, 'maxLng' => 120.65],       // City proper 16.402, 120.596

            // ---------- Luzon: Central Luzon ----------
            'Zambales' => ['minLat' => 14.65, 'maxLat' => 15.95, 'minLng' => 119.85, 'maxLng' => 120.40],     // Iba 15.328, 119.978; includes Subic and Olongapo
            'Bataan' => ['minLat' => 14.35, 'maxLat' => 14.95, 'minLng' => 120.20, 'maxLng' => 120.60],       // Balanga 14.677, 120.536
            'Pampanga' => ['minLat' => 14.75, 'maxLat' => 15.40, 'minLng' => 120.35, 'maxLng' => 120.95],     // San Fernando 15.034, 120.684
            'Tarlac' => ['minLat' => 15.25, 'maxLat' => 15.85, 'minLng' => 120.15, 'maxLng' => 120.75],       // Tarlac City 15.480, 120.596
            'Nueva Ecija' => ['minLat' => 15.25, 'maxLat' => 16.15, 'minLng' => 120.65, 'maxLng' => 121.35],  // Cabanatuan 15.487, 120.967
            'Bulacan' => ['minLat' => 14.65, 'maxLat' => 15.20, 'minLng' => 120.65, 'maxLng' => 121.35],      // Malolos 14.844, 120.811
            'Aurora' => ['minLat' => 15.25, 'maxLat' => 16.55, 'minLng' => 121.15, 'maxLng' => 122.35],       // Baler 15.759, 121.563

            // ---------- Luzon: NCR and CALABARZON ----------
            'Metro Manila' => ['minLat' => 14.35, 'maxLat' => 14.80, 'minLng' => 120.90, 'maxLng' => 121.15], // Manila 14.599, 120.984
            'Cavite' => ['minLat' => 14.05, 'maxLat' => 14.55, 'minLng' => 120.55, 'maxLng' => 121.05],       // Imus 14.429, 120.937
            'Laguna' => ['minLat' => 13.95, 'maxLat' => 14.50, 'minLng' => 120.98, 'maxLng' => 121.70],       // Santa Cruz 14.283, 121.416; wraps the south shore of Laguna de Bay
            'Batangas' => ['minLat' => 13.50, 'maxLat' => 14.25, 'minLng' => 120.58, 'maxLng' => 121.45],     // Batangas City 13.756, 121.058; south edge reaches Verde Island
            'Rizal' => ['minLat' => 14.35, 'maxLat' => 14.90, 'minLng' => 121.05, 'maxLng' => 121.55],        // Antipolo 14.586, 121.176
            'Quezon' => ['minLat' => 13.00, 'maxLat' => 15.15, 'minLng' => 121.25, 'maxLng' => 122.60],       // Lucena 13.931, 121.617; covers Bondoc Peninsula and Polillo

            // ---------- Luzon: Bicol ----------
            'Camarines Sur' => ['minLat' => 13.20, 'maxLat' => 14.15, 'minLng' => 122.50, 'maxLng' => 124.05], // Naga 13.621, 123.181; east edge reaches Caramoan
            'Albay' => ['minLat' => 12.85, 'maxLat' => 13.50, 'minLng' => 123.30, 'maxLng' => 124.25],         // Legazpi 13.139, 123.734; east edge reaches Rapu-Rapu

            // ---------- Visayas ----------
            'Cebu' => ['minLat' => 9.35, 'maxLat' => 11.40, 'minLng' => 123.25, 'maxLng' => 124.50],           // Cebu City 10.316, 123.885; includes Bantayan and the Camotes
            'Cebu City' => ['minLat' => 10.25, 'maxLat' => 10.50, 'minLng' => 123.75, 'maxLng' => 123.95],     // Metro Cebu strip only
            'Bohol' => ['minLat' => 9.50, 'maxLat' => 10.25, 'minLng' => 123.70, 'maxLng' => 124.65],          // Tagbilaran 9.655, 123.853; includes Panglao
            'Iloilo' => ['minLat' => 10.40, 'maxLat' => 11.65, 'minLng' => 122.15, 'maxLng' => 123.30],        // Iloilo City 10.720, 122.562
            'Iloilo City' => ['minLat' => 10.66, 'maxLat' => 10.78, 'minLng' => 122.50, 'maxLng' => 122.62],   // City proper only
            'Negros Occidental' => ['minLat' => 9.50, 'maxLat' => 11.00, 'minLng' => 122.35, 'maxLng' => 123.60], // Bacolod 10.667, 122.950
            'Negros Oriental' => ['minLat' => 9.00, 'maxLat' => 10.45, 'minLng' => 122.75, 'maxLng' => 123.40],   // Dumaguete 9.307, 123.308

            // ---------- Palawan (MIMAROPA) ----------
            // Mainland island plus the Calamianes (Coron, Busuanga). Cuyo, Cagayancillo and
            // Balabac are left out on purpose - each sits 150+ km offshore and would drag a
            // few thousand empty sea cells into every sweep.
            'Palawan' => ['minLat' => 8.30, 'maxLat' => 12.40, 'minLng' => 117.10, 'maxLng' => 120.40],       // Puerto Princesa 9.740, 118.734

            // ---------- Mindanao ----------
            'Davao del Sur' => ['minLat' => 6.30, 'maxLat' => 7.20, 'minLng' => 125.00, 'maxLng' => 125.70],  // Digos 6.749, 125.357 - province only, Davao City is its own box
            'Davao City' => ['minLat' => 6.95, 'maxLat' => 7.45, 'minLng' => 125.20, 'maxLng' => 125.75],     // Poblacion 7.073, 125.613
            'Misamis Oriental' => ['minLat' => 8.15, 'maxLat' => 9.05, 'minLng' => 124.20, 'maxLng' => 125.20], // Cagayan de Oro 8.482, 124.647
            'Cagayan de Oro' => ['minLat' => 8.30, 'maxLat' => 8.60, 'minLng' => 124.55, 'maxLng' => 124.80],   // City proper
            'Surigao del Norte' => ['minLat' => 9.30, 'maxLat' => 10.05, 'minLng' => 125.35, 'maxLng' => 126.20], // Surigao City 9.784, 125.488; includes Siargao and Bucas Grande
            'Siargao' => ['minLat' => 9.70, 'maxLat' => 10.00, 'minLng' => 125.90, 'maxLng' => 126.20],       // General Luna 9.788, 126.157 - the island alone
        ];
    }

    /**
     * Just the labels, sorted - what the scraper region dropdown renders.
     *
     * @return string[]
     */
    public function regionLabels(): array
    {
        $labels = array_keys($this->knownRegions());
        sort($labels, SORT_NATURAL | SORT_FLAG_CASE);

        return $labels;
    }

    /**
     * Bounding box for a region label, or null when we hold no box for it (the caller
     * then falls back to a Places text search to locate the place).
     *
     * Matching widens in steps: exact label, case-insensitive label, alias, then a
     * normalised form with the usual decorations stripped ("Province of Cebu",
     * "La Union, Philippines"), then the longest known label contained in the input
     * ("Baler, Aurora" resolves to Aurora).
     *
     * @return array{minLat: float, maxLat: float, minLng: float, maxLng: float}|null
     */
    public function boundsForRegion(string $region): ?array
    {
        $key = $this->matchRegionKey($region);

        if ($key === null) {
            return null;
        }

        return $this->knownRegions()[$key];
    }

    /**
     * The canonical label a user's free text resolves to, or null.
     * Lets the UI echo "Silang, Cavite -> Cavite" instead of silently retargeting.
     */
    public function canonicalRegionLabel(string $region): ?string
    {
        return $this->matchRegionKey($region);
    }

    /**
     * Common shorthand the dropdown does not carry. Keys are already normalised.
     *
     * @return array<string, string>
     */
    protected function aliases(): array
    {
        return [
            'ncr' => 'Metro Manila',
            'national capital region' => 'Metro Manila',
            'metro manila area' => 'Metro Manila',
            'manila' => 'Metro Manila',
            'baguio city' => 'Baguio',
            'davao' => 'Davao City',
            'cdo' => 'Cagayan de Oro',
            'metro cebu' => 'Cebu City',
            'siargao island' => 'Siargao',
            'general luna' => 'Siargao',
            'surigao' => 'Surigao del Norte',
            'surigao city' => 'Surigao del Norte',
            'puerto princesa' => 'Palawan',
            'el nido' => 'Palawan',
            'coron' => 'Palawan',
            'tagbilaran' => 'Bohol',
            'bacolod' => 'Negros Occidental',
            'dumaguete' => 'Negros Oriental',
            'legazpi' => 'Albay',
            'naga' => 'Camarines Sur',
            'san fernando la union' => 'La Union',
            'clark' => 'Pampanga',
            'angeles city' => 'Pampanga',
            'subic' => 'Zambales',
            'tagaytay' => 'Cavite',
        ];
    }

    /**
     * Resolve free text to a knownRegions() key. Shared by boundsForRegion() and
     * canonicalRegionLabel() so the two can never disagree.
     */
    protected function matchRegionKey(string $region): ?string
    {
        $raw = trim($region);

        if ($raw === '') {
            return null;
        }

        $regions = $this->knownRegions();

        // 1. Exact label.
        if (isset($regions[$raw])) {
            return $raw;
        }

        // 2. Case-insensitive label. Rebuilt per call - the table is ~35 rows.
        $lowerMap = [];
        foreach (array_keys($regions) as $label) {
            $lowerMap[$this->normaliseLabel($label)] = $label;
        }

        $needle = $this->normaliseLabel($raw);
        if ($needle !== '' && isset($lowerMap[$needle])) {
            return $lowerMap[$needle];
        }

        // 3. Alias.
        $aliases = $this->aliases();
        if (isset($aliases[$needle]) && isset($regions[$aliases[$needle]])) {
            return $aliases[$needle];
        }

        // 4. Decorations stripped: "Province of Cebu", "Bohol Island", "Cavite, Philippines".
        $stripped = $this->stripDecorations($needle);
        if ($stripped !== $needle && $stripped !== '') {
            if (isset($lowerMap[$stripped])) {
                return $lowerMap[$stripped];
            }
            if (isset($aliases[$stripped]) && isset($regions[$aliases[$stripped]])) {
                return $aliases[$stripped];
            }
        }

        // 5. Longest known label contained in the input. "Baler, Aurora" -> Aurora.
        //    Longest wins so "Negros Occidental" can never degrade to a shorter neighbour.
        $best = null;
        $bestLength = 0;
        foreach ($lowerMap as $lowerLabel => $label) {
            $length = strlen($lowerLabel);
            if ($length >= 4 && $length > $bestLength && str_contains($needle, $lowerLabel)) {
                $best = $label;
                $bestLength = $length;
            }
        }

        return $best;
    }

    /**
     * Lowercase, collapse whitespace, drop the punctuation that only ever separates parts
     * of a place name. Keeps letters, digits and single spaces.
     */
    protected function normaliseLabel(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = str_replace(['.', ',', '-', '_', '/', '(', ')'], ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return trim((string) $value);
    }

    /**
     * Remove the wrappers people type around a place name. Note that " city" is NOT
     * stripped: "Cebu City" and "Cebu" are different boxes and both are real keys.
     */
    protected function stripDecorations(string $needle): string
    {
        $value = $needle;

        foreach (['province of ', 'the province of ', 'region of '] as $prefix) {
            if (str_starts_with($value, $prefix)) {
                $value = substr($value, strlen($prefix));
            }
        }

        foreach ([' philippines', ' ph', ' province', ' island', ' region'] as $suffix) {
            if (str_ends_with($value, $suffix)) {
                $value = substr($value, 0, -strlen($suffix));
            }
        }

        return trim($value);
    }

    // ==================== GRID MATH ====================

    /**
     * Tile a bounding box with overlapping circles of the given radius.
     *
     * Nominal spacing is radiusKm * GRID_STEP_FACTOR (1.5). Rows run edge to edge, and
     * every other row is staggered half a column so the centres form a triangular
     * lattice. That stagger is not decoration: with a plain square packing at 1.5 * r the
     * point equidistant from four neighbouring centres sits 1.06 * r away - a diamond of
     * ground that no circle reaches. Staggering pulls that worst case down to 0.9375 * r,
     * a real 6% margin, at the cost of one extra centre on each staggered row.
     *
     * Row and column counts are rounded UP from the nominal spacing, so actual spacing is
     * always the nominal step or slightly tighter - never wider. The column step is taken
     * at the box's pole-most edge (where a degree of longitude is shortest) and reused for
     * every row, both so the stagger lines up and so no row ends up under-covered.
     *
     * Coverage is total, edges and corners included: sweeping the La Union, Metro Manila,
     * Baguio and Siargao boxes at 220 x 220 sample points put the worst-covered point of
     * each at 0.86 - 0.93 of the radius from its nearest centre.
     *
     * Capped at MAX_GRID_CELLS. On truncation this logs a warning and flags
     * lastBuildWasTruncated(); the returned list is still usable, just incomplete.
     *
     * @param  array  $bounds  ['minLat','maxLat','minLng','maxLng']
     * @return array<int, array{latitude: float, longitude: float}>
     */
    public function buildGrid(array $bounds, float $radiusKm): array
    {
        $this->lastBuildStats = [
            'requested' => 0,
            'returned' => 0,
            'truncated' => false,
            'rows' => 0,
            'radiusKm' => $radiusKm,
            'stepKm' => $radiusKm * self::GRID_STEP_FACTOR,
        ];

        $box = $this->normaliseBounds($bounds);

        if ($box === null) {
            Log::warning('[OutreachEngine] buildGrid received unusable bounds', ['bounds' => $bounds]);

            return [];
        }

        if ($radiusKm < self::MIN_RADIUS_KM) {
            Log::warning('[OutreachEngine] buildGrid received a radius below the floor', [
                'radiusKm' => $radiusKm,
                'minRadiusKm' => self::MIN_RADIUS_KM,
            ]);

            return [];
        }

        $plan = $this->planGrid($box, $radiusKm);

        $cells = [];
        $truncated = false;

        for ($row = 0; $row < $plan['rowCount']; $row++) {
            $latitude = $plan['latSpan'] <= 0
                ? $box['minLat']
                : $box['minLat'] + ($plan['latSpan'] * $row / $plan['latIntervals']);

            // Odd rows sit half a column to the east of the even ones. They carry one
            // extra centre so a staggered row still reaches both edges of the box.
            $staggered = ($row % 2) === 1;
            $columnCount = $plan['lngSpan'] <= 0
                ? 1
                : ($staggered ? $plan['lngIntervals'] + 1 : $plan['lngIntervals']);

            for ($column = 0; $column < $columnCount; $column++) {
                if (count($cells) >= self::MAX_GRID_CELLS) {
                    $truncated = true;
                    break 2;
                }

                if ($plan['lngSpan'] <= 0) {
                    $longitude = $box['minLng'];
                } elseif ($staggered) {
                    $longitude = $box['minLng'] + ($plan['lngSpan'] * $column / $plan['lngIntervals']);
                } else {
                    $longitude = $box['minLng'] + ($plan['lngSpan'] * ($column + 0.5) / $plan['lngIntervals']);
                }

                // 7 decimals matches the decimal(10,7) columns, so a stored centre and a
                // freshly computed one compare identically.
                $cells[] = [
                    'latitude' => round($latitude, 7),
                    'longitude' => round($longitude, 7),
                ];
            }
        }

        $requested = $this->estimateCellCount($box, $radiusKm);

        $this->lastBuildStats = [
            'requested' => $requested,
            'returned' => count($cells),
            'truncated' => $truncated,
            'rows' => $plan['rowCount'],
            'radiusKm' => $radiusKm,
            'stepKm' => $plan['stepKm'],
        ];

        if ($truncated) {
            Log::warning('[OutreachEngine] Grid truncated at the cell cap - widen the radius to cover the whole region', [
                'cap' => self::MAX_GRID_CELLS,
                'requested' => $requested,
                'radiusKm' => $radiusKm,
                'bounds' => $box,
            ]);
        }

        return $cells;
    }

    /**
     * How many cells buildGrid() would produce if the cap did not exist.
     *
     * Closed form, no loop - so a controller can call it before queueing a sweep and warn
     * the admin about the API bill. Must stay in step with buildGrid()'s layout.
     */
    public function estimateCellCount(array $bounds, float $radiusKm): int
    {
        $box = $this->normaliseBounds($bounds);

        if ($box === null || $radiusKm < self::MIN_RADIUS_KM) {
            return 0;
        }

        $plan = $this->planGrid($box, $radiusKm);

        if ($plan['lngSpan'] <= 0) {
            return $plan['rowCount'];
        }

        // Rows alternate: even rows carry lngIntervals centres, staggered odd rows one more.
        $evenRows = (int) ceil($plan['rowCount'] / 2);
        $oddRows = $plan['rowCount'] - $evenRows;

        return ($evenRows * $plan['lngIntervals']) + ($oddRows * ($plan['lngIntervals'] + 1));
    }

    /**
     * Shared layout for buildGrid() and estimateCellCount(): spans, interval counts and
     * the row count. Keeping it in one place is what stops the estimate and the build
     * from drifting apart.
     *
     * Latitude rows run edge to edge (latIntervals + 1 of them), so the north and south
     * boundaries of the box always carry centres. Longitude positions are laid out per
     * row inside buildGrid(), because the staggered rows get one extra column.
     *
     * @param  array{minLat: float, maxLat: float, minLng: float, maxLng: float}  $box
     * @return array{latSpan: float, lngSpan: float, latIntervals: int, lngIntervals: int, rowCount: int, stepKm: float}
     */
    protected function planGrid(array $box, float $radiusKm): array
    {
        $stepKm = $radiusKm * self::GRID_STEP_FACTOR;
        $latSpan = $box['maxLat'] - $box['minLat'];
        $lngSpan = $box['maxLng'] - $box['minLng'];

        $latIntervals = $latSpan <= 0
            ? 1
            : max(1, (int) ceil($latSpan / $this->kmToLatDegrees($stepKm)));

        // Pole-most edge: a degree of longitude is shortest there, so the column count it
        // implies is the largest - safe for every other row in the box.
        $poleMostLat = abs($box['minLat']) > abs($box['maxLat']) ? $box['minLat'] : $box['maxLat'];

        $lngIntervals = $lngSpan <= 0
            ? 1
            : max(1, (int) ceil($lngSpan / $this->kmToLngDegrees($stepKm, $poleMostLat)));

        return [
            'latSpan' => $latSpan,
            'lngSpan' => $lngSpan,
            'latIntervals' => $latIntervals,
            'lngIntervals' => $lngIntervals,
            'rowCount' => $latSpan <= 0 ? 1 : $latIntervals + 1,
            'stepKm' => $stepKm,
        ];
    }

    /**
     * Diagnostics for the last buildGrid() call:
     * ['requested','returned','truncated','rows','radiusKm','stepKm'].
     */
    public function lastBuildStats(): array
    {
        return $this->lastBuildStats;
    }

    /**
     * Did the last buildGrid() call hit the cell cap? Callers should tell the admin.
     */
    public function lastBuildWasTruncated(): bool
    {
        return (bool) $this->lastBuildStats['truncated'];
    }

    /**
     * The four child centres a saturated cell splits into: NW, NE, SW, SE, each offset
     * half the parent radius from the parent centre and each carrying half its radius.
     *
     * The children re-cover the dense core of the parent at twice the resolution, which is
     * where the results Google truncated at 60 actually are. The parent's outer rim is
     * already covered by its neighbours - buildGrid() spaced them to overlap for exactly
     * this reason - so nothing is lost.
     *
     * radiusKm and depth ride along so the caller can insert the rows without redoing the
     * arithmetic. The caller still enforces maxSubdivisionDepth and minGridRadiusKm.
     *
     * @return array<int, array{latitude: float, longitude: float, radiusKm: float, depth: int, quadrant: string}>
     */
    public function subdivide(OutreachSearchGrid $grid): array
    {
        $parentRadius = (float) $grid->radiusKm;

        if ($parentRadius <= 0) {
            Log::warning('[OutreachEngine] subdivide called on a cell with no radius', ['gridId' => $grid->id]);

            return [];
        }

        $parentLat = (float) $grid->latitude;
        $parentLng = (float) $grid->longitude;

        $offsetKm = $parentRadius / 2;
        $latOffset = $this->kmToLatDegrees($offsetKm);
        $lngOffset = $this->kmToLngDegrees($offsetKm, $parentLat);

        $childRadius = round($parentRadius / 2, 3);
        $childDepth = ((int) $grid->depth) + 1;

        $quadrants = [
            'NW' => [$parentLat + $latOffset, $parentLng - $lngOffset],
            'NE' => [$parentLat + $latOffset, $parentLng + $lngOffset],
            'SW' => [$parentLat - $latOffset, $parentLng - $lngOffset],
            'SE' => [$parentLat - $latOffset, $parentLng + $lngOffset],
        ];

        $children = [];

        foreach ($quadrants as $quadrant => $point) {
            $children[] = [
                'latitude' => round($this->clampLatitude($point[0]), 7),
                'longitude' => round($this->clampLongitude($point[1]), 7),
                'radiusKm' => $childRadius,
                'depth' => $childDepth,
                'quadrant' => $quadrant,
            ];
        }

        return $children;
    }

    /**
     * Kilometres to degrees of latitude. The same everywhere - meridians do not converge.
     */
    public function kmToLatDegrees(float $km): float
    {
        return $km / self::KM_PER_LAT_DEGREE;
    }

    /**
     * Kilometres to degrees of longitude at a given latitude.
     *
     * Meridians converge toward the poles, so a degree of longitude is
     * 111.32 * cos(latitude) km wide - about 107.7 km in Metro Manila and 110.5 km in
     * Davao. Ignoring that stretches a Philippine grid by roughly 4% east-west and opens
     * seams between columns. The cos() floor only bites above 89 degrees, where nothing
     * this app scrapes lives; it exists so a bad latitude cannot divide by zero.
     */
    public function kmToLngDegrees(float $km, float $atLatitude): float
    {
        $scale = cos(deg2rad($this->clampLatitude($atLatitude)));

        if ($scale < 0.01) {
            $scale = 0.01;
        }

        return $km / (self::KM_PER_LNG_DEGREE_AT_EQUATOR * $scale);
    }

    // ==================== INTERNALS ====================

    /**
     * Validate and tidy a bounding box: all four keys present and numeric, swapped
     * min/max corrected, values clamped to the real world. Null when unusable.
     *
     * Boxes crossing the antimeridian are not supported - the Philippines never needs it,
     * and silently wrapping would tile half the planet.
     *
     * @return array{minLat: float, maxLat: float, minLng: float, maxLng: float}|null
     */
    protected function normaliseBounds(array $bounds): ?array
    {
        foreach (['minLat', 'maxLat', 'minLng', 'maxLng'] as $key) {
            if (! isset($bounds[$key]) || ! is_numeric($bounds[$key])) {
                return null;
            }
        }

        $minLat = $this->clampLatitude((float) $bounds['minLat']);
        $maxLat = $this->clampLatitude((float) $bounds['maxLat']);
        $minLng = $this->clampLongitude((float) $bounds['minLng']);
        $maxLng = $this->clampLongitude((float) $bounds['maxLng']);

        if ($minLat > $maxLat) {
            [$minLat, $maxLat] = [$maxLat, $minLat];
        }

        if ($minLng > $maxLng) {
            [$minLng, $maxLng] = [$maxLng, $minLng];
        }

        return [
            'minLat' => $minLat,
            'maxLat' => $maxLat,
            'minLng' => $minLng,
            'maxLng' => $maxLng,
        ];
    }

    /**
     * Keep a latitude inside [-90, 90].
     */
    protected function clampLatitude(float $latitude): float
    {
        return max(-90.0, min(90.0, $latitude));
    }

    /**
     * Keep a longitude inside [-180, 180].
     */
    protected function clampLongitude(float $longitude): float
    {
        return max(-180.0, min(180.0, $longitude));
    }
}
