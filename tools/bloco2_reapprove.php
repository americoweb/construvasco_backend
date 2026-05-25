<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = \App\Models\User::where('identifier', 'cliente@construvasco.co.mz')->firstOrFail();
$request = \Illuminate\Http\Request::create('/api/v1/customer/project-requests/6/approve-ai-generation/12', 'POST');
$request->setUserResolver(fn () => $user);

app(\App\Http\Controllers\Customer\CustomerProjectRequestController::class)
    ->approveAiGeneration($request, 6, 12);

echo "approve called\n";
