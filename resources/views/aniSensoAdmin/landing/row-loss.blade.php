<div class="lp-row">
    @include('aniSensoAdmin.landing.row-head', ['label' => 'Figure'])
    <div class="row g-2">
        <div class="col-4 col-md-2">
            <label class="form-label" for="{{ 'ln_' . $k }}">Up to %</label>
            <input type="number" class="form-control" id="{{ 'ln_' . $k }}" name="losses[items][{{ $k }}][n]" value="{{ $l['n'] ?? '' }}" min="0" max="100" step="1">
        </div>
        @include('aniSensoAdmin.landing.field', ['name' => "losses[items][$k][title]", 'label' => 'What is lost', 'value' => $l['title'] ?? '', 'col' => 'col-8 col-md-10', 'max' => 120, 'default' => 'e.g. lost to pests and diseases'])
        @include('aniSensoAdmin.landing.field', ['name' => "losses[items][$k][text]", 'label' => 'Why', 'value' => $l['text'] ?? '', 'max' => 300])
    </div>
</div>
