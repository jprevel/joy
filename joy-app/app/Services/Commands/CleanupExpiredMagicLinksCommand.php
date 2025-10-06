<?php

namespace App\Services\Commands;

use App\Models\MagicLink;

class CleanupExpiredMagicLinksCommand implements CleanupCommandInterface
{
    /**
     * Execute expired magic links cleanup.
     */
    public function execute(int $days): array
    {
        $deleted = MagicLink::where('expires_at', '<', now())->delete();

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
        return 'expired_magic_links';
    }
}
