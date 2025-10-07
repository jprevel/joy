<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trello_cards', function (Blueprint $table) {
            // content_item_id was already updated from variant_id in previous migration
            // Make content_item_id nullable to support comments relationship
            $table->foreignId('content_item_id')->nullable()->change();

            // Add comment_id field (nullable - either content_item or comment)
            $table->foreignId('comment_id')->nullable()->after('content_item_id')->constrained()->onDelete('cascade');

            // Rename trello_id to trello_card_id to match spec
            if (Schema::hasColumn('trello_cards', 'trello_id')) {
                $table->renameColumn('trello_id', 'trello_card_id');
            }

            // Add Trello board and list IDs
            $table->string('trello_board_id')->after('trello_card_id');
            $table->string('trello_list_id')->after('trello_board_id');

            // Add sync status and timestamp
            $table->enum('sync_status', ['pending', 'synced', 'failed'])->default('pending')->after('trello_list_id');
            $table->timestamp('last_synced_at')->nullable()->after('sync_status');

            // Remove url field as it's not in spec
            $table->dropColumn('url');

            // Add unique constraint for trello_card_id
            $table->unique('trello_card_id', 'idx_trello_cards_trello_card_id');
        });

        // Add check constraint to ensure either content_item_id or comment_id is present
        // Note: PostgreSQL supports check constraints, MySQL might need alternative
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE trello_cards ADD CONSTRAINT chk_trello_cards_content_or_comment CHECK (content_item_id IS NOT NULL OR comment_id IS NOT NULL)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trello_cards', function (Blueprint $table) {
            // Drop constraint
            if (Schema::getConnection()->getDriverName() === 'pgsql') {
                $table->dropCheckConstraint('chk_trello_cards_content_or_comment');
            }

            // Drop unique constraint
            $table->dropUnique('idx_trello_cards_trello_card_id');

            // Remove spec fields
            $table->dropForeign(['comment_id']);
            $table->dropColumn(['comment_id', 'trello_board_id', 'trello_list_id', 'sync_status', 'last_synced_at']);

            // Rename trello_card_id back to trello_id
            if (Schema::hasColumn('trello_cards', 'trello_card_id')) {
                $table->renameColumn('trello_card_id', 'trello_id');
            }

            // Make content_item_id not nullable again
            $table->foreignId('content_item_id')->nullable(false)->change();

            // Add back url field
            $table->string('url')->after('trello_id');
        });
    }
};
