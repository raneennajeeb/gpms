@props(['icon', 'label', 'value', 'color' => 'info'])

<div {{ $attributes->merge(['class' => 'stat-card']) }}>
    <span class="stat-card__icon tone-{{ $color }}">
        <x-icon :name="$icon" :size="24" />
    </span>
    <div>
        <div class="stat-card__value">{{ $value }}</div>
        <div class="stat-card__label">{{ $label }}</div>
    </div>
</div>
