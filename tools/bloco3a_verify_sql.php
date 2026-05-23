<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Construction\Quote;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

$quote = Quote::where('project_request_id', 6)->latest('id')->first();
$project = Project::withoutGlobalScopes()->where('project_request_id', 6)->latest('id')->first();

echo json_encode([
    'quote_status' => $quote?->status?->value ?? $quote?->status,
    'quote_type' => $quote?->quote_type?->value ?? $quote?->quote_type,
    'project_contract_phase' => $project?->contract_phase?->value ?? $project?->contract_phase,
    'email_quote_available' => DB::table('email_dispatches')->where('event_type', 'quote_available')->where('related_entity_id', (string) ($quote?->id))->count(),
    'email_quote_accepted' => DB::table('email_dispatches')->where('event_type', 'quote_accepted')->where('related_entity_id', (string) ($quote?->id))->count(),
    'activity_quotes' => DB::table('activity_log')->where('log_name', 'quotes')->orderByDesc('id')->limit(5)->pluck('description'),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
