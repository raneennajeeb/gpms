@extends('layouts.app')

@section('title', __('student.team_chat'))

@section('content')
    <x-page-header :title="__('student.team_chat')" :subtitle="$team->name" />

    @if ($teamNotes->isNotEmpty())
        {{-- The supervisor's notes sit apart from the team's own messages --}}
        <x-card class="team-notes">
            <x-slot:title>{{ __('student.team_notes') }}</x-slot:title>

            @foreach ($teamNotes as $note)
                <div class="notice">
                    <p>{{ $note->body }}</p>
                    <small>{{ $note->user?->name }} — {{ $note->created_at->diffForHumans() }}</small>
                </div>
            @endforeach
        </x-card>
    @endif

    <x-card>
        <div class="chat" id="chat"
             data-chat
             data-url="{{ route('student.team-chat') }}"
             data-last="{{ $messages->max('id') ?? 0 }}">
            @forelse ($messages as $message)
                <div class="chat__row {{ $message->user_id === auth()->id() ? 'chat__row--mine' : '' }}">
                    <span class="chat__who">{{ $message->user->name }}</span>
                    <p class="chat__body">{{ $message->body }}</p>
                    <small class="chat__at">{{ $message->created_at->format('H:i') }}</small>
                </div>
            @empty
                <p class="text-muted" data-chat-empty>{{ __('student.no_messages') }}</p>
            @endforelse
        </div>

        <form method="POST" action="{{ route('student.team-chat.store') }}" class="chat-form">
            @csrf
            <label class="sr-only" for="chat-body">{{ __('student.message') }}</label>
            <input type="text" name="body" id="chat-body" maxlength="2000" required
                   placeholder="{{ __('student.message') }}" autocomplete="off">
            <x-button type="submit" icon="send">{{ __('student.send') }}</x-button>
        </form>

        @error('body') <p class="form-field__error">{{ $message }}</p> @enderror
    </x-card>
@endsection
