@props(['value' => 0, 'color' => 'info', 'label' => null])

@php
    $value = max(0, min(100, (int) $value));
@endphp

<div {{ $attributes->merge(['class' => 'progress-bar']) }} role="progressbar"
     aria-valuenow="{{ $value }}" aria-valuemin="0" aria-valuemax="100"
     @if ($label) aria-label="{{ $label }}" @endif>
    <div class="progress-bar__fill fill-{{ $color }}" style="width: {{ $value }}%"></div>
</div>
