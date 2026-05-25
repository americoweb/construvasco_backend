<?php

/**
 * Validação B — tipos MySQL dos campos de estado.
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$columns = [
    'quotes' => ['status', 'quote_type'],
    'projects' => ['contract_phase'],
    'project_requests' => ['status'],
    'project_payments' => ['status', 'phase'],
    'ai_generations' => ['status'],
];

echo "=== Validação B: tipos de coluna (MySQL) ===\n\n";
echo str_pad('Tabela.coluna', 40) . str_pad('Tipo MySQL', 30) . "Enum values\n";
echo str_repeat('-', 90) . "\n";

$db = DB::connection()->getDatabaseName();

foreach ($columns as $table => $cols) {
    foreach ($cols as $col) {
        $row = DB::selectOne(
            'SELECT COLUMN_TYPE, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$db, $table, $col]
        );
        if (! $row) {
            echo str_pad("{$table}.{$col}", 40) . "MISSING\n";
            continue;
        }
        $type = $row->COLUMN_TYPE;
        $isEnum = str_starts_with(strtolower($type), 'enum');
        $label = $isEnum ? 'ENUM MySQL' : strtoupper($row->DATA_TYPE) . ' (só PHP)';
        echo str_pad("{$table}.{$col}", 40) . str_pad($label, 30) . $type . "\n";
    }
}

echo "\nNota: VARCHAR/STRING aceita qualquer texto; ENUM restringe no MySQL.\n";
