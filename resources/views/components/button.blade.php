@props(['variant' => 'primary', 'href' => null, 'icon' => null, 'type' => 'button'])

@php
    $classes = 'btn btn-' . $variant;
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon) <x-icon :name="$icon" /> @endif
        <span>{{ $slot }}</span>
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon) <x-icon :name="$icon" /> @endif
        <span>{{ $slot }}</span>
    </button>
@endif
