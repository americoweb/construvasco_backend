<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('designs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('session_id', 191)->nullable();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_color_id');
            $table->unsignedBigInteger('product_print_area_id');
            $table->text('prompt')->nullable();
            $table->text('mockup_url')->nullable();
            $table->longText('mockup_base64')->nullable();
            $table->string('logo_path', 500)->nullable();
            $table->string('logo_mime_type', 50)->nullable();
            $table->string('reference_image_path', 500)->nullable();
            $table->string('reference_mime_type', 50)->nullable();
            $table->string('status', 50)->default('draft');
            $table->integer('generation_attempts')->default(0);
            $table->string('ai_model_used')->nullable();
            $table->json('ai_response_metadata')->nullable();
            $table->boolean('is_from_suggestion')->default(false);
            $table->string('suggestion_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['session_id', 'status']);
            $table->index('status');
            $table->index('created_at');

            $table->foreign('product_id')
                  ->references('id')
                  ->on('products')
                  ->onDelete('cascade');

            $table->foreign('product_color_id')
                  ->references('id')
                  ->on('product_colors')
                  ->onDelete('cascade');

            $table->foreign('product_print_area_id')
                  ->references('id')
                  ->on('product_print_areas')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('designs');
    }
};
