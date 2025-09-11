<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        $permissions = [
            'view calendar',
            'edit content',
            'approve content',
            'manage clients',
            'manage users',
            'manage system',
            'view comments',
            'add comments',
            'edit comments',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        $clientRole = Role::firstOrCreate(['name' => 'client']);
        $agencyRole = Role::firstOrCreate(['name' => 'agency']);
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        // Client permissions
        $clientRole->givePermissionTo([
            'view calendar',
            'view comments',
            'add comments',
        ]);

        // Agency permissions
        $agencyRole->givePermissionTo([
            'view calendar',
            'edit content',
            'view comments',
            'add comments',
            'edit comments',
            'manage clients',
        ]);

        // Admin permissions (all permissions)
        $adminRole->givePermissionTo($permissions);

        // Create users for each role
        
        // Admin user
        $adminUser = User::firstOrCreate([
            'email' => 'admin@example.com'
        ], [
            'name' => 'Admin User',
            'password' => bcrypt('password')
        ]);
        $adminUser->assignRole('admin');

        // Client user
        $clientUser = User::firstOrCreate([
            'email' => 'client@example.com'
        ], [
            'name' => 'Client User',
            'password' => bcrypt('password')
        ]);
        $clientUser->assignRole('client');

        // Agency users
        // Shaira Hernandez (Bukonuts team)
        $shairaUser = User::firstOrCreate([
            'email' => 'shaira@majormajor.marketing'
        ], [
            'name' => 'Shaira Hernandez',
            'password' => bcrypt('password')
        ]);
        $shairaUser->assignRole('agency');

        // Ariane Salvador (Kalamansi team)
        $arianeUser = User::firstOrCreate([
            'email' => 'ariane@majormajor.marketing'
        ], [
            'name' => 'Ariane Salvador',
            'password' => bcrypt('password')
        ]);
        $arianeUser->assignRole('agency');
    }
}
