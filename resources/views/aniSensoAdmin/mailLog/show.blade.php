@extends('layouts.master')

@section('title') Mail Log @endsection

@section('css')
<style>
    .mv-facts { display:grid; grid-template-columns:8rem 1fr; gap:6px 14px; font-size:13.5px; }
    .mv-facts dt { color:#74788d; }
    .mv-facts dd { margin:0; color:#2a3042; word-break:break-word; }
    /* The message as it will arrive, inside a frame so its own styles cannot
       leak into the admin's. An iframe is the only honest preview. */
    .mv-frame { width:100%; height:70vh; border:1px solid #eff2f7; border-radius:8px; background:#f4f7f0; }
    .mv-why { border-left:3px solid #f46a6a; background:#fff5f5; padding:12px 14px; border-radius:6px;
        font-size:13px; color:#a12c2c; margin-bottom:16px; }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') AniSenso @endslot
@slot('title') One message @endslot
@endcomponent

@if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

<div class="row">
    <div class="col-lg-4">
        <div class="card"><div class="card-body">
            <h5 class="card-title mb-3">{{ $task->subject }}</h5>

            @if ($task->lastError)
                <div class="mv-why">
                    <strong>{{ $task->statusLabel() }}.</strong><br>{{ $task->lastError }}
                </div>
            @endif

            <dl class="mv-facts">
                <dt>To</dt><dd>{{ $task->toName ? $task->toName . ' — ' : '' }}{{ $task->toEmail }}</dd>
                <dt>Status</dt><dd><span class="badge bg-{{ $task->statusTone() }}">{{ $task->statusLabel() }}</span></dd>
                <dt>Template</dt><dd>{{ $task->templateKey ?: '—' }}</dd>
                <dt>Tries</dt><dd>{{ $task->attempts }}</dd>
                <dt>Written</dt><dd>{{ optional($task->created_at)->format('M j, Y g:ia') }}</dd>
                <dt>Sent</dt><dd>{{ $task->sentAt ? $task->sentAt->format('M j, Y g:ia') : '—' }}</dd>
                <dt>Due</dt><dd>{{ $task->sendAfter ? $task->sendAfter->format('M j, Y g:ia') : 'as soon as possible' }}</dd>
                <dt>About</dt><dd>{{ $task->relatedType ?: '—' }}{{ $task->relatedId ? ' #' . $task->relatedId : '' }}</dd>
                <dt>Season</dt><dd>{{ $task->croppingScheduleId ? '#' . $task->croppingScheduleId : '—' }}</dd>
                {{-- Resend's own id, so a delivery question can be taken to
                     their dashboard without guessing which email it was. --}}
                <dt>Resend id</dt><dd>{{ $task->providerId ?: '—' }}</dd>
            </dl>

            <div class="mt-3 d-flex gap-2">
                <a href="{{ route('anisenso-mail-log.index') }}" class="btn btn-secondary btn-sm">Back to the log</a>
                @if ($task->status !== \App\Models\AsEmailTask::SENT)
                    <form method="POST" action="{{ route('anisenso-mail-log.retry') }}">
                        @csrf
                        <input type="hidden" name="id" value="{{ $task->id }}">
                        <button class="btn btn-primary btn-sm" type="submit">Send now</button>
                    </form>
                @endif
            </div>
        </div></div>
    </div>

    <div class="col-lg-8">
        <div class="card"><div class="card-body">
            <h5 class="card-title mb-3">As it arrives</h5>
            <iframe class="mv-frame" srcdoc="{{ $task->bodyHtml }}" title="The message"></iframe>
        </div></div>
    </div>
</div>
@endsection
