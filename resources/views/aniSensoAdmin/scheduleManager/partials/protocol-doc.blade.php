@php
    // The Protocol Introduction is stored on the version row
    // (globalActivityNote), so we surface the active version's intro
    // here. Switching versions in the Activities tab still re-points
    // this section. Attachments and Critical Rules are schedule-level.
    $protocolVersion = $schedule->versions->firstWhere('isActive', true)
        ?? $schedule->versions->firstWhere('isOriginal', true)
        ?? $schedule->versions->first();
    $hasProtocolIntro = $protocolVersion && !empty($protocolVersion->globalActivityNote);
@endphp

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h5 class="text-dark mb-1">Protocol &amp; Documentation</h5>
        <small class="text-secondary">
            The full protocol document, the per-version introduction, reference attachments, and
            critical rules. All four render at the top of the
            <strong>Worker Presentation</strong> and the <strong>Export Schedule</strong>.
        </small>
    </div>
</div>

{{-- ============================================================
     Subtabs — Protocol Document · Introduction · Attachments · Rules
============================================================ --}}
@php
    $hasProtocolDoc = optional($schedule->protocol)->protocolContent
                    || optional($schedule->protocol)->protocolFile;
@endphp
<ul class="nav nav-pills doc-subtabs mb-3" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" data-bs-toggle="tab" href="#sub-protocol-pdf" role="tab">
            <i class="bx bx-file"></i> Protocol Document
            @if($hasProtocolDoc)<span class="doc-subtab-dot" title="Saved"></span>@endif
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#sub-protocol-intro" role="tab">
            <i class="bxs-message-detail bx"></i> Introduction
            @if($hasProtocolIntro)<span class="doc-subtab-dot" title="Saved"></span>@endif
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#sub-attachments" role="tab">
            <i class="bx bx-image-alt"></i> Attachments
            <span class="badge bg-light text-dark ms-1">{{ $schedule->attachments->count() }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#sub-critical-rules" role="tab">
            <i class="bx bx-flag text-danger"></i> Critical Rules
            <span class="badge bg-light text-dark ms-1">{{ $schedule->criticalRules->count() }}</span>
        </a>
    </li>
</ul>

<div class="tab-content doc-subtab-content">

{{-- ============================================================
     Subtab 1. Protocol Document (formerly the standalone Protocol tab —
     PDF/DOC upload + plain-text protocol content. Moved in here as a
     subtab so all protocol-related setup lives under one umbrella.)
============================================================ --}}
<div class="tab-pane fade show active" id="sub-protocol-pdf" role="tabpanel">
    <div class="card mb-0 protocol-section">
        <div class="card-body">
            @include('aniSensoAdmin.scheduleManager.partials.protocol', ['schedule' => $schedule])
        </div>
    </div>
</div>

{{-- ============================================================
     Subtab 2. Protocol Introduction (per-version, formatted rich text)
============================================================ --}}
<div class="tab-pane fade" id="sub-protocol-intro" role="tabpanel">
    <div class="card mb-0 protocol-section">
        <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
            <div>
                <h6 class="text-dark mb-1"><i class="bx bxs-message-detail text-primary"></i> Protocol Introduction</h6>
                <small class="text-secondary">
                    Free-form intro that explains the protocol's strategy, season context, and rationale.
                    Supports rich text — headings, bold, lists, links, tables.
                    @if($protocolVersion)
                        <span class="ms-1">Editing the <strong>{{ $protocolVersion->versionName }}</strong> version.</span>
                    @endif
                </small>
            </div>
            <button type="button"
                    class="btn btn-sm {{ $hasProtocolIntro ? 'btn-primary' : 'btn-outline-secondary' }} global-note-trigger-btn"
                    id="protocolIntroEditBtn"
                    @if($protocolVersion)
                        data-version-id="{{ $protocolVersion->id }}"
                        data-existing="{{ $protocolVersion->globalActivityNote ?? '' }}"
                    @endif>
                <i class="bx {{ $hasProtocolIntro ? 'bxs-edit-alt' : 'bx-message-square-add' }} me-1"></i>
                {{ $hasProtocolIntro ? 'Edit Introduction' : 'Add Introduction' }}
            </button>
        </div>

        @if($hasProtocolIntro)
            <div class="protocol-intro-preview" id="protocolIntroPreview">
                {!! $protocolVersion->globalActivityNote !!}
            </div>
        @else
            <div class="protocol-empty-state text-center py-4">
                <i class="bx bxs-message-detail" style="font-size: 2rem; color: #b8c0d3;"></i>
                <p class="text-dark mt-2 mb-1">No introduction written yet.</p>
                <small class="text-secondary">Click <strong>Add Introduction</strong> to write the protocol context.</small>
            </div>
        @endif
    </div>
</div>
</div> {{-- /#sub-protocol-intro --}}

{{-- ============================================================
     Subtab 3. Attachments (image / PDF uploads with descriptions)
============================================================ --}}
<div class="tab-pane fade" id="sub-attachments" role="tabpanel">
<div class="card mb-0 protocol-section">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
            <div>
                <h6 class="text-dark mb-1"><i class="bx bx-image-alt text-primary"></i> Attachments</h6>
                <small class="text-secondary">
                    Reference images, field maps, seed-bag photos, equipment diagrams.
                    Each can carry a description. Max 10&nbsp;MB per file (JPG, PNG, GIF, WebP, PDF).
                </small>
            </div>
            <button type="button" class="btn btn-sm btn-primary" id="openAttachmentUploadBtn">
                <i class="bx bx-upload me-1"></i> Upload Attachment
            </button>
        </div>

        <div id="attachmentsGrid" class="attachments-grid">
            @forelse($schedule->attachments as $att)
                @include('aniSensoAdmin.scheduleManager.partials.attachment-card', ['att' => $att])
            @empty
                <div id="attachmentsEmpty" class="protocol-empty-state text-center py-4 w-100">
                    <i class="bx bx-image-alt" style="font-size: 2rem; color: #b8c0d3;"></i>
                    <p class="text-dark mt-2 mb-1">No attachments uploaded yet.</p>
                    <small class="text-secondary">Click <strong>Upload Attachment</strong> to add reference images.</small>
                </div>
            @endforelse
        </div>
    </div>
</div>
</div> {{-- /#sub-attachments --}}

{{-- ============================================================
     Subtab 4. Critical Rules (list of season-wide reminders)
============================================================ --}}
<div class="tab-pane fade" id="sub-critical-rules" role="tabpanel">
<div class="card mb-0 protocol-section">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
            <div>
                <h6 class="text-dark mb-1"><i class="bx bx-flag text-danger"></i> Critical Rules</h6>
                <small class="text-secondary">
                    Season-wide reminders that apply every time the field is worked.
                    These render prominently at the top of the Worker Presentation and Export Schedule.
                </small>
            </div>
            <button type="button" class="btn btn-sm btn-primary" id="openCriticalRuleAddBtn">
                <i class="bx bx-plus me-1"></i> Add Rule
            </button>
        </div>

        <div id="criticalRulesList" class="critical-rules-list">
            @forelse($schedule->criticalRules as $rule)
                @include('aniSensoAdmin.scheduleManager.partials.critical-rule-row', ['rule' => $rule])
            @empty
                <div id="criticalRulesEmpty" class="protocol-empty-state text-center py-4">
                    <i class="bx bx-flag" style="font-size: 2rem; color: #b8c0d3;"></i>
                    <p class="text-dark mt-2 mb-1">No critical rules defined yet.</p>
                    <small class="text-secondary">Click <strong>Add Rule</strong> to write the first reminder.</small>
                </div>
            @endforelse
        </div>
    </div>
</div>
</div> {{-- /#sub-critical-rules --}}

</div> {{-- /.doc-subtab-content --}}

{{-- ============================================================
     Modals (upload, edit description, add/edit rule)
============================================================ --}}
<div class="modal fade" id="attachmentUploadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-dark"><i class="bx bx-upload me-2"></i>Upload Attachment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label text-dark">File <span class="text-danger">*</span></label>
                    <input type="file" class="form-control" id="attachmentFileInput" accept="image/jpeg,image/png,image/gif,image/webp,application/pdf">
                    <small class="text-secondary">Max 10 MB. Allowed: JPG, PNG, GIF, WebP, PDF.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label text-dark">Description</label>
                    <textarea class="form-control" id="attachmentDescriptionInput" rows="3" maxlength="5000"
                              placeholder="e.g. Apartado lot 1 — field condition on June 1, 2026"></textarea>
                </div>
                <div id="attachmentUploadProgress" class="progress" style="display:none; height: 6px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="uploadAttachmentBtn">
                    <i class="bx bx-upload me-1"></i> Upload
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="attachmentEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-dark"><i class="bx bx-edit-alt me-2"></i>Edit Description</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-dark mb-2"><strong id="attachmentEditFilename">—</strong></p>
                <textarea class="form-control" id="attachmentEditDescription" rows="4" maxlength="5000"
                          placeholder="Describe what's in this image."></textarea>
                <input type="hidden" id="attachmentEditId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveAttachmentEditBtn">
                    <i class="bx bx-save me-1"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="criticalRuleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-dark"><i class="bx bx-flag me-2 text-danger"></i><span id="criticalRuleModalTitle">Add Critical Rule</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label class="form-label text-dark">Rule text <span class="text-danger">*</span></label>
                <textarea class="form-control" id="criticalRuleInput" rows="3" maxlength="2000"
                          placeholder="e.g. Always confirm tank-mix the day before any spray."></textarea>
                <small class="text-secondary d-block mt-1">Short, imperative sentences work best. One rule per item.</small>
                <input type="hidden" id="criticalRuleEditId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveCriticalRuleBtn">
                    <i class="bx bx-save me-1"></i> Save Rule
                </button>
            </div>
        </div>
    </div>
</div>
