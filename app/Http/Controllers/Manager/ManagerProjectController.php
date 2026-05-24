<?php

namespace App\Http\Controllers\Manager;

use App\Constants\NotificationTypes;
use App\Enums\DeliverableStatus;
use App\Http\Controllers\Concerns\LoadsProjectWithBriefing;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectDeliverableResource;
use App\Models\Construction\ProjectAssignment;
use App\Models\Construction\ProjectDeliverable;
use App\Models\Construction\ProjectMilestone;
use App\Models\Project;
use App\Services\Construction\DeliverableService;
use App\Services\Construction\ProjectFlowService;
use App\Services\Notifications\NotificationService;
use App\Services\Storage\FileStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ManagerProjectController extends Controller
{
    use LoadsProjectWithBriefing;

    public function __construct(
        private ProjectFlowService $flow,
        private NotificationService $notifications,
        private DeliverableService $deliverables,
        private FileStorageService $storage,
    ) {}

    public function assignableUsers(): JsonResponse
    {
        $users = \App\Models\User::role(['technician', 'project_manager'], 'api')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'identifier']);

        return response()->json(['data' => $users]);
    }

    public function index(): JsonResponse
    {
        $projects = Project::with(['client', 'milestones', 'assignments.assignedUser'])
            ->withCount([
                'deliverables as pending_review_count' => fn ($q) => $q->whereIn(
                    'status',
                    DeliverableStatus::pendingReviewValues()
                ),
            ])
            ->latest()
            ->paginate(30);

        return response()->json($projects);
    }

    public function show(int $id): JsonResponse
    {
        $project = $this->projectWithBriefing($id);

        return response()->json(['data' => $this->projectPayload($project)]);
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

    public function listDeliverables(int $id): JsonResponse
    {
        $project = Project::findOrFail($id);
        $items = $this->deliverables->listForManager($project);

        return response()->json(['data' => ProjectDeliverableResource::collection($items)]);
    }

    public function approveDeliverable(int $id, int $deliverableId, Request $request): JsonResponse
    {
        $project = Project::findOrFail($id);
        $deliverable = ProjectDeliverable::where('project_id', $project->id)->findOrFail($deliverableId);
        $deliverable = $this->deliverables->approve($project, $deliverable, $request->user());

        return response()->json(['data' => new ProjectDeliverableResource($deliverable)]);
    }

    public function rejectDeliverable(Request $request, int $id, int $deliverableId): JsonResponse
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|min:10|max:2000',
        ]);

        $project = Project::findOrFail($id);
        $deliverable = ProjectDeliverable::where('project_id', $project->id)->findOrFail($deliverableId);
        $deliverable = $this->deliverables->reject(
            $project,
            $deliverable,
            $request->user(),
            $validated['rejection_reason'],
        );

        return response()->json(['data' => new ProjectDeliverableResource($deliverable)]);
    }

    public function downloadDeliverable(Request $request, int $id, int $deliverableId)
    {
        $project = Project::findOrFail($id);
        $deliverable = ProjectDeliverable::where('project_id', $project->id)->findOrFail($deliverableId);
        $role = $request->user()->getRoleNames()->first() ?? 'project_manager';
        $this->deliverables->authorizeDownload($project, $deliverable, $request->user(), $role);

        return $this->storage->streamDownload(
            $deliverable->file_path,
            $deliverable->file_disk ?? config('filesystems.deliverables_disk', 'local'),
            $deliverable->original_name ?? $deliverable->title
        );
    }

    public function markArchitectureDelivered(Request $request, int $id): JsonResponse
    {
        $project = Project::findOrFail($id);
        $project = $this->deliverables->markArchitectureDelivered($project, $request->user());

        return response()->json(['data' => $project]);
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

    /** @deprecated Bloco 3B usa aprovação por entregável */
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

    /** @deprecated Bloco 3B usa rejeição por entregável */
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
