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
        Schema::create('project_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('role')->default('member'); // owner, manager, member, viewer, consultant, client
            $table->boolean('can_manage_tasks')->default(false);
            $table->boolean('can_manage_files')->default(false);
            $table->boolean('can_manage_users')->default(false);
            $table->boolean('can_view_budget')->default(false);
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'user_id']);
            $table->index(['project_id', 'role']);
            $table->index(['user_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_user');
    }
};
