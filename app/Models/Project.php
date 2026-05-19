<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_ON_HOLD = 'on_hold';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REVIEW = 'review';

    protected $fillable = [
        'name',
        'description',
        'client_name',
        'manager_id',
        'status',
        'starts_at',
        'due_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'due_at' => 'date',
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_ACTIVE => 'قيد التنفيذ',
            self::STATUS_COMPLETED => 'مكتمل',
            self::STATUS_ON_HOLD => 'متوقف',
            self::STATUS_REVIEW => 'مراجعة',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProjectDocument::class);
    }

    public function bugReports(): HasMany
    {
        return $this->hasMany(BugReport::class);
    }

    public function sprints(): HasMany
    {
        return $this->hasMany(Sprint::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
}
