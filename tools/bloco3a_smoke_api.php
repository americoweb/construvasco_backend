<?php

/**
 * Smoke API Bloco 3A (sem browser).
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Construction\Quote;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

$base = rtrim(env('APP_URL', 'http://127.0.0.1:8000'), '/');
$api = $base . '/api';

function login(string $api, string $email, string $password): string
{
    $res = Http::post("{$api}/auth/login", [
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

Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\UserSeeder', '--force' => true]);
include __DIR__ . '/bloco3a_prep_pr6.php';

$gestorToken = login($api, 'gestor@construvasco.co.mz', 'Gestor@2026');
$clienteToken = login($api, 'cliente@construvasco.co.mz', 'Cliente@2026');

$http = fn () => Http::acceptJson()->withToken($gestorToken);

$show = $http()->get("{$api}/v1/manager/project-requests/6");
ok($show->successful(), 'GET manager project-request #6');
$body = $show->json('data');
ok(isset($body['client']['email']), 'Payload inclui cliente');
ok(isset($body['approved_ai_generation']) || $body['approved_ai_generation_id'] === null, 'Mockup no payload');

$quoteRes = $http()->post("{$api}/v1/manager/project-requests/6/quotes", [
    'quote_type' => 'architecture',
    'total_amount_mt' => 75000,
    'delivery_days' => 30,
    'conditions' => '50% sinal, 50% entrega',
]);
ok($quoteRes->status() === 201, 'POST quote architecture');
$quoteId = $quoteRes->json('data.id');

ok(
    DB::table('email_dispatches')->where('event_type', 'quote_available')
        ->where('related_entity_id', (string) $quoteId)->exists(),
    'email quote_available'
);
ok(
    DB::table('activity_log')->where('log_name', 'quotes')->where('description', 'like', '%enviado%')->exists(),
    'activity log envio'
);

$sentCount = Quote::where('project_request_id', 6)->where('status', 'sent')->count();
ok($sentCount === 1, "Existe 1 quote sent (tem {$sentCount})");

$http()->post("{$api}/v1/manager/project-requests/6/quotes", [
    'total_amount_mt' => 80000,
    'delivery_days' => 20,
]);
$sentAfterDup = Quote::where('project_request_id', 6)->where('status', 'sent')->count();
ok($sentAfterDup === 1, 'Segundo quote sent bloqueado (só 1 sent na BD)');

$accept = Http::acceptJson()->withToken($clienteToken)->post("{$api}/v1/customer/quotes/{$quoteId}/accept");
ok($accept->successful(), 'POST accept quote');

$quote = Quote::find($quoteId);
$project = Project::withoutGlobalScopes()->where('project_request_id', 6)->first();
ok($quote->status->value === 'accepted', 'Quote status accepted');
ok($project && ($project->contract_phase->value ?? $project->contract_phase) === 'architecture', 'Project architecture phase');
ok(
    DB::table('email_dispatches')->where('event_type', 'quote_accepted')
        ->where('related_entity_id', (string) $quoteId)->exists(),
    'email quote_accepted'
);

echo "\nOK Bloco 3A API smoke\n";
