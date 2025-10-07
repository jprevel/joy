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
        Schema::table('audit_logs', function (Blueprint $table) {
            // workspace_id was already renamed to client_id in previous migration
            // Rename action to event to match spec
            if (Schema::hasColumn('audit_logs', 'action')) {
                $table->renameColumn('action', 'event');
            }

            // Remove extra fields not in spec
            $table->dropColumn([
                'user_type',
                'session_id',
                'request_data',
                'response_data',
                'severity',
                'tags',
                'expires_at'
            ]);

            // Add performance indexes
            $table->index(['auditable_type', 'auditable_id'], 'idx_audit_logs_auditable');
            $table->index('created_at', 'idx_audit_logs_created_at');
            $table->index('client_id', 'idx_audit_logs_client_id');
            $table->index('user_id', 'idx_audit_logs_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex('idx_audit_logs_auditable');
            $table->dropIndex('idx_audit_logs_created_at');
            $table->dropIndex('idx_audit_logs_client_id');
            $table->dropIndex('idx_audit_logs_user_id');

            // Rename event back to action
            if (Schema::hasColumn('audit_logs', 'event')) {
                $table->renameColumn('event', 'action');
            }

            // Add back removed fields
            $table->string('user_type')->nullable()->after('user_id');
            $table->string('session_id')->nullable()->after('user_agent');
            $table->json('request_data')->nullable()->after('session_id');
            $table->json('response_data')->nullable()->after('request_data');
            $table->string('severity', 20)->default('info')->after('response_data');
            $table->json('tags')->nullable()->after('severity');
            $table->timestamp('expires_at')->nullable()->after('tags');
        });
    }
};
