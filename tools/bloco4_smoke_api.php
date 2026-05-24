<?php

/**
 * Smoke API Bloco 4 — orçamento de obra, pagamento, conclusão + rejeição.
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Enums\ProjectContractPhase;
use App\Enums\ProjectPaymentPhase;
use App\Enums\ProjectPaymentStatus;
use App\Enums\QuoteType;
use App\Models\Construction\ProjectPayment;
use App\Models\Construction\Quote;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

$base = rtrim(env('APP_URL', 'http://127.0.0.1:8000'), '/');
$api = $base . '/api';

function login(string $api, string $email, string $password): string
{
    $res = Http::acceptJson()->post("{$api}/auth/login", [
        'identifier' => $email,
        'password' => $password,
    ]);
    if (! $res->successful()) {
        throw new RuntimeException("Login failed {$email}: " . $res->body());
    }

    return $res->json('access_token') ?? $res->json('token');
}

function ok(bool $cond, string $label): void
{
    echo ($cond ? '✅' : '❌') . " {$label}\n";
    if (! $cond) {
        exit(1);
    }
}

function resetProjectForConstructionSmoke(Project $project): void
{
    $archPay = ProjectPayment::where('project_id', $project->id)
        ->where('phase', ProjectPaymentPhase::Architecture)
        ->latest('id')
        ->first();
    if ($archPay && $archPay->status !== ProjectPaymentStatus::Confirmed) {
        $archPay->update(['status' => ProjectPaymentStatus::Confirmed, 'confirmed_at' => now()]);
    }
    if (! $project->architecture_completed_at) {
        $project->update(['architecture_completed_at' => now()]);
    }
    $project->update([
        'contract_phase' => ProjectContractPhase::Architecture,
        'construction_completed_at' => null,
        'construction_quote_requested_at' => null,
        'construction_request_notes' => null,
        'construction_quote_id' => null,
        'suggested_site_visit_date' => null,
    ]);
    Quote::where('project_request_id', $project->project_request_id)
        ->where('quote_type', QuoteType::Construction)
        ->delete();
    ProjectPayment::where('project_id', $project->id)
        ->where('phase', ProjectPaymentPhase::Construction)
        ->delete();
}

$gestorToken = login($api, 'gestor@construvasco.co.mz', 'Gestor@2026');
$clienteToken = login($api, 'cliente@construvasco.co.mz', 'Cliente@2026');
$gestor = static fn () => Http::acceptJson()->withToken($gestorToken);
$clienteHttp = static fn () => Http::acceptJson()->withToken($clienteToken);

$project = Project::withoutGlobalScopes()
    ->whereHas('projectRequest', fn ($q) => $q->where('reference_code', 'DEMO-PED-002'))
    ->latest('id')
    ->first();
ok((bool) $project, 'Projecto demo DEMO-PED-002 existe');
$projectId = $project->id;
$requestId = $project->project_request_id;

resetProjectForConstructionSmoke($project);
$project->refresh();

$archPay = ProjectPayment::where('project_id', $projectId)
    ->where('phase', ProjectPaymentPhase::Architecture)
    ->latest('id')
    ->first();
ok($archPay && $archPay->status === ProjectPaymentStatus::Confirmed, 'Pagamento arquitectura confirmed');

// --- Rejeição (antes do fluxo feliz) ---
$reqReject = $clienteHttp()->post("{$api}/v1/customer/projects/{$projectId}/request-construction-quote", [
    'notes' => 'Teste rejeição smoke',
]);
ok($reqReject->status() === 201, 'POST request-construction-quote (rejeição)');

$qReject = $gestor()->post("{$api}/v1/manager/project-requests/{$requestId}/quotes", [
    'quote_type' => 'construction',
    'total_amount_mt' => 500000,
    'delivery_days' => 90,
    'conditions' => 'Condições teste rejeição',
]);
ok($qReject->status() === 201, 'POST quote construction (rejeição)');
$quoteRejectId = $qReject->json('data.id');

$rej = $clienteHttp()->post("{$api}/v1/customer/quotes/{$quoteRejectId}/reject", [
    'reason' => 'Valor acima do orçamento previsto para rejeição smoke teste',
]);
ok($rej->successful(), 'POST reject quote construction');

$project->refresh();
ok($project->contract_phase === ProjectContractPhase::Closed, 'Projecto closed após rejeição');
ok(
    DB::table('email_dispatches')->where('event_type', 'quote_rejected')->where('related_entity_id', (string) $quoteRejectId)->exists(),
    'email quote_rejected (obra)'
);
echo "✅ Validação rejeição obra (project #{$projectId})\n";

// --- Fluxo feliz completo ---
resetProjectForConstructionSmoke($project->fresh());
$project->refresh();

$reqQuote = $clienteHttp()->post("{$api}/v1/customer/projects/{$projectId}/request-construction-quote", [
    'suggested_visit_date' => '2026-06-15',
    'notes' => 'Visita preferencialmente sábado de manhã',
]);
ok($reqQuote->status() === 201, 'POST request-construction-quote');

$project->refresh();
ok($project->contract_phase === ProjectContractPhase::ExecutionQuote, 'Fase execution_quote');
ok(
    DB::table('email_dispatches')->where('event_type', 'construction_quote_requested')->exists(),
    'email construction_quote_requested'
);

$sendQuote = $gestor()->post("{$api}/v1/manager/project-requests/{$requestId}/quotes", [
    'quote_type' => 'construction',
    'total_amount_mt' => 850000,
    'delivery_days' => 120,
    'conditions' => '30% sinal + 40% meio da obra + 30% entrega',
    'valid_until' => now()->addDays(30)->toDateString(),
]);
ok($sendQuote->status() === 201, 'POST quote construction');
$quoteId = $sendQuote->json('data.id');

ok(
    DB::table('email_dispatches')->where('event_type', 'quote_available')->where('related_entity_id', (string) $quoteId)->exists(),
    'email quote_available'
);

$accept = $clienteHttp()->post("{$api}/v1/customer/quotes/{$quoteId}/accept");
ok($accept->successful(), 'POST accept quote construction');

$project->refresh();
ok($project->contract_phase === ProjectContractPhase::Construction, 'Fase construction');
ok((int) $project->construction_quote_id === (int) $quoteId, 'construction_quote_id');

$constPay = ProjectPayment::where('project_id', $projectId)->where('phase', ProjectPaymentPhase::Construction)->first();
ok((bool) $constPay && $constPay->status === ProjectPaymentStatus::Pending, 'ProjectPayment construction pending');

$pdfPath = storage_path('app/smoke-test.pdf');
if (! is_file($pdfPath)) {
    file_put_contents($pdfPath, '%PDF-1.4 smoke 4');
}
$upload = $clienteHttp()->attach('file', file_get_contents($pdfPath), 'comprovativo-obra.pdf')
    ->post("{$api}/v1/customer/projects/{$projectId}/payments/{$constPay->id}/proof", ['notes' => 'Pagamento obra ref 888']);
ok($upload->successful(), 'POST proof construction');

$constPay->refresh();
ok($constPay->status === ProjectPaymentStatus::ProofSubmitted, 'Payment proof_submitted antes de confirmar');

$confirm = $gestor()->post("{$api}/v1/manager/projects/{$projectId}/payments/{$constPay->id}/confirm");
ok($confirm->successful(), 'POST confirm construction payment');

$complete = $gestor()->post("{$api}/v1/manager/projects/{$projectId}/mark-construction-completed");
ok($complete->successful(), 'POST mark-construction-completed');

$project->refresh();
ok($project->contract_phase === ProjectContractPhase::Completed, 'Fase completed');
ok($project->construction_completed_at !== null, 'construction_completed_at');

ok(
    DB::table('email_dispatches')->where('event_type', 'construction_completed')->where('related_entity_id', (string) $projectId)->exists(),
    'email construction_completed'
);

echo "\nBloco 4 API smoke OK (project #{$projectId})\n";
