@extends('layouts.master')

@section('title') {{ $bundle ? $bundle->bundleName : $source->sourceName }} · RSS Feeds @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    #rfp .story { display: flex; gap: 1rem; padding: .9rem 0; border-bottom: 1px solid var(--bs-border-color); }
    #rfp .story:last-child { border-bottom: 0; }
    #rfp .story.is-out { opacity: .5; }
    #rfp .story img, #rfp .story .noimg { width: 112px; height: 76px; border-radius: .5rem; object-fit: cover; flex: none; background: var(--bs-tertiary-bg); }
    #rfp .story .noimg { display: flex; align-items: center; justify-content: center; color: var(--bs-secondary-color); font-size: 1.4rem; }
    #rfp .story h6 { margin: 0 0 .2rem; line-height: 1.35; }
    #rfp .story h6 a { color: var(--bs-emphasis-color); }
    #rfp .story .sum { font-size: .84rem; white-space: pre-line; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
    #rfp .story.open .sum { -webkit-line-clamp: unset; }
    #rfp .section-head { display: flex; justify-content: space-between; align-items: baseline; gap: .5rem; padding: 1.1rem 0 .4rem; margin-top: .4rem; border-bottom: 2px solid var(--bs-border-color); }
    #rfp .addr { display: flex; gap: .35rem; align-items: center; }
    #rfp .addr code { flex: 1; min-width: 0; padding: .45rem .65rem; border-radius: .5rem; background: var(--bs-tertiary-bg); color: var(--bs-emphasis-color);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: .8rem; }
    @media (max-width: 575.98px) { #rfp .story img, #rfp .story .noimg { width: 72px; height: 54px; } }
</style>
@include('aniSensoAdmin.partials.dark')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') {{ $source ? 'RSS Feeds · Sources' : 'RSS Feeds · Feeds' }} @endslot
        @slot('title') {{ $bundle ? $bundle->bundleName : $source->sourceName }} @endslot
    @endcomponent

    <div id="rfp" class="row g-3">
        <div class="col-xl-8">
            <div class="card"><div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                    <div>
                        <h4 class="card-title mb-1 text-dark">{{ $bundle ? 'What the feed shows now' : 'Every story this source gave' }}</h4>
                        <p class="text-secondary small mb-0">
                            @if ($bundle && $bundle->isCompilation())
                                {{ $items->count() }} {{ \Illuminate\Support\Str::plural('story', $items->count()) }} in {{ count($groups) }} sections, in the order the feed serves them. A story two sections both show is listed in both.
                            @elseif ($bundle)
                                {{ $items->count() }} {{ \Illuminate\Support\Str::plural('story', $items->count()) }}, newest first, exactly as the RSS address serves them.
                            @else
                                The latest {{ $items->count() }}, newest first. Faded ones are left out by this source's filter; hidden ones are left out of every feed.
                            @endif
                        </p>
                    </div>
                    <a href="{{ $source ? route('rss-feeds.sources') : route('rss-feeds') }}" class="btn btn-light btn-sm"><i class="bx bx-arrow-back me-1"></i>{{ $source ? 'Sources' : 'Feeds' }}</a>
                </div>

                @if ($items->isEmpty())
                    <div class="text-center py-5">
                        <i class="bx bx-news text-secondary" style="font-size: 2.5rem;"></i>
                        <p class="text-dark mt-2 mb-1">No stories yet.</p>
                        <small class="text-secondary">{{ $bundle ? 'Its sources have nothing that passes the filters, or have not been read yet.' : 'This source has not given any story yet.' }}</small>
                    </div>
                @endif
                @foreach ($groups as $group)
                    @if ($group['name'])
                        <div class="section-head" id="sec{{ $loop->iteration }}">
                            <h5 class="mb-0 text-dark">{{ $group['name'] }} <span class="badge bg-primary-subtle text-primary ms-1">{{ $group['items']->count() }}</span></h5>
                            <a class="small" href="{{ $group['url'] }}" target="_blank" rel="noopener">its own feed <i class="bx bx-link-external"></i></a>
                        </div>
                    @endif
                    @foreach ($group['items'] as $item)
                        @php $hidden = $item->delete_status === 'deleted'; @endphp
                        <div class="story {{ ($source && (! $item->passes || $hidden)) ? 'is-out' : '' }}">
                            @if ($item->itemImage)
                                <img src="{{ $item->itemImage }}" alt="" loading="lazy" referrerpolicy="no-referrer" onerror="this.outerHTML='<div class=&quot;noimg&quot;><i class=&quot;bx bx-image&quot;></i></div>'">
                            @else
                                <div class="noimg"><i class="bx bx-news"></i></div>
                            @endif
                            <div class="flex-grow-1" style="min-width: 0;">
                                <h6><a href="{{ $item->itemLink }}" target="_blank" rel="noopener">{{ $item->itemTitle }}</a></h6>
                                <div class="small text-secondary mb-1">
                                    {{ $item->itemPublisher }}
                                    @if ($item->publishedAt) · {{ $item->publishedAt->format('M j, Y g:i A') }} ({{ $item->publishedAt->diffForHumans() }})@endif
                                    @if ($bundle && $item->relationLoaded('source')) · <span class="text-dark">{{ $item->source->sourceName }}</span>@endif
                                    @if ($source && ! $item->passes) · <span class="text-danger">left out by the filter</span>@endif
                                    @if ($hidden) · <span class="badge bg-secondary">hidden</span>@endif
                                </div>
                                @if ($item->categories)
                                    <div class="mb-1">@foreach ($item->categories as $category)<span class="badge bg-primary-subtle text-primary me-1">{{ $category }}</span>@endforeach</div>
                                @endif
                                @if ($item->itemSummary)<div class="sum text-dark">{{ $item->itemSummary }}</div>@endif
                            </div>
                            <div class="flex-none">
                                <button type="button" class="btn btn-sm {{ $hidden ? 'btn-soft-success' : 'btn-soft-secondary' }} rfp-hide" data-url="{{ route('rss-feeds.item.hide', $item->id) }}" data-hide="{{ $hidden ? 0 : 1 }}" title="{{ $hidden ? 'Show it again' : 'Hide it from every feed' }}">
                                    <i class="bx {{ $hidden ? 'bx-show' : 'bx-hide' }}"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                @endforeach
            </div></div>
        </div>

        <div class="col-xl-4">
            <div class="card"><div class="card-body">
                @if ($bundle)
                    <h5 class="card-title text-dark">RSS address</h5>
                    <div class="addr mb-3">
                        <code>{{ $bundle->outputUrl() }}</code>
                        <a class="btn btn-light btn-sm" href="{{ $bundle->outputUrl() }}" target="_blank" rel="noopener" title="Open the RSS"><i class="bx bx-link-external"></i></a>
                    </div>
                    <p class="small text-dark mb-1">Shows at most <b>{{ $bundle->maxItems }}</b> stories from the last <b>{{ $bundle->maxAgeDays }}</b> {{ \Illuminate\Support\Str::plural('day', $bundle->maxAgeDays) }}.</p>
                    @if ($bundle->isCompilation())
                        @if ($bundle->usedFor)<p class="small text-secondary mb-1"><b class="text-dark">Used for:</b> {{ $bundle->usedFor }}</p>@endif
                        <h6 class="text-dark mt-3">Sections</h6>
                        <p class="small text-secondary mb-2">Each story is tagged with its section, as an RSS category.</p>
                        <ul class="list-unstyled mb-0 small">
                            @foreach ($bundle->members as $m)
                                <li class="mb-1"><a href="{{ route('rss-feeds.preview', ['bundle' => $m->id]) }}">{{ $m->bundleName }}</a>{!! $m->isActive ? '' : ' <span class="text-secondary">· paused, left out</span>' !!}</li>
                            @endforeach
                        </ul>
                    @else
                        @if ($bundle->includeKeywords)<p class="small text-secondary mb-1"><b class="text-dark">Only:</b> {{ $bundle->includeKeywords }}</p>@endif
                        @if ($bundle->excludeKeywords)<p class="small text-secondary mb-1"><b class="text-dark">Never:</b> {{ $bundle->excludeKeywords }}</p>@endif
                        <h6 class="text-dark mt-3">Sources</h6>
                        <ul class="list-unstyled mb-0 small">
                            @foreach ($bundle->sources as $s)
                                <li class="mb-1"><a href="{{ route('rss-feeds.preview', ['source' => $s->id]) }}">{{ $s->sourceName }}</a> <span class="text-secondary">· {{ $s->typeLabel() }}{{ $s->isActive ? '' : ' · paused' }}</span></li>
                            @endforeach
                        </ul>
                    @endif
                @else
                    <h5 class="card-title text-dark">{{ $source->sourceName }}</h5>
                    <p class="small text-secondary mb-2">{{ $source->typeLabel() }} · <span class="text-break">{{ $source->target() }}</span></p>
                    @if ($source->lastFetchedAt)
                        <p class="small mb-2"><span class="text-dark">Last read {{ $source->lastFetchedAt->format('M j, g:i A') }}:</span> <span class="{{ $source->lastStatus === 'ok' ? 'text-success' : 'text-danger' }}">{{ $source->lastMessage }}</span></p>
                    @endif
                    @if ($source->includeKeywords)<p class="small text-secondary mb-1"><b class="text-dark">Only:</b> {{ $source->includeKeywords }}</p>@endif
                    @if ($source->excludeKeywords)<p class="small text-secondary mb-1"><b class="text-dark">Never:</b> {{ $source->excludeKeywords }}</p>@endif
                @endif
            </div></div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
(() => {
    toastr.options = { closeButton: true, progressBar: true, positionClass: 'toast-top-right', timeOut: 3000, escapeHtml: true };
    $('.rfp-hide').on('click', function () {
        const $btn = $(this);
        $btn.prop('disabled', true);
        $.post($btn.data('url'), { hide: $btn.data('hide') })
            .done((res) => { toastr.success(res.message); setTimeout(() => location.reload(), 600); })
            .fail((xhr) => { toastr.error(xhr.responseJSON?.message || 'That could not be saved.', 'Error!'); $btn.prop('disabled', false); });
    });
    $('#rfp .story .sum').on('click', function () { $(this).closest('.story').toggleClass('open'); });
})();
</script>
@endsection
