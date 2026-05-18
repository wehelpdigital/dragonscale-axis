@extends('layouts.master')

@section('title') Add Client @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Resort Guru @endslot
@slot('li_2') Clients @endslot
@slot('li_2_link') {{ route('resort-guru-owners.index') }} @endslot
@slot('title') Add Client @endslot
@endcomponent

@if($errors->any())
    <div class="alert alert-danger">@foreach($errors->all() as $e)<p class="mb-0">{{ $e }}</p>@endforeach</div>
@endif

<div class="row">
    <div class="col-lg-8">
        <form action="{{ route('resort-guru-owners.store') }}" method="POST">
            @csrf
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">New Client Account</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="+63...">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="pending">Pending</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password <small class="text-muted">(leave blank to auto-generate)</small></label>
                            <input type="text" name="password" class="form-control" placeholder="Will be auto-generated if blank">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Initial Gold Points <small class="text-muted">(optional)</small></label>
                            <input type="number" name="initial_gp" class="form-control" value="0" min="0">
                            <small class="text-muted">GP credited to the account immediately (free promotional grant).</small>
                        </div>
                    </div>
                    <div class="alert alert-info small">
                        <i class="bx bx-info-circle"></i> The temporary password will be shown once after creation. Share securely with the client.
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Create Client</button>
                    <a href="{{ route('resort-guru-owners.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
