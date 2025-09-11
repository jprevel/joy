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
        Schema::create('concepts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('client_workspaces')->onDelete('cascade');
            $table->string('title');
            $table->text('notes')->nullable();
            $table->foreignId('owner_id')->constrained('agency_users')->onDelete('cascade');
            $table->enum('status', ['Draft', 'In Review', 'Approved', 'Scheduled'])->default('Draft');
            $table->date('due_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('concepts');
    }
};
