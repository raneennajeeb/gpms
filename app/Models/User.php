<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'university_id',
        'name',
        'email',
        'password',
        'role',
        'department_id',
        'branch_id',
        'section_id',
        'phone',
        'avatar',
        'must_change_password',
        'is_active',
        'locale',
        'theme',
        'onboarding_done',
        'is_demo',
        'last_login_at',
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

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function scopeStudentsOfSection(Builder $query, Section|int $section): void
    {
        $query->where('section_id', $section instanceof Section ? $section->id : $section)->where('role', 'student');
    }

    // الإضافات الجديدة
    public function teamMemberships(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function supervisedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'supervisor_id');
    }
}
