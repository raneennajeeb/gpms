<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\Idea;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IdeaBankController extends Controller
{
    public function index(Request $request): View
    {
        return view('supervisor.idea-bank', [
            'ideas' => Idea::where('supervisor_id', $request->user()->id)
                ->with('reservedByTeam.section')
                ->latest()
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Idea::create($this->validated($request) + [
            'supervisor_id' => $request->user()->id,
            'status' => 'available',
        ]);

        return back()->with('success', __('supervisor.idea_added'));
    }

    public function update(Request $request, Idea $idea): RedirectResponse
    {
        $this->own($request, $idea);

        $idea->update($this->validated($request));

        return back()->with('success', __('supervisor.idea_updated'));
    }

    public function destroy(Request $request, Idea $idea): RedirectResponse
    {
        $this->own($request, $idea);

        // A reserved idea is part of a team's proposal and cannot vanish
        abort_if($idea->status === 'reserved', 403);

        $idea->delete();

        return back()->with('success', __('supervisor.idea_deleted'));
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'min:10', 'max:150'],
            'description' => ['required', 'string', 'min:40', 'max:2000'],
            'technologies' => ['array', 'max:12'],
            'technologies.*' => ['string', 'max:40'],
        ], [], [
            'title' => __('student.proposal_title'),
            'description' => __('student.proposal_description'),
            'technologies' => __('student.proposal_technologies'),
        ]);

        $data['technologies'] = $data['technologies'] ?? [];

        return $data;
    }

    private function own(Request $request, Idea $idea): void
    {
        abort_unless($idea->supervisor_id === $request->user()->id, 403);
    }
}
