<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    /**
     * Projects the user may open: admins see everything, others see projects
     * they manage, belong to, or have tasks in.
     */
    public function scopeAccessibleBy(Builder $query, User $user): Builder
    {
        if ($user->role === 'admin') {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user) {
            $query->where('manager_id', $user->id)
                ->orWhereHas('members', fn ($members) => $members->where('users.id', $user->id))
                ->orWhereHas('tasks', fn ($tasks) => $tasks->where('user_id', $user->id));
        });
    }

    public function isAccessibleBy(User $user): bool
    {
        return static::whereKey($this->id)->accessibleBy($user)->exists();
    }

    public function canManageMembers(User $user): bool
    {
        return $user->role === 'admin' || (int) $this->manager_id === $user->id;
    }

    public function scopeManagedBy(Builder $query, User $user): Builder
    {
        return $user->role === 'admin' ? $query : $query->where('manager_id', $user->id);
    }

    /**
     * IDs of the people a task in this project can be assigned to: its members and its manager.
     */
    public function assignableUserIds(): array
    {
        return $this->members()->pluck('users.id')
            ->push($this->manager_id)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function addMember(User|int $user, ?User $addedBy = null): bool
    {
        $userId = $user instanceof User ? $user->id : (int) $user;

        // The manager already has full access, so they are never listed as a plain member.
        if ($userId === (int) $this->manager_id || $this->members()->whereKey($userId)->exists()) {
            return false;
        }

        $this->members()->attach($userId, ['added_by' => $addedBy?->id]);

        return true;
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->withPivot('added_by')
            ->withTimestamps();
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
