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
        // Check if workspace_id column exists and client_id doesn't
        if (Schema::hasColumn('trello_integrations', 'workspace_id') && !Schema::hasColumn('trello_integrations', 'client_id')) {
            Schema::table('trello_integrations', function (Blueprint $table) {
                // Drop the foreign key constraint first
                $table->dropForeign(['workspace_id']);
                
                // Rename the column
                $table->renameColumn('workspace_id', 'client_id');
            });
            
            // Add the new foreign key constraint in a separate operation
            Schema::table('trello_integrations', function (Blueprint $table) {
                $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
                
                // Update the unique constraint
                $table->dropUnique(['workspace_id']);
                $table->unique(['client_id']);
            });
        } else {
            // Table is already updated, just clean up old constraints
            Schema::table('trello_integrations', function (Blueprint $table) {
                // Drop old unique constraint if it exists (using raw SQL to avoid errors)
                \DB::statement('ALTER TABLE trello_integrations DROP CONSTRAINT IF EXISTS trello_integrations_workspace_id_unique');
                
                // Ensure client_id unique constraint exists (only if it doesn't already exist)
                $hasClientUnique = \DB::select("
                    SELECT 1 
                    FROM information_schema.table_constraints tc
                    JOIN information_schema.key_column_usage kcu ON tc.constraint_name = kcu.constraint_name
                    WHERE tc.table_name = 'trello_integrations' 
                    AND tc.constraint_type = 'UNIQUE' 
                    AND kcu.column_name = 'client_id'
                ");
                
                if (empty($hasClientUnique)) {
                    $table->unique(['client_id']);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trello_integrations', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['client_id']);
            $table->dropUnique(['client_id']);
            
            // Rename the column back
            $table->renameColumn('client_id', 'workspace_id');
        });
        
        Schema::table('trello_integrations', function (Blueprint $table) {
            // Add back the old foreign key constraint
            $table->foreign('workspace_id')->references('id')->on('client_workspaces')->onDelete('cascade');
            $table->unique(['workspace_id']);
        });
    }
};