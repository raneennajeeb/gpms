@extends('layouts.app')

@section('title', __('student.profile'))

@section('content')
    <x-page-header :title="__('student.profile')" />

    <div class="dash-grid">
        <div class="dash-grid__main">
            {{-- Name, ID and department come from the university: shown, not editable --}}
            <x-card>
                <x-slot:title>{{ __('student.profile') }}</x-slot:title>

                <p class="text-muted">{{ __('student.read_only_hint') }}</p>

                <dl class="meta-list">
                    <div>
                        <dt>{{ __('common.name') }}</dt>
                        <dd>{{ $student->name }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('student.university_id') }}</dt>
                        <dd class="num">{{ $student->university_id }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('common.email') }}</dt>
                        <dd>{{ $student->email }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('archive.department') }}</dt>
                        <dd>{{ $student->department?->localizedName() ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('archive.branch') }}</dt>
                        <dd>{{ $student->branch?->localizedName() ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('nav.section') }}</dt>
                        <dd>{{ $student->section?->number ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('student.my_team') }}</dt>
                        <dd>{{ $team?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('student.current_project') }}</dt>
                        <dd>{{ $team?->project?->title ?? __('student.no_current_project') }}</dd>
                    </div>
                </dl>
            </x-card>

            <x-card>
                <x-slot:title>{{ __('student.change_password') }}</x-slot:title>

                <form method="POST" action="{{ route('student.profile.password') }}">
                    @csrf
                    @method('PUT')
                    <x-password-input name="current_password" :label="__('auth.current_password')" required />
                    <x-password-input name="password" :label="__('auth.new_password')" required />
                    <x-password-input name="password_confirmation" :label="__('auth.confirm_password')" required />
                    <x-button type="submit" icon="lock">{{ __('auth.save_password') }}</x-button>
                </form>
            </x-card>
        </div>

        <aside class="dash-grid__side">
            <x-card>
                <x-slot:title>{{ __('student.preferences') }}</x-slot:title>

                <form method="POST" action="{{ route('student.profile.update') }}">
                    @csrf
                    @method('PUT')

                    <x-form-field name="phone" :label="__('common.phone')" :value="$student->phone" />

                    <label class="form-field">
                        <span class="form-field__label">{{ __('student.language') }}</span>
                        <select name="locale">
                            @foreach ($locales as $locale)
                                <option value="{{ $locale }}" @selected($student->locale === $locale)>
                                    {{ $locale === 'ar' ? 'العربية' : 'English' }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="form-field">
                        <span class="form-field__label">{{ __('student.theme') }}</span>
                        <select name="theme">
                            <option value="light" @selected($student->theme === 'light')>{{ __('common.theme_light') }}</option>
                            <option value="dark" @selected($student->theme === 'dark')>{{ __('common.theme_dark') }}</option>
                        </select>
                    </label>

                    <x-button type="submit" icon="check-circle">{{ __('common.save') }}</x-button>
                </form>
            </x-card>
        </aside>
    </div>
@endsection
