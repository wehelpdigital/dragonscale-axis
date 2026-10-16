@extends('layouts.master')

@section('title') RSS Feeds @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    /* Scoped under #rf: this page's styles load before Bootstrap's. */
    #rf .stat { border-radius: .9rem; padding: .9rem 1rem; background: var(--bs-tertiary-bg); height: 100%; }
    #rf .stat b { display: block; font-size: 1.35rem; color: var(--bs-emphasis-color); }
    #rf .stat span { font-size: .8rem; color: var(--bs-secondary-color); }
    #rf .addr { display: flex; gap: .35rem; align-items: center; min-width: 16rem; }
    #rf .addr code { flex: 1; min-width: 0; padding: .4rem .6rem; border-radius: .5rem; background: var(--bs-tertiary-bg); color: var(--bs-emphasis-color);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: .78rem; }
    #rf .target { font-size: .76rem; color: var(--bs-secondary-color); word-break: break-all; max-width: 26rem; }
    #rf .chip { display: inline-block; padding: .1rem .5rem; margin: .1rem .15rem .1rem 0; border-radius: 999px; font-size: .7rem;
        background: var(--bs-secondary-bg); color: var(--bs-emphasis-color); }
    #rf .kw { font-size: .74rem; color: var(--bs-secondary-color); max-width: 16rem; }
    #rf .kw b { color: var(--bs-emphasis-color); font-weight: 600; }
    #rf .ok { color: var(--bs-success-text-emphasis); }
    #rf .bad { color: var(--bs-danger-text-emphasis); }
    #rf tr.is-off td { opacity: .55; }
    #rf .form-switch .form-check-input { cursor: pointer; }
    #rf .actions { white-space: nowrap; }
    #rf tr.rf-parent td { background: var(--bs-tertiary-bg); }
    #rf tr.rf-section td:first-child { position: relative; padding-left: 2.4rem; }
    #rf tr.rf-section td:first-child::before { content: ''; position: absolute; left: 1.1rem; top: 0; bottom: 0; border-left: 2px solid var(--bs-primary-border-subtle); }
    #rf .rf-section-label { font-size: .68rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--bs-primary); }
    #rf tr.rf-group td { font-size: .72rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--bs-secondary-color); padding-top: 1.1rem; }
    #rf .rf-new-menu { min-width: 19rem; }
    #rf .rf-new-menu .dropdown-item { white-space: normal; padding-top: .5rem; padding-bottom: .5rem; }
    .rf-type-help { font-size: .78rem; }
    .rf-test { max-height: 22rem; overflow: auto; border: 1px solid var(--bs-border-color); border-radius: .6rem; }
    .rf-test .rf-row { display: flex; gap: .6rem; padding: .55rem .7rem; border-bottom: 1px solid var(--bs-border-color); }
    .rf-test .rf-row:last-child { border-bottom: 0; }
    .rf-test .rf-row.is-out { opacity: .5; }
    .rf-test img { width: 56px; height: 42px; object-fit: cover; border-radius: .35rem; flex: none; background: var(--bs-tertiary-bg); }
    .rf-test .rf-sum { white-space: pre-line; }
    .rf-sources { max-height: 16rem; overflow: auto; border: 1px solid var(--bs-border-color); border-radius: .6rem; padding: .4rem .7rem; }
