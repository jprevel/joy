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
        Schema::table('agency_users', function (Blueprint $table) {
            // Remove the workspace_id foreign key constraint and column
            // Agency users are global, not tied to specific clients
            $table->dropForeign(['workspace_id']);
            $table->dropColumn('workspace_id');
            
            // Update the role enum to be more flexible
            $table->dropColumn('role');
            $table->string('role')->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agency_users', function (Blueprint $table) {
            // Add workspace_id back
            $table->foreignId('workspace_id')->after('id')->constrained('clients')->onDelete('cascade');
            
            // Revert role to enum
            $table->dropColumn('role');
            $table->enum('role', ['Admin', 'Agency Team'])->after('workspace_id');
        });
    }
};