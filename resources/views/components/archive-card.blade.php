@props(['entry'])

@php
    // The details page is built in part 7
    $url = Route::has('public.archive.show') ? route('public.archive.show', $entry) : '#';
@endphp

<article class="archive-card">
    <a href="{{ $url }}" class="archive-card__cover" style="--cover-color: {{ $entry->faculty->color ?? '#1E4785' }}" tabindex="-1" aria-hidden="true">
        @if ($entry->cover_image)
            <img src="{{ asset('storage/' . $entry->cover_image) }}" alt="" loading="lazy">
        @else
            <x-icon :name="$entry->faculty->icon ?? 'folder'" :size="40" />
        @endif
    </a>

    <div class="archive-card__body">
        <div class="archive-card__badges">
            <x-badge :color="$entry->gradeColor()">{{ $entry->gradeLabel() }}</x-badge>
            @if ($entry->competition)
                <x-badge color="warning"><x-icon name="trophy" :size="14" /> {{ __('archive.competition') }}</x-badge>
            @endif
        </div>

        <h3 class="archive-card__title"><a href="{{ $url }}">{{ $entry->title }}</a></h3>

        <dl class="archive-card__meta">
            <div>
                <dt><x-icon name="users" :size="16" /> {{ __('archive.students') }}</dt>
                <dd>{{ $entry->students->pluck('student_name')->join('، ') }}</dd>
            </div>
            <div>
                <dt><x-icon name="graduation-cap" :size="16" /> {{ __('archive.supervisor') }}</dt>
                <dd>{{ $entry->supervisor_name }}</dd>
            </div>
            <div>
                <dt><x-icon name="scale" :size="16" /> {{ __('archive.committee') }}</dt>
                <dd>{{ $entry->committee->pluck('member_name')->join('، ') }}</dd>
            </div>
            <div>
                <dt><x-icon name="calendar" :size="16" /> {{ __('archive.defense_date') }}</dt>
                <dd>{{ $entry->defense_date->translatedFormat('j F Y') }}</dd>
            </div>
            <div>
                <dt><x-icon name="map-pin" :size="16" /> {{ __('archive.hall') }}</dt>
                <dd>{{ $entry->hall_name }}</dd>
            </div>
            @if ($entry->competition && $entry->competition_name)
                <div>
                    <dt><x-icon name="trophy" :size="16" /> {{ __('archive.competition_name') }}</dt>
                    <dd>{{ $entry->competition_name }}{{ $entry->competition_result ? ' — ' . $entry->competition_result : '' }}</dd>
                </div>
            @endif
            @if ($entry->branch)
                <div>
                    <dt><x-icon name="building" :size="16" /> {{ __('archive.branch') }}</dt>
                    <dd>{{ $entry->branch->localizedName() }}</dd>
                </div>
            @endif
        </dl>

        <x-button variant="secondary" :href="$url" class="btn-block">{{ __('archive.view_details') }}</x-button>
    </div>
</article>
