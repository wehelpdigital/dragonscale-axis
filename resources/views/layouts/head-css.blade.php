@yield('css')

<!-- Bootstrap Css -->
<link href="{{ URL::asset('build/css/bootstrap.min.css') }}" id="bootstrap-style" rel="stylesheet" type="text/css" />
<!-- Icons Css -->
<link href="{{ URL::asset('build/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
<!-- App Css-->
<link href="{{ URL::asset('build/css/app.min.css') }}" id="app-style" rel="stylesheet" type="text/css" />

<!-- Global Custom Styles -->
<style>
/* Fix unnecessary horizontal scrollbar in DataTables */
.dataTables_wrapper {
    overflow: visible;
}
.dataTables_wrapper .table-responsive {
    overflow-x: auto;
    overflow-y: hidden;
}
.dataTables_wrapper table.dataTable {
    width: 100% !important;
}

/* =================================================================
   GLOBAL MODAL FIX: Prevent stacking context issues
   The .page-content has transform animation which creates stacking context.
   Modals are moved to body via JS, and this CSS ensures proper display.
   ================================================================= */
/* Disable Bootstrap backdrop - modal provides its own overlay */
.modal-backdrop {
    display: none !important;
}

/* Modal provides its own dark overlay - covers ENTIRE viewport */
.modal {
    background: rgba(0, 0, 0, 0.5) !important;
}

/* Ensure modal dialog is clickable and centered */
.modal-dialog {
    pointer-events: auto;
}

/* Ensure modal content is above the overlay */
.modal-content {
    pointer-events: auto;
}

/* Fix: Override excessive min-height when sidebar is collapsed */
body[data-sidebar=dark].vertical-collpsed {
    min-height: auto !important;
}

/* Desktop collapsed sidebar state (> 1400px) - ensure proper widths */
@media (min-width: 1401px) {
    .page-content {
        padding-top: 90px;
    }

    body.vertical-collpsed .vertical-menu {
        width: 70px !important;
    }

    body.vertical-collpsed .main-content {
        margin-left: 70px !important;
    }

    body.vertical-collpsed .navbar-brand-box {
        width: 70px !important;
    }

    body.vertical-collpsed .footer {
        left: 70px !important;
    }
}

/* Sidebar smooth animation */
.vertical-menu,
.main-content,
.navbar-brand-box,
.footer {
    transition: all 0.2s ease-in-out;
}
</style>

<!-- App js -->
<script src="{{ URL::asset('build/js/plugin.js') }}"></script>

<!-- Restore sidebar state immediately to prevent flash -->
<script>
(function() {
    if (localStorage.getItem('sidebar-collapsed') === 'true') {
        document.documentElement.classList.add('sidebar-will-collapse');
    }
})();
</script>
<style>
html.sidebar-will-collapse body {
    --sidebar-collapsed: true;
}
html.sidebar-will-collapse body.vertical-collpsed .vertical-menu,
html.sidebar-will-collapse .vertical-menu {
    width: 70px !important;
}
html.sidebar-will-collapse .main-content {
    margin-left: 70px !important;
}
html.sidebar-will-collapse .navbar-brand-box {
    width: 70px !important;
}
html.sidebar-will-collapse .footer {
    left: 70px !important;
}
html.sidebar-will-collapse .logo span.logo-lg {
    display: none !important;
}
html.sidebar-will-collapse .logo span.logo-sm {
    display: block !important;
}
@media (max-width: 991.98px) {
    html.sidebar-will-collapse .main-content {
        margin-left: 0 !important;
    }
    html.sidebar-will-collapse .footer {
        left: 0 !important;
    }
}

/* =====================================================
   TABLET & SMALL MONITOR ENHANCEMENTS (1024px - 1366px)
   ===================================================== */

