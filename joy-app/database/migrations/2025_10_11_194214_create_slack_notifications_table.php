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
        Schema::create('slack_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('slack_workspaces')->onDelete('cascade');
            $table->enum('type', [
                'client_comment',
                'content_approved',
                'statusfaction_submitted',
                'statusfaction_approved'
            ])->comment('Type of notification');
            $table->string('notifiable_type')->comment('Polymorphic type');
            $table->unsignedBigInteger('notifiable_id')->comment('Polymorphic ID');
            $table->string('channel_id')->comment('Slack channel ID');
            $table->string('channel_name')->nullable()->comment('Slack channel name (cached)');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->json('payload')->nullable()->comment('Slack message payload');
            $table->json('response')->nullable()->comment('Slack API response');
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['notifiable_type', 'notifiable_id']);
            $table->index('status');
            $table->index('type');
            $table->index('channel_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slack_notifications');
    }
};
