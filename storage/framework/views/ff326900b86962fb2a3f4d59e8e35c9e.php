<?php $__env->startSection('title'); ?>
    <?php echo app('translator')->get('translation.Orders'); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('css'); ?>
    <!-- DataTables -->
    <link href="<?php echo e(URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css')); ?>" rel="stylesheet" type="text/css" />

    <!-- Responsive datatable examples -->
    <link href="<?php echo e(URL::asset('build/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css')); ?>" rel="stylesheet" type="text/css" />
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php $__env->startComponent('components.breadcrumb'); ?>
        <?php $__env->slot('li_1'); ?>
            Ecommerce
        <?php $__env->endSlot(); ?>
        <?php $__env->slot('title'); ?>
            Orders
        <?php $__env->endSlot(); ?>
    <?php echo $__env->renderComponent(); ?>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-sm-4">
                            <div class="search-box me-2 mb-2 d-inline-block">
                                <div class="position-relative">
                                    <input type="text" class="form-control" autocomplete="off" id="searchTableList" placeholder="Search...">
                                    <i class="bx bx-search-alt search-icon"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-8">
                            <div class="text-sm-end">
                                <a href="<?php echo e(route('ecom-orders-custom-add')); ?>" class="btn btn-success btn-rounded waves-effect waves-light mb-2 me-2">
                                    <i class="mdi mdi-plus me-1"></i> Add New Order
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <!-- Loading overlay -->
                        <div id="table-loading" class="text-center py-4" style="display: none; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1000; background: rgba(255,255,255,0.9); width: 100%; height: 100%;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                            <p class="mt-2 text-muted">Loading orders...</p>
                        </div>

                        <table class="table align-middle table-nowrap dt-responsive nowrap w-100" id="orders-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Order Number</th>
                                    <th>Payment Status</th>
                                    <th>Shipping Status</th>
                                    <th>Customer Name</th>
                                    <th>Payment</th>
                                    <th>Discount</th>
                                    <th>Shipping</th>
                                    <th>Total to Pay</th>
                                    <th>Handled By</th>
                                    <th>Date</th>
                                    <th>Time</th>
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
<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
    <!-- Required datatable js -->
    <script src="<?php echo e(URL::asset('build/libs/datatables.net/js/jquery.dataTables.min.js')); ?>"></script>
    <script src="<?php echo e(URL::asset('build/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js')); ?>"></script>

    <!-- Responsive examples -->
    <script src="<?php echo e(URL::asset('build/libs/datatables.net-responsive/js/dataTables.responsive.min.js')); ?>"></script>
    <script src="<?php echo e(URL::asset('build/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js')); ?>"></script>

    <script>
        $(document).ready(function() {
            // Show loading indicator initially
            $('#table-loading').show();

            // Initialize DataTable
            var table = $('#orders-table').DataTable({
                processing: false, // Disable built-in processing indicator
                serverSide: true,
                ajax: {
                    url: "<?php echo e(route('ecom-orders.data')); ?>",
                    type: 'GET',
                    beforeSend: function() {
                        $('#table-loading').show();
                    },
                    complete: function() {
                        $('#table-loading').hide();
                    },
                    error: function(xhr, error, thrown) {
                        console.error('DataTables error:', error);
                        $('#table-loading').hide();
                        alert('Error loading orders data. Please refresh the page.');
                    }
                },
                columns: [
                    { data: 'orderNumber', name: 'orderNumber' },
                    { data: 'paymentStatus', name: 'paymentStatus' },
                    { data: 'shippingStatus', name: 'shippingStatus' },
                    { data: 'customerFullName', name: 'customerFullName' },
                    { data: 'formatted_payment_amount', name: 'paymentAmount' },
                    { data: 'formatted_payment_discount', name: 'paymentDiscount' },
                    { data: 'formatted_shipping_amount', name: 'shippingAmount' },
                    { data: 'formatted_total_to_pay', name: 'totalToPay' },
                    { data: 'handledBy', name: 'handledBy' },
                    { data: 'formatted_date', name: 'created_at' },
                    { data: 'formatted_time', name: 'created_at' },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            return `
                                <div class="d-flex gap-2">
                                    <a href="#" class="btn btn-primary btn-sm" title="View Details">
                                        <i class="mdi mdi-eye"></i>
                                    </a>
                                    <a href="#" class="btn btn-warning btn-sm" title="Edit">
                                        <i class="mdi mdi-pencil"></i>
                                    </a>
                                    <a href="#" class="btn btn-danger btn-sm" title="Delete">
                                        <i class="mdi mdi-delete"></i>
                                    </a>
                                </div>
                            `;
                        }
                    }
                ],
                pageLength: 100,
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
                initComplete: function() {
                    // Hide loading indicator when table is initialized
                    $('#table-loading').hide();
                    console.log('Orders table initialized successfully');
                }
            });

            // Search functionality with loading indicator
            $('#searchTableList').on('keyup', function() {
                $('#table-loading').show();
                table.search(this.value).draw();
            });
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\btc-check\resources\views/ecommerce/orders/index.blade.php ENDPATH**/ ?>