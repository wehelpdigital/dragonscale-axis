@extends('layouts.master')

@section('title') GCash Approvals @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Resort Guru @endslot
@slot('title') GCash Top-up Approvals @endslot
@endcomponent

<div class="row mb-3">
    <div class="col-12">
        <ul class="nav nav-tabs">
            @foreach(['pending', 'approved', 'rejected'] as $s)
                <li class="nav-item">
                    <a class="nav-link {{ $status === $s ? 'active' : '' }}" href="{{ route('resort-guru-gcash.index', ['status' => $s]) }}">
                        {{ ucfirst($s) }} <span class="badge bg-secondary ms-1">{{ $counts[$s] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Owner</th>
                        <th>PHP</th>
                        <th>GP</th>
                        <th>GCash Ref</th>
                        <th>Phone</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topups as $t)
                        <tr>
                            <td>#{{ $t->id }}</td>
                            <td>{{ $t->owner_name ?? '—' }}<br><small class="text-muted">{{ $t->owner_email ?? '' }}</small></td>
                            <td>&#8369; {{ number_format($t->php_amount) }}</td>
                            <td>{{ number_format($t->gp_amount) }} GP</td>
                            <td><code>{{ $t->gcash_ref_number }}</code></td>
                            <td>{{ $t->gcash_phone }}</td>
                            <td>{{ \Carbon\Carbon::parse($t->created_at)->diffForHumans() }}</td>
                            <td><a href="{{ route('resort-guru-gcash.show', ['id' => $t->id]) }}" class="btn btn-sm btn-primary"><i class="bx bx-show"></i> Review</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No {{ $status }} top-ups.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $topups->links() }}
    </div>
</div>
@endsection
