<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Meeting extends Model
{
    use HasFactory;

    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'title',
        'agenda',
        'project_id',
        'organizer_id',
        'starts_at',
        'ends_at',
        'location',
        'status',
        'summary',
        'decisions',
        'next_steps',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_SCHEDULED => 'مجدول',
            self::STATUS_COMPLETED => 'منتهي',
            self::STATUS_CANCELLED => 'ملغي',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function participants(): HasMany
    {
        return $this->hasMany(MeetingParticipant::class);
    }
}
