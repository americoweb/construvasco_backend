<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('project_estimates');
    }

    public function down(): void
    {
        // Replaced by quotes table.
    }
};
