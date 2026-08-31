{{-- ===================== GROWTH STAGES =====================
     What the plant is doing, not just how old it is. Read from each lot's
     crop and its own day 0, which is why a lot with no crop or no anchor is
     named rather than guessed at.

     This was a modal behind the Activities toolbar. In the client's app it is
     a module of its own, so it is a tab of its own here. --}}
<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <h5 class="text-dark mb-1">Growth Stages</h5>
        <small class="text-secondary" id="growthStageWhen">every lot</small>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <label class="form-label mb-0 text-secondary" for="growthStageDate" style="font-size:12.5px;">Read the farm on</label>
        <input type="date" class="form-control form-control-sm" id="growthStageDate" style="max-width:190px;">
        <button type="button" class="btn btn-light btn-sm" id="growthStageToday">Today</button>
    </div>
</div>

<div id="growthStageBody"></div>

<small class="text-secondary d-block mt-3">
    Common field guidance, not a prescription — the same note the farmer sees. Stages are read
    from each lot's crop and its own day 0, so a lot with no crop or no anchor is named rather
    than guessed at.
</small>
