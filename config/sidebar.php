<?php

// Sidebar links. Each link: translation key (lang/*/nav.php), route name, icon, and the roles that see it.
// ":role" in a route name is replaced by the user's role, so one entry serves every dashboard.
// Routes that don't exist yet (built in steps 03-07) show as "#" until they are added.
return [
    ['key' => 'dashboard', 'route' => ':role.dashboard', 'icon' => 'layout-dashboard', 'roles' => ['student', 'supervisor', 'coordinator', 'committee']],

    // Student
    ['key' => 'my_project', 'route' => 'student.project', 'icon' => 'folder', 'roles' => ['student']],
    ['key' => 'my_team', 'route' => 'student.team', 'icon' => 'users', 'roles' => ['student']],
    ['key' => 'submissions', 'route' => 'student.submissions', 'icon' => 'upload', 'roles' => ['student']],

    // Supervisor
    ['key' => 'projects', 'route' => 'supervisor.projects', 'icon' => 'folder', 'roles' => ['supervisor']],
    ['key' => 'students', 'route' => 'supervisor.students', 'icon' => 'graduation-cap', 'roles' => ['supervisor']],
    ['key' => 'ideas_bank', 'route' => 'supervisor.ideas', 'icon' => 'lightbulb', 'roles' => ['supervisor']],
    ['key' => 'archive_manage', 'route' => 'supervisor.archive', 'icon' => 'archive', 'roles' => ['supervisor']],

    // Coordinator
    ['key' => 'projects', 'route' => 'coordinator.projects', 'icon' => 'folder', 'roles' => ['coordinator']],
    ['key' => 'students', 'route' => 'coordinator.students', 'icon' => 'graduation-cap', 'roles' => ['coordinator']],
    ['key' => 'supervisors', 'route' => 'coordinator.supervisors', 'icon' => 'users', 'roles' => ['coordinator']],
    ['key' => 'committees', 'route' => 'coordinator.defenses', 'icon' => 'scale', 'roles' => ['coordinator']],
    ['key' => 'reports', 'route' => 'coordinator.reports', 'icon' => 'bar-chart', 'roles' => ['coordinator']],
    ['key' => 'settings', 'route' => 'coordinator.settings', 'icon' => 'settings', 'roles' => ['coordinator']],

    // Committee
    ['key' => 'my_defenses', 'route' => 'committee.defenses', 'icon' => 'scale', 'roles' => ['committee']],

    // Everyone
    ['key' => 'calendar', 'route' => 'shared.calendar', 'icon' => 'calendar', 'roles' => ['student', 'supervisor', 'coordinator', 'committee']],
    ['key' => 'archive', 'route' => 'public.archive', 'icon' => 'archive', 'roles' => ['student', 'supervisor', 'coordinator', 'committee']],
];
