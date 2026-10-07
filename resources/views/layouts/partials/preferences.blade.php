{{-- Language switch + theme toggle, shared by every layout --}}
<form method="POST" action="{{ route('public.locale.update', app()->getLocale() === 'ar' ? 'en' : 'ar') }}">
    @csrf
    <button type="submit" class="btn-icon btn-icon--text">
        <x-icon name="globe" />
        <span>{{ __('common.switch_language') }}</span>
    </button>
</form>

<button type="button" class="btn-icon" data-theme-toggle title="{{ __('common.toggle_theme') }}"
    @auth data-save-url="{{ route('shared.theme.update') }}" @endauth>
    <x-icon name="moon" class="icon-when-light" />
    <x-icon name="sun" class="icon-when-dark" />
    <span class="visually-hidden">{{ __('common.toggle_theme') }}</span>
</button>
