<?php

namespace Tests\Unit\Services\AI;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\AI\SuggestionService;
use App\Services\AI\GeminiService;
use App\Services\AI\FallbackSuggestionService;
use App\DTOs\AI\SuggestionRequest;
use App\Models\Product\Product;
use Mockery;

class SuggestionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_uses_fallback_when_gemini_not_configured(): void
    {
        Product::factory()->count(3)->create();

        $geminiMock = Mockery::mock(GeminiService::class);
        $geminiMock->shouldReceive('isConfigured')->andReturn(false);

        $fallbackService = new FallbackSuggestionService();
        $service = new SuggestionService($geminiMock, $fallbackService);

        $request = new SuggestionRequest(
            goal: 'brindes para conferência',
            budget: 5000
        );

        $suggestions = $service->getSmartSuggestions($request);

        $this->assertIsArray($suggestions);
    }

    public function test_respects_budget_constraints(): void
    {
        Product::factory()->create(['price' => 1000, 'min_quantity' => 1]);
        Product::factory()->create(['price' => 2000, 'min_quantity' => 1]);
        Product::factory()->create(['price' => 10000, 'min_quantity' => 1]);

        $fallbackService = new FallbackSuggestionService();

        $request = new SuggestionRequest(
            goal: 'brindes para evento',
            budget: 3000
        );

        $suggestions = $fallbackService->getSuggestions($request);

        foreach ($suggestions as $suggestion) {
            $this->assertLessThanOrEqual(3000, $suggestion->estimatedTotal);
        }
    }

    public function test_includes_bundles_when_requested(): void
    {
        Product::factory()->count(5)->create(['price' => 500, 'min_quantity' => 1]);

        $fallbackService = new FallbackSuggestionService();

        $request = new SuggestionRequest(
            goal: 'brindes para evento',
            budget: 10000,
            includeBundles: true
        );

        $suggestions = $fallbackService->getSuggestions($request);

        $hasBundle = collect($suggestions)->contains(fn($s) => $s->type === 'bundle');
        $this->assertTrue($hasBundle);
    }
}
