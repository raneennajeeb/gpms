@extends('layouts.app')

@section('title', __('student.submissions'))

@section('content')
    <x-page-header :title="__('student.submissions')" />

    @if ($submissions->isEmpty())
        <x-card>
            <x-empty-state icon="upload" :title="__('student.no_submissions')" />
        </x-card>
    @else
        <x-card>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('student.stage') }}</th>
                            <th>{{ __('archive.title') }}</th>
                            <th>{{ __('student.deadline') }}</th>
                            <th>{{ __('student.status') }}</th>
                            <th>{{ __('student.last_file') }}</th>
                            <th>{{ __('student.supervisor_feedback') }}</th>
                            <th>{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($submissions as $submission)
                            @php $latest = $submission->versions->sortByDesc('version_number')->first(); @endphp
                            <tr>
                                <td>{{ $submission->stage?->name_ar ?? '—' }}</td>
                                <td>{{ $submission->title }}</td>
                                <td>{{ $submission->due_date?->translatedFormat('j M Y') ?? '—' }}</td>
                                <td>
                                    <x-badge :color="match ($submission->status) {
                                        'accepted' => 'success',
                                        'late' => 'danger',
                                        'needs_revision' => 'warning',
                                        'submitted' => 'info',
                                        default => 'neutral',
                                    }">{{ __('student.sub_status_' . $submission->status) }}</x-badge>
                                </td>
                                <td>
                                    @if ($latest)
                                        v{{ $latest->version_number }} — {{ $latest->created_at->translatedFormat('j M') }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-muted">{{ $submission->supervisor_notes ?? '—' }}</td>
                                <td>
                                    <details class="versions">
                                        <summary>{{ __('student.upload') }}</summary>

                                        <form method="POST" action="{{ route('student.submissions.upload', $submission) }}"
                                              enctype="multipart/form-data" class="upload-form">
                                            @csrf
                                            <label class="sr-only" for="file-{{ $submission->id }}">{{ __('student.file') }}</label>
                                            <input type="file" name="file" id="file-{{ $submission->id }}" required
                                                   accept=".{{ implode(',.', $allowed) }}">
                                            <input type="text" name="note" placeholder="{{ __('student.upload_note') }}" maxlength="500">
                                            <x-button type="submit" icon="upload">{{ __('student.upload') }}</x-button>
                                            <p class="form-field__hint">
                                                {{ __('student.upload_hint', ['types' => implode('، ', $allowed), 'max' => $maxMb]) }}
                                            </p>
                                        </form>

                                        {{-- Every upload is kept: nothing is ever replaced --}}
                                        <h4>{{ __('student.versions') }}</h4>
                                        @if ($submission->versions->isEmpty())
                                            <p class="text-muted">{{ __('student.no_versions') }}</p>
                                        @else
                                            <ul class="version-list">
                                                @foreach ($submission->versions->sortByDesc('version_number') as $version)
                                                    <li>
                                                        <strong>v{{ $version->version_number }}</strong>
                                                        <span>{{ $version->original_name }}</span>
                                                        <small>
                                                            {{ $version->uploader?->name }} —
                                                            {{ $version->created_at->translatedFormat('j M Y H:i') }} —
                                                            {{ round($version->size / 1024) }} KB
                                                        </small>
                                                        <a href="{{ route('student.submissions.download', $version) }}">
                                                            {{ __('student.download') }}
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </details>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    @endif
@endsection
