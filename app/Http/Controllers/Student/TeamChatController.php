<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\TeamMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeamChatController extends Controller
{
    use ResolvesStudentProject;

    public function index(Request $request): View|JsonResponse
    {
        $team = $this->studentTeam($request);

        $messages = $team->messages()->with('user')->orderBy('id')->get();

        // The page polls this same route every ten seconds
        if ($request->wantsJson()) {
            return response()->json([
                'messages' => $messages
                    ->when($request->integer('after'), fn ($all, $after) => $all->where('id', '>', $after))
                    ->values()
                    ->map(fn (TeamMessage $message) => [
                        'id' => $message->id,
                        'name' => $message->user->name,
                        'mine' => $message->user_id === $request->user()->id,
                        'body' => $message->body,
                        'at' => $message->created_at->format('H:i'),
                    ]),
            ]);
        }

        return view('student.team-chat', [
            'team' => $team,
            'messages' => $messages,
            // The supervisor's notes to the team sit in their own panel
            'teamNotes' => $team->project
                ? $team->project->comments()->where('kind', 'team_note')->with('user')->latest()->get()
                : collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $team = $this->studentTeam($request);
        abort_unless($this->canEdit($request, $team), 403);

        $data = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:2000'],
        ], [], ['body' => __('student.message')]);

        $team->messages()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        return back();
    }
}
