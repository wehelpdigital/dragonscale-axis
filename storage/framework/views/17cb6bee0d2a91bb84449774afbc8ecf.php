<?php $__env->startSection('title'); ?>
    Add New Order
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php $__env->startComponent('components.breadcrumb'); ?>
        <?php $__env->slot('li_1'); ?>
            Ecommerce
        <?php $__env->endSlot(); ?>
        <?php $__env->slot('li_2'); ?>
            Orders
        <?php $__env->endSlot(); ?>
        <?php $__env->slot('title'); ?>
            Add New Order
        <?php $__env->endSlot(); ?>
    <?php echo $__env->renderComponent(); ?>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Add New Order</h4>
                    <p class="card-title-desc">Create a new order using the step-by-step wizard below.</p>

                    <!-- Progress Bar -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar" role="progressbar" id="wizard-progress" style="width: 33.33%"></div>
                            </div>
                            <div class="d-flex justify-content-between mt-2">
                                <small class="text-muted">Step 1: Product Selection</small>
                                <small class="text-muted">Step 2: Order & Payment</small>
                                <small class="text-muted">Step 3: Status & Finalization</small>
                            </div>
                        </div>
                    </div>

                    <!-- Wizard Form -->
                    <form id="order-wizard-form" method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="wizard-content">
                            <!-- Step 1: Product Selection -->
                            <div class="wizard-step" id="step-1">
                                <h5 class="mb-3">Product Selection</h5>

                                <!-- Available Products Row -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <div class="card border-primary">
                                            <div class="card-header bg-primary text-white rounded-top">
                                                <h6 class="card-title mb-0 text-white">
                                                    <i class="mdi mdi-package-variant me-2 text-white"></i>Available Products
                                                </h6>
                                            </div>
                                            <div class="card-body">
                                                <!-- Search Bar -->
                                                <div class="mb-3">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="input-group">
                                                                <span class="input-group-text">
                                                                    <i class="mdi mdi-store"></i>
                                                                </span>
                                                                <input type="text" class="form-control" id="store-search" placeholder="Search by store name...">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="input-group">
                                                                <span class="input-group-text">
                                                                    <i class="mdi mdi-package-variant"></i>
                                                                </span>
                                                                <input type="text" class="form-control" id="product-search" placeholder="Search by product/variant name...">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Products List -->
                                                <div id="products-container" style="max-height: 500px; overflow-y: auto;">
                                                    <div class="text-center py-3">
                                                        <div class="spinner-border text-primary" role="status">
                                                            <span class="visually-hidden">Loading...</span>
                                                        </div>
                                                        <p class="mt-2 text-muted">Loading products...</p>
                                                    </div>
                                                </div>

                                                <!-- Pagination -->
                                                <nav aria-label="Products pagination" class="mt-3">
                                                    <ul class="pagination pagination-sm justify-content-center" id="products-pagination">
                                                    </ul>
                                                </nav>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Selected Products Row -->
                                <div class="row">
                                    <div class="col-12">
                                        <div class="card border-success">
                                            <div class="card-header bg-success text-white rounded-top">
                                                <h6 class="card-title mb-0">
                                                    <i class="mdi mdi-cart me-2"></i>Selected Products
                                                </h6>
                                            </div>
                                            <div class="card-body">
                                                <div id="cart-container">
                                                    <div class="text-center py-3 text-muted">
                                                        <i class="mdi mdi-cart-outline" style="font-size: 48px;"></i>
                                                        <p class="mt-2">No products selected</p>
                                                        <small>Select products from the available products above to add them to your order</small>
                                                    </div>
                                                </div>

                                                <!-- Cart Summary -->
                                                <div class="mt-3 pt-3 border-top" id="cart-summary" style="display: none;">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="d-flex justify-content-between">
                                                                <strong>Total Items:</strong>
                                                                <span id="total-items">0</span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="d-flex justify-content-between">
                                                                <strong>Total Amount:</strong>
                                                                <span id="total-amount">₱0.00</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Hidden input for selected products -->
                                <input type="hidden" id="selectedProducts" name="selectedProducts" value="">
                            </div>

                            <!-- Step 2: Order Details & Payment Information -->
                            <div class="wizard-step d-none" id="step-2">
                                <h5 class="mb-3">Order Details & Payment Information</h5>

                                <!-- Order Basic Info Row -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <div class="card border-info">
                                            <div class="card-header bg-info text-white">
                                                <h6 class="card-title mb-0">
                                                    <i class="mdi mdi-account me-2"></i>Order Details
                                                </h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="orderNumber" class="form-label">Order Number <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control" id="orderNumber" name="orderNumber" required>
                                                            <div class="invalid-feedback"></div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="customerFullName" class="form-label">Customer Full Name <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control" id="customerFullName" name="customerFullName" required>
                                                            <div class="invalid-feedback"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Information Row -->
                                <div class="row">
                                    <div class="col-12">
                                        <div class="card border-warning">
                                            <div class="card-header bg-warning text-dark">
                                                <h6 class="card-title mb-0">
                                                    <i class="mdi mdi-currency-usd me-2"></i>Payment Information
                                                </h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="paymentAmount" class="form-label">Payment Amount <span class="text-danger">*</span></label>
                                                            <div class="input-group">
                                                                <span class="input-group-text">₱</span>
                                                                <input type="number" class="form-control" id="paymentAmount" name="paymentAmount" step="0.01" min="0" required>
                                                            </div>
                                                            <div class="invalid-feedback"></div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="paymentDiscount" class="form-label">Payment Discount</label>
                                                            <div class="input-group">
                                                                <span class="input-group-text">₱</span>
                                                                <input type="number" class="form-control" id="paymentDiscount" name="paymentDiscount" step="0.01" min="0" value="0">
                                                            </div>
                                                            <div class="invalid-feedback"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="shippingAmount" class="form-label">Shipping Amount</label>
                                                            <div class="input-group">
                                                                <span class="input-group-text">₱</span>
                                                                <input type="number" class="form-control" id="shippingAmount" name="shippingAmount" step="0.01" min="0" value="0">
                                                            </div>
                                                            <div class="invalid-feedback"></div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="totalToPay" class="form-label">Total to Pay <span class="text-danger">*</span></label>
                                                            <div class="input-group">
                                                                <span class="input-group-text">₱</span>
                                                                <input type="number" class="form-control" id="totalToPay" name="totalToPay" step="0.01" min="0" required readonly>
                                                            </div>
                                                            <div class="invalid-feedback"></div>
                                                        </div>
                                                    </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

                            <!-- Step 3: Status & Finalization -->
                            <div class="wizard-step d-none" id="step-3">
                                <h5 class="mb-3">Status & Finalization</h5>
                                <div class="row">
                                    <div class="col-12">
                                        <div class="card border-secondary">
                                            <div class="card-header bg-secondary text-white">
                                                <h6 class="card-title mb-0">
                                                    <i class="mdi mdi-check-circle me-2"></i>Order Status & Finalization
                                                </h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="paymentStatus" class="form-label">Payment Status <span class="text-danger">*</span></label>
                                                            <select class="form-select" id="paymentStatus" name="paymentStatus" required>
                                                                <option value="">Select Payment Status</option>
                                                                <option value="pending">Pending</option>
                                                                <option value="paid">Paid</option>
                                                                <option value="refunded">Refunded</option>
                                                                <option value="partial">Partial</option>
                                                            </select>
                                                            <div class="invalid-feedback"></div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="shippingStatus" class="form-label">Shipping Status <span class="text-danger">*</span></label>
                                                            <select class="form-select" id="shippingStatus" name="shippingStatus" required>
                                                                <option value="">Select Shipping Status</option>
                                                                <option value="pending">Pending</option>
                                                                <option value="shipped">Shipped</option>
                                                                <option value="delivered">Delivered</option>
                                                                <option value="returned">Returned</option>
                                                            </select>
                                                            <div class="invalid-feedback"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="mb-3">
                                                            <label for="handledBy" class="form-label">Handled By</label>
                                                            <input type="text" class="form-control" id="handledBy" name="handledBy" placeholder="Enter handler name">
                                                            <div class="invalid-feedback"></div>
                                                        </div>
                                                    </div>
                                                </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

                        <!-- Navigation Buttons -->
                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn btn-secondary" id="prev-btn" style="display: none;">
                                <i class="mdi mdi-arrow-left me-1"></i> Previous
                            </button>
                            <div class="ms-auto">
                                <button type="button" class="btn btn-primary" id="next-btn">
                                    Next <i class="mdi mdi-arrow-right ms-1"></i>
                                </button>
                                <button type="submit" class="btn btn-success d-none" id="submit-btn">
                                    <i class="mdi mdi-check me-1"></i> Create Order
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Variant Details Modal -->
    <div class="modal fade" id="variantModal" tabindex="-1" aria-labelledby="variantModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="variantModalLabel">Variant Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="variantModalBody">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading variant details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Lightbox Modal -->
    <div class="modal fade" id="imageLightbox" tabindex="-1" aria-labelledby="imageLightboxLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageLightboxLabel">Image Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="lightboxImage" src="" alt="Variant Image" class="img-fluid" style="max-height: 70vh;">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Video Lightbox Modal -->
    <div class="modal fade" id="videoLightbox" tabindex="-1" aria-labelledby="videoLightboxLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="videoLightboxLabel">Video Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="ratio ratio-16x9">
                        <iframe id="lightboxVideo" src="" title="Variant Video" allowfullscreen></iframe>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Quantity Selection Modal -->
    <div class="modal fade" id="quantityModal" tabindex="-1" aria-labelledby="quantityModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="quantityModalLabel">Select Quantity</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <h6 id="quantityModalProductName" class="text-primary"></h6>
                        <p class="text-muted mb-0" id="quantityModalProductPrice"></p>
                    </div>
                    <div class="mb-3">
                        <label for="quantityInput" class="form-label">Quantity <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="quantityInput" min="1" max="1" value="1" required>
                        <div class="form-text">
                            Maximum quantity per transaction: <span id="maxQuantityDisplay" class="fw-bold text-primary">1</span>
                        </div>
                        <div class="invalid-feedback" id="quantityError"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmQuantityBtn">Add to Cart</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="successModalLabel">Order Created Successfully</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <i class="mdi mdi-check-circle text-success" style="font-size: 48px;"></i>
                        <p class="mt-3">Your order has been created successfully!</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="<?php echo e(route('ecom-orders')); ?>" class="btn btn-primary">View Orders</a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
