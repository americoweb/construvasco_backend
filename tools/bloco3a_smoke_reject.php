<?php

/**
 * Validação flow recusa: quote_rejected + email quote_rejected + reenvio orçamento.
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Enums\ProjectRequestStatus;
use App\Models\Construction\ProjectRequest;
use App\Models\Construction\Quote;
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
        throw new RuntimeException('Login failed: ' . $res->body());
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

$gestor = login($api, 'gestor@construvasco.co.mz', 'Gestor@2026');
$cliente = login($api, 'cliente@construvasco.co.mz', 'Cliente@2026');
$clienteUser = User::where('identifier', 'cliente@construvasco.co.mz')->first();

$pr = ProjectRequest::create([
    'user_id' => $clienteUser->id,
    'reference_code' => 'CV-TEST-REJECT-' . time(),
    'title' => 'Teste recusa 3A',
    'status' => ProjectRequestStatus::Submitted,
    'submitted_at' => now(),
    'project_type' => 'residencial',
    'tipologia' => 't3',
]);

$quoteRes = Http::acceptJson()->withToken($gestor)->post("{$api}/v1/manager/project-requests/{$pr->id}/quotes", [
    'total_amount_mt' => 50000,
    'delivery_days' => 20,
]);
ok($quoteRes->successful(), 'Gestor envia 1.º orçamento');
$quoteId = $quoteRes->json('data.id');

$emailsBefore = DB::table('email_dispatches')->where('event_type', 'quote_rejected')->count();

$reject = Http::acceptJson()->withToken($cliente)->post("{$api}/v1/customer/quotes/{$quoteId}/reject", [
    'reason' => 'Valor acima do orçamento previsto',
]);
ok($reject->successful(), 'Cliente recusa orçamento');

$quote = Quote::find($quoteId);
$pr->refresh();

ok($quote->status->value === 'rejected', 'Quote status rejected');
ok($quote->rejection_reason === 'Valor acima do orçamento previsto', 'rejection_reason registado');
ok($pr->status === ProjectRequestStatus::QuoteRejected, 'Pedido em quote_rejected');
ok(
    Project::withoutGlobalScopes()->where('project_request_id', $pr->id)->count() === 0,
    'Sem projecto criado'
);
ok(
    DB::table('email_dispatches')->where('event_type', 'quote_accepted')
        ->where('related_entity_id', (string) $quoteId)->count() === 0,
    'Sem email quote_accepted'
);
ok(
    DB::table('email_dispatches')->where('event_type', 'quote_rejected')
        ->where('related_entity_id', (string) $quoteId)->exists(),
    'email quote_rejected para gestor'
);
ok(
    DB::table('email_dispatches')->where('event_type', 'quote_rejected')->count() > $emailsBefore,
    'Nova entrada quote_rejected na BD'
);

$second = Http::acceptJson()->withToken($gestor)->post("{$api}/v1/manager/project-requests/{$pr->id}/quotes", [
    'total_amount_mt' => 45000,
    'delivery_days' => 25,
]);
ok($second->status() === 201, 'Gestor envia 2.º orçamento após recusa');
ok($pr->fresh()->status === ProjectRequestStatus::Quoted, 'Pedido transita para quoted');

echo "\nOK Bloco 3A reject flow\n";
