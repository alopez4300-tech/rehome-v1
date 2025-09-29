<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('workspace_members', function (Blueprint $table) {
            // Only add if not exists
            try {
                $table->index(['workspace_id', 'user_id']);
            } catch (\Exception $e) {
                // Index may already exist
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'current_workspace_id')) {
                try {
                    $table->foreign('current_workspace_id')
                        ->references('id')->on('workspaces')
                        ->nullOnDelete();
                } catch (\Exception $e) {
                    // Foreign key may already exist
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workspace_members', function (Blueprint $table) {
            $table->dropUnique(['workspace_id', 'user_id']);
            $table->dropIndex(['workspace_id', 'user_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'current_workspace_id')) {
                $table->dropForeign(['current_workspace_id']);
            }
        });
    }
};
