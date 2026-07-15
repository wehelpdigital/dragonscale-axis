@extends('layouts.master')

@section('title') Listings & Bids @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') TouristGuidePh @endslot
@slot('title') Listings & Bids @endslot
@endcomponent

<div class="row mb-3">
    <div class="col-md-8">
        <ul class="nav nav-tabs">
            @foreach(['active', 'expired', 'cancelled'] as $s)
                <li class="nav-item">
                    <a class="nav-link {{ $status === $s ? 'active' : '' }}" href="{{ route('resort-guru-listings.index', ['status' => $s]) }}">
                        {{ ucfirst($s) }} <span class="badge bg-secondary ms-1">{{ $counts[$s] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">{{ ucfirst($status) }} Listings ({{ $listings->total() }})</h5>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Keyword</th>
                                <th>Resort</th>
                                <th>Owner</th>
                                <th class="text-end">Base / Bid GP</th>
                                <th class="text-end">Days left</th>
                                <th>Last bid</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($listings as $r)
                                <tr>
                                    <td>#{{ $r->id }}</td>
                                    <td><a href="#" class="text-primary">{{ $r->keyword_phrase }}</a></td>
                                    <td>{{ $r->resort_name }}</td>
                                    <td>{{ $r->owner_name }}<br><small class="text-muted">{{ $r->owner_email }}</small></td>
                                    <td class="text-end">
                                        <small>{{ number_format($r->base_gp) }} +</small><br>
                                        <strong class="text-success">{{ number_format($r->bid_gp) }}</strong>
                                    </td>
                                    <td class="text-end {{ $r->days_left !== null && $r->days_left < 7 ? 'text-warning fw-bold' : '' }}">
                                        {{ $r->days_left ?? '∞' }}
                                    </td>
                                    <td>{{ $r->last_bid_at ? \Carbon\Carbon::parse($r->last_bid_at)->diffForHumans() : '—' }}</td>
                                    <td>
                                        <a href="{{ route('resort-guru-listings.show', $r->id) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bx bx-show"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted py-4">No {{ $status }} listings.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $listings->links() }}
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Top keywords by listing count</h5>
                @if($topKeywords->isEmpty())
                    <p class="text-muted text-center py-3 mb-0">No active listings yet.</p>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach($topKeywords as $k)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ $k->phrase }}</strong>
                                    <small class="d-block text-muted">{{ $k->listing_count }} active listings</small>
                                </div>
                                <span class="badge bg-success">{{ number_format($k->top_bid) }} GP top</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
