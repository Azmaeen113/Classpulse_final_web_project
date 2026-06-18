@props([
    'title' => 'Nothing here yet',
    'message' => null,
    'icon' => 'bi-inbox',
])

@php
    $isStudent = (auth()->user()->role ?? null) === 'student';
@endphp

<div {{ $attributes->class(['cp-empty', 'cp-surface-flat', 'cp-fade-in']) }}>
    @if ($isStudent)
        <div class="cp-empty-shapes" aria-hidden="true">
            <span></span><span></span><span></span><span></span>
        </div>
    @endif
    <i class="bi {{ $icon }} d-block mb-3"></i>
    <h3 class="h5 mb-2" style="color: var(--cp-text);">{{ $title }}</h3>
    @if ($message)
        <p class="mb-3">{{ $message }}</p>
    @endif
    {{ $slot }}
</div>
