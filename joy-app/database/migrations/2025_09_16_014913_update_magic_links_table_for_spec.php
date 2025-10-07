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
        Schema::table('magic_links', function (Blueprint $table) {
            // Remove fields not needed per spec
            $table->dropColumn(['email', 'name', 'permissions', 'is_active', 'access_count']);

            // Rename last_accessed_at to accessed_at to match spec
            if (Schema::hasColumn('magic_links', 'last_accessed_at')) {
                $table->renameColumn('last_accessed_at', 'accessed_at');
            }

            // Add back scopes field (JSON array)
            $table->json('scopes')->after('token');

            // Add back pin field (4-digit optional)
            $table->string('pin', 4)->nullable()->after('expires_at');

            // Add indexes for performance
            $table->index('token', 'idx_magic_links_token');
            $table->index('expires_at', 'idx_magic_links_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('magic_links', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex('idx_magic_links_token');
            $table->dropIndex('idx_magic_links_expires_at');

            // Remove spec fields
            $table->dropColumn(['scopes', 'pin']);

            // Rename accessed_at back to last_accessed_at
            if (Schema::hasColumn('magic_links', 'accessed_at')) {
                $table->renameColumn('accessed_at', 'last_accessed_at');
            }

            // Add back removed fields
            $table->string('email')->after('token');
            $table->string('name')->after('email');
            $table->json('permissions')->nullable()->after('name');
            $table->integer('access_count')->default(0)->after('expires_at');
            $table->boolean('is_active')->default(true)->after('access_count');
        });
    }
};
