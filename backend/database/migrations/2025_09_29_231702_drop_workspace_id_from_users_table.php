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
        Schema::table('users', function (Blueprint $table) {
            // Drop the confusing workspace_id column - use current_workspace_id as single source of truth
            if (Schema::hasColumn('users', 'workspace_id')) {
                // Drop all constraints and indexes first
                try {
                    $table->dropForeign(['workspace_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist
                }

                try {
                    $table->dropIndex('users_workspace_id_role_index');
                } catch (\Exception $e) {
                    // Index might not exist
                }

                try {
                    $table->dropIndex('users_workspace_id_is_active_index');
                } catch (\Exception $e) {
                    // Index might not exist
                }

                $table->dropColumn('workspace_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Restore workspace_id if needed for rollback
            $table->foreignId('workspace_id')->nullable()->constrained()->after('email');
        });
    }
};
