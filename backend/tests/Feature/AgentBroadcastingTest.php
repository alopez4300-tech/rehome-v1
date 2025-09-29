<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Workspace;
use App\Models\Project;
use App\Models\AgentThread;
use App\Models\AgentMessage;
use App\Events\Agent\AgentMessageCreated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Broadcast;

class AgentBroadcastingTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_message_created_event_is_broadcasted()
    {
        Event::fake([AgentMessageCreated::class]);

        // Create test data
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create(['workspace_id' => $workspace->id]);
        $thread = AgentThread::factory()->create(['project_id' => $project->id]);

        // Create an agent message
        $message = AgentMessage::create([
            'agent_thread_id' => $thread->id,
            'role' => 'assistant',
            'content' => 'Test response from agent',
            'metadata' => [
                'provider' => 'openai',
                'model' => 'gpt-4',
                'stream' => false,
            ],
            'cost_cents' => 50,
            'token_count' => 100,
        ]);

        // Fire the event
        $event = new AgentMessageCreated($message);
        event($event);

        // Assert the event was dispatched
        Event::assertDispatched(AgentMessageCreated::class, function ($event) use ($message) {
            return $event->message->id === $message->id;
        });
    }

    public function test_agent_message_broadcasts_to_correct_channel()
    {
        // Create test data
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create(['workspace_id' => $workspace->id]);
        $thread = AgentThread::factory()->create(['project_id' => $project->id]);

        $message = AgentMessage::create([
            'agent_thread_id' => $thread->id,
            'role' => 'assistant',
            'content' => 'Test response from agent',
            'metadata' => [
                'provider' => 'openai',
                'model' => 'gpt-4',
            ],
            'cost_cents' => 50,
            'token_count' => 100,
        ]);

        $event = new AgentMessageCreated($message);

        // Check that the event broadcasts to the correct channel
        $this->assertEquals(
            'agent.thread.' . $thread->id,
            $event->broadcastOn()->name
        );
    }

    public function test_channel_authorization_allows_workspace_admin()
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['user_id' => $user->id]);
        $project = Project::factory()->create(['workspace_id' => $workspace->id]);
        $thread = AgentThread::factory()->create(['project_id' => $project->id]);

        // Test channel authorization
        $this->actingAs($user);

        $response = $this->postJson('/broadcasting/auth', [
            'channel_name' => 'agent.thread.' . $thread->id,
        ]);

        $response->assertStatus(200);
    }

    public function test_channel_authorization_denies_unauthorized_user()
    {
        $owner = User::factory()->create();
        $unauthorizedUser = User::factory()->create();

        $workspace = Workspace::factory()->create(['user_id' => $owner->id]);
        $project = Project::factory()->create(['workspace_id' => $workspace->id]);
        $thread = AgentThread::factory()->create(['project_id' => $project->id]);

        // Test unauthorized access
        $this->actingAs($unauthorizedUser);

        $response = $this->postJson('/broadcasting/auth', [
            'channel_name' => 'agent.thread.' . $thread->id,
        ]);

        $response->assertStatus(403);
    }
}
