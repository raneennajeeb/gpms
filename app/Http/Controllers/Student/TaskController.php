<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskController extends Controller
{
    use ResolvesStudentProject;

    private const COLUMNS = ['todo', 'doing', 'done'];

    public function index(Request $request): View
    {
        $project = $this->studentProject($request);

        $tasks = $project->tasks()->with('assignee')->orderBy('position')->get();

        return view('student.tasks', [
            'project' => $project,
            'columns' => collect(self::COLUMNS)
                ->mapWithKeys(fn (string $key) => [$key => $tasks->where('status', $key)->values()]),
            'members' => $project->team->members()->where('status', 'accepted')->with('user')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $project = $this->studentProject($request);
        abort_unless($this->canEdit($request, $project->team), 403);

        $data = $this->validated($request);

        $project->tasks()->create($data + [
            'status' => 'todo',
            'position' => (int) $project->tasks()->where('status', 'todo')->max('position') + 1,
        ]);

        return back()->with('success', __('student.task_added'));
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        $project = $this->guardTask($request, $task);

        $task->update($this->validated($request));

        return back()->with('success', __('student.task_updated'));
    }

    public function destroy(Request $request, Task $task): RedirectResponse
    {
        $this->guardTask($request, $task);

        $task->delete();

        return back()->with('success', __('student.task_deleted'));
    }

    /** Drag and drop: the board posts the new column and order of every card */
    public function reorder(Request $request): JsonResponse
    {
        $project = $this->studentProject($request);
        abort_unless($this->canEdit($request, $project->team), 403);

        $data = $request->validate([
            'tasks' => ['required', 'array'],
            'tasks.*.id' => ['required', 'integer'],
            'tasks.*.status' => ['required', 'in:' . implode(',', self::COLUMNS)],
            'tasks.*.position' => ['required', 'integer', 'min:0'],
        ]);

        // Scoped to this project, so no id from elsewhere can be moved
        $ids = $project->tasks()->pluck('id');

        foreach ($data['tasks'] as $row) {
            if ($ids->contains($row['id'])) {
                Task::whereKey($row['id'])->update([
                    'status' => $row['status'],
                    'position' => $row['position'],
                ]);
            }
        }

        return response()->json(['ok' => true]);
    }

    private function validated(Request $request): array
    {
        $project = $this->studentProject($request);

        $memberIds = $project->team->members()
            ->where('status', 'accepted')
            ->pluck('user_id')
            ->implode(',');

        return $request->validate([
            'title' => ['required', 'string', 'min:3', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            // A task can only be given to someone actually on the team
            'assigned_to' => ['nullable', 'integer', 'in:' . $memberIds],
            'due_date' => ['nullable', 'date'],
            'status' => ['sometimes', 'in:' . implode(',', self::COLUMNS)],
        ], [], [
            'title' => __('student.task_title'),
            'assigned_to' => __('student.task_assignee'),
            'due_date' => __('student.task_due'),
        ]);
    }

    private function guardTask(Request $request, Task $task)
    {
        $project = $this->studentProject($request);

        abort_unless($task->project_id === $project->id, 403);
        abort_unless($this->canEdit($request, $project->team), 403);

        return $project;
    }
}
