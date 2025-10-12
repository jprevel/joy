<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Client extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'team_id',
        'trello_board_id',
        'trello_list_id',
        'slack_channel_id',
        'slack_channel_name',
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
        return $this->hasMany(ClientStatusfactionUpdate::class);
    }

    /**
     * Check if client has Trello integration configured.
     */
    public function hasTrelloIntegration(): bool
    {
        return !empty($this->trello_board_id) && !empty($this->trello_list_id);
    }

    /**
     * Check if client has Slack integration configured.
     */
    public function hasSlackIntegration(): bool
    {
        return !empty($this->slack_channel_id);
    }
}
