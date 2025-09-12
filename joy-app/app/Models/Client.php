<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Client extends Model
{
    protected $fillable = [
        'name',
        'description',
        'team_id',
    ];

    public function contentItems(): HasMany
    {
        return $this->hasMany(ContentItem::class, 'client_id');
    }

    public function magicLinks(): HasMany
    {
        return $this->hasMany(MagicLink::class, 'client_id');
    }

    public function trelloIntegrations(): HasMany
    {
        return $this->hasMany(TrelloIntegration::class, 'client_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'client_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function statusUpdates(): HasMany
    {
        return $this->hasMany(ClientStatusUpdate::class);
    }
}
