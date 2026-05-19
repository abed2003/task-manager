<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sprint extends Model
{
    use HasFactory;

    protected $fillable = ['project_id', 'name', 'goal', 'status', 'starts_at', 'ends_at', 'review_notes', 'created_by'];

    protected function casts(): array
    {
        return ['starts_at' => 'date', 'ends_at' => 'date'];
    }

    public static function statusLabels(): array
    {
        return ['planned' => 'Planned', 'active' => 'Active', 'review' => 'Review', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
    }

    public function progress(): int
    {
        $total = $this->tasks->count();

        if ($total === 0) {
            return 0;
        }

        return (int) round(($this->tasks->where('status', Task::STATUS_COMPLETED)->count() / $total) * 100);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
