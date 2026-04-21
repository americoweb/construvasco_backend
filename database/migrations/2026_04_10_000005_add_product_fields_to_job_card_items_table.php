<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_card_items', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->after('job_card_id');
            $table->unsignedBigInteger('product_color_id')->nullable()->after('product_id');
            $table->unsignedBigInteger('product_size_id')->nullable()->after('product_color_id');

            $table->foreign('product_id')
                ->references('id')->on('products')
                ->nullOnDelete();

            $table->foreign('product_color_id')
                ->references('id')->on('product_colors')
                ->nullOnDelete();

            $table->foreign('product_size_id')
                ->references('id')->on('product_sizes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('job_card_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['product_color_id']);
            $table->dropForeign(['product_size_id']);
            $table->dropColumn(['product_id', 'product_color_id', 'product_size_id']);
        });
    }
};
