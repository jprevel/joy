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
        Schema::create('statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., 'In Review', 'Approved', etc.
            $table->string('slug')->unique(); // e.g., 'in_review', 'approved', etc.
            $table->text('description')->nullable(); // Description of what this status means
            $table->string('color', 7)->default('#6b7280'); // Hex color for UI display
            $table->string('background_color', 7)->default('#f3f4f6'); // Background color
            $table->integer('sort_order')->default(0); // For ordering statuses
            $table->boolean('is_reviewable')->default(false); // Can be reviewed by client
            $table->boolean('is_approved')->default(false); // Indicates approval state
            $table->boolean('is_active')->default(true); // Status is active/available
            $table->timestamps();
            
            // Index for common queries
            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statuses');
    }
};
