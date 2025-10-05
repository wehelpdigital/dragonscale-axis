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
                                <small class="text-muted">Step 2: Client Details</small>
                                <small class="text-muted">Step 3: Client Logins</small>
                                <small class="text-muted">Step 4: Shipping</small>
                                <small class="text-muted">Step 5: Final Step</small>
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
                                                <h6 class="card-title mb-0" style="color: #fff !important;">
                                                    <i class="mdi mdi-package-variant me-2"></i>Available Products
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
                                                <h6 class="card-title mb-0" style="color: #000 !important;">
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

                            <!-- Step 2: Client Details -->
                            <div class="wizard-step d-none" id="step-2">
                                <h5 class="mb-3">Client Details</h5>

                                <!-- Add New Client Button Row -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <div class="d-flex justify-content-end">
                                            <button type="button" class="btn btn-success" id="add-new-client-btn">
                                                <i class="mdi mdi-account-plus me-1"></i>Add New Client
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Notification Area -->
                                <div class="row mb-3" id="client-notification-area" style="display: none;">
                                    <div class="col-12">
                                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                                            <i class="mdi mdi-check-circle me-2"></i>
                                            <span id="client-notification-message"></span>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Client Search Row -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <div class="card border-primary">
                                            <div class="card-header bg-primary text-white">
                                                <h6 class="card-title mb-0" style="color: #fff !important;">
                                                    <i class="mdi mdi-account-search me-2"></i>Search Client
                                                </h6>
                                            </div>
                                            <div class="card-body">
                                                <!-- Search Fields -->
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <div class="input-group">
                                                            <span class="input-group-text">
                                                                <i class="mdi mdi-account"></i>
                                                            </span>
                                                            <input type="text" class="form-control" id="client-first-name-search" placeholder="First Name">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="input-group">
                                                            <span class="input-group-text">
                                                                <i class="mdi mdi-account"></i>
                                                            </span>
                                                            <input type="text" class="form-control" id="client-middle-name-search" placeholder="Middle Name">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <div class="input-group">
                                                            <span class="input-group-text">
                                                                <i class="mdi mdi-account"></i>
                                                            </span>
                                                            <input type="text" class="form-control" id="client-last-name-search" placeholder="Last Name">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="input-group">
                                                            <span class="input-group-text">
                                                                <i class="mdi mdi-phone"></i>
                                                            </span>
                                                            <input type="text" class="form-control" id="client-phone-search" placeholder="Phone Number">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <div class="input-group">
                                                            <span class="input-group-text">
                                                                <i class="mdi mdi-email"></i>
                                                            </span>
                                                            <input type="text" class="form-control" id="client-email-search" placeholder="Email Address">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="d-flex gap-2">
                                                            <button type="button" class="btn btn-primary" id="search-clients-btn">
                                                                <i class="mdi mdi-magnify me-1"></i> Search
                                                            </button>
                                                            <button type="button" class="btn btn-outline-secondary" id="clear-client-search-btn">
                                                                <i class="mdi mdi-refresh me-1"></i> Clear
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Client Results Row -->
                                <div class="row">
                                    <div class="col-12">
                                        <div class="card border-success">
                                            <div class="card-header bg-success text-white">
                                                <h6 class="card-title mb-0" style="color: #fff !important;">
                                                    <i class="mdi mdi-account-group me-2"></i>Select Client
                                                </h6>
                                            </div>
                                            <div class="card-body">
                                                <!-- Clients Table -->
                                                <div id="clients-container" style="max-height: 400px; overflow-y: auto;">
                                                    <div class="text-center py-4">
                                                        <div class="spinner-border text-primary" role="status">
                                                            <span class="visually-hidden">Loading...</span>
                                                        </div>
                                                        <p class="mt-2 text-muted">Loading clients...</p>
                                                    </div>
                                                </div>

                                                <!-- Pagination -->
                                                <nav aria-label="Clients pagination" class="mt-3">
                                                    <ul class="pagination pagination-sm justify-content-center" id="clients-pagination">
                                                    </ul>
                                                </nav>

                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Hidden input for selected client -->
                                <input type="hidden" id="selectedClient" name="selectedClient" value="">

                            </div>

                            <!-- Add New Client Modal -->
                            <div class="modal fade" id="addNewClientModal" tabindex="-1" aria-labelledby="addNewClientModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header bg-primary text-white">
                                            <h5 class="modal-title" id="addNewClientModalLabel">
                                                <i class="mdi mdi-account-plus me-2"></i>Add New Client
                                            </h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form id="addNewClientForm">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="mb-3">
                                                            <label for="newClientFirstName" class="form-label">First Name <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control" id="newClientFirstName" name="clientFirstName" required>
                                                            <div class="invalid-feedback"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="mb-3">
                                                            <label for="newClientMiddleName" class="form-label">Middle Name <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control" id="newClientMiddleName" name="clientMiddleName" required>
                                                            <div class="invalid-feedback"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="mb-3">
                                                            <label for="newClientLastName" class="form-label">Last Name <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control" id="newClientLastName" name="clientLastName" required>
                                                            <div class="invalid-feedback"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="mb-3">
                                                            <label for="newClientPhoneNumber" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control" id="newClientPhoneNumber" name="clientPhoneNumber" placeholder="09XXXXXXXXX or +63XXXXXXXXX or 63XXXXXXXXX" required>
                                                            <div class="invalid-feedback"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="mb-3">
                                                            <label for="newClientEmailAddress" class="form-label">Email Address <span class="text-danger">*</span></label>
                                                            <input type="email" class="form-control" id="newClientEmailAddress" name="clientEmailAddress" required>
                                                            <div class="invalid-feedback"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                <i class="mdi mdi-close me-1"></i>Cancel
                                            </button>
                                            <button type="button" class="btn btn-primary" id="saveNewClientBtn">
                                                <i class="mdi mdi-content-save me-1"></i>Save Client
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Success Notification Modal -->
                            <div class="modal fade" id="clientSuccessModal" tabindex="-1" aria-labelledby="clientSuccessModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-sm">
                                    <div class="modal-content">
                                        <div class="modal-header bg-success text-white">
                                            <h5 class="modal-title" id="clientSuccessModalLabel">
                                                <i class="mdi mdi-check-circle me-2"></i>Success
                                            </h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body text-center">
                                            <i class="mdi mdi-check-circle display-4 text-success mb-3"></i>
                                            <h6>Client Added Successfully!</h6>
                                            <p class="text-muted mb-0">The new client has been added to the database.</p>
                                        </div>
                                        <div class="modal-footer justify-content-center">
                                            <button type="button" class="btn btn-success" data-bs-dismiss="modal">
                                                <i class="mdi mdi-check me-1"></i>OK
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

        <!-- Step 3: Client Logins -->
        <div class="wizard-step d-none" id="step-3">
            <h5 class="mb-3">Client Logins</h5>

            <!-- Access Products Stores -->
            <div id="access-stores-container">
                <!-- Stores will be dynamically loaded here -->
            </div>

            <!-- No Access Products Message -->
            <div id="no-access-products" class="text-center py-5" style="display: none;">
                <i class="mdi mdi-skip-next display-4 text-muted mb-3"></i>
                <h6 class="text-muted">No Access Products Selected</h6>
                <p class="text-muted">You are not buying any access type products, so skip this test.</p>
            </div>

        </div>

        <!-- Step 4: Shipping -->
        <div class="wizard-step d-none" id="step-4">
            <h5 class="mb-3">Shipping</h5>
            <div class="text-center py-5">
                <i class="mdi mdi-truck display-4 text-info mb-3"></i>
                <h6 class="text-muted">Shipping Information</h6>
                <p class="text-muted">This step will be implemented later.</p>
            </div>
        </div>

        <!-- Step 5: Final Step -->
        <div class="wizard-step d-none" id="step-5">
            <h5 class="mb-3">Final Step</h5>
            <div class="text-center py-5">
                <i class="mdi mdi-check-circle display-4 text-success mb-3"></i>
                <h6 class="text-muted">Step 5 Content</h6>
                <p class="text-muted">This step will be implemented later.</p>
            </div>
        </div>
    </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- General Navigation Buttons -->
    <div class="d-flex justify-content-between mb-5">
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
    <div class="modal fade" id="quantityModal" tabindex="-1" aria-labelledby="quantityModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
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

    <!-- Quantity Exceeded Error Modal -->
    <div class="modal fade" id="quantityExceededModal" tabindex="-1" aria-labelledby="quantityExceededModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="quantityExceededModalLabel">Quantity Limit Exceeded</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="quantityExceededMessage" class="mb-0"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Exceeded Error Modal -->
    <div class="modal fade" id="stockExceededModal" tabindex="-1" aria-labelledby="stockExceededModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="stockExceededModalLabel">Insufficient Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="stockExceededMessage" class="mb-0"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
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

    <!-- Store Products Modal -->
    <div class="modal fade" id="storeProductsModal" tabindex="-1" aria-labelledby="storeProductsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="storeProductsModalLabel">Products for Store</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="storeProductsContent">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2 text-muted">Loading products...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Alert Modal -->
    <div class="modal fade" id="errorAlertModal" tabindex="-1" aria-labelledby="errorAlertModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="errorAlertModalLabel">
                        <i class="mdi mdi-alert-circle me-2"></i>Validation Error
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <i class="mdi mdi-alert-circle text-danger" style="font-size: 3rem;"></i>
                        <h6 class="mt-3" id="errorAlertMessage">Please check your input and try again.</h6>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
                        <i class="mdi mdi-check me-1"></i>OK
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create New Access Modal -->
    <div class="modal fade" id="createAccessModal" tabindex="-1" aria-labelledby="createAccessModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createAccessModalLabel">Create New Access</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="createAccessForm">
                        <div class="mb-3">
                            <label for="accessPhoneNumber" class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="accessPhoneNumber" name="phoneNumber" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label for="accessEmail" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="accessEmail" name="email" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label for="accessFirstName" class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="accessFirstName" name="firstName" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label for="accessMiddleName" class="form-label">Middle Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="accessMiddleName" name="middleName" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label for="accessLastName" class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="accessLastName" name="lastName" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label for="accessPassword" class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="accessPassword" name="password" required>
                            <div class="invalid-feedback"></div>
                            <div class="form-text">
                                <small class="text-muted">Password must be at least 8 characters with uppercase, lowercase, number, and special character</small>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="accessConfirmPassword" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="accessConfirmPassword" name="confirmPassword" required>
                            <div class="invalid-feedback"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveAccessBtn">Save Access</button>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
