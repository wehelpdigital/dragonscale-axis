<div class="row g-3 mb-3">
    <div class="col-md-8">
        <label class="form-label">Phrase / Keyphrase <span class="text-danger">*</span></label>
        <input type="text" name="phrase" value="{{ old('phrase', $k->phrase ?? '') }}" class="form-control" required>
        <small class="text-muted">e.g. "resort in Bulacan", "beach resort Palawan"</small>
        @error('phrase') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
            @foreach(['active' => 'Active', 'draft' => 'Draft', 'archived' => 'Archived'] as $v => $l)
                <option value="{{ $v }}" {{ old('status', $k->status ?? 'active') == $v ? 'selected' : '' }}>{{ $l }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Monthly Search Volume</label>
        <input type="number" name="search_volume_monthly" value="{{ old('search_volume_monthly', $k->search_volume_monthly ?? 0) }}" class="form-control" min="0">
    </div>
    <div class="col-md-4">
        <label class="form-label">Keyword Difficulty (0-100)</label>
        <input type="number" name="keyword_difficulty" value="{{ old('keyword_difficulty', $k->keyword_difficulty ?? 0) }}" class="form-control" min="0" max="100">
    </div>
    <div class="col-md-4">
        <label class="form-label">Cluster Tag</label>
        <input type="text" name="cluster_tag" value="{{ old('cluster_tag', $k->cluster_tag ?? '') }}" class="form-control" placeholder="e.g. luzon-resorts">
    </div>
    <div class="col-md-6">
        <label class="form-label">Intent</label>
        <input type="text" name="intent" value="{{ old('intent', $k->intent ?? '') }}" class="form-control" placeholder="informational | commercial | transactional">
    </div>
    <div class="col-12">
        <label class="form-label">Notes (internal)</label>
        <textarea name="notes" class="form-control" rows="3">{{ old('notes', $k->notes ?? '') }}</textarea>
    </div>
</div>
