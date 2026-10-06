<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommitteeMember extends Model
{
    protected $fillable = ['defense_schedule_id', 'user_id', 'role'];

    public function defenseSchedule(): BelongsTo
    {
        return $this->belongsTo(DefenseSchedule::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
