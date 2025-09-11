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
        // Rename the table and update structure
        Schema::rename('client_workspaces', 'clients');
        
        // Update the clients table structure
        Schema::table('clients', function (Blueprint $table) {
            // Remove workspace-specific columns if any exist
            // Keep: id, name, description (if description doesn't exist, we'll add it)
            // The existing name column should work fine
            
            // Add description if it doesn't exist
            if (!Schema::hasColumn('clients', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
        });
        
        // Update all foreign key columns from workspace_id to client_id
        
        // Update concepts table
        if (Schema::hasColumn('concepts', 'workspace_id')) {
            Schema::table('concepts', function (Blueprint $table) {
                $table->dropForeign(['workspace_id']);
                $table->renameColumn('workspace_id', 'client_id');
                $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            });
        }
        
        // Update audit_logs table  
        if (Schema::hasColumn('audit_logs', 'workspace_id')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->renameColumn('workspace_id', 'client_id');
            });
        }
        
        // Update magic_links table
        if (Schema::hasColumn('magic_links', 'workspace_id')) {
            Schema::table('magic_links', function (Blueprint $table) {
                $table->dropForeign(['workspace_id']);
                $table->renameColumn('workspace_id', 'client_id');
                $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            });
        }
        
        // Update trello_integrations table
        if (Schema::hasColumn('trello_integrations', 'workspace_id')) {
            Schema::table('trello_integrations', function (Blueprint $table) {
                $table->dropForeign(['workspace_id']);  
                $table->renameColumn('workspace_id', 'client_id');
                $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse all the changes
        
        // Revert trello_integrations
        if (Schema::hasColumn('trello_integrations', 'client_id')) {
            Schema::table('trello_integrations', function (Blueprint $table) {
                $table->dropForeign(['client_id']);
                $table->renameColumn('client_id', 'workspace_id');
                $table->foreign('workspace_id')->references('id')->on('client_workspaces')->onDelete('cascade');
            });
        }
        
        // Revert magic_links
        if (Schema::hasColumn('magic_links', 'client_id')) {
            Schema::table('magic_links', function (Blueprint $table) {
                $table->dropForeign(['client_id']);
                $table->renameColumn('client_id', 'workspace_id');
                $table->foreign('workspace_id')->references('id')->on('client_workspaces')->onDelete('cascade');
            });
        }
        
        // Revert audit_logs
        if (Schema::hasColumn('audit_logs', 'client_id')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->renameColumn('client_id', 'workspace_id');
            });
        }
        
        // Revert concepts
        if (Schema::hasColumn('concepts', 'client_id')) {
            Schema::table('concepts', function (Blueprint $table) {
                $table->dropForeign(['client_id']);
                $table->renameColumn('client_id', 'workspace_id');
                $table->foreign('workspace_id')->references('id')->on('client_workspaces')->onDelete('cascade');
            });
        }
        
        // Rename table back
        Schema::rename('clients', 'client_workspaces');
        
        // Remove description column if we added it
        Schema::table('client_workspaces', function (Blueprint $table) {
            if (Schema::hasColumn('client_workspaces', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};