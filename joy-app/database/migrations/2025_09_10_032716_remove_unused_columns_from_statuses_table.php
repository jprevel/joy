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
        Schema::table('statuses', function (Blueprint $table) {
            // Remove the columns we no longer need
            $table->dropColumn([
                'slug',
                'description', 
                'color',
                'background_color',
                'is_approved'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('statuses', function (Blueprint $table) {
            // Add the columns back if we need to rollback
            $table->string('slug')->unique()->after('name');
            $table->text('description')->nullable()->after('slug');
            $table->string('color', 7)->default('#6b7280')->after('description');
            $table->string('background_color', 7)->default('#f3f4f6')->after('color');
            $table->boolean('is_approved')->default(false)->after('is_reviewable');
        });
    }
};
