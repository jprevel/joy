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
        Schema::create('slack_workspaces', function (Blueprint $table) {
            $table->id();
            $table->string('team_id')->unique()->comment('Slack team/workspace ID');
            $table->string('team_name')->comment('Slack workspace name');
            $table->text('bot_token')->comment('Encrypted bot OAuth token');
            $table->text('access_token')->nullable()->comment('Encrypted user access token');
            $table->text('scopes')->nullable()->comment('JSON array of granted OAuth scopes');
            $table->string('bot_user_id')->nullable()->comment('Slack bot user ID');
            $table->boolean('is_active')->default(true)->comment('Is this workspace connection active');
            $table->timestamp('last_sync_at')->nullable()->comment('Last time channels were synced');
            $table->text('last_error')->nullable()->comment('Last connection error message');
            $table->json('metadata')->nullable()->comment('Additional workspace metadata');
            $table->timestamps();

            $table->index('team_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slack_workspaces');
    }
};
