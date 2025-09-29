<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class VerifyPolicies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:verify-policies';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify that authorization policies are working correctly';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== POLICY VERIFICATION TEST ===');

        // Clean up any existing test data first
        $this->cleanupTestData();

        $this->info('1. Creating test data...');

        // Create test workspaces
        $workspace1 = \App\Models\Workspace::create([
            'name' => 'Test Workspace 1',
            'slug' => 'test-workspace-1',
            'description' => 'Test workspace for policies'
        ]);

        $workspace2 = \App\Models\Workspace::create([
            'name' => 'Test Workspace 2',
            'slug' => 'test-workspace-2',
            'description' => 'Another test workspace'
        ]);

        // Create test users
        $admin1 = \App\Models\User::create([
            'name' => 'Admin One',
            'email' => 'admin1@test.com',
            'password' => bcrypt('password'),
            'workspace_id' => $workspace1->id
        ]);

        $admin2 = \App\Models\User::create([
            'name' => 'Admin Two',
            'email' => 'admin2@test.com',
            'password' => bcrypt('password'),
            'workspace_id' => $workspace2->id
        ]);

        $teamMember = \App\Models\User::create([
            'name' => 'Team Member',
            'email' => 'team@test.com',
            'password' => bcrypt('password'),
            'workspace_id' => $workspace1->id
        ]);

        // Assign roles
        $admin1->assignRole('system-admin');
        $admin2->assignRole('system-admin');
        $teamMember->assignRole('team');

        // Create test project
        $project1 = \App\Models\Project::create([
            'name' => 'Test Project 1',
            'description' => 'Test project in workspace 1',
            'workspace_id' => $workspace1->id
        ]);

        $this->info('✓ Test data created');

        // Test Workspace Policies
        $this->info('');
        $this->info('2. Testing Workspace Policies...');

        $canView = $admin1->can('view', $workspace1);
        $this->info('Admin1 can view own workspace: ' . ($canView ? '✓' : '✗'));

        $cannotView = $admin1->can('view', $workspace2);
        $this->info('Admin1 CANNOT view other workspace: ' . ($cannotView ? '✗' : '✓'));

        $teamCannotView = $teamMember->can('view', $workspace1);
        $this->info('Team member CANNOT view workspace: ' . ($teamCannotView ? '✗' : '✓'));

        // Test User Policies
        $this->info('');
        $this->info('3. Testing User Policies...');

        $canViewUser = $admin1->can('view', $teamMember);
        $this->info('Admin1 can view team member in same workspace: ' . ($canViewUser ? '✓' : '✗'));

        $cannotViewOther = $admin1->can('view', $admin2);
        $this->info('Admin1 CANNOT view admin in other workspace: ' . ($cannotViewOther ? '✗' : '✓'));

        $canViewSelf = $teamMember->can('view', $teamMember);
        $this->info('Team member can view themselves: ' . ($canViewSelf ? '✓' : '✗'));

        // Test Project Policies
        $this->info('');
        $this->info('4. Testing Project Policies...');

        $canViewProject = $admin1->can('view', $project1);
        $this->info('Admin1 can view project in own workspace: ' . ($canViewProject ? '✓' : '✗'));

        $cannotViewProject = $admin2->can('view', $project1);
        $this->info('Admin2 CANNOT view project in other workspace: ' . ($cannotViewProject ? '✗' : '✓'));

        $this->info('');
        $this->info('=== POLICY VERIFICATION COMPLETE ===');

        // Clean up test data
        $this->cleanupTestData();
        $this->info('✓ Test data cleaned up');
    }

    private function cleanupTestData()
    {
        \App\Models\User::whereIn('email', ['admin1@test.com', 'admin2@test.com', 'team@test.com'])->delete();
        \App\Models\Workspace::whereIn('slug', ['test-workspace-1', 'test-workspace-2'])->delete();
    }
}
