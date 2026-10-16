<?php

namespace App\Http\Controllers\RssFeeds;

use App\Http\Controllers\Controller;
use App\Models\RssBundle;
use App\Models\RssItem;
use App\Models\RssSource;
use App\Services\RssFeeds\RssBundleOutput;
use App\Services\RssFeeds\RssHtml;
use App\Services\RssFeeds\RssHttp;
use App\Services\RssFeeds\RssKeywordFilter;
use App\Services\RssFeeds\RssRefresher;
use App\Services\RssFeeds\RssSourceReader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * RSS Feeds (2026-10-16): DS AXIS's own RSS.app. Sources are read on a
 * schedule (rss:refresh); combined feeds put several sources together,
 * filtered, at one public address (RssOutputController).
 *
 * The sources and combined feeds belong to the company rather than to one
 * admin (anee.io's news roundup reads them, like the AniSystem news feeds
 * they replace), so every admin sees and manages all of them; usersId only
 * records who added a row.
 */
class RssFeedsController extends Controller
{
    public function index(RssBundleOutput $output)
    {
        $sources = RssSource::active()->orderBy('sourceName')->get();
        $counts = RssItem::where('delete_status', 'active')
            ->selectRaw('sourceId, count(*) as total, sum(case when created_at >= ? then 1 else 0 end) as fresh', [now()->subDay()])
            ->groupBy('sourceId')->get()->keyBy('sourceId');
        $sources->each(function (RssSource $s) use ($counts) {
            $s->setAttribute('itemTotal', (int) ($counts[$s->id]->total ?? 0));
            $s->setAttribute('itemFresh', (int) ($counts[$s->id]->fresh ?? 0));
        });

        $bundles = RssBundle::active()->combined()->with(['sources' => fn ($q) => $q->active()])->orderBy('bundleName')->get();
        $compilations = RssBundle::active()->compilations()->with(['members' => fn ($q) => $q->active()->orderBy('bundleName')])
            ->orderBy('usedFor')->orderBy('bundleName')->get();
        $bundles->concat($compilations)->each(function (RssBundle $b) use ($output) {
            $shown = $output->items($b);
            $b->setAttribute('shownCount', $shown->count());
            $b->setAttribute('newestAt', $shown->first()?->publishedAt);
        });
        // Grouped by what they are used for; one without a use is "Not in use yet".
        $compilationGroups = $compilations->groupBy(fn (RssBundle $c) => $c->usedFor ?: 'Not in use yet');

        $stats = [
            'compilations' => $compilations->count(),
            'bundles' => $bundles->count(),
            'sources' => $sources->count(),
            'items' => (int) $counts->sum('total'),
            'fresh' => (int) $counts->sum('fresh'),
        ];

        // What the edit forms are filled with.
        $sourceForms = $sources->mapWithKeys(fn (RssSource $s) => [$s->id => [
            'sourceName' => $s->sourceName, 'sourceType' => $s->sourceType, 'sourceUrl' => $s->sourceUrl, 'searchQuery' => $s->searchQuery,
            'settings' => $s->sourceSettings ?: new \stdClass(), 'includeKeywords' => $s->includeKeywords, 'excludeKeywords' => $s->excludeKeywords,
            'refreshMinutes' => $s->refreshMinutes, 'fetchDetails' => $s->fetchDetails,
            'bundleIds' => $bundles->filter(fn ($b) => $b->sources->contains('id', $s->id))->pluck('id')->values(),
        ]]);
        $bundleForms = $bundles->mapWithKeys(fn (RssBundle $b) => [$b->id => [
            'bundleName' => $b->bundleName, 'bundleDescription' => $b->bundleDescription, 'includeKeywords' => $b->includeKeywords,
            'excludeKeywords' => $b->excludeKeywords, 'maxItems' => $b->maxItems, 'maxAgeDays' => $b->maxAgeDays,
            'sourceIds' => $b->sources->pluck('id')->values(),
        ]]);
        $compilationForms = $compilations->mapWithKeys(fn (RssBundle $c) => [$c->id => [
            'bundleName' => $c->bundleName, 'usedFor' => $c->usedFor, 'bundleDescription' => $c->bundleDescription,
            'maxItems' => $c->maxItems, 'maxAgeDays' => $c->maxAgeDays, 'memberIds' => $c->members->pluck('id')->values(),
        ]]);

        return view('rss-feeds.index', compact('sources', 'bundles', 'compilationGroups', 'stats', 'sourceForms', 'bundleForms', 'compilationForms'));
    }

