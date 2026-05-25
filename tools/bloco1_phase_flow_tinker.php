<?php

/**
 * Validação A — ciclo completo das 3 fases contratuais (sem UI).
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Enums\ProjectContractPhase;
use App\Enums\ProjectRequestStatus;
use App\Enums\QuoteStatus;
use App\Enums\QuoteType;
use App\Models\Construction\ProjectRequest;
use App\Models\Construction\Quote;
use App\Models\Project;
use App\Models\User;
use App\Services\Construction\QuoteService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

session(['tenant_id' => 1]);

$ok = true;
function step(string $label, bool $pass, ?string $detail = null): void
{
    global $ok;
    echo ($pass ? 'OK' : 'FAIL') . " — {$label}";
    if ($detail) {
        echo " ({$detail})";
    }
    echo "\n";
    if (! $pass) {
        $ok = false;
    }
}

echo "=== Validação A: fluxo 3 fases (Tinker) ===\n\n";

$cliente = User::where('identifier', 'cliente@construvasco.co.mz')->firstOrFail();
$gestor = User::where('identifier', 'gestor@construvasco.co.mz')->firstOrFail();
$quotes = app(QuoteService::class);

// --- Setup: pedido limpo para o teste ---
$ref = 'TEST-FASES-' . now()->format('His');
$request = ProjectRequest::create([
    'user_id' => $cliente->id,
    'reference_code' => $ref,
    'title' => 'Teste fluxo 3 fases',
    'description' => 'Validação A Bloco 1',
    'project_type' => 'residencial',
    'localizacao' => 'Maputo',
    'status' => ProjectRequestStatus::Submitted,
    'submitted_at' => now(),
]);

$archQuote = Quote::create([
    'project_request_id' => $request->id,
    'quote_type' => QuoteType::Architecture,
    'created_by_user_id' => $gestor->id,
    'total_amount_mt' => 500000,
    'delivery_days' => 60,
    'conditions' => 'Teste arquitectura',
    'status' => QuoteStatus::Sent,
    'sent_at' => now(),
]);

// 1) Aceitar quote architecture
$project = $quotes->accept($archQuote->fresh(), $cliente);
$project->refresh();
step(
    '1. Aceitar quote architecture → projecto em architecture',
    $project->contract_phase === ProjectContractPhase::Architecture
    && $project->quote_id === $archQuote->id,
    "phase={$project->contract_phase->value}, quote_id={$project->quote_id}"
);

// 2) Marcar arquitectura concluída + criar quote construction
$project->update([
    'architecture_completed_at' => now(),
    'contract_phase' => ProjectContractPhase::ExecutionQuote,
]);
$project->refresh();

try {
    $quotes->assertCanCreateQuote($request->fresh(), QuoteType::Construction);
    $blocked = false;
} catch (\Throwable $e) {
    $blocked = true;
    $blockMsg = $e->getMessage();
}
step(
    '2a. Com architecture_completed_at → pode criar quote construction',
    ! $blocked,
    $blocked ? ($blockMsg ?? '') : ''
);

$constQuote = Quote::create([
    'project_request_id' => $request->id,
    'quote_type' => QuoteType::Construction,
    'created_by_user_id' => $gestor->id,
    'total_amount_mt' => 1200000,
    'delivery_days' => 90,
    'conditions' => 'Teste obra',
    'status' => QuoteStatus::Sent,
    'sent_at' => now(),
]);
$request->update(['status' => ProjectRequestStatus::ExecutionQuoteRequested]);
step('2b. Quote construction sent criada', $constQuote->exists);

// 3) Aceitar quote construction
$projectId = $project->id;
$project = $quotes->accept($constQuote->fresh(), $cliente);
$project->refresh();
step(
    '3. Aceitar quote construction → fase construction + construction_quote_id',
    $project->id === $projectId
    && $project->contract_phase === ProjectContractPhase::Construction
    && (int) $project->construction_quote_id === (int) $constQuote->id,
    "phase={$project->contract_phase->value}, construction_quote_id={$project->construction_quote_id}"
);

// 4) Rejeitar quote construction num pedido separado
$ref2 = 'TEST-FASES-REJ-' . now()->format('His');
$request2 = ProjectRequest::create([
    'user_id' => $cliente->id,
    'reference_code' => $ref2,
    'title' => 'Teste rejeição obra',
    'status' => ProjectRequestStatus::Submitted,
    'submitted_at' => now(),
]);
$arch2 = Quote::create([
    'project_request_id' => $request2->id,
    'quote_type' => QuoteType::Architecture,
    'created_by_user_id' => $gestor->id,
    'total_amount_mt' => 300000,
    'status' => QuoteStatus::Sent,
    'sent_at' => now(),
]);
$proj2 = $quotes->accept($arch2, $cliente);
$proj2->update([
    'architecture_completed_at' => now(),
    'contract_phase' => ProjectContractPhase::ExecutionQuote,
]);
$const2 = Quote::create([
    'project_request_id' => $request2->id,
    'quote_type' => QuoteType::Construction,
    'created_by_user_id' => $gestor->id,
    'total_amount_mt' => 400000,
    'status' => QuoteStatus::Sent,
    'sent_at' => now(),
]);
$quotes->reject($const2->fresh(), $cliente, 'Cliente recusou orçamento de obra');
$proj2->refresh();
$request2->refresh();
step(
    '4. Rejeitar quote construction → project closed',
    $proj2->contract_phase === ProjectContractPhase::Closed
    && $request2->status === ProjectRequestStatus::Closed,
    "phase={$proj2->contract_phase->value}, request={$request2->status->value}"
);

// Cleanup test rows (optional — keep for inspection if FAIL)
if ($ok) {
    Quote::whereIn('project_request_id', [$request->id, $request2->id])->delete();
    Project::withoutGlobalScopes()->whereIn('project_request_id', [$request->id, $request2->id])->forceDelete();
    $request->delete();
    $request2->delete();
    echo "\nDados de teste removidos.\n";
}

echo "\n" . ($ok ? "VALIDAÇÃO A: PASSED\n" : "VALIDAÇÃO A: FAILED\n");
exit($ok ? 0 : 1);
