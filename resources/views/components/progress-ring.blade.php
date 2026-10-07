@props(['value' => 0, 'size' => 120, 'color' => 'info', 'label' => null])

@php
    $value = max(0, min(100, (int) $value));
    $circumference = 2 * M_PI * 40;
    $offset = $circumference * (1 - $value / 100);
@endphp

<div {{ $attributes->merge(['class' => 'progress-ring']) }} style="width: {{ $size }}px; height: {{ $size }}px;"
     role="progressbar" aria-valuenow="{{ $value }}" aria-valuemin="0" aria-valuemax="100">
    <svg viewBox="0 0 100 100" aria-hidden="true">
        <circle class="progress-ring__track" cx="50" cy="50" r="40" />
        <circle class="progress-ring__fill fill-{{ $color }}" cx="50" cy="50" r="40"
                stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $offset }}" />
    </svg>
    <div class="progress-ring__text">
        <span class="progress-ring__value">{{ $value }}%</span>
        @if ($label)
            <span class="progress-ring__label">{{ $label }}</span>
        @endif
    </div>
</div>
