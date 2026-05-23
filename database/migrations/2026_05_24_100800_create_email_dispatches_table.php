<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_dispatches', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 80);
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('related_entity_type')->nullable();
            $table->unsignedBigInteger('related_entity_id')->nullable();
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(
                ['event_type', 'related_entity_type', 'related_entity_id'],
                'email_dispatches_idempotent_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_dispatches');
    }
};
