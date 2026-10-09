{{-- A horizontal row of up to 6 archive cards. Hidden when there is nothing to show. --}}
@if ($entries->isNotEmpty())
    <section class="archive-section">
        <div class="archive-section__head">
            <h2 class="archive-section__title">{{ $title }}</h2>
            @if ($url)
                <a href="{{ $url }}">{{ __('archive.view_all') }}</a>
            @endif
        </div>

        <div class="archive-row">
            @foreach ($entries as $entry)
                <x-archive-card :entry="$entry" />
            @endforeach
        </div>
    </section>
@endif
