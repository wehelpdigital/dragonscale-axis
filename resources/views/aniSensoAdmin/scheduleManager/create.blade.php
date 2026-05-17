@extends('layouts.master')

@section('title') Create Cropping Schedule @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('anisenso-schedule-manager.index') }}" class="text-decoration-none">Schedule Manager</a> @endslot
        @slot('title') New Cropping Schedule @endslot
    @endcomponent

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title text-dark mb-1">Step 1 — Basic Details</h4>
                    <p class="text-secondary mb-4">Give your cropping schedule a clear title and a description. You'll set up the rest (lots, workers, activities, etc.) in the next step.</p>

                    <form method="POST" action="{{ route('anisenso-schedule-manager.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="title" class="form-label text-dark">
                                Cropping Schedule Title <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror"
                                   id="title" name="title" value="{{ old('title') }}"
                                   placeholder="e.g. Wet Season 2026 — Rice Cropping" required>
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="text-secondary">Short, descriptive name you'll recognize later.</small>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label text-dark">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="4"
                                      placeholder="Notes about this cropping cycle (optional)...">{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('anisenso-schedule-manager.index') }}" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i> Create & Continue
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
