<?php

use Illuminate\Database\Migrations\Migration;

/**
 * project_deliverables.status é varchar — valores submitted_for_review, approved, rejected.
 * Migração documental (sem alteração de schema).
 */
return new class extends Migration
{
    public function up(): void
    {
        // submitted_for_review | approved | rejected
    }

    public function down(): void
    {
        //
    }
};
