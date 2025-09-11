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
        Schema::create('variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('concept_id')->constrained()->onDelete('cascade');
            $table->enum('platform', ['facebook', 'instagram', 'linkedin', 'blog']);
            $table->text('copy')->nullable();
            $table->string('media_url')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->enum('status', ['Draft', 'In Review', 'Approved', 'Scheduled'])->default('Draft');
            $table->string('trello_card_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variants');
    }
};
