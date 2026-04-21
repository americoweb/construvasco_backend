<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_card_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_card_id')->constrained('job_cards')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();

            $table->text('comment');
            $table->string('role'); // comercial | designer | client
            $table->unsignedTinyInteger('version')->default(1); // design iteration version
            $table->boolean('is_approved')->default(false); // marks approval feedback

            $table->timestamps();

            $table->index(['job_card_id', 'version']);
            $table->index('job_card_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_card_feedback');
    }
};
