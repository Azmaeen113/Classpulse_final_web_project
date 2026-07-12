@extends('layouts.app')

@section('title', 'Your history — ClassPulse')

@section('header')
    <h1 class="cp-page-title">Your scores</h1>
    <p class="cp-page-sub">Past quizzes — tap View for the full breakdown.</p>
@endsection

@section('content')
    <form method="GET" class="row g-2 mb-4">
        <div class="col-md-8">
            <input type="search" name="q" value="{{ $q ?? request('q') }}" class="form-control form-control-lg" placeholder="Search quizzes...">
        </div>
        <div class="col-md-4">
            <button class="btn btn-cp w-100 btn-lg" type="submit">Search</button>
        </div>
    </form>

    @if (($sessions ?? collect())->isEmpty())
        <x-empty-state title="No history yet" message="Finish a live quiz and your scores will land here." icon="bi-trophy" />
    @else
        @foreach ($sessions as $session)
            @php
                $score = $scores[$session->id] ?? $session->score ?? $session->total_points ?? '—';
            @endphp
            <div class="cp-history-card cp-fade-in">
                <div>
                    <div class="fw-bold">{{ $session->quiz->title ?? 'Quiz' }}</div>
                    <div class="small cp-muted">
                        {{ $session->classroom->name ?? '—' }}
                        · {{ optional($session->ended_at ?? $session->started_at)->format('M j, Y') ?? '—' }}
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="text-end">
                        <div class="small cp-muted text-uppercase" style="letter-spacing:0.06em;">Score</div>
                        <div class="fw-bold fs-4" style="color: var(--cp-primary);">{{ $score }}</div>
                    </div>
                    <a href="{{ route('student.sessions.result', $session) }}" class="btn btn-cp-outline">View</a>
                </div>
            </div>
        @endforeach
        @if (method_exists($sessions, 'links'))
            <div class="mt-3">{{ $sessions->withQueryString()->links() }}</div>
        @endif
    @endif
@endsection
