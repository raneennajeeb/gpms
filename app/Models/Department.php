<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasLocalizedName;

    protected $fillable = ['faculty_id', 'name_ar', 'name_en'];

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
