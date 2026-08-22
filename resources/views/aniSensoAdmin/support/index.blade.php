@extends('layouts.master')

@section('title') Ani-Senso — Support @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') Support @endslot
        @slot('title') Support Tickets @endslot
    @endcomponent

    @php
        $badge = ['open' => 'bg-warning', 'answered' => 'bg-success', 'closed' => 'bg-secondary'];
        $openCount = (int) ($counts['open'] ?? 0);
        $answeredCount = (int) ($counts['answered'] ?? 0);
        $closedCount = (int) ($counts['closed'] ?? 0);
        $allCount = $openCount + $answeredCount + $closedCount;
    @endphp

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <ul class="nav nav-pills gap-1">
                            <li class="nav-item">
                                <a class="nav-link {{ !$status ? 'active' : '' }}" href="{{ route('anisenso-support.index', request()->only('q')) }}">All <span class="badge bg-light text-dark ms-1">{{ $allCount }}</span></a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $status === 'open' ? 'active' : '' }}" href="{{ route('anisenso-support.index', array_merge(request()->only('q'), ['status' => 'open'])) }}">Open <span class="badge bg-warning ms-1">{{ $openCount }}</span></a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $status === 'answered' ? 'active' : '' }}" href="{{ route('anisenso-support.index', array_merge(request()->only('q'), ['status' => 'answered'])) }}">Answered <span class="badge bg-success ms-1">{{ $answeredCount }}</span></a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $status === 'closed' ? 'active' : '' }}" href="{{ route('anisenso-support.index', array_merge(request()->only('q'), ['status' => 'closed'])) }}">Closed <span class="badge bg-secondary ms-1">{{ $closedCount }}</span></a>
                            </li>
                        </ul>
                        <form method="GET" action="{{ route('anisenso-support.index') }}" class="d-flex gap-2">
                            @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
                            <input type="text" name="q" value="{{ $search }}" class="form-control form-control-sm" placeholder="Search subject or client…" style="min-width:220px;">
                            <button class="btn btn-sm btn-primary" type="submit"><i class="bx bx-search"></i></button>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Ticket</th>
                                    <th>Client</th>
                                    <th>Category</th>
                                    <th class="text-center">Messages</th>
                                    <th>Last activity</th>
                                    <th class="text-center">Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tickets as $ticket)
                                    <tr>
                                        <td>
                                            <a href="{{ route('anisenso-support.index', ['id' => $ticket->id]) }}" class="fw-semibold text-dark">{{ $ticket->subject }}</a>
                                            <div class="text-secondary small">#{{ $ticket->id }} · opened {{ optional($ticket->created_at)->diffForHumans() }}</div>
                                        </td>
                                        <td>
                                            @if($ticket->user)
                                                <div class="text-dark">{{ $ticket->user->full_name }}</div>
                                                <div class="text-secondary small">{{ $ticket->user->email }}</div>
                                            @else
                                                <span class="text-secondary">Unknown (#{{ $ticket->userId }})</span>
                                            @endif
                                        </td>
                                        <td><span class="badge bg-light text-dark">{{ \App\Models\SupportTicket::CATEGORIES[$ticket->category] ?? 'General' }}</span></td>
                                        <td class="text-center">{{ $ticket->messages_count }}</td>
                                        <td class="text-secondary small">{{ optional($ticket->lastReplyAt ?? $ticket->updated_at)->diffForHumans() }}</td>
                                        <td class="text-center"><span class="badge {{ $badge[$ticket->status] ?? 'bg-secondary' }}">{{ ucfirst($ticket->status) }}</span></td>
                                        <td class="text-end">
                                            <a href="{{ route('anisenso-support.index', ['id' => $ticket->id]) }}" class="btn btn-sm btn-outline-primary">Open</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-secondary py-5"><i class="bx bx-message-square-detail fs-1 d-block mb-2"></i>No tickets{{ $search || $status ? ' match this filter' : ' yet' }}.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">{{ $tickets->links() }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
