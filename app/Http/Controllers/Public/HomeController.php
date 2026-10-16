<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\ArchiveEntry;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $stats = Cache::remember('home.stats', now()->addMinutes(10), fn () => $this->stats());

        $featured = ArchiveEntry::where('is_featured', true)
            ->withCardData()
            ->latest('defense_date')
            ->take(3)
            ->get();

        return view('public.home', compact('stats', 'featured'));
    }

    private function stats(): array
    {
        $currentProjects = Project::whereHas('semester', fn ($query) => $query->where('is_current', true));

        $total = (clone $currentProjects)->count();
        $finished = (clone $currentProjects)->whereIn('status', ['completed', 'defended', 'archived'])->count();

        return [
            'archived' => ArchiveEntry::count(),
            'staff' => User::where('role', 'supervisor')->count(),
            'completion' => $total > 0 ? (int) round($finished / $total * 100) : 0,
            'active' => (clone $currentProjects)->whereIn('status', ['approved', 'in_progress', 'awaiting_submission'])->count(),
        ];
    }
}
