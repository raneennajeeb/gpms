@props(['color' => 'neutral'])

<span {{ $attributes->merge(['class' => 'badge tone-' . $color]) }}>{{ $slot }}</span>
