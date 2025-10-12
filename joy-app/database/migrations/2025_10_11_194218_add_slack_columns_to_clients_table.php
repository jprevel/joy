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
        Schema::table('clients', function (Blueprint $table) {
            $table->string('slack_channel_id')->nullable()->comment('Associated Slack channel ID')->after('trello_list_id');
            $table->string('slack_channel_name')->nullable()->comment('Associated Slack channel name (cached)')->after('slack_channel_id');

            $table->index('slack_channel_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex(['slack_channel_id']);
            $table->dropColumn(['slack_channel_id', 'slack_channel_name']);
        });
    }
};
