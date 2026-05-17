<div class="mb-3">
    <h5 class="text-dark mb-1">Protocol</h5>
    <small class="text-secondary">Paste your protocol content here, or upload a file (PDF/DOC). Either or both are accepted.</small>
</div>

<form id="protocolForm" enctype="multipart/form-data">
    @csrf
    <div class="mb-3">
        <label class="form-label text-dark">Protocol Content</label>
        <textarea class="form-control" id="protocolContent" name="protocolContent" rows="10" placeholder="Paste your full cropping protocol here...">{{ optional($schedule->protocol)->protocolContent }}</textarea>
    </div>

    <div class="mb-3">
        <label class="form-label text-dark">Or upload a protocol file (PDF, DOC, DOCX, TXT, max 10MB)</label>
        <input type="file" class="form-control" id="protocolFile" name="protocolFile" accept=".pdf,.doc,.docx,.txt">
        <div class="mt-2" id="protocolCurrentFile" @if(!optional($schedule->protocol)->protocolFile) style="display:none;" @endif>
            <small class="text-secondary me-2">Current file:</small>
            <a href="{{ route('anisenso-schedule-manager.protocol.download', ['scheduleId' => $schedule->id]) }}" class="badge bg-info text-white text-decoration-none" id="protocolCurrentFileLink">
                <i class="bx bx-download me-1"></i><span id="protocolCurrentFileName">{{ optional($schedule->protocol)->protocolFileOriginalName ?: 'Download' }}</span>
            </a>
        </div>
    </div>

    <div class="d-flex justify-content-end">
        <button type="button" class="btn btn-primary" id="saveProtocolBtn"><i class="bx bx-save me-1"></i>Save Protocol</button>
    </div>
</form>
