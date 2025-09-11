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
        // Rename the variants table to content_items
        Schema::rename('variants', 'content_items');
        
        // Update foreign key references in other tables
        
        // Update comments table
        if (Schema::hasColumn('comments', 'variant_id')) {
            Schema::table('comments', function (Blueprint $table) {
                $table->dropForeign(['variant_id']);
                $table->renameColumn('variant_id', 'content_item_id');
                $table->foreign('content_item_id')->references('id')->on('content_items')->onDelete('cascade');
            });
        }
        
        // Update trello_cards table
        if (Schema::hasColumn('trello_cards', 'variant_id')) {
            Schema::table('trello_cards', function (Blueprint $table) {
                $table->dropForeign(['variant_id']);
                $table->renameColumn('variant_id', 'content_item_id');
                $table->foreign('content_item_id')->references('id')->on('content_items')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert trello_cards
        if (Schema::hasColumn('trello_cards', 'content_item_id')) {
            Schema::table('trello_cards', function (Blueprint $table) {
                $table->dropForeign(['content_item_id']);
                $table->renameColumn('content_item_id', 'variant_id');
                $table->foreign('variant_id')->references('id')->on('variants')->onDelete('cascade');
            });
        }
        
        // Revert comments
        if (Schema::hasColumn('comments', 'content_item_id')) {
            Schema::table('comments', function (Blueprint $table) {
                $table->dropForeign(['content_item_id']);
                $table->renameColumn('content_item_id', 'variant_id');
                $table->foreign('variant_id')->references('id')->on('variants')->onDelete('cascade');
            });
        }
        
        // Rename table back
        Schema::rename('content_items', 'variants');
    }
};