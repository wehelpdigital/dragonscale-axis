@extends('layouts.master')

@section('title') Customer Referrals @endsection

@section('css')
<!-- DataTables -->
<link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<!-- Toastr -->
<link rel="stylesheet" type="text/css" href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}">
<!-- DataTables customization -->
<style>
.dataTables_wrapper .dataTables_length select {
    min-width: 60px;
}
.dataTables_wrapper .dataTables_filter input {
    min-width: 200px;
}
.store-card .dataTables_wrapper {
    padding: 0 1rem 1rem 1rem;
}
.store-card .dataTables_info,
.store-card .dataTables_paginate {
    padding: 0.5rem 0;
}
</style>

<style>
.affiliate-photo {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e9ecef;
}

.affiliate-photo-placeholder {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #e9ecef;
}

.store-card {
    border: 1px solid #e9ecef;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
}

.store-card-header {
    background: #f8f9fa;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e9ecef;
    border-radius: 0.5rem 0.5rem 0 0;
}

.search-highlight {
    background-color: #fff3cd;
    padding: 0 2px;
    border-radius: 2px;
}

.customer-select-row {
    cursor: pointer;
    transition: background-color 0.15s ease;
}

.customer-select-row:hover {
    background-color: #e3f2fd !important;
}

.customer-select-row.selected {
    background-color: #d4edda !important;
}

.btn-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
}
</style>
@endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') E-commerce @endslot
    @slot('li_2') <a href="{{ route('ecom-affiliates') }}">Affiliates</a> @endslot
    @slot('title') Customer Referrals @endslot
@endcomponent

<!-- Affiliate Header -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex align-items-center">
            @if($affiliate->userPhoto)
                <img src="{{ asset($affiliate->userPhoto) }}" alt="{{ $affiliate->full_name }}" class="affiliate-photo me-3">
            @else
                <div class="affiliate-photo-placeholder me-3">
                    <i class="bx bx-user text-secondary" style="font-size: 1.75rem;"></i>
                </div>
            @endif
            <div class="flex-grow-1">
                <div class="d-flex align-items-center mb-1">
                    <h5 class="text-dark mb-0 me-2">{{ $affiliate->full_name }}</h5>
                    @if($affiliate->accountStatus === 'active')
                        <span class="badge bg-success">Active</span>
                    @else
                        <span class="badge bg-secondary">Inactive</span>
                    @endif
                </div>
                <div class="text-secondary">
                    <i class="bx bx-phone me-1"></i>{{ $affiliate->phoneNumber }}
                    @if($affiliate->emailAddress)
                        <span class="mx-2">|</span>
                        <i class="bx bx-envelope me-1"></i>{{ $affiliate->emailAddress }}
                    @endif
                </div>
            </div>
            <div class="text-end">
                <div class="mb-2">
                    <span class="text-dark fw-medium me-3">
                        <i class="bx bx-store text-primary me-1"></i>{{ $affiliate->stores->count() }} Store(s)
                    </span>
                    <span class="text-dark fw-medium">
                        <i class="bx bx-group text-info me-1"></i>{{ $totalReferrals }} Referral(s)
                    </span>
                </div>
                <a href="{{ route('ecom-affiliates') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bx bx-arrow-back me-1"></i>Back to Affiliates
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Flash Messages -->
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Store Sections -->
@if($affiliateStores->isEmpty())
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bx bx-store text-secondary" style="font-size: 4rem;"></i>
            <h5 class="text-dark mt-3">No Stores Assigned</h5>
            <p class="text-secondary mb-0">This affiliate is not connected to any stores yet.</p>
            <a href="{{ route('ecom-affiliates.edit', ['id' => $affiliate->id]) }}" class="btn btn-primary mt-3">
                <i class="bx bx-edit me-1"></i>Edit Affiliate
            </a>
        </div>
    </div>
