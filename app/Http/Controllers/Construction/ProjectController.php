<?php

namespace App\Http\Controllers\Construction;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\Construction\ProjectFlowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct(
        protected ProjectFlowService $projectFlowService
    ) {}

    public function store(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        abort_unless($user && $user->hasPermissionTo('projects.create'), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_type' => 'nullable|string|max:120',
            'location' => 'nullable|string|max:255',
            'target_budget' => 'nullable|numeric',
            'desired_deadline' => 'nullable|date',
            'service_category_id' => 'nullable|exists:service_categories,id',
            'construction_service_id' => 'nullable|exists:construction_services,id',
        ]);

        $project = $this->projectFlowService->createProject($validated + [
            'tenant_id' => session('tenant_id'),
            'client_user_id' => $user->id,
            'status' => 'new',
            'current_phase' => 'new',
        ]);

        return response()->json(['data' => $project], 201);
    }

    public function myProjects(): JsonResponse
    {
        $user = auth('api')->user();
        abort_unless($user && $user->hasPermissionTo('projects.view'), 403);

        $projects = Project::query()
            ->where('client_user_id', $user->id)
            ->latest()
            ->get();

        return response()->json(['data' => $projects]);
    }
}
