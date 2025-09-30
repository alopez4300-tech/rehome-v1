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
            $table->foreignId('workspace_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('role')->default('user'); // admin, team_member, consultant, client
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_active_at')->nullable();
            $table->json('preferences')->nullable();

            $table->index(['workspace_id', 'is_active']);
            $table->index(['workspace_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['workspace_id']);
            $table->dropColumn(['workspace_id', 'role', 'is_active', 'last_active_at', 'preferences']);
        });
    }
};
