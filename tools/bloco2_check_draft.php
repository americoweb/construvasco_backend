<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = \App\Models\User::where('identifier', 'cliente@construvasco.co.mz')->first();
$draft = \App\Models\Construction\ProjectRequest::where('user_id', $user->id)
    ->where('status', 'draft')
    ->latest('updated_at')
    ->first();

echo $draft ? "draft#{$draft->id} gens=" . $draft->aiGenerations()->count() . "\n" : "no draft\n";

$pr6 = \App\Models\Construction\ProjectRequest::find(6);
echo "PR6 gens=" . $pr6->aiGenerations()->count() . " status={$pr6->status->value}\n";
