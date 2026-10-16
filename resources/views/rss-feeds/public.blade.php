{{-- What a person sees when they open a feed's address in a browser (RssOutputController).
     Apps and feed readers get the RSS at the same address. Public, no login, no admin layout. --}}
@php
    $multi = count($sections) > 1;
    $shown = 10;
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $bundle->bundleName }} · RSS feed</title>
    <link rel="alternate" type="application/rss+xml" title="{{ $bundle->bundleName }}" href="{{ $bundle->outputUrl() }}">
    <style>
        :root {
            --bg: #f5f6fa; --card: #ffffff; --text: #1f2430; --muted: #646b7d; --line: #e6e8ef;
            --accent: #4f5bd5; --accent-soft: #eceefc; --chip: #f0f1f5; --shadow: 0 1px 2px rgba(20, 24, 40, .06), 0 4px 14px rgba(20, 24, 40, .05);
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #14161c; --card: #1c1f27; --text: #e8eaf0; --muted: #9aa1b2; --line: #2b2f3a;
                --accent: #8e98ff; --accent-soft: #262a45; --chip: #262a33; --shadow: none;
            }
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; scroll-padding-top: 4.5rem; }
        body { margin: 0; background: var(--bg); color: var(--text); font: 15px/1.55 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
        a { color: inherit; }
        .wrap { max-width: 980px; margin: 0 auto; padding: 0 16px; }

        header.top { padding: 2.2rem 0 1.4rem; }
        .kicker { display: inline-flex; align-items: center; gap: .4rem; font-size: .78rem; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: var(--accent); }
        .kicker svg { width: 14px; height: 14px; }
        h1 { margin: .35rem 0 .3rem; font-size: clamp(1.6rem, 3.5vw, 2.2rem); line-height: 1.2; }
        .lead { margin: 0 0 .7rem; color: var(--muted); max-width: 46rem; }
        .facts { display: flex; flex-wrap: wrap; gap: .4rem 1.1rem; font-size: .86rem; color: var(--muted); }
        .facts b { color: var(--text); font-weight: 600; }
        .address { display: flex; gap: .5rem; align-items: center; margin-top: 1rem; max-width: 46rem; }
        .address code { flex: 1; min-width: 0; padding: .55rem .75rem; border: 1px solid var(--line); border-radius: .6rem; background: var(--card);
            font: 12.5px/1.4 ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .btn { flex: none; border: 1px solid var(--line); background: var(--card); color: var(--text); border-radius: .6rem; padding: .5rem .8rem; font: inherit; font-size: .85rem; cursor: pointer; text-decoration: none; }
        .btn:hover { border-color: var(--accent); color: var(--accent); }
        .btn.primary { background: var(--accent); border-color: var(--accent); color: #fff; }
        .hint { margin: .45rem 0 0; font-size: .8rem; color: var(--muted); }

        nav.sections { position: sticky; top: 0; z-index: 5; background: color-mix(in srgb, var(--bg) 92%, transparent); backdrop-filter: blur(6px); border-bottom: 1px solid var(--line); }
        nav.sections .wrap { display: flex; flex-wrap: wrap; gap: .4rem; padding-top: .6rem; padding-bottom: .6rem; }
        /* On a phone the sections are one row that scrolls sideways, so the bar stays short. */
        @media (max-width: 640px) { nav.sections .wrap { flex-wrap: nowrap; overflow-x: auto; scrollbar-width: thin; } }
        nav.sections a { flex: none; text-decoration: none; padding: .38rem .8rem; border-radius: 999px; background: var(--card); border: 1px solid var(--line); font-size: .85rem; white-space: nowrap; }
        nav.sections a span { color: var(--muted); margin-left: .25rem; }
        nav.sections a:hover { border-color: var(--accent); color: var(--accent); }

        main { padding: 1.4rem 0 2rem; }
        section.feed { background: var(--card); border: 1px solid var(--line); border-radius: 1rem; box-shadow: var(--shadow); margin-bottom: 1.4rem; overflow: hidden; }
        .feed-head { display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: .4rem 1rem; padding: 1rem 1.2rem .8rem; border-bottom: 1px solid var(--line); }
        .feed-head h2 { margin: 0; font-size: 1.15rem; }
        .feed-head .count { margin-left: .5rem; font-size: .8rem; font-weight: 600; color: var(--accent); background: var(--accent-soft); padding: .12rem .5rem; border-radius: 999px; vertical-align: 2px; }
        .feed-head a { font-size: .82rem; color: var(--muted); text-decoration: none; }
        .feed-head a:hover { color: var(--accent); }

        ol.stories { list-style: none; margin: 0; padding: 0; }
        .story { display: flex; gap: 1rem; padding: .95rem 1.2rem; border-bottom: 1px solid var(--line); }
        .story:last-child { border-bottom: 0; }
        .thumb { position: relative; flex: none; width: 116px; height: 78px; border-radius: .6rem; overflow: hidden; background: var(--chip); display: flex; align-items: center; justify-content: center;
            color: var(--muted); font-weight: 700; font-size: 1.3rem; text-decoration: none; }
        /* The picture covers the publisher's initial; if it fails to load it is removed and the initial shows. */
        .thumb img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; display: block; }
        .body { min-width: 0; flex: 1; }
        /* A social post's headline is its whole text: three lines of it are enough. */
        .title { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; font-weight: 600; font-size: 1rem; line-height: 1.35; text-decoration: none; }
        .title:hover { color: var(--accent); }
        .meta { margin-top: .2rem; font-size: .8rem; color: var(--muted); }
        .summary { margin: .35rem 0 0; font-size: .88rem; color: var(--muted); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .also { margin-top: .4rem; display: flex; flex-wrap: wrap; gap: .3rem; font-size: .74rem; color: var(--muted); align-items: center; }
        .also a { text-decoration: none; background: var(--chip); padding: .08rem .5rem; border-radius: 999px; }
        .also a:hover { color: var(--accent); }
        .story.extra { display: none; }
        section.feed.open .story.extra { display: flex; }
        .more { display: block; width: 100%; border: 0; border-top: 1px solid var(--line); background: transparent; color: var(--accent); font: inherit; font-size: .88rem; font-weight: 600; padding: .75rem; cursor: pointer; }
        .more:hover { background: var(--accent-soft); }
        .empty { padding: 1.2rem; color: var(--muted); font-size: .9rem; }

        footer { padding: 0 0 2.5rem; font-size: .8rem; color: var(--muted); }
        @media (max-width: 560px) {
            .story { gap: .75rem; padding: .85rem 1rem; }
            .thumb { width: 84px; height: 64px; }
            .feed-head { padding: .9rem 1rem .7rem; }
            .summary { -webkit-line-clamp: 3; }
        }
    </style>
    <noscript><style>.story.extra { display: flex; } .more { display: none; }</style></noscript>
</head>
<body>
    <header class="top">
        <div class="wrap">
            <div class="kicker">
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.18 15.64a2.18 2.18 0 1 1 0 4.36 2.18 2.18 0 0 1 0-4.36zM4 4.44A15.56 15.56 0 0 1 19.56 20h-2.83A12.73 12.73 0 0 0 4 7.27zm0 5.66A9.9 9.9 0 0 1 13.9 20h-2.83A7.07 7.07 0 0 0 4 12.93z"/></svg>
                RSS feed
            </div>
            <h1>{{ $bundle->bundleName }}</h1>
            @if ($bundle->bundleDescription)<p class="lead">{{ $bundle->bundleDescription }}</p>@endif
            <div class="facts">
                <span><b>{{ number_format($total) }}</b> {{ \Illuminate\Support\Str::plural('story', $total) }}</span>
                @if ($multi)<span><b>{{ count($sections) }}</b> sections</span>@endif
                <span>from the last <b>{{ $bundle->maxAgeDays }}</b> {{ \Illuminate\Support\Str::plural('day', $bundle->maxAgeDays) }}</span>
                @if ($newestAt)<span>newest <b>{{ $newestAt->diffForHumans() }}</b></span>@endif
            </div>
            <div class="address">
                <code id="feedUrl">{{ $bundle->outputUrl() }}</code>
                <button type="button" class="btn primary" id="copyBtn">Copy address</button>
                <a class="btn" href="{{ $bundle->outputUrl() }}?raw=1">Raw RSS</a>
            </div>
            <p class="hint">Paste this address into a feed reader or an app that takes RSS. This page is what a browser shows; apps get the feed itself.</p>
        </div>
    </header>

    @if ($multi)
        <nav class="sections" aria-label="Sections">
            <div class="wrap">
                @foreach ($sections as $i => $section)
                    <a href="#s{{ $i + 1 }}">{{ $section['name'] }}<span>{{ $section['items']->count() }}</span></a>
                @endforeach
            </div>
        </nav>
    @endif

    <main>
        <div class="wrap">
            @foreach ($sections as $i => $section)
                <section class="feed" id="s{{ $i + 1 }}">
                    <div class="feed-head">
                        <h2>{{ $section['name'] }}<span class="count">{{ $section['items']->count() }}</span></h2>
                        @if ($multi)<a href="{{ $section['url'] }}">This section's own feed &rarr;</a>@endif
                    </div>
                    @if ($section['items']->isEmpty())
                        <div class="empty">No stories in this section right now.</div>
                    @else
                        <ol class="stories">
                            @foreach ($section['items'] as $n => $item)
                                @php
                                    $others = array_values(array_diff((array) $item->categories, [$section['name']]));
                                    $publisher = $item->itemPublisher ?: parse_url($item->itemLink, PHP_URL_HOST);
                                @endphp
                                <li class="story {{ $n >= $shown ? 'extra' : '' }}">
                                    <a class="thumb" href="{{ $item->itemLink }}" target="_blank" rel="noopener" aria-hidden="true" tabindex="-1">
                                        @if ($item->itemImage)
                                            <img src="{{ $item->itemImage }}" alt="" loading="lazy" referrerpolicy="no-referrer" onerror="this.remove()">
                                        @endif
                                        <span>{{ mb_strtoupper(mb_substr($publisher, 0, 1)) }}</span>
                                    </a>
                                    <div class="body">
                                        <a class="title" href="{{ $item->itemLink }}" target="_blank" rel="noopener">{{ $item->itemTitle }}</a>
                                        <div class="meta">
                                            {{ $publisher }}
                                            @if ($item->publishedAt) · <time datetime="{{ $item->publishedAt->toIso8601String() }}" title="{{ $item->publishedAt->format('M j, Y g:i A') }}">{{ $item->publishedAt->diffForHumans() }}</time>@endif
                                        </div>
                                        @if ($item->itemSummary && $item->itemSummary !== $item->itemTitle)
                                            <p class="summary">{{ \Illuminate\Support\Str::limit($item->itemSummary, 320) }}</p>
                                        @endif
                                        @if ($others)
                                            <div class="also">Also in
                                                @foreach ($others as $other)
                                                    @php $j = collect($sections)->search(fn ($s) => $s['name'] === $other); @endphp
                                                    <a href="#s{{ $j === false ? '' : $j + 1 }}">{{ $other }}</a>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                        @if ($section['items']->count() > $shown)
                            <button type="button" class="more" data-label="Show all {{ $section['items']->count() }} stories">Show all {{ $section['items']->count() }} stories</button>
                        @endif
                    @endif
                </section>
            @endforeach
        </div>
    </main>

    <footer>
        <div class="wrap">Updated every few minutes from the newsrooms' own websites. Each headline links to the original report.</div>
    </footer>

    <script>
        document.getElementById('copyBtn').addEventListener('click', async function () {
            const url = document.getElementById('feedUrl').textContent.trim();
            try { await navigator.clipboard.writeText(url); this.textContent = 'Copied'; }
            catch (e) { window.prompt('Copy this address', url); }
            setTimeout(() => { this.textContent = 'Copy address'; }, 1600);
        });
        document.querySelectorAll('.more').forEach((btn) => btn.addEventListener('click', () => {
            const feed = btn.closest('.feed');
            const open = feed.classList.toggle('open');
            btn.textContent = open ? 'Show fewer' : btn.dataset.label;
            if (!open) feed.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }));
    </script>
</body>
</html>
