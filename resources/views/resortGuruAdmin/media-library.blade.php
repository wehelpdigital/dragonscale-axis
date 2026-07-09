@extends('layouts.master')

@section('title') Media Library @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Resort Guru @endslot
@slot('title') Media Library @endslot
@endcomponent

{{-- KPI strip --}}
<div class="row g-2 mb-3">
    <div class="col-md-3 col-6">
        <div class="card mb-0"><div class="card-body py-3">
            <small class="text-muted text-uppercase" style="font-size:10px">Total items</small>
            <h4 class="mb-0 text-primary">{{ number_format($stats['total']) }}</h4>
        </div></div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card mb-0"><div class="card-body py-3">
            <small class="text-muted text-uppercase" style="font-size:10px">Images</small>
            <h4 class="mb-0 text-success">{{ number_format($stats['images']) }}</h4>
        </div></div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card mb-0"><div class="card-body py-3">
            <small class="text-muted text-uppercase" style="font-size:10px">Videos</small>
            <h4 class="mb-0 text-info">{{ number_format($stats['videos']) }}</h4>
        </div></div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card mb-0"><div class="card-body py-3">
            <small class="text-muted text-uppercase" style="font-size:10px">Storage used</small>
            <h4 class="mb-0 text-warning">{{ $stats['size_mb'] }} MB</h4>
        </div></div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
            <form method="GET" class="d-flex flex-wrap gap-2" style="flex:1">
                <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="Search filename / alt / caption / credit" style="max-width:280px">
                <select name="kind" class="form-select form-select-sm" style="max-width:130px" onchange="this.form.submit()">
                    <option value="">All kinds</option>
                    <option value="image" {{ $kind === 'image' ? 'selected' : '' }}>Images</option>
                    <option value="video" {{ $kind === 'video' ? 'selected' : '' }}>Videos</option>
                </select>
                <select name="source" class="form-select form-select-sm" style="max-width:160px" onchange="this.form.submit()">
                    <option value="">All sources</option>
                    @foreach($sources as $s)
                        <option value="{{ $s->source }}" {{ $source === $s->source ? 'selected' : '' }}>{{ $s->source }} ({{ $s->c }})</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-sm btn-primary"><i class="bx bx-search"></i></button>
            </form>
            <div>
                <input type="file" id="bulkUpload" style="display:none" accept="image/*,video/*" multiple onchange="handleBulkUpload(this.files)">
                <button class="btn btn-success btn-sm" onclick="document.getElementById('bulkUpload').click()">
                    <i class="bx bx-upload me-1"></i>Upload
                </button>
            </div>
        </div>

        @if($media->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bx bx-image" style="font-size:48px"></i>
                <p class="mt-2">No media yet. Use the Upload button or run the LandmarkImageSeeder.</p>
            </div>
        @else
            <div class="row g-2">
                @foreach($media as $m)
                    <div class="col-md-2 col-sm-3 col-4">
                        <div class="border rounded p-2 h-100 d-flex flex-column" style="background:#fafafa">
                            <a href="{{ \App\Http\Controllers\resortGuruAdmin\RgMediaController::mediaUrl($m->path) }}" target="_blank" class="d-block mb-2" style="background:#fff;border-radius:3px;overflow:hidden;aspect-ratio:1/1;display:flex;align-items:center;justify-content:center">
                                @if($m->kind === 'image')
                                    <img src="{{ \App\Http\Controllers\resortGuruAdmin\RgMediaController::mediaUrl($m->path) }}" alt="{{ $m->alt }}" loading="lazy" style="max-width:100%;max-height:100%;object-fit:cover;width:100%;height:100%">
                                @else
                                    <i class="bx bx-video" style="font-size:48px;color:#888"></i>
                                @endif
                            </a>
                            <div class="small">
                                <strong class="d-block text-truncate" title="{{ $m->filename }}">{{ $m->filename }}</strong>
                                <div class="text-muted" style="font-size:11px">
                                    {{ $m->width ?? '?' }}×{{ $m->height ?? '?' }} ·
                                    {{ round($m->size_bytes / 1024) }} KB
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-1">
                                    <span class="badge bg-light text-dark" style="font-size:9px">{{ $m->source }}</span>
                                    <div>
                                        <button class="btn btn-sm btn-link p-0 text-primary" title="Copy URL" onclick="copyUrl('{{ \App\Http\Controllers\resortGuruAdmin\RgMediaController::mediaUrl($m->path) }}')"><i class="bx bx-link"></i></button>
                                        <button class="btn btn-sm btn-link p-0 text-danger" title="Delete" onclick="deleteMedia({{ $m->id }})"><i class="bx bx-trash"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-3">{{ $media->links() }}</div>
        @endif
    </div>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
function copyUrl(u) {
    navigator.clipboard.writeText(u);
    if (typeof toastr !== 'undefined') toastr.success('Copied: ' + u);
}
function deleteMedia(id) {
    Swal.fire({
        title: 'Delete this media item?',
        text: 'This permanently removes the file from storage.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        confirmButtonColor: '#dc3545',
    }).then(function (r) {
        if (!r.isConfirmed) return;
        $.ajax({
            url: '/resort-guru-media-delete',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}', id: id },
        }).done(function () { location.reload(); });
    });
}
function handleBulkUpload(files) {
    if (!files || !files.length) return;
    let done = 0, total = files.length;
    if (typeof toastr !== 'undefined') toastr.info('Uploading ' + total + ' file(s)...');
    Array.from(files).forEach(function (file) {
        const fd = new FormData();
        fd.append('file', file);
        fd.append('_token', '{{ csrf_token() }}');
        $.ajax({
            url: '/resort-guru-media-upload',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
        }).done(function () {
            done++;
            if (done === total) { location.reload(); }
        });
    });
}
</script>
@endsection
