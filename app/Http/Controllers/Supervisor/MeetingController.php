<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\Room;
use App\Models\SupervisorInquiry;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MeetingController extends Controller
{
    use ScopesToSupervisor;

    public function index(Request $request): View
    {
        $meetings = Meeting::where('supervisor_id', $request->user()->id)
            ->with(['project.team.members.user', 'room'])
            ->orderBy('starts_at')
            ->get();

        return view('supervisor.meetings', [
            'upcoming' => $meetings->filter(fn (Meeting $m) => $m->starts_at->isFuture())->values(),
            'past' => $meetings->filter(fn (Meeting $m) => $m->starts_at->isPast())->sortByDesc('starts_at')->values(),
            'projects' => $this->supervisorProjects($request)->orderBy('title')->get(),
            'rooms' => Room::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'project_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'min:5', 'max:150'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'time' => ['required', 'date_format:H:i'],
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'location_note' => ['nullable', 'string', 'max:200'],
        ], [], [
            'title' => __('supervisor.meeting_title'),
            'date' => __('student.meeting_date'),
            'time' => __('student.meeting_time'),
        ]);

        $project = $this->supervisorProjects($request)->whereKey($data['project_id'])->firstOr(fn () => abort(403));
        $startsAt = Carbon::parse($data['date'] . ' ' . $data['time']);

        $meeting = $project->meetings()->create([
            'supervisor_id' => $request->user()->id,
            'title' => $data['title'],
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHour(),
            'room_id' => $data['room_id'] ?? null,
            'location_note' => $data['location_note'] ?? null,
            'status' => 'confirmed',
        ]);

        $this->notify($meeting, 'meeting_confirmed');

        return back()->with('success', __('supervisor.meeting_created'));
    }

    public function confirm(Request $request, Meeting $meeting): RedirectResponse
    {
        $this->own($request, $meeting);

        $meeting->update(['status' => 'confirmed']);
        $this->notify($meeting, 'meeting_confirmed');

        return back()->with('success', __('supervisor.meeting_confirmed'));
    }

    public function cancel(Request $request, Meeting $meeting): RedirectResponse
    {
        $this->own($request, $meeting);

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ], [], ['reason' => __('supervisor.cancel_reason')]);

        $meeting->update(['status' => 'cancelled', 'notes' => $data['reason']]);
        $this->notify($meeting, 'meeting_cancelled', ['reason' => $data['reason']]);

        return back()->with('success', __('supervisor.meeting_cancelled'));
    }

    /* ---------- Student inquiries ---------- */

    public function inquiries(Request $request): View
    {
        $inquiries = $this->supervisorInquiries($request)
            ->with(['student', 'project'])
            ->latest()
            ->get();

        // Opening the page is reading them
        $this->supervisorInquiries($request)->where('status', 'new')->update(['status' => 'read']);

        return view('supervisor.inquiries', ['inquiries' => $inquiries]);
    }

    public function reply(Request $request, SupervisorInquiry $inquiry): RedirectResponse
    {
        abort_unless($inquiry->supervisor_id === $request->user()->id, 403);

        $data = $request->validate([
            'reply' => ['required', 'string', 'min:5', 'max:1000'],
        ], [], ['reply' => __('supervisor.reply')]);

        $inquiry->update([
            'reply' => $data['reply'],
            'status' => 'replied',
            'replied_at' => now(),
        ]);

        $inquiry->student->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'inquiry_replied',
            'data' => [
                'key' => 'notifications.inquiry_replied',
                'params' => ['supervisor' => $request->user()->name],
            ],
        ]);

        return back()->with('success', __('supervisor.reply_sent'));
    }

    private function own(Request $request, Meeting $meeting): void
    {
        abort_unless($meeting->supervisor_id === $request->user()->id, 403);
    }

    private function notify(Meeting $meeting, string $key, array $params = []): void
    {
        $params += ['title' => $meeting->title];

        foreach ($meeting->project->team->members()->where('status', 'accepted')->with('user')->get() as $member) {
            $member->user->notifications()->create([
                'id' => (string) Str::uuid(),
                'type' => $key,
                'data' => ['key' => 'notifications.' . $key, 'params' => $params],
            ]);
        }
    }
}