@else
    @foreach($affiliateStores as $affiliateStore)
        @php
            $storeReferrals = $referralsByStore->get($affiliateStore->storeId, collect());
        @endphp
        <div class="store-card">
            <div class="store-card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0 text-dark">
                        <i class="bx bx-store me-2 text-primary"></i>{{ $affiliateStore->store->storeName ?? 'Unknown Store' }}
                    </h5>
                    <small class="text-secondary">{{ $storeReferrals->count() }} customer(s) referred</small>
                </div>
                <div>
                    <button type="button" class="btn btn-primary btn-sm add-existing-btn"
                            data-store-id="{{ $affiliateStore->storeId }}"
                            data-store-name="{{ $affiliateStore->store->storeName ?? 'Unknown Store' }}">
                        <i class="bx bx-user-plus me-1"></i>Add Existing Customer
                    </button>
                    <button type="button" class="btn btn-success btn-sm add-new-btn"
                            data-store-id="{{ $affiliateStore->storeId }}"
                            data-store-name="{{ $affiliateStore->store->storeName ?? 'Unknown Store' }}">
                        <i class="bx bx-plus me-1"></i>Add New Customer
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                @if($storeReferrals->isEmpty())
                    <div class="text-center py-4">
                        <i class="bx bx-user-x text-secondary" style="font-size: 2.5rem;"></i>
                        <p class="text-secondary mt-2 mb-0">No customers referred for this store yet.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 referrals-datatable" id="referrals-table-{{ $affiliateStore->storeId }}">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-dark">Customer Name</th>
                                    <th class="text-dark">Phone</th>
                                    <th class="text-dark">Email</th>
                                    <th class="text-dark">Referral Date</th>
                                    <th class="text-dark">Notes</th>
                                    <th class="text-center text-dark" style="width: 100px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($storeReferrals as $referral)
                                    <tr id="referral-row-{{ $referral->id }}">
                                        <td class="text-dark">
                                            <i class="bx bx-user me-1 text-secondary"></i>
                                            {{ $referral->client->full_name ?? 'Unknown' }}
                                        </td>
                                        <td class="text-dark">{{ $referral->client->clientPhoneNumber ?? '-' }}</td>
                                        <td class="text-dark">{{ $referral->client->clientEmailAddress ?? '-' }}</td>
                                        <td class="text-dark" data-order="{{ $referral->referralDate->format('Y-m-d') }}">{{ $referral->referralDate->format('M d, Y') }}</td>
                                        <td class="text-secondary">
                                            <small>{{ $referral->referralNotes ?? '-' }}</small>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-outline-danger btn-sm remove-referral-btn"
                                                    data-referral-id="{{ $referral->id }}"
                                                    data-client-name="{{ $referral->client->full_name ?? 'Unknown' }}"
                                                    data-store-name="{{ $affiliateStore->store->storeName ?? 'Unknown Store' }}"
                                                    title="Remove Referral">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @endforeach
@endif

