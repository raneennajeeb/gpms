{{-- One meeting row, with a calendar-style date badge --}}
<div class="meeting">
    <span class="meeting__date" aria-hidden="true">
        <strong>{{ $meeting->starts_at->format('j') }}</strong>
        <small>{{ $meeting->starts_at->translatedFormat('M') }}</small>
    </span>

    <div class="meeting__body">
        <strong>{{ $meeting->title }}</strong>
        <small>
            {{ $meeting->starts_at->translatedFormat('l j F Y — H:i') }}
            @if ($meeting->room)
                <span aria-hidden="true">•</span> {{ __('student.location') }}: {{ $meeting->room->name }}
            @elseif ($meeting->location_note)
                <span aria-hidden="true">•</span> {{ $meeting->location_note }}
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
</div>
