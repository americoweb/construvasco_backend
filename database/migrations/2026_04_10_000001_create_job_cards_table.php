<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_cards', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('job_number', 12)->unique(); // e.g. JC-2026-0001

            // People
            $table->foreignId('client_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_designer_id')->nullable()->constrained('users')->nullOnDelete();

            // Status workflow: draft → briefing → design → revision → approval → production → done | cancelled
            $table->string('status')->default('draft');

            // Brief info
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('objective')->nullable(); // promoção, branding, evento...

            // Scheduling
            $table->dateTime('deadline');

            // Priority system
            $table->string('priority')->default('medium'); // low | medium | high
            $table->boolean('priority_override')->default(false);
            $table->string('priority_reason')->nullable();
            $table->integer('priority_score')->default(0); // computed: recalculated on save

            // Client tier (influences auto-weight in score)
            $table->string('client_tier')->default('normal'); // vip | normal | new

            // Revision control
            $table->unsignedTinyInteger('revision_limit')->default(2);
            $table->unsignedTinyInteger('revision_count')->default(0);

            // Link to order after approval → production
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for ordered list queries
            $table->index(['status', 'priority_score']);
            $table->index(['status', 'deadline']);
            $table->index('client_id');
            $table->index('assigned_designer_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_cards');
    }
};
