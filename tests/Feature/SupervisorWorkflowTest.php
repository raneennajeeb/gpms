<?php

namespace Tests\Feature;

use App\Models\ArchiveEntry;
use App\Models\Section;
use App\Models\StageTemplate;
use App\Models\User;

class SupervisorWorkflowTest extends SupervisorTestCase
{
    public function test_approving_a_proposal_creates_the_stages_with_real_due_dates(): void
    {
        $project = $this->project('pending_approval');

        $this->actingAs($this->supervisor)
            ->post('/supervisor/projects/' . $project->id . '/approve')
            ->assertRedirect();

        $project->refresh();

        $this->assertSame('in_progress', $project->status);
        $this->assertSame($this->supervisor->id, $project->approved_by);
        $this->assertNotNull($project->approved_at);

        $templates = StageTemplate::orderBy('sort_order')->get();
        $stages = $project->stages()->orderBy('sort_order')->get();

        $this->assertCount($templates->count(), $stages);

        // The idea stage is closed by the act of approving; the next one opens
        $this->assertSame('completed', $stages[0]->status);
        $this->assertSame('in_progress', $stages[1]->status);
        $this->assertSame('locked', $stages[2]->status);

        // Each due date is its template's offset from the approval
        foreach ($stages as $index => $stage) {
            $this->assertSame(
                $project->approved_at->copy()->addDays($templates[$index]->days_after_approval)->toDateString(),
                $stage->due_date->toDateString()
            );
        }

        // Progress is the weight of the finished stages
        $this->assertSame((int) $templates[0]->weight, $project->fresh()->progress_percent);
    }

    public function test_rejecting_without_a_reason_is_refused(): void
    {
        $project = $this->project('pending_approval');

        $this->actingAs($this->supervisor)
            ->from('/supervisor/ideas-queue')
            ->post('/supervisor/projects/' . $project->id . '/reject', ['review_note' => 'قصير'])
            ->assertSessionHasErrors('review_note');

        $this->assertSame('pending_approval', $project->fresh()->status);
    }

    public function test_a_reason_reaches_the_student(): void
    {
        $project = $this->project('pending_approval');
        $reason = 'الفكرة قريبة جداً من مشروع سابق، يرجى تعديل النطاق.';

        $this->actingAs($this->supervisor)
            ->post('/supervisor/projects/' . $project->id . '/request-changes', ['review_note' => $reason])
            ->assertRedirect();

        $project->refresh();

        $this->assertSame('changes_requested', $project->status);
        $this->assertSame($reason, $project->review_note);
    }

    public function test_a_supervisor_cannot_decide_on_another_supervisors_project(): void
    {
        $stranger = User::factory()->create(['role' => 'supervisor']);
        $project = $this->project('pending_approval');

        $this->actingAs($stranger)
            ->post('/supervisor/projects/' . $project->id . '/approve')
            ->assertForbidden();

        $this->assertSame('pending_approval', $project->fresh()->status);
    }

    public function test_the_students_list_only_shows_the_supervisors_own_sections(): void
    {
        $mine = $this->student();

        $otherSupervisor = User::factory()->create(['role' => 'supervisor']);
        $otherSection = Section::create([
            'number' => '903',
            'department_id' => $this->section->department_id,
            'branch_id' => $this->branch->id,
            'semester_id' => $this->semester->id,
            'supervisor_id' => $otherSupervisor->id,
        ]);
        $theirs = $this->student($otherSection);

        $response = $this->actingAs($this->supervisor)->get('/supervisor/students');

        $response->assertOk();
        $response->assertSee($mine->name);
        $response->assertDontSee($theirs->name);
    }

    public function test_approving_a_stage_moves_the_progress_bar(): void
    {
        $project = $this->project('pending_approval');

        $this->actingAs($this->supervisor)->post('/supervisor/projects/' . $project->id . '/approve');

        $second = $project->stages()->where('sort_order', 2)->firstOrFail();
        $before = $project->fresh()->progress_percent;

        $this->actingAs($this->supervisor)
            ->post('/supervisor/stages/' . $second->id . '/complete')
            ->assertRedirect();

        $this->assertSame('completed', $second->fresh()->status);
        $this->assertSame('in_progress', $project->stages()->where('sort_order', 3)->first()->status);
        $this->assertSame($before + (int) $second->weight, $project->fresh()->progress_percent);
    }

    public function test_the_evaluation_uses_the_shared_grade_scale(): void
    {
        $project = $this->project();

        // 85 is a B on the agreed scale, not an A
        $this->actingAs($this->supervisor)
            ->post('/supervisor/projects/' . $project->id . '/evaluation', ['score' => 85])
            ->assertRedirect();

        $evaluation = $project->fresh()->supervisorEvaluation;

        $this->assertSame('B', $evaluation->letter);
        $this->assertSame(__('grades.very_good'), $evaluation->label_ar);
    }

    /* ---------- Archive ownership ---------- */

    private function archivePayload(): array
    {
        return [
            'title' => 'نظام أرشفة للاختبار الآلي',
            'idea_summary' => 'ملخص قصير لفكرة المشروع المستخدم في الاختبار.',
            'description' => str_repeat('وصف تفصيلي للمشروع. ', 12),
            'faculty_id' => $this->section->department->faculty_id,
            'department_id' => $this->section->department_id,
            'branch_id' => $this->branch->id,
            'year' => now()->year,
            'students' => ['طالب أول', 'طالب ثانٍ'],
            'committee' => ['د. عضو أول'],
            'defense_date' => now()->subMonth()->toDateString(),
            'hall_name' => 'قاعة 101',
            'grade' => 'excellent',
        ];
    }

    public function test_an_archived_entry_appears_on_the_public_pages(): void
    {
        $this->actingAs($this->supervisor)
            ->post('/supervisor/archive', $this->archivePayload())
            ->assertRedirect();

        $entry = ArchiveEntry::firstOrFail();

        $this->assertSame($this->supervisor->id, $entry->created_by);
        $this->assertSame($this->supervisor->name, $entry->supervisor_name);

        $this->get('/archive/project/' . $entry->id)
            ->assertOk()
            ->assertSee($entry->title);
    }

    public function test_another_supervisor_cannot_edit_or_delete_my_archive_entry(): void
    {
        $this->actingAs($this->supervisor)->post('/supervisor/archive', $this->archivePayload());

        $entry = ArchiveEntry::firstOrFail();
        $stranger = User::factory()->create(['role' => 'supervisor']);

        $this->actingAs($stranger)->get('/supervisor/archive/' . $entry->id . '/edit')->assertForbidden();
        $this->actingAs($stranger)->delete('/supervisor/archive/' . $entry->id)->assertForbidden();

        $this->assertSame(1, ArchiveEntry::count());
    }
}
