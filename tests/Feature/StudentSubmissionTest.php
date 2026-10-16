<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Faculty;
use App\Models\Project;
use App\Models\Section;
use App\Models\Semester;
use App\Models\Submission;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private Project $project;

    private Submission $submission;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

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

        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $section = Section::create([
            'number' => '901',
            'department_id' => $department->id,
            'branch_id' => $branch->id,
            'semester_id' => $semester->id,
            'supervisor_id' => $supervisor->id,
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'section_id' => $section->id,
            'branch_id' => $branch->id,
            'department_id' => $department->id,
        ]);

        $team = Team::create([
            'section_id' => $section->id,
            'leader_id' => $this->student->id,
            'name' => 'فريق',
            'status' => 'active',
        ]);

        $team->members()->create([
            'user_id' => $this->student->id,
            'status' => 'accepted',
            'invited_at' => now(),
            'expires_at' => now()->addDays(7),
            'responded_at' => now(),
        ]);

        $this->project = Project::create([
            'team_id' => $team->id,
            'supervisor_id' => $supervisor->id,
            'semester_id' => $semester->id,
            'title' => 'مشروع الاختبار',
            'description' => str_repeat('وصف ', 30),
            'category' => 'web',
            'keywords' => ['أ', 'ب', 'ج'],
            'status' => 'in_progress',
            'submitted_at' => now(),
        ]);

        $this->submission = $this->project->submissions()->create([
            'title' => 'التقرير الأول',
            'type' => 'report',
            'due_date' => now()->addWeek(),
            'status' => 'pending',
        ]);
    }

    public function test_uploading_twice_creates_v1_and_v2_and_keeps_both(): void
    {
        foreach ([1, 2] as $round) {
            $this->actingAs($this->student)
                ->post('/student/submissions/' . $this->submission->id . '/upload', [
                    'file' => UploadedFile::fake()->create('report-v' . $round . '.pdf', 10, 'application/pdf'),
                ])
                ->assertRedirect();
        }

        $versions = $this->submission->versions()->orderBy('version_number')->get();

        $this->assertSame([1, 2], $versions->pluck('version_number')->all());

        // Two separate files, and the first one is still there
        $this->assertNotSame($versions[0]->file_path, $versions[1]->file_path);

        foreach ($versions as $version) {
            Storage::disk('local')->assertExists($version->file_path);
        }
    }

    public function test_an_upload_after_the_deadline_is_marked_late(): void
    {
        $this->submission->update(['due_date' => now()->subDay()]);

        $this->actingAs($this->student)
            ->post('/student/submissions/' . $this->submission->id . '/upload', [
                'file' => UploadedFile::fake()->create('late.pdf', 10, 'application/pdf'),
            ]);

        $this->assertSame('late', $this->submission->fresh()->status);
    }

    public function test_a_student_cannot_upload_to_another_teams_submission(): void
    {
        $outsider = User::factory()->create([
            'role' => 'student',
            'section_id' => $this->student->section_id,
            'branch_id' => $this->student->branch_id,
        ]);

        $team = Team::create([
            'section_id' => $outsider->section_id,
            'leader_id' => $outsider->id,
            'name' => 'فريق آخر',
            'status' => 'active',
        ]);

        $team->members()->create([
            'user_id' => $outsider->id,
            'status' => 'accepted',
            'invited_at' => now(),
            'expires_at' => now()->addDays(7),
            'responded_at' => now(),
        ]);

        Project::create([
            'team_id' => $team->id,
            'supervisor_id' => $this->project->supervisor_id,
            'semester_id' => $this->project->semester_id,
            'title' => 'مشروع آخر',
            'description' => str_repeat('وصف ', 30),
            'category' => 'web',
            'keywords' => ['أ', 'ب', 'ج'],
            'status' => 'in_progress',
            'submitted_at' => now(),
        ]);

        $this->actingAs($outsider)
            ->post('/student/submissions/' . $this->submission->id . '/upload', [
                'file' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
            ])
            ->assertForbidden();

        $this->assertSame(0, $this->submission->versions()->count());
    }

    public function test_a_student_cannot_download_another_teams_file(): void
    {
        $this->actingAs($this->student)
            ->post('/student/submissions/' . $this->submission->id . '/upload', [
                'file' => UploadedFile::fake()->create('mine.pdf', 10, 'application/pdf'),
            ]);

        $version = $this->submission->versions()->firstOrFail();

        $outsider = User::factory()->create([
            'role' => 'student',
            'section_id' => $this->student->section_id,
        ]);

        // No team at all: the trait refuses before any file is touched
        $this->actingAs($outsider)
            ->get('/student/submissions/versions/' . $version->id . '/download')
            ->assertForbidden();
    }
}
