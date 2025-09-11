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
            // Drop the old foreign key constraint
            $table->dropForeign(['owner_id']);
            
            // Add new foreign key constraint pointing to users table
            $table->foreign('owner_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('content_items', function (Blueprint $table) {
            // Drop the new foreign key constraint
            $table->dropForeign(['owner_id']);
            
            // Add back the old foreign key constraint pointing to agency_users table
            $table->foreign('owner_id')->references('id')->on('agency_users')->onDelete('set null');
        });
    }
};
