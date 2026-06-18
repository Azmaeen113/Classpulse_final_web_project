@props([
    'label' => '',
    'value' => '0',
    'icon' => null,
    'hint' => null,
])

<div {{ $attributes->class(['cp-surface-flat', 'cp-stat', 'cp-fade-in']) }}>
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <div class="cp-stat-label">{{ $label }}</div>
            <div class="cp-stat-value">{{ $value }}</div>
            @if ($hint)
                <div class="small cp-muted mt-1">{{ $hint }}</div>
            @endif
        </div>
        @if ($icon)
            <i class="bi {{ $icon }} fs-3 opacity-75" style="color: var(--cp-primary);"></i>
        @endif
    </div>
</div>
