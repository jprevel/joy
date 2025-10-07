<?php

namespace Tests\Unit\Services\Commands;

use App\Services\Commands\CleanupExpiredMagicLinksCommand;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CleanupExpiredMagicLinksCommandTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_deletes_expired_magic_links()
    {
        $this->markTestIncomplete('Test execute deletes MagicLink records where expires_at is before now');
    }

    /** @test */
    public function it_does_not_delete_active_magic_links()
    {
        $this->markTestIncomplete('Test execute preserves MagicLink records where expires_at is in the future');
    }

    /** @test */
    public function it_returns_deleted_count_and_operation_name()
    {
        $this->markTestIncomplete('Test execute returns array with deleted_count and operation keys');
    }

    /** @test */
    public function it_has_correct_operation_name()
    {
        $this->markTestIncomplete('Test getName returns "expired_magic_links"');
    }

    /** @test */
    public function it_implements_cleanup_command_interface()
    {
        $this->markTestIncomplete('Test CleanupExpiredMagicLinksCommand implements CleanupCommandInterface');
    }
}
