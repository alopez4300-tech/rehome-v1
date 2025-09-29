<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AuthDiag extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:auth-diag {email?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose roles, current workspace, and membership checks';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email') ?? 'ops@rehome.com';
        $u = \App\Models\User::where('email', $email)->first();

        if (!$u) {
            $this->error("User not found: {$email}");
            return 1;
        }

        $wid = $u->current_workspace_id;
        $this->info("User: {$u->email} (id={$u->id})");
        $this->line("current_workspace_id: " . var_export($wid, true));
        $this->line('isSystemAdmin(): ' . ($u->isSystemAdmin() ? 'true' : 'false'));
        $this->line('isWorkspaceAdmin(): ' . ($u->isWorkspaceAdmin() ? 'true' : 'false'));

        $exists = \App\Models\WorkspaceMember::where('workspace_id', $wid)
            ->where('user_id', $u->id)
            ->whereIn('role', ['owner','admin'])
            ->exists();

        $this->line("DB membership exists (owner|admin): " . ($exists ? 'true' : 'false'));
        $this->line("Gate manage-current-workspace: " . (\Illuminate\Support\Facades\Gate::forUser($u)->allows('manage-current-workspace') ? 'true' : 'false'));

        $rows = \App\Models\WorkspaceMember::where('user_id', $u->id)->get(['workspace_id','role']);
        foreach ($rows as $r) {
            $this->line("membership -> workspace_id={$r->workspace_id}, role={$r->role}");
        }

        return 0;
    }
}
