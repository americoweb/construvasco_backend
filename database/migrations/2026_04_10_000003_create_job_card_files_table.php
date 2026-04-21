<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_card_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_card_id')->constrained('job_cards')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();

            // briefing | reference | design | preview | final
            $table->string('type');

            $table->string('file_name');
            $table->string('file_url');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable(); // bytes
            $table->unsignedTinyInteger('version')->default(1);
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['job_card_id', 'type']);
            $table->index(['job_card_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_card_files');
    }
};
