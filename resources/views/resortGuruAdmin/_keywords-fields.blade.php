<div class="row g-3 mb-3">
    <div class="col-md-6">
        <label class="form-label">Phrase / Keyphrase <span class="text-danger">*</span></label>
        <input type="text" name="phrase" value="{{ old('phrase', $k->phrase ?? '') }}" class="form-control" required>
        <small class="text-muted">e.g. "resort in Bulacan", "beach resort Palawan"</small>
        @error('phrase') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Type <span class="text-danger">*</span></label>
        @php
            $rgKwCategories = $categories ?? ['resort' => 'Resorts', 'food' => 'Food', 'destination' => 'Destinations'];
            $rgKwCategoryCurrent = old('category', $k->category ?? ($selectedCategory ?? 'resort'));
        @endphp
        <select name="category" class="form-select" required>
            @foreach($rgKwCategories as $v => $l)
                <option value="{{ $v }}" {{ $rgKwCategoryCurrent === $v ? 'selected' : '' }}>{{ $l }}</option>
            @endforeach
        </select>
        <small class="text-muted">Groups this keyword by vertical on the Keywords screen.</small>
        @error('category') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
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
        <label class="form-label">Region Cluster</label>
        @php
            // Canonical region clusters (must match App\Support\RegionResolver
            // / DestinationsController::clusterMetadata() in the frontend app).
            $rgClusters = [
                'north-luzon'  => 'North Luzon (Pangasinan, La Union, Ilocos, Zambales, Bataan, Baguio)',
                'metro-manila' => 'Metro Manila',
                'bulacan'      => 'Bulacan',
                'pampanga'     => 'Pampanga',
                'rizal'        => 'Rizal',
                'cavite'       => 'Cavite',
                'laguna'       => 'Laguna',
                'batangas'     => 'Batangas',
                'quezon'       => 'Quezon',
                'bicol'        => 'Bicol',
                'palawan'      => 'Palawan & Mindoro',
                'visayas'      => 'Visayas',
                'mindanao'     => 'Mindanao',
                'other'        => 'Other',
            ];
            $rgClusterCurrent = old('cluster_tag', $k->cluster_tag ?? '');
        @endphp
        <select name="cluster_tag" class="form-select">
            <option value="">Auto-detect from location</option>
            @foreach($rgClusters as $v => $l)
                <option value="{{ $v }}" {{ $rgClusterCurrent === $v ? 'selected' : '' }}>{{ $l }}</option>
            @endforeach
        </select>
        <small class="text-muted">Groups this page in the destination footer. Leave on "Auto-detect" to group by the place named in the phrase.</small>
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