<style>
    /* Loading animations */
    .search-loading {
        animation: pulse 1.5s ease-in-out infinite;
    }

    .spinner-border {
        animation: spin 1s linear infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Smooth transitions for loading states */
    #products-container {
        transition: all 0.3s ease;
    }

    .input-group-text {
        transition: all 0.3s ease;
    }

    /* Step transition effects */
    .wizard-step {
        transition: all 0.4s ease-in-out;
        opacity: 0;
        transform: translateX(30px);
    }

    .wizard-step.d-none {
        opacity: 0;
        transform: translateX(30px);
    }

    .wizard-step:not(.d-none) {
        opacity: 1;
        transform: translateX(0);
    }

    /* Custom client row styling */
    .client-row.table-primary, .access-client-row.table-primary {
        background-color: #f6b93b !important; /* Orange background */
        color: #fff !important;
        --bs-table-hover-bg: #f6b93b !important;
        --bs-table-bg: #f6b93b !important;
    }

    /* Fix padding and alignment for selected rows */
    .client-row.table-primary td, .access-client-row.table-primary td {
        padding: 0.75rem !important;
        vertical-align: middle !important;
        border-color: #f6b93b !important;
    }

    /* Ensure consistent row height */
    .client-row, .access-client-row {
        height: auto !important;
    }

    .client-row td, .access-client-row td {
        padding: 0.75rem !important;
        vertical-align: middle !important;
    }

    /* Store color coding for step 3 */
    .store-card-primary {
        border-color: #0d6efd !important;
    }
    .store-card-primary .card-header {
        background-color: #0d6efd !important;
    }
    .store-card-primary .card-header .card-title {
        color: #ffffff !important;
    }
    .store-card-primary .access-client-row.table-primary {
        background-color: #0d6efd !important;
    }
    .store-card-primary .access-client-row.table-primary td {
        border-color: none !important;
    }

    .store-card-success {
        border-color: #198754 !important;
    }
    .store-card-success .card-header {
        background-color: #198754 !important;
    }
    .store-card-success .card-header .card-title {
        color: #ffffff !important;
    }
    .store-card-success .access-client-row.table-primary {
        background-color: #198754 !important;
    }
    .store-card-success .access-client-row.table-primary td {
        border-color: #198754 !important;
    }

    .store-card-warning {
        border-color: #fd7e14 !important;
    }
    .store-card-warning .card-header {
        background-color: #fd7e14 !important;
    }
    .store-card-warning .access-client-row.table-primary {
        background-color: #fd7e14 !important;
    }
    .store-card-warning .access-client-row.table-primary td {
        border-color: #fd7e14 !important;
    }

    .store-card-danger {
        border-color: #dc3545 !important;
    }
    .store-card-danger .card-header {
        background-color: #dc3545 !important;
    }
    .store-card-danger .card-header .card-title {
        color: #ffffff !important;
    }
    .store-card-danger .access-client-row.table-primary {
        background-color: #dc3545 !important;
    }
    .store-card-danger .access-client-row.table-primary td {
        border-color: #dc3545 !important;
    }

    .store-card-info {
        border-color: #0dcaf0 !important;
    }
    .store-card-info .card-header {
        background-color: #0dcaf0 !important;
    }
    .store-card-info .access-client-row.table-primary {
        background-color: #0dcaf0 !important;
    }
    .store-card-info .access-client-row.table-primary td {
        border-color: #0dcaf0 !important;
    }

    .store-card-secondary {
        border-color: #6c757d !important;
    }
    .store-card-secondary .card-header {
        background-color: #6c757d !important;
    }
    .store-card-secondary .access-client-row.table-primary {
        background-color: #6c757d !important;
    }
    .store-card-secondary .access-client-row.table-primary td {
        border-color: #6c757d !important;
    }

    .table .bg-secondary, .table .bg-info {
        font-size: 14px;
        font-weight: 400;
        background: none !important;
        color: #000;
    }



    .client-row.table-primary:hover,
    .client-row.table-primary:hover td,
    .client-row.table-primary:focus,
    .client-row.table-primary:focus td {
        background-color: #f6b93b !important; /* Keep orange on hover */
        --bs-table-hover-bg: #f6b93b;
        color: #fff !important;
    }

    .client-row.table-primary td {
        background-color: transparent !important;
        color: #fff !important;
        vertical-align: middle !important;
    }

    .access-client-row.table-primary td .fw-bold, .client-row.table-primary td .fw-bold {
        color: #000 !important;
    }

    /* Consistent button sizing to prevent text movement */
    .client-row button {
        width: 32px !important;
        height: 32px !important;
        padding: 0 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    /* Vertical center all client row cells */
    .client-row td {
        vertical-align: middle !important;
    }

    .client-row.table-primary td:hover {
        background-color: transparent !important;
        color: #fff !important;
    }

    /* Override Bootstrap table hover variables */
    .table-hover .client-row.table-primary:hover > td,
    .table-hover .client-row.table-primary:hover > th {
        background-color: #f6b93b !important;
        color: #fff !important;
    }

    /* Fix modal positioning */
    .modal {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        z-index: 1055 !important;
        width: 100% !important;
        height: 100% !important;
        overflow-x: hidden !important;
        overflow-y: auto !important;
        outline: 0 !important;
    }

    .modal-dialog {
        position: relative !important;
        width: auto !important;
        margin: 1.75rem auto !important;
        max-width: 500px !important;
        pointer-events: none !important;
    }

    .modal-dialog-centered {
        display: flex !important;
        align-items: center !important;
        min-height: calc(100% - 3.5rem) !important;
    }

    .modal-content {
        position: relative !important;
        display: flex !important;
        flex-direction: column !important;
        width: 100% !important;
        pointer-events: auto !important;
        background-color: #fff !important;
        background-clip: padding-box !important;
        border: 1px solid rgba(0,0,0,.2) !important;
        border-radius: 0.3rem !important;
        outline: 0 !important;
    }

    .modal.fade .modal-dialog {
        transition: transform .3s ease-out !important;
        transform: translate(0, -50px) !important;
    }

    .modal.show .modal-dialog {
        transform: none !important;
    }

    /* Ensure modal backdrop appears correctly */
    .modal-backdrop {
        z-index: 1050 !important;
    }

    /* Specific styling for quantity modal */
    #quantityModal .modal-dialog {
        margin: 1.75rem auto !important;
        max-width: 400px !important;
    }

    /* Ensure modal appears on top of everything */
    .modal.show {
        display: block !important;
    }

    /* Product animation styles */
    .product-item {
        transition: all 0.3s ease-in-out;
        opacity: 0;
        transform: translateY(-10px);
    }

    .product-item.show {
        opacity: 1;
        transform: translateY(0);
    }

    .product-item.removing {
        opacity: 0;
        transform: translateY(-10px);
        max-height: 0;
        margin: 0;
        padding: 0;
        overflow: hidden;
    }

    /* Loading animation for add/remove buttons */
    .btn-loading {
        position: relative;
        pointer-events: none;
    }

    .btn-loading::after {
        content: "";
        position: absolute;
        width: 16px;
        height: 16px;
        top: 50%;
        left: 50%;
        margin-left: -8px;
        margin-top: -8px;
        border: 2px solid transparent;
        border-top-color: #ffffff;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    .btn-loading.btn-outline-primary::after {
        border-top-color: #007bff;
    }

    .btn-loading.btn-outline-danger::after {
        border-top-color: #dc3545;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Cart item animations */
    .cart-item {
        transition: all 0.3s ease-in-out;
        opacity: 0;
        transform: translateX(-20px);
    }

    .cart-item.show {
        opacity: 1;
        transform: translateX(0);
    }

    .cart-item.removing {
        opacity: 0;
        transform: translateX(20px);
        max-height: 0;
        margin: 0;
        padding: 0;
        overflow: hidden;
    }

    /* Smooth transitions for quantity updates */
    .quantity-updating {
        opacity: 0.6;
        pointer-events: none;
    }

    .quantity-updating input {
        background-color: #f8f9fa;
        transform: scale(1.05);
        transition: all 0.2s ease;
    }

    .cart-item input[type="number"] {
        transition: all 0.2s ease;
        border: 1px solid #dee2e6;
    }

    .cart-item input[type="number"]:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        transform: scale(1.02);
    }

    /* Cart item styling improvements */
    .cart-item {
        border-left: 4px solid #007bff !important;
        background-color: #f8f9fa;
        border-radius: 8px !important;
    }

    .cart-item:hover {
        background-color: #e9ecef;
        border-left-color: #0056b3 !important;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0,123,255,0.15);
    }

    .cart-item .fw-bold.text-primary {
        font-size: 0.9rem;
        line-height: 1.2;
    }

    .cart-item .text-muted {
        font-size: 0.8rem;
    }

    .cart-item .text-success {
        font-size: 0.85rem;
    }

    .cart-item .text-info {
        font-size: 0.75rem;
    }

