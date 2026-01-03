@extends('layouts.master')

@section('title')
    Add New Order
@endsection

@section('css')
<!-- Toastr -->
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Ecommerce
        @endslot
        @slot('li_2')
            Orders
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

            <!-- Ship Products Stores -->
            <div id="ship-stores-container">
                <!-- Stores will be dynamically loaded here -->
            </div>

            <!-- No Ship Products Message -->
            <div id="no-ship-products" class="text-center py-5" style="display: none;">
                <i class="mdi mdi-skip-next display-4 text-muted mb-3"></i>
                <h6 class="text-muted">No Ship Products Selected</h6>
                <p class="text-muted">You are not buying any ship type products, so skip this step.</p>
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
                                    <label for="shipping_middle_name" class="form-label">Middle Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="shipping_middle_name" name="shipping_middle_name" required>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="shipping_last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="shipping_last_name" name="shipping_last_name" required>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="shipping_phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="shipping_phone" name="shipping_phone" required>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="shipping_email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="shipping_email" name="shipping_email" required>
                                    <div class="invalid-feedback"></div>
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
                                    <label for="shipping_house_number" class="form-label">House Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="shipping_house_number" name="shipping_house_number" required>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <div class="col-md-8 mb-3">
                                    <label for="shipping_street" class="form-label">Street <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="shipping_street" name="shipping_street" required>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="shipping_zone" class="form-label">Zone (if any)</label>
                                    <input type="text" class="form-control" id="shipping_zone" name="shipping_zone">
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
                                    <label for="shipping_barangay" class="form-label">Barangay <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="shipping_barangay" name="shipping_barangay" rows="2" required disabled></textarea>
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

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h6 class="card-title mb-0" style="color: #fff !important;">
                                <i class="mdi mdi-account-group me-2"></i>Affiliate Settings
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="text-center py-5">
                                <i class="mdi mdi-account-group text-muted" style="font-size: 4rem;"></i>
                                <h5 class="text-muted mt-3">Affiliate Features Coming Soon</h5>
                                <p class="text-secondary">
                                    This section will allow you to assign affiliate commissions and track referrals for this order.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Order Summary Card -->
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">
                                <i class="mdi mdi-clipboard-text me-2"></i>Order Summary
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
                            <hr>
                            <div class="d-flex justify-content-between fw-bold fs-5">
                                <span>Grand Total:</span>
                                <span id="affiliate-grand-total" class="text-primary">₱0.00</span>
                            </div>
                        </div>
                    </div>

                    <!-- Info Card -->
                    <div class="card border-info">
                        <div class="card-body">
                            <h6 class="card-title text-info">
                                <i class="mdi mdi-information me-1"></i>About Affiliates
                            </h6>
                            <p class="text-muted small mb-0">
                                The affiliate system will enable you to:
                            </p>
                            <ul class="text-muted small mt-2 mb-0">
                                <li>Assign affiliate referrals to orders</li>
                                <li>Calculate commission percentages</li>
                                <li>Track affiliate performance</li>
                            </ul>
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