<!-- Add Existing Customer Modal -->
<div class="modal fade" id="addExistingModal" tabindex="-1" aria-labelledby="addExistingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addExistingModalLabel">
                    <i class="bx bx-user-plus me-2"></i>Add Existing Customer as Referral
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="addExistingStoreId">

                <div class="mb-3">
                    <label class="form-label text-dark fw-medium">Store</label>
                    <input type="text" class="form-control" id="addExistingStoreName" readonly>
                </div>

                <!-- Search Box -->
                <div class="mb-3">
                    <label class="form-label text-dark fw-medium">Search Customer</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="text" class="form-control" id="customerSearchInput"
                               placeholder="Type to search by name, phone, or email...">
                    </div>
                </div>

                <!-- Customer Table -->
                <div class="mb-3">
                    <label class="form-label text-dark fw-medium">Select Customer <span class="text-danger">*</span></label>
                    <div class="border rounded" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-sm table-hover mb-0" id="customerSelectTable">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="text-dark">Name</th>
                                    <th class="text-dark">Phone</th>
                                    <th class="text-dark">Email</th>
                                </tr>
                            </thead>
                            <tbody id="customerTableBody">
                                <tr>
                                    <td colspan="3" class="text-center text-secondary py-3">
                                        <i class="bx bx-loader-alt bx-spin me-1"></i>Loading customers...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <input type="hidden" id="selectedClientId">
                    <div id="selectedCustomerInfo" class="mt-2" style="display: none;">
                        <span class="badge bg-success">
                            <i class="bx bx-check me-1"></i>Selected: <span id="selectedCustomerName"></span>
                        </span>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="addExistingRefDate" class="form-label text-dark fw-medium">Referral Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="addExistingRefDate" required value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="addExistingRefNotes" class="form-label text-dark fw-medium">Notes (Optional)</label>
                        <input type="text" class="form-control" id="addExistingRefNotes" maxlength="500" placeholder="Any notes...">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary" id="confirmAddExisting" disabled>
                    <i class="bx bx-check me-1"></i>Add Referral
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add New Customer Modal -->
<div class="modal fade" id="addNewModal" tabindex="-1" aria-labelledby="addNewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="addNewModalLabel">
                    <i class="bx bx-user-plus me-2"></i>Add New Customer as Referral
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addNewForm">
                    <input type="hidden" id="addNewStoreId">

                    <div class="mb-3">
                        <label class="form-label text-dark fw-medium">Store</label>
                        <input type="text" class="form-control" id="addNewStoreName" readonly>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="newFirstName" class="form-label text-dark fw-medium">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="newFirstName" required maxlength="100" placeholder="First name">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="newMiddleName" class="form-label text-dark fw-medium">Middle Name</label>
                            <input type="text" class="form-control" id="newMiddleName" maxlength="100" placeholder="Middle name">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="newLastName" class="form-label text-dark fw-medium">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="newLastName" required maxlength="100" placeholder="Last name">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="newPhone" class="form-label text-dark fw-medium">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="newPhone" required maxlength="50" placeholder="e.g., 09171234567">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="newEmail" class="form-label text-dark fw-medium">Email Address</label>
                            <input type="email" class="form-control" id="newEmail" maxlength="255" placeholder="e.g., customer@email.com">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="newRefDate" class="form-label text-dark fw-medium">Referral Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="newRefDate" required value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="newRefNotes" class="form-label text-dark fw-medium">Notes (Optional)</label>
                            <input type="text" class="form-control" id="newRefNotes" maxlength="500" placeholder="Any notes...">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-success" id="confirmAddNew">
                    <i class="bx bx-check me-1"></i>Create Customer & Add Referral
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Remove Referral Confirmation Modal -->
<div class="modal fade" id="removeReferralModal" tabindex="-1" aria-labelledby="removeReferralModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="removeReferralModalLabel">
                    <i class="bx bx-user-minus text-danger me-2"></i>Remove Referral
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-dark">Are you sure you want to remove this referral?</p>
                <p class="mb-0"><strong class="text-dark">Customer:</strong> <span id="removeClientName" class="text-dark"></span></p>
                <p class="mb-0"><strong class="text-dark">Store:</strong> <span id="removeStoreName" class="text-dark"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmRemove">
                    <i class="bx bx-trash me-1"></i>Remove
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<!-- DataTables -->
<script src="{{ URL::asset('build/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js') }}"></script>
<!-- Toastr -->
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>

<script>
// Toastr configuration
toastr.options = {
    closeButton: true,
    progressBar: true,
    positionClass: "toast-top-right",
    timeOut: 3000,
    extendedTimeOut: 1000,
    preventDuplicates: true
};

// Initialize DataTables for all referral tables
$(document).ready(function() {
    $('.referrals-datatable').each(function() {
        $(this).DataTable({
            responsive: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            order: [[3, 'desc']], // Sort by Referral Date descending
            columnDefs: [
                { orderable: false, targets: 5 } // Disable sorting on Action column
            ],
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ referrals",
                infoEmpty: "Showing 0 to 0 of 0 referrals",
                infoFiltered: "(filtered from _MAX_ total referrals)",
                paginate: {
                    first: '<i class="bx bx-chevrons-left"></i>',
                    previous: '<i class="bx bx-chevron-left"></i>',
                    next: '<i class="bx bx-chevron-right"></i>',
                    last: '<i class="bx bx-chevrons-right"></i>'
                },
                emptyTable: "No referrals found"
            },
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip'
        });
    });
});

const affiliateId = {{ $affiliate->id }};
let availableClients = [];
let selectedClientId = null;
let referralToRemove = null;

// Helper function to escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ===== ADD EXISTING CUSTOMER =====

