@extends('layouts.master')

@section('title') Website pages @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    /* Scoped under #spAdm: this page's styles load before Bootstrap's. */
    #spAdm .sp-bar { display: flex; flex-wrap: wrap; gap: .6rem; align-items: center; margin-bottom: 1rem; }
    #spAdm .sp-chips { display: flex; flex-wrap: wrap; gap: .35rem; }
    #spAdm .sp-chip { border: 1px solid var(--bs-border-color); background: var(--bs-body-bg); color: var(--bs-body-color); border-radius: 999px;
        padding: .3rem .8rem; font-size: .85rem; font-weight: 500;
        transition: background .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    #spAdm .sp-chip.is-on { background: var(--bs-primary); border-color: var(--bs-primary); color: #fff; }
    #spAdm .sp-chip .n { opacity: .75; margin-left: .25rem; }
    #spAdm .sp-search { flex: 1 1 14rem; max-width: 22rem; margin-left: auto; }
    #spAdm .sp-row { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: .35rem 1rem; align-items: center; padding: .8rem 1rem;
        border: 1px solid var(--bs-border-color); border-radius: .75rem; background: var(--bs-body-bg); margin-bottom: .5rem;
        transition: border-color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1), opacity .28s; }
    #spAdm .sp-row:hover { border-color: var(--bs-primary); box-shadow: 0 8px 24px -16px rgb(0 0 0 / .35); }
    #spAdm .sp-row.is-hidden { display: none; }
    #spAdm .sp-row .t { font-weight: 600; min-width: 0; }
    #spAdm .sp-row .t a { color: var(--bs-body-color); }
    #spAdm .sp-row .s { font-size: .82rem; color: var(--bs-secondary-color); min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    #spAdm .sp-row .acts { display: flex; gap: .3rem; justify-content: flex-end; flex-wrap: wrap; grid-row: span 2; }
    #spAdm .sp-kw { font-size: .72rem; padding: .1rem .45rem; border-radius: 999px; background: var(--bs-primary-bg-subtle); color: var(--bs-primary-text-emphasis); }
    @media (max-width: 575.98px) { #spAdm .sp-row { grid-template-columns: 1fr; } #spAdm .sp-row .acts { grid-row: auto; justify-content: flex-start; } #spAdm .sp-search { max-width: none; margin-left: 0; } }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') AniSystem @endslot
        @slot('title') Website pages @endslot
    @endcomponent

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif
    @unless ($ready)
        <div class="alert alert-warning">anee.io has not made its pages table yet. It appears once anee.io's latest release has run.</div>
    @endunless

    <div id="spAdm">
        <div class="card"><div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h4 class="card-title mb-1">anee.io's guides, blog and feature pages</h4>
                    <p class="text-secondary mb-0">Every page of the public Philippine site's crop guides, crop problems, blog and feature pages. Open one to edit it in the drag and drop builder; the preview beside it is anee.io itself.</p>
                </div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#spNew"><i class="bx bx-plus"></i> New page</button>
            </div>
            <div class="sp-bar">
                <div class="sp-chips" id="spChips">
                    <button type="button" class="sp-chip is-on" data-sec="">All <span class="n">{{ $rows->count() }}</span></button>
                    @foreach ($sections as $k => $label)
                        <button type="button" class="sp-chip" data-sec="{{ $k }}">{{ $label }} <span class="n">{{ $rows->where('section', $k)->count() }}</span></button>
                    @endforeach
                    <button type="button" class="sp-chip" data-sec="draft">Drafts <span class="n">{{ $rows->where('status', 'draft')->count() }}</span></button>
                </div>
                <input type="search" class="form-control sp-search" id="spSearch" placeholder="Search title, address or keyphrase" autocomplete="off">
            </div>
            <div id="spList">
                @forelse ($rows as $r)
                    <div class="sp-row" data-id="{{ $r->id }}" data-sec="{{ $r->section }}" data-status="{{ $r->status }}"
                         data-find="{{ strtolower($r->title . ' ' . $r->section . '/' . $r->slug . ' ' . $r->focusKeyword . ' ' . $r->category) }}">
                        <div class="t"><a href="{{ route('anisenso-site-pages.build', ['id' => $r->id]) }}">{{ $r->title }}</a></div>
                        <div class="acts">
                            <span class="badge {{ $r->status === 'published' ? 'bg-success' : 'bg-secondary' }} align-self-center" data-badge>{{ $r->status === 'published' ? 'Live' : 'Draft' }}</span>
                            <a class="btn btn-sm btn-primary" href="{{ route('anisenso-site-pages.build', ['id' => $r->id]) }}"><i class="bx bx-customize"></i> Build</a>
                            <a class="btn btn-sm btn-light" href="{{ $r->liveUrl }}" target="_blank" rel="noopener" title="Open on anee.io"><i class="bx bx-link-external"></i></a>
                            <button type="button" class="btn btn-sm btn-light" data-toggle title="Publish or take down"><i class="bx {{ $r->status === 'published' ? 'bx-hide' : 'bx-show' }}"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-del title="Remove"><i class="bx bx-trash"></i></button>
                        </div>
                        <div class="s">
                            /{{ $r->section }}/{{ $r->slug }} · {{ $r->category ?: 'No category' }} · {{ number_format($r->words) }} words
                            @if ($r->focusKeyword) · <span class="sp-kw">{{ $r->focusKeyword }}</span>@endif
                            @if ($r->lang === 'tl') · Tagalog @endif
                            @if ($r->editedAt) · edited by {{ $r->editedBy ?: 'an admin' }} @endif
                        </div>
                    </div>
                @empty
                    <p class="text-secondary text-center py-4">No pages yet. anee.io ships its guides with its next release, or make one with New page.</p>
                @endforelse
            </div>
        </div></div>
    </div>

    <div class="modal fade" id="spNew" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="POST" action="{{ route('anisenso-site-pages.store') }}">
                @csrf
                <div class="modal-header"><h5 class="modal-title">New page</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <label class="form-label">Where it goes</label>
                    <select class="form-select mb-3" name="section">
                        @foreach ($sections as $k => $label)<option value="{{ $k }}" @selected(old('section') === $k)>{{ $label }} (/{{ $k }}/...)</option>@endforeach
                    </select>
                    <label class="form-label">Title</label>
                    <input class="form-control mb-3" name="title" value="{{ old('title') }}" maxlength="200" required placeholder="e.g. Rice Tungro: Signs and Control">
                    <label class="form-label">Address <span class="text-secondary">(optional; made from the title)</span></label>
                    <input class="form-control" name="slug" value="{{ old('slug') }}" maxlength="120" placeholder="e.g. rice-tungro">
                    <div class="form-text">It starts as a draft. Publish it from the builder when it is ready.</div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Create and open the builder</button></div>
            </form>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
(() => {
    const CSRF = "{{ csrf_token() }}";
    const U = {
        toggle: "{{ route('anisenso-site-pages.toggle') }}",
        del: "{{ route('anisenso-site-pages.destroy') }}",
    };
    let sec = '', q = '';
    function filter() {
        document.querySelectorAll('#spList .sp-row').forEach((r) => {
            const inSec = !sec || (sec === 'draft' ? r.dataset.status === 'draft' : r.dataset.sec === sec);
            r.classList.toggle('is-hidden', !(inSec && (!q || r.dataset.find.includes(q))));
        });
    }
    document.getElementById('spChips').addEventListener('click', (e) => {
        const c = e.target.closest('[data-sec]');
        if (!c) return;
        sec = c.dataset.sec;
        document.querySelectorAll('#spChips .sp-chip').forEach((x) => x.classList.toggle('is-on', x === c));
        filter();
    });
    document.getElementById('spSearch').addEventListener('input', (e) => { q = e.target.value.trim().toLowerCase(); filter(); });
    const post = async (url, id) => {
        const res = await fetch(url + '?id=' + id, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' } });
        const j = await res.json().catch(() => ({}));
        if (!res.ok || !j.success) throw new Error(j.message || 'That did not work.');
        return j;
    };
    document.getElementById('spList').addEventListener('click', async (e) => {
        const row = e.target.closest('.sp-row');
        if (!row) return;
        if (e.target.closest('[data-toggle]')) {
            try {
                const j = await post(U.toggle, row.dataset.id);
                row.dataset.status = j.status;
                const b = row.querySelector('[data-badge]');
                b.textContent = j.status === 'published' ? 'Live' : 'Draft';
                b.className = 'badge align-self-center ' + (j.status === 'published' ? 'bg-success' : 'bg-secondary');
                row.querySelector('[data-toggle] i').className = 'bx ' + (j.status === 'published' ? 'bx-hide' : 'bx-show');
                toastr.success(j.message);
            } catch (err) { toastr.error(err.message); }
        }
        if (e.target.closest('[data-del]')) {
            if (!confirm('Remove this page from anee.io?')) return;
            try {
                const j = await post(U.del, row.dataset.id);
                row.style.opacity = '0';
                setTimeout(() => row.remove(), 280);
                toastr.success(j.message);
            } catch (err) { toastr.error(err.message); }
        }
    });
    @if ($errors->any()) new bootstrap.Modal(document.getElementById('spNew')).show(); @endif
})();
</script>
@endsection
