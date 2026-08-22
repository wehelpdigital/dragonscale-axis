{{-- One picture, big. Opened from any thumbnail on these screens. --}}
<div class="modal fade" id="mediaViewer" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mediaViewerTitle">Photo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body media-viewer" id="mediaViewerBody"></div>
            <div class="modal-footer">
                <a href="#" target="_blank" class="btn btn-soft-primary" id="mediaViewerOpen">Open the file</a>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<script>
/* A file that is not there says so.
 * These paths point at two different disks and either can lose a file -- the
 * app's own storage is rebuilt on deploy -- so a row can outlive its picture.
 * A broken-image glyph reads as a broken SCREEN; this reads as a missing
 * file, which is what it is. Capture phase, because an image's error event
 * does not bubble. */
document.addEventListener('error', function (e) {
    const el = e.target;
    if (!el || el.tagName !== 'IMG' || !el.classList.contains('media-thumb')) return;
    const gone = document.createElement('span');
    gone.className = 'media-gone';
    gone.title = 'The file is not on the disk any more';
    gone.innerHTML = '<i class="bx bx-image-alt"></i>';
    el.replaceWith(gone);
}, true);

$(function () {
    $(document).on('click', '.js-open', function () {
        const url = $(this).data('url');
        const caption = $(this).data('caption') || '';
        $('#mediaViewerTitle').text(caption || 'Photo');
        $('#mediaViewerOpen').attr('href', url);
        $('#mediaViewerBody').html('<img src="' + url + '" alt="">' +
            (caption ? '<div class="media-caption">' + $('<div>').text(caption).html() + '</div>' : ''));
        $('#mediaViewer').modal('show');
    });
});
</script>
