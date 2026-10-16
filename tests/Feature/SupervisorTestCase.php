<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Faculty;
use App\Models\Project;
use App\Models\Room;
use App\Models\Section;
use App\Models\Semester;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Shared scaffolding: a branch, a section, a supervisor and a committee pool */
abstract class SupervisorTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $supervisor;

    protected Section $section;

    protected Branch $branch;

    protected Semester $semester;

    protected Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        // RefreshDatabase migrates but does not seed, and approving a proposal
        // builds its stages from these templates
        $this->seed(\Database\Seeders\StageTemplatesSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);

        $faculty = Faculty::create([
            'name_ar' => 'كلية', 'name_en' => 'Faculty', 'icon' => 'cpu',
            'color' => '#000000', 'is_featured' => false,
        ]);

        $department = $faculty->departments()->create(['name_ar' => 'تخصص', 'name_en' => 'Department']);

        $this->branch = Branch::create(['code' => '09999', 'name_ar' => 'فرع', 'name_en' => 'Branch']);

        $this->semester = Semester::create([
            'name' => 'Test', 'start_date' => '2026-02-01',
            'end_date' => '2026-06-30', 'is_current' => true,
        ]);

        $this->supervisor = User::factory()->create(['role' => 'supervisor', 'name' => 'د. المشرف']);

        $this->section = Section::create([
            'number' => '901',
            'department_id' => $department->id,
            'branch_id' => $this->branch->id,
            'semester_id' => $this->semester->id,
            'supervisor_id' => $this->supervisor->id,
        ]);

        $this->room = Room::create([
            'name' => 'قاعة الاختبار',
            'building' => 'مبنى',
            'capacity' => 30,
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);
    }

    /** Doctors the branch allows on a committee */
    protected function pool(int $count = 3): array
    {
        $doctors = [];

        foreach (range(1, $count) as $index) {
            $doctor = User::factory()->create(['role' => 'supervisor', 'name' => 'د. عضو ' . $index]);
            $this->branch->committeePool()->attach($doctor->id);
            $doctors[] = $doctor;
        }

        return $doctors;
    }

    protected function student(?Section $section = null): User
    {
        $section ??= $this->section;

        return User::factory()->create([
            'role' => 'student',
            'section_id' => $section->id,
            'branch_id' => $section->branch_id,
            'department_id' => $section->department_id,
        ]);
    }

    protected function project(string $status = 'in_progress', ?Section $section = null, ?User $supervisor = null): Project
    {
        $section ??= $this->section;
        $leader = $this->student($section);

        $team = Team::create([
            'section_id' => $section->id,
            'leader_id' => $leader->id,
            'name' => 'فريق ' . $leader->id,
            'status' => 'active',
        ]);

        $team->members()->create([
            'user_id' => $leader->id,
            'status' => 'accepted',
            'invited_at' => now(),
            'expires_at' => now()->addDays(7),
            'responded_at' => now(),
        ]);

        return Project::create([
            'team_id' => $team->id,
            'supervisor_id' => ($supervisor ?? $this->supervisor)->id,
            'semester_id' => $this->semester->id,
            'title' => 'مشروع رقم ' . $team->id,
            'description' => str_repeat('وصف المشروع ', 15),
            'category' => 'web',
            'keywords' => ['أ', 'ب', 'ج'],
            'status' => $status,
            'submitted_at' => now(),
        ]);
    }

    /** The next Saturday, so a test date never lands on the closed days */
    protected function openDate(int $weeksAhead = 1): string
    {
        return now()->addWeeks($weeksAhead)->next(\Carbon\Carbon::SATURDAY)->toDateString();
    }
}
