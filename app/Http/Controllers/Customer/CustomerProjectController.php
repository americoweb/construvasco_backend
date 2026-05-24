<?php

namespace App\Http\Controllers\Customer;

use App\Enums\ProjectPaymentType;
use App\Http\Controllers\Concerns\LoadsProjectWithBriefing;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectDeliverableResource;
use App\Models\Construction\ProjectPayment;
use App\Models\Construction\ProjectDeliverable;
use App\Models\Project;
use App\Services\Construction\DeliverableService;
use App\Services\Storage\FileStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CustomerProjectController extends Controller
{
    use LoadsProjectWithBriefing;

    public function __construct(private DeliverableService $deliverables) {}

    public function index(Request $request): JsonResponse
    {
        $items = Project::where('client_user_id', $request->user()->id)
            ->with(['milestones', 'assignments.assignedUser'])
            ->latest()
            ->paginate(15);

        return response()->json($items);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $project = Project::where('client_user_id', $request->user()->id)
            ->findOrFail($id);

        $project = $this->projectWithBriefing($project->id);

        return response()->json(['data' => $this->projectPayload($project)]);
    }

    public function payFinal(Request $request, int $id): JsonResponse
    {
        $project = Project::where('client_user_id', $request->user()->id)->findOrFail($id);
        $amount = $project->target_budget ?? $project->budget ?? 0;

        $payment = ProjectPayment::create([
            'project_id' => $project->id,
            'user_id' => $request->user()->id,
            'type' => ProjectPaymentType::ProjectFinal,
            'provider' => 'mpesa',
            'reference' => 'PF-' . Str::upper(Str::random(10)),
            'amount' => $amount,
            'currency' => 'MZN',
            'status' => 'pending',
        ]);

        return response()->json(['data' => $payment], 201);
    }

    public function deliverables(Request $request, int $id): JsonResponse
    {
        $project = Project::where('client_user_id', $request->user()->id)->findOrFail($id);
        $items = $this->deliverables->listForCustomer($project);

        return response()->json(['data' => ProjectDeliverableResource::collection($items)]);
    }

    public function downloadDeliverable(
        Request $request,
        int $id,
        int $deliverableId,
        FileStorageService $storage,
    ) {
        $project = Project::where('client_user_id', $request->user()->id)->findOrFail($id);
        $deliverable = ProjectDeliverable::where('project_id', $project->id)->findOrFail($deliverableId);
        $this->deliverables->authorizeDownload($project, $deliverable, $request->user(), 'customer');

        return $storage->streamDownload(
            $deliverable->file_path,
            $deliverable->file_disk ?? config('filesystems.deliverables_disk', 'local'),
            $deliverable->original_name ?? $deliverable->title
        );
    }
}
