@extends('layouts.master')

@section('title') Community — Members @endsection

@section('css')
<style> #membersTable td { vertical-align: middle; } .avatar-chip { width:38px;height:38px;border-radius:50%;background:#556ee6;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-weight:600;font-size:13px; } </style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') Community @endslot
        @slot('title') Members @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                        <div>
                            <h4 class="card-title mb-1 text-dark">Members</h4>
                            <p class="text-secondary mb-0">AniSystem members and their community activity. Open a member to see their profile, connections and wall.</p>
                        </div>
                        <form method="GET" action="{{ route('anisenso-community.members') }}" class="d-flex gap-2">
                            <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Search name, place or email…" style="min-width:240px;">
                            <button class="btn btn-primary" type="submit"><i class="bx bx-search"></i></button>
                            @if($search)<a href="{{ route('anisenso-community.members') }}" class="btn btn-light">Clear</a>@endif
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="membersTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Member</th>
                                    <th>Location</th>
                                    <th class="text-center">Shared plans</th>
                                    <th class="text-center">Connections</th>
                                    <th>Joined</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($members as $member)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="avatar-chip">{{ $member->initials ?: '?' }}</span>
                                                <div>
                                                    <a href="{{ route('anisenso-community.members.show', $member->id) }}" class="fw-semibold text-dark">{{ $member->full_name }}</a>
                                                    <div class="text-secondary small">{{ $member->email }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $member->location ?: '—' }}</td>
                                        <td class="text-center">{{ (int) $member->sharedPlanCount }}</td>
                                        <td class="text-center">{{ (int) $member->connectionCount }}</td>
                                        <td>{{ $member->created_at?->format('M j, Y') }}</td>
                                        <td class="text-end"><a href="{{ route('anisenso-community.members.show', $member->id) }}" class="btn btn-sm btn-soft-primary">View</a></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-secondary py-4">{{ $search ? 'No members match your search.' : 'No members yet.' }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">{{ $members->links('pagination::bootstrap-4') }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
