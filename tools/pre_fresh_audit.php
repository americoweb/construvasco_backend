<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo 'users: ' . DB::table('users')->count() . PHP_EOL;
echo 'project_requests: ' . DB::table('project_requests')->count() . PHP_EOL;
echo 'projects: ' . DB::table('projects')->count() . PHP_EOL;
echo "--- users ---" . PHP_EOL;
foreach (DB::table('users')->orderBy('id')->get(['id', 'identifier', 'name']) as $u) {
    echo "{$u->id} {$u->identifier} {$u->name}" . PHP_EOL;
}
