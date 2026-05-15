<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_request_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 40);
            $table->text('prompt')->nullable();
            $table->json('parameters')->nullable();
            $table->string('image_url')->nullable();
            $table->string('image_path')->nullable();
            $table->string('status', 30)->default('pending');
            $table->unsignedInteger('credits_consumed')->default(0);
            $table->text('error_message')->nullable();
            $table->string('provider', 40)->default('gemini');
            $table->unsignedBigInteger('parent_generation_id')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index('project_request_id');
        });

        Schema::table('project_requests', function (Blueprint $table) {
            $table->foreign('approved_ai_generation_id')
                ->references('id')
                ->on('ai_generations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('project_requests', function (Blueprint $table) {
            $table->dropForeign(['approved_ai_generation_id']);
        });
        Schema::dropIfExists('ai_generations');
    }
};
