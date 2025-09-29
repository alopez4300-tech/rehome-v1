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
        Schema::create('agent_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('agent_threads')->onDelete('cascade');
            $table->enum('role', ['user', 'assistant', 'tool', 'system']);
            $table->longText('content');
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['thread_id', 'created_at']);
            $table->index(['role', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_messages');
    }
};
