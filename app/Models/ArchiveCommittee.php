<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchiveCommittee extends Model
{
    // Laravel would guess "archive_committees"; our table name is singular
    protected $table = 'archive_committee';

    protected $fillable = ['archive_entry_id', 'member_name'];

    public function archiveEntry(): BelongsTo
    {
        return $this->belongsTo(ArchiveEntry::class);
    }
}
