@extends('layouts.master')

@section('title') Streamr Support @endsection

@section('css')
@include('streamr._styles', ['tables' => false])
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Streamr @endslot
        @slot('title') Support @endslot
    @endcomponent

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="bx bx-error-circle me-2"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
    @endif

    @php
        $statusBadges = \App\Models\StreamrDeskTicket::STATUS_BADGES;
        $statusLabels = \App\Models\StreamrDeskTicket::STATUSES;
        $priorityBadges = \App\Models\StreamrDeskTicket::PRIORITY_BADGES;
        $priorityLabels = \App\Models\StreamrDeskTicket::PRIORITIES;
    @endphp

    <div id="st">
        <div class="card"><div class="card-body">
            <p class="text-secondary mb-3">Tickets Streamr's clients open in Streamr, kept in step both ways: answer here or in Streamr's own admin. Streamr emails the client; internal notes stay with the admins.</p>

            @if ($client)
                <div class="alert alert-info py-2">
                    Only <b>{{ $client->fullName() }}</b>'s tickets ({{ $client->email }}).
                    <a href="{{ route('streamr-support.index', array_filter(['status' => $status, 'q' => $q])) }}" class="ms-1">Show everyone's</a>
                </div>
            @endif

            <div class="bar">
                <ul class="nav nav-pills">
                    @foreach (['open' => 'Open', 'answered' => 'Answered', 'closed' => 'Closed', 'all' => 'All'] as $key => $label)
                        <li class="nav-item">
                            <a class="nav-link {{ $status === $key ? 'active' : '' }}" href="{{ route('streamr-support.index', array_filter(['status' => $key, 'q' => $q, 'client' => $clientId ?: null])) }}">
                                {{ $label }} <span class="badge {{ $status === $key ? 'bg-light text-dark' : 'bg-secondary' }} ms-1">{{ $counts[$key] ?? 0 }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <form method="GET" action="{{ route('streamr-support.index') }}" class="search d-flex gap-2">
                    <input type="hidden" name="status" value="{{ $status }}">
                    @if ($clientId)<input type="hidden" name="client" value="{{ $clientId }}">@endif
                    <input type="search" class="form-control" name="q" value="{{ $q }}" placeholder="Subject, ticket no., client name or email" aria-label="Search tickets">
                    <button class="btn btn-primary" type="submit" aria-label="Search"><i class="bx bx-search"></i></button>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Ticket</th><th>Client</th><th>Category</th><th>Priority</th><th>Status</th><th>Last activity</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($tickets as $t)
                            @php
                                $url = route('streamr-support.index', ['id' => $t->id]);
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ $url }}" class="{{ $t->adminUnread > 0 ? 'fw-bold' : 'fw-semibold' }} text-dark">{{ $t->subject }}</a>
                                    @if ($t->adminUnread > 0)<span class="badge bg-danger ms-1">{{ $t->adminUnread }}</span>@endif
                                    <div class="sub st-mono">{{ $t->ticketNumber }}</div>
                                </td>
                                <td><span class="text-dark">{{ $t->clientName ?: '—' }}</span><div class="sub">{{ $t->clientEmail }}</div></td>
                                <td><span class="badge bg-light text-dark">{{ $t->categoryLabel() }}</span></td>
                                <td><span class="badge {{ $priorityBadges[$t->priority] ?? 'bg-secondary' }}">{{ $priorityLabels[$t->priority] ?? $t->priority }}</span></td>
                                <td><span class="badge {{ $statusBadges[$t->status] ?? 'bg-secondary' }}">{{ $statusLabels[$t->status] ?? $t->status }}</span></td>
                                <td>
                                    <span class="text-dark">{{ $t->lastMessageAt ? $t->lastMessageAt->diffForHumans() : '—' }}</span>
                                    @if ($t->lastMessageBy)<div class="sub">from {{ $t->lastMessageBy === 'client' ? 'client' : ($t->lastMessageBy === 'system' ? 'Streamr' : 'admin') }}</div>@endif
                                </td>
                                <td class="text-end"><a href="{{ $url }}" class="btn btn-sm btn-outline-primary">Open</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7"><div class="empty my-2">{{ $q !== '' ? 'No ticket matches “' . $q . '”.' : 'No ' . ($status === 'all' ? '' : $status . ' ') . 'tickets. Tickets appear here once Streamr is linked and a client writes in.' }}</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $tickets->links() }}</div>
        </div></div>
    </div>
@endsection
