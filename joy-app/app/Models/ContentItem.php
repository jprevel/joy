<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Helpers\PlatformHelper;

class ContentItem extends Model
{
    protected $fillable = [
        'client_id',
        'title',
        'notes',
        'owner_id',
        'platform',
        'copy',
        'media_url', // Keep for backward compatibility during transition
        'image_path',
        'image_filename',
        'image_mime_type',
        'image_size',
        'scheduled_at',
        'status', // Keep for backward compatibility during transition
        'status_id',
        'trello_card_id',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'content_item_id');
    }

    public function trelloCard(): HasOne
    {
        return $this->hasOne(TrelloCard::class, 'content_item_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function statusModel(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    // Helper methods for platform styling
    public function getPlatformColorAttribute(): string
    {
        return PlatformHelper::getBackgroundColor($this->platform);
    }

    public function getPlatformIconAttribute(): string
    {
        return PlatformHelper::getIcon($this->platform);
    }

    // Status helper methods
    public function getStatusNameAttribute(): string
    {
        // Use relationship if available, fallback to old status column
        return $this->statusModel?->name ?? $this->attributes['status'] ?? 'Unknown';
    }

    public function getIsReviewableAttribute(): bool
    {
        return $this->statusModel?->is_reviewable ?? false;
    }

    // Image helper methods
    public function getImageUrlAttribute(): ?string
    {
        // Use local image if available, fallback to media_url for backward compatibility
        if ($this->image_path) {
            return asset('storage/' . $this->image_path);
        }
        
        return $this->media_url;
    }

    public function hasImage(): bool
    {
        return !empty($this->image_path) || !empty($this->media_url);
    }

    public function getImageSizeFormattedAttribute(): string
    {
        if (!$this->image_size) {
            return 'Unknown';
        }

        $bytes = $this->image_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function storeImage($file, $directory = 'content-images'): bool
    {
        if (!$file) {
            return false;
        }

        // Delete old image if exists
        $this->deleteImage();

        // Store new image
        $path = $file->store($directory, 'public');
        
        if ($path) {
            $this->update([
                'image_path' => $path,
                'image_filename' => $file->getClientOriginalName(),
                'image_mime_type' => $file->getMimeType(),
                'image_size' => $file->getSize(),
            ]);
            
            return true;
        }
        
        return false;
    }

    public function deleteImage(): bool
    {
        if ($this->image_path && \Storage::disk('public')->exists($this->image_path)) {
            \Storage::disk('public')->delete($this->image_path);
            
            $this->update([
                'image_path' => null,
                'image_filename' => null,
                'image_mime_type' => null,
                'image_size' => null,
            ]);
            
            return true;
        }
        
        return false;
    }
}
