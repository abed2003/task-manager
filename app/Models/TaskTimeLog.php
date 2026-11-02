<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskTimeLog extends Model
{
    use HasFactory;

    protected $fillable = ['task_id', 'user_id', 'minutes', 'logged_on', 'note'];

    protected function casts(): array
    {
        return ['logged_on' => 'date'];
    }

    public function hours(): float
    {
        return round($this->minutes / 60, 2);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
