<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StageTemplate extends Model
{
    protected $fillable = ['name_ar', 'name_en', 'description_ar', 'sort_order', 'weight', 'days_after_approval'];

    public function projectStages(): HasMany
    {
        return $this->hasMany(ProjectStage::class);
    }
}
