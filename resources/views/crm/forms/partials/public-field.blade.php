@php
    $fieldId = $element['id'] ?? '';
    $fieldType = $element['type'] ?? 'text';
    $label = $element['label'] ?? '';
    $placeholder = $element['placeholder'] ?? '';
    $required = $element['required'] ?? false;
    $width = $element['width'] ?? 'col-12';
    $options = $element['options'] ?? [];
@endphp

<div class="{{ $width }} mb-3">
    @switch($fieldType)
        @case('text')
        @case('email')
        @case('phone')
        @case('number')
            <label class="form-label" for="{{ $fieldId }}">
                {{ $label }}
                @if($required)<span class="required-indicator">*</span>@endif
            </label>
            <input
                type="{{ $fieldType === 'phone' ? 'tel' : $fieldType }}"
                class="form-control"
                id="{{ $fieldId }}"
                name="{{ $fieldId }}"
                placeholder="{{ $placeholder }}"
                {{ $required ? 'required' : '' }}
                @if($fieldType === 'number' && isset($element['min'])) min="{{ $element['min'] }}" @endif
                @if($fieldType === 'number' && isset($element['max'])) max="{{ $element['max'] }}" @endif
            >
            @break

        @case('textarea')
            <label class="form-label" for="{{ $fieldId }}">
                {{ $label }}
                @if($required)<span class="required-indicator">*</span>@endif
            </label>
            <textarea
                class="form-control"
                id="{{ $fieldId }}"
                name="{{ $fieldId }}"
                rows="{{ $element['rows'] ?? 4 }}"
                placeholder="{{ $placeholder }}"
                {{ $required ? 'required' : '' }}
            ></textarea>
            @break

        @case('select')
            <label class="form-label" for="{{ $fieldId }}">
                {{ $label }}
                @if($required)<span class="required-indicator">*</span>@endif
            </label>
            <select
                class="form-select"
                id="{{ $fieldId }}"
                name="{{ $fieldId }}"
                {{ $required ? 'required' : '' }}
            >
                <option value="">{{ $placeholder ?: 'Choose...' }}</option>
                @foreach($options as $option)
                    <option value="{{ $option }}">{{ $option }}</option>
                @endforeach
            </select>
            @break

        @case('radio')
            <label class="form-label d-block">
                {{ $label }}
                @if($required)<span class="required-indicator">*</span>@endif
            </label>
            @foreach($options as $i => $option)
                <div class="form-check {{ ($element['inline'] ?? false) ? 'form-check-inline' : '' }}">
                    <input
                        class="form-check-input"
                        type="radio"
                        name="{{ $fieldId }}"
                        id="{{ $fieldId }}_{{ $i }}"
                        value="{{ $option }}"
                        {{ $required && $i === 0 ? 'required' : '' }}
                    >
                    <label class="form-check-label" for="{{ $fieldId }}_{{ $i }}">{{ $option }}</label>
                </div>
            @endforeach
            @break

        @case('checkbox')
            <label class="form-label d-block">
                {{ $label }}
                @if($required)<span class="required-indicator">*</span>@endif
            </label>
            @foreach($options as $i => $option)
                <div class="form-check {{ ($element['inline'] ?? false) ? 'form-check-inline' : '' }}">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        name="{{ $fieldId }}[]"
                        id="{{ $fieldId }}_{{ $i }}"
                        value="{{ $option }}"
                    >
                    <label class="form-check-label" for="{{ $fieldId }}_{{ $i }}">{{ $option }}</label>
                </div>
            @endforeach
            @break

        @case('single_checkbox')
            <div class="form-check">
                <input
                    class="form-check-input"
                    type="checkbox"
                    name="{{ $fieldId }}"
                    id="{{ $fieldId }}"
                    value="1"
                    {{ $required ? 'required' : '' }}
                >
                <label class="form-check-label" for="{{ $fieldId }}">
                    {{ $label }}
                    @if($required)<span class="required-indicator">*</span>@endif
                </label>
            </div>
            @break

        @case('date')
            <label class="form-label" for="{{ $fieldId }}">
                {{ $label }}
                @if($required)<span class="required-indicator">*</span>@endif
            </label>
            <input
                type="date"
                class="form-control"
                id="{{ $fieldId }}"
                name="{{ $fieldId }}"
                {{ $required ? 'required' : '' }}
            >
            @break

        @case('time')
            <label class="form-label" for="{{ $fieldId }}">
                {{ $label }}
                @if($required)<span class="required-indicator">*</span>@endif
            </label>
            <input
                type="time"
                class="form-control"
                id="{{ $fieldId }}"
                name="{{ $fieldId }}"
                {{ $required ? 'required' : '' }}
            >
            @break

        @case('file')
            <label class="form-label" for="{{ $fieldId }}">
                {{ $label }}
                @if($required)<span class="required-indicator">*</span>@endif
            </label>
            <input
                type="file"
                class="form-control"
                id="{{ $fieldId }}"
                name="{{ $fieldId }}"
                accept="{{ $element['accept'] ?? '' }}"
                {{ $required ? 'required' : '' }}
            >
            @if(isset($element['maxSize']))
                <small class="text-muted">Max file size: {{ $element['maxSize'] }}MB</small>
            @endif
            @break

        @case('hidden')
            <input type="hidden" name="{{ $fieldId }}" value="{{ $element['value'] ?? '' }}">
            @break

        @case('heading')
            @php $tag = $element['size'] ?? 'h4'; @endphp
            <{{ $tag }} class="form-heading">{{ $element['text'] ?? '' }}</{{ $tag }}>
            @break

        @case('paragraph')
            <p class="form-paragraph">{{ $element['text'] ?? '' }}</p>
            @break

        @case('divider')
            <hr class="form-divider">
            @break

        @case('submit_button')
            <div class="d-grid">
                <button type="submit" class="btn btn-submit" id="submitBtn" style="background-color: {{ $element['buttonColor'] ?? '#556ee6' }}; border-color: {{ $element['buttonColor'] ?? '#556ee6' }};">
                    {{ $element['buttonText'] ?? 'Submit' }}
                </button>
            </div>
            @break

        @case('image')
            @if(!empty($element['imageUrl']))
            @php
                $imageSizeMap = ['small' => '25%', 'medium' => '50%', 'large' => '75%', 'full' => '100%'];
                $imageSize = $imageSizeMap[$element['imageSize'] ?? 'medium'] ?? '50%';
                $imagePosition = $element['imagePosition'] ?? 'center';
                $justifyClass = $imagePosition === 'left' ? 'justify-content-start' : ($imagePosition === 'right' ? 'justify-content-end' : 'justify-content-center');
            @endphp
            <div class="form-image d-flex {{ $justifyClass }}">
                <div style="width: {{ $imageSize }}; max-width: 100%;">
                    <img src="{{ $element['imageUrl'] }}" alt="{{ $element['caption'] ?? '' }}" class="img-fluid rounded" style="width: 100%;">
                    @if(!empty($element['caption']))
                    <p class="form-image-caption mt-2 text-muted small text-center">{{ $element['caption'] }}</p>
                    @endif
                </div>
            </div>
            @endif
            @break

        @case('video')
            @if(!empty($element['videoUrl']))
            @php
                $videoUrl = $element['videoUrl'];
                $embedUrl = '';
                // Convert YouTube URLs to embed format
                if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]+)/', $videoUrl, $matches)) {
                    $embedUrl = 'https://www.youtube.com/embed/' . $matches[1];
                } elseif (preg_match('/vimeo\.com\/(\d+)/', $videoUrl, $matches)) {
                    $embedUrl = 'https://player.vimeo.com/video/' . $matches[1];
                } else {
                    $embedUrl = $videoUrl;
                }
            @endphp
            <div class="form-video ratio ratio-16x9">
                <iframe src="{{ $embedUrl }}" allowfullscreen></iframe>
            </div>
            @endif
            @break
    @endswitch
</div>
