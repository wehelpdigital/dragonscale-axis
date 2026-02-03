@extends('layouts.master')

@section('title') Business Contacts @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />

<style>
    .stats-card {
        border-radius: 8px;
        padding: 1rem;
        text-align: center;
    }
    .stats-card h3 {
        margin-bottom: 0.25rem;
    }
    .contact-row {
        cursor: pointer;
        transition: background-color 0.15s ease;
    }
    .contact-row:hover {
        background-color: rgba(var(--bs-primary-rgb), 0.05);
    }
    .contact-row td {
        vertical-align: middle;
    }
    .avatar-circle {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 13px;
        color: #fff;
    }
    .type-badge {
        font-size: 11px;
        padding: 3px 8px;
        border-radius: 12px;
    }
    .strength-indicator {
        font-size: 12px;
    }
    .tag-badge {
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 4px;
        background: #e8f0fe;
        color: #556ee6;
        margin-right: 3px;
    }
    .filter-row {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
    }
    .status-filter-btn {
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-size: 13px;
        border: 1px solid #e9ebec;
        background: #fff;
        color: #495057;
        transition: all 0.2s ease;
    }
    .status-filter-btn:hover {
        background: #f8f9fa;
    }
    .status-filter-btn.active {
        background: var(--bs-primary);
        color: #fff;
        border-color: var(--bs-primary);
    }
</style>
@endsection

