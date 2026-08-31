{{-- ===================== GALLERY =====================
     The client's Gallery module: every picture the season produced, and the
     albums they put them into on purpose. Same two tables, read live — a
     caption fixed here is the caption they see. --}}
<style>
    .gl-albums { display: flex; flex-wrap: wrap; gap: .4rem; margin-bottom: 1rem; }
    .gl-albums .gl-chip {
        border: 1px solid #e6e8ec; border-radius: 999px; padding: .35rem .9rem;
        font-size: 12.5px; font-weight: 500; color: #495057; background: #fff;
        display: inline-flex; align-items: center; gap: .4rem; cursor: pointer;
    }
    .gl-albums .gl-chip:hover { background: #eef2ff; color: #2c3e8c; }
    .gl-albums .gl-chip.active { background: #556ee6; border-color: #556ee6; color: #fff; }
    .gl-albums .gl-chip .badge { font-size: 10.5px; font-weight: 600; background: #eef1f6; color: #495057; }
    .gl-albums .gl-chip.active .badge { background: rgba(255,255,255,.85); color: #2c3e8c; }

    .gl-tiles { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: .6rem; }
    .gl-tile { border: 1px solid #e6e8ec; border-radius: 10px; overflow: hidden; background: #fff; }
    .gl-tile img { width: 100%; height: 124px; object-fit: cover; display: block; cursor: zoom-in; }
    .gl-tile .gl-body { padding: .45rem .55rem; }
    .gl-tile .gl-cap { font-size: 12px; font-weight: 600; color: #343a40; }
    .gl-tile .gl-meta { font-size: 11px; color: #98a4b6; margin-top: .1rem; }
    .gl-gone {
        width: 100%; height: 124px; display: flex; align-items: center; justify-content: center;
        background: #f6f8fb; color: #c3cbd6; font-size: 1.6rem;
    }
    .gl-empty { text-align: center; padding: 2.5rem 1rem; color: #98a4b6; }
    .gl-empty i { font-size: 2.2rem; display: block; margin-bottom: .4rem; }
</style>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <h5 class="text-dark mb-1">Gallery</h5>
        <small class="text-secondary">
            Every picture this season produced, and the albums the client sorted them into.
            Captions, albums and deletions here are the ones they see.
        </small>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-light btn-sm" id="glReload"><i class="bx bx-refresh"></i> Refresh</button>
        <button type="button" class="btn btn-outline-primary btn-sm" id="glNewAlbum"><i class="bx bx-folder-plus me-1"></i> New album</button>
        <button type="button" class="btn btn-primary btn-sm" id="glAddBtn"><i class="bx bx-image-add me-1"></i> Add pictures</button>
        <input type="file" id="glFiles" accept="image/*" multiple class="d-none">
    </div>
</div>

<div class="gl-albums" id="glAlbums"></div>
<div id="glBody"></div>

{{-- One picture: its caption, its words, and which album it belongs to. --}}
<div class="modal fade" id="glModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title mb-0" id="glModalTitle">Picture</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="glId">
                <div class="text-center mb-3" id="glPreview"></div>
                <div class="mb-3">
                    <label class="form-label text-dark">Caption</label>
                    <input type="text" class="form-control" id="glCaption" maxlength="191">
                </div>
                <div class="mb-3">
                    <label class="form-label text-dark">Words</label>
                    <textarea class="form-control" id="glDescription" rows="3"></textarea>
                </div>
                <div class="mb-1">
                    <label class="form-label text-dark">Album</label>
                    <select class="form-select" id="glAlbumPick"></select>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-outline-danger btn-sm" id="glDeleteBtn"><i class="bx bx-trash"></i> Delete</button>
                <div class="d-flex gap-2">
                    <a href="#" target="_blank" class="btn btn-outline-primary btn-sm" id="glOpen">Open the file</a>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="glSaveBtn"><i class="bx bx-save me-1"></i> Save</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- One album. --}}
<div class="modal fade" id="glAlbumModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title mb-0" id="glAlbumModalTitle">Album</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="glAlbumId">
                <div class="mb-3">
                    <label class="form-label text-dark">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="glAlbumTitle" maxlength="191" placeholder="e.g. Land preparation">
                </div>
                <div class="mb-1">
                    <label class="form-label text-dark">What is in it</label>
                    <textarea class="form-control" id="glAlbumDescription" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-outline-danger btn-sm" id="glAlbumDeleteBtn"><i class="bx bx-trash"></i> Delete album</button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="glAlbumSaveBtn"><i class="bx bx-save me-1"></i> Save album</button>
                </div>
            </div>
        </div>
    </div>
</div>
