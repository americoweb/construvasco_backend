<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = \App\Models\User::where('identifier', 'cliente@construvasco.co.mz')->first();
$request = \Illuminate\Http\Request::create('/api/v1/customer/project-requests/6', 'GET');
$request->setUserResolver(fn () => $user);

$response = app(\App\Http\Controllers\Customer\CustomerProjectRequestController::class)->show($request, 6);
$data = json_decode($response->getContent(), true)['data'];
echo json_encode([
    'approved_id' => $data['approved_ai_generation_id'] ?? null,
    'relation_keys' => array_keys($data),
    'approved_gen' => $data['approved_ai_generation'] ?? $data['approvedAiGeneration'] ?? null,
], JSON_PRETTY_PRINT);
