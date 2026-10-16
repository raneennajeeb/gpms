@extends('layouts.app')

@section('title', __('nav.dashboard'))

@section('content')
    <section class="welcome-card">
        <div>
            <h1>{{ __('student.welcome', ['name' => $student->name]) }}</h1>
            <p>
                {{ $student->branch?->localizedName() }}
                @if ($student->section)
                    <span aria-hidden="true">•</span> {{ __('nav.section') }} {{ $student->section->number }}
                @endif
                @if ($supervisor)
                    <span aria-hidden="true">•</span> {{ __('archive.supervisor') }}: {{ $supervisor->name }}
                @endif
            </p>
        </div>

        <x-button type="button" variant="secondary" icon="refresh" data-refresh
                  data-url="{{ route('student.dashboard') }}"
                  data-done="{{ __('student.refreshed') }}">{{ __('student.refresh') }}</x-button>
    </section>

    @if ($invitations->isNotEmpty())
        @foreach ($invitations as $invitation)
            <x-alert type="info" icon="users">
                {{ __('student.invite_from', ['team' => $invitation->team->name]) }}
                <form method="POST" action="{{ route('student.team.respond', $invitation) }}" class="alert__actions">
                    @csrf
                    <button name="answer" value="accept" class="btn btn-primary btn-sm">{{ __('student.accept') }}</button>
                    <button name="answer" value="decline" class="btn btn-secondary btn-sm">{{ __('student.decline') }}</button>
                </form>
            </x-alert>
        @endforeach
    @endif

    @if (! $team)
        <x-card>
            <x-empty-state icon="users" :title="__('student.no_team_title')" :text="__('student.no_team_text')">
                <x-button :href="route('student.team')" icon="users">{{ __('student.create_team') }}</x-button>
            </x-empty-state>
        </x-card>
    @elseif (! $project)
        <x-card>
            <x-empty-state icon="lightbulb" :title="__('student.no_project_title')" :text="__('student.no_project_text')">
                <x-button :href="route('student.proposal')" icon="upload">{{ __('student.submit_proposal') }}</x-button>
            </x-empty-state>
        </x-card>
    @else
        <div class="stat-grid">
            <x-stat-card icon="layout-dashboard" :label="__('student.progress')" :value="$project->progress_percent . '%'" data-stat="progress" />
            <x-stat-card icon="check-circle" :label="__('student.status')" :value="$statusLabel" data-stat="status" />
            <x-stat-card icon="star" :label="__('student.supervisor_rating')"
                         :value="$evaluationLabel ?? __('student.not_rated')" data-stat="evaluation" />
            <x-stat-card icon="calendar" :label="__('student.next_event')"
                         :value="$nextEvent ? $nextEvent['date']->translatedFormat('j M') : __('student.no_next_event')"
                         :hint="$nextEvent['title'] ?? null" data-stat="next" />
        </div>
    @endif

    <div class="dash-grid">
        <div class="dash-grid__main">
            {{-- One action, never a list: the most blocking thing right now --}}
            <x-card>
                <x-slot:title>{{ __('student.what_now') }}</x-slot:title>

                @if ($nextAction)
                    <p>{{ __('student.action_' . $nextAction['key'], ['title' => $nextAction['title'] ?? '']) }}</p>
                    @if ($nextAction['date'])
                        <p class="text-muted">{{ $nextAction['date']->translatedFormat('l j F Y') }}</p>
                    @endif
                    <x-button :href="$nextAction['url']" icon="arrow-left">{{ __('student.go') }}</x-button>
                @else
                    <p class="text-muted">{{ __('student.nothing_pending') }}</p>
                @endif
            </x-card>

            @if ($project)
                <x-card>
                    <x-slot:title>{{ __('student.stages') }}</x-slot:title>

                    <ol class="stage-list">
                        @foreach ($project->stages as $stage)
                            <li class="stage stage--{{ $stage->status }}">
                                <span class="stage__mark">
                                    @if ($stage->status === 'completed')
                                        <x-icon name="check-circle" :size="18" />
                                    @elseif ($stage->status === 'in_progress')
                                        <x-icon name="refresh" :size="18" class="spin" />
                                    @else
                                        {{ $loop->iteration }}
                                    @endif
                                </span>

                                <span class="stage__body">
                                    <strong>{{ $stage->name_ar }}</strong>
                                    @if ($stage->status === 'completed')
                                        <small>
                                            {{ __('student.stage_done') }}
                                            @if ($stage->approver)
                                                — {{ __('student.approved_by', ['name' => $stage->approver->name]) }}
                                            @endif
                                            @if ($stage->approved_at)
                                                ({{ $stage->approved_at->translatedFormat('j M Y') }})
                                            @endif
                                        </small>
                                    @elseif ($stage->status === 'in_progress')
                                        <small>{{ __('student.stage_in_progress') }}</small>
                                    @elseif ($stage->due_date)
                                        <small>{{ __('student.due', ['date' => $stage->due_date->translatedFormat('j M Y')]) }}</small>
                                    @endif
                                </span>

                                <span class="stage__weight">{{ $stage->weight }}%</span>
                            </li>
                        @endforeach
                    </ol>

                    <x-slot:footer>
                        <a href="{{ route('student.project') }}">{{ __('student.full_details') }}</a>
                    </x-slot:footer>
                </x-card>
            @endif
        </div>

        <aside class="dash-grid__side">
            @if ($supervisor)
                <x-card>
                    <x-slot:title>{{ __('student.my_supervisor') }}</x-slot:title>

                    <dl class="meta-list">
                        <div>
                            <dt>{{ __('common.name') }}</dt>
                            <dd>{{ $supervisor->name }}</dd>
                        </div>
                        <div>
                            <dt>{{ __('common.email') }}</dt>
                            <dd><a href="mailto:{{ $supervisor->email }}">{{ $supervisor->email }}</a></dd>
                        </div>
                        @if ($supervisor->phone)
                            <div>
                                <dt>{{ __('common.phone') }}</dt>
                                <dd>{{ $supervisor->phone }}</dd>
                            </div>
                        @endif
                        @if ($supervisor->office_hours)
                            <div>
                                <dt>{{ __('student.office_hours') }}</dt>
                                <dd>{{ $supervisor->office_hours }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt>{{ __('student.next_meeting') }}</dt>
                            <dd>
                                @php $meeting = $project?->meetings()->where('starts_at', '>', now())->orderBy('starts_at')->first(); @endphp
                                {{ $meeting ? $meeting->starts_at->translatedFormat('l j F — H:i') : __('student.no_meeting') }}
                            </dd>
                        </div>
                    </dl>
                </x-card>
            @endif

            <x-card>
                <x-slot:title>{{ __('student.latest_notifications') }}</x-slot:title>

                @forelse ($notifications as $notification)
                    <div class="notice {{ $notification->read_at ? '' : 'notice--unread' }}">
                        <p>{{ __($notification->data['key'], $notification->data['params'] ?? []) }}</p>
                        <small>{{ $notification->created_at->diffForHumans() }}</small>
                    </div>
                @empty
                    <p class="text-muted">{{ __('student.no_notifications') }}</p>
                @endforelse
            </x-card>
        </aside>
    </div>
@endsection