@section('content')

    @component('components.breadcrumb')
        @slot('li_1') CRM @endslot
        @slot('title') Business Contacts @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Stats Row -->
    <div class="row mb-3">
        <div class="col-md-3 col-sm-6 col-6 mb-2">
            <div class="stats-card bg-light">
                <h3 class="text-dark mb-1">{{ $contacts->total() }}</h3>
                <small class="text-secondary">Total Contacts</small>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-6 mb-2">
            <div class="stats-card bg-success bg-opacity-10">
                <h3 class="text-success mb-1">{{ $contacts->where('contactStatus', 'active')->count() }}</h3>
                <small class="text-secondary">Active</small>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-6 mb-2">
            <div class="stats-card bg-info bg-opacity-10">
                <h3 class="text-info mb-1">{{ $contacts->where('relationshipStrength', 'strong')->count() }}</h3>
                <small class="text-secondary">Strong Relations</small>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-6 mb-2">
            <div class="stats-card bg-warning bg-opacity-10">
                <h3 class="text-warning mb-1">{{ $contacts->whereNotNull('lastContactDate')->where('lastContactDate', '<', now()->subDays(30))->count() }}</h3>
                <small class="text-secondary">Need Follow-up</small>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <!-- Header -->
                    <div class="d-flex flex-wrap align-items-center mb-3 gap-2">
                        <h4 class="card-title me-3 mb-0">Business Contacts</h4>
                        <div class="ms-auto">
                            <a href="{{ route('crm-business-contacts.create') }}" class="btn btn-primary btn-sm">
                                <i class="bx bx-plus me-1"></i> Add Contact
                            </a>
                        </div>
                    </div>

                    <!-- Filters -->
                    <form method="GET" action="{{ route('crm-business-contacts') }}" id="filterForm">
                        <div class="filter-row mb-3">
                            <!-- Search -->
                            <input type="text" class="form-control form-control-sm" name="search" value="{{ request('search') }}"
                                   placeholder="Search contacts..." style="width: 180px;">

                            <!-- Contact Type -->
                            <select class="form-select form-select-sm" name="type" style="width: auto; min-width: 130px;" onchange="this.form.submit()">
                                <option value="">All Types</option>
                                @foreach(\App\Models\CrmBusinessContact::CONTACT_TYPE_OPTIONS as $value => $option)
                                    <option value="{{ $value }}" {{ request('type') == $value ? 'selected' : '' }}>
                                        {{ $option['label'] }}
                                    </option>
                                @endforeach
                            </select>

                            <!-- Relationship Strength -->
                            <select class="form-select form-select-sm" name="strength" style="width: auto; min-width: 130px;" onchange="this.form.submit()">
                                <option value="">All Strengths</option>
                                @foreach(\App\Models\CrmBusinessContact::RELATIONSHIP_STRENGTH_OPTIONS as $value => $option)
                                    <option value="{{ $value }}" {{ request('strength') == $value ? 'selected' : '' }}>
                                        {{ $option['label'] }}
                                    </option>
                                @endforeach
                            </select>

                            <!-- Store -->
                            @if($stores->count() > 0)
                            <select class="form-select form-select-sm" name="store" style="width: auto; min-width: 130px;" onchange="this.form.submit()">
                                <option value="">All Stores</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}" {{ request('store') == $store->id ? 'selected' : '' }}>
                                        {{ $store->storeName }}
                                    </option>
                                @endforeach
                            </select>
                            @endif

                            <!-- Clear Filters -->
                            <a href="{{ route('crm-business-contacts') }}" class="btn btn-outline-secondary btn-sm" title="Clear all filters">
                                <i class="bx bx-x"></i>
                            </a>
                        </div>

                        <!-- Status Filter Pills -->
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <a href="{{ route('crm-business-contacts', array_merge(request()->except('status', 'page'), ['status' => ''])) }}"
                               class="status-filter-btn {{ !request('status') ? 'active' : '' }}">
                                <i class="bx bx-list-ul me-1"></i> All
                            </a>
                            @foreach(\App\Models\CrmBusinessContact::STATUS_OPTIONS as $value => $option)
                            <a href="{{ route('crm-business-contacts', array_merge(request()->except('page'), ['status' => $value])) }}"
                               class="status-filter-btn {{ request('status') == $value ? 'active' : '' }}">
                                <i class="mdi {{ $option['icon'] }} me-1"></i> {{ $option['label'] }}
                            </a>
                            @endforeach
                        </div>
                    </form>

                    <!-- Contacts Table -->
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;"></th>
                                    <th>Contact</th>
                                    <th>Contact Info</th>
                                    <th style="min-width: 130px;">Type</th>
                                    <th class="text-center" style="width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($contacts as $contact)
                                    @php
                                        $avatarColors = ['#556ee6', '#34c38f', '#50a5f1', '#f1b44c', '#f46a6a', '#74788d', '#343a40', '#0ab39c'];
                                        $avatarColor = $avatarColors[$loop->index % count($avatarColors)];
                                        $initials = '';
                                        if ($contact->firstName) {
                                            $initials .= strtoupper(substr($contact->firstName, 0, 1));
                                        }
                                        if ($contact->lastName) {
                                            $initials .= strtoupper(substr($contact->lastName, 0, 1));
                                        }
                                        if (!$initials && $contact->companyName) {
                                            $initials = strtoupper(substr($contact->companyName, 0, 2));
                                        }
                                        if (!$initials) {
                                            $initials = '?';
                                        }
                                    @endphp
                                    <tr class="contact-row" data-contact-id="{{ $contact->id }}">
                                        <td>
                                            <div class="avatar-circle" style="background-color: {{ $avatarColor }};">
                                                {{ $initials }}
                                            </div>
                                        </td>
                                        <td>
                                            <strong class="text-dark d-block">{{ $contact->fullName }}</strong>
                                            @if($contact->nickname)
                                                <small class="text-secondary">"{{ $contact->nickname }}"</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($contact->phone)
                                                <div class="text-dark"><i class="bx bx-phone text-muted me-1"></i>{{ $contact->phone }}</div>
                                            @endif
                                            @if($contact->email)
                                                <div class="text-secondary small"><i class="bx bx-envelope text-muted me-1"></i>{{ $contact->email }}</div>
                                            @endif
                                            @if(!$contact->phone && !$contact->email)
                                                <span class="text-secondary">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="type-badge bg-{{ $contact->type_color }} text-white">
                                                <i class="{{ $contact->type_icon }} me-1"></i>{{ $contact->type_label }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('crm-business-contacts.show', ['id' => $contact->id]) }}" class="btn btn-soft-secondary" title="View">
                                                    <i class="bx bx-show"></i>
                                                </a>
                                                <a href="{{ route('crm-business-contacts.edit', ['id' => $contact->id]) }}" class="btn btn-soft-primary" title="Edit">
                                                    <i class="bx bx-edit-alt"></i>
                                                </a>
                                                <button type="button" class="btn btn-soft-danger delete-btn" data-id="{{ $contact->id }}" data-name="{{ $contact->fullName }}" title="Delete">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
                                            <i class="mdi mdi-account-search text-secondary" style="font-size: 2.5rem;"></i>
                                            <p class="text-dark mt-2 mb-0">No business contacts found.</p>
                                            <small class="text-secondary">Add a new contact or adjust your filters.</small>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($contacts->hasPages())
                    <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 gap-2">
                        <div>
                            <span class="text-secondary small">
                                Showing <strong class="text-dark">{{ $contacts->firstItem() ?? 0 }}</strong> to
                                <strong class="text-dark">{{ $contacts->lastItem() ?? 0 }}</strong> of
                                <strong class="text-dark">{{ $contacts->total() }}</strong> contacts
                            </span>
                        </div>
                        <nav>
                            {{ $contacts->appends(request()->query())->links() }}
                        </nav>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-trash text-danger me-2"></i>Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-dark">Are you sure you want to delete <strong id="deleteContactName"></strong>?</p>
                    <p class="text-secondary small mb-0">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">
                        <i class="bx bx-trash me-1"></i> Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>

<script>
$(document).ready(function() {
    let deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    let contactToDelete = null;

    // Toastr options
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };

    // Row click - go to view
    $('.contact-row').on('click', function(e) {
        if ($(e.target).closest('button, a').length) return;
        const contactId = $(this).data('contact-id');
        window.location.href = '{{ url("crm-business-contacts-view") }}?id=' + contactId;
    });

    // Delete button click
    $('.delete-btn').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        contactToDelete = {
            id: $(this).data('id'),
            name: $(this).data('name')
        };
        $('#deleteContactName').text(contactToDelete.name);
        deleteModal.show();
    });

    // Confirm delete
    $('#confirmDelete').on('click', function() {
        if (!contactToDelete) return;

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Deleting...');

        $.ajax({
            url: '/crm-business-contacts/' + contactToDelete.id,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    deleteModal.hide();
                    toastr.success(response.message, 'Deleted!');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toastr.error(response.message, 'Error!');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to delete contact', 'Error!');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-trash me-1"></i> Delete');
            }
        });
    });

    // Search on enter
    $('input[name="search"]').on('keypress', function(e) {
        if (e.which === 13) {
            $('#filterForm').submit();
        }
    });
});
</script>
@endsection
