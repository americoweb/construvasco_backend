<?php

/**
 * Smoke API Bloco 3C — pagamento arquitectura (upload, confirmação, gating download).
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Enums\DeliverableStatus;
use App\Enums\ProjectContractPhase;
use App\Enums\ProjectPaymentPhase;
use App\Enums\ProjectPaymentStatus;
use App\Models\Construction\ProjectDeliverable;
use App\Models\Construction\ProjectPayment;
use App\Models\Project;
use App\Models\User;
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

    return $res->json('access_token') ?? $res->json('data.token') ?? $res->json('token');
}

function ok(bool $cond, string $label): void
{
    echo ($cond ? '✅' : '❌') . " {$label}\n";
    if (! $cond) {
        exit(1);
    }
}

$gestorToken = login($api, 'gestor@construvasco.co.mz', 'Gestor@2026');
$tecnicoToken = login($api, 'tecnico@construvasco.co.mz', 'Tecnico@2026');
$clienteToken = login($api, 'cliente@construvasco.co.mz', 'Cliente@2026');

$gestor = static fn () => Http::acceptJson()->withToken($gestorToken);
$tecnicoHttp = static fn () => Http::acceptJson()->withToken($tecnicoToken);
$clienteHttp = static fn () => Http::acceptJson()->withToken($clienteToken);

$cliente = User::where('identifier', 'cliente@construvasco.co.mz')->firstOrFail();

$project = Project::withoutGlobalScopes()
    ->where('id', 6)
    ->where('client_user_id', $cliente->id)
    ->first();

if (! $project) {
    $project = Project::withoutGlobalScopes()
        ->where('contract_phase', ProjectContractPhase::Architecture)
        ->where('client_user_id', $cliente->id)
        ->latest('id')
        ->first();
}

ok((bool) $project, 'Projecto de teste encontrado');
$projectId = $project->id;

$payment = ProjectPayment::where('project_id', $projectId)
    ->where('phase', ProjectPaymentPhase::Architecture)
    ->latest('id')
    ->first();

if (! $payment) {
    $quoteAmount = $project->target_budget ?? 100000;
    $payment = ProjectPayment::create([
        'project_id' => $projectId,
        'user_id' => $cliente->id,
        'type' => 'architecture',
        'phase' => ProjectPaymentPhase::Architecture,
        'provider' => 'manual',
        'reference' => 'SMOKE3C-' . $projectId,
        'amount' => $quoteAmount,
        'currency' => 'MZN',
        'status' => ProjectPaymentStatus::Pending,
    ]);
}

$payment->update([
    'status' => ProjectPaymentStatus::Pending,
    'proof_path' => null,
    'proof_uploaded_at' => null,
    'confirmed_by' => null,
    'confirmed_at' => null,
    'rejected_reason' => null,
    'notes' => null,
]);

ok(
    (float) $payment->amount > 0,
    "ProjectPayment pending valor {$payment->amount} MT"
);

$showCust = $clienteHttp()->get("{$api}/v1/customer/projects/{$projectId}");
ok($showCust->successful(), 'GET customer project com payment');
$payPayload = $showCust->json('data.payment');
ok((bool) ($payPayload['id'] ?? null), 'Payload inclui payment.id');
ok(($payPayload['status'] ?? '') === 'pending', 'Payment status pending no payload');

$deliverable = ProjectDeliverable::where('project_id', $projectId)
    ->where('status', DeliverableStatus::Approved->value)
    ->latest('id')
    ->first();

$pdfPath = storage_path('app/smoke-test.pdf');
if (! is_file($pdfPath)) {
    file_put_contents($pdfPath, '%PDF-1.4 smoke 3c payment proof');
}

if (! $deliverable) {
    $tecnico = User::where('identifier', 'tecnico@construvasco.co.mz')->firstOrFail();
    $gestor()->post("{$api}/v1/manager/projects/{$projectId}/assign", [
        'assigned_to' => $tecnico->id,
        'assignment_role' => 'main',
    ]);
    $uploadDel = $tecnicoHttp()->attach(
        'file',
        file_get_contents($pdfPath),
        'planta-smoke-3c.pdf'
    )->post("{$api}/v1/technician/projects/{$projectId}/deliverables", [
        'title' => 'Planta smoke 3C',
    ]);
    if ($uploadDel->successful()) {
        $delId = $uploadDel->json('data.id');
        $gestor()->post("{$api}/v1/manager/projects/{$projectId}/deliverables/{$delId}/approve");
        $deliverable = ProjectDeliverable::find($delId);
    }
}

if ($deliverable) {
    $dlBlocked = $clienteHttp()->get(
        "{$api}/v1/customer/projects/{$projectId}/deliverables/{$deliverable->id}/download"
    );
    ok($dlBlocked->status() === 403, 'Download cliente bloqueado sem pagamento confirmado');
} else {
    echo "⚠️  Sem entregável aprovado — a saltar testes de download\n";
}

$upload = $clienteHttp()->attach(
    'file',
    file_get_contents($pdfPath),
    'comprovativo-m-pesa.pdf'
)->post("{$api}/v1/customer/projects/{$projectId}/payments/{$payment->id}/proof", [
    'notes' => 'Pagamento M-Pesa ref 999',
]);
ok(
    in_array($upload->status(), [200, 201], true) && is_array($upload->json('data')),
    'POST upload comprovativo cliente (' . $upload->status() . ')'
);

$payment->refresh();
ok($payment->status === ProjectPaymentStatus::ProofSubmitted, 'Payment proof_submitted na BD');

ok(
    DB::table('email_dispatches')->where('event_type', 'payment_proof_submitted')->exists(),
    'email payment_proof_submitted para gestor'
);

$showMgr = $gestor()->get("{$api}/v1/manager/projects/{$projectId}");
ok($showMgr->successful(), 'GET manager project');
ok(($showMgr->json('data.payment.status') ?? '') === 'proof_submitted', 'Gestor vê proof_submitted');

$proofDl = $gestor()->get(
    "{$api}/v1/manager/projects/{$projectId}/payments/{$payment->id}/proof/download"
);
ok($proofDl->successful() && strlen($proofDl->body()) > 10, 'GET download comprovativo gestor');

$confirm = $gestor()->post(
    "{$api}/v1/manager/projects/{$projectId}/payments/{$payment->id}/confirm"
);
ok($confirm->successful(), 'POST confirmar pagamento');

$payment->refresh();
ok($payment->status === ProjectPaymentStatus::Confirmed, 'Payment confirmed na BD');

ok(
    DB::table('email_dispatches')->where('event_type', 'payment_confirmed')
        ->where('related_entity_id', (string) $payment->id)
        ->exists(),
    'email payment_confirmed para cliente'
);

$showCust2 = $clienteHttp()->get("{$api}/v1/customer/projects/{$projectId}");
ok(($showCust2->json('data.payment.status') ?? '') === 'confirmed', 'Cliente vê payment confirmed');

if ($deliverable) {
    $dlOk = $clienteHttp()->get(
        "{$api}/v1/customer/projects/{$projectId}/deliverables/{$deliverable->id}/download"
    );
    ok($dlOk->successful() && strlen($dlOk->body()) > 10, 'Download cliente após confirmação');
}

$pendingList = $gestor()->get("{$api}/v1/manager/payments/pending");
ok($pendingList->successful(), 'GET manager payments/pending');

$dash = $gestor()->get("{$api}/v1/manager/dashboard");
ok($dash->successful(), 'GET manager dashboard');
ok(array_key_exists('pending_payments_count', $dash->json('data') ?? []), 'Dashboard tem pending_payments_count');

// Fluxo rejeição (segundo pagamento ou reset)
$payment2 = ProjectPayment::create([
    'project_id' => $projectId,
    'user_id' => $cliente->id,
    'type' => 'architecture',
    'phase' => ProjectPaymentPhase::Architecture,
    'provider' => 'manual',
    'reference' => 'SMOKE3C-REJ-' . time(),
    'amount' => 50000,
    'currency' => 'MZN',
    'status' => ProjectPaymentStatus::Pending,
]);

$upload2 = $clienteHttp()->attach(
    'file',
    file_get_contents($pdfPath),
    'comprovativo-rejeitar.pdf'
)->post("{$api}/v1/customer/projects/{$projectId}/payments/{$payment2->id}/proof", [
    'notes' => 'Teste rejeição',
]);
ok($upload2->successful(), 'POST comprovativo para teste rejeição');

$reject = $gestor()->post(
    "{$api}/v1/manager/projects/{$projectId}/payments/{$payment2->id}/reject",
    ['rejection_reason' => 'Comprovativo ilegível, refaça scan']
);
ok($reject->successful(), 'POST rejeitar comprovativo');

$payment2->refresh();
ok($payment2->status === ProjectPaymentStatus::Rejected, 'Payment rejected na BD');
ok(strlen((string) $payment2->rejected_reason) >= 10, 'Motivo rejeição guardado');

$upload3 = $clienteHttp()->attach(
    'file',
    file_get_contents($pdfPath),
    'comprovativo-resubmit.pdf'
)->post("{$api}/v1/customer/projects/{$projectId}/payments/{$payment2->id}/proof", [
    'notes' => 'Novo scan legível',
]);
ok($upload3->successful(), 'Cliente resubmete após rejeição');

$payment2->refresh();
ok($payment2->status === ProjectPaymentStatus::ProofSubmitted, 'Estado volta a proof_submitted');

ok(
    DB::table('email_dispatches')->where('event_type', 'payment_rejected')
        ->where('related_entity_id', (string) $payment2->id)
        ->exists(),
    'email payment_rejected para cliente'
);

// Limpar pagamento de teste extra (manter pagamento principal confirmado)
$payment2->delete();

echo "\nBloco 3C API smoke OK (project #{$projectId}, payment principal #{$payment->id})\n";
