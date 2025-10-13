<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    protected $fillable = [
        'content_item_id',
        'user_id',
        'author_name',
        'content',
        'is_internal',
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
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isFromClient(): bool
    {
        // Client comments have no user_id and is_internal = false
        return is_null($this->user_id) && $this->is_internal === false;
    }

    public function isFromAgency(): bool
    {
        // Agency comments have a user_id or is_internal = true
        return !is_null($this->user_id) || $this->is_internal === true;
    }

    // Backwards compatibility: body is an alias for content
    public function getBodyAttribute(): ?string
    {
        return $this->content;
    }
}
