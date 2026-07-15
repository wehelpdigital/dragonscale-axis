@extends('layouts.master')

@section('title') {{ $title ?? 'TouristGuidePh' }} @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') TouristGuidePh @endslot
@slot('title') {{ $title ?? 'Module' }} @endslot
@endcomponent

<div class="row justify-content-center">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bx bx-cloud-download" style="font-size: 60px; color: #556ee6;"></i>
                <h3 class="mt-3">{{ $title ?? 'Module' }} &mdash; Awaiting Migrations</h3>
                <p class="text-muted mb-4">The database tables for this module have not been created yet.</p>
                <div class="alert alert-info text-start" style="max-width: 600px; margin: 0 auto;">
                    <h6 class="alert-heading"><i class="bx bx-info-circle me-1"></i> Next step</h6>
                    <p class="mb-2">From the frontend project, run:</p>
                    <pre class="mb-0 bg-light p-2 rounded"><code>cd c:\xampp\htdocs\resortguruph
php artisan migrate
php artisan db:seed</code></pre>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
