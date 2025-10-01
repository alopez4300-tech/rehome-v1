<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles if they don't exist
        $roles = ['admin', 'team', 'consultant', 'client'];

        foreach ($roles as $roleName) {
            \Spatie\Permission\Models\Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
        }

        // Clean up old 'system-admin' role if it exists to avoid confusion
        $oldSystemAdminRole = \Spatie\Permission\Models\Role::where('name', 'system-admin')->first();
        if ($oldSystemAdminRole) {
            // Transfer any users with 'system-admin' role to 'admin'
            $adminUsers = $oldSystemAdminRole->users;
            foreach ($adminUsers as $user) {
                $user->assignRole('admin');
                $user->removeRole('system-admin');
            }
            $oldSystemAdminRole->delete();
            $this->command->info('Migrated system-admin users to admin role');
        }

        $this->command->info('Roles created successfully!');
    }
}
