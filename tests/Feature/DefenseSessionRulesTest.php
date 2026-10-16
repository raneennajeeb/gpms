<?php

namespace Tests\Feature;

use App\Models\DefenseSession;
use App\Models\Room;
use Carbon\Carbon;

/** The six booking rules, one test each */
class DefenseSessionRulesTest extends SupervisorTestCase
{
    /** A booking that breaks nothing, as the baseline every test bends */
    private function payload(array $overrides = []): array
    {
        [$a, $b] = $this->pool();

        return array_merge([
            'section_id' => $this->section->id,
            'room_id' => $this->room->id,
            'session_date' => $this->openDate(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'committee' => [$a->id, $b->id],
            'chair' => $a->id,
        ], $overrides);
    }

    private function book(array $payload)
    {
        return $this->actingAs($this->supervisor)
            ->from('/supervisor/defense-sessions/create')
            ->post('/supervisor/defense-sessions', $payload);
    }

    public function test_a_valid_booking_is_accepted(): void
    {
        $this->book($this->payload())->assertRedirect('/supervisor/defense-sessions');

        $this->assertSame(1, DefenseSession::count());
    }

    /* ---------- Rule 3: date and hours ---------- */

    public function test_rule_3_rejects_a_past_date(): void
    {
        $this->book($this->payload(['session_date' => now()->subWeek()->toDateString()]))
            ->assertSessionHasErrors('session_date');

        $this->assertSame(0, DefenseSession::count());
    }

    public function test_rule_3_rejects_thursday_and_friday(): void
    {
        foreach ([Carbon::THURSDAY, Carbon::FRIDAY] as $day) {
            $this->book($this->payload([
                'session_date' => now()->addWeek()->next($day)->toDateString(),
            ]))->assertSessionHasErrors('session_date');
        }

        $this->assertSame(0, DefenseSession::count());
    }

    public function test_rule_3_rejects_hours_outside_8_to_14(): void
    {
        $this->book($this->payload(['start_time' => '07:00', 'end_time' => '09:00']))
            ->assertSessionHasErrors('start_time');

        $this->book($this->payload(['start_time' => '13:00', 'end_time' => '15:00']))
            ->assertSessionHasErrors('end_time');

        $this->assertSame(0, DefenseSession::count());
    }

    public function test_rule_3_rejects_an_end_before_the_start(): void
    {
        $this->book($this->payload(['start_time' => '11:00', 'end_time' => '09:00']))
            ->assertSessionHasErrors('end_time');
    }

    /* ---------- Rule 1: the room ---------- */

    public function test_rule_1_rejects_a_room_already_booked_at_that_time(): void
    {
        $this->book($this->payload())->assertRedirect();

        // A second booking in the same room, overlapping by an hour
        $this->book($this->payload(['start_time' => '10:00', 'end_time' => '12:00']))
            ->assertSessionHasErrors('room_id');

        $this->assertSame(1, DefenseSession::count());
    }

    public function test_rule_1_rejects_an_inactive_room(): void
    {
        $closed = Room::create([
            'name' => 'قاعة مغلقة', 'building' => 'مبنى', 'capacity' => 10,
            'branch_id' => $this->branch->id, 'is_active' => false,
        ]);

        $this->book($this->payload(['room_id' => $closed->id]))->assertSessionHasErrors('room_id');
    }

    public function test_a_room_booked_at_a_different_hour_is_fine(): void
    {
        $this->book($this->payload())->assertRedirect();

        // 11:00 starts exactly when the first ends: no overlap
        $this->book($this->payload(['start_time' => '11:00', 'end_time' => '13:00']))->assertRedirect();

        $this->assertSame(2, DefenseSession::count());
    }

    /* ---------- Rule 2: the people ---------- */

    public function test_rule_2_rejects_a_committee_member_who_is_busy(): void
    {
        $pool = $this->pool();
        $other = Room::create([
            'name' => 'قاعة ثانية', 'building' => 'مبنى', 'capacity' => 10,
            'branch_id' => $this->branch->id, 'is_active' => true,
        ]);

        $this->book([
            'section_id' => $this->section->id,
            'room_id' => $this->room->id,
            'session_date' => $this->openDate(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'committee' => [$pool[0]->id, $pool[1]->id],
            'chair' => $pool[0]->id,
        ])->assertRedirect();

        // A different room, but one of the same doctors, at an overlapping time
        $this->book([
            'section_id' => $this->section->id,
            'room_id' => $other->id,
            'session_date' => $this->openDate(),
            'start_time' => '10:00',
            'end_time' => '12:00',
            'committee' => [$pool[1]->id, $pool[2]->id],
            'chair' => $pool[1]->id,
        ])->assertSessionHasErrors('committee');

        $this->assertSame(1, DefenseSession::count());
    }

    public function test_the_supervisor_cannot_sit_on_their_own_committee(): void
    {
        $pool = $this->pool();
        $this->branch->committeePool()->attach($this->supervisor->id);

        $this->book($this->payload([
            'committee' => [$this->supervisor->id, $pool[0]->id],
            'chair' => $pool[0]->id,
        ]))->assertSessionHasErrors('committee');
    }

    public function test_committee_members_must_come_from_the_branch_pool(): void
    {
        $outsider = \App\Models\User::factory()->create(['role' => 'supervisor']);
        $pool = $this->pool();

        $this->book($this->payload([
            'committee' => [$pool[0]->id, $outsider->id],
            'chair' => $pool[0]->id,
        ]))->assertSessionHasErrors('committee');
    }

    public function test_the_chair_must_be_one_of_the_chosen_members(): void
    {
        $pool = $this->pool();

        $this->book($this->payload([
            'committee' => [$pool[0]->id, $pool[1]->id],
            'chair' => $pool[2]->id,
        ]))->assertSessionHasErrors('chair');
    }

    /* ---------- Rules 4, 5 and 6: the slots ---------- */

    public function test_rule_4_rejects_a_slot_outside_the_session(): void
    {
        $project = $this->project();

        $this->book($this->payload([
            'projects' => [
                ['project_id' => $project->id, 'position' => 1, 'start_time' => '12:00'],
            ],
        ]))->assertSessionHasErrors('projects');
    }

    public function test_rule_5_rejects_two_projects_starting_together(): void
    {
        $first = $this->project();
        $second = $this->project();

        $this->book($this->payload([
            'projects' => [
                ['project_id' => $first->id, 'position' => 1, 'start_time' => '09:00'],
                ['project_id' => $second->id, 'position' => 2, 'start_time' => '09:00'],
            ],
        ]))->assertSessionHasErrors('projects');
    }

    public function test_rule_6_rejects_positions_with_a_gap(): void
    {
        $first = $this->project();
        $second = $this->project();

        $this->book($this->payload([
            'projects' => [
                ['project_id' => $first->id, 'position' => 1, 'start_time' => '09:00'],
                ['project_id' => $second->id, 'position' => 3, 'start_time' => '10:00'],
            ],
        ]))->assertSessionHasErrors('projects');
    }

    public function test_rule_6_rejects_an_order_that_contradicts_the_times(): void
    {
        $first = $this->project();
        $second = $this->project();

        // Position 1 starts after position 2
        $this->book($this->payload([
            'projects' => [
                ['project_id' => $first->id, 'position' => 1, 'start_time' => '10:00'],
                ['project_id' => $second->id, 'position' => 2, 'start_time' => '09:00'],
            ],
        ]))->assertSessionHasErrors('projects');
    }

    public function test_valid_slots_get_an_end_time_from_the_next_one(): void
    {
        $first = $this->project();
        $second = $this->project();

        $this->book($this->payload([
            'projects' => [
                ['project_id' => $first->id, 'position' => 1, 'start_time' => '09:00'],
                ['project_id' => $second->id, 'position' => 2, 'start_time' => '10:00'],
            ],
        ]))->assertRedirect();

        $slots = DefenseSession::firstOrFail()->sessionProjects()->orderBy('position')->get();

        $this->assertSame('09:00:00', $slots[0]->start_time);
        $this->assertSame('10:00:00', $slots[0]->end_time); // until the next one starts
        $this->assertSame('11:00:00', $slots[1]->end_time); // the last runs to the end
    }

    /* ---------- Eligibility ---------- */

    public function test_a_project_from_another_section_is_refused(): void
    {
        $otherSection = \App\Models\Section::create([
            'number' => '902',
            'department_id' => $this->section->department_id,
            'branch_id' => $this->branch->id,
            'semester_id' => $this->semester->id,
            'supervisor_id' => $this->supervisor->id,
        ]);

        $outsider = $this->project('in_progress', $otherSection);

        $this->book($this->payload([
            'projects' => [['project_id' => $outsider->id, 'position' => 1, 'start_time' => '09:00']],
        ]))->assertSessionHasErrors('projects');
    }

    public function test_a_project_that_is_not_in_progress_is_refused(): void
    {
        $pending = $this->project('pending_approval');

        $this->book($this->payload([
            'projects' => [['project_id' => $pending->id, 'position' => 1, 'start_time' => '09:00']],
        ]))->assertSessionHasErrors('projects');
    }

    public function test_only_the_booking_supervisor_may_cancel(): void
    {
        $this->book($this->payload())->assertRedirect();
        $session = DefenseSession::firstOrFail();

        $stranger = \App\Models\User::factory()->create(['role' => 'supervisor']);

        $this->actingAs($stranger)
            ->delete('/supervisor/defense-sessions/' . $session->id, ['reason' => 'سبب كافٍ للإلغاء'])
            ->assertForbidden();

        $this->assertSame('scheduled', $session->fresh()->status);
    }
}
