<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'university_id', 'name', 'email', 'password', 'role',
        'department_id', 'faculty_id', 'branch_id', 'section_id', 'phone', 'avatar',
        'must_change_password', 'is_active', 'locale', 'theme',
        'onboarding_done', 'is_demo', 'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'is_active' => 'boolean',
            'onboarding_done' => 'boolean',
            'is_demo' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    // Only the coordinator has one: the faculty whose branches they manage
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    // Sections this user supervises (only meaningful for supervisors)
    public function supervisedSections(): HasMany
    {
        return $this->hasMany(Section::class, 'supervisor_id');
    }

    public function teamMemberships(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function supervisedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'supervisor_id');
    }

    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(ArchiveEntry::class, 'favorites')->withTimestamps();
    }

    public function scopeStudentsOfSection(Builder $query, int $sectionId): void
    {
        $query->where('role', 'student')->where('section_id', $sectionId);
    }
}
