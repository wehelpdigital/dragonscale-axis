<?php

namespace App\Http\Controllers\resortGuruAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;

class RgReviewsController extends Controller
{
    public function index(Request $request)
    {
        if (!Schema::hasTable('rg_destination_reviews')) {
            return view('resortGuruAdmin.pending', ['title' => 'Reviews']);
        }

        if ($request->ajax()) {
            $rows = DB::table('rg_destination_reviews as r')
                ->leftJoin('rg_keywords as k', 'k.id', '=', 'r.keyword_id')
                ->select('r.*', 'k.phrase as keyword_phrase');

            return DataTables::of($rows)
                ->addColumn('keyword', fn($r) => $r->keyword_phrase ?: '— Global —')
                ->addColumn('stars', function ($r) {
                    return str_repeat('★', (int) $r->rating) . str_repeat('☆', 5 - (int) $r->rating);
                })
                ->addColumn('snippet', fn($r) => e(\Illuminate\Support\Str::limit($r->review_text, 80)))
                ->addColumn('status_pill', function ($r) {
                    $cls = $r->status === 'published' ? 'success' : 'secondary';
                    return '<span class="badge bg-' . $cls . '">' . ucfirst($r->status) . '</span>';
                })
                ->addColumn('featured_pill', fn($r) => $r->is_featured ? '<span class="badge bg-warning text-dark">Featured</span>' : '')
                ->addColumn('actions', function ($r) {
                    return '<div class="d-flex gap-1">'
                        . '<a href="/resort-guru-reviews-edit?id=' . $r->id . '" class="btn btn-sm btn-info"><i class="bx bx-edit"></i></a>'
                        . '<button onclick="deleteReview(' . $r->id . ')" class="btn btn-sm btn-danger"><i class="bx bx-trash"></i></button>'
                        . '</div>';
                })
                ->rawColumns(['status_pill', 'featured_pill', 'actions'])
                ->make(true);
        }

        return view('resortGuruAdmin.reviews-index');
    }

