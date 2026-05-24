<?php

/**
 * Smoke API Bloco 3B (sem browser).
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Enums\ProjectContractPhase;
use App\Models\Construction\ProjectAssignment;
use App\Models\Construction\ProjectDeliverable;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

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

$tecnico = User::where('identifier', 'tecnico@construvasco.co.mz')->firstOrFail();
$cliente = User::where('identifier', 'cliente@construvasco.co.mz')->firstOrFail();

$project = Project::withoutGlobalScopes()
    ->where('contract_phase', ProjectContractPhase::Architecture)
    ->whereNull('architecture_completed_at')
    ->where('client_user_id', $cliente->id)
    ->latest('id')
    ->first();

if (! $project) {
    $project = Project::withoutGlobalScopes()
        ->where('contract_phase', ProjectContractPhase::Architecture)
        ->whereNull('architecture_completed_at')
        ->latest('id')
        ->first();
}

ok((bool) $project, 'Projecto em fase architecture encontrado');
$projectId = $project->id;

ProjectDeliverable::where('project_id', $projectId)->delete();
$project->update(['architecture_completed_at' => null]);

$gestor = fn () => Http::acceptJson()->withToken($gestorToken);
$tecnicoHttp = fn () => Http::acceptJson()->withToken($tecnicoToken);
$clienteHttp = fn () => Http::acceptJson()->withToken($clienteToken);

$assign = $gestor()->post("{$api}/v1/manager/projects/{$projectId}/assign", [
    'assigned_to' => $tecnico->id,
    'assignment_role' => 'main',
]);
ok($assign->successful(), 'POST assign técnico');

$pdfPath = storage_path('app/smoke-test.pdf');
if (! is_file($pdfPath)) {
  file_put_contents($pdfPath, str_repeat('%PDF-1.4 smoke test ', 50000));
}
$upload = $tecnicoHttp()->attach(
    'file',
    file_get_contents($pdfPath),
    'planta-piso-0.pdf'
)->post("{$api}/v1/technician/projects/{$projectId}/deliverables", [
    'title' => 'Planta piso 0',
    'description' => 'Smoke 3B',
]);
ok($upload->status() === 201 || $upload->successful(), 'POST upload entregável');
$deliverableId = $upload->json('data.id');
ok((bool) $deliverableId, 'Deliverable criado');

$listTech = $tecnicoHttp()->get("{$api}/v1/technician/projects/{$projectId}/deliverables");
ok($listTech->successful() && count($listTech->json('data')) >= 1, 'GET deliverables técnico');

$listMgr = $gestor()->get("{$api}/v1/manager/projects/{$projectId}/deliverables");
ok($listMgr->successful(), 'GET deliverables gestor');

$listCustBefore = $clienteHttp()->get("{$api}/v1/customer/projects/{$projectId}/deliverables");
ok($listCustBefore->successful() && count($listCustBefore->json('data')) === 0, 'Cliente não vê antes de aprovar');

$approve = $gestor()->post("{$api}/v1/manager/projects/{$projectId}/deliverables/{$deliverableId}/approve");
ok($approve->successful(), 'POST approve');

ok(
    DB::table('email_dispatches')->where('event_type', 'deliverable_available')
        ->where('related_entity_id', (string) $deliverableId)->exists(),
    'email deliverable_available'
);

$listCust = $clienteHttp()->get("{$api}/v1/customer/projects/{$projectId}/deliverables");
ok(count($listCust->json('data')) === 1, 'Cliente vê 1 aprovado');

$dl = $clienteHttp()->get("{$api}/v1/customer/projects/{$projectId}/deliverables/{$deliverableId}/download");
ok($dl->successful() && strlen($dl->body()) > 100, 'GET download cliente');

$mark = $gestor()->post("{$api}/v1/manager/projects/{$projectId}/mark-architecture-delivered");
ok($mark->successful(), 'POST mark architecture delivered');

$project->refresh();
ok($project->architecture_completed_at !== null, 'architecture_completed_at preenchido');

ok(
    DB::table('email_dispatches')->where('event_type', 'architecture_completed')
        ->where('related_entity_id', (string) $projectId)->exists(),
    'email architecture_completed'
);

// Rejeição
$upload2 = $tecnicoHttp()->attach(
    'file',
    file_get_contents($pdfPath),
    'planta-cobertura.pdf'
)->post("{$api}/v1/technician/projects/{$projectId}/deliverables", [
    'title' => 'Planta cobertura',
]);
$del2 = $upload2->json('data.id');
$reject = $gestor()->post("{$api}/v1/manager/projects/{$projectId}/deliverables/{$del2}/reject", [
    'rejection_reason' => 'Falta planta de cobertura detalhada',
]);
ok($reject->successful(), 'POST reject segundo entregável');

$showTech = $tecnicoHttp()->get("{$api}/v1/technician/projects/{$projectId}");
$deliverables = collect($showTech->json('data.deliverables') ?? []);
$rejected = $deliverables->firstWhere('id', $del2);
ok(($rejected['rejection_reason'] ?? '') !== '', 'Técnico vê motivo rejeição');

$listCust2 = $clienteHttp()->get("{$api}/v1/customer/projects/{$projectId}/deliverables");
ok(count($listCust2->json('data')) === 1, 'Cliente continua só com 1 aprovado');

echo "\nBloco 3B API smoke OK (project #{$projectId})\n";
