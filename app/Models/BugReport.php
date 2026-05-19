<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BugReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'task_id',
        'title',
        'description',
        'reproduction_steps',
        'severity',
        'status',
        'assigned_to',
        'reported_by',
        'fixed_at',
    ];

    protected function casts(): array
    {
        return ['fixed_at' => 'datetime'];
    }

    public static function severityLabels(): array
    {
        return ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'];
    }

    public static function statusLabels(): array
    {
        return ['open' => 'Open', 'in_progress' => 'In Progress', 'testing' => 'Testing', 'fixed' => 'Fixed', 'closed' => 'Closed'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(BugComment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(BugAttachment::class);
    }
}
