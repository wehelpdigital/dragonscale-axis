@extends('layouts.master')

@section('title') Legal &amp; Info Pages @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') Content @endslot
        @slot('title') Legal &amp; Info Pages @endslot
    @endcomponent

    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <div>
                <h4 class="card-title mb-1 text-dark">Footer pages</h4>
                <p class="text-secondary mb-0">Privacy, Terms, Cookies and About — shown in the AniSystem footer.</p>
            </div>
            <form method="POST" action="{{ route('anisenso-legal.store') }}" class="d-flex gap-2">
                @csrf
                <input type="text" name="title" class="form-control" placeholder="New page title" style="min-width:200px;">
                <input type="text" name="slug" class="form-control" placeholder="slug (optional)" style="max-width:160px;">
                <button class="btn btn-primary" type="submit"><i class="bx bx-plus"></i> Add</button>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Page</th><th>Slug</th><th>Status</th><th>Updated</th><th></th></tr></thead>
                <tbody>
                @foreach($pages as $page)
                    <tr>
                        <td class="text-dark fw-semibold">{{ $page->title }}</td>
                        <td><code>/legal/{{ $page->slug }}</code></td>
                        <td>@if($page->isPublished)<span class="badge bg-success">Published</span>@else<span class="badge bg-secondary">Draft</span>@endif</td>
                        <td class="text-secondary">{{ $page->updated_at?->format('M j, Y') }}</td>
                        <td class="text-end">
                            <a href="{{ route('anisenso-legal.edit', ['id' => $page->id]) }}" class="btn btn-sm btn-soft-primary"><i class="bx bx-edit"></i> Edit</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div></div></div></div>
@endsection
