@extends('layouts.app')

@section('title', __('supervisor.archive'))

@section('content')
    <x-page-header :title="__('supervisor.archive')">
        <x-slot:actions>
            <x-button :href="route('supervisor.archive.create')" icon="archive">
                {{ __('supervisor.add_to_archive') }}
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <x-card>
        @if ($entries->isEmpty())
            <x-empty-state icon="archive" :title="__('supervisor.archive_empty')" />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('archive.title') }}</th>
                            <th>{{ __('archive.faculty') }}</th>
                            <th>{{ __('archive.year') }}</th>
                            <th>{{ __('archive.grade') }}</th>
                            <th>{{ __('supervisor.featured') }}</th>
                            <th>{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($entries as $entry)
                            <tr>
                                <td><a href="{{ route('public.archive.show', $entry) }}">{{ $entry->title }}</a></td>
                                <td class="text-muted">{{ $entry->faculty->localizedName() }}</td>
                                <td class="num">{{ $entry->year }}</td>
                                <td><x-badge :color="$entry->gradeColor()">{{ $entry->gradeLabel() }}</x-badge></td>
                                <td>
                                    <form method="POST" action="{{ route('supervisor.archive.featured', $entry) }}">
                                        @csrf
                                        <button class="btn btn-secondary btn-sm">
                                            <x-icon name="star" :size="14" />
                                            {{ $entry->is_featured ? __('supervisor.featured') : '—' }}
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <div class="decision">
                                        <a href="{{ route('supervisor.archive.edit', $entry) }}" class="btn btn-secondary btn-sm">
                                            {{ __('supervisor.save') }}
                                        </a>
                                        <form method="POST" action="{{ route('supervisor.archive.destroy', $entry) }}"
                                              data-confirm="{{ __('supervisor.archive_delete_confirm') }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-danger btn-sm">
                                                <x-icon name="trash" :size="14" />
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
@endsection
