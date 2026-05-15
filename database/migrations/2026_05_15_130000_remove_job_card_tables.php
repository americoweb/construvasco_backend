<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Job card tables are dropped in 2026_05_15_120000_remove_amazing_commerce_tables.
     * Kept as no-op for migration history consistency.
     */
    public function up(): void
    {
        Schema::dropIfExists('job_card_feedback');
        Schema::dropIfExists('job_card_files');
        Schema::dropIfExists('job_card_items');
        Schema::dropIfExists('job_cards');
    }

    public function down(): void
    {
        //
    }
};
