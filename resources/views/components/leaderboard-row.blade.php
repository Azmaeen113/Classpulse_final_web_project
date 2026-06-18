@props([
    'rank' => 1,
    'name' => 'Student',
    'points' => 0,
    'avgTime' => null,
])

@php
    $isTop = (int) $rank <= 3;
@endphp

<div {{ $attributes->class(['cp-leaderboard-row', 'is-top' => $isTop]) }}>
    <div class="cp-rank">#{{ $rank }}</div>
    <div class="text-truncate">{{ $name }}</div>
    <div class="fw-bold">{{ number_format((int) $points) }}</div>
    <div class="cp-avg-time cp-muted small">
        @if ($avgTime !== null)
            {{ is_numeric($avgTime) ? number_format((int) $avgTime) . ' ms' : $avgTime }}
        @endif
    </div>
</div>
