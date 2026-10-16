<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Support\ProjectHealth;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use ScopesToSupervisor;

    public function __invoke(Request $request): View
    {
        $supervisor = $request->user();
        $projects = $this->supervisorProjects($request)->with(['team.section', 'team.members.user'])->get();

        // Same health rule as the student's own page, so both sides agree
        $needsAttention = $projects
            ->map(fn (Project $project) => ['project' => $project, 'health' => ProjectHealth::for($project)])
            ->filter(fn (array $row) => $row['health']->level !== ProjectHealth::GOOD)
            ->sortByDesc(fn (array $row) => $row['health']->points)
            ->values();

        return view('supervisor.dashboard', [
            'stats' => [
                'students' => $this->supervisorStudents($request)->count(),
                'pending' => $projects->where('status', 'pending_approval')->count(),
                'active' => $projects->where('status', 'in_progress')->count(),
                'late' => $needsAttention->where('health.level', ProjectHealth::RISK)->count(),
            ],
            // Two plain charts, drawn from these counts in CSS: no chart library
            'byStatus' => $projects->countBy('status'),
            'bySection' => $projects
                ->groupBy(fn (Project $project) => $project->team?->section?->number ?? '—')
                ->map(fn ($group) => (int) round($group->avg('progress_percent'))),
            'needsAttention' => $needsAttention->take(6),
            'meetings' => $supervisor->supervisedProjects()
                ->with('meetings.room')
                ->get()
                ->flatMap->meetings
                ->filter(fn ($meeting) => $meeting->starts_at->isFuture())
                ->sortBy('starts_at')
                ->take(5),
            'sessions' => $this->supervisorSessions($request)
                ->with(['room', 'section'])
                ->whereDate('session_date', '>=', today())
                ->orderBy('session_date')
                ->take(5)
                ->get(),
            'inquiries' => $this->supervisorInquiries($request)
                ->with(['student', 'project'])
                ->latest()
                ->take(5)
                ->get(),
        ]);
    }
}
