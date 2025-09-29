<?php

namespace App\Services\Agent;

use App\Models\Project;
use App\Models\AgentThread;
use App\Services\Agent\PIIRedactor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Agent Context Builder - Manages token budget allocation and context building
 *
 * Implements the 50/30/20 token split (messages/tasks/files) with intelligent
 * truncation and PII redaction as specified in the technical requirements.
 */
class ContextBuilder
{
    private PIIRedactor $piiRedactor;
    private array $config;

    public function __construct(PIIRedactor $piiRedactor)
    {
        $this->piiRedactor = $piiRedactor;
        $this->config = config('ai');
    }

    /**
     * Build context for an agent thread within token budget
     */
    public function buildContext(AgentThread $thread, int $maxTokens): array
    {
        $safetyBuffer = (int) ($maxTokens * $this->config['token_safety_buffer']);
        $availableTokens = $maxTokens - $safetyBuffer;

        Log::info('Building agent context', [
            'thread_id' => $thread->id,
            'max_tokens' => $maxTokens,
            'available_tokens' => $availableTokens,
            'safety_buffer' => $safetyBuffer,
        ]);

        // Calculate token allocations based on 50/30/20 split
        $allocations = $this->calculateTokenAllocations($availableTokens);

        // Build context components
        $context = [
            'system_prompt' => $this->buildSystemPrompt($thread),
            'messages' => $this->buildMessagesContext($thread, $allocations['messages']),
            'tasks' => $this->buildTasksContext($thread->project, $allocations['tasks']),
            'files' => $this->buildFilesContext($thread->project, $allocations['files']),
            'metadata' => $this->buildMetadataContext($thread),
        ];

        // Apply PII redaction
        $context = $this->piiRedactor->redactContext($context, $thread->created_by);

        // Log context stats for monitoring
        $this->logContextStats($thread, $context, $allocations);

        return $context;
    }

    /**
     * Calculate token allocations based on configured split percentages
     */
    private function calculateTokenAllocations(int $availableTokens): array
    {
        $budget = $this->config['context_budget'];

        return [
            'messages' => (int) ($availableTokens * $budget['messages']),
            'tasks' => (int) ($availableTokens * $budget['tasks']),
            'files' => (int) ($availableTokens * $budget['files']),
        ];
    }

    /**
     * Build system prompt based on thread audience and project context
     */
    private function buildSystemPrompt(AgentThread $thread): string
    {
        $isAdmin = $thread->audience === 'admin';
        $project = $thread->project;
        $workspace = $project->workspace;

        $basePrompt = $isAdmin
            ? "You are an AI assistant for workspace administrators with access to all workspace data."
            : "You are an AI assistant for project participants with access only to this project's data.";

        $contextPrompt = "
Project: {$project->name}
Workspace: {$workspace->name}
Current Date: " . now()->format('Y-m-d H:i T') . "

Guidelines:
- Provide concise, actionable responses
- Focus on recent activities and current priorities
- Highlight blockers and risks when relevant
- Maintain professional tone
- Respect data scoping based on user permissions
";

        if (!$isAdmin) {
            $contextPrompt .= "\nIMPORTANT: You can only access data from the current project. Do not reference other projects or workspace-wide information.";
        }

        return $basePrompt . "\n" . $contextPrompt;
    }

    /**
     * Build messages context with intelligent truncation
     */
    private function buildMessagesContext(AgentThread $thread, int $tokenLimit): array
    {
        $messages = $thread->messages()
            ->orderBy('created_at', 'desc')
            ->get();

        $context = [];
        $tokenCount = 0;

        foreach ($messages as $message) {
            $messageTokens = $this->estimateTokens($message->content);

            if ($tokenCount + $messageTokens > $tokenLimit) {
                break; // Drop whole message to avoid truncation
            }

            $context[] = [
                'role' => $message->role,
                'content' => $message->content,
                'created_at' => $message->created_at->toISOString(),
                'meta' => $message->meta ?? [],
            ];

            $tokenCount += $messageTokens;
        }

        // Reverse to chronological order
        return array_reverse($context);
    }

    /**
     * Build tasks context prioritizing recent activity and @mentions
     */
    private function buildTasksContext(Project $project, int $tokenLimit): array
    {
        // Get recent tasks with priority for @mentions and status changes
        $tasks = collect(); // TODO: Implement when Task model exists

        // For now, return placeholder structure
        return [
            'recent_tasks' => [],
            'overdue_tasks' => [],
            'blocked_tasks' => [],
            'completed_tasks' => [],
            'token_usage' => 0,
        ];
    }

    /**
     * Build files/metadata context
     */
    private function buildFilesContext(Project $project, int $tokenLimit): array
    {
        // TODO: Implement when file/document models exist
        return [
            'recent_files' => [],
            'project_meta' => [
                'created_at' => $project->created_at->toISOString(),
                'updated_at' => $project->updated_at->toISOString(),
                'description' => $project->description ?? '',
            ],
            'token_usage' => $this->estimateTokens($project->description ?? ''),
        ];
    }

    /**
     * Build metadata context with thread and project info
     */
    private function buildMetadataContext(AgentThread $thread): array
    {
        return [
            'thread' => [
                'id' => $thread->id,
                'title' => $thread->title,
                'audience' => $thread->audience,
                'status' => $thread->status,
                'created_at' => $thread->created_at->toISOString(),
            ],
            'project' => [
                'id' => $thread->project->id,
                'name' => $thread->project->name,
            ],
            'workspace' => [
                'id' => $thread->project->workspace->id,
                'name' => $thread->project->workspace->name,
            ],
        ];
    }

    /**
     * Estimate token count for text (rough approximation)
     */
    private function estimateTokens(string $text): int
    {
        // Rough estimate: 1 token ≈ 4 characters for English text
        return (int) ceil(strlen($text) / 4);
    }

    /**
     * Check if context needs refreshing based on token usage
     */
    public function needsRefresh(array $context, int $maxTokens): bool
    {
        $currentUsage = $this->calculateContextTokens($context);
        $threshold = $maxTokens * $this->config['behavior']['context_refresh_threshold'];

        return $currentUsage > $threshold;
    }

    /**
     * Calculate total tokens used in context
     */
    private function calculateContextTokens(array $context): int
    {
        $total = 0;

        $total += $this->estimateTokens($context['system_prompt'] ?? '');

        foreach ($context['messages'] ?? [] as $message) {
            $total += $this->estimateTokens($message['content'] ?? '');
        }

        $total += $context['tasks']['token_usage'] ?? 0;
        $total += $context['files']['token_usage'] ?? 0;

        return $total;
    }

    /**
     * Log context building statistics for monitoring
     */
    private function logContextStats(AgentThread $thread, array $context, array $allocations): void
    {
        $stats = [
            'thread_id' => $thread->id,
            'project_id' => $thread->project_id,
            'workspace_id' => $thread->project->workspace_id,
            'audience' => $thread->audience,
            'token_allocations' => $allocations,
            'actual_usage' => [
                'total' => $this->calculateContextTokens($context),
                'messages' => count($context['messages']),
                'tasks' => count($context['tasks']['recent_tasks'] ?? []),
                'files' => count($context['files']['recent_files'] ?? []),
            ],
        ];

        Log::info('Agent context built', $stats);
    }
}
