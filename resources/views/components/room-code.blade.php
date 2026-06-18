@props([
    'code' => '',
    'label' => 'Room code',
])

<div {{ $attributes->class(['text-center']) }}>
    @if ($label)
        <div class="cp-muted text-uppercase small mb-2 letter-spacing">{{ $label }}</div>
    @endif
    <div class="cp-room-code" data-room-code>{{ strtoupper($code) }}</div>
</div>
