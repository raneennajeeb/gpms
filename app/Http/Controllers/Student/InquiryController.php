<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ProjectAttachment;
use App\Models\SupervisorInquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InquiryController extends Controller
{
    use ResolvesStudentProject;

    private const TYPES = ['documentation_inquiry', 'feature_done', 'meeting_request', 'other'];

    private const ALLOWED = ['png', 'jpg', 'jpeg', 'pdf', 'zip'];

    public function index(Request $request): View
    {
        $project = $this->studentProject($request);

        return view('student.contact-supervisor', [
            'project' => $project,
            'supervisor' => $project->supervisor,
            'types' => self::TYPES,
            'inquiries' => $project->inquiries()
                ->where('student_id', $request->user()->id)
                ->with('supervisor')
                ->latest()
                ->get(),
            // Notes and files the supervisor sent to this student's project
            'feedback' => $project->comments()
                ->whereIn('kind', ['feedback', 'reply'])
                ->with(['user', 'attachments'])
                ->latest()
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $project = $this->studentProject($request);
        abort_unless($this->canEdit($request, $project->team), 403);

        $data = $request->validate([
            'type' => ['required', 'in:' . implode(',', self::TYPES)],
            'message' => ['required', 'string', 'min:20', 'max:1000'],
            'attachment' => ['nullable', 'file', 'mimes:' . implode(',', self::ALLOWED), 'max:5120'],
        ], [], [
            'type' => __('student.inquiry_type'),
            'message' => __('student.message'),
            'attachment' => __('student.attachment'),
        ]);

        $inquiry = SupervisorInquiry::create([
            'project_id' => $project->id,
            'student_id' => $request->user()->id,
            'supervisor_id' => $project->supervisor_id,
            'type' => $data['type'],
            'message' => $data['message'],
            'status' => 'new',
        ]);

        if ($file = $request->file('attachment')) {
            // Private disk: a screenshot is only reachable through the route below
            ProjectAttachment::create([
                'project_id' => $project->id,
                'inquiry_id' => $inquiry->id,
                'user_id' => $request->user()->id,
                'file_path' => $file->store('inquiries/' . $inquiry->id, 'local'),
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        $project->supervisor?->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'inquiry_received',
            'data' => [
                'key' => 'notifications.inquiry_received',
                'params' => ['student' => $request->user()->name],
            ],
        ]);

        return back()->with('success', __('student.inquiry_sent'));
    }

    public function download(Request $request, ProjectAttachment $attachment): StreamedResponse
    {
        $project = $this->studentProject($request);

        abort_unless($attachment->project_id === $project->id, 403);
        abort_unless(Storage::disk('local')->exists($attachment->file_path), 404);

        return Storage::disk('local')->download($attachment->file_path, $attachment->original_name);
    }
}
