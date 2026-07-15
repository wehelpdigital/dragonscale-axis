@extends('layouts.master')

@section('title') Client Profile @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') TouristGuidePh @endslot
@slot('li_2') Clients @endslot
@slot('li_2_link') {{ route('resort-guru-owners.index') }} @endslot
@slot('title') {{ $owner->name }} @endslot
@endcomponent

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="avatar-md me-3">
                        <span class="avatar-title rounded-circle bg-primary text-white font-size-24">
                            {{ strtoupper(substr($owner->name, 0, 1)) }}
                        </span>
                    </div>
                    <div>
                        <h4 class="mb-0">{{ $owner->name }}</h4>
                        <p class="text-muted mb-0">{{ $owner->email }}</p>
                    </div>
                </div>

                {{-- Inline edit form --}}
                <form action="{{ route('resort-guru-owners.update') }}" method="POST" id="profileForm">
                    @csrf
                    <input type="hidden" name="id" value="{{ $owner->id }}">
                    <div class="mb-2">
                        <label class="form-label small">Name</label>
                        <input type="text" name="name" class="form-control form-control-sm" value="{{ $owner->name }}" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Email</label>
                        <input type="email" name="email" class="form-control form-control-sm" value="{{ $owner->email }}" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Phone</label>
                        <input type="text" name="phone" class="form-control form-control-sm" value="{{ $owner->phone }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="active" {{ $owner->status==='active'?'selected':'' }}>Active</option>
                            <option value="suspended" {{ $owner->status==='suspended'?'selected':'' }}>Suspended</option>
                            <option value="pending" {{ $owner->status==='pending'?'selected':'' }}>Pending</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bx bx-save me-1"></i>Save Profile</button>
                </form>

                <hr>
                <ul class="list-unstyled small text-muted mb-0">
                    <li>Joined: {{ \Carbon\Carbon::parse($owner->created_at)->format('Y-m-d') }}</li>
                    <li>Last login: {{ $owner->last_login_at ? \Carbon\Carbon::parse($owner->last_login_at)->diffForHumans() : 'never' }}</li>
                </ul>
            </div>
        </div>

        {{-- Password Reset --}}
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><i class="bx bx-key me-1"></i>Password Reset</h5>
                <p class="text-muted small">Set a new password for this client. Leave blank to auto-generate one.</p>
                <div class="mb-2">
                    <input type="text" id="newPwd" class="form-control form-control-sm" placeholder="New password (min 8 chars) or blank to auto-generate">
                </div>
                <button class="btn btn-warning btn-sm w-100" onclick="resetPassword()"><i class="bx bx-refresh me-1"></i>Reset Password</button>
                <div id="pwdResult" class="mt-2"></div>
            </div>
        </div>

        {{-- Gold Points --}}
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><i class="bx bx-coin-stack me-1"></i>Gold Points</h5>
                <h2 class="mb-1">{{ number_format($balance) }} GP</h2>
                <small class="text-muted d-block mb-3">Posted: {{ number_format($posted) }} · On hold: {{ number_format($held) }}</small>

                <div class="mb-2">
                    <label class="form-label small">Credit / Debit GP</label>
                    <div class="input-group input-group-sm">
                        <input type="number" id="gpAmount" class="form-control" placeholder="amount (negative to debit)">
                        <input type="text" id="gpReason" class="form-control" placeholder="reason">
                        <button class="btn btn-success" onclick="creditGp()"><i class="bx bx-check"></i></button>
                    </div>
                </div>
                <small class="text-muted">Manual adjustment posts to the ledger immediately.</small>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Properties ({{ $resorts->count() }})</h5>
                @if($resorts->isEmpty())
                    <p class="text-muted">No properties yet.</p>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach($resorts as $r)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ $r->name }}</strong><br>
                                    <small class="text-muted">{{ $r->city }}{{ $r->province ? ', ' . $r->province : '' }}</small>
                                </div>
                                @php $rc = ['draft'=>'secondary','pending_review'=>'warning','published'=>'success','suspended'=>'danger'][$r->status] ?? 'secondary'; @endphp
                                <span class="badge bg-{{ $rc }}">{{ ucwords(str_replace('_',' ',$r->status)) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        @if(!empty($recentLedger) && count($recentLedger) > 0)
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Recent GP activity</h5>
                <table class="table table-sm small">
                    <thead><tr><th>Date</th><th>Amount</th><th>Reason</th><th>Notes</th></tr></thead>
                    <tbody>
                        @foreach($recentLedger as $row)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($row->created_at)->format('M j H:i') }}</td>
                            <td class="{{ $row->amount > 0 ? 'text-success' : 'text-danger' }}"><strong>{{ $row->amount > 0 ? '+' : '' }}{{ number_format($row->amount) }}</strong></td>
                            <td>{{ ucwords(str_replace('_',' ',$row->reason)) }}</td>
                            <td class="small text-muted">{{ $row->meta_json ? (json_decode($row->meta_json, true)['note'] ?? '') : '' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
function resetPassword() {
    const pwd = document.getElementById('newPwd').value;
    if (pwd && pwd.length < 8) { Swal.fire('Password too short', 'Must be 8 characters minimum.', 'warning'); return; }
    Swal.fire({
        title: 'Reset this client\'s password?',
        text: 'They will need the new password to log in.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Reset password',
        confirmButtonColor: '#f0ad4e',
    }).then(function (r) {
        if (!r.isConfirmed) return;
        $.post('{{ route("resort-guru-owners.reset-password") }}', { _token: '{{ csrf_token() }}', id: {{ $owner->id }}, password: pwd })
            .done(function (d) {
                document.getElementById('pwdResult').innerHTML =
                    '<div class="alert alert-success small p-2 mb-0">New password: <code>' + d.temporary_password + '</code><br>Share securely with the client.</div>';
                document.getElementById('newPwd').value = '';
            })
            .fail(function () { Swal.fire('Failed', 'Could not reset password.', 'error'); });
    });
}

function creditGp() {
    const amount = parseInt(document.getElementById('gpAmount').value, 10);
    const reason = document.getElementById('gpReason').value.trim();
    if (!amount || amount === 0) { Swal.fire('Invalid', 'Amount must be non-zero.', 'warning'); return; }
    if (!reason) { Swal.fire('Need a reason', 'Reason is required for audit.', 'warning'); return; }
    Swal.fire({
        title: amount > 0 ? 'Credit ' + amount + ' GP?' : 'Debit ' + Math.abs(amount) + ' GP?',
        text: 'Reason: ' + reason,
        icon: 'question',
        showCancelButton: true,
    }).then(function (r) {
        if (!r.isConfirmed) return;
        $.post('{{ route("resort-guru-owners.credit-gp") }}', { _token: '{{ csrf_token() }}', id: {{ $owner->id }}, amount: amount, reason: reason })
            .done(function () { location.reload(); })
            .fail(function () { Swal.fire('Failed', 'Could not post.', 'error'); });
    });
}
</script>
@endsection
