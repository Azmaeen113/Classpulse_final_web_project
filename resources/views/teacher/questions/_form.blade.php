@php
    $q = $question ?? null;
    $type = old('type', $q->type ?? 'mcq');
    $storedOptions = $q?->options?->sortBy('sort_order')->values() ?? collect();
    $defaultMcq = [
        ['option_text' => '', 'is_correct' => true],
        ['option_text' => '', 'is_correct' => false],
        ['option_text' => '', 'is_correct' => false],
        ['option_text' => '', 'is_correct' => false],
    ];
    $options = old('options');
    if (! is_array($options)) {
        if ($storedOptions->count() >= 4 && ($q->type ?? '') === 'mcq') {
            $options = $storedOptions->take(4)->map(fn ($o) => [
                'option_text' => $o->option_text,
                'is_correct' => (bool) $o->is_correct,
            ])->values()->all();
        } else {
            $options = $defaultMcq;
            if (($q->type ?? '') === 'mcq' && $storedOptions->isNotEmpty()) {
                foreach ($storedOptions->take(4) as $i => $o) {
                    $options[$i] = [
                        'option_text' => $o->option_text,
                        'is_correct' => (bool) $o->is_correct,
                    ];
                }
            }
        }
    }
    while (count($options) < 4) {
        $options[] = ['option_text' => '', 'is_correct' => false];
    }
    $options = array_slice(array_values($options), 0, 4);

    $correctOption = old('correct_option');
    if ($correctOption === null) {
        if ($type === 'true_false') {
            $falseOpt = $storedOptions->first(fn ($o) => strcasecmp($o->option_text, 'False') === 0 && $o->is_correct);
            $correctOption = $falseOpt ? 1 : 0;
        } else {
            $correctOption = 0;
            foreach ($options as $i => $opt) {
                if (! empty($opt['is_correct'])) {
                    $correctOption = $i;
                    break;
                }
            }
            if ($storedOptions->isNotEmpty() && ($q->type ?? '') === 'mcq') {
                foreach ($storedOptions as $i => $o) {
                    if ($o->is_correct) {
                        $correctOption = $i;
                        break;
                    }
                }
            }
        }
    }
    $labels = ['A', 'B', 'C', 'D'];
@endphp

<div class="mb-3">
    <label for="type" class="form-label">Question type</label>
    <select id="type" name="type" class="form-select @error('type') is-invalid @enderror" required>
        <option value="mcq" @selected($type === 'mcq')>Multiple choice (4 options)</option>
        <option value="true_false" @selected($type === 'true_false')>True / False</option>
        <option value="short_answer" @selected($type === 'short_answer')>Fill in the blank</option>
    </select>
    @error('type')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    <p class="small cp-muted mb-0 mt-1">Only these three types are supported. Scores are graded automatically from the correct answer you set.</p>
</div>

<div class="mb-3">
    <label for="prompt" class="form-label">Question / prompt</label>
    <textarea id="prompt" name="prompt" rows="3" class="form-control @error('prompt') is-invalid @enderror" required>{{ old('prompt', $q->prompt ?? '') }}</textarea>
    @error('prompt')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <label for="points" class="form-label">Points</label>
        <input id="points" type="number" min="1" name="points" value="{{ old('points', $q->points ?? 100) }}"
               class="form-control @error('points') is-invalid @enderror" required>
        @error('points')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-4">
        <label for="time_limit_seconds" class="form-label">Time limit (seconds)</label>
        <input id="time_limit_seconds" type="number" min="5" max="600" name="time_limit_seconds"
               value="{{ old('time_limit_seconds', $q->time_limit_seconds ?? ($defaultTime ?? $quiz->default_time_limit_seconds ?? 30)) }}"
               class="form-control @error('time_limit_seconds') is-invalid @enderror" required>
        @error('time_limit_seconds')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <p class="small cp-muted mb-0 mt-1">Each question can have its own duration (5–600s).</p>
    </div>
    <div class="col-md-4">
        <label for="image" class="form-label">Image (optional)</label>
        <input id="image" type="file" name="image" accept="image/*" class="form-control @error('image') is-invalid @enderror">
        @error('image')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

