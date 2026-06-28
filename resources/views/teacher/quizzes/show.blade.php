@extends('layouts.app')

@section('title', $quiz->title . ' — ClassPulse')

@section('header')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h1 class="cp-page-title">{{ $quiz->title }}</h1>
            <p class="cp-page-sub mb-0">
                {{ $quiz->classroom->name ?? 'Classroom' }}
                @if ($quiz->is_published)
                    · <span class="badge badge-live">Published</span>
                @else
                    · <span class="badge text-bg-secondary">Draft</span>
                @endif
                · Default time: {{ $quiz->default_time_limit_seconds ?? 30 }}s
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('teacher.dashboard') }}" class="btn btn-cp-outline">
                <i class="bi bi-house-door me-1"></i> Home
            </a>
            @if ($quiz->is_published)
                <form method="POST" action="{{ route('teacher.quizzes.publish', $quiz) }}">
                    @csrf
                    <input type="hidden" name="unpublish" value="1">
                    <button type="submit" class="btn btn-cp-outline">Unpublish</button>
                </form>
            @else
                <form method="POST" action="{{ route('teacher.quizzes.publish', $quiz) }}">
                    @csrf
                    <button type="submit" class="btn btn-cp" @disabled(($quiz->questions_count ?? $quiz->questions->count() ?? 0) < 1)>
                        <i class="bi bi-check2-circle me-1"></i> Publish all questions
                    </button>
                </form>
            @endif
            @if (Route::has('teacher.quizzes.edit'))
                <a href="{{ route('teacher.quizzes.edit', $quiz) }}" class="btn btn-cp-outline">Edit quiz</a>
            @endif
            @if (Route::has('teacher.questions.create'))
                <a href="{{ route('teacher.questions.create', $quiz) }}" class="btn btn-cp-outline">Add question</a>
            @endif
            @if (Route::has('teacher.questions.ai'))
                <a href="{{ route('teacher.questions.ai', $quiz) }}" class="btn btn-cp">
                    <i class="bi bi-stars me-1"></i> AI Question Generation
                </a>
            @endif
            @if (Route::has('teacher.live.start'))
                <form method="POST" action="{{ route('teacher.live.start', $quiz) }}">
                    @csrf
                    <button type="submit" class="btn btn-cp-live" @disabled(($quiz->questions_count ?? $quiz->questions->count() ?? 0) < 1)>
                        <span class="cp-live-dot me-1"></span> Start live session
                    </button>
                </form>
            @endif
        </div>
    </div>
@endsection

