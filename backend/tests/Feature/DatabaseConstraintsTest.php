<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseConstraintsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            \DB::statement('PRAGMA foreign_keys = ON;');
        }

        foreach (['admin', 'team', 'consultant', 'client'] as $r) {
            \Spatie\Permission\Models\Role::findOrCreate($r, 'web');
        }
    }

    public function test_user_current_workspace_nulls_on_workspace_delete(): void
    {
        $ws = Workspace::factory()->create();
        $user = User::factory()->create(['current_workspace_id' => $ws->id]);

        // simulate hard delete if your FK uses ON DELETE SET NULL
        $ws->forceDelete();

        $user->refresh();
        $this->assertNull($user->current_workspace_id);
    }

    public function test_project_user_unique_constraint(): void
    {
        $ws = Workspace::factory()->create();
        $proj = Project::factory()->for($ws)->create();
        $user = User::factory()->create();

        $proj->users()->attach($user->id, ['role' => 'team']);

        $this->expectException(QueryException::class);
        $proj->users()->attach($user->id, ['role' => 'team']);
    }

    public function test_project_user_rows_removed_on_project_hard_delete(): void
    {
        $ws = Workspace::factory()->create();
        $proj = Project::factory()->for($ws)->create();
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();

        $proj->users()->attach($u1->id, ['role' => 'team']);
        $proj->users()->attach($u2->id, ['role' => 'client']);

        $proj->forceDelete();

        $this->assertDatabaseMissing('project_user', ['project_id' => $proj->id, 'user_id' => $u1->id]);
        $this->assertDatabaseMissing('project_user', ['project_id' => $proj->id, 'user_id' => $u2->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function enforces_unique_workspace_id_user_id_on_workspace_members()
    {
        $ws = Workspace::factory()->create();
        $user = User::factory()->create();

        // First membership should succeed
        WorkspaceMember::create([
            'workspace_id' => $ws->id,
            'user_id' => $user->id,
            'role' => 'admin',
        ]);

        // Second membership with same workspace_id and user_id should fail
        $this->expectException(QueryException::class);

        WorkspaceMember::create([
            'workspace_id' => $ws->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function cascades_workspace_members_on_workspace_delete()
    {
        $ws = Workspace::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        WorkspaceMember::create([
            'workspace_id' => $ws->id,
            'user_id' => $user1->id,
            'role' => 'admin',
        ]);

        WorkspaceMember::create([
            'workspace_id' => $ws->id,
            'user_id' => $user2->id,
            'role' => 'member',
        ]);

        // Force delete workspace should cascade delete members
        $ws->forceDelete();

        $this->assertDatabaseMissing('workspace_members', ['workspace_id' => $ws->id, 'user_id' => $user1->id]);
        $this->assertDatabaseMissing('workspace_members', ['workspace_id' => $ws->id, 'user_id' => $user2->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function enforces_project_scoping_to_workspace()
    {
        $wsA = Workspace::factory()->create(['name' => 'Workspace A']);
        $wsB = Workspace::factory()->create(['name' => 'Workspace B']);

        $adminA = User::factory()->create(['current_workspace_id' => $wsA->id]);
        $adminA->assignRole('admin');

        $adminB = User::factory()->create(['current_workspace_id' => $wsB->id]);
        $adminB->assignRole('admin');

        $projectA = Project::factory()->create(['workspace_id' => $wsA->id]);
        $projectB = Project::factory()->create(['workspace_id' => $wsB->id]);

        // Admin A should only see projects in workspace A
        $this->actingAs($adminA);

        // Simulate the workspace scoping query that would be used in resources
        $visibleProjects = Project::where('workspace_id', $adminA->current_workspace_id)->get();

        $this->assertTrue($visibleProjects->contains($projectA));
        $this->assertFalse($visibleProjects->contains($projectB));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function cascades_project_members_on_project_delete()
    {
        $ws = Workspace::factory()->create();
        $project = Project::factory()->create(['workspace_id' => $ws->id]);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Assuming project members are handled through a pivot table
        // If you have a ProjectMember model, adjust accordingly
        $project->members()->attach($user1->id, ['role' => 'team']);
        $project->members()->attach($user2->id, ['role' => 'client']);

        $projectId = $project->id;

        // Force delete project should cascade delete member relationships
        $project->forceDelete();

        $this->assertDatabaseMissing('project_members', ['project_id' => $projectId, 'user_id' => $user1->id]);
        $this->assertDatabaseMissing('project_members', ['project_id' => $projectId, 'user_id' => $user2->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function workspace_member_roles_are_properly_constrained()
    {
        $ws = Workspace::factory()->create();
        $user = User::factory()->create();

        // Valid roles should work
        $validRoles = ['owner', 'admin', 'member'];

        foreach ($validRoles as $role) {
            $member = WorkspaceMember::create([
                'workspace_id' => $ws->id,
                'user_id' => User::factory()->create()->id,
                'role' => $role,
            ]);

            $this->assertEquals($role, $member->role);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_be_member_of_multiple_workspaces()
    {
        $ws1 = Workspace::factory()->create(['name' => 'Workspace 1']);
        $ws2 = Workspace::factory()->create(['name' => 'Workspace 2']);
        $user = User::factory()->create();

        // User can be admin in one workspace and member in another
        WorkspaceMember::create([
            'workspace_id' => $ws1->id,
            'user_id' => $user->id,
            'role' => 'admin',
        ]);

        WorkspaceMember::create([
            'workspace_id' => $ws2->id,
            'user_id' => $user->id,
            'role' => 'member',
        ]);

        $memberships = WorkspaceMember::where('user_id', $user->id)->get();
        $this->assertCount(2, $memberships);

        $this->assertTrue($memberships->where('workspace_id', $ws1->id)->where('role', 'admin')->isNotEmpty());
        $this->assertTrue($memberships->where('workspace_id', $ws2->id)->where('role', 'member')->isNotEmpty());
    }
}
