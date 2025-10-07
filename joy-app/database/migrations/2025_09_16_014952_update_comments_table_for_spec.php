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
        Schema::table('comments', function (Blueprint $table) {
            // Foreign key was already updated to content_item_id in previous migration
            // Add user_id field (nullable for client comments)
            $table->foreignId('user_id')->nullable()->after('content_item_id')->constrained()->onDelete('set null');

            // Rename body to content to match spec
            if (Schema::hasColumn('comments', 'body')) {
                $table->renameColumn('body', 'content');
            }

            // Replace author_type with is_internal boolean
            $table->dropColumn('author_type');
            $table->boolean('is_internal')->default(false)->after('author_name');

            // Add index for performance
            $table->index(['content_item_id', 'created_at'], 'idx_comments_content_item_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            // Drop index
            $table->dropIndex('idx_comments_content_item_created');

            // Remove user_id
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');

            // Rename content back to body
            if (Schema::hasColumn('comments', 'content')) {
                $table->renameColumn('content', 'body');
            }

            // Remove is_internal and restore author_type
            $table->dropColumn('is_internal');
            $table->enum('author_type', ['client', 'agency'])->after('content_item_id');
        });
    }
};
