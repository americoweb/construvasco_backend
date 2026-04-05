<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('cart_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('design_id')->nullable();
            $table->unsignedBigInteger('product_color_id');
            $table->unsignedBigInteger('product_print_area_id');
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_price', 12, 2);
            $table->text('design_prompt')->nullable();
            $table->text('mockup_url')->nullable();
            $table->timestamps();

            $table->index('cart_id');
            $table->index(['cart_id', 'product_id']);

            $table->foreign('cart_id')
                  ->references('id')
                  ->on('carts')
                  ->onDelete('cascade');

            $table->foreign('product_id')
                  ->references('id')
                  ->on('products')
                  ->onDelete('cascade');

            $table->foreign('design_id')
                  ->references('id')
                  ->on('designs')
                  ->onDelete('set null');

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
        Schema::dropIfExists('cart_items');
    }
};
