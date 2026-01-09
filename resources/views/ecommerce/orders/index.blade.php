@extends('layouts.master')

@section('title')
    @lang('translation.Orders')
@endsection

@section('css')
    <!-- DataTables -->
    <link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('build/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />

    <!-- Toastr -->
    <link rel="stylesheet" type="text/css" href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}">

    <style>
        .badge-style {
            font-size: 0.75rem;
            padding: 0.35rem 0.65rem;
        }
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 0.25rem;
        }
        .order-detail-label {
            font-weight: 600;
            color: #495057;
        }
        .order-detail-value {
            color: #212529;
        }
        .order-section-title {
            font-size: 1rem;
            font-weight: 600;
            border-bottom: 2px solid #556ee6;
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
        }
        .filter-section {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 0.25rem;
            margin-bottom: 1rem;
        }

        /* DataTables Responsive - Custom expand/collapse icons */
        table.dataTable.dtr-inline.collapsed > tbody > tr > td.dtr-control:before,
        table.dataTable.dtr-inline.collapsed > tbody > tr > th.dtr-control:before {
            content: '+';
            font-family: inherit;
            font-size: 14px;
            font-weight: bold;
            color: #556ee6;
            background-color: #e8ecf4;
            border: none;
            border-radius: 4px;
            box-shadow: none;
            width: 20px;
            height: 20px;
            line-height: 20px;
            text-align: center;
            transition: all 0.2s ease-in-out;
            transform: rotate(0deg);
        }

        table.dataTable.dtr-inline.collapsed > tbody > tr.parent > td.dtr-control:before,
        table.dataTable.dtr-inline.collapsed > tbody > tr.parent > th.dtr-control:before {
            content: '−';
            font-family: inherit;
            font-size: 14px;
            font-weight: bold;
            color: #fff;
            background-color: #556ee6;
            border: none;
            border-radius: 4px;
            box-shadow: none;
            transform: rotate(180deg);
        }

        table.dataTable.dtr-inline.collapsed > tbody > tr > td.dtr-control:hover:before,
        table.dataTable.dtr-inline.collapsed > tbody > tr > th.dtr-control:hover:before {
            background-color: #556ee6;
            color: #fff;
            transform: scale(1.1);
        }

        table.dataTable.dtr-inline.collapsed > tbody > tr.parent > td.dtr-control:hover:before,
        table.dataTable.dtr-inline.collapsed > tbody > tr.parent > th.dtr-control:hover:before {
            background-color: #3b5bdb;
            transform: rotate(180deg) scale(1.1);
        }

        /* Animate the child row expansion */
        table.dataTable > tbody > tr.child {
            animation: slideDown 0.2s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Ecommerce
        @endslot
        @slot('title')
            Orders
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <!-- Filter Section -->
                    <div class="filter-section">
                        <div class="row g-3">
                            <div class="col-md-2">
                                <label class="form-label text-dark fw-medium">Order Number</label>
                                <input type="text" class="form-control" id="filterOrderNumber" placeholder="Search order...">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-dark fw-medium">Customer Name</label>
                                <input type="text" class="form-control" id="filterCustomerName" placeholder="Search customer...">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-dark fw-medium">Order Status</label>
                                <select class="form-select" id="filterOrderStatus">
                                    <option value="">All Statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="paid">Paid</option>
                                    <option value="complete">Complete</option>
                                    <option value="cancelled">Cancelled</option>
                                    <option value="refunded">Refunded</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-dark fw-medium">Shipping Status</label>
                                <select class="form-select" id="filterShippingStatus">
                                    <option value="">All Shipping</option>
                                    <option value="pending">Pending</option>
                                    <option value="shipped">Shipped</option>
                                    <option value="not_applicable">Not Applicable</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-dark fw-medium">Date From</label>
                                <input type="date" class="form-control" id="filterDateFrom">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-dark fw-medium">Date To</label>
                                <input type="date" class="form-control" id="filterDateTo">
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-12 text-end">
                                <button type="button" class="btn btn-secondary" id="clearFilters">
                                    <i class="bx bx-reset me-1"></i>Clear Filters
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-sm-12">
                            <div class="text-sm-end">
                                <a href="{{ route('ecom-orders-custom-add') }}" class="btn btn-success btn-rounded waves-effect waves-light mb-2">
                                    <i class="mdi mdi-plus me-1"></i> Add New Order
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap dt-responsive nowrap w-100" id="orders-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Order Number</th>
                                    <th>Order Status</th>
                                    <th>Shipping Status</th>
                                    <th>Customer Name</th>
                                    <th>Subtotal</th>
                                    <th>Discount</th>
                                    <th>Shipping</th>
                                    <th>Grand Total</th>
                                    <th>Order Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- View Order Details Modal -->
    <div class="modal fade" id="viewOrderModal" tabindex="-1" aria-labelledby="viewOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="viewOrderModalLabel">
                        <i class="bx bx-receipt me-2"></i>Order Details - <span id="viewOrderNumber"></span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="viewOrderBody">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-secondary">Loading order details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bx bx-x me-1"></i>Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Status Modal -->
    <div class="modal fade" id="changeStatusModal" tabindex="-1" aria-labelledby="changeStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="changeStatusModalLabel">
                        <i class="bx bx-transfer me-2"></i>Change Order Status
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="statusOrderId">
                    <p class="mb-3 text-dark">Order: <strong id="statusOrderNumber"></strong></p>
                    <div class="mb-3">
                        <label for="newOrderStatus" class="form-label text-dark">New Status</label>
                        <select class="form-select" id="newOrderStatus">
                            <option value="pending">Pending</option>
                            <option value="paid">Paid</option>
                            <option value="complete">Complete</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="refunded">Refunded</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bx bx-x me-1"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-info" id="confirmChangeStatus">
                        <i class="bx bx-check me-1"></i>Update Status
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Shipping Modal -->
    <div class="modal fade" id="changeShippingModal" tabindex="-1" aria-labelledby="changeShippingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="changeShippingModalLabel">
                        <i class="bx bx-package me-2"></i>Change Shipping Status
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="shippingOrderId">
                    <p class="mb-3 text-dark">Order: <strong id="shippingOrderNumber"></strong></p>
                    <div class="mb-3">
                        <label for="newShippingStatus" class="form-label text-dark">Shipping Status</label>
                        <select class="form-select" id="newShippingStatus">
                            <option value="pending">Pending</option>
                            <option value="shipped">Shipped</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bx bx-x me-1"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-warning" id="confirmChangeShipping">
                        <i class="bx bx-check me-1"></i>Update Shipping
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Final Status Confirmation Modal -->
    <div class="modal fade" id="finalStatusConfirmModal" tabindex="-1" aria-labelledby="finalStatusConfirmModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="finalStatusConfirmModalLabel">
                        <i class="bx bx-error-circle me-2"></i>Final Status Confirmation
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="finalStatusOrderId">
                    <input type="hidden" id="finalStatusNewStatus">

                    <div class="alert alert-warning mb-3 d-flex align-items-start">
                        <i class="bx bx-error-circle fs-4 me-2 text-warning"></i>
                        <div class="text-dark">
                            <strong>Warning!</strong> You are about to change the order status to <strong id="finalStatusLabel"></strong>.
                        </div>
                    </div>

                    <div class="alert alert-danger mb-3 d-flex align-items-start">
                        <i class="bx bx-lock fs-4 me-2 text-danger"></i>
                        <div class="text-dark">
                            <strong>This action is irreversible!</strong><br>
                            Once the order is marked as <span id="finalStatusLabel2"></span>, the status <strong>cannot be changed</strong> anymore.
                        </div>
                    </div>

                    <p class="text-dark mb-2">Order: <strong id="finalStatusOrderNumber"></strong></p>

                    <hr>

                    <div class="mb-3">
                        <label class="form-label text-dark fw-medium">
                            To confirm, type <strong class="text-danger">CONFIRM</strong> in the box below:
                        </label>
                        <input type="text" class="form-control" id="confirmationInput" placeholder="Type CONFIRM here" autocomplete="off">
                        <div class="invalid-feedback" id="confirmationError">Please type CONFIRM exactly to proceed.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bx bx-x me-1"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmFinalStatus" disabled>
                        <i class="bx bx-check me-1"></i>Confirm Status Change
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Audit Trail Modal -->
    <div class="modal fade" id="auditTrailModal" tabindex="-1" aria-labelledby="auditTrailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title" id="auditTrailModalLabel">
                        <i class="bx bx-history me-2"></i>Audit Trail - <span id="auditOrderNumber"></span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="auditOrderId">
                    <!-- Date Range Filters -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="auditDateFrom" class="form-label text-dark fw-medium">Date From</label>
                            <input type="date" class="form-control" id="auditDateFrom">
                        </div>
                        <div class="col-md-4">
                            <label for="auditDateTo" class="form-label text-dark fw-medium">Date To</label>
                            <input type="date" class="form-control" id="auditDateTo">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="button" class="btn btn-primary me-2" id="applyAuditFilter">
                                <i class="bx bx-filter me-1"></i>Filter
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="clearAuditFilter">
                                <i class="bx bx-reset me-1"></i>Clear
                            </button>
                        </div>
                    </div>

                    <!-- Audit Logs Content -->
                    <div id="auditTrailBody">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2 text-secondary">Loading audit trail...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bx bx-x me-1"></i>Close
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <!-- Required datatable js -->
    <script src="{{ URL::asset('build/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>

    <!-- Responsive examples -->
    <script src="{{ URL::asset('build/libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js') }}"></script>

    <!-- Toastr -->
    <script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            // Toastr options
            toastr.options = {
                closeButton: true,
                progressBar: true,
                positionClass: "toast-top-right",
                timeOut: 3000
            };

            // Check for order success message from session storage
            const orderSuccess = sessionStorage.getItem('orderSuccess');
            if (orderSuccess) {
                toastr.success(orderSuccess, 'Order Created!');
                sessionStorage.removeItem('orderSuccess');
            }

            // Initialize DataTable
            var table = $('#orders-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('ecom-orders.data') }}",
                    type: "GET",
                    data: function(d) {
                        d.orderNumber = $('#filterOrderNumber').val();
                        d.customerName = $('#filterCustomerName').val();
                        d.orderStatus = $('#filterOrderStatus').val();
                        d.shippingStatus = $('#filterShippingStatus').val();
                        d.dateFrom = $('#filterDateFrom').val();
                        d.dateTo = $('#filterDateTo').val();
                    },
                    error: function(xhr, error, thrown) {
                        console.error('DataTables AJAX Error:', error);
                        console.error('Response:', xhr.responseText);
                        toastr.error('Failed to load orders: ' + error, 'Error');
                    }
                },
                columns: [
                    { data: 'orderNumber', name: 'orderNumber' },
                    { data: 'orderStatus', name: 'orderStatus' },
                    { data: 'shippingStatusBadge', name: 'shippingStatus' },
                    { data: 'customerFullName', name: 'customerFullName' },
                    { data: 'formatted_subtotal', name: 'subtotal' },
                    { data: 'formatted_discount', name: 'discountTotal' },
                    { data: 'formatted_shipping', name: 'shippingTotal' },
                    { data: 'formatted_grand_total', name: 'grandTotal' },
                    { data: 'formatted_date', name: 'created_at' },
                    {
                        data: 'id',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            // Check if order is in a final status
                            const finalStatuses = ['complete', 'cancelled', 'refunded'];
                            const isFinalStatus = finalStatuses.includes(row.orderStatusRaw);
                            const isComplete = row.orderStatusRaw === 'complete';
                            const isLockedFinal = ['cancelled', 'refunded'].includes(row.orderStatusRaw);
                            // Only show Shipping button if shippingStatus is not 'not_applicable' and not in final status
                            const showShippingBtn = row.shippingStatusRaw && row.shippingStatusRaw !== 'not_applicable' && !isFinalStatus;

                            // Status button logic: Complete orders can be refunded, others are locked or changeable
                            let statusBtn = '';
                            if (isLockedFinal) {
                                statusBtn = `<button type="button" class="btn btn-sm btn-secondary badge-style" disabled
                                        title="Status is locked (${row.orderStatusRaw})">
                                    <i class="bx bx-lock me-1"></i>Locked
                                </button>`;
                            } else if (isComplete) {
                                statusBtn = `<button type="button" class="btn btn-sm btn-outline-secondary badge-style change-status-btn"
                                        data-id="${row.id}"
                                        data-order-number="${row.orderNumber}"
                                        data-current-status="${row.orderStatusRaw}"
                                        title="Mark as Refunded">
                                    <i class="bx bx-revision me-1"></i>Refund
                                </button>`;
                            } else {
                                statusBtn = `<button type="button" class="btn btn-sm btn-outline-info badge-style change-status-btn"
                                        data-id="${row.id}"
                                        data-order-number="${row.orderNumber}"
                                        data-current-status="${row.orderStatusRaw}"
                                        title="Change Status">
                                    <i class="bx bx-transfer me-1"></i>Status
                                </button>`;
                            }

                            return `
                                <div class="action-buttons">
                                    <button type="button" class="btn btn-sm btn-outline-primary badge-style view-order-btn"
                                            data-id="${row.id}"
                                            data-order-number="${row.orderNumber}"
                                            title="View Details">
                                        <i class="bx bx-show me-1"></i>View
                                    </button>
                                    ${statusBtn}
                                    ${showShippingBtn ? `
                                    <button type="button" class="btn btn-sm btn-outline-warning badge-style change-shipping-btn"
                                            data-id="${row.id}"
                                            data-order-number="${row.orderNumber}"
                                            data-current-shipping="${row.shippingStatusRaw}"
                                            title="Change Shipping">
                                        <i class="bx bx-package me-1"></i>Shipping
                                    </button>
                                    ` : ''}
                                    <button type="button" class="btn btn-sm btn-outline-secondary badge-style audit-trail-btn"
                                            data-id="${row.id}"
                                            data-order-number="${row.orderNumber}"
                                            title="Audit Trail">
                                        <i class="bx bx-history me-1"></i>Audit
                                    </button>
                                </div>
                            `;
                        }
                    }
                ],
                order: [[8, 'desc']], // Order by date descending
                pageLength: 25,
                lengthMenu: [[25, 50, 100, 200, -1], [25, 50, 100, 200, "All"]],
                responsive: true,
                language: {
                    emptyTable: "No orders found",
                    zeroRecords: "No matching orders found",
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                },
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                     '<"row"<"col-sm-12"tr>>' +
                     '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                initComplete: function(settings, json) {
                    console.log('Orders table initialized. Total records:', json.recordsTotal);
                }
            });

            // Filter event handlers
            $('#filterOrderNumber, #filterCustomerName').on('keyup', function() {
                table.draw();
            });

            $('#filterOrderStatus, #filterShippingStatus').on('change', function() {
                table.draw();
            });

            // Date filter handlers
            $('#filterDateFrom, #filterDateTo').on('change', function() {
                table.draw();
            });

            // Clear filters
            $('#clearFilters').on('click', function() {
                $('#filterOrderNumber').val('');
                $('#filterCustomerName').val('');
                $('#filterOrderStatus').val('');
                $('#filterShippingStatus').val('');
                $('#filterDateFrom').val('');
                $('#filterDateTo').val('');
                table.draw();
            });

            // Use event delegation for dynamically created buttons
            // View Order Details
            $(document).on('click', '.view-order-btn', function(e) {
                e.preventDefault();
                const orderId = $(this).data('id');
                const orderNumber = $(this).data('order-number');
                console.log('View clicked:', orderId, orderNumber);
                viewOrderDetails(orderId, orderNumber);
            });

            // Change Status
            $(document).on('click', '.change-status-btn', function(e) {
                e.preventDefault();
                const orderId = $(this).data('id');
                const orderNumber = $(this).data('order-number');
                const currentStatus = $(this).data('current-status');
                console.log('Status clicked:', orderId, orderNumber, currentStatus);
                openChangeStatusModal(orderId, orderNumber, currentStatus);
            });

            // Change Shipping
            $(document).on('click', '.change-shipping-btn', function(e) {
                e.preventDefault();
                const orderId = $(this).data('id');
                const orderNumber = $(this).data('order-number');
                const currentShipping = $(this).data('current-shipping');
                console.log('Shipping clicked:', orderId, orderNumber, currentShipping);
                openChangeShippingModal(orderId, orderNumber, currentShipping);
            });

            // View Order Details function
            function viewOrderDetails(orderId, orderNumber) {
                $('#viewOrderNumber').text(orderNumber);
                $('#viewOrderBody').html(`
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-secondary">Loading order details...</p>
                    </div>
                `);
                $('#viewOrderModal').modal('show');

                $.ajax({
                    url: '/ecom-orders/' + orderId + '/details',
                    type: 'GET',
                    success: function(response) {
                        console.log('Order details response:', response);
                        if (response.success) {
                            renderOrderDetails(response.order);
                        } else {
                            $('#viewOrderBody').html('<div class="alert alert-danger">Failed to load order details.</div>');
                        }
                    },
                    error: function(xhr) {
                        console.error('Error loading order details:', xhr);
                        $('#viewOrderBody').html('<div class="alert alert-danger">Error loading order details: ' + (xhr.responseJSON?.message || 'Unknown error') + '</div>');
                    }
                });
            }

            // Render order details HTML
            function renderOrderDetails(order) {
                // Check if there are any ship-type products
                const hasShipProducts = order.items && order.items.some(item => item.productType === 'ship');
                const hasAccessProducts = order.items && order.items.some(item => item.productType === 'access');
                const isPackagePurchase = order.isPackage === true || order.isPackage === 1;

                // Build items table with product type badges
                let itemsHtml = '';
                if (order.items && order.items.length > 0) {
                    order.items.forEach(function(item) {
                        const typeBadge = item.productType === 'ship'
                            ? '<span class="badge bg-primary ms-2">Ship</span>'
                            : '<span class="badge bg-info text-white ms-2">Access</span>';

                        if (isPackagePurchase) {
                            // Package purchase - hide price columns
                            itemsHtml += `
                                <tr>
                                    <td class="text-dark">
                                        <strong>${escapeHtml(item.productName)}</strong>${typeBadge}<br>
                                        <small class="text-secondary">${escapeHtml(item.variantName || '')}</small>
                                        ${item.variantSku ? `<br><small class="text-secondary">SKU: ${escapeHtml(item.variantSku)}</small>` : ''}
                                    </td>
                                    <td class="text-dark">${escapeHtml(item.productStore || 'N/A')}</td>
                                    <td class="text-center text-dark">${item.quantity}</td>
                                </tr>
                            `;
                        } else {
                            // Regular purchase - show all columns
                            itemsHtml += `
                                <tr>
                                    <td class="text-dark">
                                        <strong>${escapeHtml(item.productName)}</strong>${typeBadge}<br>
                                        <small class="text-secondary">${escapeHtml(item.variantName || '')}</small>
                                        ${item.variantSku ? `<br><small class="text-secondary">SKU: ${escapeHtml(item.variantSku)}</small>` : ''}
                                    </td>
                                    <td class="text-dark">${escapeHtml(item.productStore || 'N/A')}</td>
                                    <td class="text-center text-dark">${item.quantity}</td>
                                    <td class="text-end text-dark">₱${parseFloat(item.unitPrice).toFixed(2)}</td>
                                    <td class="text-end text-dark">₱${parseFloat(item.subtotal).toFixed(2)}</td>
                                </tr>
                            `;
                        }
                    });
                } else {
                    itemsHtml = isPackagePurchase
                        ? '<tr><td colspan="3" class="text-center text-secondary">No items</td></tr>'
                        : '<tr><td colspan="5" class="text-center text-secondary">No items</td></tr>';
                }

                // Build access clients section for access-type products
                let accessClientsHtml = '';
                if (hasAccessProducts && order.items) {
                    const accessItems = order.items.filter(item => item.productType === 'access');
                    accessItems.forEach(function(item) {
                        const hasAccessClient = item.accessClientName || item.accessClientPhone || item.accessClientEmail;
                        accessClientsHtml += `
                            <tr>
                                <td class="text-dark">
                                    <strong>${escapeHtml(item.productName)}</strong><br>
                                    <small class="text-secondary">${escapeHtml(item.variantName || '')}</small>
                                </td>
                                <td class="text-dark">${escapeHtml(item.productStore || 'N/A')}</td>
                                <td class="text-dark">${hasAccessClient ? escapeHtml(item.accessClientName || 'N/A') : '<span class="text-secondary">Same as buyer</span>'}</td>
                                <td class="text-dark">${item.accessClientPhone ? '<a href="tel:' + item.accessClientPhone + '">' + item.accessClientPhone + '</a>' : (hasAccessClient ? 'N/A' : '-')}</td>
                                <td class="text-dark">${item.accessClientEmail ? '<a href="mailto:' + item.accessClientEmail + '">' + item.accessClientEmail + '</a>' : (hasAccessClient ? 'N/A' : '-')}</td>
                            </tr>
                        `;
                    });
                }

                // Build discounts table (only if there are discounts)
                let discountsHtml = '';
                const hasDiscounts = order.discounts && order.discounts.length > 0;
                if (hasDiscounts) {
                    order.discounts.forEach(function(discount) {
                        const typeLabel = discount.discountType === 'percentage' ? 'Percentage' : 'Fixed Amount';
                        const autoAppliedBadge = discount.isAutoApplied
                            ? '<span class="badge bg-success"><i class="bx bx-check-circle me-1"></i>Auto Apply</span>'
                            : '<span class="badge bg-primary"><i class="bx bx-code me-1"></i>Discount Code</span>';
                        discountsHtml += `
                            <tr>
                                <td class="text-dark">${escapeHtml(discount.discountName)}</td>
                                <td class="text-dark">${discount.discountCode || '-'}</td>
                                <td class="text-dark">${typeLabel}</td>
                                <td class="text-dark">${discount.discountType === 'percentage' ? discount.discountValue + '%' : '₱' + parseFloat(discount.discountValue).toFixed(2)}</td>
                                <td class="text-center">${autoAppliedBadge}</td>
                                <td class="text-end text-danger">-₱${parseFloat(discount.calculatedAmount).toFixed(2)}</td>
                            </tr>
                        `;
                    });
                }

                // Build affiliate commissions table (only if there are commissions)
                let commissionsHtml = '';
                const hasCommissions = order.affiliate_commissions && order.affiliate_commissions.length > 0;
                if (hasCommissions) {
                    order.affiliate_commissions.forEach(function(comm) {
                        commissionsHtml += `
                            <tr>
                                <td class="text-dark">
                                    ${escapeHtml(comm.affiliateName)}
                                    ${comm.affiliatePhone ? '<br><small class="text-secondary">' + comm.affiliatePhone + '</small>' : ''}
                                </td>
                                <td class="text-dark">${escapeHtml(comm.storeName || 'N/A')}</td>
                                <td class="text-dark">${parseFloat(comm.commissionPercentage).toFixed(2)}%</td>
                                <td class="text-dark">₱${parseFloat(comm.baseAmount || 0).toFixed(2)}</td>
                                <td class="text-end text-dark">₱${parseFloat(comm.commissionAmount).toFixed(2)}</td>
                            </tr>
                        `;
                    });
                }

                // Build shipping section HTML (only if there are ship products)
                let shippingSectionHtml = '';
                if (hasShipProducts) {
                    const recipientName = [order.shippingFirstName, order.shippingMiddleName, order.shippingLastName].filter(Boolean).join(' ') || 'N/A';
                    const shippingAddress = [order.shippingHouseNumber, order.shippingStreet, order.shippingZone ? 'Zone ' + order.shippingZone : '', order.shippingMunicipality, order.shippingProvince, order.shippingZipCode].filter(Boolean).join(', ') || 'N/A';

                    shippingSectionHtml = `
                        <!-- Shipping Info (Step 4) -->
                        <div class="col-md-6 mb-4">
                            <h6 class="order-section-title"><i class="bx bx-package me-2"></i>Shipping Information</h6>
                            <div class="row mb-2">
                                <div class="col-5 order-detail-label">Shipping Method:</div>
                                <div class="col-7 order-detail-value">${order.shippingName ? '<span class="badge bg-info text-white">' + escapeHtml(order.shippingName) + '</span>' : 'N/A'}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 order-detail-label">Shipping Type:</div>
                                <div class="col-7 order-detail-value">${order.shippingType || 'N/A'}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 order-detail-label">Recipient Name:</div>
                                <div class="col-7 order-detail-value">${escapeHtml(recipientName)}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 order-detail-label">Recipient Phone:</div>
                                <div class="col-7 order-detail-value">${order.shippingPhone ? '<a href="tel:' + order.shippingPhone + '">' + order.shippingPhone + '</a>' : 'N/A'}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 order-detail-label">Recipient Email:</div>
                                <div class="col-7 order-detail-value">${order.shippingEmail ? '<a href="mailto:' + order.shippingEmail + '">' + order.shippingEmail + '</a>' : 'N/A'}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 order-detail-label">Delivery Address:</div>
                                <div class="col-7 order-detail-value">${escapeHtml(shippingAddress)}</div>
                            </div>
                        </div>
                    `;
                }

                // Build access clients section HTML (only if there are access products)
                let accessClientsSectionHtml = '';
                if (hasAccessProducts) {
                    accessClientsSectionHtml = `
                        <!-- Access Clients (Step 3) -->
                        <div class="mb-4">
                            <h6 class="order-section-title"><i class="bx bx-key me-2"></i>Access Recipients</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-dark">Product</th>
                                            <th class="text-dark">Store</th>
                                            <th class="text-dark">Access Client</th>
                                            <th class="text-dark">Phone</th>
                                            <th class="text-dark">Email</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${accessClientsHtml}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    `;
                }

                const html = `
                    <div class="row">
                        <!-- Order Info -->
                        <div class="col-md-6 mb-4">
                            <h6 class="order-section-title"><i class="bx bx-info-circle me-2"></i>Order Information</h6>
                            <div class="row mb-2">
                                <div class="col-5 order-detail-label">Order Number:</div>
                                <div class="col-7 order-detail-value"><strong>${order.orderNumber}</strong></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 order-detail-label">Order Status:</div>
                                <div class="col-7 order-detail-value">${getStatusBadge(order.orderStatus)}</div>
                            </div>
                            ${hasShipProducts ? `
                            <div class="row mb-2">
                                <div class="col-5 order-detail-label">Shipping Status:</div>
                                <div class="col-7 order-detail-value">${getShippingBadge(order.shippingStatus)}</div>
                            </div>
                            ` : ''}
                            <div class="row mb-2">
                                <div class="col-5 order-detail-label">Date Created:</div>
                                <div class="col-7 order-detail-value">${order.created_at}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 order-detail-label">Handled By:</div>
                                <div class="col-7 order-detail-value">${order.user?.name || 'N/A'}</div>
                            </div>
                        </div>

                        <!-- Client/Buyer Info (Step 2) -->
                        <div class="col-md-6 mb-4">
                            <h6 class="order-section-title"><i class="bx bx-user me-2"></i>Buyer Information</h6>
                            <div class="row mb-2">
                                <div class="col-5 order-detail-label">Name:</div>
                                <div class="col-7 order-detail-value">${getClientName(order)}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 order-detail-label">Phone:</div>
                                <div class="col-7 order-detail-value">${order.clientPhone ? '<a href="tel:' + order.clientPhone + '">' + order.clientPhone + '</a>' : 'N/A'}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 order-detail-label">Email:</div>
                                <div class="col-7 order-detail-value">${order.clientEmail ? '<a href="mailto:' + order.clientEmail + '">' + order.clientEmail + '</a>' : 'N/A'}</div>
                            </div>
                        </div>

                        ${shippingSectionHtml}
                    </div>

                    ${isPackagePurchase ? `
                    <!-- Package Banner -->
                    <div class="mb-4">
                        <div class="alert alert-primary border-primary" role="alert">
                            <div class="d-flex align-items-start">
                                <div class="me-3">
                                    <i class="bx bx-package" style="font-size: 2.5rem;"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="alert-heading mb-1">
                                        <i class="bx bx-check-circle me-1"></i>Package Purchase
                                    </h5>
                                    <h6 class="mb-2 fw-bold">${escapeHtml(order.packageName || 'Package')}</h6>
                                    ${order.packageDescription ? `<p class="mb-2 small">${escapeHtml(order.packageDescription)}</p>` : ''}
                                    <div class="d-flex flex-wrap gap-3 mt-2">
                                        <div>
                                            <span class="text-muted small">Package Price:</span>
                                            <span class="fw-bold text-primary">₱${parseFloat(order.packagePrice || 0).toFixed(2)}</span>
                                        </div>
                                        ${order.packageSavings && parseFloat(order.packageSavings) > 0 ? `
                                        <div>
                                            <span class="text-muted small">You Save:</span>
                                            <span class="fw-bold text-success">₱${parseFloat(order.packageSavings).toFixed(2)}</span>
                                        </div>
                                        ` : ''}
                                        ${order.packageCalculatedPrice ? `
                                        <div>
                                            <span class="text-muted small">Original Value:</span>
                                            <span class="text-secondary text-decoration-line-through">₱${parseFloat(order.packageCalculatedPrice).toFixed(2)}</span>
                                        </div>
                                        ` : ''}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    ` : ''}

                    <!-- Order Items (Step 1) -->
                    <div class="mb-4">
                        <h6 class="order-section-title"><i class="bx bx-cart me-2"></i>${isPackagePurchase ? 'Package Contents' : 'Order Items'}</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="table-light">
                                    ${isPackagePurchase ? `
                                    <tr>
                                        <th class="text-dark">Product</th>
                                        <th class="text-dark">Store</th>
                                        <th class="text-center text-dark">Qty</th>
                                    </tr>
                                    ` : `
                                    <tr>
                                        <th class="text-dark">Product</th>
                                        <th class="text-dark">Store</th>
                                        <th class="text-center text-dark">Qty</th>
                                        <th class="text-end text-dark">Unit Price</th>
                                        <th class="text-end text-dark">Subtotal</th>
                                    </tr>
                                    `}
                                </thead>
                                <tbody>
                                    ${itemsHtml}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    ${accessClientsSectionHtml}

                    ${hasDiscounts ? `
                    <!-- Discounts (Step 5) -->
                    <div class="mb-4">
                        <h6 class="order-section-title"><i class="bx bx-purchase-tag me-2"></i>Discounts Applied</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-dark">Discount Name</th>
                                        <th class="text-dark">Code</th>
                                        <th class="text-dark">Type</th>
                                        <th class="text-dark">Value</th>
                                        <th class="text-center text-dark">Trigger</th>
                                        <th class="text-end text-dark">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${discountsHtml}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    ` : ''}

                    ${hasCommissions ? `
                    <!-- Affiliate Commissions (Step 6) -->
                    <div class="mb-4">
                        <h6 class="order-section-title"><i class="bx bx-group me-2"></i>Affiliate Commissions</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-dark">Affiliate</th>
                                        <th class="text-dark">Store</th>
                                        <th class="text-dark">Rate</th>
                                        <th class="text-dark">Base Amount</th>
                                        <th class="text-end text-dark">Commission</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${commissionsHtml}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    ` : ''}

                    ${order.orderNotes ? `
                        <div class="mb-4">
                            <h6 class="order-section-title"><i class="bx bx-note me-2"></i>Order Notes</h6>
                            <div class="p-3 bg-light rounded">
                                <p class="text-dark mb-0">${escapeHtml(order.orderNotes)}</p>
                            </div>
                        </div>
                    ` : ''}

                    <!-- Order Summary (at the bottom) -->
                    <div class="mb-4">
                        <h6 class="order-section-title"><i class="bx bx-calculator me-2"></i>Order Summary</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded">
                                    ${isPackagePurchase ? `
                                    <!-- Package Purchase Summary -->
                                    <div class="row mb-2">
                                        <div class="col-6 order-detail-label">Package Price:</div>
                                        <div class="col-6 text-end order-detail-value">₱${parseFloat(order.packagePrice || 0).toFixed(2)}</div>
                                    </div>
                                    ${order.packageSavings && parseFloat(order.packageSavings) > 0 ? `
                                    <div class="row mb-2">
                                        <div class="col-6 order-detail-label">Package Savings:</div>
                                        <div class="col-6 text-end text-success">₱${parseFloat(order.packageSavings).toFixed(2)}</div>
                                    </div>
                                    ` : ''}
                                    ${hasShipProducts ? `
                                    <div class="row mb-2">
                                        <div class="col-6 order-detail-label">Shipping Fee:</div>
                                        <div class="col-6 text-end order-detail-value">₱${parseFloat(order.shippingTotal || 0).toFixed(2)}</div>
                                    </div>
                                    ` : ''}
                                    ${parseFloat(order.discountTotal || 0) > 0 ? `
                                    <div class="row mb-2">
                                        <div class="col-6 order-detail-label">Discount:</div>
                                        <div class="col-6 text-end text-danger">-₱${parseFloat(order.discountTotal || 0).toFixed(2)}</div>
                                    </div>
                                    ` : ''}
                                    ` : `
                                    <!-- Regular Purchase Summary -->
                                    <div class="row mb-2">
                                        <div class="col-6 order-detail-label">Subtotal:</div>
                                        <div class="col-6 text-end order-detail-value">₱${parseFloat(order.subtotal || 0).toFixed(2)}</div>
                                    </div>
                                    ${hasShipProducts ? `
                                    <div class="row mb-2">
                                        <div class="col-6 order-detail-label">Shipping Fee:</div>
                                        <div class="col-6 text-end order-detail-value">₱${parseFloat(order.shippingTotal || 0).toFixed(2)}</div>
                                    </div>
                                    ` : ''}
                                    <div class="row mb-2">
                                        <div class="col-6 order-detail-label">Discount:</div>
                                        <div class="col-6 text-end text-danger">-₱${parseFloat(order.discountTotal || 0).toFixed(2)}</div>
                                    </div>
                                    `}
                                    <hr class="my-2">
                                    <div class="row">
                                        <div class="col-6"><strong class="text-dark">Grand Total:</strong></div>
                                        <div class="col-6 text-end"><strong class="text-primary fs-5">₱${parseFloat(order.grandTotal || 0).toFixed(2)}</strong></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded">
                                    <div class="row mb-2">
                                        <div class="col-6 order-detail-label">Affiliate Commission:</div>
                                        <div class="col-6 text-end text-warning">₱${parseFloat(order.affiliateCommissionTotal || 0).toFixed(2)}</div>
                                    </div>
                                    <hr class="my-2">
                                    <div class="row">
                                        <div class="col-6"><strong class="text-dark">Net Revenue:</strong></div>
                                        <div class="col-6 text-end"><strong class="text-success fs-5">₱${parseFloat(order.netRevenue || 0).toFixed(2)}</strong></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                $('#viewOrderBody').html(html);
            }

            // Get status badge HTML
            function getStatusBadge(status) {
                const badges = {
                    'pending': '<span class="badge bg-warning text-dark">Pending</span>',
                    'paid': '<span class="badge bg-info text-white">Paid</span>',
                    'complete': '<span class="badge bg-success">Complete</span>',
                    'cancelled': '<span class="badge bg-danger">Cancelled</span>',
                    'refunded': '<span class="badge bg-secondary">Refunded</span>'
                };
                return badges[status] || '<span class="badge bg-secondary">' + (status || 'Unknown') + '</span>';
            }

            // Get shipping badge HTML
            function getShippingBadge(status) {
                const badges = {
                    'pending': '<span class="badge bg-warning text-dark">Pending</span>',
                    'shipped': '<span class="badge bg-success">Shipped</span>',
                    'not_applicable': '<span class="badge bg-secondary">Not Applicable</span>'
                };
                return badges[status] || '<span class="badge bg-secondary">' + (status || 'Pending') + '</span>';
            }

            // Get client/buyer name - shows name or falls back to email/phone for identification
            function getClientName(order) {
                const nameParts = [order.clientFirstName, order.clientMiddleName, order.clientLastName].filter(Boolean);
                if (nameParts.length > 0) {
                    return escapeHtml(nameParts.join(' '));
                }
                // If no name stored, show email or phone for identification
                if (order.clientEmail) {
                    return '<span class="text-secondary">' + escapeHtml(order.clientEmail) + '</span>';
                }
                if (order.clientPhone) {
                    return '<span class="text-secondary">' + escapeHtml(order.clientPhone) + '</span>';
                }
                return 'N/A';
            }

            // Open Change Status Modal
            function openChangeStatusModal(orderId, orderNumber, currentStatus) {
                $('#statusOrderId').val(orderId);
                $('#statusOrderNumber').text(orderNumber);

                // Adjust dropdown options based on current status
                const $dropdown = $('#newOrderStatus');
                $dropdown.empty();

                if (currentStatus === 'complete') {
                    // Complete orders can only be changed to Refunded
                    $dropdown.append('<option value="complete" disabled>Complete (Current)</option>');
                    $dropdown.append('<option value="refunded" selected>Refunded</option>');
                    $('#changeStatusModalLabel').html('<i class="bx bx-revision me-2"></i>Mark Order as Refunded');
                    $('.modal-header', '#changeStatusModal').removeClass('bg-info').addClass('bg-secondary');
                } else {
                    // Normal status options
                    $dropdown.append('<option value="pending">Pending</option>');
                    $dropdown.append('<option value="paid">Paid</option>');
                    $dropdown.append('<option value="complete">Complete</option>');
                    $dropdown.append('<option value="cancelled">Cancelled</option>');
                    $dropdown.append('<option value="refunded">Refunded</option>');
                    $dropdown.val(currentStatus || 'pending');
                    $('#changeStatusModalLabel').html('<i class="bx bx-transfer me-2"></i>Change Order Status');
                    $('.modal-header', '#changeStatusModal').removeClass('bg-secondary').addClass('bg-info');
                }

                $('#changeStatusModal').modal('show');
            }

            // Open Change Shipping Modal
            function openChangeShippingModal(orderId, orderNumber, currentShipping) {
                $('#shippingOrderId').val(orderId);
                $('#shippingOrderNumber').text(orderNumber);
                $('#newShippingStatus').val(currentShipping || 'pending');
                $('#changeShippingModal').modal('show');
            }

            // Define final statuses
            const finalStatuses = ['complete', 'cancelled', 'refunded'];

            // Confirm Change Status
            $('#confirmChangeStatus').on('click', function() {
                const orderId = $('#statusOrderId').val();
                const newStatus = $('#newOrderStatus').val();
                const orderNumber = $('#statusOrderNumber').text();

                // Check if changing to a final status - show confirmation modal
                if (finalStatuses.includes(newStatus)) {
                    const statusLabel = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                    $('#finalStatusOrderId').val(orderId);
                    $('#finalStatusNewStatus').val(newStatus);
                    $('#finalStatusOrderNumber').text(orderNumber);
                    $('#finalStatusLabel').text(statusLabel);
                    $('#finalStatusLabel2').text(statusLabel);
                    $('#confirmationInput').val('').removeClass('is-invalid');
                    $('#confirmFinalStatus').prop('disabled', true);
                    $('#changeStatusModal').modal('hide');
                    $('#finalStatusConfirmModal').modal('show');
                    return;
                }

                // Non-final status - proceed directly
                updateOrderStatus(orderId, newStatus, null);
            });

            // Enable/disable confirm button based on input
            $('#confirmationInput').on('input', function() {
                const value = $(this).val().trim();
                const isValid = value === 'CONFIRM';
                $('#confirmFinalStatus').prop('disabled', !isValid);
                if (value.length > 0 && !isValid) {
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });

            // Clear confirmation input when modal is closed
            $('#finalStatusConfirmModal').on('hidden.bs.modal', function() {
                $('#confirmationInput').val('').removeClass('is-invalid');
                $('#confirmFinalStatus').prop('disabled', true);
            });

            // Confirm Final Status Change
            $('#confirmFinalStatus').on('click', function() {
                const confirmValue = $('#confirmationInput').val().trim();
                if (confirmValue !== 'CONFIRM') {
                    $('#confirmationInput').addClass('is-invalid');
                    return;
                }

                const orderId = $('#finalStatusOrderId').val();
                const newStatus = $('#finalStatusNewStatus').val();
                updateOrderStatus(orderId, newStatus, 'CONFIRM');
            });

            // Actual status update function
            function updateOrderStatus(orderId, newStatus, confirmationToken) {
                const $btn = confirmationToken ? $('#confirmFinalStatus') : $('#confirmChangeStatus');
                const originalText = $btn.html();

                $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Updating...');

                const data = {
                    _token: '{{ csrf_token() }}',
                    status: newStatus
                };
                if (confirmationToken) {
                    data.confirmationToken = confirmationToken;
                }

                $.ajax({
                    url: '/ecom-orders/' + orderId + '/status',
                    type: 'PUT',
                    data: data,
                    success: function(response) {
                        if (response.success) {
                            toastr.success('Order status updated successfully!', 'Success');
                            $('#changeStatusModal').modal('hide');
                            $('#finalStatusConfirmModal').modal('hide');
                            table.ajax.reload(null, false);
                        } else {
                            toastr.error(response.message || 'Failed to update status', 'Error');
                        }
                    },
                    error: function(xhr) {
                        const response = xhr.responseJSON || {};
                        if (response.isFinal) {
                            toastr.warning(response.message, 'Status Locked');
                            $('#changeStatusModal').modal('hide');
                            $('#finalStatusConfirmModal').modal('hide');
                            table.ajax.reload(null, false);
                        } else {
                            toastr.error(response.message || 'Error updating status', 'Error');
                        }
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html(originalText);
                    }
                });
            }

            // Confirm Change Shipping
            $('#confirmChangeShipping').on('click', function() {
                const orderId = $('#shippingOrderId').val();
                const shippingStatus = $('#newShippingStatus').val();
                const $btn = $(this);
                const originalText = $btn.html();

                $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Updating...');

                $.ajax({
                    url: '/ecom-orders/' + orderId + '/shipping',
                    type: 'PUT',
                    data: {
                        _token: '{{ csrf_token() }}',
                        shippingStatus: shippingStatus
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success('Shipping status updated successfully!', 'Success');
                            $('#changeShippingModal').modal('hide');
                            table.ajax.reload(null, false);
                        } else {
                            toastr.error(response.message || 'Failed to update shipping', 'Error');
                        }
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON?.message || 'Error updating shipping', 'Error');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html(originalText);
                    }
                });
            });

            // Escape HTML helper
            function escapeHtml(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            // Audit Trail Button Click
            $(document).on('click', '.audit-trail-btn', function(e) {
                e.preventDefault();
                const orderId = $(this).data('id');
                const orderNumber = $(this).data('order-number');
                console.log('Audit trail clicked:', orderId, orderNumber);
                openAuditTrailModal(orderId, orderNumber);
            });

            // Open Audit Trail Modal
            function openAuditTrailModal(orderId, orderNumber) {
                $('#auditOrderId').val(orderId);
                $('#auditOrderNumber').text(orderNumber);
                $('#auditDateFrom').val('');
                $('#auditDateTo').val('');
                $('#auditTrailBody').html(`
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-secondary">Loading audit trail...</p>
                    </div>
                `);
                $('#auditTrailModal').modal('show');
                loadAuditLogs(orderId);
            }

            // Load Audit Logs
            function loadAuditLogs(orderId, dateFrom = '', dateTo = '') {
                let url = '/ecom-orders/' + orderId + '/audit-logs';
                let params = [];
                if (dateFrom) params.push('dateFrom=' + dateFrom);
                if (dateTo) params.push('dateTo=' + dateTo);
                if (params.length > 0) url += '?' + params.join('&');

                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function(response) {
                        console.log('Audit logs response:', response);
                        if (response.success) {
                            renderAuditLogs(response.logs);
                        } else {
                            $('#auditTrailBody').html('<div class="alert alert-danger">Failed to load audit logs.</div>');
                        }
                    },
                    error: function(xhr) {
                        console.error('Error loading audit logs:', xhr);
                        $('#auditTrailBody').html('<div class="alert alert-danger">Error loading audit logs: ' + (xhr.responseJSON?.message || 'Unknown error') + '</div>');
                    }
                });
            }

            // Render Audit Logs
            function renderAuditLogs(logs) {
                if (!logs || logs.length === 0) {
                    $('#auditTrailBody').html(`
                        <div class="text-center py-4">
                            <i class="bx bx-history text-secondary" style="font-size: 3rem;"></i>
                            <p class="mt-2 text-dark">No audit logs found for this order.</p>
                            <small class="text-secondary">Changes to this order will appear here.</small>
                        </div>
                    `);
                    return;
                }

                let html = `
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-dark" style="width: 180px;">Date/Time</th>
                                    <th class="text-dark" style="width: 150px;">Action</th>
                                    <th class="text-dark">Change Details</th>
                                    <th class="text-dark" style="width: 120px;">User</th>
                                    <th class="text-dark" style="width: 120px;">IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                logs.forEach(function(log) {
                    const actionBadge = getActionBadge(log.actionType);
                    const changeDetails = formatChangeDetails(log);

                    html += `
                        <tr>
                            <td class="text-dark"><small>${escapeHtml(log.createdAt)}</small></td>
                            <td>${actionBadge}</td>
                            <td class="text-dark">${changeDetails}</td>
                            <td class="text-dark"><small>${escapeHtml(log.userName)}</small></td>
                            <td class="text-secondary"><small>${escapeHtml(log.ipAddress || 'N/A')}</small></td>
                        </tr>
                    `;
                });

                html += `
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2 text-end">
                        <small class="text-secondary">Showing ${logs.length} audit log(s)</small>
                    </div>
                `;

                $('#auditTrailBody').html(html);
            }

            // Get action badge based on action type
            function getActionBadge(actionType) {
                const badges = {
                    'order_created': '<span class="badge bg-success"><i class="bx bx-plus-circle me-1"></i>Created</span>',
                    'status_change': '<span class="badge bg-info text-white"><i class="bx bx-transfer me-1"></i>Status</span>',
                    'shipping_change': '<span class="badge bg-warning text-dark"><i class="bx bx-package me-1"></i>Shipping</span>',
                    'order_cancelled': '<span class="badge bg-danger"><i class="bx bx-x-circle me-1"></i>Cancelled</span>',
                    'order_refunded': '<span class="badge bg-secondary"><i class="bx bx-undo me-1"></i>Refunded</span>',
                    'tracking_updated': '<span class="badge bg-primary"><i class="bx bx-location-plus me-1"></i>Tracking</span>',
                    'notes_updated': '<span class="badge bg-dark"><i class="bx bx-note me-1"></i>Notes</span>'
                };
                return badges[actionType] || '<span class="badge bg-secondary">' + escapeHtml(actionType) + '</span>';
            }

            // Format change details
            function formatChangeDetails(log) {
                if (log.description) {
                    return escapeHtml(log.description);
                }

                if (log.fieldChanged && (log.previousValue || log.newValue)) {
                    return `<strong>${escapeHtml(log.fieldChanged)}</strong>: ${escapeHtml(log.formattedPreviousValue || '-')} → ${escapeHtml(log.formattedNewValue || '-')}`;
                }

                return log.actionTypeLabel || escapeHtml(log.actionType);
            }

            // Apply Audit Filter
            $('#applyAuditFilter').on('click', function() {
                const orderId = $('#auditOrderId').val();
                const dateFrom = $('#auditDateFrom').val();
                const dateTo = $('#auditDateTo').val();

                $('#auditTrailBody').html(`
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-secondary">Loading audit trail...</p>
                    </div>
                `);

                loadAuditLogs(orderId, dateFrom, dateTo);
            });

            // Clear Audit Filter
            $('#clearAuditFilter').on('click', function() {
                $('#auditDateFrom').val('');
                $('#auditDateTo').val('');
                const orderId = $('#auditOrderId').val();
                loadAuditLogs(orderId);
            });

            // Handle DataTable errors
            $.fn.dataTable.ext.errMode = 'throw';
        });
    </script>
@endsection
