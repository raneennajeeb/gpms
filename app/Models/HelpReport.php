<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpReport extends Model
{
    protected $fillable = ['user_id', 'ticket_number', 'type', 'description', 'screenshot_path', 'status'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
