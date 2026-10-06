<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupervisorEvaluation extends Model
{
    protected $fillable = ['project_id', 'supervisor_id', 'score', 'letter', 'label_ar', 'comment'];

    protected function casts(): array
    {
        return ['score' => 'decimal:2'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }
}
