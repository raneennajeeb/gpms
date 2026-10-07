{{-- One shared confirm dialog per page, used by any form with data-confirm="message" --}}
<dialog id="confirm-dialog" class="modal modal--small" aria-labelledby="confirm-dialog-message">
    <div class="modal__body">
        <p id="confirm-dialog-message" class="confirm-dialog__message"></p>
    </div>
    <div class="modal__footer">
        <x-button variant="secondary" data-confirm-cancel>{{ __('common.cancel') }}</x-button>
        <x-button variant="danger" data-confirm-ok>{{ __('common.confirm') }}</x-button>
    </div>
</dialog>
