<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evaluation extends Model
{
    protected $fillable = [
        'defense_schedule_id', 'project_id', 'evaluator_id',
        'report_score', 'presentation_score', 'discussion_score', 'project_score',
        'final_score', 'comments', 'is_locked',
    ];

    protected function casts(): array
    {
        return [
            'report_score' => 'decimal:2',
            'presentation_score' => 'decimal:2',
            'discussion_score' => 'decimal:2',
            'project_score' => 'decimal:2',
            'final_score' => 'decimal:2',
            'is_locked' => 'boolean',
        ];
    }

    public function defenseSchedule(): BelongsTo
    {
        return $this->belongsTo(DefenseSchedule::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }
}
