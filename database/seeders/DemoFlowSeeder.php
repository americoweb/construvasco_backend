<?php

namespace Database\Seeders;

use App\Enums\AiGenerationStatus;
use App\Enums\AiGenerationType;
use App\Enums\ProjectContractPhase;
use App\Enums\ProjectRequestStatus;
use App\Enums\QuoteStatus;
use App\Enums\QuoteType;
use App\Models\AI\AiGeneration;
use App\Models\Construction\ProjectAssignment;
use App\Models\Construction\ProjectRequest;
use App\Models\Construction\Quote;
use App\Models\Project;
use App\Models\User;
use App\Services\Construction\QuoteService;
use App\Services\Credits\CreditService;
use Illuminate\Database\Seeder;

/**
 * Dados demo: estúdio com mockups estáticos, pedidos em vários estados, projecto activo.
 */
class DemoFlowSeeder extends Seeder
{
    /** @var list<string> */
    private const DEMO_FACADE_FILES = [
        'demo-fachada-1.png',
        'demo-fachada-2.png',
        'demo-fachada-3.png',
        'demo-fachada-4.png',
    ];

    public function run(): void
    {
        if (! session('tenant_id')) {
            session(['tenant_id' => 1]);
        }

        $cliente = User::where('identifier', 'cliente@construvasco.co.mz')->first();
        $gestor = User::where('identifier', 'gestor@construvasco.co.mz')->first();
        $tecnico = User::where('identifier', 'tecnico@construvasco.co.mz')->first();

        if (! $cliente || ! $gestor) {
            return;
        }

        app(CreditService::class)->grantInitialCredits($cliente);

        $studioRequest = ProjectRequest::firstOrCreate(
            ['reference_code' => 'DEMO-PED-STUDIO'],
            [
                'user_id' => $cliente->id,
                'project_type' => 'residencial',
                'tipologia' => 't4',
                'title' => 'Moradia T4 — Estúdio demo',
                'description' => 'Pedido submetido com mockup aprovado para demonstração do estúdio.',
                'localizacao' => 'Maputo, Moçambique',
                'area_m2' => 220,
                'num_pisos' => 2,
                'status' => ProjectRequestStatus::Submitted,
                'submitted_at' => now()->subDays(3),
                'briefing_data' => [
                    'estilo_arquitectonico' => 'contemporâneo tropical',
                    'paleta_acabamento' => 'terracota, branco e madeira',
                    'programa' => 'sala, cozinha, 4 quartos, varanda',
                ],
            ]
        );

        $this->seedDemoFacadeGallery($studioRequest, $cliente, approvedIndex: 1);

        $generatedRequest = ProjectRequest::firstOrCreate(
            ['reference_code' => 'DEMO-PED-GERADO'],
            [
                'user_id' => $cliente->id,
                'project_type' => 'residencial',
                'tipologia' => 't3',
                'title' => 'Moradia T3 — Mockups gerados (demo)',
                'description' => 'Pedido recente com galeria de fachadas geradas para demonstração.',
                'localizacao' => 'Maputo, Moçambique',
                'area_m2' => 150,
                'num_pisos' => 2,
                'status' => ProjectRequestStatus::Submitted,
                'submitted_at' => now()->subHours(6),
                'briefing_data' => [
                    'estilo_arquitectonico' => 'moderno',
                    'paleta_acabamento' => 'branco, cinza e madeira clara',
                    'programa' => 'sala, cozinha, 3 quartos, varanda',
                    'area_m2' => 150,
                    'num_pisos' => 2,
                    'localizacao' => 'Maputo, Moçambique',
                ],
            ]
        );

        $this->seedDemoFacadeGallery($generatedRequest, $cliente, approvedIndex: 2);

        $pending = ProjectRequest::firstOrCreate(
            ['reference_code' => 'DEMO-PED-001'],
            [
                'user_id' => $cliente->id,
                'project_type' => 'residencial',
                'tipologia' => 't3',
                'title' => 'Moradia T3 — Orçamento pendente',
                'description' => 'Pedido de demonstração com orçamento de arquitectura enviado.',
                'localizacao' => 'Maputo, Moçambique',
                'status' => ProjectRequestStatus::Quoted,
                'submitted_at' => now()->subDays(2),
                'briefing_data' => ['demo' => true],
            ]
        );

        Quote::firstOrCreate(
            [
                'project_request_id' => $pending->id,
                'quote_type' => QuoteType::Architecture,
                'status' => QuoteStatus::Sent,
            ],
            [
                'created_by_user_id' => $gestor->id,
                'total_amount_mt' => 1250000,
                'delivery_days' => 90,
                'conditions' => 'Pagamento em 3 fases. Materiais não incluídos.',
                'sent_at' => now()->subDay(),
            ]
        );

        $acceptedRequest = ProjectRequest::firstOrCreate(
            ['reference_code' => 'DEMO-PED-002'],
            [
                'user_id' => $cliente->id,
                'project_type' => 'comercial',
                'tipologia' => 'loja',
                'title' => 'Loja comercial — Demo aceite',
                'description' => 'Projecto em fase de arquitectura com técnico atribuído.',
                'localizacao' => 'Matola',
                'status' => ProjectRequestStatus::Quoted,
                'submitted_at' => now()->subDays(10),
            ]
        );

        $acceptedQuote = Quote::firstOrCreate(
            [
                'project_request_id' => $acceptedRequest->id,
                'quote_type' => QuoteType::Architecture,
            ],
            [
                'created_by_user_id' => $gestor->id,
                'status' => QuoteStatus::Sent,
                'total_amount_mt' => 850000,
                'delivery_days' => 60,
                'conditions' => 'Demo — projecto em arquitectura.',
                'sent_at' => now()->subDays(8),
            ]
        );

        $projectExists = Project::withoutGlobalScopes()
            ->where('project_request_id', $acceptedRequest->id)
            ->exists();

        if (! $projectExists) {
            if ($acceptedQuote->status !== QuoteStatus::Sent) {
                $acceptedQuote->update([
                    'status' => QuoteStatus::Sent,
                    'responded_at' => null,
                ]);
                $acceptedQuote->refresh();
            }
            app(QuoteService::class)->accept($acceptedQuote, $cliente);
        }

        $project = Project::withoutGlobalScopes()
            ->where('project_request_id', $acceptedRequest->id)
            ->first();

        if ($project && $tecnico) {
            ProjectAssignment::firstOrCreate(
                [
                    'project_id' => $project->id,
                    'assigned_to' => $tecnico->id,
                    'assignment_role' => 'main',
                ],
                [
                    'assigned_by' => $gestor->id,
                    'role' => 'technician',
                    'status' => 'active',
                    'assigned_at' => now(),
                ]
            );

            $project->update([
                'contract_phase' => ProjectContractPhase::Architecture,
            ]);
        }
    }

