{{-- One A4 card, hidden on screen and the only thing that prints.
     The browser's own "Save as PDF" does the export: no PDF library. --}}
<section class="print-card" aria-hidden="true">
    <header class="print-card__head">
        <img src="{{ asset('assets/img/logo.png') }}" alt="" width="64" height="62">
        <div>
            <strong>{{ __('common.university') }}</strong>
            <span>{{ __('archive.title') }}</span>
        </div>
    </header>

    <h1>{{ $entry->title }}</h1>

    @if ($entry->idea_summary)
        <p class="print-card__lead">{{ $entry->idea_summary }}</p>
    @endif

    <dl class="print-card__meta">
        <div>
            <dt>{{ __('archive.students') }}</dt>
            <dd>{{ $entry->students->pluck('student_name')->join('، ') }}</dd>
        </div>
        <div>
            <dt>{{ __('archive.supervisor') }}</dt>
            <dd>{{ $entry->supervisor_name }}</dd>
        </div>
        <div>
            <dt>{{ __('archive.faculty') }}</dt>
            <dd>{{ $entry->faculty->localizedName() }}</dd>
        </div>
        <div>
            <dt>{{ __('archive.year') }}</dt>
            <dd>{{ $entry->year }}</dd>
        </div>
        <div>
            <dt>{{ __('archive.final_grade') }}</dt>
            <dd>{{ $entry->gradeLabel() }}</dd>
        </div>
        @if ($entry->competition)
            <div>
                <dt>{{ __('archive.competition_name') }}</dt>
                <dd>{{ $entry->competition_name }}{{ $entry->competition_result ? ' — ' . $entry->competition_result : '' }}</dd>
            </div>
        @endif
    </dl>

    @if ($entry->technologies->isNotEmpty())
        <p class="print-card__tech">
            <strong>{{ __('archive.technologies') }}:</strong>
            {{ $entry->technologies->pluck('name')->join('، ') }}
        </p>
    @endif

    <p class="print-card__desc">{{ $entry->description }}</p>

    <footer class="print-card__foot">{{ url()->current() }}</footer>
</section>
