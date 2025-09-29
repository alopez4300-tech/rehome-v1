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
            $table->enum('global_role', ['user', 'staff', 'super_admin'])->default('user')->after('email');
            $table->foreignId('current_workspace_id')->nullable()->constrained('workspaces')->after('global_role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['current_workspace_id']);
            $table->dropColumn(['global_role', 'current_workspace_id']);
        });
    }
};
