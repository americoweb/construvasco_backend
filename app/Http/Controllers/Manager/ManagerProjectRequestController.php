<?php

namespace App\Http\Controllers\Manager;

use App\Constants\NotificationTypes;
use App\Enums\ProjectRequestStatus;
use App\Enums\QuoteStatus;
use App\Http\Controllers\Controller;
use App\Models\Construction\ProjectRequest;
use App\Models\Construction\Quote;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ManagerProjectRequestController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function index(Request $request): JsonResponse
    {
        $query = ProjectRequest::with('user')->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate(20));
    }

    public function show(int $id): JsonResponse
    {
        $item = ProjectRequest::with(['user', 'quotes', 'aiGenerations'])->findOrFail($id);

        return response()->json(['data' => $item]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = ProjectRequest::findOrFail($id);
        $item->update($request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'localizacao' => 'nullable|string',
            'observacoes' => 'nullable|string',
        ]));

        return response()->json(['data' => $item->fresh()]);
    }

    public function approve(int $id): JsonResponse
    {
        $item = ProjectRequest::findOrFail($id);
        $item->update([
            'status' => ProjectRequestStatus::UnderReview,
            'reviewed_at' => now(),
        ]);

        return response()->json(['data' => $item]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $item = ProjectRequest::findOrFail($id);
        $item->update(['status' => ProjectRequestStatus::Rejected]);

        $this->notifications->notify($item->user, NotificationTypes::QUOTE_REJECTED, [
            'title' => 'Pedido recusado',
            'message' => $request->input('reason', 'O pedido foi recusado pela equipa.'),
        ]);

        return response()->json(['data' => $item]);
    }

    public function storeQuote(Request $request, int $id): JsonResponse
    {
        $item = ProjectRequest::findOrFail($id);
        $validated = $request->validate([
            'total_amount_mt' => 'required|numeric|min:0',
            'delivery_days' => 'nullable|integer|min:1',
            'conditions' => 'nullable|string',
            'breakdown' => 'nullable|array',
            'project_template_id' => 'nullable|exists:project_templates,id',
        ]);

        $quote = Quote::create(array_merge($validated, [
            'project_request_id' => $item->id,
            'created_by_user_id' => $request->user()->id,
            'status' => QuoteStatus::Sent,
            'sent_at' => now(),
        ]));

        $item->update(['status' => ProjectRequestStatus::Quoted]);

        $this->notifications->notify($item->user, NotificationTypes::QUOTE_RECEIVED, [
            'title' => 'Orçamento disponível',
            'message' => 'Recebeu um orçamento para o seu pedido.',
            'reference_type' => Quote::class,
            'reference_id' => $quote->id,
        ]);

        return response()->json(['data' => $quote], 201);
    }
}
