@extends('layouts.master')

@section('title') How-to Guides @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') Content @endslot
        @slot('title') How-to Guides @endslot
    @endcomponent

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
        <h4 class="card-title mb-1 text-dark">One guide per module, per device</h4>
        <p class="text-secondary">
            These are what the question mark opens inside AniSystem. The same module is driven differently on a
            phone and at a desk, so each gets its own page — a reader on a device with nothing written sees the
            nearest page that exists rather than an empty screen.
        </p>

        <div class="table-responsive mt-3">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Module</th>
                        @foreach (\App\Models\AsTutorialPage::DEVICES as $d)
                            <th class="text-center">{{ \App\Models\AsTutorialPage::DEVICE_LABELS[$d] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach (\App\Models\AsTutorialPage::MODULES as $key => $label)
                        <tr>
                            <td class="fw-semibold text-dark">{{ $label }}</td>
                            @foreach (\App\Models\AsTutorialPage::DEVICES as $d)
                                @php
                                    $page = $rows[$key . '|' . $d] ?? null;
                                    $count = $page ? count($page->blocks ?? []) : 0;
                                @endphp
                                <td class="text-center">
                                    <a href="{{ route('anisenso-help-guides.edit', ['module' => $key, 'device' => $d]) }}"
                                       class="btn btn-sm {{ $page ? 'btn-soft-success' : 'btn-soft-secondary' }}">
                                        {{ $page ? $count . ' block' . ($count === 1 ? '' : 's') : 'Write it' }}
                                    </a>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div></div></div></div>
@endsection
