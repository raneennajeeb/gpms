@extends('layouts.public')

@section('title', $faculty->localizedName())

@section('content')
    <div class="container">
        <x-breadcrumb :items="[
            ['label' => __('nav.home'), 'url' => route('public.home')],
            ['label' => __('nav.archive')],
            ['label' => $faculty->localizedName()],
        ]" />

        <x-page-header :title="$faculty->localizedName()"
                       :subtitle="__('archive.faculty_projects', ['count' => $entries->total()])" />

        @if ($entries->isEmpty())
            <x-card>
                <x-empty-state icon="archive" :title="__('archive.empty_title')" :text="__('archive.empty_text')" />
            </x-card>
        @else
            <div class="archive-grid">
                @foreach ($entries as $entry)
                    <x-archive-card :entry="$entry" />
                @endforeach
            </div>

            {{ $entries->links() }}
        @endif
    </div>
@endsection
