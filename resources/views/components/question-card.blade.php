@props([
    'question' => null,
    'showMeta' => true,
])

@php
    $prompt = is_object($question)
        ? ($question->prompt ?? '')
        : (is_array($question) ? ($question['prompt'] ?? '') : (string) $question);
    $type = is_object($question) ? ($question->type ?? null) : (is_array($question) ? ($question['type'] ?? null) : null);
    $points = is_object($question) ? ($question->points ?? null) : (is_array($question) ? ($question['points'] ?? null) : null);
    $timeLimit = is_object($question) ? ($question->time_limit_seconds ?? null) : (is_array($question) ? ($question['time_limit_seconds'] ?? null) : null);
    $image = is_object($question) ? ($question->image_path ?? null) : (is_array($question) ? ($question['image_path'] ?? null) : null);
@endphp

<div {{ $attributes->class(['cp-surface-flat', 'p-4', 'cp-fade-in']) }}>
    @if ($showMeta && ($type || $points || $timeLimit))
        <div class="d-flex flex-wrap gap-2 mb-3">
            @if ($type)
                <span class="badge badge-cp">
                    {{ match ($type) {
                        'mcq' => 'Multiple choice',
                        'true_false' => 'True / False',
                        'short_answer' => 'Fill in the blank',
                        default => str_replace('_', ' ', $type),
                    } }}
                </span>
            @endif
            @if ($points !== null)
                <span class="badge text-bg-secondary">{{ $points }} pts</span>
            @endif
            @if ($timeLimit !== null)
                <span class="badge text-bg-secondary">{{ $timeLimit }}s</span>
            @endif
        </div>
    @endif

    <div class="cp-question-prompt" data-question-prompt>{{ $prompt }}</div>

    @if ($image)
        <div class="mt-3">
            <img src="{{ asset('storage/' . ltrim($image, '/')) }}" alt="Question image" class="img-fluid rounded">
        </div>
    @endif

    {{ $slot }}
</div>
