<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Keep transitional commercial tables active for Construvasco flow.
        // This migration is intentionally a no-op to prevent accidental data loss.
    }

    public function down(): void
    {
        // Legacy print domain removal is irreversible in this migration chain.
    }
};