    /** The stories a feed shows, or everything one source gave. */
    public function preview(Request $request, RssBundleOutput $output)
    {
        if ($request->filled('bundle')) {
            $bundle = RssBundle::active()->with(['sources' => fn ($q) => $q->active(), 'members' => fn ($q) => $q->active()->orderBy('bundleName')])
                ->findOrFail((int) $request->bundle);

            return view('rss-feeds.preview', ['bundle' => $bundle, 'source' => null, 'items' => $output->items($bundle)]);
        }

        $source = RssSource::active()->findOrFail((int) $request->source);
        $filter = new RssKeywordFilter($source->includeKeywords, $source->excludeKeywords);
        $items = RssItem::where('sourceId', $source->id)->orderByDesc('publishedAt')->orderByDesc('id')->limit(150)->get()
            ->each(fn (RssItem $i) => $i->setAttribute('passes', $filter->passes($i->itemTitle, $i->itemSummary)));

        return view('rss-feeds.preview', ['bundle' => null, 'source' => $source, 'items' => $items]);
    }

    // ------------------------------------------------------------------
    // Sources
    // ------------------------------------------------------------------

    public function storeSource(Request $request)
    {
        $data = $this->validatedSource($request);
        if ($data instanceof \Illuminate\Http\JsonResponse) {
            return $data;
        }
        $source = RssSource::create($data + ['usersId' => Auth::id(), 'isActive' => true, 'delete_status' => 'active']);
        $this->syncSourceBundles($source, (array) $request->input('bundleIds', []));
        RssRefresher::touch();

        return response()->json(['success' => true, 'message' => 'Source added.', 'data' => ['id' => $source->id]]);
    }

    public function updateSource(Request $request, $id)
    {
        $source = RssSource::active()->find($id);
        if (! $source) {
            return response()->json(['success' => false, 'message' => 'Source not found'], 404);
        }
        $data = $this->validatedSource($request);
        if ($data instanceof \Illuminate\Http\JsonResponse) {
            return $data;
        }
        $moved = $source->sourceType !== $data['sourceType'] || $source->sourceUrl !== $data['sourceUrl'] || $source->searchQuery !== $data['searchQuery'];
        $source->fill($data);
        if ($moved) {
            // A new address is read at the next run, not after the old interval.
            $source->fill(['lastFetchedAt' => null, 'lastStatus' => null, 'lastMessage' => null, 'failCount' => 0]);
        }
        $source->save();
        $this->syncSourceBundles($source, (array) $request->input('bundleIds', []));
        RssRefresher::touch();

        return response()->json(['success' => true, 'message' => 'Source saved.', 'data' => ['id' => $source->id, 'moved' => $moved]]);
    }

    public function destroySource($id)
    {
        $source = RssSource::active()->find($id);
        if (! $source) {
            return response()->json(['success' => false, 'message' => 'Source not found'], 404);
        }
        $source->update(['delete_status' => 'deleted', 'isActive' => false]);
        $source->bundles()->detach();
        RssRefresher::touch();

        return response()->json(['success' => true, 'message' => 'Source deleted. Its stories no longer show in any combined feed.']);
    }

    public function toggleSource(Request $request, $id)
    {
        $source = RssSource::active()->find($id);
        if (! $source) {
            return response()->json(['success' => false, 'message' => 'Source not found'], 404);
        }
        $source->update(['isActive' => $request->boolean('isActive')]);
        RssRefresher::touch();

        return response()->json(['success' => true, 'message' => $source->isActive ? 'Source turned on.' : 'Source paused. Its stories are left out of the combined feeds.']);
    }

    public function refreshSource($id, RssRefresher $refresher)
    {
        $source = RssSource::active()->find($id);
        if (! $source) {
            return response()->json(['success' => false, 'message' => 'Source not found'], 404);
        }
        @set_time_limit(150);
        $result = $refresher->refresh($source, microtime(true) + 75);

        return response()->json(['success' => $result['ok'], 'message' => $result['message'], 'data' => ['new' => $result['new']]], $result['ok'] ? 200 : 422);
    }

