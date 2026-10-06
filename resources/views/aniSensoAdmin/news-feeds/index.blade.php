@extends('layouts.master')

@section('title') Latest in Agriculture @endsection

@section('css')
<style>
    /* Scoped under #nf: this page's styles load before Bootstrap's. */
    #nf .stat { border-radius: .9rem; padding: .9rem 1rem; background: var(--bs-tertiary-bg); height: 100%; }
    #nf .stat b { display: block; font-size: 1.35rem; color: var(--bs-emphasis-color); }
    #nf .stat span { font-size: .8rem; color: var(--bs-secondary-color); }
    #nf .cron { display: flex; gap: .5rem; align-items: center; }
    #nf .cron code { flex: 1; min-width: 0; padding: .55rem .7rem; border-radius: .6rem; background: var(--bs-tertiary-bg); color: var(--bs-emphasis-color);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: .82rem; }
    #nf .feed-url { font-size: .78rem; color: var(--bs-secondary-color); word-break: break-all; }
    #nf .st { display: inline-block; padding: .15rem .55rem; border-radius: 999px; font-size: .72rem; font-weight: 700; }
    #nf .st.created { background: var(--bs-success-bg-subtle); color: var(--bs-success-text-emphasis); }
    #nf .st.skipped { background: var(--bs-secondary-bg-subtle); color: var(--bs-secondary-text-emphasis); }
    #nf .st.failed { background: var(--bs-danger-bg-subtle); color: var(--bs-danger-text-emphasis); }
    #nf .st.running { background: var(--bs-warning-bg-subtle); color: var(--bs-warning-text-emphasis); }
    #nf .ok { color: var(--bs-success-text-emphasis); }
    #nf .bad { color: var(--bs-danger-text-emphasis); }
    #nf tr.is-off td { opacity: .55; }
    #nf .form-switch .form-check-input { cursor: pointer; }
