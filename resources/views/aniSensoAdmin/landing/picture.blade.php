{{-- A picture slot: what it shows now (an upload, or anee's built-in file),
     a new upload, and the way back to the built-in one. Posts
     {prefix}[{field}] (what it held), {field}File and {field}Clear. --}}
@php
    $current = (string) ($current ?? '');
    $isUrl = preg_match('#^https?://#i', $current);
    $shown = $current === '' ? null : ($isUrl ? $current : \Illuminate\Support\Facades\Storage::disk('public')->url($current));
    $builtInUrl = ! empty($builtIn) ? rtrim($base, '/') . '/images/site/' . $builtIn : null;
    // Readable, not hashed: a template row's __K__ must reach the id to be replaced.
    $pid = 'pc_' . preg_replace('/[^A-Za-z0-9]+/', '_', $prefix . '_' . $field);
@endphp
<div class="lp-pic {{ $shown ? 'has-upload' : '' }}" data-pic data-built-in="{{ $builtInUrl }}">
    <div class="lp-pic-frame {{ ($shape ?? 'photo') === 'phone' ? 'is-phone' : '' }} {{ ($shape ?? '') === 'face' ? 'is-face' : '' }}">
        @if ($shown || $builtInUrl)
            <img src="{{ $shown ?: $builtInUrl }}" alt="" loading="lazy" data-pic-img>
        @else
            <img alt="" loading="lazy" data-pic-img hidden>
            <span class="lp-pic-none" data-pic-none>No photo<br><small>initials show instead</small></span>
        @endif
    </div>
    <div class="lp-pic-side">
        <div class="lp-pic-state">
            <span class="badge bg-success-subtle text-success" data-pic-state="upload">Your upload</span>
            <span class="badge bg-secondary-subtle text-secondary" data-pic-state="builtin">{{ $builtInUrl ? 'Built-in picture' : 'None' }}</span>
        </div>
        <input type="hidden" name="{{ $prefix }}[{{ $field }}]" value="{{ $current }}">
        <input type="file" class="form-control form-control-sm" name="{{ $prefix }}[{{ $field }}File]" accept="image/jpeg,image/png,image/webp" data-pic-file>
        @if (! empty($hint))<div class="form-text">{{ $hint }}</div>@endif
        <div class="form-check mt-1 lp-pic-clear">
            <input class="form-check-input" type="checkbox" value="1" name="{{ $prefix }}[{{ $field }}Clear]" id="{{ $pid }}" data-pic-clear>
            <label class="form-check-label small" for="{{ $pid }}">{{ $builtInUrl ? 'Use the built-in picture again' : 'Remove the photo' }}</label>
        </div>
    </div>
</div>
