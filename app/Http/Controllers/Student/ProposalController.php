<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\ProposalRequest;
use App\Models\ActivityLog;
use App\Models\Idea;
use App\Models\Project;
use App\Models\SimilarityResult;
use App\Models\Technology;
use App\Models\User;
use App\Support\SimilarityChecker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProposalController extends Controller
{
    public function edit(Request $request): View
    {
        $student = $request->user();
        $team = $student->currentTeam();

        abort_if($team === null, 403);

        $project = $team->project?->load('technologies');

        return view('student.proposal', [
            'team' => $team,
            'project' => $project,
            'isLeader' => $team->leader_id === $student->id,
            // Once sent, the form is read-only until the supervisor asks for changes
            'readOnly' => $project !== null
                && ! in_array($project->status, ['changes_requested', 'rejected'], true),
            'reservedIdea' => Idea::where('reserved_by_team_id', $team->id)->first(),
            'threshold' => (int) setting('similarity_threshold', 70),
            'technologies' => Technology::orderBy('name')->pluck('name'),
            'categories' => ['web', 'mobile', 'ai', 'network', 'research', 'other'],
        ]);
    }

    /** Called by the page before the student sends, and on demand */
    public function checkSimilarity(Request $request): JsonResponse
    {
        $team = $request->user()->currentTeam();
        abort_if($team === null, 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'min:10', 'max:150'],
            'description' => ['required', 'string', 'min:100', 'max:3000'],
            'keywords' => ['array'],
            'keywords.*' => ['string', 'max:40'],
        ]);

        $result = (new SimilarityChecker($team->project?->id))
            ->check($data['title'], $data['description'], $data['keywords'] ?? []);

        return response()->json([
            'score' => $result['score'],
            'threshold' => (int) setting('similarity_threshold', 70),
            'matches' => $result['matches'],
        ]);
    }

    public function store(ProposalRequest $request): RedirectResponse
    {
        $student = $request->user();
        $team = $student->currentTeam();

        abort_if($team === null, 403);
        abort_unless($team->leader_id === $student->id, 403);

        $project = $team->project;

        // Re-submitting is only allowed after the supervisor asked for changes
        if ($project && ! in_array($project->status, ['changes_requested', 'rejected'], true)) {
            abort(403);
        }

        $data = $request->validated();

        $check = (new SimilarityChecker($project?->id))
            ->check($data['title'], $data['description'], $data['keywords']);

        DB::transaction(function () use (&$project, $team, $student, $data, $check) {
            $payload = [
                'title' => $data['title'],
                'description' => $data['description'],
                'category' => $data['category'],
                'keywords' => $data['keywords'],
                'status' => 'pending_approval',
                'similarity_score' => $check['score'],
                'submitted_at' => now(),
                'review_note' => null,
            ];

            if ($project) {
                $project->update($payload);
            } else {
                $project = Project::create($payload + [
                    'team_id' => $team->id,
                    'supervisor_id' => $team->section->supervisor_id,
                    'semester_id' => $team->section->semester_id,
                    'idea_id' => Idea::where('reserved_by_team_id', $team->id)->value('id'),
                ]);
            }

            $this->syncTechnologies($project, $data['technologies']);

            // Only archive matches are stored: the table has archive_entry_id alone
            SimilarityResult::where('project_id', $project->id)->delete();

            foreach ($check['matches'] as $match) {
                if ($match['kind'] === 'archive') {
                    SimilarityResult::create([
                        'project_id' => $project->id,
                        'archive_entry_id' => $match['id'],
                        'score' => $match['score'],
                    ]);
                }
            }

            $team->update(['status' => 'active']);

            ActivityLog::create([
                'project_id' => $project->id,
                'user_id' => $student->id,
                'action' => 'proposal_submitted',
                'description' => __('activity.proposal_submitted', ['title' => $project->title]),
            ]);
        });

        $this->notifySupervisor($project, $team->section->supervisor, $student);

        return redirect()->route('student.proposal')->with('success', __('student.proposal_sent'));
    }

    private function syncTechnologies(Project $project, array $names): void
    {
        $ids = collect($names)
            ->map(fn (string $name) => Technology::firstOrCreate(['name' => trim($name)])->id)
            ->all();

        $project->technologies()->sync($ids);
    }

    private function notifySupervisor(Project $project, ?User $supervisor, User $student): void
    {
        $supervisor?->notifications()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'proposal_submitted',
            'data' => [
                'key' => 'notifications.proposal_submitted',
                'params' => ['title' => $project->title, 'student' => $student->name],
            ],
        ]);
    }
}
