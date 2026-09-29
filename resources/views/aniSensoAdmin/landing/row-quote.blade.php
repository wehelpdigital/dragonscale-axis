<div class="lp-row">
    @include('aniSensoAdmin.landing.row-head', ['label' => 'Testimonial'])
    <div class="row g-2">
        @include('aniSensoAdmin.landing.field', ['name' => "testimonials[items][$k][name]", 'label' => 'Name (real)', 'value' => $t['name'] ?? '', 'col' => 'col-md-4', 'max' => 80])
        @include('aniSensoAdmin.landing.field', ['name' => "testimonials[items][$k][role]", 'label' => 'What they grow or do', 'value' => $t['role'] ?? '', 'col' => 'col-md-4', 'max' => 80, 'default' => 'e.g. Rice farmer, 3 hectares'])
        @include('aniSensoAdmin.landing.field', ['name' => "testimonials[items][$k][location]", 'label' => 'Where', 'value' => $t['location'] ?? '', 'col' => 'col-md-4', 'max' => 80, 'default' => 'e.g. Nueva Ecija'])
        @include('aniSensoAdmin.landing.field', ['name' => "testimonials[items][$k][quote]", 'label' => 'Their words', 'value' => $t['quote'] ?? '', 'type' => 'textarea', 'rows' => 3, 'max' => 1200])
        @include('aniSensoAdmin.landing.field', ['name' => "testimonials[items][$k][result]", 'label' => 'The result, in numbers', 'value' => $t['result'] ?? '', 'col' => 'col-md-8', 'max' => 120,
            'default' => 'e.g. 2 fewer sprays this season', 'help' => 'Shown as a green badge under the words. Leave blank if there is no honest number.'])
        <div class="col-md-4">
            <label class="form-label" for="{{ 'tr_' . $k }}">Stars</label>
            <select class="form-select" name="testimonials[items][{{ $k }}][rating]" id="{{ 'tr_' . $k }}">
                @foreach ([5 => '★★★★★', 4 => '★★★★', 3 => '★★★', 0 => 'No stars'] as $n => $l)
                    <option value="{{ $n }}" {{ (int) ($t['rating'] ?? 5) === $n ? 'selected' : '' }}>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12">
            <label class="form-label">Their photo</label>
            @include('aniSensoAdmin.landing.picture', ['prefix' => "testimonials[items][$k]", 'field' => 'photo', 'current' => $t['photo'] ?? '', 'builtIn' => null, 'shape' => 'face', 'base' => $base,
                'hint' => 'A square photo of the person, with their consent. Without one, their initial shows.'])
        </div>
    </div>
</div>
