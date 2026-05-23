<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo 'db=' . config('database.connections.mysql.database') . PHP_EOL;
echo 'users=' . App\Models\User::count() . PHP_EOL;
foreach (App\Models\User::orderBy('id')->pluck('identifier') as $id) {
    echo $id . PHP_EOL;
}
