@extends('layouts.master')

@section('title') Schedule Manager @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    .status-badge { text-transform: capitalize; font-size: 11px; letter-spacing: .3px; }
    .schedule-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); transform: translateY(-1px); transition: all .15s ease; }
    .empty-state { padding: 60px 20px; }
    /* Whose farm. A strip rather than a heading, so it reads as context for
       everything under it rather than as the first item in the list. */
    .sm-client-strip { display: flex; align-items: center; gap: .55rem;
        padding: .5rem .7rem; border-radius: .5rem;
        background: #eff2ff; border: 1px solid #d9e0fb; }
    .sm-client-strip i { font-size: 1.15rem; color: #556ee6; }
    .sm-client-strip b { display: block; font-size: 13px; color: #343a40; line-height: 1.2; }
    .sm-client-strip small { color: #74788d; font-size: 11.5px; }
    [data-bs-theme="dark"] .sm-client-strip { background: #262c3c; border-color: #39405a; }
    [data-bs-theme="dark"] .sm-client-strip b { color: #cdd3e4; }
    [data-bs-theme="dark"] .sm-client-strip small { color: #8b93ab; }
    [data-bs-theme="dark"] .sm-client-strip i { color: #9fb0f5; }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('title') Schedule Manager @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="bx bx-info-circle me-2"></i>{{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <div>
                    @if(!empty($client))
                        {{-- Whose farm this is. Said before the list, because
                             the list has had most of its rows taken out of it
                             and nothing else on the page would explain why. --}}
                        <div class="sm-client-strip mb-2">
                            <i class="bx bx-user-circle"></i>
                            <span>
                                <b>{{ trim(($client->firstName ?? '') . ' ' . ($client->lastName ?? '')) ?: $client->email }}</b>
                                <small>{{ $client->email }}</small>
                            </span>
                            <a href="{{ route('anisenso-clients.index') }}" class="btn btn-sm btn-light ms-auto text-nowrap">
                                <i class="bx bx-arrow-back me-1"></i>All clients
                            </a>
                        </div>
                        <h4 class="card-title mb-1 text-dark">Their cropping schedules</h4>
                        <p class="text-secondary mb-0">Only this client's seasons are listed. Everything inside one — lots, workers, materials, activities — opens from its Setup.</p>
                    @else
                        <h4 class="card-title mb-1 text-dark">Cropping Schedules</h4>
                        <p class="text-secondary mb-0">Create a cropping schedule, set up its lots, workers, materials, activities, and irrigations, then generate the calendar.</p>
                    @endif
                </div>
                {{-- Not offered while one client's farm is on screen. A
                     schedule made from here belongs to the admin who made it,
                     not to the client whose name is at the top of the page —
                     so the button would read as "add a season for Kathleen"
                     and do something else. Seasons are the client's to make. --}}
                @if(empty($client))
                    <a href="{{ route('anisenso-schedule-manager.create') }}" class="btn btn-primary">
                        <i class="bx bx-plus me-1"></i> New Cropping Schedule
                    </a>
                @endif
            </div>

            <form method="GET" class="row g-2 mb-3">
                {{-- Searching inside one client's farm must not throw you out
                     to every client's farm. --}}
                @if(!empty($client))
                    <input type="hidden" name="clientId" value="{{ $client->id }}">
                @endif
                <div class="col-md-5">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search by title or description...">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All statuses</option>
                        @foreach(['draft','setup','generated','completed','archived'] as $s)
                            <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100"><i class="bx bx-search me-1"></i>Filter</button>
                </div>
                @if(request()->hasAny(['search','status']))
                    <div class="col-md-2">
                        <a href="{{ route('anisenso-schedule-manager.index', !empty($client) ? ['clientId' => $client->id] : []) }}" class="btn btn-outline-secondary w-100">Clear</a>
                    </div>
                @endif
            </form>

            @if($schedules->total() === 0)
                <div class="text-center empty-state">
                    <i class="bx bx-calendar-x text-secondary" style="font-size: 3.5rem;"></i>
                    @if(!empty($client))
                        {{-- An empty farm and an empty system are different
                             facts, and the offer to "create your first
                             schedule" is wrong for the first one: a schedule
                             made here belongs to the admin, not to them. --}}
                        <p class="text-dark mt-2 mb-1 fs-5">This client has no cropping schedules.</p>
                        <small class="text-secondary d-block mb-3">Seasons are created by the client in their own app. Nothing here is missing — there is nothing yet.</small>
                        <a href="{{ route('anisenso-clients.index') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-arrow-back me-1"></i> Back to clients
                        </a>
                    @else
                        <p class="text-dark mt-2 mb-1 fs-5">No cropping schedules yet.</p>
                        <small class="text-secondary d-block mb-3">Start by creating one — you'll be able to set up lots, workers, materials, activities and more.</small>
                        <a href="{{ route('anisenso-schedule-manager.create') }}" class="btn btn-primary">
                            <i class="bx bx-plus me-1"></i> Create your first schedule
                        </a>
                    @endif
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Title</th>
                                <th>Owner</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th class="text-end" style="width: 150px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($schedules as $s)
                                <tr data-id="{{ $s->id }}">
                                    <td class="text-dark">{{ $loop->iteration + (($schedules->currentPage()-1) * $schedules->perPage()) }}</td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $s->title }}</div>
                                        @if($s->description)
                                            <small class="text-secondary d-block">{{ \Illuminate\Support\Str::limit($s->description, 100) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($s->anisystemUserId)
                                            <span class="badge bg-warning text-dark d-block mb-1" style="width: fit-content;">AniSystem Client</span>
                                            <span class="badge bg-light text-dark d-block" style="width: fit-content;"
                                                  title="{{ $s->anisystemUser->email ?? '' }}" data-bs-toggle="tooltip">
                                                {{ $s->anisystemUser->fullName ?? 'Unknown client' }}
                                            </span>
                                        @elseif($s->usersId != Auth::id())
                                            <span class="badge bg-secondary-subtle text-secondary border">Admin — {{ $s->owner->name ?? 'Unknown' }}</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary border">Mine</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $statusMap = [
                                                'draft' => 'bg-secondary text-white',
                                                'setup' => 'bg-info text-white',
                                                'generated' => 'bg-primary text-white',
                                                'completed' => 'bg-success text-white',
                                                'archived' => 'bg-dark text-white',
                                            ];
                                            $cls = $statusMap[$s->status] ?? 'bg-secondary text-white';
                                        @endphp
                                        <span class="badge {{ $cls }} status-badge">{{ $s->status }}</span>
                                    </td>
                                    <td class="text-dark">{{ $s->created_at?->format('M j, Y g:i A') }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('anisenso-schedule-manager.setup', ['id' => $s->id]) }}" class="btn btn-sm btn-outline-primary" title="Setup">
                                            <i class="bx bx-cog"></i> Setup
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger delete-schedule-btn"
                                                data-id="{{ $s->id }}" data-name="{{ $s->title }}" title="Delete">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $schedules->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Delete modal --}}
    <div class="modal fade" id="deleteScheduleModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-trash text-danger me-2"></i>Delete Cropping Schedule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-dark mb-2">Are you sure you want to delete <strong id="delScheduleName" class="text-danger"></strong>?</p>
                    <small class="text-secondary">All sub-module data (lots, workers, activities, generated calendar) will be hidden from view. Completed activity history is preserved.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteSchedule">
                        <i class="bx bx-trash me-1"></i>Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
toastr.options = { closeButton: true, progressBar: true, positionClass: "toast-top-right", timeOut: 3000 };

// Initialize Bootstrap tooltips (the owner email in the table uses one)
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));

let scheduleToDelete = null;

$('.delete-schedule-btn').on('click', function () {
    scheduleToDelete = {
        id: $(this).data('id'),
        name: $(this).data('name'),
        row: $(this).closest('tr')
    };
    $('#delScheduleName').text(scheduleToDelete.name);
    $('#deleteScheduleModal').modal('show');
});

$('#confirmDeleteSchedule').on('click', function () {
    if (!scheduleToDelete) return;
    const $btn = $(this);
    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Deleting...');

    $.ajax({
        url: '{{ url('/') }}/anisenso-schedule-manager-delete?id=' + scheduleToDelete.id,
        type: 'DELETE',
        data: { _token: '{{ csrf_token() }}' },
        success: function (res) {
            if (res.success) {
                $('#deleteScheduleModal').modal('hide');
                toastr.success(res.message || 'Deleted.');
                scheduleToDelete.row.fadeOut(300, function () { $(this).remove(); });
            } else {
                toastr.error(res.message || 'Failed to delete.');
            }
        },
        error: function (xhr) {
            toastr.error(xhr.responseJSON?.message || 'Failed to delete.');
        },
        complete: function () {
            $btn.prop('disabled', false).html('<i class="bx bx-trash me-1"></i>Delete');
            scheduleToDelete = null;
        }
    });
});
</script>
@endsection
