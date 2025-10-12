<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientStatusfactionUpdate extends Model
{
    use HasFactory;

    // Keep pointing to existing table name for backwards compatibility
    protected $table = 'client_status_updates';

    protected $fillable = [
        'user_id',
        'client_id',
        'status_notes',
        'client_satisfaction',
        'team_health',
        'status_date',
        'week_start_date',
        'approval_status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'status_date' => 'datetime',
        'week_start_date' => 'date',
        'approved_at' => 'datetime',
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

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeForUser($query, User $user)
    {
        return $query->whereHas('client.teams', function ($q) use ($user) {
            $q->whereIn('teams.id', $user->teams->pluck('id'));
        });
    }

    public function scopePending($query)
    {
        return $query->where('approval_status', 'pending_approval');
    }

    public function scopeApproved($query)
    {
        return $query->where('approval_status', 'approved');
    }

    public function scopeForWeek($query, $date)
    {
        return $query->where('week_start_date', $date);
    }

    public function scopeLastFiveWeeks($query, $clientId)
    {
        $weekStart = \Carbon\Carbon::now()->startOfWeek(\Carbon\Carbon::SUNDAY);
        $fiveWeeksAgo = $weekStart->copy()->subWeeks(4);

        return $query->where('client_id', $clientId)
            ->whereBetween('week_start_date', [$fiveWeeksAgo, $weekStart])
            ->orderBy('week_start_date', 'asc');
    }
}
