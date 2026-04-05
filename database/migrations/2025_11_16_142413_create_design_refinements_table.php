<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('design_refinements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('design_id');
            $table->text('refinement_prompt');
            $table->text('previous_mockup_url')->nullable();
            $table->text('new_mockup_url')->nullable();
            $table->longText('new_mockup_base64')->nullable();
            $table->string('status')->default('generating');
            $table->json('ai_response_metadata')->nullable();
            $table->timestamps();

            $table->index(['design_id', 'status']);
            $table->index('created_at');

            $table->foreign('design_id')
                  ->references('id')
                  ->on('designs')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('design_refinements');
    }
};
