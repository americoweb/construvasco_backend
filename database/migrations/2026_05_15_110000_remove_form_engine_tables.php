<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('form_submissions');
        Schema::dropIfExists('form_instances');
        Schema::dropIfExists('form_template_versions');
        Schema::dropIfExists('form_templates');
    }

    public function down(): void
    {
        // Irreversible — form engine removed from Construvasco.
    }
};