</style>
@include('aniSensoAdmin.partials.dark')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Tools @endslot
        @slot('title') RSS Feeds @endslot
    @endcomponent

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert"><i class="bx bx-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div id="rf">
        <div class="row g-3 mb-3">
            <div class="col-sm-6 col-xl-3"><div class="stat"><b>{{ number_format($stats['feeds']) }}</b><span>feeds</span></div></div>
            <div class="col-sm-6 col-xl-3"><div class="stat"><b>{{ number_format($stats['sources']) }}</b><span>sources</span></div></div>
            <div class="col-sm-6 col-xl-3"><div class="stat"><b>{{ number_format($stats['items']) }}</b><span>stories kept (last {{ \App\Services\RssFeeds\RssRefresher::KEEP_DAYS }} days)</span></div></div>
            <div class="col-sm-6 col-xl-3"><div class="stat"><b>{{ number_format($stats['fresh']) }}</b><span>new stories in the last 24 hours</span></div></div>
        </div>

        {{-- Automatic reading --}}
        @php $running = $cron['lastRunAt'] && $cron['lastRunAt']->gt(now()->subMinutes(30)); @endphp
        <div class="card"><div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div style="max-width: 46rem;">
                    <h5 class="card-title mb-1 text-dark"><i class="bx {{ $running ? 'bx-check-circle text-success' : 'bx-error text-warning' }} me-1"></i>Automatic reading</h5>
                    @if ($running)
                        <p class="small text-secondary mb-0">The sources are being read on their own: last run <span class="text-dark">{{ $cron['lastRunAt']->diffForHumans() }}</span>, by {{ $cron['lastRunBy'] === 'cron' ? 'the cron address below' : "the app's scheduler" }}.</p>
                    @else
                        <p class="small text-dark mb-1">Nothing has read the sources on its own {{ $cron['lastRunAt'] ? 'since ' . $cron['lastRunAt']->diffForHumans() : 'yet' }}, so the feeds only catch up when someone opens them.</p>
                        <p class="small text-secondary mb-0">The app's scheduler is not running on the server. Either switch it on in Laravel Cloud, or give the address below to a cron service (for example cron-job.org, which is free) and have it call every 5 minutes. Calling it often is safe: each source is still read only when its own interval has passed.</p>
                    @endif
                </div>
                <div class="addr flex-grow-1" style="max-width: 34rem;">
                    <code id="rfCronUrl" title="{{ $cron['url'] }}">{{ $cron['url'] }}</code>
                    <button type="button" class="btn btn-light btn-sm rf-copy" id="rfCronCopy" data-url="{{ $cron['url'] }}" title="Copy the cron address"><i class="bx bx-copy"></i></button>
                    <button type="button" class="btn btn-light btn-sm" id="rfCronKey" title="Make a new address (the old one stops working)"><i class="bx bx-key"></i></button>
                </div>
            </div>
        </div></div>

        {{-- Feeds: one list. A feed with sections comes first with its sections right under it;
             the feeds that are no one's section follow. --}}
        <div class="card"><div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div style="max-width: 52rem;">
                    <h4 class="card-title mb-1 text-dark">Feeds</h4>
                    <p class="text-secondary mb-0 small">Each feed is one RSS address. A feed is made from sources (newest story first, through its own keyword filter), or from other feeds, which become its sections. Give the address to anything that reads RSS, the way you used an RSS.app address; opened in a browser, it shows the stories as a page.</p>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="bx bx-plus me-1"></i>New feed</button>
                    <ul class="dropdown-menu dropdown-menu-end rf-new-menu">
                        <li><a class="dropdown-item {{ $sources->isEmpty() ? 'disabled' : '' }}" href="#" id="rfNewBundle">
                            <span class="fw-semibold text-dark">From sources</span><span class="d-block small text-secondary">The stories of the sources you pick, through a keyword filter</span></a></li>
                        <li><a class="dropdown-item {{ $bundles->isEmpty() ? 'disabled' : '' }}" href="#" id="rfNewCompilation">
                            <span class="fw-semibold text-dark">With sections</span><span class="d-block small text-secondary">Other feeds at one address, one section each</span></a></li>
                    </ul>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Feed</th><th class="text-center" style="min-width: 7.5rem;">Stories shown</th><th>RSS address</th><th class="text-center">On</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($compilations as $c)
                        <tr class="rf-parent {{ $c->isActive ? '' : 'is-off' }}">
                            <td>
                                <div class="fw-semibold text-dark"><i class="bx bx-collection text-primary me-1"></i>{{ $c->bundleName }}</div>
                                @if ($c->usedFor)<span class="badge bg-primary-subtle text-primary mt-1">for {{ $c->usedFor }}</span>@endif
                                @if ($c->bundleDescription)<div class="small text-secondary mt-1">{{ $c->bundleDescription }}</div>@endif
                                <div class="small text-secondary mt-1">{{ $c->sections->count() }} {{ \Illuminate\Support\Str::plural('section', $c->sections->count()) }}, listed below it</div>
                            </td>
                            <td class="text-center">
                                <span class="text-dark">{{ $c->shownCount }}</span>
                                <div class="small text-secondary">of {{ $c->maxItems }}, {{ $c->maxAgeDays }} {{ \Illuminate\Support\Str::plural('day', $c->maxAgeDays) }}</div>
                                @if ($c->newestAt)<div class="small text-secondary">newest {{ $c->newestAt->diffForHumans() }}</div>@endif
                            </td>
                            <td>
                                <div class="addr">
                                    <code title="{{ $c->outputUrl() }}">{{ $c->outputUrl() }}</code>
                                    <button type="button" class="btn btn-light btn-sm rf-copy" data-url="{{ $c->outputUrl() }}" title="Copy the address"><i class="bx bx-copy"></i></button>
                                    <a class="btn btn-light btn-sm" href="{{ $c->outputUrl() }}" target="_blank" rel="noopener" title="Open the feed"><i class="bx bx-link-external"></i></a>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="form-check form-switch d-inline-block m-0">
                                    <input class="form-check-input rf-toggle" type="checkbox" data-url="{{ route('rss-feeds.bundle.toggle', $c->id) }}" {{ $c->isActive ? 'checked' : '' }} title="Serve this feed">
                                </div>
                            </td>
                            <td class="text-end actions">
                                <a class="btn btn-soft-primary btn-sm" href="{{ route('rss-feeds.preview', ['bundle' => $c->id]) }}" title="See the stories"><i class="bx bx-show"></i></a>
                                <button type="button" class="btn btn-soft-success btn-sm rf-refresh" data-url="{{ route('rss-feeds.bundle.refresh', $c->id) }}" title="Read the sources of every section now"><i class="bx bx-refresh"></i></button>
                                <button type="button" class="btn btn-soft-secondary btn-sm rf-edit-compilation" data-id="{{ $c->id }}" title="Edit (name, sections and their order)"><i class="bx bx-edit"></i></button>
                                <button type="button" class="btn btn-soft-danger btn-sm rf-delete" data-url="{{ route('rss-feeds.bundle.destroy', $c->id) }}" data-name="{{ $c->bundleName }}" data-what="feed with sections" title="Delete"><i class="bx bx-trash"></i></button>
                            </td>
                        </tr>
                        @foreach ($c->sections as $i => $b)
                            @include('rss-feeds._feed-row', ['b' => $b, 'sectionNo' => $i + 1, 'uid' => $c->id . '-' . $b->id])
                        @endforeach
                    @endforeach
                    @if ($compilations->isNotEmpty() && $standalone->isNotEmpty())
                        <tr class="rf-group"><td colspan="5">Feeds that are not a section</td></tr>
                    @endif
                    @foreach ($standalone as $b)
                        @include('rss-feeds._feed-row', ['b' => $b, 'sectionNo' => null, 'uid' => '0-' . $b->id])
                    @endforeach
                    @if ($compilations->isEmpty() && $standalone->isEmpty())
                        <tr><td colspan="5" class="text-center py-4">
                            <i class="bx bx-rss text-secondary" style="font-size: 2.2rem;"></i>
                            <p class="text-dark mt-2 mb-1">No feed yet.</p>
                            <small class="text-secondary">{{ $sources->isEmpty() ? 'Add a source first, then make a feed from it with "New feed".' : 'Put some of your sources together with "New feed".' }}</small>
                        </td></tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </div></div>

        {{-- Sources --}}
        <div class="card"><div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h4 class="card-title mb-1 text-dark">Sources</h4>
                    <p class="text-secondary mb-0 small">Where the stories come from. Each is read on its own schedule and every story is kept once. Facebook pages cannot be read (Facebook asks for a login); use the newsroom's website or a Google News search instead.</p>
                </div>
                <button type="button" class="btn btn-primary" id="rfNewSource"><i class="bx bx-plus me-1"></i>Add a source</button>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Source</th><th>Filter</th><th class="text-center">Stories</th><th>Last read</th><th class="text-center">On</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($sources as $s)
                        <tr class="{{ $s->isActive ? '' : 'is-off' }}">
                            <td>
                                <div class="fw-semibold text-dark">{{ $s->sourceName }}</div>
                                <span class="badge {{ ['rss' => 'bg-primary', 'google_news' => 'bg-info text-white', 'webpage' => 'bg-warning text-dark', 'page_watch' => 'bg-success'][$s->sourceType] ?? 'bg-secondary' }}">{{ $s->typeLabel() }}</span>
                                <div class="target mt-1">{{ $s->target() }}</div>
                            </td>
                            <td class="kw">
                                @if ($s->includeKeywords)<div><b>Only:</b> {{ \Illuminate\Support\Str::limit($s->includeKeywords, 90) }}</div>@endif
                                @if ($s->excludeKeywords)<div><b>Never:</b> {{ \Illuminate\Support\Str::limit($s->excludeKeywords, 90) }}</div>@endif
                                @if (! $s->includeKeywords && ! $s->excludeKeywords)<span class="text-secondary">Every story</span>@endif
                            </td>
                            <td class="text-center">
                                <span class="text-dark">{{ number_format($s->itemTotal) }}</span>
                                <div class="small text-secondary">{{ $s->itemFresh }} today</div>
                            </td>
                            <td class="small">
                                @if ($s->lastFetchedAt)
                                    <span class="text-dark">{{ $s->lastFetchedAt->format('M j, g:i A') }}</span>
                                    <div class="{{ $s->lastStatus === 'ok' ? 'ok' : 'bad' }}">{{ \Illuminate\Support\Str::limit($s->lastMessage, 140) }}</div>
                                    <div class="text-secondary">every {{ $s->refreshMinutes >= 60 ? ($s->refreshMinutes / 60) . ' ' . \Illuminate\Support\Str::plural('hour', $s->refreshMinutes / 60) : $s->refreshMinutes . ' minutes' }}</div>
                                @else
                                    <span class="text-secondary">Not read yet</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="form-check form-switch d-inline-block m-0">
                                    <input class="form-check-input rf-toggle" type="checkbox" data-url="{{ route('rss-feeds.source.toggle', $s->id) }}" {{ $s->isActive ? 'checked' : '' }} title="Read this source">
                                </div>
                            </td>
                            <td class="text-end actions">
                                <a class="btn btn-soft-primary btn-sm" href="{{ route('rss-feeds.preview', ['source' => $s->id]) }}" title="See its stories"><i class="bx bx-show"></i></a>
                                <button type="button" class="btn btn-soft-success btn-sm rf-refresh" data-url="{{ route('rss-feeds.source.refresh', $s->id) }}" title="Read it now"><i class="bx bx-refresh"></i></button>
                                <button type="button" class="btn btn-soft-secondary btn-sm rf-edit-source" data-id="{{ $s->id }}" title="Edit"><i class="bx bx-edit"></i></button>
                                <button type="button" class="btn btn-soft-danger btn-sm rf-delete" data-url="{{ route('rss-feeds.source.destroy', $s->id) }}" data-name="{{ $s->sourceName }}" data-what="source" title="Delete"><i class="bx bx-trash"></i></button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-4">
                            <i class="bx bx-news text-secondary" style="font-size: 2.2rem;"></i>
                            <p class="text-dark mt-2 mb-1">No source yet.</p>
                            <small class="text-secondary">Add an RSS feed, a Google News search, a news page or a page to watch.</small>
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div></div>
    </div>

    {{-- Source form --}}
    <div class="modal fade" id="sourceModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <form class="modal-content" id="sourceForm" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title text-dark"><i class="bx bx-news me-2"></i><span id="sourceModalTitle">Add a source</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger d-none" id="sourceError"></div>
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label" for="sfName">Name <span class="text-danger">*</span></label>
                            <input class="form-control" id="sfName" name="sourceName" maxlength="190" placeholder="e.g. Philstar Agriculture">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="sfType">Type</label>
                            <select class="form-select" id="sfType" name="sourceType">
                                @foreach (\App\Models\RssSource::TYPES as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="rf-type-help text-body-secondary" data-help="rss">Any RSS or Atom feed. Many news sites have one at /feed or /rss.</div>
                            <div class="rf-type-help text-body-secondary d-none" data-help="google_news">Searches Google News, like RSS.app's keyword feeds. Each story's link is turned back into the newsroom's own address. Use <code>site:pna.gov.ph</code> to stay on one site, and OR between words.</div>
                            <div class="rf-type-help text-body-secondary d-none" data-help="webpage">For a news site with no feed: its news page is read and each article link on it becomes a story. The article's own page gives the title, date and picture.</div>
                            <div class="rf-type-help text-body-secondary d-none" data-help="page_watch">For a page that is rewritten in place, like PAGASA's forecast: every new version of the watched part becomes one story.</div>
                        </div>

                        <div class="col-12 rf-field" data-types="rss webpage page_watch">
                            <label class="form-label" for="sfUrl">Address <span class="text-danger">*</span></label>
                            <input class="form-control" id="sfUrl" name="sourceUrl" type="url" maxlength="1000" placeholder="https://">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-8 rf-field" data-types="google_news">
                            <label class="form-label" for="sfQuery">Search for <span class="text-danger">*</span></label>
                            <input class="form-control" id="sfQuery" name="searchQuery" maxlength="500" placeholder='e.g. palay OR rice price Philippines'>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-2 col-6 rf-field" data-types="google_news">
                            <label class="form-label" for="sfRegion">Country</label>
                            <input class="form-control text-uppercase" id="sfRegion" name="region" maxlength="2" value="PH">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-2 col-6 rf-field" data-types="google_news">
                            <label class="form-label" for="sfDays">Last</label>
                            <div class="input-group"><input class="form-control" id="sfDays" name="withinDays" type="number" min="1" max="30" value="7"><span class="input-group-text">days</span></div>
                        </div>

                        <div class="col-md-6 rf-field" data-types="webpage">
                            <label class="form-label" for="sfLinkSel">Article links (CSS selector) <span class="text-secondary small">optional</span></label>
                            <input class="form-control" id="sfLinkSel" name="linkSelector" maxlength="300" placeholder="e.g. h2.entry-title a">
                            <div class="invalid-feedback"></div>
                            <div class="form-text text-body-secondary">Left empty, links that read like articles are taken, menus and footers left out.</div>
                        </div>
                        <div class="col-md-6 rf-field" data-types="webpage">
                            <label class="form-label" for="sfLinkHas">Only links containing <span class="text-secondary small">optional</span></label>
                            <input class="form-control" id="sfLinkHas" name="linkContains" maxlength="300" placeholder="e.g. /news/">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-12 rf-field" data-types="page_watch">
                            <label class="form-label" for="sfContentSel">Part of the page to watch (CSS selector) <span class="text-secondary small">optional</span></label>
                            <input class="form-control" id="sfContentSel" name="contentSelector" maxlength="300" placeholder="e.g. .article-content">
                            <div class="invalid-feedback"></div>
                            <div class="form-text text-body-secondary">Left empty, the page's main text is watched. Pick the part that changes only when there is news, so clocks and counters do not count as updates.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="sfInclude">Only stories with these words</label>
                            <textarea class="form-control" id="sfInclude" name="includeKeywords" rows="3" placeholder="farm*, palay, rice, fertilizer"></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="sfExclude">Never stories with these words</label>
                            <textarea class="form-control" id="sfExclude" name="excludeKeywords" rows="3" placeholder="showbiz, lotto"></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12"><div class="form-text text-body-secondary mt-0">Separate words with commas. Whole words, any case: <code>rice</code> does not match "price". End a word with * for its longer forms: <code>farm*</code> matches farmer and farming. Leave both empty for every story.</div></div>

                        <div class="col-md-4">
                            <label class="form-label" for="sfEvery">Read it every</label>
                            <select class="form-select" id="sfEvery" name="refreshMinutes">
                                <option value="15">15 minutes</option>
                                <option value="30" selected>30 minutes</option>
                                <option value="60">hour</option>
                                <option value="120">2 hours</option>
                                <option value="360">6 hours</option>
                                <option value="720">12 hours</option>
                                <option value="1440">day</option>
                            </select>
                        </div>
                        <div class="col-md-8 rf-field d-flex align-items-end" data-types="rss google_news webpage">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="sfDetails" name="fetchDetails" value="1" checked>
                                <label class="form-check-label" for="sfDetails">Fill in missing pictures and summaries from each story's own page</label>
                            </div>
                        </div>

                        @if ($bundles->isNotEmpty())
                            <div class="col-12">
                                <label class="form-label">Put it in these feeds</label>
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach ($bundles as $b)
                                        <div class="form-check">
                                            <input class="form-check-input sf-bundle" type="checkbox" name="bundleIds[]" value="{{ $b->id }}" id="sfB{{ $b->id }}">
                                            <label class="form-check-label" for="sfB{{ $b->id }}">{{ $b->bundleName }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="col-12 d-none" id="sourceTestBox">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label mb-0">What it gives now</label>
                                <small class="text-secondary" id="sourceTestNote"></small>
                            </div>
                            <div class="rf-test" id="sourceTest"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-outline-primary" id="sourceTestBtn"><i class="bx bx-test-tube me-1"></i>Test it</button>
                    <button type="submit" class="btn btn-primary" id="sourceSaveBtn"><i class="bx bx-save me-1"></i>Save</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Feed from sources: form --}}
    <div class="modal fade" id="bundleModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <form class="modal-content" id="bundleForm" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title text-dark"><i class="bx bx-rss me-2"></i><span id="bundleModalTitle">New feed from sources</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger d-none" id="bundleError"></div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="bfName">Name <span class="text-danger">*</span></label>
                            <input class="form-control" id="bfName" name="bundleName" maxlength="190" placeholder="e.g. Agriculture news PH">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="bfDesc">Description <span class="text-secondary small">optional</span></label>
                            <input class="form-control" id="bfDesc" name="bundleDescription" maxlength="500" placeholder="Shown as the feed's description">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Sources <span class="text-danger">*</span></label>
                            <div class="rf-sources" id="bfSources">
                                @foreach ($sources as $s)
                                    <div class="form-check my-1">
                                        <input class="form-check-input bf-source" type="checkbox" name="sourceIds[]" value="{{ $s->id }}" id="bfS{{ $s->id }}">
                                        <label class="form-check-label" for="bfS{{ $s->id }}"><span class="text-dark">{{ $s->sourceName }}</span> <small class="text-secondary">· {{ $s->typeLabel() }}{{ $s->isActive ? '' : ' · paused' }}</small></label>
                                    </div>
                                @endforeach
                            </div>
                            <div class="invalid-feedback d-block" id="bfSourcesError"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="bfInclude">Only stories with these words</label>
                            <textarea class="form-control" id="bfInclude" name="includeKeywords" rows="3" placeholder="diesel, gasoline, fuel price*, rollback"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="bfExclude">Never stories with these words</label>
                            <textarea class="form-control" id="bfExclude" name="excludeKeywords" rows="3"></textarea>
                        </div>
                        <div class="col-12"><div class="form-text text-body-secondary mt-0">Applied to every source in the feed, after each source's own filter. Same rules: commas between words, whole words, * for longer forms.</div></div>
                        <div class="col-md-6">
                            <label class="form-label" for="bfMax">Show at most</label>
                            <div class="input-group"><input class="form-control" id="bfMax" name="maxItems" type="number" min="5" max="200" value="50"><span class="input-group-text">stories</span></div>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="bfAge">From the last</label>
                            <div class="input-group"><input class="form-control" id="bfAge" name="maxAgeDays" type="number" min="1" max="60" value="7"><span class="input-group-text">days</span></div>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="bundleSaveBtn"><i class="bx bx-save me-1"></i>Save</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Feed with sections: form --}}
    <div class="modal fade" id="compilationModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <form class="modal-content" id="compilationForm" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title text-dark"><i class="bx bx-collection me-2"></i><span id="compilationModalTitle">New feed with sections</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger d-none" id="compilationError"></div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="cfName">Name <span class="text-danger">*</span></label>
                            <input class="form-control" id="cfName" name="bundleName" maxlength="190" placeholder="e.g. All farm news">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="cfUsedFor">Used for</label>
                            <input class="form-control" id="cfUsedFor" name="usedFor" maxlength="190" list="cfUsedForList" placeholder="e.g. anee.io Latest in Agriculture">
                            <datalist id="cfUsedForList">
                                @foreach ($compilations->pluck('usedFor')->filter()->unique() as $use)<option value="{{ $use }}">@endforeach
                            </datalist>
                            <div class="invalid-feedback"></div>
                            <div class="form-text text-body-secondary">Shown as a tag on the feed, so you can see what each feed is for.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="cfDesc">Description <span class="text-secondary small">optional</span></label>
                            <input class="form-control" id="cfDesc" name="bundleDescription" maxlength="500" placeholder="Shown as the feed's description">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Sections, in order <span class="text-danger">*</span></label>
                            <div class="rf-sources" id="cfMembers">
                                @foreach ($bundles as $b)
                                    <div class="cf-row d-flex align-items-center gap-2 py-1" data-id="{{ $b->id }}">
                                        <div class="form-check m-0 flex-grow-1">
                                            <input class="form-check-input cf-member" type="checkbox" name="memberIds[]" value="{{ $b->id }}" id="cfM{{ $b->id }}">
                                            <label class="form-check-label" for="cfM{{ $b->id }}"><span class="text-dark">{{ $b->bundleName }}</span> <small class="text-secondary">· {{ $b->sources->count() }} {{ \Illuminate\Support\Str::plural('source', $b->sources->count()) }}{{ $b->isActive ? '' : ' · paused' }}</small></label>
                                        </div>
                                        <button type="button" class="btn btn-light btn-sm cf-up" title="Move up"><i class="bx bx-chevron-up"></i></button>
                                        <button type="button" class="btn btn-light btn-sm cf-down" title="Move down"><i class="bx bx-chevron-down"></i></button>
                                    </div>
                                @endforeach
                            </div>
                            <div class="invalid-feedback d-block" id="cfMembersError"></div>
                            <div class="form-text text-body-secondary">Each section is one of your feeds and keeps its own sources and filters. Tick the ones to use; the arrows set their order.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="cfMax">Show at most</label>
                            <div class="input-group"><input class="form-control" id="cfMax" name="maxItems" type="number" min="5" max="300" value="150"><span class="input-group-text">stories</span></div>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="cfAge">From the last</label>
                            <div class="input-group"><input class="form-control" id="cfAge" name="maxAgeDays" type="number" min="1" max="60" value="7"><span class="input-group-text">days</span></div>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="compilationSaveBtn"><i class="bx bx-save me-1"></i>Save</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete --}}
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark"><i class="bx bx-trash text-danger me-2"></i>Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-dark mb-1">Delete the <span id="deleteItemWhat"></span> <strong id="deleteItemName"></strong>?</p>
                    <small class="text-secondary" id="deleteItemNote"></small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
