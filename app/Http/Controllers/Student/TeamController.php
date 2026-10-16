<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(Request $request): View
    {
        $student = $request->user();
        $team = $student->currentTeam();

        if ($team) {
            $team->load(['members.user', 'leader', 'section', 'project']);
        }

        return view('student.team', [
            'team' => $team,
            'isLeader' => $team?->leader_id === $student->id,
            'invitations' => $student->pendingInvitations()->get(),
            'maxSize' => (int) setting('max_team_size', 3),
            // Only classmates who are still free can be invited
            'available' => $team ? $this->invitableClassmates($student, $team) : collect(),
        ]);
    }

    /** The student's own section, nothing else */
    public function section(Request $request): View
    {
        $student = $request->user();
        $team = $student->currentTeam();

        $classmates = User::studentsOfSection($student->section_id)
            ->whereKeyNot($student->id)
            ->with(['teamMemberships.team.project'])
            ->orderBy('name')
            ->get()
            ->map(function (User $classmate) {
                $membership = $classmate->teamMemberships->firstWhere('status', 'accepted');

                return [
                    'user' => $classmate,
                    'in_team' => $membership !== null,
                    // Title only: a classmate's project details stay private
                    'project_title' => $membership?->team?->project?->title,
                ];
            });

        return view('student.section', [
            'classmates' => $classmates,
            'team' => $team,
            'isLeader' => $team?->leader_id === $student->id,
            'maxSize' => (int) setting('max_team_size', 3),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $student = $request->user();

        $this->guardNoTeam($student);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:60'],
        ]);

        $team = Team::create([
            'section_id' => $student->section_id,
            'leader_id' => $student->id,
            'name' => $data['name'],
            'status' => 'forming',
        ]);

        // The leader is a member from the start, already accepted.
        // expires_at is required by the table, even though it never applies here.
        $team->members()->create([
            'user_id' => $student->id,
            'status' => 'accepted',
            'invited_at' => now(),
            'expires_at' => now()->addDays((int) setting('invitation_expiry_days', 7)),
            'responded_at' => now(),
        ]);

        $this->log($team, $student, 'team_created', __('activity.team_created', ['name' => $team->name]));

        return redirect()->route('student.team')->with('success', __('student.team_created'));
    }

    public function invite(Request $request): RedirectResponse
    {
        $student = $request->user();
        $team = $this->leaderTeam($student);

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $invitee = User::findOrFail($data['user_id']);

        // Every rule is checked here on the server, not only in the form
        if ($invitee->section_id !== $team->section_id) {
            throw ValidationException::withMessages(['user_id' => __('student.invite_other_section')]);
        }

        if ($invitee->currentTeam()) {
            throw ValidationException::withMessages(['user_id' => __('student.invite_already_in_team')]);
        }

        if ($team->members()->whereIn('status', ['accepted', 'pending'])->count() >= (int) setting('max_team_size', 3)) {
            throw ValidationException::withMessages(['user_id' => __('student.invite_team_full')]);
        }

        if ($team->members()->where('user_id', $invitee->id)->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages(['user_id' => __('student.invite_already_sent')]);
        }

        $team->members()->create([
            'user_id' => $invitee->id,
            'status' => 'pending',
            'invited_at' => now(),
            'expires_at' => now()->addDays((int) setting('invitation_expiry_days', 7)),
        ]);

        $this->notify($invitee, 'team_invited', ['team' => $team->name]);
        $this->log($team, $student, 'team_invited', __('activity.team_invited', ['name' => $invitee->name]));

        return back()->with('success', __('student.invite_sent'));
    }

    public function respond(Request $request, TeamMember $member): RedirectResponse
    {
        $student = $request->user();

        abort_unless($member->user_id === $student->id, 403);
        abort_unless($member->status === 'pending', 403);

        $data = $request->validate([
            'answer' => ['required', 'in:accept,decline'],
        ]);

        if ($member->expires_at?->isPast()) {
            $member->update(['status' => 'expired']);

            return back()->with('error', __('student.invite_expired'));
        }

        if ($data['answer'] === 'accept') {
            $this->guardNoTeam($student);

            $member->update(['status' => 'accepted', 'responded_at' => now()]);

            // Any other invitation this student had is now moot
            TeamMember::where('user_id', $student->id)
                ->whereKeyNot($member->id)
                ->where('status', 'pending')
                ->update(['status' => 'declined', 'responded_at' => now()]);
        } else {
            $member->update(['status' => 'declined', 'responded_at' => now()]);
        }

        $team = $member->team;
        $key = $data['answer'] === 'accept' ? 'team_invite_accepted' : 'team_invite_declined';

        $this->notify($team->leader, $key, ['name' => $student->name]);
        $this->log($team, $student, $key, __('activity.' . $key, ['name' => $student->name]));

        return back()->with('success', __('student.' . $key));
    }

    public function destroy(Request $request, TeamMember $member): RedirectResponse
    {
        $student = $request->user();
        $team = $this->leaderTeam($student);

        abort_unless($member->team_id === $team->id, 403);
        abort_if($member->user_id === $team->leader_id, 403); // a leader cannot remove themselves

        $removed = $member->user;
        $member->delete();

        $this->notify($removed, 'team_removed', ['team' => $team->name]);
        $this->log($team, $student, 'team_removed', __('activity.team_removed', ['name' => $removed->name]));

        return back()->with('success', __('student.member_removed'));
    }

    /* ---------- Helpers ---------- */

    private function leaderTeam(User $student): Team
    {
        $team = $student->currentTeam();

        abort_if($team === null, 403);
        abort_unless($team->leader_id === $student->id, 403);

        return $team;
    }

    private function guardNoTeam(User $student): void
    {
        if ($student->currentTeam()) {
            throw ValidationException::withMessages(['name' => __('student.already_in_team')]);
        }
    }

    private function invitableClassmates(User $student, Team $team)
    {
        $taken = TeamMember::where('status', 'accepted')->pluck('user_id');
        $invited = $team->members()->where('status', 'pending')->pluck('user_id');

        return User::studentsOfSection($student->section_id)
            ->whereKeyNot($taken->merge($invited)->all())
            ->orderBy('name')
            ->get();
    }

    private function notify(User $user, string $key, array $params): void
    {
        $user->notifications()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => $key,
            'data' => ['key' => 'notifications.' . $key, 'params' => $params],
        ]);
    }

    private function log(Team $team, User $actor, string $action, string $description): void
    {
        ActivityLog::create([
            'project_id' => $team->project?->id,
            'user_id' => $actor->id,
            'action' => $action,
            'description' => $description,
        ]);
    }
}
