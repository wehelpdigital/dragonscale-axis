{{-- ===================== REPORTS =====================
     The client's Reports module holds two: the labor report, which this page
     has had its own screen for since the beginning, and the post-harvest
     report — a saved copy of what the season yielded against what it cost.
     Those saved copies are listed here as they were saved. --}}
<style>
    .rp-links { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: .6rem; margin-bottom: 1.2rem; }
    .rp-link { border: 1px solid #e6e8ec; border-radius: 10px; padding: .8rem .95rem; background: #fff; text-align: left; }
    .rp-link:hover { border-color: #c7d2fe; background: #fbfcff; }
    .rp-link b { display: block; color: #343a40; font-size: 13.5px; }
    .rp-link span { font-size: 11.5px; color: #98a4b6; }

    .rp-card { border: 1px solid #e6e8ec; border-radius: 10px; padding: .8rem .95rem; margin-bottom: .6rem; }
    .rp-title { font-weight: 600; color: #343a40; font-size: 13.5px; }
    .rp-meta { font-size: 11.5px; color: #98a4b6; margin-top: .12rem; }
    .rp-figs { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .5rem; }
    .rp-fig {
        display: inline-flex; align-items: center; gap: .25rem; font-size: 11px; font-weight: 600;
        background: #eef1f6; color: #556ee6; border-radius: 999px; padding: .1rem .55rem;
    }
    .rp-fig.is-good { background: #e9f7ef; color: #0f8a5f; }
    .rp-fig.is-bad { background: #fdeceb; color: #c0392b; }
    .rp-empty { text-align: center; padding: 2.5rem 1rem; color: #98a4b6; }
    .rp-empty i { font-size: 2.2rem; display: block; margin-bottom: .4rem; }
</style>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <h5 class="text-dark mb-1">Reports</h5>
        <small class="text-secondary">
            What this season cost and what it returned. The two screens below are computed from
            the plan as it stands; the list under them is what the client saved.
        </small>
    </div>
    <button type="button" class="btn btn-light btn-sm" id="rpReload"><i class="bx bx-refresh"></i> Refresh</button>
</div>

<div class="rp-links">
    <button type="button" class="rp-link" id="rpLaborBtn">
        <b><i class="bx bx-money me-1"></i> Labor report</b>
        <span>Worker days and labor cost across the whole plan.</span>
    </button>
    <a class="rp-link" href="{{ route('anisenso-schedule-manager.reports', ['scheduleId' => $schedule->id]) }}" target="_blank">
        <b><i class="bx bx-bar-chart-square me-1"></i> Plan costing</b>
        <span>Materials, services and labour against the generated calendar.</span>
    </a>
</div>

<h6 class="text-dark mb-2"><i class="bx bx-save me-1"></i>Post-harvest reports the client saved</h6>
<div id="rpBody"></div>
