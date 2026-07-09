@extends('layouts.master')

@section('title') Fiestas @endsection

@section('css')
<link href="{{ URL::asset('assets/libs/datatables/datatables.min.css') }}" rel="stylesheet">
<link href="{{ URL::asset('assets/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet">
@endsection

@section('content')

@component('components.breadcrumb')
@slot('li_1') Resort Guru @endslot
@slot('title') Fiestas @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title">Philippine Fiestas</h4>
                    <a href="{{ route('resort-guru-fiestas.create') }}" class="btn btn-primary">
                        <i class="bx bx-plus"></i> Add Fiesta
                    </a>
                </div>
                <p class="text-muted">Manage the fiesta calendar. Add a new fiesta with its basic metadata, then use the Blocks button to assemble its content cards.</p>
                <table id="rgFiestasTable" class="table table-bordered nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Region</th>
                            <th>Province</th>
                            <th>City / Town</th>
                            <th>Month</th>
                            <th>Date Label</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script src="{{ URL::asset('assets/libs/datatables/datatables.min.js') }}"></script>
<script src="{{ URL::asset('assets/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
$(function () {
    $('#rgFiestasTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("resort-guru-fiestas.index") }}',
        columns: [
            { data: 'name', name: 'name' },
            { data: 'region_label', name: 'region_cluster' },
            { data: 'province', name: 'province' },
            { data: 'city_or_town', name: 'city_or_town' },
            { data: 'month_label', name: 'month' },
            { data: 'date_label', name: 'date_label' },
            { data: 'status_label', name: 'is_published', orderable: false, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[ 0, 'asc' ]],
        pageLength: 25
    });
});

window.rgDeleteFiesta = function (id, url) {
    Swal.fire({
        title: 'Delete this fiesta?',
        text: 'All content blocks attached to it will be removed.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete',
        confirmButtonColor: '#ef4444'
    }).then((r) => {
        if (!r.isConfirmed) return;
        fetch(url, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        }).then(r => r.json()).then(j => {
            if (j.ok) {
                Swal.fire('Deleted', '', 'success');
                $('#rgFiestasTable').DataTable().ajax.reload();
            } else {
                Swal.fire('Error', j.error || 'Could not delete', 'error');
            }
        });
    });
};
</script>
@endsection
