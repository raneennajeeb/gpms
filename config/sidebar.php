<?php

// Sidebar links. Each link: translation key (lang/*/nav.php), route name, icon, and the roles that see it.
// ":role" in a route name is replaced by the user's role, so one entry serves every dashboard.
// Routes that don't exist yet (built in steps 03-07) show as "#" until they are added.
return [
    ['key' => 'dashboard', 'route' => ':role.dashboard', 'icon' => 'layout-dashboard', 'roles' => ['student', 'supervisor', 'coordinator', 'admin']],

    // Student
    ['key' => 'my_project', 'route' => 'student.project', 'icon' => 'folder', 'roles' => ['student']],
    ['key' => 'my_team', 'route' => 'student.team', 'icon' => 'users', 'roles' => ['student']],
    ['key' => 'submissions', 'route' => 'student.submissions', 'icon' => 'upload', 'roles' => ['student']],

    // Supervisor
    ['key' => 'ideas_queue', 'route' => 'supervisor.ideas-queue', 'icon' => 'lightbulb', 'roles' => ['supervisor']],
    ['key' => 'students', 'route' => 'supervisor.students', 'icon' => 'graduation-cap', 'roles' => ['supervisor']],
    ['key' => 'ideas_bank', 'route' => 'supervisor.idea-bank', 'icon' => 'folder', 'roles' => ['supervisor']],
    ['key' => 'defense_sessions', 'route' => 'supervisor.defense-sessions', 'icon' => 'scale', 'roles' => ['supervisor']],
    ['key' => 'meetings', 'route' => 'supervisor.meetings', 'icon' => 'calendar', 'roles' => ['supervisor']],
    ['key' => 'inquiries', 'route' => 'supervisor.inquiries', 'icon' => 'send', 'roles' => ['supervisor']],
    ['key' => 'archive_manage', 'route' => 'supervisor.archive', 'icon' => 'archive', 'roles' => ['supervisor']],

    // Coordinator
    ['key' => 'projects', 'route' => 'coordinator.projects', 'icon' => 'folder', 'roles' => ['coordinator']],
    ['key' => 'students', 'route' => 'coordinator.students', 'icon' => 'graduation-cap', 'roles' => ['coordinator']],
    ['key' => 'supervisors', 'route' => 'coordinator.supervisors', 'icon' => 'users', 'roles' => ['coordinator']],
    ['key' => 'committees', 'route' => 'coordinator.defenses', 'icon' => 'scale', 'roles' => ['coordinator']],
    ['key' => 'reports', 'route' => 'coordinator.reports', 'icon' => 'bar-chart', 'roles' => ['coordinator']],
    ['key' => 'settings', 'route' => 'coordinator.settings', 'icon' => 'settings', 'roles' => ['coordinator']],

    // Everyone
    ['key' => 'calendar', 'route' => 'shared.calendar', 'icon' => 'calendar', 'roles' => ['student', 'supervisor', 'coordinator', 'admin']],
    ['key' => 'archive', 'route' => 'public.archive.index', 'icon' => 'archive', 'roles' => ['student', 'supervisor', 'coordinator', 'admin']],
];
