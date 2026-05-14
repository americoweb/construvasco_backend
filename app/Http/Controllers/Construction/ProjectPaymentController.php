<?php

namespace App\Http\Controllers\Construction;

use App\Http\Controllers\Controller;
use App\Models\Construction\ProjectPayment;
use App\Models\Project;
use App\Services\Construction\ProjectFlowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProjectPaymentController extends Controller
{
    public function __construct(
        protected ProjectFlowService $projectFlowService
    ) {}

    public function store(Request $request, int $id): JsonResponse
    {
        $user = auth('api')->user();
        abort_unless($user && $user->hasPermissionTo('payments.create'), 403);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'provider' => 'nullable|string|max:50',
            'currency' => 'nullable|string|max:3',
            'reference' => 'nullable|string|max:120',
        ]);

        $project = Project::findOrFail($id);
        $payment = $this->projectFlowService->createPayment($project, [
            'provider' => $validated['provider'] ?? 'manual',
            'reference' => $validated['reference'] ?? ('PRJ-' . Str::upper(Str::random(10))),
            'amount' => $validated['amount'],
            'currency' => strtoupper($validated['currency'] ?? 'MZN'),
            'status' => 'pending',
            'metadata' => ['created_via' => 'projects_endpoint'],
        ]);

        return response()->json(['data' => $payment], 201);
    }

    public function webhook(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reference' => 'required|string',
            'status' => 'required|string',
            'transaction_id' => 'nullable|string',
        ]);

        $payment = ProjectPayment::query()->where('reference', $validated['reference'])->first();

        if (!$payment) {
            return response()->json(['message' => 'Payment reference not found'], 404);
        }

        $payment->update([
            'status' => strtolower($validated['status']),
            'transaction_id' => $validated['transaction_id'] ?? $payment->transaction_id,
            'paid_at' => strtolower($validated['status']) === 'paid' ? now() : $payment->paid_at,
            'metadata' => array_merge($payment->metadata ?? [], ['webhook' => $request->all()]),
        ]);

        return response()->json(['success' => true]);
    }
}
