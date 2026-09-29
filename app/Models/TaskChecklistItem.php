<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskChecklistItem extends Model
{
    use HasFactory;

    protected $fillable = ['task_id', 'title', 'is_done', 'position', 'completed_by', 'completed_at'];

    protected function casts(): array
    {
        return [
            'is_done' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
