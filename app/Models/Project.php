<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = ['team_id', 'supervisor_id', 'semester_id', 'title', 'description', 'keywords', 'submitted_at'];

    protected function casts(): array
    {
        return [
            'keywords' => 'array',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'similarity_score' => 'decimal:2',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function idea(): BelongsTo
    {
        return $this->belongsTo(Idea::class);
    }

    public function technologies(): BelongsToMany
    {
        return $this->belongsToMany(Technology::class, 'project_technologies');
    }

    public function stages(): HasMany
    {
        return $this->hasMany(ProjectStage::class)->orderBy('sort_order');
    }

    /**
     * نسبة الإنجاز = مجموع أوزان المراحل المكتملة
     */
    public function calculateProgress(): int
    {
        $percent = (int) $this->stages()->where('status', 'completed')->sum('weight');

        $this->forceFill(['progress_percent' => $percent])->save();

        return $percent;
    }
}
