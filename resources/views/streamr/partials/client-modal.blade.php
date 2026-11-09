{{-- Streamr > Clients: one client's details (filled by the page's script from /streamr-clients-show). --}}
<div class="modal fade st-modal" id="stClient" tabindex="-1" aria-labelledby="stClientTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <div class="min-w-0">
                    <h5 class="modal-title text-dark" id="stClientTitle">Client</h5>
                    <div class="small text-secondary" id="stClientSub"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="stClientBody"><div class="skel" style="height: 12rem"></div></div>
            <div class="modal-footer justify-content-between">
                <div class="btn-group dropup">
                    <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" id="stActionsMenu">
                        <i class="bx bx-slider-alt me-1"></i>Actions
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="stActionsMenu">
                        <li><h6 class="dropdown-header">Subscription</h6></li>
                        <li><button type="button" class="dropdown-item" data-action="extend_days">Extend days</button></li>
                        <li><button type="button" class="dropdown-item" data-action="set_expiry">Change end date</button></li>
                        <li><button type="button" class="dropdown-item" data-action="change_plan">Change plan</button></li>
                        <li><button type="button" class="dropdown-item" data-action="cancel_queued">Cancel upcoming period</button></li>
                        <li><button type="button" class="dropdown-item" data-action="suspend_subscription">Suspend subscription</button></li>
                        <li><button type="button" class="dropdown-item" data-action="unsuspend_subscription">Unsuspend subscription</button></li>
                        <li><button type="button" class="dropdown-item text-danger" data-action="revoke_subscription">End plan now</button></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header">Credits &amp; account</h6></li>
                        <li><button type="button" class="dropdown-item" data-action="adjust_credits">Adjust credits</button></li>
                        <li><button type="button" class="dropdown-item" data-action="update_profile">Edit client details</button></li>
                        <li><button type="button" class="dropdown-item" data-action="change_email">Change login email</button></li>
                        <li><button type="button" class="dropdown-item" data-action="send_login_link">Send login link</button></li>
                        <li><button type="button" class="dropdown-item" data-action="add_note">Add note</button></li>
                        <li><button type="button" class="dropdown-item text-danger" data-action="suspend_account">Suspend account</button></li>
                        <li><button type="button" class="dropdown-item" data-action="restore_account">Restore account</button></li>
                    </ul>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-light" id="stRefresh"><i class="bx bx-refresh me-1"></i>Refresh from Streamr</button>
                    <a href="#" class="btn btn-outline-primary" id="stOpenStreamr" target="_blank" rel="noopener" hidden><i class="bx bx-link-external me-1"></i>Open in Streamr</a>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
