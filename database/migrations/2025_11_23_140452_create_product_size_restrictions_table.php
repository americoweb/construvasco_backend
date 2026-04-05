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
        Schema::create('product_size_restrictions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->decimal('min_width_cm', 8, 2)->nullable();
            $table->decimal('max_width_cm', 8, 2)->nullable();
            $table->decimal('min_height_cm', 8, 2)->nullable();
            $table->decimal('max_height_cm', 8, 2)->nullable();
            $table->decimal('min_aspect_ratio', 5, 2)->nullable();
            $table->decimal('max_aspect_ratio', 5, 2)->nullable();
            $table->decimal('step_increment_cm', 5, 2)->nullable();
            $table->timestamps();

            $table->foreign('product_id')
                  ->references('id')
                  ->on('products')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_size_restrictions');
    }
};
