@extends('layouts.live')

@section('title', 'Live control — ClassPulse')

@section('content')
<div class="container-fluid cp-control-room"
     data-live-teacher
     data-session-id="{{ $session->id }}"
     data-poll-state-url="{{ route('api.poll.session-state', $session) }}"
     data-poll-counter-url="{{ route('api.poll.response-counter', $session) }}"
     data-poll-distribution-url="{{ route('api.poll.answer-distribution', $session) }}"
     data-poll-leaderboard-url="{{ route('api.poll.leaderboard', $session) }}">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="cp-live-dot"></span>
                <span class="badge badge-live" data-session-status>{{ strtoupper($session->status) }}</span>
                <span class="cp-sync-hint">Live sync</span>
            </div>
            <h1 class="h3 mb-0">{{ $session->quiz->title ?? 'Live session' }}</h1>
            <div class="cp-muted">{{ $session->classroom->name ?? '' }} · Session control room</div>
            <div class="small mt-1" data-question-progress>
                @php
                    $ordered = $session->quiz?->questions?->sortBy([['sort_order','asc'],['id','asc']])->values() ?? collect();
                    $idx = $ordered->search(fn ($q) => (int) $q->id === (int) $session->current_question_id);
                    $num = $idx === false ? '—' : ($idx + 1);
                @endphp
                Question {{ $num }} of {{ $ordered->count() }}
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('teacher.dashboard') }}" class="btn btn-cp-outline">
                <i class="bi bi-house-door me-1"></i> Home
            </a>
            <a href="{{ route('teacher.live.leaderboard', $session) }}" class="btn btn-cp-outline" target="_blank" rel="noopener">Projector board</a>
            <form method="POST" action="{{ route('teacher.live.pause', $session) }}">@csrf<button class="btn btn-cp-outline" type="submit">Pause</button></form>
            <form method="POST" action="{{ route('teacher.live.resume', $session) }}">@csrf<button class="btn btn-cp-outline" type="submit">Resume</button></form>
            <form method="POST" action="{{ route('teacher.live.reveal', $session) }}">@csrf<button class="btn btn-cp-outline" type="submit">Reveal</button></form>
            <form method="POST" action="{{ route('teacher.live.next', $session) }}">@csrf<button class="btn btn-cp" type="submit" title="Or wait — the quiz auto-advances when time is up or everyone answers">Next</button></form>
            <form method="POST" action="{{ route('teacher.live.skip', $session) }}">@csrf<button class="btn btn-cp-outline" type="submit">Skip</button></form>
            <form method="POST" action="{{ route('teacher.live.end', $session) }}" onsubmit="return confirm('End this live session?');">
                @csrf
                <button class="btn btn-cp-danger" type="submit">End</button>
            </form>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="cp-surface p-4 mb-4 text-center">
                <div class="cp-eyebrow mb-2">Timer</div>
                <div class="cp-timer-glow">
                    <x-live-timer size="lg" />
                </div>
                <p class="small cp-muted mt-2 mb-3">
                    Base {{ $session->currentQuestion->time_limit_seconds ?? 0 }}s
                    @if (($session->time_bonus_seconds ?? 0) > 0)
                        · Extended +{{ $session->time_bonus_seconds }}s
                    @elseif (($session->time_bonus_seconds ?? 0) < 0)
                        · Shortened {{ $session->time_bonus_seconds }}s
                    @endif
                    · Total {{ $session->effectiveTimeLimitSeconds() }}s
                </p>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <form method="POST" action="{{ route('teacher.live.extend', $session) }}">
                        @csrf
                        <input type="hidden" name="seconds" value="15">
                        <button class="btn btn-sm btn-cp-outline" type="submit">+15s</button>
                    </form>
                    <form method="POST" action="{{ route('teacher.live.extend', $session) }}">
                        @csrf
                        <input type="hidden" name="seconds" value="30">
                        <button class="btn btn-sm btn-cp-outline" type="submit">+30s</button>
                    </form>
                    <form method="POST" action="{{ route('teacher.live.extend', $session) }}">
                        @csrf
                        <input type="hidden" name="seconds" value="60">
                        <button class="btn btn-sm btn-cp-outline" type="submit">+60s</button>
                    </form>
                </div>
                <form method="POST" action="{{ route('teacher.live.set-time', $session) }}" class="row g-2 justify-content-center align-items-end mt-3">
                    @csrf
                    <div class="col-auto">
                        <label class="form-label small mb-1" for="set_seconds">Set total seconds</label>
                        <input id="set_seconds" type="number" min="5" max="600" name="seconds" class="form-control form-control-sm"
                               value="{{ $session->effectiveTimeLimitSeconds() }}" style="width:7rem;">
                    </div>
                    <div class="col-auto">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="save_to_question" value="1" id="save_to_question">
                            <label class="form-check-label small" for="save_to_question">Also save on question</label>
                        </div>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-sm btn-cp" type="submit">Set time</button>
                    </div>
                </form>
            </div>

            <x-question-card :question="$session->currentQuestion" class="mb-4" />

            <div class="cp-surface-flat p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0">Answer distribution</h2>
                    <div class="cp-muted">
                        <span data-answered-count>0</span> / <span data-total-count>0</span> answered
                    </div>
                </div>
                <div data-distribution></div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="cp-surface-flat p-0 overflow-hidden">
                <div class="p-3 border-bottom border-secondary border-opacity-25 d-flex justify-content-between">
                    <h2 class="h5 mb-0">Leaderboard</h2>
                    <span class="cp-muted small">Live</span>
                </div>
                <div data-leaderboard>
                    <div class="cp-muted p-3">Waiting for scores...</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/live-session-teacher.js') }}"></script>
@endpush
