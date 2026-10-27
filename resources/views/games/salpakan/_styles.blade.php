{{-- The Games > Salpakan Tactics! pages' shared styles. $tables: the page has DataTables. --}}
@if (! empty($tables))
<link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
@endif
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    /* Scoped under #gs: this page's styles load before Bootstrap's. */
    #gs .stat { border-radius: .9rem; padding: .9rem 1rem; background: var(--bs-tertiary-bg); height: 100%; display: flex; gap: .8rem; align-items: center; }
    #gs .stat i { font-size: 1.6rem; color: var(--bs-primary); }
    #gs .stat b { display: block; font-size: 1.35rem; line-height: 1.2; color: var(--bs-emphasis-color); }
    #gs .stat span { font-size: .8rem; color: var(--bs-secondary-color); }
    #gs .filters .form-label { font-size: .75rem; margin-bottom: .2rem; color: var(--bs-secondary-color); }
    #gs table.dataTable td, #gs .table td { vertical-align: middle; }
    #gs .sub { font-size: .76rem; color: var(--bs-secondary-color); }
    #gs .mono { font-family: SFMono-Regular, Menlo, Consolas, monospace; font-size: .76rem; }
    #gs .chip { display: inline-block; padding: .1rem .5rem; margin: .1rem .15rem .1rem 0; border-radius: 999px; font-size: .7rem;
        background: var(--bs-secondary-bg); color: var(--bs-emphasis-color); }
    #gs .ok { color: var(--bs-success-text-emphasis); }
    #gs .bad { color: var(--bs-danger-text-emphasis); }
    #gs .segment { cursor: pointer; border: 2px solid transparent; transition: border-color .15s ease; }
    #gs .segment.is-on { border-color: var(--bs-primary); }
    #gs .chart-box { min-height: 320px; }
    #gs .kv dt { font-size: .74rem; font-weight: 600; color: var(--bs-secondary-color); text-transform: uppercase; letter-spacing: .03em; }
    #gs .kv dd { color: var(--bs-emphasis-color); margin-bottom: .7rem; }
    #gs .table-sm td, #gs .table-sm th { font-size: .82rem; }
    #gs tr.is-clickable { cursor: pointer; }
</style>
@include('aniSensoAdmin.partials.dark')
