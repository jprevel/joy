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
        Schema::create('client_status_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->text('status_notes');
            $table->integer('client_satisfaction')->unsigned()->between(1, 10);
            $table->integer('team_health')->unsigned()->between(1, 10);
            $table->timestamp('status_date');
            $table->timestamps();
            
            $table->index(['user_id', 'client_id']);
            $table->index('status_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_status_updates');
    }
};
