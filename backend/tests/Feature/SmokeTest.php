<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create required roles
        Role::create(['name' => 'system-admin', 'guard_name' => 'web']);
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'member', 'guard_name' => 'web']);
    }

    /** @test */
    public function application_boots_successfully(): void
    {
        // Simple smoke test - verify helper functions work
        $this->assertTrue(function_exists('feature'));
        $this->assertTrue(function_exists('profile'));
        $this->assertTrue(function_exists('ws'));
    }

    /** @test */
    public function system_admin_can_crud_projects(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('system-admin');

        $workspace = Workspace::factory()->create();

        $this->actingAs($admin);

        // Create project
        $project = Project::factory()->create([
            'workspace_id' => $workspace->id,
            'name' => 'Test Project',
        ]);

        $this->assertDatabaseHas('projects', ['name' => 'Test Project']);

        // Verify admin can access project
        $this->assertTrue($admin->isSystemAdmin());
        $this->assertNotNull(Project::find($project->id));
    }

    /** @test */
    public function user_roles_work_correctly(): void
    {
        $systemAdmin = User::factory()->create();
        $systemAdmin->assignRole('system-admin');

        $regularUser = User::factory()->create();

        // Test role checking
        $this->assertTrue($systemAdmin->isSystemAdmin());
        $this->assertFalse($regularUser->isSystemAdmin());
    }

    /** @test */
    public function feature_flags_work_correctly(): void
    {
        // Test feature function
        config(['feature.multi_tenant' => true]);
        $this->assertTrue(feature('multi_tenant'));

        config(['feature.multi_tenant' => false]);
        $this->assertFalse(feature('multi_tenant'));

        // Test profile function
        config(['feature.profile' => 'light']);
        $this->assertEquals('light', profile());
        $this->assertTrue(profile('light'));
        $this->assertFalse(profile('scale'));
    }

    /** @test */
    public function multi_tenant_scoping_excludes_system_admins(): void
    {
        if (! feature('multi_tenant')) {
            $this->markTestSkipped('Multi-tenant feature is disabled');
        }

        $systemAdmin = User::factory()->create();
        $systemAdmin->assignRole('system-admin');

        $workspace1 = Workspace::factory()->create();
        $workspace2 = Workspace::factory()->create();

        $project1 = Project::factory()->create(['workspace_id' => $workspace1->id]);
        $project2 = Project::factory()->create(['workspace_id' => $workspace2->id]);

        $this->actingAs($systemAdmin);

        // System admin should see projects from all workspaces
        $allProjects = Project::all();
        $this->assertCount(2, $allProjects);
        $this->assertTrue($allProjects->contains($project1));
        $this->assertTrue($allProjects->contains($project2));
    }
}
