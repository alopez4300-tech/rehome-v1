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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('status')->default('active'); // active, completed, archived, cancelled
            $table->string('priority')->default('medium'); // low, medium, high, urgent
            $table->decimal('budget', 12, 2)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('deadline')->nullable();
            $table->json('metadata')->nullable(); // Custom fields, tags, etc.
            $table->timestamps();

            $table->unique(['workspace_id', 'slug']);
            $table->index(['workspace_id', 'status']);
            $table->index(['workspace_id', 'priority']);
            $table->index(['deadline']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
