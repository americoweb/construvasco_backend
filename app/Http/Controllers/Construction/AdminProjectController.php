<?php

namespace App\Http\Controllers\Construction;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\Construction\ProjectFlowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminProjectController extends Controller
{
    public function __construct(
        protected ProjectFlowService $projectFlowService
    ) {}

    public function index(): JsonResponse
    {
        $user = auth('api')->user();
        abort_unless($user && $user->hasPermissionTo('projects.view'), 403);

        $projects = Project::query()->latest()->paginate(20);
        return response()->json($projects);
    }

    public function assign(Request $request, int $id): JsonResponse
    {
        $user = auth('api')->user();
        abort_unless($user && $user->hasPermissionTo('assignments.edit'), 403);

        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        $project = Project::findOrFail($id);
        $assignment = $this->projectFlowService->assignProject($project, (int) $validated['assigned_to'], $user->id);

        return response()->json(['data' => $assignment]);
    }
}
