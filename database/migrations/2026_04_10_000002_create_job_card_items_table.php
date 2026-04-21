<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_card_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_card_id')->constrained('job_cards')->cascadeOnDelete();

            $table->string('product_type');   // cartaz, t-shirt, flyer, banner, etc.
            $table->unsignedInteger('quantity')->default(1);
            $table->string('size')->nullable(); // ex: A4, 90x60cm, M
            $table->string('material')->nullable(); // vinil, papel couché, algodão...
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_card_items');
    }
};
