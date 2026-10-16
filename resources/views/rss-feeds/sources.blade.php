@extends('layouts.master')

@section('title') RSS Sources @endsection

@section('css')
@include('rss-feeds._styles')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') RSS Feeds @endslot
        @slot('title') Sources @endslot
    @endcomponent

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert"><i class="bx bx-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div id="rf">
        <div class="row g-3 mb-3">
            <div class="col-sm-6 col-xl-4"><div class="stat"><b>{{ number_format($stats['sources']) }}</b><span>sources</span></div></div>
            <div class="col-sm-6 col-xl-4"><div class="stat"><b>{{ number_format($stats['items']) }}</b><span>stories kept (last {{ \App\Services\RssFeeds\RssRefresher::KEEP_DAYS }} days)</span></div></div>
            <div class="col-sm-12 col-xl-4"><div class="stat"><b>{{ number_format($stats['fresh']) }}</b><span>new stories in the last 24 hours</span></div></div>
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

    @include('rss-feeds._delete-modal')
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
(() => {
    @include('rss-feeds._script-helpers')

    const SOURCES = @json($sourceForms);
    const URLS = {
        sourceStore: @json(route('rss-feeds.source.store')),
        sourceTest: @json(route('rss-feeds.source.test')),
        sourceUpdate: @json(url('/rss-feeds-source')) + '/',
    };

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

    @include('rss-feeds._script-shared')
})();
</script>
@endsection
