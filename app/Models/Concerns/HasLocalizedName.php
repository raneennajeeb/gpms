<?php

namespace App\Models\Concerns;

// For models with name_ar and name_en columns (faculties, departments, branches)
trait HasLocalizedName
{
    public function localizedName(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }
}
