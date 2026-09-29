{{-- One text field of the landing editor. The default shows as the
     placeholder, so an emptied field says what the page will use. --}}
@php $fid = 'lf_' . preg_replace('/[^A-Za-z0-9]+/', '_', $name); @endphp
<div class="{{ $col ?? 'col-12' }}">
    <label class="form-label" for="{{ $fid }}">{{ $label }}</label>
    @if (($type ?? 'input') === 'textarea')
        <textarea class="form-control" id="{{ $fid }}" name="{{ $name }}" rows="{{ $rows ?? 3 }}" maxlength="{{ $max ?? 600 }}" placeholder="{{ $default ?? '' }}">{{ $value ?? '' }}</textarea>
    @else
        <input type="text" class="form-control" id="{{ $fid }}" name="{{ $name }}" value="{{ $value ?? '' }}" maxlength="{{ $max ?? 600 }}" placeholder="{{ $default ?? '' }}">
    @endif
    @if (! empty($help))<div class="form-text">{{ $help }}</div>@endif
</div>
