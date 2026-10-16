@extends('layouts.app')

@section('title', $entry ? __('supervisor.save') : __('supervisor.add_to_archive'))

@section('content')
    <x-page-header :title="$entry ? __('supervisor.save') : __('supervisor.add_to_archive')" />

    @php
        // Three sources, in order: what was typed, what was saved, what the project holds
        $value = function (string $field, $fallback = '') use ($entry, $prefill) {
            return old($field, $entry->{$field} ?? ($prefill[$field] ?? $fallback));
        };
        $students = old('students', $entry?->students->pluck('student_name')->all() ?? ($prefill['students'] ?? []));
        $committee = old('committee', $entry?->committee->pluck('member_name')->all() ?? []);
        $techs = old('technologies', $entry?->technologies->pluck('name')->all() ?? ($prefill['technologies'] ?? []));
        $competition = (bool) old('competition', $entry?->competition ?? false);
    @endphp

    <form method="POST"
          action="{{ $entry ? route('supervisor.archive.update', $entry) : route('supervisor.archive.store') }}"
          enctype="multipart/form-data">
        @csrf
        @if ($entry) @method('PUT') @endif
        @if ($project) <input type="hidden" name="project_id" value="{{ $project->id }}"> @endif

        <x-card>
            <x-slot:title>{{ __('supervisor.basic_info') }}</x-slot:title>

            <x-form-field name="title" :label="__('archive.title')" :value="$value('title')" required />
            <x-form-field name="idea_summary" type="textarea" rows="2"
                          :label="__('archive.idea_summary')" :value="$value('idea_summary')" required />
            <x-form-field name="description" type="textarea" rows="6"
                          :label="__('archive.about_project')" :value="$value('description')" required />

            <div class="filters__row">
                <label class="filters__field">
                    <span>{{ __('archive.faculty') }}</span>
                    <select name="faculty_id" required>
                        @foreach ($faculties as $faculty)
                            <option value="{{ $faculty->id }}" @selected($value('faculty_id') == $faculty->id)>
                                {{ $faculty->localizedName() }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="filters__field">
                    <span>{{ __('archive.department') }}</span>
                    <select name="department_id" required>
                        @foreach ($faculties->flatMap->departments as $department)
                            <option value="{{ $department->id }}" @selected($value('department_id') == $department->id)>
                                {{ $department->localizedName() }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="filters__field">
                    <span>{{ __('archive.branch') }}</span>
                    <select name="branch_id" required>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected($value('branch_id') == $branch->id)>
                                {{ $branch->localizedName() }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="filters__field">
                    <span>{{ __('archive.year') }}</span>
                    <input type="number" name="year" min="2000" max="{{ now()->year + 1 }}"
                           value="{{ $value('year', now()->year) }}" required>
                </label>
            </div>

            <x-tag-input name="technologies" :label="__('archive.technologies')"
                         :values="$techs" :suggestions="$technologies" />
        </x-card>

        <x-card>
            <x-slot:title>{{ __('supervisor.people') }}</x-slot:title>

            <x-tag-input name="students" :label="__('archive.students')" :hint="__('supervisor.add_name')" :values="$students" />
            <x-tag-input name="committee" :label="__('archive.committee')" :hint="__('supervisor.add_name')" :values="$committee" />

            <p class="text-muted">{{ __('archive.supervisor') }}: {{ auth()->user()->name }}</p>
        </x-card>

        <x-card>
            <x-slot:title>{{ __('supervisor.defense_info') }}</x-slot:title>

            <div class="filters__row">
                <label class="filters__field">
                    <span>{{ __('archive.defense_date') }}</span>
                    <input type="date" name="defense_date" required
                           value="{{ old('defense_date', $entry?->defense_date?->toDateString()) }}">
                </label>

                <label class="filters__field">
                    <span>{{ __('archive.hall') }}</span>
                    <input type="text" name="hall_name" list="halls" required value="{{ $value('hall_name') }}">
                    <datalist id="halls">
                        @foreach ($rooms as $room)
                            <option value="{{ $room->name }}"></option>
                        @endforeach
                    </datalist>
                </label>

                <label class="filters__field">
                    <span>{{ __('archive.grade') }}</span>
                    <select name="grade" required>
                        @foreach (['excellent', 'very_good', 'good'] as $grade)
                            <option value="{{ $grade }}" @selected($value('grade') === $grade)>{{ __('grades.' . $grade) }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="filters__field">
                    <span>{{ __('supervisor.final_score') }}</span>
                    <input type="number" name="final_score" step="0.5" min="0" max="100" value="{{ $value('final_score') }}">
                </label>
            </div>

            {{-- The two competition fields only appear when the switch is on --}}
            <label class="form-field form-field--switch">
                <input type="checkbox" name="competition" value="1" data-toggle="#competition-fields" @checked($competition)>
                <span>{{ __('supervisor.competition_switch') }}</span>
            </label>

            <div id="competition-fields" @class(['filters__row']) @if (! $competition) hidden @endif>
                <label class="filters__field">
                    <span>{{ __('archive.competition_name') }}</span>
                    <input type="text" name="competition_name" value="{{ $value('competition_name') }}">
                </label>

                <label class="filters__field">
                    <span>{{ __('archive.competition_result') }}</span>
                    <input type="text" name="competition_result" value="{{ $value('competition_result') }}">
                </label>
            </div>
        </x-card>

        <x-card>
            <x-slot:title>{{ __('supervisor.files') }}</x-slot:title>

            <label class="form-field">
                <span class="form-field__label">{{ __('supervisor.cover_image') }}</span>
                <input type="file" name="cover_image" accept=".jpg,.jpeg,.png,.webp">
                <span class="form-field__hint">{{ __('supervisor.cover_hint') }}</span>
            </label>

            <label class="form-field">
                <span class="form-field__label">{{ __('supervisor.documents') }}</span>
                <input type="file" name="documents[]" accept=".pdf,.zip" multiple>
                <span class="form-field__hint">{{ __('supervisor.documents_hint') }}</span>
            </label>

            <x-form-field name="github_url" type="url" :label="__('archive.github')"
                          :hint="__('student.github_hint')" :value="$value('github_url')" />
        </x-card>

        <x-button type="submit" icon="archive">{{ __('supervisor.save') }}</x-button>
    </form>
@endsection
