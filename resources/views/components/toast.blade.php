{{-- Toasts are added here by showToast() in app.js. Flash messages from the server show on page load. --}}
<div id="toast-container" class="toast-container" aria-live="polite"
     @if (session('success')) data-flash-success="{{ session('success') }}" @endif
     @if (session('error')) data-flash-error="{{ session('error') }}" @endif></div>
