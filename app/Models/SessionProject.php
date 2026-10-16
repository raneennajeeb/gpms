<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionProject extends Model
{
    protected $fillable = ['defense_session_id', 'project_id', 'position', 'start_time', 'end_time'];

    public function defenseSession(): BelongsTo
    {
        return $this->belongsTo(DefenseSession::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
