@extends('layouts.app')

@section('title', __('supervisor.review_project'))

@section('content')
    <x-page-header :title="$project->title"
                   :subtitle="$project->team->members->where('status', 'accepted')->pluck('user.name')->join('، ')">
        <x-slot:actions>
            <x-badge :color="$health->color()">{{ __('student.health_' . $health->level) }}</x-badge>
            @if ($project->supervisorEvaluation && $project->status !== 'archived')
                <x-button :href="route('supervisor.archive.from-project', $project)" variant="secondary" icon="archive">
                    {{ __('supervisor.archive_this') }}
                </x-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="dash-grid">
        <div class="dash-grid__main">
            <x-card>
                <x-slot:title>{{ __('student.project_info') }}</x-slot:title>
                <p>{{ $project->description }}</p>
                <div class="tag-list">
                    @foreach ($project->technologies as $technology)
                        <x-tag>{{ $technology->name }}</x-tag>
                    @endforeach
                </div>
            </x-card>

            <x-card>
                <x-slot:title>{{ __('supervisor.stages') }}</x-slot:title>

                <ol class="stage-list">
                    @foreach ($project->stages as $stage)
                        <li class="stage stage--{{ $stage->status }}">
                            <span class="stage__mark">
                                @if ($stage->status === 'completed')
                                    <x-icon name="check-circle" :size="18" />
                                @else
                                    {{ $loop->iteration }}
                                @endif
                            </span>

                            <span class="stage__body">
                                <strong>{{ $stage->name_ar }}</strong>
                                <small>
                                    {{ $stage->due_date?->translatedFormat('j M Y') }}
                                    @if ($stage->status === 'completed' && $stage->approved_at)
                                        — {{ __('student.stage_done') }} {{ $stage->approved_at->translatedFormat('j M') }}
                                    @endif
                                </small>
                            </span>

                            <span class="stage__weight">{{ $stage->weight }}%</span>

                            @if ($stage->status !== 'completed')
                                <form method="POST" action="{{ route('supervisor.stages.complete', $stage) }}">
                                    @csrf
                                    <button class="btn btn-secondary btn-sm">{{ __('supervisor.approve_stage') }}</button>
                                </form>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </x-card>

            <x-card>
                <x-slot:title>{{ __('supervisor.submissions') }}</x-slot:title>

                @forelse ($project->submissions as $submission)
                    <div class="review-row">
                        <div class="review-row__head">
                            <strong>{{ $submission->title }}</strong>
                            <x-badge :color="match ($submission->status) {
                                'accepted' => 'success',
                                'late' => 'danger',
                                'needs_revision' => 'warning',
                                default => 'neutral',
                            }">{{ __('student.sub_status_' . $submission->status) }}</x-badge>
                            <small class="text-muted">{{ $submission->due_date?->translatedFormat('j M Y') }}</small>
                        </div>

                        @if ($submission->versions->isNotEmpty())
                            <ul class="version-list">
                                @foreach ($submission->versions->sortByDesc('version_number') as $version)
                                    <li>
                                        <strong>v{{ $version->version_number }}</strong>
                                        <span>{{ $version->original_name }}</span>
                                        <small>{{ $version->uploader?->name }} — {{ $version->created_at->translatedFormat('j M H:i') }}</small>
                                        <a href="{{ route('supervisor.versions.download', $version) }}">{{ __('student.download') }}</a>
                                    </li>
                                @endforeach
                            </ul>

                            <form method="POST" action="{{ route('supervisor.submissions.review', $submission) }}" class="review-form">
                                @csrf
                                <select name="status" required>
                                    <option value="accepted">{{ __('supervisor.accept') }}</option>
                                    <option value="needs_revision">{{ __('supervisor.needs_revision') }}</option>
                                </select>
                                <input type="text" name="supervisor_notes" maxlength="1000"
                                       placeholder="{{ __('supervisor.notes') }}"
                                       value="{{ $submission->supervisor_notes }}">
                                <x-button type="submit" variant="secondary">{{ __('supervisor.review') }}</x-button>
                            </form>
                        @else
                            <p class="text-muted">{{ __('student.no_versions') }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-muted">{{ __('student.no_submissions') }}</p>
                @endforelse
            </x-card>

            <x-card>
                <x-slot:title>{{ __('supervisor.student_questions') }}</x-slot:title>

                @forelse ($project->inquiries as $inquiry)
                    <div class="notice">
                        <p>{{ $inquiry->message }}</p>
                        <small>{{ $inquiry->student->name }} — {{ $inquiry->created_at->diffForHumans() }}</small>
                    </div>
                @empty
                    <p class="text-muted">{{ __('supervisor.inquiries_empty') }}</p>
                @endforelse
            </x-card>

            <x-card>
                <x-slot:title>{{ __('student.activity_log') }}</x-slot:title>

                @forelse ($project->activities->sortByDesc('created_at')->take(15) as $activity)
                    <div class="notice">
                        <p>{{ $activity->description }}</p>
                        <small>{{ $activity->user?->name }} — {{ $activity->created_at->translatedFormat('j M Y — H:i') }}</small>
                    </div>
                @empty
                    <p class="text-muted">{{ __('student.no_activity') }}</p>
                @endforelse
            </x-card>
        </div>

        <aside class="dash-grid__side">
            <x-card>
                <x-slot:title>{{ __('supervisor.evaluation') }}</x-slot:title>

                @if ($project->supervisorEvaluation)
                    <p class="health health--good">
                        <strong>{{ $project->supervisorEvaluation->label_ar }} ({{ $project->supervisorEvaluation->letter }})</strong>
                        — {{ $project->supervisorEvaluation->score }}
                    </p>
                @endif

                @if ($project->status !== 'archived')
                    <form method="POST" action="{{ route('supervisor.projects.evaluate', $project) }}">
                        @csrf
                        <x-form-field name="score" type="number" step="0.5" min="0" max="100"
                                      :label="__('supervisor.score')"
                                      :value="$project->supervisorEvaluation?->score" required />
                        <x-form-field name="comment" type="textarea" rows="3"
                                      :label="__('supervisor.evaluation_comment')"
                                      :value="$project->supervisorEvaluation?->comment" />
                        <x-button type="submit" icon="star">{{ __('supervisor.save_evaluation') }}</x-button>
                    </form>
                @endif
            </x-card>

            <x-card>
                <x-slot:title>{{ __('supervisor.note_to_project') }}</x-slot:title>

                <form method="POST" action="{{ route('supervisor.projects.comment', $project) }}" enctype="multipart/form-data">
                    @csrf
                    <x-form-field name="body" type="textarea" rows="3" :label="__('supervisor.notes')" required />

                    <label class="form-field">
                        <span class="form-field__label">{{ __('supervisor.note_to_team') }}</span>
                        <select name="kind">
                            <option value="feedback">{{ __('supervisor.note_to_project') }}</option>
                            <option value="team_note">{{ __('supervisor.note_to_team') }}</option>
                        </select>
                    </label>

                    <label class="form-field">
                        <span class="form-field__label">{{ __('supervisor.attach') }}</span>
                        <input type="file" name="attachment" accept=".png,.jpg,.jpeg,.pdf,.zip">
                    </label>

                    <x-button type="submit" icon="send">{{ __('supervisor.send_note') }}</x-button>
                </form>
            </x-card>

            <x-card>
                <x-slot:title>{{ __('student.supervisor_feedback') }}</x-slot:title>

                @forelse ($project->comments->sortByDesc('created_at') as $comment)
                    <div class="notice">
                        <p>{{ $comment->body }}</p>
                        @foreach ($comment->attachments as $attachment)
                            <a href="{{ route('supervisor.attachments.download', $attachment) }}" class="file-chip">
                                <x-icon name="file" :size="14" /> {{ $attachment->original_name }}
                            </a>
                        @endforeach
                        <small>{{ $comment->created_at->diffForHumans() }}</small>
                    </div>
                @empty
                    <p class="text-muted">{{ __('student.no_feedback') }}</p>
                @endforelse
            </x-card>
        </aside>
    </div>
@endsection
