<?php

/**
 * Prepara projecto #6 para smoke browser Bloco 3B.
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Enums\ProjectContractPhase;
use App\Models\Construction\ProjectDeliverable;
use App\Models\Project;
use App\Models\User;

$project = Project::withoutGlobalScopes()->find(6);
if (! $project) {
    echo "Project #6 not found\n";
    exit(1);
}

$tecnico = User::where('identifier', 'tecnico@construvasco.co.mz')->firstOrFail();

ProjectDeliverable::where('project_id', $project->id)->delete();
$project->update([
    'contract_phase' => ProjectContractPhase::Architecture,
    'architecture_completed_at' => null,
    'current_phase' => 'architecture',
]);

\App\Models\Construction\ProjectAssignment::updateOrCreate(
    ['project_id' => $project->id, 'assigned_to' => $tecnico->id],
    ['assigned_by' => 1, 'assignment_role' => 'main', 'status' => 'active']
);

echo "Project #6 ready for 3B browser smoke\n";
