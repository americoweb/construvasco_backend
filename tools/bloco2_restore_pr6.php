<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$pr = \App\Models\Construction\ProjectRequest::find(6);
$pr->update([
    'status' => 'submitted',
    'submitted_at' => $pr->submitted_at ?? now(),
]);
echo "PR#6 restored to submitted\n";