</style>
<script>
$(document).ready(function() {
    let currentStep = 1;
    const totalSteps = 6;
    let selectedProducts = [];
    let currentProductsPage = 1;
    let currentStoreSearch = '';
    let currentProductSearch = '';
    let searchTimeout;
    let variantSearchTimeout = {};
    let currentVariantForModal = null;

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
        const totalAmount = selectedProducts.reduce((sum, item) => sum + (parseFloat(item.price) * item.quantity), 0);
        const totalItems = selectedProducts.reduce((sum, item) => sum + item.quantity, 0);
        const uniqueProducts = new Set(selectedProducts.map(item => item.productId)).size;

        // Animate the summary update
        $('#total-items').fadeOut(100, function() {
            $(this).text(totalItems);
            // Remove any existing product count text first
            $(this).find('small').remove();
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

    // Show step
    function showStep(step) {
        // Save shipping form data when leaving step 4
        if (currentStep === 4) {
            saveShippingFormData();
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
    }

    // Update affiliate summary (Step 6)
    function updateAffiliateSummary() {
        // Calculate subtotal from selected products
        const subtotal = selectedProducts.reduce((sum, item) => sum + (parseFloat(item.price) * item.quantity), 0);

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

        const grandTotal = Math.max(0, subtotal - discountTotal + shippingTotal);

        // Update affiliate summary
        $('#affiliate-subtotal').text('₱' + formatNumber(subtotal));
        $('#affiliate-shipping').text('₱' + formatNumber(shippingTotal));

        if (discountTotal > 0) {
            $('#affiliate-discount').text('-₱' + formatNumber(discountTotal));
            $('#affiliate-discount-row').show();
        } else {
            $('#affiliate-discount-row').hide();
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

        // Special validation for step 4 - check if shipping address is complete
        if (step === 4) {
            // Get ship products (products with type 'ship')
            const shipProducts = selectedProducts.filter(product =>
                product.productType === 'ship' || product.productType === 'Ship'
            );

            // Validate ship coverage for ship products
            const province = $('#shipping_province').val();
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
                // Validate all required shipping address fields
                const requiredFields = [
                    'shipping_first_name', 'shipping_middle_name', 'shipping_last_name',
                    'shipping_phone', 'shipping_email', 'shipping_house_number',
                    'shipping_street', 'shipping_province', 'shipping_municipality',
                    'shipping_barangay', 'shipping_zip_code'
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
            showErrorAlertModal('An error occurred while validating. Please try again.');
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
        const cartItems = selectedProducts.map(item => ({
            productId: item.productId,
            productType: item.productType,
            quantity: item.quantity
        }));

        // Get current shipping value from the display
        let currentShipping = 0;
        const shippingText = $('#shipping_total_calculated').text();
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
                                $('#shipping_total_calculated').text('₱' + formatNumber(newShipping));
                                orderShipping = newShipping;
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

    // Load ship products by store for step 4
    function loadShipProductsStores() {
        const shipProducts = selectedProducts.filter(product => {
            // Check if product type is 'ship' - you may need to adjust this based on your data structure
            return product.productType === 'ship' || product.productType === 'Ship';
        });

        if (shipProducts.length === 0) {
            $('#no-ship-products').show();
            $('#ship-stores-container').hide();
            $('#shipping-address-section').hide();

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

        // Generate products HTML
        let productsHtml = `
            <div class="mb-3">
                <h6>Selected Ship Products (${storeShipProducts.length})</h6>
            </div>
        `;

        storeShipProducts.forEach((product, index) => {
            const quantity = product.quantity || 1;
            const price = parseFloat(product.price) || 0;
            const subtotal = quantity * price;

            productsHtml += `
                <div class="card mb-2">
                    <div class="card-body py-2">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <h6 class="mb-1">${product.productName || 'Unnamed Product'}</h6>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted">Variant:</small>
                                <div class="fw-medium">${product.variantName || 'Default'}</div>
                            </div>
                            <div class="col-md-2 text-center">
                                <small class="text-muted">Quantity:</small>
                                <div class="fw-bold text-primary">${quantity}</div>
                            </div>
                            <div class="col-md-2 text-center">
                                <small class="text-muted">Price:</small>
                                <div class="fw-medium">$${price.toFixed(2)}</div>
                            </div>
                            <div class="col-md-2 text-end">
                                <small class="text-muted">Subtotal:</small>
                                <div class="fw-bold text-success">$${subtotal.toFixed(2)}</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        $(containerId).html(productsHtml);
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

        // Set Pangasinan as default if there are ship products and no saved province
        const shipProducts = selectedProducts && selectedProducts.length > 0
            ? selectedProducts.filter(product =>
                product.productType === 'ship' || product.productType === 'Ship'
            )
            : [];

        // Check if there's no saved province or saved province is empty
        const currentProvinceValue = provinceSelect.val();
        const hasSavedProvince = shippingFormData.shipping_province && shippingFormData.shipping_province !== '';

        // Auto-select Pangasinan if:
        // 1. There are ship products
        // 2. No saved province data
        // 3. Province dropdown is currently empty
        if (shipProducts.length > 0 && !hasSavedProvince && !currentProvinceValue) {
            console.log('Auto-selecting Pangasinan for ship products');
            provinceSelect.val('Pangasinan');

            // Load municipalities for Pangasinan
            loadMunicipalities('Pangasinan', function() {
                console.log('Municipalities loaded, triggering calculation');
                // Trigger shipping calculation after setting default province
                if (selectedProducts && selectedProducts.length > 0) {
                    calculateShippingCosts();
                }
            });
        }
    }

    // Load municipalities for selected province
    function loadMunicipalities(province, callback) {
        const municipalitySelect = $('#shipping_municipality');

        if (!province) {
            municipalitySelect.html('<option value="">Select Municipality/City</option>').prop('disabled', true);
            $('#shipping_barangay').prop('disabled', true).val('');
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
        // Accept various Philippine phone formats
        const phoneRegex = /^(\+63|0)?[0-9]{10}$/;
        return phoneRegex.test(phone.replace(/[\s\-\(\)]/g, ''));
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

            // Recalculate shipping when province changes (only if products exist)
            if (selectedProducts && selectedProducts.length > 0) {
                calculateShippingCosts();
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
        $(document).on('change', '#shipping_municipality', function() {
            const selectedMunicipality = $(this).val();
            if (selectedMunicipality) {
                $('#shipping_barangay').prop('disabled', false);
            } else {
                $('#shipping_barangay').prop('disabled', true).val('');
            }
        });

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

        // Early return if no products selected
        if (!selectedProducts || selectedProducts.length === 0) {
            $('#shipping-calculation-loading').hide();
            $('#shipping-calculation-results').hide();
            $('#no-province-selected').hide();
            $('#no-ship-products-calculation').show();
            return;
        }

        // Show loading state with spinner
        $('#shipping-calculation-loading').show();
        $('#shipping-calculation-results').hide();
        $('#no-province-selected').hide();
        $('#no-ship-products-calculation').hide();

        // Prepare data for API call - send ALL selected products
        const requestData = {
            selectedProducts: selectedProducts,
            province: province || '' // Send empty string if no province selected
        };

        // Debug log
        console.log('Calculating shipping with data:', {
            province: province,
            productsCount: selectedProducts.length,
            products: selectedProducts
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

        // Debug: Log the received data
        console.log('Shipping calculation data received:', data);
        console.log('Product stores found:', data.completeBreakdown.map(item => ({
            productName: item.productName,
            productStore: item.productStore,
            productType: item.productType
        })));
        console.log('All product data:', data.completeBreakdown);

        // Update order summary
        $('#order-subtotal').text('₱' + data.subtotal.toFixed(2));
        $('#total-shipping').text('₱' + data.totalShipping.toFixed(2));
        $('#order-total').text('₱' + data.total.toFixed(2));

        // Generate complete breakdown with all products
        let breakdownHtml = '';
        let productSummaryHtml = '';

        if (data.completeBreakdown && data.completeBreakdown.length > 0) {
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

            data.completeBreakdown.forEach(item => {
                const total = item.subtotal + item.shippingPrice;
                const typeBadge = item.productType === 'ship' ?
                    '<span class="badge bg-primary">Ship</span>' :
                    '<span class="badge bg-primary">Access</span>';

                let detailsHtml = '';
                if (item.shippingDetails) {
                    const shippingDetails = item.shippingDetails;
                    detailsHtml = `
                        <div class="small">
                            <div><strong>${shippingDetails.shippingName || 'Default Shipping'}</strong></div>
                            <div><strong>${shippingDetails.pricingType || 'N/A'}</strong></div>
                            <div>Max Qty: ${shippingDetails.maxQuantity || 'N/A'}</div>
                            <div>Price/Batch: ₱${parseFloat(shippingDetails.pricePerBatch || 0).toFixed(2)}</div>
                            <div>Batches: ${shippingDetails.batches || 'N/A'}</div>
                            <div class="text-muted">${shippingDetails.province || 'N/A'}</div>
                        </div>
                    `;
                } else {
                    detailsHtml = '<div class="small text-muted">No shipping required</div>';
                }

                breakdownHtml += `
                    <tr>
                        <td class="align-middle">${typeBadge}</td>
                        <td class="align-middle">${item.productName}</td>
                        <td class="align-middle">${item.variantName}</td>
                        <td class="text-center align-middle">${item.quantity}</td>
                        <td class="text-end align-middle">₱${item.subtotal.toFixed(2)}</td>
                        <td class="text-end align-middle">₱${item.shippingPrice.toFixed(2)}</td>
                        <td class="text-end align-middle fw-bold">₱${total.toFixed(2)}</td>
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

            // Generate product summary
            productSummaryHtml = `
                <div class="d-flex justify-content-between mb-2">
                    <span>Ship Products:</span>
                    <span>${shipCount} items - ₱${shipTotal.toFixed(2)}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Access Products:</span>
                    <span>${accessCount} items - ₱${accessTotal.toFixed(2)}</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between fw-bold">
                    <span>Total Items:</span>
                    <span>${shipCount + accessCount} items</span>
                </div>
            `;
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
            const sourceLabel = discount.trigger === 'auto'
                ? '<span class="badge bg-success"><i class="mdi mdi-auto-fix me-1"></i>Auto-Apply</span>'
                : '<span class="badge bg-primary"><i class="mdi mdi-ticket-percent me-1"></i>Code</span>';

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
    function addAppliedDiscount(discount, trigger) {
        if (!isDiscountApplied(discount.id)) {
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
        // Calculate subtotal from selected products
        orderSubtotal = 0;
        orderShipping = 0;

        selectedProducts.forEach(function(product) {
            const price = parseFloat(product.price) || 0;
            const quantity = parseInt(product.quantity) || 1;
            orderSubtotal += price * quantity;
        });

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

        let html = '<table class="table table-sm table-bordered mb-0">';
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

        if (totalDiscount > 0) {
            $('#discount-row').show();
            $('#discount-amount').text('-₱' + formatNumber(totalDiscount));
        } else {
            $('#discount-row').hide();
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

    // Initialize
    showStep(1);
    showProductsLoading(); // Show loading indicator
    loadProducts(); // Load products on page load
});
</script>

@endsection

