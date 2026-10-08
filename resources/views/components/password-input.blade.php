@props(['name', 'label', 'autocomplete' => 'current-password', 'hint' => null])

<x-form-field :name="$name" :label="$label" :hint="$hint">
    <div class="password-input">
        <input type="password" id="{{ $name }}" name="{{ $name }}" autocomplete="{{ $autocomplete }}"
               {{ $attributes->merge(['class' => 'form-field__input' . ($errors->has($name) ? ' is-invalid' : '')]) }}>
        <button type="button" class="password-input__toggle" data-password-toggle="{{ $name }}"
                aria-label="{{ __('auth.show_password') }}">
            <x-icon name="eye" class="icon-when-hidden" />
            <x-icon name="eye-off" class="icon-when-shown" />
        </button>
    </div>
</x-form-field>
