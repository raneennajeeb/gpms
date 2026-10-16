@extends('layouts.app')

@section('title', __('supervisor.inquiries'))

@section('content')
    <x-page-header :title="__('supervisor.inquiries')" />

    <x-card>
        @forelse ($inquiries as $inquiry)
            <div class="inquiry">
                <div class="inquiry__head">
                    <strong>{{ __('student.inquiry_types.' . $inquiry->type) }}</strong>
                    <x-badge :color="$inquiry->status === 'replied' ? 'success' : 'warning'">
                        {{ __('student.inquiry_status_' . $inquiry->status) }}
                    </x-badge>
                    <small class="text-muted">
                        {{ $inquiry->student->name }}
                        <span aria-hidden="true">•</span>
                        {{ $inquiry->project->title }}
                        <span aria-hidden="true">•</span>
                        {{ $inquiry->created_at->translatedFormat('j M Y') }}
                    </small>
                </div>

                <p>{{ $inquiry->message }}</p>

                @if ($inquiry->reply)
                    <div class="inquiry__reply">
                        <strong>{{ __('supervisor.reply') }}</strong>
                        <p>{{ $inquiry->reply }}</p>
                        <small>{{ $inquiry->replied_at?->diffForHumans() }}</small>
                    </div>
                @else
                    <form method="POST" action="{{ route('supervisor.inquiries.reply', $inquiry) }}" class="inline-form">
                        @csrf
                        <input type="text" name="reply" required minlength="5" maxlength="1000"
                               placeholder="{{ __('supervisor.reply') }}">
                        <x-button type="submit" icon="send">{{ __('supervisor.send_reply') }}</x-button>
                    </form>
                @endif
            </div>
        @empty
            <x-empty-state icon="send" :title="__('supervisor.inquiries_empty')" />
        @endforelse
    </x-card>
@endsection
