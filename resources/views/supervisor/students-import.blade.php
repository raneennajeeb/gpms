@extends('layouts.app')

@section('title', __('supervisor.import_students'))

@section('content')
    <x-page-header :title="__('supervisor.import_students')" />

    @if (session('import_passwords'))
        {{-- Shown once: the link clears the list from the session --}}
        <x-alert type="warning" icon="alert-circle">
            <strong>{{ __('supervisor.import_passwords') }}</strong>
            <p>{{ __('supervisor.import_passwords_hint') }}</p>
            <a href="{{ route('supervisor.students.import.passwords') }}" class="btn btn-primary btn-sm">
                {{ __('supervisor.import_passwords_download') }}
            </a>
        </x-alert>
    @endif

    @if ($preview)
        <x-card>
            <x-slot:title>{{ __('supervisor.import_preview_title') }}</x-slot:title>
            <x-slot:actions>
                <x-badge color="success">{{ __('supervisor.import_valid', ['count' => $preview['valid']]) }}</x-badge>
                @if ($preview['invalid'])
                    <x-badge color="danger">{{ __('supervisor.import_invalid', ['count' => $preview['invalid']]) }}</x-badge>
                @endif
            </x-slot:actions>

            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('supervisor.import_line') }}</th>
                            <th>{{ __('student.university_id') }}</th>
                            <th>{{ __('common.name') }}</th>
                            <th>{{ __('common.email') }}</th>
                            <th>{{ __('supervisor.import_result') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($preview['rows'] as $row)
                            <tr class="{{ $row['error'] ? 'row-error' : '' }}">
                                <td class="num">{{ $row['line'] }}</td>
                                <td class="num">{{ $row['university_id'] }}</td>
                                <td>{{ $row['name'] }}</td>
                                <td class="num">{{ $row['email'] }}</td>
                                <td>
                                    @if ($row['error'])
                                        <x-badge color="danger">{{ $row['error'] }}</x-badge>
                                    @elseif ($row['exists'])
                                        <x-badge color="info">{{ __('supervisor.import_will_update') }}</x-badge>
                                    @else
                                        <x-badge color="success">{{ __('supervisor.import_will_create') }}</x-badge>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($preview['valid'] > 0)
                <form method="POST" action="{{ route('supervisor.students.import.confirm') }}">
                    @csrf
                    <input type="hidden" name="section_id" value="{{ $sectionId }}">

                    @foreach ($preview['rows'] as $index => $row)
                        @if ($row['error'] === null)
                            <input type="hidden" name="rows[{{ $index }}][university_id]" value="{{ $row['university_id'] }}">
                            <input type="hidden" name="rows[{{ $index }}][name]" value="{{ $row['name'] }}">
                            <input type="hidden" name="rows[{{ $index }}][email]" value="{{ $row['email'] }}">
                        @endif
                    @endforeach

                    <x-button type="submit" icon="check-circle">{{ __('supervisor.import_confirm') }}</x-button>
                </form>
            @endif
        </x-card>
    @endif

    <div class="dash-grid">
        <div class="dash-grid__main">
            <x-card>
                <x-slot:title>{{ __('supervisor.import_upload') }}</x-slot:title>

                <p class="text-muted">{{ __('supervisor.import_hint', ['columns' => implode('، ', $columns)]) }}</p>
                <a href="{{ route('supervisor.students.import.template') }}">{{ __('supervisor.import_template') }}</a>

                <form method="POST" action="{{ route('supervisor.students.import.preview') }}" enctype="multipart/form-data">
                    @csrf

                    <label class="form-field">
                        <span class="form-field__label">{{ __('supervisor.section') }}</span>
                        <select name="section_id" required>
                            @foreach ($sections as $section)
                                <option value="{{ $section->id }}">{{ $section->number }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="form-field">
                        <span class="form-field__label">{{ __('supervisor.import_file') }}</span>
                        <input type="file" name="file" accept=".csv,.txt" required>
                    </label>
                    @error('file') <p class="form-field__error">{{ $message }}</p> @enderror

                    <x-button type="submit" icon="search">{{ __('supervisor.import_preview') }}</x-button>
                </form>
            </x-card>
        </div>

        <aside class="dash-grid__side">
            <x-card>
                <x-slot:title>{{ __('supervisor.import_manual') }}</x-slot:title>

                <form method="POST" action="{{ route('supervisor.students.store') }}">
                    @csrf

                    <label class="form-field">
                        <span class="form-field__label">{{ __('supervisor.section') }}</span>
                        <select name="section_id" required>
                            @foreach ($sections as $section)
                                <option value="{{ $section->id }}">{{ $section->number }}</option>
                            @endforeach
                        </select>
                    </label>

                    <x-form-field name="university_id" :label="__('student.university_id')" inputmode="numeric" required />
                    <x-form-field name="name" :label="__('common.name')" required />
                    <x-form-field name="email" type="email" :label="__('common.email')" required />

                    <x-button type="submit" icon="users">{{ __('supervisor.import_manual') }}</x-button>
                </form>
            </x-card>
        </aside>
    </div>
@endsection
