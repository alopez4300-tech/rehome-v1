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
        Schema::create('agent_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('agent_threads')->onDelete('cascade');
            $table->enum('status', ['queued', 'running', 'completed', 'failed'])->default('queued');
            $table->string('provider')->nullable(); // openai, anthropic, etc.
            $table->string('model')->nullable(); // gpt-4o-mini, claude-3-haiku, etc.
            $table->integer('tokens_in')->nullable();
            $table->integer('tokens_out')->nullable();
            $table->integer('cost_cents')->nullable();
            $table->json('context_used')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
            
            $table->index(['thread_id', 'status']);
            $table->index(['provider', 'model']);
            $table->index(['started_at', 'finished_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_runs');
    }
};
