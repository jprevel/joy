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
        Schema::create('trello_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('client_workspaces')->onDelete('cascade');
            $table->string('api_key');
            $table->string('api_token');
            $table->string('board_id')->nullable();
            $table->string('list_id')->nullable();
            $table->json('webhook_config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sync_at')->nullable();
            $table->json('sync_status')->nullable();
            $table->timestamps();
            
            $table->unique(['workspace_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trello_integrations');
    }
};
