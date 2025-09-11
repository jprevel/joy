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
        // Drop the old constraint with the wrong name
        DB::statement('ALTER TABLE content_items DROP CONSTRAINT IF EXISTS variants_platform_check');
        
        // Update any invalid platform values to valid ones
        DB::statement("UPDATE content_items SET platform = 'Facebook' WHERE platform NOT IN ('Facebook', 'Instagram', 'LinkedIn', 'Twitter', 'Blog') OR platform IS NULL");
        
        // Add the correct constraint for content_items
        DB::statement("ALTER TABLE content_items ADD CONSTRAINT content_items_platform_check CHECK (platform IN ('Facebook', 'Instagram', 'LinkedIn', 'Twitter', 'Blog'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the new constraint
        DB::statement('ALTER TABLE content_items DROP CONSTRAINT IF EXISTS content_items_platform_check');
        
        // Restore the old constraint (if needed)
        DB::statement("ALTER TABLE content_items ADD CONSTRAINT variants_platform_check CHECK (platform IN ('Facebook', 'Instagram', 'LinkedIn', 'Twitter', 'Blog'))");
    }
};
