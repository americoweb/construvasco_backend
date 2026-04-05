<?php

namespace Tests\Feature\AI;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Product\Product;
use App\Services\AI\GeminiService;
use Mockery;

class SuggestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_smart_suggestions(): void
    {
        // Create test products
        Product::factory()->count(5)->create();

        $requestData = [
            'goal' => 'brindes para evento de empresa com 50 pessoas',
            'budget' => 10000,
        ];

        $response = $this->postJson('/api/v1/ai/suggestions', $requestData);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'suggestions' => [
                             '*' => [
                                 'type',
                                 'title',
                                 'products',
                                 'estimated_total',
                                 'cta',
                             ]
                         ],
                         'total_suggestions',
                         'has_bundles',
                     ],
                     'goal',
                     'budget',
                 ]);
    }

    public function test_validates_minimum_budget(): void
    {
        $requestData = [
            'goal' => 'brindes para evento',
            'budget' => 100, // Below minimum
        ];

        $response = $this->postJson('/api/v1/ai/suggestions', $requestData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['budget']);
    }

    public function test_validates_goal_length(): void
    {
        $requestData = [
            'goal' => 'short', // Too short
            'budget' => 5000,
        ];

        $response = $this->postJson('/api/v1/ai/suggestions', $requestData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['goal']);
    }

    public function test_health_check_returns_status(): void
    {
        $response = $this->getJson('/api/v1/ai/health');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'gemini_configured',
                     'timestamp',
                 ]);
    }

    public function test_can_refine_design(): void
    {
        $this->mock(GeminiService::class, function ($mock) {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('generateText')->andReturn('Novo prompt refinado com logo moderno');
        });

        $requestData = [
            'current_prompt' => 'Design simples com logo',
            'feedback' => 'Quero cores mais vibrantes e logo maior',
        ];

        $response = $this->postJson('/api/v1/ai/refine', $requestData);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'refined_prompt',
                     ],
                     'message',
                 ]);
    }
}
