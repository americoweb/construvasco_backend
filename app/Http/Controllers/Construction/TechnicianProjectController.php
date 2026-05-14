<?php

namespace App\Http\Controllers\Construction;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\Construction\ProjectFlowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TechnicianProjectController extends Controller
{
    public function __construct(
        protected ProjectFlowService $projectFlowService
    ) {}

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $user = auth('api')->user();
        abort_unless($user && $user->hasPermissionTo('projects.edit'), 403);

        $validated = $request->validate([
            'status' => 'required|string|max:60',
        ]);

        $project = Project::findOrFail($id);
        $updated = $this->projectFlowService->updateProjectStatus($project, $validated['status']);

        return response()->json(['data' => $updated]);
    }

    public function addDeliverable(Request $request, int $id): JsonResponse
    {
        $user = auth('api')->user();
        abort_unless($user && $user->hasPermissionTo('projects.edit'), 403);

        $validated = $request->validate([
            'deliverable_type' => 'required|string|max:120',
            'title' => 'required|string|max:255',
            'file_path' => 'required|string|max:500',
            'file_format' => 'nullable|string|max:50',
            'version' => 'nullable|string|max:30',
        ]);

        $project = Project::findOrFail($id);
        $deliverable = $this->projectFlowService->addDeliverable($project, $validated + [
            'uploaded_by' => $user->id,
            'status' => 'submitted',
            'version' => $validated['version'] ?? 'v1',
        ]);

        return response()->json(['data' => $deliverable], 201);
    }
}
