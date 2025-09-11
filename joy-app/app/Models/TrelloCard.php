<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrelloCard extends Model
{
    protected $fillable = [
        'content_item_id',
        'trello_id',
        'url',
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
}
