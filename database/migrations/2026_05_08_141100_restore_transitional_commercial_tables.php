<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->decimal('price', 10, 2);
                $table->string('pricing_type')->default('fixed');
                $table->decimal('price_per_sqm', 10, 2)->nullable();
                $table->boolean('has_sizes')->default(false);
                $table->integer('min_quantity')->default(1);
                $table->string('image_url', 500)->nullable();
                $table->string('base_image_url', 500)->nullable();
                $table->text('design_hint')->nullable();
                $table->string('status')->default('active');
                $table->boolean('is_featured')->default(false);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('image_url', 500)->nullable();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('parent_id')->references('id')->on('categories')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('tags')) {
            Schema::create('tags', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('color', 7)->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('product_colors')) {
            Schema::create('product_colors', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_id');
                $table->string('name', 100);
                $table->string('hex_code', 7);
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->integer('stock_quantity')->nullable();
                $table->timestamps();

                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('product_print_areas')) {
            Schema::create('product_print_areas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_id');
                $table->string('name', 100);
                $table->string('position');
                $table->text('description')->nullable();
                $table->decimal('max_width_cm', 8, 2)->nullable();
                $table->decimal('max_height_cm', 8, 2)->nullable();
                $table->decimal('additional_price', 10, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('product_sizes')) {
            Schema::create('product_sizes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_id');
                $table->string('name', 100);
                $table->decimal('width_cm', 8, 2)->nullable();
                $table->decimal('height_cm', 8, 2)->nullable();
                $table->boolean('is_predefined')->default(false);
                $table->boolean('is_custom')->default(false);
                $table->decimal('fixed_price', 10, 2)->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('product_size_restrictions')) {
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

                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('category_product')) {
            Schema::create('category_product', function (Blueprint $table) {
                $table->id();
                $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
                $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
                $table->timestamps();
                $table->unique(['category_id', 'product_id']);
            });
        }

        if (!Schema::hasTable('product_tag')) {
            Schema::create('product_tag', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
                $table->foreignId('tag_id')->constrained('tags')->onDelete('cascade');
                $table->timestamps();
                $table->unique(['product_id', 'tag_id']);
            });
        }

        if (!Schema::hasTable('designs')) {
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

                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
                $table->foreign('product_color_id')->references('id')->on('product_colors')->onDelete('cascade');
                $table->foreign('product_print_area_id')->references('id')->on('product_print_areas')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('design_refinements')) {
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

                $table->foreign('design_id')->references('id')->on('designs')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('carts')) {
            Schema::create('carts', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('session_id', 191)->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('cart_items')) {
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

                $table->foreign('cart_id')->references('id')->on('carts')->onDelete('cascade');
                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
                $table->foreign('design_id')->references('id')->on('designs')->onDelete('set null');
                $table->foreign('product_color_id')->references('id')->on('product_colors')->onDelete('cascade');
                $table->foreign('product_print_area_id')->references('id')->on('product_print_areas')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('testimonials')) {
            Schema::create('testimonials', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
                $table->string('photo_url', 500)->nullable();
                $table->text('text');
                $table->string('author');
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        // Intentionally keep restored tables.
    }
};

