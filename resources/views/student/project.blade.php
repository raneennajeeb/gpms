@extends('layouts.app')

@section('title', __('student.project'))

@section('content')
    <x-page-header :title="$project->title">
        <x-slot:actions>
            <x-badge :color="match ($project->status) {
                'approved', 'in_progress' => 'success',
                'changes_requested' => 'warning',
                'rejected' => 'danger',
                default => 'neutral',
            }">{{ __('projects.status.' . $project->status) }}</x-badge>
        </x-slot:actions>
    </x-page-header>

    <div class="dash-grid">
        <div class="dash-grid__main">
            <x-card>
                <x-slot:title>{{ __('student.project_info') }}</x-slot:title>

                <p>{{ $project->description }}</p>

                @if ($project->keywords)
                    <p class="text-muted">{{ __('student.keywords') }}</p>
                    <div class="tag-list">
                        @foreach ($project->keywords as $keyword)
                            <x-tag>{{ $keyword }}</x-tag>
                        @endforeach
                    </div>
                @endif

                @if ($project->technologies->isNotEmpty())
                    <p class="text-muted">{{ __('archive.technologies') }}</p>
                    <div class="tag-list">
                        @foreach ($project->technologies as $technology)
                            <x-tag>{{ $technology->name }}</x-tag>
                        @endforeach
                    </div>
                @endif
            </x-card>

            @if ($timeline)
                <x-card>
                    <x-slot:title>{{ __('student.timeline') }}</x-slot:title>

                    <div class="gantt">
                        @if ($timeline['today'] !== null)
                            <span class="gantt__today" style="inset-inline-start: {{ $timeline['today'] }}%"
                                  title="{{ __('student.today') }}"></span>
                        @endif

                        @foreach ($timeline['bars'] as $bar)
                            <div class="gantt__row">
                                <span class="gantt__label">{{ $bar['stage']->name_ar }}</span>
                                <span class="gantt__track">
                                    <span class="gantt__bar gantt__bar--{{ $bar['stage']->status }} @if ($bar['overdue']) gantt__bar--overdue @endif"
                                          style="inset-inline-start: {{ $bar['left'] }}%; width: {{ $bar['width'] }}%"
                                          title="{{ $bar['stage']->due_date->translatedFormat('j M Y') }}"></span>
                                </span>
                                <span class="gantt__date">
                                    {{ $bar['stage']->due_date->translatedFormat('j M') }}
                                    @if ($bar['overdue'])
                                        <x-badge color="danger">{{ __('student.overdue') }}</x-badge>
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                </x-card>
            @endif

            <x-card>
                <x-slot:title>{{ __('student.activity_log') }}</x-slot:title>

                @forelse ($project->activities->sortByDesc('created_at') as $activity)
                    <div class="notice">
                        <p>{{ $activity->description }}</p>
                        <small>
                            {{ $activity->user?->name }} — {{ $activity->created_at->translatedFormat('j M Y — H:i') }}
                        </small>
                    </div>
                @empty
                    <p class="text-muted">{{ __('student.no_activity') }}</p>
                @endforelse
            </x-card>

            <x-card>
                <x-slot:title>{{ __('student.supervisor_feedback') }}</x-slot:title>

                @forelse ($project->comments->sortByDesc('created_at') as $comment)
                    <div class="notice">
                        <p>{{ $comment->body }}</p>
                        <small>{{ $comment->user?->name }} — {{ $comment->created_at->diffForHumans() }}</small>
                    </div>
                @empty
                    <p class="text-muted">{{ __('student.no_feedback') }}</p>
                @endforelse
            </x-card>
        </div>

        <aside class="dash-grid__side">
            {{-- Health is worked out on the server; the reasons come with it --}}
            <x-card>
                <x-slot:title>{{ __('student.health') }}</x-slot:title>

                <p class="health health--{{ $health->level }}">
                    <x-icon :name="$health->icon()" :size="20" />
                    <strong>{{ __('student.health_' . $health->level) }}</strong>
                </p>

                @if ($health->reasons)
                    <p class="text-muted">{{ __('student.health_why') }}</p>
                    <ul class="checklist">
                        @foreach ($health->reasons as $reason)
                            <li>{{ __('student.reason_' . $reason['key'], ['count' => $reason['count']]) }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted">{{ __('student.health_clear') }}</p>
                @endif
            </x-card>

            <x-card>
                <x-slot:title>{{ __('student.team_members') }}</x-slot:title>

                <ul class="member-list">
                    @foreach ($project->team->members->where('status', 'accepted') as $member)
                        <li class="member">
                            <span class="member__name">
                                {{ $member->user->name }}
                                @if ($member->user_id === $project->team->leader_id)
                                    <x-badge color="info">{{ __('student.leader') }}</x-badge>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            </x-card>

            <x-card>
                <x-slot:title>
                    {{ __('student.github_url') }}
                    @if ($project->github_url)
                        <x-badge color="success">{{ __('student.github_linked') }}</x-badge>
                    @endif
                </x-slot:title>

                <form method="POST" action="{{ route('student.project.github') }}">
                    @csrf
                    @method('PUT')
                    <x-form-field name="github_url" type="url" :label="__('student.github_url')"
                                  :hint="__('student.github_hint')" :value="$project->github_url" />
                    <x-button type="submit" icon="code">{{ __('common.save') }}</x-button>
                </form>
            </x-card>
        </aside>
    </div>
@endsection
