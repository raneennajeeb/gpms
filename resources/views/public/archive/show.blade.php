@extends('layouts.public')

@section('title', $entry->title)

@push('scripts')
    {{-- qrcode-generator (MIT), served from our own assets: no CDN, no Composer, no npm --}}
    <script src="{{ asset('assets/js/qrcode.min.js') }}" defer></script>
@endpush

@section('content')
    <div class="container">
        <x-breadcrumb :items="[
            ['label' => __('nav.home'), 'url' => route('public.home')],
            ['label' => __('nav.archive'), 'url' => route('public.archive.index')],
            ['label' => $entry->faculty->localizedName(), 'url' => route('public.archive.faculty', $entry->faculty)],
            ['label' => $entry->title],
        ]" />

        <article class="entry">
            <header class="entry__head">
                <div class="entry__badges">
                    <x-badge color="neutral">{{ __('archive.archived') }}</x-badge>
                    <x-badge :color="$entry->gradeColor()">{{ $entry->gradeLabel() }}</x-badge>
                    @if ($entry->competition)
                        <x-badge color="warning"><x-icon name="trophy" :size="14" /> {{ __('archive.competition') }}</x-badge>
                    @endif
                </div>

                <h1 class="entry__title">{{ $entry->title }}</h1>

                @if ($entry->idea_summary)
                    <p class="entry__lead">{{ $entry->idea_summary }}</p>
                @endif

                <div class="entry__actions">
                    @auth
                        @php $isFavorite = auth()->user()->favorites->contains($entry->id); @endphp
                        <form method="POST" action="{{ route('public.archive.favorite', $entry) }}">
                            @csrf
                            <x-button type="submit" :variant="$isFavorite ? 'primary' : 'secondary'" icon="star">
                                {{ $isFavorite ? __('archive.favorite_remove') : __('archive.favorite_add') }}
                            </x-button>
                        </form>
                    @else
                        <x-button :href="route('login')" variant="secondary" icon="star">{{ __('archive.favorite_guest') }}</x-button>
                    @endauth

                    <x-button type="button" variant="secondary" icon="share" data-share
                              data-copied="{{ __('archive.share_copied') }}"
                              data-failed="{{ __('archive.share_failed') }}">{{ __('archive.share') }}</x-button>

                    <x-button type="button" variant="secondary" icon="download" data-print>{{ __('archive.export_pdf') }}</x-button>
                </div>
            </header>

            <div class="entry__layout">
                <div class="entry__main">
                    <section class="entry__section">
                        <h2>{{ __('archive.about_project') }}</h2>
                        <p>{{ $entry->description }}</p>
                    </section>

                    @if ($entry->technologies->isNotEmpty())
                        <section class="entry__section">
                            <h2>{{ __('archive.technologies') }}</h2>
                            <div class="tag-list">
                                @foreach ($entry->technologies as $technology)
                                    <x-tag>{{ $technology->name }}</x-tag>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    @if ($entry->files->isNotEmpty())
                        <section class="entry__section">
                            <h2>{{ __('archive.files') }}</h2>
                            <ul class="file-list">
                                @foreach ($entry->files as $file)
                                    <li>
                                        <x-icon name="file" :size="18" />
                                        <span>{{ $file->original_name ?? $file->file_name }}</span>
                                        <a href="{{ asset('storage/' . $file->file_path) }}" download>{{ __('archive.download') }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @if ($entry->github_url)
                        <section class="entry__section">
                            <a href="{{ $entry->github_url }}" target="_blank" rel="noopener noreferrer" class="entry__github">
                                <x-icon name="code" :size="18" /> {{ __('archive.github') }}
                            </a>
                        </section>
                    @endif
                </div>

                <aside class="entry__side">
                    <x-card>
                        <h2 class="entry__side-title">{{ __('archive.details') }}</h2>

                        <dl class="entry__meta">
                            {{-- Names only: no university IDs or emails on a public page --}}
                            <div>
                                <dt>{{ __('archive.students') }}</dt>
                                <dd>{{ $entry->students->pluck('student_name')->join('، ') }}</dd>
                            </div>
                            <div>
                                <dt>{{ __('archive.supervisor') }}</dt>
                                <dd>{{ $entry->supervisor_name }}</dd>
                            </div>
                            <div>
                                <dt>{{ __('archive.committee') }}</dt>
                                <dd>{{ $entry->committee->pluck('member_name')->join('، ') }}</dd>
                            </div>
                            <div>
                                <dt>{{ __('archive.defense_date') }}</dt>
                                <dd>{{ $entry->defense_date->translatedFormat('j F Y') }}</dd>
                            </div>
                            <div>
                                <dt>{{ __('archive.hall') }}</dt>
                                <dd>{{ $entry->hall_name }}</dd>
                            </div>
                            @if ($entry->branch)
                                <div>
                                    <dt>{{ __('archive.branch') }}</dt>
                                    <dd>{{ $entry->branch->localizedName() }}</dd>
                                </div>
                            @endif
                            <div>
                                <dt>{{ __('archive.faculty') }}</dt>
                                <dd>{{ $entry->faculty->localizedName() }}</dd>
                            </div>
                            @if ($entry->department)
                                <div>
                                    <dt>{{ __('archive.department') }}</dt>
                                    <dd>{{ $entry->department->localizedName() }}</dd>
                                </div>
                            @endif
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
                                    <dd>{{ $entry->competition_name }}</dd>
                                </div>
                                @if ($entry->competition_result)
                                    <div>
                                        <dt>{{ __('archive.competition_result') }}</dt>
                                        <dd>{{ $entry->competition_result }}</dd>
                                    </div>
                                @endif
                            @endif
                        </dl>
                    </x-card>

                    <x-card class="entry__qr">
                        <div id="qr" data-qr="{{ url()->current() }}"></div>
                        <p class="text-muted">{{ __('archive.qr_hint') }}</p>
                    </x-card>
                </aside>
            </div>

            @if ($similar->isNotEmpty())
                <section class="archive-section">
                    <h2 class="archive-section__title">{{ __('archive.similar') }}</h2>
                    <div class="archive-grid">
                        @foreach ($similar as $other)
                            <x-archive-card :entry="$other" />
                        @endforeach
                    </div>
                </section>
            @endif
        </article>
    </div>

    @include('public.archive.partials.print-card')
@endsection
