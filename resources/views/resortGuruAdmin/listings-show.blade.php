@extends('layouts.master')

@section('title') Listing #{{ $listing->id }} @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') TouristGuidePh @endslot
@slot('li_2') Listings & Bids @endslot
@slot('li_2_link') {{ route('resort-guru-listings.index') }} @endslot
@slot('title') Listing #{{ $listing->id }} @endslot
@endcomponent

<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">{{ $listing->keyword_phrase }}</h5>
                <table class="table table-sm">
                    <tr><th>Resort</th><td>{{ $listing->resort_name }}</td></tr>
                    <tr><th>Owner</th><td>{{ $listing->owner_name }} <br><small>{{ $listing->owner_email }}</small></td></tr>
                    <tr><th>Base GP</th><td>{{ number_format($listing->base_gp) }}</td></tr>
                    <tr><th>Bid GP</th><td><strong class="text-success">{{ number_format($listing->bid_gp) }}</strong></td></tr>
                    <tr><th>Started</th><td>{{ \Carbon\Carbon::parse($listing->starts_at)->format('Y-m-d H:i') }}</td></tr>
                    <tr><th>Expires</th><td>{{ \Carbon\Carbon::parse($listing->expires_at)->format('Y-m-d H:i') }}</td></tr>
                    <tr><th>Last bid</th><td>{{ $listing->last_bid_at ? \Carbon\Carbon::parse($listing->last_bid_at)->diffForHumans() : '—' }}</td></tr>
                    <tr><th>Status</th><td><span class="badge bg-{{ ['active'=>'success','expired'=>'secondary','cancelled'=>'danger'][$listing->status] ?? 'secondary' }}">{{ ucfirst($listing->status) }}</span></td></tr>
                    <tr><th>Search volume</th><td>{{ number_format($listing->search_volume_monthly) }} /mo</td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Bid history ({{ $bids->count() }})</h5>
                @if($bids->isEmpty())
                    <p class="text-muted">No bid events recorded.</p>
                @else
                    <ul class="list-group list-group-flush small">
                        @foreach($bids as $b)
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <span><span class="badge bg-light text-dark">{{ $b->action }}</span> +{{ number_format($b->gp_amount) }} GP</span>
                                    <small class="text-muted">{{ \Carbon\Carbon::parse($b->created_at)->diffForHumans() }}</small>
                                </div>
                                <small class="text-muted">Bid GP after: {{ number_format($b->bid_gp_after) }} @if($b->days_added > 0)| +{{ $b->days_added }} days @endif</small>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
