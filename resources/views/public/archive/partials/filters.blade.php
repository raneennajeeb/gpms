{{-- Filters travel in the query string so a filtered page can be shared as a link --}}
<form method="GET" action="{{ route('public.archive.faculty', $faculty) }}" class="filters">
    <div class="filters__search">
        <label class="sr-only" for="filter-q">{{ __('archive.search') }}</label>
        <input type="search" id="filter-q" name="q" value="{{ request('q') }}"
               placeholder="{{ __('archive.search_placeholder') }}">
        <button type="submit" class="btn btn-primary">{{ __('archive.search') }}</button>
    </div>

    <div class="filters__row">
        <label class="filters__field">
            <span>{{ __('archive.year') }}</span>
            <select name="year">
                <option value="">{{ __('archive.all') }}</option>
                @foreach ($filters['years'] as $year)
                    <option value="{{ $year }}" @selected(request('year') == $year)>{{ $year }}</option>
                @endforeach
            </select>
        </label>

        <label class="filters__field">
            <span>{{ __('archive.branch') }}</span>
            <select name="branch">
                <option value="">{{ __('archive.all') }}</option>
                @foreach ($filters['branches'] as $branch)
                    <option value="{{ $branch->id }}" @selected(request('branch') == $branch->id)>{{ $branch->localizedName() }}</option>
                @endforeach
            </select>
        </label>

        <label class="filters__field">
            <span>{{ __('archive.grade') }}</span>
            <select name="grade">
                <option value="">{{ __('archive.all') }}</option>
                @foreach ($filters['grades'] as $grade)
                    <option value="{{ $grade }}" @selected(request('grade') === $grade)>{{ __('grades.' . $grade) }}</option>
                @endforeach
            </select>
        </label>

        <label class="filters__field">
            <span>{{ __('archive.technology') }}</span>
            <select name="technology">
                <option value="">{{ __('archive.all') }}</option>
                @foreach ($filters['technologies'] as $technology)
                    <option value="{{ $technology->id }}" @selected(request('technology') == $technology->id)>{{ $technology->name }}</option>
                @endforeach
            </select>
        </label>

        <label class="filters__field">
            <span>{{ __('archive.competition') }}</span>
            <select name="competition">
                <option value="">{{ __('archive.all') }}</option>
                <option value="yes" @selected(request('competition') === 'yes')>{{ __('archive.competition_yes') }}</option>
                <option value="no" @selected(request('competition') === 'no')>{{ __('archive.competition_no') }}</option>
            </select>
        </label>

        <label class="filters__field">
            <span>{{ __('archive.sort') }}</span>
            <select name="sort">
                @foreach ($filters['sorts'] as $sort)
                    <option value="{{ $sort }}" @selected(request('sort', 'newest') === $sort)>{{ __('archive.sort_' . $sort) }}</option>
                @endforeach
            </select>
        </label>

        <button type="submit" class="btn btn-secondary">{{ __('archive.apply') }}</button>
    </div>
</form>

@if ($chips)
    <div class="chips">
        @foreach ($chips as $chip)
            <a href="{{ $chip['url'] }}" class="chip" title="{{ __('archive.remove_filter') }}">
                <span>{{ $chip['label'] }}</span>
                <x-icon name="x" :size="14" />
            </a>
        @endforeach

        <a href="{{ route('public.archive.faculty', $faculty) }}" class="chips__clear">{{ __('archive.clear_filters') }}</a>
    </div>
@endif
