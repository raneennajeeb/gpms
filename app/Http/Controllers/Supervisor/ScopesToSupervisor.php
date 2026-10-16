<?php

namespace App\Http\Controllers\Supervisor;

use App\Models\ArchiveEntry;
use App\Models\DefenseSession;
use App\Models\Project;
use App\Models\Section;
use App\Models\SupervisorInquiry;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Everything a supervisor can reach is filtered by their own id, here, once.
 * A controller that forgets to call one of these returns nothing rather than
 * someone else's data.
 */
trait ScopesToSupervisor
{
    protected function supervisorProjects(Request $request): Builder
    {
        return Project::where('supervisor_id', $request->user()->id);
    }

    /** Opening a project that is not theirs is a 403, not an empty page */
    protected function ownProject(Request $request, Project $project): Project
    {
        abort_unless($project->supervisor_id === $request->user()->id, 403);

        return $project;
    }

    protected function supervisorSections(Request $request): Builder
    {
        return Section::where('supervisor_id', $request->user()->id);
    }

    protected function supervisorStudents(Request $request): Builder
    {
        return User::where('role', 'student')
            ->whereIn('section_id', $this->supervisorSections($request)->select('id'));
    }

    protected function supervisorSessions(Request $request): Builder
    {
        return DefenseSession::where('supervisor_id', $request->user()->id);
    }

    protected function ownSession(Request $request, DefenseSession $session): DefenseSession
    {
        abort_unless($session->supervisor_id === $request->user()->id, 403);

        return $session;
    }

    protected function supervisorInquiries(Request $request): Builder
    {
        return SupervisorInquiry::where('supervisor_id', $request->user()->id);
    }

    /** Archive entries belong to whoever created them */
    protected function supervisorArchive(Request $request): Builder
    {
        return ArchiveEntry::where('created_by', $request->user()->id);
    }

    protected function ownArchiveEntry(Request $request, ArchiveEntry $entry): ArchiveEntry
    {
        abort_unless($entry->created_by === $request->user()->id, 403);

        return $entry;
    }
}
