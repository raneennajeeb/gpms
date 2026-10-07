@props(['id', 'title'])

<dialog id="{{ $id }}" {{ $attributes->merge(['class' => 'modal']) }} aria-labelledby="{{ $id }}-title">
    <div class="modal__header">
        <h3 class="modal__title" id="{{ $id }}-title">{{ $title }}</h3>
        <button type="button" class="btn-icon" data-modal-close>
            <x-icon name="x" />
            <span class="visually-hidden">{{ __('common.close') }}</span>
        </button>
    </div>

    <div class="modal__body">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="modal__footer">{{ $footer }}</div>
    @endisset
</dialog>
