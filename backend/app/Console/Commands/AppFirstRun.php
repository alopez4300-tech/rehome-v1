<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AppFirstRun extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:first-run
        {--email=admin@example.com}
        {--name=Admin}
        {--password=secret}
        {--workspace=Acme HQ}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bootstrap first admin, workspace, and membership';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Ensure roles exist
        foreach (['admin', 'team', 'consultant', 'client'] as $roleName) {
            \Spatie\Permission\Models\Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
        }

        // Create or fetch user
        $user = \App\Models\User::firstOrCreate(
            ['email' => $this->option('email')],
            [
                'name' => $this->option('name'),
                'password' => \Illuminate\Support\Facades\Hash::make($this->option('password')),
            ]
        );

        $user->syncRoles(['admin']);

        // Create or fetch workspace
        $workspace = \App\Models\Workspace::firstOrCreate([
            'name' => $this->option('workspace'),
        ], [
            'slug' => \Illuminate\Support\Str::slug($this->option('workspace')),
            'description' => 'Primary workspace for '.$this->option('workspace'),
        ]);

        // Set current workspace on user (if column exists)
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'current_workspace_id')) {
            $user->forceFill(['current_workspace_id' => $workspace->id])->save();
        }

        // Ensure membership as owner
        \App\Models\WorkspaceMember::firstOrCreate([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
        ], [
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        $this->info('✅ First run complete:');
        $this->info("- User: {$user->email}");
        $this->info('- Role: admin');
        $this->info("- Workspace: {$workspace->name} (owner)");
        $this->info('You can now visit /admin (Admin Panel).');

        return static::SUCCESS;
    }
}
