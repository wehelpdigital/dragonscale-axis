{{-- ===================== DOCUMENTS =====================
     What the client's Documentation module actually holds: typed entries —
     a protocol, an introduction, a critical rule, something miscellaneous, or
     a custom kind under a tag they named — each with rich text and any number
     of files.

     This drawer is why the tab could look empty while the season had six
     documents on it: the three older shelves beside it are a different pair
     of tables, and nothing writes them any more. --}}
@php
    $docTypes = \App\Http\Controllers\aniSensoAdmin\ScheduleManager\DocEntryController::TYPES;
@endphp
<style>
    .de-card { border: 1px solid #e6e8ec; border-radius: 10px; padding: .8rem .95rem; margin-bottom: .6rem; }
    .de-card:hover { border-color: #c7d2fe; }
    .de-title { font-weight: 600; color: #343a40; font-size: 13.5px; }
    .de-meta { font-size: 11.5px; color: #98a4b6; margin-top: .12rem; }
    .de-type {
        display: inline-flex; align-items: center; gap: .25rem; font-size: 10.5px; font-weight: 600;
        background: #eef1f6; color: #556ee6; border-radius: 999px; padding: .1rem .5rem;
    }
    .de-type.is-rule { background: #fdeceb; color: #c0392b; }
    .de-type.is-custom { background: #e9f7ef; color: #0f8a5f; }
    .de-words { font-size: 12.5px; color: #74788d; margin-top: .35rem; }
    .de-files { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .5rem; }
    .de-file {
        display: inline-flex; align-items: center; gap: .3rem; font-size: 11.5px;
        border: 1px solid #e6e8ec; border-radius: 8px; padding: .2rem .5rem; color: #495057; background: #fff;
    }
    .de-files img { width: 64px; height: 48px; object-fit: cover; border-radius: 6px; border: 1px solid #e6e8ec; }
    .de-empty { text-align: center; padding: 2.2rem 1rem; color: #98a4b6; }
    .de-empty i { font-size: 2.2rem; display: block; margin-bottom: .4rem; }
    .de-tags { display: flex; flex-wrap: wrap; gap: .35rem; margin-bottom: .8rem; }
    .de-tag {
        display: inline-flex; align-items: center; gap: .3rem; font-size: 11.5px; font-weight: 600;
        border: 1px solid #e6e8ec; border-radius: 999px; padding: .15rem .6rem; background: #fff; color: #495057;
    }
</style>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <h6 class="text-dark mb-1">Documents</h6>
        <small class="text-secondary">
            The entries the client's Documentation module holds — protocols, introductions,
            critical rules and anything they tagged for themselves.
        </small>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-light btn-sm" id="deReload"><i class="bx bx-refresh"></i> Refresh</button>
        <button type="button" class="btn btn-outline-primary btn-sm" id="deNewTag"><i class="bx bx-purchase-tag me-1"></i> New tag</button>
        <button type="button" class="btn btn-primary btn-sm" id="deNewBtn"><i class="bx bx-plus me-1"></i> New document</button>
    </div>
</div>

<div class="de-tags" id="deTags"></div>
<div id="deBody"></div>

<div class="modal fade" id="deModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title mb-0" id="deModalTitle">Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="deId">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-dark">What kind <span class="text-danger">*</span></label>
                        <select class="form-select" id="deType">
                            @foreach ($docTypes as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                            <option value="custom">Custom — under a tag</option>
                        </select>
                    </div>
                    <div class="col-md-6" id="deTagRow" style="display:none;">
                        <label class="form-label text-dark">Tag <span class="text-danger">*</span></label>
                        <select class="form-select" id="deTagId"></select>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-dark">Title</label>
                        <input type="text" class="form-control" id="deTitle" maxlength="191">
                    </div>
                    <div class="col-12">
                        <label class="form-label text-dark">Words</label>
                        <textarea class="form-control" id="deContent" rows="8"></textarea>
                        <small class="text-secondary">The client's app writes rich text here; the tags are shown as they are.</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-dark">Files</label>
                        <div id="deKeep" class="de-files mb-2"></div>
                        <input type="file" class="form-control" id="deFiles" multiple
                               accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.txt,.xls,.xlsx">
                        <small class="text-secondary">Images, PDF, Word, Excel or TXT — 10 MB each.</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-outline-danger btn-sm" id="deDeleteBtn"><i class="bx bx-trash"></i> Delete</button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="deSaveBtn"><i class="bx bx-save me-1"></i> Save document</button>
                </div>
            </div>
        </div>
    </div>
</div>
