<?php

namespace App\Http\Controllers\Customer;

use App\Constants\NotificationTypes;
use App\Enums\ProjectRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\BriefingDataRules;
use App\Models\AI\AiGeneration;
use App\Models\Construction\ProjectDocument;
use App\Models\Construction\ProjectRequest;
use App\Services\Notifications\NotificationService;
use App\Services\Storage\FileStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerProjectRequestController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function index(Request $request): JsonResponse
    {
        $items = ProjectRequest::where('user_id', $request->user()->id)
            ->with('quotes')
            ->latest()
            ->paginate(15);

        return response()->json($items);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request, true);
        $data['user_id'] = $request->user()->id;
        $data['reference_code'] = $this->nextReference();
        $data['status'] = ProjectRequestStatus::Draft;

        $item = ProjectRequest::create($data);

        return response()->json(['data' => $item], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $item = ProjectRequest::where('user_id', $request->user()->id)
            ->with(['quotes', 'aiGenerations', 'documents'])
            ->findOrFail($id);

        $this->authorize('view', $item);

        return response()->json(['data' => $item]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = ProjectRequest::where('user_id', $request->user()->id)->findOrFail($id);
        abort_unless($item->status === ProjectRequestStatus::Draft, 422, 'Só rascunhos podem ser editados.');

        $item->update($this->validated($request));

        return response()->json(['data' => $item->fresh()]);
    }

    public function submit(Request $request, int $id): JsonResponse
    {
        $item = ProjectRequest::where('user_id', $request->user()->id)->findOrFail($id);
        $item->update([
            'status' => ProjectRequestStatus::Submitted,
            'submitted_at' => now(),
        ]);

        $this->notifications->notify($request->user(), NotificationTypes::REQUEST_SUBMITTED, [
            'title' => 'Pedido submetido',
            'message' => "O pedido {$item->reference_code} foi enviado para análise.",
            'reference_type' => ProjectRequest::class,
            'reference_id' => $item->id,
        ]);

        return response()->json(['data' => $item->fresh()]);
    }

    public function approveAiGeneration(Request $request, int $id, int $generationId): JsonResponse
    {
        $item = ProjectRequest::where('user_id', $request->user()->id)->findOrFail($id);
        $gen = AiGeneration::where('user_id', $request->user()->id)->findOrFail($generationId);
        $item->update(['approved_ai_generation_id' => $gen->id]);

        return response()->json(['data' => $item->fresh(['approvedAiGeneration'])]);
    }

    public function documents(Request $request, int $id): JsonResponse
    {
        $item = ProjectRequest::where('user_id', $request->user()->id)->findOrFail($id);
        $this->authorize('view', $item);

        return response()->json(['data' => $item->documents()->latest()->get()]);
    }

    public function uploadDocument(Request $request, int $id, FileStorageService $storage): JsonResponse
    {
        $item = ProjectRequest::where('user_id', $request->user()->id)->findOrFail($id);
        $this->authorize('update', $item);

        $request->validate([
            'file' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx',
            'document_type' => 'nullable|string|max:120',
        ]);

        $file = $request->file('file');
        $storage->validateFile($file, ['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'], 10240);
        $stored = $storage->storePublicFile($file, "project-requests/{$item->id}");

        $doc = ProjectDocument::create([
            'project_request_id' => $item->id,
            'document_type' => $request->input('document_type', 'briefing'),
            'file_name' => basename($stored['path']),
            'original_name' => $stored['original_name'],
            'file_path' => $stored['path'],
            'disk' => $stored['disk'],
            'mime_type' => $stored['mime'],
            'size_bytes' => $stored['size'],
            'uploaded_by' => $request->user()->id,
        ]);

        return response()->json(['data' => $doc], 201);
    }

    public function destroyDocument(Request $request, int $documentId, FileStorageService $storage): JsonResponse
    {
        $doc = ProjectDocument::findOrFail($documentId);
        $item = ProjectRequest::where('user_id', $request->user()->id)
            ->where('id', $doc->project_request_id)
            ->firstOrFail();
        $this->authorize('update', $item);

        $storage->deleteFile($doc->file_path, $doc->disk ?? 'public');
        $doc->delete();

        return response()->json(['message' => 'Documento removido.']);
    }

    private function validated(Request $request, bool $requireTitle = false): array
    {
        return $request->validate([
            'title' => ($requireTitle ? 'required' : 'sometimes') . '|string|max:255',
            'description' => 'nullable|string',
            'project_type' => 'nullable|string|max:120',
            'tipologia' => 'nullable|string|max:120',
            'area_m2' => 'nullable|numeric',
            'largura_m' => 'nullable|numeric',
            'comprimento_m' => 'nullable|numeric',
            'num_pisos' => 'nullable|integer|min:0|max:20',
            'num_quartos' => 'nullable|integer|min:0|max:50',
            'orcamento_estimado_mt' => 'nullable|numeric',
            'prazo_desejado' => 'nullable|date',
            'localizacao' => 'nullable|string|max:255',
            'estilo_arquitectonico' => 'nullable|string|max:255',
            'paleta_acabamento' => 'nullable|string|max:255',
            'zona_prioritaria' => 'nullable|string|max:255',
            'whatsapp' => 'nullable|string|max:30',
            'observacoes' => 'nullable|string',
            'reference_files' => 'nullable|array',
        ], BriefingDataRules::rules());
    }

    private function nextReference(): string
    {
        $year = now()->year;
        $seq = ProjectRequest::whereYear('created_at', $year)->count() + 1;

        return sprintf('CV-%d-%04d', $year, $seq);
    }
}
