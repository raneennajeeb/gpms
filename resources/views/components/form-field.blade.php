@props(['name', 'label', 'type' => 'text', 'value' => null, 'hint' => null])

@php
    $id = $attributes->get('id', $name);
    $hasError = $errors->has($name);
    $inputClasses = 'form-field__input' . ($hasError ? ' is-invalid' : '');
@endphp

<div class="form-field">
    <label for="{{ $id }}" class="form-field__label">{{ $label }}</label>

    @if ($slot->isNotEmpty())
        {{-- Custom control (select, password with toggle...) passed by the page --}}
        {{ $slot }}
    @elseif ($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}"
                  {{ $attributes->except('id')->merge(['class' => $inputClasses]) }}
                  @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif>{{ old($name, $value) }}</textarea>
    @else
        <input type="{{ $type }}" id="{{ $id }}" name="{{ $name }}"
               value="{{ $type === 'password' ? '' : old($name, $value) }}"
               {{ $attributes->except('id')->merge(['class' => $inputClasses]) }}
               @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif>
    @endif

    @if ($hint)
        <p class="form-field__hint">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="form-field__error" id="{{ $id }}-error">{{ $message }}</p>
    @enderror
</div>
