<?php

namespace App\Http\Controllers\Manager;

use App\Enums\ProjectPaymentPhase;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectPaymentResource;
use App\Models\Construction\ProjectPayment;
use App\Models\Project;
use App\Services\Construction\PaymentService;
use App\Services\Storage\FileStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ManagerPaymentController extends Controller
{
    public function __construct(
        private PaymentService $payments,
        private FileStorageService $storage,
    ) {}

    public function pending(): JsonResponse
    {
        $items = $this->payments->listPendingForManager();

        return response()->json([
            'data' => ProjectPaymentResource::collection($items),
        ]);
    }

    public function confirm(Request $request, int $projectId, int $paymentId): JsonResponse
    {
        $project = Project::findOrFail($projectId);
        $payment = $this->findPayment($project, $paymentId);
        $payment = $this->payments->confirm($project, $payment, $request->user());

        return response()->json(['data' => new ProjectPaymentResource($payment)]);
    }

    public function reject(Request $request, int $projectId, int $paymentId): JsonResponse
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|min:10|max:2000',
        ]);

        $project = Project::findOrFail($projectId);
        $payment = $this->findPayment($project, $paymentId);
        $payment = $this->payments->reject(
            $project,
            $payment,
            $request->user(),
            $validated['rejection_reason'],
        );

        return response()->json(['data' => new ProjectPaymentResource($payment)]);
    }

    public function downloadProof(int $projectId, int $paymentId)
    {
        $project = Project::findOrFail($projectId);
        $payment = $this->findPayment($project, $paymentId);
        abort_unless($payment->proof_path, 404, 'Comprovativo não encontrado.');

        return $this->storage->streamDownload(
            $payment->proof_path,
            config('filesystems.default', 'local'),
            basename($payment->proof_path)
        );
    }

    private function findPayment(Project $project, int $paymentId): ProjectPayment
    {
        return ProjectPayment::where('project_id', $project->id)->findOrFail($paymentId);
    }
}