@section('content')
    @if ($quiz->description)
        <p class="cp-muted mb-4">{{ $quiz->description }}</p>
    @endif

    @php $questions = ($questions ?? $quiz->questions ?? collect())->sortBy([['sort_order', 'asc'], ['id', 'asc']])->values(); @endphp

    @if ($questions->isNotEmpty() && ! $quiz->is_published)
        <div class="alert alert-warning border-0 mb-4" role="status">
            <strong>Draft quiz.</strong>
            You have {{ $questions->count() }} question(s). Click
            <em>Publish all questions</em> above (or just Start live — that publishes them automatically) so students can play every question in order.
        </div>
    @endif

    @if ($questions->isEmpty())
        <x-empty-state title="No questions" message="Add questions manually or generate a full set from a topic with AI. Both use MCQ (4 choices), True/False, and Fill in the blank." icon="bi-question-circle">
            <div class="d-flex flex-wrap gap-2 justify-content-center">
                @if (Route::has('teacher.questions.ai'))
                    <a href="{{ route('teacher.questions.ai', $quiz) }}" class="btn btn-cp">
                        <i class="bi bi-stars me-1"></i> AI Question Generation
                    </a>
                @endif
                @if (Route::has('teacher.questions.create'))
                    <a href="{{ route('teacher.questions.create', $quiz) }}" class="btn btn-cp-outline">Add manually</a>
                @endif
            </div>
        </x-empty-state>
    @else
        <form method="POST" action="{{ route('teacher.questions.timings', $quiz) }}" class="cp-surface-flat p-3 p-md-4 mb-4">
            @csrf
            <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
                <div>
                    <h2 class="h5 fw-bold mb-1">Question times</h2>
                    <p class="small cp-muted mb-0">Set a different duration per question, or apply one time to every question.</p>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-end">
                    <div>
                        <label class="form-label small mb-1" for="default_time_limit_seconds">Quiz default (new questions)</label>
                        <input type="number" min="5" max="600" class="form-control" style="width:7rem"
                               id="default_time_limit_seconds" name="default_time_limit_seconds"
                               value="{{ old('default_time_limit_seconds', $quiz->default_time_limit_seconds ?? 30) }}">
                    </div>
                    <div>
                        <label class="form-label small mb-1" for="apply_all_seconds">Apply to all</label>
                        <input type="number" min="5" max="600" class="form-control" style="width:7rem"
                               id="apply_all_seconds" name="apply_all_seconds" placeholder="e.g. 45">
                    </div>
                    <button type="submit" class="btn btn-cp">Save times</button>
                </div>
            </div>
            <p class="small cp-muted mt-2 mb-0">Tip: leave “Apply to all” empty to save only the per-question times below.</p>
        </form>

        <form method="POST" action="{{ route('teacher.questions.timings', $quiz) }}" id="per-question-times">
            @csrf
            <input type="hidden" name="default_time_limit_seconds" value="{{ $quiz->default_time_limit_seconds ?? 30 }}">
            <div class="vstack gap-3">
                @foreach ($questions as $index => $question)
                    <div class="cp-surface-flat p-3 p-md-4">
                        <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                            <div class="fw-semibold">
                                Q{{ $index + 1 }} ·
                                {{ match ($question->type) {
                                    'mcq' => 'Multiple choice',
                                    'true_false' => 'True / False',
                                    'short_answer' => 'Fill in the blank',
                                    default => $question->type,
                                } }}
                            </div>
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <span class="badge text-bg-secondary">{{ $question->points }} pts</span>
                                <div class="input-group input-group-sm" style="width: 8.5rem;">
                                    <input type="number" min="5" max="600" class="form-control"
                                           name="timings[{{ $question->id }}]"
                                           value="{{ old('timings.'.$question->id, $question->time_limit_seconds) }}"
                                           aria-label="Time for question {{ $index + 1 }}">
                                    <span class="input-group-text">sec</span>
                                </div>
                                @if (Route::has('teacher.questions.edit'))
                                    <a href="{{ route('teacher.questions.edit', [$quiz, $question]) }}" class="btn btn-sm btn-cp-outline">Edit</a>
                                @endif
                                @if (Route::has('teacher.questions.destroy'))
                                    <button type="submit" form="delete-q-{{ $question->id }}" class="btn btn-sm btn-cp-danger"
                                            onclick="return confirm('Delete this question?');">Delete</button>
                                @endif
                            </div>
                        </div>
                        <div>{{ $question->prompt }}</div>
                        @if ($question->type === 'short_answer')
                            <div class="mt-2 small cp-muted">
                                Correct answer (case-insensitive): <code>{{ $question->short_answer_expected }}</code>
                            </div>
                        @elseif ($question->relationLoaded('options') || isset($question->options))
                            <ul class="mt-2 mb-0 cp-muted">
                                @foreach ($question->options as $option)
                                    <li>
                                        {{ $option->option_text }}
                                        @if ($option->is_correct)
                                            <span class="badge badge-live ms-1">Correct</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-cp">Save question times</button>
            </div>
        </form>

        @foreach ($questions as $question)
            <form id="delete-q-{{ $question->id }}" method="POST" action="{{ route('teacher.questions.destroy', [$quiz, $question]) }}" class="d-none">
                @csrf
                @method('DELETE')
            </form>
        @endforeach
    @endif
@endsection
