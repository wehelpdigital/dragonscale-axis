@extends('layouts.master')

@section('title') AI Technician Clients @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    .stats-card {
        border-radius: 0.5rem;
        transition: all 0.2s;
    }
    .stats-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .stats-icon {
        width: 48px;
        height: 48px;
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    .client-card {
        border: 1px solid #e9ecef;
        border-radius: 0.5rem;
        transition: all 0.2s;
        background: #fff;
    }
    .client-card:hover {
        border-color: #556ee6;
        box-shadow: 0 2px 8px rgba(85, 110, 230, 0.15);
    }
    .client-avatar {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        font-weight: 600;
        flex-shrink: 0;
    }
    .search-box {
        position: relative;
    }
    .search-box .search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #74788d;
    }
    .search-box input {
        padding-left: 38px;
    }
    .filter-badge {
        cursor: pointer;
        transition: all 0.2s;
    }
    .filter-badge:hover {
        opacity: 0.8;
    }
    .filter-badge.active {
        box-shadow: 0 0 0 2px rgba(85, 110, 230, 0.5);
    }
    .empty-state {
        padding: 4rem 2rem;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    .empty-state .empty-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1.5rem;
    }
    .empty-state .empty-icon i {
        font-size: 2.5rem;
        color: #6c757d;
    }
    .empty-state h5 {
        margin-bottom: 0.5rem;
    }
    .empty-state p {
        max-width: 300px;
        margin-bottom: 1.5rem;
    }
    .client-table th {
        font-size: 0.75rem;
        text-transform: uppercase;
        color: #74788d;
        font-weight: 600;
        border-bottom: 2px solid #e9ecef;
    }
    .client-table td {
        vertical-align: middle;
    }
    .action-btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
    }
    .select-client-item {
        cursor: pointer;
        padding: 0.75rem;
        border: 1px solid #e9ecef;
        border-radius: 0.375rem;
        margin-bottom: 0.5rem;
        transition: all 0.15s;
    }
    .select-client-item:hover {
        background: #f8f9fa;
        border-color: #556ee6;
    }
    .select-client-item.selected {
        background: #e8edfa;
        border-color: #556ee6;
    }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') AI Technician @endslot
@slot('title') Clients @endslot
@endcomponent

