@extends('layouts.master')

@section('title')
    Add New Order
@endsection

@section('css')
<!-- Toastr -->
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    .shipping-suggestion-item {
        padding: 10px 12px;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        margin-bottom: 8px;
        cursor: pointer;
        background: #fff;
        transition: all 0.2s ease;
    }
    .shipping-suggestion-item:hover {
        background: #f0f7ff;
        border-color: #556ee6;
        box-shadow: 0 2px 4px rgba(85, 110, 230, 0.15);
    }
    .shipping-suggestion-item:last-child {
        margin-bottom: 0;
    }
    .shipping-suggestion-item .suggestion-name {
        font-weight: 600;
        color: #495057;
    }
    .shipping-suggestion-item .suggestion-address {
        font-size: 0.85rem;
        color: #74788d;
        margin-top: 4px;
    }
    .shipping-suggestion-item .suggestion-label {
        font-size: 0.7rem;
        background: #556ee6;
        color: #fff;
        padding: 2px 6px;
        border-radius: 3px;
        margin-left: 8px;
    }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Ecommerce
        @endslot
        @slot('li_2')
            Orders
        @endslot
        @slot('li_2_link')
            {{ route('ecom-orders') }}
        @endslot
        @slot('title')
            Add New Order
        @endslot
    @endcomponent

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
                                <small class="text-muted">Step 1: Products</small>
                                <small class="text-muted">Step 2: Client</small>
                                <small class="text-muted">Step 3: Logins</small>
                                <small class="text-muted">Step 4: Shipping</small>
                                <small class="text-muted">Step 5: Discounts</small>
                                <small class="text-muted">Step 6: Affiliates</small>
                                <small class="text-muted">Step 7: Finalize</small>
                            </div>
                        </div>
                    </div>

                    <!-- Wizard Form -->
                    <form id="order-wizard-form" method="POST" novalidate>
                        @csrf
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

                                <!-- Available Packages Row -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <div class="card border-info">
                                            <div class="card-header bg-info text-white rounded-top">
                                                <h6 class="card-title mb-0" style="color: #fff !important;">
                                                    <i class="mdi mdi-package-variant-closed me-2"></i>Available Packages
                                                    <small class="ms-2">(Select a package to add all its products)</small>
                                                </h6>
                                            </div>
                                            <div class="card-body">
                                                <!-- Search Bar -->
                                                <div class="mb-3">
                                                    <div class="input-group">
                                                        <span class="input-group-text">
                                                            <i class="mdi mdi-magnify"></i>
                                                        </span>
                                                        <input type="text" class="form-control" id="package-search" placeholder="Search packages by name...">
                                                    </div>
                                                </div>

                                                <!-- Packages List -->
                                                <div id="packages-container" style="max-height: 400px; overflow-y: auto;">
                                                    <div class="text-center py-3">
                                                        <div class="spinner-border text-info" role="status">
                                                            <span class="visually-hidden">Loading...</span>
                                                        </div>
                                                        <p class="mt-2 text-muted">Loading packages...</p>
                                                    </div>
                                                </div>

                                                <!-- Pagination -->
                                                <nav aria-label="Packages pagination" class="mt-3">
                                                    <ul class="pagination pagination-sm justify-content-center" id="packages-pagination">
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
                                <!-- Hidden input for active package (if using package pricing) -->
                                <input type="hidden" id="activePackageData" name="activePackageData" value="">
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
                                            <form id="addNewClientForm" novalidate>
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
                                                            <input type="text" class="form-control" id="newClientPhoneNumber" name="clientPhoneNumber" placeholder="09225512233" maxlength="11" required>
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
            <h5 class="mb-3">Shipping & Delivery</h5>

            <!-- Access Products Section (No Shipping Required) -->
            <div id="access-products-section" class="mb-4" style="display: none;">
                <div class="card border-primary">
                    <div class="card-header bg-primary">
                        <h6 class="card-title mb-0 text-white">
                            <i class="mdi mdi-key-variant me-2 text-white"></i>Access Products (No Shipping Required)
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3"><small>These products are digital/access-based and do not require shipping.</small></p>
                        <div id="access-products-list">
                            <!-- Access products will be dynamically loaded here -->
                        </div>
                        <div class="border-top pt-3 mt-3">
                            <div class="d-flex justify-content-between fw-bold">
                                <span>Access Products Subtotal:</span>
                                <span id="access-products-subtotal" class="text-primary">₱0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Shipping Type Selection -->
            <div id="shipping-type-section" class="mb-4" style="display: none;">
                <div class="card border-secondary">
                    <div class="card-header bg-secondary">
                        <h6 class="card-title mb-0 text-white">
                            <i class="mdi mdi-truck-delivery me-2 text-white"></i>Shipping Type
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="shipping_type" class="form-label">Select Shipping Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="shipping_type" name="shipping_type">
                                    <option value="">Select Shipping Type</option>
                                    <option value="Regular">Regular Shipping</option>
                                    <option value="Cash on Delivery">Cash on Delivery (COD)</option>
                                    <option value="Cash on Pickup">Cash on Pickup</option>
                                </select>
                                <small class="text-muted mt-1 d-block">Choose how you want the order to be shipped and paid.</small>
                            </div>
                            <div class="col-md-6">
                                <div id="shipping-type-info" class="mt-4">
                                    <!-- Shipping type description will appear here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Shipping Method Selection (shown when multiple methods available) -->
            <div id="shipping-method-section" class="mb-4" style="display: none;">
                <div class="card border-primary">
                    <div class="card-header bg-primary">
                        <h6 class="card-title mb-0 text-white">
                            <i class="mdi mdi-truck-check me-2 text-white"></i>Select Shipping Method
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="text-secondary mb-3">Multiple shipping methods are available. Please select one to apply to all products:</p>
                        <div id="shipping-methods-list" class="row g-3">
                            <!-- Shipping methods will be dynamically loaded here -->
                        </div>
                        <div id="selected-shipping-method-info" class="mt-3" style="display: none;">
                            <div class="alert alert-success mb-0">
                                <i class="mdi mdi-check-circle me-2"></i>
                                <strong>Selected:</strong> <span id="selected-method-name"></span>
                                <span class="float-end">
                                    <span class="badge bg-light text-dark" id="selected-method-price"></span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ship Products Stores -->
            <div id="ship-stores-container">
                <!-- Stores will be dynamically loaded here -->
            </div>

            <!-- No Ship Products Message -->
            <div id="no-ship-products" class="text-center py-5" style="display: none;">
                <i class="mdi mdi-truck-fast display-4 text-muted mb-3"></i>
                <h6 class="text-muted">No Shipping Required</h6>
                <p class="text-muted">All selected products are access-type and do not require shipping. You can proceed to the next step.</p>
            </div>

            <!-- Shipping Error Banner -->
            <div id="shipping-error-banner" class="alert alert-danger" style="display: none;">
                <div class="d-flex align-items-center">
                    <i class="mdi mdi-alert-circle-outline me-2" style="font-size: 1.5rem;"></i>
                    <div>
                        <strong>Cannot Proceed:</strong> One or more products do not have shipping methods configured.
                        Please <a href="{{ route('ecom-shipping') }}" target="_blank" class="alert-link">configure shipping settings</a> first,
                        then refresh this page.
                    </div>
                </div>
            </div>

            <!-- Shipping Address Section -->
            <div class="row mt-4" id="shipping-address-section">
                <div class="col-12">
                    <div class="card border-info">
                        <div class="card-header bg-info text-white">
                            <h6 class="card-title mb-0">
                                <i class="mdi mdi-map-marker me-2"></i>Shipping Address
                            </h6>
                        </div>
                        <div class="card-body">
                            <!-- Who Will Receive the Order -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="text-primary mb-3">
                                        <i class="mdi mdi-account me-2"></i>Who Will Receive the Order
                                    </h6>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="shipping_first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="shipping_first_name" name="shipping_first_name" required>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="shipping_middle_name" class="form-label">Middle Name</label>
                                    <input type="text" class="form-control" id="shipping_middle_name" name="shipping_middle_name">
                                    <div class="invalid-feedback"></div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="shipping_last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="shipping_last_name" name="shipping_last_name" required>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="shipping_phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="shipping_phone" name="shipping_phone" placeholder="09XXXXXXXXX" maxlength="11" required>
                                    <div class="invalid-feedback"></div>
                                    <small class="text-muted">Format: 09XXXXXXXXX (11 digits)</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="shipping_email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="shipping_email" name="shipping_email" required>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <!-- Address Autocomplete Suggestions -->
                                <div class="col-12" id="shippingAddressSuggestions" style="display: none;">
                                    <div class="alert alert-info py-2 mb-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="mdi mdi-lightbulb-on-outline me-2"></i>
                                            <strong class="text-dark">Saved addresses found! Click to auto-fill:</strong>
                                        </div>
                                        <div id="suggestionsList"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Address Details -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="text-primary mb-3">
                                        <i class="mdi mdi-home me-2"></i>Address Details
                                    </h6>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="shipping_house_number" class="form-label">House Number</label>
                                    <input type="text" class="form-control" id="shipping_house_number" name="shipping_house_number">
                                    <div class="invalid-feedback"></div>
                                </div>

                                <div class="col-md-8 mb-3">
                                    <label for="shipping_street" class="form-label">Street</label>
                                    <input type="text" class="form-control" id="shipping_street" name="shipping_street">
                                    <div class="invalid-feedback"></div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="shipping_zone" class="form-label">Zone (if any)</label>
                                    <input type="number" class="form-control" id="shipping_zone" name="shipping_zone" min="0" placeholder="Enter zone number">
                                    <div class="invalid-feedback"></div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="shipping_barangay" class="form-label">Barangay <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="shipping_barangay" name="shipping_barangay" placeholder="Enter barangay name" required>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="shipping_province" class="form-label">Province <span class="text-danger">*</span></label>
                                    <select class="form-select" id="shipping_province" name="shipping_province" required>
                                        <option value="">Select Province</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="shipping_municipality" class="form-label">Municipality/City <span class="text-danger">*</span></label>
                                    <select class="form-select" id="shipping_municipality" name="shipping_municipality" required disabled>
                                        <option value="">Select Municipality/City</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="shipping_zip_code" class="form-label">Zip Code <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="shipping_zip_code" name="shipping_zip_code" required>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="shipping_landmark" class="form-label">Landmark</label>
                                    <textarea class="form-control" id="shipping_landmark" name="shipping_landmark" rows="2" placeholder="Nearby landmarks or additional directions"></textarea>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Shipping Calculation Section -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card border-success">
                        <div class="card-header bg-success text-white">
                            <h6 class="card-title mb-0">
                                <i class="mdi mdi-calculator me-2"></i>Shipping & Total Calculation
                            </h6>
                        </div>
                        <div class="card-body">
                            <!-- Loading State -->
                            <div id="shipping-calculation-loading" class="text-center py-5">
                                <div class="spinner-border text-success mb-3" role="status" style="width: 3rem; height: 3rem;">
                                    <span class="visually-hidden">Calculating...</span>
                                </div>
                                <h6 class="text-success">Calculating Order Total...</h6>
                                <p class="text-muted">Please wait while we calculate your shipping costs and total price</p>
                            </div>

                            <!-- Calculation Results -->
                            <div id="shipping-calculation-results" style="display: none;">
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6 class="text-success mb-3">Complete Order Breakdown</h6>
                                        <div id="complete-breakdown">
                                            <!-- Complete breakdown will be populated here -->
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <!-- Product Summary Accordion -->
                                        <div class="card border-info mb-3">
                                            <div class="card-header bg-info text-white" data-bs-toggle="collapse" data-bs-target="#productSummaryCollapse" aria-expanded="false" aria-controls="productSummaryCollapse" style="cursor: pointer;">
                                                <h6 class="mb-0">
                                                    <i class="mdi mdi-package-variant me-2"></i>Product Summary
                                                    <i class="mdi mdi-chevron-down float-end"></i>
                                                </h6>
                                            </div>
                                            <div class="collapse" id="productSummaryCollapse">
                                                <div class="card-body">
                                                    <div id="product-summary">
                                                        <!-- Product summary will be populated here -->
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <!-- Order Summary Accordion -->
                                        <div class="card border-success">
                                            <div class="card-header bg-success text-white" data-bs-toggle="collapse" data-bs-target="#orderSummaryCollapse" aria-expanded="true" aria-controls="orderSummaryCollapse" style="cursor: pointer;">
                                                <h6 class="mb-0">
                                                    <i class="mdi mdi-calculator me-2"></i>Order Summary
                                                    <i class="mdi mdi-chevron-up float-end"></i>
                                                </h6>
                                            </div>
                                            <div class="collapse show" id="orderSummaryCollapse">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between mb-2">
                                                        <span>Subtotal:</span>
                                                        <span id="order-subtotal">₱0.00</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between mb-2">
                                                        <span>Shipping:</span>
                                                        <span id="total-shipping">₱0.00</span>
                                                    </div>
                                                    <hr>
                                                    <div class="d-flex justify-content-between fw-bold">
                                                        <span>Total:</span>
                                                        <span id="order-total" class="text-success">₱0.00</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- No Province Selected Message -->
                            <div id="no-province-selected" class="text-center py-3" style="display: none;">
                                <i class="mdi mdi-map-marker-off text-muted" style="font-size: 2rem;"></i>
                                <p class="text-muted mt-2">Please select a province to calculate shipping costs</p>
                            </div>

                            <!-- No Ship Products Message -->
                            <div id="no-ship-products-calculation" class="text-center py-3" style="display: none;">
                                <i class="mdi mdi-package-variant-closed text-muted" style="font-size: 2rem;"></i>
                                <p class="text-muted mt-2">No ship products selected for shipping calculation</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 5: Discounts -->
        <div class="wizard-step d-none" id="step-5">
            <h5 class="mb-3">Discounts</h5>

            <!-- Discount Code Entry Section (Top) -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center border-bottom">
                            <h6 class="card-title mb-0 text-dark">
                                <i class="mdi mdi-ticket-percent me-2 text-primary"></i>Enter Discount Code
                            </h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="refresh_auto_discounts" title="Refresh Auto-Apply Discounts">
                                <i class="mdi mdi-refresh me-1"></i>Refresh Discounts
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <input type="text"
                                               class="form-control"
                                               id="discount_code_input"
                                               placeholder="Enter discount code"
                                               maxlength="50">
                                        <button type="button" class="btn btn-primary" id="apply_discount_code">
                                            <i class="mdi mdi-check me-1"></i>Apply Code
                                        </button>
                                    </div>
                                    <div id="discount_code_feedback" class="mt-2" style="display: none;"></div>
                                </div>
                                <div class="col-md-6 d-flex align-items-center">
                                    <small class="text-body-secondary">
                                        <i class="mdi mdi-information-outline me-1"></i>
                                        Enter a valid discount code and click Apply. Use <strong>Refresh Discounts</strong> to re-apply removed auto-apply discounts.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Applied Discounts Table Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-success">
                        <div class="card-header bg-success text-white">
                            <h6 class="card-title mb-0">
                                <i class="mdi mdi-tag-multiple me-2"></i>Applied Discounts
                            </h6>
                        </div>
                        <div class="card-body">
                            <!-- Loading State -->
                            <div id="auto-apply-discounts-loading" class="text-center py-3">
                                <div class="spinner-border spinner-border-sm text-success" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <span class="ms-2 text-muted">Loading available discounts...</span>
                            </div>

                            <!-- Applied Discounts Table -->
                            <div id="applied-discounts-table-container" style="display: none;">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover mb-0" id="applied-discounts-table">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 30%;">Discount Name</th>
                                                <th style="width: 20%;">Type</th>
                                                <th style="width: 20%;">Value</th>
                                                <th style="width: 15%;">Source</th>
                                                <th style="width: 15%;" class="text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="applied-discounts-tbody">
                                            <!-- Applied discounts rows will be populated here -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- No Discounts Applied Message -->
                            <div id="no-discounts-applied" class="text-center py-4" style="display: none;">
                                <i class="mdi mdi-tag-off text-secondary" style="font-size: 2.5rem;"></i>
                                <p class="text-dark mt-2 mb-1">No discounts applied to this order.</p>
                                <small class="text-secondary">Auto-apply discounts will appear here automatically, or enter a discount code above.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Breakdown with Discounts Section -->
            <div class="row">
                <div class="col-12">
                    <div class="card border-info">
                        <div class="card-header bg-info text-white">
                            <h6 class="card-title mb-0">
                                <i class="mdi mdi-calculator me-2"></i>Order Summary with Discounts
                            </h6>
                        </div>
                        <div class="card-body">
                            <!-- Loading State -->
                            <div id="discount-calculation-loading" class="text-center py-4" style="display: none;">
                                <div class="spinner-border text-info mb-3" role="status" style="width: 2rem; height: 2rem;">
                                    <span class="visually-hidden">Calculating...</span>
                                </div>
                                <p class="text-muted mb-0">Calculating discounts...</p>
                            </div>

                            <!-- Order Breakdown -->
                            <div id="discount-order-breakdown">
                                <!-- Product Summary Accordion -->
                                <div class="card border-secondary mb-3">
                                    <div class="card-header bg-light" data-bs-toggle="collapse" data-bs-target="#discountProductSummaryCollapse" aria-expanded="false" aria-controls="discountProductSummaryCollapse" style="cursor: pointer;">
                                        <h6 class="mb-0 text-secondary">
                                            <i class="mdi mdi-package-variant me-2"></i>Product Summary
                                            <i class="mdi mdi-chevron-down float-end"></i>
                                        </h6>
                                    </div>
                                    <div class="collapse" id="discountProductSummaryCollapse">
                                        <div class="card-body">
                                            <div id="discount-product-summary">
                                                <!-- Product summary will be populated here -->
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Order Totals -->
                                <div class="card border-primary">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0">
                                            <i class="mdi mdi-receipt me-2"></i>Order Total
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Subtotal:</span>
                                            <span id="discount-subtotal">₱0.00</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Shipping:</span>
                                            <span id="discount-shipping">₱0.00</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2 text-success" id="discount-row" style="display: none;">
                                            <span>Discount:</span>
                                            <span id="discount-amount">-₱0.00</span>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between fw-bold fs-5">
                                            <span>Grand Total:</span>
                                            <span id="discount-grand-total" class="text-primary">₱0.00</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

        <!-- Step 6: Affiliates -->
        <div class="wizard-step d-none" id="step-6">
            <h5 class="mb-3">Affiliates</h5>

            <!-- Affiliate Commissions Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-success">
                        <div class="card-header bg-success text-white">
                            <h6 class="card-title mb-0">
                                <i class="mdi mdi-account-group me-2"></i>Affiliate Commissions
                            </h6>
                        </div>
                        <div class="card-body">
                            <!-- Loading State -->
                            <div id="affiliate-loading" class="text-center py-4">
                                <div class="spinner-border spinner-border-sm text-success" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <span class="ms-2 text-muted">Checking affiliate referrals...</span>
                            </div>

                            <!-- Affiliate Commission Table -->
                            <div id="affiliate-commissions-container" style="display: none;">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover mb-0" id="affiliate-commissions-table">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 20%;">Store</th>
                                                <th style="width: 25%;">Affiliate</th>
                                                <th style="width: 25%;">Product/Variant</th>
                                                <th style="width: 10%;" class="text-center">Qty</th>
                                                <th style="width: 10%;" class="text-end">Rate</th>
                                                <th style="width: 10%;" class="text-end">Commission</th>
                                            </tr>
                                        </thead>
                                        <tbody id="affiliate-commissions-tbody">
                                            <!-- Affiliate commission rows will be populated here -->
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <td colspan="5" class="text-end fw-bold">Total Affiliate Commissions:</td>
                                                <td class="text-end fw-bold text-success" id="total-affiliate-commission">₱0.00</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                            <!-- No Affiliates Message -->
                            <div id="no-affiliates-found" class="text-center py-4" style="display: none;">
                                <i class="mdi mdi-account-off text-secondary" style="font-size: 2.5rem;"></i>
                                <p class="text-dark mt-2 mb-1">No affiliate referrals found for this customer.</p>
                                <small class="text-secondary">The selected customer was not referred by any affiliate for the stores in this order.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Summary with Affiliate Commissions Section -->
            <div class="row">
                <div class="col-12">
                    <div class="card border-info">
                        <div class="card-header bg-info text-white">
                            <h6 class="card-title mb-0">
                                <i class="mdi mdi-calculator me-2"></i>Order Summary with Affiliate Commissions
                            </h6>
                        </div>
                        <div class="card-body">
                            <!-- Product Summary Accordion -->
                            <div class="card border-secondary mb-3">
                                <div class="card-header bg-light" data-bs-toggle="collapse" data-bs-target="#affiliateProductSummaryCollapse" aria-expanded="false" aria-controls="affiliateProductSummaryCollapse" style="cursor: pointer;">
                                    <h6 class="mb-0 text-secondary">
                                        <i class="mdi mdi-package-variant me-2"></i>Product Summary
                                        <i class="mdi mdi-chevron-down float-end"></i>
                                    </h6>
                                </div>
                                <div class="collapse" id="affiliateProductSummaryCollapse">
                                    <div class="card-body">
                                        <div id="affiliate-product-summary">
                                            <!-- Product summary will be populated here -->
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Order Totals -->
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0">
                                        <i class="mdi mdi-receipt me-2"></i>Order Total
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Subtotal:</span>
                                        <span id="affiliate-subtotal">₱0.00</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Shipping:</span>
                                        <span id="affiliate-shipping">₱0.00</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2 text-success" id="affiliate-discount-row" style="display: none;">
                                        <span>Discount:</span>
                                        <span id="affiliate-discount">-₱0.00</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2 text-warning" id="affiliate-commission-row" style="display: none;">
                                        <span>Affiliate Commission:</span>
                                        <span id="affiliate-commission-display">₱0.00</span>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between fw-bold fs-5">
                                        <span>Customer Pays:</span>
                                        <span id="affiliate-grand-total" class="text-primary">₱0.00</span>
                                    </div>
                                    <div class="d-flex justify-content-between text-secondary mt-2" id="affiliate-net-revenue-row" style="display: none;">
                                        <span><small>Net Revenue (after commission):</small></span>
                                        <span id="affiliate-net-revenue"><small>₱0.00</small></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 7: Finalize -->
        <div class="wizard-step d-none" id="step-7">
            <h5 class="mb-3">Review & Finalize Order</h5>

            <div class="alert alert-info mb-4">
                <i class="mdi mdi-information-outline me-2"></i>
                <strong>Final Review:</strong> Please carefully review all order details below before clicking "Create Order".
            </div>

            <div class="row">
                <!-- Left Column: Products, Client, Logins -->
                <div class="col-lg-8">
                    <!-- Products Summary -->
                    <div class="card border-primary mb-3">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0 text-white">
                                <i class="mdi mdi-package-variant me-2 text-white"></i>Products Summary
                            </h6>
                            <span class="badge bg-light text-primary" id="review-products-count">0 items</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 40%;">Product</th>
                                            <th class="text-center">Type</th>
                                            <th class="text-center">Qty</th>
                                            <th class="text-end">Price</th>
                                            <th class="text-end">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody id="review-products-tbody">
                                        <!-- Products will be populated here -->
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <td colspan="4" class="text-end fw-bold">Products Subtotal:</td>
                                            <td class="text-end fw-bold text-primary" id="review-products-subtotal">₱0.00</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Client Information -->
                    <div class="card border-info mb-3">
                        <div class="card-header bg-info text-white">
                            <h6 class="card-title mb-0 text-white">
                                <i class="mdi mdi-account me-2 text-white"></i>Client Information
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row" id="review-client-info">
                                <!-- Client info will be populated here -->
                            </div>
                        </div>
                    </div>

                    <!-- Access Logins (shown only if access products exist) -->
                    <div class="card border-warning mb-3" id="review-logins-section" style="display: none;">
                        <div class="card-header bg-warning text-white">
                            <h6 class="card-title mb-0 text-white">
                                <i class="mdi mdi-key-variant me-2 text-white"></i>Access Logins
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="review-logins-info">
                                <!-- Access login info will be populated here -->
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Details (shown only if ship products exist) -->
                    <div class="card border-success mb-3" id="review-shipping-section" style="display: none;">
                        <div class="card-header bg-success">
                            <h6 class="card-title mb-0 text-white">
                                <i class="mdi mdi-truck-delivery me-2"></i>Shipping Details
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-dark mb-3"><i class="mdi mdi-account-outline me-1"></i>Recipient</h6>
                                    <div id="review-shipping-recipient">
                                        <!-- Recipient info will be populated here -->
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-dark mb-3"><i class="mdi mdi-map-marker me-1"></i>Delivery Address</h6>
                                    <div id="review-shipping-address">
                                        <!-- Address info will be populated here -->
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-dark mb-3"><i class="mdi mdi-truck me-1"></i>Shipping Method</h6>
                                    <div id="review-shipping-method">
                                        <!-- Shipping method info will be populated here -->
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-dark mb-3"><i class="mdi mdi-calculator me-1"></i>Shipping Cost Breakdown</h6>
                                    <div id="review-shipping-cost">
                                        <!-- Shipping cost breakdown will be populated here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Discounts, Affiliates, Order Totals -->
                <div class="col-lg-4">
                    <!-- Discounts Applied -->
                    <div class="card border-danger mb-3" id="review-discounts-section" style="display: none;">
                        <div class="card-header bg-danger text-white">
                            <h6 class="card-title mb-0 text-white">
                                <i class="mdi mdi-tag-multiple me-2 text-white"></i>Discounts Applied
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="review-discounts-list">
                                <!-- Discounts will be populated here -->
                            </div>
                            <div class="border-top pt-2 mt-2">
                                <div class="d-flex justify-content-between fw-bold">
                                    <span>Total Discount:</span>
                                    <span class="text-danger" id="review-total-discount">-₱0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Affiliate Commissions -->
                    <div class="card border-secondary mb-3" id="review-affiliates-section" style="display: none;">
                        <div class="card-header bg-secondary text-white">
                            <h6 class="card-title mb-0 text-white">
                                <i class="mdi mdi-account-group me-2 text-white"></i>Affiliate Commissions
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="review-affiliates-list">
                                <!-- Affiliate commissions will be populated here -->
                            </div>
                            <div class="border-top pt-2 mt-2">
                                <div class="d-flex justify-content-between fw-bold">
                                    <span>Total Commission:</span>
                                    <span class="text-warning" id="review-total-commission">₱0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Totals -->
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h6 class="card-title mb-0 text-white">
                                <i class="mdi mdi-calculator-variant me-2 text-white"></i>Order Totals
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-secondary fs-6">Products Subtotal:</span>
                                <span class="text-dark fs-6 fw-medium" id="review-subtotal">₱0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3" id="review-shipping-row" style="display: none;">
                                <span class="text-secondary fs-6">Shipping:</span>
                                <span class="text-dark fs-6 fw-medium" id="review-shipping-total">₱0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3 text-danger" id="review-discount-row" style="display: none;">
                                <span class="fs-6">Discount:</span>
                                <span class="fs-6 fw-medium" id="review-discount-total">-₱0.00</span>
                            </div>
                            <hr class="my-3">
                            <div class="d-flex justify-content-between mb-3 py-2 bg-light rounded px-2">
                                <span class="fw-bold text-dark fs-4">Grand Total:</span>
                                <span class="fw-bold text-success fs-4" id="review-grand-total">₱0.00</span>
                            </div>
                            <div class="d-flex justify-content-between text-warning mb-2" id="review-commission-row" style="display: none;">
                                <span class="fs-6">Affiliate Commission:</span>
                                <span class="fs-6" id="review-commission-deduct">-₱0.00</span>
                            </div>
                            <div class="d-flex justify-content-between text-secondary" id="review-net-row" style="display: none;">
                                <span class="fs-6">Net Revenue:</span>
                                <span class="fs-6 fw-medium" id="review-net-revenue">₱0.00</span>
                            </div>
                        </div>
                    </div>

                    <!-- Ready to Submit -->
                    <div class="card bg-light mt-3">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <i class="mdi mdi-checkbox-marked-circle text-success me-3" style="font-size: 2.5rem;"></i>
                                <div>
                                    <h6 class="text-dark mb-1">Ready to Create Order</h6>
                                    <p class="text-secondary small mb-0">
                                        Review all details above, then click the button to finalize.
                                    </p>
                                </div>
                            </div>
                            <div class="text-end">
                                <button type="button" class="btn btn-success btn-lg" id="confirm-order-btn">
                                    <i class="mdi mdi-check-circle me-2"></i>Confirm Order
                                </button>
                            </div>
                        </div>
                    </div>
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
        <div class="modal-dialog modal-xl">
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
                    <a href="{{ route('ecom-orders') }}" class="btn btn-primary">View Orders</a>
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

    <!-- Data Changes Detection Modal -->
    <div class="modal fade" id="changesDetectedModal" tabindex="-1" aria-labelledby="changesDetectedModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="changesDetectedModalLabel">
                        <i class="mdi mdi-alert-circle-outline me-2"></i>Changes Detected
                    </h5>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning mb-3">
                        <i class="mdi mdi-information-outline me-2"></i>
                        <strong>Important:</strong> Some data has changed since you started this order. Please review the changes below and click "Apply Changes & Review" to update your order.
                    </div>
                    <div id="changesListContainer">
                        <!-- Changes will be dynamically inserted here -->
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-warning" id="acceptChangesBtn">
                        <i class="mdi mdi-check me-1"></i>Apply Changes & Review
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
                    <form id="createAccessForm" novalidate>
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
@endsection

