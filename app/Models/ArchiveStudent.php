<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchiveStudent extends Model
{
    protected $fillable = ['archive_entry_id', 'student_name'];

    public function archiveEntry(): BelongsTo
    {
        return $this->belongsTo(ArchiveEntry::class);
    }
}
