<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = [
        'name',
        'description',
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
}
