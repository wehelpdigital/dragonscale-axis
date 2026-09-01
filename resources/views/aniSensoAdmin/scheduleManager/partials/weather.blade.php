{{-- ===================== WEATHER =====================
     Per lot, because a farm is not a point: two fields an hour apart get
     different rain. Identical addresses are resolved once.

     This was a modal behind the Activities toolbar. In the client's app it is
     a module of its own, so it is a tab of its own here. --}}
<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <h5 class="text-dark mb-1">Weather</h5>
        <small class="text-secondary">Six days over each lot's own location, from the address on the lot. Tap a day for its hours.</small>
    </div>
    <button type="button" class="btn btn-light btn-sm" id="scheduleWeatherReload"><i class="bx bx-refresh"></i> Refresh</button>
</div>

<style>
    /* A day card is a button now, so it has to look like one can be pressed. */
    .wx-day { cursor: pointer; transition: border-color .12s ease, background .12s ease; }
    .wx-day:hover { border-color: #556ee6; }
    .wx-day.is-open { border-color: #556ee6; background: #eef1fe; }
    .wx-day-hint { font-size: 9.5px; color: #98a4b6; margin-top: .15rem; }

    /* The hours of the open day. A sideways rail, because twenty-four of
       anything down the page buries whatever is under it. */
    .wx-hours-wrap { border: 1px solid #e6e8ec; border-radius: 10px; padding: .6rem .7rem; margin-top: .5rem; background: #fbfcfe; }
    .wx-hours-say { font-size: 12px; color: #495057; margin-bottom: .45rem; }
    .wx-hours-say b { color: #2c3e8c; }
    .wx-hours { display: flex; gap: .4rem; overflow-x: auto; padding-bottom: .35rem; }
    .wx-hour {
        flex: 0 0 auto; min-width: 4.2rem; text-align: center;
        border: 1px solid #e6e8ec; border-radius: 8px; padding: .4rem .3rem; background: #fff;
    }
    .wx-hour.is-now { border-color: #556ee6; box-shadow: 0 0 0 1px #556ee6 inset; }
    .wx-hour.is-wet { background: #eef4ff; }
    .wx-hour-h { font-size: 10px; font-weight: 700; color: #74788d; }
    .wx-hour-e { font-size: 15px; line-height: 1.3; }
    .wx-hour-t { font-size: 12.5px; font-weight: 700; color: #343a40; }
    .wx-hour-p { font-size: 10px; color: #2c5bb5; font-weight: 600; }
    .wx-hour-w { font-size: 9.5px; color: #98a4b6; }
    .wx-legend { font-size: 10.5px; color: #98a4b6; margin: .35rem 0 0; }
</style>

<div id="scheduleWeatherBody"></div>
