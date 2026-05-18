@extends('layouts.master')

@section('title') Gold Points @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Resort Guru @endslot
@slot('title') Gold Points Ledger @endslot
@endcomponent

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="row">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><i class="bx bx-coin me-2"></i>Manual GP Adjustment</h5>
                <p class="text-muted small">Posts a row to the ledger immediately. Use for refunds, corrections, or promotional credits.</p>
                <form action="{{ route('resort-guru-gp.adjust') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Owner</label>
                        <select name="owner_id" class="form-select" required>
                            <option value="">Select owner...</option>
                            @foreach($owners as $o)
                                <option value="{{ $o->id }}">{{ $o->name }} ({{ $o->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount (GP)</label>
                        <input type="number" name="amount" class="form-control" placeholder="positive=credit, negative=debit" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason / Note</label>
                        <textarea name="reason" class="form-control" rows="2" required></textarea>
                    </div>
                    <button class="btn btn-primary w-100"><i class="bx bx-check"></i> Post Adjustment</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Ledger Entries</h5>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>When</th>
                                <th>Owner</th>
                                <th>Reason</th>
                                <th class="text-end">Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ledger as $row)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($row->created_at)->format('Y-m-d H:i') }}</td>
                                    <td>{{ $row->owner_name ?? '—' }}<br><small class="text-muted">{{ $row->owner_email ?? '' }}</small></td>
                                    <td><span class="badge bg-light text-dark">{{ $row->reason }}</span></td>
                                    <td class="text-end {{ $row->amount >= 0 ? 'text-success' : 'text-danger' }}">
                                        <strong>{{ $row->amount >= 0 ? '+' : '' }}{{ number_format($row->amount) }}</strong>
                                    </td>
                                    <td><span class="badge bg-{{ $row->status === 'posted' ? 'success' : ($row->status === 'pending' ? 'warning' : 'secondary') }}">{{ $row->status }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">No entries yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $ledger->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