/* Common laptop resolution (1366px and below) */
@media (max-width: 1400px) {
    .page-content {
        padding: 90px 20px 60px 20px;
    }

    .container-fluid {
        padding-left: 15px;
        padding-right: 15px;
    }

    /* Sidebar - wide enough for menu text */
    .vertical-menu {
        width: 270px !important;
    }

    .main-content {
        margin-left: 270px !important;
    }

    .navbar-brand-box {
        width: 270px !important;
    }

    .footer {
        left: 270px !important;
    }

    /* Collapsed sidebar state */
    body.vertical-collpsed .vertical-menu {
        width: 70px !important;
    }

    body.vertical-collpsed .main-content {
        margin-left: 70px !important;
    }

    body.vertical-collpsed .navbar-brand-box {
        width: 70px !important;
    }

    body.vertical-collpsed .footer {
        left: 70px !important;
    }

    /* Menu item adjustments */
    #sidebar-menu ul li a {
        padding: 10px 18px;
        font-size: 13.5px;
    }

    #sidebar-menu ul li a i {
        font-size: 1.15rem;
        min-width: 1.75rem;
        margin-right: 8px;
    }

    /* Submenu indentation for readability */
    #sidebar-menu .sub-menu li a {
        padding-left: 48px;
        font-size: 13px;
    }

    #sidebar-menu .sub-menu .sub-menu li a {
        padding-left: 56px;
        font-size: 12.5px;
    }

    #sidebar-menu .sub-menu .sub-menu .sub-menu li a {
        padding-left: 64px;
        font-size: 12px;
    }

    .menu-title {
        font-size: 10px;
        padding: 12px 18px 6px;
        letter-spacing: 0.5px;
    }

    /* Card adjustments */
    .card-body {
        padding: 18px;
    }

    .card-header {
        padding: 14px 18px;
    }

    /* Table adjustments */
    .table thead th {
        font-size: 13px;
        padding: 12px 10px;
    }

    .table tbody td {
        font-size: 13px;
        padding: 12px 10px;
    }

    /* Form elements */
    .form-control,
    .form-select {
        padding: 8px 12px;
    }
}

/* Small laptops and large tablets (1280px and below) */
@media (max-width: 1280px) {
    .page-content {
        padding: 90px 16px 60px 16px;
    }

    /* Sidebar - wider for text readability */
    .vertical-menu {
        width: 265px !important;
    }

    .main-content {
        margin-left: 265px !important;
    }

    .navbar-brand-box {
        width: 265px !important;
    }

    .footer {
        left: 265px !important;
    }

    /* Collapsed sidebar state */
    body.vertical-collpsed .vertical-menu {
        width: 70px !important;
    }

    body.vertical-collpsed .main-content {
        margin-left: 70px !important;
    }

    body.vertical-collpsed .navbar-brand-box {
        width: 70px !important;
    }

    body.vertical-collpsed .footer {
        left: 70px !important;
    }

    /* Logo adjustments */
    .logo-lg img {
        max-width: 160px;
        height: auto;
    }

    #sidebar-menu ul li a {
        padding: 9px 16px;
        font-size: 13px;
    }

    #sidebar-menu ul li a i {
        font-size: 1.1rem;
        min-width: 1.6rem;
        margin-right: 6px;
    }

    /* Submenu indentation */
    #sidebar-menu .sub-menu li a {
        padding-left: 44px;
        font-size: 12.5px;
    }

    #sidebar-menu .sub-menu .sub-menu li a {
        padding-left: 52px;
        font-size: 12px;
    }

    #sidebar-menu .sub-menu .sub-menu .sub-menu li a {
        padding-left: 58px;
        font-size: 11.5px;
    }

    .menu-title {
        font-size: 10px;
        padding: 10px 16px 5px;
    }

    /* Expand arrow adjustments */
    #sidebar-menu ul li a.has-arrow::after {
        font-size: 0.6rem;
        right: 14px;
    }

    /* Card adjustments */
    .card-body {
        padding: 16px;
    }

    .card-header {
        padding: 12px 16px;
    }

    .card-title {
        font-size: 15px;
    }

    /* Stats cards - better grid */
    .row > [class*="col-md-3"] {
        padding-left: 8px;
        padding-right: 8px;
    }

    /* Button sizes */
    .btn {
        padding: 7px 14px;
        font-size: 13px;
    }

    .btn-sm {
        padding: 5px 10px;
        font-size: 12px;
    }

    /* DataTables adjustments */
    .dataTables_wrapper .dataTables_filter input {
        width: 180px;
    }
}

