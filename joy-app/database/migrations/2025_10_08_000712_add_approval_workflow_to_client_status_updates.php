<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('client_status_updates', function (Blueprint $table) {
            $table->date('week_start_date')->after('status_date');
            $table->enum('approval_status', ['needs_status', 'pending_approval', 'approved'])
                  ->default('pending_approval')
                  ->after('week_start_date');
            $table->foreignId('approved_by')->nullable()->after('approval_status')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');

            // Indexes
            $table->unique(['client_id', 'week_start_date'], 'client_week_unique');
            $table->index('approval_status');
            $table->index('week_start_date');
        });

        // Backfill existing records with calculated week_start_date and mark as approved
        // PostgreSQL compatible: Calculate Sunday of the week for each status_date
        DB::statement("
            UPDATE client_status_updates
            SET week_start_date = (status_date::date - EXTRACT(DOW FROM status_date)::int * INTERVAL '1 day')::date,
                approval_status = 'approved'
            WHERE week_start_date IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_status_updates', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropUnique('client_week_unique');
            $table->dropIndex(['approval_status']);
            $table->dropIndex(['week_start_date']);
            $table->dropColumn(['week_start_date', 'approval_status', 'approved_by', 'approved_at']);
        });
    }
};
