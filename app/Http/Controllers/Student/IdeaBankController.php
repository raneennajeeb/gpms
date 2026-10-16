<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Idea;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IdeaBankController extends Controller
{
    public function index(Request $request): View
    {
        $student = $request->user();
        $team = $student->currentTeam();
        $supervisor = $student->section?->supervisor;

        // A student only ever sees their own supervisor's open ideas
        $ideas = Idea::where('supervisor_id', $supervisor?->id)
            ->where('status', 'available')
            ->latest()
            ->get();

        $accepted = $team?->members()->where('status', 'accepted')->count() ?? 0;

        return view('student.idea-bank', [
            'ideas' => $ideas,
            'supervisor' => $supervisor,
            'team' => $team,
            'isLeader' => $team?->leader_id === $student->id,
            'hasProject' => $team?->project !== null,
            'meetsMinimum' => $accepted >= (int) setting('min_team_size', 1),
            'minSize' => (int) setting('min_team_size', 1),
        ]);
    }

    public function reserve(Request $request, Idea $idea): RedirectResponse
    {
        $student = $request->user();
        $team = $student->currentTeam();

        abort_if($team === null, 403);
        abort_unless($team->leader_id === $student->id, 403);
        abort_unless($idea->supervisor_id === $student->section?->supervisor_id, 403);
        abort_unless($idea->status === 'available', 403);
        abort_if($team->project !== null, 403);

        $accepted = $team->members()->where('status', 'accepted')->count();
        abort_if($accepted < (int) setting('min_team_size', 1), 403);

        $idea->update(['status' => 'reserved', 'reserved_by_team_id' => $team->id]);

        // The proposal form opens pre-filled from the reserved idea
        return redirect()->route('student.proposal')->with('success', __('student.idea_reserved'));
    }
}
