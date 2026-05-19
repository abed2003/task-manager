<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BugComment extends Model
{
    use HasFactory;

    protected $fillable = ['bug_report_id', 'user_id', 'body'];

    public function bugReport(): BelongsTo
    {
        return $this->belongsTo(BugReport::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
