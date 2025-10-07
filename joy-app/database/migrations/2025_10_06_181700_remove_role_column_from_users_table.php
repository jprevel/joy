<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, migrate existing role data to Spatie roles
        $users = DB::table('users')->whereNotNull('role')->get();

        foreach ($users as $user) {
            $roleId = DB::table('roles')->where('name', $user->role)->value('id');

            // Only insert if not already exists
            $exists = DB::table('model_has_roles')
                ->where('model_id', $user->id)
                ->where('model_type', 'App\\Models\\User')
                ->where('role_id', $roleId)
                ->exists();

            if (!$exists && $roleId) {
                DB::table('model_has_roles')->insert([
                    'role_id' => $roleId,
                    'model_type' => 'App\\Models\\User',
                    'model_id' => $user->id,
                ]);
            }
        }

        // Drop the role column
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add role column back
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->nullable();
        });

        // Migrate Spatie roles back to role column
        $userRoles = DB::table('model_has_roles')
            ->where('model_type', 'App\\Models\\User')
            ->get();

        foreach ($userRoles as $userRole) {
            $roleName = DB::table('roles')->where('id', $userRole->role_id)->value('name');
            DB::table('users')->where('id', $userRole->model_id)->update(['role' => $roleName]);
        }
    }
};