(() => {
    toastr.options = { closeButton: true, progressBar: true, positionClass: 'toast-top-right', timeOut: 4000, escapeHtml: true };

    const SOURCES = @json($sourceForms);
    const BUNDLES = @json($bundleForms);
    const COMPILATIONS = @json($compilationForms);
    const URLS = {
        sourceStore: @json(route('rss-feeds.source.store')),
        sourceTest: @json(route('rss-feeds.source.test')),
        sourceUpdate: @json(url('/rss-feeds-source')) + '/',
        bundleStore: @json(route('rss-feeds.bundle.store')),
        bundleUpdate: @json(url('/rss-feeds-bundle')) + '/',
        compilationStore: @json(route('rss-feeds.compilation.store')),
        compilationUpdate: @json(url('/rss-feeds-compilation')) + '/',
    };
    const DELETE_NOTES = {
        'source': 'Its stories leave every feed it was in.',
        'feed': 'Its RSS address stops working, so anything reading it gets nothing, and it stops being a section of any other feed.',
        'feed with sections': 'Its RSS address stops working. The feeds that were its sections stay as they are.',
    };

    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const fail = (xhr, fallback) => toastr.error(xhr.responseJSON?.message || fallback, 'Error!');
    const busy = ($btn, on, html) => { if (on) { $btn.data('html', $btn.html()).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> ' + html); } else { $btn.prop('disabled', false).html($btn.data('html')); } };

    function showErrors($form, $box, xhr) {
        $form.find('.is-invalid').removeClass('is-invalid');
        const errors = xhr.responseJSON?.errors || {};
        Object.keys(errors).forEach((field) => {
            const $input = $form.find('[name="' + field + '"], [name="' + field + '[]"]').first();
            if ($input.length && !$input.is(':checkbox')) {
                $input.addClass('is-invalid');
                $input.closest('[class*="col-"]').find('.invalid-feedback').first().text(errors[field][0]);
            }
        });
        $box.removeClass('d-none').text(xhr.responseJSON?.message || 'That could not be saved.');
    }

    // ---------------- Sources ----------------
    const $sModal = $('#sourceModal');
    const $sForm = $('#sourceForm');
    let editingSource = null;

    function showType() {
        const type = $('#sfType').val();
        $('.rf-field').each(function () { $(this).toggleClass('d-none', !String($(this).data('types')).split(' ').includes(type)); });
        $('.rf-type-help').each(function () { $(this).toggleClass('d-none', $(this).data('help') !== type); });
    }
    $('#sfType').on('change', showType);

    // The fields of the other types are hidden, not gone: leave them out of what is sent.
    function sourceData() {
        const $hidden = $sForm.find('.rf-field.d-none :input').prop('disabled', true);
        const data = $sForm.serialize();
        $hidden.prop('disabled', false);
        return data;
    }

    function openSource(id) {
        editingSource = id || null;
        const s = id ? SOURCES[id] : null;
        $sForm[0].reset();
        $sForm.find('.is-invalid').removeClass('is-invalid');
        $('#sourceError').addClass('d-none');
        $('#sourceTestBox').addClass('d-none');
        $('#sourceModalTitle').text(s ? 'Edit ' + s.sourceName : 'Add a source');
        $('#sfName').val(s?.sourceName || '');
        $('#sfType').val(s?.sourceType || 'rss');
        $('#sfUrl').val(s?.sourceUrl || '');
        $('#sfQuery').val(s?.searchQuery || '');
        $('#sfRegion').val(s?.settings?.region || 'PH');
        $('#sfDays').val(s?.settings?.withinDays || 7);
        $('#sfLinkSel').val(s?.settings?.linkSelector || '');
        $('#sfLinkHas').val(s?.settings?.linkContains || '');
        $('#sfContentSel').val(s?.settings?.contentSelector || '');
        $('#sfInclude').val(s?.includeKeywords || '');
        $('#sfExclude').val(s?.excludeKeywords || '');
        $('#sfEvery').val(String(s?.refreshMinutes || 30));
        $('#sfDetails').prop('checked', s ? !!s.fetchDetails : true);
        $('.sf-bundle').each(function () { $(this).prop('checked', !!s && s.bundleIds.includes(Number(this.value))); });
        showType();
        $sModal.modal('show');
    }
    $('#rfNewSource').on('click', () => openSource(null));
    $('.rf-edit-source').on('click', function () { openSource($(this).data('id')); });

    $('#sourceTestBtn').on('click', function () {
        const $btn = $(this);
        busy($btn, true, 'Reading...');
        $('#sourceError').addClass('d-none');
        $.post(URLS.sourceTest, sourceData())
            .done((res) => {
                const items = res.data.items || [];
                $('#sourceTestNote').text(res.message);
                $('#sourceTest').html(items.length ? items.map((i) => `
                    <div class="rf-row ${i.passes ? '' : 'is-out'}">
                        ${i.image ? `<img src="${esc(i.image)}" alt="" loading="lazy" referrerpolicy="no-referrer">` : ''}
                        <div class="min-w-0">
                            <a href="${esc(i.link)}" target="_blank" rel="noopener" class="fw-semibold text-dark">${esc(i.title)}</a>
                            <div class="small text-secondary">${esc(i.publisher)}${i.date ? ' · ' + esc(i.date) : ''}${i.passes ? '' : ' · <span class="text-danger">left out by the filter</span>'}</div>
                            ${i.summary ? `<div class="small text-dark rf-sum">${esc(i.summary)}</div>` : ''}
                        </div>
                    </div>`).join('') : '<div class="p-3 text-secondary">It answered, but gave no stories.</div>');
                $('#sourceTestBox').removeClass('d-none');
            })
            .fail((xhr) => showErrors($sForm, $('#sourceError'), xhr))
            .always(() => busy($btn, false));
    });

    $sForm.on('submit', function (e) {
        e.preventDefault();
        const $btn = $('#sourceSaveBtn');
        busy($btn, true, 'Saving...');
        const data = sourceData() + (editingSource ? '&_method=PUT' : '');
        $.post(editingSource ? URLS.sourceUpdate + editingSource : URLS.sourceStore, data)
            .done((res) => {
                $sModal.modal('hide');
                const read = !editingSource || res.data?.moved;
                if (!read) { toastr.success(res.message, 'Saved'); setTimeout(() => location.reload(), 600); return; }
                toastr.info('Reading it for the first time...', res.message);
                $.post(URLS.sourceUpdate + res.data.id + '/refresh')
                    .done((r) => toastr.success(r.message, 'Read'))
                    .fail((xhr) => toastr.warning(xhr.responseJSON?.message || 'The first read failed. It will be tried again.', 'Saved, but'))
                    .always(() => setTimeout(() => location.reload(), 900));
            })
            .fail((xhr) => showErrors($sForm, $('#sourceError'), xhr))
            .always(() => busy($btn, false));
    });

    // ---------------- Feeds from sources ----------------
    const $bModal = $('#bundleModal');
    const $bForm = $('#bundleForm');
    let editingBundle = null;

    function openBundle(id) {
        editingBundle = id || null;
        const b = id ? BUNDLES[id] : null;
        $bForm[0].reset();
        $bForm.find('.is-invalid').removeClass('is-invalid');
        $('#bundleError').addClass('d-none');
        $('#bfSourcesError').text('');
        $('#bundleModalTitle').text(b ? 'Edit ' + b.bundleName : 'New feed from sources');
        $('#bfName').val(b?.bundleName || '');
        $('#bfDesc').val(b?.bundleDescription || '');
        $('#bfInclude').val(b?.includeKeywords || '');
        $('#bfExclude').val(b?.excludeKeywords || '');
        $('#bfMax').val(b?.maxItems || 50);
        $('#bfAge').val(b?.maxAgeDays || 7);
        $('.bf-source').each(function () { $(this).prop('checked', !!b && b.sourceIds.includes(Number(this.value))); });
        $bModal.modal('show');
    }
    $('#rfNewBundle').on('click', (e) => { e.preventDefault(); if (!$(e.currentTarget).hasClass('disabled')) openBundle(null); });
    $('.rf-edit-bundle').on('click', function () { openBundle($(this).data('id')); });

    $bForm.on('submit', function (e) {
        e.preventDefault();
        const $btn = $('#bundleSaveBtn');
        busy($btn, true, 'Saving...');
        const data = $bForm.serialize() + (editingBundle ? '&_method=PUT' : '');
        $.post(editingBundle ? URLS.bundleUpdate + editingBundle : URLS.bundleStore, data)
            .done((res) => { $bModal.modal('hide'); toastr.success(res.message, 'Saved'); setTimeout(() => location.reload(), 600); })
            .fail((xhr) => {
                showErrors($bForm, $('#bundleError'), xhr);
                $('#bfSourcesError').text(xhr.responseJSON?.errors?.sourceIds?.[0] || '');
            })
            .always(() => busy($btn, false));
    });

    // ---------------- Cron address ----------------
    $('#rfCronKey').on('click', function () {
        if (!confirm('Make a new cron address? The current one stops working, so the cron service must be given the new one.')) return;
        const $btn = $(this);
        busy($btn, true, '');
        $.post(@json(route('rss-feeds.cron-key')))
            .done((res) => {
                $('#rfCronUrl').text(res.data.url).attr('title', res.data.url);
                $('#rfCronCopy').data('url', res.data.url);
                toastr.success(res.message, 'New address');
            })
            .fail((xhr) => fail(xhr, 'A new address could not be made.'))
            .always(() => busy($btn, false));
    });

    // ---------------- Feeds with sections ----------------
    const $cModal = $('#compilationModal');
    const $cForm = $('#compilationForm');
    let editingCompilation = null;

    function openCompilation(id) {
        editingCompilation = id || null;
        const c = id ? COMPILATIONS[id] : null;
        $cForm[0].reset();
        $cForm.find('.is-invalid').removeClass('is-invalid');
        $('#compilationError').addClass('d-none');
        $('#cfMembersError').text('');
        $('#compilationModalTitle').text(c ? 'Edit ' + c.bundleName : 'New feed with sections');
        $('#cfName').val(c?.bundleName || '');
        $('#cfUsedFor').val(c?.usedFor || '');
        $('#cfDesc').val(c?.bundleDescription || '');
        $('#cfMax').val(c?.maxItems || 150);
        $('#cfAge').val(c?.maxAgeDays || 7);
        // A new feed with sections starts with every feed ticked. The rows are put in the
        // feed's section order, the ones it does not use after them.
        const $list = $('#cfMembers');
        const rank = (id) => { const i = c ? c.memberIds.indexOf(Number(id)) : -1; return i === -1 ? 1000 : i; };
        $list.children('.cf-row').sort((a, b) => rank(a.dataset.id) - rank(b.dataset.id)).appendTo($list);
        $('.cf-member').each(function () { $(this).prop('checked', c ? c.memberIds.includes(Number(this.value)) : true); });
        $cModal.modal('show');
    }
    $('#rfNewCompilation').on('click', (e) => { e.preventDefault(); if (!$(e.currentTarget).hasClass('disabled')) openCompilation(null); });
    $('.rf-edit-compilation').on('click', function () { openCompilation($(this).data('id')); });
    // Moving a row moves its section: memberIds[] is sent in the order of the rows.
    $('#cfMembers').on('click', '.cf-up, .cf-down', function () {
        const $row = $(this).closest('.cf-row');
        if ($(this).hasClass('cf-up')) { $row.prev('.cf-row').before($row); } else { $row.next('.cf-row').after($row); }
    });

    $cForm.on('submit', function (e) {
        e.preventDefault();
        const $btn = $('#compilationSaveBtn');
        busy($btn, true, 'Saving...');
        const data = $cForm.serialize() + (editingCompilation ? '&_method=PUT' : '');
        $.post(editingCompilation ? URLS.compilationUpdate + editingCompilation : URLS.compilationStore, data)
            .done((res) => { $cModal.modal('hide'); toastr.success(res.message, 'Saved'); setTimeout(() => location.reload(), 600); })
            .fail((xhr) => {
                showErrors($cForm, $('#compilationError'), xhr);
                $('#cfMembersError').text(xhr.responseJSON?.errors?.memberIds?.[0] || '');
            })
            .always(() => busy($btn, false));
    });

    // ---------------- Shared ----------------
    $('.rf-toggle').on('change', function () {
        const box = this;
        box.disabled = true;
        $.post($(box).data('url'), { isActive: box.checked ? 1 : 0 })
            .done((res) => { $(box).closest('tr').toggleClass('is-off', !box.checked); toastr.success(res.message); })
            .fail((xhr) => { box.checked = !box.checked; fail(xhr, 'That could not be saved.'); })
            .always(() => { box.disabled = false; });
    });

    $('.rf-refresh').on('click', function () {
        const $btn = $(this);
        busy($btn, true, '');
        $.post($btn.data('url'))
            .done((res) => { toastr.success(res.message, 'Read'); setTimeout(() => location.reload(), 1200); })
            .fail((xhr) => { fail(xhr, 'It could not be read.'); setTimeout(() => location.reload(), 2500); })
            .always(() => busy($btn, false));
    });

    $('.rf-copy').on('click', async function () {
        const $btn = $(this);
        try { await navigator.clipboard.writeText($btn.data('url')); $btn.html('<i class="bx bx-check"></i>'); setTimeout(() => $btn.html('<i class="bx bx-copy"></i>'), 1500); }
        catch (_) { toastr.info($btn.data('url'), 'Copy this address'); }
    });

    let toDelete = null;
    $('.rf-delete').on('click', function () {
        toDelete = { url: $(this).data('url'), row: $(this).closest('tr') };
        $('#deleteItemWhat').text($(this).data('what'));
        $('#deleteItemName').text($(this).data('name'));
        $('#deleteItemNote').text(DELETE_NOTES[$(this).data('what')] || '');
        $('#deleteModal').modal('show');
    });
    $('#confirmDelete').on('click', function () {
        if (!toDelete) return;
        const $btn = $(this);
        busy($btn, true, 'Deleting...');
        $.ajax({ url: toDelete.url, type: 'DELETE' })
            .done((res) => { $('#deleteModal').modal('hide'); toastr.success(res.message, 'Deleted'); setTimeout(() => location.reload(), 700); })
            .fail((xhr) => fail(xhr, 'It could not be deleted.'))
            .always(() => { busy($btn, false); toDelete = null; });
    });
})();
</script>
@endsection
