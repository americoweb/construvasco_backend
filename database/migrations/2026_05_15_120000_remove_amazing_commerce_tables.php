<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Job cards (FK to orders)
        Schema::dropIfExists('job_card_feedback');
        Schema::dropIfExists('job_card_files');
        Schema::dropIfExists('job_card_items');
        Schema::dropIfExists('job_cards');

        // Cart / checkout (FK to designs, products)
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');

        // Orders
        Schema::dropIfExists('order_status_history');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');

        // Designs
        Schema::dropIfExists('design_refinements');
        Schema::dropIfExists('designs');

        // Product catalog
        Schema::dropIfExists('testimonials');
        Schema::dropIfExists('product_tag');
        Schema::dropIfExists('category_product');
        Schema::dropIfExists('product_size_restrictions');
        Schema::dropIfExists('product_sizes');
        Schema::dropIfExists('product_print_areas');
        Schema::dropIfExists('product_colors');
        Schema::dropIfExists('products');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('categories');
    }

    public function down(): void
    {
        // Irreversible — Amazing commerce domain removed.
    }
};
