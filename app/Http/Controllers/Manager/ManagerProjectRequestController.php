<?php

namespace App\Http\Controllers\Manager;

use App\Constants\NotificationTypes;
use App\Enums\ProjectRequestStatus;
use App\Enums\QuoteStatus;
use App\Enums\QuoteType;
use App\Mail\QuoteAvailableMail;
use App\Http\Controllers\Controller;
use App\Http\Resources\ManagerProjectRequestDetailResource;
use App\Http\Requests\BriefingDataRules;
use App\Models\Construction\ProjectDocument;
use App\Models\Construction\ProjectRequest;
use App\Models\Construction\Quote;
use App\Models\Project;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use App\Services\Construction\QuoteService;
use App\Services\Mail\EmailDispatcher;
use App\Services\Notifications\NotificationService;
use App\Services\Storage\FileStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ManagerProjectRequestController extends Controller
{
    public function __construct(
        private NotificationService $notifications,
        private QuoteService $quotes,
        private EmailDispatcher $emails,
    ) {}

    public function index(Request $request): JsonResponse
    {
        // TODO multi-tenant: filtrar por tenant_id do utilizador autenticado quando multi-tenant estiver activo.
        $query = ProjectRequest::with('user')->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate(20));
    }

    /**
     * Registar pedido em nome de um cliente (telefone, email, WhatsApp).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(array_merge($this->briefingRules(true), [
            'user_id' => 'required|exists:users,id',
            'submit' => 'nullable|boolean',
        ]));

        $client = User::findOrFail((int) $validated['user_id']);
        $submit = (bool) ($validated['submit'] ?? true);
        unset($validated['user_id'], $validated['submit']);

        $item = ProjectRequest::create(array_merge($validated, [
            'user_id' => $client->id,
            'reference_code' => $this->nextReference(),
            'status' => $submit ? ProjectRequestStatus::Submitted : ProjectRequestStatus::Draft,
            'submitted_at' => $submit ? now() : null,
        ]));

        if ($submit) {
            $this->notifications->notify($client, NotificationTypes::REQUEST_SUBMITTED, [
                'title' => 'Pedido registado',
                'message' => "O pedido {$item->reference_code} foi registado pela equipa Construvasco.",
                'reference_type' => ProjectRequest::class,
                'reference_id' => $item->id,
            ]);
        }

        return response()->json(['data' => $item->load('user')], 201);
    }

    public function show(int $id): JsonResponse
    {
        $item = ProjectRequest::with([
            'user',
            'quotes' => fn ($q) => $q->latest(),
            'documents',
            'approvedAiGeneration',
        ])->findOrFail($id);

        return response()->json(['data' => new ManagerProjectRequestDetailResource($item)]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = ProjectRequest::findOrFail($id);
        $item->update($request->validate($this->briefingRules(false)));

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
            'total_amount_mt' => 'required|numeric|gt:0',
            'delivery_days' => 'required|integer|min:1',
            'conditions' => 'nullable|string|max:5000',
            'valid_until' => 'nullable|date|after_or_equal:today',
            'breakdown' => 'nullable|array',
            'project_template_id' => 'nullable|exists:project_templates,id',
            'quote_type' => 'nullable|in:architecture,construction',
        ]);

        $quoteType = QuoteType::from($validated['quote_type'] ?? QuoteType::Architecture->value);
        unset($validated['quote_type']);

        $expiresAt = $validated['valid_until'] ?? now()->addDays(30)->toDateString();
        unset($validated['valid_until']);

        if ($quoteType === QuoteType::Construction) {
            $project = Project::withoutGlobalScopes()
                ->where('project_request_id', $item->id)
                ->where('contract_phase', \App\Enums\ProjectContractPhase::ExecutionQuote)
                ->latest('id')
                ->first();
            if (! $project) {
                throw ValidationException::withMessages([
                    'quote_type' => ['O projecto deve estar à espera de orçamento de obra.'],
                ]);
            }
        } else {
            $this->quotes->assertRequestAllowsQuote($item);
        }
        $this->quotes->assertCanCreateQuote($item, $quoteType);

        $quote = Quote::create(array_merge($validated, [
            'project_request_id' => $item->id,
            'quote_type' => $quoteType,
            'created_by_user_id' => $request->user()->id,
            'status' => QuoteStatus::Sent,
            'sent_at' => now(),
            'expires_at' => $expiresAt,
        ]));

        $item->update(['status' => ProjectRequestStatus::Quoted]);

        activity('quotes')
            ->performedOn($quote)
            ->causedBy($request->user())
            ->withProperties([
                'quote_type' => $quoteType->value,
                'total_amount_mt' => $quote->total_amount_mt,
                'delivery_days' => $quote->delivery_days,
            ])
            ->log($quoteType === QuoteType::Construction
                ? 'Gestor enviou orçamento de obra ao cliente'
                : 'Orçamento de arquitectura enviado ao cliente');

        $this->notifications->notify($item->user, NotificationTypes::QUOTE_RECEIVED, [
            'title' => 'Orçamento disponível',
            'message' => 'Recebeu um orçamento para o seu pedido.',
            'reference_type' => Quote::class,
            'reference_id' => $quote->id,
        ]);

        $this->emails->dispatchIdempotent(
            'quote_available',
            $item->user,
            new QuoteAvailableMail($quote->load('projectRequest')),
            Quote::class,
            $quote->id,
        );

        return response()->json(['data' => $quote], 201);
    }

    public function uploadDocument(Request $request, int $id, FileStorageService $storage): JsonResponse
    {
        $item = ProjectRequest::findOrFail($id);

        $request->validate([
            'file' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,webp,doc,docx',
            'document_type' => 'nullable|string|max:120',
        ]);

        $file = $request->file('file');
        $storage->validateFile(
            $file,
            ['application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            10240
        );
        $stored = $storage->storePublicFile($file, "project-requests/{$item->id}");

        $doc = ProjectDocument::create([
            'project_request_id' => $item->id,
            'document_type' => $request->input('document_type', 'referencia'),
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

    /** @return array<string, string> */
    private function briefingRules(bool $requireTitle): array
    {
        $titleRule = ($requireTitle ? 'required' : 'sometimes') . '|string|max:255';

        return array_merge([
            'title' => $titleRule,
            'description' => 'nullable|string',
            'project_type' => 'nullable|string|max:120',
            'tipologia' => 'nullable|string|max:120',
            'area_m2' => 'nullable|numeric|min:0',
            'largura_m' => 'nullable|numeric|min:0',
            'comprimento_m' => 'nullable|numeric|min:0',
            'num_pisos' => 'nullable|integer|min:0|max:20',
            'num_quartos' => 'nullable|integer|min:0|max:50',
            'orcamento_estimado_mt' => 'nullable|numeric|min:0',
            'prazo_desejado' => 'nullable|date',
            'localizacao' => 'nullable|string|max:255',
            'estilo_arquitectonico' => 'nullable|string|max:255',
            'paleta_acabamento' => 'nullable|string|max:255',
            'zona_prioritaria' => 'nullable|string|max:255',
            'whatsapp' => 'nullable|string|max:30',
            'observacoes' => 'nullable|string',
        ], BriefingDataRules::rules());
    }

    private function nextReference(): string
    {
        $year = now()->year;
        $seq = ProjectRequest::whereYear('created_at', $year)->count() + 1;

        return sprintf('CV-%d-%04d', $year, $seq);
    }
}
