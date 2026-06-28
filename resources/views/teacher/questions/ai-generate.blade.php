@extends('layouts.app')

@section('title', 'AI Question Generation — ClassPulse')

@section('header')
    <h1 class="cp-page-title">AI Question Generation</h1>
    <p class="cp-page-sub mb-0">
        Quiz: <strong>{{ $quiz->title }}</strong>
        · Tell ClassPulse a topic and OpenAI drafts the questions for you.
    </p>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="cp-surface p-4 p-md-5">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge badge-live">AI</span>
                    <span class="cp-muted small text-uppercase" style="letter-spacing:0.08em;">OpenAI-powered</span>
                </div>

                <p class="cp-muted mb-4">
                    Enter a classroom topic (for example: <em>Kepler&rsquo;s laws</em> or <em>Photosynthesis</em>).
                    ClassPulse generates the same question types as manual entry:
                    <strong>Multiple choice (4 options)</strong>, <strong>True / False</strong>, and
                    <strong>Fill in the blank</strong> (case-insensitive scoring). Edit anything before going live.
                </p>

                <form method="POST" action="{{ route('teacher.questions.ai.store', $quiz) }}" id="ai-generate-form">
                    @csrf

                    <div class="mb-3">
                        <label for="topic" class="form-label">Topic</label>
                        <input type="text" name="topic" id="topic" value="{{ old('topic') }}"
                               class="form-control form-control-lg @error('topic') is-invalid @enderror"
                               placeholder="e.g. Gravitational force on Mars" required maxlength="200" autofocus>
                        @error('topic')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label for="count" class="form-label">How many questions?</label>
                            <select name="count" id="count" class="form-select">
                                @foreach ([3, 5, 7, 10, 12, 15] as $n)
                                    <option value="{{ $n }}" @selected((int) old('count', 5) === $n)>{{ $n }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="difficulty" class="form-label">Difficulty</label>
                            <select name="difficulty" id="difficulty" class="form-select">
                                @foreach (['easy' => 'Easy', 'medium' => 'Medium', 'hard' => 'Hard'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('difficulty', 'medium') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="time_limit_seconds" class="form-label">Time per question (sec)</label>
                            <input type="number" min="5" max="600" name="time_limit_seconds" id="time_limit_seconds"
                                   class="form-control"
                                   value="{{ old('time_limit_seconds', $quiz->default_time_limit_seconds ?? 30) }}">
                            <p class="small cp-muted mb-0 mt-1">Edit each question’s time later on the quiz page.</p>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-cp btn-lg" id="ai-generate-submit">
                            <i class="bi bi-stars me-1"></i> Generate questions
                        </button>
                        <a href="{{ route('teacher.quizzes.show', $quiz) }}" class="btn btn-cp-outline btn-lg">Cancel</a>
                    </div>

                    <p class="small cp-muted mt-3 mb-0" id="ai-generate-hint">
                        Generation usually takes a few seconds. Stay on this page.
                    </p>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('ai-generate-form');
    var btn = document.getElementById('ai-generate-submit');
    var hint = document.getElementById('ai-generate-hint');
    if (!form || !btn) return;
    form.addEventListener('submit', function () {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Generating…';
        if (hint) hint.textContent = 'Talking to OpenAI — this can take up to a minute for larger sets.';
    });
})();
</script>
@endpush
