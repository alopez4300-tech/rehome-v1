<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuthenticationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles for testing
        foreach (['system-admin', 'team', 'consultant', 'client'] as $roleName) {
            \Spatie\Permission\Models\Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web'
            ]);
        }
    }

    /** @test */
    public function system_admin_has_global_access()
    {
        $workspace = Workspace::factory()->create();
        $systemAdmin = User::factory()->create(['current_workspace_id' => $workspace->id]);
        $systemAdmin->assignRole('system-admin');

        $this->assertTrue($systemAdmin->isSystemAdmin());
        $this->assertTrue($systemAdmin->isWorkspaceAdmin());
        $this->assertTrue(Gate::forUser($systemAdmin)->allows('manage-current-workspace'));
    }

    /** @test */
    public function workspace_admin_has_workspace_access_only()
    {
        $workspace = Workspace::factory()->create();
        $workspaceAdmin = User::factory()->create(['current_workspace_id' => $workspace->id]);
        $workspaceAdmin->assignRole('team');

        WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $workspaceAdmin->id,
            'role' => 'admin'
        ]);

        $this->assertFalse($workspaceAdmin->isSystemAdmin());
        $this->assertTrue($workspaceAdmin->isWorkspaceAdmin());
        $this->assertTrue(Gate::forUser($workspaceAdmin)->allows('manage-current-workspace'));
    }

    /** @test */
    public function workspace_owner_has_workspace_access()
    {
        $workspace = Workspace::factory()->create();
        $workspaceOwner = User::factory()->create(['current_workspace_id' => $workspace->id]);
        $workspaceOwner->assignRole('team');

        WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $workspaceOwner->id,
            'role' => 'owner'
        ]);

        $this->assertFalse($workspaceOwner->isSystemAdmin());
        $this->assertTrue($workspaceOwner->isWorkspaceAdmin());
        $this->assertTrue(Gate::forUser($workspaceOwner)->allows('manage-current-workspace'));
    }

    /** @test */
    public function regular_user_has_no_admin_access()
    {
        $workspace = Workspace::factory()->create();
        $regularUser = User::factory()->create(['current_workspace_id' => $workspace->id]);
        $regularUser->assignRole('team');

        WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $regularUser->id,
            'role' => 'member'
        ]);

        $this->assertFalse($regularUser->isSystemAdmin());
        $this->assertFalse($regularUser->isWorkspaceAdmin());
        $this->assertFalse(Gate::forUser($regularUser)->allows('manage-current-workspace'));
    }

    /** @test */
    public function user_without_workspace_has_no_workspace_admin_access()
    {
        $user = User::factory()->create(['current_workspace_id' => null]);
        $user->assignRole('team');

        $this->assertFalse($user->isSystemAdmin());
        $this->assertFalse($user->isWorkspaceAdmin());
        $this->assertFalse(Gate::forUser($user)->allows('manage-current-workspace'));
    }

    /** @test */
    public function workspace_admin_cannot_access_other_workspaces()
    {
        $workspace1 = Workspace::factory()->create();
        $workspace2 = Workspace::factory()->create();

        $workspaceAdmin = User::factory()->create(['current_workspace_id' => $workspace1->id]);
        $workspaceAdmin->assignRole('team');

        WorkspaceMember::create([
            'workspace_id' => $workspace1->id,
            'user_id' => $workspaceAdmin->id,
            'role' => 'admin'
        ]);

        $this->assertTrue($workspaceAdmin->isWorkspaceAdmin($workspace1->id));
        $this->assertFalse($workspaceAdmin->isWorkspaceAdmin($workspace2->id));
    }

    /** @test */
    public function login_response_redirects_correctly()
    {
        $workspace = Workspace::factory()->create();

        // System admin
        $systemAdmin = User::factory()->create(['current_workspace_id' => $workspace->id]);
        $systemAdmin->assignRole('system-admin');

        // Workspace admin
        $workspaceAdmin = User::factory()->create(['current_workspace_id' => $workspace->id]);
        $workspaceAdmin->assignRole('team');
        WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $workspaceAdmin->id,
            'role' => 'admin'
        ]);

        // Regular user
        $regularUser = User::factory()->create(['current_workspace_id' => $workspace->id]);
        $regularUser->assignRole('team');
        WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $regularUser->id,
            'role' => 'member'
        ]);

        // Test LoginResponse redirects
        $loginResponse = new \App\Filament\Responses\LoginResponse();

        // System admin should go to /admin
        $request = new \Illuminate\Http\Request();
        $request->setUserResolver(fn() => $systemAdmin);
        $response = $loginResponse->toResponse($request);
        $this->assertEquals(url('/admin'), $response->getTargetUrl());

        // Workspace admin should go to /ops
        $request->setUserResolver(fn() => $workspaceAdmin);
        $response = $loginResponse->toResponse($request);
        $this->assertEquals(url('/ops'), $response->getTargetUrl());

        // Regular user should go to /app
        $request->setUserResolver(fn() => $regularUser);
        $response = $loginResponse->toResponse($request);
        $this->assertEquals(url('/app'), $response->getTargetUrl());
    }

    /** @test */
    public function workspace_member_uniqueness_is_enforced()
    {
        $workspace = Workspace::factory()->create();
        $user = User::factory()->create();

        // First membership should succeed
        $membership1 = WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => 'admin'
        ]);

        $this->assertNotNull($membership1);

        // Second membership should fail due to unique constraint
        $this->expectException(\Illuminate\Database\QueryException::class);

        WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => 'owner'
        ]);
    }
}
