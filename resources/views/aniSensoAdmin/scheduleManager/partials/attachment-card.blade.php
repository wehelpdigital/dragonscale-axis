@php
    $attUrl = $att->getPublicUrl();
    $isImg  = $att->isImage();
    $sizeKb = $att->fileSize ? number_format($att->fileSize / 1024, 1) . ' KB' : null;
@endphp
<div class="attachment-card" data-id="{{ $att->id }}">
    <div class="attachment-thumb">
        @if($isImg && $attUrl)
            <a href="{{ $attUrl }}" target="_blank" rel="noopener">
                <img src="{{ $attUrl }}" alt="{{ $att->filename }}" loading="lazy">
            </a>
        @else
            <a href="{{ $attUrl }}" target="_blank" rel="noopener" class="attachment-file-icon">
                <i class="bx bxs-file"></i>
                <span class="ext-badge">{{ pathinfo($att->filename, PATHINFO_EXTENSION) }}</span>
            </a>
        @endif
    </div>
    <div class="attachment-body">
        <div class="attachment-filename" title="{{ $att->filename }}">{{ $att->filename }}</div>
        @if($sizeKb)<small class="text-secondary">{{ $sizeKb }}</small>@endif
        <div class="attachment-description" data-id="{{ $att->id }}">
            @if($att->description)
                {{ $att->description }}
            @else
                <em class="text-secondary">No description.</em>
            @endif
        </div>
        <div class="attachment-actions">
            <button type="button" class="btn btn-sm btn-outline-primary edit-attachment-btn"
                    data-id="{{ $att->id }}"
                    data-filename="{{ $att->filename }}"
                    data-description="{{ $att->description }}"
                    title="Edit description"><i class="bx bx-edit-alt"></i></button>
            <button type="button" class="btn btn-sm btn-outline-danger delete-attachment-btn"
                    data-id="{{ $att->id }}"
                    data-filename="{{ $att->filename }}"
                    title="Delete attachment"><i class="bx bx-trash"></i></button>
        </div>
    </div>
</div>
