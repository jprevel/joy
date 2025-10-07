<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('content_items', function (Blueprint $table) {
            // Rename owner_id to user_id to match spec
            if (Schema::hasColumn('content_items', 'owner_id') && !Schema::hasColumn('content_items', 'user_id')) {
                $table->renameColumn('owner_id', 'user_id');
            }

            // Rename notes to description to match spec
            if (Schema::hasColumn('content_items', 'notes') && !Schema::hasColumn('content_items', 'description')) {
                $table->renameColumn('notes', 'description');
            }

            // Update platform enum to match spec values
            if (Schema::hasColumn('content_items', 'platform')) {
                // Drop and recreate platform with correct enum values
                $table->dropColumn('platform');
            }
        });

        // Add platform column with correct enum values
        Schema::table('content_items', function (Blueprint $table) {
            $table->enum('platform', ['facebook', 'instagram', 'linkedin', 'twitter', 'blog'])
                ->after('description');
        });

        Schema::table('content_items', function (Blueprint $table) {
            // Update status to match spec enum values
            if (Schema::hasColumn('content_items', 'status')) {
                $table->dropColumn('status');
            }
        });

        Schema::table('content_items', function (Blueprint $table) {
            $table->enum('status', ['draft', 'review', 'approved', 'scheduled'])
                ->default('draft')
                ->after('platform');

            // Add scheduled_at field if not exists
            if (!Schema::hasColumn('content_items', 'scheduled_at')) {
                $table->timestamp('scheduled_at')->nullable()->after('status');
            }

            // Add media_path field if not exists (rename from image_path if exists)
            if (Schema::hasColumn('content_items', 'image_path') && !Schema::hasColumn('content_items', 'media_path')) {
                $table->renameColumn('image_path', 'media_path');
            } elseif (!Schema::hasColumn('content_items', 'media_path')) {
                $table->string('media_path')->nullable()->after('scheduled_at');
            }

            // Add indexes for performance
            $table->index(['client_id', 'status'], 'idx_content_items_client_status');
            $table->index('scheduled_at', 'idx_content_items_scheduled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('content_items', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex('idx_content_items_client_status');
            $table->dropIndex('idx_content_items_scheduled_at');

            // Revert media_path to image_path
            if (Schema::hasColumn('content_items', 'media_path')) {
                $table->renameColumn('media_path', 'image_path');
            }

            // Revert user_id to owner_id
            if (Schema::hasColumn('content_items', 'user_id')) {
                $table->renameColumn('user_id', 'owner_id');
            }

            // Revert description to notes
            if (Schema::hasColumn('content_items', 'description')) {
                $table->renameColumn('description', 'notes');
            }

            // Drop scheduled_at if we added it
            $table->dropColumn('scheduled_at');
        });
    }
};
