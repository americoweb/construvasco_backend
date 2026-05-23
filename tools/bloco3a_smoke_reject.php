<?php

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
    $res = Http::post("{$api}/auth/login", ['identifier' => $email, 'password' => $password]);
    if (! $res->successful()) {
        throw new RuntimeException('Login failed: ' . $res->body());
    }
    return $res->json('access_token') ?? $res->json('data.token') ?? $res->json('token');
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

$quoteRes = Http::withToken($gestor)->post("{$api}/v1/manager/project-requests/{$pr->id}/quotes", [
    'total_amount_mt' => 50000,
    'delivery_days' => 20,
]);
$quoteId = $quoteRes->json('data.id');

$reject = Http::withToken($cliente)->post("{$api}/v1/customer/quotes/{$quoteId}/reject", [
    'reason' => 'Valor acima do orçamento disponível',
]);
if (! $reject->successful()) {
    echo "Reject failed: " . $reject->body() . "\n";
    exit(1);
}

$quote = Quote::find($quoteId);
$projectCount = Project::withoutGlobalScopes()->where('project_request_id', $pr->id)->count();
$acceptedEmail = DB::table('email_dispatches')->where('event_type', 'quote_accepted')
    ->where('related_entity_id', (string) $quoteId)->count();

$statusAfterReject = $pr->fresh()->status->value;

$second = Http::acceptJson()->withToken($gestor)->post("{$api}/v1/manager/project-requests/{$pr->id}/quotes", [
    'total_amount_mt' => 45000,
    'delivery_days' => 25,
]);

echo json_encode([
    'quote_status' => $quote->status->value,
    'rejection_reason' => $quote->rejection_reason,
    'request_status_after_reject' => $statusAfterReject,
    'project_created' => $projectCount === 0,
    'quote_accepted_email' => $acceptedEmail === 0,
    'second_quote_http' => $second->status(),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
