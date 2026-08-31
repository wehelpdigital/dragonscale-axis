{{-- ===================== WEATHER =====================
     Per lot, because a farm is not a point: two fields an hour apart get
     different rain. Identical addresses are resolved once.

     This was a modal behind the Activities toolbar. In the client's app it is
     a module of its own, so it is a tab of its own here. --}}
<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <h5 class="text-dark mb-1">Weather</h5>
        <small class="text-secondary">Six days over each lot's own location, from the address on the lot.</small>
    </div>
    <button type="button" class="btn btn-light btn-sm" id="scheduleWeatherReload"><i class="bx bx-refresh"></i> Refresh</button>
</div>

<div id="scheduleWeatherBody"></div>
