@extends('layouts.master')

@section('title') Review GCash Top-up @endsection

@section('css')
<link href="{{ URL::asset('/build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') TouristGuidePh @endslot
@slot('li_2') GCash Approvals @endslot
@slot('li_2_link') {{ route('resort-guru-gcash.index') }} @endslot
@slot('title') Review Top-up #{{ $topup->id }} @endslot
@endcomponent

<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body text-center">
                <h5 class="card-title text-start">GCash Screenshot</h5>
                @if($topup->screenshot_path)
                    <img src="{{ asset('storage/' . $topup->screenshot_path) }}" class="img-fluid border rounded" style="max-height: 600px;">
                @else
                    <div class="alert alert-warning">No screenshot uploaded.</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Top-up Details</h5>
                <table class="table table-sm">
                    <tr><th>Owner</th><td>{{ $owner->name ?? '—' }}<br><small>{{ $owner->email ?? '' }}</small></td></tr>
                    <tr><th>PHP Amount</th><td><strong>&#8369; {{ number_format($topup->php_amount) }}</strong></td></tr>
                    <tr><th>GP to Credit</th><td><strong>{{ number_format($topup->gp_amount) }} GP</strong></td></tr>
                    <tr><th>GCash Ref #</th><td><code>{{ $topup->gcash_ref_number }}</code></td></tr>
                    <tr><th>Phone</th><td>{{ $topup->gcash_phone }}</td></tr>
                    <tr><th>Submitted</th><td>{{ \Carbon\Carbon::parse($topup->created_at)->format('Y-m-d H:i') }}</td></tr>
                    <tr><th>Status</th><td>
                        @php $sc = ['pending'=>'warning','approved'=>'success','rejected'=>'danger'][$topup->status] ?? 'secondary'; @endphp
                        <span class="badge bg-{{ $sc }}">{{ ucfirst($topup->status) }}</span>
                    </td></tr>
                    @if($topup->status === 'rejected' && $topup->rejection_reason)
                        <tr><th>Reason</th><td class="text-danger">{{ $topup->rejection_reason }}</td></tr>
                    @endif
                </table>

                @if($topup->status === 'pending')
                    <div class="d-grid gap-2 mt-3">
                        <button class="btn btn-success" onclick="approve()"><i class="bx bx-check-circle"></i> Approve &amp; Credit {{ number_format($topup->gp_amount) }} GP</button>
                        <button class="btn btn-outline-danger" onclick="reject()"><i class="bx bx-x-circle"></i> Reject</button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
function approve() {
    Swal.fire({
        title: 'Approve this top-up?',
        text: 'This will credit {{ number_format($topup->gp_amount) }} GP to {{ addslashes($owner->name ?? "owner") }}.',
        icon: 'success',
        showCancelButton: true,
        confirmButtonText: 'Yes, approve',
        confirmButtonColor: '#34c38f',
    }).then(function (r) {
        if (!r.isConfirmed) return;
        $.post('{{ route("resort-guru-gcash.approve", ["id" => $topup->id]) }}', { _token: '{{ csrf_token() }}' })
            .done(function () { toastr.success('Approved.'); setTimeout(() => location.reload(), 800); })
            .fail(function (xhr) { toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Failed.'); });
    });
}
function reject() {
    Swal.fire({
        title: 'Reject this top-up?',
        input: 'textarea',
        inputLabel: 'Rejection reason (shown to owner)',
        inputAttributes: { required: true },
        showCancelButton: true,
        confirmButtonText: 'Reject',
        confirmButtonColor: '#f46a6a',
    }).then(function (r) {
        if (!r.isConfirmed || !r.value) return;
        $.post('{{ route("resort-guru-gcash.reject", ["id" => $topup->id]) }}', { _token: '{{ csrf_token() }}', rejection_reason: r.value })
            .done(function () { toastr.success('Rejected.'); setTimeout(() => location.reload(), 800); })
            .fail(function () { toastr.error('Failed.'); });
    });
}
</script>
@endsection
