{{-- Streamr > Payment reviews: one order (filled by the page's script from /streamr-payments-one).
     The full-size receipt overlay lives inside the modal: .page-content is its own stacking
     context, so an overlay outside the modal would sit beneath it. --}}
<div class="modal fade st-modal" id="stReview" tabindex="-1" aria-labelledby="stReviewTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <div class="min-w-0">
                    <h5 class="modal-title text-dark" id="stReviewTitle">Order</h5>
                    <div class="small text-secondary" id="stReviewSub"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="stReviewBody"><div class="skel" style="height: 12rem"></div></div>
            <div class="modal-footer" id="stReviewFoot"></div>
        </div>
    </div>
    <div class="st-zoom" id="stZoom" role="button" aria-label="Close the full-size receipt"><img alt="The receipt, full size"></div>
</div>
