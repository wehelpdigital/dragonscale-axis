@extends('layouts.master')

@section('title') Review Resort @endsection

@section('css')
<link href="{{ URL::asset('/build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') TouristGuidePh @endslot
@slot('li_2') Properties @endslot
@slot('li_2_link') {{ route('resort-guru-resorts.index') }} @endslot
@slot('title') {{ $resort->name }} @endslot
@endcomponent

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            @if($resort->hero_path)
                <img src="{{ \App\Http\Controllers\resortGuruAdmin\RgMediaController::mediaUrl($resort->hero_path) }}" class="card-img-top" style="max-height: 320px; object-fit: cover;">
            @endif
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    @if($resort->logo_path)
                        <img src="{{ \App\Http\Controllers\resortGuruAdmin\RgMediaController::mediaUrl($resort->logo_path) }}" class="me-3 rounded" style="height: 64px;">
                    @endif
                    <div>
                        <h3 class="mb-0" style="color: {{ $resort->primary_color }}">{{ $resort->name }}</h3>
                        <p class="text-muted mb-0">{{ $resort->tagline }}</p>
                    </div>
                </div>

                <p><strong>Location:</strong> {{ $resort->address }}, {{ $resort->city }}, {{ $resort->province }}</p>
                <p><strong>Contact:</strong> {{ $resort->phone }} &middot; {{ $resort->email }} &middot; <a href="{{ $resort->website }}" target="_blank">{{ $resort->website }}</a></p>
                <p><strong>Price range:</strong> {{ $resort->price_range }}</p>
                <p><strong>Capacity:</strong> {{ $resort->capacity }}</p>

                <h5>Description</h5>
                <div class="border rounded p-3 bg-light">{!! $resort->description_html !!}</div>

                @if($media->isNotEmpty())
                    <h5 class="mt-4">Media ({{ $media->count() }})</h5>
                    <div class="row g-2">
                        @foreach($media as $m)
                            <div class="col-md-3 col-6">
                                <img src="{{ \App\Http\Controllers\resortGuruAdmin\RgMediaController::mediaUrl($m->path) }}" class="img-fluid rounded">
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Status &amp; Owner</h5>
                @php $sc = ['draft'=>'secondary','pending_review'=>'warning','published'=>'success','suspended'=>'danger'][$resort->status] ?? 'secondary'; @endphp
                <p>Current status: <span class="badge bg-{{ $sc }}">{{ ucwords(str_replace('_',' ',$resort->status)) }}</span></p>
                @if($owner)
                    <p><strong>Owner:</strong> <a href="{{ route('resort-guru-owners.show', ['id' => $owner->id]) }}">{{ $owner->name }}</a><br>
                    <small>{{ $owner->email }}</small></p>
                @endif

                <hr>
                <div class="d-grid gap-2">
                    @if($resort->status !== 'published')
                        <button class="btn btn-success" onclick="action('approve', 'Publish this resort?')"><i class="bx bx-check-circle"></i> Approve &amp; Publish</button>
                    @endif
                    @if($resort->status === 'pending_review')
                        <button class="btn btn-outline-warning" onclick="action('reject', 'Send back to owner as draft?')"><i class="bx bx-undo"></i> Send Back to Draft</button>
                    @endif
                    @if($resort->status === 'published')
                        <button class="btn btn-outline-danger" onclick="action('suspend', 'Suspend this resort?')"><i class="bx bx-block"></i> Suspend</button>
                    @endif
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Branding</h5>
                <p>
                    <span class="badge me-2" style="background-color: {{ $resort->primary_color }}; color: white;">Primary {{ $resort->primary_color }}</span>
                    <span class="badge" style="background-color: {{ $resort->secondary_color }}; color: white;">Secondary {{ $resort->secondary_color }}</span>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
function action(name, prompt) {
    Swal.fire({ title: prompt, icon: 'question', showCancelButton: true })
        .then(function (r) {
            if (!r.isConfirmed) return;
            $.post('/resort-guru-resorts-' + name + '?id={{ $resort->id }}', { _token: '{{ csrf_token() }}' })
                .done(function () { toastr.success('Done.'); setTimeout(() => location.reload(), 600); })
                .fail(function () { toastr.error('Failed.'); });
        });
}
</script>
@endsection
