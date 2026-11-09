{{-- Streamr > Clients: the action form. Only the fields of the chosen action show; Streamr checks everything again. --}}
<div class="modal fade st-modal" id="stAction" tabindex="-1" aria-labelledby="stActionTitle" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <form class="modal-content" id="stActionForm" novalidate>
            <div class="modal-header">
                <h5 class="modal-title text-dark" id="stActionTitle">Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-dark mb-3" id="stActionConfirm"></p>

                {{-- Extend days --}}
                <div class="mb-3" data-fields="extend_days" hidden>
                    <label class="form-label text-dark" for="stFieldDays">Days to add</label>
                    <input type="number" class="form-control" id="stFieldDays" name="days" min="1" max="3650" step="1" value="30">
                </div>

                {{-- Change end date --}}
                <div class="mb-3" data-fields="set_expiry" hidden id="stExpiryForm">
                    <label class="form-label text-dark" for="stFieldExpiresAt">New end date and time (Philippine time)</label>
                    <input type="datetime-local" class="form-control" id="stFieldExpiresAt" name="expires_at">
                    <div class="form-text">At least an hour from now and within 10 years. Upcoming periods move to follow it.</div>
                </div>

                {{-- Cancel upcoming period --}}
                <div class="mb-3" data-fields="cancel_queued" hidden id="stQueuedForm">
                    <label class="form-label text-dark d-block">Upcoming period to cancel</label>
                    <div id="stQueuedList" class="d-grid gap-1"></div>
                    <div class="form-text">Later periods move up.</div>
                </div>

                {{-- Change plan --}}
                <div class="mb-3" data-fields="change_plan" hidden>
                    <label class="form-label text-dark" for="stFieldPlan">New plan</label>
                    <select class="form-select" id="stFieldPlan" name="plan_key">
                        @foreach (\App\Models\StreamrClient::PLANS as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="stFieldKeepExpiry" name="keep_expiry" value="1">
                        <label class="form-check-label text-dark" for="stFieldKeepExpiry">Keep the current end date</label>
                    </div>
                </div>

                {{-- Adjust credits --}}
                <div class="row g-2 mb-3" data-fields="adjust_credits" hidden>
                    <div class="col-7">
                        <label class="form-label text-dark" for="stFieldDelta">Credits (+ to add, − to take)</label>
                        <input type="number" class="form-control" id="stFieldDelta" name="delta" step="1" min="-10000000" max="10000000">
                    </div>
                    <div class="col-5">
                        <label class="form-label text-dark" for="stFieldBucket">Bucket</label>
                        <select class="form-select" id="stFieldBucket" name="bucket">
                            <option value="purchased">Purchased</option>
                            <option value="plan">Plan</option>
                        </select>
                    </div>
                </div>

                {{-- Edit client details --}}
                <div class="row g-2 mb-3" data-fields="update_profile" hidden id="stProfileForm">
                    <div class="col-sm-6">
                        <label class="form-label text-dark" for="stFieldFirstName">First name</label>
                        <input type="text" class="form-control" id="stFieldFirstName" name="first_name" maxlength="60">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label text-dark" for="stFieldLastName">Last name</label>
                        <input type="text" class="form-control" id="stFieldLastName" name="last_name" maxlength="60">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label text-dark" for="stFieldPhone">Mobile</label>
                        <input type="tel" class="form-control" id="stFieldPhone" name="phone" maxlength="13" placeholder="09XXXXXXXXX" pattern="^(09|\+639)\d{9}$">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label text-dark" for="stFieldStoreName">Store name</label>
                        <input type="text" class="form-control" id="stFieldStoreName" name="store_name" minlength="2" maxlength="80">
                        <div class="form-text">The store's address (handle) never changes.</div>
                    </div>
                </div>

                {{-- Change login email --}}
                <div class="mb-3" data-fields="change_email" hidden>
                    <label class="form-label text-dark" for="stFieldEmail">New login email</label>
                    <input type="email" class="form-control" id="stFieldEmail" name="email" maxlength="191">
                    <div class="form-text">Streamr tells both addresses.</div>
                </div>

                {{-- Add note --}}
                <div class="mb-3" data-fields="add_note" hidden>
                    <label class="form-label text-dark" for="stFieldBody">Note</label>
                    <textarea class="form-control" id="stFieldBody" name="body" rows="4" maxlength="2000"></textarea>
                </div>

                {{-- The reason, for the actions that keep one --}}
                <div class="mb-1" data-fields="suspend_subscription revoke_subscription extend_days set_expiry cancel_queued change_plan adjust_credits suspend_account" hidden>
                    <label class="form-label text-dark" for="stFieldReason">Reason</label>
                    <textarea class="form-control" id="stFieldReason" name="reason" rows="2" maxlength="500" placeholder="Kept with the change in Streamr"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="stActionSubmit">Send to Streamr</button>
            </div>
        </form>
    </div>
</div>
