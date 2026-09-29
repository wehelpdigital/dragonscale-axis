{{-- One of the "What guessing costs a hectare" cards. The peso, photo and
     upload fields show only while anee's published defaults have them. --}}
@php
    $lossPhotos = \App\Http\Controllers\aniSensoAdmin\AnisystemLandingController::LOSS_PHOTOS;
    $pic = array_key_exists($l['image'] ?? '', $lossPhotos) ? $l['image'] : 'palay-heads';
    $rich = array_key_exists('peso', $l);
@endphp
<div class="lp-row">
    @include('aniSensoAdmin.landing.row-head', ['label' => 'Figure'])
    <div class="row g-2">
        <div class="col-4 col-md-2">
            <label class="form-label" for="{{ 'ln_' . $k }}">Up to %</label>
            <input type="number" class="form-control" id="{{ 'ln_' . $k }}" name="losses[items][{{ $k }}][n]" value="{{ $l['n'] ?? '' }}" min="0" max="100" step="1">
        </div>
        @include('aniSensoAdmin.landing.field', ['name' => "losses[items][$k][title]", 'label' => 'What is lost', 'value' => $l['title'] ?? '', 'col' => 'col-8 col-md-10', 'max' => 120, 'default' => 'e.g. Yield lost to pests and diseases'])
        @include('aniSensoAdmin.landing.field', ['name' => "losses[items][$k][text]", 'label' => 'Why', 'value' => $l['text'] ?? '', 'max' => 300])
        @if ($rich)
            @include('aniSensoAdmin.landing.field', ['name' => "losses[items][$k][peso]", 'label' => 'Pesos lost per hectare (Philippine page)', 'value' => $l['peso'] ?? '', 'col' => 'col-md-6', 'max' => 40, 'default' => 'e.g. ₱25,000–₱40,000',
                'help' => 'Shown as "… lost per hectare". The international page shows the percentage instead.'])
            <div class="col-md-6">
                <label class="form-label" for="{{ 'lp_' . $k }}">Built-in photo</label>
                <select class="form-select" name="losses[items][{{ $k }}][image]" id="{{ 'lp_' . $k }}" data-shot>
                    @foreach ($lossPhotos as $key => [$label, $file])
                        <option value="{{ $key }}" data-src="{{ rtrim($base, '/') . '/images/site/' . $file }}" {{ $pic === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                @include('aniSensoAdmin.landing.picture', ['prefix' => "losses[items][$k]", 'field' => 'upload', 'current' => $l['upload'] ?? '', 'builtIn' => $lossPhotos[$pic][1], 'shape' => 'phone', 'base' => $base,
                    'hint' => 'Optional: your own photo in place of the built-in one. It is shown as a tall strip beside the words, so keep the subject in the middle.'])
            </div>
        @endif
    </div>
</div>