/* iPad landscape and 1024px monitors */
@media (max-width: 1024px) {
    .page-content {
        padding: 85px 14px 60px 14px;
    }

    /* Sidebar - wide enough for menu text */
    .vertical-menu {
        width: 260px !important;
    }

    .main-content {
        margin-left: 260px !important;
    }

    .navbar-brand-box {
        width: 260px !important;
    }

    .footer {
        left: 260px !important;
    }

    /* Collapsed sidebar state */
    body.vertical-collpsed .vertical-menu {
        width: 70px !important;
    }

    body.vertical-collpsed .main-content {
        margin-left: 70px !important;
    }

    body.vertical-collpsed .navbar-brand-box {
        width: 70px !important;
    }

    body.vertical-collpsed .footer {
        left: 70px !important;
    }

    /* Logo adjustments */
    .logo-lg img {
        max-width: 155px;
        height: auto;
    }

    #sidebar-menu ul li a {
        padding: 8px 14px;
        font-size: 12.5px;
        line-height: 1.4;
    }

    #sidebar-menu ul li a i {
        font-size: 1rem;
        min-width: 1.5rem;
        margin-right: 5px;
    }

    /* Submenu indentation for 1024px */
    #sidebar-menu .sub-menu li a {
        padding-left: 40px;
        font-size: 12px;
    }

    #sidebar-menu .sub-menu .sub-menu li a {
        padding-left: 48px;
        font-size: 11.5px;
    }

    #sidebar-menu .sub-menu .sub-menu .sub-menu li a {
        padding-left: 54px;
        font-size: 11px;
    }

    .menu-title {
        font-size: 9px;
        padding: 8px 14px 5px;
        letter-spacing: 0.4px;
    }

    /* Better visibility for nested menus */
    #sidebar-menu .sub-menu {
        background-color: rgba(0, 0, 0, 0.03);
    }

    #sidebar-menu .sub-menu .sub-menu {
        background-color: rgba(0, 0, 0, 0.02);
    }

    /* Arrow indicators */
    #sidebar-menu ul li a.has-arrow::after {
        font-size: 0.55rem;
        right: 12px;
    }

    /* Card adjustments */
    .card-body {
        padding: 14px;
    }

    .card-header {
        padding: 12px 14px;
    }

    .card-title {
        font-size: 14px;
    }

    /* Table adjustments */
    .table thead th {
        font-size: 12px;
        padding: 10px 8px;
    }

    .table tbody td {
        font-size: 12.5px;
        padding: 10px 8px;
    }

    /* Badge sizes */
    .badge {
        font-size: 11px;
        padding: 4px 8px;
    }

    /* Button group adjustments */
    .btn-group .btn {
        padding: 5px 8px;
    }

    /* Stats cards responsive */
    .stats-card .card-body,
    [class*="stats-card"] {
        padding: 12px 14px;
    }

    /* Form adjustments */
    .form-label {
        font-size: 13px;
    }

    .form-control,
    .form-select {
        font-size: 13px;
        padding: 7px 10px;
    }

    /* DataTables compact */
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
        font-size: 13px;
    }

    .dataTables_wrapper .dataTables_filter input {
        width: 160px;
        padding: 5px 10px;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 6px 10px;
        font-size: 12px;
    }

    .dataTables_wrapper .dataTables_info {
        font-size: 12px;
    }

    /* Modal sizing */
    .modal-lg {
        max-width: 700px;
    }

    .modal-body {
        padding: 18px;
    }

    /* Breadcrumb */
    .page-title-box h4 {
        font-size: 17px;
    }

    .page-title-box .breadcrumb {
        font-size: 12px;
    }

    /* Topbar adjustments */
    .header-item {
        padding: 0 10px;
    }

    .dropdown-menu {
        font-size: 13px;
    }
}

/* Very small monitors (around 1024px) */
@media (min-width: 992px) and (max-width: 1099px) {
    /* Keep sidebar wide enough for text */
    .vertical-menu {
        width: 255px !important;
    }

    .main-content {
        margin-left: 255px !important;
    }

    .navbar-brand-box {
        width: 255px !important;
    }

    .footer {
        left: 255px !important;
    }

    /* Collapsed sidebar state */
    body.vertical-collpsed .vertical-menu {
        width: 70px !important;
    }

    body.vertical-collpsed .main-content {
        margin-left: 70px !important;
    }

    body.vertical-collpsed .navbar-brand-box {
        width: 70px !important;
    }

    body.vertical-collpsed .footer {
        left: 70px !important;
    }

    /* Compact menu items */
    #sidebar-menu ul li a {
        padding: 7px 12px;
        font-size: 12px;
    }

    #sidebar-menu ul li a i {
        font-size: 0.95rem;
        min-width: 1.4rem;
    }

    /* Submenu spacing */
    #sidebar-menu .sub-menu li a {
        padding-left: 38px;
        font-size: 11.5px;
        padding-top: 6px;
        padding-bottom: 6px;
    }

    #sidebar-menu .sub-menu .sub-menu li a {
        padding-left: 46px;
        font-size: 11px;
    }

    #sidebar-menu .sub-menu .sub-menu .sub-menu li a {
        padding-left: 52px;
        font-size: 10.5px;
    }

    .menu-title {
        font-size: 9px;
        padding: 8px 12px 4px;
    }

    /* Logo */
    .logo-lg img {
        max-width: 150px;
    }
}

