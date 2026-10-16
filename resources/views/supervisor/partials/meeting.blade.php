<div class="meeting">
    <span class="meeting__date" aria-hidden="true">
        <strong>{{ $meeting->starts_at->format('j') }}</strong>
        <small>{{ $meeting->starts_at->translatedFormat('M') }}</small>
    </span>

    <div class="meeting__body">
        <strong>{{ $meeting->title }}</strong>
        <small>
            {{ $meeting->project->title }}
            <span aria-hidden="true">•</span>
            {{ $meeting->starts_at->translatedFormat('l j F — H:i') }}
            @if ($meeting->room)
                <span aria-hidden="true">•</span> {{ $meeting->room->name }}
            @endif
        </small>
        @if ($meeting->notes)
            <p class="text-muted">{{ $meeting->notes }}</p>
        @endif
    </div>

    <x-badge :color="match ($meeting->status) {
        'confirmed' => 'success',
        'requested' => 'warning',
        'cancelled' => 'danger',
        default => 'neutral',
    }">{{ __('student.meeting_status_' . $meeting->status) }}</x-badge>

    @if ($meeting->status === 'requested')
        <form method="POST" action="{{ route('supervisor.meetings.confirm', $meeting) }}">
            @csrf
            <button class="btn btn-primary btn-sm">{{ __('supervisor.confirm') }}</button>
        </form>
    @endif

    @if (in_array($meeting->status, ['requested', 'confirmed'], true))
        <details class="versions">
            <summary>{{ __('supervisor.cancel_meeting') }}</summary>
            <form method="POST" action="{{ route('supervisor.meetings.cancel', $meeting) }}" class="inline-form">
                @csrf
                <input type="text" name="reason" required maxlength="500" placeholder="{{ __('supervisor.cancel_reason') }}">
                <button class="btn btn-danger btn-sm">{{ __('supervisor.cancel_meeting') }}</button>
            </form>
        </details>
    @endif
</div>
