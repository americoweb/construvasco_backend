<?php

use Illuminate\Database\Migrations\Migration;

/**
 * ProjectRequest.status é varchar — novos valores são suportados via App\Enums\ProjectRequestStatus.
 * Migração reservada para alinhar histórico de migrações do Bloco 1.
 */
return new class extends Migration
{
    public function up(): void
    {
        // execution_quote_requested | closed — sem alteração de schema
    }

    public function down(): void
    {
        //
    }
};