{{-- Fill in the blank --}}
<div class="mb-3" data-short-fields>
    <label for="short_answer_expected" class="form-label">Correct answer (fill in the blank)</label>
    <input id="short_answer_expected" name="short_answer_expected" type="text"
           value="{{ old('short_answer_expected', $q->short_answer_expected ?? '') }}"
           class="form-control @error('short_answer_expected') is-invalid @enderror"
           autocomplete="off"
           spellcheck="false">
    @error('short_answer_expected')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    <p class="small cp-muted mb-0 mt-1">
        Matching ignores capitalization and surrounding spaces.
        Example: <code>POST</code>, <code>post</code>, and <code>Post</code> are all accepted.
    </p>
</div>

{{-- True / False --}}
<div class="mb-3" data-tf-fields>
    <label class="form-label">Correct answer</label>
    <div class="d-flex flex-wrap gap-3">
        <div class="form-check">
            <input class="form-check-input" type="radio" name="correct_option" id="tf_true" value="0"
                   @checked((string) $correctOption === '0')>
            <label class="form-check-label" for="tf_true">True</label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="correct_option" id="tf_false" value="1"
                   @checked((string) $correctOption === '1')>
            <label class="form-check-label" for="tf_false">False</label>
        </div>
    </div>
    @error('correct_option')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>

{{-- Multiple choice: exactly 4 --}}
<div data-options-fields>
    <label class="form-label">Answer choices (exactly 4)</label>
    @foreach ($options as $i => $option)
        <div class="input-group mb-2">
            <div class="input-group-text">
                <input class="form-check-input mt-0" type="radio" name="correct_option" value="{{ $i }}"
                       @checked((string) $correctOption === (string) $i)
                       title="Mark choice {{ $labels[$i] }} as correct"
                       data-mcq-radio>
            </div>
            <span class="input-group-text fw-semibold">{{ $labels[$i] }}</span>
            <input type="text" name="options[{{ $i }}][option_text]" class="form-control"
                   value="{{ $option['option_text'] ?? '' }}" placeholder="Choice {{ $labels[$i] }}" required>
        </div>
    @endforeach
    @error('options')
        <div class="text-danger small mb-2">{{ $message }}</div>
    @enderror
    <p class="small cp-muted mb-0">Select the radio next to the one correct choice. Students are scored automatically.</p>
</div>

@push('scripts')
<script>
(function () {
    var type = document.getElementById('type');
    var shortFields = document.querySelector('[data-short-fields]');
    var tfFields = document.querySelector('[data-tf-fields]');
    var optionFields = document.querySelector('[data-options-fields]');
    var mcqRadios = document.querySelectorAll('[data-mcq-radio]');
    var tfRadios = document.querySelectorAll('[data-tf-fields] input[type="radio"]');
    var mcqInputs = document.querySelectorAll('[data-options-fields] input[type="text"]');

    function sync() {
        var t = type ? type.value : 'mcq';
        var isShort = t === 'short_answer';
        var isTf = t === 'true_false';
        var isMcq = t === 'mcq';

        if (shortFields) shortFields.style.display = isShort ? '' : 'none';
        if (tfFields) tfFields.style.display = isTf ? '' : 'none';
        if (optionFields) optionFields.style.display = isMcq ? '' : 'none';

        // Avoid duplicate correct_option names conflicting across hidden groups
        mcqRadios.forEach(function (el) { el.disabled = !isMcq; });
        tfRadios.forEach(function (el) { el.disabled = !isTf; });
        mcqInputs.forEach(function (el) { el.required = isMcq; });
    }

    if (type) {
        type.addEventListener('change', sync);
        sync();
    }
})();
</script>
@endpush
