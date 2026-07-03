@extends('layouts.live')

@section('title', 'Leaderboard — ClassPulse')

@section('content')
@php $sessionModel = $session ?? $quizSession ?? null; @endphp

<div class="container-fluid"
     data-live-teacher
     data-session-id="{{ $sessionModel->id ?? '' }}"
     @if ($sessionModel)
         data-poll-leaderboard-url="{{ route('api.poll.leaderboard', $sessionModel) }}"
         data-poll-state-url="{{ route('api.poll.session-state', $sessionModel) }}"
     @endif>

    <div class="text-center mb-4">
        <div class="d-flex justify-content-center align-items-center gap-2 mb-2">
            <span class="cp-live-dot"></span>
            <span class="badge badge-live">LIVE LEADERBOARD</span>
        </div>
        <h1 class="display-5 fw-bold mb-1">{{ $sessionModel->quiz->title ?? 'ClassPulse' }}</h1>
        <div class="cp-muted fs-4">{{ $sessionModel->classroom->name ?? '' }}</div>
    </div>

    <div class="row justify-content-center mb-4">
        <div class="col-auto">
            <x-live-timer size="lg" />
        </div>
    </div>

    <div class="cp-surface cp-leaderboard-projector mx-auto" style="max-width: 960px;" data-leaderboard>
        <div class="cp-muted p-5 text-center fs-3">Waiting for scores...</div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/live-session-teacher.js') }}"></script>
@endpush
