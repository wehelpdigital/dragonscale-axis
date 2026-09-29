<div class="lp-row">
    @include('aniSensoAdmin.landing.row-head', ['label' => 'Tile'])
    <div class="row g-2">
        @include('aniSensoAdmin.landing.field', ['name' => "more[items][$k][group]", 'label' => 'Group', 'value' => $t['group'] ?? '', 'col' => 'col-md-3', 'max' => 40, 'default' => 'Plan, Grow, Manage…'])
        @include('aniSensoAdmin.landing.field', ['name' => "more[items][$k][icon]", 'label' => 'Emoji', 'value' => $t['icon'] ?? '', 'col' => 'col-3 col-md-2', 'max' => 16, 'default' => '🌱'])
        @include('aniSensoAdmin.landing.field', ['name' => "more[items][$k][title]", 'label' => 'Title', 'value' => $t['title'] ?? '', 'col' => 'col-9 col-md-7', 'max' => 80])
        @include('aniSensoAdmin.landing.field', ['name' => "more[items][$k][text]", 'label' => 'Words', 'value' => $t['text'] ?? '', 'max' => 200])
    </div>
</div>
