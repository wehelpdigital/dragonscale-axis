<div class="lp-row">
    @include('aniSensoAdmin.landing.row-head', ['label' => 'Card'])
    <div class="row g-2">
        @include('aniSensoAdmin.landing.field', ['name' => "precision[items][$k][icon]", 'label' => 'Emoji', 'value' => $r['icon'] ?? '', 'col' => 'col-3 col-md-2', 'max' => 16, 'default' => '🌱'])
        @include('aniSensoAdmin.landing.field', ['name' => "precision[items][$k][title]", 'label' => 'Title', 'value' => $r['title'] ?? '', 'col' => 'col-9 col-md-4', 'max' => 80])
        @include('aniSensoAdmin.landing.field', ['name' => "precision[items][$k][text]", 'label' => 'Words', 'value' => $r['text'] ?? '', 'col' => 'col-md-6', 'max' => 300])
    </div>
</div>
