@extends('layouts.master')

@section('title') Pages for {{ $keyword->phrase }} @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') TouristGuidePh @endslot
@slot('li_2') Keywords @endslot
@slot('li_2_link') {{ route('resort-guru-keywords.index') }} @endslot
@slot('title') Pages for "{{ $keyword->phrase }}" @endslot
@endcomponent

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row">
    <div class="col-lg-9">
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div>
                        <h4 class="mb-1 text-capitalize">{{ $keyword->phrase }}</h4>
                        <small class="text-muted">
                            Volume: <strong>{{ number_format($keyword->search_volume_monthly) }}/mo</strong> ·
                            KD: <strong>{{ $keyword->keyword_difficulty }}</strong> ·
                            Cluster: <strong>{{ $keyword->cluster_tag ?: '—' }}</strong> ·
                            Active listings: <strong>{{ $listingsCount }}</strong>
                        </small>
                    </div>
                    <div>
                        <a href="{{ route('resort-guru-keywords-pages.create', ['keyword_id' => $keyword->id]) }}" class="btn btn-success">
                            <i class="bx bx-plus me-1"></i>Add New Page
                        </a>
                        <a href="{{ route('resort-guru-keywords.edit', ['id' => $keyword->id]) }}" class="btn btn-outline-secondary">
                            <i class="bx bx-cog"></i>
                        </a>
                    </div>
                </div>
                <div class="alert alert-info small mt-3 mb-0">
                    <i class="bx bx-info-circle me-1"></i>
                    Clients who bid on this keyword get featured on <strong>all pages</strong> below.
                    The primary page (marked &#x1F31F;) is the canonical SEO target.
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Pages ({{ $pages->count() }})</h5>
                @if($pages->isEmpty())
                    <div class="text-center py-4">
                        <p class="text-muted mb-3">No pages yet for this keyword.</p>
                        <a href="{{ route('resort-guru-keywords-pages.create', ['keyword_id' => $keyword->id]) }}" class="btn btn-success">
                            <i class="bx bx-plus me-1"></i>Create first page
                        </a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th></th>
                                    <th>Title</th>
                                    <th>URL Slug</th>
                                    <th>Status</th>
                                    <th>Views 30d</th>
                                    <th>Updated</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pages as $p)
                                    <tr>
                                        <td>
                                            @if($p->is_primary)
                                                <span title="Primary page (canonical)" style="font-size:18px">&#x1F31F;</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('resort-guru-pages.edit', ['id' => $p->id]) }}" class="fw-semibold">
                                                {{ $p->title }}
                                            </a>
                                            @if($p->is_primary)<span class="badge bg-warning text-dark ms-1">Primary</span>@endif
                                        </td>
                                        <td><code class="small">/{{ $p->slug }}</code></td>
                                        <td>
                                            @if($p->is_published)
                                                <span class="badge bg-success">Live</span>
                                            @else
                                                <span class="badge bg-secondary">Draft</span>
                                            @endif
                                        </td>
                                        <td>{{ number_format($p->pageviews_30d ?? 0) }}</td>
                                        <td><small>{{ \Carbon\Carbon::parse($p->updated_at)->diffForHumans() }}</small></td>
                                        <td class="text-end">
                                            @if($p->is_published)
                                                <a href="{{ \App\Support\RgFrontend::urlFor($p->slug) }}" target="_blank" class="btn btn-sm btn-outline-success" title="View live"><i class="bx bx-link-external"></i></a>
                                            @endif
                                            <a href="{{ route('resort-guru-pages.edit', ['id' => $p->id]) }}" class="btn btn-sm btn-primary"><i class="bx bx-edit-alt"></i> Edit</a>
                                            <a href="{{ route('resort-guru-schemas.edit', ['id' => $p->id]) }}" class="btn btn-sm btn-outline-secondary" title="Edit JSON-LD schema"><i class="bx bx-code-curly"></i></a>
                                            @if(!$p->is_primary)
                                                <button class="btn btn-sm btn-outline-warning" onclick="setPrimary({{ $p->id }})" title="Mark as primary"><i class="bx bx-star"></i></button>
                                            @endif
                                            <button class="btn btn-sm btn-outline-danger" onclick="deletePage({{ $p->id }}, '{{ addslashes($p->title) }}')"><i class="bx bx-trash"></i></button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-3">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Why multiple pages?</h6></div>
            <div class="card-body small text-muted">
                <p>One keyword can target multiple search intents:</p>
                <ul class="ps-3">
                    <li>Primary landing page (e.g. <code>/resort-in-bulacan</code>)</li>
                    <li>Variant for families (<code>/resort-in-bulacan-with-pool</code>)</li>
                    <li>Cheap-stay variant (<code>/cheap-resort-bulacan</code>)</li>
                    <li>Long-form review (<code>/best-resorts-bulacan-2026</code>)</li>
                </ul>
                <p class="mb-0">Each ranks for its own long-tail variant. Bid revenue is shared across all of them.</p>
            </div>
        </div>
    </div>
</div>

<form id="deleteForm" method="POST" action="{{ route('resort-guru-keywords-pages.delete') }}" style="display:none">
    @csrf
    @method('DELETE')
    <input type="hidden" name="id" id="deleteId">
</form>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
function setPrimary(id) {
    Swal.fire({
        title: 'Mark as primary page?',
        text: 'This page becomes the canonical SEO target. Any current primary is demoted.',
        icon: 'question',
        showCancelButton: true,
    }).then(function (r) {
        if (!r.isConfirmed) return;
        $.post('{{ route("resort-guru-keywords-pages.set-primary") }}', { _token: '{{ csrf_token() }}', id: id })
            .done(function () { location.reload(); });
    });
}
function deletePage(id, title) {
    Swal.fire({
        title: 'Delete this page?',
        html: '<strong>' + title + '</strong><br><small>All content blocks for this page will also be removed. The keyword itself stays.</small>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        confirmButtonColor: '#dc3545',
    }).then(function (r) {
        if (!r.isConfirmed) return;
        document.getElementById('deleteId').value = id;
        $.ajax({
            url: '{{ route("resort-guru-keywords-pages.delete") }}',
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}', id: id },
        }).done(function () { location.reload(); });
    });
}
</script>
@endsection
