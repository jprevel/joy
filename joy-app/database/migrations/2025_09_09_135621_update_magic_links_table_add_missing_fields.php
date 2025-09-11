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
        Schema::table('magic_links', function (Blueprint $table) {
            $table->string('email')->after('token');
            $table->string('name')->after('email');
            $table->json('permissions')->nullable()->after('name');
            $table->timestamp('expires_at')->after('token');
            $table->timestamp('last_accessed_at')->nullable()->after('expires_at');
            $table->integer('access_count')->default(0)->after('last_accessed_at');
            $table->boolean('is_active')->default(true)->after('access_count');
            
            $table->dropColumn(['scopes', 'expiry', 'pin']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('magic_links', function (Blueprint $table) {
            $table->dropColumn([
                'email', 'name', 'permissions', 'expires_at', 
                'last_accessed_at', 'access_count', 'is_active'
            ]);
            
            $table->json('scopes')->after('token');
            $table->timestamp('expiry')->nullable()->after('scopes');
            $table->string('pin', 4)->nullable()->after('expiry');
        });
    }
};