<script>
$(document).ready(function() {
    let currentStep = 1;
    const totalSteps = 3;
    let selectedProducts = [];
    let currentProductsPage = 1;
    let currentStoreSearch = '';
    let currentProductSearch = '';
    let searchTimeout;
    let currentVariantForModal = null;

    // Auto-calculate total to pay
    function calculateTotal() {
        const paymentAmount = parseFloat($('#paymentAmount').val()) || 0;
        const paymentDiscount = parseFloat($('#paymentDiscount').val()) || 0;
        const shippingAmount = parseFloat($('#shippingAmount').val()) || 0;

        const total = paymentAmount - paymentDiscount + shippingAmount;
        $('#totalToPay').val(total.toFixed(2));
    }

    // Bind calculation events
    $('#paymentAmount, #paymentDiscount, #shippingAmount').on('input', calculateTotal);

    // Load products
    function loadProducts(page = 1, storeSearch = '', productSearch = '') {
        $.ajax({
            url: '<?php echo e(route("ecom-orders-custom-add.products")); ?>',
            type: 'GET',
            data: {
                page: page,
                store_search: storeSearch,
                product_search: productSearch,
                per_page: 20
            },
            success: function(response) {
                if (response.success) {
                    displayProducts(response.data);
                    updateProductsPagination(response.pagination);
                }
            },
            error: function() {
                $('#products-container').html('<div class="text-center py-3 text-danger">Error loading products</div>');
            }
        });
    }

    // Display products
    function displayProducts(products) {
        if (products.length === 0) {
            $('#products-container').html('<div class="text-center py-3 text-muted">No products found</div>');
            return;
        }

        let html = '';
        products.forEach(function(product, index) {
            const isLast = index === products.length - 1;
            html += `
                <div class="card mb-2 ${!isLast ? 'border-bottom' : ''}" style="border-left: 4px solid #007bff;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">${product.productName}</h6>
                                <small class="text-muted">${product.productStore}</small>
                            </div>
                            <button class="btn btn-sm btn-outline-primary" onclick="toggleProductVariants(${product.id})">
                                <i class="mdi mdi-chevron-down" id="chevron-${product.id}"></i>
                            </button>
                        </div>
                        <div id="variants-${product.id}" class="mt-3" style="display: none;">
                            <div class="text-center py-2">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <small class="ms-2">Loading variants...</small>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        $('#products-container').html(html);
    }

    // Toggle product variants
    window.toggleProductVariants = function(productId) {
        const variantsContainer = $(`#variants-${productId}`);
        const chevron = $(`#chevron-${productId}`);

        if (variantsContainer.is(':visible')) {
            variantsContainer.slideUp();
            chevron.removeClass('mdi-chevron-up').addClass('mdi-chevron-down');
        } else {
            variantsContainer.slideDown();
            chevron.removeClass('mdi-chevron-down').addClass('mdi-chevron-up');
            loadProductVariants(productId);
        }
    };

    // Load product variants
    function loadProductVariants(productId, page = 1, search = '') {
        $.ajax({
            url: '<?php echo e(route("ecom-orders-custom-add.variants")); ?>',
            type: 'GET',
            data: {
                product_id: productId,
                page: page,
                search: search,
                per_page: 5
            },
            success: function(response) {
                if (response.success) {
                    displayProductVariants(productId, response.data);
                }
            },
            error: function() {
                $(`#variants-${productId}`).html('<div class="text-center py-2 text-danger">Error loading variants</div>');
            }
        });
    }

    // Display product variants
    function displayProductVariants(productId, variants) {
        if (variants.length === 0) {
            $(`#variants-${productId}`).html('<div class="text-center py-2 text-muted">No variants available</div>');
            return;
        }

        let html = `
            <div class="mb-2">
                <div class="input-group input-group-sm">
                    <span class="input-group-text">
                        <i class="mdi mdi-magnify"></i>
                    </span>
                    <input type="text" class="form-control" id="variant-search-${productId}" placeholder="Search variants...">
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover" id="variants-table-${productId}">
                    <thead>
                        <tr>
                            <th>Variant</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Max Order</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        variants.forEach(function(variant) {
            const isInCart = selectedProducts.some(item => item.variantId === variant.id);
            const maxOrderPerTransaction = parseInt(variant.maxOrderPerTransaction) || 1;
            html += `
                <tr data-variant-name="${variant.ecomVariantName.toLowerCase()}">
                    <td>${variant.ecomVariantName}</td>
                    <td>₱${parseFloat(variant.ecomVariantPrice).toFixed(2)}</td>
                    <td>
                        <span class="badge ${variant.stocksAvailable > 0 ? 'bg-success' : 'bg-danger'}">
                            ${variant.stocksAvailable}
                        </span>
                    </td>
                    <td>
                        <span class="badge ${maxOrderPerTransaction === 1 ? 'bg-info' : 'bg-warning'}">
                            ${maxOrderPerTransaction}
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-info me-1" onclick="viewVariant(${variant.id})">
                            <i class="mdi mdi-eye"></i>
                        </button>
                        <button class="btn btn-sm ${isInCart ? 'btn-success' : 'btn-primary'}"
                                onclick="toggleVariantInCart(${variant.id}, ${productId})"
                                ${variant.stocksAvailable === 0 ? 'disabled' : ''}>
                            <i class="mdi ${isInCart ? 'mdi-check' : 'mdi-plus'}"></i>
                        </button>
                    </td>
                </tr>
            `;
        });

        html += `
                    </tbody>
                </table>
            </div>
        `;

        $(`#variants-${productId}`).html(html);

        // Bind variant search functionality
        $(`#variant-search-${productId}`).on('input', function() {
            const searchTerm = $(this).val().toLowerCase();
            $(`#variants-table-${productId} tbody tr`).each(function() {
                const variantName = $(this).data('variant-name');
                if (variantName.includes(searchTerm)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });
    }

    // Toggle variant in cart
    window.toggleVariantInCart = function(variantId, productId) {
        const existingIndex = selectedProducts.findIndex(item => item.variantId === variantId);

        if (existingIndex > -1) {
            // Remove from cart
            selectedProducts.splice(existingIndex, 1);
            updateCartDisplay();
        } else {
            // Add to cart - we need to get variant details first
            $.ajax({
                url: '<?php echo e(route("ecom-orders-custom-add.variants")); ?>',
                type: 'GET',
                data: { product_id: productId },
                success: function(response) {
                    if (response.success) {
                        const variant = response.data.find(v => v.id === variantId);
                        if (variant) {
                            // Check maxOrderPerTransaction
                            const maxOrderPerTransaction = parseInt(variant.maxOrderPerTransaction) || 1;

                            if (maxOrderPerTransaction === 1) {
                                // Add directly to cart with quantity 1
                                selectedProducts.push({
                                    variantId: variant.id,
                                    variantName: variant.ecomVariantName,
                                    price: variant.ecomVariantPrice,
                                    productId: productId,
                                    quantity: 1
                                });
                                updateCartDisplay();
                            } else {
                                // Show quantity selection modal
                                showQuantityModal(variant, productId);
                            }
                        }
                    }
                }
            });
        }
    };

    // Update cart display
    function updateCartDisplay() {
        const cartContainer = $('#cart-container');
        const cartSummary = $('#cart-summary');

        if (selectedProducts.length === 0) {
            cartContainer.html(`
                <div class="text-center py-3 text-muted">
                    <i class="mdi mdi-cart-outline" style="font-size: 48px;"></i>
                    <p class="mt-2">No products selected</p>
                    <small>Select products from the left panel to add them to your order</small>
                </div>
            `);
            cartSummary.hide();
        } else {
            let html = '';
            let totalAmount = 0;

            selectedProducts.forEach(function(item, index) {
                totalAmount += parseFloat(item.price) * item.quantity;
                html += `
                    <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                        <div>
                            <small class="fw-bold">${item.variantName}</small><br>
                            <small class="text-muted">₱${parseFloat(item.price).toFixed(2)}</small>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="input-group input-group-sm me-2" style="width: 100px;">
                                <button class="btn btn-outline-secondary" type="button" onclick="updateQuantity(${index}, -1)">-</button>
                                <input type="number" class="form-control text-center" value="${item.quantity}" min="1" onchange="updateQuantity(${index}, 0, this.value)">
                                <button class="btn btn-outline-secondary" type="button" onclick="updateQuantity(${index}, 1)">+</button>
                            </div>
                            <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart(${index})">
                                <i class="mdi mdi-delete"></i>
                            </button>
                        </div>
                    </div>
                `;
            });

            cartContainer.html(html);
            $('#total-items').text(selectedProducts.reduce((sum, item) => sum + item.quantity, 0));
            $('#total-amount').text('₱' + totalAmount.toFixed(2));
            cartSummary.show();
        }

        // Update hidden input
        $('#selectedProducts').val(JSON.stringify(selectedProducts));
    }

    // Update quantity
    window.updateQuantity = function(index, change, newValue = null) {
        if (newValue !== null) {
            selectedProducts[index].quantity = Math.max(1, parseInt(newValue));
        } else {
            selectedProducts[index].quantity = Math.max(1, selectedProducts[index].quantity + change);
        }
        updateCartDisplay();
    };

    // Remove from cart
    window.removeFromCart = function(index) {
        selectedProducts.splice(index, 1);
        updateCartDisplay();
    };

    // Show quantity selection modal
    function showQuantityModal(variant, productId) {
        currentVariantForModal = {
            variant: variant,
            productId: productId
        };

        // Update modal content
        $('#quantityModalProductName').text(variant.ecomVariantName);
        $('#quantityModalProductPrice').text('₱' + parseFloat(variant.ecomVariantPrice).toFixed(2));

        const maxOrderPerTransaction = parseInt(variant.maxOrderPerTransaction) || 1;
        $('#quantityInput').attr('max', maxOrderPerTransaction).val(1);
        $('#maxQuantityDisplay').text(maxOrderPerTransaction);

        // Clear any previous validation
        $('#quantityInput').removeClass('is-invalid');
        $('#quantityError').text('').hide();

        // Show modal
        $('#quantityModal').modal('show');
    }

    // Handle quantity modal confirmation
    $('#confirmQuantityBtn').click(function() {
        const quantity = parseInt($('#quantityInput').val());
        const maxOrderPerTransaction = parseInt(currentVariantForModal.variant.maxOrderPerTransaction) || 1;

        // Validate quantity
        if (!quantity || quantity < 1) {
            $('#quantityInput').addClass('is-invalid');
            $('#quantityError').text('Please enter a valid quantity.').show();
            return;
        }

        if (quantity > maxOrderPerTransaction) {
            $('#quantityInput').addClass('is-invalid');
            $('#quantityError').text(`Quantity cannot exceed ${maxOrderPerTransaction}.`).show();
            return;
        }

        // Add to cart with selected quantity
        selectedProducts.push({
            variantId: currentVariantForModal.variant.id,
            variantName: currentVariantForModal.variant.ecomVariantName,
            price: currentVariantForModal.variant.ecomVariantPrice,
            productId: currentVariantForModal.productId,
            quantity: quantity
        });

        // Close modal and update display
        $('#quantityModal').modal('hide');
        updateCartDisplay();
        currentVariantForModal = null;
    });

    // Handle quantity input validation
    $('#quantityInput').on('input', function() {
        const quantity = parseInt($(this).val());
        const maxOrderPerTransaction = parseInt(currentVariantForModal?.variant?.maxOrderPerTransaction) || 1;

        $(this).removeClass('is-invalid');
        $('#quantityError').text('').hide();

        if (quantity > maxOrderPerTransaction) {
            $(this).addClass('is-invalid');
            $('#quantityError').text(`Quantity cannot exceed ${maxOrderPerTransaction}.`).show();
        }
    });

    // View variant details
    window.viewVariant = function(variantId) {
        // Show modal and load variant details
        $('#variantModal').modal('show');

        // Reset modal body to loading state
        $('#variantModalBody').html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading variant details...</p>
            </div>
        `);

        // Fetch variant details
        $.ajax({
            url: '<?php echo e(route("ecom-orders-custom-add.variant-details")); ?>',
            type: 'GET',
            data: { variant_id: variantId },
            success: function(response) {
                if (response.success) {
                    displayVariantDetails(response.data);
                } else {
                    $('#variantModalBody').html(`
                        <div class="text-center py-4 text-danger">
                            <i class="mdi mdi-alert-circle" style="font-size: 48px;"></i>
                            <p class="mt-2">${response.message || 'Error loading variant details'}</p>
                        </div>
                    `);
                }
            },
            error: function() {
                $('#variantModalBody').html(`
                    <div class="text-center py-4 text-danger">
                        <i class="mdi mdi-alert-circle" style="font-size: 48px;"></i>
                        <p class="mt-2">Error loading variant details</p>
                    </div>
                `);
            }
        });
    };

    // Display variant details in modal
    function displayVariantDetails(data) {
        const variant = data.variant;
        const product = data.product;
        const images = data.images;

        let html = `
            <!-- Header Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                            <i class="mdi mdi-package-variant text-white"></i>
                        </div>
                        <div>
                            <h4 class="mb-1 text-primary">${variant.ecomVariantName || 'Variant Details'}</h4>
                            <p class="text-muted mb-0">${product.productName || 'Product'}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="row">
                <!-- Left Column - Product Information -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-light border-0">
                            <h6 class="mb-0 text-primary">
                                <i class="mdi mdi-information-outline me-2"></i>Product Information
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-bold text-muted small">Product Name</label>
                                    <p class="mb-0">${product.productName || 'N/A'}</p>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold text-muted small">Product Store</label>
                                    <p class="mb-0">
                                        <span class="badge bg-info">${product.productStore || 'N/A'}</span>
                                    </p>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold text-muted small">Product Description</label>
                                    <p class="mb-0 text-muted">${product.productDescription || 'No description available'}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Product Images -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-light border-0">
                            <h6 class="mb-0 text-primary">
                                <i class="mdi mdi-image-multiple me-2"></i>Product Images
                            </h6>
                        </div>
                        <div class="card-body">
        `;

        if (images.length > 0) {
            html += `
                <div class="row g-3" id="image-gallery">
            `;

            images.forEach(function(image, index) {
                html += `
                    <div class="col-6">
                        <div class="card border-0 shadow-sm">
                            <img src="${image.imageLink}"
                                 class="card-img-top rounded"
                                 style="height: 150px; object-fit: cover; cursor: pointer; transition: transform 0.2s;"
                                 alt="Variant Image ${index + 1}"
                                 onclick="showImageLightbox('${image.imageLink}')"
                                 onmouseover="this.style.transform='scale(1.05)'"
                                 onmouseout="this.style.transform='scale(1)'"
                                 onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iI2RkZCIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTIiIGZpbGw9IiM5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5JbWFnZTwvdGV4dD48L3N2Zz4='">
                        </div>
                    </div>
                `;
            });

            html += `
                </div>
            `;
        } else {
            html += `
                    <div class="text-center py-5">
                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="mdi mdi-image-off text-muted" style="font-size: 32px;"></i>
                    </div>
                    <h6 class="text-muted">No Images Available</h6>
                    <p class="text-muted small mb-0">This variant doesn't have any images yet.</p>
                </div>
            `;
        }

        html += `
                        </div>
                    </div>
                </div>
            </div>

            <!-- Second Row - Variant Information & Videos -->
            <div class="row">
                <!-- Left Column - Variant Information -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light border-0">
                            <h6 class="mb-0 text-primary">
                                <i class="mdi mdi-tag-outline me-2"></i>Variant Information
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-bold text-muted small">Variant Name</label>
                                    <p class="mb-0">${variant.ecomVariantName || 'N/A'}</p>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold text-muted small">Variant Description</label>
                                    <p class="mb-0 text-muted">${variant.ecomVariantDescription || 'No description available'}</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-muted small">Price</label>
                                    <div class="d-flex align-items-center">
                                        <span class="h5 text-success mb-0">₱${parseFloat(variant.ecomVariantPrice || 0).toFixed(2)}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-muted small">Stock Available</label>
                                    <div class="d-flex align-items-center">
                                        <span class="badge ${variant.stocksAvailable > 0 ? 'bg-success' : 'bg-danger'} fs-6 px-3 py-2">
                                            <i class="mdi mdi-${variant.stocksAvailable > 0 ? 'check-circle' : 'close-circle'} me-1"></i>
                                            ${variant.stocksAvailable || 0} ${variant.stocksAvailable === 1 ? 'item' : 'items'}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-muted small">Max Order Per Transaction</label>
                                    <div class="d-flex align-items-center">
                                        <span class="badge ${parseInt(variant.maxOrderPerTransaction) === 1 ? 'bg-info' : 'bg-warning'} fs-6 px-3 py-2">
                                            <i class="mdi mdi-${parseInt(variant.maxOrderPerTransaction) === 1 ? 'lock' : 'lock-open'} me-1"></i>
                                            ${variant.maxOrderPerTransaction || 1}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

                <!-- Right Column - Product Videos -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light border-0">
                            <h6 class="mb-0 text-primary">
                                <i class="mdi mdi-video me-2"></i>Product Videos
                            </h6>
                        </div>
                        <div class="card-body">
        `;

        if (data.videos && data.videos.length > 0) {
            html += `
                <div class="row g-3" id="video-gallery">
            `;

            data.videos.forEach(function(video, index) {
                // Extract YouTube video ID from embed URL
                const videoId = extractYouTubeVideoId(video.videoLink);
                const thumbnailUrl = `https://img.youtube.com/vi/${videoId}/hqdefault.jpg`;

                html += `
                    <div class="col-6">
                        <div class="card border-0 shadow-sm">
                            <div class="position-relative">
                                <img src="${thumbnailUrl}"
                                     class="card-img-top rounded"
                                     style="height: 120px; object-fit: cover; cursor: pointer;"
                                     alt="Video Thumbnail ${index + 1}"
                                     onclick="showVideoLightbox('${video.videoLink}')">
                                <div class="position-absolute top-50 start-50 translate-middle">
                                    <div class="bg-danger rounded-circle p-2">
                                        <i class="mdi mdi-play text-white"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });

            html += `
                </div>
            `;
        } else {
            html += `
                <div class="text-center py-5">
                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="mdi mdi-video-off text-muted" style="font-size: 32px;"></i>
                    </div>
                    <h6 class="text-muted">No Videos Available</h6>
                    <p class="text-muted small mb-0">This variant doesn't have any videos yet.</p>
                </div>
            `;
        }

        html += `
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('#variantModalBody').html(html);
    }

    // Show image lightbox
    window.showImageLightbox = function(imageSrc) {
        $('#lightboxImage').attr('src', imageSrc);
        $('#imageLightbox').modal('show');
    };

    // Extract YouTube video ID from embed URL
    function extractYouTubeVideoId(url) {
        const match = url.match(/embed\/([a-zA-Z0-9_-]+)/);
        return match ? match[1] : null;
    }

    // Show video lightbox
    window.showVideoLightbox = function(videoLink) {
        $('#lightboxVideo').attr('src', videoLink);
        $('#videoLightbox').modal('show');
    };

    // Clear video when modal is closed
    $('#videoLightbox').on('hidden.bs.modal', function () {
        $('#lightboxVideo').attr('src', '');
    });

    // Update products pagination
    function updateProductsPagination(pagination) {
        const paginationContainer = $('#products-pagination');
        let html = '';

        if (pagination.last_page > 1) {
            // Previous button
            html += `<li class="page-item ${pagination.current_page === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="loadProductsPage(${pagination.current_page - 1})">Previous</a>
            </li>`;

            // Page numbers
            for (let i = 1; i <= pagination.last_page; i++) {
                html += `<li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="loadProductsPage(${i})">${i}</a>
                </li>`;
            }

            // Next button
            html += `<li class="page-item ${pagination.current_page === pagination.last_page ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="loadProductsPage(${pagination.current_page + 1})">Next</a>
            </li>`;
        }

        paginationContainer.html(html);
    }

    // Load products page
    window.loadProductsPage = function(page) {
        currentProductsPage = page;
        loadProducts(page, currentStoreSearch, currentProductSearch);
    };

    // Dynamic search function with debouncing
    function performSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            currentStoreSearch = $('#store-search').val();
            currentProductSearch = $('#product-search').val();
            currentProductsPage = 1;
            loadProducts(1, currentStoreSearch, currentProductSearch);
        }, 300); // 300ms delay
    }

    // Bind search events
    $('#store-search, #product-search').on('input', performSearch);

    // Search on enter key
    $('#store-search, #product-search').keypress(function(e) {
        if (e.which === 13) {
            clearTimeout(searchTimeout);
            performSearch();
        }
    });

    // Show step
    function showStep(step) {
        $('.wizard-step').addClass('d-none');
        $(`#step-${step}`).removeClass('d-none');

        // Update progress bar
        const progress = (step / totalSteps) * 100;
        $('#wizard-progress').css('width', progress + '%');

        // Update navigation buttons
        $('#prev-btn').toggle(step > 1);
        $('#next-btn').toggle(step < totalSteps);
        $('#submit-btn').toggle(step === totalSteps);

        currentStep = step;
    }

    // Validate current step
    function validateStep(step) {
        const stepData = {};
        let isValid = true;

        // Get form data for current step
        $(`#step-${step} input, #step-${step} select`).each(function() {
            const $field = $(this);
            const name = $field.attr('name');
            const value = $field.val();

            if (name) {
                stepData[name] = value;

                // Clear previous validation
                $field.removeClass('is-invalid');
                $field.siblings('.invalid-feedback').text('');

                // Basic client-side validation
                if ($field.prop('required') && !value.trim()) {
                    $field.addClass('is-invalid');
                    $field.siblings('.invalid-feedback').text('This field is required.');
                    isValid = false;
                }
            }
        });

        // Special validation for step 1 - check if products are selected
        if (step === 1) {
            if (selectedProducts.length === 0) {
                showAlert('error', 'Please select at least one product before proceeding.');
                isValid = false;
            }
        }

        return isValid;
    }

    // Next button click
    $('#next-btn').click(function() {
        if (validateStep(currentStep)) {
            showStep(currentStep + 1);
        }
    });

    // Previous button click
    $('#prev-btn').click(function() {
        showStep(currentStep - 1);
    });

    // Form submission
    $('#order-wizard-form').submit(function(e) {
        e.preventDefault();

        if (!validateStep(currentStep)) {
            return;
        }

        // Show loading state
        const $submitBtn = $('#submit-btn');
        const originalText = $submitBtn.html();
        $submitBtn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin me-1"></i>Creating...');

        // Get all form data
        const formData = new FormData(this);

        // Submit form
        $.ajax({
            url: '<?php echo e(route("ecom-orders-custom-add.store")); ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $('#successModal').modal('show');
                    $('#order-wizard-form')[0].reset();
                    showStep(1);
                } else {
                    showAlert('error', response.message || 'Failed to create order');
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                if (response && response.errors) {
                    // Show validation errors
                    Object.keys(response.errors).forEach(function(field) {
                        const $field = $(`[name="${field}"]`);
                        $field.addClass('is-invalid');
                        $field.siblings('.invalid-feedback').text(response.errors[field][0]);
                    });
                    showAlert('error', 'Please fix the validation errors');
                } else {
                    showAlert('error', response?.message || 'An error occurred while creating the order');
                }
            },
            complete: function() {
                $submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Show alert function
    function showAlert(type, message) {
        const alertClass = type === 'error' ? 'alert-danger' : 'alert-success';
        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        $('.card-body').prepend(alertHtml);

        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);
    }

    // Initialize
    showStep(1);
    loadProducts(); // Load products on page load
});
</script>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\btc-check\resources\views/ecommerce/orders/custom-add.blade.php ENDPATH**/ ?>