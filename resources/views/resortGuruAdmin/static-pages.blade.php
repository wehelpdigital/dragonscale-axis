@extends('layouts.master')

@section('title') Static Pages @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Resort Guru @endslot
@slot('title') Static Pages @endslot
@endcomponent

<div class="card">
    <div class="card-body">
        <h4 class="card-title mb-3">Static Pages</h4>
        <p class="text-muted">About, Contact, Terms, Privacy etc. Seed these via the frontend seeder; edit content here.</p>
        @if($pages->isEmpty())
            <div class="alert alert-warning">No static pages found. Run <code>php artisan db:seed --class=RgStaticPagesSeeder</code> from the frontend project to seed the defaults.</div>
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Slug</th><th>Title</th><th>Published</th><th>Actions</th></tr></thead>
                    <tbody>
                        @foreach($pages as $p)
                            <tr>
                                <td><code>/{{ $p->slug }}</code></td>
                                <td>{{ $p->title }}</td>
                                <td><span class="badge bg-{{ $p->is_published ? 'success' : 'secondary' }}">{{ $p->is_published ? 'Yes' : 'No' }}</span></td>
                                <td><a href="{{ route('resort-guru-static.edit', ['id' => $p->id]) }}" class="btn btn-sm btn-primary"><i class="bx bx-edit"></i> Edit</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
