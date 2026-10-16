<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Support\ProjectHealth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    use ResolvesStudentProject;

    public function show(Request $request): View
    {
        $project = $this->studentProject($request);

        $project->load([
            'technologies',
            'team.members.user',
            'team.leader',
            'supervisor',
            'stages' => fn ($query) => $query->orderBy('sort_order'),
            'comments.user',
            'activities.user',
            'sessionProject.defenseSession.room',
        ]);

        return view('student.project', [
            'project' => $project,
            'health' => ProjectHealth::for($project),
            'timeline' => $this->timeline($project),
        ]);
    }

    public function updateGithub(Request $request): RedirectResponse
    {
        $project = $this->studentProject($request);

        $data = $request->validate([
            // Only a real GitHub URL, so the badge cannot point anywhere else
            'github_url' => ['nullable', 'url', 'starts_with:https://github.com/', 'max:255'],
        ], [], ['github_url' => __('student.github_url')]);

        $project->update($data);

        ActivityLog::create([
            'project_id' => $project->id,
            'user_id' => $request->user()->id,
            'action' => 'github_updated',
            'description' => __('activity.github_updated'),
        ]);

        return back()->with('success', __('student.github_saved'));
    }

    /**
     * Stage bars laid out on one timeline. Each bar's position and width are
     * a percentage of the whole span, so the view needs no date maths.
     */
    private function timeline(Project $project): array
    {
        $stages = $project->stages->filter(fn ($stage) => $stage->due_date !== null);

        if ($stages->isEmpty()) {
            return [];
        }

        // The semester start, not created_at: a project row is written whenever
        // the data is loaded, which can be long after the stages were planned
        $start = ($project->semester?->start_date ?? $stages->min('due_date'))->copy()->startOfDay();
        $end = $stages->max('due_date')->copy()->endOfDay();

        if ($end->lessThanOrEqualTo($start)) {
            return [];
        }

        $span = max($start->diffInDays($end), 1);

        $cursor = $start;
        $bars = [];

        foreach ($project->stages as $stage) {
            if (! $stage->due_date) {
                continue;
            }

            $offset = $start->diffInDays($cursor);
            $length = max($cursor->diffInDays($stage->due_date), 1);

            $bars[] = [
                'stage' => $stage,
                'left' => round($offset / $span * 100, 2),
                'width' => round($length / $span * 100, 2),
                // Overdue means the date passed and the stage is still open
                'overdue' => $stage->status !== 'completed' && $stage->due_date->isPast(),
            ];

            $cursor = $stage->due_date->copy();
        }

        return [
            'bars' => $bars,
            'start' => $start,
            'end' => $end,
            'today' => now()->between($start, $end)
                ? round($start->diffInDays(now()) / $span * 100, 2)
                : null,
        ];
    }
}
