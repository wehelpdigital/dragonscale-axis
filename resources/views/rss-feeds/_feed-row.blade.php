{{-- One feed made from sources, in the Feeds list: on its own, or as section $sectionNo of feed $parentId above it.
     $uid keeps its collapse id unique when the same feed is a section of two feeds.
     Every cell's content sits in .rf-cell > .rf-cell-in: a table row cannot animate its height,
     but a grid row inside each cell can, which is how a feed's sections fold away. --}}
<tr class="{{ $b->isActive ? '' : 'is-off' }} {{ $sectionNo ? 'rf-section' : '' }}" @if ($sectionNo) data-parent="{{ $parentId }}" style="--i: {{ $sectionNo }}" @endif>
    <td><div class="rf-cell"><div class="rf-cell-in">
        @if ($sectionNo)<div class="rf-section-label">Section {{ $sectionNo }}</div>@endif
        <div class="fw-semibold text-dark">{{ $b->bundleName }}</div>
        @if ($b->bundleDescription)<div class="small text-secondary">{{ $b->bundleDescription }}</div>@endif
        <div class="small mt-1">
            <a class="text-secondary" data-bs-toggle="collapse" href="#rfSrc{{ $uid }}" role="button" aria-expanded="false">{{ $b->sources->count() }} {{ \Illuminate\Support\Str::plural('source', $b->sources->count()) }} <i class="bx bx-chevron-down"></i></a>
            @if ($b->includeKeywords || $b->excludeKeywords)<span class="text-secondary"> · keyword filter</span>@endif
        </div>
        <div class="collapse" id="rfSrc{{ $uid }}">
            <div class="mt-1">@foreach ($b->sources as $s)<span class="chip">{{ $s->sourceName }}</span>@endforeach</div>
            @if ($b->includeKeywords || $b->excludeKeywords)
                <div class="kw mt-1">
                    @if ($b->includeKeywords)<div><b>Only:</b> {{ \Illuminate\Support\Str::limit($b->includeKeywords, 160) }}</div>@endif
                    @if ($b->excludeKeywords)<div><b>Never:</b> {{ \Illuminate\Support\Str::limit($b->excludeKeywords, 160) }}</div>@endif
                </div>
            @endif
        </div>
    </div></div></td>
    <td class="text-center"><div class="rf-cell"><div class="rf-cell-in">
        <span class="text-dark">{{ $b->shownCount }}</span>
        <div class="small text-secondary">of {{ $b->maxItems }}, {{ $b->maxAgeDays }} {{ \Illuminate\Support\Str::plural('day', $b->maxAgeDays) }}</div>
        @if ($b->newestAt)<div class="small text-secondary">newest {{ $b->newestAt->diffForHumans() }}</div>@endif
    </div></div></td>
    <td><div class="rf-cell"><div class="rf-cell-in">
        <div class="addr">
            <code title="{{ $b->outputUrl() }}">{{ $b->outputUrl() }}</code>
            <button type="button" class="btn btn-light btn-sm rf-copy" data-url="{{ $b->outputUrl() }}" title="Copy the address"><i class="bx bx-copy"></i></button>
            <a class="btn btn-light btn-sm" href="{{ $b->outputUrl() }}" target="_blank" rel="noopener" title="Open the feed"><i class="bx bx-link-external"></i></a>
        </div>
    </div></div></td>
    <td class="text-center"><div class="rf-cell"><div class="rf-cell-in">
        <div class="form-check form-switch d-inline-block m-0">
            <input class="form-check-input rf-toggle" type="checkbox" data-url="{{ route('rss-feeds.bundle.toggle', $b->id) }}" {{ $b->isActive ? 'checked' : '' }} title="Serve this feed">
        </div>
    </div></div></td>
    <td class="text-end actions"><div class="rf-cell"><div class="rf-cell-in">
        <a class="btn btn-soft-primary btn-sm" href="{{ route('rss-feeds.preview', ['bundle' => $b->id]) }}" title="See the stories"><i class="bx bx-show"></i></a>
        <button type="button" class="btn btn-soft-success btn-sm rf-refresh" data-url="{{ route('rss-feeds.bundle.refresh', $b->id) }}" title="Read its sources now"><i class="bx bx-refresh"></i></button>
        <button type="button" class="btn btn-soft-secondary btn-sm rf-edit-bundle" data-id="{{ $b->id }}" title="Edit"><i class="bx bx-edit"></i></button>
        <button type="button" class="btn btn-soft-danger btn-sm rf-delete" data-url="{{ route('rss-feeds.bundle.destroy', $b->id) }}" data-name="{{ $b->bundleName }}" data-what="feed" title="Delete"><i class="bx bx-trash"></i></button>
    </div></div></td>
</tr>
