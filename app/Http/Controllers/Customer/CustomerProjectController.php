<?php

namespace App\Http\Controllers\Customer;

use App\Enums\ProjectPaymentType;
use App\Http\Controllers\Controller;
use App\Models\Construction\ProjectPayment;
use App\Models\Construction\ProjectDeliverable;
use App\Models\Project;
use App\Services\Storage\FileStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CustomerProjectController extends Controller
{
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
            ->with(['milestones', 'deliverables', 'quote'])
            ->findOrFail($id);

        return response()->json(['data' => $project]);
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
        $project = Project::where('client_user_id', $request->user()->id)
            ->with('deliverables')
            ->findOrFail($id);

        if (!$project->client_can_download) {
            return response()->json([
                'message' => 'Pagamento pendente. Conclua o pagamento para descarregar.',
                'preview_only' => true,
                'data' => $project->deliverables->map(fn ($d) => [
                    'id' => $d->id,
                    'title' => $d->title,
                    'deliverable_type' => $d->deliverable_type,
                    'status' => $d->status,
                ]),
            ]);
        }

        return response()->json(['data' => $project->deliverables]);
    }

    public function downloadDeliverable(Request $request, int $id, int $deliverableId, FileStorageService $storage)
    {
        $project = Project::where('client_user_id', $request->user()->id)->findOrFail($id);
        abort_unless($project->client_can_download, 403, 'Pagamento pendente.');

        $deliverable = ProjectDeliverable::where('project_id', $project->id)->findOrFail($deliverableId);

        return $storage->streamDownload(
            $deliverable->file_path,
            $deliverable->file_disk ?? config('filesystems.deliverables_disk', 'local'),
            $deliverable->original_name ?? $deliverable->title
        );
    }
}
