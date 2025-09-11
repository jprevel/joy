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
            // Add new columns for better image handling
            $table->string('image_path')->nullable()->after('media_url'); // Local storage path
            $table->string('image_filename')->nullable()->after('image_path'); // Original filename
            $table->string('image_mime_type')->nullable()->after('image_filename'); // MIME type
            $table->unsignedInteger('image_size')->nullable()->after('image_mime_type'); // File size in bytes
            
            // Keep media_url for backward compatibility during transition
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('content_items', function (Blueprint $table) {
            $table->dropColumn([
                'image_path',
                'image_filename', 
                'image_mime_type',
                'image_size'
            ]);
        });
    }
};
