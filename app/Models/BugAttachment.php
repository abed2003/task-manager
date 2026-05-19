<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BugAttachment extends Model
{
    use HasFactory;

    protected $fillable = ['bug_report_id', 'uploaded_by', 'path', 'original_name', 'mime_type', 'size'];

    public function bugReport(): BelongsTo
    {
        return $this->belongsTo(BugReport::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
