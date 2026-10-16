<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MeetingController extends Controller
{
    use ResolvesStudentProject;

    public function index(Request $request): View
    {
        $project = $this->studentProject($request);

        $meetings = $project->meetings()->with(['supervisor', 'room'])->orderBy('starts_at')->get();

        return view('student.meetings', [
            'project' => $project,
            'upcoming' => $meetings->filter(fn (Meeting $m) => $m->starts_at->isFuture())->values(),
            'past' => $meetings->filter(fn (Meeting $m) => $m->starts_at->isPast())->sortByDesc('starts_at')->values(),
        ]);
    }

    public function request(Request $request): RedirectResponse
    {
        $project = $this->studentProject($request);
        abort_unless($this->canEdit($request, $project->team), 403);

        $data = $request->validate([
            'date' => ['required', 'date', 'after:today'],
            'time' => ['required', 'date_format:H:i'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ], [], [
            'date' => __('student.meeting_date'),
            'time' => __('student.meeting_time'),
            'reason' => __('student.meeting_reason'),
        ]);

        $startsAt = \Carbon\Carbon::parse($data['date'] . ' ' . $data['time']);

        // The student asks; the supervisor confirms from their side
        $project->meetings()->create([
            'supervisor_id' => $project->supervisor_id,
            'title' => $data['reason'],
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHour(),
            'status' => 'requested',
        ]);

        $project->supervisor?->notifications()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'meeting_requested',
            'data' => [
                'key' => 'notifications.meeting_requested',
                'params' => ['student' => $request->user()->name],
            ],
        ]);

        return back()->with('success', __('student.meeting_requested'));
    }
}
