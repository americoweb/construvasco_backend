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
        Schema::table('products', function (Blueprint $table) {
            $table->string('pricing_type')->default('fixed')->after('price');
            $table->decimal('price_per_sqm', 10, 2)->nullable()->after('pricing_type');
            $table->boolean('has_sizes')->default(false)->after('price_per_sqm');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['pricing_type', 'price_per_sqm', 'has_sizes']);
        });
    }
};
