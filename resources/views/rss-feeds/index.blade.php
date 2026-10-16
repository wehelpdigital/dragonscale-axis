@extends('layouts.master')

@section('title') RSS Feeds @endsection

@section('css')
@include('rss-feeds._styles')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') RSS Feeds @endslot
        @slot('title') Feeds @endslot
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

        {{-- The full status and the cron address are on the Sources page; here only when something is wrong. --}}
        @if (! ($cron['lastRunAt'] && $cron['lastRunAt']->gt(now()->subMinutes(30))))
            <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span class="text-dark"><i class="bx bx-error me-1"></i>Nothing has read the sources on its own {{ $cron['lastRunAt'] ? 'since ' . $cron['lastRunAt']->diffForHumans() : 'yet' }}, so these feeds only catch up when someone opens them.</span>
                <a class="btn btn-sm btn-warning" href="{{ route('rss-feeds.sources') }}">Set up automatic reading</a>
            </div>
        @endif

        {{-- Feeds: one list. A feed with sections comes first with its sections right under it;
             the feeds that are no one's section follow. --}}
        <div class="card"><div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div style="max-width: 52rem;">
                    <h4 class="card-title mb-1 text-dark">Feeds</h4>
                    <p class="text-secondary mb-0 small">Each feed is one RSS address. A feed is made from sources (newest story first, through its own keyword filter; the sources are on the <a href="{{ route('rss-feeds.sources') }}">Sources</a> page), or from other feeds, which become its sections. Give the address to anything that reads RSS, the way you used an RSS.app address; opened in a browser, it shows the stories as a page.</p>
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
                                <button type="button" class="btn btn-sm btn-soft-primary rf-sections-toggle mt-2" data-parent="{{ $c->id }}" aria-expanded="true" title="Show or hide its sections">
                                    <i class="bx bx-chevron-down"></i>
                                    <span class="rf-toggle-label" data-shown="Hide the {{ $c->sections->count() }} {{ \Illuminate\Support\Str::plural('section', $c->sections->count()) }}" data-hidden="Show the {{ $c->sections->count() }} {{ \Illuminate\Support\Str::plural('section', $c->sections->count()) }}">Hide the {{ $c->sections->count() }} {{ \Illuminate\Support\Str::plural('section', $c->sections->count()) }}</span>
                                </button>
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
                            @include('rss-feeds._feed-row', ['b' => $b, 'sectionNo' => $i + 1, 'parentId' => $c->id, 'uid' => $c->id . '-' . $b->id])
                        @endforeach
                    @endforeach
                    @if ($compilations->isNotEmpty() && $standalone->isNotEmpty())
                        <tr class="rf-group"><td colspan="5">Feeds that are not a section</td></tr>
                    @endif
                    @foreach ($standalone as $b)
                        @include('rss-feeds._feed-row', ['b' => $b, 'sectionNo' => null, 'parentId' => null, 'uid' => '0-' . $b->id])
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

    @include('rss-feeds._delete-modal')
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
(() => {
    @include('rss-feeds._script-helpers')

    const BUNDLES = @json($bundleForms);
    const COMPILATIONS = @json($compilationForms);
    const URLS = {
        bundleStore: @json(route('rss-feeds.bundle.store')),
        bundleUpdate: @json(url('/rss-feeds-bundle')) + '/',
        compilationStore: @json(route('rss-feeds.compilation.store')),
        compilationUpdate: @json(url('/rss-feeds-compilation')) + '/',
    };

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

    // ---------------- A feed's sections: show / hide ----------------
    // Open or folded is remembered per feed in this browser (a convenience only).
    function setSections(btn, open, animate) {
        const rows = document.querySelectorAll('#rf tr.rf-section[data-parent="' + btn.dataset.parent + '"]');
        if (!animate) rows.forEach((r) => r.classList.add('no-anim'));
        rows.forEach((r) => {
            r.classList.toggle('is-collapsed', !open);
            r.inert = !open;
        });
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        const label = btn.querySelector('.rf-toggle-label');
        label.textContent = open ? label.dataset.shown : label.dataset.hidden;
        if (!animate) requestAnimationFrame(() => requestAnimationFrame(() => rows.forEach((r) => r.classList.remove('no-anim'))));
    }
    document.querySelectorAll('.rf-sections-toggle').forEach((btn) => {
        let open = true;
        try { open = localStorage.getItem('rf.sections.' + btn.dataset.parent) !== '0'; } catch (_) {}
        setSections(btn, open, false);
        btn.addEventListener('click', () => {
            const next = btn.getAttribute('aria-expanded') !== 'true';
            setSections(btn, next, true);
            try { localStorage.setItem('rf.sections.' + btn.dataset.parent, next ? '1' : '0'); } catch (_) {}
        });
    });

    @include('rss-feeds._script-shared')
})();
</script>
@endsection
