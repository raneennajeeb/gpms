@props(['type' => 'danger', 'icon' => 'alert-circle'])

<div {{ $attributes->merge(['class' => 'alert alert--' . $type]) }} role="alert">
    <x-icon :name="$icon" />
    <span>{{ $slot }}</span>
</div>
