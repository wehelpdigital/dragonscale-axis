@extends('layouts.master')

@section('title') SEO keywords @endsection

@section('css')
<style>
    /* Scoped under #sk: this page's styles load before Bootstrap's. */
    #sk .stat { border-radius: .9rem; padding: .9rem 1rem; background: var(--bs-tertiary-bg); }
    #sk .stat b { display: block; font-size: 1.35rem; color: var(--bs-emphasis-color); }
    #sk .stat span { font-size: .8rem; color: var(--bs-secondary-color); }
    #sk .vol { font-weight: 700; color: var(--bs-emphasis-color); }
    #sk .diff { display: inline-block; min-width: 2.2rem; text-align: center; padding: .1rem .45rem; border-radius: 999px; font-size: .75rem; font-weight: 700; }
    #sk .diff.e { background: var(--bs-success-bg-subtle); color: var(--bs-success-text-emphasis); }
    #sk .diff.m { background: var(--bs-warning-bg-subtle); color: var(--bs-warning-text-emphasis); }
    #sk .diff.h { background: var(--bs-danger-bg-subtle); color: var(--bs-danger-text-emphasis); }
    #sk .sorts a { text-decoration: none; }
    #sk .sorts a.on { font-weight: 700; }
    #sk tr.is-sel td { background: var(--bs-primary-bg-subtle); }
