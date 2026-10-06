<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectStage extends Model
{
    protected $fillable = [
        'project_id',
        'stage_template_id',
        'name_ar',
        'name_en',
        'description_ar',
        'sort_order',
        'weight',
        'status',
        'due_date',
        'completed_at',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(StageTemplate::class, 'stage_template_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
