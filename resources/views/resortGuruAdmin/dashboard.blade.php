@extends('layouts.master')

@section('title') Resort Guru @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Resort Guru @endslot
@slot('title') Dashboard @endslot
@endcomponent

<div class="row">
    <div class="col-md-6 col-xl-3">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium">Resort Owners</p>
                        <h4 class="mb-0">{{ number_format($kpis['owners']) }}</h4>
                    </div>
                    <div class="avatar-sm rounded-circle bg-primary align-self-center mini-stat-icon">
                        <span class="avatar-title rounded-circle bg-primary">
                            <i class="bx bx-user font-size-24"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium">Published Resorts</p>
                        <h4 class="mb-0">{{ number_format($kpis['resorts_published']) }}</h4>
                    </div>
                    <div class="avatar-sm rounded-circle bg-success align-self-center mini-stat-icon">
                        <span class="avatar-title rounded-circle bg-success">
                            <i class="bx bx-building-house font-size-24"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium">Published SEO Pages</p>
                        <h4 class="mb-0">{{ number_format($kpis['pages_published']) }}</h4>
                    </div>
                    <div class="avatar-sm rounded-circle bg-info align-self-center mini-stat-icon">
                        <span class="avatar-title rounded-circle bg-info">
                            <i class="bx bx-file font-size-24"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium">Pending GCash Approvals</p>
                        <h4 class="mb-0">{{ number_format($kpis['topups_pending']) }}</h4>
                        @if($kpis['topups_pending'] > 0)
                            <a href="{{ route('resort-guru-gcash.index') }}" class="text-warning small">Review now &rarr;</a>
                        @endif
                    </div>
                    <div class="avatar-sm rounded-circle bg-warning align-self-center mini-stat-icon">
                        <span class="avatar-title rounded-circle bg-warning">
                            <i class="bx bx-wallet font-size-24"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3"><i class="bx bx-coin-stack me-2"></i>GP Minted Today</h5>
                <h2 class="mb-1">{{ number_format($kpis['gp_minted_today']) }} GP</h2>
                <p class="text-muted">From approved GCash top-ups posted today.</p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3"><i class="bx bx-rocket me-2"></i>Quick actions</h5>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('resort-guru-keywords.create') }}" class="btn btn-primary"><i class="bx bx-plus"></i> New Keyword</a>
                    <a href="{{ route('resort-guru-pages.index') }}" class="btn btn-info"><i class="bx bx-file"></i> Edit SEO Pages</a>
                    <a href="{{ route('resort-guru-gcash.index') }}" class="btn btn-warning"><i class="bx bx-wallet"></i> Approve GCash</a>
                    <a href="{{ route('resort-guru-settings.index') }}" class="btn btn-secondary"><i class="bx bx-cog"></i> Settings</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3"><i class="bx bx-history me-2"></i>Recent activity</h5>
                @if($recent->isEmpty())
                    <p class="text-muted text-center py-4 mb-0">No activity yet.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>When</th>
                                    <th>Actor</th>
                                    <th>Action</th>
                                    <th>Target</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recent as $row)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($row->created_at)->diffForHumans() }}</td>
                                        <td><span class="badge bg-secondary">{{ $row->actor_type }}</span> #{{ $row->actor_id }}</td>
                                        <td>{{ $row->action }}</td>
                                        <td>{{ $row->target_type }} #{{ $row->target_id }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