</style>
@include('aniSensoAdmin.partials.dark')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') AniSystem @endslot
        @slot('li_2') Website @endslot
        @slot('title') SEO keywords @endslot
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

    <div id="sk">
    @unless ($ready)
        <div class="alert alert-warning">The keyword list (<code>as_seo_keywords</code>) is not in the database yet. anee.io has to deploy its migration first.</div>
    @else
        <div class="row g-3 mb-3">
            <div class="col-sm-4"><div class="stat"><b>{{ number_format($totals['n']) }}</b><span>keywords on the list</span></div></div>
            <div class="col-sm-4"><div class="stat"><b>{{ number_format($totals['volume']) }}</b><span>monthly searches, all together</span></div></div>
            <div class="col-sm-4"><div class="stat"><b>{{ number_format($totals['used']) }}</b><span>already used in a page</span></div></div>
        </div>

        <div class="row g-3">
            <div class="col-xl-8">
                <div class="card"><div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div>
                            <h4 class="card-title mb-1 text-dark">The keywords anee.io writes toward</h4>
                            <p class="text-secondary mb-0 small">Anee picks the keywords nearest each page she writes (Ask Anee answers and Write with Anee), and uses the ones not yet worn out first.</p>
                        </div>
                        <form class="d-flex gap-2" method="GET">
                            <input type="hidden" name="sort" value="{{ $sort }}">
                            <input class="form-control form-control-sm" name="q" value="{{ $q }}" placeholder="Search keywords" style="min-width:12rem">
                            <button class="btn btn-light btn-sm"><i class="bx bx-search"></i></button>
                        </form>
                    </div>
                    <div class="sorts small text-secondary mb-2">Sort:
                        @foreach (['volume' => 'Searches', 'difficulty' => 'Easiest', 'used' => 'Most used', 'keyword' => 'A to Z', 'newest' => 'Newest'] as $k => $label)
                            <a href="{{ request()->fullUrlWithQuery(['sort' => $k, 'page' => null]) }}" class="ms-2 {{ $sort === $k ? 'on' : '' }}">{{ $label }}</a>
                        @endforeach
                    </div>
                    <form method="POST" action="{{ route('anisenso-seo-keywords.destroy') }}" id="skDel">
                        @csrf
                        <div class="table-responsive">
                            <table class="table align-middle mb-2">
                                <thead><tr>
                                    <th style="width:2rem"><input type="checkbox" class="form-check-input" id="skAll"></th>
                                    <th>Keyword</th><th class="text-end">Searches / month</th><th class="text-end">CPC</th><th class="text-center">SEO difficulty</th><th class="text-center">Used</th>
                                </tr></thead>
                                <tbody>
                                @forelse ($rows as $r)
                                    @php $d = (int) ($r->seoDifficulty ?? 0); @endphp
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input sk-one" name="ids[]" value="{{ $r->id }}"></td>
                                        <td class="text-dark fw-semibold">{{ $r->keyword }}</td>
                                        <td class="text-end vol">{{ number_format((int) $r->volume) }}</td>
                                        <td class="text-end text-secondary">{{ $r->cpc !== null ? number_format((float) $r->cpc, 2) : '' }}</td>
                                        <td class="text-center">@if ($r->seoDifficulty !== null)<span class="diff {{ $d < 30 ? 'e' : ($d < 60 ? 'm' : 'h') }}">{{ $d }}</span>@endif</td>
                                        <td class="text-center">{{ (int) $r->usedCount ?: '' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-secondary py-4">{{ $q !== '' ? 'No keyword matches that search.' : 'No keywords yet. Add one or import a CSV.' }}</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <button type="submit" class="btn btn-soft-danger btn-sm" id="skDelBtn" disabled><i class="bx bx-trash"></i> Remove selected</button>
                            {{ $rows->links() }}
                        </div>
                    </form>
                </div></div>
            </div>

            <div class="col-xl-4">
                <div class="card"><div class="card-body">
                    <h5 class="card-title text-dark">Add a keyword</h5>
                    <form method="POST" action="{{ route('anisenso-seo-keywords.store') }}" class="row g-2">
                        @csrf
                        <div class="col-12"><label class="form-label">Keyword</label><input class="form-control" name="keyword" maxlength="190" required placeholder="e.g. urea fertilizer price"></div>
                        <div class="col-6"><label class="form-label">Searches / month</label><input class="form-control" name="volume" type="number" min="0"></div>
                        <div class="col-6"><label class="form-label">CPC</label><input class="form-control" name="cpc" type="number" min="0" step="0.01"></div>
                        <div class="col-6"><label class="form-label">Paid difficulty</label><input class="form-control" name="paidDifficulty" type="number" min="0" max="100"></div>
                        <div class="col-6"><label class="form-label">SEO difficulty</label><input class="form-control" name="seoDifficulty" type="number" min="0" max="100"></div>
                        <div class="col-12"><button class="btn btn-primary w-100"><i class="bx bx-plus"></i> Add</button></div>
                    </form>
                </div></div>
                <div class="card"><div class="card-body">
                    <h5 class="card-title text-dark">Import a CSV</h5>
                    <p class="text-secondary small">Same columns as the keyword export: <code>No, Keyword, Volume, CPC, Paid Difficulty, SEO Difficulty</code>. A keyword already on the list gets its numbers updated.</p>
                    <form method="POST" action="{{ route('anisenso-seo-keywords.import') }}" enctype="multipart/form-data" class="d-flex flex-column gap-2">
                        @csrf
                        <input class="form-control" type="file" name="file" accept=".csv,text/csv" required>
                        <button class="btn btn-outline-primary"><i class="bx bx-upload"></i> Import</button>
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
    const all = document.getElementById('skAll'), btn = document.getElementById('skDelBtn');
    if (!all) return;
    const ones = () => [...document.querySelectorAll('.sk-one')];
    const paint = () => {
        const n = ones().filter((c) => c.checked).length;
        btn.disabled = !n;
        btn.innerHTML = '<i class="bx bx-trash"></i> Remove selected' + (n ? ' (' + n + ')' : '');
        ones().forEach((c) => c.closest('tr').classList.toggle('is-sel', c.checked));
    };
    all.addEventListener('change', () => { ones().forEach((c) => { c.checked = all.checked; }); paint(); });
    document.addEventListener('change', (e) => { if (e.target.classList.contains('sk-one')) paint(); });
    document.getElementById('skDel').addEventListener('submit', (e) => { if (!confirm('Remove the selected keywords from the list?')) e.preventDefault(); });
})();
</script>
@endsection
