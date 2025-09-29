<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\AgentThread;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/*
|--------------------------------------------------------------------------
| Agent Channel Authorization
|--------------------------------------------------------------------------
*/

// Agent thread channels - role-based access control
Broadcast::channel('agent.thread.{threadId}', function ($user, $threadId) {
    $thread = AgentThread::find($threadId);

    if (!$thread) {
        return false;
    }

    // Admin users can access all threads in their workspace
    if ($thread->audience === 'admin') {
        return $user->workspace_id === $thread->project->workspace_id;
    }

    // Participants can only access threads in their assigned projects
    return $thread->project->users()->where('user_id', $user->id)->exists();
});

// Project-wide agent channels for summaries and notifications
Broadcast::channel('agent.project.{projectId}', function ($user, $projectId) {
    return \App\Models\Project::find($projectId)
        ?->users()
        ->where('user_id', $user->id)
        ->exists();
});

// Workspace-wide agent channels (admin only)
Broadcast::channel('agent.workspace.{workspaceId}', function ($user, $workspaceId) {
    return $user->workspace_id === (int) $workspaceId && $user->role === 'admin';
});
