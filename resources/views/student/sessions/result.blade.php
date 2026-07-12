@extends('layouts.app')

@section('title', 'Session result — ClassPulse')

@section('content')
    <div class="cp-result-hero cp-fade-in">
        <div class="d-flex align-items-center gap-2 mb-2">
            <i class="bi bi-trophy-fill"></i>
            <span class="text-uppercase small fw-bold" style="letter-spacing:0.08em;">Results</span>
        </div>
        <h1 class="h2 fw-bold mb-1">{{ $session->quiz->title ?? $quiz->title ?? 'Quiz' }}</h1>
        <p class="mb-4" style="opacity:0.85;">{{ $session->classroom->name ?? '' }}</p>

        <div class="row g-3">
            <div class="col-md-4">
                <x-stat-card label="Score" :value="$result['score'] ?? ($score ?? 0)" icon="bi-star-fill" />
            </div>
            <div class="col-md-4">
                <x-stat-card label="Rank" :value="isset($result['rank']) || isset($rank) ? ('#' . ($result['rank'] ?? $rank)) : '—'" icon="bi-trophy" />
            </div>
            <div class="col-md-4">
                <x-stat-card label="Correct" :value="($result['correct'] ?? ($correctCount ?? 0)) . ' / ' . ($result['total'] ?? ($totalQuestions ?? 0))" icon="bi-check2-circle" />
            </div>
        </div>
    </div>

    <div class="cp-surface-flat p-4 mb-4">
        <h2 class="h5 fw-bold mb-3">Leaderboard</h2>
        @forelse ($leaderboard ?? [] as $row)
            <x-leaderboard-row
                :rank="$row['rank'] ?? $loop->iteration"
                :name="$row['name'] ?? $row['student_name'] ?? 'Student'"
                :points="$row['points'] ?? $row['total_points'] ?? 0"
                :avg-time="$row['avg_response_time_ms'] ?? null"
            />
        @empty
            <p class="cp-muted mb-0">Leaderboard unavailable.</p>
        @endforelse
    </div>

    <div class="cp-surface-flat p-4">
        <h2 class="h5 fw-bold mb-3">Your answers</h2>
        @php
            $allQuestions = ($session->quiz->questions ?? collect())->sortBy([['sort_order', 'asc'], ['id', 'asc']])->values();
            $responseMap = $responses ?? collect();
        @endphp
        @forelse ($allQuestions as $index => $q)
            @php
                $response = $responseMap->get($q->id);
                $typeLabel = match ($q->type ?? '') {
                    'mcq' => 'Multiple choice',
                    'true_false' => 'True / False',
                    'short_answer' => 'Fill in the blank',
                    default => 'Question',
                };
            @endphp
            <div class="py-3 border-bottom" style="border-color: var(--cp-border) !important;">
                <div class="fw-semibold mb-1">Q{{ $index + 1 }}. {{ $q->prompt ?? 'Question' }}</div>
                <div class="small mb-1">
                    <span class="badge badge-cp">{{ $typeLabel }}</span>
                    @if (! $response)
                        <span class="badge text-bg-secondary">No answer</span>
                    @elseif ($response->is_correct)
                        <span class="badge badge-live">Correct</span>
                    @else
                        <span class="badge" style="background: rgba(226,61,77,0.15); color: var(--cp-danger);">Incorrect</span>
                    @endif
                    @if ($response)
                        <span class="cp-muted ms-2">{{ $response->points_awarded }} pts</span>
                    @endif
                </div>
                <div class="small cp-muted">
                    @if (! $response)
                        You did not answer this question.
                    @elseif (($q->type ?? '') === 'short_answer')
                        Your answer: <code>{{ $response->short_answer_text !== null && $response->short_answer_text !== '' ? $response->short_answer_text : '—' }}</code>
                        @unless ($response->is_correct)
                            · Correct: <code>{{ $q->short_answer_expected }}</code>
                            <span class="ms-1">(case-insensitive)</span>
                        @endunless
                    @elseif ($response->selectedOption)
                        Your choice: {{ $response->selectedOption->option_text }}
                    @endif
                </div>
            </div>
        @empty
            <p class="cp-muted mb-0">No questions in this quiz.</p>
        @endforelse
    </div>

    <div class="mt-4">
        <a href="{{ route('student.history') }}" class="btn btn-cp-outline">Back to history</a>
        <a href="{{ route('student.dashboard') }}" class="btn btn-cp">Dashboard</a>
    </div>
@endsection
