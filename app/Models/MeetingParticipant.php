<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'user_id',
        'peer_id',
        'display_name',
        'camera_enabled',
        'microphone_enabled',
        'screen_enabled',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'camera_enabled' => 'boolean',
            'microphone_enabled' => 'boolean',
            'screen_enabled' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
