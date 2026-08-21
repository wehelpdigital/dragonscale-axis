@extends('layouts.master')

@section('title') Article comments @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') Technician's Blog @endslot
        @slot('title') Comments @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                        <div>
                            <h4 class="card-title mb-1 text-dark">{{ $post->title }}</h4>
                            <p class="text-secondary mb-0">
                                {{ $comments->total() }} {{ \Illuminate\Support\Str::plural('comment', $comments->total()) }}
                                — what members said about this article.
                            </p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('anisenso-blog.edit', $post->id) }}" class="btn btn-soft-primary"><i class="bx bx-edit"></i> Edit article</a>
                            <a href="{{ route('anisenso-blog.index') }}" class="btn btn-light"><i class="bx bx-arrow-back"></i> All articles</a>
                        </div>
                    </div>

                    @forelse($comments as $comment)
                        <div class="d-flex gap-3 py-3 border-bottom" data-comment="{{ $comment->id }}">
                            <div class="flex-grow-1">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                    <span class="fw-semibold text-dark">{{ optional($comment->author)->fullName ?: 'Member #' . $comment->userId }}</span>
                                    <span class="text-secondary small">{{ optional($comment->created_at)->format('M j, Y g:i A') }}</span>
                                </div>
                                <div class="text-dark">{{ $comment->body }}</div>
                                @if($comment->imagePath)
                                    <div class="text-secondary small mt-1"><i class="bx bx-image me-1"></i>Photo attached</div>
                                @endif
                            </div>
                            <div>
                                <button type="button" class="btn btn-sm btn-soft-danger btn-del-comment" data-id="{{ $comment->id }}" title="Remove this comment">
                                    <i class="bx bx-trash"></i>
                                </button>
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-secondary py-4 mb-0">Nobody has commented on this article yet.</p>
                    @endforelse

                    <div class="mt-3">{{ $comments->links('pagination::bootstrap-4') }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
    const CSRF = "{{ csrf_token() }}";
    document.querySelectorAll('.btn-del-comment').forEach((b) => b.addEventListener('click', async () => {
        if (!confirm('Remove this comment? The member will no longer see it on the article.')) return;
        const id = b.getAttribute('data-id');
        const res = await fetch('/anisenso-blog-comments/' + id, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
        });
        const data = await res.json();
        if (data.success) {
            toastr.success(data.message);
            document.querySelector('[data-comment="' + id + '"]')?.remove();
        } else {
            toastr.error(data.message || 'Could not remove the comment.');
        }
    }));
</script>
@endsection
