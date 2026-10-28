<div class="lp-row">
    @include('aniSensoAdmin.landing.row-head', ['label' => 'Tool'])
    <div class="row g-2">
        @include('aniSensoAdmin.landing.field', ['name' => "space[items][$k][title]", 'label' => 'Title', 'value' => $t['title'] ?? '', 'col' => 'col-md-4', 'max' => 80])
        @include('aniSensoAdmin.landing.field', ['name' => "space[items][$k][text]", 'label' => 'Words', 'value' => $t['text'] ?? '', 'col' => 'col-md-5', 'max' => 300])
        <div class="col-md-3">
            <label class="form-label">Screen</label>
            <select name="space[items][{{ $k }}][image]" class="form-select">
                @foreach (\App\Http\Controllers\aniSensoAdmin\AnisystemLandingController::SPACE_TOOLS as $v => $l)
                    <option value="{{ $v }}" @selected(($t['image'] ?? '') === $v)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>