    /** Reads a source from the form without saving it, to show what it would give. */
    public function testSource(Request $request, RssSourceReader $reader)
    {
        $data = $this->validatedSource($request, false);
        if ($data instanceof \Illuminate\Http\JsonResponse) {
            return $data;
        }
        @set_time_limit(120);
        $source = new RssSource($data);
        $reader->decodeLimit = 6;
        try {
            $read = $reader->read($source, microtime(true) + 60);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
        $filter = new RssKeywordFilter($source->includeKeywords, $source->excludeKeywords);
        $items = collect($read['items'])->take(15)->map(fn ($i) => [
            'title' => $i['title'],
            'link' => $i['link'],
            'publisher' => $i['publisher'],
            'date' => $i['publishedAt'] ? $i['publishedAt']->format('M j, g:i A') : null,
            'image' => $i['image'],
            'summary' => Str::limit((string) $i['summary'], $source->sourceType === 'page_watch' ? 1200 : 220),
            'passes' => $filter->passes($i['title'], $i['summary']),
        ])->values();

        return response()->json(['success' => true, 'message' => $read['message'], 'data' => ['items' => $items, 'total' => count($read['items'])]]);
    }

    /** The form's fields as model attributes, or the JSON error to send back. */
    private function validatedSource(Request $request, bool $named = true)
    {
        $validator = Validator::make($request->all(), [
            'sourceName' => ($named ? 'required' : 'nullable') . '|string|max:190',
            'sourceType' => 'required|in:' . implode(',', array_keys(RssSource::TYPES)),
            'sourceUrl' => 'nullable|required_unless:sourceType,google_news|url:http,https|max:1000',
            'searchQuery' => 'nullable|required_if:sourceType,google_news|string|max:500',
            'region' => 'nullable|alpha|size:2',
            'withinDays' => 'nullable|integer|min:1|max:30',
            'linkSelector' => 'nullable|string|max:300',
            'linkContains' => 'nullable|string|max:300',
            'contentSelector' => 'nullable|string|max:300',
            'includeKeywords' => 'nullable|string|max:3000',
            'excludeKeywords' => 'nullable|string|max:3000',
            'refreshMinutes' => 'nullable|integer|min:15|max:1440',
            'bundleIds' => 'nullable|array',
            'bundleIds.*' => 'integer',
        ], [
            'sourceName.required' => 'Give the source a name.',
            'sourceUrl.required_unless' => 'Give the address to read.',
            'sourceUrl.url' => 'The address must start with http:// or https://.',
            'searchQuery.required_if' => 'Give the words to search Google News for.',
            'region.size' => 'The region is a two-letter country code, like PH.',
        ]);
        $validator->after(function ($v) use ($request) {
            foreach (['linkSelector', 'contentSelector'] as $field) {
                if (trim((string) $request->input($field)) !== '') {
                    try {
                        RssHtml::xpath(trim((string) $request->input($field)));
                    } catch (\Throwable $e) {
                        $v->errors()->add($field, $e->getMessage());
                    }
                }
            }
            if ($request->input('sourceType') !== 'google_news' && filter_var($request->input('sourceUrl'), FILTER_VALIDATE_URL)) {
                try {
                    RssHttp::assertPublic((string) $request->input('sourceUrl'));
                } catch (\Throwable $e) {
                    $v->errors()->add('sourceUrl', $e->getMessage());
                }
            }
        });
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        $type = $request->input('sourceType');
        $settings = match ($type) {
            'google_news' => ['region' => strtoupper((string) ($request->input('region') ?: 'PH')), 'language' => 'en', 'withinDays' => (int) ($request->input('withinDays') ?: 7)],
            'webpage' => array_filter(['linkSelector' => trim((string) $request->input('linkSelector')), 'linkContains' => trim((string) $request->input('linkContains'))]),
            'page_watch' => array_filter(['contentSelector' => trim((string) $request->input('contentSelector'))]),
            default => [],
        };

        return [
            'sourceName' => trim((string) ($request->input('sourceName') ?: 'Test')),
            'sourceType' => $type,
            'sourceUrl' => $type === 'google_news' ? null : trim((string) $request->input('sourceUrl')),
            'searchQuery' => $type === 'google_news' ? trim((string) $request->input('searchQuery')) : null,
            'sourceSettings' => $settings ?: null,
            'includeKeywords' => trim((string) $request->input('includeKeywords')) ?: null,
            'excludeKeywords' => trim((string) $request->input('excludeKeywords')) ?: null,
            'fetchDetails' => $type !== 'page_watch' && $request->boolean('fetchDetails'),
            'refreshMinutes' => (int) ($request->input('refreshMinutes') ?: 30),
        ];
    }

    /** Puts the source in exactly the combined feeds ticked in its form. */
    private function syncSourceBundles(RssSource $source, array $bundleIds): void
    {
        $wanted = RssBundle::active()->combined()->whereIn('id', array_map('intval', $bundleIds))->pluck('id')->all();
        $source->bundles()->sync($wanted);
    }

    // ------------------------------------------------------------------
    // Combined feeds and compilations
    // ------------------------------------------------------------------

    public function storeBundle(Request $request)
    {
        $data = $this->validatedBundle($request);
        if ($data instanceof \Illuminate\Http\JsonResponse) {
            return $data;
        }
        $bundle = RssBundle::create($data['bundle'] + ['bundleType' => 'combined', 'usersId' => Auth::id(), 'isActive' => true, 'delete_status' => 'active']);
        $bundle->sources()->sync($data['sourceIds']);
        RssRefresher::touch();

        return response()->json(['success' => true, 'message' => 'Combined feed created.', 'data' => ['id' => $bundle->id, 'url' => $bundle->outputUrl()]]);
    }

    public function updateBundle(Request $request, $id)
    {
        $bundle = RssBundle::active()->combined()->find($id);
        if (! $bundle) {
            return response()->json(['success' => false, 'message' => 'Combined feed not found'], 404);
        }
        $data = $this->validatedBundle($request);
        if ($data instanceof \Illuminate\Http\JsonResponse) {
            return $data;
        }
        $bundle->update($data['bundle']);
        $bundle->sources()->sync($data['sourceIds']);
        $bundle->touch();
        RssRefresher::touch();

        return response()->json(['success' => true, 'message' => 'Combined feed saved.']);
    }

    public function storeCompilation(Request $request)
    {
        $data = $this->validatedCompilation($request);
        if ($data instanceof \Illuminate\Http\JsonResponse) {
            return $data;
        }
        $compilation = RssBundle::create($data['bundle'] + ['bundleType' => 'compilation', 'usersId' => Auth::id(), 'isActive' => true, 'delete_status' => 'active']);
        $compilation->members()->sync($data['memberIds']);

        return response()->json(['success' => true, 'message' => 'Compilation created.', 'data' => ['id' => $compilation->id, 'url' => $compilation->outputUrl()]]);
    }

    public function updateCompilation(Request $request, $id)
    {
        $compilation = RssBundle::active()->compilations()->find($id);
        if (! $compilation) {
            return response()->json(['success' => false, 'message' => 'Compilation not found'], 404);
        }
        $data = $this->validatedCompilation($request);
        if ($data instanceof \Illuminate\Http\JsonResponse) {
            return $data;
        }
        $compilation->update($data['bundle']);
        $compilation->members()->sync($data['memberIds']);
        $compilation->touch();

        return response()->json(['success' => true, 'message' => 'Compilation saved.']);
    }

    public function destroyBundle($id)
    {
        $bundle = RssBundle::active()->find($id);
        if (! $bundle) {
            return response()->json(['success' => false, 'message' => 'Feed not found'], 404);
        }
        $bundle->update(['delete_status' => 'deleted', 'isActive' => false]);
        // A deleted combined feed leaves every compilation it was in.
        $bundle->partOfCompilations()->detach();
        RssRefresher::touch();

        return response()->json(['success' => true, 'message' => ($bundle->isCompilation() ? 'Compilation' : 'Combined feed') . ' deleted. Its address now answers 404.']);
    }

    public function toggleBundle(Request $request, $id)
    {
        $bundle = RssBundle::active()->find($id);
        if (! $bundle) {
            return response()->json(['success' => false, 'message' => 'Feed not found'], 404);
        }
        $bundle->update(['isActive' => $request->boolean('isActive')]);
        RssRefresher::touch();
        $what = $bundle->isCompilation() ? 'Compilation' : 'Combined feed';

        return response()->json(['success' => true, 'message' => $bundle->isActive ? $what . ' is live again.'
            : $what . ' paused. Its address answers 404 until it is turned back on' . ($bundle->isCompilation() ? '.' : ', and compilations leave it out.')]);
    }

    /** Reads every source of a combined feed (or of a compilation's combined feeds) now. */
    public function refreshBundle($id, RssRefresher $refresher)
    {
        $bundle = RssBundle::active()->find($id);
        if (! $bundle) {
            return response()->json(['success' => false, 'message' => 'Feed not found'], 404);
        }
        @set_time_limit(180);
        $deadline = microtime(true) + 100;
        $read = $new = $failed = 0;
        $left = 0;
        foreach ($bundle->liveSources()->sortBy(fn ($s) => $s->lastFetchedAt?->getTimestamp() ?? 0) as $source) {
            if (microtime(true) > $deadline - 10) {
                $left++;

                continue;
            }
            $r = $refresher->refresh($source, $deadline);
            $read++;
            $new += $r['new'];
            $failed += $r['ok'] ? 0 : 1;
        }
        $message = 'Read ' . $read . ' ' . Str::plural('source', $read) . ': ' . $new . ' new ' . Str::plural('story', $new) . '.'
            . ($failed ? ' ' . $failed . ' failed (see Sources).' : '') . ($left ? ' ' . $left . ' left for the scheduler.' : '');

        return response()->json(['success' => true, 'message' => $message]);
    }

    private function validatedBundle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bundleName' => 'required|string|max:190',
            'bundleDescription' => 'nullable|string|max:500',
            'sourceIds' => 'required|array|min:1',
            'sourceIds.*' => 'integer',
            'includeKeywords' => 'nullable|string|max:3000',
            'excludeKeywords' => 'nullable|string|max:3000',
            'maxItems' => 'required|integer|min:5|max:200',
            'maxAgeDays' => 'required|integer|min:1|max:60',
        ], [
            'bundleName.required' => 'Give the combined feed a name.',
            'sourceIds.required' => 'Tick at least one source.',
            'sourceIds.min' => 'Tick at least one source.',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }
        $sourceIds = RssSource::active()->whereIn('id', array_map('intval', (array) $request->input('sourceIds')))->pluck('id')->all();
        if (! $sourceIds) {
            return response()->json(['success' => false, 'message' => 'Tick at least one source.', 'errors' => ['sourceIds' => ['Tick at least one source.']]], 422);
        }

