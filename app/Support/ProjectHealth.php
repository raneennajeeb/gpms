<?php

namespace App\Support;

use App\Models\Project;

/**
 * How a project is doing, judged on the server from three real signals.
 *
 * Points add up, and the total picks the label:
 *   late submission      2 points each  (a missed deadline is the loudest signal)
 *   overdue task         1 point each   (slippage inside the team)
 *   7-14 days quiet      1 point        (no activity logged)
 *   over 14 days quiet   2 points
 *
 *   0-1  good            جيدة
 *   2-3  needs attention تحتاج متابعة
 *   4+   at risk         بخطر
 *
 * The reasons are returned with the score so the page can explain itself
 * instead of showing a colour nobody can account for.
 */
class ProjectHealth
{
    public const GOOD = 'good';

    public const WATCH = 'watch';

    public const RISK = 'risk';

    public function __construct(
        public readonly string $level,
        public readonly int $points,
        public readonly array $reasons,
    ) {}

    public static function for(Project $project): self
    {
        $points = 0;
        $reasons = [];

        $lateSubmissions = $project->submissions()
            ->whereNotIn('status', ['accepted'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->count();

        if ($lateSubmissions > 0) {
            $points += $lateSubmissions * 2;
            $reasons[] = ['key' => 'late_submissions', 'count' => $lateSubmissions];
        }

        $overdueTasks = $project->tasks()
            ->where('status', '!=', 'done')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->count();

        if ($overdueTasks > 0) {
            $points += $overdueTasks;
            $reasons[] = ['key' => 'overdue_tasks', 'count' => $overdueTasks];
        }

        $lastActivity = $project->activities()->max('created_at') ?? $project->created_at;
        $quietDays = (int) now()->diffInDays($lastActivity);

        if ($quietDays > 14) {
            $points += 2;
            $reasons[] = ['key' => 'quiet', 'count' => $quietDays];
        } elseif ($quietDays >= 7) {
            $points += 1;
            $reasons[] = ['key' => 'quiet', 'count' => $quietDays];
        }

        $level = match (true) {
            $points >= 4 => self::RISK,
            $points >= 2 => self::WATCH,
            default => self::GOOD,
        };

        return new self($level, $points, $reasons);
    }

    public function color(): string
    {
        return match ($this->level) {
            self::RISK => 'danger',
            self::WATCH => 'warning',
            default => 'success',
        };
    }

    public function icon(): string
    {
        return match ($this->level) {
            self::RISK => 'alert-circle',
            self::WATCH => 'clock',
            default => 'check-circle',
        };
    }
}
