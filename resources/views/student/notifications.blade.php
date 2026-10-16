@extends('layouts.app')

@section('title', __('student.notifications'))

@section('content')
    <x-page-header :title="__('student.notifications')">
        <x-slot:actions>
            @if (auth()->user()->unreadNotifications->isNotEmpty())
                <form method="POST" action="{{ route('student.notifications.read-all') }}">
                    @csrf
                    <x-button type="submit" variant="secondary" icon="check-circle">
                        {{ __('student.mark_all_read') }}
                    </x-button>
                </form>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-card>
        @forelse ($notifications as $notification)
            <form method="POST" action="{{ route('student.notifications.read', $notification->id) }}"
                  class="notice {{ $notification->read_at ? '' : 'notice--unread' }}">
                @csrf
                <button type="submit" class="notice__button">
                    <p>{{ __($notification->data['key'], $notification->data['params'] ?? []) }}</p>
                    <small>
                        {{ $notification->created_at->diffForHumans() }}
                        @unless ($notification->read_at)
                            <x-badge color="info">{{ __('student.unread') }}</x-badge>
                        @endunless
                    </small>
                </button>
            </form>
        @empty
            <x-empty-state icon="bell" :title="__('student.no_notifications')" />
        @endforelse
    </x-card>

    {{ $notifications->links() }}
@endsection