</style>
@include('aniSensoAdmin.partials.dark')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') AniSystem @endslot
        @slot('li_2') Website @endslot
        @slot('title') Latest in Agriculture @endslot
    @endcomponent

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div id="nf">
    @unless ($ready)
        <div class="alert alert-warning">The news tables (<code>as_news_feeds</code>) are not in the database yet. anee.io has to deploy its migration first.</div>
    @else
        <div class="row g-3 mb-3">
            <div class="col-sm-6 col-xl-3"><div class="stat"><b>{{ number_format($stats['roundups']) }}</b><span>roundups written</span></div></div>
            <div class="col-sm-6 col-xl-3"><div class="stat"><b>{{ number_format($stats['items']) }}</b><span>stories read from the feeds</span></div></div>
            <div class="col-sm-6 col-xl-3"><div class="stat"><b>{{ number_format($stats['featured']) }}</b><span>stories featured (never again)</span></div></div>
            <div class="col-sm-6 col-xl-3"><div class="stat"><b>{{ number_format($stats['waiting']) }}</b><span>new stories waiting (last 10 days)</span></div></div>
        </div>

        <div class="row g-3">
            <div class="col-xl-8">
                <div class="card"><div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                        <div>
                            <h4 class="card-title mb-1 text-dark">The feeds</h4>
                            <p class="text-secondary mb-0 small">Every few days anee.io reads these RSS feeds, keeps each story once, and Anee writes a roundup of the new ones under Latest in Agriculture: grouped by theme, each story with what it means for a farmer and a link to the full report. A story is never featured twice, and nothing is written when there is no news.</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead><tr><th>Feed</th><th class="text-center">Stories</th><th>Last read</th><th class="text-center">On</th><th></th></tr></thead>
                            <tbody>
                            @forelse ($feeds as $f)
                                <tr class="{{ $f->isActive ? '' : 'is-off' }}">
                                    <td>
                                        <form method="POST" action="{{ route('anisenso-news-feeds.update') }}" class="d-flex gap-1 mb-1">
                                            @csrf <input type="hidden" name="id" value="{{ $f->id }}">
                                            <input class="form-control form-control-sm fw-semibold" name="label" value="{{ $f->label }}" maxlength="190" style="min-width:12rem">
                                            <button class="btn btn-light btn-sm" title="Save the name"><i class="bx bx-check"></i></button>
                                        </form>
                                        <div class="feed-url">{{ $f->url }}</div>
                                    </td>
                                    <td class="text-center">{{ $f->kept }}<div class="small text-secondary">{{ $f->featured }} featured</div></td>
                                    <td class="small">
                                        @if ($f->lastFetchedAt)
                                            {{ \Illuminate\Support\Carbon::parse($f->lastFetchedAt)->timezone('Asia/Manila')->format('M j, g:i A') }}
                                            <div class="{{ str_starts_with((string) $f->lastStatus, 'OK') ? 'ok' : 'bad' }}">{{ $f->lastStatus }}</div>
                                        @else
                                            <span class="text-secondary">Not read yet</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check form-switch d-inline-block m-0">
                                            <input class="form-check-input nf-on" type="checkbox" data-id="{{ $f->id }}" {{ $f->isActive ? 'checked' : '' }} title="Read this feed">
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('anisenso-news-feeds.destroy') }}" class="nf-del">
                                            @csrf <input type="hidden" name="id" value="{{ $f->id }}">
                                            <button class="btn btn-soft-danger btn-sm" title="Remove"><i class="bx bx-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-secondary py-4">No feeds yet. Add one on the right.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div></div>

                <div class="card"><div class="card-body">
                    <h4 class="card-title mb-1 text-dark">Roundup log</h4>
                    <p class="text-secondary small">Every time the roundup was asked for, by the cron, by "Write one now" or by the scheduler, and what came of it.</p>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>When</th><th>By</th><th>Result</th><th>Stories</th><th>Details</th></tr></thead>
                            <tbody>
                            @forelse ($runs as $r)
                                <tr>
                                    <td class="small text-nowrap">{{ \Illuminate\Support\Carbon::parse($r->created_at)->timezone('Asia/Manila')->format('M j, g:i A') }}</td>
                                    <td class="small">{{ $r->trigger }}</td>
                                    <td><span class="st {{ $r->status }}">{{ $r->status }}</span></td>
                                    <td class="small">{{ $r->itemCount ?: '' }}@if ($r->rangeFrom)<div class="text-secondary">{{ \Illuminate\Support\Carbon::parse($r->rangeFrom)->format('M j') }} to {{ \Illuminate\Support\Carbon::parse($r->rangeTo)->format('M j') }}</div>@endif</td>
                                    <td class="small">
                                        @if ($r->pageId && $r->slug)
                                            <a href="{{ \App\Http\Controllers\aniSensoAdmin\AnisystemSitePagesController::liveUrl($r->section, $r->slug) }}" target="_blank" rel="noopener">{{ $r->pageTitle }}</a>
                                        @else
                                            {{ $r->reason }}
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-secondary py-4">No roundup asked for yet.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div></div>
            </div>

            <div class="col-xl-4">
                <div class="card"><div class="card-body">
                    <h5 class="card-title text-dark">Write one now</h5>
                    <p class="text-secondary small">Reads the feeds and writes a roundup right away, even if the last one is recent. Still nothing is written when there are fewer than three new stories.</p>
                    <form method="POST" action="{{ route('anisenso-news-feeds.run') }}" id="nfRun">
                        @csrf
                        <button class="btn btn-primary w-100"><i class="bx bx-news"></i> Write a roundup now</button>
                    </form>
                </div></div>

                <div class="card"><div class="card-body">
                    <h5 class="card-title text-dark">The cron address</h5>
                    <p class="text-secondary small">Give this address to a cron service (for example cron-job.org) and let it call once a day. anee.io writes a roundup only when the last one is older than the days below and there is new news, so calling it often is safe.</p>
                    @if ($cronUrl)
                        <div class="cron mb-3"><code id="nfCron">{{ $cronUrl }}</code><button type="button" class="btn btn-light btn-sm" id="nfCopy" title="Copy"><i class="bx bx-copy"></i></button></div>
                    @else
                        <div class="alert alert-warning small">The cron key is not made yet. It appears once anee.io's latest release has run.</div>
                    @endif
                    <form method="POST" action="{{ route('anisenso-news-feeds.settings') }}" class="d-flex align-items-end gap-2">
                        @csrf
                        <div class="flex-grow-1"><label class="form-label small mb-1">Write a roundup at most every</label>
                            <div class="input-group input-group-sm"><input class="form-control" type="number" name="every" min="1" max="30" value="{{ $every }}"><span class="input-group-text">days</span></div></div>
                        <button class="btn btn-outline-primary btn-sm">Save</button>
                    </form>
                </div></div>

                <div class="card"><div class="card-body">
                    <h5 class="card-title text-dark">Add a feed</h5>
                    <form method="POST" action="{{ route('anisenso-news-feeds.store') }}" class="row g-2">
                        @csrf
                        <div class="col-12"><label class="form-label">RSS address</label><input class="form-control" name="url" type="url" maxlength="600" required placeholder="https://rss.app/feeds/....xml" value="{{ old('url') }}"></div>
                        <div class="col-12"><label class="form-label">Name <span class="text-secondary small">(optional)</span></label><input class="form-control" name="label" maxlength="190" placeholder="e.g. Rice news PH" value="{{ old('label') }}"></div>
                        <div class="col-12"><button class="btn btn-primary w-100"><i class="bx bx-plus"></i> Add the feed</button></div>
                    </form>
                </div></div>
            </div>
        </div>
    @endunless
    </div>
@endsection

@section('script')
<script>
(() => {
    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
    document.querySelectorAll('.nf-on').forEach((box) => box.addEventListener('change', async () => {
        box.disabled = true;
        try {
            const r = await fetch(@json(route('anisenso-news-feeds.update')), { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                body: JSON.stringify({ id: box.dataset.id, isActive: box.checked ? 1 : 0 }) });
            if (!r.ok) throw new Error();
            box.closest('tr').classList.toggle('is-off', !box.checked);
        } catch (_) { box.checked = !box.checked; alert('That could not be saved. Please try again.'); }
        box.disabled = false;
    }));
    document.querySelectorAll('.nf-del').forEach((f) => f.addEventListener('submit', (e) => { if (!confirm('Remove this feed? The stories it already gave stay in the log.')) e.preventDefault(); }));
    const run = document.getElementById('nfRun');
    if (run) run.addEventListener('submit', () => { const b = run.querySelector('button'); b.disabled = true; b.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Asking anee.io...'; });
    const copy = document.getElementById('nfCopy');
    if (copy) copy.addEventListener('click', async () => {
        try { await navigator.clipboard.writeText(document.getElementById('nfCron').textContent.trim()); copy.innerHTML = '<i class="bx bx-check"></i>'; setTimeout(() => { copy.innerHTML = '<i class="bx bx-copy"></i>'; }, 1500); } catch (_) {}
    });
})();
</script>
@endsection
