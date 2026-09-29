<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory;

    public const STATUS_TODO = 'todo';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_REVIEW = 'review';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'project_id',
        'sprint_id',
        'title',
        'description',
        'status',
        'priority',
        'due_at',
        'estimated_hours',
        'is_completed',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
            'due_at' => 'date',
            'estimated_hours' => 'decimal:2',
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_TODO => 'لم تبدأ',
            self::STATUS_IN_PROGRESS => 'قيد التنفيذ',
            self::STATUS_REVIEW => 'قيد المراجعة',
            self::STATUS_COMPLETED => 'مكتملة',
        ];
    }

    public static function priorityLabels(): array
    {
        return [
            'low' => 'منخفضة',
            'medium' => 'متوسطة',
            'high' => 'عالية',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? ($this->is_completed ? 'مكتملة' : 'قيد التنفيذ');
    }

    public function priorityLabel(): string
    {
        return self::priorityLabels()[$this->priority] ?? 'متوسطة';
    }

    public function isOverdue(): bool
    {
        return $this->status !== self::STATUS_COMPLETED
            && $this->due_at !== null
            && $this->due_at->lt(now()->startOfDay());
    }

    public function canBeAccessedBy(User $user): bool
    {
        return $user->role === 'admin' || $this->user_id === $user->id;
    }

    public function loggedHours(): float
    {
        $minutes = $this->relationLoaded('timeLogs')
            ? $this->timeLogs->sum('minutes')
            : $this->timeLogs()->sum('minutes');

        return round($minutes / 60, 2);
    }

    public function recordActivity(string $type, string $description, ?User $actor = null): TaskActivity
    {
        return $this->activities()->create([
            'user_id' => $actor?->id ?? auth()->id(),
            'type' => $type,
            'description' => $description,
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class);
    }

    public function bugReports()
    {
        return $this->hasMany(BugReport::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(TaskChecklistItem::class)->orderBy('position')->orderBy('id');
    }

    public function timeLogs(): HasMany
    {
        return $this->hasMany(TaskTimeLog::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(TaskActivity::class);
    }
}