<!-- Statistics Cards -->
<div class="row mb-4" id="statsRow">
    <div class="col-md-3">
        <div class="card stats-card mb-0">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stats-icon bg-primary bg-soft text-primary me-3">
                        <i class="bx bx-user"></i>
                    </div>
                    <div>
                        <p class="text-secondary mb-1 small">Total Clients</p>
                        <h4 class="mb-0 text-dark" id="statTotal">0</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card mb-0">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stats-icon bg-success bg-soft text-success me-3">
                        <i class="bx bx-check-circle"></i>
                    </div>
                    <div>
                        <p class="text-secondary mb-1 small">Active</p>
                        <h4 class="mb-0 text-dark" id="statActive">0</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card mb-0">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stats-icon bg-warning bg-soft text-warning me-3">
                        <i class="bx bx-time"></i>
                    </div>
                    <div>
                        <p class="text-secondary mb-1 small">Expiring Soon</p>
                        <h4 class="mb-0 text-dark" id="statExpiring">0</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card mb-0">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stats-icon bg-danger bg-soft text-danger me-3">
                        <i class="bx bx-x-circle"></i>
                    </div>
                    <div>
                        <p class="text-secondary mb-1 small">Expired</p>
                        <h4 class="mb-0 text-dark" id="statExpired">0</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <!-- Header with Search and Add Button -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="search-box">
                            <i class="bx bx-search search-icon"></i>
                            <input type="text" class="form-control" id="searchInput" placeholder="Search by name, email, phone...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="statusFilter">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="expiring">Expiring Soon</option>
                            <option value="expired">Expired</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="storeFilter">
                            <option value="">All Stores</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->storeName }}">{{ $store->storeName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 text-end">
                        <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#addClientModal">
                            <i class="bx bx-plus me-1"></i>Add Client
                        </button>
                    </div>
                </div>

                <!-- Clients Table -->
                <div class="table-responsive">
                    <table class="table table-hover client-table mb-0">
                        <thead>
                            <tr>
                                <th style="width: 30px;">
                                    <input type="checkbox" class="form-check-input" id="selectAllClients">
                                </th>
                                <th>Client</th>
                                <th>Store</th>
                                <th>Granted</th>
                                <th>Expiration</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="clientsTableBody">
                            <!-- Populated by JavaScript -->
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div class="empty-state d-none" id="emptyState">
                    <div class="empty-icon">
                        <i class="bx bx-user-x"></i>
                    </div>
                    <h5 class="text-dark">No Clients Found</h5>
                    <p class="text-secondary text-center">Add clients to grant them access to AI Technician features.</p>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClientModal">
                        <i class="bx bx-plus me-1"></i>Add Your First Client
                    </button>
                </div>

                <!-- Loading State -->
                <div class="text-center py-5" id="loadingState">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-secondary mt-2">Loading clients...</p>
                </div>

                <!-- Bulk Actions (shown when items selected) -->
                <div class="card bg-light mt-3 d-none" id="bulkActionsCard">
                    <div class="card-body py-2">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-dark"><strong id="selectedCount">0</strong> clients selected</span>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="bulkExtendBtn">
                                    <i class="bx bx-calendar-plus me-1"></i>Extend Access
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Client Modal -->
<div class="modal fade" id="addClientModal" tabindex="-1" aria-labelledby="addClientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-dark" id="addClientModalLabel">
                    <i class="bx bx-user-plus text-primary me-2"></i>Grant AI Technician Access
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Search for clients -->
                <div class="mb-4">
                    <label class="form-label text-dark">Search Client <span class="text-danger">*</span></label>
                    <div class="search-box">
                        <i class="bx bx-search search-icon"></i>
                        <input type="text" class="form-control" id="clientSearchInput" placeholder="Search by name, email, phone...">
                    </div>
                    <small class="text-secondary">Search from Ani-Senso store logins</small>
                </div>

                <!-- Client search results -->
                <div class="mb-4" id="clientSearchResults" style="max-height: 250px; overflow-y: auto;">
                    <div class="text-center py-4 text-secondary" id="clientSearchPlaceholder">
                        <i class="bx bx-search-alt" style="font-size: 2rem;"></i>
                        <p class="mt-2 mb-0">Type to search for clients</p>
                    </div>
                    <div class="text-center py-4 d-none" id="clientSearchLoading">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        <p class="mt-2 mb-0 text-secondary">Searching...</p>
                    </div>
                    <div id="clientSearchList">
                        <!-- Populated by JavaScript -->
                    </div>
                </div>

                <!-- Selected client display -->
                <div class="mb-4 d-none" id="selectedClientCard">
                    <label class="form-label text-dark">Selected Client</label>
                    <div class="p-3 bg-light rounded">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <div class="client-avatar bg-primary text-white me-3" id="selectedClientAvatar">JD</div>
                                <div>
                                    <h6 class="mb-0 text-dark" id="selectedClientName">John Doe</h6>
                                    <small class="text-secondary" id="selectedClientInfo">john@example.com</small>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger" id="clearSelectedClient">
                                <i class="bx bx-x"></i>
                            </button>
                        </div>
                    </div>
                    <input type="hidden" id="selectedClientId">
                </div>

                <hr>

                <!-- Expiration Date -->
                <div class="mb-3">
                    <label class="form-label text-dark">Access Expiration</label>
                    <input type="date" class="form-control" id="newExpirationDate">
                    <small class="text-secondary">Leave empty for lifetime access</small>
                </div>

                <!-- Notes -->
                <div class="mb-3">
                    <label class="form-label text-dark">Notes</label>
                    <textarea class="form-control" id="newNotes" rows="2" placeholder="Optional notes about this client..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="grantAccessBtn" disabled>
                    <i class="bx bx-check me-1"></i>Grant Access
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Client Modal -->
<div class="modal fade" id="editClientModal" tabindex="-1" aria-labelledby="editClientModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-dark" id="editClientModalLabel">
                    <i class="bx bx-edit text-primary me-2"></i>Edit Client Access
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Client info display -->
                <div class="mb-4 p-3 bg-light rounded">
                    <div class="d-flex align-items-center">
                        <div class="client-avatar bg-primary text-white me-3" id="editClientAvatar">JD</div>
                        <div>
                            <h6 class="mb-0 text-dark" id="editClientName">John Doe</h6>
                            <small class="text-secondary" id="editClientInfo">john@example.com</small>
                        </div>
                    </div>
                </div>

                <input type="hidden" id="editAccessId">

                <!-- Expiration Date -->
                <div class="mb-3">
                    <label class="form-label text-dark">Access Expiration</label>
                    <input type="date" class="form-control" id="editExpirationDate">
                    <small class="text-secondary">Leave empty for lifetime access</small>
                </div>

                <!-- Active Status -->
                <div class="mb-3">
                    <label class="form-label text-dark">Status</label>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="editIsActive" style="width: 3rem; height: 1.5rem;">
                        <label class="form-check-label ms-2 text-dark" for="editIsActive" id="editStatusLabel">Active</label>
                    </div>
                </div>

                <!-- Notes -->
                <div class="mb-3">
                    <label class="form-label text-dark">Notes</label>
                    <textarea class="form-control" id="editNotes" rows="3" placeholder="Optional notes..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveEditBtn">
                    <i class="bx bx-save me-1"></i>Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-dark">
                    <i class="bx bx-trash text-danger me-2"></i>Revoke Access
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-dark">Are you sure you want to revoke AI Technician access for <strong id="deleteClientName"></strong>?</p>
                <p class="text-secondary small mb-0">This action can be undone by granting access again.</p>
            </div>
            <div class="modal-footer">
                <input type="hidden" id="deleteAccessId">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="bx bx-trash me-1"></i>Revoke Access
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Extend Modal -->
<div class="modal fade" id="bulkExtendModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-dark">
                    <i class="bx bx-calendar-plus text-primary me-2"></i>Extend Access
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-dark">Extend access for <strong id="bulkExtendCount">0</strong> selected clients.</p>
                <div class="mb-3">
                    <label class="form-label text-dark">Extension Period</label>
                    <select class="form-select" id="extensionDays">
                        <option value="7">7 days</option>
                        <option value="14">14 days</option>
                        <option value="30" selected>30 days</option>
                        <option value="60">60 days</option>
                        <option value="90">90 days</option>
                        <option value="180">180 days</option>
                        <option value="365">1 year</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmBulkExtendBtn">
                    <i class="bx bx-check me-1"></i>Extend Access
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
    // Toastr configuration
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };

    let searchTimeout = null;
    let clientSearchTimeout = null;
    let selectedClientIds = [];

    // Initial load
    loadClients();

    // Search input handler
    $('#searchInput').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            loadClients();
        }, 300);
    });

    // Filter handlers
    $('#statusFilter, #storeFilter').on('change', function() {
        loadClients();
    });

    // Load clients
    function loadClients() {
        $('#loadingState').removeClass('d-none');
        $('#emptyState').addClass('d-none');
        $('#clientsTableBody').html('');

        $.ajax({
            url: '{{ route("ai-technician.clients.data") }}',
            type: 'GET',
            data: {
                search: $('#searchInput').val(),
                status: $('#statusFilter').val(),
                store: $('#storeFilter').val()
            },
            success: function(response) {
                $('#loadingState').addClass('d-none');

                if (response.success) {
                    // Update stats
                    $('#statTotal').text(response.stats.total);
                    $('#statActive').text(response.stats.active);
                    $('#statExpiring').text(response.stats.expiring);
                    $('#statExpired').text(response.stats.expired);

                    if (response.clients.length === 0) {
                        $('#emptyState').removeClass('d-none');
                    } else {
                        renderClients(response.clients);
                    }
                }
            },
            error: function() {
                $('#loadingState').addClass('d-none');
                toastr.error('Failed to load clients');
            }
        });
    }

    // Render clients table
    function renderClients(clients) {
        let html = '';
        clients.forEach(client => {
            const initials = getInitials(client.fullName);
            const bgColor = getColorFromName(client.fullName);

            html += `
                <tr data-access-id="${client.accessId}">
                    <td>
                        <input type="checkbox" class="form-check-input client-checkbox" data-id="${client.accessId}">
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="client-avatar me-3" style="background-color: ${bgColor}; color: #fff;">
                                ${initials}
                            </div>
                            <div>
                                <h6 class="mb-0 text-dark">${escapeHtml(client.fullName)}</h6>
                                <small class="text-secondary">
                                    ${client.email !== 'N/A' ? escapeHtml(client.email) : ''}
                                    ${client.email !== 'N/A' && client.phone !== 'N/A' ? ' | ' : ''}
                                    ${client.phone !== 'N/A' ? escapeHtml(client.phone) : ''}
                                </small>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge bg-secondary">${escapeHtml(client.store)}</span></td>
                    <td class="text-dark">${escapeHtml(client.grantedAt)}</td>
                    <td>${client.expirationBadge}</td>
                    <td>${client.statusBadge}</td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-outline-primary action-btn edit-btn me-1" data-id="${client.accessId}" title="Edit">
                            <i class="bx bx-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-${client.isActive ? 'warning' : 'success'} action-btn toggle-btn me-1" data-id="${client.accessId}" title="${client.isActive ? 'Disable' : 'Enable'}">
                            <i class="bx bx-${client.isActive ? 'pause' : 'play'}"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger action-btn delete-btn" data-id="${client.accessId}" data-name="${escapeHtml(client.fullName)}" title="Revoke">
                            <i class="bx bx-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        $('#clientsTableBody').html(html);
        updateBulkActionsVisibility();
    }

    // Client search in modal
    $('#clientSearchInput').on('input', function() {
        const search = $(this).val().trim();
        clearTimeout(clientSearchTimeout);

        if (search.length < 2) {
            $('#clientSearchPlaceholder').removeClass('d-none');
            $('#clientSearchLoading').addClass('d-none');
            $('#clientSearchList').html('');
            return;
        }

        $('#clientSearchPlaceholder').addClass('d-none');
        $('#clientSearchLoading').removeClass('d-none');
        $('#clientSearchList').html('');

        clientSearchTimeout = setTimeout(() => {
            searchAvailableClients(search);
        }, 300);
    });

    // Search available clients
    function searchAvailableClients(search) {
        $.ajax({
            url: '{{ route("ai-technician.clients.search") }}',
            type: 'GET',
            data: { search: search },
            success: function(response) {
                $('#clientSearchLoading').addClass('d-none');

                if (response.success && response.data.length > 0) {
                    let html = '';
                    response.data.forEach(client => {
                        const initials = getInitials(client.fullName);
                        const bgColor = getColorFromName(client.fullName);
                        html += `
                            <div class="select-client-item" data-id="${client.id}" data-name="${escapeHtml(client.fullName)}" data-email="${escapeHtml(client.email || '')}" data-phone="${escapeHtml(client.phone || '')}" data-store="${escapeHtml(client.store)}">
                                <div class="d-flex align-items-center">
                                    <div class="client-avatar me-3" style="background-color: ${bgColor}; color: #fff; width: 40px; height: 40px; font-size: 0.9rem;">
                                        ${initials}
                                    </div>
                                    <div>
                                        <h6 class="mb-0 text-dark">${escapeHtml(client.fullName)}</h6>
                                        <small class="text-secondary">
                                            ${client.email || ''} ${client.email && client.phone ? '|' : ''} ${client.phone || ''}
                                            <span class="badge bg-secondary ms-1">${escapeHtml(client.store)}</span>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    $('#clientSearchList').html(html);
                } else {
                    $('#clientSearchList').html('<div class="text-center py-3 text-secondary">No clients found</div>');
                }
            },
            error: function() {
                $('#clientSearchLoading').addClass('d-none');
                toastr.error('Failed to search clients');
            }
        });
    }

    // Select client from search results
    $(document).on('click', '.select-client-item', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const email = $(this).data('email');
        const phone = $(this).data('phone');
        const initials = getInitials(name);
        const bgColor = getColorFromName(name);

        $('#selectedClientId').val(id);
        $('#selectedClientName').text(name);
        $('#selectedClientInfo').text(email || phone || '');
        $('#selectedClientAvatar').text(initials).css('background-color', bgColor);
        $('#selectedClientCard').removeClass('d-none');
        $('#grantAccessBtn').prop('disabled', false);

        // Hide search results
        $('#clientSearchInput').val('');
        $('#clientSearchPlaceholder').removeClass('d-none');
        $('#clientSearchList').html('');
    });

    // Clear selected client
    $('#clearSelectedClient').on('click', function() {
        $('#selectedClientId').val('');
        $('#selectedClientCard').addClass('d-none');
        $('#grantAccessBtn').prop('disabled', true);
    });

    // Grant access
    $('#grantAccessBtn').on('click', function() {
        const clientId = $('#selectedClientId').val();
        if (!clientId) {
            toastr.error('Please select a client');
            return;
        }

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Granting...');

        $.ajax({
            url: '{{ route("ai-technician.clients.grant") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                accessClientId: clientId,
                expirationDate: $('#newExpirationDate').val() || null,
                notes: $('#newNotes').val()
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#addClientModal').modal('hide');
                    resetAddModal();
                    loadClients();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to grant access');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>Grant Access');
            }
        });
    });

    // Reset add modal
    function resetAddModal() {
        $('#selectedClientId').val('');
        $('#selectedClientCard').addClass('d-none');
        $('#grantAccessBtn').prop('disabled', true);
        $('#clientSearchInput').val('');
        $('#clientSearchList').html('');
        $('#clientSearchPlaceholder').removeClass('d-none');
        $('#newExpirationDate').val('');
        $('#newNotes').val('');
    }

    // Edit button handler
    $(document).on('click', '.edit-btn', function() {
        const accessId = $(this).data('id');
        loadClientForEdit(accessId);
    });

    // Load client for editing
    function loadClientForEdit(accessId) {
        $.ajax({
            url: `/ai-technician-clients/${accessId}`,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const access = response.access;
                    const client = response.client;

                    $('#editAccessId').val(access.id);
                    $('#editClientName').text(client?.fullName || 'Unknown');
                    $('#editClientInfo').text(client?.email || client?.phone || '');
                    $('#editClientAvatar').text(getInitials(client?.fullName || ''));
                    $('#editExpirationDate').val(access.expirationDate || '');
                    $('#editIsActive').prop('checked', access.isActive);
                    $('#editStatusLabel').text(access.isActive ? 'Active' : 'Inactive');
                    $('#editNotes').val(access.notes || '');

                    $('#editClientModal').modal('show');
                }
            },
            error: function() {
                toastr.error('Failed to load client details');
            }
        });
    }

    // Edit status toggle label
    $('#editIsActive').on('change', function() {
        $('#editStatusLabel').text($(this).is(':checked') ? 'Active' : 'Inactive');
    });

    // Save edit
    $('#saveEditBtn').on('click', function() {
        const accessId = $('#editAccessId').val();
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...');

        $.ajax({
            url: `/ai-technician-clients/${accessId}`,
            type: 'PUT',
            data: {
                _token: '{{ csrf_token() }}',
                expirationDate: $('#editExpirationDate').val() || null,
                isActive: $('#editIsActive').is(':checked') ? 1 : 0,
                notes: $('#editNotes').val()
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#editClientModal').modal('hide');
                    loadClients();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to update');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Changes');
            }
        });
    });

    // Toggle status
    $(document).on('click', '.toggle-btn', function() {
        const accessId = $(this).data('id');
        const $btn = $(this);
        $btn.prop('disabled', true);

        $.ajax({
            url: `/ai-technician-clients/${accessId}/toggle`,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    loadClients();
                }
            },
            error: function() {
                toastr.error('Failed to toggle status');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });

    // Delete button handler
    $(document).on('click', '.delete-btn', function() {
        const accessId = $(this).data('id');
        const name = $(this).data('name');
        $('#deleteAccessId').val(accessId);
        $('#deleteClientName').text(name);
        $('#deleteModal').modal('show');
    });

    // Confirm delete
    $('#confirmDeleteBtn').on('click', function() {
        const accessId = $('#deleteAccessId').val();
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Revoking...');

        $.ajax({
            url: `/ai-technician-clients/${accessId}`,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#deleteModal').modal('hide');
                    loadClients();
                }
            },
            error: function() {
                toastr.error('Failed to revoke access');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-trash me-1"></i>Revoke Access');
            }
        });
    });

    // Select all checkbox
    $('#selectAllClients').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('.client-checkbox').prop('checked', isChecked);
        updateBulkActionsVisibility();
    });

    // Individual checkbox
    $(document).on('change', '.client-checkbox', function() {
        updateBulkActionsVisibility();
    });

    // Update bulk actions visibility
    function updateBulkActionsVisibility() {
        selectedClientIds = [];
        $('.client-checkbox:checked').each(function() {
            selectedClientIds.push($(this).data('id'));
        });

        if (selectedClientIds.length > 0) {
            $('#bulkActionsCard').removeClass('d-none');
            $('#selectedCount').text(selectedClientIds.length);
        } else {
            $('#bulkActionsCard').addClass('d-none');
        }
    }

    // Bulk extend button
    $('#bulkExtendBtn').on('click', function() {
        $('#bulkExtendCount').text(selectedClientIds.length);
        $('#bulkExtendModal').modal('show');
    });

    // Confirm bulk extend
    $('#confirmBulkExtendBtn').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Extending...');

        $.ajax({
            url: '{{ route("ai-technician.clients.bulk-extend") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                accessIds: selectedClientIds,
                extensionDays: $('#extensionDays').val()
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#bulkExtendModal').modal('hide');
                    loadClients();
                    $('#selectAllClients').prop('checked', false);
                }
            },
            error: function() {
                toastr.error('Failed to extend access');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>Extend Access');
            }
        });
    });

    // Helper functions
    function getInitials(name) {
        if (!name) return '?';
        const parts = name.trim().split(' ');
        if (parts.length >= 2) {
            return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
        }
        return name.substring(0, 2).toUpperCase();
    }

    function getColorFromName(name) {
        const colors = ['#556ee6', '#34c38f', '#f1b44c', '#f46a6a', '#50a5f1', '#6f42c1'];
        let hash = 0;
        for (let i = 0; i < (name || '').length; i++) {
            hash = name.charCodeAt(i) + ((hash << 5) - hash);
        }
        return colors[Math.abs(hash) % colors.length];
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Reset modal on close
    $('#addClientModal').on('hidden.bs.modal', function() {
        resetAddModal();
    });
});
</script>
@endsection
