@extends('layouts.master')

@section('title') Ani-Senso — App Reviews @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') App Reviews @endslot
        @slot('title') What growers think of AniSystem @endslot
    @endcomponent

    <div class="row">
        {{-- The shape of the feedback, not just its average: four fives and a
             one is a different story from five fours with the same mean. --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    <p class="text-muted mb-1">Average rating</p>
                    <h1 class="display-4 fw-bold mb-0">{{ $avg !== null ? number_format($avg, 2) : '—' }}</h1>
                    <div class="text-warning fs-4 mb-2">
                        @for ($i = 1; $i <= 5; $i++)
                            <i class="mdi mdi-star{{ $avg !== null && $i <= round($avg) ? '' : '-outline' }}"></i>
                        @endfor
                    </div>
                    <p class="text-muted mb-0">
                        {{ $total }} {{ \Illuminate\Support\Str::plural('rating', $total) }}
                        @if ($dismissed) · {{ $dismissed }} asked, not answered @endif
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">How they split</h5>
                    @for ($star = 5; $star >= 1; $star--)
                        @php
                            $n = (int) ($counts[$star] ?? 0);
                            $pct = $total ? round($n / $total * 100) : 0;
                        @endphp
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <a href="{{ route('anisenso-reviews.index', ['stars' => $star]) }}" class="text-nowrap small text-muted" style="width:3.2rem">
                                {{ $star }} <i class="mdi mdi-star text-warning"></i>
                            </a>
                            <div class="progress flex-grow-1" style="height:.6rem">
                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $pct }}%"
                                     aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <span class="small text-muted text-end" style="width:3rem">{{ $n }}</span>
                        </div>
                    @endfor
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <ul class="nav nav-pills gap-1">
                            <li class="nav-item">
                                <a class="nav-link {{ ! $stars && ! $onlyWritten ? 'active' : '' }}"
                                   href="{{ route('anisenso-reviews.index') }}">Everything</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $onlyWritten ? 'active' : '' }}"
                                   href="{{ route('anisenso-reviews.index', ['written' => 1]) }}">With words</a>
                            </li>
                            @for ($s = 5; $s >= 1; $s--)
                                <li class="nav-item">
                                    <a class="nav-link {{ $stars === $s ? 'active' : '' }}"
                                       href="{{ route('anisenso-reviews.index', ['stars' => $s]) }}">{{ $s }}★</a>
                                </li>
                            @endfor
                        </ul>
                    </div>

                    @forelse ($reviews as $r)
                        <div class="border rounded p-3 mb-2">
                            <div class="d-flex align-items-start justify-content-between gap-2">
                                <div class="min-w-0">
                                    <div class="text-warning">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <i class="mdi mdi-star{{ $i <= $r->rating ? '' : '-outline' }}"></i>
                                        @endfor
                                    </div>
                                    <p class="mb-0 fw-semibold">{{ $r->name ?: ($r->email ?? 'A grower') }}</p>
                                    <p class="text-muted small mb-0">{{ $r->email }}</p>
                                </div>
                                <div class="text-end text-muted small text-nowrap">
                                    {{ \Carbon\Carbon::parse($r->updated_at)->format('M j, Y') }}<br>
                                    <span class="badge bg-light text-dark">{{ $r->device ?: 'unknown device' }}</span>
                                </div>
                            </div>
                            @if (filled($r->review))
                                <p class="mb-0 mt-2">{{ $r->review }}</p>
                            @else
                                <p class="mb-0 mt-2 text-muted fst-italic">Rated, no words.</p>
                            @endif
                        </div>
                    @empty
                        <div class="text-center text-muted py-5">
                            <i class="mdi mdi-star-outline display-5 d-block mb-2"></i>
                            Nothing here yet. The app asks each grower once, a few days after they sign up.
                        </div>
                    @endforelse

                    <div class="mt-3">{{ $reviews->links() }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
