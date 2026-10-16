@extends('layouts.app')

@section('title', __('student.contact_supervisor'))

@section('content')
    <x-page-header :title="__('student.contact_supervisor')" :subtitle="$supervisor?->name" />

    <div class="dash-grid">
        <div class="dash-grid__main">
            <x-card>
                <form method="POST" action="{{ route('student.contact-supervisor.store') }}" enctype="multipart/form-data">
                    @csrf

                    <label class="form-field">
                        <span class="form-field__label">{{ __('student.inquiry_type') }}</span>
                        <select name="type" required>
                            @foreach ($types as $type)
                                <option value="{{ $type }}" @selected(old('type') === $type)>
                                    {{ __('student.inquiry_types.' . $type) }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    @error('type') <p class="form-field__error">{{ $message }}</p> @enderror

                    <x-form-field name="message" type="textarea" rows="6" :label="__('student.message')" required />

                    <label class="form-field">
                        <span class="form-field__label">{{ __('student.attachment') }}</span>
                        <input type="file" name="attachment" accept=".png,.jpg,.jpeg,.pdf,.zip">
                        <span class="form-field__hint">{{ __('student.attachment_hint') }}</span>
                    </label>
                    @error('attachment') <p class="form-field__error">{{ $message }}</p> @enderror

                    <x-button type="submit" icon="send">{{ __('student.send_inquiry') }}</x-button>
                </form>
            </x-card>

            <x-card>
                <x-slot:title>{{ __('student.my_inquiries') }}</x-slot:title>

                @forelse ($inquiries as $inquiry)
                    <div class="inquiry">
                        <div class="inquiry__head">
                            <strong>{{ __('student.inquiry_types.' . $inquiry->type) }}</strong>
                            <x-badge :color="$inquiry->status === 'replied' ? 'success' : 'neutral'">
                                {{ __('student.inquiry_status_' . $inquiry->status) }}
                            </x-badge>
                            <small class="text-muted">{{ $inquiry->created_at->translatedFormat('j M Y') }}</small>
                        </div>

                        <p>{{ $inquiry->message }}</p>

                        @if ($inquiry->reply)
                            <div class="inquiry__reply">
                                <strong>{{ __('student.supervisor_reply') }}</strong>
                                <p>{{ $inquiry->reply }}</p>
                                <small>{{ $inquiry->replied_at?->diffForHumans() }}</small>
                            </div>
                        @else
                            <small class="text-muted">{{ __('student.awaiting_reply') }}</small>
                        @endif
                    </div>
                @empty
                    <p class="text-muted">{{ __('student.no_inquiries') }}</p>
                @endforelse
            </x-card>
        </div>

        <aside class="dash-grid__side">
            <x-card>
                <x-slot:title>{{ __('student.supervisor_feedback') }}</x-slot:title>

                @forelse ($feedback as $comment)
                    <div class="notice">
                        <p>{{ $comment->body }}</p>

                        @foreach ($comment->attachments as $attachment)
                            <a href="{{ route('student.attachments.download', $attachment) }}" class="file-chip">
                                <x-icon name="file" :size="14" /> {{ $attachment->original_name }}
                            </a>
                        @endforeach

                        <small>{{ $comment->user?->name }} — {{ $comment->created_at->diffForHumans() }}</small>
                    </div>
                @empty
                    <p class="text-muted">{{ __('student.no_feedback') }}</p>
                @endforelse
            </x-card>
        </aside>
    </div>
@endsection
