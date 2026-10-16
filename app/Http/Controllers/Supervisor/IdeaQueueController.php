<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\Project;
use App\Models\StageTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class IdeaQueueController extends Controller
{
    use ScopesToSupervisor;

    public function index(Request $request): View
    {
        $projects = $this->supervisorProjects($request)
            ->where('status', 'pending_approval')
            ->with(['team.members.user', 'team.section', 'technologies', 'similarityResults.archiveEntry'])
            ->oldest('submitted_at')
            ->get();

        return view('supervisor.ideas-queue', [
            'projects' => $projects,
            'threshold' => (int) setting('similarity_threshold', 70),
        ]);
    }

    /**
     * Approving does five things at once, so it runs in one transaction:
     * set the status, stamp who and when, build the stages from the templates,
     * open the first real stage, and recalculate progress.
     */
    public function approve(Request $request, Project $project): RedirectResponse
    {
        $this->ownProject($request, $project);
        $this->guardPending($project);

        DB::transaction(function () use ($request, $project) {
            $approvedAt = now();

            $project->update([
                'status' => 'in_progress',
                'approved_by' => $request->user()->id,
                'approved_at' => $approvedAt,
                'review_note' => null,
            ]);

            foreach (StageTemplate::orderBy('sort_order')->get() as $template) {
                $project->stages()->create([
                    'stage_template_id' => $template->id,
                    'name_ar' => $template->name_ar,
                    'name_en' => $template->name_en,
                    'description_ar' => $template->description_ar,
                    'sort_order' => $template->sort_order,
                    'weight' => $template->weight,
                    // Each stage is due a set number of days after approval
                    'due_date' => $approvedAt->copy()->addDays($template->days_after_approval),
                    // The idea stage is finished by the act of approving it
                    'status' => $template->sort_order === 1 ? 'completed' : 'locked',
                    'completed_at' => $template->sort_order === 1 ? $approvedAt : null,
                    'approved_by' => $template->sort_order === 1 ? $request->user()->id : null,
                    'approved_at' => $template->sort_order === 1 ? $approvedAt : null,
                ]);
            }

            // The stage after the idea is the one the team works on now
            $project->stages()->where('sort_order', 2)->update(['status' => 'in_progress']);

            $project->calculateProgress();

            $this->record($request, $project, 'idea_approved', __('activity.idea_approved'));
        });

        $this->notifyTeam($project, 'idea_approved', ['title' => $project->title]);

        return back()->with('success', __('supervisor.idea_approved'));
    }

    public function requestChanges(Request $request, Project $project): RedirectResponse
    {
        return $this->reject($request, $project, 'changes_requested');
    }

    public function reject(Request $request, Project $project, string $status = 'rejected'): RedirectResponse
    {
        $this->ownProject($request, $project);
        $this->guardPending($project);

        // The reason is what the student sees, so it cannot be left blank
        $data = $request->validate([
            'review_note' => ['required', 'string', 'min:10', 'max:1000'],
        ], [], ['review_note' => __('supervisor.reason')]);

        $project->update([
            'status' => $status,
            'review_note' => $data['review_note'],
        ]);

        $key = $status === 'rejected' ? 'idea_rejected' : 'idea_changes_requested';

        $this->record($request, $project, $key, __('activity.' . $key, ['reason' => $data['review_note']]));
        $this->notifyTeam($project, $key, ['title' => $project->title, 'reason' => $data['review_note']]);

        return back()->with('success', __('supervisor.' . $key));
    }

    private function guardPending(Project $project): void
    {
        // A decision is only taken once; re-posting the form changes nothing
        abort_unless($project->status === 'pending_approval', 403);
    }

    private function record(Request $request, Project $project, string $action, string $description): void
    {
        ActivityLog::create([
            'project_id' => $project->id,
            'user_id' => $request->user()->id,
            'action' => $action,
            'description' => $description,
        ]);

        AuditLog::record($action, $project, [], ['status' => $project->status]);
    }

    private function notifyTeam(Project $project, string $key, array $params): void
    {
        $members = $project->team->members()->where('status', 'accepted')->with('user')->get();

        foreach ($members as $member) {
            $member->user->notifications()->create([
                'id' => (string) Str::uuid(),
                'type' => $key,
                'data' => ['key' => 'notifications.' . $key, 'params' => $params],
            ]);
        }
    }
}
