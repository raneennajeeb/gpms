<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchiveFile extends Model
{
    protected $fillable = ['archive_entry_id', 'title', 'file_path', 'original_name'];

    public function archiveEntry(): BelongsTo
    {
        return $this->belongsTo(ArchiveEntry::class);
    }
}
