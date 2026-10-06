<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Team extends Model
{
    protected $fillable = ['section_id', 'leader_id', 'name', 'status'];

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function project(): HasOne
    {
        return $this->hasOne(Project::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TeamMessage::class);
    }
}
