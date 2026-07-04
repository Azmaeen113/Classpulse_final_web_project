@extends('layouts.live')

@section('title', 'Live answer — ClassPulse')

@section('content')
<div class="container"
     data-live-student
     data-poll-state-url="{{ route('api.poll.session-state', $session) }}"
     data-submit-url="{{ route('student.live.answer', $session) }}"
     data-result-url="{{ route('student.sessions.result', $session) }}">

    <div class="text-center mb-4">
        <div class="d-flex justify-content-center align-items-center gap-2 mb-2">
            <span class="cp-live-dot"></span>
            <span class="badge badge-live">LIVE</span>
        </div>
        <div class="cp-muted fw-semibold">{{ $session->quiz->title ?? 'Quiz' }}</div>
        <div class="small cp-muted mt-1" data-question-progress></div>
        <div class="mt-3">
            <x-live-timer size="md" variant="friendly" />
        </div>
    </div>

    <div class="text-center mb-3">
        <p class="mb-0 d-none" data-answer-feedback></p>
    </div>

    <div data-state="waiting">
        <div class="cp-wait-card cp-fade-in mx-auto" style="max-width: 520px;">
            <div class="cp-wait-icon"><i class="bi bi-hourglass-split"></i></div>
            <div class="cp-empty-shapes mb-3" aria-hidden="true">
                <span></span><span></span><span></span><span></span>
            </div>
            <h1 class="h3 fw-bold">Nice! Waiting for everyone else</h1>
            <p class="cp-muted mb-0">Stay here — your teacher will send the next question any second.</p>
        </div>
    </div>

    <div data-state="submitted" class="d-none">
        <div class="cp-wait-card cp-fade-in mx-auto" style="max-width: 520px;">
            <div class="cp-wait-icon" style="background: linear-gradient(135deg, var(--cp-tile-3), var(--cp-live));">
                <i class="bi bi-check-lg"></i>
            </div>
            <h1 class="h3 fw-bold">Locked in</h1>
            <p class="cp-muted mb-0">Great job. The next question will appear automatically when everyone is ready — or when the timer ends.</p>
        </div>
    </div>

    <div data-state="answering" class="d-none">
        <div class="cp-question-live text-center mb-4">
            <h2 class="display-6 fw-bold mb-0" data-question-prompt>{{ $session->currentQuestion->prompt ?? '' }}</h2>
        </div>

        <form data-answer-form>
            <div data-options class="mb-3"></div>
            <textarea data-short-answer class="form-control form-control-lg mb-3 d-none" rows="3"
                      placeholder="Type your answer (capitalization does not matter)"
                      spellcheck="false"
                      autocomplete="off"></textarea>
            <p class="small cp-muted mt-2 mb-3 d-none" data-fill-hint>Fill in the blank — capitalization does not matter.</p>
            <button type="button" class="btn btn-cp btn-lg w-100" data-submit-answer>Lock answer</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/live-session-student.js') }}?v={{ filemtime(public_path('js/live-session-student.js')) }}"></script>
@endpush
