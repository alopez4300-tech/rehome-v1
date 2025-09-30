<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentThread;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectAgentController extends Controller
{
    /**
     * Create a new agent thread for a project
     */
    public function createThread(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProjectAccess($project);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'audience' => 'required|in:participant,admin',
            'metadata' => 'sometimes|array',
        ]);

        $thread = AgentThread::create([
            'project_id' => $project->id,
            'user_id' => Auth::id(),
            'audience' => $validated['audience'],
            'title' => $validated['title'] ?? $this->generateThreadTitle($validated['audience']),
            'metadata' => $validated['metadata'] ?? null,
        ]);

        return response()->json([
            'data' => $thread->load(['user', 'project']),
            'message' => 'Thread created successfully',
        ], 201);
    }

    /**
     * Get threads for a project
     */
    public function getThreads(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProjectAccess($project);

        $user = Auth::user();
        $audience = $request->get('audience', 'participant');

        $query = AgentThread::where('project_id', $project->id);

        if ($audience === 'admin') {
            // Admin threads: check workspace access
            if ($project->workspace_id !== $user->workspace_id) {
                abort(403, 'Access denied to admin threads');
            }
            $query->where('audience', 'admin');
        } else {
            // Participant threads: check project assignment
            if (! $project->users()->where('users.id', $user->id)->exists()) {
                abort(403, 'Access denied to participant threads');
            }
            $query->where('audience', 'participant');
        }

        $threads = $query->with(['user', 'messages' => function ($q) {
            $q->latest()->limit(1);
        }])
            ->latest()
            ->paginate(20);

        return response()->json($threads);
    }

    /**
     * Authorize project access
     */
    private function authorizeProjectAccess(Project $project): void
    {
        $user = Auth::user();

        // Check if user has access to this project through workspace or assignment
        $hasAccess = $project->workspace_id === $user->workspace_id ||
                    $project->users()->where('users.id', $user->id)->exists();

        if (! $hasAccess) {
            abort(403, 'Access denied to this project');
        }
    }

    /**
     * Generate a default thread title
     */
    private function generateThreadTitle(string $audience): string
    {
        $prefix = $audience === 'admin' ? 'Admin Chat' : 'Team Chat';

        return $prefix.' - '.now()->format('M j, Y H:i');
    }
}
