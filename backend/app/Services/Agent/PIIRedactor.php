<?php

namespace App\Services\Agent;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * PII Redactor - Protects sensitive information in agent contexts
 *
 * Implements configurable PII patterns and role-based redaction
 * as specified in the security requirements.
 */
class PIIRedactor
{
    private array $config;

    public function __construct()
    {
        $this->config = config('ai.pii_redaction');
    }

    /**
     * Redact PII from agent context based on user permissions
     */
    public function redactContext(array $context, User $user): array
    {
        if (! $this->config['enabled']) {
            return $context;
        }

        Log::info('Applying PII redaction', [
            'user_id' => $user->id,
            'user_role' => $this->getUserRole($user),
        ]);

        // Apply redaction based on user role and permissions
        $redactedContext = $context;

        // Redact system prompt
        if (isset($context['system_prompt'])) {
            $redactedContext['system_prompt'] = $this->redactText($context['system_prompt']);
        }

        // Redact messages
        if (isset($context['messages'])) {
            $redactedContext['messages'] = $this->redactMessages($context['messages'], $user);
        }

        // Redact task content
        if (isset($context['tasks'])) {
            $redactedContext['tasks'] = $this->redactTasks($context['tasks'], $user);
        }

        // Redact file metadata
        if (isset($context['files'])) {
            $redactedContext['files'] = $this->redactFiles($context['files'], $user);
        }

        return $redactedContext;
    }

    /**
     * Redact PII patterns from text
     */
    public function redactText(string $text): string
    {
        $redacted = $text;

        foreach ($this->config['patterns'] as $type => $pattern) {
            $redacted = preg_replace($pattern, $this->config['replacement'], $redacted);
        }

        return $redacted;
    }

    /**
     * Redact messages based on user permissions
     */
    private function redactMessages(array $messages, User $user): array
    {
        $userRole = $this->getUserRole($user);

        return array_map(function ($message) use ($userRole) {
            $redactedMessage = $message;

            // Always redact PII patterns
            $redactedMessage['content'] = $this->redactText($message['content']);

            // Additional role-based redaction
            if ($userRole === 'client') {
                // Clients see less internal discussion
                $redactedMessage = $this->applyClientRedaction($redactedMessage);
            }

            return $redactedMessage;
        }, $messages);
    }

    /**
     * Redact task information based on user permissions
     */
    private function redactTasks(array $tasks, User $user): array
    {
        $userRole = $this->getUserRole($user);
        $redactedTasks = $tasks;

        foreach (['recent_tasks', 'overdue_tasks', 'blocked_tasks', 'completed_tasks'] as $taskType) {
            if (isset($tasks[$taskType])) {
                $redactedTasks[$taskType] = array_map(function ($task) use ($userRole) {
                    $redactedTask = $task;

                    // Redact PII in task descriptions
                    if (isset($task['description'])) {
                        $redactedTask['description'] = $this->redactText($task['description']);
                    }

                    if (isset($task['notes'])) {
                        $redactedTask['notes'] = $this->redactText($task['notes']);
                    }

                    // Role-based field redaction
                    if ($userRole === 'client' && isset($task['internal_notes'])) {
                        unset($redactedTask['internal_notes']);
                    }

                    return $redactedTask;
                }, $tasks[$taskType]);
            }
        }

        return $redactedTasks;
    }

    /**
     * Redact file metadata based on user permissions
     */
    private function redactFiles(array $files, User $user): array
    {
        $userRole = $this->getUserRole($user);
        $redactedFiles = $files;

        if (isset($files['recent_files'])) {
            $redactedFiles['recent_files'] = array_filter(
                array_map(function ($file) use ($userRole) {
                    // Skip confidential files for clients
                    if ($userRole === 'client' && ($file['confidential'] ?? false)) {
                        return null;
                    }

                    $redactedFile = $file;

                    // Redact PII in file names and descriptions
                    if (isset($file['name'])) {
                        $redactedFile['name'] = $this->redactText($file['name']);
                    }

                    if (isset($file['description'])) {
                        $redactedFile['description'] = $this->redactText($file['description']);
                    }

                    return $redactedFile;
                }, $files['recent_files'])
            );
        }

        // Redact project metadata
        if (isset($files['project_meta']['description'])) {
            $redactedFiles['project_meta']['description'] = $this->redactText(
                $files['project_meta']['description']
            );
        }

        return $redactedFiles;
    }

    /**
     * Apply client-specific redaction rules
     */
    private function applyClientRedaction(array $message): array
    {
        $redacted = $message;

        // Remove internal communication markers
        $internalPatterns = [
            '/\[INTERNAL\].*?\[\/INTERNAL\]/s',
            '/\@team\s+[^\n]+/i',
            '/\binternal\s+note:.*$/im',
        ];

        foreach ($internalPatterns as $pattern) {
            $redacted['content'] = preg_replace($pattern, '[INTERNAL COMMUNICATION REDACTED]', $redacted['content']);
        }

        return $redacted;
    }

    /**
     * Determine user role for redaction purposes
     */
    private function getUserRole(User $user): string
    {
        // TODO: Implement proper role detection when permission system is in place
        // For now, return a placeholder

        if ($user->email && str_contains($user->email, 'admin')) {
            return 'admin';
        }

        if ($user->email && str_contains($user->email, 'consultant')) {
            return 'consultant';
        }

        return 'client';
    }

    /**
     * Check if user has permission to see sensitive data
     */
    public function canAccessSensitiveData(User $user, string $dataType = 'general'): bool
    {
        $role = $this->getUserRole($user);

        $permissions = [
            'admin' => ['general', 'financial', 'internal', 'all'],
            'consultant' => ['general', 'internal'],
            'team' => ['general', 'internal'],
            'client' => ['general'],
        ];

        return in_array($dataType, $permissions[$role] ?? []);
    }

    /**
     * Log redaction activity for audit purposes
     */
    private function logRedactionActivity(User $user, string $contextType, array $stats): void
    {
        Log::info('PII redaction applied', [
            'user_id' => $user->id,
            'user_role' => $this->getUserRole($user),
            'context_type' => $contextType,
            'redaction_stats' => $stats,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Validate redaction configuration
     */
    public function validateConfiguration(): array
    {
        $issues = [];

        if (! is_array($this->config['patterns'])) {
            $issues[] = 'PII patterns configuration is invalid';
        }

        foreach ($this->config['patterns'] as $type => $pattern) {
            if (@preg_match($pattern, '') === false) {
                $issues[] = "Invalid regex pattern for PII type: {$type}";
            }
        }

        if (empty($this->config['replacement'])) {
            $issues[] = 'PII replacement text is not configured';
        }

        return $issues;
    }
}
