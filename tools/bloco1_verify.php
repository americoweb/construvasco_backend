<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Enums\QuoteStatus;
use App\Enums\QuoteType;
use App\Models\AI\AiGeneration;
use App\Models\Construction\ProjectRequest;
use App\Models\Construction\Quote;
use App\Models\Mail\EmailDispatch;
use App\Models\Project;
use App\Models\User;
use App\Services\AI\BriefingPromptBuilder;
use App\Services\Construction\QuoteService;
use App\Services\Credits\CreditService;
use App\Services\Mail\EmailDispatcher;
use App\Mail\ProjectRequestSubmittedMail;
use Illuminate\Support\Facades\DB;

$ok = true;
function check(string $label, bool $pass): void
{
    global $ok;
    echo ($pass ? 'OK' : 'FAIL') . " — {$label}\n";
    if (! $pass) {
        $ok = false;
    }
}

echo "=== Bloco 1 verification ===\n\n";

$pending = shell_exec('php artisan migrate:status 2>&1');
check('migrate:status sem Pending', ! str_contains($pending, 'Pending'));

$request = ProjectRequest::where('reference_code', 'DEMO-PED-STUDIO')->first();
check('DemoFlowSeeder: pedido estúdio', $request !== null);
check('DemoFlowSeeder: mockup aprovado', $request?->approved_ai_generation_id !== null);
check('DemoFlowSeeder: projecto arquitectura', Project::withoutGlobalScopes()->where('contract_phase', 'architecture')->exists());

$builder = app(BriefingPromptBuilder::class);
$prompt = $builder->build($request ?? ProjectRequest::first());
check('BriefingPromptBuilder legível', str_contains($prompt, 'Moçambique') && str_contains($prompt, 'arquitect'));

$cliente = User::where('identifier', 'cliente@construvasco.co.mz')->first();
$admin = User::where('identifier', 'admin@construvasco.co.mz')->first();
$before = app(CreditService::class)->getBalance($cliente);
app(CreditService::class)->manualGrant($cliente, 3, 'Teste Bloco 1', $admin);
$after = app(CreditService::class)->getBalance($cliente);
$txn = DB::table('credit_transactions')
    ->where('user_id', $cliente->id)
    ->where('type', 'manual_grant')
    ->orderByDesc('id')
    ->first();
check('manualGrant +3 créditos', $after === $before + 3 && $txn !== null);

$demoReq = ProjectRequest::where('reference_code', 'DEMO-PED-001')->first();
if ($demoReq) {
    $quotes = QuoteService::class;
    $svc = app(QuoteService::class);
    try {
        $svc->assertCanCreateQuote($demoReq, QuoteType::Architecture);
        $fail = false;
    } catch (\Throwable $e) {
        $fail = true;
    }
    check('Segunda quote architecture sent bloqueada', $fail);
}

$gen1 = AiGeneration::create([
    'user_id' => $cliente->id,
    'project_request_id' => $request?->id,
    'type' => 'facade_render',
    'status' => 'completed',
    'provider' => 'gemini',
]);
$gen2 = AiGeneration::create([
    'user_id' => $cliente->id,
    'project_request_id' => $request?->id,
    'type' => 'facade_render',
    'status' => 'completed',
    'provider' => 'gemini',
]);
$request->update(['approved_ai_generation_id' => $gen1->id]);
DB::transaction(function () use ($request, $gen1, $gen2) {
    AiGeneration::where('id', $gen1->id)->update(['status' => 'superseded']);
    $request->update(['approved_ai_generation_id' => $gen2->id]);
});
$gen1->refresh();
check('AiGeneration anterior superseded', $gen1->status->value === 'superseded');

$dispatcher = app(EmailDispatcher::class);
EmailDispatch::where('event_type', 'bloco1_idempotency_test')->delete();
$countBefore = EmailDispatch::where('event_type', 'bloco1_idempotency_test')->count();
$first = $dispatcher->dispatchIdempotent(
    'bloco1_idempotency_test',
    $cliente,
    new ProjectRequestSubmittedMail($request),
    ProjectRequest::class,
    $request->id,
);
$second = $dispatcher->dispatchIdempotent(
    'bloco1_idempotency_test',
    $cliente,
    new ProjectRequestSubmittedMail($request),
    ProjectRequest::class,
    $request->id,
);
$countAfter = EmailDispatch::where('event_type', 'bloco1_idempotency_test')->count();
check('Email idempotência', $first && ! $second && $countAfter === $countBefore + 1);

$quote = Quote::first();
if ($quote) {
    activity('quotes')->performedOn($quote)->log('Verificação Bloco 1 — quote');
}
$activity = DB::table('activity_log')
    ->where(function ($q) {
        $q->where('subject_type', 'like', '%Quote%')
            ->orWhere('description', 'like', '%Mockup%')
            ->orWhere('description', 'like', '%Bloco 1%');
    })
    ->count();
check('Activity log tem registos', $activity > 0);

echo "\n" . ($ok ? "ALL BLOCO1 CHECKS PASSED\n" : "SOME CHECKS FAILED\n");
exit($ok ? 0 : 1);
