@extends('layouts.master')

@section('title') {{ $author ? 'Edit Author' : 'Add Author' }} @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Resort Guru @endslot
@slot('title') {{ $author ? 'Edit Author' : 'Add Author' }} @endslot
@endcomponent

@php
    $action = $author ? '/resort-guru-authors-update' : '/resort-guru-authors-store';
    $coversArr = $author && $author->covers_clusters ? array_map('trim', explode(',', $author->covers_clusters)) : [];
    $frontendBase = \App\Support\RgFrontend::url();
    $avatarPreview = $author && $author->avatar_path
        ? (preg_match('#^https?://#i', $author->avatar_path) ? $author->avatar_path : $frontendBase . '/storage/' . ltrim($author->avatar_path, '/'))
        : 'https://api.dicebear.com/7.x/notionists/svg?seed=Preview';
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data">
    @csrf
    @if($author)<input type="hidden" name="id" value="{{ $author->id }}">@endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Author profile</h5>

                    <div class="mb-3">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" class="form-control" value="{{ $author->name ?? '' }}" required maxlength="200">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Role / title</label>
                            <input type="text" name="role" class="form-control" placeholder="e.g. Travel Writer, Senior Editor" value="{{ $author->role ?? '' }}" maxlength="200">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Home base</label>
                            <input type="text" name="home_base" class="form-control" placeholder="e.g. Quezon City" value="{{ $author->home_base ?? '' }}" maxlength="200">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Bio</label>
                        <textarea name="bio" rows="4" class="form-control" maxlength="2000" placeholder="2 to 3 sentences readers will see under the byline.">{{ $author->bio ?? '' }}</textarea>
                    </div>

                    <h6 class="mt-4 mb-3 text-muted">Social handles</h6>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ $author->email ?? '' }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Instagram</label>
                            <input type="text" name="instagram" class="form-control" placeholder="handle only" value="{{ $author->instagram ?? '' }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Facebook</label>
                            <input type="text" name="facebook" class="form-control" value="{{ $author->facebook ?? '' }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Twitter / X</label>
                            <input type="text" name="twitter" class="form-control" value="{{ $author->twitter ?? '' }}">
                        </div>
                    </div>

                    <h6 class="mt-4 mb-3 text-muted">Coverage</h6>
                    <p class="small text-muted">Pick the destination clusters this author writes about. When you assign pages by cluster, the seeder
                    rotates among matching authors.</p>
                    <div class="row">
                        @foreach($clusterOptions as $key => $label)
                            <div class="col-md-3 col-6 mb-2">
                                <label class="form-check">
                                    <input type="checkbox" name="covers_clusters[]" value="{{ $key }}" class="form-check-input" {{ in_array($key, $coversArr) ? 'checked' : '' }}>
                                    <span class="form-check-label">{{ $label }}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Avatar</h5>
                    <div class="text-center mb-3">
                        <img id="avatarPreview" src="{{ $avatarPreview }}" alt="" style="width:120px;height:120px;border-radius:50%;background:#f1f5f9;border:1px solid #e2e8f0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Avatar URL (recommended)</label>
                        <input type="url" name="avatar_url" class="form-control" id="avatarUrlInput" value="{{ ($author && preg_match('#^https?://#i', $author->avatar_path ?? '')) ? $author->avatar_path : '' }}" placeholder="https://api.dicebear.com/7.x/notionists/svg?seed=Maria">
                        <div class="form-text">Paste any image URL, or leave blank to auto-generate a DiceBear avatar from the name.</div>
                    </div>
                    <div class="mb-3">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="genAvatar()">
                            <i class="bx bx-refresh me-1"></i>Generate from name
                        </button>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <label class="form-label">Or upload a file</label>
                        <input type="file" name="avatar" class="form-control" accept="image/*">
                        <div class="form-text">Local upload stores to the mother app and may not display on the frontend.</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Status</h5>
                    <select name="status" class="form-select">
                        <option value="active" {{ ($author->status ?? 'active') === 'active' ? 'selected' : '' }}>Active (eligible for new pages)</option>
                        <option value="inactive" {{ ($author->status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive (existing pages keep their byline)</option>
                    </select>
                    <div class="mt-3">
                        <label class="form-label small text-muted">Sort order</label>
                        <input type="number" name="sort_order" class="form-control form-control-sm" value="{{ $author->sort_order ?? 99 }}">
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="bx bx-save me-1"></i>{{ $author ? 'Update Author' : 'Create Author' }}
                </button>
                <a href="/resort-guru-authors" class="btn btn-light">Cancel</a>
            </div>
        </div>
    </div>
</form>
@endsection

@section('script')
<script>
function genAvatar() {
    var name = document.querySelector('input[name="name"]').value || 'Author';
    var url = 'https://api.dicebear.com/7.x/notionists/svg?seed=' + encodeURIComponent(name);
    document.getElementById('avatarUrlInput').value = url;
    document.getElementById('avatarPreview').src = url;
}
// Live preview when URL changes
document.getElementById('avatarUrlInput').addEventListener('input', function (e) {
    if (e.target.value) document.getElementById('avatarPreview').src = e.target.value;
});
</script>
@endsection