/* Tablet portrait mode (768px - 991px) */
@media (min-width: 768px) and (max-width: 991.98px) {
    .page-content {
        padding: 65px 12px 60px 12px;
    }

    /* Stats row - 2 columns */
    .row > [class*="col-md-3"] {
        flex: 0 0 50%;
        max-width: 50%;
        margin-bottom: 12px;
    }

    .row > [class*="col-md-6"] {
        flex: 0 0 100%;
        max-width: 100%;
    }

    /* Card grid - 2 columns max */
    .row > [class*="col-lg-3"] {
        flex: 0 0 50%;
        max-width: 50%;
    }

    .row > [class*="col-lg-4"] {
        flex: 0 0 50%;
        max-width: 50%;
    }

    /* Card adjustments */
    .card-body {
        padding: 14px;
    }

    /* Filter rows */
    .row.mb-4 > [class*="col-md-"] {
        margin-bottom: 10px;
    }

    /* Action buttons inline */
    .d-flex.gap-1 .btn,
    .d-flex.gap-2 .btn {
        padding: 5px 8px;
    }

    /* Hide less important columns */
    .table .d-none-tablet {
        display: none !important;
    }
}

/* =====================================================
   SIDEBAR ENHANCEMENTS FOR ALL SCREEN SIZES
   ===================================================== */

/* Base sidebar improvements */
#sidebar-menu ul li a {
    display: flex;
    align-items: center;
    line-height: 1.4;
}

/* Prevent text wrapping in sidebar menu items */
#sidebar-menu ul li a,
#sidebar-menu ul li a span {
    white-space: nowrap !important;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Arrow indicator - let metismenu handle this */

/* Submenu styling - no margin overlap */
#sidebar-menu .sub-menu {
    padding-left: 0;
    list-style: none;
}

/* Active menu item highlight */
#sidebar-menu ul li.mm-active > a {
    background: rgba(255, 255, 255, 0.05);
}

/* Menu dividers styling */
.menu-title {
    color: rgba(255, 255, 255, 0.4) !important;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Better scrollbar for sidebar */
.vertical-menu[data-simplebar] .simplebar-track.simplebar-vertical {
    width: 6px;
    right: 2px;
}

.vertical-menu[data-simplebar] .simplebar-scrollbar::before {
    background: rgba(255, 255, 255, 0.25) !important;
    border-radius: 3px;
}

/* Hover effects for desktop */
@media (min-width: 992px) {
    #sidebar-menu ul li a:hover {
        background-color: rgba(255, 255, 255, 0.05);
        transition: all 0.2s ease;
    }
}

/* =====================================================
   MOBILE SIDEBAR ENHANCEMENTS WITH ANIMATIONS
   ===================================================== */

/* Mobile sidebar sliding animation */
@media (max-width: 991.98px) {
    .vertical-menu {
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1),
                    box-shadow 0.3s ease !important;
        transform: translateX(-100%);
        box-shadow: none;
        z-index: 1050;
        position: fixed !important;
        left: 0;
        top: 0;
        height: 100%;
    }

    body.sidebar-enable .vertical-menu {
        transform: translateX(0);
        box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15);
    }

    /* CRITICAL: Reset main-content margin on mobile - sidebar is overlay */
    .main-content {
        margin-left: 0 !important;
        width: 100% !important;
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }

    /* Backdrop overlay - using a real element for click handling */
    .sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1040;
        cursor: pointer;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity 0.3s ease, visibility 0.3s ease;
    }

    body.sidebar-enable .sidebar-overlay {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }

    /* Prevent body scroll when sidebar is open */
    body.sidebar-enable {
        overflow: hidden;
    }
}

