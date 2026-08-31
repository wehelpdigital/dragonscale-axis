{{-- ===================== SETTINGS =====================
     The same two things the client's own Settings module holds, and nothing
     else: what this schedule IS, and who hears about it each morning.

     Default Groupings used to live here. The client app has no such screen
     any more — a season is lots and activities, and a group was a fourth
     thing to keep in step with both — so the shelf that could still define
     them was the only place in the product they existed. Default Stagger
     Days went with it: it pre-filled a group's start offset, and the
     generator asks for that per group anyway. --}}
<div class="mb-4">
    <h5 class="text-dark mb-1">Schedule Settings</h5>
    <small class="text-secondary">What this season is called, how its days are counted, and who gets the morning email. Saved straight to the client's app.</small>
</div>

<div class="card border mb-3">
    <div class="card-body">
        <h6 class="text-dark mb-3"><i class="bx bx-info-circle me-1"></i>Basic Info</h6>

        <div class="mb-3">
            <label class="form-label text-dark">Title <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="settingsTitle" maxlength="255" value="{{ $schedule->title }}">
        </div>

        <div class="mb-3">
            <label class="form-label text-dark">Description</label>
            <textarea class="form-control" id="settingsDescription" rows="3" maxlength="5000">{{ $schedule->description }}</textarea>
        </div>

        {{-- The same four answers the client sees, worded the same way. A
             season set up as TREE keeps no day count at all, which is why the
             list is not simply DAP/DAS/DAT any more. --}}
        <div class="mb-3">
            <label class="form-label text-dark">How days are counted <span class="text-danger">*</span></label>
            <select class="form-select" id="settingsDayType">
                <option value="DAT" @selected(($schedule->dayType ?: 'DAS') === 'DAT')>DAS &rarr; DAT — sown, then transplanted</option>
                <option value="DAS" @selected(($schedule->dayType ?: 'DAS') === 'DAS')>DAS only — direct seeded (DSR)</option>
                <option value="DAP" @selected($schedule->dayType === 'DAP')>DAP — days after planting</option>
                <option value="TREE" @selected($schedule->dayType === 'TREE')>Mature trees — no day count, read by age</option>
            </select>
            <small class="text-secondary">The season's default. A lot can still be set differently in the <strong>Lots</strong> tab.</small>
        </div>

        <div class="text-end">
            <button type="button" class="btn btn-primary btn-sm" id="saveSettingsBasicBtn">
                <i class="bx bx-save me-1"></i> Save Basic Info
            </button>
        </div>
    </div>
</div>

{{-- Who hears about the season each morning. The client can set this from
     their own Settings module; this is the same three columns. --}}
<div class="card border">
    <div class="card-body">
        <h6 class="text-dark mb-1"><i class="bx bx-envelope me-1"></i>Daily schedule email</h6>
        <small class="text-secondary d-block mb-3">
            One message each morning with what is on today and what is coming tomorrow, so nobody
            has to open the app to find out where to be.
        </small>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="notifyWorkersDaily" @checked($schedule->notifyWorkersDaily)>
                    <label class="form-check-label text-dark fw-semibold" for="notifyWorkersDaily">Email the workers</label>
                </div>
                <small class="text-secondary d-block ps-4">
                    Each worker gets only the activities they are actually on. Anyone with no address on file is skipped.
                </small>
            </div>
            <div class="col-md-6">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="notifyOwnerDaily" @checked($schedule->notifyOwnerDaily)>
                    <label class="form-check-label text-dark fw-semibold" for="notifyOwnerDaily">Email the owner</label>
                </div>
                <small class="text-secondary d-block ps-4">
                    The whole day — every activity, and whoever is on it.
                </small>
            </div>
        </div>

        <div class="row g-3 mt-1 align-items-end">
            <div class="col-md-4">
                <label class="form-label text-dark mb-1" for="notifyHour">Send at</label>
                <select class="form-select" id="notifyHour">
                    @for ($h = 0; $h < 24; $h++)
                        <option value="{{ $h }}" @selected((int) $schedule->notifyHour === $h)>
                            {{ \Carbon\Carbon::createFromTime($h)->format('g:00 A') }}
                        </option>
                    @endfor
                </select>
                <small class="text-secondary">Philippine time. Once a day — a re-run never sends twice.</small>
            </div>
            <div class="col-md-8 text-end">
                <button type="button" class="btn btn-primary btn-sm" id="saveNotifyBtn">
                    <i class="bx bx-save me-1"></i> Save Notifications
                </button>
            </div>
        </div>
    </div>
</div>
