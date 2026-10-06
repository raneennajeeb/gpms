<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Idea extends Model
{
    protected $fillable = ['supervisor_id', 'title', 'description', 'technologies', 'status', 'reserved_by_team_id'];

    protected function casts(): array
    {
        return ['technologies' => 'array'];
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function reservedByTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'reserved_by_team_id');
    }
}