/* Touch-friendly menu items */
@media (max-width: 991.98px) {
    #sidebar-menu ul li a {
        min-height: 48px;
        display: flex !important;
        align-items: center;
        padding: 12px 20px !important;
        transition: all 0.2s ease;
    }

    #sidebar-menu ul li a:active {
        background-color: rgba(255, 255, 255, 0.1) !important;
        transform: scale(0.98);
    }

    #sidebar-menu ul li a i {
        font-size: 1.25rem;
        min-width: 2rem;
    }

    /* Submenu styling for mobile */
    #sidebar-menu .sub-menu {
        overflow: hidden;
    }

    #sidebar-menu .sub-menu li a {
        padding-left: 55px !important;
        font-size: 14px;
        min-height: 44px;
    }

    #sidebar-menu .sub-menu .sub-menu li a {
        padding-left: 70px !important;
        font-size: 13.5px;
    }

    #sidebar-menu .sub-menu .sub-menu .sub-menu li a {
        padding-left: 85px !important;
        font-size: 13px;
    }

    /* Active menu highlight - use border instead of ::after to avoid arrow conflict */
    #sidebar-menu ul li.mm-active > a {
        border-left: 3px solid #556ee6;
        background: rgba(255, 255, 255, 0.08);
    }

    /* Menu title styling */
    .menu-title {
        font-size: 11px !important;
        letter-spacing: 0.5px;
        padding: 12px 20px 8px !important;
    }

    /* Scrollbar styling for sidebar */
    .vertical-menu[data-simplebar] .simplebar-scrollbar::before {
        background: rgba(255, 255, 255, 0.3) !important;
        border-radius: 4px;
    }
}

/* Hamburger button animation */
#vertical-menu-btn {
    position: relative;
    transition: all 0.3s ease;
}

#vertical-menu-btn:active {
    transform: scale(0.9);
}

#vertical-menu-btn i {
    transition: transform 0.3s ease;
}

body.sidebar-enable #vertical-menu-btn i {
    transform: rotate(90deg);
}

/* Small mobile adjustments */
@media (max-width: 575.98px) {
    .vertical-menu {
        width: 290px !important;
    }

    #sidebar-menu ul li a {
        font-size: 14px;
        padding: 10px 16px;
    }

    #sidebar-menu ul li a i {
        font-size: 1.1rem;
        min-width: 1.75rem;
    }

    /* Submenu spacing for mobile */
    #sidebar-menu .sub-menu li a {
        padding-left: 48px;
        font-size: 13.5px;
    }

    #sidebar-menu .sub-menu .sub-menu li a {
        padding-left: 56px;
        font-size: 13px;
    }

    #sidebar-menu .sub-menu .sub-menu .sub-menu li a {
        padding-left: 64px;
        font-size: 12.5px;
    }

    .navbar-brand-box {
        padding: 0 0.5rem;
    }

    .logo-lg img {
        max-width: 160px;
        height: auto !important;
    }

    /* Touch feedback for topbar items */
    .header-item {
        min-width: 44px;
        min-height: 44px;
        display: flex !important;
        align-items: center;
        justify-content: center;
    }

    .header-item:active {
        background-color: rgba(0, 0, 0, 0.05) !important;
    }
}

/* Tablet landscape improvements - sidebar opens as overlay */
@media (min-width: 768px) and (max-width: 991.98px) {
    .vertical-menu {
        width: 300px !important;
    }

    /* Better sidebar styling for tablet landscape */
    #sidebar-menu ul li a {
        padding: 11px 18px;
        font-size: 14px;
    }

    #sidebar-menu ul li a span {
        font-size: 14px;
    }

    #sidebar-menu ul li a i {
        font-size: 1.15rem;
        min-width: 1.75rem;
    }

    /* Submenu spacing - wider to prevent text wrap */
    #sidebar-menu .sub-menu li a {
        padding-left: 50px;
        font-size: 13.5px;
    }

    #sidebar-menu .sub-menu .sub-menu li a {
        padding-left: 58px;
        font-size: 13px;
    }

    #sidebar-menu .sub-menu .sub-menu .sub-menu li a {
        padding-left: 66px;
        font-size: 12.5px;
    }

    .menu-title {
        font-size: 10px;
        padding: 12px 18px 6px;
    }

    /* Better logo spacing */
    .navbar-brand-box {
        padding: 0 15px;
    }

    .logo-lg img {
        max-width: 180px;
    }
}

/* Touch device optimizations */
@media (hover: none) and (pointer: coarse) {
    /* Disable hover effects on touch devices */
    #sidebar-menu ul li a:hover {
        background-color: transparent !important;
    }

    /* Better touch targets */
    #sidebar-menu ul li a {
        -webkit-tap-highlight-color: rgba(255, 255, 255, 0.1);
    }

    /* Smooth scrolling in sidebar */
    .simplebar-content-wrapper {
        -webkit-overflow-scrolling: touch;
    }
}

