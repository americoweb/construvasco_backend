<?php

/** Prepara PR#6 como único rascunho activo para screenshots da galeria (smoke). */
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = \App\Models\User::where('identifier', 'cliente@construvasco.co.mz')->first();

\App\Models\Construction\ProjectRequest::where('user_id', $user->id)
    ->where('status', 'draft')
    ->where('id', '!=', 6)
    ->update(['status' => 'cancelled']);

$pr = \App\Models\Construction\ProjectRequest::find(6);
$pr->update(['status' => 'draft', 'submitted_at' => null]);
$pr->touch();

echo "PR#6 is now the active draft for studio UI\n";
