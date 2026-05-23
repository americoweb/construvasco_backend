<?php

/**
 * Reset pedido #6 para smoke Bloco 3A (quotes + projecto de teste).
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Enums\ProjectRequestStatus;
use App\Models\Construction\ProjectRequest;
use App\Models\Construction\Quote;
use App\Models\Project;

$pr = ProjectRequest::find(6);
if (! $pr) {
    echo "PR#6 not found\n";
    exit(1);
}

Project::withoutGlobalScopes()->where('project_request_id', 6)->delete();
Quote::where('project_request_id', 6)->delete();

$pr->update([
    'status' => ProjectRequestStatus::Submitted,
    'converted_project_id' => null,
]);

echo "PR#6 reset: status=submitted, quotes cleared, projects cleared\n";
