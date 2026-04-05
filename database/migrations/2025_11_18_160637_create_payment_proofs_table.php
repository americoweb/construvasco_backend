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
        Schema::create('payment_proofs', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 10, 2);
            $table->string('reference')->nullable();
            $table->string('file_path');
            $table->string('bank_account')->nullable();
            $table->dateTime('transfer_date')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending_review', 'approved', 'rejected'])->default('pending_review');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->text('review_notes')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->timestamps();
            
            // Optional: Add foreign key if you have a users table for reviewers
            // $table->foreign('reviewed_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_proofs');
    }
};
