<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    protected $fillable = [
        'content_item_id',
        'author_type',
        'author_name',
        'body',
    ];
    
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    public function contentItem(): BelongsTo
    {
        return $this->belongsTo(ContentItem::class, 'content_item_id');
    }

    // Backwards compatibility alias
    public function variant(): BelongsTo
    {
        return $this->contentItem();
    }
    
    public function getAuthorDisplayNameAttribute(): string
    {
        return $this->author_name ?? 'Anonymous';
    }
    
    public function isFromClient(): bool
    {
        return $this->author_type === 'client';
    }
    
    public function isFromAgency(): bool
    {
        return $this->author_type === 'agency';
    }
}
