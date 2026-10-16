@props([
    'name',
    'label',
    'hint' => null,
    'values' => [],
    'suggestions' => [],
    'disabled' => false,
])

@php
    $id = $name . '-tags';
    $values = collect($values)->filter()->values();
    $listId = $suggestions ? $name . '-suggestions' : null;
@endphp

<div class="form-field tag-input" data-tag-input data-name="{{ $name }}" @if ($disabled) data-disabled @endif>
    <span class="form-field__label" id="{{ $id }}-label">{{ $label }}</span>

    <div class="tag-input__box">
        <span class="tag-input__tags" data-tags>
            @foreach ($values as $value)
                {{-- Each tag carries its own hidden input, so the array posts as name[] --}}
                <span class="tag-input__tag">
                    <span>{{ $value }}</span>
                    <input type="hidden" name="{{ $name }}[]" value="{{ $value }}">
                    @unless ($disabled)
                        <button type="button" data-remove-tag aria-label="{{ __('archive.remove_filter') }}">
                            <x-icon name="x" :size="12" />
                        </button>
                    @endunless
                </span>
            @endforeach
        </span>

        @unless ($disabled)
            <input type="text" id="{{ $id }}" data-tag-field
                   aria-labelledby="{{ $id }}-label"
                   @if ($listId) list="{{ $listId }}" @endif
                   autocomplete="off">
        @endunless
    </div>

    @if ($listId)
        <datalist id="{{ $listId }}">
            @foreach ($suggestions as $suggestion)
                <option value="{{ $suggestion }}"></option>
            @endforeach
        </datalist>
    @endif

    @if ($hint)
        <p class="form-field__hint">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="form-field__error">{{ $message }}</p>
    @enderror
    @error($name . '.*')
        <p class="form-field__error">{{ $message }}</p>
    @enderror
</div>