$('.add-existing-btn').on('click', function() {
    const storeId = $(this).data('store-id');
    const storeName = $(this).data('store-name');

    // Reset
    $('#addExistingStoreId').val(storeId);
    $('#addExistingStoreName').val(storeName);
    $('#customerSearchInput').val('');
    $('#selectedClientId').val('');
    selectedClientId = null;
    $('#selectedCustomerInfo').hide();
    $('#confirmAddExisting').prop('disabled', true);
    $('#addExistingRefDate').val('{{ date("Y-m-d") }}');
    $('#addExistingRefNotes').val('');

    // Show loading
    $('#customerTableBody').html(`
        <tr>
            <td colspan="3" class="text-center text-secondary py-3">
                <i class="bx bx-loader-alt bx-spin me-1"></i>Loading customers...
            </td>
        </tr>
    `);

    // Show modal
    $('#addExistingModal').modal('show');

    // Load available clients
    $.ajax({
        url: '/ecom-affiliates/' + affiliateId + '/referrals/available-clients/' + storeId,
        type: 'GET',
        success: function(response) {
            if (response.success) {
                availableClients = response.clients;
                renderCustomerTable(availableClients);
            } else {
                toastr.error(response.message, 'Error!');
                $('#addExistingModal').modal('hide');
            }
        },
        error: function() {
            toastr.error('Failed to load customers.', 'Error!');
            $('#addExistingModal').modal('hide');
        }
    });
});

// Render customer table
function renderCustomerTable(clients) {
    if (clients.length === 0) {
        $('#customerTableBody').html(`
            <tr>
                <td colspan="3" class="text-center text-secondary py-3">
                    <i class="bx bx-user-x me-1"></i>No available customers found.
                </td>
            </tr>
        `);
        return;
    }

    let html = '';
    clients.forEach(function(client) {
        const isSelected = selectedClientId == client.id;
        html += `
            <tr class="customer-select-row ${isSelected ? 'selected' : ''}" data-client-id="${client.id}" data-client-name="${escapeHtml(client.fullName)}">
                <td class="text-dark">${escapeHtml(client.fullName)}</td>
                <td class="text-dark">${escapeHtml(client.phone || '-')}</td>
                <td class="text-dark">${escapeHtml(client.email || '-')}</td>
            </tr>
        `;
    });
    $('#customerTableBody').html(html);
}

// Search customers
$('#customerSearchInput').on('input', function() {
    const searchTerm = $(this).val().toLowerCase().trim();

    if (!searchTerm) {
        renderCustomerTable(availableClients);
        return;
    }

    const filtered = availableClients.filter(function(client) {
        const name = (client.fullName || '').toLowerCase();
        const phone = (client.phone || '').toLowerCase();
        const email = (client.email || '').toLowerCase();
        return name.includes(searchTerm) || phone.includes(searchTerm) || email.includes(searchTerm);
    });

    renderCustomerTable(filtered);
});

// Select customer from table
$(document).on('click', '.customer-select-row', function() {
    const clientId = $(this).data('client-id');
    const clientName = $(this).data('client-name');

    // Update selection
    $('.customer-select-row').removeClass('selected');
    $(this).addClass('selected');

    selectedClientId = clientId;
    $('#selectedClientId').val(clientId);
    $('#selectedCustomerName').text(clientName);
    $('#selectedCustomerInfo').show();
    $('#confirmAddExisting').prop('disabled', false);
});

// Confirm add existing referral
$('#confirmAddExisting').on('click', function() {
    if (!selectedClientId) {
        toastr.error('Please select a customer.', 'Validation Error');
        return;
    }

    const storeId = $('#addExistingStoreId').val();
    const referralDate = $('#addExistingRefDate').val();
    const referralNotes = $('#addExistingRefNotes').val();

    if (!referralDate) {
        toastr.error('Please enter referral date.', 'Validation Error');
        return;
    }

    const $btn = $(this);
    const originalText = $btn.html();
    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Adding...');

    $.ajax({
        url: '/ecom-affiliates/' + affiliateId + '/referrals',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            storeId: storeId,
            clientId: selectedClientId,
            referralDate: referralDate,
            referralNotes: referralNotes
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message, 'Success!');
                // Reload page to show new referral
                location.reload();
            } else {
                toastr.error(response.message, 'Error!');
                $btn.prop('disabled', false).html(originalText);
            }
        },
        error: function(xhr) {
            let errorMessage = 'An error occurred while adding the referral.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            toastr.error(errorMessage, 'Error!');
            $btn.prop('disabled', false).html(originalText);
        }
    });
});

