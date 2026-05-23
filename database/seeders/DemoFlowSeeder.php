<?php

namespace Database\Seeders;

use App\Enums\ProjectRequestStatus;
use App\Enums\QuoteStatus;
use App\Models\Construction\ProjectRequest;
use App\Models\Construction\Quote;
use App\Models\Project;
use App\Models\User;
use App\Services\Construction\QuoteService;
use Illuminate\Database\Seeder;

/**
 * Dados demo para smoke test MVP: pedido → orçamento (pendente) + projecto já aceite.
 */
class DemoFlowSeeder extends Seeder
{
    public function run(): void
    {
        // Contexto de tenant obrigatório em CLI (evita auth('api')/JWT no TenantScope).
        if (! session('tenant_id')) {
            session(['tenant_id' => 1]);
        }

        $cliente = User::where('identifier', 'cliente@construvasco.co.mz')->first();
        $gestor = User::where('identifier', 'gestor@construvasco.co.mz')->first();

        if (! $cliente || ! $gestor) {
            return;
        }

        $pending = ProjectRequest::firstOrCreate(
            ['reference_code' => 'DEMO-PED-001'],
            [
                'user_id' => $cliente->id,
                'project_type' => 'residencial',
                'tipologia' => 't3',
                'title' => 'Moradia T3 — Demo MVP',
                'description' => 'Pedido de demonstração para testar orçamento no portal cliente.',
                'localizacao' => 'Maputo, Moçambique',
                'status' => ProjectRequestStatus::Quoted,
                'submitted_at' => now()->subDays(2),
                'briefing_data' => ['demo' => true],
            ]
        );

        Quote::firstOrCreate(
            ['project_request_id' => $pending->id, 'status' => QuoteStatus::Sent],
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
                'description' => 'Pedido já convertido em projecto para listagens.',
                'localizacao' => 'Matola',
                'status' => ProjectRequestStatus::Quoted,
                'submitted_at' => now()->subDays(10),
            ]
        );

        $acceptedQuote = Quote::firstOrCreate(
            [
                'project_request_id' => $acceptedRequest->id,
                'status' => QuoteStatus::Accepted,
            ],
            [
                'created_by_user_id' => $gestor->id,
                'total_amount_mt' => 850000,
                'delivery_days' => 60,
                'conditions' => 'Demo — projecto já iniciado.',
                'sent_at' => now()->subDays(8),
                'responded_at' => now()->subDays(7),
            ]
        );

        $projectExists = Project::withoutGlobalScopes()
            ->where('project_request_id', $acceptedRequest->id)
            ->exists();

        if (! $projectExists) {
            app(QuoteService::class)->accept($acceptedQuote, $cliente);
        }
    }
}
