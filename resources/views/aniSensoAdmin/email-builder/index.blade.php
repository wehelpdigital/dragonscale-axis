@extends('layouts.master')

@section('title') Email Layouts @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') Mail @endslot
        @slot('title') Email Layouts @endslot
    @endcomponent

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
        <h4 class="card-title mb-1 text-dark">Build an email by dragging blocks</h4>
        <p class="text-secondary">
            The same templates as Mail Settings, edited as blocks instead of raw HTML. Saving here rewrites the
            HTML that actually gets sent, so a layout can be rearranged later instead of becoming hand-edited
            markup forever. Templates never opened here keep whatever HTML they already have.
        </p>

        <div class="table-responsive mt-3">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Template</th><th>Subject</th><th class="text-center">Built with blocks</th><th></th></tr></thead>
                <tbody>
                    @foreach ($templates as $t)
                        <tr>
                            <td class="fw-semibold text-dark">{{ $t->templateName }}</td>
                            <td class="text-secondary">{{ \Illuminate\Support\Str::limit($t->subject, 60) }}</td>
                            <td class="text-center">
                                @if (is_array($t->blocks) && count($t->blocks))
                                    <span class="badge bg-success">{{ count($t->blocks) }} blocks</span>
                                @else
                                    <span class="badge bg-secondary">Raw HTML</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('anisenso-email-builder.index', ['id' => $t->id]) }}" class="btn btn-sm btn-soft-primary">
                                    <i class="bx bx-edit"></i> Open builder
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div></div></div></div>
@endsection
