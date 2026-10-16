<?php

namespace App\Http\Controllers\Student;

use App\Models\Project;
use App\Models\Team;
use Illuminate\Http\Request;

/**
 * Every student page works on the viewer's own team and project, and nothing
 * else. Resolving it here rather than from a route id means there is no id in
 * a URL for anyone to change.
 */
trait ResolvesStudentProject
{
    protected function studentTeam(Request $request): Team
    {
        $team = $request->user()->currentTeam();

        abort_if($team === null, 403);

        return $team;
    }

    protected function studentProject(Request $request): Project
    {
        $project = $this->studentTeam($request)->project;

        abort_if($project === null, 403);

        return $project;
    }

    /** True when the viewer may change things: any accepted member counts */
    protected function canEdit(Request $request, Team $team): bool
    {
        return $team->members()
            ->where('user_id', $request->user()->id)
            ->where('status', 'accepted')
            ->exists();
    }
}
