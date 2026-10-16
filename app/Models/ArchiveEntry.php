<?php

namespace App\Models;

use App\Support\ArabicText;
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
        'github_url', 'cover_image', 'is_featured', 'idea_summary',
    ];

    protected static function booted(): void
    {
        // Keep the normalised search column in step with the row's own columns.
        // Student names and technologies are attached afterwards, so the seeder
        // and the archive form call refreshSearchText() once they are in place.
        static::saving(function (self $entry) {
            $entry->search_text = $entry->buildSearchText();
        });
    }

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

    /* ---------- Search ---------- */

    private function buildSearchText(): string
    {
        $parts = [$this->title, $this->idea_summary, $this->description, $this->supervisor_name];

        if ($this->exists) {
            $parts[] = $this->students()->pluck('student_name')->join(' ');
            $parts[] = $this->technologies()->pluck('name')->join(' ');
        }

        return ArabicText::normalize(implode(' ', array_filter($parts)));
    }

    /** Call this after students and technologies have been attached. */
    public function refreshSearchText(): void
    {
        $this->newQuery()->whereKey($this->getKey())->update(['search_text' => $this->buildSearchText()]);
    }

    public function scopeSearch(Builder $query, ?string $term): void
    {
        $normalized = ArabicText::normalize($term);

        if ($normalized === '') {
            return;
        }

        // FULLTEXT is fast but skips words under four letters, which rules out
        // plenty of real Arabic words, so those get a LIKE pass as well
        $query->where(function (Builder $inner) use ($normalized) {
            $inner->whereFullText('search_text', $normalized);

            if (ArabicText::needsLikeFallback($normalized)) {
                foreach (explode(' ', $normalized) as $word) {
                    $inner->orWhere('search_text', 'like', '%' . $word . '%');
                }
            }
        });
    }

    /**
     * Projects a reader is likely to want next.
     * Score = (shared technologies x 2) + (shared meaningful words in the title).
     * Technologies weigh double because they are a deliberate choice, while a
     * shared title word can be a coincidence.
     */
    public function similarEntries(int $limit = 4)
    {
        $techIds = $this->technologies()->pluck('technologies.id');
        $words = array_filter(
            explode(' ', ArabicText::normalize($this->title)),
            fn (string $word) => mb_strlen($word) >= 3
        );

        // Narrow to plausible candidates in SQL, then score them in PHP:
        // the archive is small, and the rule stays readable
        $candidates = static::query()
            ->whereKeyNot($this->getKey())
            ->where(function (Builder $query) use ($techIds, $words) {
                $query->whereHas('technologies', fn (Builder $t) => $t->whereIn('technologies.id', $techIds));

                foreach ($words as $word) {
                    $query->orWhere('search_text', 'like', '%' . $word . '%');
                }
            })
            ->withCardData()
            ->with('technologies')
            ->take(30)
            ->get();

        return $candidates
            ->map(function (self $entry) use ($techIds, $words) {
                $sharedTech = $entry->technologies->pluck('id')->intersect($techIds)->count();
                $sharedWords = count(array_filter(
                    $words,
                    fn (string $word) => str_contains((string) $entry->search_text, $word)
                ));

                $entry->similarity_score = ($sharedTech * 2) + $sharedWords;

                return $entry;
            })
            ->filter(fn (self $entry) => $entry->similarity_score > 0)
            ->sortByDesc(fn (self $entry) => [$entry->similarity_score, (float) $entry->final_score])
            ->take($limit)
            ->values();
    }
}
