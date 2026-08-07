@extends('layouts.master')

@section('title') Tutorials @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') Community @endslot
        @slot('title') Tutorials @endslot
    @endcomponent

    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <div>
                <h4 class="card-title mb-1 text-dark">Tutorial videos</h4>
                <p class="text-secondary mb-0">YouTube guides shown on the AniSystem Tutorials page.</p>
            </div>
            <a href="{{ route('anisenso-tutorials.create') }}" class="btn btn-primary"><i class="bx bx-plus"></i> New tutorial</a>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th></th><th>Title</th><th>Category</th><th>Status</th><th>Order</th><th></th></tr></thead>
                <tbody>
                @forelse($tutorials as $t)
                    <tr data-row="{{ $t->id }}">
                        <td style="width:96px;">
                            @if($t->youtubeId)
                                <img src="https://i.ytimg.com/vi/{{ $t->youtubeId }}/default.jpg" alt="" style="width:80px;height:45px;object-fit:cover;border-radius:6px;">
                            @elseif($t->coverImagePath)
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($t->coverImagePath) }}" alt="" style="width:80px;height:45px;object-fit:cover;border-radius:6px;">
                            @else <span class="text-secondary">—</span> @endif
                        </td>
                        <td class="text-dark fw-semibold">{{ $t->title }}</td>
                        <td class="text-secondary">{{ $t->category ?: '—' }}</td>
                        <td>@if($t->isPublished)<span class="badge bg-success">Published</span>@else<span class="badge bg-secondary">Hidden</span>@endif</td>
                        <td>{{ $t->sortOrder }}</td>
                        <td class="text-end">
                            <a href="{{ route('anisenso-tutorials.edit', $t->id) }}" class="btn btn-sm btn-soft-primary"><i class="bx bx-edit"></i></a>
                            <button type="button" class="btn btn-sm btn-soft-danger btn-del" data-id="{{ $t->id }}"><i class="bx bx-trash"></i></button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-secondary py-4">No tutorials yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $tutorials->links('pagination::bootstrap-4') }}
    </div></div></div></div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
    const CSRF = "{{ csrf_token() }}";
    document.querySelectorAll('.btn-del').forEach((b) => b.addEventListener('click', async () => {
        if (!confirm('Remove this tutorial?')) return;
        const res = await fetch('/anisenso-tutorials/' + b.getAttribute('data-id'), { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' } });
        const data = await res.json();
        if (data.success) { toastr.success(data.message); document.querySelector('[data-row="' + b.getAttribute('data-id') + '"]')?.remove(); }
        else toastr.error(data.message || 'Could not remove.');
    }));
</script>
@endsection
