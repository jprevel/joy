<?php

namespace App\Services\Commands;

interface CleanupCommandInterface
{
    /**
     * Execute the cleanup operation.
     */
    public function execute(int $days): array;

    /**
     * Get the operation name.
     */
    public function getName(): string;
}
