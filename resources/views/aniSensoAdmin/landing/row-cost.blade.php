@php $photo = array_key_exists($c['image'] ?? '', $photos) ? $c['image'] : 'tractor'; @endphp
<div class="lp-row">
    @include('aniSensoAdmin.landing.row-head', ['label' => 'Cost row'])
    <div class="row g-2">
        @include('aniSensoAdmin.landing.field', ['name' => "costs[items][$k][kicker]", 'label' => 'Small line', 'value' => $c['kicker'] ?? '', 'col' => 'col-md-5', 'max' => 80, 'default' => 'e.g. Fuel keeps going up'])
        @include('aniSensoAdmin.landing.field', ['name' => "costs[items][$k][headline]", 'label' => 'Headline', 'value' => $c['headline'] ?? '', 'col' => 'col-md-7', 'max' => 160])
        @include('aniSensoAdmin.landing.field', ['name' => "costs[items][$k][text]", 'label' => 'What goes wrong', 'value' => $c['text'] ?? '', 'type' => 'textarea', 'rows' => 3, 'max' => 1200])
        @include('aniSensoAdmin.landing.field', ['name' => "costs[items][$k][fixes]", 'label' => 'How anee.io helps, one per line (green ticks)', 'help' => 'Keep each to about 36 characters: every tick shows on one line, even on a phone, and a longer one is cut short with …', 'value' => implode("\n", $c['fixes'] ?? []), 'type' => 'textarea', 'rows' => 3, 'max' => 2000])
        <div class="col-md-6">
            <label class="form-label" for="{{ 'cp_' . $k }}">Built-in photo</label>
            <select class="form-select" name="costs[items][{{ $k }}][image]" id="{{ 'cp_' . $k }}" data-shot>
                @foreach ($photos as $key => [$label, $file])
                    <option value="{{ $key }}" data-src="{{ rtrim($base, '/') . '/images/site/' . $file }}" {{ $photo === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12">
            @include('aniSensoAdmin.landing.picture', ['prefix' => "costs[items][$k]", 'field' => 'upload', 'current' => $c['upload'] ?? '', 'builtIn' => $photos[$photo][1], 'base' => $base,
                'hint' => 'Optional: your own photo in place of the built-in one. Landscape, 4:3 works best. JPG, PNG or WebP, up to 6 MB.'])
        </div>
    </div>
</div>
