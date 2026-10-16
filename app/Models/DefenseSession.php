<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DefenseSession extends Model
{
    protected $fillable = [
        'section_id', 'supervisor_id', 'room_id', 'session_date',
        'start_time', 'end_time', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return ['session_date' => 'date'];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // The projects in the order they are defended
    public function sessionProjects(): HasMany
    {
        return $this->hasMany(SessionProject::class)->orderBy('position');
    }

    // The committee for this session (pivot table session_committee)
    public function committee(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'session_committee')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }
}
