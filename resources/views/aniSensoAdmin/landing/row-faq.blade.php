<div class="lp-row">
    @include('aniSensoAdmin.landing.row-head', ['label' => 'Question'])
    <div class="row g-2">
        @include('aniSensoAdmin.landing.field', ['name' => "faq[items][$k][q]", 'label' => 'Question', 'value' => $qa['q'] ?? '', 'max' => 200])
        @include('aniSensoAdmin.landing.field', ['name' => "faq[items][$k][a]", 'label' => 'Answer', 'value' => $qa['a'] ?? '', 'type' => 'textarea', 'rows' => 3, 'max' => 1500])
    </div>
</div>
