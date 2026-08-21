@extends('layouts.master')

@section('title') Technician's Blog @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') Community @endslot
        @slot('title') Technician's Blog @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                        <div>
                            <h4 class="card-title mb-1 text-dark">Articles</h4>
                            <p class="text-secondary mb-0">Publish guides for the community. Publishing notifies every member.</p>
                        </div>
                        <a href="{{ route('anisenso-blog.create') }}" class="btn btn-primary"><i class="bx bx-plus"></i> New article</a>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead><tr>
                                <th>Title</th><th>Author</th><th>Status</th><th>Comments</th><th>Published</th><th></th>
                            </tr></thead>
                            <tbody>
                            @forelse($posts as $post)
                                <tr data-row="{{ $post->id }}">
                                    <td class="text-dark fw-semibold">{{ $post->title }}</td>
                                    <td class="text-secondary">{{ $post->authorName ?: '—' }}</td>
                                    <td>
                                        @if($post->isPublished)<span class="badge bg-success">Published</span>
                                        @else<span class="badge bg-secondary">Draft</span>@endif
                                    </td>
                                    <td>
                                        @if($post->comments_count)
                                            <a href="{{ route('anisenso-blog.comments', $post->id) }}" class="badge bg-info text-decoration-none"
                                               title="Read and moderate the comments">{{ $post->comments_count }}</a>
                                        @else
                                            <span class="text-secondary">0</span>
                                        @endif
                                    </td>
                                    <td class="text-secondary">{{ $post->publishedAt ? $post->publishedAt->format('M j, Y') : '—' }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('anisenso-blog.edit', $post->id) }}" class="btn btn-sm btn-soft-primary"><i class="bx bx-edit"></i></a>
                                        <button type="button" class="btn btn-sm btn-soft-danger btn-del" data-id="{{ $post->id }}"><i class="bx bx-trash"></i></button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-secondary py-4">No articles yet.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $posts->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
    const CSRF = "{{ csrf_token() }}";
    document.querySelectorAll('.btn-del').forEach((b) => b.addEventListener('click', async () => {
        if (!confirm('Remove this article?')) return;
        const res = await fetch('/anisenso-blog/' + b.getAttribute('data-id'), { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' } });
        const data = await res.json();
        if (data.success) { toastr.success(data.message); document.querySelector('[data-row="' + b.getAttribute('data-id') + '"]')?.remove(); }
        else toastr.error(data.message || 'Could not remove.');
    }));
</script>
@endsection