/* Page content adjustments for mobile */
@media (max-width: 991.98px) {
    /* Reset ALL sidebar-related margins/widths on mobile */
    body .main-content,
    body[data-sidebar] .main-content,
    body.vertical-collpsed .main-content,
    body[data-sidebar=dark] .main-content,
    body[data-sidebar=dark].vertical-collpsed .main-content {
        margin-left: 0 !important;
        width: 100% !important;
    }

    body .navbar-brand-box,
    body.vertical-collpsed .navbar-brand-box {
        width: auto !important;
        position: relative;
    }

    .page-content {
        padding: 80px 12px 60px 12px !important;
        margin-left: 0 !important;
    }

    .container-fluid {
        padding-left: 8px;
        padding-right: 8px;
    }

    /* Breadcrumb mobile styling */
    .page-title-box {
        padding-bottom: 15px !important;
    }

    .page-title-box h4 {
        font-size: 16px !important;
    }

    .page-title-box .breadcrumb {
        font-size: 12px;
    }

    .page-title-box .breadcrumb-item + .breadcrumb-item::before {
        font-size: 10px;
    }
}

/* Card animations for mobile */
@media (max-width: 767.98px) {
    .card {
        border-radius: 8px;
        margin-bottom: 16px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .card-body {
        padding: 16px;
    }

    .card-header {
        padding: 12px 16px;
    }

    .card-title {
        font-size: 15px !important;
    }
}

/* Footer mobile styling */
@media (max-width: 991.98px) {
    .footer {
        left: 0 !important;
        padding: 15px 12px;
    }

    .footer .text-sm-end {
        text-align: center !important;
        margin-top: 8px;
    }
}

/* Global mobile button styling */
@media (max-width: 767.98px) {
    .btn {
        padding: 8px 16px;
        font-size: 14px;
    }

    .btn-sm {
        padding: 6px 12px;
        font-size: 13px;
    }

    .btn-lg {
        padding: 12px 24px;
        font-size: 16px;
    }
}

/* Mobile form controls */
@media (max-width: 767.98px) {
    .form-control,
    .form-select {
        font-size: 16px !important; /* Prevents iOS zoom on focus */
        padding: 10px 14px;
        min-height: 44px;
    }

    .form-label {
        font-size: 14px;
        margin-bottom: 6px;
    }
}

/* Table mobile responsiveness (global) */
@media (max-width: 767.98px) {
    .table-responsive {
        border-radius: 8px;
        overflow: hidden;
    }

    .table thead th {
        font-size: 12px;
        padding: 10px 8px;
        white-space: nowrap;
    }

    .table tbody td {
        font-size: 13px;
        padding: 10px 8px;
    }
}

/* Alert mobile styling */
@media (max-width: 767.98px) {
    .alert {
        padding: 12px 16px;
        font-size: 14px;
        border-radius: 8px;
    }

    .alert .btn-close {
        padding: 16px;
    }
}

/* Modal mobile styling */
@media (max-width: 575.98px) {
    .modal-dialog {
        margin: 8px !important;
        max-width: calc(100% - 16px);
    }

    .modal-content {
        border-radius: 12px;
    }

    .modal-header {
        padding: 16px;
    }

    .modal-body {
        padding: 16px;
    }

    .modal-footer {
        padding: 12px 16px;
        flex-wrap: wrap;
        gap: 8px;
    }

    .modal-footer .btn {
        flex: 1;
        min-width: 100px;
    }
}

/* DataTables mobile enhancements */
@media (max-width: 767.98px) {
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
        text-align: center !important;
        margin-bottom: 12px;
    }

    .dataTables_wrapper .dataTables_length select {
        min-width: 70px;
    }

    .dataTables_wrapper .dataTables_filter input {
        min-width: 200px;
    }

    .dataTables_wrapper .dataTables_paginate {
        margin-top: 16px;
        text-align: center !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 8px 14px !important;
        margin: 2px;
    }

    .dataTables_wrapper .dataTables_info {
        text-align: center !important;
        padding-top: 12px !important;
        font-size: 13px;
    }
}

/* Toastr mobile positioning */
@media (max-width: 575.98px) {
    #toast-container {
        top: auto !important;
        bottom: 12px !important;
        left: 12px !important;
        right: 12px !important;
    }

    #toast-container > div {
        width: 100% !important;
        margin-bottom: 8px;
    }
}

/* Loading spinner animation */
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.bx-spin,
.bx-loader-alt {
    animation: spin 1s linear infinite;
}

/* Smooth page transitions */
.page-content {
    animation: fadeInUp 0.3s ease forwards;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: none;
    }
}
</style>