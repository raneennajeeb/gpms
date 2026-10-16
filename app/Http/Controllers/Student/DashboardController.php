<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View|JsonResponse
    {
        $data = $this->dashboardData($request->user());

        // The "refresh" button re-reads the same figures without a page load
        if ($request->wantsJson()) {
            return response()->json([
                'progress' => $data['project']?->progress_percent ?? 0,
                'status' => $data['statusLabel'],
                'evaluation' => $data['evaluationLabel'],
                'next' => $data['nextEvent'],
            ]);
        }

        return view('student.dashboard', $data);
    }

    private function dashboardData(User $student): array
    {
        $team = $student->currentTeam();
        $project = $team?->project;

        if ($project) {
            $project->load(['stages' => fn ($q) => $q->orderBy('sort_order'), 'supervisor', 'supervisorEvaluation']);
        }

        return [
            'student' => $student->load(['branch', 'section.supervisor']),
            'team' => $team,
            'project' => $project,
            'supervisor' => $project?->supervisor ?? $student->section?->supervisor,
            'statusLabel' => $this->statusLabel($project),
            'evaluationLabel' => $this->evaluationLabel($project),
            'nextEvent' => $this->nextEvent($student, $project),
            'nextAction' => $this->nextAction($student, $team, $project),
            'invitations' => $student->pendingInvitations()->get(),
            'notifications' => $student->notifications()->latest()->take(5)->get(),
        ];
    }

    /** The stage being worked on says more than the raw project status */
    private function statusLabel(?Project $project): ?string
    {
        if (! $project) {
            return null;
        }

        $current = $project->stages->firstWhere('status', 'in_progress');

        return $current?->name_ar ?? __('projects.status.' . $project->status);
    }

    private function evaluationLabel(?Project $project): ?string
    {
        $evaluation = $project?->supervisorEvaluation;

        if (! $evaluation) {
            return null;
        }

        return $evaluation->label_ar . ' (' . $evaluation->letter . ')';
    }

    /**
     * The soonest thing ahead of the student, whichever kind it is:
     * the defense, a meeting, or a submission deadline.
     */
    private function nextEvent(User $student, ?Project $project): ?array
    {
        if (! $project) {
            return null;
        }

        $events = collect();

        $slot = $project->sessionProject?->loadMissing('defenseSession');

        if ($slot && $slot->defenseSession->session_date->isFuture()) {
            $events->push([
                'title' => __('student.defense'),
                'date' => $slot->defenseSession->session_date,
                'url' => route('student.project'),
            ]);
        }

        foreach ($project->meetings()->where('starts_at', '>', now())->get() as $meeting) {
            $events->push([
                'title' => $meeting->title,
                'date' => $meeting->starts_at,
                'url' => route('student.meetings'),
            ]);
        }

        foreach ($project->submissions()->whereNotNull('due_date')->where('due_date', '>', now())->get() as $submission) {
            $events->push([
                'title' => $submission->title,
                'date' => $submission->due_date,
                'url' => route('student.submissions'),
            ]);
        }

        return $events->sortBy('date')->first();
    }

    /**
     * One thing to do next, never a list. Ordered by how blocking it is:
     * an invitation stops the team forming, and changes block the project.
     */
    private function nextAction(User $student, $team, ?Project $project): ?array
    {
        if ($student->pendingInvitations()->exists()) {
            return ['key' => 'invitation', 'url' => route('student.team'), 'date' => null];
        }

        if (! $team) {
            return ['key' => 'create_team', 'url' => route('student.team'), 'date' => null];
        }

        if (! $project) {
            return ['key' => 'submit_proposal', 'url' => route('student.proposal'), 'date' => null];
        }

        if (in_array($project->status, ['changes_requested', 'rejected'], true)) {
            return ['key' => 'fix_proposal', 'url' => route('student.proposal'), 'date' => null];
        }

        $next = $this->nextEvent($student, $project);

        return $next ? ['key' => 'upcoming', 'url' => $next['url'], 'date' => $next['date'], 'title' => $next['title']] : null;
    }
}
