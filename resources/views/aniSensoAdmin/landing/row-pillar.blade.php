@php $shot = array_key_exists($p['image'] ?? '', $shots) ? $p['image'] : 'board'; @endphp
<div class="lp-row">
    @include('aniSensoAdmin.landing.row-head', ['label' => 'Pillar'])
    <div class="row g-2">
        @include('aniSensoAdmin.landing.field', ['name' => "pillars[$k][kicker]", 'label' => 'Small line', 'value' => $p['kicker'] ?? '', 'col' => 'col-md-5', 'max' => 80])
        @include('aniSensoAdmin.landing.field', ['name' => "pillars[$k][title]", 'label' => 'Benefit headline', 'value' => $p['title'] ?? '', 'col' => 'col-md-7', 'max' => 120])
        @include('aniSensoAdmin.landing.field', ['name' => "pillars[$k][text]", 'label' => 'Words', 'value' => $p['text'] ?? '', 'type' => 'textarea', 'rows' => 3, 'max' => 1200])
        @include('aniSensoAdmin.landing.field', ['name' => "pillars[$k][bullets]", 'label' => 'Ticks, one per line', 'value' => implode("\n", $p['bullets'] ?? []), 'type' => 'textarea', 'rows' => 3, 'max' => 2000, 'col' => 'col-md-7'])
        @include('aniSensoAdmin.landing.field', ['name' => "pillars[$k][plan]", 'label' => 'Plan badge', 'value' => $p['plan'] ?? '', 'col' => 'col-md-5', 'max' => 80,
            'help' => 'e.g. "Free on Libre". A badge without "free" in it is drawn gold, as a paid plan.'])
        <div class="col-md-5">
            <label class="form-label" for="{{ 'ps_' . $k }}">Built-in picture</label>
            <select class="form-select" name="pillars[{{ $k }}][image]" id="{{ 'ps_' . $k }}" data-shot>
                @foreach ($shots as $key => [$label, $file])
                    <option value="{{ $key }}" data-src="{{ rtrim($base, '/') . '/images/site/' . $file }}" {{ $shot === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-7">
            <label class="form-label" for="{{ 'pf_' . $k }}">An uploaded picture is shown</label>
            <select class="form-select" name="pillars[{{ $k }}][frame]" id="{{ 'pf_' . $k }}">
                <option value="phone" {{ ($p['frame'] ?? 'phone') !== 'photo' ? 'selected' : '' }}>Inside a phone (for a screenshot)</option>
                <option value="photo" {{ ($p['frame'] ?? '') === 'photo' ? 'selected' : '' }}>As a photo</option>
            </select>
        </div>
        <div class="col-12">
            @include('aniSensoAdmin.landing.picture', ['prefix' => "pillars[$k]", 'field' => 'upload', 'current' => $p['upload'] ?? '', 'builtIn' => $shots[$shot][1],
                'shape' => ($p['upload'] ?? '') !== '' && ($p['frame'] ?? '') === 'photo' ? 'photo' : 'phone', 'base' => $base,
                'hint' => 'Optional: your own picture in place of the built-in one. A screenshot about 780 × 1600, or a photo about 1200 × 1600.'])
        </div>
    </div>
</div>
