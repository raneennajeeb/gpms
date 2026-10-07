@props(['title' => null])

<div {{ $attributes->merge(['class' => 'card']) }}>
    @if ($title)
        <div class="card__header">
            <h3 class="card__title">{{ $title }}</h3>
            @isset($actions)
                <div class="card__actions">{{ $actions }}</div>
            @endisset
        </div>
    @endif
    <div class="card__body">
        {{ $slot }}
    </div>
</div>
