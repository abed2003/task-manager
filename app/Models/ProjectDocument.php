<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectDocument extends Model
{
    use HasFactory;

    public const CATEGORY_API_DOCS = 'api_docs';
    public const CATEGORY_ERD = 'erd';
    public const CATEGORY_TECHNICAL_NOTES = 'technical_notes';
    public const CATEGORY_MEETING_NOTES = 'meeting_notes';
    public const CATEGORY_OTHER = 'other';

    protected $fillable = ['project_id', 'title', 'category', 'content', 'created_by', 'updated_by'];

    public static function categoryLabels(): array
    {
        return [
            self::CATEGORY_API_DOCS => 'API Docs',
            self::CATEGORY_ERD => 'ERD',
            self::CATEGORY_TECHNICAL_NOTES => 'Technical Notes',
            self::CATEGORY_MEETING_NOTES => 'Meeting Notes',
            self::CATEGORY_OTHER => 'Other',
        ];
    }

    public function categoryLabel(): string
    {
        return self::categoryLabels()[$this->category] ?? $this->category;
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(DocumentRevision::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(DocumentAttachment::class);
    }
}
