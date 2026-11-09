{{-- The Streamr pages' shared styles (a copy of games/salpakan/_styles, scoped under #st). $tables: the page has DataTables. --}}
@if (! empty($tables))
<link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
@endif
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    /* Scoped under #st (and the modals, which vendor-scripts moves to <body>): this page's styles load before Bootstrap's.
       Colours come from Bootstrap's variables, so the admin's dark mode dresses it too. */
    #st .stat { border-radius: .9rem; padding: .9rem 1rem; background: var(--bs-tertiary-bg); height: 100%; display: flex; gap: .8rem; align-items: center; }
    #st .stat i { font-size: 1.6rem; color: var(--bs-primary); }
    #st .stat b { display: block; font-size: 1.3rem; line-height: 1.2; color: var(--bs-emphasis-color); word-break: break-word; }
    #st .stat span { font-size: .8rem; color: var(--bs-secondary-color); }
    #st .stat a { color: inherit; }
    /* Six KPIs in a row: smaller figures that never break inside a number. */
    #st .stat.kpi { min-width: 0; }
    #st .stat.kpi > div { min-width: 0; }
    #st .stat.kpi b { font-size: 1.05rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; word-break: normal; }
    #st .stat.kpi b small { display: block; font-size: .85rem; font-weight: 600; color: var(--bs-secondary-color); }
    #st .filters .form-label { font-size: .75rem; margin-bottom: .2rem; color: var(--bs-secondary-color); }
    #st table.dataTable td, #st .table td { vertical-align: middle; }
    .st-sub, #st .sub { font-size: .76rem; color: var(--bs-secondary-color); }
    .st-mono, #st .mono { font-family: SFMono-Regular, Menlo, Consolas, monospace; font-size: .78rem; }
    #st .chips, .st-modal .chips { display: flex; flex-wrap: wrap; gap: .4rem; }
    #st .chip { border: 1px solid var(--bs-border-color); background: var(--bs-body-bg); color: var(--bs-body-color); border-radius: 999px;
        padding: .3rem .8rem; font-size: .85rem; font-weight: 500; display: inline-flex; align-items: center; gap: .4rem; cursor: pointer;
        transition: background .25s ease, color .25s ease, border-color .25s ease; }
    #st .chip:hover { border-color: var(--bs-primary); }
    #st .chip.is-on { background: var(--bs-primary); border-color: var(--bs-primary); color: #fff; }
    #st .chip .n { font-size: .72rem; padding: 0 .45rem; border-radius: 999px; background: var(--bs-secondary-bg); color: var(--bs-body-color); }
    #st .chip.is-on .n { background: rgb(255 255 255 / .25); color: #fff; }
    #st .chip.is-hot .n { background: var(--bs-danger); color: #fff; }
    #st .bar { display: flex; flex-wrap: wrap; gap: .75rem; align-items: center; margin-bottom: 1rem; }
    #st .bar .search { flex: 1 1 14rem; max-width: 24rem; margin-left: auto; }
    #st .empty { text-align: center; color: var(--bs-secondary-color); padding: 2.5rem 1rem; border: 1px dashed var(--bs-border-color); border-radius: .75rem; }
    #st .skel, .st-modal .skel { height: 3.6rem; border-radius: .75rem; margin-bottom: .5rem;
        background: linear-gradient(90deg, var(--bs-secondary-bg) 0%, var(--bs-tertiary-bg) 50%, var(--bs-secondary-bg) 100%);
        background-size: 200% 100%; animation: stShim 1.2s linear infinite; }
    @keyframes stShim { to { background-position: -200% 0; } }
    #st .ok, .st-modal .ok { color: var(--bs-success-text-emphasis); }
    #st .bad, .st-modal .bad { color: var(--bs-danger-text-emphasis); }
    #st .kv dt, .st-modal .kv dt { font-size: .72rem; font-weight: 600; color: var(--bs-secondary-color); text-transform: uppercase; letter-spacing: .03em; }
    #st .kv dd, .st-modal .kv dd { color: var(--bs-emphasis-color); margin-bottom: .6rem; word-break: break-word; }
    #st .table-sm td, #st .table-sm th, .st-modal .table-sm td, .st-modal .table-sm th { font-size: .82rem; }
    #st tr.is-clickable { cursor: pointer; }
    #st .chart-box { min-height: 320px; }
    .st-modal .sec { margin-top: 1.1rem; }
    .st-modal .sec h6 { font-size: .74rem; text-transform: uppercase; letter-spacing: .06em; color: var(--bs-secondary-color); margin-bottom: .5rem; }
    .st-modal .facts { display: grid; grid-template-columns: repeat(auto-fill, minmax(10rem, 1fr)); gap: .5rem 1rem; }
    .st-modal .facts div { min-width: 0; }
    .st-modal .facts span { display: block; font-size: .74rem; color: var(--bs-secondary-color); }
    .st-modal .facts b { font-weight: 600; word-break: break-word; color: var(--bs-emphasis-color); }
    .st-modal .proof img { max-width: 100%; max-height: 26rem; border-radius: .6rem; border: 1px solid var(--bs-border-color); cursor: zoom-in; display: block; }
    .st-modal .proof iframe { width: 100%; height: 26rem; border: 1px solid var(--bs-border-color); border-radius: .6rem; }
    .st-modal .verdict { display: block; padding: .6rem .8rem; border-radius: .6rem; }
    .st-modal .verdict.pass { background: var(--bs-success-bg-subtle); color: var(--bs-success-text-emphasis); }
    .st-modal .verdict.fail { background: var(--bs-danger-bg-subtle); color: var(--bs-danger-text-emphasis); }
    .st-modal .verdict.unsure, .st-modal .verdict.error, .st-modal .verdict.running { background: var(--bs-warning-bg-subtle); color: var(--bs-warning-text-emphasis); }
    .st-modal .verdict.skipped { background: var(--bs-secondary-bg); color: var(--bs-body-color); }
    .st-modal .checks { margin-top: .5rem; display: grid; gap: .35rem; }
    .st-modal .check { display: flex; gap: .5rem; align-items: flex-start; }
    .st-modal .check i { flex: none; width: 1.3rem; height: 1.3rem; border-radius: 999px; display: grid; place-items: center; font-style: normal; font-size: .75rem; font-weight: 700; margin-top: .1rem; }
    .st-modal .check i.y { background: var(--bs-success-bg-subtle); color: var(--bs-success-text-emphasis); }
    .st-modal .check i.n { background: var(--bs-danger-bg-subtle); color: var(--bs-danger-text-emphasis); }
    .st-modal .check i.q { background: var(--bs-warning-bg-subtle); color: var(--bs-warning-text-emphasis); }
    .st-modal .check small { display: block; color: var(--bs-secondary-color); }
    .st-modal .timeline { display: grid; gap: .45rem; border-left: 2px solid var(--bs-border-color); padding-left: .9rem; }
    .st-modal .timeline div span { display: block; font-size: .74rem; color: var(--bs-secondary-color); }
    .st-modal pre.notes { white-space: pre-wrap; word-break: break-word; font-size: .78rem; background: var(--bs-tertiary-bg); color: var(--bs-body-color);
        border-radius: .5rem; padding: .6rem .75rem; margin: .4rem 0 0; max-height: 14rem; overflow: auto; }
    .st-modal .modal-footer { flex-wrap: wrap; gap: .5rem; }
    .st-zoom { position: fixed; inset: 0; z-index: 1090; background: rgb(0 0 0 / .85); display: grid; place-items: center; padding: 1rem; opacity: 0;
        pointer-events: none; transition: opacity .25s ease; cursor: zoom-out; }
    .st-zoom.is-on { opacity: 1; pointer-events: auto; }
    .st-zoom img { max-width: 100%; max-height: 100%; border-radius: .5rem; }
    #st .qr-preview { width: 8rem; aspect-ratio: 1; border-radius: .6rem; border: 1px solid var(--bs-border-color); object-fit: contain; background: #fff; padding: .25rem; }
    /* Support thread */
    #st .thread { max-width: 780px; margin: 0 auto; }
    #st .msg-row { display: flex; margin-bottom: .75rem; }
    #st .msg-row.admin { justify-content: flex-end; }
    #st .bubble { max-width: 82%; border-radius: 1rem; padding: .7rem 1rem; }
    #st .bubble .meta { font-size: .72rem; margin-bottom: .25rem; font-weight: 600; }
    #st .bubble .text { word-break: break-word; }
    #st .bubble.admin { background: #556ee6; color: #fff; }
    #st .bubble.admin .meta, #st .bubble.admin a { color: #e4e8fc; }
    #st .bubble.client { background: #f3f4f6; color: #1f2937; border: 1px solid #e5e7eb; }
    #st .bubble.client .meta { color: #6b7280; }
    #st .bubble.internal { background: #fff7d6; color: #5c4400; border: 1px solid #f1d27a; }
    #st .bubble.internal .meta { color: #7a5b00; }
    #st .status-line { text-align: center; font-size: .75rem; color: var(--bs-secondary-color); margin: .4rem 0 .9rem; }
    @media (max-width: 575.98px) {
        #st .bar .search { max-width: none; margin-left: 0; }
        #st .bubble { max-width: 94%; }
    }
    @media (prefers-reduced-motion: reduce) {
        #st .chip, .st-zoom { transition: none; }
        #st .skel, .st-modal .skel { animation: none; }
    }
</style>
@include('aniSensoAdmin.partials.dark')
<style>
    /* The support bubbles are written in hex: give them their dark answer here. */
    [data-bs-theme="dark"] #st .bubble.client { background: #2a3042; color: #e3e6ef; border-color: #3a4156; }
    [data-bs-theme="dark"] #st .bubble.client .meta { color: #aab2c8; }
    [data-bs-theme="dark"] #st .bubble.internal { background: #4a3c0d; color: #fbe9b0; border-color: #6d5815; }
    [data-bs-theme="dark"] #st .bubble.internal .meta { color: #f2d77c; }
    [data-bs-theme="dark"] #st .qr-preview { background: #fff; }
</style>
