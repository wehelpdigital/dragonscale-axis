@extends('layouts.master')

@section('title') Blog @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Resort Guru @endslot
@slot('title') Blog @endslot
@endcomponent

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="card-title mb-0">Blog Posts</h4>
            <a href="{{ route('resort-guru-blog.create') }}" class="btn btn-primary"><i class="bx bx-plus"></i> New Post</a>
        </div>
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>ID</th><th>Title</th><th>Slug</th><th>Status</th><th>Published</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse($posts as $p)
                        <tr>
                            <td>{{ $p->id }}</td>
                            <td>{{ $p->title }}</td>
                            <td><code>{{ $p->slug }}</code></td>
                            <td><span class="badge bg-{{ $p->status === 'published' ? 'success' : 'secondary' }}">{{ $p->status }}</span></td>
                            <td>{{ $p->published_at ? \Carbon\Carbon::parse($p->published_at)->format('Y-m-d') : '—' }}</td>
                            <td>
                                <a href="{{ route('resort-guru-blog.edit', ['id' => $p->id]) }}" class="btn btn-sm btn-primary"><i class="bx bx-edit"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No posts yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $posts->links() }}
    </div>
</div>
@endsection