    /**
     * Galeria de fachadas demo: uma aprovada (completed), restantes superseded.
     */
    private function seedDemoFacadeGallery(
        ProjectRequest $request,
        User $cliente,
        int $approvedIndex = 1,
    ): void {
        AiGeneration::where('project_request_id', $request->id)->delete();

        $approvedGen = null;

        foreach (self::DEMO_FACADE_FILES as $index => $filename) {
            $slot = $index + 1;
            $paths = $this->demoImagePaths($filename);
            $isApproved = $slot === $approvedIndex;

            $gen = AiGeneration::create([
                'project_request_id' => $request->id,
                'user_id' => $cliente->id,
                'type' => AiGenerationType::FacadeRender,
                'prompt' => "Render de fachada demo {$slot} — Moradia Maputo",
                'status' => $isApproved ? AiGenerationStatus::Completed : AiGenerationStatus::Superseded,
                'image_path' => $paths['image_path'],
                'image_url' => $paths['image_url'],
                'credits_consumed' => 0,
                'provider' => 'demo',
            ]);

            if ($isApproved) {
                $approvedGen = $gen;
            }
        }

        if ($approvedGen) {
            $request->update(['approved_ai_generation_id' => $approvedGen->id]);
        }
    }

    /** @return array{image_path: string, image_url: string} */
    private function demoImagePaths(string $filename): array
    {
        $path = 'renders/demo/' . $filename;

        return [
            'image_path' => $path,
            'image_url' => '/storage/' . $path,
        ];
    }
}
