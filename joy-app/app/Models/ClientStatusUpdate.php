<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientStatusUpdate extends Model
{
    protected $fillable = [
        'user_id',
        'client_id',
        'status_notes',
        'client_satisfaction',
        'team_health',
        'status_date',
    ];

    protected $casts = [
        'status_date' => 'datetime',
        'client_satisfaction' => 'integer',
        'team_health' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function scopeForUser($query, User $user)
    {
        return $query->whereHas('client.teams', function ($q) use ($user) {
            $q->whereIn('teams.id', $user->teams->pluck('id'));
        });
    }
}
