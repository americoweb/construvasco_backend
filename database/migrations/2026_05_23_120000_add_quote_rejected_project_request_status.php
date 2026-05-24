<?php

use Illuminate\Database\Migrations\Migration;

/**
 * ProjectRequest.status é varchar — o valor quote_rejected é suportado via App\Enums\ProjectRequestStatus::QuoteRejected.
 * Migração documental (sem alteração de schema).
 */
return new class extends Migration
{
    public function up(): void
    {
        // quote_rejected — distingue pedido com orçamento recusado a aguardar acção do gestor
    }

    public function down(): void
    {
        //
    }
};
