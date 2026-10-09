<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\ArchiveEntry;
use App\Models\Faculty;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ArchiveController extends Controller
{
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

    public function faculty(Faculty $faculty): View
    {
        $faculty->load('departments');

        $entries = $faculty->archiveEntries()
            ->withCardData()
            ->latest('defense_date')
            ->paginate(12);

        return view('public.archive.faculty', compact('faculty', 'entries'));
    }
}
