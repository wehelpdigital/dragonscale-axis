<div class="lp-row">
    @include('aniSensoAdmin.landing.row-head', ['label' => 'Step'])
    <div class="row g-2">
        @include('aniSensoAdmin.landing.field', ['name' => "problem[steps][$k][title]", 'label' => 'Title', 'value' => $st['title'] ?? '', 'col' => 'col-md-5', 'max' => 100])
        @include('aniSensoAdmin.landing.field', ['name' => "problem[steps][$k][text]", 'label' => 'Words', 'value' => $st['text'] ?? '', 'col' => 'col-md-7', 'max' => 300])
    </div>
</div>
