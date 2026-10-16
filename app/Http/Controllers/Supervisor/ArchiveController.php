<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\ArchiveEntry;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Faculty;
use App\Models\Project;
use App\Models\Room;
use App\Models\Technology;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ArchiveController extends Controller
{
    use ScopesToSupervisor;

    /** Faculties are managed by the system administrator; here they are a dropdown */
    public function mine(Request $request): View
    {
        return view('supervisor.archive', [
            'entries' => $this->supervisorArchive($request)
                ->with(['faculty', 'branch'])
                ->latest('defense_date')
                ->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('supervisor.archive-form', $this->formData($request) + [
            'entry' => null,
            'prefill' => [],
            'project' => null,
        ]);
    }

    /** The same form, filled in from a project that has finished */
    public function fromProject(Request $request, Project $project): View
    {
        $this->ownProject($request, $project);

        $project->load(['team.members.user', 'team.section.branch', 'team.section.department', 'technologies']);

        return view('supervisor.archive-form', $this->formData($request) + [
            'entry' => null,
            'project' => $project,
            'prefill' => [
                'title' => $project->title,
                'description' => $project->description,
                'department_id' => $project->team?->section?->department_id,
                'branch_id' => $project->team?->section?->branch_id,
                'faculty_id' => $project->team?->section?->department?->faculty_id,
                'github_url' => $project->github_url,
                'students' => $project->team->members->where('status', 'accepted')->pluck('user.name')->values()->all(),
                'technologies' => $project->technologies->pluck('name')->all(),
                'year' => now()->year,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $entry = DB::transaction(function () use ($request, $data) {
            $entry = ArchiveEntry::create($this->entryPayload($request, $data) + [
                'created_by' => $request->user()->id,
                'project_id' => $data['project_id'] ?? null,
            ]);

            $this->syncRelations($request, $entry, $data);

            // Archiving a live project closes it: the team keeps read-only access
            if (! empty($data['project_id'])) {
                $project = $this->supervisorProjects($request)->whereKey($data['project_id'])->first();
                $project?->update(['status' => 'archived']);
            }

            AuditLog::record('archive_created', $entry, [], ['title' => $entry->title]);

            return $entry;
        });

        return redirect()->route('public.archive.show', $entry)
            ->with('success', __('supervisor.archive_saved'));
    }

    public function edit(Request $request, ArchiveEntry $entry): View
    {
        $this->ownArchiveEntry($request, $entry);

        $entry->load(['students', 'committee', 'technologies', 'files']);

        return view('supervisor.archive-form', $this->formData($request) + [
            'entry' => $entry,
            'project' => null,
            'prefill' => [],
        ]);
    }

    public function update(Request $request, ArchiveEntry $entry): RedirectResponse
    {
        $this->ownArchiveEntry($request, $entry);

        $data = $this->validated($request);
        $old = $entry->only(['title', 'grade', 'is_featured']);

        DB::transaction(function () use ($request, $entry, $data, $old) {
            $entry->update($this->entryPayload($request, $data, $entry));
            $this->syncRelations($request, $entry, $data);

            AuditLog::record('archive_updated', $entry, $old, $entry->only(['title', 'grade', 'is_featured']));
        });

        return redirect()->route('supervisor.archive')->with('success', __('supervisor.archive_saved'));
    }

    public function destroy(Request $request, ArchiveEntry $entry): RedirectResponse
    {
        $this->ownArchiveEntry($request, $entry);

        AuditLog::record('archive_deleted', $entry, $entry->only(['title']), []);
        $entry->delete();

        return back()->with('success', __('supervisor.archive_deleted'));
    }

    public function toggleFeatured(Request $request, ArchiveEntry $entry): RedirectResponse
    {
        $this->ownArchiveEntry($request, $entry);

        $entry->update(['is_featured' => ! $entry->is_featured]);

        AuditLog::record('archive_featured', $entry, [], ['is_featured' => $entry->is_featured]);

        return back()->with('success', __('supervisor.archive_saved'));
    }

    /* ---------- Internals ---------- */

    private function validated(Request $request): array
    {
        return $request->validate([
            'project_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'min:10', 'max:200'],
            'idea_summary' => ['required', 'string', 'min:20', 'max:500'],
            'description' => ['required', 'string', 'min:100', 'max:5000'],
            'faculty_id' => ['required', 'integer', 'exists:faculties,id'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:' . (now()->year + 1)],
            'technologies' => ['array', 'max:15'],
            'technologies.*' => ['string', 'max:40'],

            'students' => ['required', 'array', 'min:1', 'max:5'],
            'students.*' => ['required', 'string', 'min:3', 'max:120'],
            'committee' => ['required', 'array', 'min:1', 'max:5'],
            'committee.*' => ['required', 'string', 'min:3', 'max:120'],

            'defense_date' => ['required', 'date'],
            'hall_name' => ['required', 'string', 'max:120'],
            'grade' => ['required', 'in:excellent,very_good,good'],
            'final_score' => ['nullable', 'numeric', 'min:0', 'max:100'],

            'competition' => ['boolean'],
            // These two only matter when the switch is on
            'competition_name' => ['required_if:competition,1', 'nullable', 'string', 'max:150'],
            'competition_result' => ['nullable', 'string', 'max:150'],

            'github_url' => ['nullable', 'url', 'starts_with:https://github.com/', 'max:255'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'documents' => ['array', 'max:5'],
            'documents.*' => ['file', 'mimes:pdf,zip', 'max:20480'],
        ], [], [
            'title' => __('archive.title'),
            'idea_summary' => __('archive.idea_summary'),
            'description' => __('archive.about_project'),
            'faculty_id' => __('archive.faculty'),
            'department_id' => __('archive.department'),
            'branch_id' => __('archive.branch'),
            'students' => __('archive.students'),
            'committee' => __('archive.committee'),
            'defense_date' => __('archive.defense_date'),
            'hall_name' => __('archive.hall'),
            'grade' => __('archive.grade'),
            'competition_name' => __('archive.competition_name'),
            'cover_image' => __('supervisor.cover_image'),
        ]);
    }

    private function entryPayload(Request $request, array $data, ?ArchiveEntry $entry = null): array
    {
        $payload = [
            'faculty_id' => $data['faculty_id'],
            'department_id' => $data['department_id'],
            'branch_id' => $data['branch_id'],
            'title' => $data['title'],
            'idea_summary' => $data['idea_summary'],
            'description' => $data['description'],
            'year' => $data['year'],
            // Always the signed-in doctor: the archive records who supervised
            'supervisor_name' => $request->user()->name,
            'defense_date' => $data['defense_date'],
            'hall_name' => $data['hall_name'],
            'grade' => $data['grade'],
            'final_score' => $data['final_score'] ?? null,
            'competition' => (bool) ($data['competition'] ?? false),
            'competition_name' => ($data['competition'] ?? false) ? $data['competition_name'] : null,
            'competition_result' => ($data['competition'] ?? false) ? ($data['competition_result'] ?? null) : null,
            'github_url' => $data['github_url'] ?? null,
        ];

        if ($request->hasFile('cover_image')) {
            $payload['cover_image'] = $request->file('cover_image')->store('archive/covers', 'public');
        }

        return $payload;
    }

    private function syncRelations(Request $request, ArchiveEntry $entry, array $data): void
    {
        $entry->students()->delete();

        foreach ($data['students'] as $name) {
            $entry->students()->create(['student_name' => trim($name)]);
        }

        $entry->committee()->delete();

        foreach ($data['committee'] as $name) {
            $entry->committee()->create(['member_name' => trim($name)]);
        }

        $entry->technologies()->sync(
            collect($data['technologies'] ?? [])
                ->map(fn (string $name) => Technology::firstOrCreate(['name' => trim($name)])->id)
                ->all()
        );

        foreach ($request->file('documents') ?? [] as $file) {
            $entry->files()->create([
                'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'file_path' => $file->store('archive/files/' . $entry->id, 'public'),
                'original_name' => $file->getClientOriginalName(),
            ]);
        }

        // Student names and technologies feed the Arabic search column
        $entry->refreshSearchText();
    }

    private function formData(Request $request): array
    {
        return [
            'faculties' => Faculty::with('departments')->orderBy('name_ar')->get(),
            'branches' => Branch::orderBy('name_ar')->get(),
            'rooms' => Room::where('is_active', true)->orderBy('name')->get(),
            'technologies' => Technology::orderBy('name')->pluck('name'),
            'finished' => $this->supervisorProjects($request)
                ->whereIn('status', ['in_progress', 'defended'])
                ->whereHas('supervisorEvaluation')
                ->orderBy('title')
                ->get(),
        ];
    }
}
