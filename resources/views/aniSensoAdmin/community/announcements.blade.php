@extends('layouts.master')

@section('title') Community — Announcements @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />

{{-- The mode layer, last in the head so it answers after the rules
     above it. --}}
@include('aniSensoAdmin.partials.dark')

@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') Community @endslot
        @slot('title') Announcements @endslot
    @endcomponent

    @include('aniSensoAdmin.community.partials.shelf', ['cmHere' => 'announcements'])

    <div class="row">
        <div class="col-xl-5">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title text-dark mb-1">Broadcast an announcement</h4>
                    <p class="text-secondary small">Delivered into the notification bell of all <strong>{{ $memberCount }}</strong> AniSystem members.</p>

                    <form method="POST" action="{{ route('anisenso-community.announcements.send') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label text-dark">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" maxlength="191" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" placeholder="e.g. New feature: share your plans by day" required>
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-dark">Message</label>
                            <textarea name="body" rows="3" maxlength="500" class="form-control @error('body') is-invalid @enderror" placeholder="A short sentence shown under the title.">{{ old('body') }}</textarea>
                            @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-dark">Link <span class="text-secondary">(optional)</span></label>
                            <input type="url" name="url" maxlength="500" class="form-control @error('url') is-invalid @enderror" value="{{ old('url') }}" placeholder="https://…">
                            @error('url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-primary" onclick="return confirm('Send this announcement to all {{ $memberCount }} members?');">
                            <i class="bx bx-send"></i> Send to all members
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title text-dark mb-3">Recent announcements</h5>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light"><tr><th>Title</th><th>Sent</th><th class="text-center">Recipients</th></tr></thead>
                            <tbody>
                                @forelse($recent as $row)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-dark">{{ $row->title }}</div>
                                            @if($row->body)<div class="text-secondary small">{{ \Illuminate\Support\Str::limit($row->body, 90) }}</div>@endif
                                        </td>
                                        <td class="text-secondary small">{{ \Illuminate\Support\Carbon::parse($row->sentAt)->format('M j, Y g:i A') }}</td>
                                        <td class="text-center">{{ $row->recipients }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-secondary py-4">No announcements sent yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
    @if(session('success')) toastr.success(@json(session('success'))); @endif
</script>
@endsection