        return [
            'bundle' => [
                'bundleName' => trim((string) $request->input('bundleName')),
                'bundleDescription' => trim((string) $request->input('bundleDescription')) ?: null,
                'includeKeywords' => trim((string) $request->input('includeKeywords')) ?: null,
                'excludeKeywords' => trim((string) $request->input('excludeKeywords')) ?: null,
                'maxItems' => (int) $request->input('maxItems'),
                'maxAgeDays' => (int) $request->input('maxAgeDays'),
            ],
            'sourceIds' => $sourceIds,
        ];
    }

    private function validatedCompilation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bundleName' => 'required|string|max:190',
            'usedFor' => 'nullable|string|max:190',
            'bundleDescription' => 'nullable|string|max:500',
            'memberIds' => 'required|array|min:1',
            'memberIds.*' => 'integer',
            'maxItems' => 'required|integer|min:5|max:300',
            'maxAgeDays' => 'required|integer|min:1|max:60',
        ], [
            'bundleName.required' => 'Give the compilation a name.',
            'memberIds.required' => 'Tick at least one combined feed.',
            'memberIds.min' => 'Tick at least one combined feed.',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }
        // Only combined feeds go in: a compilation inside a compilation could loop.
        $memberIds = RssBundle::active()->combined()->whereIn('id', array_map('intval', (array) $request->input('memberIds')))->pluck('id')->all();
        if (! $memberIds) {
            return response()->json(['success' => false, 'message' => 'Tick at least one combined feed.', 'errors' => ['memberIds' => ['Tick at least one combined feed.']]], 422);
        }

        return [
            'bundle' => [
                'bundleName' => trim((string) $request->input('bundleName')),
                'usedFor' => trim((string) $request->input('usedFor')) ?: null,
                'bundleDescription' => trim((string) $request->input('bundleDescription')) ?: null,
                'maxItems' => (int) $request->input('maxItems'),
                'maxAgeDays' => (int) $request->input('maxAgeDays'),
            ],
            'memberIds' => $memberIds,
        ];
    }

    // ------------------------------------------------------------------
    // Stories
    // ------------------------------------------------------------------

    /** Hides a story from every combined feed, or brings it back. */
    public function hideItem(Request $request, $id)
    {
        $item = RssItem::find($id);
        if (! $item || ! RssSource::active()->where('id', $item->sourceId)->exists()) {
            return response()->json(['success' => false, 'message' => 'Story not found'], 404);
        }
        $hide = $request->boolean('hide');
        $item->update(['delete_status' => $hide ? 'deleted' : 'active']);
        RssRefresher::touch();

        return response()->json(['success' => true, 'message' => $hide ? 'Story hidden from every combined feed.' : 'Story shown again.']);
    }
}
