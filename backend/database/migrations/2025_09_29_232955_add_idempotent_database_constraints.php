<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureUsersCurrentWorkspaceNullOnDelete();
        $this->ensureWorkspaceMembersUniqueConstraint();
        $this->ensureProjectUserUniqueConstraint();
    }

    public function down(): void
    {
        // safe non-destructive down
        if (DB::getDriverName() === 'sqlite') {
            DB::unprepared('DROP TRIGGER IF EXISTS trg_users_current_workspace_nullify;');
        }
    }

    protected function ensureUsersCurrentWorkspaceNullOnDelete(): void
    {
        // Ensure the column exists
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'current_workspace_id')) {
                $table->foreignId('current_workspace_id')
                    ->nullable()
                    ->constrained('workspaces')
                    ->nullOnDelete();
            }
        });

        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'pgsql') {
            // Normalize FK to NULL ON DELETE (idempotent tries)
            Schema::table('users', function (Blueprint $table) {
                try {
                    $table->dropForeign(['current_workspace_id']);
                } catch (\Throwable $e) {
                }
                try {
                    $table->foreign('current_workspace_id')
                        ->references('id')->on('workspaces')
                        ->nullOnDelete();
                } catch (\Throwable $e) {
                }
            });
        } elseif ($driver === 'sqlite') {
            // Emulate ON DELETE SET NULL via trigger, idempotently
            DB::unprepared('
                CREATE TRIGGER IF NOT EXISTS trg_users_current_workspace_nullify
                AFTER DELETE ON workspaces
                FOR EACH ROW
                BEGIN
                    UPDATE users
                    SET current_workspace_id = NULL
                    WHERE current_workspace_id = OLD.id;
                END;
            ');
        }
    }

    protected function ensureWorkspaceMembersUniqueConstraint(): void
    {
        $driver = DB::getDriverName();

        // ---- workspace_members unique (workspace_id, user_id)
        if ($driver === 'sqlite') {
            DB::statement('
                CREATE UNIQUE INDEX IF NOT EXISTS workspace_members_workspace_user_unique
                ON workspace_members(workspace_id, user_id)
            ');
        } elseif ($driver === 'pgsql') {
            DB::statement('
                CREATE UNIQUE INDEX IF NOT EXISTS workspace_members_workspace_user_unique
                ON workspace_members (workspace_id, user_id)
            ');
        } else { // mysql
            $exists = DB::table('information_schema.statistics')
                ->where('table_schema', DB::raw('DATABASE()'))
                ->where('table_name', 'workspace_members')
                ->where('index_name', 'workspace_members_workspace_user_unique')
                ->exists();
            if (! $exists) {
                DB::statement('
                    CREATE UNIQUE INDEX workspace_members_workspace_user_unique
                    ON workspace_members (workspace_id, user_id)
                ');
            }
        }
    }

    protected function ensureProjectUserUniqueConstraint(): void
    {
        $driver = DB::getDriverName();

        // project_user unique (project_id, user_id) - idempotent check
        if ($driver === 'sqlite') {
            // Check if index exists first
            $indexExists = DB::select("
                SELECT name FROM sqlite_master
                WHERE type='index' AND name='project_user_project_id_user_id_unique'
            ");

            if (empty($indexExists)) {
                DB::statement('
                    CREATE UNIQUE INDEX project_user_project_id_user_id_unique
                    ON project_user (project_id, user_id)
                ');
            }
        } else {
            // For MySQL/PostgreSQL use Schema builder with try/catch
            Schema::table('project_user', function (Blueprint $table) {
                try {
                    $table->unique(['project_id', 'user_id'], 'project_user_project_id_user_id_unique');
                } catch (\Throwable $e) {
                    // ignore if exists
                }
            });
        }
    }
};
