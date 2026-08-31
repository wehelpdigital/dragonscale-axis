{{-- The community's own shelf, as tags.
     Eight entries down the sidebar is a filing cabinet; the client's app puts
     its community behind one door with the rooms named along the top. Every
     community screen carries this, so wherever you are, everywhere else is
     one tap away. --}}
<style>
    .cm-shelf { display: flex; flex-wrap: wrap; gap: .4rem; margin-bottom: 1.25rem; }
    .cm-shelf a {
        border: 1px solid #e6e8ec; border-radius: 999px; padding: .4rem .9rem;
        font-size: 13px; font-weight: 500; color: #495057; background: #fff;
        display: inline-flex; align-items: center; gap: .35rem; text-decoration: none;
    }
    .cm-shelf a:hover { background: #eef2ff; border-color: #c7d2fe; color: #2c3e8c; }
    .cm-shelf a.is-on { background: #556ee6; border-color: #556ee6; color: #fff; }
    .cm-shelf .badge { font-size: 10.5px; font-weight: 600; background: #eef1f6; color: #495057; }
    .cm-shelf a.is-on .badge { background: rgba(255,255,255,.9); color: #2c3e8c; }
</style>
@php
    $cmHere = $cmHere ?? '';
    $cmRooms = [
        ['members', 'Members', 'bx-group', 'anisenso-community.members'],
        ['cofarmers', 'Co-farmers', 'bx-link', 'anisenso-community.cofarmers'],
        ['groups', 'Discussions', 'bx-conversation', 'anisenso-community.groups'],
        ['plans', 'Shared plans', 'bx-spreadsheet', 'anisenso-community.plans'],
        ['reports', 'Reports', 'bx-flag', 'anisenso-community.reports'],
        ['announcements', 'Announcements', 'bx-broadcast', 'anisenso-community.announcements'],
        ['ai', 'AI answers', 'bx-bot', 'anisenso-community.ai-answers'],
    ];
@endphp
<div class="cm-shelf">
    @foreach ($cmRooms as [$key, $label, $icon, $route])
        <a href="{{ route($route) }}" class="{{ $cmHere === $key ? 'is-on' : '' }}">
            <i class="bx {{ $icon }}"></i> {{ $label }}
            @isset($cmCounts[$key])<span class="badge">{{ $cmCounts[$key] }}</span>@endisset
        </a>
    @endforeach
</div>
