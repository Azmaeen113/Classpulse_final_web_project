@props([
    'size' => 'md',
    'value' => '00:00',
    'variant' => null, // 'friendly' | 'broadcast' | null (auto)
])

@php
    $role = auth()->user()->role ?? null;
    $autoVariant = $role === 'student' ? 'friendly' : 'broadcast';
    $variant = $variant ?: $autoVariant;
    $sizeClass = $size === 'lg' ? 'cp-timer-lg' : 'cp-timer-md';
    $variantClass = $variant === 'friendly' ? 'cp-timer-pill' : 'cp-timer-broadcast';
@endphp

<div {{ $attributes->class(['cp-timer', $sizeClass, $variantClass]) }} data-live-timer aria-live="polite">
    {{ $value }}
</div>
