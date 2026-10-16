<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\Project;
use App\Models\ProjectAttachment;
use App\Models\ProjectStage;
use App\Models\Submission;
use App\Support\Grade;
use App\Support\ProjectHealth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectReviewController extends Controller
{
    use ScopesToSupervisor;

    public function show(Request $request, Project $project): View
    {
        $this->ownProject($request, $project);

        $project->load([
            'team.members.user', 'team.leader', 'team.section',
            'technologies',
            'stages' => fn ($query) => $query->orderBy('sort_order'),
            'submissions.versions.uploader',
            'comments.user', 'comments.attachments',
            'activities.user',
            'inquiries.student',
            'attachments.uploader',
            'supervisorEvaluation',
        ]);

        return view('supervisor.project', [
            'project' => $project,
            'health' => ProjectHealth::for($project),
        ]);
    }

    /** Approving a stage closes it, opens the next one and moves the bar */
    public function completeStage(Request $request, ProjectStage $stage): RedirectResponse
    {
        $project = $this->ownProject($request, $stage->project);

        abort_if($stage->status === 'completed', 403);

        $stage->update([
            'status' => 'completed',
            'completed_at' => now(),
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        $project->stages()
            ->where('sort_order', $stage->sort_order + 1)
            ->where('status', 'locked')
            ->update(['status' => 'in_progress']);

        $project->calculateProgress();

        ActivityLog::create([
            'project_id' => $project->id,
            'user_id' => $request->user()->id,
            'action' => 'stage_approved',
            'description' => __('activity.stage_approved', ['stage' => $stage->name_ar]),
        ]);

        $this->notifyTeam($project, 'stage_approved', ['stage' => $stage->name_ar]);

        return back()->with('success', __('supervisor.stage_approved'));
    }

    public function reviewSubmission(Request $request, Submission $submission): RedirectResponse
    {
        $project = $this->ownProject($request, $submission->project);

        $data = $request->validate([
            'status' => ['required', 'in:accepted,needs_revision'],
            // Asking for a revision without saying why is not a review
            'supervisor_notes' => ['required_if:status,needs_revision', 'nullable', 'string', 'max:1000'],
        ], [], ['supervisor_notes' => __('supervisor.notes')]);

        $submission->update($data);

        ActivityLog::create([
            'project_id' => $project->id,
            'user_id' => $request->user()->id,
            'action' => 'submission_reviewed',
            'description' => __('activity.submission_reviewed', [
                'title' => $submission->title,
                'status' => __('student.sub_status_' . $submission->status),
            ]),
        ]);

        $this->notifyTeam($project, 'submission_reviewed', [
            'title' => $submission->title,
            'status' => __('student.sub_status_' . $submission->status),
        ]);

        return back()->with('success', __('supervisor.submission_reviewed'));
    }

    public function comment(Request $request, Project $project): RedirectResponse
    {
        $this->ownProject($request, $project);

        $data = $request->validate([
            'body' => ['required', 'string', 'min:3', 'max:2000'],
            'kind' => ['required', 'in:feedback,team_note'],
            'attachment' => ['nullable', 'file', 'mimes:png,jpg,jpeg,pdf,zip', 'max:5120'],
        ], [], ['body' => __('supervisor.notes')]);

        $comment = $project->comments()->create([
            'user_id' => $request->user()->id,
            'kind' => $data['kind'],
            'body' => $data['body'],
        ]);

        if ($file = $request->file('attachment')) {
            ProjectAttachment::create([
                'project_id' => $project->id,
                'comment_id' => $comment->id,
                'user_id' => $request->user()->id,
                'file_path' => $file->store('comments/' . $comment->id, 'local'),
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        $this->notifyTeam($project, 'supervisor_note', ['title' => $project->title]);

        return back()->with('success', __('supervisor.note_sent'));
    }

    /** The supervisor's own mark: one per project, editable until it is archived */
    public function evaluate(Request $request, Project $project): RedirectResponse
    {
        $this->ownProject($request, $project);

        abort_if($project->status === 'archived', 403);

        $data = $request->validate([
            'score' => ['required', 'numeric', 'min:0', 'max:100'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ], [], ['score' => __('supervisor.score')]);

        $score = (float) $data['score'];

        // The letter and the Arabic label come from App\Support\Grade,
        // so the scale lives in exactly one place
        $project->supervisorEvaluation()->updateOrCreate(
            ['project_id' => $project->id],
            [
                'supervisor_id' => $request->user()->id,
                'score' => $score,
                'letter' => Grade::letter($score),
                'label_ar' => Grade::label(Grade::key($score), 'ar'),
                'comment' => $data['comment'] ?? null,
            ]
        );

        AuditLog::record('supervisor_evaluated', $project, [], ['score' => $score]);

        $this->notifyTeam($project, 'evaluation_published', [
            'grade' => Grade::label(Grade::key($score), 'ar') . ' (' . Grade::letter($score) . ')',
        ]);

        return back()->with('success', __('supervisor.evaluation_saved'));
    }

    public function downloadVersion(Request $request, \App\Models\SubmissionVersion $version): StreamedResponse
    {
        $this->ownProject($request, $version->submission->project);

        abort_unless(\Illuminate\Support\Facades\Storage::disk('local')->exists($version->file_path), 404);

        return \Illuminate\Support\Facades\Storage::disk('local')
            ->download($version->file_path, $version->original_name);
    }

    public function downloadAttachment(Request $request, ProjectAttachment $attachment): StreamedResponse
    {
        $this->ownProject($request, $attachment->project);

        abort_unless(\Illuminate\Support\Facades\Storage::disk('local')->exists($attachment->file_path), 404);

        return \Illuminate\Support\Facades\Storage::disk('local')
            ->download($attachment->file_path, $attachment->original_name);
    }

    private function notifyTeam(Project $project, string $key, array $params): void
    {
        foreach ($project->team->members()->where('status', 'accepted')->with('user')->get() as $member) {
            $member->user->notifications()->create([
                'id' => (string) Str::uuid(),
                'type' => $key,
                'data' => ['key' => 'notifications.' . $key, 'params' => $params],
            ]);
        }
    }
}
