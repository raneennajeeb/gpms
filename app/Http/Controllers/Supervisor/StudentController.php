<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Section;
use App\Models\User;
use App\Services\StudentImporter;
use App\Support\ProjectHealth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StudentController extends Controller
{
    use ScopesToSupervisor;

    public function index(Request $request): View
    {
        $students = $this->supervisorStudents($request)
            ->with(['section', 'teamMemberships.team.project.stages'])
            ->when($request->query('q'), function ($query, $term) {
                $query->where(fn ($inner) => $inner
                    ->where('name', 'like', '%' . $term . '%')
                    ->orWhere('university_id', 'like', '%' . $term . '%'));
            })
            ->when($request->query('section'), fn ($query, $id) => $query->where('section_id', $id))
            ->orderBy($request->query('sort') === 'id' ? 'university_id' : 'name')
            ->paginate(20)
            ->withQueryString();

        // Each row needs the student's project, stage and whether it is slipping
        $rows = $students->getCollection()->map(function (User $student) {
            $team = $student->teamMemberships->firstWhere('status', 'accepted')?->team;
            $project = $team?->project;

            return [
                'student' => $student,
                'team' => $team,
                'project' => $project,
                'stage' => $project?->stages->firstWhere('status', 'in_progress'),
                'health' => $project ? ProjectHealth::for($project) : null,
            ];
        });

        return view('supervisor.students', [
            'students' => $students,
            'rows' => $rows,
            'sections' => $this->supervisorSections($request)->orderBy('number')->get(),
        ]);
    }

    /* ---------- Import ---------- */

    public function importForm(Request $request): View
    {
        return view('supervisor.students-import', [
            'sections' => $this->supervisorSections($request)->orderBy('number')->get(),
            'columns' => StudentImporter::COLUMNS,
            'preview' => session('import_preview'),
            'sectionId' => session('import_section'),
        ]);
    }

    public function template(StudentImporter $importer): Response
    {
        // A BOM so Excel opens the Arabic correctly
        return response("\xEF\xBB\xBF" . $importer->template(), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="students-template.csv"',
        ]);
    }

    /** Pass one: read the file and show what would happen. Nothing is written. */
    public function importPreview(Request $request, StudentImporter $importer): RedirectResponse
    {
        $section = $this->validatedSection($request);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ], [], ['file' => __('supervisor.import_file')]);

        $preview = $importer->preview($request->file('file'), $section);

        return back()->with([
            'import_preview' => $preview,
            'import_section' => $section->id,
        ]);
    }

    /** Pass two: write the rows the supervisor has just seen */
    public function importConfirm(Request $request, StudentImporter $importer): RedirectResponse
    {
        $section = $this->validatedSection($request);

        $request->validate([
            'rows' => ['required', 'array', 'min:1'],
        ]);

        $result = $importer->commit($request->input('rows'), $section);

        return redirect()->route('supervisor.students.import')->with([
            'success' => __('supervisor.import_done', [
                'created' => $result['created'],
                'updated' => $result['updated'],
            ]),
            // Shown once: the file is streamed from the session and then gone
            'import_passwords' => $result['passwords'],
        ]);
    }

    public function downloadPasswords(Request $request, StudentImporter $importer): Response
    {
        $passwords = session('import_passwords');

        abort_if(empty($passwords), 404);

        // Pulling the list out of the session means this link works exactly once
        $request->session()->forget('import_passwords');

        return response("\xEF\xBB\xBF" . $importer->passwordsCsv($passwords), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="temporary-passwords.csv"',
        ]);
    }

    /** The other way in: one student at a time */
    public function store(Request $request): RedirectResponse
    {
        $section = $this->validatedSection($request);

        $data = $request->validate([
            'university_id' => ['required', 'string', 'regex:/^\d+$/', 'max:20', 'unique:users,university_id'],
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ], [], [
            'university_id' => __('student.university_id'),
            'name' => __('common.name'),
            'email' => __('common.email'),
        ]);

        $temporary = Str::password(10, true, true, false);

        $student = User::create($data + [
            'password' => Hash::make($temporary),
            'role' => 'student',
            'section_id' => $section->id,
            'branch_id' => $section->branch_id,
            'department_id' => $section->department_id,
            'must_change_password' => true,
        ]);

        AuditLog::record('student_added', $student, [], ['section' => $section->number]);

        return back()->with([
            'success' => __('supervisor.student_added'),
            'import_passwords' => [[
                'university_id' => $student->university_id,
                'name' => $student->name,
                'password' => $temporary,
            ]],
        ]);
    }

    /** The section must be one of this supervisor's own */
    private function validatedSection(Request $request): Section
    {
        $id = $request->input('section_id');

        return $this->supervisorSections($request)->whereKey($id)->firstOr(fn () => abort(403));
    }
}
