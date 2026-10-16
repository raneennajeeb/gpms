<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectAttachment extends Model
{
    protected $fillable = ['project_id', 'comment_id', 'user_id', 'file_path', 'original_name', 'mime_type', 'size'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(ProjectComment::class, 'comment_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