@section('script')
<!-- Toastr -->
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>

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

    /* Variant details modal - wider */
    #variantModal .modal-dialog {
        max-width: 1140px !important;
        width: 95% !important;
    }

    /* Image and Video lightbox modals - wider */
    #imageLightbox .modal-dialog,
    #videoLightbox .modal-dialog {
        max-width: 1140px !important;
        width: 95% !important;
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
        background-color: #f8f9fa;
        border-radius: 8px !important;
        transition: all 0.2s ease;
    }

    .cart-item:hover {
        background-color: #e9ecef;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
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

    /* Shipping method card styling */
    .shipping-method-card {
        cursor: pointer;
        transition: all 0.2s ease;
        border-width: 2px !important;
    }

    .shipping-method-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .shipping-method-card.selected {
        border-color: #556ee6 !important;
        background-color: #f8f9ff;
    }

    .shipping-method-card.selected .card-body {
        background-color: transparent;
    }

    #shipping-method-section .card-body {
        background-color: #fafbfc;
    }

    /* Package card styles */
    .package-card {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        margin-bottom: 12px;
        transition: all 0.2s ease;
        overflow: hidden;
    }

    .package-card:hover {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .package-header {
        padding: 12px 15px;
        background-color: #f8f9fa;
        cursor: pointer;
        border-bottom: 1px solid #e0e0e0;
    }

    .package-header:hover {
        background-color: #f0f0f0;
    }

    .package-items-container {
        display: none;
        padding: 10px 15px;
        background-color: #ffffff;
    }

    .package-items-container.show {
        display: block;
    }

    .package-item-row {
        display: flex;
        align-items: center;
        padding: 8px;
        border-bottom: 1px solid #f0f0f0;
    }

    .package-item-row:last-child {
        border-bottom: none;
    }

    .package-item-img {
        width: 40px;
        height: 40px;
        object-fit: cover;
        border-radius: 4px;
        border: 1px solid #e0e0e0;
    }

    .package-item-img-placeholder {
        width: 40px;
        height: 40px;
        background-color: #f5f5f5;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ccc;
    }

    .package-discount-badge {
        font-size: 0.75rem;
        padding: 2px 6px;
    }

    .package-chevron {
        transition: transform 0.2s ease;
    }

    .package-chevron.rotated {
        transform: rotate(180deg);
    }

    #packages-container {
        transition: all 0.3s ease;
    }

    /* Selected package animation styles */
    .package-store-card {
        opacity: 0;
        transform: translateY(20px);
        animation: slideInUp 0.4s ease forwards;
    }

    .package-store-card:nth-child(1) { animation-delay: 0.1s; }
    .package-store-card:nth-child(2) { animation-delay: 0.2s; }
    .package-store-card:nth-child(3) { animation-delay: 0.3s; }
    .package-store-card:nth-child(4) { animation-delay: 0.4s; }
    .package-store-card:nth-child(5) { animation-delay: 0.5s; }

    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .package-header-alert {
        opacity: 0;
        animation: fadeIn 0.3s ease forwards;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .package-pricing-summary {
        opacity: 0;
        transform: translateY(10px);
        animation: slideInUp 0.4s ease forwards;
        animation-delay: 0.4s;
    }

    .package-item-row {
        opacity: 0;
        transform: translateX(-10px);
        animation: slideInLeft 0.3s ease forwards;
    }

    @keyframes slideInLeft {
        from {
            opacity: 0;
            transform: translateX(-10px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    /* Stagger animation for items within store cards */
    .package-store-card .package-item-row:nth-child(1) { animation-delay: 0.15s; }
    .package-store-card .package-item-row:nth-child(2) { animation-delay: 0.25s; }
    .package-store-card .package-item-row:nth-child(3) { animation-delay: 0.35s; }
    .package-store-card .package-item-row:nth-child(4) { animation-delay: 0.45s; }
    .package-store-card .package-item-row:nth-child(5) { animation-delay: 0.55s; }

    /* Package variant image styles */
    .package-variant-img {
        width: 45px;
        height: 45px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid #e0e0e0;
        flex-shrink: 0;
    }

    .package-variant-img-placeholder {
        width: 45px;
        height: 45px;
        background-color: #f5f5f5;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ccc;
        flex-shrink: 0;
        border: 1px solid #e0e0e0;
    }

</style>
<script>
$(document).ready(function() {
    let currentStep = 1;
    const totalSteps = 7;
    let selectedProducts = [];
    let currentProductsPage = 1;
    let currentStoreSearch = '';
    let currentProductSearch = '';
    let searchTimeout;
    let variantSearchTimeout = {};

    // Shipping method selections - maps variantId to selected shippingId
    let selectedShippingMethods = {};
    let shippingOptionsData = null;
    let hasShippingErrors = false;
    let shippingOptionsLoading = false;
    let pendingShippingLoads = 0;
    let currentVariantForModal = null;

    // Global shipping method selection
    let availableShippingMethods = {}; // Maps shippingId to method details
    let selectedGlobalShippingMethod = null; // The globally selected shipping method ID
    let allProductShippingData = {}; // Store all shipping data per variant for recalculation

    // Client search variables
    let selectedClient = null;

    // Shipping form data storage
    let shippingFormData = {};
    let currentClientsPage = 1;
    let clientSearchTimeout;

    // Store color mapping for consistent colors
    let storeColorMap = {};
    const storeColors = [
        '#3498db', // Flat Blue
        '#e74c3c', // Flat Red
        '#f39c12', // Flat Orange
        '#9b59b6', // Flat Purple
        '#1abc9c', // Flat Turquoise
        '#e67e22', // Flat Carrot
        '#f1c40f', // Flat Yellow
        '#34495e', // Flat Dark Blue
        '#c0392b', // Flat Dark Red
        '#8e44ad', // Flat Wisteria
    ];

    // Package-related variables
    let currentPackagesPage = 1;
    let currentPackageSearch = '';
    let packageSearchTimeout;
    let availablePackages = [];
    let activePackage = null; // Tracks the currently selected package (if any)

    // Function to get consistent color for a store
    function getStoreColor(storeName) {
        if (!storeName) return '#3498db'; // Default flat blue

        // Return cached color if exists
        if (storeColorMap[storeName]) {
            return storeColorMap[storeName];
        }

        // Generate hash from store name for consistent color assignment
        let hash = 0;
        for (let i = 0; i < storeName.length; i++) {
            hash = storeName.charCodeAt(i) + ((hash << 5) - hash);
        }

        // Use hash to select color from array
        const colorIndex = Math.abs(hash) % storeColors.length;
        const color = storeColors[colorIndex];

        // Cache it
        storeColorMap[storeName] = color;

        return color;
    }

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
            url: '{{ route("ecom-orders-custom-add.products") }}',
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
            const storeColor = getStoreColor(product.productStore);
            html += `
                <div class="card mb-2 ${!isLast ? 'border-bottom' : ''}" style="border-left: 4px solid ${storeColor};">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">${product.productName}</h6>
                                <div class="d-flex align-items-center gap-2">
                                    <small class="text-muted">
                                        <i class="mdi mdi-store" style="color: ${storeColor};"></i>
                                        ${product.productStore}
                                    </small>
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
            url: '{{ route("ecom-orders-custom-add.variants") }}',
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

            // Check if package is active - disable add buttons if so
            const isPackageActive = activePackage !== null;
            const isOutOfStock = variant.stocksAvailable === 0;
            const shouldDisable = isPackageActive || isOutOfStock;
            const btnClass = shouldDisable ? 'btn-secondary' : (isInCart ? 'btn-success' : 'btn-primary');
            const btnTitle = isPackageActive
                ? 'Remove the active package first'
                : (isInCart ? 'Left click: Add quantity | Right click: Remove from cart' : 'Add to cart');

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
                        <button type="button" class="btn btn-sm ${btnClass} variant-action-btn"
                                data-variant-id="${variant.id}"
                                data-product-id="${productId}"
                                onclick="toggleVariantInCart(${variant.id}, ${productId})"
                                oncontextmenu="removeVariantFromCart(${variant.id}, ${productId}); return false;"
                                ${shouldDisable ? 'disabled' : ''}
                                title="${btnTitle}">
                            <i class="mdi mdi-plus"></i>
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
        // Check if a package is active - prevent adding individual products
        if (activePackage) {
            toastr.warning('A package is active. Remove it first to add individual products.', 'Package Active');
            return;
        }

        const existingIndex = selectedProducts.findIndex(item => item.variantId === variantId);
        const actionBtn = $(`.variant-action-btn[data-variant-id="${variantId}"]`);

        // Add loading state to button
        actionBtn.addClass('btn-loading').prop('disabled', true);

        if (existingIndex > -1) {
            // Item already in cart - add quantity instead of removing
            $.ajax({
                url: '{{ route("ecom-orders-custom-add.variants") }}',
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
                url: '{{ route("ecom-orders-custom-add.variants") }}',
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
            productStore: variant.productStore || 'Unknown Store',
            productType: variant.productType || 'Unknown',
            shipCoverage: variant.shipCoverage || 'n/a',
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
                    <small>Select products from the available products or packages above</small>
                </div>
            `);
            cartSummary.hide();
        } else if (activePackage) {
            // Package-specific display
            let html = '';
            const calculatedTotal = selectedProducts.reduce((sum, item) => sum + (parseFloat(item.price) * item.quantity), 0);

            // Package header with animation
            html += `
                <div class="alert alert-info mb-3 d-flex justify-content-between align-items-center package-header-alert">
                    <div>
                        <i class="mdi mdi-package-variant-closed me-2"></i>
                        <strong>Package: ${escapeHtml(activePackage.packageName)}</strong>
                        ${activePackage.discountAmount > 0 ? `<span class="badge bg-success ms-2">Save ₱${parseFloat(activePackage.discountAmount).toFixed(2)}</span>` : ''}
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeActivePackage()">
                        <i class="mdi mdi-close me-1"></i>Remove Package
                    </button>
                </div>
            `;

            // Group items by store
            const itemsByStore = {};
            selectedProducts.forEach(function(item) {
                const storeName = item.productStore || 'Unknown Store';
                if (!itemsByStore[storeName]) {
                    itemsByStore[storeName] = [];
                }
                itemsByStore[storeName].push(item);
            });

            // Package items organized by store
            html += '<div class="package-items-list">';
            Object.keys(itemsByStore).forEach(function(storeName) {
                const storeItems = itemsByStore[storeName];
                const storeColor = getStoreColor(storeName);
                let storeSubtotal = storeItems.reduce((sum, item) => sum + (parseFloat(item.price) * item.quantity), 0);

                // Store card with color-coded left border and animation
                html += `
                    <div class="card mb-3 package-store-card" style="border-left: 4px solid ${storeColor} !important;">
                        <div class="card-header py-2 d-flex justify-content-between align-items-center" style="background-color: #f8f9fa;">
                            <div class="d-flex align-items-center">
                                <i class="mdi mdi-store me-2" style="color: ${storeColor}; font-size: 1.1rem;"></i>
                                <strong class="text-dark">${escapeHtml(storeName)}</strong>
                                <span class="badge bg-secondary ms-2">${storeItems.length} item(s)</span>
                            </div>
                            <small class="text-muted text-decoration-line-through">₱${storeSubtotal.toFixed(2)}</small>
                        </div>
                        <div class="card-body py-2">
                `;

                // Items for this store with animation and images
                storeItems.forEach(function(item, index) {
                    const typeBadge = item.productType === 'access'
                        ? '<span class="badge bg-info text-white" style="font-size: 0.65rem;">Access</span>'
                        : '<span class="badge bg-warning text-dark" style="font-size: 0.65rem;">Ship</span>';

                    const isLast = index === storeItems.length - 1;

                    // Variant image or placeholder
                    const variantImage = item.imageUrl
                        ? `<img src="${escapeHtml(item.imageUrl)}" alt="${escapeHtml(item.variantName)}" class="package-variant-img">`
                        : `<div class="package-variant-img-placeholder"><i class="mdi mdi-image-off"></i></div>`;

                    html += `
                        <div class="d-flex align-items-center py-2 package-item-row ${!isLast ? 'border-bottom' : ''}">
                            ${variantImage}
                            <div class="flex-grow-1 ms-3">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-dark fw-medium">${escapeHtml(item.variantName)}</span>
                                    ${typeBadge}
                                </div>
                                <small class="text-secondary">${escapeHtml(item.productName)}</small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-secondary">Qty: ${item.quantity}</span>
                                <div class="text-muted small">₱${parseFloat(item.price).toFixed(2)} each</div>
                            </div>
                        </div>
                    `;
                });

                html += `
                        </div>
                    </div>
                `;
            });
            html += '</div>';

            // Pricing summary with animation
            html += `
                <div class="mt-3 p-3 bg-light rounded package-pricing-summary">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Regular Price:</span>
                        <span class="text-decoration-line-through text-muted">₱${calculatedTotal.toFixed(2)}</span>
                    </div>
                    ${activePackage.discountAmount > 0 ? `
                    <div class="d-flex justify-content-between mb-1 text-success">
                        <span>Package Discount (${activePackage.discountPercentage}%):</span>
                        <span>-₱${parseFloat(activePackage.discountAmount).toFixed(2)}</span>
                    </div>
                    ` : ''}
                    <hr class="my-2">
                    <div class="d-flex justify-content-between">
                        <strong class="text-dark">Package Price:</strong>
                        <strong class="text-primary fs-5">₱${parseFloat(activePackage.packagePrice).toFixed(2)}</strong>
                    </div>
                </div>
            `;

            cartContainer.html(html);

            // Update summary to show package price
            const totalItems = selectedProducts.reduce((sum, item) => sum + item.quantity, 0);
            $('#total-items').text(totalItems + ' items (Package)');
            $('#total-amount').text('₱' + parseFloat(activePackage.packagePrice).toFixed(2));
            cartSummary.show();
        } else {
            // Regular individual products display
            let html = '';
            let totalAmount = 0;

            selectedProducts.forEach(function(item, index) {
                totalAmount += parseFloat(item.price) * item.quantity;
                const maxOrderPerTransaction = parseInt(item.maxOrderPerTransaction) || 1;
                const isAtMaxLimit = item.quantity >= maxOrderPerTransaction;
                const storeColor = getStoreColor(item.productStore);

                html += `
                    <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded cart-item" data-item-index="${index}" style="border-left: 4px solid ${storeColor} !important;">
                        <div>
                            <small class="fw-bold text-primary">${item.productName || 'Unknown Product'}</small><br>
                            <small class="fw-bold">${item.variantName}</small><br>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <small class="text-muted">
                                    <i class="mdi mdi-store me-1" style="color: ${storeColor};"></i>${item.productStore || 'Unknown Store'}
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

        // Trigger products updated event
        $(document).trigger('productsUpdated');
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
        const totalItems = selectedProducts.reduce((sum, item) => sum + item.quantity, 0);
        const uniqueProducts = new Set(selectedProducts.map(item => item.productId)).size;

        // Use package price if a package is active, otherwise calculate from individual items
        const totalAmount = activePackage
            ? parseFloat(activePackage.packagePrice)
            : selectedProducts.reduce((sum, item) => sum + (parseFloat(item.price) * item.quantity), 0);

        // Animate the summary update
        $('#total-items').fadeOut(100, function() {
            if (activePackage) {
                $(this).text(totalItems + ' items (Package)');
            } else {
                $(this).text(totalItems);
                // Remove any existing product count text first
                $(this).find('small').remove();
                if (uniqueProducts > 1) {
                    $(this).append(` <small class="text-muted">(${uniqueProducts} products)</small>`);
                }
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

            // Update cart display and summary
            updateCartDisplay();

            // Update hidden input
            $('#selectedProducts').val(JSON.stringify(selectedProducts));

            // Trigger products updated event
            $(document).trigger('productsUpdated');
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
            productStore: currentVariantForModal.variant.productStore || 'Unknown Store',
            productType: currentVariantForModal.variant.productType || 'Unknown',
            shipCoverage: currentVariantForModal.variant.shipCoverage || 'n/a',
            quantity: quantity,
            maxOrderPerTransaction: currentVariantForModal.variant.maxOrderPerTransaction || 1,
            stocksAvailable: currentVariantForModal.variant.stocksAvailable || 0
        });

        // Close modal and update display with animation
        $('#quantityModal').modal('hide');

        // Capture values before clearing
        const variantId = currentVariantForModal.variant.id;
        const productId = currentVariantForModal.productId;

        setTimeout(() => {
            updateCartDisplay();
            updateVariantButtonState(variantId, productId, true);

            // Trigger products updated event
            $(document).trigger('productsUpdated');
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
            url: '{{ route("ecom-orders-custom-add.variant-details") }}',
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
                        <div class="flex-grow-1">
                            <h4 class="mb-1 text-primary">${variant.ecomVariantName || 'Variant Details'}</h4>
                            <p class="text-muted mb-0">${product.productName || 'Product'}</p>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-info fs-6">${product.productStore || 'N/A'}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content - 3 Column Layout -->
            <div class="row g-4">
                <!-- Column 1 - Product & Variant Info -->
                <div class="col-lg-4">
                    <!-- Product Information -->
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-light border-0 py-2">
                            <h6 class="mb-0 text-primary">
                                <i class="mdi mdi-information-outline me-2"></i>Product Information
                            </h6>
                        </div>
                        <div class="card-body py-3">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted small mb-1">Product Name</label>
                                <p class="mb-0">${product.productName || 'N/A'}</p>
                            </div>
                            <div>
                                <label class="form-label fw-bold text-muted small mb-1">Description</label>
                                <p class="mb-0 text-muted small">${product.productDescription || 'No description available'}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Variant Information -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light border-0 py-2">
                            <h6 class="mb-0 text-primary">
                                <i class="mdi mdi-tag-outline me-2"></i>Variant Information
                            </h6>
                        </div>
                        <div class="card-body py-3">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted small mb-1">Variant Name</label>
                                <p class="mb-0">${variant.ecomVariantName || 'N/A'}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted small mb-1">Description</label>
                                <p class="mb-0 text-muted small">${variant.ecomVariantDescription || 'No description available'}</p>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label fw-bold text-muted small mb-1">Price</label>
                                    <div class="h5 text-success mb-0">₱${parseFloat(variant.ecomVariantPrice || 0).toFixed(2)}</div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-bold text-muted small mb-1">Stock</label>
                                    <div>
                                        <span class="badge ${variant.stocksAvailable > 0 ? 'bg-success' : 'bg-danger'}">
                                            ${variant.stocksAvailable || 0} ${variant.stocksAvailable === 1 ? 'item' : 'items'}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-12 mt-2">
                                    <label class="form-label fw-bold text-muted small mb-1">Max Order/Transaction</label>
                                    <div>
                                        <span class="badge bg-info">${variant.maxOrderPerTransaction || 1}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Column 2 - Product Images -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-light border-0 py-2">
                            <h6 class="mb-0 text-primary">
                                <i class="mdi mdi-image-multiple me-2"></i>Product Images
                            </h6>
                        </div>
                        <div class="card-body py-3">
        `;

        if (images.length > 0) {
            html += `<div class="row g-2" id="image-gallery">`;
            images.forEach(function(image, index) {
                html += `
                    <div class="col-6">
                        <img src="${image.imageLink}"
                             class="img-fluid rounded"
                             style="height: 120px; width: 100%; object-fit: cover; cursor: pointer; transition: transform 0.2s;"
                             alt="Variant Image ${index + 1}"
                             onclick="showImageLightbox('${image.imageLink}')"
                             onmouseover="this.style.transform='scale(1.05)'"
                             onmouseout="this.style.transform='scale(1)'"
                             onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iI2RkZCIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTIiIGZpbGw9IiM5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5JbWFnZTwvdGV4dD48L3N2Zz4='">
                    </div>
                `;
            });
            html += `</div>`;
        } else {
            html += `
                <div class="text-center py-4">
                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 60px; height: 60px;">
                        <i class="mdi mdi-image-off text-muted" style="font-size: 24px;"></i>
                    </div>
                    <h6 class="text-muted mb-1">No Images</h6>
                    <p class="text-muted small mb-0">No images available</p>
                </div>
            `;
        }

        html += `
                        </div>
                    </div>
                </div>

                <!-- Column 3 - Product Videos -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-light border-0 py-2">
                            <h6 class="mb-0 text-primary">
                                <i class="mdi mdi-video me-2"></i>Product Videos
                            </h6>
                        </div>
                        <div class="card-body py-3">
        `;

        if (data.videos && data.videos.length > 0) {
            html += `<div class="row g-2" id="video-gallery">`;
            data.videos.forEach(function(video, index) {
                const videoId = extractYouTubeVideoId(video.videoLink);
                const thumbnailUrl = `https://img.youtube.com/vi/${videoId}/hqdefault.jpg`;
                html += `
                    <div class="col-6">
                        <div class="position-relative">
                            <img src="${thumbnailUrl}"
                                 class="img-fluid rounded"
                                 style="height: 100px; width: 100%; object-fit: cover; cursor: pointer;"
                                 alt="Video Thumbnail ${index + 1}"
                                 onclick="showVideoLightbox('${video.videoLink}')">
                            <div class="position-absolute top-50 start-50 translate-middle">
                                <div class="bg-danger rounded-circle p-1">
                                    <i class="mdi mdi-play text-white"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += `</div>`;
        } else {
            html += `
                <div class="text-center py-4">
                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 60px; height: 60px;">
                        <i class="mdi mdi-video-off text-muted" style="font-size: 24px;"></i>
                    </div>
                    <h6 class="text-muted mb-1">No Videos</h6>
                    <p class="text-muted small mb-0">No videos available</p>
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

    // ==========================================
    // PACKAGE SELECTION FUNCTIONALITY
    // ==========================================

    // Load available packages
    function loadPackages(page = 1, search = '') {
        showPackagesLoading();

        $.ajax({
            url: '{{ route("ecom-orders-custom-add.packages") }}',
            type: 'GET',
            data: {
                page: page,
                search: search,
                per_page: 10
            },
            success: function(response) {
                if (response.success) {
                    availablePackages = response.data;
                    displayPackages(response.data);
                    updatePackagesPagination(response.pagination);
                } else {
                    $('#packages-container').html('<div class="text-center py-3 text-muted">No packages available</div>');
                }
            },
            error: function() {
                $('#packages-container').html('<div class="text-center py-3 text-danger">Error loading packages</div>');
            }
        });
    }

    // Show packages loading indicator
    function showPackagesLoading() {
        $('#packages-container').html(`
            <div class="text-center py-3">
                <div class="spinner-border text-info" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading packages...</p>
            </div>
        `);
    }

    // Display packages
    function displayPackages(packages) {
        if (packages.length === 0) {
            $('#packages-container').html(`
                <div class="text-center py-3 text-muted">
                    <i class="mdi mdi-package-variant-closed-remove" style="font-size: 2rem;"></i>
                    <p class="mt-2 mb-0">No available packages found</p>
                    <small>All packages must have all items in stock to appear here</small>
                </div>
            `);
            return;
        }

        let html = '';
        packages.forEach(function(pkg) {
            const hasDiscount = pkg.discountAmount > 0;
            const discountBadge = hasDiscount
                ? `<span class="badge bg-success package-discount-badge ms-2">Save ₱${parseFloat(pkg.discountAmount).toFixed(2)} (${pkg.discountPercentage}% off)</span>`
                : '';

            html += `
                <div class="package-card" data-package-id="${pkg.packageId}">
                    <div class="package-header d-flex justify-content-between align-items-center" onclick="togglePackageItems(${pkg.packageId})">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center">
                                <h6 class="mb-0 text-dark fw-medium">${escapeHtml(pkg.packageName)}</h6>
                                ${discountBadge}
                            </div>
                            <div class="d-flex align-items-center gap-3 mt-1">
                                <small class="text-secondary">
                                    <i class="mdi mdi-package-variant me-1"></i>${pkg.itemCount} item(s)
                                </small>
                                <small class="text-secondary">
                                    <span class="text-decoration-line-through">₱${parseFloat(pkg.calculatedPrice).toFixed(2)}</span>
                                </small>
                                <strong class="text-primary">₱${parseFloat(pkg.packagePrice).toFixed(2)}</strong>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-info text-white add-package-btn"
                                    onclick="event.stopPropagation(); addPackageToCart(${pkg.packageId})"
                                    title="Add all items to cart">
                                <i class="mdi mdi-cart-plus"></i> Add
                            </button>
                            <i class="mdi mdi-chevron-down package-chevron" id="pkg-chevron-${pkg.packageId}"></i>
                        </div>
                    </div>
                    <div class="package-items-container" id="pkg-items-${pkg.packageId}">
                        ${renderPackageItems(pkg.items)}
                    </div>
                </div>
            `;
        });

        $('#packages-container').html(html);
    }

    // Render package items
    function renderPackageItems(items) {
        let html = '<div class="small text-secondary mb-2">Package contents:</div>';

        items.forEach(function(item) {
            const imgHtml = item.imageUrl
                ? `<img src="${item.imageUrl}" class="package-item-img" alt="${escapeHtml(item.variantName)}">`
                : `<div class="package-item-img-placeholder"><i class="mdi mdi-image"></i></div>`;

            const typeBadge = item.productType === 'access'
                ? '<span class="badge bg-info text-white" style="font-size: 0.65rem;">Access</span>'
                : '<span class="badge bg-warning text-dark" style="font-size: 0.65rem;">Ship</span>';

            const storeColor = getStoreColor(item.productStore);

            html += `
                <div class="package-item-row" style="border-left: 3px solid ${storeColor}; margin-left: 0; padding-left: 10px;">
                    ${imgHtml}
                    <div class="ms-2 flex-grow-1">
                        <div class="d-flex align-items-center gap-1">
                            <span class="text-dark" style="font-size: 0.85rem;">${escapeHtml(item.variantName)}</span>
                            ${typeBadge}
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <small class="text-secondary">${escapeHtml(item.productName)}</small>
                            <small class="text-secondary">Qty: ${item.quantity}</small>
                        </div>
                    </div>
                    <div class="text-end">
                        <small class="text-primary fw-medium">₱${parseFloat(item.unitPrice).toFixed(2)}</small>
                    </div>
                </div>
            `;
        });

        return html;
    }

    // Toggle package items visibility
    window.togglePackageItems = function(packageId) {
        const itemsContainer = $(`#pkg-items-${packageId}`);
        const chevron = $(`#pkg-chevron-${packageId}`);

        if (itemsContainer.hasClass('show')) {
            itemsContainer.removeClass('show');
            chevron.removeClass('rotated');
        } else {
            itemsContainer.addClass('show');
            chevron.addClass('rotated');
        }
    };

    // Add package to cart
    window.addPackageToCart = function(packageId) {
        // Check if there's already an active package
        if (activePackage) {
            toastr.warning('A package is already in the cart. Remove it first to add a different package.', 'Package Active');
            return;
        }

        // Check if there are already individual products in cart
        if (selectedProducts.length > 0) {
            toastr.warning('Please remove existing products before adding a package.', 'Cart Not Empty');
            return;
        }

        const pkg = availablePackages.find(p => p.packageId === packageId);
        if (!pkg) {
            toastr.error('Package not found', 'Error');
            return;
        }

        // Check if all items can be added (stock validation)
        let canAddAll = true;
        let errorMessages = [];

        pkg.items.forEach(function(item) {
            if (item.quantity > item.stocksAvailable) {
                canAddAll = false;
                errorMessages.push(`${item.variantName}: Only ${item.stocksAvailable} in stock`);
            }
        });

        if (!canAddAll) {
            toastr.error('Cannot add package:<br>' + errorMessages.join('<br>'), 'Stock Issue', {
                allowHtml: true,
                timeOut: 5000
            });
            return;
        }

        // Set the active package
        activePackage = {
            packageId: pkg.packageId,
            packageName: pkg.packageName,
            packagePrice: pkg.packagePrice,
            calculatedPrice: pkg.calculatedPrice,
            discountAmount: pkg.discountAmount,
            discountPercentage: pkg.discountPercentage,
            items: pkg.items
        };

        // Add all items to selectedProducts (for order processing)
        pkg.items.forEach(function(item) {
            selectedProducts.push({
                variantId: item.variantId,
                variantName: item.variantName,
                price: item.unitPrice,
                productId: item.productId,
                productName: item.productName,
                productStore: item.productStore,
                productType: item.productType,
                shipCoverage: item.shipCoverage,
                quantity: item.quantity,
                maxOrderPerTransaction: item.maxOrderPerTransaction,
                stocksAvailable: item.stocksAvailable,
                imageUrl: item.imageUrl || null, // Include variant image
                isPackageItem: true // Mark as package item
            });
        });

        // Update cart display
        updateCartDisplay();
        $('#selectedProducts').val(JSON.stringify(selectedProducts));
        $('#activePackageData').val(JSON.stringify(activePackage));

        // Disable all product and package add buttons
        disableProductPackageButtons();

        toastr.success(`Package "${pkg.packageName}" added to cart!`, 'Package Added');
    };

    // Remove active package from cart
    window.removeActivePackage = function() {
        if (!activePackage) return;

        const packageName = activePackage.packageName;

        // Clear selected products
        selectedProducts = [];
        activePackage = null;

        // Update cart display
        updateCartDisplay();
        $('#selectedProducts').val(JSON.stringify(selectedProducts));
        $('#activePackageData').val('');

        // Re-enable all product and package add buttons
        enableProductPackageButtons();

        // Refresh product button states
        $('.variant-action-btn').each(function() {
            const variantId = $(this).data('variant-id');
            const productId = $(this).data('product-id');
            updateVariantButtonState(variantId, productId, false);
        });

        toastr.info(`Package "${packageName}" removed from cart.`, 'Package Removed');
    };

    // Disable product and package add buttons when package is active
    function disableProductPackageButtons() {
        // Disable individual product variant buttons
        $('.variant-action-btn').prop('disabled', true)
            .removeClass('btn-primary btn-success')
            .addClass('btn-secondary')
            .attr('title', 'Remove the active package first');

        // Disable package add buttons
        $('.add-package-btn').prop('disabled', true)
            .removeClass('btn-info')
            .addClass('btn-secondary')
            .attr('title', 'Remove the active package first');
    }

    // Re-enable product and package add buttons
    function enableProductPackageButtons() {
        // Re-enable individual product variant buttons
        $('.variant-action-btn').prop('disabled', false)
            .removeClass('btn-secondary')
            .addClass('btn-primary')
            .attr('title', 'Add to cart');

        // Re-enable package add buttons
        $('.add-package-btn').prop('disabled', false)
            .removeClass('btn-secondary')
            .addClass('btn-info')
            .attr('title', 'Add all items to cart');
    }

    // Check if adding individual products is allowed
    function canAddIndividualProducts() {
        if (activePackage) {
            toastr.warning('A package is active. Remove it first to add individual products.', 'Package Active');
            return false;
        }
        return true;
    }

    // Get the correct subtotal (package price if package is active, otherwise sum of items)
    function getOrderSubtotal() {
        if (activePackage) {
            return parseFloat(activePackage.packagePrice);
        }
        return selectedProducts.reduce((sum, item) => sum + (parseFloat(item.price) * item.quantity), 0);
    }

    // Update packages pagination
    function updatePackagesPagination(pagination) {
        const paginationContainer = $('#packages-pagination');
        let html = '';

        if (pagination.last_page > 1) {
            html += '<nav aria-label="Packages pagination"><ul class="pagination pagination-sm justify-content-center">';

            // Previous button
            html += `<li class="page-item ${pagination.current_page === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="loadPackagesPage(${pagination.current_page - 1})">
                    <i class="mdi mdi-chevron-left"></i>
                </a>
            </li>`;

            // Page numbers
            for (let i = 1; i <= pagination.last_page; i++) {
                if (i === 1 || i === pagination.last_page ||
                    (i >= pagination.current_page - 1 && i <= pagination.current_page + 1)) {
                    html += `<li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="loadPackagesPage(${i})">${i}</a>
                    </li>`;
                } else if (i === pagination.current_page - 2 || i === pagination.current_page + 2) {
                    html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
            }

            // Next button
            html += `<li class="page-item ${pagination.current_page === pagination.last_page ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="loadPackagesPage(${pagination.current_page + 1})">
                    <i class="mdi mdi-chevron-right"></i>
                </a>
            </li>`;

            html += '</ul></nav>';
        }

        paginationContainer.html(html);
    }

    // Load packages page
    window.loadPackagesPage = function(page) {
        if (page < 1) return;
        currentPackagesPage = page;
        loadPackages(page, currentPackageSearch);
    };

    // Package search with debouncing
    function performPackageSearch() {
        clearTimeout(packageSearchTimeout);
        packageSearchTimeout = setTimeout(function() {
            currentPackageSearch = $('#package-search').val();
            currentPackagesPage = 1;
            loadPackages(1, currentPackageSearch);
        }, 300);
    }

    // Bind package search events
    $('#package-search').on('input', performPackageSearch);

    $('#package-search').keypress(function(e) {
        if (e.which === 13) {
            clearTimeout(packageSearchTimeout);
            performPackageSearch();
        }
    });

    // ==========================================
    // END PACKAGE SELECTION FUNCTIONALITY
    // ==========================================

    // Show step
    function showStep(step) {
        // Save shipping form data when leaving step 4
        if (currentStep === 4) {
            saveShippingFormData();
            // Reset Next button state when leaving step 4
            $('#next-btn').prop('disabled', false)
                .addClass('btn-primary')
                .removeClass('btn-secondary')
                .html('<i class="bx bx-right-arrow-alt me-1"></i>Next');
        }

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

        // Load ship products when step 4 is shown
        if (step === 4) {
            loadShipProductsStores();
            // Initialize shipping calculation state
            initializeShippingCalculation();
            // Load provinces and restore form data
            // Delay to ensure ship products are loaded first
            setTimeout(function() {
                loadPhilippineProvinces();
                restoreShippingFormData();
            }, 100);
        }

        // Load discounts and order summary when step 5 is shown
        if (step === 5) {
            loadAutoApplyDiscounts();
            updateDiscountOrderSummary();
        }

        // Load affiliate summary when step 6 is shown
        if (step === 6) {
            updateAffiliateSummary();
        }

        // Populate order review when step 7 is shown
        if (step === 7) {
            populateOrderReview();
        }
    }

    // Store affiliate commissions data
    let affiliateCommissions = [];
    let totalAffiliateCommission = 0;

    // Update affiliate summary (Step 6)
    function updateAffiliateSummary() {
        // Show loading state
        $('#affiliate-loading').show();
        $('#affiliate-commissions-container').hide();
        $('#no-affiliates-found').hide();

        // Calculate subtotal (uses package price if package is active)
        const subtotal = getOrderSubtotal();

        // Get shipping from previous calculation (if available)
        let shippingTotal = 0;
        const shippingText = $('#discount-shipping').text();
        if (shippingText) {
            shippingTotal = parseFloat(shippingText.replace(/[₱,]/g, '')) || 0;
        }

        // Get discount from previous calculation
        let discountTotal = 0;
        const discountText = $('#discount-amount').text();
        if (discountText) {
            discountTotal = parseFloat(discountText.replace(/[-₱,]/g, '')) || 0;
        }

        // Update product summary
        updateAffiliateProductSummary();

        // Call API to get affiliate commissions
        if (selectedClient && selectedProducts.length > 0) {
            loadAffiliateCommissions(subtotal, shippingTotal, discountTotal);
        } else {
            // No client or products selected
            $('#affiliate-loading').hide();
            $('#no-affiliates-found').show();
            updateAffiliateOrderTotals(subtotal, shippingTotal, discountTotal, 0);
        }
    }

    // Load affiliate commissions from API
    function loadAffiliateCommissions(subtotal, shippingTotal, discountTotal) {
        const cartItems = selectedProducts.map(item => ({
            variantId: item.variantId,
            productId: item.productId,
            productName: item.productName,
            variantName: item.variantName,
            productStore: item.productStore,
            quantity: item.quantity,
            price: item.price
        }));

        $.ajax({
            url: '/ecom-orders-custom-add/affiliate-commissions',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                clientId: selectedClient.id,
                cartItems: JSON.stringify(cartItems)
            },
            success: function(response) {
                $('#affiliate-loading').hide();

                if (response.success) {
                    affiliateCommissions = response.commissions || [];
                    totalAffiliateCommission = response.totalCommission || 0;

                    if (affiliateCommissions.length > 0) {
                        renderAffiliateCommissions(affiliateCommissions);
                        $('#affiliate-commissions-container').show();
                        $('#no-affiliates-found').hide();
                    } else {
                        $('#affiliate-commissions-container').hide();
                        $('#no-affiliates-found').show();
                    }

                    updateAffiliateOrderTotals(subtotal, shippingTotal, discountTotal, totalAffiliateCommission);
                } else {
                    $('#no-affiliates-found').show();
                    updateAffiliateOrderTotals(subtotal, shippingTotal, discountTotal, 0);
                }
            },
            error: function() {
                $('#affiliate-loading').hide();
                $('#no-affiliates-found').show();
                updateAffiliateOrderTotals(subtotal, shippingTotal, discountTotal, 0);
            }
        });
    }

    // Render affiliate commissions table
    function renderAffiliateCommissions(commissions) {
        let html = '';

        commissions.forEach(function(item) {
            html += `
                <tr>
                    <td class="text-dark">
                        <i class="mdi mdi-store me-1 text-primary"></i>
                        ${escapeHtml(item.storeName)}
                    </td>
                    <td class="text-dark">
                        <i class="mdi mdi-account me-1 text-success"></i>
                        ${escapeHtml(item.affiliateName)}
                        <br><small class="text-secondary">${escapeHtml(item.affiliatePhone || '')}</small>
                    </td>
                    <td class="text-dark">
                        ${escapeHtml(item.productName)}
                        <br><small class="text-secondary">${escapeHtml(item.variantName)}</small>
                    </td>
                    <td class="text-center text-dark">${item.quantity}</td>
                    <td class="text-end text-dark">₱${formatNumber(item.affiliateRate)}</td>
                    <td class="text-end text-success fw-bold">₱${formatNumber(item.commission)}</td>
                </tr>
            `;
        });

        $('#affiliate-commissions-tbody').html(html);
        $('#total-affiliate-commission').text('₱' + formatNumber(totalAffiliateCommission));
    }

    // Update affiliate product summary
    function updateAffiliateProductSummary() {
        let html = '<table class="table table-sm table-bordered mb-0">';
        html += '<thead class="table-light"><tr><th>Product</th><th>Variant</th><th class="text-center">Qty</th><th class="text-end">Price</th><th class="text-end">Total</th></tr></thead>';
        html += '<tbody>';

        selectedProducts.forEach(function(item) {
            const itemTotal = parseFloat(item.price) * item.quantity;
            html += `
                <tr>
                    <td class="text-dark">${escapeHtml(item.productName)}</td>
                    <td class="text-dark">${escapeHtml(item.variantName)}</td>
                    <td class="text-center text-dark">${item.quantity}</td>
                    <td class="text-end text-dark">₱${formatNumber(item.price)}</td>
                    <td class="text-end text-dark">₱${formatNumber(itemTotal)}</td>
                </tr>
            `;
        });

        html += '</tbody></table>';
        $('#affiliate-product-summary').html(html);
    }

    // Update affiliate order totals
    function updateAffiliateOrderTotals(subtotal, shippingTotal, discountTotal, commissionTotal) {
        const grandTotal = Math.max(0, subtotal - discountTotal + shippingTotal);
        const netRevenue = grandTotal - commissionTotal;

        // Update affiliate summary
        $('#affiliate-subtotal').text('₱' + formatNumber(subtotal));
        $('#affiliate-shipping').text('₱' + formatNumber(shippingTotal));

        if (discountTotal > 0) {
            $('#affiliate-discount').text('-₱' + formatNumber(discountTotal));
            $('#affiliate-discount-row').show();
        } else {
            $('#affiliate-discount-row').hide();
        }

        if (commissionTotal > 0) {
            $('#affiliate-commission-display').text('₱' + formatNumber(commissionTotal));
            $('#affiliate-commission-row').show();
            $('#affiliate-net-revenue').html('<small>₱' + formatNumber(netRevenue) + '</small>');
            $('#affiliate-net-revenue-row').show();
        } else {
            $('#affiliate-commission-row').hide();
            $('#affiliate-net-revenue-row').hide();
        }

        $('#affiliate-grand-total').text('₱' + formatNumber(grandTotal));
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

                // Basic client-side validation (skip disabled fields)
                if ($field.prop('required') && !$field.prop('disabled') && (!value || !value.trim())) {
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

        // Special validation for step 4 - check if shipping address is complete
        if (step === 4) {
            // Get ship products (products with type 'ship')
            const shipProducts = selectedProducts.filter(product =>
                product.productType === 'ship' || product.productType === 'Ship'
            );

            // Check if shipping type is selected when there are ship products
            const shippingType = $('#shipping_type').val();
            if (shipProducts.length > 0 && !shippingType) {
                showErrorAlertModal('Please select a Shipping Type (Regular, Cash on Delivery, or Cash on Pickup) before proceeding.');
                return false;
            }

            // Check if province is selected when there are ship products
            const province = $('#shipping_province').val();
            if (shipProducts.length > 0 && !province) {
                showErrorAlertModal('Please select a shipping province to view available shipping options for your products.');
                return false;
            }

            // Check if still loading shipping options
            if (shipProducts.length > 0 && (shippingOptionsLoading || pendingShippingLoads > 0)) {
                showErrorAlertModal('Please wait for shipping options to finish loading before proceeding.');
                return false;
            }

            // Check if any ship products have shipping errors (no shipping method available)
            if (shipProducts.length > 0 && hasShippingErrors) {
                showErrorAlertModal('One or more products do not have shipping methods configured for the selected province. Please configure shipping settings before proceeding.');
                return false;
            }

            // Validate ship coverage for ship products
            const shipCoverageErrors = [];

            shipProducts.forEach(product => {
                const shipCoverage = product.shipCoverage || 'n/a';
                const productName = product.productName || 'Unknown Product';

                if (shipCoverage.toLowerCase() === 'province') {
                    if (!province) {
                        shipCoverageErrors.push(`Province shipping location is required for product: ${productName}`);
                    } else if (province.toLowerCase() !== 'pangasinan') {
                        shipCoverageErrors.push(`Product '${productName}' has Province shipping coverage only and can only be shipped to Pangasinan.`);
                    }
                }
            });

            // Show ship coverage errors if any
            if (shipCoverageErrors.length > 0) {
                showErrorAlertModal(shipCoverageErrors.join(' '));
                return false;
            }

            if (shipProducts.length > 0) {
                // Validate all required shipping address fields (middle name, house number, street are optional)
                const requiredFields = [
                    'shipping_first_name', 'shipping_last_name',
                    'shipping_phone', 'shipping_email', 'shipping_province',
                    'shipping_municipality', 'shipping_barangay', 'shipping_zip_code'
                ];

                const missingFields = [];
                requiredFields.forEach(fieldId => {
                    const field = $(`#${fieldId}`);
                    if (!field.val() || field.val().trim() === '') {
                        missingFields.push(fieldId.replace('shipping_', '').replace('_', ' '));
                        field.addClass('is-invalid');
                        field.siblings('.invalid-feedback').text('This field is required.');
                    } else {
                        field.removeClass('is-invalid');
                        field.siblings('.invalid-feedback').text('');
                    }
                });

                // Validate email format
                const email = $('#shipping_email').val();
                if (email && !isValidEmail(email)) {
                    $('#shipping_email').addClass('is-invalid');
                    $('#shipping_email').siblings('.invalid-feedback').text('Please enter a valid email address.');
                    missingFields.push('valid email address');
                }

                // Validate phone format
                const phone = $('#shipping_phone').val();
                if (phone && !isValidPhone(phone)) {
                    $('#shipping_phone').addClass('is-invalid');
                    $('#shipping_phone').siblings('.invalid-feedback').text('Please enter a valid phone number.');
                    missingFields.push('valid phone number');
                }

                if (missingFields.length > 0) {
                    showErrorAlertModal('Please complete all required shipping address fields: ' + missingFields.join(', '));
                    isValid = false;
                }
            }
        }

        return isValid;
    }

    // Save shipping form data
    function saveShippingFormData() {
        shippingFormData = {
            shipping_first_name: $('#shipping_first_name').val(),
            shipping_middle_name: $('#shipping_middle_name').val(),
            shipping_last_name: $('#shipping_last_name').val(),
            shipping_phone: $('#shipping_phone').val(),
            shipping_email: $('#shipping_email').val(),
            shipping_house_number: $('#shipping_house_number').val(),
            shipping_street: $('#shipping_street').val(),
            shipping_zone: $('#shipping_zone').val(),
            shipping_type: $('#shipping_type').val(),
            shipping_province: $('#shipping_province').val(),
            shipping_municipality: $('#shipping_municipality').val(),
            shipping_barangay: $('#shipping_barangay').val(),
            shipping_zip_code: $('#shipping_zip_code').val()
        };
    }

    // Restore shipping form data
    function restoreShippingFormData() {
        // Wait for province dropdown to be populated
        setTimeout(() => {
            if (Object.keys(shippingFormData).length > 0 && shippingFormData.shipping_province) {
                // Restore all form fields
                $('#shipping_first_name').val(shippingFormData.shipping_first_name || '');
                $('#shipping_middle_name').val(shippingFormData.shipping_middle_name || '');
                $('#shipping_last_name').val(shippingFormData.shipping_last_name || '');
                $('#shipping_phone').val(shippingFormData.shipping_phone || '');
                $('#shipping_email').val(shippingFormData.shipping_email || '');
                $('#shipping_house_number').val(shippingFormData.shipping_house_number || '');
                $('#shipping_street').val(shippingFormData.shipping_street || '');
                $('#shipping_zone').val(shippingFormData.shipping_zone || '');
                $('#shipping_zip_code').val(shippingFormData.shipping_zip_code || '');

                // Restore shipping type and trigger info update
                if (shippingFormData.shipping_type) {
                    $('#shipping_type').val(shippingFormData.shipping_type);
                    updateShippingTypeInfo(shippingFormData.shipping_type);
                }

                // Restore province and trigger municipality load
                $('#shipping_province').val(shippingFormData.shipping_province);

                // Load municipalities and restore municipality selection
                loadMunicipalities(shippingFormData.shipping_province, function() {
                    if (shippingFormData.shipping_municipality) {
                        $('#shipping_municipality').val(shippingFormData.shipping_municipality);
                    }

                    // Restore barangay
                    if (shippingFormData.shipping_barangay) {
                        $('#shipping_barangay').val(shippingFormData.shipping_barangay);
                    }

                    // Recalculate with current products
                    if (selectedProducts && selectedProducts.length > 0) {
                        calculateShippingCosts();
                    } else {
                        clearShippingCalculation();
                    }
                });
            }
            // If no saved form data but there are ship products, the auto-selection
            // in populateProvinceDropdown() will handle setting Pangasinan and calculating
        }, 500);
    }

    // Previous button click
    $('#prev-btn').click(function() {
        showStep(currentStep - 1);
    });

    // Variables for change detection
    let pendingChangesCallback = null;
    let pendingUpdatedData = null;

    // Next button click - with async validation
    $('#next-btn').click(async function(e) {
        e.preventDefault();
        e.stopPropagation();

        console.log('Next button clicked, current step:', currentStep);

        // First, run basic validation
        if (!validateStep(currentStep)) {
            return false;
        }

        // Show loading state on button
        const $btn = $(this);
        const originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin me-1"></i>Validating...');

        try {
            // Run step-specific async validations
            const validationResult = await validateStepData(currentStep);

            $btn.prop('disabled', false).html(originalHtml);

            if (validationResult.hasChanges) {
                // Show changes modal and wait for user decision
                showChangesModal(validationResult.changes, validationResult.updateCallback, validationResult.updatedData);
            } else {
                // No changes, proceed to next step
                showStep(currentStep + 1);
            }
        } catch (error) {
            $btn.prop('disabled', false).html(originalHtml);
            console.error('Validation error:', error);
            // Show more detailed error message
            let errorMsg = 'An error occurred while validating. Please try again.';
            if (error && error.message) {
                errorMsg = error.message;
            }
            showErrorAlertModal(errorMsg);
        }

        return false;
    });

    // Async validation for each step's data
    async function validateStepData(step) {
        switch(step) {
            case 1:
                // Validate product prices and availability before moving to Step 2
                return await validateProductsBeforeNext();
            case 4:
                // Validate shipping rates before moving to Step 5
                return await validateShippingBeforeNext();
            case 5:
                // Validate discounts before moving to Step 6
                return await validateDiscountsBeforeNext();
            default:
                // No special validation needed for other steps
                return { hasChanges: false, changes: [], updateCallback: null, updatedData: null };
        }
    }

    // Validate products (Step 1 → Step 2)
    async function validateProductsBeforeNext() {
        const cartItems = selectedProducts.map(item => ({
            variantId: item.variantId,
            variantName: item.variantName,
            productId: item.productId,
            productName: item.productName,
            productStore: item.productStore,
            productType: item.productType,
            shipCoverage: item.shipCoverage,
            price: item.price,
            quantity: item.quantity,
            stocksAvailable: item.stocksAvailable,
            maxOrderPerTransaction: item.maxOrderPerTransaction
        }));

        return new Promise((resolve, reject) => {
            $.ajax({
                url: '{{ route("ecom-orders-custom-add.validate-product-prices") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    cartItems: JSON.stringify(cartItems)
                },
                success: function(response) {
                    if (response.success) {
                        resolve({
                            hasChanges: response.hasChanges,
                            changes: response.changes || [],
                            updatedData: response.updatedItems || [],
                            updateCallback: function(updatedItems) {
                                // Update selectedProducts with new data
                                selectedProducts = updatedItems.map(item => ({
                                    variantId: item.variantId,
                                    variantName: item.variantName,
                                    productId: item.productId,
                                    productName: item.productName,
                                    productStore: item.productStore,
                                    productType: item.productType,
                                    shipCoverage: item.shipCoverage,
                                    price: item.price,
                                    quantity: item.quantity,
                                    stocksAvailable: item.stocksAvailable,
                                    maxOrderPerTransaction: item.maxOrderPerTransaction
                                }));
                                $('#selectedProducts').val(JSON.stringify(selectedProducts));
                                updateCartDisplay();
                            }
                        });
                    } else {
                        reject(new Error(response.message || 'Validation failed'));
                    }
                },
                error: function(xhr) {
                    reject(new Error(xhr.responseJSON?.message || 'Network error'));
                }
            });
        });
    }

    // Validate shipping rates (Step 4 → Step 5)
    async function validateShippingBeforeNext() {
        // Filter only ship products for validation
        const shipProducts = selectedProducts.filter(item =>
            item.productType === 'ship' || item.productType === 'Ship'
        );

        // Skip validation if no ship products - only access products don't need shipping validation
        if (shipProducts.length === 0) {
            return {
                hasChanges: false,
                changes: [],
                updatedData: { newShipping: 0 },
                updateCallback: null
            };
        }

        const cartItems = selectedProducts.map(item => ({
            productId: item.productId,
            variantId: item.variantId,
            productType: item.productType,
            quantity: item.quantity
        }));

        // Get current shipping value from the display (only for ship products)
        let currentShipping = 0;
        const shippingText = $('#total-shipping').text();
        if (shippingText) {
            currentShipping = parseFloat(shippingText.replace(/[₱,]/g, '')) || 0;
        }

        const province = $('#shipping_province').val() || '';

        return new Promise((resolve, reject) => {
            $.ajax({
                url: '{{ route("ecom-orders-custom-add.validate-shipping-rates") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    cartItems: JSON.stringify(cartItems),
                    province: province,
                    currentShipping: currentShipping
                },
                success: function(response) {
                    if (response.success) {
                        resolve({
                            hasChanges: response.hasChanges,
                            changes: response.changes || [],
                            updatedData: { newShipping: response.newShipping },
                            updateCallback: function(updatedData) {
                                // Update shipping display
                                const newShipping = updatedData.newShipping || 0;
                                $('#total-shipping').text('₱' + formatNumber(newShipping));
                                orderShipping = newShipping;
                            }
                        });
                    } else {
                        reject(new Error(response.message || 'Validation failed'));
                    }
                },
                error: function(xhr, status, errorThrown) {
                    reject(new Error(xhr.responseJSON?.message || 'Shipping validation failed: ' + (errorThrown || 'Network error')));
                }
            });
        });
    }

    // Validate discounts (Step 5 → Step 6)
    async function validateDiscountsBeforeNext() {
        const cartItems = selectedProducts.map(item => ({
            productId: item.productId,
            productStore: item.productStore || '',
            variantId: item.variantId
        }));

        return new Promise((resolve, reject) => {
            $.ajax({
                url: '{{ route("ecom-orders-custom-add.validate-applied-discounts") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    appliedDiscounts: JSON.stringify(appliedDiscounts),
                    cartItems: JSON.stringify(cartItems)
                },
                success: function(response) {
                    if (response.success) {
                        resolve({
                            hasChanges: response.hasChanges,
                            changes: response.changes || [],
                            updatedData: {
                                validDiscounts: response.validDiscounts || [],
                                removedDiscounts: response.removedDiscounts || []
                            },
                            updateCallback: function(updatedData) {
                                // Update applied discounts with valid ones
                                appliedDiscounts = updatedData.validDiscounts.map(d => ({
                                    id: d.id,
                                    discountName: d.discountName,
                                    discountDescription: d.discountDescription,
                                    amountType: d.amountType,
                                    valuePercent: d.valuePercent,
                                    valueAmount: d.valueAmount,
                                    valueReplacement: d.valueReplacement,
                                    discountCapType: d.discountCapType,
                                    discountCapValue: d.discountCapValue,
                                    displayValue: d.displayValue,
                                    trigger: d.trigger,
                                    discountCode: d.discountCode || null
                                }));
                                renderAppliedDiscountsTable();
                                calculateDiscountTotals();
                            }
                        });
                    } else {
                        reject(new Error(response.message || 'Validation failed'));
                    }
                },
                error: function(xhr) {
                    reject(new Error(xhr.responseJSON?.message || 'Network error'));
                }
            });
        });
    }

    // Show changes modal
    function showChangesModal(changes, updateCallback, updatedData) {
        pendingChangesCallback = updateCallback;
        pendingUpdatedData = updatedData;

        // Build the changes list HTML
        let changesHtml = '<div class="list-group">';

        changes.forEach(function(change) {
            let iconClass = 'mdi-information-outline';
            let bgClass = 'list-group-item-warning';

            switch(change.type) {
                case 'removed':
                case 'out_of_stock':
                case 'deactivated':
                case 'expired':
                case 'restriction_mismatch':
                    iconClass = 'mdi-close-circle';
                    bgClass = 'list-group-item-danger';
                    break;
                case 'price_change':
                case 'value_change':
                case 'shipping_change':
                    iconClass = 'mdi-currency-usd';
                    bgClass = 'list-group-item-info';
                    break;
                case 'stock_reduced':
                case 'max_order_exceeded':
                    iconClass = 'mdi-package-variant';
                    bgClass = 'list-group-item-warning';
                    break;
            }

            changesHtml += `
                <div class="list-group-item ${bgClass}">
                    <div class="d-flex align-items-start">
                        <i class="mdi ${iconClass} me-3" style="font-size: 1.5rem;"></i>
                        <div>
                            <strong>${escapeHtml(change.variantName || change.discountName || 'Item')}</strong>
                            <p class="mb-0 small">${escapeHtml(change.message)}</p>
                        </div>
                    </div>
                </div>
            `;
        });

        changesHtml += '</div>';

        $('#changesListContainer').html(changesHtml);
        $('#changesDetectedModal').modal('show');
    }

    // Accept changes button handler
    $('#acceptChangesBtn').click(function() {
        if (pendingChangesCallback && pendingUpdatedData) {
            // Apply the updates
            pendingChangesCallback(pendingUpdatedData);
        }

        // Close modal
        $('#changesDetectedModal').modal('hide');
        pendingChangesCallback = null;
        pendingUpdatedData = null;

        // Stay on current step so user can review the changes
        // Show toastr notification
        toastr.success('The changes have been applied. Please review the updated information before proceeding.', 'Changes Applied', {
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            timeOut: 5000,
            extendedTimeOut: 2000
        });

        // Scroll to top of the step content for better visibility
        $('.wizard-step:not(.d-none)').first().get(0)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
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

    // Phone number validation function (only accepts 09XXXXXXXXX format)
    function isValidPhoneNumber(phone) {
        // Remove any spaces or dashes
        const cleanPhone = phone.replace(/[\s-]/g, '');

        // Only accept 09XXXXXXXXX format (11 digits starting with 09)
        const format09 = /^09\d{9}$/;

        return format09.test(cleanPhone);
    }

    // Email validation function
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // Check phone number uniqueness for clients
    function checkPhoneNumberUniqueness(phoneNumber) {
        return new Promise(function(resolve, reject) {
            $.ajax({
                url: '{{ route("ecom-orders-custom-add.check-client-phone") }}',
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

    // Check email uniqueness for clients
    function checkClientEmailUniqueness(email) {
        return new Promise(function(resolve, reject) {
            $.ajax({
                url: '{{ route("ecom-orders-custom-add.check-client-email") }}',
                type: 'GET',
                data: { email: email },
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
                feedback.text('Phone number must be in format: 09XXXXXXXXX (11 digits starting with 09)');
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
            } else {
                // Check email uniqueness
                const uniquenessResult = await checkClientEmailUniqueness(value);
                if (uniquenessResult.success && uniquenessResult.exists) {
                    field.addClass('is-invalid');
                    feedback.text('This email address already exists in the database.');
                    return false;
                }
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
                url: '{{ route("ecom-orders-custom-add.save-client") }}',
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
            url: '{{ route("ecom-orders-custom-add.store") }}',
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
            url: '{{ route("ecom-orders-custom-add.clients") }}',
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

            // Store client data as JSON for onclick
            const clientDataJson = JSON.stringify({
                id: client.id,
                firstName: client.clientFirstName || '',
                middleName: client.clientMiddleName || '',
                lastName: client.clientLastName || '',
                fullName: fullName,
                phone: client.clientPhoneNumber || '',
                email: client.clientEmailAddress || ''
            }).replace(/"/g, '&quot;');

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
                                onclick="selectClient('${clientDataJson}')"
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
    window.selectClient = function(clientDataJson) {
        // Parse the JSON string to get client data
        const clientData = typeof clientDataJson === 'string' ? JSON.parse(clientDataJson) : clientDataJson;
        const clientId = clientData.id;

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

            // Store selected client with all name fields
            selectedClient = {
                id: clientData.id,
                firstName: clientData.firstName || '',
                middleName: clientData.middleName || '',
                lastName: clientData.lastName || '',
                fullName: clientData.fullName || '',
                phone: clientData.phone || '',
                email: clientData.email || ''
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

    // Load ship products by store for step 4
    function loadShipProductsStores() {
        // Reset shipping error state
        hasShippingErrors = false;
        selectedShippingMethods = {};
        pendingShippingLoads = 0;
        shippingOptionsLoading = false;
        $('#shipping-error-banner').hide();

        // Filter ship products
        const shipProducts = selectedProducts.filter(product => {
            return product.productType === 'ship' || product.productType === 'Ship';
        });

        // Filter access products
        const accessProducts = selectedProducts.filter(product => {
            return product.productType === 'access' || product.productType === 'Access';
        });

        // Display access products section if any exist
        loadAccessProducts(accessProducts);

        if (shipProducts.length === 0) {
            $('#no-ship-products').show();
            $('#ship-stores-container').hide();
            $('#shipping-address-section').hide();
            $('#shipping-type-section').hide();

            // Remove required attribute from shipping fields when hidden
            $('#shipping-address-section input[required], #shipping-address-section select[required]').each(function() {
                $(this).removeAttr('required').data('was-required', true);
            });

            // Clear shipping form data and form fields when there are no ship products
            shippingFormData = {};
            $('#shipping-address-section input, #shipping-address-section select').val('');

            // Calculate totals for access products (no province needed)
            if (selectedProducts && selectedProducts.length > 0) {
                // Small delay to ensure everything is loaded
                setTimeout(function() {
                    calculateShippingCosts();
                }, 300);
            } else {
                clearShippingCalculation();
            }

            return;
        } else {
            $('#no-ship-products').hide();
            $('#ship-stores-container').show();
            $('#shipping-address-section').show();
            $('#shipping-type-section').show();

            // Restore required attribute to shipping fields when shown
            $('#shipping-address-section input[data-was-required], #shipping-address-section select[data-was-required]').each(function() {
                $(this).attr('required', 'required');
            });
        }

        // Get unique stores from ship products
        const uniqueStores = [...new Set(shipProducts.map(product => product.productStore || 'Unknown Store'))];

        // Display stores and load products for each store
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
                                            <button type="button" class="btn btn-outline-info btn-sm" id="view-ship-products-${storeId}" title="View Products for this Store">
                                                <i class="mdi mdi-package-variant me-1"></i>View Products
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex justify-content-end">
                                            <small class="text-muted">
                                                <i class="mdi mdi-package-variant me-1"></i>
                                                <span id="ship-products-count-${storeId}">0</span> ship products
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Products List -->
                                <div id="ship-products-${storeId}" class="ship-products-container">
                                    <div class="text-center py-3">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="mt-2 text-muted">Loading ship products...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        $('#ship-stores-container').html(storesHtml);
        $('#no-ship-products').hide();
        $('#ship-stores-container').show();

        // Set loading state - count stores with ship products that will need shipping options
        const storesWithProducts = uniqueStores.filter(store => {
            const storeShipProducts = selectedProducts.filter(product =>
                product.productStore === store &&
                (product.productType === 'ship' || product.productType === 'Ship')
            );
            return storeShipProducts.length > 0;
        });

        if (storesWithProducts.length > 0) {
            pendingShippingLoads = storesWithProducts.length;
            shippingOptionsLoading = true;
            // Disable Next button while loading
            updateNextButtonState();
        }

        // Load ship products for each store
        uniqueStores.forEach(store => {
            loadShipProductsForStore(store);
        });

        // Add event listeners for view products buttons
        uniqueStores.forEach(store => {
            const storeId = store.replace(/\s+/g, '-').toLowerCase();

            // View products button
            $(`#view-ship-products-${storeId}`).on('click', function() {
                showStoreProductsModal(store);
            });
        });
    }

    // Load ship products for a specific store
    function loadShipProductsForStore(store) {
        const storeId = store.replace(/\s+/g, '-').toLowerCase();
        const containerId = `#ship-products-${storeId}`;

        // Filter ship products for this store
        const storeShipProducts = selectedProducts.filter(product =>
            product.productStore === store &&
            (product.productType === 'ship' || product.productType === 'Ship')
        );

        // Update count
        $(`#ship-products-count-${storeId}`).text(storeShipProducts.length);

        if (storeShipProducts.length === 0) {
            $(containerId).html(`
                <div class="text-center py-3">
                    <i class="mdi mdi-package-variant-closed text-muted" style="font-size: 2rem;"></i>
                    <p class="text-muted mt-2">No ship products selected for ${store}</p>
                </div>
            `);
            return;
        }

        // Get the selected province and shipping type
        const province = $('#shipping_province').val() || '';
        const shippingType = $('#shipping_type').val() || '';

        // If no province or shipping type selected, show message prompting user to select
        if (!province || !shippingType) {
            let missingFields = [];
            if (!shippingType) missingFields.push('Shipping Type');
            if (!province) missingFields.push('Province');

            $(containerId).html(`
                <div class="alert alert-info mb-0">
                    <i class="mdi mdi-information-outline me-2"></i>
                    <strong>Selection Required:</strong> Please select ${missingFields.join(' and ')} above to view available shipping options for these products.
                </div>
                <div class="mt-3">
                    <h6 class="text-dark mb-2">Products awaiting shipping configuration (${storeShipProducts.length})</h6>
                    ${storeShipProducts.map(product => `
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                <span class="text-dark">${product.productName || 'Unnamed Product'}</span>
                                <small class="text-secondary ms-2">${product.variantName || 'Default'}</small>
                            </div>
                            <span class="badge bg-secondary">Qty: ${product.quantity || 1}</span>
                        </div>
                    `).join('')}
                </div>
            `);
            // Decrement pending loads since we're not making an AJAX call
            decrementPendingShippingLoads();
            return;
        }

        // Show loading state while fetching shipping options
        $(containerId).html(`
            <div class="text-center py-3">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading shipping options...</p>
            </div>
        `);

        // Get shipping options for these products
        loadShippingOptionsForStore(store, storeShipProducts, province, shippingType);
    }

    // Load shipping options for products in a store
    function loadShippingOptionsForStore(store, storeShipProducts, province, shippingType) {
        const storeId = store.replace(/\s+/g, '-').toLowerCase();
        const containerId = `#ship-products-${storeId}`;

        $.ajax({
            url: '{{ route("ecom-orders-custom-add.get-shipping-options") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                selectedProducts: storeShipProducts,
                province: province,
                shippingType: shippingType
            },
            success: function(response) {
                if (response.success) {
                    displayShipProductsWithShipping(containerId, storeShipProducts, response.data);
                } else {
                    $(containerId).html(`
                        <div class="alert alert-danger">
                            <i class="mdi mdi-alert-circle me-2"></i>
                            Error loading shipping options: ${response.message}
                        </div>
                    `);
                    // Mark as having errors and decrement pending counter
                    hasShippingErrors = true;
                    decrementPendingShippingLoads();
                }
            },
            error: function(xhr) {
                $(containerId).html(`
                    <div class="alert alert-danger">
                        <i class="mdi mdi-alert-circle me-2"></i>
                        Error loading shipping options. Please try again.
                    </div>
                `);
                // Mark as having errors and decrement pending counter
                hasShippingErrors = true;
                decrementPendingShippingLoads();
            }
        });
    }

    // Helper function to decrement pending shipping loads and update state
    function decrementPendingShippingLoads() {
        pendingShippingLoads--;
        if (pendingShippingLoads <= 0) {
            pendingShippingLoads = 0;
            shippingOptionsLoading = false;

            // Show shipping method selection if multiple methods available
            updateShippingMethodSelection();

            // Recalculate shipping costs when all loads are complete
            // This ensures the Shipping & Total Calculation section is updated
            calculateShippingCosts();
        }
        updateNextButtonState();
    }

    // Update the shipping method selection UI
    function updateShippingMethodSelection() {
        const methodIds = Object.keys(availableShippingMethods);
        const $section = $('#shipping-method-section');
        const $list = $('#shipping-methods-list');

        // Only show if there are 2 or more shipping methods available
        if (methodIds.length >= 2) {
            let methodsHtml = '';

            methodIds.forEach(methodId => {
                const method = availableShippingMethods[methodId];
                const isSelected = selectedGlobalShippingMethod == methodId;

                // Count how many products can use this method
                let applicableCount = 0;
                Object.keys(allProductShippingData).forEach(variantId => {
                    const data = allProductShippingData[variantId];
                    if (data.shippingOptions.some(o => o.shippingId == methodId)) {
                        applicableCount++;
                    }
                });

                const totalProducts = Object.keys(allProductShippingData).length;
                const applicableText = applicableCount === totalProducts
                    ? 'All products'
                    : `${applicableCount} of ${totalProducts} products`;

                methodsHtml += `
                    <div class="col-md-6 col-lg-4">
                        <div class="card shipping-method-card h-100 ${isSelected ? 'border-primary selected' : 'border-secondary'}"
                             data-method-id="${method.shippingId}"
                             style="cursor: pointer; transition: all 0.2s ease;">
                            <div class="card-body text-center py-3">
                                <div class="mb-2">
                                    <i class="mdi mdi-truck-delivery text-primary" style="font-size: 2rem;"></i>
                                </div>
                                <h6 class="card-title text-dark mb-1">${escapeHtml(method.shippingName)}</h6>
                                <p class="text-secondary small mb-2">${escapeHtml(method.shippingType || 'Regular')}</p>
                                <div class="mb-2">
                                    <span class="badge bg-success">₱${parseFloat(method.pricePerBatch).toFixed(2)} per ${method.maxQuantity} items</span>
                                </div>
                                <small class="text-muted">${applicableText}</small>
                                ${isSelected ? '<div class="mt-2"><i class="mdi mdi-check-circle text-primary" style="font-size: 1.5rem;"></i></div>' : ''}
                            </div>
                        </div>
                    </div>
                `;
            });

            $list.html(methodsHtml);
            $section.show();

            // Bind click events for method cards
            $('.shipping-method-card').on('click', function() {
                const methodId = parseInt($(this).data('method-id'));
                selectGlobalShippingMethod(methodId);
            });

            // Update the selected info display
            if (selectedGlobalShippingMethod) {
                const method = availableShippingMethods[selectedGlobalShippingMethod];
                if (method) {
                    $('#selected-method-name').text(method.shippingName);
                    $('#selected-method-price').text('₱' + parseFloat(method.pricePerBatch).toFixed(2) + ' per ' + method.maxQuantity + ' items');
                    $('#selected-shipping-method-info').show();
                }
            }
        } else {
            // Hide the section if only 0 or 1 method available
            $section.hide();
            selectedGlobalShippingMethod = null;
        }
    }

    // Select a global shipping method
    function selectGlobalShippingMethod(methodId) {
        selectedGlobalShippingMethod = methodId;
        const method = availableShippingMethods[methodId];

        // Update card styling
        $('.shipping-method-card').removeClass('border-primary selected').addClass('border-secondary');
        $(`.shipping-method-card[data-method-id="${methodId}"]`).removeClass('border-secondary').addClass('border-primary selected');

        // Add checkmark to selected card
        $('.shipping-method-card .mdi-check-circle').parent().remove();
        $(`.shipping-method-card[data-method-id="${methodId}"] .card-body`).append(
            '<div class="mt-2"><i class="mdi mdi-check-circle text-primary" style="font-size: 1.5rem;"></i></div>'
        );

        // Update selected info display
        $('#selected-method-name').text(method.shippingName);
        $('#selected-method-price').text('₱' + parseFloat(method.pricePerBatch).toFixed(2) + ' per ' + method.maxQuantity + ' items');
        $('#selected-shipping-method-info').show();

        // Apply to all products that support this method
        Object.keys(allProductShippingData).forEach(variantId => {
            const data = allProductShippingData[variantId];
            const hasMethod = data.shippingOptions.some(o => o.shippingId == methodId);

            if (hasMethod) {
                selectedShippingMethods[variantId] = methodId;

                // Update the display for this product
                const $card = $(`[data-variant-id="${variantId}"]`);
                const option = data.shippingOptions.find(o => o.shippingId == methodId);
                if ($card.length && option) {
                    // Update shipping method column to show display instead of selector
                    $card.find('.shipping-method-column').html(generateShippingDisplay(option));

                    // Update shipping cost display
                    $card.find('.shipping-cost-display').text('₱' + option.shippingCost.toFixed(2));
                }
            }
        });

        // Recalculate shipping costs
        calculateShippingCosts();

        toastr.success(`Shipping method "${method.shippingName}" applied to all applicable products.`, 'Shipping Method Selected');
    }

    // Display ship products with shipping options
    function displayShipProductsWithShipping(containerId, storeShipProducts, shippingData) {
        // Check if this is a package purchase
        const isPackagePurchase = activePackage !== null;

        let productsHtml = `
            <div class="mb-3">
                <h6 class="text-dark">Selected Ship Products (${storeShipProducts.length})</h6>
            </div>
        `;

        // If package purchase, show package info banner
        if (isPackagePurchase) {
            productsHtml += `
                <div class="alert alert-info mb-3">
                    <i class="mdi mdi-package-variant-closed me-2"></i>
                    <strong>Package Purchase:</strong> ${escapeHtml(activePackage.packageName)}
                    <span class="badge bg-success ms-2">Package Price: ₱${parseFloat(activePackage.packagePrice).toFixed(2)}</span>
                </div>
            `;
        }

        let storeHasShippingErrors = false;

        storeShipProducts.forEach((product, index) => {
            const quantity = product.quantity || 1;
            const price = parseFloat(product.price) || 0;
            const subtotal = quantity * price;
            const variantId = product.variantId;

            // Find shipping data for this product
            const productShipping = shippingData.products.find(p => p.variantId == variantId);
            const hasShipping = productShipping && productShipping.hasShippingOptions;
            const shippingOptions = productShipping ? productShipping.shippingOptions : [];

            if (!hasShipping) {
                storeHasShippingErrors = true;
                hasShippingErrors = true;
            }

            // Store shipping options for this variant (for global method selection)
            allProductShippingData[variantId] = {
                product: product,
                shippingOptions: shippingOptions,
                hasShipping: hasShipping
            };

            // Collect all unique shipping methods for global selection
            shippingOptions.forEach(option => {
                if (!availableShippingMethods[option.shippingId]) {
                    availableShippingMethods[option.shippingId] = {
                        shippingId: option.shippingId,
                        shippingName: option.shippingName,
                        shippingType: option.shippingType,
                        shippingDescription: option.shippingDescription,
                        pricePerBatch: option.pricePerBatch,
                        maxQuantity: option.maxQuantity
                    };
                }
            });

            // Initialize selected shipping method
            // If global method is selected and available for this product, use it
            // Otherwise use first available option
            if (hasShipping) {
                if (selectedGlobalShippingMethod && shippingOptions.find(o => o.shippingId == selectedGlobalShippingMethod)) {
                    selectedShippingMethods[variantId] = selectedGlobalShippingMethod;
                } else if (!selectedShippingMethods[variantId]) {
                    selectedShippingMethods[variantId] = shippingOptions[0].shippingId;
                }
            }

            const selectedShippingId = selectedShippingMethods[variantId];
            const selectedOption = shippingOptions.find(o => o.shippingId == selectedShippingId);
            const shippingCost = selectedOption ? selectedOption.shippingCost : 0;

            // Determine if we should show the selector (hide if global method is selected)
            const showSelector = !selectedGlobalShippingMethod || shippingOptions.length <= 1;

            if (isPackagePurchase) {
                // Package purchase - hide price column, show only product info and shipping
                productsHtml += `
                    <div class="card mb-2 ${!hasShipping ? 'border-danger' : ''}" data-variant-id="${variantId}">
                        <div class="card-body py-2">
                            <div class="row align-items-center">
                                <div class="col-md-4">
                                    <h6 class="mb-1 text-dark">${product.productName || 'Unnamed Product'}</h6>
                                    <small class="text-secondary">${product.variantName || 'Default'}</small>
                                </div>
                                <div class="col-md-2 text-center">
                                    <small class="text-secondary d-block">Qty</small>
                                    <span class="fw-bold text-primary">${quantity}</span>
                                </div>
                                <div class="col-md-4 shipping-method-column">
                                    ${hasShipping ? (showSelector ? generateShippingSelector(variantId, shippingOptions, selectedShippingId) : generateShippingDisplay(selectedOption)) : `
                                        <div class="alert alert-danger mb-0 py-1 px-2">
                                            <small><i class="mdi mdi-alert-circle me-1"></i>No shipping method configured</small>
                                        </div>
                                    `}
                                </div>
                                <div class="col-md-2 text-end">
                                    <small class="text-secondary d-block">Shipping</small>
                                    <span class="fw-bold shipping-cost-display ${hasShipping ? 'text-success' : 'text-danger'}">
                                        ${hasShipping ? '₱' + shippingCost.toFixed(2) : 'N/A'}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                // Regular purchase - show all columns including price
                productsHtml += `
                    <div class="card mb-2 ${!hasShipping ? 'border-danger' : ''}" data-variant-id="${variantId}">
                        <div class="card-body py-2">
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <h6 class="mb-1 text-dark">${product.productName || 'Unnamed Product'}</h6>
                                    <small class="text-secondary">${product.variantName || 'Default'}</small>
                                </div>
                                <div class="col-md-2 text-center">
                                    <small class="text-secondary d-block">Qty</small>
                                    <span class="fw-bold text-primary">${quantity}</span>
                                </div>
                                <div class="col-md-2 text-center">
                                    <small class="text-secondary d-block">Price</small>
                                    <span class="text-dark">₱${price.toFixed(2)}</span>
                                </div>
                                <div class="col-md-3 shipping-method-column">
                                    ${hasShipping ? (showSelector ? generateShippingSelector(variantId, shippingOptions, selectedShippingId) : generateShippingDisplay(selectedOption)) : `
                                        <div class="alert alert-danger mb-0 py-1 px-2">
                                            <small><i class="mdi mdi-alert-circle me-1"></i>No shipping method configured</small>
                                        </div>
                                    `}
                                </div>
                                <div class="col-md-2 text-end">
                                    <small class="text-secondary d-block">Shipping</small>
                                    <span class="fw-bold shipping-cost-display ${hasShipping ? 'text-success' : 'text-danger'}">
                                        ${hasShipping ? '₱' + shippingCost.toFixed(2) : 'N/A'}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
        });

        // Add error message if any product has no shipping
        if (storeHasShippingErrors) {
            productsHtml = `
                <div class="alert alert-danger mb-3">
                    <i class="mdi mdi-alert-circle me-2"></i>
                    <strong>Shipping Configuration Required:</strong> Some products do not have shipping methods configured.
                    Please <a href="{{ route('ecom-shipping') }}" target="_blank">configure shipping settings</a> before proceeding.
                </div>
            ` + productsHtml;
        }

        $(containerId).html(productsHtml);

        // Decrement pending loads counter and update Next button state
        decrementPendingShippingLoads();

        // Bind change events for shipping selectors
        $(containerId).find('.shipping-method-select').on('change', function() {
            const variantId = $(this).data('variant-id');
            const shippingId = $(this).val();
            selectedShippingMethods[variantId] = parseInt(shippingId);

            // Clear global selection since user is manually selecting per product
            selectedGlobalShippingMethod = null;
            $('.shipping-method-card').removeClass('border-primary selected');
            $('#selected-shipping-method-info').hide();

            // Recalculate shipping costs
            calculateShippingCosts();
        });
    }

    // Generate a display-only shipping method badge (when global method is selected)
    function generateShippingDisplay(option) {
        if (!option) return '<span class="text-muted">-</span>';
        return `
            <div class="small">
                <span class="badge bg-info text-white">${option.shippingName}</span>
                <div class="text-secondary mt-1">₱${option.pricePerBatch.toFixed(2)} per ${option.maxQuantity} items</div>
            </div>
        `;
    }

    // Generate shipping method selector HTML
    function generateShippingSelector(variantId, shippingOptions, selectedShippingId) {
        if (shippingOptions.length === 0) {
            return '<span class="text-danger">No shipping available</span>';
        }

        if (shippingOptions.length === 1) {
            // Single option - just display it
            const option = shippingOptions[0];
            return `
                <div class="small">
                    <span class="badge bg-info text-white">${option.shippingName}</span>
                    <div class="text-secondary mt-1">₱${option.pricePerBatch.toFixed(2)} per ${option.maxQuantity} items</div>
                </div>
            `;
        }

        // Multiple options - show selector
        let selectHtml = `
            <select class="form-select form-select-sm shipping-method-select" data-variant-id="${variantId}">
        `;

        shippingOptions.forEach(option => {
            const selected = option.shippingId == selectedShippingId ? 'selected' : '';
            selectHtml += `
                <option value="${option.shippingId}" ${selected}>
                    ${option.shippingName} - ₱${option.shippingCost.toFixed(2)}
                </option>
            `;
        });

        selectHtml += '</select>';
        selectHtml += `<small class="text-secondary">${shippingOptions.length} methods available</small>`;

        return selectHtml;
    }

    // Update shipping type info display
    function updateShippingTypeInfo(shippingType) {
        const $infoDiv = $('#shipping-type-info');

        if (!shippingType) {
            $infoDiv.html('');
            return;
        }

        let infoHtml = '';
        switch (shippingType) {
            case 'Regular':
                infoHtml = `
                    <div class="alert alert-primary mb-0 py-2">
                        <i class="mdi mdi-truck me-2"></i>
                        <strong>Regular Shipping</strong>
                        <p class="mb-0 small mt-1">Standard delivery to your address. Payment is made during checkout.</p>
                    </div>
                `;
                break;
            case 'Cash on Delivery':
                infoHtml = `
                    <div class="alert alert-info mb-0 py-2">
                        <i class="mdi mdi-cash-multiple me-2"></i>
                        <strong>Cash on Delivery (COD)</strong>
                        <p class="mb-0 small mt-1">Pay in cash when the order is delivered to your doorstep.</p>
                    </div>
                `;
                break;
            case 'Cash on Pickup':
                infoHtml = `
                    <div class="alert alert-warning mb-0 py-2">
                        <i class="mdi mdi-store me-2"></i>
                        <strong>Cash on Pickup</strong>
                        <p class="mb-0 small mt-1">Pick up your order at a designated location and pay in cash.</p>
                    </div>
                `;
                break;
        }

        $infoDiv.html(infoHtml);
    }

    // Update Next button state based on shipping errors and loading state
    function updateNextButtonState() {
        if (currentStep !== 4) {
            return; // Only manage button state for Step 4
        }

        const $nextBtn = $('#next-btn');
        const province = $('#shipping_province').val();
        const shippingType = $('#shipping_type').val();

        // Check if there are ship products
        const shipProducts = selectedProducts.filter(product =>
            product.productType === 'ship' || product.productType === 'Ship'
        );

        // Check if shipping type or province is not selected when there are ship products
        if (shipProducts.length > 0 && (!province || !shippingType)) {
            $nextBtn.prop('disabled', true)
                .addClass('btn-secondary')
                .removeClass('btn-primary')
                .html('<i class="bx bx-right-arrow-alt me-1"></i>Next');
            $('#shipping-error-banner').hide();
            return;
        }

        // Check if still loading shipping options
        if (shippingOptionsLoading || pendingShippingLoads > 0) {
            $nextBtn.prop('disabled', true)
                .addClass('btn-secondary')
                .removeClass('btn-primary')
                .html('<i class="bx bx-loader-alt bx-spin me-1"></i>Loading Shipping...');
            $('#shipping-error-banner').hide();
            return;
        }

        // Check if there are shipping errors
        if (hasShippingErrors) {
            $nextBtn.prop('disabled', true)
                .addClass('btn-secondary')
                .removeClass('btn-primary')
                .html('<i class="bx bx-right-arrow-alt me-1"></i>Next');
            $('#shipping-error-banner').show();
        } else {
            $nextBtn.prop('disabled', false)
                .addClass('btn-primary')
                .removeClass('btn-secondary')
                .html('<i class="bx bx-right-arrow-alt me-1"></i>Next');
            $('#shipping-error-banner').hide();
        }
    }

    // Load access products (digital/non-shipping products) for step 4
    function loadAccessProducts(accessProducts) {
        const $section = $('#access-products-section');
        const $list = $('#access-products-list');
        const $subtotal = $('#access-products-subtotal');
        const $subtotalRow = $section.find('.border-top');

        if (!accessProducts || accessProducts.length === 0) {
            $section.hide();
            $list.html('');
            $subtotal.text('₱0.00');
            return;
        }

        // Show the section
        $section.show();

        // Check if this is a package purchase
        const isPackagePurchase = activePackage !== null;

        // Calculate subtotal (only used for non-package purchases)
        let totalSubtotal = 0;

        // Generate products HTML
        let productsHtml = '';

        // If package purchase, show package info banner
        if (isPackagePurchase) {
            productsHtml += `
                <div class="alert alert-info mb-3">
                    <i class="mdi mdi-package-variant-closed me-2"></i>
                    <strong>Package Purchase:</strong> ${escapeHtml(activePackage.packageName)}
                    <span class="badge bg-success ms-2">Package Price: ₱${parseFloat(activePackage.packagePrice).toFixed(2)}</span>
                </div>
            `;
        }

        accessProducts.forEach((product, index) => {
            const quantity = product.quantity || 1;
            const price = parseFloat(product.price) || 0;
            const subtotal = quantity * price;
            totalSubtotal += subtotal;

            if (isPackagePurchase) {
                // Package purchase - hide price and subtotal, show only product info
                productsHtml += `
                    <div class="card mb-2 border-0 bg-light">
                        <div class="card-body py-2 px-3">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <i class="mdi mdi-key-variant text-primary me-2"></i>
                                        <div>
                                            <h6 class="mb-0 text-dark">${product.productName || 'Unnamed Product'}</h6>
                                            <small class="text-secondary">${product.variantName || 'Default Variant'}</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-secondary d-block">Store</small>
                                    <span class="text-dark">${product.productStore || 'N/A'}</span>
                                </div>
                                <div class="col-md-3 text-center">
                                    <small class="text-secondary d-block">Quantity</small>
                                    <span class="badge bg-info text-white">${quantity}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                // Regular purchase - show all columns including price and subtotal
                productsHtml += `
                    <div class="card mb-2 border-0 bg-light">
                        <div class="card-body py-2 px-3">
                            <div class="row align-items-center">
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center">
                                        <i class="mdi mdi-key-variant text-primary me-2"></i>
                                        <div>
                                            <h6 class="mb-0 text-dark">${product.productName || 'Unnamed Product'}</h6>
                                            <small class="text-secondary">${product.variantName || 'Default Variant'}</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <small class="text-secondary d-block">Store</small>
                                    <span class="text-dark">${product.productStore || 'N/A'}</span>
                                </div>
                                <div class="col-md-2 text-center">
                                    <small class="text-secondary d-block">Quantity</small>
                                    <span class="badge bg-info text-white">${quantity}</span>
                                </div>
                                <div class="col-md-2 text-center">
                                    <small class="text-secondary d-block">Price</small>
                                    <span class="text-dark">₱${price.toFixed(2)}</span>
                                </div>
                                <div class="col-md-2 text-end">
                                    <small class="text-secondary d-block">Subtotal</small>
                                    <span class="fw-bold text-success">₱${subtotal.toFixed(2)}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
        });

        $list.html(productsHtml);

        // Hide subtotal row for package purchases, show for regular
        if (isPackagePurchase) {
            $subtotalRow.hide();
        } else {
            $subtotalRow.show();
            $subtotal.text('₱' + totalSubtotal.toFixed(2));
        }
    }

    // Philippine Location Data and Functions
    let philippineProvinces = [];
    let philippineMunicipalities = {};

    // Load Philippine provinces
    function loadPhilippineProvinces() {
        const provinceSelect = $('#shipping_province');

        console.log('loadPhilippineProvinces called, current products:', selectedProducts.length);

        // Check if provinces are already loaded
        if (philippineProvinces.length > 0) {
            console.log('Provinces already cached, populating dropdown');
            populateProvinceDropdown();
            return;
        }

        // Show loading state
        provinceSelect.html('<option value="">Loading provinces...</option>');

        // Load provinces from API or static data
        $.ajax({
            url: '{{ route("ecom-orders-custom-add.philippine-provinces") }}',
            type: 'GET',
            success: function(response) {
                if (response.success && response.data) {
                    philippineProvinces = response.data;
                    populateProvinceDropdown();
                } else {
                    // Fallback to static data if API fails
                    loadStaticProvinces();
                }
            },
            error: function() {
                // Fallback to static data
                loadStaticProvinces();
            }
        });
    }

    // Fallback static provinces data
    function loadStaticProvinces() {
        philippineProvinces = [
            'Abra', 'Agusan del Norte', 'Agusan del Sur', 'Aklan', 'Albay', 'Antique', 'Apayao',
            'Aurora', 'Basilan', 'Bataan', 'Batanes', 'Batangas', 'Benguet', 'Biliran', 'Bohol',
            'Bukidnon', 'Bulacan', 'Cagayan', 'Camarines Norte', 'Camarines Sur', 'Camiguin',
            'Capiz', 'Catanduanes', 'Cavite', 'Cebu', 'Cotabato', 'Davao de Oro', 'Davao del Norte',
            'Davao del Sur', 'Davao Occidental', 'Davao Oriental', 'Dinagat Islands', 'Eastern Samar',
            'Guimaras', 'Ifugao', 'Ilocos Norte', 'Ilocos Sur', 'Iloilo', 'Isabela', 'Kalinga',
            'Laguna', 'Lanao del Norte', 'Lanao del Sur', 'La Union', 'Leyte', 'Maguindanao',
            'Marinduque', 'Masbate', 'Misamis Occidental', 'Misamis Oriental', 'Mountain Province',
            'Negros Occidental', 'Negros Oriental', 'Northern Samar', 'Nueva Ecija', 'Nueva Vizcaya',
            'Occidental Mindoro', 'Oriental Mindoro', 'Palawan', 'Pampanga', 'Pangasinan',
            'Quezon', 'Quirino', 'Rizal', 'Romblon', 'Samar', 'Sarangani', 'Siquijor',
            'Sorsogon', 'South Cotabato', 'Southern Leyte', 'Sultan Kudarat', 'Sulu',
            'Surigao del Norte', 'Surigao del Sur', 'Tarlac', 'Tawi-Tawi', 'Zambales',
            'Zamboanga del Norte', 'Zamboanga del Sur', 'Zamboanga Sibugay'
        ];
        populateProvinceDropdown();
    }

    // Populate province dropdown
    function populateProvinceDropdown() {
        const provinceSelect = $('#shipping_province');
        provinceSelect.html('<option value="">Select Province</option>');

        philippineProvinces.forEach(province => {
            provinceSelect.append(`<option value="${province}">${province}</option>`);
        });

        // Only restore saved province if available - otherwise leave as "Select Province"
        const hasSavedProvince = shippingFormData.shipping_province && shippingFormData.shipping_province !== '';

        if (hasSavedProvince) {
            console.log('Restoring saved province:', shippingFormData.shipping_province);
            provinceSelect.val(shippingFormData.shipping_province);

            // Load municipalities for saved province
            loadMunicipalities(shippingFormData.shipping_province, function() {
                console.log('Municipalities loaded for saved province');
                // Trigger shipping calculation after restoring province
                if (selectedProducts && selectedProducts.length > 0) {
                    calculateShippingCosts();
                }
            });
        }
        // If no saved province, leave dropdown at "Select Province" - user must select to trigger validation
    }

    // Load municipalities for selected province
    function loadMunicipalities(province, callback) {
        const municipalitySelect = $('#shipping_municipality');

        if (!province) {
            municipalitySelect.html('<option value="">Select Municipality/City</option>').prop('disabled', true);
            return;
        }

        // Check if municipalities for this province are already loaded
        if (philippineMunicipalities[province]) {
            populateMunicipalityDropdown(philippineMunicipalities[province]);
            if (callback) callback();
            return;
        }

        // Show loading state
        municipalitySelect.html('<option value="">Loading municipalities...</option>').prop('disabled', false);

        // Load municipalities from API
        $.ajax({
            url: '{{ route("ecom-orders-custom-add.philippine-municipalities") }}',
            type: 'GET',
            data: { province: province },
            success: function(response) {
                if (response.success && response.data) {
                    philippineMunicipalities[province] = response.data;
                    populateMunicipalityDropdown(response.data);
                    if (callback) callback();
                } else {
                    municipalitySelect.html('<option value="">No municipalities found for ' + province + '</option>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading municipalities for', province, ':', error);
                municipalitySelect.html('<option value="">Error loading municipalities. Please try again.</option>');

                // Show user-friendly error message
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    showErrorAlertModal('Error loading municipalities: ' + xhr.responseJSON.message);
                } else {
                    showErrorAlertModal('Error loading municipalities for ' + province + '. Please try again.');
                }
            }
        });
    }

    // Populate municipality dropdown
    function populateMunicipalityDropdown(municipalities) {
        const municipalitySelect = $('#shipping_municipality');
        municipalitySelect.html('<option value="">Select Municipality/City</option>');

        municipalities.forEach(municipality => {
            municipalitySelect.append(`<option value="${municipality}">${municipality}</option>`);
        });

        municipalitySelect.prop('disabled', false);
    }

    // Validation helper functions
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    function isValidPhone(phone) {
        // Accept Philippine mobile format: 09XXXXXXXXX (11 digits starting with 09)
        const cleanPhone = phone.replace(/[\s\-\(\)]/g, '');
        const phoneRegex = /^09\d{9}$/;
        return phoneRegex.test(cleanPhone);
    }

    // Dynamic validation for shipping address fields
    function validateShippingField(fieldId) {
        const field = $(`#${fieldId}`);
        const value = field.val().trim();

        field.removeClass('is-invalid');
        field.siblings('.invalid-feedback').text('');

        if (field.prop('required') && !value) {
            field.addClass('is-invalid');
            field.siblings('.invalid-feedback').text('This field is required.');
            return false;
        }

        // Email validation
        if (fieldId === 'shipping_email' && value && !isValidEmail(value)) {
            field.addClass('is-invalid');
            field.siblings('.invalid-feedback').text('Please enter a valid email address.');
            return false;
        }

        // Phone validation
        if (fieldId === 'shipping_phone' && value && !isValidPhone(value)) {
            field.addClass('is-invalid');
            field.siblings('.invalid-feedback').text('Please enter a valid phone number.');
            return false;
        }

        return true;
    }

    // Event listeners for shipping address form
    $(document).ready(function() {
        // Province change event
        $(document).on('change', '#shipping_province', function() {
            const selectedProvince = $(this).val();
            loadMunicipalities(selectedProvince);

            // Validate ship coverage when province changes
            validateShipCoverage(selectedProvince);

            // Reload shipping options when province changes (pricing may vary by province)
            if (selectedProducts && selectedProducts.length > 0) {
                // Reset shipping selections since pricing may have changed
                hasShippingErrors = false;
                selectedShippingMethods = {};
                pendingShippingLoads = 0;
                shippingOptionsLoading = false;
                // Reset global shipping method selection
                availableShippingMethods = {};
                allProductShippingData = {};
                selectedGlobalShippingMethod = null;
                $('#shipping-method-section').hide();
                $('#selected-shipping-method-info').hide();
                $('#shipping-error-banner').hide();

                // Show loading state in Shipping & Total Calculation section immediately
                $('#shipping-calculation-loading').show();
                $('#shipping-calculation-results').hide();
                $('#no-province-selected').hide();
                $('#no-ship-products-calculation').hide();

                // Reload ship products to get new shipping options for this province
                const shipProducts = selectedProducts.filter(product =>
                    product.productType === 'ship' || product.productType === 'Ship'
                );

                if (shipProducts.length > 0) {
                    const uniqueStores = [...new Set(shipProducts.map(product => product.productStore || 'Unknown Store'))];

                    // Set loading state
                    pendingShippingLoads = uniqueStores.length;
                    shippingOptionsLoading = true;
                    updateNextButtonState();

                    uniqueStores.forEach(store => {
                        loadShipProductsForStore(store);
                    });

                    // Note: calculateShippingCosts() will be called automatically
                    // when all loads complete via decrementPendingShippingLoads()
                } else {
                    // No ship products, just recalculate for access products
                    calculateShippingCosts();
                }
            }
        });

        // Shipping Type change event
        $(document).on('change', '#shipping_type', function() {
            const selectedType = $(this).val();

            // Update shipping type info display
            updateShippingTypeInfo(selectedType);

            // Reload shipping options when shipping type changes
            if (selectedProducts && selectedProducts.length > 0) {
                // Reset shipping selections since available methods may have changed
                hasShippingErrors = false;
                selectedShippingMethods = {};
                pendingShippingLoads = 0;
                shippingOptionsLoading = false;
                // Reset global shipping method selection
                availableShippingMethods = {};
                allProductShippingData = {};
                selectedGlobalShippingMethod = null;
                $('#shipping-method-section').hide();
                $('#selected-shipping-method-info').hide();
                $('#shipping-error-banner').hide();

                // Show loading state in Shipping & Total Calculation section immediately
                $('#shipping-calculation-loading').show();
                $('#shipping-calculation-results').hide();
                $('#no-province-selected').hide();
                $('#no-ship-products-calculation').hide();

                // Reload ship products to get new shipping options for this type
                const shipProducts = selectedProducts.filter(product =>
                    product.productType === 'ship' || product.productType === 'Ship'
                );

                if (shipProducts.length > 0) {
                    const uniqueStores = [...new Set(shipProducts.map(product => product.productStore || 'Unknown Store'))];

                    // Set loading state
                    pendingShippingLoads = uniqueStores.length;
                    shippingOptionsLoading = true;
                    updateNextButtonState();

                    uniqueStores.forEach(store => {
                        loadShipProductsForStore(store);
                    });

                    // Note: calculateShippingCosts() will be called automatically
                    // when all loads complete via decrementPendingShippingLoads()
                } else {
                    // No ship products, just recalculate for access products
                    calculateShippingCosts();
                }
            }
        });

        // Listen for product selection changes (triggered from other steps)
        $(document).on('productsUpdated', function() {
            // Recalculate shipping when products are updated from other steps
            if (currentStep === 4 && selectedProducts && selectedProducts.length > 0) {
                calculateShippingCosts();
            }
        });

        // Handle accordion chevron rotation
        $(document).on('click', '[data-bs-toggle="collapse"]', function() {
            const target = $(this).data('bs-target');
            const chevron = $(this).find('.mdi-chevron-down, .mdi-chevron-up');

            $(target).on('shown.bs.collapse', function() {
                chevron.removeClass('mdi-chevron-down').addClass('mdi-chevron-up');
            });

            $(target).on('hidden.bs.collapse', function() {
                chevron.removeClass('mdi-chevron-up').addClass('mdi-chevron-down');
            });
        });

        // Municipality change event
        // Municipality change - no longer controls barangay (barangay is now independent)

        // Dynamic validation on input/change
        const shippingFields = [
            'shipping_first_name', 'shipping_middle_name', 'shipping_last_name',
            'shipping_phone', 'shipping_email', 'shipping_house_number',
            'shipping_street', 'shipping_zone', 'shipping_province',
            'shipping_municipality', 'shipping_barangay', 'shipping_zip_code',
            'shipping_landmark'
        ];

        shippingFields.forEach(fieldId => {
            $(document).on('input blur', `#${fieldId}`, function() {
                validateShippingField(fieldId);
            });
        });

        // Shipping Address Autocomplete - monitor recipient fields
        let shippingSearchTimeout = null;
        const recipientFields = ['#shipping_first_name', '#shipping_last_name', '#shipping_phone', '#shipping_email'];

        recipientFields.forEach(field => {
            $(document).on('input', field, function() {
                clearTimeout(shippingSearchTimeout);
                shippingSearchTimeout = setTimeout(function() {
                    checkShippingAddressMatch();
                }, 500);
            });
        });

        // Function to check for matching shipping addresses
        function checkShippingAddressMatch() {
            const firstName = $('#shipping_first_name').val().trim();
            const lastName = $('#shipping_last_name').val().trim();
            const phone = $('#shipping_phone').val().trim();
            const email = $('#shipping_email').val().trim();

            // Count filled fields
            let filledCount = 0;
            if (firstName) filledCount++;
            if (lastName) filledCount++;
            if (phone) filledCount++;
            if (email) filledCount++;

            // Need at least 3 fields
            if (filledCount < 3) {
                $('#shippingAddressSuggestions').hide();
                return;
            }

            // Search for matching addresses
            $.ajax({
                url: '{{ route("ecom-orders-custom-add.search-shipping-address") }}',
                type: 'GET',
                data: {
                    firstName: firstName,
                    lastName: lastName,
                    phone: phone,
                    email: email
                },
                success: function(response) {
                    if (response.success && response.matches && response.matches.length > 0) {
                        displayShippingSuggestions(response.matches);
                    } else {
                        $('#shippingAddressSuggestions').hide();
                    }
                },
                error: function() {
                    $('#shippingAddressSuggestions').hide();
                }
            });
        }

        // Display shipping address suggestions
        function displayShippingSuggestions(matches) {
            const container = $('#suggestionsList');
            container.empty();

            matches.forEach(function(match) {
                const labelHtml = match.addressLabel ? `<span class="suggestion-label">${escapeHtml(match.addressLabel)}</span>` : '';
                const html = `
                    <div class="shipping-suggestion-item" data-address='${JSON.stringify(match)}'>
                        <div class="suggestion-name">
                            ${escapeHtml(match.recipientName)}${labelHtml}
                        </div>
                        <div class="suggestion-address">
                            <i class="mdi mdi-map-marker-outline me-1"></i>${escapeHtml(match.fullAddress)}
                        </div>
                    </div>
                `;
                container.append(html);
            });

            $('#shippingAddressSuggestions').show();
        }

        // Handle suggestion click
        $(document).on('click', '.shipping-suggestion-item', function() {
            const addressData = $(this).data('address');

            if (addressData) {
                // Fill in recipient fields
                if (addressData.firstName) $('#shipping_first_name').val(addressData.firstName);
                if (addressData.middleName) $('#shipping_middle_name').val(addressData.middleName);
                if (addressData.lastName) $('#shipping_last_name').val(addressData.lastName);
                if (addressData.phoneNumber) $('#shipping_phone').val(addressData.phoneNumber);
                if (addressData.emailAddress) $('#shipping_email').val(addressData.emailAddress);

                // Fill in address fields
                if (addressData.houseNumber) $('#shipping_house_number').val(addressData.houseNumber);
                if (addressData.street) $('#shipping_street').val(addressData.street);
                if (addressData.zone) $('#shipping_zone').val(addressData.zone);
                if (addressData.zipCode) $('#shipping_zip_code').val(addressData.zipCode);

                // Handle province and municipality (dropdowns)
                if (addressData.province) {
                    // Try to find and select the province
                    const $provinceSelect = $('#shipping_province');
                    const provinceOption = $provinceSelect.find(`option`).filter(function() {
                        return $(this).text().toLowerCase() === addressData.province.toLowerCase();
                    });

                    if (provinceOption.length > 0) {
                        $provinceSelect.val(provinceOption.val()).trigger('change');

                        // After province loads municipalities, try to select municipality
                        setTimeout(function() {
                            if (addressData.municipality) {
                                const $municipalitySelect = $('#shipping_municipality');
                                const municipalityOption = $municipalitySelect.find(`option`).filter(function() {
                                    return $(this).text().toLowerCase() === addressData.municipality.toLowerCase();
                                });
                                if (municipalityOption.length > 0) {
                                    $municipalitySelect.val(municipalityOption.val()).trigger('change');
                                }
                            }
                        }, 800);
                    }
                }

                // Hide suggestions
                $('#shippingAddressSuggestions').hide();

                // Show success message
                toastr.success('Address details auto-filled from saved address', 'Auto-fill Complete');
            }
        });

        // Helper function to escape HTML
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    });

    // Track if shipping form has been initialized
    let shippingFormInitialized = false;

    // Ship Coverage Validation Function
    function validateShipCoverage(province) {
        const shipProducts = selectedProducts.filter(product =>
            product.productType === 'ship' || product.productType === 'Ship'
        );

        const shipCoverageErrors = [];

        shipProducts.forEach(product => {
            const shipCoverage = product.shipCoverage || 'n/a';
            const productName = product.productName || 'Unknown Product';

            if (shipCoverage.toLowerCase() === 'province') {
                if (!province) {
                    shipCoverageErrors.push(`Province shipping location is required for product: ${productName}`);
                } else if (province.toLowerCase() !== 'pangasinan') {
                    shipCoverageErrors.push(`Product '${productName}' has Province shipping coverage only and can only be shipped to Pangasinan.`);
                }
            }
        });

        // Show ship coverage errors if any
        if (shipCoverageErrors.length > 0) {
            showErrorAlertModal(shipCoverageErrors.join(' '));
        }
    }

    // Shipping Calculation Functions
    function initializeShippingCalculation() {
        // Don't calculate immediately - let province loading complete first
        // The calculation will be triggered by:
        // 1. Auto-selection of Pangasinan (in populateProvinceDropdown)
        // 2. User selecting a province manually
        // 3. Restoring saved form data

        // Just hide calculation states initially
        $('#shipping-calculation-loading').hide();
        $('#shipping-calculation-results').hide();
        $('#no-province-selected').hide();

        if (!selectedProducts || selectedProducts.length === 0) {
            $('#no-ship-products-calculation').show();
        } else {
            $('#no-ship-products-calculation').hide();
        }

        shippingFormInitialized = true;
    }

    function clearShippingCalculation() {
        // Clear the calculation display
        $('#shipping-calculation-loading').hide();
        $('#shipping-calculation-results').hide();
        $('#no-province-selected').hide();
        $('#no-ship-products-calculation').show();

        // Reset order summary values
        $('#order-subtotal').text('₱0.00');
        $('#total-shipping').text('₱0.00');
        $('#order-total').text('₱0.00');

        // Clear breakdown displays
        $('#shipping-breakdown-container').html('');
        $('#product-summary-container').html('');
    }

    function showShippingCalculationState() {
        const province = $('#shipping_province').val();

        // Hide all states first
        $('#shipping-calculation-loading').hide();
        $('#shipping-calculation-results').hide();
        $('#no-province-selected').hide();
        $('#no-ship-products-calculation').hide();

        if (!selectedProducts || selectedProducts.length === 0) {
            $('#no-ship-products-calculation').show();
        } else {
            // Calculate shipping costs (works even without province for access products)
            calculateShippingCosts();
        }
    }

    function calculateShippingCosts() {
        const province = $('#shipping_province').val();
        const shippingType = $('#shipping_type').val();

        // Early return if no products selected
        if (!selectedProducts || selectedProducts.length === 0) {
            $('#shipping-calculation-loading').hide();
            $('#shipping-calculation-results').hide();
            $('#no-province-selected').hide();
            $('#no-ship-products-calculation').show();
            return;
        }

        // Check if there are ship products that require shipping type selection
        const shipProducts = selectedProducts.filter(product =>
            product.productType === 'ship' || product.productType === 'Ship'
        );

        // If there are ship products but no shipping type selected, show message instead of calculating
        if (shipProducts.length > 0 && !shippingType) {
            $('#shipping-calculation-loading').hide();
            $('#shipping-calculation-results').hide();
            $('#no-ship-products-calculation').hide();
            $('#no-province-selected').show().html(`
                <div class="alert alert-info mb-0">
                    <i class="mdi mdi-information-outline me-2"></i>
                    <strong>Selection Required:</strong> Please select a <strong>Shipping Type</strong> above to calculate shipping costs.
                </div>
            `);
            // Reset totals display
            $('#order-subtotal').text('₱0.00');
            $('#total-shipping').text('₱0.00');
            $('#order-total').text('₱0.00');
            return;
        }

        // If there are ship products but no province selected, show message
        if (shipProducts.length > 0 && !province) {
            $('#shipping-calculation-loading').hide();
            $('#shipping-calculation-results').hide();
            $('#no-ship-products-calculation').hide();
            $('#no-province-selected').show().html(`
                <div class="alert alert-info mb-0">
                    <i class="mdi mdi-information-outline me-2"></i>
                    <strong>Selection Required:</strong> Please select a <strong>Province</strong> in the shipping address above to calculate shipping costs.
                </div>
            `);
            // Reset totals display
            $('#order-subtotal').text('₱0.00');
            $('#total-shipping').text('₱0.00');
            $('#order-total').text('₱0.00');
            return;
        }

        // Show loading state with spinner
        $('#shipping-calculation-loading').show();
        $('#shipping-calculation-results').hide();
        $('#no-province-selected').hide();
        $('#no-ship-products-calculation').hide();

        // Prepare data for API call - send ALL selected products with selected shipping methods
        const requestData = {
            selectedProducts: selectedProducts,
            province: province || '', // Send empty string if no province selected
            shippingType: shippingType || '', // Send selected shipping type
            selectedShippingMethods: selectedShippingMethods // Include user-selected shipping methods
        };

        // Debug log
        console.log('Calculating shipping with data:', {
            province: province,
            shippingType: shippingType,
            productsCount: selectedProducts.length,
            products: selectedProducts,
            selectedShippingMethods: selectedShippingMethods
        });

        $.ajax({
            url: '{{ route("ecom-orders-custom-add.calculate-shipping") }}',
            type: 'POST',
            data: requestData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    displayShippingCalculation(response.data);
                } else {
                    showShippingCalculationError(response.message || 'Error calculating shipping costs');
                }
            },
            error: function(xhr, status, error) {
                console.error('Shipping calculation error:', error);
                console.error('Response:', xhr.responseJSON);
                console.error('Request data:', requestData);

                let errorMessage = 'Error calculating shipping costs';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }

                showShippingCalculationError(errorMessage);
            }
        });
    }

    function displayShippingCalculation(data) {
        // Hide loading state
        $('#shipping-calculation-loading').hide();

        // Check if this is a package purchase
        const isPackagePurchase = activePackage !== null;

        // Debug: Log the received data
        console.log('Shipping calculation data received:', data);
        console.log('Is package purchase:', isPackagePurchase);
        if (isPackagePurchase) {
            console.log('Package info:', activePackage);
        }

        // Calculate values based on purchase type
        let displaySubtotal, displayTotal;

        if (isPackagePurchase) {
            // Package purchase: use package price as subtotal
            displaySubtotal = parseFloat(activePackage.packagePrice);
            displayTotal = displaySubtotal + data.totalShipping;
        } else {
            // Regular purchase: use calculated subtotal
            displaySubtotal = data.subtotal;
            displayTotal = data.total;
        }

        // Update order summary
        $('#order-subtotal').text('₱' + displaySubtotal.toFixed(2));

        // Show shipping total with warning if there are errors
        if (data.hasShippingErrors) {
            $('#total-shipping').html(`<span class="text-danger">₱${data.totalShipping.toFixed(2)} <i class="mdi mdi-alert-circle" title="Some products have no shipping configured"></i></span>`);
            $('#order-total').html(`<span class="text-warning">₱${displayTotal.toFixed(2)} *</span>`);
        } else {
            $('#total-shipping').text('₱' + data.totalShipping.toFixed(2));
            $('#order-total').text('₱' + displayTotal.toFixed(2));
        }

        // Generate complete breakdown with all products
        let breakdownHtml = '';
        let productSummaryHtml = '';

        // Show warning banner if there are shipping errors
        if (data.hasShippingErrors) {
            breakdownHtml += `
                <div class="alert alert-danger mb-3">
                    <i class="mdi mdi-alert-circle me-2"></i>
                    <strong>Shipping Configuration Required:</strong> ${data.productsWithShippingErrors} product(s) do not have shipping configured for the selected shipping type or province.
                    <a href="{{ route('ecom-shipping') }}" target="_blank" class="alert-link">Configure shipping settings</a> to resolve this issue.
                </div>
            `;
        }

        if (data.completeBreakdown && data.completeBreakdown.length > 0) {
            // Different display for package vs regular purchases
            if (isPackagePurchase) {
                // Package purchase: simplified display without individual pricing
                breakdownHtml += `
                    <div class="alert alert-info mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="mdi mdi-package-variant-closed me-2"></i>
                                <strong>Package Purchase:</strong> ${escapeHtml(activePackage.packageName)}
                            </div>
                            <div>
                                ${activePackage.discountAmount > 0 ? `<span class="badge bg-success me-2">Save ₱${parseFloat(activePackage.discountAmount).toFixed(2)}</span>` : ''}
                                <span class="badge bg-primary fs-6">₱${parseFloat(activePackage.packagePrice).toFixed(2)}</span>
                            </div>
                        </div>
                    </div>
                `;

                // Show items in a simplified table (no price/subtotal columns)
                breakdownHtml += `
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-info">
                                <tr>
                                    <th>Type</th>
                                    <th>Product</th>
                                    <th>Variant</th>
                                    <th>Qty</th>
                                    <th>Shipping</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                let totalShippingForItems = 0;
                let errorCount = 0;

                data.completeBreakdown.forEach(item => {
                    const hasError = item.hasShippingError === true;

                    // Type badge
                    let typeBadge = '';
                    if (item.productType === 'ship') {
                        typeBadge = hasError ?
                            '<span class="badge bg-danger">Ship <i class="mdi mdi-alert"></i></span>' :
                            '<span class="badge bg-primary">Ship</span>';
                        totalShippingForItems += item.shippingPrice || 0;
                    } else {
                        typeBadge = '<span class="badge bg-info text-white">Access</span>';
                    }

                    let detailsHtml = '';
                    if (hasError) {
                        detailsHtml = `
                            <div class="small text-danger">
                                <i class="mdi mdi-alert-circle me-1"></i>
                                <strong>No Shipping Available</strong>
                            </div>
                        `;
                        errorCount++;
                    } else if (item.shippingDetails) {
                        const shippingDetails = item.shippingDetails;
                        detailsHtml = `
                            <div class="small">
                                <div><strong class="text-dark">${shippingDetails.shippingName || 'Default Shipping'}</strong></div>
                                <div class="text-secondary">${shippingDetails.pricingType || 'N/A'}</div>
                            </div>
                        `;
                    } else {
                        detailsHtml = '<div class="small text-secondary">No shipping required</div>';
                    }

                    const rowClass = hasError ? 'table-danger' : '';

                    breakdownHtml += `
                        <tr class="${rowClass}">
                            <td class="align-middle">${typeBadge}</td>
                            <td class="align-middle text-dark">${item.productName}</td>
                            <td class="align-middle text-dark">${item.variantName}</td>
                            <td class="text-center align-middle">${item.quantity}</td>
                            <td class="text-end align-middle ${hasError ? 'text-danger fw-bold' : ''}">${item.productType === 'ship' ? (hasError ? '<i class="mdi mdi-alert-circle"></i> N/A' : '₱' + item.shippingPrice.toFixed(2)) : '-'}</td>
                            <td class="align-middle">${detailsHtml}</td>
                        </tr>
                    `;
                });

                breakdownHtml += `
                            </tbody>
                        </table>
                    </div>
                `;

                // Package-specific product summary
                productSummaryHtml = `
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-dark"><i class="mdi mdi-package-variant-closed me-1"></i>Package:</span>
                        <span class="text-dark">${escapeHtml(activePackage.packageName)}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-dark">Total Items:</span>
                        <span class="text-dark">${data.completeBreakdown.length} items</span>
                    </div>
                    ${errorCount > 0 ? `
                    <div class="d-flex justify-content-between mb-2 text-danger">
                        <span><i class="mdi mdi-alert-circle me-1"></i>Shipping Errors:</span>
                        <span>${errorCount} items</span>
                    </div>
                    ` : ''}
                    <hr>
                    <div class="d-flex justify-content-between fw-bold text-primary">
                        <span>Package Price:</span>
                        <span>₱${parseFloat(activePackage.packagePrice).toFixed(2)}</span>
                    </div>
                `;
            } else {
                // Regular purchase: original detailed display
                breakdownHtml += `
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-success">
                                <tr>
                                    <th>Type</th>
                                    <th>Product</th>
                                    <th>Variant</th>
                                    <th>Qty</th>
                                    <th>Subtotal</th>
                                    <th>Shipping</th>
                                    <th>Total</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                let shipCount = 0;
                let accessCount = 0;
                let shipTotal = 0;
                let accessTotal = 0;
                let errorCount = 0;

                data.completeBreakdown.forEach(item => {
                    const total = item.subtotal + item.shippingPrice;
                    const hasError = item.hasShippingError === true;

                    // Type badge with error indication if needed
                    let typeBadge = '';
                    if (item.productType === 'ship') {
                        typeBadge = hasError ?
                            '<span class="badge bg-danger">Ship <i class="mdi mdi-alert"></i></span>' :
                            '<span class="badge bg-primary">Ship</span>';
                    } else {
                        typeBadge = '<span class="badge bg-info text-white">Access</span>';
                    }

                    let detailsHtml = '';
                    if (hasError) {
                        // Show error message for products without shipping
                        detailsHtml = `
                            <div class="small text-danger">
                                <i class="mdi mdi-alert-circle me-1"></i>
                                <strong>No Shipping Available</strong>
                                <div class="text-muted">${item.shippingErrorReason || 'Shipping not configured'}</div>
                            </div>
                        `;
                        errorCount++;
                    } else if (item.shippingDetails) {
                        const shippingDetails = item.shippingDetails;
                        detailsHtml = `
                            <div class="small">
                                <div><strong class="text-dark">${shippingDetails.shippingName || 'Default Shipping'}</strong></div>
                                <div><strong class="text-dark">${shippingDetails.pricingType || 'N/A'}</strong></div>
                                <div class="text-secondary">Max Qty: ${shippingDetails.maxQuantity || 'N/A'}</div>
                                <div class="text-secondary">Price/Batch: ₱${parseFloat(shippingDetails.pricePerBatch || 0).toFixed(2)}</div>
                                <div class="text-secondary">Batches: ${shippingDetails.batches || 'N/A'}</div>
                                <div class="text-muted">${shippingDetails.province || 'N/A'}</div>
                            </div>
                        `;
                    } else {
                        detailsHtml = '<div class="small text-secondary">No shipping required</div>';
                    }

                    // Row class for error highlighting
                    const rowClass = hasError ? 'table-danger' : '';

                    breakdownHtml += `
                        <tr class="${rowClass}">
                            <td class="align-middle">${typeBadge}</td>
                            <td class="align-middle ${hasError ? 'text-danger' : 'text-dark'}">${item.productName}</td>
                            <td class="align-middle ${hasError ? 'text-danger' : 'text-dark'}">${item.variantName}</td>
                            <td class="text-center align-middle">${item.quantity}</td>
                            <td class="text-end align-middle">₱${item.subtotal.toFixed(2)}</td>
                            <td class="text-end align-middle ${hasError ? 'text-danger fw-bold' : ''}">${hasError ? '<i class="mdi mdi-alert-circle"></i> N/A' : '₱' + item.shippingPrice.toFixed(2)}</td>
                            <td class="text-end align-middle fw-bold ${hasError ? 'text-danger' : ''}">${hasError ? 'N/A' : '₱' + total.toFixed(2)}</td>
                            <td class="align-middle">${detailsHtml}</td>
                        </tr>
                    `;

                    // Update counters for summary
                    if (item.productType === 'ship') {
                        shipCount += item.quantity;
                        shipTotal += item.subtotal;
                    } else {
                        accessCount += item.quantity;
                        accessTotal += item.subtotal;
                    }
                });

                breakdownHtml += `
                            </tbody>
                        </table>
                    </div>
                `;

                // Generate product summary with error count
                productSummaryHtml = `
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-dark">Ship Products:</span>
                        <span class="text-dark">${shipCount} items - ₱${shipTotal.toFixed(2)}</span>
                    </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-dark">Access Products:</span>
                    <span class="text-dark">${accessCount} items - ₱${accessTotal.toFixed(2)}</span>
                </div>
                ${errorCount > 0 ? `
                <div class="d-flex justify-content-between mb-2 text-danger">
                    <span><i class="mdi mdi-alert-circle me-1"></i>Products with Shipping Errors:</span>
                    <span>${errorCount} items</span>
                </div>
                ` : ''}
                <hr>
                <div class="d-flex justify-content-between fw-bold">
                    <span class="text-dark">Total Items:</span>
                    <span class="text-dark">${shipCount + accessCount} items</span>
                </div>
            `;
            }
        } else {
            breakdownHtml = `
                <div class="alert alert-info">
                    <i class="mdi mdi-information"></i>
                    No products found for calculation.
                </div>
            `;
        }

        $('#complete-breakdown').html(breakdownHtml);
        $('#product-summary').html(productSummaryHtml);
        $('#shipping-calculation-results').show();
    }

    function showShippingCalculationError(message) {
        $('#shipping-calculation-loading').hide();

        const errorHtml = `
            <div class="alert alert-danger">
                <i class="mdi mdi-alert-circle"></i>
                ${message}
            </div>
        `;

        $('#complete-breakdown').html(errorHtml);
        $('#shipping-calculation-results').show();
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
            url: '{{ route("ecom-orders-custom-add.access-clients") }}',
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
                url: '{{ route("ecom-orders-custom-add.check-phone") }}',
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

    // Check if email already exists for access login
    function checkAccessEmailExists(email, storeName) {
        return new Promise((resolve) => {
            $.ajax({
                url: '{{ route("ecom-orders-custom-add.check-access-email") }}',
                type: 'GET',
                data: {
                    email: email,
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

    // Validate phone format for access (09XXXXXXXXX format)
    function isValidAccessPhoneFormat(phone) {
        const normalizedPhone = normalizePhoneNumber(phone);
        // Should be 11 digits starting with 09
        return normalizedPhone.length === 11 && normalizedPhone.startsWith('09');
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

    // Real-time validation for phone number with format and uniqueness check
    $('#accessPhoneNumber').on('blur', async function() {
        const phoneNumber = $(this).val();
        const storeName = $('#createAccessModal').data('store-name');

        if (phoneNumber.trim()) {
            // First validate format (09XXXXXXXXX)
            if (!isValidAccessPhoneFormat(phoneNumber)) {
                $(this).removeClass('is-valid').addClass('is-invalid');
                $(this).siblings('.invalid-feedback').text('Phone must be in format: 09XXXXXXXXX (11 digits starting with 09)').show();
            } else {
                // Check uniqueness
                const exists = await checkPhoneExists(phoneNumber, storeName);
                if (exists) {
                    $(this).removeClass('is-valid').addClass('is-invalid');
                    $(this).siblings('.invalid-feedback').text('Phone number already exists for this store').show();
                } else {
                    $(this).removeClass('is-invalid').addClass('is-valid');
                    $(this).siblings('.invalid-feedback').text('').hide();
                }
            }
        } else {
            validateField('phoneNumber', phoneNumber, storeName);
        }
    });

    // Real-time validation for email with uniqueness check
    $('#accessEmail').on('blur', async function() {
        const email = $(this).val();
        const storeName = $('#createAccessModal').data('store-name');

        if (email.trim()) {
            // First validate format
            if (!isValidEmail(email.trim())) {
                $(this).removeClass('is-valid').addClass('is-invalid');
                $(this).siblings('.invalid-feedback').text('Please enter a valid email address').show();
            } else {
                // Check uniqueness
                const exists = await checkAccessEmailExists(email.trim(), storeName);
                if (exists) {
                    $(this).removeClass('is-valid').addClass('is-invalid');
                    $(this).siblings('.invalid-feedback').text('Email address already exists for this store').show();
                } else {
                    $(this).removeClass('is-invalid').addClass('is-valid');
                    $(this).siblings('.invalid-feedback').text('').hide();
                }
            }
        } else {
            validateField('email', email, storeName);
        }
    });

    // Real-time validation for other fields
    $('#accessFirstName, #accessMiddleName, #accessLastName, #accessPassword, #accessConfirmPassword').on('blur', function() {
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

        // Check phone number format and existence
        const phoneNumber = $('#accessPhoneNumber').val();
        if (phoneNumber.trim()) {
            // Validate phone format first (09XXXXXXXXX)
            if (!isValidAccessPhoneFormat(phoneNumber)) {
                $('#accessPhoneNumber').removeClass('is-valid').addClass('is-invalid');
                $('#accessPhoneNumber').siblings('.invalid-feedback').text('Phone must be in format: 09XXXXXXXXX (11 digits starting with 09)').show();
                isFormValid = false;
            } else {
                // Check uniqueness
                const phoneExists = await checkPhoneExists(phoneNumber, storeName);
                if (phoneExists) {
                    $('#accessPhoneNumber').removeClass('is-valid').addClass('is-invalid');
                    $('#accessPhoneNumber').siblings('.invalid-feedback').text('Phone number already exists for this store').show();
                    isFormValid = false;
                }
            }
        }

        // Check email format and existence
        const email = $('#accessEmail').val();
        if (email.trim()) {
            // Validate email format first
            if (!isValidEmail(email.trim())) {
                $('#accessEmail').removeClass('is-valid').addClass('is-invalid');
                $('#accessEmail').siblings('.invalid-feedback').text('Please enter a valid email address').show();
                isFormValid = false;
            } else {
                // Check uniqueness
                const emailExists = await checkAccessEmailExists(email.trim(), storeName);
                if (emailExists) {
                    $('#accessEmail').removeClass('is-valid').addClass('is-invalid');
                    $('#accessEmail').siblings('.invalid-feedback').text('Email address already exists for this store').show();
                    isFormValid = false;
                }
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
                    url: '{{ route("ecom-orders-custom-add.save-access") }}',
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

    // ==========================================
    // STEP 5: DISCOUNT FUNCTIONS
    // ==========================================

    // Store for applied discounts
    let appliedDiscounts = [];
    let autoApplyDiscountsData = [];
    let orderSubtotal = 0;
    let orderShipping = 0;
    let calculatedDiscountBreakdown = []; // Store calculated discount amounts for review
    let calculatedTotalDiscount = 0; // Store total discount for review

    // Load auto-apply discounts
    function loadAutoApplyDiscounts() {
        $('#auto-apply-discounts-loading').show();
        $('#applied-discounts-table-container').hide();
        $('#no-discounts-applied').hide();

        // Prepare cart items for restriction checking
        const cartItems = selectedProducts.map(item => ({
            productId: item.productId,
            productStore: item.productStore || '',
            variantId: item.variantId
        }));

        $.ajax({
            url: '{{ route("ecom-orders-custom-add.auto-apply-discounts") }}',
            type: 'GET',
            data: { cartItems: JSON.stringify(cartItems) },
            success: function(response) {
                $('#auto-apply-discounts-loading').hide();

                if (response.success && response.data && response.data.length > 0) {
                    autoApplyDiscountsData = response.data;

                    // Auto-apply all auto-apply discounts
                    response.data.forEach(function(discount) {
                        if (!isDiscountApplied(discount.id)) {
                            addAppliedDiscount(discount, 'auto');
                        }
                    });

                    // Render the unified table and recalculate totals
                    renderAppliedDiscountsTable();
                    calculateDiscountTotals();
                } else {
                    // No auto-apply discounts, but check if there are any applied discounts
                    renderAppliedDiscountsTable();
                }
            },
            error: function(xhr) {
                $('#auto-apply-discounts-loading').hide();
                renderAppliedDiscountsTable();
                console.error('Error loading auto-apply discounts:', xhr);
            }
        });
    }

    // Generate display value for a discount (fallback if not provided)
    function getDiscountDisplayValue(discount) {
        // If displayValue is already set, use it
        if (discount.displayValue) {
            return discount.displayValue;
        }

        // Otherwise, generate it from the discount data
        if (discount.amountType === 'Percentage' && discount.valuePercent !== null && discount.valuePercent !== undefined) {
            return discount.valuePercent + '%';
        } else if (discount.amountType === 'Specific Amount' && discount.valueAmount !== null && discount.valueAmount !== undefined) {
            return '₱' + formatNumber(discount.valueAmount);
        } else if (discount.amountType === 'Price Replacement' && discount.valueReplacement !== null && discount.valueReplacement !== undefined) {
            return '₱' + formatNumber(discount.valueReplacement) + ' (replacement)';
        }

        return 'N/A';
    }

    // Render unified applied discounts table (both auto-apply and code-based)
    function renderAppliedDiscountsTable() {
        const $tableContainer = $('#applied-discounts-table-container');
        const $tbody = $('#applied-discounts-tbody');
        const $noDiscounts = $('#no-discounts-applied');

        if (appliedDiscounts.length === 0) {
            $tableContainer.hide();
            $noDiscounts.show();
            return;
        }

        let html = '';
        appliedDiscounts.forEach(function(discount) {
            // Check for 'Auto Apply' (from database) - discount trigger values are "Auto Apply" or "Discount Code"
            const sourceLabel = discount.trigger === 'Auto Apply'
                ? '<span class="badge bg-success"><i class="mdi mdi-auto-fix me-1"></i>Auto Apply</span>'
                : '<span class="badge bg-primary"><i class="mdi mdi-ticket-percent me-1"></i>Discount Code</span>';

            const codeDisplay = discount.discountCode
                ? `<br><small class="text-secondary">Code: <code class="text-dark">${escapeHtml(discount.discountCode)}</code></small>`
                : '';

            // Get display value with fallback
            const displayValue = getDiscountDisplayValue(discount);

            html += `
                <tr data-discount-id="${discount.id}">
                    <td>
                        <strong class="text-dark">${escapeHtml(discount.discountName)}</strong>
                        ${discount.discountDescription ? `<br><small class="text-secondary">${escapeHtml(discount.discountDescription)}</small>` : ''}
                        ${codeDisplay}
                    </td>
                    <td class="text-dark">${escapeHtml(discount.amountType || 'N/A')}</td>
                    <td><span class="badge bg-success text-white">${escapeHtml(displayValue)}</span></td>
                    <td>${sourceLabel}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-discount-btn" data-discount-id="${discount.id}" title="Remove Discount">
                            <i class="mdi mdi-trash-can-outline me-1"></i>Remove
                        </button>
                    </td>
                </tr>
            `;
        });

        $tbody.html(html);
        $noDiscounts.hide();
        $tableContainer.show();
    }

    // Check if a discount is already applied
    function isDiscountApplied(discountId) {
        return appliedDiscounts.some(d => d.id === discountId);
    }

    // Add a discount to applied list
    function addAppliedDiscount(discount, triggerOverride) {
        if (!isDiscountApplied(discount.id)) {
            // Use discountTrigger from API response, or fall back to the override parameter
            const trigger = discount.discountTrigger || triggerOverride || 'Auto Apply';
            appliedDiscounts.push({
                id: discount.id,
                discountName: discount.discountName,
                discountDescription: discount.discountDescription,
                amountType: discount.amountType,
                valuePercent: discount.valuePercent,
                valueAmount: discount.valueAmount,
                valueReplacement: discount.valueReplacement,
                discountCapType: discount.discountCapType,
                discountCapValue: discount.discountCapValue,
                displayValue: discount.displayValue,
                trigger: trigger,
                discountCode: discount.discountCode || null
            });
        }
    }

    // Remove a discount from applied list
    function removeAppliedDiscount(discountId) {
        appliedDiscounts = appliedDiscounts.filter(d => d.id !== discountId);
        renderAppliedDiscountsTable();
        calculateDiscountTotals();
    }

    // Apply discount code
    $('#apply_discount_code').on('click', function() {
        applyDiscountCode();
    });

    $('#discount_code_input').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            applyDiscountCode();
        }
    });

    function applyDiscountCode() {
        const code = $('#discount_code_input').val().trim();

        if (!code) {
            showDiscountCodeFeedback('Please enter a discount code.', 'warning');
            return;
        }

        // Prepare cart items for restriction checking
        const cartItems = selectedProducts.map(item => ({
            productId: item.productId,
            productStore: item.productStore || '',
            variantId: item.variantId
        }));

        const $btn = $('#apply_discount_code');
        const originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin me-1"></i>Applying...');

        $.ajax({
            url: '{{ route("ecom-orders-custom-add.validate-discount-code") }}',
            type: 'GET',
            data: { code: code, cartItems: JSON.stringify(cartItems) },
            success: function(response) {
                $btn.prop('disabled', false).html(originalHtml);

                if (response.success) {
                    const discount = response.data;

                    // Check if already applied
                    if (isDiscountApplied(discount.id)) {
                        showDiscountCodeFeedback('This discount code is already applied.', 'warning');
                        return;
                    }

                    // Add to applied discounts
                    addAppliedDiscount(discount, 'code');
                    renderAppliedDiscountsTable();
                    calculateDiscountTotals();

                    // Clear input and show success
                    $('#discount_code_input').val('');
                    showDiscountCodeFeedback('Discount code applied successfully!', 'success');
                } else {
                    showDiscountCodeFeedback(response.message || 'Invalid discount code.', 'danger');
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html(originalHtml);
                const message = xhr.responseJSON?.message || 'Error applying discount code.';
                showDiscountCodeFeedback(message, 'danger');
            }
        });
    }

    function showDiscountCodeFeedback(message, type) {
        const $feedback = $('#discount_code_feedback');
        $feedback.html(`<div class="alert alert-${type} alert-sm py-2 mb-0">${message}</div>`).show();

        setTimeout(function() {
            $feedback.fadeOut();
        }, 3000);
    }

    // Remove discount handler (unified for both auto-apply and code-based)
    $(document).on('click', '.remove-discount-btn', function() {
        const discountId = $(this).data('discount-id');
        removeAppliedDiscount(discountId);
    });

    // Refresh auto-apply discounts button handler
    $('#refresh_auto_discounts').on('click', function() {
        refreshAutoApplyDiscounts();
    });

    // Refresh auto-apply discounts function
    function refreshAutoApplyDiscounts() {
        const $btn = $('#refresh_auto_discounts');
        const originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin me-1"></i>Refreshing...');

        // Prepare cart items for restriction checking
        const cartItems = selectedProducts.map(item => ({
            productId: item.productId,
            productStore: item.productStore || '',
            variantId: item.variantId
        }));

        $.ajax({
            url: '{{ route("ecom-orders-custom-add.auto-apply-discounts") }}',
            type: 'GET',
            data: { cartItems: JSON.stringify(cartItems) },
            success: function(response) {
                $btn.prop('disabled', false).html(originalHtml);

                if (response.success && response.data && response.data.length > 0) {
                    autoApplyDiscountsData = response.data;
                    let addedCount = 0;

                    // Re-apply any auto-apply discounts that are not currently applied
                    response.data.forEach(function(discount) {
                        if (!isDiscountApplied(discount.id)) {
                            addAppliedDiscount(discount, 'auto');
                            addedCount++;
                        }
                    });

                    // Render the unified table and recalculate totals
                    renderAppliedDiscountsTable();
                    calculateDiscountTotals();

                    if (addedCount > 0) {
                        showDiscountCodeFeedback(addedCount + ' auto-apply discount(s) have been re-applied.', 'success');
                    } else {
                        showDiscountCodeFeedback('All auto-apply discounts are already applied.', 'info');
                    }
                } else {
                    showDiscountCodeFeedback('No auto-apply discounts available at this time.', 'info');
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html(originalHtml);
                showDiscountCodeFeedback('Error refreshing discounts. Please try again.', 'danger');
                console.error('Error refreshing auto-apply discounts:', xhr);
            }
        });
    }

    // Update discount order summary
    function updateDiscountOrderSummary() {
        // Calculate subtotal - use package price if package is active, otherwise sum individual prices
        orderSubtotal = 0;
        orderShipping = 0;

        if (activePackage) {
            // Package purchase: use package price as subtotal
            orderSubtotal = parseFloat(activePackage.packagePrice) || 0;
        } else {
            // Regular purchase: calculate from selected products
            selectedProducts.forEach(function(product) {
                const price = parseFloat(product.price) || 0;
                const quantity = parseInt(product.quantity) || 1;
                orderSubtotal += price * quantity;
            });
        }

        // Get shipping from Step 4 calculation (if available)
        const shippingText = $('#total-shipping').text();
        if (shippingText) {
            orderShipping = parseFloat(shippingText.replace(/[^\d.]/g, '')) || 0;
        }

        // Update product summary in Step 5
        renderDiscountProductSummary();

        // Calculate discount totals
        calculateDiscountTotals();
    }

    // Render product summary in Step 5
    function renderDiscountProductSummary() {
        if (selectedProducts.length === 0) {
            $('#discount-product-summary').html('<p class="text-dark mb-0">No products selected.</p>');
            return;
        }

        // Check if this is a package purchase
        const isPackagePurchase = activePackage !== null;

        let html = '';

        // If package purchase, show package info banner
        if (isPackagePurchase) {
            html += `
                <div class="alert alert-info mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="mdi mdi-package-variant-closed me-2"></i>
                            <strong>Package Purchase:</strong> ${escapeHtml(activePackage.packageName)}
                        </div>
                        <div>
                            ${activePackage.discountAmount > 0 ? `<span class="badge bg-success me-2">Save ₱${parseFloat(activePackage.discountAmount).toFixed(2)}</span>` : ''}
                            <span class="badge bg-primary fs-6">₱${parseFloat(activePackage.packagePrice).toFixed(2)}</span>
                        </div>
                    </div>
                </div>
            `;

            // Package purchase - simplified table without Price and Subtotal columns
            html += '<table class="table table-sm table-bordered mb-0">';
            html += '<thead class="table-info"><tr><th class="text-dark">Product</th><th class="text-dark">Variant</th><th class="text-dark">Type</th><th class="text-dark text-center">Qty</th></tr></thead>';
            html += '<tbody>';

            selectedProducts.forEach(function(product) {
                const quantity = parseInt(product.quantity) || 1;

                // Determine product type and badge
                const productType = (product.productType || 'unknown').toLowerCase();
                let typeBadge = '';
                if (productType === 'access') {
                    typeBadge = '<span class="badge bg-primary text-white"><i class="mdi mdi-key me-1"></i>Access</span>';
                } else if (productType === 'ship') {
                    typeBadge = '<span class="badge bg-warning text-dark"><i class="mdi mdi-truck me-1"></i>Ship</span>';
                } else {
                    typeBadge = '<span class="badge bg-secondary text-white">' + escapeHtml(product.productType || 'N/A') + '</span>';
                }

                html += `
                    <tr>
                        <td class="text-dark">${escapeHtml(product.productName || 'Unknown')}</td>
                        <td class="text-dark">${escapeHtml(product.variantName || 'Default')}</td>
                        <td>${typeBadge}</td>
                        <td class="text-dark text-center">${quantity}</td>
                    </tr>
                `;
            });

            html += '</tbody></table>';
        } else {
            // Regular purchase - show all columns including Price and Subtotal
            html += '<table class="table table-sm table-bordered mb-0">';
            html += '<thead class="table-light"><tr><th class="text-dark">Product</th><th class="text-dark">Variant</th><th class="text-dark">Type</th><th class="text-dark text-center">Qty</th><th class="text-dark text-end">Price</th><th class="text-dark text-end">Subtotal</th></tr></thead>';
            html += '<tbody>';

            selectedProducts.forEach(function(product) {
                const price = parseFloat(product.price) || 0;
                const quantity = parseInt(product.quantity) || 1;
                const subtotal = price * quantity;

                // Determine product type and badge
                const productType = (product.productType || 'unknown').toLowerCase();
                let typeBadge = '';
                if (productType === 'access') {
                    typeBadge = '<span class="badge bg-primary text-white"><i class="mdi mdi-key me-1"></i>Access</span>';
                } else if (productType === 'ship') {
                    typeBadge = '<span class="badge bg-warning text-dark"><i class="mdi mdi-truck me-1"></i>Ship</span>';
                } else {
                    typeBadge = '<span class="badge bg-secondary text-white">' + escapeHtml(product.productType || 'N/A') + '</span>';
                }

                html += `
                    <tr>
                        <td class="text-dark">${escapeHtml(product.productName || 'Unknown')}</td>
                        <td class="text-dark">${escapeHtml(product.variantName || 'Default')}</td>
                        <td>${typeBadge}</td>
                        <td class="text-dark text-center">${quantity}</td>
                        <td class="text-dark text-end">₱${formatNumber(price)}</td>
                        <td class="text-dark text-end fw-semibold">₱${formatNumber(subtotal)}</td>
                    </tr>
                `;
            });

            html += '</tbody></table>';
        }

        $('#discount-product-summary').html(html);
    }

    // Calculate discount totals
    function calculateDiscountTotals() {
        if (appliedDiscounts.length === 0) {
            // No discounts applied
            updateDiscountDisplay(orderSubtotal, orderShipping, 0, orderSubtotal + orderShipping, []);
            return;
        }

        // Calculate discounts via API
        $('#discount-calculation-loading').show();

        $.ajax({
            url: '{{ route("ecom-orders-custom-add.calculate-with-discounts") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                subtotal: orderSubtotal,
                shippingTotal: orderShipping,
                appliedDiscounts: appliedDiscounts.map(d => ({ id: d.id }))
            },
            success: function(response) {
                $('#discount-calculation-loading').hide();

                if (response.success) {
                    const data = response.data;
                    updateDiscountDisplay(
                        data.subtotal,
                        data.shippingTotal,
                        data.totalDiscount,
                        data.grandTotal,
                        data.discountBreakdown
                    );
                }
            },
            error: function(xhr) {
                $('#discount-calculation-loading').hide();
                console.error('Error calculating discounts:', xhr);
                // Fall back to simple calculation
                updateDiscountDisplay(orderSubtotal, orderShipping, 0, orderSubtotal + orderShipping, []);
            }
        });
    }

    // Update discount display
    function updateDiscountDisplay(subtotal, shipping, totalDiscount, grandTotal, breakdown) {
        $('#discount-subtotal').text('₱' + formatNumber(subtotal));
        $('#discount-shipping').text('₱' + formatNumber(shipping));
        $('#discount-grand-total').text('₱' + formatNumber(grandTotal));

        // Store for Step 7 review
        calculatedDiscountBreakdown = breakdown || [];
        calculatedTotalDiscount = totalDiscount || 0;

        if (totalDiscount > 0) {
            $('#discount-row').show();
            $('#discount-amount').text('-₱' + formatNumber(totalDiscount));
        } else {
            $('#discount-row').hide();
        }
    }

    // =====================================================
    // STEP 7: ORDER REVIEW FUNCTIONS
    // =====================================================

    /**
     * Populate the order review (Step 7) with all order details
     */
    function populateOrderReview() {
        console.log('Populating order review...');

        // 1. Populate Products Table
        populateReviewProducts();

        // 2. Populate Client Information
        populateReviewClient();

        // 3. Populate Access Logins (if any access products)
        populateReviewLogins();

        // 4. Populate Shipping Details (if any ship products)
        populateReviewShipping();

        // 5. Populate Discounts (if any applied)
        populateReviewDiscounts();

        // 6. Populate Affiliate Commissions (if any)
        populateReviewAffiliates();

        // 7. Calculate and display totals
        populateReviewTotals();
    }

    /**
     * Populate the products table in the review
     */
    function populateReviewProducts() {
        // Check if this is a package purchase
        const isPackagePurchase = activePackage !== null;

        let html = '';
        let totalItems = 0;
        let calculatedSubtotal = 0; // Sum of individual prices (for savings calculation)

        if (isPackagePurchase) {
            // Package purchase - show package info and simplified product list

            // Calculate totals for display
            selectedProducts.forEach(function(product) {
                calculatedSubtotal += parseFloat(product.price) * product.quantity;
                totalItems += product.quantity;
            });

            const packagePrice = parseFloat(activePackage.packagePrice) || 0;
            const packageSavings = calculatedSubtotal - packagePrice;

            // Package info banner (inserted before table)
            const packageBannerHtml = `
                <div class="alert alert-info mb-0 rounded-0 border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="mdi mdi-package-variant-closed me-2 fs-5"></i>
                            <strong class="fs-5">${escapeHtml(activePackage.packageName)}</strong>
                            ${activePackage.packageDescription ? `<div class="small text-secondary mt-1">${escapeHtml(activePackage.packageDescription)}</div>` : ''}
                        </div>
                        <div class="text-end">
                            ${packageSavings > 0 ? `
                                <div class="mb-1">
                                    <span class="badge bg-success fs-6">
                                        <i class="mdi mdi-tag me-1"></i>Save ₱${formatNumber(packageSavings)}
                                    </span>
                                </div>
                                <small class="text-muted text-decoration-line-through">₱${formatNumber(calculatedSubtotal)}</small>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;

            // Update the card header to show package badge
            const $cardHeader = $('#review-products-tbody').closest('.card').find('.card-header');
            if (!$cardHeader.find('.package-purchase-badge').length) {
                $cardHeader.find('.badge').after('<span class="badge bg-info text-white ms-2 package-purchase-badge"><i class="mdi mdi-package-variant me-1"></i>Package</span>');
            }

            // Build simplified table rows (no Price/Subtotal columns)
            selectedProducts.forEach(function(product) {
                const typeClass = (product.productType || '').toLowerCase() === 'ship' ? 'bg-success' : 'bg-primary';
                const typeLabel = (product.productType || '').toLowerCase() === 'ship' ? 'Ship' : 'Access';

                html += `
                    <tr>
                        <td>
                            <div class="fw-medium text-dark">${escapeHtml(product.productName)}</div>
                            <small class="text-secondary">${escapeHtml(product.variantName || 'Default')}</small>
                            <br><small class="text-muted">${escapeHtml(product.productStore || '')}</small>
                        </td>
                        <td class="text-center">
                            <span class="badge ${typeClass}">${typeLabel}</span>
                        </td>
                        <td class="text-center text-dark">${product.quantity}</td>
                    </tr>
                `;
            });

            if (html === '') {
                html = '<tr><td colspan="3" class="text-center text-muted py-3">No products in package</td></tr>';
            }

            // Update table structure for package display
            const $table = $('#review-products-tbody').closest('table');

            // Update thead for package (3 columns instead of 5)
            $table.find('thead').html(`
                <tr class="table-info">
                    <th style="width: 60%;">Product</th>
                    <th class="text-center">Type</th>
                    <th class="text-center">Qty</th>
                </tr>
            `);

            // Add package banner before table if not exists
            const $cardBody = $table.closest('.card-body');
            if (!$cardBody.find('.package-banner-container').length) {
                $table.before(`<div class="package-banner-container">${packageBannerHtml}</div>`);
            } else {
                $cardBody.find('.package-banner-container').html(packageBannerHtml);
            }

            // Update tfoot for package
            $table.find('tfoot').html(`
                <tr class="table-info">
                    <td colspan="2" class="text-end fw-bold">
                        <i class="mdi mdi-package-variant me-1"></i>Package Price:
                    </td>
                    <td class="text-center fw-bold text-primary fs-5" id="review-products-subtotal">₱${formatNumber(packagePrice)}</td>
                </tr>
                ${packageSavings > 0 ? `
                <tr class="table-success">
                    <td colspan="2" class="text-end text-success">
                        <i class="mdi mdi-piggy-bank me-1"></i>You Save:
                    </td>
                    <td class="text-center fw-bold text-success">₱${formatNumber(packageSavings)}</td>
                </tr>
                ` : ''}
            `);

            $('#review-products-tbody').html(html);
            $('#review-products-count').text(totalItems + ' item' + (totalItems !== 1 ? 's' : '') + ' (Package)');

        } else {
            // Regular purchase - show all columns including Price and Subtotal

            // Remove package banner if exists
            const $table = $('#review-products-tbody').closest('table');
            const $cardBody = $table.closest('.card-body');
            $cardBody.find('.package-banner-container').remove();

            // Remove package badge from header if exists
            const $cardHeader = $cardBody.closest('.card').find('.card-header');
            $cardHeader.find('.package-purchase-badge').remove();

            // Restore original thead
            $table.find('thead').html(`
                <tr class="table-light">
                    <th style="width: 40%;">Product</th>
                    <th class="text-center">Type</th>
                    <th class="text-center">Qty</th>
                    <th class="text-end">Price</th>
                    <th class="text-end">Subtotal</th>
                </tr>
            `);

            // Restore original tfoot
            $table.find('tfoot').html(`
                <tr class="table-light">
                    <td colspan="4" class="text-end fw-bold">Products Subtotal:</td>
                    <td class="text-end fw-bold text-primary" id="review-products-subtotal">₱0.00</td>
                </tr>
            `);

            selectedProducts.forEach(function(product) {
                const productSubtotal = parseFloat(product.price) * product.quantity;
                calculatedSubtotal += productSubtotal;
                totalItems += product.quantity;

                const typeClass = (product.productType || '').toLowerCase() === 'ship' ? 'bg-success' : 'bg-primary';
                const typeLabel = (product.productType || '').toLowerCase() === 'ship' ? 'Ship' : 'Access';

                html += `
                    <tr>
                        <td>
                            <div class="fw-medium text-dark">${escapeHtml(product.productName)}</div>
                            <small class="text-secondary">${escapeHtml(product.variantName || 'Default')}</small>
                            <br><small class="text-muted">${escapeHtml(product.productStore || '')}</small>
                        </td>
                        <td class="text-center">
                            <span class="badge ${typeClass}">${typeLabel}</span>
                        </td>
                        <td class="text-center text-dark">${product.quantity}</td>
                        <td class="text-end text-dark">₱${formatNumber(product.price)}</td>
                        <td class="text-end fw-medium text-dark">₱${formatNumber(productSubtotal)}</td>
                    </tr>
                `;
            });

            if (html === '') {
                html = '<tr><td colspan="5" class="text-center text-muted py-3">No products selected</td></tr>';
            }

            $('#review-products-tbody').html(html);
            $('#review-products-count').text(totalItems + ' item' + (totalItems !== 1 ? 's' : ''));
            $('#review-products-subtotal').text('₱' + formatNumber(calculatedSubtotal));
        }
    }

    /**
     * Populate client information in the review
     */
    function populateReviewClient() {
        if (!selectedClient) {
            $('#review-client-info').html('<p class="text-muted mb-0">No client selected</p>');
            return;
        }

        // Use the fields that are actually stored when client is selected
        // selectedClient has: id, fullName, phone, email
        const fullName = selectedClient.fullName || [
            selectedClient.firstName || selectedClient.clientFirstName,
            selectedClient.middleName || selectedClient.clientMiddleName,
            selectedClient.lastName || selectedClient.clientLastName
        ].filter(Boolean).join(' ') || 'N/A';

        const phone = selectedClient.phone || selectedClient.phoneNumber || selectedClient.clientPhoneNumber || 'N/A';
        const email = selectedClient.email || selectedClient.emailAddress || selectedClient.clientEmailAddress || 'N/A';

        const html = `
            <div class="col-md-6 mb-2">
                <small class="text-secondary d-block">Full Name</small>
                <span class="text-dark fw-medium">${escapeHtml(fullName)}</span>
            </div>
            <div class="col-md-6 mb-2">
                <small class="text-secondary d-block">Client ID</small>
                <span class="text-dark">#${selectedClient.id}</span>
            </div>
            <div class="col-md-6 mb-2">
                <small class="text-secondary d-block">Phone Number</small>
                <span class="text-dark">${escapeHtml(phone)}</span>
            </div>
            <div class="col-md-6 mb-2">
                <small class="text-secondary d-block">Email Address</small>
                <span class="text-dark">${escapeHtml(email)}</span>
            </div>
        `;

        $('#review-client-info').html(html);
    }

    /**
     * Populate access logins in the review (if any access products)
     */
    function populateReviewLogins() {
        const accessProducts = selectedProducts.filter(p =>
            (p.productType || '').toLowerCase() === 'access'
        );

        if (accessProducts.length === 0) {
            $('#review-logins-section').hide();
            return;
        }

        // Check if there are selected access clients (from Step 3)
        const hasSelectedAccessClients = selectedAccessClients && Object.keys(selectedAccessClients).length > 0;

        if (!hasSelectedAccessClients) {
            $('#review-logins-section').hide();
            return;
        }

        let html = '';

        // Group access products by store
        const productsByStore = {};
        accessProducts.forEach(function(product) {
            const store = product.productStore || 'Unknown Store';
            if (!productsByStore[store]) {
                productsByStore[store] = [];
            }
            productsByStore[store].push(product);
        });

        // Display selected access client for each store
        Object.keys(selectedAccessClients).forEach(function(store) {
            const client = selectedAccessClients[store];
            const storeProducts = productsByStore[store] || [];

            html += `
                <div class="mb-3 pb-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="text-primary mb-0"><i class="mdi mdi-store me-1"></i>${escapeHtml(store)}</h6>
                        <span class="badge bg-info text-white">${storeProducts.length} product${storeProducts.length !== 1 ? 's' : ''}</span>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <small class="text-secondary d-block">Account Holder</small>
                            <span class="text-dark fw-medium">${escapeHtml(client.fullName) || 'N/A'}</span>
                        </div>
                        <div class="col-md-6 mb-2">
                            <small class="text-secondary d-block">Phone Number</small>
                            <span class="text-dark">${escapeHtml(client.phone) || 'N/A'}</span>
                        </div>
                        <div class="col-12 mb-2">
                            <small class="text-secondary d-block">Email Address</small>
                            <span class="text-dark">${escapeHtml(client.email) || 'N/A'}</span>
                        </div>
                    </div>
            `;

            // Show products for this store
            if (storeProducts.length > 0) {
                html += `<div class="mt-2"><small class="text-secondary">Products:</small>`;
                storeProducts.forEach(function(product) {
                    html += `
                        <div class="d-flex justify-content-between align-items-center py-1">
                            <small class="text-dark">${escapeHtml(product.productName)} <span class="text-muted">${escapeHtml(product.variantName || '')}</span></small>
                            <span class="badge bg-secondary">x${product.quantity}</span>
                        </div>
                    `;
                });
                html += `</div>`;
            }

            html += `</div>`;
        });

        // Remove last border
        html = html.replace(/border-bottom">([^<]*?)$/, '">$1');

        $('#review-logins-info').html(html);
        $('#review-logins-section').show();
    }

    /**
     * Populate shipping details in the review (if any ship products)
     */
    function populateReviewShipping() {
        const shipProducts = selectedProducts.filter(p =>
            (p.productType || '').toLowerCase() === 'ship'
        );

        if (shipProducts.length === 0) {
            $('#review-shipping-section').hide();
            return;
        }

        // Get shipping data from form
        const firstName = $('#shipping_first_name').val() || '';
        const middleName = $('#shipping_middle_name').val() || '';
        const lastName = $('#shipping_last_name').val() || '';
        const phone = $('#shipping_phone').val() || '';
        const email = $('#shipping_email').val() || '';

        const fullName = [firstName, middleName, lastName].filter(Boolean).join(' ');

        // Recipient info
        const recipientHtml = `
            <p class="mb-1"><strong class="text-dark">${escapeHtml(fullName)}</strong></p>
            <p class="mb-1 text-secondary"><i class="mdi mdi-phone me-1"></i>${escapeHtml(phone)}</p>
            ${email ? `<p class="mb-0 text-secondary"><i class="mdi mdi-email me-1"></i>${escapeHtml(email)}</p>` : ''}
        `;
        $('#review-shipping-recipient').html(recipientHtml);

        // Address info
        const houseNumber = $('#shipping_house_number').val() || '';
        const street = $('#shipping_street').val() || '';
        const zone = $('#shipping_zone').val() || '';
        const municipality = $('#shipping_municipality').val() || '';
        const province = $('#shipping_province').val() || '';
        const zipCode = $('#shipping_zip_code').val() || '';

        // Format zone with "Zone" prefix if it has a value
        const zoneDisplay = zone ? 'Zone ' + zone : '';
        const addressParts = [houseNumber, street, zoneDisplay].filter(Boolean).join(', ');
        const locationParts = [municipality, province, zipCode].filter(Boolean).join(', ');

        const addressHtml = `
            <p class="mb-1 text-dark">${escapeHtml(addressParts) || 'N/A'}</p>
            <p class="mb-0 text-secondary">${escapeHtml(locationParts) || 'N/A'}</p>
        `;
        $('#review-shipping-address').html(addressHtml);

        // Shipping type and method
        const shippingType = $('#shipping_type').val() || 'Regular';

        // Get selected shipping methods from Step 4
        let methodHtml = `<p class="mb-1"><span class="badge bg-info text-white">${escapeHtml(shippingType)}</span></p>`;

        // Check if we have selected shipping methods stored
        if (Object.keys(selectedShippingMethods).length > 0) {
            // Group by shipping method
            const methodGroups = {};
            shipProducts.forEach(function(product) {
                const methodId = selectedShippingMethods[product.variantId];
                if (methodId) {
                    if (!methodGroups[methodId]) {
                        methodGroups[methodId] = [];
                    }
                    methodGroups[methodId].push(product);
                }
            });

            // Display methods
            Object.keys(methodGroups).forEach(function(methodId) {
                const products = methodGroups[methodId];
                // Try to find method name from allProductShippingData
                let methodName = 'Shipping Method #' + methodId;
                for (let variantId in allProductShippingData) {
                    const data = allProductShippingData[variantId];
                    if (data && data.shippingOptions) {
                        const method = data.shippingOptions.find(m => m.shippingId == methodId);
                        if (method) {
                            methodName = method.shippingName;
                            break;
                        }
                    }
                }
                methodHtml += `<p class="mb-0 text-dark"><i class="mdi mdi-check-circle text-success me-1"></i>${escapeHtml(methodName)}</p>`;
            });
        } else if (selectedGlobalShippingMethod) {
            // Use global shipping method
            methodHtml += `<p class="mb-0 text-dark"><i class="mdi mdi-check-circle text-success me-1"></i>Method ID: ${selectedGlobalShippingMethod}</p>`;
        }

        $('#review-shipping-method').html(methodHtml);

        // Shipping cost breakdown
        const shippingText = $('#total-shipping').text() || '₱0.00';
        const shippingTotal = parseFloat(shippingText.replace(/[^\d.]/g, '')) || 0;

        let costHtml = `
            <div class="d-flex justify-content-between mb-1">
                <span class="text-secondary">Ship Products:</span>
                <span class="text-dark">${shipProducts.length} item(s)</span>
            </div>
            <div class="d-flex justify-content-between">
                <span class="text-secondary fw-bold">Total Shipping:</span>
                <span class="text-success fw-bold">₱${formatNumber(shippingTotal)}</span>
            </div>
        `;
        $('#review-shipping-cost').html(costHtml);

        $('#review-shipping-section').show();
    }

    /**
     * Populate applied discounts in the review
     */
    function populateReviewDiscounts() {
        // Use the calculated discount breakdown from Step 5
        if ((!calculatedDiscountBreakdown || calculatedDiscountBreakdown.length === 0) &&
            (!appliedDiscounts || appliedDiscounts.length === 0)) {
            $('#review-discounts-section').hide();
            $('#review-discount-row').hide();
            return;
        }

        let html = '';

        // If we have calculated breakdown from API, use it (most accurate)
        if (calculatedDiscountBreakdown && calculatedDiscountBreakdown.length > 0) {
            calculatedDiscountBreakdown.forEach(function(discount) {
                const discountAmount = parseFloat(discount.calculatedAmount) || 0;

                html += `
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <span class="text-dark fw-medium">${escapeHtml(discount.name)}</span>
                            <br><small class="text-secondary">${escapeHtml(discount.displayValue)} ${discount.code ? '(Code: ' + escapeHtml(discount.code) + ')' : ''}</small>
                        </div>
                        <span class="text-danger fw-medium">-₱${formatNumber(discountAmount)}</span>
                    </div>
                `;
            });

            $('#review-discounts-list').html(html);
            $('#review-total-discount').text('-₱' + formatNumber(calculatedTotalDiscount));
            $('#review-discounts-section').show();
            $('#review-discount-row').show();
            $('#review-discount-total').text('-₱' + formatNumber(calculatedTotalDiscount));
        }
        // Fallback to appliedDiscounts if no calculated breakdown yet
        else if (appliedDiscounts && appliedDiscounts.length > 0) {
            appliedDiscounts.forEach(function(discount) {
                html += `
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <span class="text-dark fw-medium">${escapeHtml(discount.discountName)}</span>
                            <br><small class="text-secondary">${escapeHtml(discount.displayValue || '')} ${discount.discountCode ? '(Code: ' + escapeHtml(discount.discountCode) + ')' : ''}</small>
                        </div>
                        <span class="text-secondary">Calculating...</span>
                    </div>
                `;
            });

            $('#review-discounts-list').html(html);
            $('#review-total-discount').text('Calculating...');
            $('#review-discounts-section').show();
            $('#review-discount-row').show();
            $('#review-discount-total').text('Calculating...');
        }
    }

    /**
     * Populate affiliate commissions in the review
     */
    function populateReviewAffiliates() {
        if (!affiliateCommissions || affiliateCommissions.length === 0) {
            $('#review-affiliates-section').hide();
            $('#review-commission-row').hide();
            $('#review-net-row').hide();
            return;
        }

        // Group commissions by affiliate
        const affiliateGroups = {};
        affiliateCommissions.forEach(function(comm) {
            const key = comm.affiliateId + '-' + comm.storeId;
            if (!affiliateGroups[key]) {
                affiliateGroups[key] = {
                    affiliateName: comm.affiliateName,
                    storeName: comm.storeName,
                    totalCommission: 0,
                    items: []
                };
            }
            affiliateGroups[key].totalCommission += parseFloat(comm.commission) || 0;
            affiliateGroups[key].items.push(comm);
        });

        let html = '';

        Object.values(affiliateGroups).forEach(function(group) {
            html += `
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <span class="text-dark fw-medium">${escapeHtml(group.affiliateName)}</span>
                        <br><small class="text-secondary">${escapeHtml(group.storeName)} (${group.items.length} item${group.items.length > 1 ? 's' : ''})</small>
                    </div>
                    <span class="text-warning fw-medium">₱${formatNumber(group.totalCommission)}</span>
                </div>
            `;
        });

        $('#review-affiliates-list').html(html);
        $('#review-total-commission').text('₱' + formatNumber(totalAffiliateCommission));
        $('#review-affiliates-section').show();
        $('#review-commission-row').show();
        $('#review-commission-deduct').text('-₱' + formatNumber(totalAffiliateCommission));
        $('#review-net-row').show();
    }

    /**
     * Calculate and display order totals in the review
     */
    function populateReviewTotals() {
        // Check if this is a package purchase
        const isPackagePurchase = activePackage !== null;

        // Calculate subtotal (uses package price if package is active)
        const subtotal = getOrderSubtotal();

        // Get shipping total
        const shipProducts = selectedProducts.filter(p =>
            (p.productType || '').toLowerCase() === 'ship'
        );
        let shippingTotal = 0;
        if (shipProducts.length > 0) {
            const shippingText = $('#total-shipping').text() || '₱0.00';
            shippingTotal = parseFloat(shippingText.replace(/[^\d.]/g, '')) || 0;
        }

        // Use the calculated discount total from Step 5
        const discountTotal = calculatedTotalDiscount || 0;

        // Calculate grand total
        const grandTotal = Math.max(0, subtotal + shippingTotal - discountTotal);

        // Update subtotal label and value based on purchase type
        const $subtotalRow = $('#review-subtotal').closest('.d-flex');
        if (isPackagePurchase) {
            // Change label to "Package Price" and add package icon
            $subtotalRow.find('span').first().html('<i class="mdi mdi-package-variant me-1"></i>Package Price:');

            // Add package savings row if not exists and there are savings
            const calculatedPrice = selectedProducts.reduce((sum, p) => sum + (parseFloat(p.price) * p.quantity), 0);
            const packageSavings = calculatedPrice - subtotal;

            if (packageSavings > 0) {
                // Add or update savings row
                if (!$('#review-package-savings-row').length) {
                    $subtotalRow.after(`
                        <div class="d-flex justify-content-between mb-3 text-success" id="review-package-savings-row">
                            <span class="fs-6"><i class="mdi mdi-piggy-bank me-1"></i>Package Savings:</span>
                            <span class="fs-6 fw-medium" id="review-package-savings">₱${formatNumber(packageSavings)}</span>
                        </div>
                    `);
                } else {
                    $('#review-package-savings').text('₱' + formatNumber(packageSavings));
                }
            } else {
                $('#review-package-savings-row').remove();
            }
        } else {
            // Restore original label
            $subtotalRow.find('span').first().text('Products Subtotal:');
            // Remove package savings row if exists
            $('#review-package-savings-row').remove();
        }

        $('#review-subtotal').text('₱' + formatNumber(subtotal));

        if (shippingTotal > 0) {
            $('#review-shipping-row').show();
            $('#review-shipping-total').text('₱' + formatNumber(shippingTotal));
        } else {
            $('#review-shipping-row').hide();
        }

        if (discountTotal > 0) {
            $('#review-discount-row').show();
            $('#review-discount-total').text('-₱' + formatNumber(discountTotal));
        } else {
            $('#review-discount-row').hide();
        }

        $('#review-grand-total').text('₱' + formatNumber(grandTotal));

        // Net revenue calculation (if affiliates exist)
        if (totalAffiliateCommission > 0) {
            $('#review-commission-row').show();
            $('#review-commission-deduct').text('-₱' + formatNumber(totalAffiliateCommission));
            $('#review-net-row').show();
            const netRevenue = grandTotal - totalAffiliateCommission;
            $('#review-net-revenue').text('₱' + formatNumber(netRevenue));
        } else {
            $('#review-commission-row').hide();
            $('#review-net-row').hide();
        }
    }

    // Helper: Format number
    function formatNumber(num) {
        return parseFloat(num).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    // Helper: Escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Confirm Order Button Handler
    $('#confirm-order-btn').on('click', function() {
        const $btn = $(this);
        const originalText = $btn.html();

        console.log('=== CONFIRM ORDER CLICKED ===');

        // Disable button and show loading
        $btn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin me-2"></i>Processing...');

        // Gather all order data
        const orderData = gatherOrderData();
        console.log('Order data gathered:', orderData);

        // Send to server
        $.ajax({
            url: '{{ route("ecom-orders-custom-add.store-order") }}',
            type: 'POST',
            data: JSON.stringify(orderData),
            contentType: 'application/json',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                console.log('Order creation response:', response);
                if (response.success) {
                    toastr.success(response.message || 'Order created successfully!', 'Success');
                    // Store success message in session storage for the orders page
                    sessionStorage.setItem('orderSuccess', 'Order #' + response.orderNumber + ' has been created and is pending.');
                    // Redirect to orders page
                    setTimeout(function() {
                        window.location.href = '{{ route("ecom-orders") }}';
                    }, 500);
                } else {
                    console.error('Order creation failed:', response);
                    toastr.error(response.message || 'Failed to create order', 'Error');
                    $btn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', { xhr: xhr, status: status, error: error });
                console.error('Response text:', xhr.responseText);
                let errorMessage = 'Failed to create order';
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    if (xhr.responseJSON.errors) {
                        const errors = Object.values(xhr.responseJSON.errors).flat();
                        errorMessage += ': ' + errors.join(', ');
                    }
                }
                toastr.error(errorMessage, 'Error');
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Gather all order data for submission
    function gatherOrderData() {
        // Products data
        const products = selectedProducts.map(function(product) {
            return {
                productId: product.productId,
                productName: product.productName,
                productStore: product.storeName || product.productStore,
                productType: product.productType,
                variantId: product.variantId,
                variantName: product.variantName,
                variantSku: product.sku || '',
                variantImage: product.image || '',
                unitPrice: parseFloat(product.price) || 0,
                quantity: parseInt(product.quantity) || 1
            };
        });

        // Client data
        const client = selectedClient ? {
            id: selectedClient.id,
            firstName: selectedClient.firstName || '',
            middleName: selectedClient.middleName || '',
            lastName: selectedClient.lastName || '',
            phone: selectedClient.phone || '',
            email: selectedClient.email || ''
        } : null;

        // Shipping type
        const shippingType = $('#shipping_type').val() || null;

        // Check if there are any ship-type products
        const hasShipProducts = selectedProducts.some(p => p.productType === 'ship');

        // Shipping recipient - always read from Step 4 form fields
        let shippingRecipient = {};
        if (hasShipProducts) {
            shippingRecipient = {
                firstName: $('#shipping_first_name').val() || '',
                middleName: $('#shipping_middle_name').val() || '',
                lastName: $('#shipping_last_name').val() || '',
                phone: $('#shipping_phone').val() || '',
                email: $('#shipping_email').val() || ''
            };
        }

        // Shipping address - only if there are ship products
        let shippingAddress = {};
        if (hasShipProducts) {
            shippingAddress = {
                houseNumber: $('#shipping_house_number').val() || '',
                street: $('#shipping_street').val() || '',
                zone: $('#shipping_zone').val() || '',
                municipality: $('#shipping_municipality').val() || '',
                province: $('#shipping_province').val() || '',
                zipCode: $('#shipping_zip_code').val() || ''
            };
        }

        // Access clients - format for server (map by variantId)
        const accessClients = {};
        // selectedAccessClients is keyed by store name with structure: { id, fullName, phone, email, store }
        // We need to map each access product's variantId to the access client for that store
        selectedProducts.forEach(function(product) {
            if (product.productType === 'access') {
                const storeName = product.productStore;
                const ac = selectedAccessClients[storeName];
                if (ac && ac.id) {
                    accessClients[product.variantId] = {
                        id: ac.id,
                        name: ac.fullName || '',
                        phone: ac.phone || '',
                        email: ac.email || ''
                    };
                }
            }
        });

        // Shipping methods - format for server
        // selectedShippingMethods maps variantId -> shippingId (integer)
        // availableShippingMethods maps shippingId -> method object with full details
        // allProductShippingData maps variantId -> { shippingOptions: [...] }
        const shippingMethods = {};
        const uniqueShippingNames = new Set();

        Object.keys(selectedShippingMethods).forEach(function(variantId) {
            const shippingId = selectedShippingMethods[variantId];
            if (shippingId) {
                let shippingCost = 0;
                let shippingMethodName = null;

                // Try to get shipping details from allProductShippingData first (most reliable)
                const productData = allProductShippingData[variantId];
                if (productData && productData.shippingOptions) {
                    const option = productData.shippingOptions.find(o => o.shippingId == shippingId);
                    if (option) {
                        shippingCost = parseFloat(option.shippingCost || 0);
                        shippingMethodName = option.shippingName || null;
                    }
                }

                // Fallback to availableShippingMethods if name not found
                if (!shippingMethodName && availableShippingMethods[shippingId]) {
                    shippingMethodName = availableShippingMethods[shippingId].shippingName || null;
                }

                shippingMethods[variantId] = {
                    id: shippingId,
                    name: shippingMethodName || 'Default Shipping',
                    cost: shippingCost
                };

                // Collect unique shipping names
                if (shippingMethodName) {
                    uniqueShippingNames.add(shippingMethodName);
                }
            }
        });

        // Get the primary shipping name (comma-separated if multiple)
        const shippingName = Array.from(uniqueShippingNames).join(', ') || null;

        // Discounts - format for server
        // Note: discountTrigger values from DB are "Auto Apply" or "Discount Code"
        const discounts = calculatedDiscountBreakdown.map(function(disc) {
            return {
                id: disc.id || disc.discountId,
                name: disc.name || disc.discountName,
                code: disc.code || disc.discountCode || null,
                type: disc.type || disc.discountType || 'percentage',
                value: parseFloat(disc.value || disc.discountValue || 0),
                calculatedAmount: parseFloat(disc.calculatedAmount || 0),
                isAutoApplied: disc.trigger === 'Auto Apply' || disc.isAutoApplied === true
            };
        });

        // Affiliate commissions - format for server
        const affCommissions = affiliateCommissions.map(function(comm) {
            return {
                affiliateId: comm.affiliateId || comm.id,
                affiliateName: comm.affiliateName || comm.name,
                affiliateEmail: comm.affiliateEmail || '',
                affiliatePhone: comm.affiliatePhone || '',
                storeId: comm.storeId || null,
                storeName: comm.storeName || '',
                percentage: parseFloat(comm.percentage || comm.commissionPercentage || 0),
                baseAmount: parseFloat(comm.baseAmount || 0),
                commissionAmount: parseFloat(comm.commission || comm.commissionAmount || 0)
            };
        });

        // Calculate totals - use package price if package is active
        const calculatedSubtotal = products.reduce((sum, p) => sum + (p.unitPrice * p.quantity), 0);
        const isPackagePurchase = activePackage !== null;

        // For package purchases, use package price as subtotal
        const subtotal = isPackagePurchase ? parseFloat(activePackage.packagePrice) : calculatedSubtotal;

        let shippingTotal = 0;
        Object.values(shippingMethods).forEach(function(m) {
            shippingTotal += parseFloat(m.cost || 0);
        });

        const discountTotal = calculatedTotalDiscount || 0;

        let affiliateCommissionTotal = 0;
        affCommissions.forEach(function(c) {
            affiliateCommissionTotal += parseFloat(c.commissionAmount || 0);
        });

        const grandTotal = subtotal + shippingTotal - discountTotal;
        const netRevenue = grandTotal - affiliateCommissionTotal;

        // Package data (if applicable)
        let packageData = null;
        if (isPackagePurchase) {
            packageData = {
                id: activePackage.id,
                name: activePackage.packageName,
                description: activePackage.packageDescription || null,
                calculatedPrice: calculatedSubtotal, // Sum of individual item prices
                packagePrice: parseFloat(activePackage.packagePrice),
                savings: calculatedSubtotal - parseFloat(activePackage.packagePrice)
            };
        }

        return {
            products: products,
            client: client,
            hasShipProducts: hasShipProducts,
            shippingType: hasShipProducts ? shippingType : null,
            shippingName: hasShipProducts ? shippingName : null,
            shippingRecipient: shippingRecipient,
            shippingAddress: shippingAddress,
            accessClients: accessClients,
            shippingMethods: shippingMethods,
            discounts: discounts,
            affiliateCommissions: affCommissions,
            // Package purchase data
            isPackage: isPackagePurchase,
            package: packageData,
            totals: {
                subtotal: subtotal,
                calculatedSubtotal: calculatedSubtotal, // Always include for reference
                shippingTotal: shippingTotal,
                discountTotal: discountTotal,
                grandTotal: grandTotal,
                affiliateCommissionTotal: affiliateCommissionTotal,
                netRevenue: netRevenue
            }
        };
    }

    // Initialize
    showStep(1);
    showProductsLoading(); // Show loading indicator
    loadProducts(); // Load products on page load
    loadPackages(); // Load packages on page load
});
</script>

@endsection

