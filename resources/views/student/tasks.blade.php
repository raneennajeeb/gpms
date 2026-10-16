@extends('layouts.app')

@section('title', __('student.tasks'))

@section('content')
    <x-page-header :title="__('student.tasks')" :subtitle="__('student.drag_hint')" />

    <x-card>
        <x-slot:title>{{ __('student.add_task') }}</x-slot:title>

        <form method="POST" action="{{ route('student.tasks.store') }}" class="task-form">
            @csrf
            <x-form-field name="title" :label="__('student.task_title')" required />

            <label class="form-field">
                <span class="form-field__label">{{ __('student.task_assignee') }}</span>
                <select name="assigned_to">
                    <option value="">{{ __('student.task_unassigned') }}</option>
                    @foreach ($members as $member)
                        <option value="{{ $member->user_id }}">{{ $member->user->name }}</option>
                    @endforeach
                </select>
            </label>

            <x-form-field name="due_date" type="date" :label="__('student.task_due')" />

            <x-button type="submit" icon="check-circle">{{ __('student.add_task') }}</x-button>
        </form>
    </x-card>

    <div class="kanban" data-kanban data-url="{{ route('student.tasks.reorder') }}">
        @foreach ($columns as $key => $tasks)
            <section class="kanban__column" data-status="{{ $key }}">
                <h2 class="kanban__title">
                    {{ __('student.task_' . $key) }}
                    <span class="kanban__count">{{ $tasks->count() }}</span>
                </h2>

                <div class="kanban__list" data-drop>
                    @foreach ($tasks as $task)
                        @php $overdue = $task->due_date && $task->status !== 'done' && $task->due_date->isPast(); @endphp
                        <article class="task @if ($overdue) task--overdue @endif"
                                 draggable="true" data-task="{{ $task->id }}">
                            <h3>{{ $task->title }}</h3>

                            @if ($task->assignee)
                                <p class="task__who">{{ $task->assignee->name }}</p>
                            @endif

                            @if ($task->due_date)
                                <p class="task__due">
                                    <x-icon name="calendar" :size="14" />
                                    {{ $task->due_date->translatedFormat('j M') }}
                                    @if ($overdue)
                                        <x-badge color="danger">{{ __('student.overdue') }}</x-badge>
                                    @endif
                                </p>
                            @endif

                            <form method="POST" action="{{ route('student.tasks.destroy', $task) }}"
                                  data-confirm="{{ __('student.task_delete_confirm') }}">
                                @csrf
                                @method('DELETE')
                                <button class="task__delete" aria-label="{{ __('student.task_deleted') }}">
                                    <x-icon name="trash" :size="14" />
                                </button>
                            </form>
                        </article>
                    @endforeach

                    @if ($tasks->isEmpty())
                        <p class="kanban__empty text-muted">{{ __('student.no_tasks') }}</p>
                    @endif
                </div>
            </section>
        @endforeach
    </div>
@endsection
