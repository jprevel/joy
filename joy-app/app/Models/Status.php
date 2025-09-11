<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Status extends Model
{
    protected $fillable = [
        'name',
        'sort_order',
        'is_reviewable',
        'is_active',
    ];

    protected $casts = [
        'is_reviewable' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function contentItems(): HasMany
    {
        return $this->hasMany(ContentItem::class);
    }

    // Scope for active statuses
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope for reviewable statuses
    public function scopeReviewable($query)
    {
        return $query->where('is_reviewable', true);
    }


    // Get ordered statuses
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
