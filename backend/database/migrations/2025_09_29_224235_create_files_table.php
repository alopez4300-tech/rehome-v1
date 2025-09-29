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
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('original_name');
            $table->string('path');
            $table->string('category')->default('documents'); // documents, images, contracts, misc
            $table->string('mime_type');
            $table->bigInteger('size');
            $table->foreignId('uploaded_by')->constrained('users');

            // Project assets derived data columns
            $table->string('ocr_path')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->string('embedding_path')->nullable();
            $table->boolean('has_ocr')->default(false);
            $table->boolean('has_thumbnail')->default(false);
            $table->boolean('has_embedding')->default(false);

            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
