<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Concerns\LoadsProjectWithBriefing;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectDeliverableResource;
use App\Models\Construction\ProjectAssignment;
use App\Models\Construction\ProjectDeliverable;
use App\Models\Construction\ProjectMilestone;
use App\Models\Project;
use App\Services\Construction\DeliverableService;
use App\Services\Construction\ProjectFlowService;
use App\Services\Storage\FileStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TechnicianProjectController extends Controller
{
    use LoadsProjectWithBriefing;

    public function __construct(
        private ProjectFlowService $flow,
        private FileStorageService $storage,
        private DeliverableService $deliverables,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $ids = ProjectAssignment::where('assigned_to', $request->user()->id)
            ->where('status', 'active')
            ->pluck('project_id');

        $projects = Project::whereIn('id', $ids)
            ->with('milestones')
            ->latest()
            ->paginate(20);

        return response()->json($projects);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $this->ensureAssigned($request, $id);
        $project = $this->projectWithBriefing($id);

        return response()->json(['data' => $this->projectPayload($project)]);
    }

    public function listDeliverables(Request $request, int $id): JsonResponse
    {
        $this->ensureAssigned($request, $id);
        $project = Project::findOrFail($id);
        $items = $this->deliverables->listForTechnician($project, $request->user());

        return response()->json(['data' => ProjectDeliverableResource::collection($items)]);
    }

    public function storeDeliverable(Request $request, int $id): JsonResponse
    {
        $this->ensureAssigned($request, $id);
        $validated = $request->validate([
            'file' => 'required|file|max:51200|mimes:pdf,dwg,png,jpg,jpeg',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'milestone_id' => 'nullable|integer|exists:project_milestones,id',
        ]);

        $project = Project::findOrFail($id);
        $deliverable = $this->deliverables->upload(
            $project,
            $request->user(),
            $request->file('file'),
            $validated['title'],
            $validated['description'] ?? null,
            $validated['milestone_id'] ?? null,
        );

        return response()->json(['data' => new ProjectDeliverableResource($deliverable)], 201);
    }

    public function downloadDeliverable(Request $request, int $id, int $deliverableId)
    {
        $this->ensureAssigned($request, $id);
        $project = Project::findOrFail($id);
        $deliverable = ProjectDeliverable::where('project_id', $project->id)->findOrFail($deliverableId);
        $this->deliverables->authorizeDownload($project, $deliverable, $request->user(), 'technician');

        return $this->storage->streamDownload(
            $deliverable->file_path,
            $deliverable->file_disk ?? config('filesystems.deliverables_disk', 'local'),
            $deliverable->original_name ?? $deliverable->title
        );
    }

    public function updatePhase(Request $request, int $id, int $phaseId): JsonResponse
    {
        $this->ensureAssigned($request, $id);
        $milestone = ProjectMilestone::where('project_id', $id)->findOrFail($phaseId);
        $milestone->update($request->validate([
            'status' => 'required|in:pending,in_progress,completed,blocked',
            'description' => 'nullable|string',
        ]));

        if ($milestone->status === 'completed') {
            $milestone->update(['completed_at' => now()]);
        }

        return response()->json(['data' => $milestone->fresh()]);
    }

    /** @deprecated Use storeDeliverable — mantido para compatibilidade de rota antiga */
    public function uploadDeliverable(Request $request, int $id, int $phaseId): JsonResponse
    {
        $request->merge(['milestone_id' => $phaseId]);

        return $this->storeDeliverable($request, $id);
    }

    public function destroyDeliverable(Request $request, int $deliverableId): JsonResponse
    {
        $deliverable = ProjectDeliverable::findOrFail($deliverableId);
        abort_unless($deliverable->uploaded_by === $request->user()->id, 403);

        $this->storage->deleteFile($deliverable->file_path, $deliverable->file_disk ?? 'local');
        $deliverable->delete();

        return response()->json(['message' => 'Removido.']);
    }

    public function submitForReview(Request $request, int $id): JsonResponse
    {
        $this->ensureAssigned($request, $id);
        $project = Project::findOrFail($id);
        $project->update(['status' => 'in_review', 'current_phase' => 'in_review']);

        return response()->json(['data' => $project]);
    }

    private function ensureAssigned(Request $request, int $projectId): void
    {
        $ok = ProjectAssignment::where('project_id', $projectId)
            ->where('assigned_to', $request->user()->id)
            ->where('status', 'active')
            ->exists();

        abort_unless($ok, 403);
    }
}
