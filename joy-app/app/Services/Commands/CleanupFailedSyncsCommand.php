<?php

namespace App\Services\Commands;

use App\Models\TrelloCard;

class CleanupFailedSyncsCommand implements CleanupCommandInterface
{
    /**
     * Execute failed syncs cleanup.
     */
    public function execute(int $days): array
    {
        $deleted = TrelloCard::where('sync_status', 'failed')
            ->where('updated_at', '<', now()->subDays($days))
            ->delete();

        return [
            'deleted_count' => $deleted,
            'operation' => $this->getName()
        ];
    }

    /**
     * Get the operation name.
     */
    public function getName(): string
    {
        return 'failed_syncs';
    }
}
