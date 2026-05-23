<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo 'project_requests=' . App\Models\Construction\ProjectRequest::count() . PHP_EOL;
echo 'projects=' . App\Models\Project::count() . PHP_EOL;
App\Models\Construction\ProjectRequest::select('id','reference_code')->get()->each(fn($r)=>print("  req {$r->id} {$r->reference_code}\n"));
App\Models\Project::select('id','title')->get()->each(fn($p)=>print("  proj {$p->id} {$p->title}\n"));
