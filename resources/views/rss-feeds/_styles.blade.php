{{-- The RSS Feeds pages' own styles (Feeds and Sources). --}}
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    /* Scoped under #rf: this page's styles load before Bootstrap's. */
    #rf .stat { border-radius: .9rem; padding: .9rem 1rem; background: var(--bs-tertiary-bg); height: 100%; }
    #rf .stat b { display: block; font-size: 1.35rem; color: var(--bs-emphasis-color); }
    #rf .stat span { font-size: .8rem; color: var(--bs-secondary-color); }
    #rf .addr { display: flex; gap: .35rem; align-items: center; min-width: 16rem; }
    #rf .addr code { flex: 1; min-width: 0; padding: .4rem .6rem; border-radius: .5rem; background: var(--bs-tertiary-bg); color: var(--bs-emphasis-color);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: .78rem; }
    #rf .target { font-size: .76rem; color: var(--bs-secondary-color); word-break: break-all; max-width: 26rem; }
    #rf .chip { display: inline-block; padding: .1rem .5rem; margin: .1rem .15rem .1rem 0; border-radius: 999px; font-size: .7rem;
        background: var(--bs-secondary-bg); color: var(--bs-emphasis-color); }
    #rf .kw { font-size: .74rem; color: var(--bs-secondary-color); max-width: 16rem; }
    #rf .kw b { color: var(--bs-emphasis-color); font-weight: 600; }
    #rf .ok { color: var(--bs-success-text-emphasis); }
    #rf .bad { color: var(--bs-danger-text-emphasis); }
    #rf tr.is-off td { opacity: .55; }
    #rf .form-switch .form-check-input { cursor: pointer; }
    #rf .actions { white-space: nowrap; }
    #rf tr.rf-parent td { background: var(--bs-tertiary-bg); }
    #rf tr.rf-section td:first-child { position: relative; padding-left: 2.4rem; }
    #rf tr.rf-section td:first-child::before { content: ''; position: absolute; left: 1.1rem; top: 0; bottom: 0; border-left: 2px solid var(--bs-primary-border-subtle); }
    #rf .rf-section-label { font-size: .68rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--bs-primary); }
    #rf tr.rf-group td { font-size: .72rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--bs-secondary-color); padding-top: 1.1rem; }
    #rf .rf-new-menu { min-width: 19rem; }
    #rf .rf-new-menu .dropdown-item { white-space: normal; padding-top: .5rem; padding-bottom: .5rem; }
    /* A feed's sections fold away: each cell's content is a grid row that goes from 1fr to 0fr
       (a table row itself cannot animate its height), while the cell's padding and border close
       with it. Rows go one after another (--i), so the list unrolls rather than jumps. */
    #rf .rf-cell { display: grid; grid-template-rows: 1fr; opacity: 1;
        transition: grid-template-rows .35s cubic-bezier(.4, 0, .2, 1), opacity .25s ease; transition-delay: calc((var(--i, 1) - 1) * 35ms); }
    #rf .rf-cell-in { min-height: 0; overflow: hidden; }
    #rf tr.rf-section td { transition: padding .35s cubic-bezier(.4, 0, .2, 1), border-bottom-width .35s ease; transition-delay: calc((var(--i, 1) - 1) * 35ms); }
    #rf tr.rf-section.is-collapsed td { padding-top: 0; padding-bottom: 0; border-bottom-width: 0; }
    #rf tr.rf-section.is-collapsed .rf-cell { grid-template-rows: 0fr; opacity: 0; }
    #rf tr.no-anim td, #rf tr.no-anim .rf-cell { transition: none !important; }
    #rf .rf-sections-toggle i { display: inline-block; transition: transform .35s cubic-bezier(.4, 0, .2, 1); }
    #rf .rf-sections-toggle[aria-expanded="false"] i { transform: rotate(-90deg); }
    @media (prefers-reduced-motion: reduce) {
        #rf .rf-cell, #rf tr.rf-section td, #rf .rf-sections-toggle i { transition: none !important; }
    }
    .rf-type-help { font-size: .78rem; }
    .rf-test { max-height: 22rem; overflow: auto; border: 1px solid var(--bs-border-color); border-radius: .6rem; }
    .rf-test .rf-row { display: flex; gap: .6rem; padding: .55rem .7rem; border-bottom: 1px solid var(--bs-border-color); }
    .rf-test .rf-row:last-child { border-bottom: 0; }
    .rf-test .rf-row.is-out { opacity: .5; }
    .rf-test img { width: 56px; height: 42px; object-fit: cover; border-radius: .35rem; flex: none; background: var(--bs-tertiary-bg); }
    .rf-test .rf-sum { white-space: pre-line; }
    .rf-sources { max-height: 16rem; overflow: auto; border: 1px solid var(--bs-border-color); border-radius: .6rem; padding: .4rem .7rem; }
</style>
@include('aniSensoAdmin.partials.dark')
