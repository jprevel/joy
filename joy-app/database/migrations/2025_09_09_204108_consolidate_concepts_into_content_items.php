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
        // Add concept fields to content_items table
        Schema::table('content_items', function (Blueprint $table) {
            // Add new columns from concepts table
            $table->foreignId('client_id')->after('id')->constrained()->onDelete('cascade');
            $table->string('title')->after('client_id');
            $table->text('notes')->nullable()->after('title');
            $table->foreignId('owner_id')->after('notes')->constrained('agency_users')->onDelete('cascade');
        });
        
        // Migrate data from concepts to content_items
        // This will populate the new fields in content_items with data from their related concepts
        DB::statement("
            UPDATE content_items 
            SET 
                client_id = concepts.client_id,
                title = concepts.title || ' - ' || INITCAP(content_items.platform),
                notes = concepts.notes,
                owner_id = concepts.owner_id
            FROM concepts 
            WHERE content_items.concept_id = concepts.id
        ");
        
        // Remove concept_id since we're flattening the relationship
        Schema::table('content_items', function (Blueprint $table) {
            $table->dropForeign('variants_concept_id_foreign'); // Use actual constraint name
            $table->dropColumn('concept_id');
        });
        
        // Drop the concepts table since it's no longer needed
        Schema::dropIfExists('concepts');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate concepts table
        Schema::create('concepts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('notes')->nullable();
            $table->foreignId('owner_id')->constrained('agency_users')->onDelete('cascade');
            $table->enum('status', ['Draft', 'In Review', 'Approved', 'Scheduled'])->default('Draft');
            $table->timestamps();
        });
        
        // Recreate the data in concepts (this is lossy since we combined title with platform)
        DB::statement("
            INSERT INTO concepts (client_id, title, notes, owner_id, status, created_at, updated_at)
            SELECT DISTINCT 
                client_id, 
                REGEXP_REPLACE(title, ' - (Facebook|Instagram|Linkedin|Blog)$', '', 'gi') as title,
                notes,
                owner_id,
                status,
                created_at,
                updated_at
            FROM content_items
        ");
        
        // Add concept_id back to content_items
        Schema::table('content_items', function (Blueprint $table) {
            $table->foreignId('concept_id')->after('id')->constrained()->onDelete('cascade');
        });
        
        // Update content_items with concept_id references (this is approximate)
        DB::statement("
            UPDATE content_items 
            SET concept_id = concepts.id 
            FROM concepts 
            WHERE content_items.client_id = concepts.client_id 
            AND content_items.owner_id = concepts.owner_id
        ");
        
        // Remove the consolidated columns
        Schema::table('content_items', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropForeign(['owner_id']);
            $table->dropColumn(['client_id', 'title', 'notes', 'owner_id']);
        });
    }
};