    public function create()
    {
        return view('resortGuruAdmin.reviews-form', [
            'review' => null,
            'keywords' => $this->keywordOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['created_at'] = now();
        $data['updated_at'] = now();
        DB::table('rg_destination_reviews')->insert($data);
        return redirect('/resort-guru-reviews')->with('success', 'Review added.');
    }

    public function edit(Request $request)
    {
        $review = DB::table('rg_destination_reviews')->where('id', (int) $request->input('id'))->first();
        if (!$review) abort(404);
        return view('resortGuruAdmin.reviews-form', [
            'review' => $review,
            'keywords' => $this->keywordOptions(),
        ]);
    }

    public function update(Request $request)
    {
        $id = (int) $request->input('id');
        $data = $this->validateData($request);
        $data['updated_at'] = now();
        DB::table('rg_destination_reviews')->where('id', $id)->update($data);
        return redirect('/resort-guru-reviews')->with('success', 'Review updated.');
    }

    public function destroy(Request $request)
    {
        $id = (int) $request->input('id');
        DB::table('rg_destination_reviews')->where('id', $id)->delete();
        return response()->json(['ok' => true]);
    }

    /**
     * Generate a batch of plausible positive reviews across all published keywords.
     * Reviews are written as natural guest comments: they mention the destination
     * by NAME (Iloilo, Tagaytay) rather than the SEO phrase ("resort in iloilo"),
     * and they reference a real spot + a real food from destinations.php so the
     * text feels grounded in an actual stay.
     */
    public function generate(Request $request)
    {
        $perKeyword = max(1, min(8, (int) $request->input('per_keyword', 4)));
        $clear = $request->boolean('clear_existing');

        if ($clear) {
            DB::table('rg_destination_reviews')->where('status', 'published')->delete();
        }

        // Frontend project owns destinations.php; the mother app reads from a
        // shared path. Adjust if the path moves.
        $destFile = base_path('../resortguruph/database/data/destinations.php');
        $destinations = is_file($destFile) ? require $destFile : [];
        $slugMap = $this->slugToDestinationKey();

        $keywords = DB::table('rg_keywords')->where('status', 'active')->get(['id', 'phrase', 'slug', 'cluster_tag']);

        $reviewerNames = [
            'Jonathan Cruz', 'Hannah Reyes', 'Patricia delos Santos', 'Mark Anthony Lim',
            'Joan Villaruel', 'Carlo Mendoza', 'Aileen Bautista', 'Renzo Aquino',
            'Sheryl Magno', 'Jessa Ramirez', 'Daniel Pascual', 'Rina Sandoval',
            'Bryan Tan', 'Carmela Yulo', 'Aldous Cabrera', 'Ynna Domingo',
            'Edwin Castillo', 'Mara Hernandez', 'Kim Esguerra', 'Liza Rivera',
        ];
        $cities = [
            'Quezon City', 'Makati', 'Pasig', 'Mandaluyong', 'Marikina', 'Taguig',
            'Cebu City', 'Davao City', 'Iloilo City', 'Baguio', 'Antipolo', 'Caloocan',
        ];

        // Templates speak about the place, the stay, and a specific local detail.
        // %loc% = destination name (Tagaytay, Iloilo). %spot% = nearby tourist spot.
        // %food% = a local dish. The selector below picks one of each from the data file.
        $openers = [
            'We came up to %loc% for a long weekend and the stay was a highlight.',
            'Booked this for a quick getaway to %loc% and the photos held up to the place.',
            'Family of six in %loc% last month, no complaints from the kids.',
            'Spent two nights in %loc% and would honestly stay longer next time.',
            'Our anniversary weekend in %loc% ended up better than planned.',
            'Drove down to %loc% on a Saturday morning, smooth check-in by lunch.',
            'Friends had been hyping %loc% for months, finally got to see why.',
            'Quick escape from Manila to %loc% and totally worth the trip.',
            'Mid-week stay in %loc% when the rates dipped a bit, great call.',
        ];
        $bodies = [
            'Pool area was clean and quiet even on a Saturday afternoon.',
            'Staff went out of their way to recommend a couple of spots we would have missed.',
            'Breakfast was generous, and the menu switched up on the second morning.',
            'Room was way bigger than the photos suggested, and the bed was honestly great.',
            'Walking distance to %spot%, which made our morning plans easy.',
            'Tried %food% at the place they pointed us to, hard to top after that.',
            'Did the %spot% side trip and still made it back for sunset by the pool.',
            'View from the balcony alone was sulit, woke up early just to sit out there.',
            'Aircon was strong, water pressure was good, Wi-Fi held up for our work calls.',
            'Loved that we could bring outside food, no corkage drama.',
            'Hot shower, soft towels, clean linens, the basics done right.',
        ];
        $closers = [
            'Coming back for sure, probably with more friends next time.',
            'Sulit promise, would book again.',
            'Already telling friends to check this place out.',
            'Five stars, no hesitation.',
            'Solid choice if you are doing %loc% as a weekend trip.',
            'Easy to plan around, would do it again on the next long weekend.',
            'Booked our return already.',
            'Petmalu, no other word for it.',
        ];

        $now = now();
        $rows = [];
        foreach ($keywords as $kw) {
            $seed = abs(crc32($kw->phrase));
            $destKey = $slugMap[$kw->slug] ?? $this->clusterDefaultKey($kw->cluster_tag);
            $dest = $destinations[$destKey] ?? null;
            $location = $dest['name'] ?? $this->prettyLocationFromPhrase($kw->phrase);
            $spots = $dest['spots'] ?? [];
            $foods = $dest['food'] ?? [];

            for ($i = 0; $i < $perKeyword; $i++) {
                $reviewer = $reviewerNames[($seed + $i * 7) % count($reviewerNames)];
                $city = $cities[($seed + $i * 11) % count($cities)];

                $opener = $openers[($seed + $i * 3) % count($openers)];
                $body = $bodies[($seed + $i * 5) % count($bodies)];
                $closer = $closers[($seed + $i * 13) % count($closers)];

                $spot = !empty($spots) ? $spots[($seed + $i * 2) % count($spots)]['name'] : 'the town center';
                $food = !empty($foods) ? $this->dishNameFromString($foods[($seed + $i * 4) % count($foods)]) : 'the local food';

                $text = $opener . ' ' . $body . ' ' . $closer;
                $text = str_replace(['%loc%', '%spot%', '%food%'], [$location, $spot, $food], $text);

                $rows[] = [
                    'keyword_id' => $kw->id,
                    'reviewer_name' => $reviewer,
                    'reviewer_location' => $city,
                    'reviewer_avatar' => 'https://i.pravatar.cc/200?img=' . (1 + (($seed + $i) % 70)),
                    'rating' => 4 + (($seed + $i) % 2),
                    'review_text' => $text,
                    'review_date' => $now->copy()->subDays(($seed + $i * 9) % 240)->format('Y-m-d'),
                    'is_featured' => ($i === 0),
                    'status' => 'published',
                    'sort_order' => $i,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('rg_destination_reviews')->insert($chunk);
        }

        return back()->with('success', 'Generated ' . count($rows) . ' positive reviews across ' . $keywords->count() . ' keywords.');
    }

    /**
     * Map a keyword slug to a destination key. Mirrors the seeder's lookup so
     * generated reviews resolve to the right destination spot/food set.
     */
    private function slugToDestinationKey(): array
    {
        return [
            'resort-in-antipolo' => 'antipolo', 'resort-in-antipolo-private' => 'antipolo',
            'resort-in-tagaytay' => 'tagaytay', 'resort-in-cavite' => 'tagaytay',
            'resort-in-alfonso-cavite' => 'alfonso', 'resort-in-amadeo-cavite' => 'amadeo',
            'resort-in-bacoor-cavite' => 'bacoor', 'resort-in-dasma' => 'dasmarinas',
            'resort-in-imus' => 'imus', 'resort-in-imus-cavite' => 'imus',
            'resort-in-indang-cavite' => 'indang', 'resort-in-naic-cavite' => 'naic',
            'resort-in-silang-cavite' => 'silang',
            'resort-in-bulacan' => 'bulacan-province', 'resort-in-pandi-bulacan' => 'pandi',
            'resort-in-pampanga' => 'pampanga-province', 'resort-in-angeles-pampanga' => 'angeles',
            'resort-in-arayat-pampanga' => 'arayat',
            'resort-in-batangas' => 'batangas-city', 'resort-in-batangas-city' => 'batangas-city',
            'resort-in-batangas-with-pool-and-beach' => 'laiya',
            'resort-in-calatagan' => 'calatagan', 'resort-in-calatagan-batangas' => 'calatagan',
            'resort-in-laiya' => 'laiya', 'resort-in-san-juan-batangas' => 'laiya',
            'resort-in-lipa' => 'lipa', 'resort-in-lipa-batangas' => 'lipa',
            'resort-in-lobo-batangas' => 'lobo', 'resort-in-mabini-batangas' => 'anilao-mabini',
            'resort-in-nasugbu' => 'nasugbu', 'resort-in-nasugbu-batangas' => 'nasugbu',
            'resort-in-laguna' => 'pansol', 'resort-in-pansol' => 'pansol',
            'resort-in-calamba-laguna' => 'calamba', 'resort-in-san-pablo-laguna' => 'san-pablo',
            'resort-in-nagcarlan-laguna' => 'nagcarlan',
            'resort-in-tanay' => 'tanay', 'resort-in-rodriguez-rizal' => 'rodriguez-montalban',
            'resort-in-binangonan-rizal' => 'binangonan', 'resort-in-san-mateo-rizal' => 'san-mateo-rizal',
            'resort-in-taytay-rizal' => 'taytay-rizal', 'resort-in-marikina' => 'marikina',
            'resort-in-rizal' => 'antipolo', 'resort-in-rizal-province' => 'antipolo',
            'resort-in-lucena-city' => 'lucena', 'resort-in-sariaya-quezon' => 'sariaya',
            'resort-in-quezon' => 'lucena', 'resort-in-quezon-province' => 'lucena',
            'resort-in-albay' => 'albay-legazpi', 'resort-in-naga' => 'naga-camarines-sur',
            'resort-in-naga-city' => 'naga-camarines-sur', 'resort-in-naga-city-camarines-sur' => 'naga-camarines-sur',
            'resort-in-sorsogon' => 'sorsogon',
            'resort-in-subic' => 'subic', 'resort-in-subic-zambales' => 'subic',
            'resort-in-morong-bataan' => 'morong-bataan', 'resort-in-bataan' => 'bataan-province',
            'resort-in-pangasinan' => 'pangasinan-general', 'resort-in-bolinao' => 'bolinao',
            'beach-resort-in-la-union' => 'la-union', 'resort-in-la-union' => 'la-union',
            'resort-in-hundred-islands' => 'alaminos-hundred-islands',
            'resort-in-davao' => 'davao-city', 'resort-in-davao-city' => 'davao-city',
            'resort-in-samal-island' => 'samal-island', 'resort-in-gensan' => 'general-santos',
            'resort-in-glan' => 'glan-sarangani', 'resort-in-zamboanga' => 'zamboanga-city',
            'resort-in-kidapawan-city' => 'kidapawan',
            'resort-in-cebu-city' => 'cebu-city', 'hotel-in-cebu' => 'cebu-city',
            'resort-in-lapu-lapu' => 'mactan', 'resort-in-lapu-lapu-city' => 'mactan',
            'resort-in-panglao-bohol' => 'panglao',
            'resort-in-dumaguete' => 'dumaguete', 'resort-in-dauin' => 'dauin',
            'resort-in-iloilo' => 'iloilo-city', 'resort-in-iloilo-city' => 'iloilo-city',
            'resort-in-guimaras' => 'guimaras', 'resort-in-guimaras-island' => 'guimaras',
            'resort-in-bacolod' => 'bacolod', 'resort-in-don-salvador-benedicto' => 'bacolod',
            'resort-in-siquijor' => 'siquijor',
            'hotel-in-boracay' => 'boracay',
            'resort-in-el-nido' => 'el-nido', 'resort-in-el-nido-palawan' => 'el-nido',
            'beach-resort-in-palawan' => 'el-nido', 'resort-in-puerto-galera' => 'puerto-galera',
            'airbnb-in-manila' => 'manila', 'resort-in-manila' => 'manila',
            'resort-in-taguig' => 'taguig', 'resort-in-quezon-city' => 'quezon-city',
            'resort-in-nueva-ecija' => 'nueva-ecija', 'resort-in-tarlac' => 'tarlac',
            'resort-in-urdaneta-city-pangasinan' => 'urdaneta',
            'resort-in-dingalan-aurora' => 'dingalan',
        ];
    }

    private function clusterDefaultKey(?string $cluster): string
    {
        return [
            'rizal' => 'antipolo', 'cavite' => 'tagaytay', 'bulacan' => 'bulacan-province',
            'pampanga' => 'pampanga-province', 'batangas' => 'batangas-city', 'laguna' => 'pansol',
            'quezon' => 'lucena', 'bicol' => 'albay-legazpi', 'north-luzon' => 'la-union',
            'metro-manila' => 'manila', 'mindanao' => 'davao-city', 'visayas' => 'cebu-city',
            'palawan' => 'el-nido',
        ][$cluster] ?? 'tagaytay';
    }

    private function prettyLocationFromPhrase(string $phrase): string
    {
        // "resort in iloilo" → "Iloilo"
        $clean = preg_replace('/^(resort in|hotel in|airbnb in|beach resort in)\s+/i', '', $phrase);
        return ucwords(trim($clean));
    }

    private function dishNameFromString(string $food): string
    {
        if (preg_match('/^([^(]+)\(/', $food, $m)) return trim($m[1]);
        foreach ([' at ', ' from ', ' with ', ' along ', ' by '] as $sep) {
            $pos = mb_stripos($food, $sep);
            if ($pos !== false && $pos > 0) return trim(mb_substr($food, 0, $pos));
        }
        return trim($food);
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'keyword_id' => 'nullable|integer',
            'reviewer_name' => 'required|string|max:120',
            'reviewer_location' => 'nullable|string|max:120',
            'reviewer_avatar' => 'nullable|string|max:500',
            'rating' => 'required|integer|min:1|max:5',
            'review_text' => 'required|string',
            'review_date' => 'nullable|date',
            'is_featured' => 'nullable',
            'status' => 'required|in:draft,published',
            'sort_order' => 'nullable|integer',
        ]);
        $data['is_featured'] = $request->has('is_featured') ? 1 : 0;
        $data['keyword_id'] = $data['keyword_id'] ?: null;
        return $data;
    }

    private function keywordOptions()
    {
        return DB::table('rg_keywords')->where('status', 'active')->orderBy('phrase')->get(['id', 'phrase']);
    }
}
