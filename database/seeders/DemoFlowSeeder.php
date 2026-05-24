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
 * Dados demo Bloco 1: estúdio + arquitectura + projecto activo (sem fase obra).
 */
class DemoFlowSeeder extends Seeder
{
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
                'description' => 'Pedido submetido com mockup aprovado para testes do estúdio.',
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

        $mockup = AiGeneration::firstOrCreate(
            [
                'project_request_id' => $studioRequest->id,
                'user_id' => $cliente->id,
                'type' => AiGenerationType::FacadeRender,
            ],
            [
                'prompt' => 'Render de fachada — demo Bloco 1',
                'status' => AiGenerationStatus::Completed,
                'image_path' => 'renders/demo_mockup.png',
                'image_url' => '/storage/renders/demo_mockup.png',
                'credits_consumed' => 0,
                'provider' => 'gemini',
            ]
        );

        $studioRequest->update(['approved_ai_generation_id' => $mockup->id]);

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
}
