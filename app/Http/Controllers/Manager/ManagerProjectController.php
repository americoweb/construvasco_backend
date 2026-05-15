<?php

namespace App\Http\Controllers\Manager;

use App\Constants\NotificationTypes;
use App\Http\Controllers\Controller;
use App\Models\Construction\ProjectAssignment;
use App\Models\Construction\ProjectMilestone;
use App\Models\Project;
use App\Services\Construction\ProjectFlowService;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ManagerProjectController extends Controller
{
    public function __construct(
        private ProjectFlowService $flow,
        private NotificationService $notifications
    ) {}

    public function index(): JsonResponse
    {
        $projects = Project::with(['client', 'milestones', 'assignments.assignedUser'])
            ->latest()
            ->paginate(30);

        return response()->json($projects);
    }

    public function show(int $id): JsonResponse
    {
        $project = Project::with(['milestones', 'deliverables', 'assignments.assignedUser', 'client'])
            ->findOrFail($id);

        return response()->json(['data' => $project]);
    }

    public function assign(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id',
            'assignment_role' => 'nullable|in:main,collaborator',
        ]);

        $project = Project::findOrFail($id);
        $assignment = $this->flow->assignProject($project, (int) $validated['assigned_to'], $request->user()->id);
        $assignment->update(['assignment_role' => $validated['assignment_role'] ?? 'main']);

        return response()->json(['data' => $assignment]);
    }

    public function addPhase(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
        ]);

        $project = Project::findOrFail($id);
        $position = $project->milestones()->max('order_position') + 1;

        $milestone = ProjectMilestone::create(array_merge($validated, [
            'project_id' => $project->id,
            'status' => 'pending',
            'sort_order' => $position,
            'order_position' => $position,
        ]));

        return response()->json(['data' => $milestone], 201);
    }

    public function approveDeliverables(int $id): JsonResponse
    {
        $project = Project::with('client')->findOrFail($id);
        $project->update(['status' => 'awaiting_payment', 'current_phase' => 'awaiting_payment']);

        if ($project->client) {
            $this->notifications->notify($project->client, NotificationTypes::PROJECT_READY_FOR_PAYMENT, [
                'title' => 'Projecto pronto para pagamento',
                'message' => 'Os entregáveis foram aprovados. Efectue o pagamento final.',
            ]);
        }

        return response()->json(['data' => $project->fresh()]);
    }

    public function rejectDeliverables(Request $request, int $id): JsonResponse
    {
        $project = Project::findOrFail($id);
        $project->update(['status' => 'revision', 'current_phase' => 'revision']);

        return response()->json([
            'data' => $project,
            'feedback' => $request->input('feedback'),
        ]);
    }
}
