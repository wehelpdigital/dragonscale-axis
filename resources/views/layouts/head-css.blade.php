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
</style>

<!-- App js -->
<script src="{{ URL::asset('build/js/plugin.js') }}"></script>