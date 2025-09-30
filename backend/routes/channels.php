<?php

use App\Models\AgentThread;
use Illuminate\Support\Facades\Broadcast;

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
| Agent Channel Authorization (Phase 2: Broadcasting)
|--------------------------------------------------------------------------
*/

Broadcast::channel('agent.thread.{threadId}', function ($user, int $threadId) {
    // Only allow if the user can view this thread
    return AgentThread::query()
        ->whereKey($threadId)
        ->whereHas('project', function ($q) use ($user) {
            $q->where('workspace_id', $user->workspace_id ?? $user->current_workspace_id);
        })
        ->exists();
});

// Presence channel for "who's online / typing" in a workspace
Broadcast::channel('presence.workspace.{workspaceId}', function ($user, int $workspaceId) {
    if (! $user->isMemberOf($workspaceId)) {
        return false;
    }

    return [
        'id' => $user->id,
        'name' => $user->name,
        // add avatar, role, etc. if useful
    ];
});

// Agent thread channels - role-based access control
Broadcast::channel('agent.thread.{threadId}', function ($user, $threadId) {
    $thread = AgentThread::find($threadId);

    if (! $thread) {
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
