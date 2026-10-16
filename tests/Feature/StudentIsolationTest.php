<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Section;
use App\Models\Semester;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Section $sectionA;

    private Section $sectionB;

    protected function setUp(): void
    {
        parent::setUp();

        $faculty = Faculty::create([
            'name_ar' => 'كلية', 'name_en' => 'Faculty', 'icon' => 'cpu',
            'color' => '#000000', 'is_featured' => false,
        ]);

        $department = $faculty->departments()->create(['name_ar' => 'تخصص', 'name_en' => 'Department']);
        $branch = Branch::create(['code' => '09999', 'name_ar' => 'فرع', 'name_en' => 'Branch']);

        $semester = Semester::create([
            'name' => 'Test', 'start_date' => '2026-02-01',
            'end_date' => '2026-06-30', 'is_current' => true,
        ]);

        $this->sectionA = $this->makeSection('901', $department, $branch, $semester);
        $this->sectionB = $this->makeSection('902', $department, $branch, $semester);
    }

    private function makeSection(string $number, Department $department, Branch $branch, Semester $semester): Section
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        return Section::create([
            'number' => $number,
            'department_id' => $department->id,
            'branch_id' => $branch->id,
            'semester_id' => $semester->id,
            'supervisor_id' => $supervisor->id,
        ]);
    }

    private function student(Section $section, string $name = 'طالب'): User
    {
        return User::factory()->create([
            'role' => 'student',
            'name' => $name,
            'section_id' => $section->id,
            'branch_id' => $section->branch_id,
            'department_id' => $section->department_id,
        ]);
    }

    public function test_the_section_page_only_lists_the_students_own_section(): void
    {
        $mine = $this->student($this->sectionA, 'زميلي');
        $theirs = $this->student($this->sectionB, 'غريب');
        $me = $this->student($this->sectionA, 'أنا');

        $response = $this->actingAs($me)->get('/student/section');

        $response->assertOk();
        $response->assertSee($mine->name);
        $response->assertDontSee($theirs->name);
    }

    public function test_a_leader_cannot_invite_a_student_from_another_section(): void
    {
        $leader = $this->student($this->sectionA);
        $outsider = $this->student($this->sectionB);

        $team = $this->teamFor($leader);

        $response = $this->actingAs($leader)
            ->from('/student/team')
            ->post('/student/team/invite', ['user_id' => $outsider->id]);

        $response->assertSessionHasErrors('user_id');
        $this->assertDatabaseMissing('team_members', ['user_id' => $outsider->id, 'team_id' => $team->id]);
    }

    public function test_a_team_cannot_grow_past_the_configured_maximum(): void
    {
        $leader = $this->student($this->sectionA);
        $team = $this->teamFor($leader);

        // The leader already fills one of the three seats
        foreach (range(1, 2) as $index) {
            $this->actingAs($leader)->post('/student/team/invite', [
                'user_id' => $this->student($this->sectionA, 'عضو ' . $index)->id,
            ]);
        }

        $extra = $this->student($this->sectionA, 'زائد');

        $response = $this->actingAs($leader)
            ->from('/student/team')
            ->post('/student/team/invite', ['user_id' => $extra->id]);

        $response->assertSessionHasErrors('user_id');
        $this->assertSame(3, $team->members()->count());
    }

    public function test_a_student_can_only_belong_to_one_team(): void
    {
        $student = $this->student($this->sectionA);
        $this->teamFor($student);

        $response = $this->actingAs($student)
            ->from('/student/team')
            ->post('/student/team', ['name' => 'فريق ثانٍ']);

        $response->assertSessionHasErrors('name');
        $this->assertSame(1, Team::count());
    }

    public function test_a_student_cannot_answer_someone_elses_invitation(): void
    {
        $leader = $this->student($this->sectionA);
        $team = $this->teamFor($leader);

        $invitee = $this->student($this->sectionA, 'المدعو');
        $this->actingAs($leader)->post('/student/team/invite', ['user_id' => $invitee->id]);

        $invitation = TeamMember::where('user_id', $invitee->id)->firstOrFail();
        $bystander = $this->student($this->sectionA, 'متطفل');

        $this->actingAs($bystander)
            ->post('/student/team/invitations/' . $invitation->id . '/respond', ['answer' => 'accept'])
            ->assertForbidden();

        $this->assertSame('pending', $invitation->fresh()->status);
    }

    private function teamFor(User $leader): Team
    {
        $this->actingAs($leader)->post('/student/team', ['name' => 'فريق الاختبار']);

        return Team::where('leader_id', $leader->id)->firstOrFail();
    }
}
