<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Submission;
use App\Models\SubmissionVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionController extends Controller
{
    use ResolvesStudentProject;

    private const ALLOWED = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'zip', 'rar'];

    public function index(Request $request): View
    {
        $project = $this->studentProject($request);

        return view('student.submissions', [
            'project' => $project,
            'submissions' => $project->submissions()
                ->with(['stage', 'versions.uploader'])
                ->orderBy('due_date')
                ->get(),
            'maxMb' => (int) setting('max_upload_mb', 20),
            'allowed' => self::ALLOWED,
        ]);
    }

    public function upload(Request $request, Submission $submission): RedirectResponse
    {
        $project = $this->studentProject($request);

        // The submission must belong to this student's own project
        abort_unless($submission->project_id === $project->id, 403);
        abort_unless($this->canEdit($request, $project->team), 403);

        $maxKb = (int) setting('max_upload_mb', 20) * 1024;

        $request->validate([
            'file' => ['required', 'file', 'mimes:' . implode(',', self::ALLOWED), 'max:' . $maxKb],
            'note' => ['nullable', 'string', 'max:500'],
        ], [], ['file' => __('student.file')]);

        $file = $request->file('file');

        // A new row every time: v1, v2, v3... nothing is ever overwritten
        $next = (int) $submission->versions()->max('version_number') + 1;

        // Private disk, so the only way in is the download route below
        $path = $file->store('submissions/' . $submission->id, 'local');

        SubmissionVersion::create([
            'submission_id' => $submission->id,
            'version_number' => $next,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => $request->user()->id,
            'note' => $request->input('note'),
        ]);

        $submission->update([
            'status' => $submission->due_date && now()->greaterThan($submission->due_date)
                ? 'late'
                : 'submitted',
        ]);

        ActivityLog::create([
            'project_id' => $project->id,
            'user_id' => $request->user()->id,
            'action' => 'submission_uploaded',
            'description' => __('activity.submission_uploaded', [
                'title' => $submission->title,
                'version' => $next,
            ]),
        ]);

        return back()->with('success', __('student.upload_done', ['version' => $next]));
    }

    public function download(Request $request, SubmissionVersion $version): StreamedResponse
    {
        $project = $this->studentProject($request);

        // Files are private: the viewer must be on the project that owns them
        abort_unless($version->submission->project_id === $project->id, 403);
        abort_unless(Storage::disk('local')->exists($version->file_path), 404);

        return Storage::disk('local')->download($version->file_path, $version->original_name);
    }
}
