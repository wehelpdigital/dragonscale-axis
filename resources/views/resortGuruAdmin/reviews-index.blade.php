@extends('layouts.master')

@section('title') Destination Reviews @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') TouristGuidePh @endslot
@slot('title') Reviews @endslot
@endcomponent

<div class="row g-2 mb-3">
    <div class="col-md-3 col-6">
        <div class="card mb-0"><div class="card-body py-3">
            <small class="text-muted text-uppercase" style="font-size:10px">Total reviews</small>
            <h4 class="mb-0 text-primary">{{ \Illuminate\Support\Facades\DB::table('rg_destination_reviews')->count() }}</h4>
        </div></div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card mb-0"><div class="card-body py-3">
            <small class="text-muted text-uppercase" style="font-size:10px">Published</small>
            <h4 class="mb-0 text-success">{{ \Illuminate\Support\Facades\DB::table('rg_destination_reviews')->where('status','published')->count() }}</h4>
        </div></div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card mb-0"><div class="card-body py-3">
            <small class="text-muted text-uppercase" style="font-size:10px">Featured</small>
            <h4 class="mb-0 text-warning">{{ \Illuminate\Support\Facades\DB::table('rg_destination_reviews')->where('is_featured',1)->count() }}</h4>
        </div></div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card mb-0"><div class="card-body py-3">
            <small class="text-muted text-uppercase" style="font-size:10px">Avg rating</small>
            <h4 class="mb-0 text-info">{{ number_format(\Illuminate\Support\Facades\DB::table('rg_destination_reviews')->where('status','published')->avg('rating') ?? 0, 2) }}</h4>
        </div></div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
            <div>
                <h5 class="card-title mb-1">Bulk-generate positive reviews</h5>
                <small class="text-muted">Auto-create 1 to 8 reviews per active keyword. Useful for seeding the page with social proof during launch.</small>
            </div>
            <form method="POST" action="/resort-guru-reviews-generate" class="d-flex align-items-end gap-2">
                @csrf
                <div>
                    <label class="form-label small mb-1">Per keyword</label>
                    <input type="number" name="per_keyword" min="1" max="8" value="4" class="form-control form-control-sm" style="width:80px">
                </div>
                <div>
                    <label class="form-check small mt-3"><input type="checkbox" name="clear_existing" value="1" class="form-check-input"> <span class="form-check-label">Clear existing first</span></label>
                </div>
                <button type="submit" class="btn btn-sm btn-warning"><i class="bx bx-shuffle me-1"></i>Generate</button>
            </form>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-end mb-3">
            <a href="/resort-guru-reviews-create" class="btn btn-primary btn-sm"><i class="bx bx-plus me-1"></i>Add Review</a>
        </div>
        <table id="reviews-dt" class="table table-striped" style="width:100%">
            <thead>
                <tr>
                    <th>Reviewer</th>
                    <th>Location</th>
                    <th>Keyword</th>
                    <th>Rating</th>
                    <th>Review</th>
                    <th>Date</th>
                    <th>Featured</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
$(function () {
    $('#reviews-dt').DataTable({
        processing: true, serverSide: true,
        ajax: '/resort-guru-reviews',
        order: [[5, 'desc']],
        columns: [
            { data: 'reviewer_name' },
            { data: 'reviewer_location' },
            { data: 'keyword' },
            { data: 'stars', orderable: false },
            { data: 'snippet', orderable: false, searchable: false },
            { data: 'review_date' },
            { data: 'featured_pill', orderable: false, searchable: false },
            { data: 'status_pill' },
            { data: 'actions', orderable: false, searchable: false }
        ]
    });
});
function deleteReview(id) {
    Swal.fire({
        title: 'Delete this review?', icon: 'warning', showCancelButton: true,
        confirmButtonText: 'Delete', confirmButtonColor: '#dc3545'
    }).then(function (r) {
        if (!r.isConfirmed) return;
        $.post('/resort-guru-reviews-delete', { _token: '{{ csrf_token() }}', id: id })
            .done(function () { $('#reviews-dt').DataTable().ajax.reload(); });
    });
}
</script>
@endsection
