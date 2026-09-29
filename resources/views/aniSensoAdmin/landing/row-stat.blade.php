<div class="lp-row">
    @include('aniSensoAdmin.landing.row-head', ['label' => 'Figure'])
    <div class="row g-2">
        @include('aniSensoAdmin.landing.field', ['name' => "reality[items][$k][figure]", 'label' => 'The figure', 'value' => $st['figure'] ?? '', 'col' => 'col-4 col-md-3', 'max' => 24, 'default' => 'e.g. 27%'])
        @include('aniSensoAdmin.landing.field', ['name' => "reality[items][$k][label]", 'label' => 'What it counts', 'value' => $st['label'] ?? '', 'col' => 'col-8 col-md-9', 'max' => 120, 'default' => 'e.g. of Filipino farmers live below the poverty line'])
        @include('aniSensoAdmin.landing.field', ['name' => "reality[items][$k][text]", 'label' => 'One line of context', 'value' => $st['text'] ?? '', 'col' => 'col-md-8', 'max' => 300])
        @include('aniSensoAdmin.landing.field', ['name' => "reality[items][$k][source]", 'label' => 'Source and year', 'value' => $st['source'] ?? '', 'col' => 'col-md-4', 'max' => 120, 'default' => 'e.g. PSA, 2023'])
    </div>
</div>
