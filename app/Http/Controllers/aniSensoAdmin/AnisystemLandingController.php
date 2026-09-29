<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use App\Models\AsSiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * anee.io's ads landing page (/start), edited here.
 *
 * anee keeps the page's default words in code (App\Support\LandingPage) and
 * publishes them on the settings shelf as `landing.defaults`; this editor
 * prefills its form from them, and saves under `landing.page` only what
 * differs, so a field left as it was keeps following the defaults when they
 * change. A text field emptied goes back to its default. A list (bullets,
 * steps, pillars, tiles, testimonials, questions) is saved whole once it
 * differs. Pictures uploaded here land on this app's public disk under
 * anisystem/landing, where anee reads them (its MediaStore's way).
 */
class AnisystemLandingController extends Controller
{
    public const KEY = 'landing.page';

    public const DEFAULTS_KEY = 'landing.defaults';

    public const FOLDER = 'anisystem/landing';

    /** The ad networks' ids (anee's App\Support\AdTags reads them). */
    public const TRACKING_KEY = 'landing.tracking';

    /** What each id must look like; anee refuses anything else too. */
    public const TRACKING = [
        'metaPixel' => ['/^\d{5,20}$/', 'The Meta Pixel id is the number only, e.g. 123456789012345.'],
        'googleTag' => ['/^(G|AW|GT)-[A-Z0-9]{4,20}$/', 'The Google tag id looks like G-XXXXXXX or AW-1234567890.'],
        'googleAdsSendTo' => ['#^AW-\d{5,15}/[A-Za-z0-9_-]{3,40}$#', 'The Google Ads conversion looks like AW-1234567890/AbCdEfGh.'],
    ];

    /** The built-in pictures a pillar may show (files on anee, public/images/site/lp). */
    public const SHOTS = [
        'board' => ['The season board (phone)', 'lp/board.webp'],
        'growth' => ['Growth stages (phone)', 'lp/growth.webp'],
        'weather' => ['Weather forecast (phone)', 'lp/weather.webp'],
        'hub' => ['The season\'s modules (phone)', 'lp/hub.webp'],
        'report-top' => ['Season report, the top (phone)', 'lp/report-top.webp'],
        'report-money' => ['Season report, the money (phone)', 'lp/report-money.webp'],
        'datediff' => ['Date difference (phone)', 'lp/datediff.webp'],
        'anee-chat-hand' => ['Anee on a phone in the field (photo)', 'lp/anee-chat-hand.webp'],
    ];

    /** Words that fill themselves in on the page, so it cannot drift from the app. */
    public const TOKENS = [
        '{farmers}' => 'the country\'s word for its farmers ("Filipino farmers")',
        '{crops}' => 'how many crops the app knows for the country',
        '{pay}' => 'how paid plans are paid (GCash)',
        '{signupWays}' => '"just your email", or with Google once Google sign-in is on',
        '{libreAnee}' => 'the Libre + Anee monthly price',
        '{solo}' => 'the Solo Farmer monthly price',
    ];

    public function index()
    {
        $defaults = $this->defaults();
        $page = $defaults ? self::merge($defaults, $this->stored()) : null;
        $edited = $this->stored() !== [];
        $base = rtrim((string) config('anisystem.url'), '/');
        $tracking = $this->tracking();
        $sources = $this->sources();

        return view('aniSensoAdmin.landing.index', compact('defaults', 'page', 'edited', 'base', 'tracking', 'sources'));
    }

    public function save(Request $request)
    {
        $defaults = $this->defaults();
        if (! $defaults) {
            return redirect()->route('anisenso-landing.index')->with('error', 'anee.io has not published the page\'s defaults yet. Open its /start page once, then save again.');
        }

        $pic = 'nullable|file|mimes:jpg,jpeg,png,webp|max:6144';
        $request->validate([
            'hero.imageFile' => $pic,
            'problem.imageFile' => $pic,
            'closer.imageFile' => $pic,
            'pillars.*.uploadFile' => $pic,
            'testimonials.items.*.photoFile' => $pic,
        ], [
            '*.mimes' => 'Pictures must be JPG, PNG or WebP.',
            '*.max' => 'Pictures must be 6 MB or smaller.',
        ]);

        // The ad networks' ids: each blank or the right shape.
        $ids = [];
        foreach (self::TRACKING as $k => [$re, $say]) {
            $v = trim((string) $request->input("tracking.$k"));
            if ($v !== '' && ! preg_match($re, $v)) {
                return redirect()->route('anisenso-landing.index')->withInput()->with('error', $say . ' Nothing was saved.');
            }
            $ids[$k] = $v;
        }

        $before = $this->stored();
        $dropped = 0;

        $page = [
            'meta' => [
                'title' => $this->text($request, 'meta.title'),
                'description' => $this->text($request, 'meta.description'),
            ],
            'hero' => [
                'kicker' => $this->text($request, 'hero.kicker'),
                'headline' => $this->text($request, 'hero.headline'),
                'sub' => $this->text($request, 'hero.sub'),
                'cta' => $this->text($request, 'hero.cta'),
                'note' => $this->text($request, 'hero.note'),
                'align' => $request->input('hero.align') === 'left' ? 'left' : 'right',
                'image' => $this->picture($request, 'hero.image'),
                'chips' => $this->rows($request, 'hero.chips', fn ($k) => [
                    'icon' => $this->text($request, "hero.chips.$k.icon", 16),
                    'title' => $this->text($request, "hero.chips.$k.title"),
                    'sub' => $this->text($request, "hero.chips.$k.sub"),
                ], keepBlank: true),
            ],
            'proof' => [
                'lead' => $this->text($request, 'proof.lead'),
                'items' => $this->lines($request, 'proof.items'),
                'stats' => $request->input('proof.stats') === 'hide' ? 'hide' : 'show',
            ],
            'problem' => [
                'kicker' => $this->text($request, 'problem.kicker'),
                'headline' => $this->text($request, 'problem.headline'),
                'image' => $this->picture($request, 'problem.image'),
                'bullets' => $this->lines($request, 'problem.bullets'),
                'solutionKicker' => $this->text($request, 'problem.solutionKicker'),
                'solutionHeadline' => $this->text($request, 'problem.solutionHeadline'),
                'steps' => $this->rows($request, 'problem.steps', fn ($k) => [
                    'title' => $this->text($request, "problem.steps.$k.title"),
                    'text' => $this->text($request, "problem.steps.$k.text"),
                ]),
            ],
            'pillars' => $this->rows($request, 'pillars', fn ($k) => [
                'kicker' => $this->text($request, "pillars.$k.kicker"),
                'title' => $this->text($request, "pillars.$k.title"),
                'text' => $this->text($request, "pillars.$k.text", 1200),
                'bullets' => $this->lines($request, "pillars.$k.bullets"),
                'image' => array_key_exists((string) $request->input("pillars.$k.image"), self::SHOTS) ? (string) $request->input("pillars.$k.image") : 'board',
                'plan' => $this->text($request, "pillars.$k.plan"),
                'upload' => $this->picture($request, "pillars.$k.upload"),
                'frame' => $request->input("pillars.$k.frame") === 'photo' ? 'photo' : 'phone',
            ]),
            'losses' => [
                'headline' => $this->text($request, 'losses.headline'),
                'items' => $this->rows($request, 'losses.items', fn ($k) => [
                    'n' => max(0, min(100, (int) $request->input("losses.items.$k.n"))),
                    'title' => $this->text($request, "losses.items.$k.title"),
                    'text' => $this->text($request, "losses.items.$k.text"),
                ]),
                'note' => $this->text($request, 'losses.note'),
            ],
            'precision' => [
                'kicker' => $this->text($request, 'precision.kicker'),
                'headline' => $this->text($request, 'precision.headline'),
                'sub' => $this->text($request, 'precision.sub'),
                'items' => $this->rows($request, 'precision.items', fn ($k) => [
                    'icon' => $this->text($request, "precision.items.$k.icon", 16),
                    'title' => $this->text($request, "precision.items.$k.title"),
                    'text' => $this->text($request, "precision.items.$k.text"),
                ]),
            ],
            'more' => [
                'headline' => $this->text($request, 'more.headline'),
                'sub' => $this->text($request, 'more.sub'),
                'image' => $this->picture($request, 'more.image'),
                'items' => $this->rows($request, 'more.items', fn ($k) => [
                    'group' => $this->text($request, "more.items.$k.group", 40),
                    'icon' => $this->text($request, "more.items.$k.icon", 16),
                    'title' => $this->text($request, "more.items.$k.title"),
                    'text' => $this->text($request, "more.items.$k.text"),
                ]),
            ],
            'testimonials' => [
                'headline' => $this->text($request, 'testimonials.headline'),
                'items' => array_values(array_filter(
                    $this->rows($request, 'testimonials.items', fn ($k) => [
                        'name' => $this->text($request, "testimonials.items.$k.name"),
                        'role' => $this->text($request, "testimonials.items.$k.role"),
                        'location' => $this->text($request, "testimonials.items.$k.location"),
                        'quote' => $this->text($request, "testimonials.items.$k.quote", 1200),
                        'result' => $this->text($request, "testimonials.items.$k.result"),
                        'rating' => max(0, min(5, (int) $request->input("testimonials.items.$k.rating", 5))),
                        'photo' => $this->picture($request, "testimonials.items.$k.photo"),
                    ]),
                    // A testimonial needs a name and the words; a row without them is let go.
                    function ($t) use (&$dropped) {
                        $ok = $t['name'] !== '' && $t['quote'] !== '';
                        $dropped += $ok ? 0 : 1;

                        return $ok;
                    }
                )),
            ],
            'faq' => [
                'headline' => $this->text($request, 'faq.headline'),
                'items' => $this->rows($request, 'faq.items', fn ($k) => [
                    'q' => $this->text($request, "faq.items.$k.q"),
                    'a' => $this->text($request, "faq.items.$k.a", 1500),
                ]),
            ],
            'closer' => [
                'headline' => $this->text($request, 'closer.headline'),
                'sub' => $this->text($request, 'closer.sub'),
                'cta' => $this->text($request, 'closer.cta'),
                'risk' => $this->text($request, 'closer.risk'),
                'image' => $this->picture($request, 'closer.image'),
            ],
        ];

        $diff = self::diff($defaults, $page);
        try {
            if ($diff === []) {
                AsSiteSetting::query()->where('key', self::KEY)->delete();
            } else {
                AsSiteSetting::put(self::KEY, json_encode($diff, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
            if (array_filter($ids) === []) {
                AsSiteSetting::query()->where('key', self::TRACKING_KEY)->delete();
            } else {
                AsSiteSetting::put(self::TRACKING_KEY, json_encode($ids, JSON_UNESCAPED_SLASHES));
            }
        } catch (\Throwable $e) {
            return redirect()->route('anisenso-landing.index')->with('error', 'The settings shelf could not be written: ' . $e->getMessage());
        }
        $this->sweep($before, $diff);

        $say = $diff === [] ? 'Saved. Nothing differs from the defaults, so the page shows its own words.' : 'Saved. The landing page shows it now.';
        if ($dropped) {
            $say .= ' ' . $dropped . ' testimonial' . ($dropped === 1 ? '' : 's') . ' without a name or words ' . ($dropped === 1 ? 'was' : 'were') . ' left out.';
        }

        return redirect()->route('anisenso-landing.index')->with('success', $say);
    }

    /** Back to anee's own words and pictures: the edits and the uploads go. */
    public function reset()
    {
        $before = $this->stored();
        try {
            AsSiteSetting::query()->where('key', self::KEY)->delete();
        } catch (\Throwable $e) {
            return redirect()->route('anisenso-landing.index')->with('error', 'The settings shelf could not be written: ' . $e->getMessage());
        }
        $this->sweep($before, []);

        return redirect()->route('anisenso-landing.index')->with('success', 'The landing page is back to its default words and pictures.');
    }

    // ---------------------------------------------------------------------

    private function defaults(): ?array
    {
        $d = json_decode((string) AsSiteSetting::get(self::DEFAULTS_KEY, ''), true);

        return is_array($d) && isset($d['hero']) ? $d : null;
    }

    private function tracking(): array
    {
        $t = json_decode((string) AsSiteSetting::get(self::TRACKING_KEY, ''), true);

        return array_merge(array_fill_keys(array_keys(self::TRACKING), ''), is_array($t) ? $t : []);
    }

    /**
     * The last 90 days of signups that came with an ad's tags (anee keeps
     * them on as_signup_sources), by source and campaign, beside every
     * signup in the same days.
     */
    private function sources(): array
    {
        try {
            $since = now()->subDays(90);
            $rows = DB::table('as_signup_sources as s')
                ->leftJoin('anisystem_users as u', 'u.id', '=', 's.userId')
                ->where('s.created_at', '>=', $since)
                ->groupBy('s.source', 's.campaign')
                ->selectRaw("s.source, s.campaign, COUNT(*) as signups, SUM(CASE WHEN u.status = 'active' THEN 1 ELSE 0 END) as confirmed, MAX(s.created_at) as lastAt")
                ->orderByDesc('signups')
                ->limit(50)
                ->get();
            $all = DB::table('anisystem_users')->where('created_at', '>=', $since)->count();

            return ['ready' => true, 'rows' => $rows, 'all' => $all, 'tagged' => (int) $rows->sum('signups')];
        } catch (\Throwable $e) {
            // anee has not deployed the table yet.
            return ['ready' => false, 'rows' => collect(), 'all' => 0, 'tagged' => 0];
        }
    }

    private function stored(): array
    {
        $s = json_decode((string) AsSiteSetting::get(self::KEY, ''), true);

        return is_array($s) ? $s : [];
    }

    private function text(Request $request, string $key, int $max = 600): string
    {
        $v = $request->input($key);
        $v = is_string($v) ? trim(str_replace(["\r\n", "\r"], "\n", $v)) : '';

        return mb_substr($v, 0, $max);
    }

    /** A textarea of one item per line. */
    private function lines(Request $request, string $key): array
    {
        $v = $request->input($key);
        $v = is_string($v) ? $v : '';

        return array_values(array_filter(array_map(fn ($l) => mb_substr(trim($l), 0, 300), preg_split('/\R/', $v)), fn ($l) => $l !== ''));
    }

    /**
     * A repeatable list: its rows in the order the form sent them (each row
     * carries its own key, so moved and removed rows need no renumbering).
     * A row left wholly empty is dropped, unless the list keeps blank rows
     * (the hero's two chips: a blank title hides that chip).
     */
    private function rows(Request $request, string $key, callable $row, bool $keepBlank = false): array
    {
        $in = $request->input($key);
        if (! is_array($in)) {
            return [];
        }
        $out = [];
        foreach (array_keys($in) as $k) {
            if (! preg_match('/^[A-Za-z0-9_]{1,24}$/', (string) $k)) {
                continue;
            }
            $r = $row($k);
            $filled = array_filter($r, fn ($x) => is_array($x) ? $x !== [] : (is_string($x) && $x !== '' && ! in_array($x, ['board', 'phone'], true)));
            if ($keepBlank || $filled !== []) {
                $out[] = $r;
            }
        }

        return $out;
    }

    /**
     * A picture slot: a new upload wins; "use the built-in one" empties it;
     * otherwise what it held (sent back in the form, and only accepted when
     * it is one of ours or a web address).
     */
    private function picture(Request $request, string $key): string
    {
        if ($request->hasFile($key . 'File') && $request->file($key . 'File')->isValid()) {
            $path = $request->file($key . 'File')->store(self::FOLDER, 'public');
            if ($path) {
                return $path;
            }
        }
        if ($request->boolean($key . 'Clear')) {
            return '';
        }
        $held = trim((string) $request->input($key));
        if (preg_match('#^https?://\S+$#i', $held) || preg_match('#^' . preg_quote(self::FOLDER, '#') . '/[A-Za-z0-9._-]+$#', $held)) {
            return mb_substr($held, 0, 500);
        }

        return '';
    }

    /** Uploads the page no longer uses are deleted from the disk. */
    private function sweep(array $before, array $after): void
    {
        $kept = self::paths($after);
        foreach (array_diff(self::paths($before), $kept) as $gone) {
            try {
                Storage::disk('public')->delete($gone);
            } catch (\Throwable $e) {
                // A file that will not go is only a file; the page no longer shows it.
            }
        }
    }

    private static function paths(array $page): array
    {
        $out = [];
        array_walk_recursive($page, function ($v) use (&$out) {
            if (is_string($v) && str_starts_with($v, self::FOLDER . '/')) {
                $out[] = $v;
            }
        });

        return array_values(array_unique($out));
    }

    /**
     * What differs from the defaults: a text only when it is not empty and
     * not the default's; a list whole when it is not the default list.
     */
    private static function diff(array $base, array $new): array
    {
        $out = [];
        foreach ($base as $k => $b) {
            if (! array_key_exists($k, $new)) {
                continue;
            }
            $n = $new[$k];
            if (is_array($b) && ! array_is_list($b)) {
                $d = self::diff($b, is_array($n) ? $n : []);
                if ($d !== []) {
                    $out[$k] = $d;
                }
            } elseif (is_array($b)) {
                if (is_array($n) && $n != $b) {
                    $out[$k] = $n;
                }
            } elseif (is_string($n) && $n !== '' && $n !== $b) {
                $out[$k] = $n;
            }
        }

        return $out;
    }

    /**
     * Stored over defaults, for the form (tokens left as tokens). The twin of
     * anee's LandingPage::merge(); keep the two alike.
     */
    private static function merge(array $base, array $over): array
    {
        foreach ($over as $k => $v) {
            if (! array_key_exists($k, $base)) {
                continue;
            }
            if (is_array($base[$k]) && is_array($v)) {
                if (! array_is_list($base[$k])) {
                    $base[$k] = self::merge($base[$k], $v);
                    continue;
                }
                $first = $base[$k][0] ?? null;
                if (is_string($first)) {
                    $v = array_values(array_filter($v, fn ($x) => is_string($x) && trim($x) !== ''));
                } elseif (is_array($first)) {
                    $blank = self::shape($first);
                    $v = array_values(array_map(fn ($x) => array_replace($blank, $x), array_filter($v, 'is_array')));
                } else {
                    $v = array_values(array_filter($v, 'is_array'));
                }
                if ($v !== [] || $base[$k] === []) {
                    $base[$k] = $v;
                }
            } elseif (is_string($base[$k]) && is_string($v) && trim($v) !== '') {
                $base[$k] = $v;
            }
        }

        return $base;
    }

    private static function shape(array $item): array
    {
        return array_map(fn ($x) => is_array($x) ? (array_is_list($x) ? [] : self::shape($x)) : (is_string($x) ? '' : $x), $item);
    }
}
