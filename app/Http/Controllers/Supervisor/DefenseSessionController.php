<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\DefenseSession;
use App\Models\Project;
use App\Models\Room;
use App\Models\User;
use App\Services\DefenseSessionValidator;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DefenseSessionController extends Controller
{
    use ScopesToSupervisor;

    public function index(Request $request): View
    {
        return view('supervisor.defense-sessions', [
            'sessions' => $this->supervisorSessions($request)
                ->with(['room', 'section', 'committee', 'sessionProjects.project.team.members.user'])
                ->orderByDesc('session_date')
                ->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('supervisor.defense-session-form', $this->formData($request) + ['session' => null]);
    }

    public function edit(Request $request, DefenseSession $session): View
    {
        $this->ownSession($request, $session);
        $session->load(['committee', 'sessionProjects.project']);

        return view('supervisor.defense-session-form', $this->formData($request, $session) + ['session' => $session]);
    }

    public function store(Request $request): RedirectResponse
    {
        $session = $this->save($request, null);

        $this->announce($session, 'defense_scheduled');

        return redirect()->route('supervisor.defense-sessions')
            ->with('success', __('supervisor.session_booked'));
    }

    public function update(Request $request, DefenseSession $session): RedirectResponse
    {
        $this->ownSession($request, $session);

        $this->announce($this->save($request, $session), 'defense_changed');

        return redirect()->route('supervisor.defense-sessions')
            ->with('success', __('supervisor.session_updated'));
    }

    public function destroy(Request $request, DefenseSession $session): RedirectResponse
    {
        $this->ownSession($request, $session);

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ], [], ['reason' => __('supervisor.cancel_reason')]);

        $session->update(['status' => 'cancelled']);

        AuditLog::record('session_cancelled', $session, [], ['reason' => $data['reason']]);
        $this->announce($session, 'defense_cancelled', ['reason' => $data['reason']]);

        return back()->with('success', __('supervisor.session_cancelled'));
    }

    /** Same rules as the server uses, so the page can say "busy" before saving */
    public function availability(Request $request, DefenseSessionValidator $validator): JsonResponse
    {
        $data = $request->validate([
            'session_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'committee' => ['array'],
            'committee.*' => ['integer'],
            'session_id' => ['nullable', 'integer'],
        ]);

        $ignore = $data['session_id'] ?? null
            ? $this->supervisorSessions($request)->whereKey($data['session_id'])->first()
            : null;

        try {
            $validator->validateSession(
                Carbon::parse($data['session_date']),
                $data['start_time'],
                $data['end_time'],
                Room::findOrFail($data['room_id']),
                $request->user(),
                User::whereKey($data['committee'] ?? [])->get(),
                $ignore,
            );
        } catch (ValidationException $e) {
            return response()->json([
                'available' => false,
                'message' => collect($e->errors())->flatten()->first(),
            ]);
        }

        return response()->json(['available' => true, 'message' => __('supervisor.slot_free')]);
    }

    /* ---------- Shared save path ---------- */

    private function save(Request $request, ?DefenseSession $session): DefenseSession
    {
        $validator = DefenseSessionValidator::make();

        $data = $request->validate([
            'section_id' => ['required', 'integer'],
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'session_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            // Two or three doctors, one of whom chairs
            'committee' => ['required', 'array', 'min:2', 'max:3'],
            'committee.*' => ['integer', 'distinct'],
            'chair' => ['required', 'integer'],
            'projects' => ['array'],
            'projects.*.project_id' => ['required', 'integer'],
            'projects.*.position' => ['required', 'integer', 'min:1'],
            'projects.*.start_time' => ['required', 'date_format:H:i'],
        ], [], [
            'committee' => __('supervisor.committee'),
            'chair' => __('supervisor.chair'),
            'session_date' => __('supervisor.session_date'),
        ]);

        $section = $this->supervisorSections($request)->whereKey($data['section_id'])->firstOr(fn () => abort(403));
        $room = Room::findOrFail($data['room_id']);
        $committee = $this->validatedCommittee($request, $data, $section);

        // Rules 1, 2 and 3
        $validator->validateSession(
            Carbon::parse($data['session_date']),
            $data['start_time'],
            $data['end_time'],
            $room,
            $request->user(),
            $committee->values(),
            $session,
        );

        return DB::transaction(function () use ($request, $session, $data, $section, $room, $committee, $validator) {
            $payload = [
                'section_id' => $section->id,
                'supervisor_id' => $request->user()->id,
                'room_id' => $room->id,
                'session_date' => $data['session_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'status' => 'scheduled',
            ];

            if ($session) {
                $session->update($payload);
            } else {
                $session = DefenseSession::create($payload + ['created_by' => $request->user()->id]);
            }

            $session->committee()->sync(
                $committee->mapWithKeys(fn (User $member) => [
                    $member->id => ['role' => $member->id === (int) $data['chair'] ? 'chair' : 'member'],
                ])->all()
            );

            $slots = $data['projects'] ?? [];

            if ($slots) {
                // The page lists only eligible projects, but it is not the guard
                $validator->validateProjectsEligible(array_column($slots, 'project_id'), $session);

                // Rules 4, 5 and 6, which also work out each end_time
                $slots = $validator->validateProjectSlots($slots, $session, $data['start_time'], $data['end_time']);
            }

            $session->sessionProjects()->delete();

            foreach ($slots as $slot) {
                $session->sessionProjects()->create($slot);
            }

            AuditLog::record($session->wasRecentlyCreated ? 'session_booked' : 'session_updated', $session, [], $payload);

            return $session->fresh(['room', 'committee', 'sessionProjects']);
        });
    }

    /** Two or three doctors from this branch's pool, never the supervisor */
    private function validatedCommittee(Request $request, array $data, $section)
    {
        $pool = $section->branch->committeePool()->pluck('users.id');
        $chosen = collect($data['committee'])->map(fn ($id) => (int) $id);

        if ($chosen->contains($request->user()->id)) {
            throw ValidationException::withMessages([
                'committee' => __('supervisor.rule_committee_self'),
            ]);
        }

        if ($chosen->diff($pool)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'committee' => __('supervisor.rule_committee_pool'),
            ]);
        }

        if (! $chosen->contains((int) $data['chair'])) {
            throw ValidationException::withMessages([
                'chair' => __('supervisor.rule_chair_in_committee'),
            ]);
        }

        return User::whereKey($chosen)->get();
    }

    private function formData(Request $request, ?DefenseSession $session = null): array
    {
        $sections = $this->supervisorSections($request)->with('branch')->orderBy('number')->get();
        $branchIds = $sections->pluck('branch_id')->unique();

        return [
            'sections' => $sections,
            'rooms' => Room::whereIn('branch_id', $branchIds)->where('is_active', true)->orderBy('name')->get(),
            'pool' => User::whereIn(
                'id',
                DB::table('committee_members')->whereIn('branch_id', $branchIds)->pluck('user_id')
            )->whereKeyNot($request->user()->id)->orderBy('name')->get(),
            // Approved projects in these sections that are not booked yet
            'available' => Project::where('status', 'in_progress')
                ->whereHas('team', fn ($query) => $query->whereIn('section_id', $sections->pluck('id')))
                ->where(function ($query) use ($session) {
                    $query->whereDoesntHave('sessionProject');

                    if ($session) {
                        $query->orWhereHas('sessionProject', fn ($inner) => $inner->where('defense_session_id', $session->id));
                    }
                })
                ->with('team.members.user')
                ->get(),
            'dayStart' => setting('defense_day_start', '08:00'),
            'dayEnd' => setting('defense_day_end', '14:00'),
        ];
    }

    /** Everyone affected hears about it: both teams and the committee */
    private function announce(DefenseSession $session, string $key, array $params = []): void
    {
        $params += [
            'date' => $session->session_date->translatedFormat('j F Y'),
            'room' => $session->room->name,
        ];

        $recipients = collect();

        foreach ($session->sessionProjects()->with('project.team.members.user')->get() as $slot) {
            foreach ($slot->project->team->members->where('status', 'accepted') as $member) {
                $recipients->push($member->user);
            }
        }

        $recipients = $recipients->concat($session->committee)->unique('id');

        foreach ($recipients as $user) {
            $user->notifications()->create([
                'id' => (string) Str::uuid(),
                'type' => $key,
                'data' => ['key' => 'notifications.' . $key, 'params' => $params],
            ]);
        }

        // activity_log hangs off a project, so a session with no projects
        // yet leaves no trace here; each booked project gets its own row
        foreach ($session->sessionProjects()->pluck('project_id') as $projectId) {
            ActivityLog::create([
                'project_id' => $projectId,
                'user_id' => $session->supervisor_id,
                'action' => $key,
                'description' => __('activity.' . $key, $params),
            ]);
        }
    }
}
