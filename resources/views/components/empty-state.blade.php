@props(['icon' => 'inbox', 'title', 'text' => null])

<div {{ $attributes->merge(['class' => 'empty-state']) }}>
    <span class="empty-state__icon">
        <x-icon :name="$icon" :size="36" />
    </span>
    <h3 class="empty-state__title">{{ $title }}</h3>
    @if ($text)
        <p class="empty-state__text">{{ $text }}</p>
    @endif
    {{ $slot }}
</div>
