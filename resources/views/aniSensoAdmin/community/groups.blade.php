@extends('layouts.master')

@section('title') Community — Groups @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style> #groupsTable td { vertical-align: middle; } </style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') Community @endslot
        @slot('title') Groups @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                        <div>
                            <h4 class="card-title mb-1 text-dark">Groups</h4>
                            <p class="text-secondary mb-0">Member-run groups where farmers post topics and reply. Review posts and remove anything abusive.</p>
                        </div>
                        <form method="GET" action="{{ route('anisenso-community.groups') }}" class="d-flex gap-2">
                            <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Search group name…" style="min-width:240px;">
                            <button class="btn btn-primary" type="submit"><i class="bx bx-search"></i></button>
                            @if($search)<a href="{{ route('anisenso-community.groups') }}" class="btn btn-light">Clear</a>@endif
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="groupsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Group</th>
                                    <th>Owner</th>
                                    <th class="text-center">Members</th>
                                    <th class="text-center">Posts</th>
                                    <th>Created</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($groups as $group)
                                    <tr data-group-row="{{ $group->id }}">
                                        <td>
                                            <a href="{{ route('anisenso-community.groups.show', $group->id) }}" class="fw-semibold text-dark">{{ $group->name }}</a>
                                            @if($group->description)<div class="text-secondary small">{{ \Illuminate\Support\Str::limit($group->description, 80) }}</div>@endif
                                        </td>
                                        <td>{{ optional($group->creator)->full_name ?: '—' }}</td>
                                        <td class="text-center">{{ $group->member_count }}</td>
                                        <td class="text-center">{{ $group->post_count }}</td>
                                        <td>{{ $group->created_at?->format('M j, Y') }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('anisenso-community.groups.show', $group->id) }}" class="btn btn-sm btn-soft-primary">View</a>
                                            <button type="button" class="btn btn-sm btn-soft-danger btn-del-group" data-id="{{ $group->id }}" data-name="{{ $group->name }}">Delete</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-secondary py-4">{{ $search ? 'No groups match your search.' : 'No groups yet.' }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">{{ $groups->links('pagination::bootstrap-4') }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
    const CSRF = "{{ csrf_token() }}";
    document.querySelectorAll('.btn-del-group').forEach((btn) => {
        btn.addEventListener('click', async () => {
            if (!confirm('Delete "' + btn.getAttribute('data-name') + '" and all its posts?')) return;
            btn.disabled = true;
            try {
                const res = await fetch('/anisenso-community-groups/' + btn.getAttribute('data-id'), { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' } });
                const data = await res.json();
                if (data.success) { toastr.success(data.message); document.querySelector('[data-group-row="' + btn.getAttribute('data-id') + '"]')?.remove(); }
                else { toastr.error(data.message); btn.disabled = false; }
            } catch (_) { toastr.error('Network error — try again.'); btn.disabled = false; }
        });
    });
</script>
@endsection
