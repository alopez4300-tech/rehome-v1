<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class WorkspaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        $permissions = [
            'manage workspaces',
            'manage users',
            'manage projects',
            'manage tasks',
            'manage files',
            'view reports',
            'manage billing',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions($permissions);

        $teamRole = Role::firstOrCreate(['name' => 'team']);
        $teamRole->syncPermissions(['manage projects', 'manage tasks', 'manage files', 'view reports']);

        $consultantRole = Role::firstOrCreate(['name' => 'consultant']);
        $consultantRole->syncPermissions(['view reports', 'manage files']);

        $clientRole = Role::firstOrCreate(['name' => 'client']);
        $clientRole->syncPermissions(['view reports']);

        // Create demo workspace
        $workspace = Workspace::firstOrCreate(
            ['slug' => 'demo-workspace'],
            [
                'name' => 'Demo Workspace',
                'description' => 'A demonstration workspace for testing the application',
                'is_active' => true,
                'settings' => [
                    'timezone' => 'UTC',
                    'date_format' => 'Y-m-d',
                    'time_format' => 'H:i',
                ],
            ]
        );

        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'workspace_id' => $workspace->id,
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('admin');

        // Create team member
        $teamMember = User::firstOrCreate(
            ['email' => 'team@example.com'],
            [
                'name' => 'Team Member',
                'password' => Hash::make('password'),
                'workspace_id' => $workspace->id,
                'role' => 'team_member',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $teamMember->assignRole('team');

        // Create consultant
        $consultant = User::firstOrCreate(
            ['email' => 'consultant@example.com'],
            [
                'name' => 'External Consultant',
                'password' => Hash::make('password'),
                'workspace_id' => $workspace->id,
                'role' => 'consultant',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $consultant->assignRole('consultant');

        // Create client
        $client = User::firstOrCreate(
            ['email' => 'client@example.com'],
            [
                'name' => 'Client User',
                'password' => Hash::make('password'),
                'workspace_id' => $workspace->id,
                'role' => 'client',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $client->assignRole('client');

        // Create demo projects
        $project1 = Project::firstOrCreate(
            ['slug' => 'website-redesign', 'workspace_id' => $workspace->id],
            [
                'name' => 'Website Redesign',
                'description' => 'Complete redesign of the company website with modern UX/UI',
                'status' => 'active',
                'priority' => 'high',
                'budget' => 25000.00,
                'start_date' => now()->subDays(30),
                'end_date' => now()->addDays(60),
                'deadline' => now()->addDays(45),
                'metadata' => [
                    'client_name' => 'ABC Corp',
                    'project_type' => 'web_development',
                    'tags' => ['design', 'development', 'responsive'],
                ],
            ]
        );

        $project2 = Project::firstOrCreate(
            ['slug' => 'mobile-app', 'workspace_id' => $workspace->id],
            [
                'name' => 'Mobile App Development',
                'description' => 'Native mobile app for iOS and Android platforms',
                'status' => 'active',
                'priority' => 'medium',
                'budget' => 45000.00,
                'start_date' => now()->subDays(15),
                'end_date' => now()->addDays(90),
                'deadline' => now()->addDays(75),
                'metadata' => [
                    'client_name' => 'XYZ Startup',
                    'project_type' => 'mobile_development',
                    'tags' => ['ios', 'android', 'react-native'],
                ],
            ]
        );

        // Assign users to projects with different roles
        $project1->users()->syncWithoutDetaching([
            $admin->id => [
                'role' => 'owner',
                'can_manage_tasks' => true,
                'can_manage_files' => true,
                'can_manage_users' => true,
                'can_view_budget' => true,
                'joined_at' => now()->subDays(30),
            ],
            $teamMember->id => [
                'role' => 'member',
                'can_manage_tasks' => true,
                'can_manage_files' => true,
                'can_manage_users' => false,
                'can_view_budget' => false,
                'hourly_rate' => 75.00,
                'joined_at' => now()->subDays(25),
            ],
            $consultant->id => [
                'role' => 'consultant',
                'can_manage_tasks' => false,
                'can_manage_files' => true,
                'can_manage_users' => false,
                'can_view_budget' => false,
                'hourly_rate' => 125.00,
                'joined_at' => now()->subDays(20),
            ],
            $client->id => [
                'role' => 'client',
                'can_manage_tasks' => false,
                'can_manage_files' => false,
                'can_manage_users' => false,
                'can_view_budget' => true,
                'joined_at' => now()->subDays(30),
            ],
        ]);

        $project2->users()->syncWithoutDetaching([
            $admin->id => [
                'role' => 'owner',
                'can_manage_tasks' => true,
                'can_manage_files' => true,
                'can_manage_users' => true,
                'can_view_budget' => true,
                'joined_at' => now()->subDays(15),
            ],
            $teamMember->id => [
                'role' => 'manager',
                'can_manage_tasks' => true,
                'can_manage_files' => true,
                'can_manage_users' => true,
                'can_view_budget' => true,
                'hourly_rate' => 85.00,
                'joined_at' => now()->subDays(15),
            ],
        ]);

        $this->command->info('Workspace seeder completed successfully!');
        $this->command->info('Demo workspace created with:');
        $this->command->info('- Admin: admin@example.com (password: password)');
        $this->command->info('- Team Member: team@example.com (password: password)');
        $this->command->info('- Consultant: consultant@example.com (password: password)');
        $this->command->info('- Client: client@example.com (password: password)');
    }
}