</style>
<script>
$(document).ready(function() {
    let currentStep = 1;
    const totalSteps = 5;
    let selectedProducts = [];
    let currentProductsPage = 1;
    let currentStoreSearch = '';
    let currentProductSearch = '';
    let searchTimeout;
    let variantSearchTimeout = {};
    let currentVariantForModal = null;

    // Client search variables
    let selectedClient = null;
    let currentClientsPage = 1;
    let clientSearchTimeout;

    // Access clients selection variables
    let selectedAccessClients = {}; // Object to store selected access clients by store
    let currentClientSearch = {
        firstName: '',
        middleName: '',
        lastName: '',
        phone: '',
        email: ''
    };

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

    // Show products loading indicator
    function showProductsLoading() {
        $('#products-container').html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="text-muted mb-0">Loading products...</p>
                <small class="text-muted">Please wait while we fetch the latest products</small>
            </div>
        `);
    }

    // Show search loading indicator
    function showSearchLoading() {
        // Add loading spinner to search inputs
        $('#store-search, #product-search').each(function() {
            const $input = $(this);
            const $group = $input.closest('.input-group');

            // Remove existing loading indicator
            $group.find('.search-loading').remove();

            // Add loading indicator
            $group.append(`
                <span class="input-group-text search-loading">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">Searching...</span>
                    </div>
                </span>
            `);
        });
    }

    // Hide search loading indicator
    function hideSearchLoading() {
        $('.search-loading').remove();
    }

    // Search variants with loading indicator and debouncing
    window.searchVariants = function(productId, searchTerm) {
        // Clear existing timeout for this product
        if (variantSearchTimeout[productId]) {
            clearTimeout(variantSearchTimeout[productId]);
        }

        // Show loading indicator in the variants table
        $(`#variants-table-${productId} tbody`).html(`
            <tr>
                <td colspan="5" class="text-center py-3">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">Searching...</span>
                    </div>
                    <span class="ms-2 text-muted">Searching variants...</span>
                </td>
            </tr>
        `);

        // Debounce the search
        variantSearchTimeout[productId] = setTimeout(function() {
            loadProductVariants(productId, 1, searchTerm);
        }, 300); // 300ms delay
    };

    // Load products
    function loadProducts(page = 1, storeSearch = '', productSearch = '') {
        // Show loading indicator
        showProductsLoading();

        $.ajax({
            url: '<?php echo e(route("ecom-orders-custom-add.products")); ?>',
            type: 'GET',
            data: {
                page: page,
                store_search: storeSearch,
                product_search: productSearch,
                per_page: 15
            },
            success: function(response) {
                hideSearchLoading(); // Hide search loading indicator
                if (response.success) {
                    displayProducts(response.data);
                    updateProductsPagination(response.pagination);
                }
            },
            error: function() {
                hideSearchLoading(); // Hide search loading indicator
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
                                <div class="d-flex align-items-center gap-2">
                                    <small class="text-muted">${product.productStore}</small>
                                    <span class="badge bg-info text-white">${product.productType || 'N/A'}</span>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleProductVariants(${product.id})">
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
        // Show loading indicator for variants
        $(`#variants-${productId}`).html(`
            <div class="text-center py-3">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Loading variants...</span>
                </div>
                <span class="ms-2 text-muted">Loading variants...</span>
            </div>
        `);

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
                    <input type="text" class="form-control" id="variant-search-${productId}" placeholder="Search variants..." oninput="searchVariants(${productId}, this.value)">
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
                        <button type="button" class="btn btn-sm btn-outline-info me-1" onclick="viewVariant(${variant.id})">
                            <i class="mdi mdi-eye"></i>
                        </button>
                        <button type="button" class="btn btn-sm ${isInCart ? 'btn-success' : 'btn-primary'} variant-action-btn"
                                data-variant-id="${variant.id}"
                                data-product-id="${productId}"
                                onclick="toggleVariantInCart(${variant.id}, ${productId})"
                                oncontextmenu="removeVariantFromCart(${variant.id}, ${productId}); return false;"
                                ${variant.stocksAvailable === 0 ? 'disabled' : ''}
                                title="${isInCart ? 'Left click: Add quantity | Right click: Remove from cart' : 'Add to cart'}">
                            <i class="mdi ${isInCart ? 'mdi-plus' : 'mdi-plus'}"></i>
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
        const actionBtn = $(`.variant-action-btn[data-variant-id="${variantId}"]`);

        // Add loading state to button
        actionBtn.addClass('btn-loading').prop('disabled', true);

        if (existingIndex > -1) {
            // Item already in cart - add quantity instead of removing
            $.ajax({
                url: '<?php echo e(route("ecom-orders-custom-add.variants")); ?>',
                type: 'GET',
                data: { product_id: productId },
                success: function(response) {
                    if (response.success) {
                        const variant = response.data.find(v => v.id === variantId);
                        if (variant) {
                            const maxOrderPerTransaction = parseInt(variant.maxOrderPerTransaction) || 1;
                            const availableStock = parseInt(variant.stocksAvailable) || 0;
                            const currentQuantity = selectedProducts[existingIndex].quantity;
                            const newQuantity = currentQuantity + 1;

                            // Check stock availability first
                            if (newQuantity > availableStock) {
                                showStockExceededModal(variant.ecomVariantName, newQuantity, availableStock);
                                actionBtn.removeClass('btn-loading').prop('disabled', false);
                            } else if (newQuantity <= maxOrderPerTransaction) {
                                // Increase quantity
                                selectedProducts[existingIndex].quantity = newQuantity;
                                updateQuantityDisplay(existingIndex, newQuantity);
                                updateCartSummary();
                                $('#selectedProducts').val(JSON.stringify(selectedProducts));
                                actionBtn.removeClass('btn-loading').prop('disabled', false);
                            } else {
                                // Show modal if trying to exceed max quantity
                                showQuantityExceededModal(variant.ecomVariantName, currentQuantity, maxOrderPerTransaction);
                                actionBtn.removeClass('btn-loading').prop('disabled', false);
                            }
                        }
                    } else {
                        actionBtn.removeClass('btn-loading').prop('disabled', false);
                    }
                },
                error: function() {
                    actionBtn.removeClass('btn-loading').prop('disabled', false);
                }
            });
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
                            // Check maxOrderPerTransaction and stock availability
                            const maxOrderPerTransaction = parseInt(variant.maxOrderPerTransaction) || 1;
                            const availableStock = parseInt(variant.stocksAvailable) || 0;

                            // Check if stock is available
                            if (availableStock === 0) {
                                showStockExceededModal(variant.ecomVariantName, 1, 0);
                                actionBtn.removeClass('btn-loading').prop('disabled', false);
                            } else if (maxOrderPerTransaction === 1) {
                                // Add directly to cart with quantity 1
                                selectedProducts.push({
                                    variantId: variant.id,
                                    variantName: variant.ecomVariantName,
                                    price: variant.ecomVariantPrice,
                                    productId: productId,
            productName: variant.productName,
            productStore: variant.product ? variant.product.productStore : 'Unknown Store',
            productType: variant.product ? variant.product.productType : 'Unknown',
            quantity: 1,
            maxOrderPerTransaction: variant.maxOrderPerTransaction || 1,
                                    stocksAvailable: variant.stocksAvailable || 0
                                });

                                setTimeout(() => {
                                    updateCartDisplay();
                                    updateVariantButtonState(variantId, productId, true);
                                    actionBtn.removeClass('btn-loading').prop('disabled', false);
                                }, 300);
                            } else {
                                // Show quantity selection modal
                                actionBtn.removeClass('btn-loading').prop('disabled', false);
                                showQuantityModal(variant, productId);
                            }
                        }
                    } else {
                        actionBtn.removeClass('btn-loading').prop('disabled', false);
                    }
                },
                error: function() {
                    actionBtn.removeClass('btn-loading').prop('disabled', false);
                }
            });
        }
    };

    // Remove variant from cart (for right-click functionality)
    window.removeVariantFromCart = function(variantId, productId) {
        const existingIndex = selectedProducts.findIndex(item => item.variantId === variantId);
        const actionBtn = $(`.variant-action-btn[data-variant-id="${variantId}"]`);

        if (existingIndex > -1) {
            // Add loading state to button
            actionBtn.addClass('btn-loading').prop('disabled', true);

            // Remove from cart with animation
            setTimeout(() => {
                selectedProducts.splice(existingIndex, 1);

                // Update data-item-index for remaining items
                $('.cart-item').each(function(i) {
                    $(this).attr('data-item-index', i);
                    $(this).find('.remove-item-btn').attr('data-item-index', i);
                    $(this).find('button[onclick*="updateQuantity"]').attr('onclick', `updateQuantity(${i}, -1)`);
                    $(this).find('button[onclick*="updateQuantity"]').last().attr('onclick', `updateQuantity(${i}, 1)`);
                    $(this).find('input').attr('onchange', `updateQuantity(${i}, 0, this.value)`);
                });

                updateCartDisplay();
                updateVariantButtonState(variantId, productId, false);
                actionBtn.removeClass('btn-loading').prop('disabled', false);
            }, 300);
        }
    };

    // Update variant button state
    function updateVariantButtonState(variantId, productId, isInCart) {
        const actionBtn = $(`.variant-action-btn[data-variant-id="${variantId}"]`);
        const icon = actionBtn.find('i');

        if (isInCart) {
            actionBtn.removeClass('btn-primary').addClass('btn-success');
            icon.removeClass('mdi-plus').addClass('mdi-plus');
            actionBtn.attr('title', 'Left click: Add quantity | Right click: Remove from cart');
        } else {
            actionBtn.removeClass('btn-success').addClass('btn-primary');
            icon.removeClass('mdi-plus').addClass('mdi-plus');
            actionBtn.attr('title', 'Add to cart');
        }
    }

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
                const maxOrderPerTransaction = parseInt(item.maxOrderPerTransaction) || 1;
                const isAtMaxLimit = item.quantity >= maxOrderPerTransaction;

                html += `
                    <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded cart-item" data-item-index="${index}">
                        <div>
                            <small class="fw-bold text-primary">${item.productName || 'Unknown Product'}</small><br>
                            <small class="fw-bold">${item.variantName}</small><br>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <small class="text-muted">
                                    <i class="mdi mdi-store me-1"></i>${item.productStore || 'Unknown Store'}
                                </small>
                                <span class="badge bg-info text-white" style="font-size: 0.7em;">${item.productType || 'N/A'}</span>
                            </div>
                            <small class="text-success fw-bold">₱${parseFloat(item.price).toFixed(2)}</small>
                            <br><small class="text-info" title="Maximum quantity allowed for this product">Max: ${maxOrderPerTransaction}</small>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="input-group input-group-sm me-2" style="width: 100px;">
                                <button class="btn btn-outline-secondary" type="button" onclick="updateQuantity(${index}, -1)" ${item.quantity <= 1 ? 'disabled' : ''}>-</button>
                                <input type="number" class="form-control text-center" value="${item.quantity}" min="1" max="${maxOrderPerTransaction}" onchange="updateQuantity(${index}, 0, this.value)" oninput="validateQuantityInput(this, ${maxOrderPerTransaction})">
                                <button class="btn btn-outline-secondary" type="button" onclick="updateQuantity(${index}, 1)" ${isAtMaxLimit ? 'disabled' : ''}>+</button>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger remove-item-btn" data-item-index="${index}" onclick="removeFromCart(${index})">
                                <i class="mdi mdi-delete"></i>
                            </button>
                        </div>
                    </div>
                `;
            });

            cartContainer.html(html);

            // Animate cart items
            setTimeout(() => {
                cartContainer.find('.cart-item').addClass('show');
            }, 50);

            const totalItems = selectedProducts.reduce((sum, item) => sum + item.quantity, 0);
            const uniqueProducts = new Set(selectedProducts.map(item => item.productId)).size;

            $('#total-items').text(totalItems);
            $('#total-amount').text('₱' + totalAmount.toFixed(2));

            // Add unique products count if more than 1
            if (uniqueProducts > 1) {
                $('#total-items').append(` <small class="text-muted">(${uniqueProducts} products)</small>`);
            }

            cartSummary.show();
        }

        // Update hidden input
        $('#selectedProducts').val(JSON.stringify(selectedProducts));
    }

    // Update quantity
    window.updateQuantity = function(index, change, newValue = null) {
        const product = selectedProducts[index];
        const maxOrderPerTransaction = parseInt(product.maxOrderPerTransaction) || 1;
        let newQuantity;

        if (newValue !== null) {
            newQuantity = Math.max(1, parseInt(newValue));
        } else {
            newQuantity = Math.max(1, product.quantity + change);
        }

        // Validate against stock availability first
        const availableStock = parseInt(product.stocksAvailable) || 0;
        if (newQuantity > availableStock) {
            const variantName = selectedProducts[index].variantName;
            showStockExceededModal(variantName, newQuantity, availableStock);
            return;
        }

        // Validate against maxOrderPerTransaction
        if (newQuantity > maxOrderPerTransaction) {
            const variantName = selectedProducts[index].variantName;
            showQuantityExceededModal(variantName, product.quantity, maxOrderPerTransaction);
            return;
        }

        // Update the quantity without re-rendering the entire cart
        selectedProducts[index].quantity = newQuantity;
        updateQuantityDisplay(index, newQuantity);
        updateCartSummary();

        // Update hidden input
        $('#selectedProducts').val(JSON.stringify(selectedProducts));
    };

    // Update only the quantity display for a specific item
    function updateQuantityDisplay(index, newQuantity) {
        const cartItem = $(`.cart-item[data-item-index="${index}"]`);
        const input = cartItem.find('input[type="number"]');
        const maxOrderPerTransaction = parseInt(selectedProducts[index].maxOrderPerTransaction) || 1;

        // Add subtle animation to the input
        input.addClass('quantity-updating');

        // Update the input value
        input.val(newQuantity);

        // Update button states
        const minusBtn = cartItem.find('button:first');
        const plusBtn = cartItem.find('button:last');

        minusBtn.prop('disabled', newQuantity <= 1);
        plusBtn.prop('disabled', newQuantity >= maxOrderPerTransaction);

        // Remove animation class after a short delay
        setTimeout(() => {
            input.removeClass('quantity-updating');
        }, 200);
    }

    // Update only the cart summary (total items and amount)
    function updateCartSummary() {
        const totalAmount = selectedProducts.reduce((sum, item) => sum + (parseFloat(item.price) * item.quantity), 0);
        const totalItems = selectedProducts.reduce((sum, item) => sum + item.quantity, 0);
        const uniqueProducts = new Set(selectedProducts.map(item => item.productId)).size;

        // Animate the summary update
        $('#total-items').fadeOut(100, function() {
            $(this).text(totalItems);
            if (uniqueProducts > 1) {
                $(this).append(` <small class="text-muted">(${uniqueProducts} products)</small>`);
            }
            $(this).fadeIn(100);
        });

        $('#total-amount').fadeOut(100, function() {
            $(this).text('₱' + totalAmount.toFixed(2));
            $(this).fadeIn(100);
        });
    }

    // Remove from cart
    window.removeFromCart = function(index) {
        const cartItem = $(`.cart-item[data-item-index="${index}"]`);
        const removeBtn = $(`.remove-item-btn[data-item-index="${index}"]`);

        // Add loading state to remove button
        removeBtn.addClass('btn-loading').prop('disabled', true);

        // Animate removal
        cartItem.addClass('removing');

        setTimeout(() => {
            selectedProducts.splice(index, 1);

            // Update data-item-index for remaining items
            $('.cart-item').each(function(i) {
                $(this).attr('data-item-index', i);
                $(this).find('.remove-item-btn').attr('data-item-index', i);
                $(this).find('button[onclick*="updateQuantity"]').attr('onclick', `updateQuantity(${i}, -1)`);
                $(this).find('button[onclick*="updateQuantity"]').last().attr('onclick', `updateQuantity(${i}, 1)`);
                $(this).find('input').attr('onchange', `updateQuantity(${i}, 0, this.value)`);
            });

            // Update cart summary
            updateCartSummary();

            // Update hidden input
            $('#selectedProducts').val(JSON.stringify(selectedProducts));

            // If no items left, show empty state
            if (selectedProducts.length === 0) {
                $('#cart-container').html(`
                    <div class="text-center py-3 text-muted">
                        <i class="mdi mdi-cart-outline" style="font-size: 48px;"></i>
                        <p class="mt-2">No products selected</p>
                        <small>Select products from the left panel to add them to your order</small>
                    </div>
                `);
                $('#cart-summary').hide();
            }
        }, 300);
    };

    // Validate quantity input in real-time
    window.validateQuantityInput = function(input, maxOrderPerTransaction) {
        const value = parseInt(input.value);
        const cartItem = $(input).closest('.cart-item');
        const index = parseInt(cartItem.attr('data-item-index'));

        // Remove any existing validation classes
        input.classList.remove('is-invalid', 'is-valid');

        if (isNaN(value) || value < 1) {
            input.classList.add('is-invalid');
        } else if (value > maxOrderPerTransaction) {
            input.classList.add('is-invalid');
        } else {
            input.classList.add('is-valid');
            // Update the quantity if it's valid and different from current
            if (selectedProducts[index] && selectedProducts[index].quantity !== value) {
                selectedProducts[index].quantity = value;
                updateQuantityDisplay(index, value);
                updateCartSummary();
                $('#selectedProducts').val(JSON.stringify(selectedProducts));
            }
        }
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
        const availableStock = parseInt(variant.stocksAvailable) || 0;
        const maxAllowed = Math.min(maxOrderPerTransaction, availableStock);

        $('#quantityInput').attr('max', maxAllowed).attr('min', 1).val(1);
        $('#maxQuantityDisplay').text(maxAllowed);

        // Update the form text to show both limits
        const formText = availableStock < maxOrderPerTransaction
            ? `Limited by stock: ${availableStock} (max order limit: ${maxOrderPerTransaction})`
            : `Maximum quantity per transaction: ${maxOrderPerTransaction}`;
        $('#quantityInput').next('.form-text').html(formText);

        // Clear any previous validation
        $('#quantityInput').removeClass('is-invalid');
        $('#quantityError').text('').hide();

        // Show modal
        $('#quantityModal').modal('show');
    }

    // Handle quantity modal confirmation
    $('#confirmQuantityBtn').click(function() {
        if (!currentVariantForModal || !currentVariantForModal.variant) {
            return;
        }

        const quantity = parseInt($('#quantityInput').val());
        const maxOrderPerTransaction = parseInt(currentVariantForModal.variant.maxOrderPerTransaction) || 1;

        // Validate quantity
        if (isNaN(quantity) || quantity < 1) {
            $('#quantityInput').addClass('is-invalid');
            $('#quantityError').text('Please enter a valid quantity (minimum 1).').show();
            return;
        }

        // Check stock availability first
        const availableStock = parseInt(currentVariantForModal.variant.stocksAvailable) || 0;
        if (quantity > availableStock) {
            $('#quantityModal').modal('hide');
            showStockExceededModal(currentVariantForModal.variant.ecomVariantName, quantity, availableStock);
            return;
        }

        if (quantity > maxOrderPerTransaction) {
            $('#quantityModal').modal('hide');
            showQuantityExceededModal(currentVariantForModal.variant.ecomVariantName, 1, maxOrderPerTransaction);
            return;
        }

        // Add to cart with selected quantity
        selectedProducts.push({
            variantId: currentVariantForModal.variant.id,
            variantName: currentVariantForModal.variant.ecomVariantName,
            price: currentVariantForModal.variant.ecomVariantPrice,
            productId: currentVariantForModal.productId,
            productName: currentVariantForModal.variant.productName,
            productStore: currentVariantForModal.variant.product ? currentVariantForModal.variant.product.productStore : 'Unknown Store',
            productType: currentVariantForModal.variant.product ? currentVariantForModal.variant.product.productType : 'Unknown',
            quantity: quantity,
            maxOrderPerTransaction: currentVariantForModal.variant.maxOrderPerTransaction || 1,
            stocksAvailable: currentVariantForModal.variant.stocksAvailable || 0
        });

        // Close modal and update display with animation
        $('#quantityModal').modal('hide');

        setTimeout(() => {
            updateCartDisplay();
            updateVariantButtonState(currentVariantForModal.variant.id, currentVariantForModal.productId, true);
        }, 300);

        currentVariantForModal = null;
    });

    // Handle quantity input validation
    $('#quantityInput').on('input', function() {
        const quantity = parseInt($(this).val());
        const maxOrderPerTransaction = parseInt(currentVariantForModal?.variant?.maxOrderPerTransaction) || 1;

        // Check if currentVariantForModal is valid
        if (!currentVariantForModal || !currentVariantForModal.variant) {
            return;
        }

        $(this).removeClass('is-invalid');
        $('#quantityError').text('').hide();

        // Validate quantity
        if (isNaN(quantity) || quantity < 1) {
            $(this).addClass('is-invalid');
            $('#quantityError').text('Please enter a valid quantity (minimum 1).').show();
        } else if (quantity > maxOrderPerTransaction) {
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
            // Show pagination info
            const startItem = pagination.from || 0;
            const endItem = pagination.to || 0;
            const totalItems = pagination.total || 0;

            html += `
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <small class="text-muted">
                        Showing ${startItem} to ${endItem} of ${totalItems} products
                    </small>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary"
                                onclick="loadProductsPage(${pagination.current_page - 1})"
                                ${pagination.current_page === 1 ? 'disabled' : ''}>
                            <i class="mdi mdi-chevron-left"></i> Previous
                        </button>
                        <button type="button" class="btn btn-outline-primary"
                                onclick="loadProductsPage(${pagination.current_page + 1})"
                                ${pagination.current_page === pagination.last_page ? 'disabled' : ''}>
                            Next <i class="mdi mdi-chevron-right"></i>
                        </button>
                    </div>
                </div>
            `;

            // Page numbers with smart pagination
            const currentPage = pagination.current_page;
            const lastPage = pagination.last_page;
            const showPages = 5; // Show max 5 page numbers

            let startPage = Math.max(1, currentPage - Math.floor(showPages / 2));
            let endPage = Math.min(lastPage, startPage + showPages - 1);

            // Adjust start page if we're near the end
            if (endPage - startPage < showPages - 1) {
                startPage = Math.max(1, endPage - showPages + 1);
            }

            html += '<nav aria-label="Products pagination"><ul class="pagination pagination-sm justify-content-center">';

            // First page and ellipsis
            if (startPage > 1) {
                html += `<li class="page-item">
                    <a class="page-link" href="#" onclick="loadProductsPage(1)">1</a>
                </li>`;
                if (startPage > 2) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
            }

            // Page numbers
            for (let i = startPage; i <= endPage; i++) {
                html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="loadProductsPage(${i})">${i}</a>
                </li>`;
            }

            // Last page and ellipsis
            if (endPage < lastPage) {
                if (endPage < lastPage - 1) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
                html += `<li class="page-item">
                    <a class="page-link" href="#" onclick="loadProductsPage(${lastPage})">${lastPage}</a>
                </li>`;
            }

            html += '</ul></nav>';
        }

        paginationContainer.html(html);
    }

    // Load products page
    window.loadProductsPage = function(page) {
        currentProductsPage = page;
        // Show loading indicator for pagination
        showProductsLoading();
        loadProducts(page, currentStoreSearch, currentProductSearch);
    };

    // Dynamic search function with debouncing
    function performSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            currentStoreSearch = $('#store-search').val();
            currentProductSearch = $('#product-search').val();
            currentProductsPage = 1;

            // Show loading indicator for search
            showSearchLoading();
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
        // Hide all steps first
        $('.wizard-step').addClass('d-none');

        // Show target step with slight delay for smooth transition
        setTimeout(() => {
            $(`#step-${step}`).removeClass('d-none');
        }, 50);

        // Update progress bar
        const progress = (step / totalSteps) * 100;
        $('#wizard-progress').css('width', progress + '%');

        // Update navigation buttons
        $('#prev-btn').toggle(step > 1);
        $('#next-btn').toggle(step < totalSteps);
        $('#submit-btn').toggle(step === totalSteps);

        currentStep = step;

        // Load clients when step 2 is shown
        if (step === 2) {
            showClientsLoading();
            loadClients(1, currentClientSearch);
        }

        // Load access products when step 3 is shown
        if (step === 3) {
            loadAccessProductsStores();
        }
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
                showErrorAlertModal('Please select at least one product before proceeding.');
                isValid = false;
            }
        }

        // Special validation for step 2 - check if client is selected
        if (step === 2) {
            if (!selectedClient) {
                showErrorAlertModal('Please select a client before proceeding.');
                isValid = false;
            }
        }

        // Special validation for step 3 - check if access clients are selected for each store
        if (step === 3) {
            // Get access products (products with type 'access')
            const accessProducts = selectedProducts.filter(product =>
                product.productType === 'access' || product.productType === 'Access'
            );

            if (accessProducts.length > 0) {
                // Get unique stores from access products
                const uniqueStores = [...new Set(accessProducts.map(product => product.productStore))];

                // Check if each store has a selected access client
                const storesWithoutClients = [];
                uniqueStores.forEach(store => {
                    if (!selectedAccessClients[store]) {
                        storesWithoutClients.push(store);
                    }
                });

                if (storesWithoutClients.length > 0) {
                    const storeList = storesWithoutClients.join(', ');
                    showErrorAlertModal(`Please select access clients for the following stores: ${storeList}`);
                    isValid = false;
                }
            }
        }

        return isValid;
    }

    // Previous button click
    $('#prev-btn').click(function() {
        showStep(currentStep - 1);
    });

    // Next button click
    $('#next-btn').click(function(e) {
        e.preventDefault();
        e.stopPropagation();

        console.log('Next button clicked, current step:', currentStep);

        if (validateStep(currentStep)) {
            showStep(currentStep + 1);
        }

        return false;
    });

    // Add New Client button click
    $('#add-new-client-btn').click(function() {
        $('#addNewClientModal').modal('show');
        // Clear form and validation when modal opens
        clearNewClientForm();
    });

    // Clear new client form
    function clearNewClientForm() {
        try {
            const form = $('#addNewClientForm')[0];
            if (form) {
                form.reset();
            }
            $('#addNewClientForm .form-control').removeClass('is-valid is-invalid');
            $('#addNewClientForm .invalid-feedback').text('');
        } catch (error) {
            console.log('Error clearing form:', error);
            // Fallback: manually clear all form fields
            $('#newClientFirstName, #newClientMiddleName, #newClientLastName, #newClientPhoneNumber, #newClientEmailAddress').val('');
            $('#addNewClientForm .form-control').removeClass('is-valid is-invalid');
            $('#addNewClientForm .invalid-feedback').text('');
        }
    }

    // Phone number validation function
    function isValidPhoneNumber(phone) {
        // Remove any spaces or dashes
        const cleanPhone = phone.replace(/[\s-]/g, '');

        // Check for 09XXXXXXXXX format (11 digits starting with 09)
        const format09 = /^09\d{9}$/;

        // Check for +63XXXXXXXXX format (13 characters starting with +63)
        const formatPlus63 = /^\+63\d{9}$/;

        // Check for 63XXXXXXXXX format (12 characters starting with 63)
        const format63 = /^63\d{9}$/;

        return format09.test(cleanPhone) || formatPlus63.test(cleanPhone) || format63.test(cleanPhone);
    }

    // Email validation function
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // Check phone number uniqueness
    function checkPhoneNumberUniqueness(phoneNumber) {
        return new Promise(function(resolve, reject) {
            $.ajax({
                url: '<?php echo e(route("ecom-orders-custom-add.check-client-phone")); ?>',
                type: 'GET',
                data: { phone_number: phoneNumber },
                success: function(response) {
                    resolve(response);
                },
                error: function() {
                    resolve({ success: false, exists: false });
                }
            });
        });
    }

    // Validate individual field
    async function validateNewClientField(fieldId, value, fieldName) {
        const field = $(`#${fieldId}`);
        const feedback = field.siblings('.invalid-feedback');

        // Clear previous validation
        field.removeClass('is-valid is-invalid');
        feedback.text('');

        // Required field validation
        if (!value.trim()) {
            field.addClass('is-invalid');
            feedback.text(`${fieldName} is required.`);
            return false;
        }

        // Specific field validations
        if (fieldId === 'newClientPhoneNumber') {
            if (!isValidPhoneNumber(value)) {
                field.addClass('is-invalid');
                feedback.text('Phone number must be in format: 09XXXXXXXXX, +63XXXXXXXXX, or 63XXXXXXXXX');
                return false;
            } else {
                // Check phone number uniqueness
                const uniquenessResult = await checkPhoneNumberUniqueness(value);
                if (uniquenessResult.success && uniquenessResult.exists) {
                    field.addClass('is-invalid');
                    feedback.text('This phone number already exists in the database.');
                    return false;
                }
            }
        }

        if (fieldId === 'newClientEmailAddress') {
            if (!isValidEmail(value)) {
                field.addClass('is-invalid');
                feedback.text('Please enter a valid email address.');
                return false;
            }
        }

        // If we get here, field is valid
        field.addClass('is-valid');
        return true;
    }

    // Real-time validation for all new client form fields
    $('#newClientFirstName').on('input', function() {
        validateNewClientField('newClientFirstName', $(this).val(), 'First Name');
    });

    $('#newClientMiddleName').on('input', function() {
        validateNewClientField('newClientMiddleName', $(this).val(), 'Middle Name');
    });

    $('#newClientLastName').on('input', function() {
        validateNewClientField('newClientLastName', $(this).val(), 'Last Name');
    });

    $('#newClientPhoneNumber').on('input', function() {
        validateNewClientField('newClientPhoneNumber', $(this).val(), 'Phone Number');
    });

    $('#newClientEmailAddress').on('input', function() {
        validateNewClientField('newClientEmailAddress', $(this).val(), 'Email Address');
    });

    // Save New Client button click
    $('#saveNewClientBtn').click(async function() {
        const saveBtn = $(this);
        const originalText = saveBtn.html();

        // Disable button and show loading
        saveBtn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin me-1"></i>Saving...');

        // Validate all fields
        let isFormValid = true;
        const fields = [
            { id: 'newClientFirstName', name: 'First Name' },
            { id: 'newClientMiddleName', name: 'Middle Name' },
            { id: 'newClientLastName', name: 'Last Name' },
            { id: 'newClientPhoneNumber', name: 'Phone Number' },
            { id: 'newClientEmailAddress', name: 'Email Address' }
        ];

        // Validate each field
        for (const field of fields) {
            const value = $(`#${field.id}`).val();
            const isValid = await validateNewClientField(field.id, value, field.name);
            if (!isValid) {
                isFormValid = false;
            }
        }

        // If form is valid, proceed with save
        if (isFormValid) {
            const formData = {
                clientFirstName: $('#newClientFirstName').val().trim(),
                clientMiddleName: $('#newClientMiddleName').val().trim(),
                clientLastName: $('#newClientLastName').val().trim(),
                clientPhoneNumber: $('#newClientPhoneNumber').val().trim(),
                clientEmailAddress: $('#newClientEmailAddress').val().trim()
            };

            // Send AJAX request to save client
            $.ajax({
                url: '<?php echo e(route("ecom-orders-custom-add.save-client")); ?>',
                type: 'POST',
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                beforeSend: function() {
                    console.log('Sending AJAX request to save client:', formData);
                },
                success: function(response) {
                    console.log('AJAX success response:', response);
                    if (response.success) {
                        // Clear form and close modal
                        clearNewClientForm();
                        $('#addNewClientModal').modal('hide');

                        // Show success notification
                        $('#clientSuccessModal').modal('show');

                        // Refresh clients list and auto-select the new client
                        setTimeout(function() {
                            loadClients(1, {});
                            // Auto-select the new client after a short delay
                            setTimeout(function() {
                                selectClient(response.client.id, response.client.fullName, response.client.clientPhoneNumber, response.client.clientEmailAddress);
                            }, 1000);
                        }, 500);
                    } else {
                        // Handle validation errors from server
                        if (response.errors) {
                            Object.keys(response.errors).forEach(function(field) {
                                const input = $(`#newClient${field.charAt(0).toUpperCase() + field.slice(1)}`);
                                input.addClass('is-invalid');
                                input.siblings('.invalid-feedback').text(response.errors[field][0]);
                            });
                        } else {
                            alert('Error: ' + (response.message || 'Failed to save client'));
                        }
                    }
                },
                error: function(xhr) {
                    console.log('AJAX error:', xhr);
                    let errorMessage = 'Failed to save client';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        // Handle validation errors
                        const errors = xhr.responseJSON.errors;
                        Object.keys(errors).forEach(function(field) {
                            const input = $(`#newClient${field.charAt(0).toUpperCase() + field.slice(1)}`);
                            input.addClass('is-invalid');
                            input.siblings('.invalid-feedback').text(errors[field][0]);
                        });
                        return; // Don't show alert for validation errors
                    }
                    alert('Error: ' + errorMessage);
                },
                complete: function() {
                    // Restore button state
                    saveBtn.prop('disabled', false).html(originalText);
                }
            });
        } else {
            // Restore button state if validation failed
            saveBtn.prop('disabled', false).html(originalText);
        }
    });

    // Form submission
    $('#order-wizard-form').submit(function(e) {
        e.preventDefault();

        // Only allow form submission on the final step
        if (currentStep !== totalSteps) {
            console.log('Form submission prevented - not on final step');
            return false;
        }

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
                    showErrorAlertModal(response.message || 'Failed to create order');
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
                    showErrorAlertModal('Please fix the validation errors');
                } else {
                    showErrorAlertModal(response?.message || 'An error occurred while creating the order');
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

    // Show quantity exceeded modal
    function showQuantityExceededModal(variantName, currentQuantity, maxQuantity) {
        $('#quantityExceededMessage').text(`Cannot add more items. Maximum quantity allowed is ${maxQuantity}.`);
        $('#quantityExceededModal').modal('show');
    }

    // Show stock exceeded modal
    function showStockExceededModal(variantName, requestedQuantity, availableStock) {
        $('#stockExceededMessage').text(`Cannot add ${requestedQuantity} items. Only ${availableStock} items available in stock.`);
        $('#stockExceededModal').modal('show');
    }

    // ===== CLIENT SEARCH FUNCTIONS =====

    // Show clients loading indicator
    function showClientsLoading() {
        $('#clients-container').html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="text-muted mb-0">Loading clients...</p>
                <small class="text-muted">Please wait while we fetch the clients</small>
            </div>
        `);
    }

    // Load clients
    function loadClients(page = 1, searchParams = {}) {
        showClientsLoading();

        const searchQuery = Object.values(searchParams).filter(val => val.trim() !== '').join(' ');

        $.ajax({
            url: '<?php echo e(route("ecom-orders-custom-add.clients")); ?>',
            type: 'GET',
            data: {
                page: page,
                search: searchQuery,
                per_page: 20
            },
            success: function(response) {
                if (response.success) {
                    displayClients(response.data);
                    updateClientsPagination(response.pagination);
                } else {
                    $('#clients-container').html('<div class="text-center py-3 text-danger">Error loading clients</div>');
                }
            },
            error: function() {
                $('#clients-container').html('<div class="text-center py-3 text-danger">Error loading clients</div>');
            }
        });
    }

    // Display clients
    function displayClients(clients) {
        if (clients.length === 0) {
            $('#clients-container').html('<div class="text-center py-3 text-muted">No clients found</div>');
            return;
        }

        let html = '<div class="table-responsive"><table class="table table-hover">';
        html += `
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
        `;

        clients.forEach(function(client) {
            const fullName = `${client.clientFirstName || ''} ${client.clientMiddleName || ''} ${client.clientLastName || ''}`.trim();
            const isSelected = selectedClient && selectedClient.id === client.id;

            html += `
                <tr class="client-row ${isSelected ? 'table-primary' : ''}" data-client-id="${client.id}">
                    <td>
                        <div class="fw-bold">${fullName}</div>
                    </td>
                    <td>
                        <span class="badge bg-info">${client.clientPhoneNumber || 'N/A'}</span>
                    </td>
                    <td>
                        <span class="badge bg-secondary">${client.clientEmailAddress || 'N/A'}</span>
                    </td>
                    <td>
                        <button class="btn btn-sm ${isSelected ? 'btn-success' : 'btn-outline-primary'}"
                                onclick="selectClient(${client.id}, '${fullName.replace(/'/g, "\\'")}', '${client.clientPhoneNumber || ''}', '${client.clientEmailAddress || ''}')"
                                title="${isSelected ? 'Selected' : 'Select Client'}">
                            <i class="mdi ${isSelected ? 'mdi-check' : 'mdi-account-plus'}"></i>
                        </button>
                    </td>
                </tr>
            `;
        });

        html += '</tbody></table></div>';
        $('#clients-container').html(html);
    }

    // Select/Unselect client
    window.selectClient = function(clientId, fullName, phone, email) {
        const selectedRow = $(`.client-row[data-client-id="${clientId}"]`);
        const isCurrentlySelected = selectedClient && selectedClient.id === clientId;

        if (isCurrentlySelected) {
            // Unselect the client
            selectedRow.removeClass('table-primary');
            const selectBtn = selectedRow.find('button');
            selectBtn.removeClass('btn-success').addClass('btn-outline-primary');
            selectBtn.find('i').removeClass('mdi-check').addClass('mdi-account-plus');
            selectBtn.attr('title', 'Select Client');

            // Clear selected client
            selectedClient = null;
            $('#selectedClient').val('');
        } else {
            // Remove previous selection
            $('.client-row').removeClass('table-primary');
            $('.client-row button').removeClass('btn-success').addClass('btn-outline-primary');
            $('.client-row button i').removeClass('mdi-check').addClass('mdi-account-plus');
            $('.client-row button').attr('title', 'Select Client');

            // Add selection to current row
            selectedRow.addClass('table-primary');
            const selectBtn = selectedRow.find('button');
            selectBtn.removeClass('btn-outline-primary').addClass('btn-success');
            selectBtn.find('i').removeClass('mdi-account-plus').addClass('mdi-check');
            selectBtn.attr('title', 'Selected');

            // Store selected client
            selectedClient = {
                id: clientId,
                fullName: fullName,
                phone: phone,
                email: email
            };


            // Update hidden input
            $('#selectedClient').val(JSON.stringify(selectedClient));
        }
    };

    // Update clients pagination
    function updateClientsPagination(pagination) {
        const paginationContainer = $('#clients-pagination');
        let html = '';

        if (pagination.last_page > 1) {
            // Previous button
            html += `<li class="page-item ${pagination.current_page === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="loadClientsPage(${pagination.current_page - 1})">Previous</a>
            </li>`;

            // Page numbers
            for (let i = 1; i <= pagination.last_page; i++) {
                html += `<li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="loadClientsPage(${i})">${i}</a>
                </li>`;
            }

            // Next button
            html += `<li class="page-item ${pagination.current_page === pagination.last_page ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="loadClientsPage(${pagination.current_page + 1})">Next</a>
            </li>`;
        }

        paginationContainer.html(html);
    }

    // Load clients page
    window.loadClientsPage = function(page) {
        currentClientsPage = page;
        showClientsLoading();
        loadClients(page, currentClientSearch);
    };

    // Client search function with debouncing
    function performClientSearch() {
        clearTimeout(clientSearchTimeout);
        clientSearchTimeout = setTimeout(function() {
            currentClientSearch = {
                firstName: $('#client-first-name-search').val(),
                middleName: $('#client-middle-name-search').val(),
                lastName: $('#client-last-name-search').val(),
                phone: $('#client-phone-search').val(),
                email: $('#client-email-search').val()
            };
            currentClientsPage = 1;
            showClientsLoading();
            loadClients(1, currentClientSearch);
        }, 300); // 300ms delay
    }

    // Clear client search
    function clearClientSearch() {
        $('#client-first-name-search, #client-middle-name-search, #client-last-name-search, #client-phone-search, #client-email-search').val('');
        currentClientSearch = {
            firstName: '',
            middleName: '',
            lastName: '',
            phone: '',
            email: ''
        };
        currentClientsPage = 1;
        showClientsLoading();
        loadClients(1, currentClientSearch);
    }

    // Bind client search events
    $('#client-first-name-search, #client-middle-name-search, #client-last-name-search, #client-phone-search, #client-email-search').on('input', performClientSearch);
    $('#search-clients-btn').click(performClientSearch);
    $('#clear-client-search-btn').click(clearClientSearch);

    // ===== END CLIENT SEARCH FUNCTIONS =====






    // Show client notification function
    function showClientNotification(message, type = 'success') {
        const notificationArea = $('#client-notification-area');
        const notificationMessage = $('#client-notification-message');
        const alertDiv = notificationArea.find('.alert');

        // Set message
        notificationMessage.text(message);

        // Set alert type
        alertDiv.removeClass('alert-success alert-danger alert-warning alert-info');
        if (type === 'error') {
            alertDiv.addClass('alert-danger');
            alertDiv.find('i').removeClass('mdi-check-circle').addClass('mdi-alert-circle');
        } else {
            alertDiv.addClass('alert-success');
            alertDiv.find('i').removeClass('mdi-alert-circle').addClass('mdi-check-circle');
        }

        // Show notification
        notificationArea.show();

        // Auto-hide after 5 seconds for success messages
        if (type === 'success') {
            setTimeout(function() {
                notificationArea.fadeOut();
            }, 5000);
        }
    }

    // Load access clients by store for step 3
    function loadAccessProductsStores() {
        const accessProducts = selectedProducts.filter(product => {
            // Check if product type is 'access' - you may need to adjust this based on your data structure
            return product.productType === 'access' || product.productType === 'Access';
        });

        if (accessProducts.length === 0) {
            $('#no-access-products').show();
            $('#access-stores-container').hide();
            return;
        }

        // Get unique stores from access products
        const uniqueStores = [...new Set(accessProducts.map(product => product.productStore || 'Unknown Store'))];

        // Display stores and load clients for each store
        let storesHtml = '';
        const colorClasses = ['store-card-primary', 'store-card-success', 'store-card-warning', 'store-card-danger', 'store-card-info', 'store-card-secondary'];

        uniqueStores.forEach((store, index) => {
            const colorClass = colorClasses[index % colorClasses.length];
            const storeId = store.replace(/\s+/g, '-').toLowerCase();
            storesHtml += `
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card ${colorClass}">
                            <div class="card-header text-white">
                                <h6 class="card-title mb-0">
                                    <i class="mdi mdi-store me-2"></i>${store}
                                </h6>
                            </div>
                            <div class="card-body">
                                <!-- Store Actions -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-outline-primary btn-sm" id="create-access-${storeId}">
                                                <i class="mdi mdi-account-plus me-1"></i>Create New Access
                                            </button>
                                            <button type="button" class="btn btn-outline-info btn-sm" id="view-products-${storeId}" title="View Products for this Store">
                                                <i class="mdi mdi-package-variant me-1"></i>View Products
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">
                                                <i class="mdi mdi-magnify"></i>
                                            </span>
                                            <input type="text" class="form-control" id="search-${storeId}" placeholder="Search by name, phone, or email...">
                                        </div>
                                    </div>
                                </div>

                                <!-- Access Clients Table -->
                                <div class="mb-3">
                                    <label class="form-label">Access Clients for ${store}</label>
                                    <div id="access-clients-${storeId}" class="table-responsive">
                                        <div class="text-center py-3">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                            <p class="mt-2 text-muted">Loading access clients...</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pagination -->
                                <div id="pagination-${storeId}" class="d-flex justify-content-between align-items-center">
                                    <!-- Pagination will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        $('#access-stores-container').html(storesHtml);
        $('#no-access-products').hide();
        $('#access-stores-container').show();

        // Load access clients for each store
        uniqueStores.forEach(store => {
            loadAccessClientsForStore(store);
        });

        // Add event listeners for search and create access buttons
        uniqueStores.forEach(store => {
            const storeId = store.replace(/\s+/g, '-').toLowerCase();

            // Search functionality
            $(`#search-${storeId}`).on('input', function() {
                const searchTerm = $(this).val();
                clearTimeout(window[`searchTimeout_${storeId}`]);
                window[`searchTimeout_${storeId}`] = setTimeout(() => {
                    loadAccessClientsForStore(store, 1, searchTerm);
                }, 500);
            });

            // Create access button
            $(`#create-access-${storeId}`).on('click', function() {
                showCreateAccessModal(store);
            });

            // View products button
            $(`#view-products-${storeId}`).on('click', function() {
                showStoreProductsModal(store);
            });
        });
    }

    // Store pagination and search data
    let storeData = {}; // Store data for each store

    // Load access clients for a specific store
    function loadAccessClientsForStore(store, page = 1, search = '', autoSelectId = null) {
        const storeId = store.replace(/\s+/g, '-').toLowerCase();
        const containerId = `#access-clients-${storeId}`;
        const paginationId = `#pagination-${storeId}`;

        // Show loading state
        showStoreLoading(containerId);

        $.ajax({
            url: '<?php echo e(route("ecom-orders-custom-add.access-clients")); ?>',
            type: 'GET',
            data: {
                productStore: store,
                page: page,
                per_page: 20,
                search: search
            },
            success: function(response) {
                if (response.success && response.data) {
                    // Store the data for this store
                    storeData[storeId] = {
                        clients: response.data,
                        currentPage: page,
                        totalPages: response.last_page || 1,
                        search: search,
                        total: response.total || response.data.length
                    };

                    displayAccessClients(containerId, response.data, store, autoSelectId);
                    displayPagination(paginationId, storeId, page, response.last_page || 1);
                } else {
                    $(containerId).html(`
                        <div class="text-center py-3">
                            <i class="mdi mdi-account-off text-muted" style="font-size: 2rem;"></i>
                            <p class="text-muted mt-2">No access clients found for ${store}</p>
                        </div>
                    `);
                    $(paginationId).html('');
                }
            },
            error: function() {
                $(containerId).html(`
                    <div class="text-center py-3">
                        <i class="mdi mdi-alert-circle text-danger" style="font-size: 2rem;"></i>
                        <p class="text-danger mt-2">Error loading access clients</p>
                    </div>
                `);
                $(paginationId).html('');
            }
        });
    }

    // Show loading state for store
    function showStoreLoading(containerId) {
        $(containerId).html(`
            <div class="text-center py-3">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading access clients...</p>
            </div>
        `);
    }

    // Display pagination
    function displayPagination(containerId, storeId, currentPage, totalPages) {
        if (totalPages <= 1) {
            $(containerId).html('');
            return;
        }

        let paginationHtml = `
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Page ${currentPage} of ${totalPages}
                </small>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
        `;

        // Previous button
        if (currentPage > 1) {
            paginationHtml += `
                <li class="page-item">
                    <a class="page-link" href="#" onclick="changeStorePage('${storeId}', ${currentPage - 1})">
                        <i class="mdi mdi-chevron-left"></i>
                    </a>
                </li>
            `;
        }

        // Page numbers
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, currentPage + 2);

        for (let i = startPage; i <= endPage; i++) {
            paginationHtml += `
                <li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="changeStorePage('${storeId}', ${i})">${i}</a>
                </li>
            `;
        }

        // Next button
        if (currentPage < totalPages) {
            paginationHtml += `
                <li class="page-item">
                    <a class="page-link" href="#" onclick="changeStorePage('${storeId}', ${currentPage + 1})">
                        <i class="mdi mdi-chevron-right"></i>
                    </a>
                </li>
            `;
        }

        paginationHtml += `
                    </ul>
                </nav>
            </div>
        `;

        $(containerId).html(paginationHtml);
    }

    // Change store page
    window.changeStorePage = function(storeId, page) {
        const store = storeId.replace(/-/g, ' ');
        const searchTerm = storeData[storeId] ? storeData[storeId].search : '';
        loadAccessClientsForStore(store, page, searchTerm);
    }


    // Display access clients in a table
    function displayAccessClients(containerId, clients, store, autoSelectId = null) {
        if (clients.length === 0) {
            $(containerId).html('<div class="text-center py-3 text-muted">No access clients available for this store</div>');
            return;
        }

        let html = `
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
        `;

        clients.forEach(function(client) {
            const fullName = `${client.clientFirstName || ''} ${client.clientMiddleName || ''} ${client.clientLastName || ''}`.trim();
            const isSelected = selectedAccessClients && selectedAccessClients[store] && selectedAccessClients[store].id === client.id;

            html += `
                <tr class="access-client-row ${isSelected ? 'table-primary' : ''}" data-client-id="${client.id}" data-store="${store}">
                    <td>
                        <div class="fw-bold">${fullName}</div>
                    </td>
                    <td>
                        <span class="badge bg-info">${client.clientPhoneNumber || 'N/A'}</span>
                    </td>
                    <td>
                        <span class="badge bg-secondary">${client.clientEmailAddress || 'N/A'}</span>
                    </td>
                    <td>
                        <button class="btn btn-sm ${isSelected ? 'btn-success' : 'btn-outline-primary'}"
                                onclick="selectAccessClient(${client.id}, '${fullName.replace(/'/g, "\\'")}', '${client.clientPhoneNumber || ''}', '${client.clientEmailAddress || ''}', '${store}')"
                                title="${isSelected ? 'Selected' : 'Select Access Client'}">
                            <i class="mdi ${isSelected ? 'mdi-check' : 'mdi-account-plus'}"></i>
                        </button>
                    </td>
                </tr>
            `;
        });

        html += '</tbody></table>';
        $(containerId).html(html);

        // Auto-select the newly added client if autoSelectId is provided
        if (autoSelectId) {
            const targetClient = clients.find(client => client.id == autoSelectId);
            if (targetClient) {
                const fullName = `${targetClient.clientFirstName || ''} ${targetClient.clientMiddleName || ''} ${targetClient.clientLastName || ''}`.trim();
                selectAccessClient(autoSelectId, fullName, targetClient.clientPhoneNumber || '', targetClient.clientEmailAddress || '', store);
            }
        }
    }

    // Select access client function
    window.selectAccessClient = function(clientId, fullName, phone, email, store) {
        const selectedRow = $(`.access-client-row[data-client-id="${clientId}"][data-store="${store}"]`);
        const isCurrentlySelected = selectedAccessClients[store] && selectedAccessClients[store].id === clientId;

        if (isCurrentlySelected) {
            // Deselect client
            selectedRow.removeClass('table-primary');
            selectedRow.find('button').removeClass('btn-success').addClass('btn-outline-primary');
            selectedRow.find('button i').removeClass('mdi-check').addClass('mdi-account-plus');
            selectedRow.find('button').attr('title', 'Select Access Client');

            // Remove from selected access clients
            delete selectedAccessClients[store];
        } else {
            // Remove previous selection for this store
            $(`.access-client-row[data-store="${store}"]`).removeClass('table-primary');
            $(`.access-client-row[data-store="${store}"] button`).removeClass('btn-success').addClass('btn-outline-primary');
            $(`.access-client-row[data-store="${store}"] button i`).removeClass('mdi-check').addClass('mdi-account-plus');
            $(`.access-client-row[data-store="${store}"] button`).attr('title', 'Select Access Client');

            // Select new client
            selectedRow.addClass('table-primary');
            selectedRow.find('button').removeClass('btn-outline-primary').addClass('btn-success');
            selectedRow.find('button i').removeClass('mdi-account-plus').addClass('mdi-check');
            selectedRow.find('button').attr('title', 'Selected');

            // Store selected access client
            selectedAccessClients[store] = {
                id: clientId,
                fullName: fullName,
                phone: phone,
                email: email,
                store: store
            };
        }
    };

    // Show store products modal
    function showStoreProductsModal(storeName) {
        // Update modal title
        $('#storeProductsModalLabel').text(`Products for ${storeName}`);

        // Show loading state
        $('#storeProductsContent').html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading products...</p>
            </div>
        `);

        // Show modal
        $('#storeProductsModal').modal('show');

        // Filter products for this store
        const storeProducts = selectedProducts.filter(product =>
            product.productStore === storeName
        );

        if (storeProducts.length === 0) {
            $('#storeProductsContent').html(`
                <div class="text-center py-4">
                    <i class="mdi mdi-package-variant-closed text-muted" style="font-size: 3rem;"></i>
                    <h6 class="text-muted mt-3">No Products Selected</h6>
                    <p class="text-muted">No products have been selected for ${storeName} in step 1.</p>
                </div>
            `);
            return;
        }

        // Generate products HTML
        let productsHtml = `
            <div class="mb-3">
                <h6>Selected Products (${storeProducts.length})</h6>
            </div>
        `;

        storeProducts.forEach((product, index) => {
            const totalPrice = (parseFloat(product.price) * product.quantity).toFixed(2);

            // Color coding similar to step 1
            const colorClasses = [
                'border-primary bg-light',
                'border-success bg-light',
                'border-warning bg-light',
                'border-danger bg-light',
                'border-info bg-light',
                'border-secondary bg-light'
            ];
            const badgeClasses = [
                'bg-primary',
                'bg-success',
                'bg-warning',
                'bg-danger',
                'bg-info',
                'bg-secondary'
            ];

            const colorIndex = index % colorClasses.length;
            const borderClass = colorClasses[colorIndex];
            const badgeClass = badgeClasses[colorIndex];

            productsHtml += `
                <div class="border rounded p-3 mb-3 ${borderClass}">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">${product.productName}</h6>
                        <span class="badge ${badgeClass} text-white">${product.productType}</span>
                    </div>
                    <p class="text-muted mb-2">${product.variantName}</p>
                    <div class="d-flex justify-content-between">
                        <div>
                            <span class="text-muted">Qty: </span><strong>${product.quantity}</strong>
                            <span class="text-muted ms-3">Price: </span><strong>₱${product.price}</strong>
                        </div>
                        <div class="text-success fw-bold">₱${totalPrice}</div>
                    </div>
                </div>
            `;
        });

        productsHtml += `
            <div class="row mt-3">
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="mdi mdi-information me-2"></i>
                        <strong>Total Products:</strong> ${storeProducts.length} |
                        <strong>Total Amount:</strong> ₱${storeProducts.reduce((sum, product) => sum + (parseFloat(product.price) * product.quantity), 0).toFixed(2)}
                    </div>
                </div>
            </div>
        `;

        $('#storeProductsContent').html(productsHtml);
    }

    // Show create access modal
    function showCreateAccessModal(storeName) {
        // Update modal title
        $('#createAccessModalLabel').text(`Create New Access for ${storeName}`);

        // Store the current store name for validation
        $('#createAccessModal').data('store-name', storeName);

        // Clear form
        $('#createAccessForm')[0].reset();

        // Remove any validation classes
        $('#createAccessForm .form-control').removeClass('is-invalid is-valid');
        $('#createAccessForm .invalid-feedback').text('').hide();

        // Show modal
        $('#createAccessModal').modal('show');
    }

    // Validate email format
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // Validate password strength
    function validatePasswordStrength(password) {
        const minLength = 8;
        const hasUpperCase = /[A-Z]/.test(password);
        const hasLowerCase = /[a-z]/.test(password);
        const hasNumbers = /\d/.test(password);
        const hasSpecialChar = /[!@#$%^&*(),.?":{}|<>]/.test(password);

        return {
            isValid: password.length >= minLength && hasUpperCase && hasLowerCase && hasNumbers && hasSpecialChar,
            minLength: password.length >= minLength,
            hasUpperCase,
            hasLowerCase,
            hasNumbers,
            hasSpecialChar
        };
    }

    // Normalize phone number to standard format (09661123355)
    function normalizePhoneNumber(phoneNumber) {
        if (!phoneNumber) return '';

        // Remove all non-digit characters except +
        let cleaned = phoneNumber.replace(/[^\d+]/g, '');

        // Handle different formats and convert to 09 format
        if (cleaned.startsWith('+63')) {
            // +639661123355 -> 09661123355
            return '0' + cleaned.substring(3);
        } else if (cleaned.startsWith('63') && cleaned.length === 12) {
            // 639661123355 -> 09661123355
            return '0' + cleaned.substring(2);
        } else if (cleaned.startsWith('09') && cleaned.length === 11) {
            // 09661123355 -> 09661123355 (already correct)
            return cleaned;
        } else if (cleaned.startsWith('9') && cleaned.length === 10) {
            // 9661123355 -> 09661123355
            return '0' + cleaned;
        }

        return cleaned;
    }

    // Check if phone number already exists
    function checkPhoneExists(phoneNumber, storeName) {
        const normalizedPhone = normalizePhoneNumber(phoneNumber);

        return new Promise((resolve) => {
            $.ajax({
                url: '<?php echo e(route("ecom-orders-custom-add.check-phone")); ?>',
                type: 'GET',
                data: {
                    phone: normalizedPhone,
                    store: storeName
                },
                success: function(response) {
                    resolve(response.exists || false);
                },
                error: function() {
                    resolve(false);
                }
            });
        });
    }

    // Validate form field
    function validateField(fieldName, value, storeName) {
        const field = $(`#access${fieldName.charAt(0).toUpperCase() + fieldName.slice(1)}`);
        const feedback = field.siblings('.invalid-feedback');

        let isValid = true;
        let errorMessage = '';

        switch(fieldName) {
            case 'phoneNumber':
                if (!value.trim()) {
                    errorMessage = 'Phone number is required';
                    isValid = false;
                } else {
                    const normalizedPhone = normalizePhoneNumber(value.trim());
                    // Check if normalized phone is valid (should be 11 digits starting with 09)
                    if (normalizedPhone.length !== 11 || !normalizedPhone.startsWith('09')) {
                        errorMessage = 'Please enter a valid phone number (e.g., +639661123355, 639661123355, 09661123355)';
                        isValid = false;
                    }
                }
                break;

            case 'email':
                if (!value.trim()) {
                    errorMessage = 'Email address is required';
                    isValid = false;
                } else if (!isValidEmail(value.trim())) {
                    errorMessage = 'Please enter a valid email address';
                    isValid = false;
                }
                break;

            case 'firstName':
            case 'middleName':
            case 'lastName':
                if (!value.trim()) {
                    errorMessage = `${fieldName.charAt(0).toUpperCase() + fieldName.slice(1)} is required`;
                    isValid = false;
                } else if (value.trim().length < 2) {
                    errorMessage = `${fieldName.charAt(0).toUpperCase() + fieldName.slice(1)} must be at least 2 characters`;
                    isValid = false;
                }
                break;

            case 'password':
                const passwordValidation = validatePasswordStrength(value);
                if (!value.trim()) {
                    errorMessage = 'Password is required';
                    isValid = false;
                } else if (!passwordValidation.isValid) {
                    errorMessage = 'Password must be at least 8 characters with uppercase, lowercase, number, and special character';
                    isValid = false;
                }
                break;

            case 'confirmPassword':
                const password = $('#accessPassword').val();
                if (!value.trim()) {
                    errorMessage = 'Confirm password is required';
                    isValid = false;
                } else if (value !== password) {
                    errorMessage = 'Passwords do not match';
                    isValid = false;
                }
                break;
        }

        // Show/hide validation
        if (isValid) {
            field.removeClass('is-invalid').addClass('is-valid');
            feedback.text('').hide();
        } else {
            field.removeClass('is-valid').addClass('is-invalid');
            feedback.text(errorMessage).show();
        }

        return isValid;
    }

    // Real-time validation for phone number
    $('#accessPhoneNumber').on('blur', async function() {
        const phoneNumber = $(this).val();
        const storeName = $('#createAccessModal').data('store-name');

        if (phoneNumber.trim()) {
            const exists = await checkPhoneExists(phoneNumber, storeName);
            if (exists) {
                $(this).removeClass('is-valid').addClass('is-invalid');
                $(this).siblings('.invalid-feedback').text('Phone number already exists for this store').show();
            } else {
                validateField('phoneNumber', phoneNumber, storeName);
            }
        }
    });

    // Real-time validation for other fields
    $('#accessEmail, #accessFirstName, #accessMiddleName, #accessLastName, #accessPassword, #accessConfirmPassword').on('blur', function() {
        const fieldName = $(this).attr('name');
        const value = $(this).val();
        const storeName = $('#createAccessModal').data('store-name');

        validateField(fieldName, value, storeName);
    });

    // Real-time validation for confirm password
    $('#accessConfirmPassword').on('input', function() {
        const confirmPassword = $(this).val();
        const password = $('#accessPassword').val();
        const storeName = $('#createAccessModal').data('store-name');

        validateField('confirmPassword', confirmPassword, storeName);
    });

    // Form submission validation
    $('#saveAccessBtn').on('click', async function() {
        const storeName = $('#createAccessModal').data('store-name');
        let isFormValid = true;

        // Validate all fields
        const fields = ['phoneNumber', 'email', 'firstName', 'middleName', 'lastName', 'password', 'confirmPassword'];

        for (const field of fields) {
            const value = $(`#access${field.charAt(0).toUpperCase() + field.slice(1)}`).val();
            const isValid = validateField(field, value, storeName);
            if (!isValid) isFormValid = false;
        }

        // Check phone number existence
        const phoneNumber = $('#accessPhoneNumber').val();
        if (phoneNumber.trim()) {
            const exists = await checkPhoneExists(phoneNumber, storeName);
            if (exists) {
                $('#accessPhoneNumber').removeClass('is-valid').addClass('is-invalid');
                $('#accessPhoneNumber').siblings('.invalid-feedback').text('Phone number already exists for this store').show();
                isFormValid = false;
            }
        }

        if (isFormValid) {
            // Show loading state
            $(this).prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin me-1"></i>Saving...');

            // Prepare form data
            const formData = {
                phoneNumber: normalizePhoneNumber($('#accessPhoneNumber').val()),
                email: $('#accessEmail').val(),
                firstName: $('#accessFirstName').val(),
                middleName: $('#accessMiddleName').val(),
                lastName: $('#accessLastName').val(),
                password: $('#accessPassword').val(),
                store: storeName
            };

            // Save access client
            try {
                const response = await $.ajax({
                    url: '<?php echo e(route("ecom-orders-custom-add.save-access")); ?>',
                    type: 'POST',
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });

                if (response.success) {
                    // Show success notification
                    showSuccessNotification('Access client added successfully!');

                    // Close modal
                    $('#createAccessModal').modal('hide');

                    // Reload the access clients table for this store and auto-select the new user
                    const storeId = storeName.replace(/\s+/g, '-').toLowerCase();
                    loadAccessClientsForStore(storeName, 1, '', response.data.id);
                } else {
                    showErrorNotification(response.message || 'Failed to save access client');
                }
            } catch (error) {
                console.error('Error saving access client:', error);
                showErrorNotification('An error occurred while saving the access client');
            } finally {
                // Reset button state
                $(this).prop('disabled', false).html('Save Access');
            }
        }
    });

    // Show success notification
    function showSuccessNotification(message) {
        // Create notification element
        const notification = $(`
            <div class="alert alert-success alert-dismissible fade show position-fixed"
                 style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;" role="alert">
                <i class="mdi mdi-check-circle me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `);

        // Add to body
        $('body').append(notification);

        // Auto remove after 3 seconds
        setTimeout(() => {
            notification.alert('close');
        }, 3000);
    }

    // Show error notification
    function showErrorNotification(message) {
        // Create notification element
        const notification = $(`
            <div class="alert alert-danger alert-dismissible fade show position-fixed"
                 style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;" role="alert">
                <i class="mdi mdi-alert-circle me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `);

        // Add to body
        $('body').append(notification);

        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.alert('close');
        }, 5000);
    }

    // Show error alert modal
    function showErrorAlertModal(message) {
        $('#errorAlertMessage').text(message);
        $('#errorAlertModal').modal('show');
    }

    // Initialize
    showStep(1);
    showProductsLoading(); // Show loading indicator
    loadProducts(); // Load products on page load
});
</script>

<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\btc-check\resources\views/ecommerce/orders/custom-add.blade.php ENDPATH**/ ?>