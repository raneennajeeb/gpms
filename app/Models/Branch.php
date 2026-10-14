<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasLocalizedName;

    protected $fillable = ['code', 'name_ar', 'name_en'];

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }
}
