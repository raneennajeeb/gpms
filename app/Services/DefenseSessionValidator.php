<?php

namespace App\Services;

use App\Models\DefenseSession;
use App\Models\Project;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * The six booking rules, all enforced here on the server. The page shows
 * availability with the same rules over AJAX, but the page is only a courtesy:
 * nothing is saved until these pass.
 *
 * validateSession()      rules 1, 2 and 3 (room, people, allowed times)
 * validateProjectSlots() rules 4, 5 and 6 (slots inside the session)
 */
class DefenseSessionValidator
{
    /** Saturday to Wednesday: Thursday and Friday are the weekend here */
    private const CLOSED_DAYS = [Carbon::THURSDAY, Carbon::FRIDAY];

    public function __construct(
        private readonly string $dayStart = '08:00',
        private readonly string $dayEnd = '14:00',
    ) {}

    public static function make(): self
    {
        return new self(
            (string) setting('defense_day_start', '08:00'),
            (string) setting('defense_day_end', '14:00'),
        );
    }

    /**
     * @param  Collection<int, User>  $committee
     * @param  DefenseSession|null  $ignore  the session being edited
     */
    public function validateSession(
        Carbon $date,
        string $startTime,
        string $endTime,
        Room $room,
        User $supervisor,
        Collection $committee,
        ?DefenseSession $ignore = null,
    ): void {
        // --- Rule 3: allowed day and allowed hours, in Asia/Hebron ---
        if ($date->copy()->endOfDay()->isPast()) {
            $this->fail('session_date', __('supervisor.rule_past_date'));
        }

        if (in_array($date->dayOfWeek, self::CLOSED_DAYS, true)) {
            $this->fail('session_date', __('supervisor.rule_closed_day'));
        }

        $start = $this->minutes($startTime);
        $end = $this->minutes($endTime);

        if ($end <= $start) {
            $this->fail('end_time', __('supervisor.rule_end_before_start'));
        }

        if ($start < $this->minutes($this->dayStart)) {
            $this->fail('start_time', __('supervisor.rule_too_early', ['time' => $this->dayStart]));
        }

        if ($end > $this->minutes($this->dayEnd)) {
            $this->fail('end_time', __('supervisor.rule_too_late', ['time' => $this->dayEnd]));
        }

        // --- Rule 1: the room ---
        if (! $room->is_active) {
            $this->fail('room_id', __('supervisor.rule_room_inactive'));
        }

        $roomClash = $this->overlapping($date, $startTime, $endTime, $ignore)
            ->where('room_id', $room->id)
            ->first();

        if ($roomClash) {
            $this->fail('room_id', __('supervisor.rule_room_busy', [
                'room' => $room->name,
                'from' => $this->short($roomClash->start_time),
                'to' => $this->short($roomClash->end_time),
            ]));
        }

        // --- Rule 2: the supervisor and every committee member ---
        $people = $committee->push($supervisor)->unique('id');
        $clashes = $this->overlapping($date, $startTime, $endTime, $ignore)
            ->with(['supervisor', 'committee'])
            ->get();

        foreach ($people as $person) {
            foreach ($clashes as $other) {
                $busy = $other->supervisor_id === $person->id
                    || $other->committee->contains('id', $person->id);

                if ($busy) {
                    $this->fail('committee', __('supervisor.rule_person_busy', [
                        'name' => $person->name,
                        'from' => $this->short($other->start_time),
                        'to' => $this->short($other->end_time),
                    ]));
                }
            }
        }
    }

    /**
     * @param  array<int, array{project_id:int, position:int, start_time:string}>  $slots
     * @return array<int, array{project_id:int, position:int, start_time:string, end_time:string}>
     */
    public function validateProjectSlots(
        array $slots,
        DefenseSession $session,
        string $startTime,
        string $endTime,
    ): array {
        if ($slots === []) {
            return [];
        }

        $sessionStart = $this->minutes($startTime);
        $sessionEnd = $this->minutes($endTime);

        // --- Rule 6: positions are 1..n with no gap and no repeat ---
        $positions = array_column($slots, 'position');
        sort($positions);

        if ($positions !== range(1, count($slots))) {
            $this->fail('projects', __('supervisor.rule_positions'));
        }

        // --- Rule 5: no two projects share a start time ---
        $starts = array_column($slots, 'start_time');

        if (count(array_unique($starts)) !== count($starts)) {
            $this->fail('projects', __('supervisor.rule_same_start'));
        }

        // --- Rule 4: every start time sits inside the session ---
        foreach ($slots as $slot) {
            $minute = $this->minutes($slot['start_time']);

            if ($minute < $sessionStart || $minute >= $sessionEnd) {
                $this->fail('projects', __('supervisor.rule_slot_outside', [
                    'time' => $slot['start_time'],
                    'from' => $this->short($startTime),
                    'to' => $this->short($endTime),
                ]));
            }
        }

        // --- Rule 6 again: the order must match the clock ---
        usort($slots, fn ($a, $b) => $a['position'] <=> $b['position']);

        $previous = null;

        foreach ($slots as $slot) {
            $minute = $this->minutes($slot['start_time']);

            if ($previous !== null && $minute <= $previous) {
                $this->fail('projects', __('supervisor.rule_order_mismatch'));
            }

            $previous = $minute;
        }

        // Each project runs until the next one starts; the last until the session ends
        $out = [];

        foreach ($slots as $index => $slot) {
            $out[] = [
                'project_id' => $slot['project_id'],
                'position' => $slot['position'],
                'start_time' => $slot['start_time'],
                'end_time' => $slots[$index + 1]['start_time'] ?? $endTime,
            ];
        }

        return $out;
    }

    /**
     * A project may only be added when it belongs to this session's section,
     * is under way, and is not already booked somewhere else. The page lists
     * only eligible projects, but the page is not the guard.
     */
    public function validateProjectsEligible(array $projectIds, DefenseSession $session): void
    {
        $projects = Project::with('team')->whereKey($projectIds)->get();

        if ($projects->count() !== count(array_unique($projectIds))) {
            $this->fail('projects', __('supervisor.rule_project_unknown'));
        }

        foreach ($projects as $project) {
            if ($project->team?->section_id !== $session->section_id) {
                $this->fail('projects', __('supervisor.rule_project_other_section', ['title' => $project->title]));
            }

            if ($project->status !== 'in_progress') {
                $this->fail('projects', __('supervisor.rule_project_status', ['title' => $project->title]));
            }

            $booked = $project->sessionProject;

            if ($booked && $booked->defense_session_id !== $session->id) {
                $this->fail('projects', __('supervisor.rule_project_booked', ['title' => $project->title]));
            }
        }
    }

    /** Sessions on the same date whose time range overlaps the one being saved */
    private function overlapping(Carbon $date, string $start, string $end, ?DefenseSession $ignore)
    {
        return DefenseSession::query()
            ->whereDate('session_date', $date->toDateString())
            ->where('status', '!=', 'cancelled')
            // old.start < new.end AND old.end > new.start
            ->whereTime('start_time', '<', $end)
            ->whereTime('end_time', '>', $start)
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id));
    }

    private function minutes(string $time): int
    {
        [$hours, $minutes] = array_pad(explode(':', $time), 2, '0');

        return ((int) $hours * 60) + (int) $minutes;
    }

    private function short(string $time): string
    {
        return substr($time, 0, 5);
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
