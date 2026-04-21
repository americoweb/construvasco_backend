<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\JobCard\JobCardService;
use App\Http\Requests\JobCard\CreateJobCardRequest;
use App\Http\Requests\JobCard\UpdateJobCardRequest;
use App\Http\Requests\JobCard\UpdateJobCardStatusRequest;
use App\Http\Requests\JobCard\AddFeedbackRequest;
use App\Http\Resources\JobCard\JobCardResource;
use App\Http\Resources\JobCard\JobCardListResource;
use App\Http\Resources\JobCard\JobCardFeedbackResource;
use App\Enums\JobCard\JobCardStatus;
use App\Enums\JobCard\JobCardPriority;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class JobCardController extends Controller
{
    public function __construct(
        protected JobCardService $jobCardService
    ) {}

    /** GET /admin/job-cards — paginated list, sorted by priority_score */
    public function index(Request $request): JsonResponse
    {
        $jobCards = $this->jobCardService->list(
            $request->all(),
            $request->get('per_page', 15)
        );

        return response()->json([
            'data' => JobCardListResource::collection($jobCards->items()),
            'meta' => [
                'current_page' => $jobCards->currentPage(),
                'last_page'    => $jobCards->lastPage(),
                'per_page'     => $jobCards->perPage(),
                'total'        => $jobCards->total(),
            ]
        ]);
    }

    /** GET /admin/job-cards/kanban — board grouped by status */
    public function kanban(): JsonResponse
    {
        $board = $this->jobCardService->getKanbanBoard();

        $formatted = [];
        foreach ($board as $statusValue => $items) {
            $formatted[$statusValue] = JobCardListResource::collection($items);
        }

        return response()->json(['data' => $formatted]);
    }

    /** POST /admin/job-cards */
    public function store(CreateJobCardRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $items = $validated['items'] ?? [];
        unset($validated['items']);

        // Set created_by from authenticated user
        $validated['created_by'] = auth()->id();

        $jobCard = $this->jobCardService->create($validated, $items);

        return response()->json([
            'data'    => new JobCardResource($jobCard),
            'message' => 'Job Card criado com sucesso'
        ], 201);
    }

    /** GET /admin/job-cards/{id} */
    public function show(int $id): JsonResponse
    {
        $jobCard = $this->jobCardService->getWithRelations($id);

        return response()->json(['data' => new JobCardResource($jobCard)]);
    }

    /** PUT /admin/job-cards/{id} */
    public function update(UpdateJobCardRequest $request, int $id): JsonResponse
    {
        $jobCard = $this->jobCardService->update($id, $request->validated());

        return response()->json([
            'data'    => new JobCardResource($jobCard),
            'message' => 'Job Card actualizado com sucesso'
        ]);
    }

    /** DELETE /admin/job-cards/{id} */
    public function destroy(int $id): JsonResponse
    {
        $this->jobCardService->delete($id);

        return response()->json(['message' => 'Job Card removido com sucesso']);
    }

    /** PATCH /admin/job-cards/{id}/status */
    public function updateStatus(UpdateJobCardStatusRequest $request, int $id): JsonResponse
    {
        $status  = JobCardStatus::from($request->status);
        $jobCard = $this->jobCardService->updateStatus(
            $id,
            $status,
            $request->notes,
            auth()->id()
        );

        return response()->json([
            'data'    => new JobCardResource($jobCard),
            'message' => 'Status actualizado com sucesso'
        ]);
    }

    /** POST /admin/job-cards/{id}/cancel */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $jobCard = $this->jobCardService->cancel($id, $request->get('reason'), auth()->id());

        return response()->json([
            'data'    => new JobCardResource($jobCard),
            'message' => 'Job Card cancelado com sucesso'
        ]);
    }

    /** PATCH /admin/job-cards/{id}/priority */
    public function updatePriority(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'priority'          => 'required|in:' . implode(',', array_column(JobCardPriority::cases(), 'value')),
            'priority_override' => 'nullable|boolean',
            'priority_reason'   => 'nullable|required_if:priority_override,true|string|max:500',
        ]);

        $jobCard = $this->jobCardService->updatePriority(
            $id,
            JobCardPriority::from($request->priority),
            (bool) $request->get('priority_override', false),
            $request->get('priority_reason')
        );

        return response()->json([
            'data'    => new JobCardResource($jobCard),
            'message' => 'Prioridade actualizada com sucesso'
        ]);
    }

    /** DELETE /admin/job-cards/{id}/priority-override */
    public function removeOverride(int $id): JsonResponse
    {
        $jobCard = $this->jobCardService->removeOverride($id);

        return response()->json([
            'data'    => new JobCardResource($jobCard),
            'message' => 'Override de prioridade removido'
        ]);
    }

    /** POST /admin/job-cards/{id}/assign-designer */
    public function assignDesigner(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'designer_id' => 'required|integer|exists:users,id',
        ]);

        $jobCard = $this->jobCardService->assignDesigner($id, $request->designer_id);

        return response()->json([
            'data'    => new JobCardResource($jobCard),
            'message' => 'Designer atribuído com sucesso'
        ]);
    }

    /** POST /admin/job-cards/{id}/feedback */
    public function addFeedback(AddFeedbackRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();

        $feedback = $this->jobCardService->addFeedback($id, $data);
        $feedback->load('author');

        return response()->json([
            'data'    => new JobCardFeedbackResource($feedback),
            'message' => 'Feedback adicionado com sucesso'
        ], 201);
    }

    /** POST /admin/job-cards/{id}/link-order */
    public function linkOrder(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
        ]);

        $jobCard = $this->jobCardService->linkOrder($id, $request->order_id);

        return response()->json([
            'data'    => new JobCardResource($jobCard),
            'message' => 'Ordem vinculada com sucesso'
        ]);
    }
}