// Reset Add Existing modal
$('#addExistingModal').on('hidden.bs.modal', function() {
    availableClients = [];
    selectedClientId = null;
});

// ===== ADD NEW CUSTOMER =====

$('.add-new-btn').on('click', function() {
    const storeId = $(this).data('store-id');
    const storeName = $(this).data('store-name');

    // Reset form
    $('#addNewForm')[0].reset();
    $('#addNewStoreId').val(storeId);
    $('#addNewStoreName').val(storeName);
    $('#newRefDate').val('{{ date("Y-m-d") }}');

    // Show modal
    $('#addNewModal').modal('show');
});

// Confirm add new customer
$('#confirmAddNew').on('click', function() {
    const storeId = $('#addNewStoreId').val();
    const firstName = $('#newFirstName').val().trim();
    const middleName = $('#newMiddleName').val().trim();
    const lastName = $('#newLastName').val().trim();
    const phone = $('#newPhone').val().trim();
    const email = $('#newEmail').val().trim();
    const referralDate = $('#newRefDate').val();
    const referralNotes = $('#newRefNotes').val().trim();

    // Validation
    if (!firstName) {
        toastr.error('First name is required.', 'Validation Error');
        $('#newFirstName').focus();
        return;
    }
    if (!lastName) {
        toastr.error('Last name is required.', 'Validation Error');
        $('#newLastName').focus();
        return;
    }
    if (!phone) {
        toastr.error('Phone number is required.', 'Validation Error');
        $('#newPhone').focus();
        return;
    }
    if (!referralDate) {
        toastr.error('Referral date is required.', 'Validation Error');
        return;
    }

    const $btn = $(this);
    const originalText = $btn.html();
    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Creating...');

    $.ajax({
        url: '/ecom-affiliates/' + affiliateId + '/referrals/new-client',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            storeId: storeId,
            clientFirstName: firstName,
            clientMiddleName: middleName,
            clientLastName: lastName,
            clientPhoneNumber: phone,
            clientEmailAddress: email,
            referralDate: referralDate,
            referralNotes: referralNotes
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message, 'Success!');
                // Reload page to show new referral
                location.reload();
            } else {
                toastr.error(response.message, 'Error!');
                $btn.prop('disabled', false).html(originalText);
            }
        },
        error: function(xhr) {
            let errorMessage = 'An error occurred while creating the customer.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            toastr.error(errorMessage, 'Error!');
            $btn.prop('disabled', false).html(originalText);
        }
    });
});

// ===== REMOVE REFERRAL =====

$('.remove-referral-btn').on('click', function() {
    const referralId = $(this).data('referral-id');
    const clientName = $(this).data('client-name');
    const storeName = $(this).data('store-name');

    referralToRemove = {
        id: referralId,
        clientName: clientName,
        storeName: storeName
    };

    $('#removeClientName').text(clientName);
    $('#removeStoreName').text(storeName);
    $('#removeReferralModal').modal('show');
});

$('#confirmRemove').on('click', function() {
    if (!referralToRemove) return;

    const $btn = $(this);
    const originalText = $btn.html();
    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Removing...');

    $.ajax({
        url: '/ecom-affiliate-referrals/' + referralToRemove.id,
        type: 'DELETE',
        data: { _token: '{{ csrf_token() }}' },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message, 'Success!');
                $('#removeReferralModal').modal('hide');
                // Remove row from table
                $('#referral-row-' + referralToRemove.id).fadeOut(400, function() {
                    $(this).remove();
                });
            } else {
                toastr.error(response.message, 'Error!');
            }
        },
        error: function(xhr) {
            let errorMessage = 'An error occurred while removing the referral.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            toastr.error(errorMessage, 'Error!');
        },
        complete: function() {
            $btn.prop('disabled', false).html(originalText);
            referralToRemove = null;
        }
    });
});

$('#removeReferralModal').on('hidden.bs.modal', function() {
    referralToRemove = null;
});
</script>
@endsection
