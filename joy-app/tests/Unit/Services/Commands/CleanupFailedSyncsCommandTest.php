<?php

namespace Tests\Unit\Services\Commands;

use App\Services\Commands\CleanupFailedSyncsCommand;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CleanupFailedSyncsCommandTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_deletes_failed_trello_syncs_older_than_specified_days()
    {
        $this->markTestIncomplete('Test execute deletes TrelloCard records with sync_status=failed and updated_at older than days');
    }

    /** @test */
    public function it_does_not_delete_recent_failed_syncs()
    {
        $this->markTestIncomplete('Test execute preserves TrelloCard records with sync_status=failed within the days threshold');
    }

    /** @test */
    public function it_does_not_delete_successful_syncs()
    {
        $this->markTestIncomplete('Test execute preserves TrelloCard records with sync_status other than failed');
    }

    /** @test */
    public function it_returns_deleted_count_and_operation_name()
    {
        $this->markTestIncomplete('Test execute returns array with deleted_count and operation keys');
    }

    /** @test */
    public function it_has_correct_operation_name()
    {
        $this->markTestIncomplete('Test getName returns "failed_syncs"');
    }

    /** @test */
    public function it_implements_cleanup_command_interface()
    {
        $this->markTestIncomplete('Test CleanupFailedSyncsCommand implements CleanupCommandInterface');
    }
}
