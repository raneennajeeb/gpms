<?php

namespace App\Models;

use App\Support\Grade;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArchiveEntry extends Model
{
    protected $fillable = [
        'faculty_id', 'department_id', 'branch_id', 'project_id', 'created_by',
        'title', 'description', 'year', 'supervisor_name', 'defense_date', 'hall_name',
        'grade', 'final_score', 'competition', 'competition_name', 'competition_result',
        'github_url', 'cover_image', 'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'defense_date' => 'date',
            'final_score' => 'decimal:2',
            'competition' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function students(): HasMany
    {
        return $this->hasMany(ArchiveStudent::class);
    }

    public function committee(): HasMany
    {
        return $this->hasMany(ArchiveCommittee::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(ArchiveFile::class);
    }

    public function technologies(): BelongsToMany
    {
        return $this->belongsToMany(Technology::class, 'archive_technologies');
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    public function similarityResults(): HasMany
    {
        return $this->hasMany(SimilarityResult::class);
    }

    public function scopeCompetitions(Builder $query): void
    {
        $query->where('competition', true);
    }

    public function scopeOfFaculty(Builder $query, int $facultyId): void
    {
        $query->where('faculty_id', $facultyId);
    }

    public function gradeLabel(?string $locale = null): string
    {
        return Grade::label($this->grade, $locale);
    }

    // Everything the archive card shows, loaded in one go to avoid N+1 queries
    public function scopeWithCardData(Builder $query): void
    {
        $query->with(['faculty', 'branch', 'students', 'committee']);
    }

    public function gradeColor(): string
    {
        return match ($this->grade) {
            'excellent' => 'success',
            'very_good' => 'info',
            default => 'warning',
        };
    }
}
