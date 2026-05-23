<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$cliente = DB::table('users')->where('identifier', 'cliente@construvasco.co.mz')->value('id');
$tecnico = DB::table('users')->where('identifier', 'tecnico@construvasco.co.mz')->value('id');
$admin = DB::table('users')->where('identifier', 'admin@construvasco.co.mz')->value('id');
$reqId = DB::table('project_requests')->value('id');

if (! $reqId) {
    echo "No project_requests\n";
    exit(1);
}

$projectId = DB::table('projects')->where('project_request_id', $reqId)->value('id');
if (! $projectId) {
    $projectId = DB::table('projects')->insertGetId([
        'project_request_id' => $reqId,
        'client_user_id' => $cliente,
        'tenant_id' => DB::table('tenants')->where('slug', 'construvasco')->value('id'),
        'name' => 'Smoke Test Project',
        'status' => 'active',
        'current_phase' => 'planning',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "Created project_id=$projectId\n";
} else {
    echo "Existing project_id=$projectId\n";
}

$exists = DB::table('project_assignments')
    ->where('project_id', $projectId)
    ->where('assigned_to', $tecnico)
    ->exists();
if (! $exists) {
    DB::table('project_assignments')->insert([
        'project_id' => $projectId,
        'assigned_by' => $admin,
        'assigned_to' => $tecnico,
        'assignment_role' => 'main',
        'status' => 'active',
        'assigned_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "Assigned technician to project\n";
} else {
    echo "Technician already assigned\n";
}
