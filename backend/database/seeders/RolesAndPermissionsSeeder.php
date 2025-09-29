<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles if they don't exist
        $roles = ['system-admin', 'team', 'consultant', 'client'];

        foreach ($roles as $roleName) {
            \Spatie\Permission\Models\Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web'
            ]);
        }

        // Clean up old 'admin' role if it exists to avoid confusion
        $oldAdminRole = \Spatie\Permission\Models\Role::where('name', 'admin')->first();
        if ($oldAdminRole) {
            // Transfer any users with 'admin' role to 'system-admin'
            $adminUsers = $oldAdminRole->users;
            foreach ($adminUsers as $user) {
                $user->assignRole('system-admin');
                $user->removeRole('admin');
            }
            $oldAdminRole->delete();
            $this->command->info('Migrated admin users to system-admin role');
        }

        $this->command->info('Roles created successfully!');
    }
}
