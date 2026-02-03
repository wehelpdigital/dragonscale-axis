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

/* Fix: Override excessive min-height when sidebar is collapsed */
body[data-sidebar=dark].vertical-collpsed {
    min-height: auto !important;
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
</style>