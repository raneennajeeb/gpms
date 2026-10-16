<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\ArchiveEntry;
use App\Models\Branch;
use App\Models\Faculty;
use App\Models\Technology;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ArchiveController extends Controller
{
    /** Columns the faculty page can sort by, mapped to a safe expression */
    private const SORTS = [
        'newest' => ['defense_date', 'desc'],
        'oldest' => ['defense_date', 'asc'],
        'grade' => ['final_score', 'desc'],
        'title' => ['title', 'asc'],
    ];

    public function index(): View
    {
        // Faculty counts change rarely, so keep them for 10 minutes
        $faculties = Cache::remember('archive.faculties', now()->addMinutes(10), function () {
            return Faculty::withCount(['archiveEntries', 'departments'])->orderBy('id')->get();
        });

        $featured = ArchiveEntry::where('is_featured', true)
            ->withCardData()
            ->latest('defense_date')
            ->take(6)
            ->get();

        $bestYear = ArchiveEntry::max('year');
        $best = ArchiveEntry::where('year', $bestYear)
            ->where('grade', 'excellent')
            ->withCardData()
            ->orderByDesc('final_score')
            ->take(6)
            ->get();

        $competitions = ArchiveEntry::competitions()
            ->withCardData()
            ->latest('defense_date')
            ->take(6)
            ->get();

        return view('public.archive.index', compact('faculties', 'featured', 'bestYear', 'best', 'competitions'));
    }

    public function faculty(Request $request, Faculty $faculty): View
    {
        $faculty->load('departments');

        $query = $faculty->archiveEntries()->withCardData();
        $this->applyFilters($query, $request);

        [$column, $direction] = self::SORTS[$request->query('sort')] ?? self::SORTS['newest'];

        $entries = $query
            ->orderBy($column, $direction)
            ->paginate(12)
            ->withQueryString(); // keeps the filters across pages

        return view('public.archive.faculty', [
            'faculty' => $faculty,
            'entries' => $entries,
            'filters' => $this->filterOptions($faculty),
            'chips' => $this->activeChips($request, $faculty),
        ]);
    }

    public function show(ArchiveEntry $entry): View
    {
        $entry->load(['faculty', 'department', 'branch', 'students', 'committee', 'technologies', 'files']);

        return view('public.archive.show', [
            'entry' => $entry,
            'similar' => $entry->similarEntries(),
        ]);
    }

    public function competitions(Request $request): View
    {
        $query = ArchiveEntry::competitions()->withCardData();

        if ($year = $request->query('year')) {
            $query->where('year', $year);
        }

        if ($result = $request->query('result')) {
            $query->where('competition_result', $result);
        }

        $entries = $query->latest('defense_date')->paginate(12)->withQueryString();

        return view('public.archive.competitions', [
            'entries' => $entries,
            'years' => ArchiveEntry::competitions()->distinct()->orderByDesc('year')->pluck('year'),
            'results' => ArchiveEntry::competitions()->whereNotNull('competition_result')
                ->distinct()->orderBy('competition_result')->pluck('competition_result'),
        ]);
    }

    public function toggleFavorite(Request $request, ArchiveEntry $entry): RedirectResponse
    {
        $changed = $request->user()->favorites()->toggle($entry->id);

        return back()->with('success', __(
            $changed['attached'] ? 'archive.favorite_added' : 'archive.favorite_removed'
        ));
    }

    /* ---------- Filters ---------- */

    private function applyFilters(Builder|Relation $query, Request $request): void
    {
        $query
            ->when($request->query('q'), fn (Builder $q, $term) => $q->search($term))
            ->when($request->query('year'), fn (Builder $q, $year) => $q->where('year', $year))
            ->when($request->query('branch'), fn (Builder $q, $id) => $q->where('branch_id', $id))
            ->when($request->query('grade'), fn (Builder $q, $grade) => $q->where('grade', $grade))
            ->when($request->query('technology'), fn (Builder $q, $id) => $q->whereHas(
                'technologies',
                fn (Builder $t) => $t->where('technologies.id', $id)
            ));

        // "no" is a real choice here, so check the key rather than the value
        if ($request->filled('competition')) {
            $query->where('competition', $request->query('competition') === 'yes');
        }
    }

    /** Only the values that actually occur in this faculty, so no filter returns nothing */
    private function filterOptions(Faculty $faculty): array
    {
        $entries = $faculty->archiveEntries();

        return [
            'years' => (clone $entries)->distinct()->orderByDesc('year')->pluck('year'),
            'grades' => (clone $entries)->distinct()->pluck('grade'),
            'branches' => Branch::whereIn('id', (clone $entries)->distinct()->pluck('branch_id'))
                ->orderBy('name_ar')->get(),
            'technologies' => Technology::whereHas(
                'archiveEntries',
                fn (Builder $q) => $q->where('faculty_id', $faculty->id)
            )->orderBy('name')->get(),
            'sorts' => array_keys(self::SORTS),
        ];
    }

    /** The removable chips above the results */
    private function activeChips(Request $request, Faculty $faculty): array
    {
        $options = $this->filterOptions($faculty);
        $chips = [];

        foreach (['q', 'year', 'branch', 'grade', 'technology', 'competition'] as $key) {
            if (! $request->filled($key)) {
                continue;
            }

            $value = $request->query($key);

            $chips[] = [
                'key' => $key,
                'label' => match ($key) {
                    'branch' => $options['branches']->firstWhere('id', (int) $value)?->localizedName(),
                    'technology' => $options['technologies']->firstWhere('id', (int) $value)?->name,
                    'grade' => __('grades.' . $value),
                    'competition' => __('archive.competition_' . $value),
                    default => $value,
                },
                // The same URL minus this one filter
                'url' => $request->fullUrlWithoutQuery([$key, 'page']),
            ];
        }

        return array_filter($chips, fn (array $chip) => $chip['label'] !== null);
    }
}
