@extends('layouts.master')

@section('title') Mail Log @endsection

@section('css')
<style>
    /* The book is read by scanning, so the things you scan for — who, what
       happened, when — are the things given weight, and everything else gets
       out of the way. */
    .ml-head { display:flex; align-items:center; gap:14px; flex-wrap:wrap; margin-bottom:16px; }
    .ml-stat { display:inline-flex; flex-direction:column; padding:10px 16px; border-radius:8px;
        background:#f8f9fa; border:1px solid #eff2f7; min-width:96px; }
    .ml-stat b { font-size:20px; font-weight:700; color:#2a3042; line-height:1; }
    .ml-stat span { font-size:11px; text-transform:uppercase; letter-spacing:.4px; color:#74788d; margin-top:4px; }
    .ml-stat.is-on { background:#eef6e6; border-color:#cfe0b8; }
    .ml-to { font-weight:600; color:#2a3042; }
    .ml-sub { color:#74788d; font-size:12.5px; }
    .ml-key { font-family:monospace; font-size:11.5px; color:#74788d; }
    .ml-why { font-size:12px; color:#f46a6a; max-width:420px; }
    .ml-when { white-space:nowrap; font-size:12.5px; color:#74788d; }
    .ml-warn { border-left:3px solid #f1b44c; background:#fff9ee; padding:12px 14px; border-radius:6px;
        font-size:13px; color:#7a5b1e; margin-bottom:16px; }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') AniSenso @endslot
@slot('title') Mail Log @endslot
@endcomponent

@if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

@unless ($configured)
    <div class="ml-warn">
        <strong>RESEND_KEY is not set in this app's .env</strong> — the cron can write the morning
        emails but cannot send any of them. Everything below will sit at "Waiting".
    </div>
@endunless

<div class="card">
    <div class="card-body">
        <div class="ml-head">
            <div class="ml-stat"><b>{{ number_format($counts['all']) }}</b><span>All</span></div>
            <div class="ml-stat {{ $due ? 'is-on' : '' }}"><b>{{ number_format($due) }}</b><span>Due now</span></div>
            <div class="ml-stat"><b>{{ number_format($counts['sent']) }}</b><span>Sent</span></div>
            <div class="ml-stat"><b>{{ number_format($counts['failed']) }}</b><span>Failed</span></div>

            <div class="ms-auto d-flex gap-2 align-items-center flex-wrap">
                {{-- Nobody should have to wait until six tomorrow morning to
                     find out whether the daily email works. --}}
                <form method="POST" action="{{ route('anisenso-mail-log.run') }}" class="d-flex gap-2">
                    @csrf
                    <button class="btn btn-primary btn-sm" type="submit">
                        <i class="bx bx-play"></i> Run now
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" type="submit" name="force" value="1"
                            title="Queue every season that has the daily email switched on, whatever the hour says">
                        Run now, ignoring the hour
                    </button>
                </form>
            </div>
        </div>

        <p class="ml-sub mb-3">
            Sending from <code>{{ $from }}</code>. The cron runs every ten minutes: it queues a season's
            morning email once its hour has come round, then sends up to fifty at a time.
        </p>

        <form method="GET" action="{{ route('anisenso-mail-log.index') }}" class="row g-2 mb-3">
            <div class="col-sm-4">
                <input type="search" name="q" value="{{ $find }}" class="form-control form-control-sm"
                       placeholder="Search address, subject or template…">
            </div>
            <div class="col-sm-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Any status</option>
                    <option value="queued" @selected($status === 'queued')>Waiting</option>
                    <option value="sent" @selected($status === 'sent')>Sent</option>
                    <option value="failed" @selected($status === 'failed')>Failed</option>
                    <option value="given_up" @selected($status === 'given_up')>Given up</option>
                </select>
            </div>
            <div class="col-sm-2">
                <button class="btn btn-secondary btn-sm w-100" type="submit">Filter</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>To</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>When</th>
                        <th class="text-end">&nbsp;</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($tasks as $t)
                    <tr>
                        <td>
                            <div class="ml-to">{{ $t->toName ?: $t->toEmail }}</div>
                            @if ($t->toName)<div class="ml-sub">{{ $t->toEmail }}</div>@endif
                        </td>
                        <td>
                            <div>{{ \Illuminate\Support\Str::limit($t->subject, 62) }}</div>
                            @if ($t->templateKey)<div class="ml-key">{{ $t->templateKey }}</div>@endif
                            @if ($t->lastError)<div class="ml-why">{{ \Illuminate\Support\Str::limit($t->lastError, 160) }}</div>@endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $t->statusTone() }}">{{ $t->statusLabel() }}</span>
                            @if ($t->attempts > 1)<div class="ml-sub">{{ $t->attempts }} tries</div>@endif
                        </td>
                        <td class="ml-when">
                            {{ optional($t->sentAt ?: $t->created_at)->format('M j, g:ia') }}
                        </td>
                        <td class="text-end">
                            <a href="{{ route('anisenso-mail-log.index', ['id' => $t->id]) }}"
                               class="btn btn-sm btn-outline-secondary">Open</a>
                            @if ($t->status !== \App\Models\AsEmailTask::SENT)
                                <form method="POST" action="{{ route('anisenso-mail-log.retry') }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $t->id }}">
                                    <button class="btn btn-sm btn-outline-primary" type="submit">Send now</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">
                        Nothing in the book yet. Emails appear here the moment the app means to send one.
                    </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $tasks->links() }}</div>
    </div>
</div>
@endsection
