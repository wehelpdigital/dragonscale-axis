<div class="mb-4">
    <h5 class="text-dark mb-1">Schedule Settings</h5>
    <small class="text-secondary">These settings are reused by every calendar generation. You can still override them at generation time.</small>
</div>

<div class="card border mb-3">
    <div class="card-body">
        <h6 class="text-dark mb-3"><i class="bx bx-info-circle me-1"></i>Basic Info</h6>

        <div class="mb-3">
            <label class="form-label text-dark">Title <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="settingsTitle" value="{{ $schedule->title }}">
        </div>

        <div class="mb-3">
            <label class="form-label text-dark">Description</label>
            <textarea class="form-control" id="settingsDescription" rows="3">{{ $schedule->description }}</textarea>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label text-dark">Day Type <span class="text-danger">*</span></label>
                <select class="form-select" id="settingsDayType">
                    <option value="DAP" @selected($schedule->dayType === 'DAP')>DAP — Days After Planting</option>
                    <option value="DAS" @selected($schedule->dayType === 'DAS')>DAS — Days After Seeding</option>
                    <option value="DAT" @selected($schedule->dayType === 'DAT')>DAT — Days After Transplanting</option>
                </select>
                <small class="text-secondary">Applies to all activities and irrigations under this schedule.</small>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label text-dark">Default Stagger Days</label>
                <input type="number" min="0" step="1" class="form-control" id="settingsDefaultStaggerDays" value="{{ $schedule->defaultStaggerDays ?? 0 }}">
                <small class="text-secondary">Pre-fills new groups during calendar generation. 0 = no stagger.</small>
            </div>
        </div>

        <div class="text-end">
            <button type="button" class="btn btn-primary btn-sm" id="saveSettingsBasicBtn">
                <i class="bx bx-save me-1"></i> Save Basic Info
            </button>
        </div>
    </div>
</div>

<div class="card border">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
            <div>
                <h6 class="text-dark mb-1"><i class="bx bx-collection me-1"></i>Default Groupings</h6>
                <small class="text-secondary">Pre-define lot groups so they auto-fill when you generate. Example: <em>Group 1 = Lot 1 + Lot 2</em>.</small>
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm" id="addDefaultGroupBtn">
                <i class="bx bx-plus me-1"></i> Add Group
            </button>
        </div>

        <div id="defaultGroupsContainer" class="mt-3"></div>

        @if($schedule->lots->count() === 0)
            <div class="alert alert-warning mb-0 mt-3">
                <i class="bx bx-info-circle me-1"></i> Add at least one lot first (in the <strong>Lots</strong> tab) before defining groupings.
            </div>
        @endif

        <div class="text-end mt-3">
            <button type="button" class="btn btn-primary btn-sm" id="saveDefaultGroupingsBtn" @if($schedule->lots->count() === 0) disabled @endif>
                <i class="bx bx-save me-1"></i> Save Default Groupings
            </button>
        </div>
    </div>
</div>
