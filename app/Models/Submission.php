<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Submission extends Model
{
    protected $fillable = ['project_id', 'project_stage_id', 'title', 'type', 'due_date', 'status', 'supervisor_notes'];

    protected function casts(): array
    {
        return ['due_date' => 'datetime'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(ProjectStage::class, 'project_stage_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(SubmissionVersion::class)->orderBy('version_number');
    }
}
