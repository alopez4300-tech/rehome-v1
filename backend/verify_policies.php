<?php

// Quick policy verification script
// Run with: php artisan tinker < verify_policies.php

use App\Models\User;
use App\Models\Workspace;
use App\Models\Project;

echo "=== POLICY VERIFICATION TEST ===\n";

// Test 1: Create test data
echo "\n1. Creating test data...\n";

$workspace1 = Workspace::create([
    'name' => 'Test Workspace 1',
    'slug' => 'test-workspace-1',
    'description' => 'Test workspace for policies'
]);

$workspace2 = Workspace::create([
    'name' => 'Test Workspace 2', 
    'slug' => 'test-workspace-2',
    'description' => 'Another test workspace'
]);

$admin1 = User::create([
    'name' => 'Admin One',
    'email' => 'admin1@test.com',
    'password' => bcrypt('password'),
    'workspace_id' => $workspace1->id
]);

$admin2 = User::create([
    'name' => 'Admin Two',
    'email' => 'admin2@test.com', 
    'password' => bcrypt('password'),
    'workspace_id' => $workspace2->id
]);

$teamMember = User::create([
    'name' => 'Team Member',
    'email' => 'team@test.com',
    'password' => bcrypt('password'),
    'workspace_id' => $workspace1->id
]);

// Assign roles
$admin1->assignRole('admin');
$admin2->assignRole('admin');
$teamMember->assignRole('team');

$project1 = Project::create([
    'name' => 'Test Project 1',
    'description' => 'Test project in workspace 1',
    'workspace_id' => $workspace1->id
]);

echo "✓ Test data created\n";

// Test 2: Workspace Policy Tests
echo "\n2. Testing Workspace Policies...\n";

// Admin should be able to view their own workspace
$canView = $admin1->can('view', $workspace1);
echo "Admin1 can view own workspace: " . ($canView ? "✓" : "✗") . "\n";

// Admin should NOT be able to view other workspace
$cannotView = $admin1->can('view', $workspace2);
echo "Admin1 CANNOT view other workspace: " . ($cannotView ? "✗" : "✓") . "\n";

// Team member should NOT be able to view workspace
$teamCannotView = $teamMember->can('view', $workspace1);
echo "Team member CANNOT view workspace: " . ($teamCannotView ? "✗" : "✓") . "\n";

// Test 3: User Policy Tests
echo "\n3. Testing User Policies...\n";

// Admin should be able to view users in their workspace
$canViewUser = $admin1->can('view', $teamMember);
echo "Admin1 can view team member in same workspace: " . ($canViewUser ? "✓" : "✗") . "\n";

// Admin should NOT be able to view users in other workspaces
$cannotViewOther = $admin1->can('view', $admin2);
echo "Admin1 CANNOT view admin in other workspace: " . ($cannotViewOther ? "✗" : "✓") . "\n";

// User should be able to view themselves
$canViewSelf = $teamMember->can('view', $teamMember);
echo "Team member can view themselves: " . ($canViewSelf ? "✓" : "✗") . "\n";

// Test 4: Project Policy Tests
echo "\n4. Testing Project Policies...\n";

// Admin should be able to view projects in their workspace
$canViewProject = $admin1->can('view', $project1);
echo "Admin1 can view project in own workspace: " . ($canViewProject ? "✓" : "✗") . "\n";

// Admin from other workspace should NOT be able to view project
$cannotViewProject = $admin2->can('view', $project1);
echo "Admin2 CANNOT view project in other workspace: " . ($cannotViewProject ? "✗" : "✓") . "\n";

echo "\n=== POLICY VERIFICATION COMPLETE ===\n";
echo "\nTo clean up test data, run:\n";
echo "User::whereIn('email', ['admin1@test.com', 'admin2@test.com', 'team@test.com'])->delete();\n";
echo "Workspace::whereIn('slug', ['test-workspace-1', 'test-workspace-2'])->delete();\n";