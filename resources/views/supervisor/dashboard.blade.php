@extends('layouts.app')

@section('title', __('nav.dashboard'))

@section('content')
    <section class="welcome-card">
        <div>
            <h1>{{ __('student.welcome', ['name' => auth()->user()->name]) }}</h1>
            <p>{{ __('roles.supervisor') }}</p>
        </div>
    </section>

    <div class="stat-grid">
        <x-stat-card icon="users" :label="__('supervisor.my_students')" :value="$stats['students']" data-count-up="{{ $stats['students'] }}" />
        <x-stat-card icon="lightbulb" :label="__('supervisor.pending_ideas')" :value="$stats['pending']" color="warning" data-count-up="{{ $stats['pending'] }}" />
        <x-stat-card icon="layout-dashboard" :label="__('supervisor.active_projects')" :value="$stats['active']" data-count-up="{{ $stats['active'] }}" />
        <x-stat-card icon="alert-circle" :label="__('supervisor.late_projects')" :value="$stats['late']" color="danger" data-count-up="{{ $stats['late'] }}" />
    </div>

    <div class="dash-grid">
        <div class="dash-grid__main">
            {{-- Two plain bar charts, drawn in CSS: no chart library --}}
            <x-card>
                <x-slot:title>{{ __('supervisor.by_status') }}</x-slot:title>

                @php $max = max(1, $byStatus->max() ?? 1); @endphp
                <div class="bars">
                    @foreach ($byStatus as $status => $count)
                        <div class="bars__row">
                            <span class="bars__label">{{ __('projects.status.' . $status) }}</span>
                            <span class="bars__track">
                                <span class="bars__fill" style="width: {{ round($count / $max * 100) }}%"></span>
                            </span>
                            <span class="bars__value">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            </x-card>

            <x-card>
                <x-slot:title>{{ __('supervisor.by_section') }}</x-slot:title>

                <div class="bars">
                    @foreach ($bySection as $section => $percent)
                        <div class="bars__row">
                            <span class="bars__label">{{ __('nav.section') }} {{ $section }}</span>
                            <span class="bars__track">
                                <span class="bars__fill bars__fill--success" style="width: {{ $percent }}%"></span>
                            </span>
                            <span class="bars__value">{{ $percent }}%</span>
                        </div>
                    @endforeach
                </div>
            </x-card>

            <x-card>
                <x-slot:title>{{ __('supervisor.needs_attention') }}</x-slot:title>

                @forelse ($needsAttention as $row)
                    <div class="attention attention--{{ $row['health']->level }}">
                        <x-icon :name="$row['health']->icon()" :size="18" />
                        <div class="attention__body">
                            <strong>{{ $row['project']->title }}</strong>
                            <small>
                                {{ __('student.health_' . $row['health']->level) }}
                                @foreach ($row['health']->reasons as $reason)
                                    <span aria-hidden="true">•</span>
                                    {{ __('student.reason_' . $reason['key'], ['count' => $reason['count']]) }}
                                @endforeach
                            </small>
                        </div>
                        <a href="{{ route('supervisor.projects.show', $row['project']) }}" class="btn btn-secondary btn-sm">
                            {{ __('supervisor.open_project') }}
                        </a>
                    </div>
                @empty
                    <p class="text-muted">{{ __('supervisor.all_healthy') }}</p>
                @endforelse
            </x-card>
        </div>

        <aside class="dash-grid__side">
            <x-card>
                <x-slot:title>{{ __('supervisor.upcoming') }}</x-slot:title>

                @forelse ($sessions as $session)
                    <div class="notice">
                        <p>{{ __('student.defense') }} — {{ __('nav.section') }} {{ $session->section->number }}</p>
                        <small>
                            {{ $session->session_date->translatedFormat('l j F') }} —
                            {{ substr($session->start_time, 0, 5) }} — {{ $session->room->name }}
                        </small>
                    </div>
                @empty
                @endforelse

                @forelse ($meetings as $meeting)
                    <div class="notice">
                        <p>{{ $meeting->title }}</p>
                        <small>{{ $meeting->starts_at->translatedFormat('l j F — H:i') }}</small>
                    </div>
                @empty
                    @if ($sessions->isEmpty())
                        <p class="text-muted">{{ __('supervisor.nothing_upcoming') }}</p>
                    @endif
                @endforelse
            </x-card>

            <x-card>
                <x-slot:title>{{ __('supervisor.latest_inquiries') }}</x-slot:title>

                @forelse ($inquiries as $inquiry)
                    <div class="notice {{ $inquiry->status === 'new' ? 'notice--unread' : '' }}">
                        <p>{{ Str::limit($inquiry->message, 70) }}</p>
                        <small>{{ $inquiry->student->name }} — {{ $inquiry->created_at->diffForHumans() }}</small>
                    </div>
                @empty
                    <p class="text-muted">{{ __('supervisor.no_inquiries') }}</p>
                @endforelse

                <x-slot:footer>
                    <a href="{{ route('supervisor.inquiries') }}">{{ __('supervisor.inquiries') }}</a>
                </x-slot:footer>
            </x-card>
        </aside>
    </div>
@endsection
